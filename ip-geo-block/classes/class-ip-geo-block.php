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
	const VERSION = '3.0.1.2';
	const GEOAPI_NAME = 'ip-geo-api';
	const PLUGIN_NAME = 'ip-geo-block';
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
		$settings = self::get_option();
		$priority = &$settings['priority'];
		$validate = &$settings['validation'];
		$loader = new IP_Geo_Block_Loader();

		// include drop in if it exists
		$key = IP_Geo_Block_Util::unslashit( $settings['api_dir'] ) . '/drop-in.php';
		file_exists( $key ) and include( $key );

		// Garbage collection for IP address cache
		add_action( self::CACHE_NAME, array( $this, 'exec_cache_gc' ) );

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] )
			add_action( self::CRON_NAME, array( $this, 'update_database' ) );

		// check the package version and upgrade if needed (activation hook never fire on upgrade)
		if ( version_compare( $settings['version'], self::VERSION ) < 0 || $settings['matching_rule'] < 0 )
			$loader->add_action( 'init', 'ip_geo_block_activate', $priority );

		// normalize requested uri and page
		$key = preg_replace( array( '!\.+/!', '!//+!' ), '/', $_SERVER['REQUEST_URI'] );
		$this->request_uri = @parse_url( $key, PHP_URL_PATH ) or $this->request_uri = $key;
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
				$loader->add_action( 'init', array( $this, 'validate_direct' ), $priority );
			else // 'widget_init' for admin dashboard
				$loader->add_action( 'wp_loaded', array( $this, 'validate_admin' ), $priority );
		}

		// analize core validation target (comment|xmlrpc|login|public)
		elseif ( isset( $list[ $this->pagenow ] ) ) {
			if ( $validate[ $list[ $this->pagenow ] ] )
				$loader->add_action( 'init', array( $this, 'validate_' . $list[ $this->pagenow ] ), $priority );
		}

		// alternative of trackback
		elseif ( 'POST' === $_SERVER['REQUEST_METHOD'] && 'trackback' === basename( $this->request_uri ) ) {
			if ( $validate['comment'] )
				$loader->add_action( 'init', array( $this, 'validate_comment' ), $priority );
		}

		else {
			// public facing pages
			if ( $validate['public'] /* && 'index.php' === $this->pagenow */ )
				$loader->add_action( 'init', array( $this, 'validate_public' ), $priority );

			// message text on comment form
			if ( $settings['comment']['pos'] ) {
				$key = ( 1 === (int)$settings['comment']['pos'] ? '_top' : '' );
				add_action( 'comment_form' . $key, array( $this, 'comment_form_message' ) );
			}

			if ( $validate['comment'] ) {
				add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ), $priority ); // wp-comments-post.php @since 2.8.0
				add_action( 'pre_trackback_post',  array( $this, 'validate_comment' ), $priority ); // wp-trackback.php @since 4.7.0
				add_filter( 'preprocess_comment',  array( $this, 'validate_comment' ), $priority ); // wp-includes/comment.php @since 1.5.0

				// bbPress: prevent creating topic/relpy and rendering form
				add_action( 'bbp_post_request_bbp-new-topic', array( $this, 'validate_comment' ), $priority );
				add_action( 'bbp_post_request_bbp-new-reply', array( $this, 'validate_comment' ), $priority );
				add_filter( 'bbp_current_user_can_access_create_topic_form', array( $this, 'validate_front' ), $priority );
				add_filter( 'bbp_current_user_can_access_create_reply_form', array( $this, 'validate_front' ), $priority );
			}

			if ( $validate['login'] ) {
				// for hide/rename wp-login.php, BuddyPress: prevent registration and rendering form
				add_action( 'login_init', array( $this, 'validate_login' ), $priority );

				// only when block on front-end is disabled
				if ( ! $validate['public'] ) {
					add_action( 'bp_core_screen_signup',  array( $this, 'validate_login' ), $priority );
					add_action( 'bp_signup_pre_validate', array( $this, 'validate_login' ), $priority );
				}
			}
		}

		// force to change the redirect URL on logout to remove nonce, embed a nonce into pages
		add_filter( 'wp_redirect', array( $this, 'logout_redirect' ), 20, 2 ); // logout_redirect @4.2
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_nonce' ), $priority );

		// Run the loader to execute all of the hooks with WordPress.
		$loader->run( $this );
		unset( $loader );
	}

	/**
	 * I/F for registering custom fileter
	 *
	 */
	public static function add_filter( $tag, $function, $priority = 10, $args = 1 ) {
		add_filter( $tag, $function, $priority, $args );
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
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
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
	 * Remove the redirecting URL on logout not to be blocked by WP-ZEP.
	 *
	 */
	public function logout_redirect( $uri ) {
		if ( isset( $_REQUEST['action'] ) && 'logout' === $_REQUEST['action'] && FALSE !== stripos( $uri, self::$wp_path['admin'] ) )
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
	 * Render a text message on the comment form.
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
			'ip'   => $ip,
			'auth' => IP_Geo_Block_Util::get_current_user_id(),
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
		$settings = self::get_option();

		if ( empty( $providers ) ) // make valid providers list
			$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		$result = self::_get_geolocation( $ip ? $ip : self::get_ip_address(), $settings, $providers, $callback );

		if ( ! empty( $result['countryCode'] ) )
			$result['code'] = $result['countryCode'];

		return $result;
	}

	/**
	 * API for internal.
	 *
	 */
	private static function _get_geolocation( $ip, $settings, $providers, $callback = 'get_country' ) {
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
	public function send_response( $hook, $settings ) {
		require_once ABSPATH . WPINC . '/functions.php'; // for get_status_header_desc() @since 2.3.0

		// prevent caching (WP Super Cache, W3TC, Wordfence, Comet Cache)
		if ( ! defined( 'DONOTCACHEPAGE' ) )
			define( 'DONOTCACHEPAGE', TRUE );

		$code = (int   )apply_filters( self::PLUGIN_NAME . '-'.$hook.'-status', $settings['response_code'] );
		$mesg = (string)apply_filters( self::PLUGIN_NAME . '-'.$hook.'-reason', $settings['response_msg' ] ? $settings['response_msg'] : get_status_header_desc( $code ) );

		nocache_headers(); // Set the headers to prevent caching for the different browsers.

		if ( defined( 'XMLRPC_REQUEST' ) && 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			status_header( 405 );
			header( 'Content-Type: text/plain' );
			die( 'XML-RPC server accepts POST requests only.' );
		}

		switch ( (int)substr( (string)$code, 0, 1 ) ) {
		  case 2: // 2xx Success (HTTP header injection should be avoided)
			header( 'Refresh: 0; url=' . esc_url_raw( $settings['redirect_uri'] ? $settings['redirect_uri'] : home_url( '/' ) ), TRUE, $code ); // @since 2.8
			exit;

		  case 3: // 3xx Redirection (HTTP header injection should be avoided)
			IP_Geo_Block_Util::safe_redirect( esc_url_raw( $settings['redirect_uri'] ? $settings['redirect_uri'] : home_url( '/' ) ), $code ); // @since 2.8
			exit;

		  default: // 4xx Client Error, 5xx Server Error
			status_header( $code ); // @since 2.0.0

			// https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
			'public' !== $hook and header( 'X-Robots-Tag: noindex, nofollow', FALSE );

			if ( function_exists( 'trackback_response' ) )
				trackback_response( $code, IP_Geo_Block_Util::kses( $mesg ) ); // @since 0.71

			// Show human readable page
			elseif ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				$hook = IP_Geo_Block_Util::may_be_logged_in() && 'admin' === $this->target_type;
				FALSE !== ( @include get_stylesheet_directory() .'/'.$code.'.php' ) or // child  theme
				FALSE !== ( @include get_template_directory()   .'/'.$code.'.php' ) or // parent theme
				wp_die( // get_dashboard_url() @since 3.1.0
					IP_Geo_Block_Util::kses( $mesg ) . ( $hook ? "\n<p><a rel='nofollow' href='" . esc_url( get_dashboard_url() ) . "'>&laquo; " . __( 'Dashboard' ) . "</a></p>" : '' ),
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
		          add_filter( $var, array( $this, 'check_fail' ), 9, 2 );
		$auth and add_filter( $var, array( $this, 'check_auth' ), 8, 2 );
		$settings['extra_ips'] = apply_filters( self::PLUGIN_NAME . '-extra-ips', $settings['extra_ips'], $hook );
		$settings['extra_ips']['white_list'] and add_filter( $var, array( $this, 'check_ips_white' ), 7, 2 );
		$settings['extra_ips']['black_list'] and add_filter( $var, array( $this, 'check_ips_black' ), 7, 2 );

		// make valid provider name list
		$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// apply custom filter for validation
		// @example add_filter( 'ip-geo-block-$hook', 'my_validation', 10, 2 );
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

		if ( $auth ) {
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

			// update cache
			IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings );

			// update statistics
			if ( $settings['save_statistics'] )
				IP_Geo_Block_Logs::update_stat( $hook, $validate, $settings );

			// send response code to refuse
			if ( $block && $die )
				$this->send_response( $hook, $settings );
		}

		return $validate;
	}

	/**
	 * Validate on comment.
	 *
	 */
	public function validate_comment( $comment = NULL ) {
		// check comment type if it comes form wp-includes/wp_new_comment()
		if ( ! is_array( $comment ) || in_array( $comment['comment_type'], array( 'trackback', 'pingback' ), TRUE ) )
			$this->validate_ip( 'comment', self::get_option() );

		return $comment;
	}

	public function validate_front( $can_access = TRUE ) {
		$validate = $this->validate_ip( 'comment', self::get_option(), TRUE, FALSE );
		return ( 'passed' === $validate['result'] ? $can_access : FALSE );
	}

	/**
	 * Validate on xmlrpc.
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
	 * Validate on login.
	 *
	 */
	public function validate_login() {
		// parse action
		$action = isset( $_GET['key'] ) ?
			'resetpass' : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login' );

		if ( 'retrievepassword' === $action )
			$action = 'lostpassword';
		elseif ( 'rp' === $action )
			$action = 'resetpass';

		$settings = self::get_option();
		$list = &$settings['login_action'];

		// the same rule should be applied to login and logout
		! empty( $list['login'] ) and $list['logout'] = TRUE;

		// wp-includes/pluggable.php @since 2.5.0
		add_action( 'wp_login_failed', array( $this, 'auth_fail' ), $settings['priority'] );

		// enables to skip validation of country on login/out except BuddyPress signup
		$this->validate_ip( 'login', $settings, ! empty( $list[ $action ] ) || 'bp_' === substr( current_filter(), 0, 3 ) );
	}

	/**
	 * Validate in admin area.
	 *
	 */
	public function validate_admin() {
		// if there's no action parameter but something is specified
		$settings = self::get_option();
		$action = isset( $_REQUEST['task' ] ) ? 'task' : 'action';
		$action = isset( $_REQUEST[$action] ) ? $_REQUEST[$action] : NULL;
		$page   = isset( $_REQUEST['page' ] ) ? $_REQUEST['page' ] : NULL;

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

		// list of request for specific action or page to bypass WP-ZEP
		$list = array_merge(
			apply_filters( self::PLUGIN_NAME . '-bypass-admins', $settings['exception']['admin'] ),
			array( 'save-widget', 'wordfence_testAjax', 'wordfence_doScan', 'wp-compression-test', // wp-admin/includes/template.php
				'upload-attachment', 'imgedit-preview', 'bp_avatar_upload', 'GOTMLS_logintime', // pluploader won't fire an event in "Media Library"
				'jetpack', 'authorize', 'jetpack_modules', 'atd_settings', 'bulk-activate', 'bulk-deactivate', // jetpack page & action
			)
		);

		$in_action = in_array( $action, $list, TRUE );
		$in_page   = in_array( $page,   $list, TRUE );

		// combination with vulnerable keys should be prevented to bypass WP-ZEP
		if ( ( ( $action xor $page ) && ( ! $in_action and ! $in_page ) ) ||
		     ( ( $action and $page ) && ( ! $in_action or  ! $in_page ) ) ) {
			if ( ( 2 & $type ) && $zep ) {
				// redirect if valid nonce in referer, otherwise register WP-ZEP (2: WP-ZEP)
				IP_Geo_Block_Util::trace_nonce( self::PLUGIN_NAME . '-auth-nonce' );
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 5, 2 );
			}

			// register validation of malicious signature (except in the comment and post)
			if ( ! IP_Geo_Block_Util::may_be_logged_in() || ! in_array( $this->pagenow, array( 'comment.php', 'post.php' ), TRUE ) )
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 6, 2 );
		}

		// validate country by IP address (1: Block by country)
		$this->validate_ip( 'admin', $settings, 1 & $type );
	}

	/**
	 * Validate in plugins/themes area.
	 *
	 */
	public function validate_direct() {
		// analyze target in wp-includes, wp-content/(plugins|themes|language|uploads)
		$path = preg_quote( self::$wp_path[ $type = $this->target_type ], '/' );
		$target = ( 'plugins' === $type || 'themes' === $type ? '[^\?\&\/]*' : '[^\?\&]*' );

		preg_match( "/($path)($target)/", $this->request_uri, $target );
		$target = empty( $target[2] ) ? $target[1] : $target[2];

		// set validation type by target (0: Bypass, 1: Block by country, 2: WP-ZEP)
		$settings = self::get_option();
		$path = apply_filters( self::PLUGIN_NAME . "-bypass-{$type}", $settings['exception'][ $type ] );
		$type = (int)$settings['validation'][ $type ];

		if ( ! in_array( $target, $path, TRUE ) ) {
			if ( 2 & $type ) {
				// redirect if valid nonce in referer, otherwise register WP-ZEP (2: WP-ZEP)
				IP_Geo_Block_Util::trace_nonce( self::PLUGIN_NAME . '-auth-nonce' );
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 5, 2 );
			}

			// register validation of malicious signature
			if ( ! IP_Geo_Block_Util::may_be_logged_in() )
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 6, 2 );
		}

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
		// Count up a number of fails when authentication is failed
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $this->remote_addr ) ) {
			$validate = self::make_validation( $this->remote_addr, array(
				'code'     => $cache['code'],
				'fail'     => TRUE,
				'result'   => 'failed',
				'provider' => 'Cache',
			) );

			$settings = self::get_option();

			if ( $cache['fail'] > max( 0, (int)$settings['login_fails'] ) )
				$validate['result'] = 'limited';

			// validate xmlrpc system.multicall
			elseif ( defined( 'XMLRPC_REQUEST' ) && FALSE !== stripos( file_get_contents( 'php://input' ), 'system.multicall' ) )
				$validate['result'] = 'multi';

			$cache = IP_Geo_Block_API_Cache::update_cache( 'login', $validate, $settings ); // count up 'fail'

			// (1) blocked, (3) unauthenticated, (5) all
			if ( 1 & (int)$settings['validation']['reclogs'] )
				IP_Geo_Block_Logs::record_logs( 'login', $validate, $settings );

			// send response code to refuse immediately
			if ( 'failed' !== $validate['result'] ) {
				if ( $settings['save_statistics'] )
					IP_Geo_Block_Logs::update_stat( 'login', $validate, $settings );

				$this->send_response( 'login', $settings );
			}
		}

		return $something; // pass through
	}

	public function check_fail( $validate, $settings ) {
		// check if number of fails reaches the limit. can't overwrite existing result.
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );
		return $cache && $cache['fail'] >= max( 0, (int)$settings['login_fails'] ) ? $validate + array( 'result' => 'limited' ) : $validate;
	}

	public function check_auth( $validate, $settings ) {
		// authentication should be prior to validation of country
		return $validate['auth'] ? $validate + array( 'result' => 'passed' ) : $validate; // can't overwrite existing result
	}

	public function check_nonce( $validate, $settings ) {
		// should be passed when nonce is valid. can't overwrite existing result
		$nonce = IP_Geo_Block_Util::retrieve_nonce( $action = self::PLUGIN_NAME . '-auth-nonce' );
		return $validate + array( 'result' => IP_Geo_Block_Util::verify_nonce( $nonce, $action ) ? 'passed' : 'wp-zep' );
	}

	public function check_signature( $validate, $settings ) {
		$score = 0.0;
		$query = strtolower( urldecode( serialize( array_values( $_GET + $_POST ) ) ) );

		foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $settings['signature'] ) as $sig ) {
			$val = explode( ':', $sig, 2 );

			if ( ( $sig = trim( $val[0] ) ) && FALSE !== strpos( $query, $sig ) ) {
				if ( ( $score += ( empty( $val[1] ) ? 1.0 : (float)$val[1] ) ) > 0.99 )
					return $validate + array( 'result' => 'badsig' ); // can't overwrite existing result
			}
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
		if ( filter_var( $ip = $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			require_once IP_GEO_BLOCK_PATH . 'includes/Net/IPv4.php';

			foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );

				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) &&
				     Net_IPv4::ipInNetwork( $ip, isset( $j[1] ) ? $i : $i.'/32' ) )
					// can't overwrite existing result
					return $validate + array( 'result' => $which ? 'extra' : 'passed' );
			}
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			require_once IP_GEO_BLOCK_PATH . 'includes/Net/IPv6.php';

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
	 * Validate on public facing pages.
	 *
	 */
	public function validate_public() {
		$settings = self::get_option();
		$public = &$settings['public'];

		// avoid redirection loop
		if ( $settings['response_code'] < 400 && IP_Geo_Block_Util::compare_url( $_SERVER['REQUEST_URI'], $settings['redirect_uri'] ? $settings['redirect_uri'] : home_url( '/' ) ) )
			return; // do not block

		if ( $public['target_rule'] ) {
			// postpone validation until 'wp' fires
			if ( ! did_action( 'wp' ) ) {
				add_action( 'wp', array( $this, 'validate_public' ) );
				return;
			}

			// register filter hook to check pages and post types
			add_filter( self::PLUGIN_NAME . '-public', array( $this, 'check_page' ), 10, 2 );
		}

		// replace "Validation rule settings"
		if ( -1 !== (int)$public['matching_rule'] ) {
			$settings['matching_rule'] = $public['matching_rule'];
			$settings['white_list'   ] = $public['white_list'   ];
			$settings['black_list'   ] = $public['black_list'   ];
		}

		// retrieve IP address of visitor via proxy services
		add_filter( self::PLUGIN_NAME . '-ip-addr', array( $this, 'get_proxy_ip' ), 20, 1 );

		// validate undesired user agent
		add_filter( self::PLUGIN_NAME . '-public', array( $this, 'check_bots' ), 6, 2 );

		// validate country by IP address (block: true, die: false)
		$this->validate_ip( 'public', $settings, TRUE, ! $public['simulate'] );
	}

	public function get_proxy_ip( $ip ) {
		if ( isset( $_SERVER['HTTP_VIA'] ) && FALSE !== strpos( $_SERVER['HTTP_VIA'], 'Chrome-Compression-Proxy' ) && isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			// require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';
			// if ( FALSE !== strpos( 'google', IP_Geo_Block_Lkup::gethostbyaddr( $ip ) ) )
			$proxy = preg_replace( '/^for=.*?([a-f\d\.:]+).*$/', '$1', $_SERVER['HTTP_FORWARDED'] );
		}

		return empty( $proxy ) ? $ip : $proxy;
	}

	public function check_page( $validate, $settings ) {
		global $post;
		$public = &$settings['public'];

		if ( $post ) {
			// check page
			if ( isset( $post->post_name ) && isset( $public['target_pages'][ $post->post_name ] ) )
				return $validate; // block by country

			// check post type (this would not block top page)
			$keys = array_keys( $public['target_posts'] );
			if ( ! empty( $keys ) && is_singular( $keys ) )
				return $validate; // block by country

			// check category (single page or category archive)
			$keys = array_keys( $public['target_cates'] );
			if ( ! empty( $keys ) && in_category( $keys ) && ( is_single() || is_category() ) )
				return $validate; // block by country

			// check tag (single page or tag archive)
			$keys = array_keys( $public['target_tags'] );
			if ( ! empty( $keys ) && has_tag( $keys ) && ( is_single() || is_tag() ) )
				return $validate; // block by country
		}

		return $validate + array( 'result' => 'passed' ); // provide content
	}

	public function check_bots( $validate, $settings ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';

		// get the name of host (from the cache if exists)
		if ( empty( $validate['host'] ) && FALSE !== strpos( $settings['public']['ua_list'], 'HOST' ) )
			$validate['host'] = IP_Geo_Block_Lkup::gethostbyaddr( $validate['ip'] );

		// check requested url
		$is_feed = IP_Geo_Block_Lkup::is_feed( $this->request_uri );
		$u_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$referer = isset( $_SERVER['HTTP_REFERER'   ] ) ? $_SERVER['HTTP_REFERER'   ] : '';

		foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $settings['public']['ua_list'] ) as $pat ) {
			list( $name, $code ) = array_pad( IP_Geo_Block_Util::multiexplode( array( ':', '#' ), $pat ), 2, '' );

			if ( $name && ( '*' === $name || FALSE !== strpos( $u_agent, $name ) ) ) {
				$which = ( FALSE === strpos( $pat, ':' ) );     // 0: pass (':'), 1: block ('#')
				$not   = ( '!' === $code[0] );                  // 0: positive, 1: negative
				$code  = ( $not ? substr( $code, 1 ) : $code ); // qualification identifier

				if ( 'FEED' === $code ) {
					if ( $not xor $is_feed )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}

				elseif ( 'HOST' === $code ) {
					if ( $not xor $validate['host'] !== $validate['ip'] )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}

				elseif ( 0 === strncmp( 'HOST=', $code, 5 ) ) {
					if ( $not xor FALSE !== strpos( $validate['host'], substr( $code, 5 ) ) )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}

				elseif ( 0 === strncmp( 'REF=', $code, 4 ) ) {
					if ( $not xor FALSE !== strpos( $referer, substr( $code, 4 ) ) )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}

				elseif ( '*' === $code || 2 === strlen( $code ) ) {
					if ( $not xor ( '*' === $code || $validate['code'] === $code ) )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}

				elseif ( preg_match( '!^[a-f\d\.:/]+$!', $code ) ) {
					$name = $this->check_ips( $validate, $code, $which );
					if ( $not xor isset( $name['result'] ) )
						return $validate + array( 'result' => $which ? 'blocked' : 'passed' );
				}
			}
		}

		return $validate;
	}

	/**
	 * Handlers of cron job
	 *
	 */
	public function update_database( $immediate = FALSE ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
		return IP_Geo_Block_Cron::exec_job( $immediate );
	}

	public function exec_cache_gc() {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
		IP_Geo_Block_Cron::exec_cache_gc( self::get_option() );
	}

}