<?php
/**
 * IP Geo Block - Admin class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2015 tokkonopapa
 */

class IP_Geo_Block_Admin {

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	/**
	 * Slug of the admin page.
	 *
	 */
	public $option_slug = array();
	public $option_name = array();
	private $admin_tab = 0;

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {
		$this->admin_tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$this->admin_tab = min( 4, max( 0, $this->admin_tab ) );

		// Set unique slug for admin page.
		foreach ( IP_Geo_Block::$option_keys as $key => $val ) {
			$this->option_slug[ $key ] = str_replace( '_', '-', $val );
			$this->option_name[ $key ] = $val;
		}

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
		if ( is_multisite() )
			add_action( 'network_admin_menu', 'IP_Geo_Block::enqueue_nonce' );
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
		load_plugin_textdomain( IP_Geo_Block::PLUGIN_SLUG, FALSE, dirname( IP_GEO_BLOCK_BASE ) . '/languages/' );
	}

	/**
	 * Add nonce when redirect into wp-admin area.
	 *
	 */
	public function add_admin_nonce( $location, $status ) {
		$key = IP_Geo_Block::PLUGIN_SLUG . '-auth-nonce';

		if ( $nonce = IP_Geo_Block::retrieve_nonce( $key ) ) { // must be sanitized
			$host = parse_url( $location, PHP_URL_HOST );

			// check if the location is internal
			if ( ! $host || $host === parse_url( home_url(), PHP_URL_HOST ) ) {
				$location = esc_url_raw( add_query_arg(
					array(
						$key => false, // delete onece
						$key => $nonce // add again
					),
					$location
				) );
			}
		}

		return $location;
	}

	/**
	 * Get the action name of ajax for nonce
	 *
	 */
	private function get_ajax_action() {
		return IP_Geo_Block::PLUGIN_SLUG . '-ajax-action';
	}

	/**
	 * Register and enqueue plugin-specific style sheet and JavaScript.
	 *
	 */
	public function enqueue_admin_assets() {
		$footer = TRUE;
		$dependency = array( 'jquery' );

		// css for option page
		wp_enqueue_style( IP_Geo_Block::PLUGIN_SLUG . '-admin-styles',
			plugins_url( 'css/admin.min.css', __FILE__ ),
			array(), IP_Geo_Block::VERSION
		);

		switch ( $this->admin_tab ) {
		  case 1:
			// js for google chart
			wp_register_script(
				$addon = IP_Geo_Block::PLUGIN_SLUG . '-google-chart',
				'https://www.google.com/jsapi', array(), NULL, $footer
			);
			wp_enqueue_script( $addon );
			break;

		  case 2:
			// js for google map
			wp_enqueue_script( IP_Geo_Block::PLUGIN_SLUG . '-google-map',
				'//maps.google.com/maps/api/js?sensor=false',
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			wp_enqueue_script( IP_Geo_Block::PLUGIN_SLUG . '-gmap-js',
				plugins_url( 'js/gmap.min.js', __FILE__ ),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
			break;

		  case 4:
			// footable https://github.com/bradvin/FooTable
			wp_enqueue_style( IP_Geo_Block::PLUGIN_SLUG . '-footable-css',
				plugins_url( 'css/footable.core.min.css', __FILE__ ),
				array(), IP_Geo_Block::VERSION
			);
			wp_enqueue_script( IP_Geo_Block::PLUGIN_SLUG . '-footable-js',
				plugins_url( 'js/footable.min.js', __FILE__ ),
				$dependency, IP_Geo_Block::VERSION, $footer
			);
		}

		// js for IP Geo Block admin page
		wp_register_script(
			$handle = IP_Geo_Block::PLUGIN_SLUG . '-admin-script',
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
				'url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( $this->get_ajax_action() ),
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
			$nonce = IP_Geo_Block::PLUGIN_SLUG . '-auth-nonce',
			wp_create_nonce( $nonce ),
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
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG ) . '">' . __( 'Settings' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Display global notice
	 *
	 * @notice: Sanitization should be done at the caller
	 */
	public function show_admin_notices() {
		$key = IP_Geo_Block::PLUGIN_SLUG . '-notice';
		if ( FALSE !== ( $notices = get_transient( $key ) ) ) {
			foreach ( $notices as $msg => $type ) {
				echo "\n<div class=\"notice is-dismissible ", $type, "\"><p><strong>IP Geo Block:</strong> ", $msg, "</p></div>\n";
			}
		}
	}

	public static function add_admin_notice( $type, $msg ) {
		$key = IP_Geo_Block::PLUGIN_SLUG . '-notice';
		if ( FALSE === ( $notices = get_transient( $key ) ) )
			$notices = array();

		if ( ! isset( $notices[ $msg ] ) ) {
			$notices[ $msg ] = $type;
			set_transient( $key, $notices, MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Display local notice
	 *
	 */
	private function show_setting_notice( $name, $type, $msg ) {
		add_settings_error( $this->option_slug['settings'], $this->option_name[ $name ], $msg, $type );
	}

	/**
	 * Register the administration menu into the WordPress Dashboard menu.
	 *
	 */
	private function add_plugin_admin_page() {
		// Add a settings page for this plugin to the Settings menu.
		$hook = add_options_page(
			__( 'IP Geo Block', 'ip-geo-block' ),
			__( 'IP Geo Block', 'ip-geo-block' ),
			'manage_options',
			IP_Geo_Block::PLUGIN_SLUG,
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
		// delete all admin noties
		delete_transient( IP_Geo_Block::PLUGIN_SLUG . '-notice' );

		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.7' ) < 0 )
			self::add_admin_notice( 'error', __( 'You need WordPress 3.7+.', 'ip-geo-block' ) );

		$settings = IP_Geo_Block::get_option( 'settings' );

		// Check consistency of matching rule
		if ( -1 === (int)$settings['matching_rule'] ) {
			if ( FALSE !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
				self::add_admin_notice( 'notice-warning', sprintf(
					__( 'Now downloading geolocation databases in background. After a little while, please check your country code and &#8220;<strong>Matching rule</strong>&#8221; at <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
					admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG )
				) );
			}
			else {
				self::add_admin_notice( 'error', sprintf(
					__( 'The &#8220;<strong>Matching rule</strong>&#8221; is not set properly. Please confirm it at <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
					admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG )
				) );
			}
		}

		// Check to finish downloading
		elseif ( 'done' === get_transient( IP_Geo_Block::CRON_NAME ) ) {
			delete_transient( IP_Geo_Block::CRON_NAME );
			self::add_admin_notice( 'updated', __( 'Downloading geolocation databases was successfully done.', 'ip-geo-block' ) );
		}

		// Check self blocking
		if ( 1 === (int)$settings['validation']['login'] ) {
			$instance = IP_Geo_Block::get_instance();
			$validate = $instance->validate_ip( 'login', $settings, TRUE, FALSE, FALSE );

			if ( 'passed' !== $validate['result'] ) {
				self::add_admin_notice( 'error',
					( $settings['matching_rule'] ?
						__( 'Once you logout, you will be unable to login again because your country code or IP address is in the blacklist.', 'ip-geo-block' ) :
						__( 'Once you logout, you will be unable to login again because your country code or IP address is not in the whitelist.', 'ip-geo-block' )
					) .
					sprintf(
						__( 'Please check your <a href="%s">Validation rule settings</a>.', 'ip-geo-block' ),
						admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG . '#' . IP_Geo_Block::PLUGIN_SLUG . '-settings-0' )
					)
				);
			}
		}

		if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			// Check creation of database table
			if ( $settings['validation']['reclogs'] ) {
				include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

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
		$this->diagnose_admin_screen();
		$this->add_plugin_admin_page();

		// Register settings page only if it is needed
		if ( ( isset( $_GET ['page'       ] ) && IP_Geo_Block::PLUGIN_SLUG      === $_GET ['page'       ] ) ||
		     ( isset( $_POST['option_page'] ) && $this->option_slug['settings'] === $_POST['option_page'] ) )
			$this->register_settings_tab();

		// Add an action link pointing to the options page. @since 2.7
		else {
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
			add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links' ), 10, 1 );
		}

		// Register scripts and admin notice
		add_action( 'admin_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page() {
		$tabs = array(
			0 => __( 'Settings',    'ip-geo-block' ),
			1 => __( 'Statistics',  'ip-geo-block' ),
			4 => __( 'Logs',        'ip-geo-block' ),
			2 => __( 'Search',      'ip-geo-block' ),
			3 => __( 'Attribution', 'ip-geo-block' ),
		);
		$tab = $this->admin_tab;
		$option_slug = $this->option_slug[ 1 === $tab ? 'statistics': 'settings' ];
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
<?php foreach ( $tabs as $key => $val ) {
	echo '<a href="?page=', IP_Geo_Block::PLUGIN_SLUG, '&amp;tab=', $key, '" class="nav-tab', ($tab === $key ? ' nav-tab-active' : ''), '">', $val, '</a>';
} ?>
	</h2>
	<form method="post" action="options.php"<?php if ( 0 !== $tab ) echo " id=\"", IP_Geo_Block::PLUGIN_SLUG, "-inhibit\""; ?>>
<?php
		settings_fields( $option_slug );
		do_settings_sections( $option_slug );
		if ( 0 === $tab )
			submit_button(); // @since 3.1
?>
	</form>
<?php if ( 2 === $tab ) { ?>
	<div id="ip-geo-block-map"></div>
<?php } elseif ( 3 === $tab ) {
	echo '<p>', __( 'Thanks for providing these great services for free.', 'ip-geo-block' ), '<br />';
	echo __( '(Most browsers will redirect you to each site <a href="http://www.ipgeoblock.com/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', 'ip-geo-block' ), '</p>';

	// show attribution (higher priority order)
	$providers = IP_Geo_Block_Provider::get_addons();
	$tab = array();
	foreach ( $providers as $provider ) {
		if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
			$tab[] = $geo->get_attribution();
		}
	}
	echo '<p>', implode( '<br />', $tab ), "</p>\n";
} ?>
<?php if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
	echo '<p>', get_num_queries(), ' queries. ', timer_stop(0), ' seconds. ', memory_get_usage(), " bytes.</p>\n";
} ?>
	<p style="text-align:right">[ <a href="#"><?php _e( 'Back to top', 'ip-geo-block' ); ?></a> ]</p>
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

		include_once( IP_GEO_BLOCK_PATH . $files[ $this->admin_tab ] );
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
				echo "\t<option value=\"$key\"", ( $key < 0 ? ' selected disabled' : selected( $args['value'], $key, FALSE ) );
				if ( isset( $args['desc'][ $key ] ) )
					echo " data-desc=\"", $args['desc'][ $key ], "\"";
				echo ">$val</option>\n";
			}
			echo "</select>\n";
			if ( 'select' === $args['type'] )
				break;
			echo "<br />\n";
			$sub_id   = '_' . $args['txt-field'];
			$sub_name = '[' . $args['txt-field'] . ']';
			$args['value'] = $args['text'];

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
	 * A callback function that validates the option's value.
	 *
	 * @param string $option_name The name of option table.
	 * @param array $input The values to be validated.
	 *
	 * @link http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_option
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_text_field
	 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/sanitize_option_$option
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 */
	public function validate_options( $option_name, $input ) {
		// setup base options
		$output = IP_Geo_Block::get_option( $option_name );
		$default = IP_Geo_Block::get_default( $option_name );

		// checkboxes not on the form
		foreach ( array( 'anonymize' ) as $key )
			$output[ $key ] = 0;

		foreach ( array( 'admin', 'ajax', 'plugins', 'themes' ) as $key )
			$output['validation'][ $key ] = 0;

		// restore the 'signature' that might be transformed to avoid self blocking
		$input['signature'] = base64_decode( $input['signature'] ); //str_rot13()

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
				include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
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
				if ( $error = IP_Geo_Block_Provider::diag_providers( $output[ $key ] ) ) {
					$this->show_setting_notice( $option_name, 'error', $error );
				}
				break;

			  case 'comment':
				if ( isset( $input[ $key ]['pos'] ) ) {
					$output[ $key ]['pos'] = (int)$input[ $key ]['pos'];
				}
				if ( isset( $input[ $key ]['msg'] ) ) {
					global $allowedtags;
					$output[ $key ]['msg'] = wp_kses( $input[ $key ]['msg'], $allowedtags );
				}
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
							wp_kses( trim( $input[ $key ] ), array() );
					}
				}

				// sub field
				else foreach ( array_keys( $val ) as $sub ) {
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
								wp_kses( preg_replace( '/[^\w\s\.\/,:!]/', '', $input[ $key ][ $sub ] ), array() )
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
		$output['validation']['proxy'] = preg_replace(
			'/[^\w,]/', '',
			strtoupper( $output['validation']['proxy'] )
		);

		// sanitize and format ip address
		$key = array( '/[^\d\n\.\/,]/', '/([\s,])+/', '/(?:^,|,$)/' );
		$val = array( '',               '$1',         ''            );
		$output['extra_ips']['white_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['white_list'] ) );
		$output['extra_ips']['black_list'] = preg_replace( $key, $val, trim( $output['extra_ips']['black_list'] ) );

		// format signature, ua_list (text area)
		array_shift( $key );
		array_shift( $val );
		$output['signature'] = preg_replace( $key, $val, trim( $output['signature'] ) );

		// reject invalid signature which potentially blocks itself
		$key = array();
		foreach ( explode( ',', $output['signature'] ) as $val ) {
			$val = trim( $val );
			if ( $val && FALSE === stripos( IP_Geo_Block::$wp_path['admin'], $val ) )
				$key[] = $val;
		}
		$output['signature'] = implode( ',', $key );

		// 2.2.5 exception : convert associative array to simple array
		foreach ( array( 'plugins', 'themes' ) as $key )
			$output['exception'][ $key ] = array_keys( $output['exception'][ $key ] );

		return $output;
	}

	/**
	 * For preg_replace_callback()
	 *
	 */
	public function strtoupper( $matches ) {
		return strtoupper( $matches[0] );
	}

	/**
	 * Check admin post
	 *
	 */
	private function check_admin_post( $ajax ) {
		$nonce = TRUE;

		if ( $ajax ) {
			$action = $this->get_ajax_action();
			$nonce &= wp_verify_nonce( IP_Geo_Block::retrieve_nonce( 'nonce' ), $action );
//			$nonce &= check_admin_referer( $this->get_ajax_action(), 'nonce' );
		}

		$action = IP_Geo_Block::PLUGIN_SLUG . '-auth-nonce';
		$nonce &= wp_verify_nonce( IP_Geo_Block::retrieve_nonce( $action ), $action );

		if ( ! current_user_can( 'manage_options' ) || empty( $_POST ) || ! $nonce ) {
			status_header( 403 );
			wp_die(
				__( 'You do not have sufficient permissions to access this page.' ), '',
				array( 'response' => 403, 'back_link' => true )
			);
		}
	}

	/**
	 * Sanitize options before saving them into DB.
	 *
	 */
	public function validate_settings( $input = array() ) {
		// must check that the user has the required capability
		$this->check_admin_post( FALSE );

		// validate setting options
		$options = $this->validate_options( 'settings', $input );

		// activate rewrite rules
		include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );
		$stat = IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $options['rewrite'] );
		$diff = array_diff( $options['rewrite'], $stat );

		if ( ! empty( $diff ) ) {
			$options['rewrite'] = $stat;

			$file = array();
			$dirs = IP_Geo_Block_Admin_Rewrite::get_dirs();

			// show which file would be the issue
			foreach ( $diff as $key => $stat ) {
				$file[] = '<code>' . $dirs[ $key ] . '.htaccess</code>';
			}

			$this->show_setting_notice( 'settings', 'error', sprintf(
				__( 'Unable to write %s. Please check permission.', 'ip-geo-block' ),
				implode( ', ', $file )
			) );
		}

		// Force to finish update matching rule
		delete_transient( IP_Geo_Block::CRON_NAME );

		// register a settings error to be displayed to the user
		$this->show_setting_notice( 'settings', 'updated', __( 'Settings saved.' ) );

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

		$which = isset( $_POST['which'] ) ? $_POST['which'] : NULL;
		switch ( isset( $_POST['cmd'  ] ) ? $_POST['cmd'  ] : NULL ) {
		  case 'download':
			$res = IP_Geo_Block::get_instance();
			$res = $res->update_database();
			break;

		  case 'search':
			// Get geolocation by IP
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::search_ip( $which );
			break;

		  case 'scan-code':
			// Fetch providers to get country code
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::scan_country();
			break;

		  case 'clear-statistics':
			// Set default values
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::clear_stat();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-cache':
			// Delete cache of IP address
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
			IP_Geo_Block_API_Cache::clear_cache();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-logs':
			// Delete logs in MySQL DB
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			$hook = array( 'comment', 'login', 'admin', 'xmlrpc' );
			$which = in_array( $which, $hook ) ? $which : NULL;
			IP_Geo_Block_Logs::clear_logs( $which );
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=4'
			);
			break;

		  case 'export-logs':
			// Export logs from MySQL DB
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			IP_Geo_Block_Admin_Ajax::export_logs( $which );
			break;

		  case 'restore':
			// Get logs from MySQL DB
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::restore_logs( $which );
			break;

		  case 'validate':
			// Validate settings
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			IP_Geo_Block_Admin_Ajax::validate_settings( $this );
			break;

		  case 'import-default':
			// Import initial settings
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::settings_to_json( IP_Geo_Block::get_default() );
			break;

		  case 'import-preferred':
			// Import preference
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::preferred_to_json();
			break;

		  case 'create-table':
		  case 'delete-table':
			// Need to define `IP_GEO_BLOCK_DEBUG` to true
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			if ( 'create-table' === $_POST['cmd'] )
				IP_Geo_Block_Logs::create_tables();
			else
				IP_Geo_Block_Logs::delete_tables();

			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
			);
		}

		if ( isset( $res ) ) // wp_send_json_{success,error}() @since 3.5.0
			wp_send_json( $res ); // @since 3.5.0

		die(); // End of ajax
	}

}