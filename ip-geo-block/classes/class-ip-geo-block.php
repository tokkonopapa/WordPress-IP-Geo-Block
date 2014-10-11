<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '1.2.0';
	const TEXT_DOMAIN = 'ip-geo-block';
	const PLUGIN_SLUG = 'ip-geo-block';
	const CACHE_KEY   = 'ip-geo-block-cache';
	const CRON_NAME   = 'ip_geo_block_cron';

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 */
	protected static $option_table = array(

		// settings (should be read on every page that has comment form)
		'ip_geo_block_settings' => array(
			'version'         => '1.2',   // Version of option data
			// from version 1.0
			'providers'       => array(), // List of providers and API keys
			'comment'         => array(   // Message on the comment form
				'pos'         => 0,       // Position (0:none, 1:top, 2:bottom)
				'msg'         => NULL,    // Message text on comment form
			),
			'matching_rule'   => 0,       // 0:white list, 1:black list
			'white_list'      => NULL,    // Comma separeted country code
			'black_list'      => NULL,    // Comma separeted country code
			'timeout'         => 5,       // Timeout in second
			'response_code'   => 403,     // Response code
			'save_statistics' => FALSE,   // Save statistics
			'clean_uninstall' => FALSE,   // Remove all savings from DB
			// from version 1.1
			'cache_hold'      => 10,      // Max entries in cache
			'cache_time'      => HOUR_IN_SECONDS, // @since 3.5
			// from version 1.2
			'flags'           => array(), // Multi purpose flags
			'login_fails'     => 5,       // Max counts of login fail
			'validation'      => array(   // Action hook for validation
				'comment'     => TRUE,    // For comment spam
				'login'       => FALSE,   // For login intrusion
				'admin'       => FALSE,   // For admin intrusion
			),
			'update'          => array(   // Updating IP address DB
				'auto'        => TRUE,    // Auto updating of DB file
				'retry'       => 0,       // Number of retry to download
				'cycle'       => 30,      // Updating cycle (days)
			),
			'maxmind'         => array(   // Maxmind
				'ipv4_path'   => NULL,    // Path to IPv4 DB file
				'ipv6_path'   => NULL,    // Path to IPv6 DB file
				'ipv4_last'   => NULL,    // Last-Modified of DB file
				'ipv6_last'   => NULL,    // Last-Modified of DB file
			),
			'ip2location'     => array(   // IP2Location
				'ipv4_path'   => NULL,    // Path to IPv4 DB file
				'ipv6_path'   => NULL,    // Path to IPv6 DB file
				'ipv4_last'   => NULL,    // Last-Modified of DB file
				'ipv6_last'   => NULL,    // Last-Modified of DB file
			),
		),

		// statistics (should be read when comment has posted)
		'ip_geo_block_statistics' => array(
			'passed'    => NULL,
			'blocked'   => NULL,
			'unknown'   => NULL,
			'IPv4'      => NULL,
			'IPv6'      => NULL,
			'countries' => array(),
			'providers' => array(),
		),
	);

	// option table accessor by name
	public static $option_keys = array(
		'settings'   => 'ip_geo_block_settings',
		'statistics' => 'ip_geo_block_statistics',
	);

	// get default settings values
	public static function get_defaults( $name = 'settings' ) {
		return self::$option_table[ self::$option_keys[ $name ] ];
	}

	// http://codex.wordpress.org/Function_Reference/wp_remote_get
	public static function get_request_headers( $options ) {
		global $wp_version;
		return apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-headers',
			array(
				'timeout' => $options['timeout'],
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

		$opts = get_option( self::$option_keys['settings'] );
		if ( $opts['validation']['comment'] ) {
			// Message text on comment form
			if ( $opts['comment']['pos'] ) {
				$pos = 'comment_form' . ( $opts['comment']['pos'] == 1 ? '_top' : '' );
				add_action( $pos, array( $this, 'comment_form_message' ) );
			}

			// action hook from wp-comments-post.php @since 2.8.0
			add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ) );
		}

		// action hook from wp-login.php @since 2.1.0, wp_signon() and wp_authenticate()
		if ( $opts['validation']['login'] ) {
			add_action( 'login_init', array( $this, 'validate_login' ) );
			add_action( 'wp_login',   array( $this, 'validate_login' ) );
			add_action( 'wp_login_failed', array( $this, 'auth_fail' ) );
		}

		// action hook from wp-admin/admin.php @since 3.1.0
		if ( $opts['validation']['admin'] ) {
			add_filter( 'secure_auth_redirect', array( $this, 'validate_admin' ) );
			add_filter( 'admin_init',           array( $this, 'validate_admin' ) );
		}

		// action hook from download cron job
		if ( $opts['update']['auto'] )
			add_action( self::CRON_NAME, array( $this, 'download_database' ) );
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
	public static function activate( $network_wide ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/upgrade.php' );
		$settings = ip_geo_block_upgrade();

		// schedule auto updating
		if ( $settings['update']['auto'] )
			self::schedule_cron_job( $settings['update'], $settings['maxmind'], TRUE );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide ) {
		// cancel schedule
		if ( wp_next_scheduled( self::CRON_NAME ) ) // @since 2.1.0
			wp_clear_scheduled_hook( self::CRON_NAME );
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		$settings = get_option( self::$option_keys['settings'] );

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
	 * Render a message to the comment form.
	 *
	 */
	public function comment_form_message( $id ) {
		$msg = get_option( self::$option_keys['settings'] );
		$msg = htmlspecialchars( $msg['comment']['msg'] );
		if ( $msg ) echo '<p id="', self::PLUGIN_SLUG, '-msg">', $msg, '</p>';
//		global $allowedtags;
//		if ( $msg = wp_kses( $msg['comment']['msg'], $allowedtags ) ) echo $msg;
	}

	/**
	 * Get country code
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
		$statistics = get_option( self::$option_keys['statistics'] );

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
		$ip = apply_filters( self::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR'] );
		$settings = get_option( self::$option_keys['settings'] );
		$validate = $this->get_country( $ip, $settings );
		$validate = apply_filters( self::PLUGIN_SLUG . "-$hook", $validate, $settings );

		// if no 'result' then validate ip address by country
		if ( empty( $validate['result'] ) )
			$validate = $this->validate_country( $validate, $settings );

		// update cache
		$passed = ( 'passed' === $validate['result'] );
		if ( $save_cache || ! $passed )
			IP_Geo_Block_API_Cache::update_cache(
				$validate['ip'],
				array(
					'code' => $validate['code'] . $mark_cache,
					'auth' => is_user_logged_in(),
				),
				$settings
			);

		// update statistics
		if ( $settings['save_statistics'] && $save_stat )
			$this->update_statistics( $validate );

		// validation succeeded
		if ( $passed ) return;

		// update statistics
		if ( $settings['save_statistics'] && ! $save_stat )
			$this->update_statistics( $validate );

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
	public function validate_comment() {// debug_log('validate_comment');
		$this->validate_ip( 'comment', NULL, TRUE, TRUE );
	}

	public function validate_login() {// debug_trace('validate_login');
		if ( isset( $_REQUEST['loggedout'] ) )
			return;

		add_filter( self::PLUGIN_SLUG . '-login', array( $this, 'auth_check' ), 10, 2 );
		$this->validate_ip( 'login', '+', TRUE, FALSE );
	}

	public function validate_admin( $secure ) {// debug_log('validate_admin');
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
			$this->validate_ip( 'admin', '*', TRUE, FALSE );

		return $secure; // pass through
	}

	/**
	 * Authentication handling
	 *
	 */
	public function auth_fail( $username ) {// debug_trace('auth_fail');
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

	public function auth_check( $validate, $settings ) {// debug_log('auth_check');
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

		// Check a number of authentication fails
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'] );
		if ( $cache && (int)$cache['fail'] > $settings['login_fails'] )
			$validate += array( 'result' => 'blocked' );

		return $validate;
	}

	/**
	 * Schedule controller.
	 *
	 */
	public static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		$schedule = wp_next_scheduled( self::CRON_NAME ); // @since 2.1.0

		if ( $schedule && ! $update['auto'] )
			wp_clear_scheduled_hook( self::CRON_NAME );

		else if ( ! $schedule && $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * $update['cycle'];

			if ( FALSE === $immediate &&
				$now - $db['ipv4_last'] < $cycle &&
				$now - $db['ipv6_last'] < $cycle ) {
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
	public function download_database( $only = NULL ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/download.php' );

		// download database
		$settings = get_option( self::$option_keys['settings'] );
		$res = ip_geo_block_download(
			$settings['maxmind'],
			trailingslashit(
				apply_filters( self::PLUGIN_SLUG . '-maxmind-dir', IP_GEO_BLOCK_DB_DIR )
			), 
			self::get_request_headers( $settings )
		);

		// re-schedule cron job
		self::schedule_cron_job( $settings['update'], $settings['maxmind'] );

		// limit options to be updated
		if ( $only )
			$settings[ $only ] = TRUE;

		// update option settings
		update_option( self::$option_keys['settings'], $settings );

		return $res;
	}

}