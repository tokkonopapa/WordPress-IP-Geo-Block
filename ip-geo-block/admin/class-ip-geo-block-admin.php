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

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {
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
			$location = esc_url_raw( add_query_arg(
				array(
					$key => false, // delete onece
					$key => $nonce // add again
				),
				$location
			) );
		}

		return $location;
	}

	/**
	 * Display global notice
	 *
	 * @notice: Sanitization should be done at the caller
	 */
	public function admin_notices() {
		foreach ( $this->notice as $notice ) {
			echo "\n<div class=\"notice is-dismissible ", key( $notice ), "\"><p><strong>IP Geo Block:</strong> ", current( $notice ), "</p></div>\n";
		}
	}

	public function add_admin_notice( $type, $msg ) {
		$this->notice[] = array( $type => $msg );
	}

	/**
	 * Display local notice
	 *
	 * @notice: Sanitization should be done at the caller
	 */
	public function setting_notice( $name, $type, $msg ) {
		add_settings_error( $this->option_slug, $this->option_name[ $name ], $msg, $type );
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
		$tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$tab = min( 4, max( 0, $tab ) );

		// css for option page
		wp_enqueue_style( IP_Geo_Block::PLUGIN_SLUG . '-admin-styles',
			plugins_url( 'css/admin.css', __FILE__ ),
			array(), IP_Geo_Block::VERSION
		);

		switch ( $tab ) {
		  case 1:
			// js for google chart
			wp_enqueue_script( $addon = IP_Geo_Block::PLUGIN_SLUG . '-google-chart',
				'https://www.google.com/jsapi', array(), NULL, $footer
			);
			break;

		  case 2:
			// js for google map
			wp_enqueue_script( IP_Geo_Block::PLUGIN_SLUG . '-google-map',
				'//maps.google.com/maps/api/js?sensor=false',
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
		wp_enqueue_script( $handle = IP_Geo_Block::PLUGIN_SLUG . '-admin-script',
			plugins_url( 'js/admin.js', __FILE__ ),
			$dependency + ( isset( $addon ) ? array( $addon ) : array() ),
			IP_Geo_Block::VERSION, $footer
		);

		// global value for ajax @since r16
		wp_localize_script( $handle,
			'IP_GEO_BLOCK',
			array(
				'action' => 'ip_geo_block',
				'url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( $this->get_ajax_action() ),
			)
		);
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
	 * Add plugin meta links
	 *
	 */
	public function add_plugin_meta_links( $links, $file ) {
		if ( $file === IP_GEO_BLOCK_BASE ) {
			$title = __( 'Contribute at GitHub', IP_Geo_Block::TEXT_DOMAIN );
			array_push(
				$links,
				"<a href=\"https://github.com/tokkonopapa/WordPress-IP-Geo-Block\" title=\"$title\" target=_blank>$title</a>"
			);
		}

		return $links;
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
		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.7' ) < 0 )
			$this->add_admin_notice( 'error', __( 'You need WordPress 3.7+.', IP_Geo_Block::TEXT_DOMAIN ) );

		// Check consistency of matching rule
		$settings = IP_Geo_Block::get_option( 'settings' );
		if ( -1 == $settings['matching_rule'] ) {
			$this->add_admin_notice( 'notice-warning', sprintf(
				__( 'Welcome to %s!! At first, please confirm &#8217;<strong>Matching rule</strong>&#8217; at <a href="%s">Validation rule settings</a>.', IP_Geo_Block::TEXT_DOMAIN ),
				__( 'IP Geo Block', IP_Geo_Block::TEXT_DOMAIN ),
				admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG )
			) );
		}

		// check force to save
		elseif ( 1 == $settings['validation']['login'] ) {
			$validate = IP_Geo_Block::get_geolocation();
			$validate = IP_Geo_Block::validate_country( $validate, $settings );
			if ( 'passed' !== $validate['result'] ) {
				$url = admin_url( 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG ) . '#';
				$this->add_admin_notice( 'error', sprintf(
					__( 'You will be blocked out when you log out. Please confirm &#8217;<strong>Matching rule</strong>&#8217; and the country code in &#8217;<strong>White list</strong>&#8217; or &#8217;<strong>Black list</strong>&#8217; at <a href="%s">Validation rule settings</a>. Otherwise select &#8217;<strong>Block by country (register, lost password)</strong>&#8217; for &#8217;<strong>Login form</strong>&#8217; at <a href="%s">Validation target settings</a>.', IP_Geo_Block::TEXT_DOMAIN ),
					$url . IP_Geo_Block::PLUGIN_SLUG . '-settings-0', $url . IP_Geo_Block::PLUGIN_SLUG . '-settings-1'
				) );
			}
		}

		if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			// Check creation of database table
			if ( $settings['validation']['reclogs'] ) {
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

				if ( ( $warn = IP_Geo_Block_Logs::diag_table() ) &&
				     FALSE === IP_Geo_Block_Logs::create_log() )
					$this->add_admin_notice( 'notice-warning', $warn );
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
		$this->register_admin_settings();

		// Add an action link pointing to the options page. @since 2.7
		add_action( 'admin_enqueue_scripts', array( 'IP_Geo_Block', 'enqueue_nonce' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links' ), 10, 1 );

		// Register admin notice
		if ( isset( $this->notice ) )
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page( $tab = 0 ) {
		$tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$tab = min( 4, max( 0, $tab ) );
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
<?php } elseif ( 3 === $tab ) { ?>
	<p><?php echo __( 'Thanks for providing these great services for free.', IP_Geo_Block::TEXT_DOMAIN ); ?><br />
	<?php echo __( '(Most browsers will redirect you to each site <a href="http://tokkonopapa.github.io/WordPress-IP-Geo-Block/etc/referer.html" title="Referer Checker">without referrer when you click the link</a>.)', IP_Geo_Block::TEXT_DOMAIN ); ?></p>
	<p>This product includes GeoLite data created by MaxMind, available from <a class="ip-geo-block-link" href="http://www.maxmind.com" rel=noreferrer target=_blank>http://www.maxmind.com</a>.<br />
	This product includes IP2Location open source libraries available from <a class="ip-geo-block-link" href="http://www.ip2location.com" rel=noreferrer target=_blank>http://www.ip2location.com</a>.</p>
<?php } ?>
<?php if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) { ?>
	<p><?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. <?php echo memory_get_usage(); ?> bytes.</p>
<?php } ?>
</div>
<?php
	}

	/**
	 * Initializes the options page by registering the Sections, Fields, and Settings
	 *
	 */
	private function register_admin_settings() {
		$tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		switch( min( 4, max( 0, $tab ) ) ) {
		  case 0:
			// Settings
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-settings.php' );
			ip_geo_block_tab_settings( $this );
			break;

		  case 1:
			// Statistics
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-statistics.php' );
			ip_geo_block_tab_statistics( $this );
			break;

		  case 2:
			// Geolocation
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-geolocation.php' );
			ip_geo_block_tab_geolocation( $this );
			break;

		  case 3:
			// Attribution
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-attribution.php' );
			ip_geo_block_tab_attribution( $this );
			break;

		  case 4:
			// Access log
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-accesslog.php' );
			ip_geo_block_tab_accesslog( $this );
		}
	}

	/**
	 * Function that fills the field with the desired inputs as part of the larger form.
	 * The 'id' and 'name' should match the $id given in the add_settings_field().
	 * @param array $args['value'] must be sanitized because it comes from external.
	 */
	public function callback_field( $args ) {
		if ( ! empty( $args['before'] ) )
			echo $args['before'], "\n"; // must be sanitized at caller

		$id   = "${args['option']}_${args['field']}";
		$name = "${args['option']}[${args['field']}]";

		// sub field
		$sub_id = $sub_name = '';
		if ( ! empty( $args['sub-field'] ) ) {
			$sub_id   = "_${args['sub-field']}";
			$sub_name = "[${args['sub-field']}]";
		}

		switch ( $args['type'] ) {

		  case 'check-provider':
			echo "\n<ul id=\"check-provider\">\n";
			foreach ( $args['providers'] as $key => $val ) {
				$id   = "${args['option']}_providers_$key";
				$name = "${args['option']}[providers][$key]"; ?>
	<li>
		<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $val; ?>"<?php
			checked(
				( NULL   === $val   && ! isset( $args['value'][ $key ] ) ) ||
				( FALSE  === $val   && ! empty( $args['value'][ $key ] ) ) ||
				( is_string( $val ) && ! empty( $args['value'][ $key ] ) )
			); ?> />
		<label for="<?php echo $id; ?>" title="<?php echo esc_attr( $args['titles'][ $key ] ); ?>"><?php echo $key; ?></label>
<?php
				if ( is_string( $val ) ) { ?>
		<input type="text" class="regular-text code" name="<?php echo $name; ?>" value="<?php echo esc_attr( isset( $args['value'][ $key ] ) ? $args['value'][ $key ] : '' ); ?>"<?php if ( ! isset( $val ) ) disabled( TRUE, TRUE ); ?> />
	</li>
<?php
				}
			}
			echo "</ul>\n";
			break;

		  case 'select':
		  case 'comment-msg':
			echo "\n<select id=\"${id}${sub_id}\" name=\"${name}${sub_name}\">\n";
			foreach ( $args['list'] as $key => $val )
				echo "\t<option value=\"$val\"", ( $val < 0 ? ' selected disabled' : selected( $args['value'], $val, FALSE ) ), ">$key</option>\n";
			echo isset( $optgroup ) ? "</optgroup>\n" : "", "</select>\n";
			if ( 'select' === $args['type'] )
				break;
			echo "<br />\n";
			$sub_id   = '_msg';
			$sub_name = '[msg]';
			$args['value'] = $args['text'];

		  case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"<?php disabled( ! empty( $args['disabled'] ), TRUE );
?> />
<?php
			break; // disabled @since 3.0

		  case 'checkbox': ?>
<input type="checkbox" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="1"<?php checked( esc_attr( $args['value'] ) ); ?> />
<label for="<?php echo $id, $sub_id; ?>"><?php echo esc_attr( isset( $args['text'] ) ? $args['text'] : __( 'Enable', IP_Geo_Block::TEXT_DOMAIN ) ); ?></label>
<?php
			break;

		  case 'button': ?>
<input type="button" class="button-secondary" id="<?php echo esc_attr( $args['field'] ); ?>" value="<?php echo esc_attr( $args['value'] ); ?>" />
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
	private function validate_options( $option_name, $input ) {
		// setup base options
		$output = IP_Geo_Block::get_option( $option_name );
		$default = IP_Geo_Block::get_default( $option_name );

		/**
		 * Sanitize a string from user input
		 */
		foreach ( $output as $key => $value ) {
			// delete old key
			if ( ! array_key_exists( $key, $default ) ) {
				unset( $output[ $key ] );
				continue;
			}

			switch( $key ) {
			  case 'providers':
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
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
					$this->setting_notice( $option_name, 'error', $error );
				}
				break;
/*
			  case 'comment':
				global $allowedtags;
				$output[ $key ]['pos'] = (int)$input[ $key ]['pos'];
				$output[ $key ]['msg'] = wp_kses( $input[ $key ]['msg'], $allowedtags );
				break;
*/
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
				else foreach ( array_keys( $value ) as $sub ) {
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
						$output[ $key ][ $sub ] = is_int( $default[ $key ][ $sub ] ) ?
							(int)$input[ $key ][ $sub ] :
							sanitize_text_field(
								preg_replace( '/\s/', '', $input[ $key ][ $sub ] )
							);
						if ( 'proxy' === $sub ) {
							$output[ $key ][ $sub ] = preg_replace( '/[^\w,]/', '',
								strtoupper( $output[ $key ][ $sub ] ) );
						}
					}
				}
				break;
			}
		}

		// Register a settings error to be displayed to the user
		$this->setting_notice( $option_name, 'updated',
			__( 'Successfully updated.', IP_Geo_Block::TEXT_DOMAIN )
		);

		return $output;
	}

	/**
	 * Sanitize options.
	 *
	 */
	public function validate_settings( $input = array() ) {
		return $this->validate_options( 'settings', $input );
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
		}

		$which = isset( $_POST['which'] ) ? $_POST['which'] : NULL;
		switch ( isset( $_POST['cmd'  ] ) ? $_POST['cmd'  ] : NULL ) {
		  case 'download':
			if ( 'maxmind' === $which )
				$res = IP_Geo_Block::download_database();
			break;

		  case 'search':
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

			// check format
			if ( filter_var( $ip = $_POST['ip'], FILTER_VALIDATE_IP ) ) {
				if ( $name = IP_Geo_Block_API::get_class_name( $which ) ) {
					// get option settings and compose request headers
					$options = IP_Geo_Block::get_option( 'settings' );
					$args    = IP_Geo_Block::get_request_headers( $options );

					// create object for provider and get location
					$geo = new $name( IP_Geo_Block_API::get_api_key( $which, $options ) );
					$res = $geo->get_location( $ip, $args );
				}
				else {
					$res = array( 'errorMessage' => 'Unknown service.' );
				}
			}
			else {
				$res = array( 'errorMessage' => 'Invalid IP address.' );
			}
			break;

		  case 'scan-code':
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

			// scan all the country code using selected APIs
			$ip        = IP_Geo_Block::get_ip_address();
			$options   = IP_Geo_Block::get_option( 'settings' );
			$args      = IP_Geo_Block::get_request_headers( $options );
			$type      = IP_Geo_Block_Provider::get_providers( 'type', FALSE, FALSE );
			$providers = IP_Geo_Block_Provider::get_valid_providers( $options['providers'], FALSE, FALSE );

			$res = array();
			$res['IP address'] = esc_html( $ip );

			foreach ( $providers as $provider ) {
				if ( $name = IP_Geo_Block_API::get_class_name( $provider ) ) {
					$geo = new $name( IP_Geo_Block_API::get_api_key( $provider, $options ) );
					$ret = $geo->get_location( $ip, $args );
					$res[ $provider ] = array(
						'type' => $type[ $provider ],
						'code' => esc_html(
							FALSE === $ret ? __( 'n/a', IP_Geo_Block::TEXT_DOMAIN ) : (
							! empty( $ret['errorMessage'] ) ? $ret['errorMessage'] : (
							! empty( $ret['countryCode' ] ) ? $ret['countryCode' ] :
							__( 'UNKNOWN', IP_Geo_Block::TEXT_DOMAIN ) ) )
						),
					);
				}
			}
			break;

		  case 'clear-statistics':
			// set default values
			update_option(
				$this->option_name['statistics'],
				IP_Geo_Block::get_default( 'statistics' )
			);
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-cache':
			// delete cache of IP address
			delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=1'
			);
			break;

		  case 'clear-logs':
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			$hook = array( 'comment', 'login', 'admin', 'xmlrpc' );
			$which = in_array( $which, $hook ) ? $which : NULL;
			IP_Geo_Block_Logs::clean_log( $which );
			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
				'tab' => 'tab=4'
			);
			break;

		  case 'restore':
			require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			// if js is slow then limit the number of rows
			$limit = IP_Geo_Block_Logs::limit_rows( @$_POST['time'] );

			// compose html with sanitization
			$which = IP_Geo_Block_Logs::restore_log( $which );
			foreach ( $which as $hook => $rows ) {
				$html = '';
				$n = 0;
				foreach ( $rows as $logs ) {
					$log = (int)array_shift( $logs );
					$html .= "<tr><td data-value=$log>";
					$html .= ip_geo_block_localdate( $log, 'Y-m-d H:i:s' ) . "</td>";
					foreach ( $logs as $log ) {
						$log = esc_html( $log );
						$html .= "<td>$log</td>";
					}
					$html .= "</tr>";
					if ( ++$n >= $limit ) break;
				}
				$res[ $hook ] = $html;
			}
			break;

		  case 'create_table':
		  case 'delete_table':
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

			if ( 'create_table' === $_POST['cmd'] )
				IP_Geo_Block_Logs::create_log();
			else
				IP_Geo_Block_Logs::delete_log();

			$res = array(
				'page' => 'options-general.php?page=' . IP_Geo_Block::PLUGIN_SLUG,
			);
		}

		if ( isset( $res ) ) // wp_send_json_{success,error}() @since 3.5.0
			wp_send_json( $res ); // @since 3.5.0

		// End of ajax
		die();
	}

}