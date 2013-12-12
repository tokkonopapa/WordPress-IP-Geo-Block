<?php
/**
 * Post Geo Block
 *
 * @package   Post_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

class Post_Geo_Block {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 */
	const VERSION = '0.9.1';

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	protected $text_domain = 'post-geo-block';
	protected $plugin_slug = 'post-geo-block';
	protected $option_name = array();

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 */
	protected static $option_table = array(

		// settings (should be read on every page that has comment form)
		'post_geo_block_settings' => array(
			'method'          => 2,       // 0: primary only, 1: in order, 2: at random
			'provider'        => '',      // Name of primary provider
			'api_key'         => array(), // API keys
			'comment_pos'     => 0,       // Message place (0: none, 1: top, 2: bottom)
			'comment_msg'     => '',      // Message text on comment form
			'matching_rule'   => 0,       // 0: white list, 1: black list
			'white_list'      => 'JP',    // Comma separeted country code
			'black_list'      => '',      // Comma separeted country code
			'timeout'         => 5,       // Timeout in second
			'response_code'   => 403,     // Response code
			'check_ipv6'      => FALSE,   // IPV6
			'clean_uninstall' => FALSE,   // Remove all savings from DB
		),

		// statistics (should be read when comment has posted)
		'post_geo_block_statistics' => array(
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
	protected static $option_keys = array(
		'settings'   => 'post_geo_block_settings',
		'statistics' => 'post_geo_block_statistics',
	);

	public static function get_defaults( $name = 'settings' ) {
		return self::$option_table[ self::$option_keys[ $name ] ];
	}

	/**
	 * Initialize the plugin
	 * 
	 */
	private function __construct() {

		// Set table accessor by name
		foreach ( $this->get_option_keys() as $key => $val) {
			$this->option_name[ $key ] = $val;
		}

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Message text on comment form
		$key = get_option( $this->option_name['settings'] );
		if ( $key['comment_pos'] ) {
			$val = 'comment_form' . ( $key['comment_pos'] == 1 ? '_top' : '' );
			add_action( $val, array( $this, "comment_form_message" ), 10 );
		}

		// Validate when comment is posted
		add_action( 'pre_comment_on_post', array( $this, "validate_comment" ), 1 );
	}

	/**
	 * Return the plugin unique value.
	 *
	 */
	public function get_plugin_base() { return POST_GEO_BLOCK_BASE; }
	public function get_plugin_slug() { return $this->plugin_slug;  }
	public function get_text_domain() { return $this->text_domain;  }
	public function get_option_keys() { return self::$option_keys;  }

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 */
	public static function activate( $network_wide ) {
		// Register options into database table @since 1.0.0
		$name = array_keys( self::$option_table );
		add_option( $name[0], self::$option_table[ $name[0] ], '', 'yes' );
		add_option( $name[1], self::$option_table[ $name[1] ], '', 'no' );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide ) {
		// Delete options from database table
		self::uninstall();
	}

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		// Delete options from database table
		$name = array_keys( self::$option_table );
		$options = get_option( $name[0] );
		if ( $options['clean_uninstall'] ) {
			foreach ( $name as $key ) {
				delete_option( $key ); // @since 1.2.0
			}
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->text_domain, FALSE,
			plugin_basename( POST_GEO_BLOCK_PATH ) . '/languages/' ); // @since 1.5.0
	}

	/**
	 * Render a message to the comment form.
	 *
	 */
	public function comment_form_message( $id ) {
		$msg = get_option( $this->option_name['settings'] );
		$msg = htmlspecialchars( $msg['comment_msg'] );
		if ( $msg ) {
			echo '<p id="', $this->plugin_slug, '-msg">', $msg, '</p>';
		}
	}

	/**
	 * Check user's geolocation.
	 *
	 */
	private function check_geolocation( $ip ) {

		// get statistics
		$option_name = $this->option_name['statistics'];
		$statistics = get_option( $option_name );
		$settings   = get_option( $this->option_name['settings'] );

		$ipv4 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
		$ipv6 = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );

		// if IPv6 is disable then pass through
		if ( ! $settings['check_ipv6'] && $ipv6 ) {
			$statistics['passed'] = intval( $statistics['passed'] ) + 1;
			update_option( $option_name, $statistics );
			return TRUE;
		}

		// include utility class
		require_once( POST_GEO_BLOCK_PATH . '/classes/class-post-geo-block-ip.php' );

		// make providers list
		$list = array();
		$providers = Post_Geo_Block_IP_Setup::get_provider_keys();
		$provider = $settings['provider']; // Primary provider

		// set a primary provider if it has an appropriate API key
		if ( 0 == $settings['method'] && ! empty( $settings['api_key'][ $provider ] ) ) {
			$list[] = $provider;
		}

		// otherwise make a list of all the appropriate providers
		else {
			foreach ( $providers as $provider => $key ) {
				if ( NULL === $key || ! empty( $settings['api_key'][ $provider ] ) ) {
					$list[] = $provider;
				}
			}

			// randomize
			if ( 2 == $settings['method'] ) {
				shuffle( $list );
			}
		}

		// matching rule
		$rule  = $settings['matching_rule'];
		$white = $settings['white_list'];
		$black = $settings['black_list'];

		foreach ( $list as $provider ) {
			$key = ! empty( $settings['api_key'][ $provider ] ) ?
				$settings['api_key'][ $provider ] : $providers[ $provider ];

			$name = Post_Geo_Block_IP::get_class_name( $provider );
			if ( $name ) {
				// start time
				$time = microtime( TRUE );

				// get country code
				$geolocation = new $name( $key );
				$code = strtoupper( $geolocation->get_country( $ip, $settings['timeout'] ) );

				// process time
				$time = microtime( TRUE ) - $time;
				// unset( $geolocation );
			}

			else {
				$code = NULL;
			}

			if ( $code ) {

				// It may not be a spam
				if ( 0 == $rule && FALSE !== strpos( $white, $code ) ||
				     1 == $rule && FALSE === strpos( $black, $code ) ) {
					$statistics['passed'] = intval( $statistics['passed'] ) + 1;
					update_option( $option_name, $statistics );
					return TRUE;
				}

				// It must be a spam !!
				if ( $ipv4 ) $statistics['IPv4'] = intval( $statistics['IPv4'] ) + 1;
				if ( $ipv6 ) $statistics['IPv6'] = intval( $statistics['IPv6'] ) + 1;

				if ( isset( $statistics['providers'][ $provider ] ) ) {
					$name = $statistics['providers'][ $provider ];
				} else {
					$name = array(
						'total_count' => 0,
						'total_time'  => 0,
					);
				}

				$statistics['providers'][ $provider ] = array(
					'total_count' => intval( $name['total_count'] ) + 1,
					'total_time'  => intval( $name['total_time' ] ) + $time,
				);

				if ( isset( $statistics['countries'][ $code ] ) ) {
					$name = $statistics['countries'];
				} else {
					$name = array(
						$code => 0,
					);
				}

				$statistics['countries'][ $code ] = intval( $name[ $code ] ) + 1;
				$statistics['blocked'] = intval( $statistics['blocked'] ) + 1;

				update_option( $option_name, $statistics );

				// return response code
				return min( 511, max( 200, intval( $settings['response_code'] ) ) );
			}
		}

		// if ip address is unknown then pass through
		$statistics['unknown'] = intval( $statistics['unknown'] ) + 1;
		update_option( $option_name, $statistics ); // @since 1.0.0
		return TRUE;
	}

	/**
	 * Validate comment.
	 *
	 */
	public function validate_comment( $id ) {
		global $user_ID;

		require_once( POST_GEO_BLOCK_PATH . 'debug.php' );
		debug( "user ID: $user_ID, post ID: $id" );

		// pass login user
		if( $user_ID ) {
			return $id;
		}

		// check ip address
		$code = $this->check_geolocation( $_SERVER['REMOTE_ADDR'] );

		// not spam
		if ( TRUE === $code ) {
			return $id;
		}

		// 2xx Success
		else if ( 200 <= $code && $code < 300 ) {
			header('Refresh: 0; url=' . get_site_url(), TRUE, $code ); // @since 2.0.4
			die();
		}

		// 3xx Redirection
		else if ( 300 <= $code && $code < 400 ) {
			header( 'Location: http://blackhole.webpagetest.org/', TRUE, $code );
			die();
		}

		// 4xx Client Error
		else if ( 400 <= $code && $code < 500 ) {
			wp_die( __( 'Sorry, your comment cannot be accepted.', $this->text_domain ),
				'Error', array( 'response' => $code, 'back_link' => TRUE ) );
		}

		// 5xx Server Error
		status_header( $code ); // @since 2.0.0
		die();
	}

}
