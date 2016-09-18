<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '2.2.8';
	const GEOAPI_NAME = 'ip-geo-api';
	const PLUGIN_NAME = 'ip-geo-block';
	const PLUGIN_SLUG = 'ip-geo-block'; // fallback for ip-geo-api 1.1.3
	const OPTION_NAME = 'ip_geo_block_settings';
	const CACHE_NAME  = 'ip_geo_block_cache';
	const CRON_NAME   = 'ip_geo_block_cron';

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = NULL;

	// Globals in this class
	public static $wp_path;
	private $pagenow = NULL;
	private $request_uri = NULL;
	private $target_type = NULL;
	private $remote_addr = NULL;

	/**
	 * Initialize the plugin
	 * 
	 */
	private function __construct() {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php';
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php';

		$settings = self::get_option();
		$priority = $settings['priority'];
		$validate = $settings['validation'];

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] )
			add_action( self::CRON_NAME, array( $this, 'update_database' ) );

		// check the package version and upgrade if needed
		if ( version_compare( $settings['version'], self::VERSION ) < 0 || $settings['matching_rule'] < 0 )
			add_action( 'init', 'ip_geo_block_activate', $priority );

		// normalize requested uri and page
		$this->request_uri = strtolower( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		$this->request_uri = preg_replace( array( '!\.+/!', '!//+!' ), '/', $this->request_uri );
		$this->pagenow = ! empty( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : basename( $_SERVER['SCRIPT_NAME'] );

		// setup the content folders
		self::$wp_path = array( 'home' => IP_Geo_Block_Util::unslashit( parse_url( site_url(), PHP_URL_PATH ) ) ); // @since 2.6.0
		$len = strlen( self::$wp_path['home'] );
		$list = array(
			'admin'     => 'admin_url',          // @since 2.6.0
			'plugins'   => 'plugins_url',        // @since 2.6.0
			'themes'    => 'get_theme_root_uri', // @since 1.5.0
		);

		// analize the validation target (admin|plugins|themes|includes)
		foreach ( $list as $key => $val ) {
			self::$wp_path[ $key ] = IP_Geo_Block_Util::slashit( substr( parse_url( call_user_func( $val ), PHP_URL_PATH ), $len ) );
			if ( ! $this->target_type && FALSE !== strpos( $this->request_uri, self::$wp_path[ $key ] ) )
				$this->target_type = $key;
		}

		// validate request to WordPress core files
		$list = array(
			'wp-comments-post.php' => 'comment',
			'wp-trackback.php'     => 'comment',
			'xmlrpc.php'           => 'xmlrpc',
			'wp-login.php'         => 'login',
			'wp-signup.php'        => 'login',
		);

		// wp-admin, wp-includes, wp-content/(plugins|themes|language|uploads)
		if ( $this->target_type ) {
			if ( 'admin' !== $this->target_type )
				add_action( 'init', array( $this, 'validate_direct' ), $priority );
			else // 'widget_init' for admin dashboard
				add_action( 'wp_loaded', array( $this, 'validate_admin' ), $priority );
		}

		// analize core validation target (comment|xmlrpc|login|public)
		elseif ( isset( $list[ $this->pagenow ] ) ) {
			if ( $validate[ $list[ $this->pagenow ] ] )
				add_action( 'init', array( $this, 'validate_' . $list[ $this->pagenow ] ), $priority );
		}

		else {
			// message text on comment form
			if ( $settings['comment']['pos'] ) {
				$key = ( 1 === (int)$settings['comment']['pos'] ? '_top' : '' );
				add_action( 'comment_form' . $key, array( $this, 'comment_form_message' ) );
			}

			if ( $validate['comment'] ) {
				// bbPress: prevent creating topic/relpy and rendering form
				add_action( 'bbp_post_request_bbp-new-topic', array( $this, 'validate_comment' ), $priority );
				add_action( 'bbp_post_request_bbp-new-reply', array( $this, 'validate_comment' ), $priority );
				add_filter( 'bbp_current_user_can_access_create_topic_form', array( $this, 'validate_front' ), $priority );
				add_filter( 'bbp_current_user_can_access_create_reply_form', array( $this, 'validate_front' ), $priority );
			}

			if ( $validate['login'] ) {
				// for hide/rename wp-login.php, BuddyPress: prevent registration and rendering form
				add_action( 'login_init', array( $this, 'validate_login' ), $priority );
				add_action( 'bp_core_screen_signup',  array( $this, 'validate_login' ), $priority );
				add_action( 'bp_signup_pre_validate', array( $this, 'validate_login' ), $priority );
			}
		}

		// force to change the redirect URL at logout to remove nonce, embed a nonce into pages
		add_filter( 'wp_redirect', array( $this, 'logout_redirect' ), 20, 2 ); // logout_redirect @4.2
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_nonce' ), $priority );
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Optional values handlings.
	 *
	 */
	public static function get_default() {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		return IP_Geo_Block_Opts::get_default();
	}

	// get optional values from wp options
	public static function get_option() {
		return FALSE !== ( $option = get_option( self::OPTION_NAME ) ) ? $option : self::get_default();
	}

	/**
	 * Register and enqueue a nonce with a specific JavaScript.
	 *
	 */
	public static function enqueue_nonce() {
		if ( is_user_logged_in() ) {
			$handle = self::PLUGIN_NAME . '-auth-nonce';
			$script = plugins_url(
				! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
				'admin/js/authenticate.min.js' : 'admin/js/authenticate.js', IP_GEO_BLOCK_BASE
			);
			$nonce = array( 'nonce' => IP_Geo_Block_Util::create_nonce( $handle ) ) + self::$wp_path;
			wp_enqueue_script( $handle, $script, array( 'jquery' ), self::VERSION );
			wp_localize_script( $handle, 'IP_GEO_BLOCK_AUTH', $nonce );
		}
	}

	/**
	 * Remove the redirecting URL at logout not to be blocked by WP-ZEP.
	 *
	 */
	public function logout_redirect( $uri ) {
		if ( FALSE !== stripos( $uri, self::$wp_path['admin'] ) &&
		     isset( $_REQUEST['action'] ) && 'logout' === $_REQUEST['action'] )
			return esc_url_raw( add_query_arg( array( 'loggedout' => 'true' ), wp_login_url() ) );
		else
			return $uri;
	}

	/**
	 * Setup the http header.
	 *
	 * @see http://codex.wordpress.org/Function_Reference/wp_remote_get
	 */
	public static function get_request_headers( $settings ) {
		return apply_filters( self::PLUGIN_NAME . '-headers', array(
			'timeout' => (int)$settings['timeout'],
			'user-agent' => ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'WordPress/' . $GLOBALS['wp_version'] . ', ' . self::PLUGIN_NAME . ' ' . self::VERSION,
		) );
	}

	/**
	 * Get current IP address
	 *
	 */
	public static function get_ip_address() {
		return apply_filters( self::PLUGIN_NAME . '-ip-addr', $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Render a text message at the comment form.
	 *
	 */
	public function comment_form_message() {
		$settings = self::get_option();
		echo '<p id="', self::PLUGIN_NAME, '-msg">', IP_Geo_Block_Util::kses( $settings['comment']['msg'] ), '</p>', "\n";
	}

	/**
	 * Build a validation result for the current user.
	 *
	 */
	private static function make_validation( $ip, $result ) {
		return array_merge( array(
			'ip' => $ip,
			'auth' => get_current_user_id(), // unavailale before 'init' hook
			'code' => 'ZZ', // may be overwritten with $result
		), $result );
	}

	/**
	 * Get geolocation and country code from an ip address.
	 *
	 * @param string $ip IP address / default: $_SERVER['REMOTE_ADDR']
	 * @param array  $providers list of providers / ex: array( 'ipinfo.io' )
	 * @param string $callback geolocation function / ex: 'get_location'
	 * @return array $result country code and so on
	 */
	public static function get_geolocation( $ip = NULL, $providers = array(), $callback = 'get_country' ) {
		$result = self::_get_geolocation( $ip ? $ip : self::get_ip_address(), self::get_option(), $providers, $callback );

		if ( ! empty( $result['countryCode'] ) )
			$result['code'] = $result['countryCode'];

		return $result;
	}

	/**
	 * API for internal.
	 *
	 */
	private static function _get_geolocation( $ip, $settings, $providers = array(), $callback = 'get_country' ) {
		// make valid providers list
		if ( empty( $providers ) )
			$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// set arguments for wp_remote_get()
		$args = self::get_request_headers( $settings );

		foreach ( $providers as $provider ) {
			$time = microtime( TRUE );
			if ( ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) ) &&
			     ( $code = $geo->$callback( $ip, $args ) ) ) {
				return self::make_validation( $ip, array(
					'time' => microtime( TRUE ) - $time,
					'provider' => $provider,
				) + ( is_array( $code ) ? $code : array( 'code' => $code ) ) );
			}
		}

		return self::make_validation( $ip, array( 'errorMessage' => 'unknown' ) );
	}

	/**
	 * Validate geolocation by country code.
	 *
	 */
	public static function validate_country( $hook, $validate, $settings, $block = TRUE ) {
		if ( $block && 0 === (int)$settings['matching_rule'] ) {
			// 'ZZ' will be blocked if it's not in the $list.
			if ( ( $list = $settings['white_list'] ) && FALSE === strpos( $list, $validate['code'] ) )
				return $validate + array( 'result' => 'blocked' ); // can't overwrite existing result
		}

		elseif( $block && 1 === (int)$settings['matching_rule'] ) {
			// 'ZZ' will NOT be blocked if it's not in the $list.
			if ( ( $list = $settings['black_list'] ) && FALSE !== strpos( $list, $validate['code'] ) )
				return $validate + array( 'result' => 'blocked' ); // can't overwrite existing result
		}

		return $validate + array( 'result' => 'passed' ); // can't overwrite existing result
	}

	/**
	 * Send response header with http status code and reason.
	 *
	 */
	public function send_response( $hook, $code ) {
		// prevent caching (WP Super Cache, W3TC, Wordfence, Comet Cache)
		if ( ! defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', TRUE );

		$code = (int   )apply_filters( self::PLUGIN_NAME . '-'.$hook.'-status', (int)$code );
		$mesg = (string)apply_filters( self::PLUGIN_NAME . '-'.$hook.'-reason', get_status_header_desc( $code ) );

		nocache_headers(); // nocache and response code

		switch ( (int)substr( (string)$code, 0, 1 ) ) {
		  case 2: // 2xx Success
			header( 'Refresh: 0; url=' . home_url(), TRUE, $code ); // @since 3.0
			exit;

		  case 3: // 3xx Redirection
			IP_Geo_Block_Util::redirect( 'http://blackhole.webpagetest.org/', $code );
			exit;

		  default: // 4xx Client Error, 5xx Server Error
			status_header( $code ); // @since 2.0.0

			if ( function_exists( 'trackback_response' ) )
				trackback_response( $code, IP_Geo_Block_Util::kses( $mesg ) ); // @since 0.71

			elseif ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				$hook = IP_Geo_Block_Util::is_user_logged_in();
				FALSE !== ( @include( get_stylesheet_directory() .'/'.$code.'.php' ) ) or // child  theme
				FALSE !== ( @include( get_template_directory()   .'/'.$code.'.php' ) ) or // parent theme
				wp_die( // get_dashboard_url() @since 3.1.0
					IP_Geo_Block_Util::kses( $mesg ) . ( $hook ? "\n<p><a href='" . esc_url( get_dashboard_url() ) . "'>&laquo; " . __( 'Dashboard' ) . "</a></p>" : '' ),
					'', array( 'response' => $code, 'back_link' => ! $hook )
				);
			}
			exit;
		}
	}

	/**
	 * Validate ip address.
	 *
	 * @param string $hook a name to identify action hook applied in this call.
	 * @param array $settings option settings
	 * @param boolean $die send http response and die if validation fails
	 */
	public function validate_ip( $hook, $settings, $block = TRUE, $die = TRUE, $auth = TRUE ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

		// set IP address to be validated
		$ips = array( self::get_ip_address() );

		// pick up all the IPs in HTTP_X_FORWARDED_FOR, HTTP_CLIENT_IP and etc.
		foreach ( explode( ',', $settings['validation']['proxy'] ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				foreach ( explode( ',', $_SERVER[ $var ] ) as $ip ) {
					if ( ! in_array( $ip = trim( $ip ), $ips, TRUE ) && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						array_unshift( $ips, $ip );
					}
				}
			}
		}

		// register auxiliary validation functions
		$var = self::PLUGIN_NAME . '-' . $hook;
		$auth and add_filter( $var, array( $this, 'check_auth' ), 9, 2 );
		$auth and add_filter( $var, array( $this, 'check_fail' ), 8, 2 );
		$settings['extra_ips'] = apply_filters( self::PLUGIN_NAME . '-extra-ips', $settings['extra_ips'], $hook );
		$settings['extra_ips']['white_list'] and add_filter( $var, array( $this, 'check_ips_white' ), 7, 2 );
		$settings['extra_ips']['black_list'] and add_filter( $var, array( $this, 'check_ips_black' ), 7, 2 );

		// make valid provider name list
		$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// apply custom filter for validation
		// @usage add_filter( 'ip-geo-block-$hook', 'my_validation', 10, 2 );
		// @param $validate = array(
		//     'ip'       => $ip,       /* validated ip address                */
		//     'auth'     => $auth,     /* authenticated or not                */
		//     'code'     => $code,     /* country code or reason of rejection */
		//     'result'   => $result,   /* 'passed', 'blocked'                 */
		// );
		foreach ( $ips as $this->remote_addr ) {
			$validate = self::_get_geolocation( $this->remote_addr, $settings, $providers );
			$validate = apply_filters( $var, $validate, $settings );

			// if no 'result' then validate ip address by country
			if ( empty( $validate['result'] ) )
				$validate = self::validate_country( $hook, $validate, $settings, $block );

			// if one of IPs is blocked then stop
			if ( 'passed' !== $validate['result'] )
				break;
		}

		// update cache
		IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings );

		// update statistics
		if ( $settings['save_statistics'] )
			IP_Geo_Block_Logs::update_stat( $hook, $validate, $settings );

		// record log (0:no, 1:blocked, 2:passed, 3:unauth, 4:auth, 5:all)
		$var = (int)apply_filters( self::PLUGIN_NAME . '-record-logs', $settings['validation']['reclogs'], $hook, $validate );
		$block = ( 'passed' !== $validate['result'] );
		if ( ( 1 === $var &&   $block ) || // blocked
		     ( 2 === $var && ! $block ) || // passed
		     ( 3 === $var && ! $validate['auth'] ) || // unauthenticated
		     ( 4 === $var &&   $validate['auth'] ) || // authenticated
		     ( 5 === $var ) ) { // all
			IP_Geo_Block_Logs::record_logs( $hook, $validate, $settings );
		}

		// send response code to refuse
		if ( $block && $die )
			$this->send_response( $hook, $settings['response_code'] );

		return $validate;
	}

	/**
	 * Validate at frontend.
	 *
	 */
	public function validate_front( $can_access = TRUE ) {
		$validate = $this->validate_ip( 'comment', self::get_option(), TRUE, FALSE );
		return ( 'passed' === $validate['result'] ? $can_access : FALSE );
	}

	/**
	 * Validate at comment.
	 *
	 */
	public function validate_comment( $comment = NULL ) {
		// check comment type if it comes form wp-includes/wp_new_comment()
		if ( ! is_array( $comment ) || in_array( $comment['comment_type'], array( 'trackback', 'pingback' ), TRUE ) )
			$this->validate_ip( 'comment', self::get_option() );

		return $comment;
	}

	/**
	 * Validate at xmlrpc.
	 *
	 */
	public function validate_xmlrpc() {
		$settings = self::get_option();

		if ( 2 === (int)$settings['validation']['xmlrpc'] ) // Completely close
			add_filter( self::PLUGIN_NAME . '-xmlrpc', array( $this, 'close_xmlrpc' ), 6, 2 );

		else // wp-includes/class-wp-xmlrpc-server.php @since 3.5.0
			add_filter( 'xmlrpc_login_error', array( $this, 'auth_fail' ), $settings['priority'] );

		$this->validate_ip( 'xmlrpc', $settings );
	}

	public function close_xmlrpc( $validate, $settings ) {
		return $validate + array( 'result' => 'closed' ); // can't overwrite existing result
	}

	/**
	 * Validate at login.
	 *
	 */
	public function validate_login() {
		// parse action
		$action = isset( $_GET['key'] ) ? 'resetpass' : (
			isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login'
		);

		if ( 'retrievepassword' === $action )
			$action = 'lostpassword';
		elseif ( 'rp' === $action )
			$action = 'resetpass';

		$settings = self::get_option();
		$actions = $settings['login_action'];

		// the same rule is applied to login / logout
		if ( ! empty( $actions['login'] ) )
			$actions += array( 'logout' => 1 );

		// wp-includes/pluggable.php @since 2.5.0
		add_action( 'wp_login_failed', array( $this, 'auth_fail' ), $settings['priority'] );

		// enables to skip validation of country at login/out except BuddyPress signup
		$this->validate_ip( 'login', $settings,
			! empty( $actions[ $action ] ) || 'bp_' === substr( current_filter(), 0, 3 )
		);
	}

	/**
	 * Validate at admin area.
	 *
	 */
	public function validate_admin() {
		$settings = self::get_option();
		$page   = isset( $_REQUEST['page'  ] ) ? $_REQUEST['page'  ] : NULL;
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : NULL;

		switch ( $this->pagenow ) {
		  case 'admin-ajax.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( 'wp_ajax_nopriv_'.$action );
			$type = (int)$settings['validation']['ajax'];
			break;

		  case 'admin-post.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( 'admin_post_nopriv' . ($action ? '_'.$action : '') );
			$type = (int)$settings['validation']['ajax'];
			break;

		  default:
			// if the request has no page and no action, skip WP-ZEP
			$zep = ( $page || $action ) ? TRUE : FALSE;
			$type = (int)$settings['validation']['admin'];
		}

		// setup WP-ZEP (2: WP-ZEP)
		if ( ( 2 & $type ) && $zep ) {
			// redirect if valid nonce in referer
			IP_Geo_Block_Util::trace_nonce( self::PLUGIN_NAME . '-auth-nonce' );

			// list of request with a specific query to bypass WP-ZEP
			$list = apply_filters( self::PLUGIN_NAME . '-bypass-admins', array(
				'wp-compression-test', // wp-admin/includes/template.php
				'upload-attachment', 'imgedit-preview', 'bp_avatar_upload', // pluploader won't fire an event in "Media Library"
				'jetpack', 'authorize', 'jetpack_modules', 'atd_settings', 'bulk-activate', 'bulk-deactivate', // jetpack page & action
			) );

			// combination with vulnerable keys should be prevented to bypass WP-ZEP
			$in_action = in_array( $action, $list, TRUE );
			$in_page   = in_array( $page,   $list, TRUE );
			if ( ( ( $action xor $page ) && ( ! $in_action and ! $in_page ) ) ||
			     ( ( $action and $page ) && ( ! $in_action or  ! $in_page ) ) )
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 5, 2 );
		}

		// register validation of malicious signature (except in the comment and post)
		if ( ! IP_Geo_Block_Util::is_user_logged_in() || ! in_array( $this->pagenow, array( 'comment.php', 'post.php' ), TRUE ) )
			add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 6, 2 );

		// validate country by IP address (1: Block by country)
		$this->validate_ip( 'admin', $settings, 1 & $type );
	}

	/**
	 * Validate at plugins/themes area.
	 *
	 */
	public function validate_direct() {
		$settings = self::get_option();
		$request = preg_quote( self::$wp_path[ $type = $this->target_type ], '/' );
		$module = in_array( $type, array( 'plugins', 'themes' ) ) ? '[^\?\&\/]*' : '[^\?\&]*';

		// wp-includes, wp-content/(plugins|themes|language|uploads)
		preg_match( "/($request)($module)/", $this->request_uri, $module );
		$request = empty( $module[2] ) ? $module[1] : $module[2];

		// set validation type (0: Bypass, 1: Block by country, 2: WP-ZEP)
		$list = apply_filters( self::PLUGIN_NAME . "-bypass-{$type}", $settings['exception'][ $type ] );
		$type = in_array( $request, $list, TRUE ) ? 0 : $settings['validation'][ $type ];

		// register validation of nonce (2: WP-ZEP)
		if ( 2 & $type )
			add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 5, 2 );

		// register validation of malicious signature
		add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 6, 2 );

		// validate country by IP address (1: Block by country)
		$validate = $this->validate_ip( 'admin', $settings, 1 & $type );

		// if the validation is successful, execute the requested uri via rewrite.php
		if ( class_exists( 'IP_Geo_Block_Rewrite' ) )
			IP_Geo_Block_Rewrite::exec( $this, $validate, $settings );
	}

	/**
	 * Auxiliary validation functions
	 *
	 */
	public function auth_fail( $something = NULL ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

		// Count up a number of fails when authentication is failed
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $this->remote_addr ) ) {
			$validate = self::make_validation( $this->remote_addr, array(
				'code' => $cache['code'],
				'fail' => TRUE,
				'result' => 'failed',
				'provider' => 'Cache',
			) );

			$settings = self::get_option();
			$cache = IP_Geo_Block_API_Cache::update_cache( $cache['hook'], $validate, $settings );

			// validate xmlrpc system.multicall ($HTTP_RAW_POST_DATA has already populated in xmlrpc.php)
			if ( defined( 'XMLRPC_REQUEST' ) && FALSE !== stripos( $GLOBALS['HTTP_RAW_POST_DATA'], 'system.multicall' ) )
				$validate['result'] = 'multi';

			// (1) blocked, (3) unauthenticated, (5) all
			if ( 1 & (int)$settings['validation']['reclogs'] )
				IP_Geo_Block_Logs::record_logs( $cache['hook'], $validate, $settings );

			// send response code to refuse immediately
			if ( $cache['fail'] > max( 0, $settings['login_fails'] ) || 'multi' === $validate['result'] ) {
				if ( $settings['save_statistics'] )
					IP_Geo_Block_Logs::update_stat( $cache['hook'], $validate, $settings );

				$this->send_response( $cache['hook'], $settings['response_code'] );
			}
		}

		return $something; // pass through
	}

	public function check_fail( $validate, $settings ) {
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );

		// if a number of fails is exceeded, then fail
		if ( $cache && $cache['fail'] > max( 0, $settings['login_fails'] ) ) {
			if ( empty( $validate['result'] ) || 'passed' === $validate['result'] )
				$validate['result'] = 'failed'; // can't overwrite existing result
		}

		return $validate;
	}

	public function check_auth( $validate, $settings ) {
		// authentication should be prior to validation of country
		return $validate['auth'] ? $validate + array( 'result' => 'passed' ) : $validate; // can't overwrite existing result
	}

	public function check_signature( $validate, $settings ) {
		$score = 0.0;
		$request = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

		foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $settings['signature'] ) as $sig ) {
			$val = explode( ':', $sig, 2 );

			if ( ( $sig = trim( $val[0] ) ) && FALSE !== strpos( $request, $sig ) ) {
				if ( ( $score += ( empty( $val[1] ) ? 1.0 : (float)$val[1] ) ) > 0.99 )
					return $validate + array( 'result' => 'badsig' ); // can't overwrite existing result
			}
		}

		return $validate;
	}

	public function check_nonce( $validate, $settings ) {
		$action = self::PLUGIN_NAME . '-auth-nonce';
		$nonce = IP_Geo_Block_Util::retrieve_nonce( $action );

		if ( ! IP_Geo_Block_Util::verify_nonce( $nonce, $action ) ) {
			if ( empty( $validate['result'] ) || 'passed' === $validate['result'] )
				$validate['result'] = 'wp-zep'; // can't overwrite existing result
		}

		return $validate;
	}

	/**
	 * Verify specific ip addresses with CIDR.
	 *
	 */
	public function check_ips_white( $validate, $settings ) {
		return $this->check_ips( $validate, $settings['extra_ips']['white_list'], 0 );
	}

	public function check_ips_black( $validate, $settings ) {
		return $this->check_ips( $validate, $settings['extra_ips']['black_list'], 1 );
	}

	private function check_ips( $validate, $ips, $which ) {
		$ip = $validate['ip'];

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			require_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv4.php' );

			foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );

				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) &&
				     Net_IPv4::ipInNetwork( $ip, isset( $j[1] ) ? $i : $i.'/32' ) )
					// can't overwrite existing result
					return $validate + array( 'result' => $which ? 'extra' : 'passed' );
			}
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			require_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv6.php' );

			foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );

				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) &&
				     Net_IPv6::isInNetmask( $ip, isset( $j[1] ) ? $i : $i.'/128' ) )
					// can't overwrite existing result
					return $validate + array( 'result' => $which ? 'extra' : 'passed' );
			}
		}

		return $validate;
	}

	/**
	 * Handlers of cron job
	 *
	 */
	public function update_database( $immediate = FALSE ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php' );
		return IP_Geo_Block_Cron::exec_job( $immediate );
	}

}