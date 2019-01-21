<?php
/**
 * IP Geo Block - Admin class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 */

class IP_Geo_Block_Admin {

	/**
	 * Constants for admin class
	 *
	 */
	const INTERVAL_LIVE_UPDATE = 5; // interval for live update [sec]
	const TIMEOUT_LIVE_UPDATE = 60; // timeout of pausing live update [sec]

	/**
	 * Globals in this class
	 *
	 */
	private static $instance = NULL;
	private $is_network_admin = FALSE;
	private $admin_tab = 0;

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {
		// Setup the tab number.
		$this->admin_tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$this->admin_tab = min( 5, max( 0, $this->admin_tab ) );

		// Load plugin text domain and add body class
		add_action( 'init', array( $this, 'admin_init' ) );

		// Add suggest text for inclusion in the site's privacy policy. @since 4.9.6
		// add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );

		// Setup a nonce to validate authentication.
		add_filter( 'wp_redirect', array( $this, 'add_redirect_nonce' ), 10, 2 ); // @since 2.1.0
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Load the plugin text domain for translation and add body class.
	 *
	 */
	public function admin_init() {
		// include drop in for admin if it exists
		$settings = IP_Geo_Block::get_option();
		file_exists( $file = IP_Geo_Block_Util::unslashit( $settings['api_dir'] ) . '/drop-in-admin.php' ) and include( $file );

		// Add the options page and menu item.
		add_action( 'admin_menu',                 array( $this, 'setup_admin_page'    ) ); // @since: 2.5.0
		add_action( 'admin_post_ip_geo_block',    array( $this, 'admin_ajax_callback' ) ); // @since: 2.6.0
		add_action( 'wp_ajax_ip_geo_block',       array( $this, 'admin_ajax_callback' ) ); // @since: 2.1.0
		add_filter( 'wp_prepare_revision_for_js', array( $this, 'add_revision_nonce'  ), 10, 3 );

		if ( IP_Geo_Block_Util::is_user_logged_in() )
			add_filter( IP_Geo_Block::PLUGIN_NAME . '-bypass-admins', array( $this, 'verify_request' ), 10, 2 );

		if ( is_multisite() && is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) { // @since: 3.0.0
			$this->is_network_admin = current_user_can( 'manage_network_options' );
			add_action( 'network_admin_menu', array( $this, 'setup_admin_page' ) ); // @since: 2.5
			add_action( 'wpmu_new_blog', array( $this, 'create_blog' ), 10, 6 ); // on creating a new blog @since MU
			add_action( 'delete_blog',   array( $this, 'delete_blog' ), 10, 2 ); // on deleting an old blog @since 3.0.0
		}

		// loads a plugin’s translated strings.
		load_plugin_textdomain( IP_Geo_Block::PLUGIN_NAME, FALSE, dirname( IP_GEO_BLOCK_BASE ) . '/languages/' );

		// add webview class into body tag.
		// https://stackoverflow.com/questions/37591279/detect-if-user-is-using-webview-for-android-ios-or-a-regular-browser
		if (  isset( $_SERVER['HTTP_USER_AGENT'] ) &&
		   ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile/' ) !== FALSE ) &&
		   ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Safari/' ) === FALSE ) ) {
			add_filter( 'admin_body_class', array( $this, 'add_webview_class' ) );
		}

		// for Android
		elseif ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === "com.company.app" ) {
			add_filter( 'admin_body_class', array( $this, 'add_webview_class' ) );
		}
	}

	/**
	 * Whether this plugin activated by network or not.
	 *
	 */
	public function is_network_admin() {
		return $this->is_network_admin;
	}

	/**
	 * Add webview class into the body.
	 *
	 */
	public function add_webview_class( $classes ) {
		return $classes . ($classes ? ' ' : '') . 'webview';
	}

	/**
	 * Add nonce when redirect into wp-admin area.
	 *
	 */
	public function add_redirect_nonce( $location, $status ) {
		$status = TRUE; // default is `retrieve` a nonce
		$urls = array( wp_login_url() );

		// avoid multiple redirection caused by WP hide 1.4.9.1
		if ( is_plugin_active( 'wp-hide-security-enhancer/wp-hide.php' ) )
			$urls[] = 'options-permalink.php';

		foreach ( $urls as $url ) {
			if ( FALSE !== strpos( $location, $url ) ) {
				$status = FALSE; // do not `retieve` a nonce
				break;
			}
		}

		return IP_Geo_Block_Util::rebuild_nonce( $location, $status );
	}

	/**
	 * Add nonce to revision @since 4.4.0
	 *
	 */
	public function add_revision_nonce( $revisions_data, $revision, $post ) {
		$revisions_data['restoreUrl'] = add_query_arg(
			$nonce = IP_Geo_Block::get_auth_key(),
			IP_Geo_Block_Util::create_nonce( $nonce ),
			$revisions_data['restoreUrl']
		);

		return $revisions_data;
	}

	/**
	 * Verify admin screen without action instead of validating nonce.
	 *
	 */
	public function verify_request( $queries, $settings ) {
		// the request that is intended to show the page without any action follows authentication of core.
		if ( 'GET' === $_SERVER['REQUEST_METHOD'] && isset( $_GET['page'] ) ) {
			foreach ( array( 'action', 'task') as $key ) {
				if ( ! empty( $_GET[ $key ] ) )
					return $queries;
			}
			$queries[] = $_GET['page'];
		}

		return $queries;
	}

	/**
	 * Do some procedures when a blog is created or deleted.
	 *
	 */
	public function create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		defined( 'IP_GEO_BLOCK_DEBUG' ) and IP_GEO_BLOCK_DEBUG and assert( is_main_site(), 'Not main blog.' );
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php';

		// get options on main blog
		$settings = IP_Geo_Block::get_option();

		// Switch to the new blog and initialize.
		switch_to_blog( $blog_id );
		IP_Geo_Block_Activate::activate_blog();

		// Copy option from main blog.
		if ( $this->is_network_admin && $settings['network_wide'] )
			IP_Geo_Block::update_option( $settings, FALSE );

		// Restore the main blog.
		restore_current_blog();
	}

	public function delete_blog( $blog_id, $drop ) {
		// blog is already switched to the target in wpmu_delete_blog()
		$drop and IP_Geo_Block_Logs::delete_tables();
	}

	/**
	 * Get the action name of ajax for nonce
	 *
	 */
	private function get_ajax_action() {
		return IP_Geo_Block::PLUGIN_NAME . '-ajax-action';
	}

	/**
	 * Register and enqueue plugin-specific style sheet and JavaScript.
	 *
	 * @see https://developers.google.com/maps/faq#china_ws_access
	 */
	public function enqueue_admin_assets() {
		$release = ( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG );

		$footer = TRUE;
		$dependency = array( 'jquery' );
		$version = $release ? IP_Geo_Block::VERSION : max(
			filemtime( plugin_dir_path( __FILE__ ) . 'css/admin.css' ),
			filemtime( plugin_dir_path( __FILE__ ) . 'js/admin.js'   )
		);

		switch ( $this->admin_tab ) {
		  case 1: /* Statistics */
		  case 4: /* Logs */
			// css and js for DataTables
			wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-datatables-css',
				plugins_url( 'datatables/css/datatables-all.min.css', __FILE__ ),
				array(), IP_Geo_Block::VERSION
			);
			wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-datatables-js',
				plugins_url( 'datatables/js/datatables-all.min.js', __FILE__ ),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			if ( 4 === $this->admin_tab )
				break;

		  case 5: /* Sites list */
			// js for google charts
			wp_register_script(
				$addon = IP_Geo_Block::PLUGIN_NAME . '-google-chart',
				apply_filters( 'google-charts', 'https://www.gstatic.com/charts/loader.js' ), array(), NULL, $footer
			);
			wp_enqueue_script( $addon );
			break;

		  case 2: /* Search */
			// Google Map in China
			$geo = IP_Geo_Block::get_geolocation();
			if ( isset( $geo['code'] ) && 'CN' === $geo['code'] ) {
				add_filter( 'google-charts',     array( $this, 'google_charts_cn'     ) );
				add_filter( 'google-maps',       array( $this, 'google_maps_cn'       ) );
				add_filter( 'google-maps-nokey', array( $this, 'google_maps_nokey_cn' ) );
			}

			// js for google map
			$settings = IP_Geo_Block::get_option();
			if ( $key = $settings['api_key']['GoogleMap'] ) {
				wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-gmap-js',
					plugins_url( $release ? 'js/gmap.min.js' : 'js/gmap.js', __FILE__ ),
					$dependency, IP_Geo_Block::VERSION, $footer
				);
				wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-google-map',
					apply_filters( 'google-maps', '//maps.googleapis.com/maps/api/js' ) . ( 'default' !== $key ? "?key=$key" : '' ),
					$dependency, IP_Geo_Block::VERSION, $footer
				);
			}

			wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-whois-js',
				plugins_url( $release ? 'js/whois.min.js' : 'js/whois.js', __FILE__ ),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			break;
		}

		// css for option page
		wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-admin-icons',
			plugins_url( $release ? 'css/admin-icons.min.css' : 'css/admin-icons.css', __FILE__ ),
			array(), IP_Geo_Block::VERSION
		);
		wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-admin-styles',
			plugins_url( $release ? 'css/admin.min.css' : 'css/admin.css', __FILE__ ),
			array(), $version
		);

		// js for IP Geo Block admin page
		wp_register_script(
			$handle = IP_Geo_Block::PLUGIN_NAME . '-admin-script',
			plugins_url( $release ? 'js/admin.min.js' : 'js/admin.js', __FILE__ ),
			$dependency + ( isset( $addon ) ? array( $addon ) : array() ),
			$version, $footer
		);
		wp_localize_script( $handle,
			'IP_GEO_BLOCK',
			array(
				'action' => 'ip_geo_block',
				'tab' => $this->admin_tab,
				'url' => admin_url( 'admin-ajax.php' ),
				'nonce' => IP_Geo_Block_Util::create_nonce( $this->get_ajax_action() ),
				'msg' => array(
					/* [ 0] */ __( 'Are you sure ?',              'ip-geo-block' ),
					/* [ 1] */ __( 'Open a new window',           'ip-geo-block' ),
					/* [ 2] */ __( 'Generate new link',           'ip-geo-block' ),
					/* [ 3] */ __( 'Delete current link',         'ip-geo-block' ),
					/* [ 4] */ __( 'Please add the following link to favorites / bookmarks in your browser : ', 'ip-geo-block' ),
					/* [ 5] */ __( 'ajax for logged-in user',     'ip-geo-block' ),
					/* [ 6] */ __( 'ajax for non logged-in user', 'ip-geo-block' ),
					/* [ 7] */ __( '[Found: %d]',                 'ip-geo-block' ),
					/* [ 8] */ __( 'Find and verify `%s` on &#8220;Logs&#8221; tab.',          'ip-geo-block' ),
					/* [ 9] */ __( 'This feature is available with HTML5 compliant browsers.', 'ip-geo-block' ),
					/* [10] */ __( 'The selected row cannot be found in the table.',           'ip-geo-block' ),
					/* [11] */ __( 'An error occurred while executing the ajax command `%s`.', 'ip-geo-block' ),
				),
				'i18n' => array(
					/* [ 0] */ '<div class="ip-geo-block-loading"></div>',
					/* [ 1] */ __( 'No data available in table',  'ip-geo-block' ),
					/* [ 2] */ __( 'No matching records found',   'ip-geo-block' ),
					/* [ 3] */ __( 'IP address',                  'ip-geo-block' ),
					/* [ 4] */ __( 'Code',                        'ip-geo-block' ),
					/* [ 5] */ __( 'ASN',                         'ip-geo-block' ),
					/* [ 6] */ __( 'Host name',                   'ip-geo-block' ),
					/* [ 7] */ __( 'Target',                      'ip-geo-block' ),
					/* [ 8] */ __( 'Failure / Total',             'ip-geo-block' ),
					/* [ 9] */ __( 'Elapsed[sec]',                'ip-geo-block' ),
					/* [10] */ __( 'Time',                        'ip-geo-block' ),
					/* [11] */ __( 'Result',                      'ip-geo-block' ),
					/* [12] */ __( 'Request',                     'ip-geo-block' ),
					/* [13] */ __( 'User agent',                  'ip-geo-block' ),
					/* [14] */ __( 'HTTP headers',                'ip-geo-block' ),
					/* [15] */ __( '$_POST data',                 'ip-geo-block' ),
				),
				'interval' => self::INTERVAL_LIVE_UPDATE, // interval for live update [sec]
				'timeout'  => self::TIMEOUT_LIVE_UPDATE,  // timeout of pausing live update [sec]
				'altgmap'  => apply_filters( 'google-maps-nokey', 'https://www.google.com/maps/embed' ),
			)
		);
		wp_enqueue_script( $handle );
	}

	/**
	 * Google Map in China
	 *
	 */
	public function google_charts_cn    ( $url ) { return 'https://www.gstatic.cn/charts/loader.js'; }
	public function google_maps_cn      ( $url ) { return '//maps.google.cn/maps/api/js';            }
	public function google_maps_nokey_cn( $url ) { return '//maps.google.cn/maps';                   }

	/**
	 * Add plugin meta links
	 *
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( $file === IP_GEO_BLOCK_BASE ) {
			array_push(
				$links,
				'<a href="https://github.com/tokkonopapa/Wordpress-ip-geo-block" title="tokkonopapa/WordPress-IP-Geo-Block" target=_blank>' . __( 'Contribute on GitHub', 'ip-geo-block' ) . '</a>'
			);
		}

		return $links;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 */
	public function add_action_links( $links ) {
		$settings = IP_Geo_Block::get_option();
		return array_merge(
			array( 'settings' => '<a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $this->dashboard_url( $settings['network_wide'] ) ) ) . '">' . __( 'Settings' ) . '</a>' ),
			$links
		);
	}

	/**
	 * Add suggest text for inclusion in the site's privacy policy. @since 4.9.6
	 *
	 * /wp-admin/tools.php?wp-privacy-policy-guide
	 * https://developer.wordpress.org/plugins/privacy/privacy-related-options-hooks-and-capabilities/
	 */
	public function add_privacy_policy() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) )
			wp_add_privacy_policy_content( 'IP Geo Block', __( 'suggested text.', 'ip-geo-block' ) );
	}

	/**
	 * Show global notice.
	 *
	 */
	public function show_admin_notices() {
		$key = IP_Geo_Block::PLUGIN_NAME . '-notice';

		if ( FALSE !== ( $notices = get_transient( $key ) ) ) {
			foreach ( $notices as $msg => $type ) {
				echo "\n", '<div class="notice is-dismissible ', esc_attr( $type ), '"><p>';
				if ( 'updated' === $type )
					echo '<strong>', IP_Geo_Block_Util::kses( $msg ), '</strong>';
				else
					echo '<strong>IP Geo Block:</strong> ', IP_Geo_Block_Util::kses( $msg );
				echo '</p></div>', "\n";
			}

			// delete all admin noties
			delete_transient( $key );
		}
	}

	/**
	 * Add global notice.
	 *
	 */
	public static function add_admin_notice( $type, $msg ) {
		$key = IP_Geo_Block::PLUGIN_NAME . '-notice';
		if ( FALSE === ( $notices = get_transient( $key ) ) )
			$notices = array();

		// can't overwrite the existent notice
		if ( ! isset( $notices[ $msg ] ) ) {
			$notices[ $msg ] = $type;
			set_transient( $key, $notices, MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Get the admin url that depends on network multisite.
	 *
	 */
	public function dashboard_url( $network_wide = FALSE ) {
		return ( $network_wide ? $this->is_network_admin : $network_wide ) ? network_admin_url( 'admin.php' /*'settings.php'*/ ) : admin_url( 'options-general.php' );
	}

	/**
	 * Register the administration menu into the WordPress Dashboard menu.
	 *
	 */
	private function add_plugin_admin_menu( $settings ) {
		// Control tab number
		if ( $admin_menu = ( 'admin_menu' === current_filter() ) ) {
			if ( $this->is_network_admin && $settings['network_wide'] )
				$this->admin_tab = min( 4, max( 1, $this->admin_tab ) );
			else
				$this->admin_tab = min( 4, max( 0, $this->admin_tab ) );
		} else {
			if ( $this->is_network_admin && $settings['network_wide'] )
				$this->admin_tab = in_array( $this->admin_tab, array( 0, 5 ), TRUE ) ? $this->admin_tab : 0;
			else
				$this->admin_tab = 5;
		}

		if ( $admin_menu ) {
			// `options-general.php` ==> `options.php` ==> `settings-updated` is added as query just after settings updated.
			if ( ! empty( $_REQUEST['page'] ) && IP_Geo_Block::PLUGIN_NAME === $_REQUEST['page'] &&
			     ! empty( $_REQUEST['settings-updated'] ) && $this->is_network_admin && $settings['network_wide'] ) {
				$this->update_multisite_settings( $settings );
				wp_safe_redirect( esc_url_raw( add_query_arg(
					array( 'page' => IP_Geo_Block::PLUGIN_NAME ),
					$this->dashboard_url( TRUE )
				) ) );
				exit;
			}

			// Add a settings page for this plugin to the Settings menu.
			$hook = add_options_page(
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'IP Geo Block', 'ip-geo-block' ),
				'manage_options',
				IP_Geo_Block::PLUGIN_NAME,
				array( $this, 'display_plugin_admin_page' )
			);
		}

		elseif ( $this->is_network_admin ) {
			// Add a settings page for this plugin to the Settings menu.
			$hook = add_menu_page(
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'IP Geo Block', 'ip-geo-block' ),
				'manage_network_options',
				IP_Geo_Block::PLUGIN_NAME,
				array( $this, 'display_plugin_admin_page' )
				//, 'dashicons-admin-site' // or 'data:image/svg+xml;base64...'
			);

			add_submenu_page(
				IP_Geo_Block::PLUGIN_NAME,
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'Sites list', 'ip-geo-block' ),
				'manage_network_options',
				IP_Geo_Block::PLUGIN_NAME . '&amp;tab=5',
				array( $this, 'display_plugin_admin_page' )
			);

			if ( $settings['network_wide'] ) {
				add_submenu_page(
				IP_Geo_Block::PLUGIN_NAME,
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'Settings', 'ip-geo-block' ),
				'manage_network_options',
				IP_Geo_Block::PLUGIN_NAME,
				array( $this, 'display_plugin_admin_page' )
				);
			}

			wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-admin-icons',
				plugins_url( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
					'css/admin-icons.min.css' : 'css/admin-icons.css', __FILE__
				),
				array(), IP_Geo_Block::VERSION
			);
		}

		// If successful, load admin assets only on this page.
		if ( ! empty( $hook ) ) // 'admin_enqueue_scripts'
			add_action( "load-$hook", array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Diagnosis of admin settings.
	 *
	 */
	private function diagnose_admin_screen( $settings ) {
		$updating = get_transient( IP_Geo_Block::CRON_NAME );
		$adminurl = $this->dashboard_url( FALSE );
		$network  = $this->dashboard_url( $settings['network_wide'] );

		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.7.0' ) < 0 )
			self::add_admin_notice( 'error', __( 'You need WordPress 3.7+.', 'ip-geo-block' ) );

		if ( ! $settings['api_dir'] || ! file_exists( $settings['api_dir'] ) ) {
			$this->add_admin_notice( 'error', sprintf(
				__( 'Can not load Geolocation API libraries from <code>%s</code>. It seems to have failed downloading <a rel="noreferrer" href="https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip" title="Download the contents of tokkonopapa/WordPress-IP-Geo-API as a zip file">ZIP file</a> from <a rel="noreferrer" href="https://github.com/tokkonopapa/WordPress-IP-Geo-API" title="tokkonopapa/WordPress-IP-Geo-API - GitHub">WordPress-IP-Geo-API</a>. Try to deactivate IP Geo Block once and activate it again, or install <code>ip-geo-api</code> with write permission according to <a rel="noreferrer" href="https://www.ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How can I fix permission troubles? | IP Geo Block">this instruction</a>.', 'ip-geo-block' ),
				apply_filters( 'ip-geo-block-api-dir', $settings['api_dir'] ? $settings['api_dir'] : basename( WP_CONTENT_DIR ) )
			) );
		}

		else {
			$providers = IP_Geo_Block_Provider::get_valid_providers( $settings, FALSE, FALSE, TRUE );
			if ( empty( $providers ) ) {
				$this->add_admin_notice( 'error', sprintf(
					__( 'You should select at least one API at <a href="%s">Geolocation API settings</a>. Otherwise <strong>you\'ll be blocked</strong> after the cache expires.', 'ip-geo-block' ),
					esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0, 'sec' => 4 ), $network ) ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-4'
				) );
			}

			else {
				$providers = IP_Geo_Block_Provider::get_addons( $settings['providers'] );
				if ( empty( $providers ) ) {
					$this->add_admin_notice( 'error', sprintf(
						__( 'You should select at least one API for local database at <a href="%s">Geolocation API settings</a>. Otherwise access to the external API may slow down the site.', 'ip-geo-block' ),
						esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0, 'sec' => 4 ), $network ) ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-4'
					) );
				}
			}
		}

		// Check consistency of matching rule
		if ( -1 === (int)$settings['matching_rule'] ) {
			if ( FALSE !== $updating ) {
				self::add_admin_notice( 'notice-warning', sprintf(
					__( 'Now downloading geolocation databases in background. After a little while, please check your country code and &#8220;<strong>Matching rule</strong>&#8221; at <a href="%s">Validation rules and behavior</a>.', 'ip-geo-block' ),
					esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $network ) )
				) );
			}
			else {
				self::add_admin_notice( 'error', sprintf(
					__( 'The &#8220;<strong>Matching rule</strong>&#8221; is not set properly. Please confirm it at <a href="%s">Validation rules and behavior</a>.', 'ip-geo-block' ),
					esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $network ) )
				) );
			}
		}

		// Check to finish updating matching rule
		elseif ( 'done' === $updating ) {
			delete_transient( IP_Geo_Block::CRON_NAME );
			self::add_admin_notice( 'updated ', __( 'Local database and matching rule have been updated.', 'ip-geo-block' ) );
		}

		// Check self blocking (skip during updating)
		if ( FALSE === $updating && 1 === (int)$settings['validation']['login'] ) {
			$instance = IP_Geo_Block::get_instance();
			$validate = $instance->validate_ip( 'login', $settings, TRUE, FALSE ); // skip authentication check

			switch( $validate['result'] ) {
			  case 'limited':
				self::add_admin_notice( 'error',
					__( 'Once you logout, you will be unable to login again because the number of login attempts reaches the limit.', 'ip-geo-block' ) . ' ' .
					sprintf(
						__( 'Please remove your IP address in &#8220;%1$sStatistics in IP address cache%2$s&#8221; on &#8220;%3$sStatistics%4$s&#8221; tab to prevent locking yourself out.', 'ip-geo-block' ),
						'<strong><a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1, 'sec' => 2 ), $adminurl ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-2' ) . '">', '</a></strong>',
						'<strong>', '</strong>'
					)
				);
				break;

			  case 'blocked':
			  case 'extra':
				self::add_admin_notice( 'error',
					( $settings['matching_rule'] ?
						__( 'Once you logout, you will be unable to login again because your country code or IP address is in the blacklist.', 'ip-geo-block' ) :
						__( 'Once you logout, you will be unable to login again because your country code or IP address is not in the whitelist.', 'ip-geo-block' )
					) . ' ' .
					( 'ZZ' !== $validate['code'] ?
						sprintf(
							__( 'Please check your &#8220;%sValidation rules and behavior%s&#8221;.', 'ip-geo-block' ),
							'<strong><a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0, 'sec' => 0 ), $network  ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-0' ) . '">', '</a></strong>'
						) :
						sprintf(
							__( 'Please confirm your local geolocation database files exist at &#8220;%sLocal database settings%s&#8221; section, or remove your IP address in cache at &#8220;%sStatistics in cache%s&#8221; section.', 'ip-geo-block' ),
							'<strong><a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0, 'sec' => 5 ), $network  ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-5' ) . '">', '</a></strong>',
							'<strong><a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1, 'sec' => 2 ), $adminurl ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-2' ) . '">', '</a></strong>'
						)
					)
				);
				break;
			}
		}

		// Check consistency of emergency login link
		if ( isset( $settings['login_link'] ) && $settings['login_link']['link'] && ! IP_Geo_Block_Util::verify_link( $settings['login_link']['link'], $settings['login_link']['hash'] ) ) {
			self::add_admin_notice( 'error',
				sprintf(
					__( 'Emergency login link is outdated. Please delete it once and generate again at &#8220;%sPlugin settings%s&#8221; section. Also do not forget to update favorites / bookmarks in your browser.', 'ip-geo-block' ),
					'<strong><a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0, 'sec' => 7 ), $network ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-7' ) . '">', '</a></strong>'
				)
			);
		}

		// Check activation of IP Geo Allow
		if ( $settings['validation']['timing'] && is_plugin_active( 'ip-geo-allow/index.php' ) ) {
			self::add_admin_notice( 'error',
				__( '&#8220;mu-plugins&#8221; (ip-geo-block-mu.php) at &#8220;Validation timing&#8221; is imcompatible with <strong>IP Geo Allow</strong>. Please select &#8220;init&#8221; action hook.', 'ip-geo-block' )
			);
		}
	}

	/**
	 * Setup menu and option page for this plugin
	 *
	 */
	public function setup_admin_page() {
		$settings = IP_Geo_Block::get_option();

		// Register the administration menu.
		$this->add_plugin_admin_menu( $settings );

		// Avoid multiple validation.
		if ( 'GET' === $_SERVER['REQUEST_METHOD'] )
			$this->diagnose_admin_screen( $settings );

		// Register settings page only if it is needed.
		if ( ( isset( $_GET ['page'       ] ) && IP_Geo_Block::PLUGIN_NAME === $_GET ['page'       ] ) ||
		     ( isset( $_POST['option_page'] ) && IP_Geo_Block::PLUGIN_NAME === $_POST['option_page'] ) ) {
			$this->register_settings_tab();
		}

		// Add an action link pointing to the options page. @since 2.7
		else {
			add_filter( 'plugin_row_meta',                          array( $this, 'add_plugin_meta_links' ), 10, 2 );
			add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links'      ), 10, 1 );
		}

		// Register scripts for admin.
		add_action( 'admin_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ), 0 );

		// Show admin notices at the place where it should be. @since 2.5.0
		add_action( 'admin_notices',         array( $this, 'show_admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Get cookie that indicates open/close section
	 *
	 */
	public function get_cookie() {
		static $cookie = array();

		if ( empty( $cookie ) && ! empty( $_COOKIE[ IP_Geo_Block::PLUGIN_NAME ] ) ) {
			foreach ( explode( '&', $_COOKIE[ IP_Geo_Block::PLUGIN_NAME ] ) as $i => $v ) {
				list( $i, $v ) = explode( '=', $v );
				$cookie[ $i ] = str_split( $v );
			}
		}

		return $cookie;
	}

	/**
	 * Prints out all settings sections added to a particular settings page
	 *
	 * wp-admin/includes/template.php @since 2.7.0
	 */
	private function do_settings_sections( $page, $tab ) {
		global $wp_settings_sections, $wp_settings_fields;

		// target section to be opened
		$target = isset( $_GET['sec'] ) ? (int)$_GET['sec'] : -1;

		if ( isset( $wp_settings_sections[ $page ] ) ) {
			$index  = 0; // index of fieldset
			$cookie = $this->get_cookie();

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				// TRUE if open ('o') or FALSE if close ('x')
				$stat = empty( $cookie[ $tab ][ $index ] ) || 'x' !== $cookie[ $tab ][ $index ] || $index === $target;

				echo "\n", '<fieldset id="', IP_Geo_Block::PLUGIN_NAME, '-section-', $index, '" class="', IP_Geo_Block::PLUGIN_NAME, '-field panel panel-default" data-section="', $index, '">', "\n",
				     '<legend class="panel-heading"><h3 class="', IP_Geo_Block::PLUGIN_NAME, ( $stat ? '-dropdown' : '-dropup' ), '">',
				     is_array( $section['title'] ) ? $section['title'][0] . '<span class="' . IP_Geo_Block::PLUGIN_NAME . '-help-link">[ ' . $section['title'][1] . ' ]</span>' : $section['title'],
				     '</h3></legend>', "\n", '<div class="panel-body',
				     ($stat ? ' ' . IP_Geo_Block::PLUGIN_NAME . '-border"' : '"'),
				     ($stat || (4 === $tab && $index) ? '>' : ' style="display:none">'), "\n";

				if ( $section['callback'] )
					call_user_func( $section['callback'], $section );

				if ( isset( $wp_settings_fields,
				            $wp_settings_fields[ $page ],
				            $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					echo '<table class="form-table">';
					do_settings_fields( $page, $section['id'] );
					echo "</table>\n";
				}

				echo "</div>\n</fieldset>\n";
				++$index;
			}
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page() {
		$tab = $this->admin_tab;
		$tabs = array(
			5 => __( 'Sites list',   'ip-geo-block' ),
			0 => __( 'Settings',     'ip-geo-block' ),
			1 => __( 'Statistics',   'ip-geo-block' ),
			4 => __( 'Logs',         'ip-geo-block' ),
			2 => __( 'Search',       'ip-geo-block' ),
			3 => __( 'Attribution',  'ip-geo-block' ),
		);

		$settings = IP_Geo_Block::get_option();
		$cookie = $this->get_cookie();
		$title = esc_html( get_admin_page_title() );

		// Target page that depends on the network multisite or not.
		if ( 'options-general.php' === $GLOBALS['pagenow'] ) {
			$action = 'options.php';
			unset( $tabs[5] ); // Sites list
			if ( $this->is_network_admin ) {
				$title .= ' <span class="ip-geo-block-menu-link"> [ ';
				$title .= '<a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 5 ), $this->dashboard_url( TRUE ) ) ) . '" target="_self">' . __( 'Sites list', 'ip-geo-block' ) . '</a>';
				if ( $settings['network_wide'] ) {
					unset( $tabs[0] ); // Settings
					$title .= ' / <a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0 ), $this->dashboard_url( TRUE ) ) ) . '" target="_self">' . __( 'Settings', 'ip-geo-block' ) . '</a>';
				}
				$title .= ' ]</span>';
			}
		}

		// '/wp-admin/network/admin.php'
		else {
			// `edit.php` is an action handler for Multisite administration dashboard.
			// `edit.php` ==> do action `network_admin_edit_ip-geo-block` ==> `validate_network_settings()`
			$action = 'edit.php?action=' . IP_Geo_Block::PLUGIN_NAME;
			if ( $this->is_network_admin ) {
				unset( $tabs[1], $tabs[4], $tabs[2], $tabs[3] ); // Statistics, Logs, Search, Attribution
				$title .= ' <span class="ip-geo-block-menu-link"> [ ';
				$title .= __( 'Sites list', 'ip-geo-block' );
				if ( $settings['network_wide'] ) {
					$title .= ' / ' . __( 'Settings', 'ip-geo-block' );
				} else {
					unset( $tabs[0] ); // Settings
				}
				$title .= ' ]</span>';
			}
		}

?>
<div class="wrap">
	<h2><?php echo $title; ?></h2>
	<h2 class="nav-tab-wrapper">
<?php foreach ( $tabs as $key => $val ) {
	echo '<a href="?page=', IP_Geo_Block::PLUGIN_NAME, '&amp;tab=', $key, '" class="nav-tab', ($tab === $key ? ' nav-tab-active' : ''), '">', $val, '</a>';
} ?>
	</h2>
	<p class="ip-geo-block-navi-link">[ <a id="ip-geo-block-toggle-sections" href="#!"><?php _e( 'Toggle all', 'ip-geo-block' ); ?></a> ]
<?php if ( 4 === $tab ) { /* Logs tab */ ?>
	<input id="ip-geo-block-live-update" type="checkbox"<?php checked( isset( $cookie[4][1] ) && 'o' === $cookie[4][1] ); disabled( $settings['validation']['reclogs'] && extension_loaded( 'pdo_sqlite' ), FALSE ); ?> /><label for="ip-geo-block-live-update">
		<dfn title="<?php _e( 'Independent of &#8220;Privacy and record settings&#8221;, you can see all the requests validated by this plugin in almost real time.', 'ip-geo-block' ); ?>"><?php _e( 'Live update', 'ip-geo-block' ); ?></dfn>
	</label>
<?php } elseif (5 === $tab ) { /* Sites list tab */ ?>
	<input id="ip-geo-block-open-new" type="checkbox"<?php checked( isset( $cookie[5][1] ) && 'o' === $cookie[5][1] );?> /><label for="ip-geo-block-open-new">
		<dfn title="<?php _e( 'Open a new window on clicking the link in the chart.', 'ip-geo-block' ); ?>"><?php _e( 'Open a new window', 'ip-geo-block' ); ?></dfn>
	</label>
<?php } ?></p>
	<form method="post" action="<?php echo $action; ?>" id="<?php echo IP_Geo_Block::PLUGIN_NAME, '-', $tab; ?>"<?php if ( $tab ) echo " class=\"", IP_Geo_Block::PLUGIN_NAME, "-inhibit\""; ?>>
<?php
		settings_fields( IP_Geo_Block::PLUGIN_NAME );
		$this->do_settings_sections( IP_Geo_Block::PLUGIN_NAME, $tab );
		if ( 0 === $tab )
			submit_button(); // @since 3.1
?>
	</form>
<?php if ( 2 === $tab ) { /* Search tab */ ?>
	<div id="ip-geo-block-apis"></div>
	<div id="ip-geo-block-map"></div>
	<div id="ip-geo-block-whois"></div>
<?php } elseif ( 3 === $tab ) { /* Attribute tab */
	// show attribution (higher priority order)
	$tab = array();
	foreach ( IP_Geo_Block_Provider::get_addons() as $provider ) {
		if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
			$tab[] = $geo->get_attribution();
		}
	}
	echo '<p>', implode( '<br />', $tab ), "</p>\n";
	echo '<p>', __( 'Thanks for providing these great services for free.', 'ip-geo-block' ), "<br />\n";
	echo __( '(Most browsers will redirect you to each site <a href="https://www.ipgeoblock.com/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', 'ip-geo-block' ), "</p>\n";
} ?>
<?php if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
	echo '<p>', get_num_queries(), ' queries. ', timer_stop(0), ' seconds. ', memory_get_usage(), " bytes.</p>\n";
} ?>
	<p id="ip-geo-block-back-to-top">[ <a href="#"><?php _e( 'Back to top', 'ip-geo-block' ); ?></a> ]</p>
</div>
<?php
	}

	/**
	 * Initializes the options page by registering the Sections and Fields.
	 *
	 */
	private function register_settings_tab() {
		$files = array(
			0 => 'admin/includes/tab-settings.php',
			1 => 'admin/includes/tab-statistics.php',
			4 => 'admin/includes/tab-accesslog.php',
			2 => 'admin/includes/tab-geolocation.php',
			3 => 'admin/includes/tab-attribution.php',
			5 => 'admin/includes/tab-network.php',
		);

		require_once IP_GEO_BLOCK_PATH . $files[ $this->admin_tab ];
		IP_Geo_Block_Admin_Tab::tab_setup( $this, $this->admin_tab );
	}

	/**
	 * Function that fills the field with the desired inputs as part of the larger form.
	 * The 'id' and 'name' should match the $id given in the add_settings_field().
	 *
	 * @param array $args['value'] must be sanitized because it comes from external.
	 */
	public function callback_field( $args ) {
		if ( ! empty( $args['before'] ) )
			echo $args['before'], "\n"; // must be sanitized at caller

		// field
		$id = $name = '';
		if ( ! empty( $args['field'] ) ) {
			$id   = "${args['option']}_${args['field']}";
			$name = "${args['option']}[${args['field']}]";
		}

		// sub field
		$sub_id = $sub_name = '';
		if ( ! empty( $args['sub-field'] ) ) {
			$sub_id   = "_${args['sub-field']}";
			$sub_name = "[${args['sub-field']}]";
		}

		switch ( $args['type'] ) {
		  case 'check-provider':
			echo "\n<ul class=\"ip-geo-block-list\">\n";
			foreach ( $args['providers'] as $key => $val ) {
				$id   = "${args['option']}_providers_{$key}";
				$name = "${args['option']}[providers][$key]";
				$stat = ( NULL   === $val   && ! isset( $args['value'][ $key ] ) ) ||
				        ( FALSE  === $val   && ! empty( $args['value'][ $key ] ) ) ||
				        ( is_string( $val ) && ! empty( $args['value'][ $key ] ) ); ?>
	<li>
		<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $val; ?>"<?php checked( $stat && -1 !== (int)$val ); disabled( -1 === (int)$val ); ?> class="<?php echo in_array( $key, $args['local'], TRUE ) ? 'API-local' : 'API-remote'; ?>" />
		<label for="<?php echo $id; ?>"><?php echo '<dfn title="', esc_attr( $args['titles'][ $key ] ), '">', $key, '</dfn>'; ?></label>
<?php			if ( is_string( $val ) ) { ?>
		<input type="text" class="regular-text code" name="<?php echo $name; ?>" value="<?php echo esc_attr( isset( $args['value'][ $key ] ) ? $args['value'][ $key ] : '' ); ?>" placeholder="API key" />
<?php			} ?>
	</li>
<?php		}
			echo "</ul>\n";
			break;

		  case 'checkboxes':
			echo "\n<ul class=\"ip-geo-block-list\">\n";
			foreach ( $args['list'] as $key => $val ) { ?>
	<li>
		<input type="checkbox" id="<?php echo $id, $sub_id, '_', $key; ?>" name="<?php echo $name, $sub_name, '[', $key, ']'; ?>" value="<?php echo $key; ?>"<?php
			checked( is_array( $args['value'] ) ? ! empty( $args['value'][ $key ] ) : ( $key & $args['value'] ? TRUE : FALSE ) ); ?> /><label for="<?php
			echo $id, $sub_id, '_', $key; ?>"><?php
			if ( isset( $args['desc'][ $key ] ) )
				echo '<dfn title="', $args['desc'][ $key ], '">', $val, '</dfn>';
			else
				echo $val;
		?></label>
	</li>
<?php
			}
			echo "</ul>\n";
			break;

		  case 'checkbox': ?>
<input type="checkbox" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="1"<?php
	checked( esc_attr( $args['value'] ) );
	disabled( ! empty( $args['disabled'] ), TRUE ); ?> /><label for="<?php
	echo $id, $sub_id; ?>"><?php
	if ( isset( $args['text'] ) ) echo esc_attr( $args['text'] );
	else if ( isset( $args['html'] ) ) echo $args['html'];
	else _e( 'Enable', 'ip-geo-block' );
?></label>
<?php
			break;

		  case 'select':
		  case 'select-text':
			$desc = '';
			echo "\n<select id=\"${id}${sub_id}\" name=\"${name}${sub_name}\" ", (isset( $args['attr'] ) ? esc_attr( $args['attr'] ) : ''), ">\n";
			foreach ( $args['list'] as $key => $val ) {
				echo "\t<option value=\"$key\"", NULL === $val ? ' selected disabled' : ( is_array( $args['value'] ) ? selected( in_array( $key, $args['value'] ), TRUE, FALSE ) : selected( $args['value'], $key, FALSE ) );
				if ( isset( $args['desc'][ $key ] ) ) {
					echo ' data-desc="', $args['desc'][ $key ], '"';
					$key === $args['value'] and $desc = $args['desc'][ $key ];
				}
				echo '>', ( NULL === $val ? __( 'Select one', 'ip-geo-block' ) : $val ), '</option>', "\n";
			}
			echo "</select>\n";

			if ( isset( $args['desc'] ) )
				echo '<p class="ip-geo-block-desc">', $desc, "</p>\n";

			if ( 'select' === $args['type'] )
				break;

			echo "<br />\n";
			$sub_id   = '_' . $args['txt-field']; // possible value of 'txt-field' is 'msg'
			$sub_name = '[' . $args['txt-field'] . ']';
			$args['value']  = $args['text']; // should be escaped because it can contain allowed tags

		  case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"<?php
	disabled( ! empty( $args['disabled'] ) );
	if ( isset( $args['placeholder'] ) ) echo ' placeholder="', esc_html( $args['placeholder'] ), '"'; ?> />
<?php
			break; // disabled @since 3.0

		  case 'textarea': ?>
<textarea class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>"<?php
	disabled( ! empty( $args['disabled'] ) );
	if ( isset( $args['placeholder'] ) ) echo ' placeholder="', esc_html( $args['placeholder'] ), '"'; ?>><?php
	echo esc_html( $args['value'] ); ?></textarea>
<?php
			break;

		  case 'button': ?>
<input type="button" class="button-secondary" id="<?php echo $id; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"
<?php disabled( ! empty( $args['disabled'] ) ); ?>/>
<?php
			break;

		  case 'html':
			echo "\n", $args['value'], "\n"; // must be sanitized at caller
			break;
		}

		if ( ! empty( $args['after'] ) )
			echo $args['after'], "\n"; // must be sanitized at caller
	}

	/**
	 * Sanitize options before saving them into DB.
	 *
	 * @param array $input The values to be validated.
	 *
	 * @link https://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
	 * @link https://codex.wordpress.org/Function_Reference/sanitize_option
	 * @link https://codex.wordpress.org/Function_Reference/sanitize_text_field
	 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/sanitize_option_$option
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 */
	public function sanitize_options( $input ) {
		// setup base options
		$output = IP_Geo_Block::get_option();
		$default = IP_Geo_Block::get_default();

		// Integrate posted data into current settings because it can be a part of hole data
		$input = $this->array_replace_recursive(
			$output = $this->preprocess_options( $output, $default ), $input
		);

		// restore the 'signature' that might be transformed to avoid self blocking
		if ( isset( $input['signature'] ) && FALSE === strpos( $input['signature'], ',' ) )
			$input['signature'] = str_rot13( base64_decode( $input['signature'] ) );

		/**
		 * Sanitize a string from user input
		 */
		foreach ( $output as $key => $val ) {
			$key = sanitize_text_field( $key ); // @since 3.0.0 can't use sanitize_key() because of capital letters.

			// delete old key
			if ( ! array_key_exists( $key, $default ) ) {
				unset( $output[ $key ] );
				continue;
			}

			switch( $key ) {
			  case 'providers':
				foreach ( IP_Geo_Block_Provider::get_providers() as $provider => $api ) {
					// need no key
					if ( NULL === $api ) {
						if ( isset( $input[ $key ][ $provider ] ) )
							unset( $output[ $key ][ $provider ] );
						else
							$output['providers'][ $provider ] = '';
					}

					// non-commercial
					elseif ( FALSE === $api ) {
						if ( isset( $input[ $key ][ $provider ] ) )
							$output['providers'][ $provider ] = '@';
						else
							unset( $output[ $key ][ $provider ] );
					}

					// need key
					else {
						$output[ $key ][ $provider ] =
							isset( $input[ $key ][ $provider ] ) ? sanitize_text_field( $input[ $key ][ $provider ] ) : '';
					}
				}
				break;

			  case 'comment':
				if ( isset( $input[ $key ]['pos'] ) )
					$output[ $key ]['pos'] = (int)$input[ $key ]['pos'];

				if ( isset( $input[ $key ]['msg'] ) )
					$output[ $key ]['msg'] = IP_Geo_Block_Util::kses( $input[ $key ]['msg'] );
				break;

			  case 'white_list':
			  case 'black_list':
				$output[ $key ] = isset( $input[ $key ] ) ? preg_replace( '/[^A-Z,]/', '', strtoupper( $input[ $key ] ) ) : '';
				break;

			  case 'mimetype':
				if ( isset( $input[ $key ]['white_list'] ) ) { // for json file before 3.0.3
					foreach ( $input[ $key ]['white_list'] as $k => $v ) {
						$output[ $key ]['white_list'][ sanitize_text_field( $k ) ] = sanitize_mime_type( $v ); // @since 3.1.3
					}
				}
				if ( isset( $input[ $key ]['black_list'] ) ) { // for json file before 3.0.3
					$output[ $key ]['black_list'] = sanitize_text_field( $input[ $key ]['black_list'] );
				}
				if ( isset( $input[ $key ]['capability'] ) ) {
					$output[ $key ]['capability'] = array_map( 'sanitize_key', explode( ',', trim( $input[ $key ]['capability'], ',' ) ) ); // @since 3.0.0
				}
				break;

			  case 'metadata':
				if ( isset( $input[ $key ] ) ) {
					if ( is_string( $input[ $key ]['pre_update_option'     ] ) ) {
						$output[ $key ]['pre_update_option'     ] = array_map( 'sanitize_key', explode( ',', trim( $input[ $key ]['pre_update_option'     ], ',' ) ) ); // @since 3.0.17
					}
					if ( is_string( $input[ $key ]['pre_update_site_option'] ) ) {
						$output[ $key ]['pre_update_site_option'] = array_map( 'sanitize_key', explode( ',', trim( $input[ $key ]['pre_update_site_option'], ',' ) ) ); // @since 3.0.17
					}
				}
				break;

			  default: // checkbox, select, text
				// single field
				if ( ! is_array( $default[ $key ] ) ) {
					// for checkbox
					if ( is_bool( $default[ $key ] ) ) {
						$output[ $key ] = ! empty( $input[ $key ] );
					}

					// for implicit data
					elseif ( isset( $input[ $key ] ) ) {
						$output[ $key ] = is_int( $default[ $key ] ) ?
							(int)$input[ $key ] :
							IP_Geo_Block_Util::kses( trim( $input[ $key ] ), FALSE );
					}

					// otherwise keep as it is
					else {
					}
				}

				// sub field
				else foreach ( array_keys( (array)$val ) as $sub ) {
					// delete old key
					if ( ! array_key_exists( $sub, $default[ $key ] ) ) {
						unset( $output[ $key ][ $sub ] );
					}

					// for checkbox
					elseif ( is_bool( $default[ $key ][ $sub ] ) ) {
						$output[ $key ][ $sub ] = ! empty( $input[ $key ][ $sub ] );
					}

					// for array
					elseif ( is_array( $default[ $key ][ $sub ] ) ) {
						$output[ $key ][ $sub ] = empty( $input[ $key ][ $sub ] ) ?
							array() : $input[ $key ][ $sub ];
					}

					// for implicit data
					elseif ( isset( $input[ $key ][ $sub ] ) ) {
						// for checkboxes
						if ( is_array( $input[ $key ][ $sub ] ) ) {
							foreach ( $input[ $key ][ $sub ] as $k => $v ) {
								$output[ $key ][ $sub ] |= $v;
							}
						} else {
							$output[ $key ][ $sub ] = ( is_int( $default[ $key ][ $sub ] ) ?
								(int)$input[ $key ][ $sub ] :
								IP_Geo_Block_Util::kses( trim( $input[ $key ][ $sub ] ), FALSE )
							);
						}
					}

					// otherwise keep as it is
					else {
					}
				}
			}
		}

		// Check and format each setting data
		return $this->postprocess_options( $output, $default );
	}

	// Initialize not on the form (mainly unchecked checkbox)
	public function preprocess_options( $output, $default ) {
		// initialize checkboxes not in the form (added after 2.0.0, just in case)
		foreach ( array( 'providers', 'save_statistics', 'cache_hold', 'anonymize', 'restrict_api', 'network_wide', 'clean_uninstall', 'simulate' ) as $key ) {
			$output[ $key ] = is_array( $default[ $key ] ) ? array() : 0;
		}

		// initialize checkboxes not in the form
		foreach ( array( 'comment', 'login', 'admin', 'ajax', 'plugins', 'themes', 'public', 'mimetype' ) as $key ) {
			$output['validation'][ $key ] = 0;
		}

		// initialize checkboxes not in the form
		foreach ( array( 'plugins', 'themes', 'includes', 'uploads', 'languages' ) as $key ) {
			$output['rewrite'][ $key ] = FALSE;
		}

		// initialize checkboxes not in the form
		$output['mimetype']['white_list'] = array();

		// keep disabled checkboxes not in the form
		foreach ( array( 'admin', 'plugins', 'themes' ) as $key ) {
			$output['exception'][ $key ] = array();
		}

		// keep disabled checkboxes not in the form
		foreach ( array( 'target_pages', 'target_posts', 'target_cates', 'target_tags', 'dnslkup', 'behavior' ) as $key ) {
			$output['public'][ $key ] = is_array( $default['public'][ $key ] ) ?  array() : FALSE;
		}

		// disabled in case IP address cache is disabled
		empty( $output['cache_hold'] ) and $output['login_fails'] = -1;

		// 3.0.4 AS number, 3.0.6 Auto updating of DB files, 3.0.8 Geolite2
		$output['Maxmind']['use_asn'] = $output['Geolite2']['use_asn'] = $output['update']['auto'] = FALSE;

		// 3.0.5 Live update
		$output['live_update']['in_memory'] = 0;

		// 3.0.9 Fix for `login_action`
		foreach ( array( 'login', 'register', 'resetpass', 'lostpassword', 'postpass' ) as $key ) {
			$output['login_action'][ $key ] = FALSE;
		}

		return $output;
	}

	// Check and format each setting data
	private function postprocess_options( $output, $default ) {
		// normalize escaped char
		$output           ['response_msg'] = preg_replace( '/\\\\/', '', $output           ['response_msg'] );
		$output['public' ]['response_msg'] = preg_replace( '/\\\\/', '', $output['public' ]['response_msg'] );
		$output['comment']['msg'         ] = preg_replace( '/\\\\/', '', $output['comment']['msg'         ] );

		// sanitize proxy
		$output['validation']['proxy'] = implode( ',', $this->trim(
			preg_replace( '/[^\w,]/', '', strtoupper( $output['validation']['proxy'] ) )
		) );

		// sanitize and format ip address (text area)
		$key = array( '/[^\w\n\.\/,:]/', '/([\s,])+/', '/(?:^,|,$)/' );
		$val = array( '',                '$1',         ''            );
		$output['extra_ips']['white_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['white_list'] ) );
		$output['extra_ips']['black_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['black_list'] ) );

		// format and reject invalid words which potentially blocks itself (text area)
		array_shift( $key );
		array_shift( $val );
		$output['signature'] = preg_replace( $key, $val, trim( $output['signature'] ) );
		$output['signature'] = implode     ( ',', $this->trim( $output['signature'] ) );

		// 3.0.3 trim extra space and comma
		$output['mimetype']['black_list'] = preg_replace( $key, $val, trim( $output['mimetype']['black_list'] ) );
		$output['mimetype']['black_list'] = implode     ( ',', $this->trim( $output['mimetype']['black_list'] ) );

		// 3.0.0 convert country code to upper case, remove redundant spaces
		$output['public']['ua_list'] = preg_replace( $key, $val, trim( $output['public']['ua_list'] ) );
		$output['public']['ua_list'] = preg_replace( '/([:#]) *([!]+) *([^ ]+) *([,\n]+)/', '$1$2$3$4', $output['public']['ua_list'] );
		$output['public']['ua_list'] = preg_replace_callback( '/[:#]([\w:]+)/', array( $this, 'strtoupper' ), $output['public']['ua_list'] );

		// 3.0.0 public : convert country code to upper case
		foreach ( array( 'white_list', 'black_list' ) as $key ) {
			$output['public'   ][ $key ] = strtoupper( preg_replace( '/\s/', '', $output['public'][ $key ] ) );
			// 3.0.4 extra_ips : convert AS number to upper case
			$output['extra_ips'][ $key ] = strtoupper( $output['extra_ips'][ $key ] );
		}

		// 2.2.5 exception : convert associative array to simple array
		foreach ( array( 'plugins', 'themes' ) as $key ) {
			$output['exception'][ $key ] = array_keys( $output['exception'][ $key ] );
		}

		// 3.0.0 - 3.0.3 exception : trim extra space and comma
		foreach ( array( 'admin', 'public', 'includes', 'uploads', 'languages', 'restapi' ) as $key ) {
			if ( empty( $output['exception'][ $key ] ) ) {
				$output['exception'][ $key ] = $default['exception'][ $key ];
			} else {
				$output['exception'][ $key ] = (  is_array( $output['exception'][ $key ] ) ?
				$output['exception'][ $key ] : $this->trim( $output['exception'][ $key ] ) );
			}
		}

		// 3.0.4 AS number, 3.0.8 Geolite2
		if ( version_compare( PHP_VERSION, '5.4' ) >= 0 )
			$output['Geolite2']['use_asn'] = $output['Maxmind']['use_asn'];

		// force to update asn file not immediately but after `validate_settings()` and `validate_network_settings()`
		if ( $output['Maxmind']['use_asn'] && (
		     ( ! $output['Maxmind']['asn4_path'] && class_exists( 'IP_Geo_Block_API_Maxmind',  FALSE ) ) ||
		     ( ! $output['Geolite2']['asn_path'] && class_exists( 'IP_Geo_Block_API_Geolite2', FALSE ) ) ) ) {
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
			add_action( IP_Geo_Block::PLUGIN_NAME . '-settings-updated', array( 'IP_Geo_Block_Cron', 'start_update_db' ), 10, 2 );
		}

		// reset path if asn file does not exist
		else {
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
			$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

			if ( ! $output['Maxmind']['use_asn'] && ! $fs->exists( $output['Maxmind']['asn4_path'] ) ) {
				$output['Maxmind']['asn4_path'] = NULL;
				$output['Maxmind']['asn6_path'] = NULL;
			}
			if ( ! $output['Geolite2']['use_asn'] && ! $fs->exists( $output['Geolite2']['asn_path'] ) ) {
				$output['Geolite2']['asn_path'] = NULL;
			}
		}

		// cron event
		$key = wp_next_scheduled( IP_Geo_Block::CRON_NAME, array( FALSE ) );
		if ( $output['update']['auto'] && ! $key ) {
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
			IP_Geo_Block_Cron::start_update_db( $output, FALSE );
		} else if ( ! $output['update']['auto'] && $key ){
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
			IP_Geo_Block_Cron::stop_update_db();
		}

		// expiration time [days]
		if ( version_compare( $output['version'], '3.0.13' ) < 0 )
			$output['validation']['explogs'] /= DAY_IN_SECONDS;
		$output['validation']['explogs'] = min( 365, max( 1, (int)$output['validation']['explogs'] ) );

		return $output;
	}

	/**
	 * A fallback function of array_replace_recursive() before PHP 5.3.
	 *
	 * @link https://php.net/manual/en/function.array-replace-recursive.php#92574
	 * @link https://php.net/manual/en/function.array-replace-recursive.php#109390
	 */
	public function array_replace_recursive() {
		if ( function_exists( 'array_replace_recursive' ) ) {
			return call_user_func_array( 'array_replace_recursive', func_get_args() );
		}

		else {
			foreach ( array_slice( func_get_args(), 1 ) as $replacements ) {
				$bref_stack = array( &$base );
				$head_stack = array( $replacements );

				do {
					end( $bref_stack );

					$bref = &$bref_stack[ key( $bref_stack ) ];
					$head = array_pop( $head_stack );

					unset( $bref_stack[ key( $bref_stack ) ] );

					foreach ( array_keys( $head ) as $key ) {
						if ( isset( $key, $bref, $bref[ $key ], $head[ $key ] ) && is_array( $bref[ $key ] ) && is_array( $head[ $key ] ) ) {
							$bref_stack[] = &$bref[ $key ];
							$head_stack[] = $head [ $key ];
						} else {
							$bref[ $key ] = $head [ $key ];
						}
					}
				} while( count( $head_stack ) );
			}

			return $base;
		}
	}

	// Callback for preg_replace_callback()
	public function strtoupper( $matches ) {
		return filter_var( $matches[1], FILTER_VALIDATE_IP ) ? $matches[0] : strtoupper( $matches[0] );
	}

	// Trim extra space and comma avoiding invalid signature which potentially blocks itself
	private function trim( $text ) {
		$path = IP_Geo_Block::get_wp_path();

		$ret = array();
		foreach ( explode( ',', $text ) as $val ) {
			$val = trim( $val );
			if ( $val && FALSE === stripos( $path['admin'], $val ) ) {
				$ret[] = $val;
			}
		}

		return $ret;
	}

	/**
	 * Check admin post
	 *
	 */
	private function check_admin_post( $ajax = FALSE ) {
		if ( $ajax )
			$nonce = IP_Geo_Block_Util::verify_nonce( IP_Geo_Block_Util::retrieve_nonce( 'nonce' ), $this->get_ajax_action() );
		else
			$nonce = check_admin_referer( IP_Geo_Block::PLUGIN_NAME . '-options' ); // a postfix '-options' is added at settings_fields().

		$settings = IP_Geo_Block::get_option();
		if ( (   $ajax and $settings['validation']['ajax' ] & 2 ) ||
		     ( ! $ajax and $settings['validation']['admin'] & 2 ) ) {
			$action = IP_Geo_Block::get_auth_key();
			$nonce &= IP_Geo_Block_Util::verify_nonce( IP_Geo_Block_Util::retrieve_nonce( $action ), $action );
		}

		if ( ! $nonce || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_network_options' ) ) ) {
			status_header( 403 );
			wp_die(
				__( 'You do not have sufficient permissions to access this page.' ), '',
				array( 'response' => 403, 'back_link' => TRUE )
			);
		}
	}

	/**
	 * Validate settings and configure some features.
	 *
	 * @note: This function is triggered when update_option() is executed.
	 */
	public function validate_settings( $input = array() ) {
		// must check that the user has the required capability
		$this->check_admin_post( FALSE );

		// validate setting options
		$options = $this->sanitize_options( $input );

		// activate rewrite rules
		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php';
		$options['rewrite'] = IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $options['rewrite'] );

		// additional configuration
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
		if ( TRUE !== ( $file = IP_Geo_Block_Opts::setup_validation_timing( $options ) ) ) {
			$options['validation']['timing'] = 0;
			self::add_admin_notice( 'error', sprintf(
				__( 'Unable to write <code>%s</code>. Please check the permission.', 'ip-geo-block' ), '<code>' . $file . '</code>'
			) );
		}

		// Force to finish update matching rule
		delete_transient( IP_Geo_Block::CRON_NAME );

		// start to update databases immediately
		do_action( IP_Geo_Block::PLUGIN_NAME . '-settings-updated', $options, TRUE );

		return $options;
	}

	/**
	 * Validate settings and configure some features for network multisite.
	 *
	 * @see https://vedovini.net/2015/10/using-the-wordpress-settings-api-with-network-admin-pages/
	 */
	public function validate_network_settings() {
		// Must check that the user has the required capability
		$this->check_admin_post( FALSE );

		// The list of registered options (IP_Geo_Block::OPTION_NAME).
		global $new_whitelist_options;
		$options = $new_whitelist_options[ IP_Geo_Block::PLUGIN_NAME ];

		// Go through the posted data and save the targetted options.
		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				$this->update_multisite_settings( $_POST[ $option ] );
			}
		}

		// Register a settings error to be displayed to the user
		self::add_admin_notice( 'updated', __( 'Settings saved.' ) );

		// Redirect in order to back to the settings page.
		wp_redirect( esc_url_raw(
			add_query_arg(
				array( 'page' => IP_Geo_Block::PLUGIN_NAME ),
				$this->dashboard_url( ! empty( $_POST[ $option ]['network_wide'] ) )
			)
		) );

		exit;
	}

	/**
	 * Update option in all blogs.
	 *
	 * @note: This function triggers `validate_settings()` on register_setting() in wp-include/option.php.
	 */
	public function update_multisite_settings( $settings ) {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" );
		$ret = TRUE;

		foreach ( $blog_ids as $id ) {
			switch_to_blog( $id );
			$map = IP_Geo_Block::get_option( FALSE );
			$settings['api_key']['GoogleMap'] = $map['api_key']['GoogleMap'];
			$ret &= IP_Geo_Block::update_option( $settings, FALSE );
			restore_current_blog();
		}

		return $ret;
	}

	/**
	 * Analyze entries in "Validation logs"
	 *
	 * @param array $logs An array including each entry where:
	 * Array (
	 *     [0 DB row number] => 154
	 *     [1 Target       ] => comment
	 *     [2 Time         ] => 1534580897
	 *     [3 IP address   ] => 102.177.147.***
	 *     [4 Country code ] => ZA
	 *     [5 Result       ] => blocked
	 *     [6 AS number    ] => AS328239
	 *     [7 Request      ] => POST[80]:/wp-comments-post.php
	 *     [8 User agent   ] => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) ...
	 *     [9 HTTP headers ] => HTTP_ORIGIN=http://localhost,HTTP_X_FORWARDED_FOR=102.177.147.***
	 *    [10 $_POST data  ] => comment=Hello.,author,email,url,comment_post_ID,comment_parent
	 * )
	 * And put a mark at "Target"
	 *    ¹¹: Passed  in Whitelist
	 *    ¹²: Passed  in Blacklist
	 *    ¹³: Passed  not in list
	 *    ²¹: Blocked in Whitelist
	 *    ²²: Blocked in Blacklist
	 *    ²³: Blocked not in list
	 */
	public function filter_logs( $logs ) {
		$settings = IP_Geo_Block::get_option();

		// White/Black list for back-end
		$white_backend = $settings['white_list'];
		$black_backend = $settings['black_list'];

		// White/Black list for front-end
		if ( $settings['public']['matching_rule'] < 0 ) {
			// Follow "Validation rule settings"
			$white_frontend = $white_backend;
			$black_frontend = $black_backend;
		} else {
			// Whitelist or Blacklist for "Public facing pages"
			$white_frontend = $settings['public']['white_list'];
			$black_frontend = $settings['public']['black_list'];
		}

		foreach ( $logs as $key => $log ) {
			// Passed or Blocked
			$mark = IP_Geo_Block::is_passed( $log[5] ) ? '&sup1;' : '&sup2;';

			// Whitelisted, Blacklisted or N/A
			if ( 'public' === $log[1] ) {
				$mark .= IP_Geo_Block::is_listed( $log[4], $white_frontend ) ? '&sup1;' : (
				         IP_Geo_Block::is_listed( $log[4], $black_frontend ) ? '&sup2;' : '&sup3;' );
			} else {
				$mark .= IP_Geo_Block::is_listed( $log[4], $white_backend  ) ? '&sup1;' : (
				         IP_Geo_Block::is_listed( $log[4], $black_backend  ) ? '&sup2;' : '&sup3;' );
			}

			// Put a mark at "Target"
			$logs[ $key ][1] .= $mark;
		}

		return $logs;
	}

	/**
	 * Register UI "Preset filters" at "Search in logs"
	 *
	 * @param  array $filters An empty array by default.
	 * @return array $filters The array of paired with 'title' and 'value'.
	 */
	public function preset_filters( $filters = array() ) {
		return array(
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-happy"    >&nbsp;</span>' . __( '<span title="Show only passed entries whose country codes are in Whitelist.">Passed in Whitelist</span>',        'ip-geo-block' ), 'value' => '&sup1;&sup1;' ),
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-grin2"    >&nbsp;</span>' . __( '<span title="Show only passed entries whose country codes are in Blacklist.">Passed in Blacklist</span>',        'ip-geo-block' ), 'value' => '&sup1;&sup2;' ),
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-cool"     >&nbsp;</span>' . __( '<span title="Show only passed entries whose country codes are not in either list.">Passed not in List</span>',   'ip-geo-block' ), 'value' => '&sup1;&sup3;' ),
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-confused" >&nbsp;</span>' . __( '<span title="Show only blocked entries whose country codes are in Whitelist.">Blocked in Whitelist</span>',      'ip-geo-block' ), 'value' => '&sup2;&sup1;' ),
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-confused2">&nbsp;</span>' . __( '<span title="Show only blocked entries whose country codes are in Blacklist.">Blocked in Blacklist</span>',      'ip-geo-block' ), 'value' => '&sup2;&sup2;' ),
			array( 'title' => '<span class="ip-geo-block-icon ip-geo-block-icon-crying"   >&nbsp;</span>' . __( '<span title="Show only blocked entries whose country codes are not in either list.">Blocked not in List</span>', 'ip-geo-block' ), 'value' => '&sup2;&sup3;' ),
		);
	}

	/**
	 * Ajax callback function
	 *
	 * @link https://codex.wordpress.org/AJAX_in_Plugins
	 * @link https://codex.wordpress.org/Function_Reference/check_ajax_referer
	 * @link https://core.trac.wordpress.org/browser/trunk/wp-admin/admin-ajax.php
	 */
	public function admin_ajax_callback() {
		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php';

		// Check request origin, nonce, capability.
		$this->check_admin_post( TRUE );

		// `$which` and `$cmd` should be restricted by whitelist in each function
		$settings = IP_Geo_Block::get_option();
		$which = isset( $_POST['which'] ) ? $_POST['which'] : NULL;
		$cmd   = isset( $_POST['cmd'  ] ) ? $_POST['cmd'  ] : NULL;

		switch ( $cmd ) {
		  case 'download':
			$res = IP_Geo_Block::get_instance();
			$res = $res->exec_update_db();
			break;

		  case 'search': // Get geolocation by IP
			$res = array();
			foreach ( (array)$which as $cmd ) {
				$res[ $cmd ] = IP_Geo_Block_Admin_Ajax::search_ip( $cmd );
			}
			break;

		  case 'scan-code': // Fetch providers to get country code
			$res = IP_Geo_Block_Admin_Ajax::scan_country( $which );
			break;

		  case 'clear-statistics': // Set default values
			IP_Geo_Block_Logs::clear_stat();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-cache': // Delete cache of IP address
			IP_Geo_Block_API_Cache::clear_cache();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-logs': // Delete logs in MySQL DB
			IP_Geo_Block_Logs::clear_logs( $which );
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=4'
			);
			break;

		  case 'export-logs':// Export logs from MySQL DB
			IP_Geo_Block_Admin_Ajax::export_logs( $which );
			break;

		  case 'restore-logs': // Get logs from MySQL DB
			has_filter( $cmd = IP_Geo_Block::PLUGIN_NAME . '-logs' ) or add_filter( $cmd, array( $this, 'filter_logs' ) );
			$res = IP_Geo_Block_Admin_Ajax::restore_logs( $which );
			break;

		  case 'live-start': // Restore live log
			has_filter( $cmd = IP_Geo_Block::PLUGIN_NAME . '-logs' ) or add_filter( $cmd, array( $this, 'filter_logs' ) );
			if ( is_wp_error( $res = IP_Geo_Block_Admin_Ajax::restore_live_log( $which, $settings ) ) )
				$res = array( 'error' => $res->get_error_message() );
			break;

		  case 'live-pause': // Pause live log
			if ( ! is_wp_error( $res = IP_Geo_Block_Admin_Ajax::catch_live_log() ) )
				$res = array( 'data' => array() );
			else
				$res = array( 'error' => $res->get_error_message() );
			break;

		  case 'live-stop': // Stop live log
			if ( ! is_wp_error( $res = IP_Geo_Block_Admin_Ajax::release_live_log() ) )
				$res = array( 'data' => array() );
			else
				$res = array( 'error' => $res->get_error_message() );
			break;

		  case 'reset-live': // Reset data source of live log
			$res = IP_Geo_Block_Admin_Ajax::reset_live_log();
			break;

		  case 'validate': // Validate settings
			IP_Geo_Block_Admin_Ajax::validate_settings( $this );
			break;

		  case 'import-default': // Import initial settings
			$res = IP_Geo_Block_Admin_Ajax::settings_to_json( IP_Geo_Block::get_default() );
			break;

		  case 'import-preferred': // Import preference
			$res = IP_Geo_Block_Admin_Ajax::preferred_to_json();
			break;

		  case 'gmap-error': // Reset Google Maps API key
			if ( $settings['api_key']['GoogleMap'] === 'default' ) {
				$settings['api_key']['GoogleMap'] = NULL;
				IP_Geo_Block::update_option( $settings );
				$res = array(
					'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
					'tab' => 'tab=2'
				);
			}
			break;

		  case 'generate-link': // Generate new link
			$res = array( 'link' => IP_Geo_Block_Util::generate_link( $this ) );
			break;

		  case 'delete-link': // Delete existing link
			IP_Geo_Block_Util::delete_link( $this );
			break;

		  case 'show-info': // Show system and debug information
			$res = IP_Geo_Block_Admin_Ajax::get_wp_info();
			break;

		  case 'get-actions': // Get all the ajax/post actions
			$res = IP_Geo_Block_Util::get_registered_actions( TRUE );
			break;

		  case 'export-cache': // Restore cache from database and format for DataTables
			IP_Geo_Block_Admin_Ajax::export_cache( $settings['anonymize'] );
			break;

		  case 'restore-cache': // Restore cache from database and format for DataTables
			$res = IP_Geo_Block_Admin_Ajax::restore_cache( $settings['anonymize'] );
			break;

		  case 'bulk-action-remove': // Delete specified IP addresses from cache
			$res = IP_Geo_Block_Logs::delete_cache_entry( $which['IP'] );
			break;

		  case 'bulk-action-ip-erase':
			$res = IP_Geo_Block_Logs::delete_logs_entry( $which['IP'] );
			break;

		  case 'bulk-action-ip-white':
		  case 'bulk-action-ip-black':
		  case 'bulk-action-as-white':
		  case 'bulk-action-as-black':
			// Bulk actions for registration of settings
			$src = ( FALSE !== strpos( $cmd, '-ip-'   ) ? 'IP'         : 'AS'         );
			$dst = ( FALSE !== strpos( $cmd, '-white' ) ? 'white_list' : 'black_list' );

			if ( empty( $which[ $src ] ) ) {
				$res = array( 'error' => sprintf( __( 'An error occurred while executing the ajax command `%s`.', 'ip-geo-block' ), $cmd ) );
				break;
			}

			foreach ( array_unique( (array)$which[ $src ] ) as $val ) {
				// replace anonymized IP address with CIDR (IPv4:256, IPv6:4096)
				$val = preg_replace(
					array( '/\.\*\*\*$/', '/:\w*\*\*\*$/', '/(::.*)::\/116$/' ),
					array( '.0/24',       '::/116',        '$1/116'           ),
					trim( $val )
				);
				if ( ( filter_var( preg_replace( '/\/\d+$/', '', $val ), FILTER_VALIDATE_IP ) || preg_match( '/^AS\d+$/', $val ) ) &&
				     ( FALSE === strpos( $settings['extra_ips'][ $dst ], $val ) ) ) {
					$settings['extra_ips'][ $dst ] .= "\n" . $val;
				}
			}

			if ( $this->is_network_admin && $settings['network_wide'] )
				$this->update_multisite_settings( $settings );
			else
				IP_Geo_Block::update_option( $settings );

			$res = array( 'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME );
			break;

		  case 'restore-network': // Restore blocked per target in logs
			$res = IP_Geo_Block_Admin_Ajax::restore_network( $which, (int)$_POST['offset'], (int)$_POST['length'], FALSE );
			break;

		  case 'find-admin':
		  case 'find-plugins':
		  case 'find-themes':
			// Get slug in blocked requests for exceptions
			$res = IP_Geo_Block_Admin_Ajax::find_exceptions( $cmd );
			break;

		  case 'diag-tables': // Check database tables
			IP_Geo_Block_Logs::diag_tables() or IP_Geo_Block_Logs::create_tables();
			$res = array( 'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME );
			break;
		}

		if ( isset( $res ) ) // wp_send_json_{success,error}() @since 3.5.0
			wp_send_json( $res ); // @since 3.5.0

		die(); // End of ajax
	}

}