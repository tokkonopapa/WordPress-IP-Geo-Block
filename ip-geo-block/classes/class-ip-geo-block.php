<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2015 tokkonopapa
 */

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '2.2.1.1';
	const TEXT_DOMAIN = 'ip-geo-block';
	const PLUGIN_SLUG = 'ip-geo-block';
	const CACHE_KEY   = 'ip_geo_block_cache';
	const CRON_NAME   = 'ip_geo_block_cron';

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	// option table accessor by name
	public static $option_keys = array(
		'settings'   => 'ip_geo_block_settings',
		'statistics' => 'ip_geo_block_statistics',
	);

	// Globals in this class
	public static $content_dir;
	private $remote_addr = NULL;

	/**
	 * Initialize the plugin
	 * 
	 */
	private function __construct() {
		$settings = self::get_option( 'settings' );
		$validate = $settings['validation'];

		// check the package version and upgrade if needed
		if ( version_compare( $settings['version'], self::VERSION ) < 0 )
			$settings = self::activate();

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] && ! has_action( self::CRON_NAME ) )
			add_action( self::CRON_NAME, array( __CLASS__, 'download_database' ), 10, 1 );

		if ( $validate['comment'] ) {
			// message text on comment form
			if ( $settings['comment']['pos'] ) {
				$pos = 'comment_form' . ( $settings['comment']['pos'] == 1 ? '_top' : '' );
				add_action( $pos, array( $this, 'comment_form_message' ) );
			}

			// wp-comments-post.php @since 2.8.0, wp-trackback.php @since 1.5.0
			add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ) );
			add_filter( 'preprocess_comment', array( $this, 'validate_comment' ) );

			// bbPress: prevent creating topic/relpy and rendering form
			add_action( 'bbp_post_request_bbp-new-topic', array( $this, 'validate_comment' ) );
			add_action( 'bbp_post_request_bbp-new-reply', array( $this, 'validate_comment' ) );
			add_filter( 'bbp_current_user_can_access_create_topic_form', array( $this, 'validate_front' ) );
			add_filter( 'bbp_current_user_can_access_create_reply_form', array( $this, 'validate_front' ) );
		}

		// xmlrpc.php @since 3.1.0, wp-includes/class-wp-xmlrpc-server.php @since 3.5.0
		if ( $validate['xmlrpc'] ) {
			add_filter( 'wp_xmlrpc_server_class', array( $this, 'validate_xmlrpc' ) );
			add_filter( 'xmlrpc_login_error', array( $this, 'auth_fail' ) );
		}

		// wp-login.php @since 2.1.0, wp-includes/pluggable.php @since 2.5.0
		if ( $validate['login'] ) {
			add_action( 'login_init', array( $this, 'validate_login' ) );
			add_action( 'wp_login_failed', array( $this, 'auth_fail' ) );

			// BuddyPress: prevent registration and rendering form
			add_action( 'bp_core_screen_signup', array( $this, 'validate_login' ) );
			add_action( 'bp_signup_pre_validate', array( $this, 'validate_login' ) );
		}

		// get content folders (with/without trailing slash)
		self::$content_dir = array(
			'root'    => untrailingslashit( parse_url  ( $uri = home_url(), PHP_URL_PATH ) ),
			'admin'   =>   trailingslashit( str_replace( $uri, '', admin_url() ) ),
			'plugins' =>   trailingslashit( str_replace( $uri, '', plugins_url() ) ),
			'themes'  =>   trailingslashit( str_replace( $uri, '', get_theme_root_uri() ) ),
		);

		// wp-admin/(admin.php|admin-apax.php|admin-post.php) @since 2.5.0
		if ( is_admin() && ( $validate['admin'] || $validate['ajax'] || $settings['signature'] ) )
			add_action( 'init', array( $this, 'validate_admin' ), $settings['priority'] );

		// wp-content/(plugins|themes)/.../*.php
		else {
			$uri = preg_replace( '!(//+|/\.+/)!', '/', $_SERVER['REQUEST_URI'] );
			$pos = FALSE !== strpos( $uri, self::$content_dir['plugins'] ) ||
			       FALSE !== strpos( $uri, self::$content_dir['themes' ] );
			if ( $pos && ( $validate['plugins'] || $validate['themes'] || $settings['signature'] ) )
				add_action( 'init', array( $this, 'validate_direct' ), $settings['priority'] );
		}

		// force to change the redirect URL at logout to remove nonce, embed a nonce into pages
		add_filter( 'wp_redirect', array( $this, 'logout_redirect' ), 20, 2 ); // logout_redirect @4.2
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_nonce' ), $settings['priority'] );
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Register options into database table when the plugin is activated.
	 *
	 */
	public static function activate( $network_wide = FALSE ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );

		// create log & upgrade and return new options
		IP_Geo_Block_Logs::create_log();
		$settings = IP_Geo_Block_Options::upgrade();

		// kick off a cron job to download database immediately
		self::exec_download( IP_GEO_BLOCK_BASE );

		return $settings;
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = FALSE ) {
		// cancel schedule
		wp_clear_scheduled_hook( self::CRON_NAME, array( FALSE ) ); // @since 2.1.0
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		$settings = self::get_option( 'settings' );

		if ( $settings['clean_uninstall'] ) {
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			// delete settings options, IP address cache, log
			delete_option( self::$option_keys['settings'  ] ); // @since 1.2.0
			delete_option( self::$option_keys['statistics'] ); // @since 1.2.0
			delete_transient( self::CACHE_KEY ); // @since 2.8
			IP_Geo_Block_Logs::delete_log();
		}
	}

	/**
	 * Optional values handlings.
	 *
	 */
	public static function get_default( $name = 'settings' ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		return IP_Geo_Block_Options::get_table( self::$option_keys[ $name ] );
	}

	// get optional values from wp options
	public static function get_option( $name = 'settings' ) {
		if ( FALSE === ( $option = get_option( self::$option_keys[ $name ] ) ) )
			$option = self::get_default( $name );

		return $option;
	}

	/**
	 * Register and enqueue a nonce with a specific JavaScript.
	 *
	 */
	public static function enqueue_nonce() {
		if ( is_user_logged_in() ) {
			$handle = self::PLUGIN_SLUG . '-auth-nonce';
			$script = plugins_url( 'admin/js/authenticate.js', IP_GEO_BLOCK_BASE );
			$nonce = array( 'nonce' => wp_create_nonce( $handle ) ) + self::$content_dir;
			wp_enqueue_script( $handle, $script, array( 'jquery' ), self::VERSION );
			wp_localize_script( $handle, 'IP_GEO_BLOCK_AUTH', $nonce );
		}
	}

	/**
	 * Remove the redirecting URL at logout not to be blocked by WP-ZEP.
	 *
	 */
	public function logout_redirect( $uri ) {
		return isset( $_REQUEST['action'] ) && 'logout' === $_REQUEST['action'] ? 'wp-login.php?loggedout=true' : $uri;
	}

	/**
	 * Setup the http header.
	 *
	 * @see http://codex.wordpress.org/Function_Reference/wp_remote_get
	 */
	public static function get_request_headers( $settings ) {
		return apply_filters(
			self::PLUGIN_SLUG . '-headers',
			array(
				'timeout' => (int)$settings['timeout'],
				'user-agent' => 'WordPress/' . $GLOBALS['wp_version'] . self::PLUGIN_SLUG . ' ' . self::VERSION,
			)
		);
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
		echo '<p id="', self::PLUGIN_SLUG, '-msg">', wp_kses( $settings['comment']['msg'], $GLOBALS['allowedtags'] ), "</p>\n";
	}

	/**
	 * Build a validation result for the current user.
	 *
	 */
	private static function make_validation( $ip, $result ) {
		return array_merge( array(
			'ip' => $ip,
			'auth' => get_current_user_id(),
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
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

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
	public static function validate_country( $validate, $settings, $block = TRUE ) {
		if ( $block && 0 == $settings['matching_rule'] ) {
			// 'ZZ' will be blocked if it's not in the $list.
			if ( ( $list = $settings['white_list'] ) && FALSE === strpos( $list, $validate['code'] ) )
				return $validate += array( 'result' => 'blocked' ); // can't overwrite existing result
		}

		elseif( $block && 1 == $settings['matching_rule'] ) {
			// 'ZZ' will NOT be blocked if it's not in the $list.
			if ( ( $list = $settings['black_list'] ) && FALSE !== strpos( $list, $validate['code'] ) )
				return $validate += array( 'result' => 'blocked' ); // can't overwrite existing result
		}

		return $validate + array( 'result' => 'passed' ); // can't overwrite existing result
	}

	/**
	 * Update statistics.
	 *
	 */
	public function update_statistics( $hook, $validate ) {
		$statistics = self::get_option( 'statistics' );

		if ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
			$statistics['IPv4']++;
		elseif ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$statistics['IPv6']++;

		@$statistics[ 'passed' !== $validate['result'] ? 'blocked' : 'passed' ]++;
		@$statistics['countries'][ $validate['code'] ]++;

		$provider = isset( $validate['provider'] ) ? $validate['provider'] : 'ZZ';
		if ( empty( $statistics['providers'][ $provider ] ) )
			$statistics['providers'][ $provider ] = array( 'count' => 0, 'time' => 0.0 );

		$statistics['providers'][ $provider ]['count']++;
		$statistics['providers'][ $provider ]['time'] += (float)@$validate['time'];

		@$statistics['daystats'][ mktime( 0, 0, 0 ) ][ $hook ]++;
		if ( count( $statistics['daystats'] ) > 30 )
			array_shift( $statistics['daystats'] );

		update_option( self::$option_keys['statistics'], $statistics );
	}

	/**
	 * Send response header with http status code and reason.
	 *
	 */
	public function send_response( $hook, $code ) {
		$code = (int   )apply_filters( self::PLUGIN_SLUG . "-{$hook}-status", (int)$code );
		$mesg = (string)apply_filters( self::PLUGIN_SLUG . "-{$hook}-reason", get_status_header_desc( $code ) );

		nocache_headers(); // nocache and response code

		switch ( (int)substr( "$code", 0, 1 ) ) {
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
			elseif ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) )
				FALSE !== ( @include( STYLESHEETPATH . "/{$code}.php" ) ) or // child  theme
				FALSE !== ( @include( TEMPLATEPATH   . "/{$code}.php" ) ) or // parent theme
				wp_die( $mesg, '', array( 'response' => $code, 'back_link' => TRUE ) );
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
	private function validate_ip( $hook, $settings, $block = TRUE, $die = TRUE ) {
		// set IP address to be validated
		$ips = array( self::get_ip_address() );

		// pick up all the IPs in HTTP_X_FORWARDED_FOR, HTTP_CLIENT_IP and etc.
		foreach ( explode( ',', $settings['validation']['proxy'] ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				foreach ( explode( ',', $_SERVER[ $var ] ) as $ip ) {
					if ( ! in_array( $ip = trim( $ip ), $ips ) && filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						array_unshift( $ips, $ip );
					}
				}
			}
		}

		// register auxiliary validation functions
		$var = self::PLUGIN_SLUG . "-${hook}";
		add_filter( $var, array( $this, 'check_auth' ), 9, 2 );
		add_filter( $var, array( $this, 'check_fail' ), 8, 2 );

		$settings['extra_ips'] = apply_filters( self::PLUGIN_SLUG . '-extra-ips', $settings['extra_ips'], $hook );
		$settings['extra_ips']['white_list'] && add_filter( $var, array( $this, 'check_ips_white' ), 7, 2 );
		$settings['extra_ips']['black_list'] && add_filter( $var, array( $this, 'check_ips_black' ), 7, 2 );

		// make valid provider name list
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
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
				$validate = self::validate_country( $validate, $settings, $block );

			// if one of IPs is blocked then stop
			if ( $result = ( 'passed' !== $validate['result'] ) )
				break;
		}

		// update cache
		IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings );

		if ( $die ) {
			// record log (0:no, 1:blocked, 2:passed, 3:unauth, 4:auth, 5:all)
			$var = (int)$settings['validation']['reclogs'];
			if ( ( 1 === $var &&   $result ) || // blocked
			     ( 2 === $var && ! $result ) || // passed
			     ( 3 === $var && ! $validate['auth'] ) || // unauthenticated
			     ( 4 === $var &&   $validate['auth'] ) || // authenticated
			     ( 5 === $var ) ) { // all
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
				IP_Geo_Block_Logs::record_log( $hook, $validate, $settings );
			}

			if ( $result ) {
				// update statistics
				if ( $settings['save_statistics'] )
					$this->update_statistics( $hook, $validate );

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
		if ( ! is_array( $comment ) || in_array( $comment['comment_type'], array( 'trackback', 'pingback' ) ) )
			$this->validate_ip( 'comment', self::get_option( 'settings' ) );

		return $comment;
	}

	/**
	 * Validate at xmlrpc.
	 *
	 */
	public function validate_xmlrpc( $something ) {
		$this->validate_ip( 'xmlrpc', self::get_option( 'settings' ) );
		return $something;
	}

	/**
	 * Validate at login.
	 *
	 */
	public function validate_login() {
		$settings = self::get_option( 'settings' );

		// enables to skip validation by country at login/out except BuddyPress signup
		$block = ( 1 == $settings['validation']['login'] ) ||
			( 'bp_' === substr( current_filter(), 0, 3 ) ) ||
			( isset( $_REQUEST['action'] ) && ! in_array( $_REQUEST['action'], array( 'login', 'logout' ) ) );

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

		switch ( $GLOBALS['pagenow'] ) {
		  case 'admin-ajax.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( "wp_ajax_nopriv_{$action}" );
			$type = 'ajax';
			break;

		  case 'admin-post.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( "admin_post_nopriv" . ($action ? "_{$action}" : "") );
			$type = 'ajax';
			break;

		  default:
			// if the request has no page and no action, skip WP-ZEP
			$zep = ( $page || $action ) ? TRUE : FALSE;
			$type = 'admin';
		}

		// setup WP-ZEP (2: WP-ZEP)
		if ( ( 2 & $settings['validation'][ $type ] ) && $zep ) {
			// redirect if valid nonce in referer
			$this->trace_nonce();

			// list of request with a specific query to bypass WP-ZEP
			$list = apply_filters( self::PLUGIN_SLUG . '-bypass-admins', array(
				'wp-compression-test', // wp-admin/includes/template.php
				'upload-attachment', 'imgedit-preview', 'bp_avatar_upload', // pluploader won't fire an event in "Media Library"
				'jetpack', 'authorize', 'jetpack_modules', 'atd_settings', // jetpack: multiple redirect for modules, cross domain ajax for proofreading
			) );

			// combination with vulnerable keys should be prevented to bypass WP-ZEP
			$in_action = in_array( $action, $list, TRUE );
			$in_page   = in_array( $page,   $list, TRUE );
			if ( ( ( $action xor $page ) && ( ! $in_action and ! $in_page ) ) ||
			     ( ( $action and $page ) && ( ! $in_action or  ! $in_page ) ) )
				add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_nonce' ), 5, 2 );
		}

		// register validation by malicious signature
		add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_signature' ), 6, 2 );

		// validate country by IP address (1: Block by country)
		$this->validate_ip( 'admin', $settings, 1 & $settings['validation'][ $type ] );
	}

	/**
	 * Validate at plugins/themes area.
	 *
	 */
	public function validate_direct() {
		// retrieve the name of plugins/themes
		$plugins = preg_quote( self::$content_dir['plugins'], '/' );
		$themes  = preg_quote( self::$content_dir['themes' ], '/' );
		$request = preg_replace( '!(//+|/\.+/)!', '/', $_SERVER['REQUEST_URI'] );

		if ( preg_match( "/(?:($plugins)|($themes))([^\/]*)\//", $request, $matches ) ) {
			// list of plugins/themes to bypass WP-ZEP
			$settings = self::get_option( 'settings' );
			$type = empty( $matches[2] ) ? 'plugins' : 'themes';
			$list = apply_filters( self::PLUGIN_SLUG . "-bypass-{$type}",
				'plugins' === $type ?
					/* list of plugins */ array() :
					/* list of themes  */ array()
			);

			// register validation by nonce (2: WP-ZEP)
			if ( ( 2 & $settings['validation'][ $type ] ) && ! in_array( $matches[3], $list, TRUE ) )
				add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_nonce' ), 5, 2 );

			// register validation by malicious signature
			add_filter( self::PLUGIN_SLUG . '-admin', array( $this, 'check_signature' ), 6, 2 );

			// validate country by IP address (1: Block by country)
			$validate = $this->validate_ip( 'admin', $settings, 1 & $settings['validation'][ $type ] );

			// if the validation is successful, execute the requested uri via rewrite.php
			if ( class_exists( 'IP_Geo_Block_Rewrite' ) )
				IP_Geo_Block_Rewrite::exec( $validate, $settings );
		}
	}

	/**
	 * Auxiliary validation functions
	 *
	 */
	public function auth_fail( $something = NULL ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		// Count up a number of fails when authentication is failed
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $this->remote_addr ) ) {
			$validate = self::make_validation( $this->remote_addr, array(
				'code' => $cache['code'],
				'fail' => TRUE,
				'result' => 'failed',
			) );

			$settings = self::get_option( 'settings' );
			IP_Geo_Block_API_Cache::update_cache( $cache['hook'], $validate, $settings );

			// (1) blocked, (3) unauthenticated, (5) all
			if ( (int)$settings['validation']['reclogs'] & 1 ) {
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
				IP_Geo_Block_Logs::record_log( $cache['hook'], $validate, $settings );
			}
		}

		return $something; // pass through
	}

	public function check_fail( $validate, $settings ) {
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );

		// if a number of fails is exceeded, then fail
		if ( $cache && $cache['fail'] >= $settings['login_fails'] )
			if ( empty( $validate['result'] ) || 'passed' === $validate['result'] )
				$validate['result'] = 'failed'; // can't overwrite existing result

		return $validate;
	}

	public function check_auth( $validate, $settings ) {
		// authentication should be prior to validation by country (can't overwrite existing result)
		return $validate['auth'] ? $validate + array( 'result' => 'passed' ) : $validate;
	}

	public function check_signature( $validate, $settings ) {
		$request = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

		foreach ( explode( ',', $settings['signature'] ) as $signature ) {
			if ( ( $signature = trim( $signature ) ) && FALSE !== strpos( $request, "/$signature" ) )
				return $validate + array( 'result' => 'badsig' ); // can't overwrite existing result
		}

		return $validate;
	}

	/**
	 * Validate nonce.
	 *
	 */
	public function check_nonce( $validate, $settings ) {
		$nonce = self::PLUGIN_SLUG . '-auth-nonce';

		if ( ! wp_verify_nonce( self::retrieve_nonce( $nonce ), $nonce ) )
			if ( empty( $validate['result'] ) || 'passed' === $validate['result'] )
				$validate['result'] = 'wp-zep'; // can't overwrite existing result

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

		if ( preg_match( "/$key=([\w]+)/", wp_get_referer(), $matches ) )
			return sanitize_text_field( $matches[1] );

		return NULL;
	}

	/**
	 * Verify specific ip addresses with CIDR.
	 *
	 * @example: 192.0.64.0/18 (Jetpack), 69.46.36.0/27 (WordFence)
	 */
	public function check_ips_white( $validate, $settings ) {
		return $this->check_ips( $validate, $settings, 0 );
	}

	public function check_ips_black( $validate, $settings ) {
		return $this->check_ips( $validate, $settings, 1 );
	}

	private function check_ips( $validate, $settings, $which ) {
		$ip = $validate['ip'];
		$ips = $settings['extra_ips'][ $which ? 'black_list' : 'white_list' ];

		if ( FALSE === strpos( $ips, '/' ) ) {
			if ( FALSE !== strpos( $ips, $ip ) )
				$validate += array( 'result' => $which ? 'extra' : 'passed' ); // can't overwrite existing result
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			require_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv4.php' );
			foreach ( explode( ',', $ips ) as $i ) {
				$j = explode( '/', $i = trim( $i ) );
				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) &&
				     Net_IPv4::ipInNetwork( $ip, ! empty( $j[1] ) ? $i : "$i/32" ) ) {
					$validate += array( 'result' => $which ? 'extra' : 'passed' ); // can't overwrite existing result
					break;
				}
			}
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			require_once( IP_GEO_BLOCK_PATH . 'includes/Net/IPv6.php' );
			foreach ( explode( ',', $ips ) as $i ) {
				$j = explode( '/', $i = trim( $i ) );
				if ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) &&
				     Net_IPv6::isInNetmask( $ip, ! empty( $j[1] ) ? $i : "$i/128" ) ) {
					$validate += array( 'result' => $which ? 'extra' : 'passed' ); // can't overwrite existing result
					break;
				}
			}
		}

		return $validate;
	}

	/**
	 * Cron scheduler.
	 *
	 */
	private static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		wp_clear_scheduled_hook( self::CRON_NAME, array( $immediate ) ); // @since 2.1.0

		if ( $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * (int)$update['cycle'];

			if ( FALSE === $immediate &&
				$now - (int)$db['ipv4_last'] < $cycle &&
				$now - (int)$db['ipv6_last'] < $cycle ) {
				$update['retry'] = 0;
				$next = max( (int)$db['ipv4_last'], (int)$db['ipv6_last'] ) +
					$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 6 );
			} else {
				++$update['retry'];
				$next = $now + ( $immediate ? 0 : DAY_IN_SECONDS );
			}

			wp_schedule_single_event( $next, self::CRON_NAME, array( $immediate ) );
		}
	}

	/**
	 * Database auto downloader.
	 *
	 */
	public static function download_database( $immediate = FALSE ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		$settings = self::get_option( 'settings' );
		$args = self::get_request_headers( $settings );

		// download database files (higher priority order)
		foreach ( $providers = IP_Geo_Block_Provider::get_addons() as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) )
				$res[ $provider ] = $geo->download( $settings[ $provider ], $args );
		}

		// re-schedule cron job
		if ( ! empty( $providers ) )
			self::schedule_cron_job( $settings['update'], $settings[ $providers[0] ], FALSE );

		// update option settings
		update_option( self::$option_keys['settings'], $settings );

		if ( $immediate ) {
			$validate = self::get_geolocation( NULL, $providers );
			$validate = self::validate_country( $validate, $settings );

			// if blocking may happen then disable validation
			if ( -1 != $settings['matching_rule'] && 'passed' !== $validate['result'] )
				$settings['matching_rule'] = -1;

			// setup country code if it needs to be initialized
			if ( -1 == $settings['matching_rule'] && 'ZZ' !== $validate['code'] ) {
				$settings['matching_rule'] = 0; // white list

				if ( FALSE === strpos( $settings['white_list'], $validate['code'] ) )
					$settings['white_list'] .= ( $settings['white_list'] ? ',' : '' ) . $validate['code'];
			}

			update_option( self::$option_keys['settings'], $settings );
		}

		return isset( $res ) ? $res : NULL;
	}

	// Kick off a cron job to download database immediately
	public static function exec_download( $plugin, $network_wide = FALSE ) {
		if ( $plugin === IP_GEO_BLOCK_BASE && current_user_can( 'manage_options' ) ) {
			add_action( self::CRON_NAME, array( __CLASS__, 'download_database' ), 10, 1 );
			$settings = self::get_option( 'settings' );
			self::schedule_cron_job( $settings['update'], NULL, TRUE );
		}
	}

}