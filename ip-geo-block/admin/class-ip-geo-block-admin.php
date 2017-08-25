<?php
/**
 * IP Geo Block - Admin class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2017 tokkonopapa
 */

class IP_Geo_Block_Admin {

	/**
	 * Globals in this class
	 *
	 */
	private static $instance = NULL;
	private $admin_tab = 0;
	private $is_network = NULL;

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {
		// Load plugin text domain and add body class
		add_action( 'init', array( $this, 'admin_init' ) );

		// Setup a nonce to validate authentication.
		add_filter( 'wp_redirect', array( $this, 'add_redirect_nonce' ), 10, 2 );
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
		require_once ABSPATH . 'wp-admin/includes/plugin.php'; // is_plugin_active_for_network() @since 3.0.0

		// Add the options page and menu item.
		add_action( 'admin_menu',                 array( $this, 'setup_admin_page'    ) );
		add_action( 'admin_post_ip_geo_block',    array( $this, 'admin_ajax_callback' ) );
		add_action( 'wp_ajax_ip_geo_block',       array( $this, 'admin_ajax_callback' ) );
		add_filter( 'wp_prepare_revision_for_js', array( $this, 'add_revision_nonce'  ), 10, 3 );

		// If multisite, then enque the authentication script for network admin
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'setup_admin_page' ) );

			// when a blog is created or deleted.
			add_action( 'wpmu_new_blog', array( $this, 'create_blog' ), 10, 6 ); // @since MU
			add_action( 'delete_blog',   array( $this, 'delete_blog' ), 10, 2 ); // @since 3.0.0

			// validate capability instead of nonce. @since 2.0.0 && 3.0.0
//			$this->is_network = current_user_can( 'manage_network_options' ) && is_plugin_active_for_network( IP_GEO_BLOCK_BASE );
//			add_filter( IP_Geo_Block::PLUGIN_NAME . '-bypass-admins', array( $this, 'verify_capability' ) );
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
		return IP_Geo_Block_Util::rebuild_nonce( $location, $status );
	}

	/**
	 * Add nonce to revision @since 4.4.0
	 *
	 */
	public function add_revision_nonce( $revisions_data, $revision, $post ) {
		$revisions_data['restoreUrl'] = add_query_arg(
			$nonce = IP_Geo_Block::PLUGIN_NAME . '-auth-nonce',
			IP_Geo_Block_Util::create_nonce( $nonce ),
			$revisions_data['restoreUrl']
		);

		return $revisions_data;
	}

	/**
	 * Validate capability instead of nonce.
	 *
	 */
	public function verify_capability( $queries ) {
		if ( isset( $_GET['page'] ) && $_GET['page'] === IP_Geo_Block::PLUGIN_NAME ) {
			if ( $this->is_network ) {
				$queries[] = IP_Geo_Block::PLUGIN_NAME;
			}
		}

		return $queries;
	}

	/**
	 * Do some procedures when a blog is created or deleted.
	 *
	 */
	public function create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		defined( 'IP_GEO_BLOCK_DEBUG' ) and IP_GEO_BLOCK_DEBUG and assert( 'is_main_site()', 'Not main blog.' );

		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php';

		// Get option of main blog.
		$settings = IP_Geo_Block::get_option();

		// Switch to the new blog and initialize.
		switch_to_blog( $blog_id );
		IP_Geo_Block_Activate::activate_blog();

		// Copy option from main blog.
		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) && $settings['network_wide'] )
			update_option( IP_Geo_Block::OPTION_NAME, $settings );

		// Restore the main blog.
		restore_current_blog();
	}

	public function delete_blog( $blog_id, $drop ) {
		if ( $drop )
			IP_Geo_Block_Logs::delete_tables(); // blog is already switched to the target in wpmu_delete_blog()
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
	 */
	public function enqueue_admin_assets() {
		$footer = TRUE;
		$dependency = array( 'jquery' );

		// css for option page
		wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-admin-styles',
			plugins_url( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
				'css/admin.min.css' : 'css/admin.css', __FILE__
			),
			array(), IP_Geo_Block::VERSION
		);

		switch ( $this->admin_tab ) {
		  case 1:
		  case 5:
			// js for google chart
			wp_register_script(
				$addon = IP_Geo_Block::PLUGIN_NAME . '-google-chart',
				'https://www.google.com/jsapi', array(), NULL, $footer
			);
			wp_enqueue_script( $addon );
			break;

		  case 2:
			// js for google map
			$settings = IP_Geo_Block::get_option();
			if ( $key = $settings['api_key']['GoogleMap'] ) {
				wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-gmap-js',
					plugins_url( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
						'js/gmap.min.js' : 'js/gmap.js', __FILE__
					),
					$dependency, IP_Geo_Block::VERSION, $footer
				);
				wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-google-map',
					'//maps.googleapis.com/maps/api/js' . ( 'default' !== $key ? "?key=$key" : '' ),
					$dependency, IP_Geo_Block::VERSION, $footer
				);
			}
			wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-whois-js',
				plugins_url( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
					'js/whois.min.js' : 'js/whois.js', __FILE__
				),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			break;

		  case 4:
			// footable https://github.com/bradvin/FooTable
			wp_enqueue_style( IP_Geo_Block::PLUGIN_NAME . '-footable-css',
				plugins_url( 'css/footable.core.min.css', __FILE__ ),
				array(), IP_Geo_Block::VERSION
			);
			wp_enqueue_script( IP_Geo_Block::PLUGIN_NAME . '-footable-js',
				plugins_url( 'js/footable.min.js', __FILE__ ),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			break;
		}

		// js for IP Geo Block admin page
		wp_register_script(
			$handle = IP_Geo_Block::PLUGIN_NAME . '-admin-script',
			plugins_url( ! defined( 'IP_GEO_BLOCK_DEBUG' ) || ! IP_GEO_BLOCK_DEBUG ?
				'js/admin.min.js' : 'js/admin.js', __FILE__
			),
			$dependency + ( isset( $addon ) ? array( $addon ) : array() ),
			IP_Geo_Block::VERSION,
			$footer
		);
		wp_localize_script( $handle,
			'IP_GEO_BLOCK',
			array(
				'action' => 'ip_geo_block',
				'tab' => $this->admin_tab,
				'url' => admin_url( 'admin-ajax.php' ),
				'nonce' => IP_Geo_Block_Util::create_nonce( $this->get_ajax_action() ),
				'msg' => array(
					__( 'Import settings ?',           'ip-geo-block' ),
					__( 'Create table ?',              'ip-geo-block' ),
					__( 'Delete table ?',              'ip-geo-block' ),
					__( 'Clear statistics ?',          'ip-geo-block' ),
					__( 'Clear cache ?',               'ip-geo-block' ),
					__( 'Clear logs ?',                'ip-geo-block' ),
					__( 'ajax for logged-in user',     'ip-geo-block' ),
					__( 'ajax for non logged-in user', 'ip-geo-block' ),
					__( 'This feature is available with HTML5 compliant browsers.', 'ip-geo-block' ),
				),
			)
		);
		wp_enqueue_script( $handle );
	}

	/**
	 * Add plugin meta links
	 *
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( $file === IP_GEO_BLOCK_BASE ) {
			$title = __( 'Contribute at GitHub', 'ip-geo-block' );
			array_push(
				$links,
				"<a href=\"http://www.ipgeoblock.com\" title=\"$title\" target=_blank>$title</a>"
			);
		}

		return $links;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 */
	public function add_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . esc_url( admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME ) ) . '">' . __( 'Settings' ) . '</a>'
			),
			$links
		);
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
		}

		// delete all admin noties
		delete_transient( $key );
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
	private function dashboard_url( $network ) {
		return $network ? network_admin_url( 'admin.php' /*'settings.php'*/ ) : admin_url( 'options-general.php' );
	}

	/**
	 * Register the administration menu into the WordPress Dashboard menu.
	 *
	 */
	private function add_plugin_admin_menu() {
		$settings = IP_Geo_Block::get_option();

		// Network wide or not
		$admin_menu = 'admin_menu' === current_filter();
		$is_network = $this->is_network && $settings['network_wide'];

		// Setup the tab number.
		$this->admin_tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$this->admin_tab = min( 5, max( 0, $this->admin_tab ) );

		if ( $is_network ) {
			if ( $admin_menu ) {
				$this->admin_tab = max( $this->admin_tab, 1 );
			} elseif ( ! in_array( $this->admin_tab, array( 0, 3, 5 ), TRUE ) ) {
				$this->admin_tab = 0;
			}
		} else {
			$this->admin_tab = min( 4, $this->admin_tab ); // exclude `Sites` in multisite.
		}

		if ( $admin_menu ) {
			// `settings-updated` would be added when `network_wide` is saved as TRUE
			if ( $is_network && isset( $_REQUEST['settings-updated'] ) ) {
				$this->sync_multisite_option( $settings );
				wp_safe_redirect(
					esc_url_raw( add_query_arg(
						array( 'page' => IP_Geo_Block::PLUGIN_NAME ),
						$this->dashboard_url( TRUE )
					) )
				);
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

		elseif ( $is_network ) {
			// Add a settings page for this plugin to the Settings menu.
			$hook = add_menu_page(
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'IP Geo Block', 'ip-geo-block' ),
				'manage_network_options',
				IP_Geo_Block::PLUGIN_NAME,
				array( $this, 'display_plugin_admin_page' ),
				plugins_url( 'img/icon-72x72.png', __FILE__ )
			);
			/*$hook = add_submenu_page(
				'settings.php',
				__( 'IP Geo Block', 'ip-geo-block' ),
				__( 'IP Geo Block', 'ip-geo-block' ),
				'manage_network_options',
				IP_Geo_Block::PLUGIN_NAME,
				array( $this, 'display_plugin_admin_page' )
			);*/
		}

		// If successful, load admin assets only on this page.
		if ( ! empty( $hook ) )
			add_action( "load-$hook", array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Diagnosis of admin settings.
	 *
	 */
	private function diagnose_admin_screen() {
		$settings = IP_Geo_Block::get_option();
		$adminurl = $this->dashboard_url( $settings['network_wide'] );

		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.7.0' ) < 0 )
			self::add_admin_notice( 'error', __( 'You need WordPress 3.7+.', 'ip-geo-block' ) );

		// Check consistency of matching rule
		if ( -1 === (int)$settings['matching_rule'] ) {
			if ( FALSE !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
				self::add_admin_notice( 'notice-warning', sprintf(
					__( 'Now downloading geolocation databases in background. After a little while, please check your country code and &#8220;<strong>Matching rule</strong>&#8221; at <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
					esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $adminurl ) )
				) );
			}
			else {
				self::add_admin_notice( 'error', sprintf(
					__( 'The &#8220;<strong>Matching rule</strong>&#8221; is not set properly. Please confirm it at <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
					esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $adminurl ) )
				) );
			}
		}

		// Check to finish updating matching rule
		elseif ( 'done' === get_transient( IP_Geo_Block::CRON_NAME ) ) {
			delete_transient( IP_Geo_Block::CRON_NAME );
			self::add_admin_notice( 'updated ', __( 'Local database and matching rule have been updated.', 'ip-geo-block' ) );
		}

		// Check self blocking
		if ( 1 === (int)$settings['validation']['login'] ) {
			$instance = IP_Geo_Block::get_instance();
			$validate = $instance->validate_ip( 'login', $settings, TRUE, FALSE, FALSE ); // skip authentication check

			switch( $validate['result'] ) {
			  case 'limited':
				self::add_admin_notice( 'error',
					__( 'Once you logout, you will be unable to login again because the number of login attempts reaches the limit.', 'ip-geo-block' ) . ' ' .
					sprintf(
						__( 'Please execute "<strong>Clear cache</strong>" on <a href="%s">Statistics tab</a> to prevent locking yourself out.', 'ip-geo-block' ),
						esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1 ), $adminurl ) )
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
					sprintf(
						__( 'Please check your <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
						esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $adminurl ) ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-0'
					)
				);
				break;
			}
		}

		// Check activation of IP Geo Allow
		if ( $settings['validation']['timing'] && is_plugin_active( 'ip-geo-allow/index.php' ) ) {
			self::add_admin_notice( 'error',
				__( '&#8220;mu-plugins&#8221; (ip-geo-block-mu.php) at &#8220;Validation timing&#8221; is imcompatible with <strong>IP Geo Allow</strong>. Please select &#8220;init&#8221; action hook.', 'ip-geo-block' )
			);
		}

		if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			// Check creation of database table
			if ( $settings['validation']['reclogs'] ) {
				if ( ( $warn = IP_Geo_Block_Logs::diag_tables() ) &&
				     ( FALSE === IP_Geo_Block_Logs::create_tables() ) ) {
					self::add_admin_notice( 'notice-warning', $warn );
				}
			}
		}
	}

	/**
	 * Setup menu and option page for this plugin
	 *
	 */
	public function setup_admin_page() {
		// Register the administration menu.
		$this->add_plugin_admin_menu();

		// Avoid multiple validation.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] )
			$this->diagnose_admin_screen();

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
		add_action( 'admin_enqueue_scripts', 'IP_Geo_Block::enqueue_nonce', 0 );

		// Show admin notices at the place where it should be. @since 2.5.0
		add_action( 'admin_notices',         array( $this, 'show_admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Get cookie that indicates open/close section
	 *
	 */
	public function get_cookie( $name ) {
		$cookie = array();
		if ( ! empty( $_COOKIE[ $name ] ) ) {
			foreach ( explode( '&', $_COOKIE[ $name ] ) as $i => $v ) {
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

		if ( isset( $wp_settings_sections[ $page ] ) ) {
			$index  = 0; // index of fieldset
			$cookie = $this->get_cookie( IP_Geo_Block::PLUGIN_NAME );

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				// TRUE if open ('o') or FALSE if close ('x')
				$stat = empty( $cookie[ $tab ][ $index ] ) || 'x' !== $cookie[ $tab ][ $index ];

				echo '<fieldset id="', IP_Geo_Block::PLUGIN_NAME, '-section-', $index, '" class="', IP_Geo_Block::PLUGIN_NAME, '-field panel panel-default" data-section="', $index, '">', "\n",
				     '<legend class="panel-heading"><h3 class="', IP_Geo_Block::PLUGIN_NAME, ( $stat ? '-dropdown' : '-dropup' ), '">', $section['title'],
				     '</h3></legend>', "\n", '<div class="panel-body',
				     ($stat ? ' ' . IP_Geo_Block::PLUGIN_NAME . '-border"' : '"'),
				     ($stat || (4 === $tab && $index) ? '>' : ' style="display:none">'), "\n";

				++$index;

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
			0 => __( 'Settings',     'ip-geo-block' ),
			1 => __( 'Statistics',   'ip-geo-block' ),
			4 => __( 'Logs',         'ip-geo-block' ),
			2 => __( 'Search',       'ip-geo-block' ),
			5 => __( 'Sites',        'ip-geo-block' ),
			3 => __( 'Attribution',  'ip-geo-block' ),
		);

		$settings = IP_Geo_Block::get_option();
		$title = esc_html( get_admin_page_title() );

		// Target page that depends on the network multisite or not.
		if ( 'options-general.php' === $GLOBALS['pagenow'] ) {
			$action = 'options.php';

			if ( $this->is_network ) {
				unset( $tabs[0], $tabs[3], $tabs[5] ); // Settings, Attribution, Sites
				$title .= ' <span class="ip-geo-block-title-link">' . __( 'Network', 'ip-geo-block' );
				$title .= ' [ <a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 0 ), $this->dashboard_url( TRUE ) ) ) . '" target="_self">' . __( 'Settings', 'ip-geo-block' ) . '</a> ]';
				$title .= ' [ <a href="' . esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 5 ), $this->dashboard_url( TRUE ) ) ) . '" target="_self">' . __( 'Sites',    'ip-geo-block' ) . '</a> ]';
				$title .= '</span>';
			} else {
				unset( $tabs[5] );
			}
		} else {
			$action = 'edit.php?action=' . IP_Geo_Block::PLUGIN_NAME;

			if ( $settings['network_wide'] ) {
				unset( $tabs[1], $tabs[4], $tabs[2] ); // Statistics, Logs, Search
				$title .= ' <span class="ip-geo-block-title-link">' . __( 'Network', 'ip-geo-block' );
				$title .= '</span>';
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
	<p style="text-align:left">[ <a id="ip-geo-block-toggle-sections" href="javascript:void(0)"><?php _e( 'Toggle all', 'ip-geo-block' ); ?></a> ]</p>
	<form method="post" action="<?php echo $action; ?>" id="<?php echo IP_Geo_Block::PLUGIN_NAME, '-', $tab; ?>"<?php if ( $tab ) echo " class=\"", IP_Geo_Block::PLUGIN_NAME, "-inhibit\""; ?>>
<?php
		settings_fields( IP_Geo_Block::PLUGIN_NAME );
		$this->do_settings_sections( IP_Geo_Block::PLUGIN_NAME, $tab );
		if ( 0 === $tab )
			submit_button(); // @since 3.1
?>
	</form>
<?php if ( 2 === $tab ) { ?>
	<div id="ip-geo-block-whois"></div>
	<div id="ip-geo-block-map"></div>
<?php } elseif ( 3 === $tab ) {
	// show attribution (higher priority order)
	$tab = array();
	foreach ( IP_Geo_Block_Provider::get_addons() as $provider ) {
		if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
			$tab[] = $geo->get_attribution();
		}
	}
	echo '<p>', implode( '<br />', $tab ), "</p>\n";

	echo '<p>', __( 'Thanks for providing these great services for free.', 'ip-geo-block' ), "<br />\n";
	echo __( '(Most browsers will redirect you to each site <a href="http://www.ipgeoblock.com/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', 'ip-geo-block' ), "</p>\n";
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
			5 => 'admin/includes/tab-network.php',
			3 => 'admin/includes/tab-attribution.php',
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
				$name = "${args['option']}[providers][$key]"; ?>
	<li>
		<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $val; ?>"<?php
			checked(
				( NULL   === $val   && ! isset( $args['value'][ $key ] ) ) ||
				( FALSE  === $val   && ! empty( $args['value'][ $key ] ) ) ||
				( is_string( $val ) && ! empty( $args['value'][ $key ] ) )
			); ?> />
		<label for="<?php echo $id; ?>"><?php echo '<dfn title="', esc_attr( $args['titles'][ $key ] ), '">', $key, '</dfn>'; ?></label>
<?php
				if ( is_string( $val ) ) { ?>
		<input type="text" class="regular-text code" name="<?php echo $name; ?>" value="<?php echo esc_attr( isset( $args['value'][ $key ] ) ? $args['value'][ $key ] : '' ); ?>"<?php if ( ! isset( $val ) ) disabled( TRUE, TRUE ); ?> />
	</li>
<?php
				}
			}
			echo "</ul>\n";
			break;

		  case 'checkboxes':
			echo "\n<ul class=\"ip-geo-block-list\">\n";
			foreach ( $args['list'] as $key => $val ) { ?>
	<li>
		<input type="checkbox" id="<?php echo $id, $sub_id, '_', $key; ?>" name="<?php echo $name, $sub_name, '[', $key, ']'; ?>" value="<?php echo $key; ?>"<?php
			checked( is_array( $args['value'] ) ? ! empty( $args['value'][ $key ] ) : ( $key & $args['value'] ? TRUE : FALSE ) ); ?> />
		<label for="<?php echo $id, $sub_id, '_', $key; ?>"><?php
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
	disabled( ! empty( $args['disabled'] ), TRUE ); ?> />
<label for="<?php echo $id, $sub_id; ?>"><?php
	if ( isset( $args['text'] ) ) echo esc_attr( $args['text'] );
	else if ( isset( $args['html'] ) ) echo $args['html'];
	else _e( 'Enable', 'ip-geo-block' ); ?>
</label>
<?php
			break;

		  case 'select':
		  case 'select-text':
			$desc = '';
			echo "\n<select id=\"${id}${sub_id}\" name=\"${name}${sub_name}\">\n";
			foreach ( $args['list'] as $key => $val ) {
				echo "\t<option value=\"$key\"", ( NULL === $val ? ' selected disabled' : selected( $args['value'], $key, FALSE ) );
				if ( isset( $args['desc'][ $key ] ) ) {
					echo ' data-desc="', $args['desc'][ $key ], '"';
					$key === $args['value'] and $desc = $args['desc'][ $key ];
				}
				echo ">$val</option>\n";
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
	disabled( ! empty( $args['disabled'] ), TRUE );
	if ( isset( $args['placeholder'] ) ) echo ' placeholder="', esc_html( $args['placeholder'] ), '"'; ?> />
<?php
			break; // disabled @since 3.0

		  case 'textarea': ?>
<textarea class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>"
<?php disabled( ! empty( $args['disabled'] ), TRUE ); ?>><?php echo esc_html( $args['value'] ); ?></textarea>
<?php
			break;

		  case 'button': ?>
<input type="button" class="button-secondary" id="<?php echo $id; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"
<?php disabled( ! empty( $args['disabled'] ), TRUE ); ?>/>
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
	 * @link http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_option
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_text_field
	 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/sanitize_option_$option
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 */
	public function sanitize_options( $input ) {
		// setup base options
		$output = IP_Geo_Block::get_option();
		$default = IP_Geo_Block::get_default();

		// Integrate posted data into current settings because if can be a part of hole data
		$input = array_replace_recursive(
			$output = $this->preprocess_options( $output, $default ),
			$input
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

				// Check providers setting
				if ( $error = IP_Geo_Block_Provider::diag_providers( $output[ $key ] ) )
					self::add_admin_notice( 'error', $error );
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
						}

						else {
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
		foreach ( array( 'providers', 'save_statistics', 'anonymize', 'network_wide', 'clean_uninstall' ) as $key ) {
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
		foreach ( array( 'target_pages', 'target_posts', 'target_cates', 'target_tags', 'simulate', 'dnslkup' ) as $key ) {
			$output['public'][ $key ] = array();
		}

		// 3.0.4 AS number
		$output['Maxmind']['use_asn'] = FALSE;

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

		// 3.0.4 AS number
		if ( $output['Maxmind']['use_asn'] && empty( $output['Maxmind']['asn4_path'] ) ) {
			require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
			IP_Geo_Block_Cron::start_update_db( $output, TRUE ); // force to update
		}
		elseif ( ! $output['Maxmind']['use_asn'] && ! @file_exists( $output['Maxmind']['asn4_path'] ) ) {
			$output['Maxmind']['asn4_path'] = NULL; // force to delete
			$output['Maxmind']['asn6_path'] = NULL;
		}

		return $output;
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
			$action = IP_Geo_Block::PLUGIN_NAME . '-auth-nonce';
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
		$stat = IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $options['rewrite'] );

		// check the status of rewrite rules
		$diff = array_diff_assoc( $options['rewrite'], $stat );
		if ( ! empty( $diff ) ) {
			$options['rewrite'] = $stat;

			$file = array();
			$dirs = IP_Geo_Block_Admin_Rewrite::get_dirs();

			// show which file would be the issue
			foreach ( $diff as $key => $stat ) {
				$file[] = '<code>' . $dirs[ $key ] . '.htaccess</code>';
			}

			self::add_admin_notice( 'error',
				sprintf( __( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), implode( ', ', $file ) ) . ' ' .
				sprintf( _n( 'Or please refer to %s to set it manually.', 'Or please refer to %s to set them manually.', count( $file ), 'ip-geo-block' ), '<a href="http://ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How to fix permission troubles? | IP Geo Block">How to fix permission troubles?</a>' )
			);
		}

		// additional configuration
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
		$file = IP_Geo_Block_Opts::setup_validation_timing( $options );
		if ( TRUE !== $file ) {
			$options['validation']['timing'] = 0;
			self::add_admin_notice( 'error', sprintf(
				__( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), '<code>' . $file . '</code>'
			) );
		}

		// Force to finish update matching rule
		delete_transient( IP_Geo_Block::CRON_NAME );

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
			if ( isset( $_POST[ $option ] ) )
				$this->sync_multisite_option( $_POST[ $option ] );
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
	private function sync_multisite_option( $option ) {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" );

		foreach ( $blog_ids as $id ) {
			switch_to_blog( $id );
			update_option( IP_Geo_Block::OPTION_NAME, $option );
			restore_current_blog();
		}
	}

	/**
	 * Ajax callback function
	 *
	 * @link http://codex.wordpress.org/AJAX_in_Plugins
	 * @link http://codex.wordpress.org/Function_Reference/check_ajax_referer
	 * @link http://core.trac.wordpress.org/browser/trunk/wp-admin/admin-ajax.php
	 */
	public function admin_ajax_callback() {
		// Check request origin, nonce, capability.
		$this->check_admin_post( TRUE );

		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php';

		$which = isset( $_POST['which'] ) ? $_POST['which'] : NULL;
		switch ( isset( $_POST['cmd'  ] ) ? $_POST['cmd'  ] : NULL ) {
		  case 'download':
			$res = IP_Geo_Block::get_instance();
			$res = $res->exec_update_db();
			break;

		  case 'search':
			// Get geolocation by IP
			$res = IP_Geo_Block_Admin_Ajax::search_ip( $which );
			break;

		  case 'scan-code':
			// Fetch providers to get country code
			$res = IP_Geo_Block_Admin_Ajax::scan_country( $which );
			break;

		  case 'clear-statistics':
			// Set default values
			IP_Geo_Block_Logs::clear_stat();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-cache':
			// Delete cache of IP address
			IP_Geo_Block_API_Cache::clear_cache();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-logs':
			// Delete logs in MySQL DB
			$hook = array( 'comment', 'login', 'admin', 'xmlrpc', 'public' );
			$which = in_array( $which, $hook ) ? $which : NULL;
			IP_Geo_Block_Logs::clear_logs( $which );
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
				'tab' => 'tab=4'
			);
			break;

		  case 'export-logs':
			// Export logs from MySQL DB
			IP_Geo_Block_Admin_Ajax::export_logs( $which );
			break;

		  case 'restore':
			// Get logs from MySQL DB
			$res = IP_Geo_Block_Admin_Ajax::restore_logs( $which );
			break;

		  case 'validate':
			// Validate settings
			IP_Geo_Block_Admin_Ajax::validate_settings( $this );
			break;

		  case 'import-default':
			// Import initial settings
			$res = IP_Geo_Block_Admin_Ajax::settings_to_json( IP_Geo_Block::get_default() );
			break;

		  case 'import-preferred':
			// Import preference
			$res = IP_Geo_Block_Admin_Ajax::preferred_to_json();
			break;

		  case 'gmap-error':
			// Reset Google Maps API key
			$which = IP_Geo_Block::get_option();
			if ( $which['api_key']['GoogleMap'] === 'default' ) {
				$which['api_key']['GoogleMap'] = NULL;
				update_option( IP_Geo_Block::OPTION_NAME, $which );
				$res = array(
					'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
					'tab' => 'tab=2'
				);
			}
			break;

		  case 'show-info':
			$res = IP_Geo_Block_Admin_Ajax::get_wp_info();
			break;

		  case 'get-actions':
			// Get all the ajax/post actions
			$res = IP_Geo_Block_Util::get_registered_actions( TRUE );
			break;

		  case 'create-table':
		  case 'delete-table':
			// Need to define `IP_GEO_BLOCK_DEBUG` to true
			if ( 'create-table' === $_POST['cmd'] )
				IP_Geo_Block_Logs::create_tables();
			else
				IP_Geo_Block_Logs::delete_tables();

			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_NAME,
			);
			break;
		}

		if ( isset( $res ) ) // wp_send_json_{success,error}() @since 3.5.0
			wp_send_json( $res ); // @since 3.5.0

		die(); // End of ajax
	}

}