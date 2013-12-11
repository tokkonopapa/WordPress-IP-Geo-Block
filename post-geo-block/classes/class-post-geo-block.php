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

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-post-geo-block-admin.php`
 *
 * @package Post_Geo_Block
 * @author  tokkonopapa <tokkonopapa@yahoo.com>
 */
class Post_Geo_Block {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @var     string
	 */
	const VERSION = '0.9.0';

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Unique identifier for this plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @var      string
	 */
	protected $text_domain = 'post-geo-block';
	protected $plugin_slug = 'post-geo-block';
	protected $option_name = array();

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 * @link http://wpengineer.com/968/wordpress-working-with-options/
	 */
	protected static $option_table = array(

		// settings (should be read on every page which has comment form)
		'post_geo_block_settings' => array(

			// Rule of provider selection
			'selection' => 2, // 0: primary only, 1: in order, 2: at random

			// Primary provider
			'provider' => '',

			// API keys
			'api_key' => array(
			),

			// Message on comment form
			// 0: none, 1: top, 2: bottom
			'comment_pos' => 0,
			'comment_msg' => '',

			// Matching rule and ccTLD list
			'matching_rule' => 0, // 0: white list, 1: black list
			'white_list' => 'JP',
			'black_list' => '',

			// Timeout in second
			'timeout' => 3,

			// Response code
			// @link http://tools.ietf.org/html/rfc2616#section-10
			'response_code' => 403,

			// IPV6
			'check_ipv6' => FALSE,

			// Remove setting from DB
			'clean_uninstall' => FALSE,
		),

		// statistics (should be read when comment has posted)
		'post_geo_block_statistics' => array(
			'passed'  => 0,
			'blocked' => 0,
			'unknown' => 0,
			'IPv4' => 0,
			'IPv6' => 0,
			'countries' => array(),
			'providers' => array(),
		),
	);

	// option table accessor by name
	protected static $option_keys = array(
		'settings'   => 'post_geo_block_settings',
		'statistics' => 'post_geo_block_statistics',
	);

	/**
	 * Initialize the plugin by setting localization
	 * and loading public scripts and styles.
	 */
	private function __construct() {

		// Set table accessor by name
		foreach ( $this->get_option_keys() as $key => $val) {
			$this->option_name[ $key ] = $val;
		}

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Settings for comment form
		// @link http://wpengineer.com/2205/comment-form-hooks-visualized/
		// @link http://justintadlock.com/archives/2010/07/21/using-the-wordpress-comment-form
		$val = get_option( $this->option_name['settings'] );
		$val = $val['comment_pos'];
		if ( $val ) {
			$val = ( 1 == $val ? 'comment_form_top' : 'comment_form' );
			add_action( $val, array( $this, "comment_form_message" ), 10 );
		}
		add_action( 'pre_comment_on_post', array( $this, "validate_comment_post" ), 1 );
	}

	/**
	 * Return the plugin unique value.
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	public function get_plugin_base() {
		return POST_GEO_BLOCK_BASE;
	}

	public function get_text_domain() {
		return $this->text_domain;
	}

	public function get_option_keys() {
		return self::$option_keys;
	}

	public static function get_defaults( $name = 'settings' ) {
		return self::$option_table[ "post_geo_block_$name" ];
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object    A single instance of this class.
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
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 */
	private static function single_activate() {
		// Register options into database table @since 1.0.0
		$name = array_keys( self::$option_table );
		add_option( $name[0], self::$option_table[ $name[0] ], '', 'yes' );
		add_option( $name[1], self::$option_table[ $name[1] ], '', 'no' );
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 */
	private static function single_deactivate() {
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
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( $this->text_domain, FALSE,
			plugin_basename( POST_GEO_BLOCK_PATH ) . '/languages/' ); // @since 1.5.0
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
		if ( 0 == $settings['selection'] && ! empty( $settings['api_key'][ $provider ] ) ) {
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
			if ( 2 == $settings['selection'] ) {
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
				$ip_geoloc = new $name( $key );
				$code = strtoupper( $ip_geoloc->get_country( $ip ) );
				// unset( $ip_geoloc );

				// process time
				$time = microtime( TRUE ) - $time;
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
				$code = intval( $settings['response_code'] );
				return min( 511, max( 200, $code ) );
			}
		}

		// if ip address is unknown then pass through
		$statistics['unknown'] = intval( $statistics['unknown'] ) + 1;
		update_option( $option_name, $statistics ); // @since 1.0.0
		return TRUE;
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
	 * Validate comment.
	 *
	 */
	public function validate_comment_post( $id ) {
		global $user_ID;

		self::debug_log( "ID: $id, user ID: $user_ID, IP: " . $_SERVER['REMOTE_ADDR'] );

		// pass login user
		if( $user_ID ) {
			return $id;
		}

		// check ip address
		$code = $this->check_geolocation( $_SERVER['REMOTE_ADDR'] );
		if ( TRUE === $code ) {
			return $id;
		}

		// 2xx Success
		if ( 200 <= $code && $code < 300 ) { // @since 2.0.4
			header('Refresh: 0; url=' . get_site_url(), TRUE, $code );
			die();
		}

		// 3xx Redirection
		else if ( 300 <= $code && $code < 400 ) {
			// can't use wp_redirect() because it pass the post
			header( 'Location: http://blackhole.webpagetest.org/', TRUE, $code );
			die();
		}

		// 4xx Client Error
		else if ( 400 <= $code && $code < 500 ) {
			wp_die( __( 'Sorry, your comment has been refused.', $this->text_domain ),
				'Error', array( 'response' => $code, 'back_link' => TRUE ) );
		}

		// 5xx Server Error
		status_header( $code ); // @since 2.0.0
		die();
	}

	/**
	 * Output log to a file
	 *
	 * @param string $msg: message strings.
	 */
	public static function debug_log( $msg = '' ) {
		$who = '';
		if ( is_admin() ) $who .= 'admin';
		if ( defined( 'DOING_AJAX' ) ) $who .= 'ajax';
		if ( defined( 'DOING_CRON' ) ) $who .= 'cron';
		if ( isset( $_GET['doing_wp_cron'] ) ) $who .= 'doing';
		$mod = empty( $msg ) ? 'w' : 'a';
		$fp = @fopen( POST_GEO_BLOCK_PATH . 'debug.log', $mod );
		if ( FALSE !== $fp ) {
			$msg = trim( $msg );
			@fwrite( $fp, date( 'd/M/Y H:i:s', current_time( 'timestamp', TRUE ) ) . " ${who}/${msg}\n" );
			@fclose( $fp );
		}
	}

}
