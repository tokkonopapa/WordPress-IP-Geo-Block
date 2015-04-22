<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013-2015 tokkonopapa
 */

/**
 * Default path to the database file
 */
define( 'IP_GEO_BLOCK_DB_DIR', IP_GEO_BLOCK_PATH . 'database/' );

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '2.0.8';
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

	/**
	 * Initialize the plugin
	 * 
	 */
	private function __construct() {
		// check the package version and upgrade if needed
		$settings = self::get_option( 'settings' );
		if ( version_compare( $settings['version'], self::VERSION ) < 0 )
			$settings = self::activate();

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] && ! has_action( self::CRON_NAME ) )
			add_action( self::CRON_NAME, array( 'IP_Geo_Block', 'download_database' ) );

		if ( $settings['validation']['comment'] ) {
			// message text on comment form
			if ( $settings['comment']['pos'] ) {
				$pos = 'comment_form' . ( $settings['comment']['pos'] == 1 ? '_top' : '' );
				add_action( $pos, array( $this, 'comment_form_message' ) );
			}

			// wp-comments-post.php @since 2.8.0, wp-includes/comment.php @since 1.5.0
			add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ) );
			add_filter( 'preprocess_comment', array( $this, 'validate_trackback' ) );
		}

		// xmlrpc.php @since 3.1.0, wp-includes/class-wp-xmlrpc-server.php @since 3.5.0
		if ( $settings['validation']['xmlrpc'] ) {
			add_filter( 'wp_xmlrpc_server_class', array( $this, 'validate_admin' ) );
			add_filter( 'xmlrpc_login_error', array( $this, 'auth_fail' ) );
		}

		// wp-login.php @since 2.1.0, wp-includes/pluggable.php @since 2.5.0
		if ( $settings['validation']['login'] ) {
			add_action( 'login_init', array( $this, 'validate_login' ) );
			add_action( 'wp_login_failed', array( $this, 'auth_fail' ) );
		}

		// wp-admin/(admin.php|admin-apax.php|admin-post.php) @since 2.5.0
		if ( ( $settings['validation']['admin'] || 
		       $settings['validation']['ajax' ] ) && is_admin() )
			add_action( 'init', array( $this, 'validate_admin' ), $settings['priority'] );

		// Load authenticated nonce
		if ( is_user_logged_in() )
			add_action( 'wp_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ), $settings['priority'] );
	}

	// Register and enqueue admin-specific style sheet and JavaScript.
	public static function enqueue_nonce() {
		wp_enqueue_script( $handle = IP_Geo_Block::PLUGIN_SLUG . '-auth-nonce',
			plugins_url( 'admin/js/auth-nonce.js', IP_GEO_BLOCK_BASE ),
			array( 'jquery' ), IP_Geo_Block::VERSION
		);

		wp_localize_script( $handle, 'IP_GEO_BLOCK_AUTH',
			array( 'nonce' => wp_create_nonce( $handle ) )
		);
	}

	// get default optional values
	public static function get_default( $name = 'settings' ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		return IP_Geo_Block_Options::get_table( self::$option_keys[ $name ] );
	}

	// get optional values from wp_options
	public static function get_option( $name = 'settings' ) {
		if ( FALSE === ( $option = get_option( self::$option_keys[ $name ] ) ) )
			$option = self::get_default( $name );

		return $option;
	}

	// http://codex.wordpress.org/Function_Reference/wp_remote_get
	public static function get_request_headers( $settings ) {
		global $wp_version;
		return apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-headers',
			array(
				'timeout' => (int)$settings['timeout'],
				'user-agent' => "WordPress/$wp_version; " . self::PLUGIN_SLUG . ' ' . self::VERSION,
			)
		);
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Register options into database table when the plugin is activated.
	 *
	 */
	public static function activate( $network_wide = NULL ) {
		// upgrade options
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		$settings = IP_Geo_Block_Options::upgrade();

		// create log
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
		IP_Geo_Block_Logs::create_log();

		// execute to download immediately
		if ( $settings['update']['auto'] ) {
			add_action( self::CRON_NAME, array( 'IP_Geo_Block', 'download_database' ) );
			self::schedule_cron_job( $settings['update'], $settings['maxmind'], TRUE );
		}

		return $settings;
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = NULL ) {
		// cancel schedule
		wp_clear_scheduled_hook( self::CRON_NAME ); // @since 2.1.0
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		$settings = self::get_option( 'settings' );

		if ( $settings['clean_uninstall'] ) {
			// delete settings options
			delete_option( self::$option_keys['settings'  ] ); // @since 1.2.0
			delete_option( self::$option_keys['statistics'] ); // @since 1.2.0

			// delete IP address cache
			delete_transient( self::CACHE_KEY ); // @since 2.8

			// delete log
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::delete_log();
		}
	}

	/**
	 * Render a text message to the comment form.
	 *
	 */
	public function comment_form_message( $id ) {
		$msg = self::get_option( 'settings' );
		$msg = esc_html( $msg['comment']['msg'] ); // Escaping for HTML blocks
		if ( $msg ) echo '<p id="', self::PLUGIN_SLUG, '-msg">', $msg, '</p>';
//		global $allowedtags;
//		if ( $msg = wp_kses( $msg['comment']['msg'], $allowedtags ) ) echo $msg;
	}

	/**
	 * Get geolocation and country code from an ip address
	 *
	 */
	public static function get_geolocation( $ip = NULL, $providers = array(), $callback = 'get_country' ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		return self::_get_geolocation(
			$ip ? $ip : $_SERVER['REMOTE_ADDR'],
			self::get_option( 'settings' ), $providers, $callback
		);
	}

	private static function _get_geolocation( $ip, $settings, $providers = array(), $callback = 'get_country' ) {
		// make valid providers list
		if ( empty( $providers ) )
			$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// set arguments for wp_remote_get()
		$args = self::get_request_headers( $settings );

		foreach ( $providers as $provider ) {
			$time = microtime( TRUE );
			$name = IP_Geo_Block_API::get_class_name( $provider );

			if ( $name ) {
				$key = ! empty( $settings['providers'][ $provider ] );
				$geo = new $name( $key ? $settings['providers'][ $provider ] : NULL );

				// get country code
				if ( $code = $geo->$callback( $ip, $args ) ) {
					$ret = array(
						'ip' => $ip,
						'auth' => get_current_user_id(),
						'time' => microtime( TRUE ) - $time,
						'provider' => $provider,
					);

					return is_array( $code ) ?
						$ret + $code :
						$ret + array( 'code' => $code );
				}
			}
		}

		return array(
			'ip' => $ip,
			'auth' => get_current_user_id(),
			'time' => 0,
			'provider'     => 'ZZ',
			'code'         => 'ZZ', // for get_country()
			'countryCode'  => 'ZZ', // for get_location()
			'errorMessage' => 'unknown',
		);
	}

	/**
	 * Validate user's geolocation.
	 *
	 */
	private function validate_country( $validate, $settings ) {
		if ( 0 == $settings['matching_rule'] ) {
			// Whitelist
			$list = $settings['white_list'];
			if ( ! $list || FALSE !== strpos( $list, $validate['code'] ) )
				return $validate + array( 'result' => 'passed' );
			else if ( 'ZZ' !== $validate['code'] )
				return $validate + array( 'result' => 'blocked' );
		}
		else {
			// Blacklist
			$list = $settings['black_list'];
			if ( $list && FALSE !== strpos( $list, $validate['code'] ) )
				return $validate + array( 'result' => 'blocked' );
			else if ( 'ZZ' !== $validate['code'] )
				return $validate + array( 'result' => 'passed' );
		}

		return $validate + array( 'result' => 'unknown' );
	}

	/**
	 * Update statistics
	 *
	 */
	private function update_statistics( $validate ) {
		$statistics = self::get_option( 'statistics' );

		if ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
			$statistics['IPv4']++;
		else if ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$statistics['IPv6']++;

		@$statistics[ $validate['result'] ]++;
		@$statistics['countries'][ $validate['code'] ]++;

		$provider = $validate['provider'] ? $validate['provider'] : 'ZZ';
		if ( empty( $statistics['providers'][ $provider ] ) )
			$statistics['providers'][ $provider ] = array( 'count' => 0, 'time' => 0.0 );

		$statistics['providers'][ $provider ]['count']++;
		$statistics['providers'][ $provider ]['time'] += (float)$validate['time'];

		update_option( self::$option_keys['statistics'], $statistics );
	}

	/**
	 * Send response header with http code.
	 *
	 */
	private function send_response( $code ) {
		nocache_headers(); // nocache and response code
		switch ( (int)substr( "$code", 0, 1 ) ) {
		  case 2: // 2xx Success
			header( 'Refresh: 0; url=' . home_url(), TRUE, (int)$code ); // @since 3.0
			die();

		  case 3: // 3xx Redirection
			wp_redirect( 'http://blackhole.webpagetest.org/', (int)$code );
			die();

		  case 4: // 4xx Client Error ('text/html' is only for comment and login)
			if ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) )
				wp_die( get_status_header_desc( $code ), '',
					array( 'response' => (int)$code, 'back_link' => TRUE )
				);

		  default: // 5xx Server Error
			status_header( (int)$code ); // @since 2.0.0
			die();
		}
	}

	/**
	 * Validate ip address
	 *
	 * @param string $hook a name to identify action hook applied in this call.
	 * @param array $settings option settings
	 */
	private function validate_ip( $hook, $settings ) {
		// set IP address to be validated
		$ips = array(
			$this->remote_addr = (string) apply_filters(
				self::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR']
			)
		);

		// pick up all the IPs in HTTP_X_FORWARDED_FOR, HTTP_CLIENT_IP and etc.
		foreach ( explode( ',', $settings['validation']['proxy'] ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				foreach ( explode( ',', $_SERVER[ $var ] ) as $ip ) {
					if ( ! in_array( $ip = trim( $ip ), $ips ) &&
					     filter_var( $ip, FILTER_VALIDATE_IP ) ) {
						array_unshift( $ips, $ip );
					}
				}
			}
		}

		// check if the authentication has been already failed
		$var = self::PLUGIN_SLUG . "-${hook}";
		add_filter( $var, array( $this, 'auth_check' ), 10, 2 );

		// make valid provider name list
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
		$providers = IP_Geo_Block_Provider::get_valid_providers( $settings['providers'] );

		// apply custom filter of validation
		// @usage add_filter( "ip-geo-block-$hook", 'my_validation' );
		// @param $validate = array(
		//     'ip'       => $ip,       /* validated ip address                */
		//     'auth'     => $auth,     /* authenticated or not                */
		//     'code'     => $code,     /* country code or reason of rejection */
		//     'result'   => $result,   /* 'passed', 'blocked' or 'unknown'    */
		// );
		foreach ( $ips as $ip ) {
			$validate = self::_get_geolocation( $ip, $settings, $providers );
			$validate = apply_filters( $var, $validate, $settings );

			// if no 'result' then validate ip address by country
			if ( empty( $validate['result'] ) )
				$validate = $this->validate_country( $validate, $settings );

			// if one of IPs is blocked then stop
			if ( $blocked = ( 'passed' !== $validate['result'] ) ) {
				$this->remote_addr = $ip;
				break;
			}
		}

		// update cache
		IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings );

		// record log (0:no, 1:blocked, 2:passed, 3:unauth, 4:auth, 5:all)
		$var = (int)$settings['validation']['reclogs'];
		if ( ( 1 === $var &&   $blocked ) || // blocked, unknown
		     ( 2 === $var && ! $blocked ) || // passed
		     ( 3 === $var && ! $validate['auth'] ) || // unauthenticated
		     ( 4 === $var &&   $validate['auth'] ) || // authenticated
		     ( 5 === $var ) ) { // all
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::record_log( $hook, $validate, $settings );
		}

		if ( $blocked ) {
			// update statistics
			if ( $settings['save_statistics'] )
				$this->update_statistics( $validate );

			// send response code to refuse
			$this->send_response( $settings['response_code'] );
		}
	}

	/**
	 * Validate ip address at comment, login, admin, xmlrpc
	 *
	 */
	public function validate_comment() {
		$this->validate_ip( 'comment', self::get_option( 'settings' ) );
	}

	public function validate_trackback( $commentdata ) {
		if ( 'trackback' === $commentdata['comment_type'] )
			$this->validate_ip( 'comment', self::get_option( 'settings' ) );

		return $commentdata;
	}

	public function validate_login() {
		if ( isset( $_REQUEST['action'] ) || empty( $_REQUEST['loggedout'] ) )
			$this->validate_ip( 'login', self::get_option( 'settings' ) );
	}

	public function validate_admin( $something ) {
		global $pagenow; // http://codex.wordpress.org/Global_Variables
		$settings = self::get_option( 'settings' );

		if ( isset( $_REQUEST['action'] ) ) {
			switch ( $pagenow ) {
			  case 'admin-ajax.php':
				if ( ! has_action( "wp_ajax_nopriv_{$_REQUEST['action']}" ) )
					$type = 'ajax';
				break;
			  case 'admin-post.php':
				if ( ! has_action( "admin_post_nopriv_{$_REQUEST['action']}" ) )
					$type = 'ajax';
				break;
			  case 'admin.php':
				$type = 'admin';
			}
		}

		if ( isset( $type ) && (int)$settings['validation'][ $type ] === 2 )
			add_filter( self::PLUGIN_SLUG . "-admin", array( $this, 'check_nonce' ), 10, 2 );

		$this->validate_ip( 'xmlrpc.php' === $pagenow ? 'xmlrpc' : 'admin', $settings );

		return $something; // pass through
	}

	/**
	 * Authentication handling
	 *
	 */
	public function auth_fail( $something ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		$settings = self::get_option( 'settings' );

		// Count up a number of fails when authentication is failed
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $this->remote_addr ) ) {
			$hook = $cache['hook'];
			$validate = array(
				'ip' => $this->remote_addr,
				'code' => $cache['code'],
				'auth' => get_current_user_id(),
				'fail' => TRUE,
				'result' => 'failed',
			);

			IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings );

			// (1) blocked, unknown, (3) unauthenticated, (5) all
			if ( (int)$settings['validation']['reclogs'] & 1 ) {
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
				IP_Geo_Block_Logs::record_log( $hook, $validate, $settings );
			}
		}

		return $something; // pass through
	}

	public function auth_check( $validate, $settings ) {
		// Check a number of authentication fails
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );
		if ( $cache && $cache['fail'] >= $settings['login_fails'] )
			$validate += array( 'result' => 'blocked' ); // can not overwrite

		return $validate;
	}

	/**
	 * validate requested queries via admin-(ajax|post).php
	 *
	 */
	public function check_nonce( $validate, $settings ) {
		$admin_actions = apply_filters( self::PLUGIN_SLUG . '-admin-actions', array(
			// list of excluded admin actions
		) );

		// check admin actions (core ajax calls in wp-admin/includes/ajax-actions.php)
		$login = is_user_logged_in(); // or user_can_access_admin_page()
		if ( ( $login && ! empty( $_REQUEST['action'] ) ) && (
			in_array( $_REQUEST['action'], $admin_actions ) ||
			function_exists( 'wp_ajax_' . str_replace( '-', '_', $_REQUEST['action'] ) )
		) ) {
			return $validate; // still potentially be blocked by country code
		}

		// check authenticated nonce
		$action = self::PLUGIN_SLUG . '-auth-nonce';
		if ( ! $login || empty( $_REQUEST[ $action ] ) ||
		     ! wp_verify_nonce( $_REQUEST[ $action ], $action ) )
			$validate['result'] = 'blocked';

		return $validate;
	}

	/**
	 * Cron scheduler.
	 *
	 */
	public static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		wp_clear_scheduled_hook( self::CRON_NAME ); // @since 2.1.0

		if ( $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * (int)$update['cycle'];

			if ( ! $immediate &&
				$now - (int)$db['ipv4_last'] < $cycle &&
				$now - (int)$db['ipv6_last'] < $cycle ) {
				$update['retry'] = 0;
				$next = max( (int)$db['ipv4_last'], (int)$db['ipv6_last'] ) +
					$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 6 );
			} else {
				++$update['retry'];
				$next = $now + ( $immediate ? 0 : DAY_IN_SECONDS );
			}

			wp_schedule_single_event( $next, self::CRON_NAME );
		}
	}

	/**
	 * Database auto downloader.
	 *
	 */
	public static function download_database() {
		require_once( IP_GEO_BLOCK_PATH . 'includes/download.php' );

		// download database files
		$settings = self::get_option( 'settings' );
		$res = ip_geo_block_download(
			$settings['maxmind'],
			IP_GEO_BLOCK_DB_DIR,
			self::get_request_headers( $settings )
		);

		// re-schedule cron job
		self::schedule_cron_job( $settings['update'], $settings['maxmind'] );

		// update option settings
		update_option( self::$option_keys['settings'], $settings );

		return $res;
	}

}