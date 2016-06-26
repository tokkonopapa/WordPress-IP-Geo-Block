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
	const VERSION = '2.2.6';
	const GEOAPI_NAME = 'ip-geo-api';
	const PLUGIN_SLUG = 'ip-geo-block';
	const CACHE_KEY   = 'ip_geo_block_cache';
	const CRON_NAME   = 'ip_geo_block_cron';

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = NULL;

	// option table accessor by name
	public static $option_keys = array(
		'settings'   => 'ip_geo_block_settings',
		'statistics' => 'ip_geo_block_statistics',
	);

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
		$settings = self::get_option( 'settings' );
		$priority = $settings['priority'];
		$validate = $settings['validation'];

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] )
			add_action( self::CRON_NAME, array( $this, 'update_database' ) );

		// check the package version and upgrade if needed
		if ( version_compare( $settings['version'], self::VERSION ) < 0 || $settings['matching_rule'] < 0 )
			add_action( 'init', array( __CLASS__, 'activate' ), $priority );

		// normalize requested uri
		$this->request_uri = strtolower( preg_replace( array( '!\.+/!', '!//+!' ), '/', $_SERVER['REQUEST_URI'] ) );
		if ( substr( $this->pagenow = basename( parse_url( $this->request_uri, PHP_URL_PATH ) ), -4 ) !== '.php' )
			$this->pagenow = ! empty( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : 'index.php';

		// setup the content folders
		self::$wp_path = array( 'home' => untrailingslashit( parse_url( site_url(), PHP_URL_PATH ) ) ); // @since 2.6.0
		$len = strlen( self::$wp_path['home'] );
		$list = array(
			'admin'     => 'admin_url',          // @since 2.6.0
			'plugins'   => 'plugins_url',        // @since 2.6.0
			'themes'    => 'get_theme_root_uri', // @since 1.5.0
		);

		// analize the validation target (admin|plugins|themes|includes)
		foreach ( $list as $key => $val ) {
			self::$wp_path[ $key ] = trailingslashit( substr( parse_url( call_user_func( $val ), PHP_URL_PATH ), $len ) );
			if ( ! $this->target_type && FALSE !== strpos( $this->request_uri, self::$wp_path[ $key ] ) )
				$this->target_type = $key;
		}

		// WordPress core files
		$key = array(
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
		elseif ( isset( $key[ $this->pagenow ] ) ) {
			if ( $validate[ $key[ $this->pagenow ] ] )
				add_action( 'init', array( $this, 'validate_' . $key[ $this->pagenow ] ), $priority );
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
	 * Activate / Deactivate plugin
	 *
	 */
	public static function activate( $network_wide = FALSE ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php' );
		IP_Geo_Block_Activate::activate( $network_wide );
	}

	public static function deactivate( $network_wide = FALSE ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php' );
		IP_Geo_Block_Activate::deactivate( $network_wide );
	}

	/**
	 * Optional values handlings.
	 *
	 */
	public static function get_default( $name = 'settings' ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		return IP_Geo_Block_Opts::get_table( self::$option_keys[ $name ] );
	}

	// get optional values from wp options
	public static function get_option( $name = 'settings' ) {
		$option = get_option( self::$option_keys[ $name ] );
		return FALSE !== $option ? $option : self::get_default( $name );
	}

	/**
	 * Register and enqueue a nonce with a specific JavaScript.
	 *
	 */
	public static function enqueue_nonce() {
		if ( is_user_logged_in() ) {
			$handle = self::PLUGIN_SLUG . '-auth-nonce';
			$script = plugins_url(
				! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
				'admin/js/authenticate.min.js' : 'admin/js/authenticate.js', IP_GEO_BLOCK_BASE
			);
			$nonce = array( 'nonce' => wp_create_nonce( $handle ) ) + self::$wp_path;
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
		return apply_filters( self::PLUGIN_SLUG . '-headers', array(
			'timeout' => (int)$settings['timeout'],
			'user-agent' => 'WordPress/' . $GLOBALS['wp_version'] . ', ' . self::PLUGIN_SLUG . ' ' . self::VERSION,
		) );
	}

	/**
	 * Get current IP address
	 *
	 */
	public static function get_ip_address() {
		return apply_filters( self::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR'] );
	}

	/**
	 * Render a text message at the comment form.
	 *
	 */
	public function comment_form_message() {
		$settings = self::get_option( 'settings' );
		echo '<p id="', self::PLUGIN_SLUG, '-msg">', wp_kses( $settings['comment']['msg'], $GLOBALS['allowedtags'] ), '</p>', "\n";
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
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		$ip = $ip ? $ip : self::get_ip_address();
		$result = self::_get_geolocation( $ip, self::get_option( 'settings' ), $providers, $callback );

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
		$code = (int   )apply_filters( self::PLUGIN_SLUG . '-'.$hook.'-status', (int)$code );
		$mesg = (string)apply_filters( self::PLUGIN_SLUG . '-'.$hook.'-reason', get_status_header_desc( $code ) );

		nocache_headers(); // nocache and response code

		switch ( (int)substr( (string)$code, 0, 1 ) ) {
		  case 2: // 2xx Success
			header( 'Refresh: 0; url=' . home_url(), TRUE, $code ); // @since 3.0
			exit;

		  case 3: // 3xx Redirection
			wp_redirect( 'http://blackhole.webpagetest.org/', $code );
			exit;

		  default: // 4xx Client Error, 5xx Server Error
			status_header( $code ); // @since 2.0.0
			if ( function_exists( 'trackback_response' ) )
				trackback_response( $code, $mesg ); // @since 0.71
			elseif ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				FALSE !== ( @include( STYLESHEETPATH . '/'.$code.'.php' ) ) or // child  theme
				FALSE !== ( @include( TEMPLATEPATH   . '/'.$code.'.php' ) ) or // parent theme
				wp_die( $mesg, '', array( 'response' => $code, 'back_link' => TRUE ) );
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
		$var = self::PLUGIN_SLUG . '-' . $hook;
		if ( $auth ) {
			add_filter( $var, array( $this, 'check_auth' ), 9, 2 );
			add_filter( $var, array( $this, 'check_fail' ), 8, 2 );
		}
		$settings['extra_ips'] = apply_filters( self::PLUGIN_SLUG . '-extra-ips', $settings['extra_ips'], $hook );
		$settings['extra_ips']['white_list'] and add_filter( $var, array( $this, 'check_ips_white' ), 7, 2 );
		$settings['extra_ips']['black_list'] and add_filter( $var, array( $this, 'check_ips_black' ), 7, 2 );

		// make valid provider name list
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
		$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// apply custom filter for validation
		// @usage add_filter( "ip-geo-block-$hook", 'my_validation' );
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

		if ( $die ) {
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			// record log (0:no, 1:blocked, 2:passed, 3:unauth, 4:auth, 5:all)
			$var = (int)apply_filters( self::PLUGIN_SLUG . '-record-logs', $settings['validation']['reclogs'], $hook, $validate );
			$result = ( 'passed' !== $validate['result'] );
			if ( ( 1 === $var &&   $result ) || // blocked
			     ( 2 === $var && ! $result ) || // passed
			     ( 3 === $var && ! $validate['auth'] ) || // unauthenticated
			     ( 4 === $var &&   $validate['auth'] ) || // authenticated
			     ( 5 === $var ) ) { // all
				IP_Geo_Block_Logs::record_logs( $hook, $validate, $settings );
			}

			if ( $result ) {
				// update statistics
				if ( $settings['save_statistics'] )
					IP_Geo_Block_Logs::update_stat( $hook, $validate, $settings );

				// send response code to refuse
				$this->send_response( $hook, $settings['response_code'] );
			}
		}

		return $validate;
	}

	/**
	 * Validate at frontend.
	 *
	 */
	public function validate_front( $can_access = TRUE ) {
		$validate = $this->validate_ip( 'comment', self::get_option( 'settings' ), TRUE, FALSE );
		return ( 'passed' === $validate['result'] ? $can_access : FALSE );
	}

	/**
	 * Validate at comment.
	 *
	 */
	public function validate_comment( $comment = NULL ) {
		// check comment type if it comes form wp-includes/wp_new_comment()
		if ( ! is_array( $comment ) || in_array( $comment['comment_type'], array( 'trackback', 'pingback' ), TRUE ) )
			$this->validate_ip( 'comment', self::get_option( 'settings' ) );

		return $comment;
	}

	/**
	 * Validate at xmlrpc.
	 *
	 */
	public function validate_xmlrpc() {
		$settings = self::get_option( 'settings' );

		if ( 2 === (int)$settings['validation']['xmlrpc'] ) // Completely close
			add_filter( self::PLUGIN_SLUG . '-xmlrpc', array( $this, 'close_xmlrpc' ), 6, 2 );

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
		$settings = self::get_option( 'settings' );

		// wp-includes/pluggable.php @since 2.5.0
		add_action( 'wp_login_failed', array( $this, 'auth_fail' ), $settings['priority'] );

		// enables to skip validation by country at login/out except BuddyPress signup
		$block = ( 1 === (int)$settings['validation']['login'] ) ||
			( 'bp_' === substr( current_filter(), 0, 3 ) ) ||
			( isset( $_REQUEST['action'] ) && ! in_array( $_REQUEST['action'], array( 'login', 'logout' ), TRUE ) );

		$this->validate_ip( 'login', $settings, $block );
	}

	/**
	 * Validate at admin area.
	 *
	 */
	public function validate_admin() {
		$settings = self::get_option( 'settings' );
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
			$this->trace_nonce();

			// list of request with a specific query to bypass WP-ZEP
			$list = apply_filters( self::PLUGIN_SLUG . '-bypass-admins', array(
				'wp-compression-test', // wp-admin/includes/template.php
				'upload-attachment', 'imgedit-preview', 'bp_avatar_upload', // pluploader won't fire an event in "Media Library"
				'jetpack', 'authorize', 'jetpack_modules', 'atd_settings', 'bulk-activate', 'bulk-deactivate', // jetpack page & action
			) );

			// combination with vulnerable keys should be prevented to bypass WP-ZEP
			$in_action = in_array( $action, $list, TRUE );
			$in_page   = in_array( $page,   $list, TRUE );
			if ( ( ( $action xor $page ) && ( ! $in_action and ! $in_page ) ) ||
			     ( ( $action and $page ) && ( ! $in_action or  ! $in_page ) ) )
				add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_nonce' ), 5, 2 );
		}

		// register validation by malicious signature
		if ( ! is_user_logged_in() || ! in_array( $this->pagenow, array( 'comment.php', 'post.php' ), TRUE ) )
			add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_signature' ), 6, 2 );

		// validate country by IP address (1: Block by country)
		$this->validate_ip( 'admin', $settings, 1 & $type );
	}

	/**
	 * Validate at plugins/themes area.
	 *
	 */
	public function validate_direct() {
		$settings = self::get_option( 'settings' );
		$request = preg_quote( self::$wp_path[ $type = $this->target_type ], '/' );
		$module = in_array( $type, array( 'plugins', 'themes' ) ) ? '[^\?\&\/]*' : '[^\?\&]*';

		// wp-includes, wp-content/(plugins|themes|language|uploads)
		preg_match( "/($request)($module)/", $this->request_uri, $module );
		$request = empty( $module[2] ) ? $module[1] : $module[2];

		// set validation type (0: Bypass, 1: Block by country, 2: WP-ZEP)
		$list = apply_filters( self::PLUGIN_SLUG . "-bypass-{$type}", $settings['exception'][ $type ] );
		$type = in_array( $request, $list, TRUE ) ? 0 : $settings['validation'][ $type ];

		// register validation of nonce (2: WP-ZEP)
		if ( 2 & $type )
			add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_nonce' ), 5, 2 );

		// register validation of malicious signature
		add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_signature' ), 6, 2 );

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
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

		// Count up a number of fails when authentication is failed
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $this->remote_addr ) ) {
			$validate = self::make_validation( $this->remote_addr, array(
				'code' => $cache['code'],
				'fail' => TRUE,
				'result' => 'failed',
				'provider' => 'Cache',
			) );

			$settings = self::get_option( 'settings' );
			$cache = IP_Geo_Block_API_Cache::update_cache( $cache['hook'], $validate, $settings );

			// validate xmlrpc system.multicall (HTTP_RAW_POST_DATA has already populated in xmlrpc.php)
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
		// authentication should be prior to validation by country (can't overwrite existing result)
		return $validate['auth'] ? $validate + array( 'result' => 'passed' ) : $validate;
	}

	public function check_signature( $validate, $settings ) {
		$score = 0.0;
		$request = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

		foreach ( $this->multiexplode( array( ',', ' ' ), $settings['signature'] ) as $sig ) {
			$val = explode( ':', $sig, 2 );

			if ( ( $sig = trim( $val[0] ) ) && FALSE !== strpos( $request, $sig ) ) {
				if ( ( $score += ( empty( $val[1] ) ? 1.0 : (float)$val[1] ) ) > 0.99 )
					return $validate + array( 'result' => 'badsig' ); // can't overwrite existing result
			}
		}

		return $validate;
	}

	/**
	 * Validate nonce.
	 *
	 */
	public function check_nonce( $validate, $settings ) {
		$nonce = self::PLUGIN_SLUG . '-auth-nonce';

		if ( ! wp_verify_nonce( self::retrieve_nonce( $nonce ), $nonce ) ) {
			if ( empty( $validate['result'] ) || 'passed' === $validate['result'] )
				$validate['result'] = 'wp-zep'; // can't overwrite existing result
		}

		return $validate;
	}

	private function trace_nonce() {
		$nonce = self::PLUGIN_SLUG . '-auth-nonce';

		if ( empty( $_REQUEST[ $nonce ] ) && self::retrieve_nonce( $nonce ) &&
		     is_user_logged_in() && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			// add nonce at add_admin_nonce() to handle the client side redirection.
			wp_redirect( esc_url_raw( $_SERVER['REQUEST_URI'] ), 302 );
			exit;
		}
	}

	public static function retrieve_nonce( $key ) {
		if ( isset( $_REQUEST[ $key ] ) )
			return sanitize_text_field( $_REQUEST[ $key ] );

		if ( preg_match( "/$key(?:=|%3D)([\w]+)/", wp_get_referer(), $matches ) )
			return sanitize_text_field( $matches[1] );

		return NULL;
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

	private function multiexplode ( $delimiters, $string ) {
		return array_filter( explode( $delimiters[0], str_replace( $delimiters, $delimiters[0], $string ) ) );
	}

	private function check_ips( $validate, $ips, $which ) {
		$ip = $validate['ip'];

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			include_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv4.php' );

			foreach ( $this->multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );

				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) &&
				     Net_IPv4::ipInNetwork( $ip, isset( $j[1] ) ? $i : $i.'/32' ) )
					// can't overwrite existing result
					return $validate + array( 'result' => $which ? 'extra' : 'passed' );
			}
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			include_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv6.php' );

			foreach ( $this->multiexplode( array( ",", "\n" ), $ips ) as $i ) {
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
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php' );
		return IP_Geo_Block_Cron::exec_job( $immediate );
	}

}