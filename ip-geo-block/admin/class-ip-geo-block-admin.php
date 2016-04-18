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
		add_action( 'admin_menu', array( $this, 'setup_admin_screen' ) );
		add_action( 'wp_ajax_ip_geo_block', array( $this, 'admin_ajax_callback' ) );
		add_action( 'admin_post_ip_geo_block', array( $this, 'admin_ajax_callback' ) );

		// If multisite, then enque the authentication script for network admin
		if ( is_multisite() )
			add_action( 'network_admin_menu', 'IP_Geo_Block::enqueue_nonce' );
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
	 * Load the plugin text domain for translation.
	 *
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( IP_Geo_Block::TEXT_DOMAIN, FALSE, dirname( IP_GEO_BLOCK_BASE ) . '/languages/' );
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
	public function get_ajax_action() {
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
			plugins_url( 'js/admin.min.js', __FILE__ ),
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
	 * Add plugin meta links
	 *
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( $file === IP_GEO_BLOCK_BASE ) {
			$title = __( 'Contribute at GitHub', IP_Geo_Block::TEXT_DOMAIN );
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

			delete_transient( $key );
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
	public function show_setting_notice( $name, $type, $msg ) {
		add_settings_error( $this->option_slug, $this->option_name[ $name ], $msg, $type );
	}

	/**
	 * Register the administration menu into the WordPress Dashboard menu.
	 *
	 */
	private function add_plugin_admin_menu() {
		// Add a settings page for this plugin to the Settings menu.
		$hook = add_options_page(
			__( 'IP Geo Block', IP_Geo_Block::TEXT_DOMAIN ),
			__( 'IP Geo Block', IP_Geo_Block::TEXT_DOMAIN ),
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
			self::add_admin_notice( 'error', __( 'You need WordPress 3.7+.', IP_Geo_Block::TEXT_DOMAIN ) );

		$settings = IP_Geo_Block::get_option( 'settings' );

		// Check consistency of matching rule
		if ( -1 === (int)$settings['matching_rule'] ) {
			if ( FALSE !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
				self::add_admin_notice( 'notice-warning', sprintf(
					__( 'Now downloading geolocation databases in background. After a little while, please check your country code and &#8220;<strong>Matching rule</strong>&#8221; at <a href="%s">Validation rule settings</a>.', IP_Geo_Block::TEXT_DOMAIN ),
					admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG )
				) );
			}
			else {
				self::add_admin_notice( 'error', sprintf(
					__( 'The &#8220;<strong>Matching rule</strong>&#8221; is not set properly. Please confirm it at <a href="%s">Validation rule settings</a>.', IP_Geo_Block::TEXT_DOMAIN ),
					admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG )
				) );
			}
		}

		// Check to finish downloading
		elseif ( 'done' === get_transient( IP_Geo_Block::CRON_NAME ) ) {
			delete_transient( IP_Geo_Block::CRON_NAME );
			self::add_admin_notice( 'updated', __( 'Downloading geolocation databases was successfully done.', IP_Geo_Block::TEXT_DOMAIN ) );
		}

		// Check self blocking
		if ( 1 === (int)$settings['validation']['login'] ) {
			$instance = IP_Geo_Block::get_instance();
			$validate = $instance->validate_ip( 'login', $settings, TRUE, FALSE, FALSE );

			if ( 'passed' !== $validate['result'] ) {
				self::add_admin_notice( 'error',
					( $settings['matching_rule'] ?
						__( 'Once you logout, you will be unable to login again because your country code or IP address is in the blacklist.', IP_Geo_Block::TEXT_DOMAIN ) :
						__( 'Once you logout, you will be unable to login again because your country code or IP address is not in the whitelist.', IP_Geo_Block::TEXT_DOMAIN )
					) .
					sprintf(
						__( 'Please check your <a href="%s">Validation rule settings</a>.', IP_Geo_Block::TEXT_DOMAIN ),
						admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG . '#' . IP_Geo_Block::PLUGIN_SLUG . '-settings-0' )
					)
				);
			}
		}

		if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			// Check creation of database table
			if ( $settings['validation']['reclogs'] ) {
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

				if ( ( $warn = IP_Geo_Block_Logs::diag_tables() ) &&
				     FALSE === IP_Geo_Block_Logs::create_tables() )
					self::add_admin_notice( 'notice-warning', $warn );
			}
		}
	}

	/**
	 * Setup the options page and menu item.
	 *
	 */
	public function setup_admin_screen() {
		$this->diagnose_admin_screen();
		$this->add_plugin_admin_menu();
		$this->register_settings_tab();

		// Add an action link pointing to the options page. @since 2.7
		add_action( 'admin_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links' ), 10, 1 );

		// Register admin notice
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page() {
		$tab = $this->admin_tab;
		$option_slug = $this->option_slug[ 1 === $tab ? 'statistics': 'settings' ];
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=0" class="nav-tab <?php echo $tab === 0 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings',    IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=1" class="nav-tab <?php echo $tab === 1 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Statistics',  IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=4" class="nav-tab <?php echo $tab === 4 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Logs',        IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=2" class="nav-tab <?php echo $tab === 2 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Search',      IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=3" class="nav-tab <?php echo $tab === 3 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Attribution', IP_Geo_Block::TEXT_DOMAIN ); ?></a>
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
	echo '<p>', __( 'Thanks for providing these great services for free.', IP_Geo_Block::TEXT_DOMAIN ), '<br />';
	echo __( '(Most browsers will redirect you to each site <a href="http://www.ipgeoblock.com/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', IP_Geo_Block::TEXT_DOMAIN ), '</p>';

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
</div>
<?php
	}

	/**
	 * Initializes the options page by registering the Sections, Fields, and Settings
	 *
	 */
	private function register_settings_tab() {
		$files = array(
			'admin/includes/tab-settings.php',
			'admin/includes/tab-statistics.php',
			'admin/includes/tab-geolocation.php',
			'admin/includes/tab-attribution.php',
			'admin/includes/tab-accesslog.php',
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
<label for="<?php echo $id, $sub_id; ?>"><?php echo esc_attr( isset( $args['text'] ) ? $args['text'] : __( 'Enable', IP_Geo_Block::TEXT_DOMAIN ) ); ?></label>
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
<?php disabled( ! empty( $args['disabled'] ), TRUE ); ?>><?php echo str_replace( ' ', "\n", esc_html( $args['value'] ) ); ?></textarea>
<?php
			break;

		  case 'button': ?>
<input type="button" class="button-secondary" id="<?php echo $id; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"
<?php disabled( ! empty( $args['disabled'] ), TRUE ); ?>/>
<?php
			break;

		  case 'html':
			echo "\n", $args['value'], "\n"; // must be sanitized at caller
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
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
		require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );

		// setup base options
		$output = IP_Geo_Block::get_option( $option_name );
		$default = IP_Geo_Block::get_default( $option_name );

		// checkboxes not on the form
		foreach ( array( 'anonymize' ) as $key )
			$output[ $key ] = 0;

		foreach ( array( 'admin', 'ajax', 'plugins', 'themes' ) as $key )
			$output['validation'][ $key ] = 0;

		// restore the 'signature' that might be transformed to avoid self blocking
		$input['signature'] = str_rot13( $input['signature'] );

		/**
		 * Sanitize a string from user input
		 */
		foreach ( $output as $key => $val ) {
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

			  // for arrays not on the form
			  case 'ip2location':
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
							sanitize_text_field( trim( $input[ $key ] ) );
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
								sanitize_text_field( preg_replace( '/[^\w\.\/\n,]/', '', $input[ $key ][ $sub ] ) )
							);
						}
					}
				}
				break;
			}
		}

		// sanitize proxy
		$output['validation']['proxy'] = preg_replace(
			'/[^\w,]/', '',
			strtoupper( $output['validation']['proxy'] )
		);

		// sanitize ip address
		$key = array( '/[^\d\.\/ ,]/', '/([ ,])+/', '/(?:^,|,$)/' );
		$val = array( '',              '$1',        ''            );
		$output['extra_ips']['white_list'] = preg_replace( $key, $val, $output['extra_ips']['white_list'] );
		$output['extra_ips']['black_list'] = preg_replace( $key, $val, $output['extra_ips']['black_list'] );

		// reject invalid signature which potentially blocks itself
		$key = array();
		foreach ( explode( ',', $output['signature'] ) as $val ) {
			$val = trim( $val );
			if ( FALSE === strpos( IP_Geo_Block::$wp_dirs['admin'], $val ) )
				$key[] = $val;
		}
		$output['signature'] = implode( ',', $key );

		return $output;
	}

	/**
	 * Sanitize options.
	 *
	 */
	public function validate_settings( $input = array() ) {
		// must check that the user has the required capability 
		if ( ! current_user_can( 'manage_options' ) ) {
			status_header( 403 ); // Forbidden @since 2.0.0
			die( 'forbidden' );
		}

		// validate setting options
		$options = $this->validate_options( 'settings', $input );

		// activate rewrite rules
		if ( FALSE === IP_Geo_Block_Rewrite::activate_rewrite_all( $options['rewrite'] ) ) {
			$options['rewrite'] = array( 'plugins' => FALSE, 'themes' => FALSE );
			$this->show_setting_notice( 'settings', 'error', sprintf(
				__( 'Unable to write %s. Please check permission.', IP_Geo_Block::TEXT_DOMAIN ),
				'<code>.htaccess</code>'
			) );
		}

		// Force to finish update matching rule
		delete_transient( IP_Geo_Block::CRON_NAME );

		// register a settings error to be displayed to the user
		$this->show_setting_notice( 'settings', 'updated',
			__( 'Successfully updated.', IP_Geo_Block::TEXT_DOMAIN )
		);

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
		if ( ! check_admin_referer( $this->get_ajax_action(), 'nonce' ) || // @since 2.5
		     ! current_user_can( 'manage_options' ) || empty( $_POST ) ) { // @since 2.0
			status_header( 403 ); // Forbidden @since 2.0.0
			die( 'forbidden' ); // never reached unless the nonce has leaked
		}

		$which = isset( $_POST['which'] ) ? $_POST['which'] : NULL;
		switch ( isset( $_POST['cmd'  ] ) ? $_POST['cmd'  ] : NULL ) {
		  case 'download':
			$res = IP_Geo_Block::update_database();
			break;

		  case 'search':
			// Get geolocation by IP
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::search_ip( $which );
			break;

		  case 'scan-code':
			// Fetch providers to get country code
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::scan_country();
			break;

		  case 'clear-statistics':
			// Set default values
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::clear_stat();
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-cache':
			// Delete cache of IP address
			delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-logs':
			// Delete logs in MySQL DB
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			$hook = array( 'comment', 'login', 'admin', 'xmlrpc' );
			$which = in_array( $which, $hook ) ? $which : NULL;
			IP_Geo_Block_Logs::clear_logs( $which );
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=4'
			);
			break;

		  case 'restore':
			// Get logs from MySQL DB
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::restore_logs( $which );
			break;

		  case 'validate':
			// Validate settings
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			IP_Geo_Block_Admin_Ajax::validate_settings( $this );
			break;

		  case 'import-default':
			// Import initial settings
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::settings_to_json( IP_Geo_Block::get_default() );
			break;

		  case 'import-preferred':
			// Import preference
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php' );
			$res = IP_Geo_Block_Admin_Ajax::preferred_to_json();
			break;

		  case 'create-table':
		  case 'delete-table':
			// Need to define `IP_GEO_BLOCK_DEBUG` to true
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

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