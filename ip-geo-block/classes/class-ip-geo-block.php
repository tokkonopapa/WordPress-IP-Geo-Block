<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013, 2014 tokkonopapa
 */

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '1.3.0';
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

	// get default optional values
	public static function get_default( $name = 'settings' ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
		return IP_Geo_Block_Options::get_table( self::$option_keys[ $name ] );
	}

	// get optional values from wp_options
	public static function get_option( $name = 'settings' ) {
		$option = get_option( self::$option_keys[ $name ] );
		if ( FALSE === $option )
			$option = self::get_default( $name );
		return $option;
	}

	// http://codex.wordpress.org/Function_Reference/wp_remote_get
	public static function get_request_headers( $settings ) {
		global $wp_version;
		return apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-headers',
			array(
				'timeout' => $settings['timeout'],
				'user-agent' => "WordPress/$wp_version; " . self::PLUGIN_SLUG . ' ' . self::VERSION,
			)
		);
	}

	/**
	 * Initialize the plugin
	 * 
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		$settings = self::get_option( 'settings' );

		// check the package version and upgrade if needed
		if ( version_compare( $settings['version'], self::VERSION ) < 0 )
			$settings = self::activate();

		// the action hook which will be fired by cron job
		if ( $settings['update']['auto'] && ! has_action( self::CRON_NAME ) )
			add_action( self::CRON_NAME, array( 'IP_Geo_Block', 'download_database' ) );

		if ( $settings['validation']['comment'] ) {
			// Message text on comment form
			if ( $settings['comment']['pos'] ) {
				$pos = 'comment_form' . ( $settings['comment']['pos'] == 1 ? '_top' : '' );
				add_action( $pos, array( $this, 'comment_form_message' ) );
			}

			// action hook from wp-comments-post.php @since 2.8.0
			add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ) );
		}

		// action hook from wp-login.php @since 2.1.0, wp_signon() and wp_authenticate()
		if ( $settings['validation']['login'] ) {
			add_action( 'login_init', array( $this, 'validate_login' ) );
			add_action( 'wp_login',   array( $this, 'validate_login' ) );
			add_action( 'wp_login_failed', array( $this, 'auth_fail' ) );
		}

		// action hook from wp-admin/admin.php @since 3.1.0
		if ( $settings['validation']['admin'] ) {
			add_filter( 'secure_auth_redirect', array( $this, 'validate_admin' ) );
			add_filter( 'admin_init',           array( $this, 'validate_admin' ) );
		}
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
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );

		// upgrade options
		$settings = IP_Geo_Block_Options::upgrade();

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
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( self::TEXT_DOMAIN, FALSE, dirname( IP_GEO_BLOCK_BASE ) . '/languages/' );
	}

	/**
	 * Render a text message to the comment form.
	 *
	 */
	public function comment_form_message( $id ) {
		$msg = self::get_option( 'settings' );
		$msg = htmlspecialchars( $msg['comment']['msg'] );
		if ( $msg ) echo '<p id="', self::PLUGIN_SLUG, '-msg">', $msg, '</p>';
//		global $allowedtags;
//		if ( $msg = wp_kses( $msg['comment']['msg'], $allowedtags ) ) echo $msg;
	}

	/**
	 * Get country code from an ip address
	 *
	 */
	private function get_country( $ip, $settings ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

		// make providers list
		$list = array();
		$geo = IP_Geo_Block_Provider::get_providers( 'key', TRUE, TRUE );
		foreach ( $geo as $provider => $key ) {
			if ( ! empty( $settings['providers'][ $provider ] ) || (
			     ! isset( $settings['providers'][ $provider ] ) && NULL === $key ) ) {
				$list[] = $provider;
			}
		}

		// set arguments for wp_remote_get()
		$ret = array( 'ip' => $ip );
		$args = self::get_request_headers( $settings );

		foreach ( $list as $provider ) {
			$time = microtime( TRUE );
			$name = IP_Geo_Block_API::get_class_name( $provider );

			if ( $name ) {
				$key = ! empty( $settings['providers'][ $provider ] );
				$geo = new $name( $key ? $settings['providers'][ $provider ] : NULL );

				// get country code
				if ( $code = $geo->get_country( $ip, $args ) ) {
					return $ret + array(
						'time' => microtime( TRUE ) - $time,
						'code' => strtoupper( $code ),
						'provider' => $provider,
					);
				}
			}
		}

		return $ret;
	}

	/**
	 * Validate user's geolocation.
	 *
	 */
	private function validate_country( $validate, $settings ) {
		// matching rule
		$rule  = $settings['matching_rule'];
		$white = $settings['white_list'];
		$black = $settings['black_list'];

		if ( ! empty( $validate['code'] ) ) {
			if ( 0 == $rule && FALSE !== strpos( $white, $validate['code'] ) ||
			     1 == $rule && FALSE === strpos( $black, $validate['code'] ) )
				return $validate + array( 'result' => 'passed' ); // It may not be a spam
			else
				return $validate + array( 'result' => 'blocked'); // It could be a spam
		}

		return $validate + array( 'result' => 'unknown' ); // It can not be decided
	}

	/**
	 * Update statistics
	 *
	 */
	private function update_statistics( $validate ) {
		$statistics = self::get_option( 'statistics' );

		$result = isset( $validate['result'] ) ? $validate['result'] : 'passed';
		++$statistics[ $result ];

		if ( 'blocked' === $result ) {
			$ip = isset( $validate['ip'] ) ? $validate['ip'] : $_SERVER['REMOTE_ADDR'];
			$time = isset( $validate['time'] ) ? $validate['time'] : 0;
			$country = isset( $validate['code'] ) ? $validate['code'] : 'ZZ';
			$provider = isset( $validate['provider'] ) ? $validate['provider'] : 'ZZ';

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
				++$statistics['IPv4'];
			else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
				++$statistics['IPv6'];

			++$statistics['countries'][ $country ];

			if ( empty( $statistics['providers'][ $provider ] ) )
				$statistics['providers'][ $provider ] = array( 'count' => 0, 'time' => 0.0 );

			$statistics['providers'][ $provider ]['count']++;
			$statistics['providers'][ $provider ]['time'] += (float)$time;
		}

		update_option( self::$option_keys['statistics'], $statistics );
	}

	/**
	 * Send response header with http code.
	 *
	 */
	private function send_response( $code, $msg ) {
		nocache_headers(); // nocache and response code
		switch ( (int)substr( "$code", 0, 1 ) ) {
		  case 2: // 2xx Success
			header( 'Refresh: 0; url=' . get_site_url(), TRUE, $code ); // @since 3.0
			die();

		  case 3: // 3xx Redirection
			header( 'Location: http://blackhole.webpagetest.org/', TRUE, $code );
			die();

		  case 4: // 4xx Client Error
			wp_die( $msg, 'Error', array( 'response' => $code, 'back_link' => TRUE ) );

		  default: // 5xx Server Error
			status_header( $code ); // @since 2.0.0
			die();
		}
	}

	/**
	 * Validate ip address
	 *
	 * @param string  $hook       a name to identify action hook applied in this call.
	 * @param string  $mark_cache a symbolic charactor to mark on 'IP address in cache'.
	 * @param boolean $save_cache cache the IP addresse regardless of validation result.
	 * @param boolean $save_stat  update statistics regardless of validation result.
	 */
	private function validate_ip( $hook, $mark_cache, $save_cache, $save_stat ) {
		// apply custom filter of validation
		// @usage add_filter( "ip-geo-block-$hook", 'my_validation' );
		// @param $validate = array(
		//     'ip'       => $ip,       /* ip address                          */
		//     'time'     => $time,     /* processing time                     */
		//     'code'     => $code,     /* country code or reason of rejection */
		//     'provider' => $provider, /* the name of validator               */
		//     'result'   => $result,   /* 'passed', 'blocked' or 'unknown'    */
		// );
		$settings = self::get_option( 'settings' );
		$ip = apply_filters( self::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR'] );
		$validate = $this->get_country( $ip, $settings );
		$validate = apply_filters( self::PLUGIN_SLUG . "-$hook", $validate, $settings );

		// if no 'result' then validate ip address by country
		if ( empty( $validate['result'] ) )
			$validate = $this->validate_country( $validate, $settings );

		// update cache
		$passed = ( 'passed' === $validate['result'] );
		if ( $save_cache || ! $passed ) {
			static $count_call = TRUE;
			IP_Geo_Block_API_Cache::update_cache(
				$validate['ip'],
				array(
					'code' => $validate['code'] . $mark_cache,
					'call' => $count_call,
					'auth' => is_user_logged_in(),
				),
				$settings
			);
			$count_call = FALSE; // avoid multiple calls
		}

		// update statistics
		if ( $settings['save_statistics'] && $save_stat )
			$this->update_statistics( $validate );

		// validation succeeded
		if ( $passed ) return;

		// update statistics
		if ( $settings['save_statistics'] && ! $save_stat )
			$this->update_statistics( $validate );

		// write logs
		if ( defined( 'IP_GEO_BLOCK_LOG' ) && IP_GEO_BLOCK_LOG ) {
			require_once( IP_GEO_BLOCK_PATH . 'includes/writelog.php' );
			ip_geo_block_log( $ip, $hook, $validate );
		}

		// send response code to refuse
		$this->send_response(
			$settings['response_code'],
			__( 'Sorry, but you cannot be accepted.', self::TEXT_DOMAIN )
		);
	}

	/**
	 * Validate ip address on comment, login, admin
	 *                  ip address       statistics
	 * non-login users  cached           saved
	 * login users      cached / hidden  not saved
	 */
	public function validate_comment() {
		$this->validate_ip( 'comment', NULL, TRUE, TRUE );
	}

	public function validate_login() {
		if ( empty( $_REQUEST['action'] ) && isset( $_REQUEST['loggedout'] ) )
			return;

		add_filter( self::PLUGIN_SLUG . '-login', array( $this, 'auth_check' ), 10, 2 );
		$this->validate_ip( 'login', '+', TRUE, FALSE );
	}

	public function validate_admin( $secure ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
			$this->validate_ip( 'admin', '*', TRUE, FALSE );

		return $secure; // pass through
	}

	/**
	 * Authentication handling
	 *
	 */
	public function auth_fail( $username ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

		// Count up a number of fails when authentication is failed
		$ip = apply_filters( self::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR'] );
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( $ip ) ) {
			IP_Geo_Block_API_Cache::update_cache(
				$ip,
				array( 'code' => $cache['code'], 'fail' => ++$cache['fail'] ),
				get_option( self::$option_keys['settings'] )
			);
		}
	}

	public function auth_check( $validate, $settings ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

		// Check a number of authentication fails
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );
		if ( $cache && (int)$cache['fail'] >= $settings['login_fails'] )
			$validate += array( 'result' => 'blocked' );

		return $validate;
	}

	/**
	 * Schedule controller.
	 *
	 */
	public static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		wp_clear_scheduled_hook( self::CRON_NAME ); // @since 2.1.0

		if ( $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * $update['cycle'];

			if ( ! $immediate &&
				$now - (int)$db['ipv4_last'] < $cycle &&
				$now - (int)$db['ipv6_last'] < $cycle ) {
				$update['retry'] = 0;
				$next = max( $db['ipv4_last'], $db['ipv6_last'] ) +
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
	public static function download_database( $only = NULL ) {
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

		// update only the portion related to Maxmind
		if ( $only )
			$settings[ $only ] = TRUE;

		// update option settings
		update_option( self::$option_keys['settings'], $settings );

		return $res;
	}

}