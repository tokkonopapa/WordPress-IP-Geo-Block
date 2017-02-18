<?php
/**
 * IP Geo Block - Admin class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block_Admin {

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	/**
	 * Tab of the admin page.
	 *
	 */
	private $admin_tab = 0;

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Setup a nonce to validate authentication.
		add_filter( 'wp_redirect', array( $this, 'add_admin_nonce' ), 10, 2 );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'setup_admin_page' ) );
		add_action( 'wp_ajax_ip_geo_block', array( $this, 'admin_ajax_callback' ) );
		add_action( 'admin_post_ip_geo_block', array( $this, 'admin_ajax_callback' ) );
		add_filter( 'wp_prepare_revision_for_js', array( $this, 'add_revision_nonce' ), 10, 3 );

		// If multisite, then enque the authentication script for network admin
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', 'IP_Geo_Block::enqueue_nonce' );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	public static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( IP_Geo_Block::PLUGIN_NAME, FALSE, dirname( IP_GEO_BLOCK_BASE ) . '/languages/' );
	}

	/**
	 * Add nonce when redirect into wp-admin area.
	 *
	 */
	public function add_admin_nonce( $location, $status ) {
		return IP_Geo_Block_Util::rebuild_nonce( $location, $status );
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
					__( 'Import settings ?',  'ip-geo-block' ),
					__( 'Create table ?',     'ip-geo-block' ),
					__( 'Delete table ?',     'ip-geo-block' ),
					__( 'Clear statistics ?', 'ip-geo-block' ),
					__( 'Clear cache ?',      'ip-geo-block' ),
					__( 'Clear logs ?',       'ip-geo-block' ),
					__( 'This feature is available with HTML5 compliant browsers.', 'ip-geo-block' ),
				),
			)
		);
		wp_enqueue_script( $handle );
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
	 * Register the administration menu into the WordPress Dashboard menu.
	 *
	 */
	private function add_plugin_admin_menu() {
		// Setup the tab number
		$this->admin_tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$this->admin_tab = min( 4, max( 0, $this->admin_tab ) );

		// Add a settings page for this plugin to the Settings menu.
		$hook = add_options_page(
			__( 'IP Geo Block', 'ip-geo-block' ),
			__( 'IP Geo Block', 'ip-geo-block' ),
			'manage_options',
			IP_Geo_Block::PLUGIN_NAME,
			array( $this, 'display_plugin_admin_page' )
		);

		// If successful, load admin assets only on this page.
		if ( $hook )
			add_action( "load-$hook", array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Diagnosis of admin settings.
	 *
	 */
	private function diagnose_admin_screen() {
		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.7.0' ) < 0 )
			self::add_admin_notice( 'error', __( 'You need WordPress 3.7+.', 'ip-geo-block' ) );

		$settings = IP_Geo_Block::get_option();
		$adminurl = 'options-general.php';

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
					) .
					sprintf(
						__( 'Please check your <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
						esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME ), $adminurl ) ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-settings-0'
					)
				);
			}
		}

		if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			// Check creation of database table
			if ( $settings['validation']['reclogs'] ) {
				if ( ( $warn = IP_Geo_Block_Logs::diag_tables() ) &&
				     FALSE === IP_Geo_Block_Logs::create_tables() ) {
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
		// Avoid multiple validation.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			$this->diagnose_admin_screen();
			$this->add_plugin_admin_menu();
		}

		// Register settings page only if it is needed.
		if ( ( isset( $_GET ['page'       ] ) && IP_Geo_Block::PLUGIN_NAME === $_GET ['page'       ] ) ||
		     ( isset( $_POST['option_page'] ) && IP_Geo_Block::PLUGIN_NAME === $_POST['option_page'] ) ) {
			$this->register_settings_tab();
		}

		// Add an action link pointing to the options page. @since 2.7
		else {
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
			add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links' ), 10, 1 );
		}

		// Register scripts for admin.
		add_action( 'admin_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ) );

		// Show admin notices at the place where it should be.
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page() {
		$tab = $this->admin_tab;
		$tabs = array(
			0 => __( 'Settings',    'ip-geo-block' ),
			1 => __( 'Statistics',  'ip-geo-block' ),
			4 => __( 'Logs',        'ip-geo-block' ),
			2 => __( 'Search',      'ip-geo-block' ),
			3 => __( 'Attribution', 'ip-geo-block' ),
		);
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
<?php foreach ( $tabs as $key => $val ) {
	echo '<a href="?page=', IP_Geo_Block::PLUGIN_NAME, '&amp;tab=', $key, '" class="nav-tab', ($tab === $key ? ' nav-tab-active' : ''), '">', $val, '</a>';
} ?>
	</h2>
<?php if ( 0 <= $tab && $tab <= 1 ) { ?>
	<p style="text-align:left">[ <a id="ip-geo-block-toggle-sections" href="javascript:void(0)"><?php _e( 'Toggle all', 'ip-geo-block' ); ?></a> ]</p>
<?php } ?>
	<form method="post" action="options.php"<?php if ( 0 !== $tab ) echo " id=\"", IP_Geo_Block::PLUGIN_NAME, "-inhibit\""; ?>>
<?php
		settings_fields( IP_Geo_Block::PLUGIN_NAME );
		do_settings_sections( IP_Geo_Block::PLUGIN_NAME );
		if ( 0 === $tab )
			submit_button(); // @since 3.1
?>
	</form>
<?php if ( 2 === $tab ) { ?>
	<div id="ip-geo-block-whois"></div>
	<div id="ip-geo-block-map"></div>
<?php } elseif ( 3 === $tab ) {
	// show attribution (higher priority order)
	$providers = IP_Geo_Block_Provider::get_addons();
	$tab = array();
	foreach ( $providers as $provider ) {
		if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
			$tab[] = $geo->get_attribution();
		}
	}
	echo '<p>', implode( '<br />', $tab ), "</p>\n";

	echo "<p>", __( 'Thanks for providing these great services for free.', 'ip-geo-block' ), "<br />\n";
	echo __( '(Most browsers will redirect you to each site <a href="http://www.ipgeoblock.com/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', 'ip-geo-block' ), "</p>\n";
} ?>
<?php if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
	echo '<p>', get_num_queries(), ' queries. ', timer_stop(0), ' seconds. ', memory_get_usage(), " bytes.</p>\n";
} ?>
	<p style="margin:0; text-align:right">[ <a id="ip-geo-block-back-to-top" href="#"><?php _e( 'Back to top', 'ip-geo-block' ); ?></a> ]</p>
</div>
<?php
	}

	/**
	 * Initializes the options page by registering the Sections, Fields, and Settings
	 *
	 */
	private function register_settings_tab() {
		$files = array(
			0 => 'admin/includes/tab-settings.php',
			1 => 'admin/includes/tab-statistics.php',
			4 => 'admin/includes/tab-accesslog.php',
			2 => 'admin/includes/tab-geolocation.php',
			3 => 'admin/includes/tab-attribution.php',
		);

		require_once IP_GEO_BLOCK_PATH . $files[ $this->admin_tab ];
		IP_Geo_Block_Admin_Tab::tab_setup( $this );
	}

	/**
	 * Function that fills the field with the desired inputs as part of the larger form.
	 * The 'id' and 'name' should match the $id given in the add_settings_field().
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
			checked( $key & $args['value'] ? TRUE : FALSE ); ?> />
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
<label for="<?php echo $id, $sub_id; ?>"><?php echo esc_attr( isset( $args['text'] ) ? $args['text'] : __( 'Enable', 'ip-geo-block' ) ); ?></label>
<?php
			break;

		  case 'select':
		  case 'select-text':
			echo "\n<select id=\"${id}${sub_id}\" name=\"${name}${sub_name}\">\n";
			foreach ( $args['list'] as $key => $val ) {
				echo "\t<option value=\"$key\"", ( NULL === $val ? ' selected disabled' : selected( $args['value'], $key, FALSE ) );
				if ( isset( $args['desc'][ $key ] ) )
					echo " data-desc=\"", $args['desc'][ $key ], "\"";
				echo ">$val</option>\n";
			}
			echo "</select>\n";
			if ( 'select' === $args['type'] )
				break;
			echo "<br />\n";
			$sub_id   = '_' . $args['txt-field']; // possible value of 'txt-field' is 'msg'
			$sub_name = '[' . $args['txt-field'] . ']';
			$args['value']  = $args['text']; // should be escaped because it can contain allowed tags

		  case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"
<?php disabled( ! empty( $args['disabled'] ), TRUE ); ?> />
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
	public function validate_options( $input ) {
		// setup base options
		$output = IP_Geo_Block::get_option();
		$default = IP_Geo_Block::get_default();

		// checkboxes not on the form (added after 2.0.0, just in case)
		foreach ( array( 'anonymize', 'network_wide' ) as $key ) {
			$output[ $key ] = 0;
		}

		// checkboxes not on the form
		foreach ( array( 'login', 'admin', 'ajax', 'plugins', 'themes', 'public' ) as $key ) {
			$output['validation'][ $key ] = 0;
		}

		// restore the 'signature' that might be transformed to avoid self blocking
		if ( isset( $input['signature'] ) && FALSE === strpos( $input['signature'], ',' ) )
			$input['signature'] = str_rot13( base64_decode( $input['signature'] ) );

		/**
		 * Sanitize a string from user input
		 */
		foreach ( $output as $key => $val ) {
			$key = sanitize_text_field( $key );

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
							isset( $input[ $key ][ $provider ] ) ?
							sanitize_text_field( $input[ $key ][ $provider ] ) : '';
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
				$output[ $key ] = isset( $input[ $key ] ) ?
					sanitize_text_field(
						preg_replace( '/[^A-Z,]/', '', strtoupper( $input[ $key ] ) )
					) : '';
				break;

			  default: // checkbox, select, text
				// single field
				if ( ! is_array( $default[ $key ] ) ) {
					// for checkbox
					if ( is_bool( $default[ $key ] ) ) {
						$output[ $key ] = ! empty( $input[ $key ] );
					}

					// otherwise if implicit
					elseif ( isset( $input[ $key ] ) ) {
						$output[ $key ] = is_int( $default[ $key ] ) ?
							(int)$input[ $key ] :
							IP_Geo_Block_Util::kses( trim( $input[ $key ] ), FALSE );
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

					// otherwise if implicit
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
				}
			}
		}

		//----------------------------------------
		// Check and format each setting data
		//----------------------------------------

		// sanitize proxy
		$output['validation']['proxy'] = implode( ',', $this->trim(
			preg_replace( '/[^\w,]/', '', strtoupper( $output['validation']['proxy'] ) )
		) );

		// sanitize and format ip address
		$key = array( '/[^\d\n\.\/,:]/', '/([\s,])+/', '/(?:^,|,$)/' );
		$val = array( '',                '$1',         ''            );
		$output['extra_ips']['white_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['white_list'] ) );
		$output['extra_ips']['black_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['black_list'] ) );

		// format signature, ua_list (text area)
		array_shift( $key );
		array_shift( $val );
		$output['signature'] = preg_replace( $key, $val, trim( $output['signature'] ) );

		// 3.0.0 convert country code to upper case, remove redundant spaces
		$output['public']['ua_list'] = preg_replace( $key, $val, trim( $output['public']['ua_list'] ) );
		$output['public']['ua_list'] = preg_replace( '/([:#]) *([!]+) *([^ ]+) *([,\n]+)/', '$1$2$3$4', $output['public']['ua_list'] );
		$output['public']['ua_list'] = preg_replace_callback( '/[:#]\w+/', array( $this, 'strtoupper' ), $output['public']['ua_list'] );

		// reject invalid signature which potentially blocks itself
		$output['signature'] = implode( ',', $this->trim( $output['signature'] ) );

		// 2.2.5 exception : convert associative array to simple array
		foreach ( array( 'plugins', 'themes' ) as $key ) {
			$output['exception'][ $key ] = array_keys( $output['exception'][ $key ] );
		}

		// 3.0.0 public : convert country code to upper case
		foreach ( array( 'white_list', 'black_list' ) as $key ) {
			$output['public'][ $key ] = strtoupper( preg_replace( '/\s/', '', $output['public'][ $key ] ) );
		}

		// 3.0.0 exception : trim extra space and comma
		foreach ( array( 'admin', 'public', 'includes', 'uploads', 'languages' ) as $key ) {
			if ( empty( $output['exception'][ $key ] ) ) {
				$output['exception'][ $key ] = $default['exception'][ $key ];
			} else {
				$output['exception'][ $key ] = (  is_array( $output['exception'][ $key ] ) ?
				$output['exception'][ $key ] : $this->trim( $output['exception'][ $key ] ) );
			}
		}

		return $output;
	}

	// Callback for preg_replace_callback()
	public function strtoupper( $matches ) {
		return strtoupper( $matches[0] );
	}

	// Trim extra space and comma avoiding invalid signature which potentially blocks itself
	private function trim( $text ) {
		$ret = array();
		foreach ( explode( ',', $text ) as $val ) {
			$val = trim( $val );
			if ( $val && FALSE === stripos( IP_Geo_Block::$wp_path['admin'], $val ) ) {
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
		if ( FALSE === $ajax ) {
			// a postfix '-options' is added at settings_fields().
			$nonce = check_admin_referer( IP_Geo_Block::PLUGIN_NAME . '-options' );
		} else {
			$nonce = IP_Geo_Block_Util::verify_nonce( IP_Geo_Block_Util::retrieve_nonce( 'nonce' ), $this->get_ajax_action() );
		}

		$action = IP_Geo_Block::PLUGIN_NAME . '-auth-nonce';
		$nonce &= IP_Geo_Block_Util::verify_nonce( IP_Geo_Block_Util::retrieve_nonce( $action ), $action );

		if ( ! $nonce || ( ! current_user_can( 'manage_options' ) ) ) {
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
	 */
	public function validate_settings( $input = array() ) {
		// must check that the user has the required capability
		$this->check_admin_post( FALSE );

		// validate setting options
		$options = $this->validate_options( $input );

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
				sprintf( __( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), implode( ', ', $file ) ) . '&nbsp;' .
				sprintf( _n( 'Or please refer to %s to set it manually.', 'Or please refer to %s to set them manually.', count( $file ), 'ip-geo-block' ), '<a href="http://ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How to fix permission troubles? | IP Geo Block">How to fix permission troubles?</a>' )
			);
		}

		// additional configuration
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
		$file = IP_Geo_Block_Opts::setup_validation_timing( $options );
		if ( TRUE !== $file ) {
			$options['validation']['timing'] = 0;
			self::add_admin_notice( 'error', sprintf(
				__( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), $file
			) );
		}

		// Force to finish update matching rule
		delete_transient( IP_Geo_Block::CRON_NAME );

		return $options;
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
			$res = $res->update_database();
			break;

		  case 'search':
			// Get geolocation by IP
			$res = IP_Geo_Block_Admin_Ajax::search_ip( $which );
			break;

		  case 'scan-code':
			// Fetch providers to get country code
			$res = IP_Geo_Block_Admin_Ajax::scan_country();
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
					'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
					'tab' => 'tab=2'
				);
			}
			break;

		  case 'show-info':
			$res = IP_Geo_Block_Admin_Ajax::get_wp_info();
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