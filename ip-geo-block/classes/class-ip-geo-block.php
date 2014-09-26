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
			'order'           => 0,       // Next order of provider (spare for future)
			'providers'       => array(), // List of providers and API keys
			'comment'         => array(   // Message on the comment form
				'pos'         => 0,       // Position (0:none, 1:top, 2:bottom)
				'msg'         => '',      // Message text on comment form
			),
			'matching_rule'   => 0,       // 0:white list, 1:black list
			'white_list'      => '',      // Comma separeted country code
			'black_list'      => '',      // Comma separeted country code
			'timeout'         => 5,       // Timeout in second
			'response_code'   => 403,     // Response code
			'save_statistics' => FALSE,   // Save statistics
			'clean_uninstall' => FALSE,   // Remove all savings from DB
			// from version 1.1
			'cache_hold'      => 10,      // Max entries in cache
			'cache_time'      => HOUR_IN_SECONDS, // @since 3.5
			// from version 1.2
			'validation'      => array(   // Action hook for validation
				'comment'     => TRUE,    // For comment spam
				'login'       => FALSE,   // For login intrusion
			),
			'update'          => array(   // Updating IP address DB
				'auto'        => TRUE,    // Auto updating of DB file
				'retry'       => 0,       // Number of retry to download
				'cycle'       => 30,      // Updating cycle (days)
			),
			'maxmind'         => array(   // Maxmind
				'ipv4_path'   => '',      // Path to IPv4 DB file
				'ipv6_path'   => '',      // Path to IPv6 DB file
				'ipv4_last'   => '',      // Last-Modified of DB file
				'ipv6_last'   => '',      // Last-Modified of DB file
			),
			'ip2location'     => array(   // IP2Location
				'ipv4_path'   => '',      // Path to IPv4 DB file
				'ipv6_path'   => '',      // Path to IPv6 DB file
				'ipv4_last'   => '',      // Last-Modified of DB file
				'ipv6_last'   => '',      // Last-Modified of DB file
			),
		),

		// statistics (should be read when comment has posted)
		'ip_geo_block_statistics' => array(
			'passed'    => 0,
			'blocked'   => 0,
			'unknown'   => 0,
			'IPv4'      => 0,
			'IPv6'      => 0,
			'countries' => array(),
			'providers' => array(),
		),
	);

	// option table accessor by name
	public static $option_keys = array(
		'settings'   => 'ip_geo_block_settings',
		'statistics' => 'ip_geo_block_statistics',
	);

	public static function get_defaults( $name = 'settings' ) {
		return self::$option_table[ self::$option_keys[ $name ] ];
	}

	public static function get_request_headers( $options ) {
		global $wp_version;
		return apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-headers',
			array(
				'timeout' => $options['timeout'],
				'user-agent' =>
					"WordPress/$wp_version; " . self::PLUGIN_SLUG . ' ' . self::VERSION,
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
				add_action( $pos, array( $this, 'comment_form_message' ), 10 );
			}

			// action hook from wp-comments-post.php @since 2.8.0
			// https://developer.wordpress.org/reference/hooks/pre_comment_on_post/
			add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ), 1 );
		}

		// action hook from wp-login.php @since 2.1.0
		// https://developer.wordpress.org/reference/hooks/login_init/
		if ( $opts['validation']['login'] )
			add_action( 'login_init', array( $this, 'validate_login' ), 1 );

		// action hook from download cron job
		if ( $opts['update']['auto'] )
			add_action( 'ip_geo_block_cron', 'IP_Geo_Block::download_database' );
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
		// find IP2Location DB
		$tmp = array(
			WP_CONTENT_DIR . '/ip2location/',
			WP_CONTENT_DIR . '/plugins/ip2location-tags/',
			WP_CONTENT_DIR . '/plugins/ip2location-variables/',
			WP_CONTENT_DIR . '/plugins/ip2location-blocker/',
		);

		// get path to IP2Location DB
		$ip2 = trailingslashit(
			apply_filters( self::PLUGIN_SLUG . '-ip2location-path', $tmp[0] )
		);
		foreach ( $tmp as $name ) {
			if ( is_readable( "${name}database.bin" ) ) {
				$ip2 = "${name}database.bin";
				break;
			}
		}

		$name = array_keys( self::$option_table );
		$opts = get_option( $name[0] );

		if ( FALSE === $opts ) {
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

			// get country code from admin's IP address and set it into white list
			$opts = self::get_request_headers( self::$option_table[ $name[0] ] );
			foreach ( array( 'ipinfo.io', 'Telize', 'IP-Json' ) as $provider ) {
				if ( $provider = IP_Geo_Block_API::get_class_name( $provider ) ) {
					$tmp = new $provider( NULL );
					if ( $tmp = $tmp->get_country( $_SERVER['REMOTE_ADDR'], $opts ) ) {
						self::$option_table[ $name[0] ]['white_list'] = $tmp;
						break;
					}
				}
			}

			// set IP2Location
			self::$option_table[ $name[0] ]['ip2location']['ipv4_path'] = $ip2;

			// create new option table
			$opts = self::$option_table[ $name[0] ];
			add_option( $name[0], self::$option_table[ $name[0] ], '', 'yes' );
			add_option( $name[1], self::$option_table[ $name[1] ], '', 'no'  );
		}

		else {
			// update format of option settings
			if ( version_compare( $opts['version'], '1.1' ) < 0 ) {
				$opts['cache_hold'] = self::$option_table[ $name[0] ]['cache_hold'];
				$opts['cache_time'] = self::$option_table[ $name[0] ]['cache_time'];
			}

			if ( version_compare( $opts['version'], '1.2' ) < 0 ) {
				unset( $opts['ip2location'] );
				$opts['validation' ] = self::$option_table[ $name[0] ]['validation' ];
				$opts['update'     ] = self::$option_table[ $name[0] ]['update'     ];
				$opts['maxmind'    ] = self::$option_table[ $name[0] ]['maxmind'    ];
				$opts['ip2location'] = self::$option_table[ $name[0] ]['ip2location'];
			}

			$opts['version'] = self::$option_table[ $name[0] ]['version'];

			// update IP2Location
			$opts['ip2location']['ipv4_path'] = $ip2;

			// update option table
			update_option( $name[0], $opts );
		}

		// schedule auto updating
		if ( $opts['update']['auto'] )
			self::schedule_cron_job( $opts['update'], $opts['maxmind'], TRUE );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide ) {
		// self::uninstall();  // for debug

		// cancel schedule
		if ( wp_next_scheduled( 'ip_geo_block_cron' ) ) // @since 2.1.0
			wp_clear_scheduled_hook( 'ip_geo_block_cron' );
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		$name = array_keys( self::$option_table );
		$settings = get_option( $name[0] );

		if ( $settings['clean_uninstall'] ) {
			// delete settings options
			foreach ( $name as $key ) {
				delete_option( $key ); // @since 1.2.0
			}

			// delete IP address cache
			delete_transient( self::CACHE_KEY ); // @since 2.8
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( self::TEXT_DOMAIN, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
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
	 * Validate user's geolocation.
	 *
	 */
	private function validate_country( $validate, $settings ) {
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

		// matching rule
		$rule  = $settings['matching_rule'];
		$white = $settings['white_list'];
		$black = $settings['black_list'];

		// set arguments for wp_remote_get()
		// http://codex.wordpress.org/Function_Reference/wp_remote_get
		$args = self::get_request_headers( $settings );

		foreach ( $list as $provider ) {
			$time = microtime( TRUE );
			$name = IP_Geo_Block_API::get_class_name( $provider );

			if ( $name ) {
				$key = ! empty( $settings['providers'][ $provider ] );
				$geo = new $name( $key ? $settings['providers'][ $provider ] : NULL );

				// get country code
				$code = strtoupper( $geo->get_country( $validate['ip'], $args ) );

				if ( $code ) {
					$validate += array(
						'time' => microtime( TRUE ) - $time,
						'code' => $code,
						'provider' => $provider,
					);

					if ( 0 == $rule && FALSE !== strpos( $white, $code ) ||
						 1 == $rule && FALSE === strpos( $black, $code ) )
						// It may not be a spam
						return $validate + array( 'result' => 'passed' );
					else
						// It could be a spam
						return $validate + array( 'result' => 'blocked');
				}
			}
		}

		// It can not be decided
		return $validate + array( 'result' => 'unknown' );
	}

	/**
	 * Update statistics
	 *
	 */
	private function update_statistics( $validate ) {
		$statistics = get_option( self::$option_keys['statistics'] );

		$result = isset( $validate['result'] ) ? $validate['result'] : 'passed';
		$statistics[ $result ] = intval( $statistics[ $result ] ) + 1;

		if ( 'blocked' === $result ) {
			$ip = isset( $validate['ip'] ) ? $validate['ip'] : $_SERVER['REMOTE_ADDR'];
			$time = isset( $validate['time'] ) ? $validate['time'] : 0;
			$country = isset( $validate['code'] ) ? $validate['code'] : 'ZZ';
			$provider = isset( $validate['provider'] ) ? $validate['provider'] : 'ZZ';

			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
				$statistics['IPv4'] = intval( $statistics['IPv4'] ) + 1;

			else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
				$statistics['IPv6'] = intval( $statistics['IPv6'] ) + 1;

			if ( isset( $statistics['providers'][ $provider ] ) )
				$stat = $statistics['providers'][ $provider ];
			else
				$stat = array( 'count' => 0, 'time' => 0 );

			$statistics['providers'][ $provider ] = array(
				'count' => intval( $stat['count'] ) + 1,
				'time'  => floatval( $stat['time' ] ) + $time,
			);

			if ( isset( $statistics['countries'][ $country ] ) )
				$stat = $statistics['countries'];
			else
				$stat = array( $country => 0 );

			$statistics['countries'][ $country ] = intval( $stat[ $country ] ) + 1;
		}

		update_option( self::$option_keys['statistics'], $statistics );
	}

	/**
	 * Send response header with http code.
	 *
	 */
	private function send_response( $code, $msg ) {
		// response code
		$code = max( 200, intval( $code ) ) & 0x1FF; // 200 - 511

		// 2xx Success
		if ( 200 <= $code && $code < 300 ) {
			header( 'Refresh: 0; url=' . get_site_url(), TRUE, $code ); // @since 3.0
			die();
		}

		// 3xx Redirection
		else if ( 300 <= $code && $code < 400 ) {
			header( 'Location: http://blackhole.webpagetest.org/', TRUE, $code );
			die();
		}

		// 4xx Client Error
		else if ( 400 <= $code && $code < 500 ) {
			wp_die( $msg, 'Error', array( 'response' => $code, 'back_link' => TRUE ) );
		}

		// 5xx Server Error
		status_header( $code ); // @since 2.0.0
		die();
	}

	/**
	 * Validate ip address
	 *
	 */
	public function validate_ip( $ip, $type ) {
		if ( is_user_logged_in() )
			return;

		$settings = get_option( self::$option_keys['settings'] );

		// apply user validation
		// @usage add_filter( 'ip-geo-block-comment', 'my_validation', 10, 2 );
		// @return array $validate = array(
		//     'ip'       => $ip,       /* ip address                          */
		//     'time'     => $time,     /* processing time                     */
		//     'code'     => $code,     /* country code or reason of rejection */
		//     'provider' => $provider, /* the name of validator               */
		//     'result'   => $result,   /* 'passed', 'blocked' or 'unknown'    */
		// );
		$ip = apply_filters( self::PLUGIN_SLUG . '-addr', $ip );
		$validate = apply_filters( self::PLUGIN_SLUG . "-$type", array( 'ip' => $ip ) );

		// if the post has not been marked then validate ip address
		if ( empty( $validate['result'] ) )
			$validate = $this->validate_country( $validate, $settings );

		// update statistics
		if ( $settings['save_statistics'] && 'comment' === $type )
			$this->update_statistics( $validate );

		// validation function should return at least the 'result'
		if ( empty( $validate['result'] ) || 'blocked' !== $validate['result'] )
			return;

		// update statistics
		if ( $settings['save_statistics'] && 'login' === $type )
			$this->update_statistics( $validate );

		// update cache
		IP_Geo_Block_API_Cache::update_cache(
			$validate['ip'],
			$validate['code'] . 'login' === $type ? '*' : '',
			$settings
		);

		// send response code to refuse
		$this->send_response(
			$settings['response_code'],
			__( 'Sorry, but you cannot be accepted.', self::TEXT_DOMAIN )
		);
	}

	/**
	 * Validate comment.
	 *
	 */
	public function validate_comment() {
		validate_ip( $_SERVER['REMOTE_ADDR'], 'comment' );
	}

	/**
	 * Validate login ip address
	 *
	 */
	public function validate_comment() {
		validate_ip( $_SERVER['REMOTE_ADDR'], 'login' );
	}

	/**
	 * Schedule controller.
	 *
	 */
	public static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		$schedule = wp_next_scheduled( 'ip_geo_block_cron' ); // @since 2.1.0

		if ( $schedule && ! $update['auto'] )
			wp_clear_scheduled_hook( 'ip_geo_block_cron' );

		else if ( ! $schedule && $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * $update['cycle'];

			if ( FALSE === $immediate &&
				$now - $db['ipv4_last'] < $cycle &&
				$now - $db['ipv6_last'] < $cycle ) {
				$update['retry'] = 0;
				$next = max( $db['ipv4_last'], $db['ipv6_last'] ) +
					$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 2 ) / 2;
			} else {
				$update['retry']++;
				$next = $now + ( $immediate ? 1 : DAY_IN_SECONDS );
			}

			wp_schedule_single_event( $next, 'ip_geo_block_cron' );
		}
	}

	/**
	 * Database auto downloader.
	 *
	 */
	public static function download_database( $only = NULL ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/download.php' );
		$options = get_option( self::$option_keys['settings'] );

		// download database
		$res = ip_geo_block_download(
			$options['maxmind'],
			trailingslashit(
				apply_filters( self::PLUGIN_SLUG . '-maxmind-path', IP_GEO_BLOCK_DB_PATH )
			), 
			self::get_request_headers( $options )
		);

		// re-schedule cron job
		self::schedule_cron_job( $options['update'], $options['maxmind'] );

		// limit options to be updated
		if ( $only )
			$options[ $only ] = TRUE;

		// update option settings
		update_option( self::$option_keys['settings'], $options );

		return $res;
	}

}