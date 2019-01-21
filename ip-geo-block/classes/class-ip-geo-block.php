<?php
/**
 * IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 */

class IP_Geo_Block {

	/**
	 * Unique identifier for this plugin.
	 *
	 */
	const VERSION = '3.0.17.4';
	const GEOAPI_NAME = 'ip-geo-api';
	const PLUGIN_NAME = 'ip-geo-block';
	const OPTION_NAME = 'ip_geo_block_settings';
	const OPTION_META = 'ip_geo_block_metadata';
	const CACHE_NAME  = 'ip_geo_block_cache';
	const CRON_NAME   = 'ip_geo_block_cron';

	/**
	 * Globals in this class
	 *
	 */
	private static $instance = NULL;
	private static $settings = NULL;
	private static $auth_key = NULL;
	private static $live_log = FALSE;
	private static $wp_path = array();
	private static $remote_addr = NULL;

	private $pagenow = NULL;
	private $request_uri = NULL;
	private $target_type = NULL;

	/**
	 * Initialize the plugin.
	 *
	 */
	private function __construct() {
		// Run the loader to execute all of the hooks with WordPress.
		$this->register_hooks( $loader = new IP_Geo_Block_Loader() );
		$loader->run( $this );
		unset( $loader );
	}

	/**
	 * Setup actions after init.
	 *
	 */
	private function register_hooks( $loader ) {
		$settings = self::get_option();
		$priority = $settings['priority'  ];
		$validate = $settings['validation'];

		// include drop in if it exists
		file_exists( $key = IP_Geo_Block_Util::unslashit( $settings['api_dir'] ) . '/drop-in.php' ) and include( $key );

		// global settings after `drop-in.php`
		self::$auth_key = apply_filters( self::PLUGIN_NAME . '-auth-key', self::PLUGIN_NAME . '-auth-nonce' );
		self::$live_log = ( $validate['reclogs'] ? get_transient( self::PLUGIN_NAME . '-live-log' ) : FALSE );

		// normalize requested uri and page
		$key = preg_replace( array( '!\.+/!', '!//+!' ), '/', $_SERVER['REQUEST_URI'] );
		$this->request_uri = @parse_url( $key, PHP_URL_PATH ) or $this->request_uri = $key;
		$this->pagenow = ! empty( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : basename( $_SERVER['SCRIPT_NAME'] );

		// setup the content folders
		self::$wp_path = array( 'home' => IP_Geo_Block_Util::unslashit( parse_url( site_url(), PHP_URL_PATH ) ) ); // @since 2.6.0
		$len = strlen( self::$wp_path['home'] );
		$list = array(
			'admin'     => 'admin_url',          // @since 2.6.0 /wp-admin/
			'plugins'   => 'plugins_url',        // @since 2.6.0 /wp-content/plugins/
			'themes'    => 'get_theme_root_uri', // @since 1.5.0 /wp-content/themes/
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

		// register target: (wp-admin|wp-includes|wp-content/(plugins|themes|language|uploads))
		if ( $this->target_type ) {
			if ( 'admin' !== $this->target_type )
				$loader->add_action( 'init', array( $this, 'validate_direct' ), $priority[1] );
			else // 'widget_init' for admin dashboard
				$loader->add_action( 'admin_init', array( $this, 'validate_admin' ), $priority[1] );
		}

		// register target: (comment|xmlrpc|login|public)
		elseif ( isset( $list[ $this->pagenow ] ) ) {
			if ( $validate[ $list[ $this->pagenow ] ] || self::$live_log )
				$loader->add_action( 'init', array( $this, 'validate_' . $list[ $this->pagenow ] ), $priority[0] );
		}

		// register target: alternative of trackback
		elseif ( 'POST' === $_SERVER['REQUEST_METHOD'] && 'trackback' === basename( $this->request_uri ) ) {
			if ( $validate['comment'] || self::$live_log )
				$loader->add_action( 'init', array( $this, 'validate_comment' ), $priority[0] );
		}

		else {
			// public facing pages
			if ( $validate['public'] || ( ! empty( $_FILES ) && $validate['mimetype'] ) || self::$live_log /* && 'index.php' === $this->pagenow */ )
				defined( 'DOING_CRON' ) or $loader->add_action( 'init', array( $this, 'validate_public' ), $priority[0] );

			// message text on comment form
			if ( $settings['comment']['pos'] ) {
				$key = ( 1 === (int)$settings['comment']['pos'] ? '_top' : '' );
				add_action( 'comment_form' . $key, array( $this, 'comment_form_message' ) );
			}

			if ( $validate['comment'] || self::$live_log ) {
				add_action( 'pre_comment_on_post', array( $this, 'validate_comment' ), $priority[0] ); // wp-comments-post.php @since 2.8.0
				add_action( 'pre_trackback_post',  array( $this, 'validate_comment' ), $priority[0] ); // wp-trackback.php @since 4.7.0
				add_filter( 'preprocess_comment',  array( $this, 'validate_comment' ), $priority[0] ); // wp-includes/comment.php @since 1.5.0

				// bbPress: prevent creating topic/relpy and rendering form
				add_action( 'bbp_post_request_bbp-new-topic', array( $this, 'validate_comment' ), $priority[0] );
				add_action( 'bbp_post_request_bbp-new-reply', array( $this, 'validate_comment' ), $priority[0] );
				add_filter( 'bbp_current_user_can_access_create_topic_form', array( $this, 'validate_front' ), $priority[0] );
				add_filter( 'bbp_current_user_can_access_create_reply_form', array( $this, 'validate_front' ), $priority[0] );
			}

			if ( $validate['login'] || self::$live_log ) {
				// for hide/rename wp-login.php, BuddyPress: prevent registration and rendering form
				add_action( 'login_init', array( $this, 'validate_login' ), $priority[0] );

				// only when block on front-end is disabled
				if ( ! $validate['public'] || self::$live_log ) {
					add_action( 'bp_core_screen_signup',  array( $this, 'validate_login' ), $priority[0] );
					add_action( 'bp_signup_pre_validate', array( $this, 'validate_login' ), $priority[0] );
				}
			}

			// the action hook which will be fired by cron job
			if ( $settings['update']['auto'] )
				add_action( self::CRON_NAME, array( $this, 'exec_update_db' ) );

			// garbage collection for IP address cache, enque script for authentication
			add_action( self::CACHE_NAME,     array( $this,     'exec_cache_gc' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_nonce' ), $priority[0] ); // @since 2.8.0
		}

		// force to redirect on logout to remove nonce, embed a nonce into pages
		add_filter( 'wp_redirect',        array( $this, 'logout_redirect' ), 20,           2 ); // logout_redirect @4.2
		add_filter( 'http_request_args',  array( $this,   'request_nonce' ), $priority[1], 2 ); // @since 2.7.0

		// register validation of updating metadata
		if ( $validate['metadata'] )
			$this->validate_metadata( $settings, $priority[0] );
	}

	/**
	 * I/F for registering custom fileter.
	 *
	 */
	public static function add_filter( $tag, $function, $priority = 10, $args = 1 ) {
		add_filter( $tag, $function, $priority, $args );
	}

	/**
	 * Get the instance of this class.
	 *
	 */
	public static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Getter functions for the private values.
	 *
	 */
	public static function get_auth_key() { return self::$auth_key; }
	public static function get_live_log() { return self::$live_log; }
	public static function get_wp_path()  { return self::$wp_path;  }

	/**
	 * Optional values handlings.
	 *
	 */
	public static function get_default() {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
		return IP_Geo_Block_Opts::get_default();
	}

	public static function get_option( $cache = TRUE ) {
		if ( $cache ) {
			self::$settings or ( self::$settings = get_option( self::OPTION_NAME ) ) or ( self::$settings = self::get_default() );
			return self::$settings;
		} else {
			return ( $settings = get_option( self::OPTION_NAME ) ) ? $settings : self::get_default();
		}
	}

	public static function update_option( $settings, $cache = TRUE ) {
		return update_option( self::OPTION_NAME, $cache ? self::$settings = $settings : $settings );
	}

	public static function get_metadata( $cache = TRUE ) {
		return ( $metadata = get_option( self::OPTION_META ) ) ? $metadata : array();
	}

	public static function update_metadata( $metadata, $cache = TRUE ) {
		return update_option( self::OPTION_META, $metadata );
	}

	/**
	 * Remove a nonce from the redirecting URL on logout to prevent disclosing a nonce.
	 *
	 */
	public function logout_redirect( $location ) {
		if ( isset( $_REQUEST['action'] ) && 'logout' === $_REQUEST['action'] )
			return IP_Geo_Block_Util::rebuild_nonce( $location, FALSE );
		else
			return $location;
	}

	/**
	 * Add nonce into arguments used in an HTTP request.
	 *
	 */
	public function request_nonce( $args = array(), $url = '' ) {
		if ( 0 === strpos( $url, admin_url() ) && empty( $args[ self::$auth_key ] ) )
			$args += array( self::$auth_key => IP_Geo_Block_Util::create_nonce( self::$auth_key ) );

		return $args;
	}

	/**
	 * Register and enqueue a nonce with a specific JavaScript.
	 *
	 */
	public static function enqueue_nonce( $hook ) {
		if ( IP_Geo_Block_Util::is_user_logged_in() ) {
			$settings = self::get_option();
			$validate = $settings['validation'];

			$args['sites'] = IP_Geo_Block_Util::get_sites_of_user();
			$args['nonce'] = IP_Geo_Block_Util::create_nonce( self::$auth_key );
			$args['key'  ] = $validate['admin'] & 2 || $validate['ajax'] & 2 || $validate['plugins'] & 2 || $validate['themes'] & 2 ? self::$auth_key : FALSE;

			$script = plugins_url(
				! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
				'admin/js/authenticate.min.js' : 'admin/js/authenticate.js', IP_GEO_BLOCK_BASE
			);

			wp_enqueue_script ( self::$auth_key, $script, array( 'jquery' ),  self::VERSION  );
			wp_localize_script( self::$auth_key, 'IP_GEO_BLOCK_AUTH', $args + self::$wp_path );
		}
	}

	/**
	 * Setup the http header.
	 *
	 * @see https://codex.wordpress.org/Function_Reference/wp_remote_get
	 */
	public static function get_request_headers( $settings ) {
		return apply_filters( self::PLUGIN_NAME . '-headers', array(
			'timeout' => (int)$settings['timeout'],
			'user-agent' => ! empty( $settings['request_ua'] ) ? $settings['request_ua'] : @$_SERVER['HTTP_USER_AGENT']
		) );
	}

	/**
	 * Get current IP address.
	 *
	 */
	public static function get_ip_address( $settings = NULL ) {
		$settings or $settings = self::get_option();
		self::$remote_addr or self::$remote_addr = IP_Geo_Block_Util::get_client_ip( $settings['validation']['proxy'] );
		return has_filter( self::PLUGIN_NAME . '-ip-addr' ) ? apply_filters( self::PLUGIN_NAME . '-ip-addr', self::$remote_addr ) : self::$remote_addr;
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
	 * Return true if the validation result is passed.
	 *
	 */
	public static function is_passed ( $result )      { return 0 === strncmp( 'pass', $result, 4 );      }
	public static function is_failed ( $result )      { return 0 === strncmp( 'fail', $result, 4 );      }
	public static function is_blocked( $result )      { return 0 !== strncmp( 'pass', $result, 4 );      }
	public static function is_listed ( $code, $list ) { return FALSE !== strpos( $list, (string)$code ); }

	/**
	 * Build a validation result for the current user.
	 *
	 */
	private static function make_validation( $ip, $result ) {
		// later parameters take precedence over previous ones
		return array_merge( array(
			'ip'   => $ip,
			'asn'  => NULL, // @since 3.0.4
			'code' => 'ZZ', // should be overwritten with $result
		), $result, array( 'auth' => IP_Geo_Block_Util::get_current_user_id() ) );
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
			$providers = IP_Geo_Block_Provider::get_valid_providers( $settings );

		$result = self::_get_geolocation( $ip ? $ip : self::get_ip_address( $settings ), $settings, $providers, array(), $callback );

		if ( ! empty( $result['countryCode'] ) )
			$result['code'] = $result['countryCode'];

		return $result;
	}

	/**
	 * API for internal.
	 *
	 */
	private static function _get_geolocation( $ip, $settings, $providers, $args = array(), $callback = 'get_country' ) {
		// check loop back / private address / empty provider
		if ( IP_Geo_Block_Util::is_private_ip( $ip ) || count( $providers ) <= 1 )
			return self::make_validation( $ip, array( 'time' => 0, 'provider' => 'Private', 'code' => 'XX' ) );

		// set arguments for wp_remote_get()
		$args += self::get_request_headers( $settings );

		foreach ( $providers as $provider ) {
			$time = microtime( TRUE );
			if ( ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) ) &&
			     ( $code = $geo->$callback( $ip, $args ) ) ) {
				// Get AS number @since 3.0.4
				if ( ( ! empty( $settings[ $provider ]['use_asn'] ) ) &&
				     ( ! isset( $code['asn'] ) || 0 !== strpos( $code['asn'], 'AS' ) ) &&
				     ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) ) ) {
					$asn = $geo->get_location( $ip, array( 'ASN' => TRUE ) );
					$asn = isset( $asn['ASN'] ) ? strtok( $asn['ASN'], ' ' ) : NULL;
				}

				return self::make_validation( $ip, array(
					'time'     => microtime( TRUE ) - $time,
					'provider' => $provider,
				) + ( is_array( $code ) ? $code : array( 'code' => $code, 'asn' => isset( $asn ) ? $asn : NULL ) ) );
			}
		}

		return self::make_validation( $ip, array( 'errorMessage' => 'unknown' ) );
	}

	/**
	 * Validate geolocation by country code.
	 *
	 */
	public static function validate_country( $hook, $validate, $settings, $block = TRUE ) {
		if ( 'XX' !== $validate['code'] ) { // 'XX' is for localhost or inside of load balancer etc
			if ( $block && 0 === (int)$settings['matching_rule'] ) {
				// 'ZZ' will be blocked if it's not in the $list.
				if ( ( $list = $settings['white_list'] ) && FALSE === strpos( $list, $validate['code'] ) )
					return $hook ? $validate + array( 'result' => 'blocked' ) : 'blocked'; // can't overwrite existing result
			}

			elseif( $block && 1 === (int)$settings['matching_rule'] ) {
				// 'ZZ' will NOT be blocked if it's not in the $list.
				if ( ( $list = $settings['black_list'] ) && FALSE !== strpos( $list, $validate['code'] ) )
					return $hook ? $validate + array( 'result' => 'blocked' ) : 'blocked'; // can't overwrite existing result
			}
		}

		return $hook ? $validate + array( 'result' => 'passed' ) : 'passed'; // can't overwrite existing result
	}

	/**
	 * Send response header with http status code and reason.
	 *
	 */
	public function send_response( $hook, $validate, $settings ) {
		require_once ABSPATH . WPINC . '/functions.php'; // for get_status_header_desc() @since 2.3.0

		$code = (int   )apply_filters( self::PLUGIN_NAME . '-'.$hook.'-status', $settings['response_code'] );
		$mesg = (string)apply_filters( self::PLUGIN_NAME . '-'.$hook.'-reason', $settings['response_msg' ] ? $settings['response_msg'] : get_status_header_desc( $code ) );

		// custom action (for fail2ban) @since 1.2.0
		do_action( self::PLUGIN_NAME . '-send-response', $hook, $code, $validate );

		// prevent caching (WP Super Cache, W3TC, Wordfence, Comet Cache)
		defined( 'DONOTCACHEPAGE' ) or define( 'DONOTCACHEPAGE', TRUE );
		nocache_headers(); // wp-includes/functions.php @since 2.0.0

		// https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
		'public' === $hook and header( 'X-Robots-Tag: noindex, nofollow', FALSE );

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
			if ( 'GET' === $_SERVER['REQUEST_METHOD'] || 'HEAD' === $_SERVER['REQUEST_METHOD'] ) {
				IP_Geo_Block_Util::safe_redirect( esc_url_raw( $settings['redirect_uri'] ? $settings['redirect_uri'] : home_url( '/' ) ), $code ); // @since 2.8
				exit;
			} else {
				$code = 403; // avoid redirection loop
			} // continue to default

		  default: // 4xx Client Error, 5xx Server Error
			status_header( $code ); // @since 2.0.0

			if ( function_exists( 'trackback_response' ) )
				trackback_response( $code, IP_Geo_Block_Util::kses( $mesg ) ); // @since 0.71

			elseif ( ! defined( 'DOING_AJAX' ) && ! defined( 'XMLRPC_REQUEST' ) ) {
				$hook = ( IP_Geo_Block_Util::is_user_logged_in() && 'admin' === $hook );
				if ( ! $hook && TRUE === $this->show_theme_template( $code, $settings ) )
					return;

				// prevent to make a cached page. `set_404()` should not be used for `wp_die()`.
				global $wp_query; isset( $wp_query->is_404 ) and $wp_query->is_404 = TRUE;

				wp_die( // get_dashboard_url() @since 3.1.0
					IP_Geo_Block_Util::kses( $mesg ) . ( $hook ? "\n<p>&laquo; <a href='javascript:history.back()'>" . __( 'Back' ) . "</a> / <a rel='nofollow' href='" . esc_url( get_dashboard_url( IP_Geo_Block_Util::get_current_user_id() ) ) . "'>" . __( 'Dashboard' ) . "</a></p>" : '' ),
					get_status_header_desc( $code ), array( 'response' => $code, 'back_link' => ! $hook )
				);
			}
			exit;
		}
	}

	/**
	 * Load and show theme template.
	 *
	 */
	private function show_theme_template( $code, $settings ) {
		if ( file_exists( $file = get_stylesheet_directory() . '/' . $code . '.php' ) /* child  theme */ ||
		     file_exists( $file = get_template_directory()   . '/' . $code . '.php' ) /* parent theme */ ) {
			// keep the response code and the template file
			$this->theme_template = array( 'code' => $code, 'file' => $file );

			// case 1: validation timing is `init`
			if ( $action = current_filter() ) { // `plugins_loaded`, `wp` or FALSE
				add_action( // `wp` (on front-end target) is too late to apply `init`
					'wp' === $action ? 'template_redirect' : 'init',
					array( $this, 'load_theme_template' ), $settings['priority'][1]
				);
				return TRUE; // load template at the specified action
			}

			// case 2: validation timing is `mu-plugins`
			elseif ( '<?php' !== file_get_contents( $file, FALSE, NULL, 0, 5 ) ) {
				$this->load_theme_template(); // load template and die immediately
			}
		}

		return FALSE; // die with wp_die() immediately
	}

	public function load_theme_template( $template = FALSE ) {
		global $wp_query; isset( $wp_query ) and $wp_query->set_404(); // for stylesheet

		// change title from `Not Found` because of `set_404()` to the right one.
		add_filter( 'document_title_parts', array( $this, 'change_title' ) ); // @since 4.4.0

		// avoid loading template for HEAD requests because of performance bump. See #14348.
		'HEAD' !== $_SERVER['REQUEST_METHOD'] and include $this->theme_template['file'];
		exit;
	}

	public function change_title( $title_parts ) {
		$title_parts['title'] = get_status_header_desc( $this->theme_template['code'] );
		return $title_parts;
	}

	/**
	 * The last process of validation.
	 *
	 */
	private function endof_validate( $hook, $validate, $settings, $block = TRUE, $die = TRUE, $countup = TRUE ) {
		// update cache and record logs
		IP_Geo_Block_API_Cache::update_cache( $hook, $validate, $settings, $countup );
		IP_Geo_Block_Logs::record_logs( $hook, $validate, $settings, self::is_blocked( $validate['result'] ) );

		if ( $block ) {
			if ( $settings['save_statistics'] && ! $validate['auth'] )
				IP_Geo_Block_Logs::update_stat( $hook, $validate, $settings );

			if ( ! $settings['simulate'] && $die )
				$this->send_response( $hook, $validate, $settings );
		}
	}

	/**
	 * Validate ip address.
	 *
	 * @param string  $hook       a name to identify action hook applied in this call.
	 * @param array   $settings   option settings
	 * @param boolean $block      block                      if validation fails (for simulate)
	 * @param boolean $die        send http response and die if validation fails (for validate_front )
	 */
	public function validate_ip( $hook, $settings, $block = TRUE, $die = TRUE ) {
		// register auxiliary validation functions
		// priority high 3 close_xmlrpc, close_restapi
		//               4 check_nonce (high), check_user (low)
		//               5 check_upload (high), check_signature (low)
		//               6 check_auth
		//               7 check_ips_black (high), check_ips_white (low)
		//               8 check_fail
		//               9 check_ua (high), check_behavior (low)
		// priority low 10 check_page (high), validate_country (low)
		$var = self::PLUGIN_NAME . '-' . $hook;
		$settings['validation' ]['mimetype'  ]  and add_filter( $var, array( $this, 'check_upload'    ), 5, 2 );
		$die                                    and add_filter( $var, array( $this, 'check_auth'      ), 6, 2 );
		$settings['extra_ips'  ] = apply_filters( self::PLUGIN_NAME . '-extra-ips', $settings['extra_ips'], $hook );
		$settings['extra_ips'  ]['black_list']  and add_filter( $var, array( $this, 'check_ips_black' ), 7, 2 );
		$settings['extra_ips'  ]['white_list']  and add_filter( $var, array( $this, 'check_ips_white' ), 7, 2 );
		$settings['login_fails'] >= 0 && $block and add_filter( $var, array( $this, 'check_fail'      ), 8, 2 );

		// make valid provider name list
		$providers = IP_Geo_Block_Provider::get_valid_providers( $settings );

		// apply custom filter for validation
		// @example add_filter( 'ip-geo-block-$hook', 'my_validation', 10, 2 );
		// @param $validate = array(
		//     'ip'       => $ip,       /* validated ip address                */
		//     'auth'     => $auth,     /* authenticated or not                */
		//     'code'     => $code,     /* country code or reason of rejection */
		//     'result'   => $result,   /* 'passed', 'blocked'                 */
		// );
		$ips = IP_Geo_Block_Util::retrieve_ips( array( self::get_ip_address( $settings ) ), $settings['validation']['proxy'] );
		foreach ( $ips as self::$remote_addr ) {
			$validate = self::_get_geolocation( self::$remote_addr, $settings, $providers, array( 'cache' => TRUE ) );
			$validate = apply_filters( $var, $validate, $settings );

			// if no 'result' then validate ip address by country
			if ( empty( $validate['result'] ) )
				$validate = self::validate_country( $hook, $validate, $settings, $block );

			// if one of IPs is blocked then stop
			if ( self::is_blocked( $validate['result'] ) )
				break;
		}

		if ( $die ) // send response code to die if validation fails
			$this->endof_validate( $hook, $validate, $settings, self::is_blocked( $validate['result'] ) );

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
		$validate = $this->validate_ip( 'comment', self::get_option(), TRUE, FALSE, FALSE );
		return self::is_passed( $validate['result'] ) ? $can_access : FALSE;
	}

	/**
	 * Validate on xmlrpc.
	 *
	 */
	public function validate_xmlrpc() {
		$settings = self::get_option();

		if ( 2 === (int)$settings['validation']['xmlrpc'] ) // Completely close
			add_filter( self::PLUGIN_NAME . '-xmlrpc', array( $this, 'close_xmlrpc' ), 3, 2 );

		else // wp-includes/class-wp-xmlrpc-server.php @since 3.5.0
			add_filter( 'xmlrpc_login_error', array( $this, 'auth_fail' ), $settings['priority'][0] );

		$this->validate_ip( 'xmlrpc', $settings );
	}

	public function close_xmlrpc( $validate, $settings ) {
		return $validate + array( 'result' => 'closed' ); // can't overwrite existing result
	}

	/**
	 * Validate on login.
	 *
	 */
 	public function filter_login_url( $url, $path, $scheme, $blog_id ) {
 		if ( isset( $this->login_key ) && FALSE !== strpos( $url, $this->request_uri ) )
			$url = esc_url( add_query_arg( self::PLUGIN_NAME . '-key', $this->login_key, $url ) );

 		return $url;
 	}

	public function validate_login() {
		// parse action
		$settings = self::get_option();
		if ( 'wp-signup.php' === $this->pagenow && $settings['login_action']['register'] ) {
			$action = 'register';
		} else {
			$action = isset( $_GET['key'] ) ? 'resetpass' : ( isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'login' );
			$action = 'retrievepassword' === $action ? 'lostpassword' : ( 'rp' === $action ? 'resetpass' : $action );
		}

		// the same rule should be applied to login and logout
		! empty( $settings['login_action']['login'] ) and $settings['login_action']['logout'] = TRUE;

		// avoid conflict with WP Limit Login Attempts (wp-includes/pluggable.php @since 2.5.0)
		! empty( $_POST ) and add_action( 'wp_login_failed', array( $this, 'auth_fail' ), $settings['priority'][0] );

		// verify emergency login key
		if ( 'login' === $action && ! empty( $_REQUEST[ self::PLUGIN_NAME . '-key' ] ) &&
		     IP_Geo_Block_Util::verify_link( $_REQUEST[ self::PLUGIN_NAME . '-key' ] ) ) {
			$this->login_key = sanitize_key( $_REQUEST[ self::PLUGIN_NAME . '-key' ] );

			// add the verified key to the url in login form
			has_filter( 'site_url', array( $this, 'filter_login_url' ) ) or
			add_filter( 'site_url', array( $this, 'filter_login_url' ), 10, 4 );
			$settings['login_action']['login'] = FALSE; // skip blocking in validate_ip()
		}

		// enables to skip validation of country on login/out except BuddyPress signup
		$this->validate_ip( 'login', $settings, ! empty( $settings['login_action'][ $action ] ) || 'bp_' === substr( current_filter(), 0, 3 ) );
	}

	/**
	 * Check exceptions.
	 *
	 */
	private function check_exceptions( $action, $page, $exceptions = array() ) {
		$in_action = in_array( $action, $exceptions, TRUE );
		$in_page   = in_array( $page,   $exceptions, TRUE );
		return ( ( $action xor $page ) && ( ! $in_action and ! $in_page ) ) ||
		       ( ( $action and $page ) && ( ! $in_action or  ! $in_page ) ) ? FALSE : TRUE;
	}

	/**
	 * Validate in admin area.
	 *
	 */
	public function validate_admin() {
		// if there's no action parameter but something is specified
		$settings = self::get_option();
		$page   = isset( $_REQUEST['page'  ] ) ? $_REQUEST['page'  ] : NULL;
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : ( isset( $_REQUEST['task'] ) ? $_REQUEST['task'] : NULL );

		switch ( $this->pagenow ) {
		  case 'admin-ajax.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( 'wp_ajax_nopriv_'.$action );
			$rule = (int)$settings['validation']['ajax'];
			break;

		  case 'admin-post.php':
			// if the request has an action for no privilege user, skip WP-ZEP
			$zep = ! has_action( 'admin_post_nopriv' . ($action ? '_'.$action : '') );
			$rule = (int)$settings['validation']['ajax'];
			break;

		  default:
			// if the request has no page and no action, skip WP-ZEP
			$zep = ( $page || $action ) ? TRUE : FALSE;
			$rule = (int)$settings['validation']['admin'];
		}

		// list of request for specific action or page to bypass WP-ZEP
		$list = array_merge( apply_filters( self::PLUGIN_NAME . '-bypass-admins', array(), $settings ), array(
			// in wp-admin js/widget.js, includes/template.php, async-upload.php, plugins.php, PHP Compatibility Checker, bbPress
			'heartbeat', 'save-widget', 'wp-compression-test', 'upload-attachment', 'deactivate', 'imgedit-preview', 'wpephpcompat_start_test', 'bp_avatar_upload',
			// Anti-Malware Security and Brute-Force Firewall, Jetpack page & action, Email Subscribers & Newsletters by Icegram, Swift Performance
			'GOTMLS_logintime', 'jetpack', 'authorize', 'jetpack_modules', 'atd_settings', 'bulk-activate', 'bulk-deactivate', 'es_sendemail', 'swift_performance_setup',
			// Advanced Access Manager
			'aam', 'aamc',
		) );

		// skip validation of country code and WP-ZEP if exceptions matches action or page
		if ( ( $page || $action ) && $this->check_exceptions( $action, $page, $settings['exception']['admin'] ) )
			$rule &= ~ ( $zep ? 2 : 3 ); // 2: WP-ZEP, 1: Block by country (validation of bad signature is still in effective)

		// combination with vulnerable keys should be prevented to bypass WP-ZEP
		elseif ( ! $this->check_exceptions( $action, $page, $list ) ) {
			if ( ( 2 & $rule ) && $zep ) {
				// redirect if valid nonce in referer, otherwise register WP-ZEP (2: WP-ZEP)
				IP_Geo_Block_Util::trace_nonce( self::$auth_key );
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 4, 2 );
			}
		}

		// register validation of malicious signature (except in the comment and post)
		if ( ! IP_Geo_Block_Util::is_user_logged_in() && ! in_array( $this->pagenow, array( 'comment.php', 'post.php' ), TRUE ) )
			add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 5, 2 );

		// validate country by IP address (1: Block by country)
		$this->validate_ip( 'admin', $settings, 1 & $rule );
	}

	/**
	 * Validate in plugins/themes area.
	 *
	 */
	public function validate_direct() {
		// analyze target in wp-includes, wp-content/(plugins|themes|language|uploads)
		$path = preg_quote( self::$wp_path[ $type = $this->target_type ], '!' );
		$name = ( 'plugins' === $type || 'themes' === $type ? '[^\?\&\/]*' : '[^\?\&]*' );

		preg_match( "!($path)($name)!", $this->request_uri, $name );
		$name = empty( $name[2] ) ? $name[1] : $name[2];

		// set validation rules by target (0: Bypass, 1: Block by country, 2: WP-ZEP)
		$settings = self::get_option();
		$rule = (int)$settings['validation'][ $type ];

		// list of request for specific action or page to bypass WP-ZEP
		$path = array( 'includes' => array( 'ms-files.php', 'js/tinymce/wp-tinymce.php', ), /* for wp-includes */ );
		$path = apply_filters( self::PLUGIN_NAME . "-bypass-{$type}", isset( $path[ $type ] ) ? $path[ $type ] : array(), $settings );

		// skip validation of country code if exceptions matches action or page
		if ( in_array( $name, $settings['exception'][ $type ], TRUE ) )
			$rule = 0;

		elseif ( ! in_array( $name, $path, TRUE ) ) {
			if ( 2 & $rule ) {
				// redirect if valid nonce in referer, otherwise register WP-ZEP (2: WP-ZEP)
				IP_Geo_Block_Util::trace_nonce( self::$auth_key );
				add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_nonce' ), 4, 2 );
			}
		}

		// register validation of malicious signature
		if ( ! IP_Geo_Block_Util::is_user_logged_in() )
			add_filter( self::PLUGIN_NAME . '-admin', array( $this, 'check_signature' ), 5, 2 );

		// validate country by IP address (1: Block by country)
		$validate = $this->validate_ip( 'admin', $settings, 1 & $rule );

		// if the validation is successful, execute the requested uri via rewrite.php
		if ( class_exists( 'IP_Geo_Block_Rewrite', FALSE ) )
			IP_Geo_Block_Rewrite::exec( $this, $validate, $settings );
	}

	/**
	 * Auxiliary validation functions.
	 *
	 */
	public function auth_fail( $something = NULL ) {
		// Count up a number of fails when authentication is failed
		$time = microtime( TRUE );
		$settings = self::get_option();
		if ( $cache = IP_Geo_Block_API_Cache::get_cache( self::$remote_addr, $settings['cache_hold'] ) ) {
			$cache['fail']++;
			$validate = self::make_validation( self::$remote_addr, array(
				'result'   => 'failed',
				'provider' => 'Cache',
				'time'     => microtime( TRUE ) - $time,
			) + $cache );

			// the whitelist of IP address should be prior
			if ( ! $this->check_ips( $validate, $settings['extra_ips']['white_list'] ) ) {
				if ( (int)$settings['login_fails'] >= 0 && $cache['fail'] > max( 0, (int)$settings['login_fails'] ) )
					$validate['result'] = 'limited';

				// validate xmlrpc system.multicall
				elseif ( defined( 'XMLRPC_REQUEST' ) && FALSE !== stripos( file_get_contents( 'php://input' ), 'system.multicall' ) )
					$validate['result'] = 'multi';
			}

			// apply filter hook for emergent functionality
			$validate = apply_filters( self::PLUGIN_NAME . '-login', $validate, $settings );

			// send response code to die if the number of login attempts exceeds the limit
			$this->endof_validate( defined( 'XMLRPC_REQUEST' ) ? 'xmlrpc' : 'login', $validate, $settings, TRUE, 'failed' !== $validate['result'], FALSE );
		}

		return $something; // pass through
	}

	public function check_fail( $validate, $settings ) {
		// check if number of fails reaches the limit. can't overwrite existing result.
		$cache = IP_Geo_Block_API_Cache::get_cache( $validate['ip'], $settings['cache_hold'] );
		return $cache && $cache['fail'] > max( 0, (int)$settings['login_fails'] ) ? $validate + array( 'result' => 'limited' ) : $validate;
	}

	public function check_auth( $validate, $settings ) {
		// authentication should be prior to validation of country
		return $validate['auth'] ? $validate + array( 'result' => 'passed' ) : $validate; // can't overwrite existing result
	}

	public function check_nonce( $validate, $settings ) {
		// should be passed when nonce is valid. can't overwrite existing result
		$nonce = IP_Geo_Block_Util::retrieve_nonce( self::$auth_key );
		return $validate + array( 'result' => IP_Geo_Block_Util::verify_nonce( $nonce, self::$auth_key ) || 'XX' === $validate['code'] ? 'passed' : 'wp-zep' );
	}

	public function check_signature( $validate, $settings ) {
		$score = 0.0;
		$query = strtolower( urldecode( serialize( array_values( $_GET + $_POST ) ) ) );

		foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $settings['signature'] ) as $sig ) {
			$val = explode( ':', $sig, 2 );
			$sig = trim( $val[0] );

			if ( $sig && FALSE !== strpos( $query, $sig ) ) {
				if ( preg_match( '!\W!', $sig ) || // ex) `../` or `/wp-config.php`
				     preg_match( '!\b' . preg_quote( $sig, '!' ) . '\b!', $query ) ) {
					if ( ( $score += ( empty( $val[1] ) ? 1.0 : (float)$val[1] ) ) > 0.99 )
						return $validate + array( 'result' => 'badsig' ); // can't overwrite existing result
				}
			}
		}

		return $validate;
	}

	/**
	 * Validate malicious file uploading. @since 3.0.3
	 * @see wp_handle_upload() in wp-admin/includes/file.php
	 */
	public function check_upload( $validate, $settings ) {
		if ( ! empty( $_FILES ) && $settings['validation']['mimetype'] ) {
			// check capability
			$files = empty( $settings['mimetype']['capability'] ) ? TRUE : FALSE; // skip if empty
			foreach ( $settings['mimetype']['capability'] as $file ) {
				if ( empty( $file ) || IP_Geo_Block_Util::current_user_can( $file ) ) {
					$files = TRUE;
					break;
				}
			}

			// when a user does not have the capability, then block
			if ( ! apply_filters( self::PLUGIN_NAME . '-upload-capability', $files ) )
				return apply_filters( self::PLUGIN_NAME . '-upload-forbidden', $validate + array( 'upload' => TRUE, 'result' => 'upload' ) );

			foreach ( $_FILES as $files ) {
				foreach ( IP_Geo_Block_Util::arrange_files( $files ) as $file ) {
					// check $_FILES corruption attack or mime type and extension
					if ( ! empty( $file['name'] ) && UPLOAD_ERR_OK !== $file['error'] ||
					     ! IP_Geo_Block_Util::check_filetype_and_ext( $file, $settings['validation']['mimetype'], $settings['mimetype'] ) ) {
						return apply_filters( self::PLUGIN_NAME . '-upload-forbidden', $validate + array( 'upload' => TRUE, 'result' => 'upload' ) );
					}
				}
			}
		}

		return $validate;
	}

	/**
	 * Verify specific ip addresses with CIDR.
	 *
	 * @param array $validate `ip`, `auth`, `code`, `asn`, `result`
	 * @param array or string $ips the list of IP addresses with CIDR notation
	 */
	public static function check_ips( $validate, $ips ) {
		if ( filter_var( $ip = $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			require_once IP_GEO_BLOCK_PATH . 'includes/Net/IPv4.php';

			foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );
				$j[1] = isset( $j[1] ) ? min( 32, max( 0, (int)$j[1] ) ) : 32;
				if ( ( ! empty( $validate['asn'] ) && $validate['asn'] === $j[0] ) ||
				     ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && Net_IPv4::ipInNetwork( $ip, $j[0].'/'.$j[1] ) ) ) {
					return TRUE;
				}
			}
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			require_once IP_GEO_BLOCK_PATH . 'includes/Net/IPv6.php';

			foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $ips ) as $i ) {
				$j = explode( '/', $i, 2 );
				$j[1] = isset( $j[1] ) ? min( 128, max( 0, (int)$j[1] ) ) : 128;
				if ( ( ! empty( $validate['asn'] ) && $validate['asn'] === $j[0] ) ||
				     ( filter_var( $j[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && Net_IPv6::isInNetmask( $ip, $j[0].'/'.$j[1] ) ) ) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	public function check_ips_white( $validate, $settings ) {
		return self::check_ips( $validate, $settings['extra_ips']['white_list'] ) ? $validate + array( 'result' => 'passed' ) : $validate;
	}

	public function check_ips_black( $validate, $settings ) {
		return self::check_ips( $validate, $settings['extra_ips']['black_list'] ) ? $validate + array( 'result' => 'extra'  ) : $validate;
	}

	/**
	 * Validate capability on updating metadata.
	 *
	 */
	private function validate_metadata( $settings, $priority = 10 ) {
		// @since 2.6.0 apply_filters( "pre_update_option_{$option}", $value, $old_value, $option ); @since 4.4.0 `$option` was added.
		// @since 2.9.0 apply_filters( "pre_update_site_option_{$option}", $value, $old_value, $option, $network_id );
		foreach ( $settings['metadata'] as $key => $options ) {
			foreach ( $options as $option ) {
				add_filter( "{$key}_{$option}", array( $this, 'check_capability' ), $priority, 3 );
			}
		}

		// @since 2.9.0 do_action( "updated_option", $option, $old_value, $value );
		// @since 3.0.0 do_action( "update_site_option", $option, $value, $old_value, $network_id ); @since 4.7.0 `$network_id` was added.
		foreach ( $settings['monitor'] as $key => $options ) {
			$options and add_action( $key, array( $this, 'update_meta_stats' ), $priority );
		}
	}

	public function update_meta_stats( $option ) {
		if ( FALSE === strpos( $option, 'transient' ) && self::OPTION_META !== $option ) {
			$metadata = self::get_metadata();
			$action = current_filter(); // @since 2.5.0

			if ( ! isset( $metadata[ $action ][ $option ] ) )
				$metadata[ $action ][ $option ] = array( 0, 0 );

			$which =IP_Geo_Block_Util::current_user_has_caps( array( 'manage_options', 'manage_network_options' ) ) ? 1 : 0;
			$metadata[ $action ][ $option ][ $which ]++;
			self::update_metadata( $metadata );
		}
	}

	public function check_capability( $value, $old_value, $option = NULL ) {
		// allow only admin and super admin
		if ( ! IP_Geo_Block_Util::current_user_has_caps( array( 'manage_options', 'manage_network_options' ) ) ) {
			$time = microtime( TRUE );
			$ip = self::get_ip_address( $settings = self::get_option() );
			$cache = IP_Geo_Block_API_Cache::get_cache( $ip, $settings['cache_hold'] );
			$validate = self::make_validation( $ip, array(
				'result'   => 'badcap',
				'provider' => 'Cache',
				'time'     => microtime( TRUE ) - $time,
			) + ( $cache ? $cache : array() ) );

			// send response code to die if the current user does not have the right capability
			$this->endof_validate( 'admin', $validate, $settings, TRUE, TRUE, FALSE );
		}

		return $value;
	}

	/**
	 * Validate on public facing pages.
	 *
	 */
	public function validate_public() {
		$settings = self::get_option();
		$public = $settings['public'];

		// replace validation rules
		if ( $settings['validation']['public'] && -1 !== (int)$public['matching_rule'] ) {
			foreach ( array( 'matching_rule', 'white_list', 'black_list', 'response_code', 'response_msg', 'redirect_uri' ) as $key ) {
				$settings[ $key ] = $public[ $key ];
			}
		}

		// avoid redirection loop
		if ( $settings['response_code'] < 400 && IP_Geo_Block_Util::compare_url( $_SERVER['REQUEST_URI'], $settings['redirect_uri'] ? $settings['redirect_uri'] : home_url( '/' ) ) )
			return; // do not block

		if ( $public['target_rule'] ) {
			if ( ! did_action( 'wp' ) ) { // deferred validation on 'wp' when the target is specified
				add_action( 'wp', array( $this, 'validate_public' ) );
				return;
			}

			// register filter hook to check pages and post types
			add_filter( self::PLUGIN_NAME . '-public', array( $this, 'check_page' ), 10, 2 );
		}

		// validate undesired user agent
		add_filter( self::PLUGIN_NAME . '-public', array( $this, 'check_ua' ), 9, 2 );

		// validate bad behavior by bots and crawlers
		$public['behavior'] and add_filter( self::PLUGIN_NAME . '-public', array( $this, 'check_behavior' ), 9, 2 );

		// retrieve IP address of visitor via proxy services
		add_filter( self::PLUGIN_NAME . '-ip-addr', array( 'IP_Geo_Block_Util', 'get_proxy_ip' ), 20, 1 );

		// validate country by IP address (block: true, die: false)
		$this->validate_ip( 'public', $settings, 1 & $settings['validation']['public'] );
	}

	public function check_behavior( $validate, $settings ) {
		// check if page view with a short period time is under the threshold
		$cache = IP_Geo_Block_API_Cache::get_cache( self::$remote_addr, $settings['cache_hold'] );

		if ( $cache && $cache['view'] >= $settings['behavior']['view'] && $_SERVER['REQUEST_TIME'] - $cache['last'] <= $settings['behavior']['time'] )
			return $validate + array( 'result' => 'badbot' ); // can't overwrite existing result

		return $validate;
	}

	public function check_page( $validate, $settings ) {
		global $pagename, $post;
		$public = $settings['public'];

		if ( $pagename ) {
			// check page
			if ( isset( $public['target_pages'][ $pagename ] ) )
				return $validate; // block by country
		} elseif ( $post ) {
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

	public function check_ua( $validate, $settings ) {
		// mask HOST if DNS lookup is false
		if ( empty( $settings['public']['dnslkup'] ) )
			$settings['public']['ua_list'] = IP_Geo_Block_Util::mask_qualification( $settings['public']['ua_list'] );

		// get the name of host (from the cache if exists)
		if ( ! isset( $validate['host'] ) && FALSE !== strpos( $settings['public']['ua_list'], 'HOST' ) ) {
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';
			$validate['host'] = IP_Geo_Block_Lkup::gethostbyaddr( $validate['ip'] );
		}

		// check requested url
		$is_feed = IP_Geo_Block_Util::is_feed( $this->request_uri );
		$u_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$referer = isset( $_SERVER['HTTP_REFERER'   ] ) ? $_SERVER['HTTP_REFERER'   ] : '';

		foreach ( IP_Geo_Block_Util::multiexplode( array( ",", "\n" ), $settings['public']['ua_list'] ) as $pat ) {
			list( $name, $code ) = array_pad( IP_Geo_Block_Util::multiexplode( array( ':', '#' ), $pat ), 2, '' );

			if ( $name && ( '*' === $name || FALSE !== strpos( $u_agent, $name ) ) ) {
				$which = ( FALSE !== strpos( $pat, '#' ) );     // 0: pass (':'), 1: block ('#')
				$not   = ( '!' === $code[0] );                  // 0: positive, 1: negative
				$code  = ( $not ? substr( $code, 1 ) : $code ); // qualification identifier

				if ( 'FEED' === $code ) {
					if ( $not xor $is_feed )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( 'HOST' === $code ) {
					if ( $not xor $validate['host'] !== $validate['ip'] )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( 0 === strncmp( 'HOST=', $code, 5 ) ) {
					if ( $not xor FALSE !== strpos( $validate['host'], substr( $code, 5 ) ) )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( 0 === strncmp( 'REF=', $code, 4 ) ) {
					if ( $not xor FALSE !== strpos( $referer, substr( $code, 4 ) ) )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( 0 === strncmp( 'AS', $code, 2 ) ) {
					if ( $not xor $validate['asn'] === $code )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( '*' === $code || 2 === strlen( $code ) ) {
					if ( $not xor ( '*' === $code || $validate['code'] === $code ) )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}

				elseif ( preg_match( '!^[a-f\d\.:/]+$!', $code = substr( $pat, strpos( $pat, $code ) ) ) ) {
					if ( $not xor $this->check_ips( $validate, $code ) )
						return $validate + array( 'result' => $which ? 'blockUA' : 'passUA' );
				}
			}
		}

		return $validate;
	}

	/**
	 * Handlers of cron job for database and garbage collection for cache.
	 *
	 */
	public function exec_update_db( $immediate = FALSE ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
		return IP_Geo_Block_Cron::exec_update_db( $immediate );
	}

	public function exec_cache_gc() {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
		IP_Geo_Block_Cron::exec_cache_gc( self::get_option() );
	}

}