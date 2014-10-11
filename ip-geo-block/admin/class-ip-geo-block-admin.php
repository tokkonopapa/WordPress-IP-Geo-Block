<?php
/**
 * Admin class of IP Geo Block
 *
 * @package   IP_Geo_Block_Admin
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

class IP_Geo_Block_Admin {

	/**
	 * Instance of this class.
	 *
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 */
	protected $plugin_screen_hook_suffix = null;

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

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_cssjs' ) );
		add_action( 'wp_ajax_ip_geo_block', array( $this, 'admin_ajax_callback' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_admin_settings' ) );

		// Add an action link pointing to the options page. @since 2.7
		add_filter( 'plugin_action_links_' . IP_GEO_BLOCK_BASE, array( $this, 'add_action_links' ), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );

		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.5' ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}

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
	 * Display notice
	 *
	 */
	public function admin_notice() {
		$info = $this->get_plugin_info();
		$msg = __( 'You need WordPress 3.5+', IP_Geo_Block::TEXT_DOMAIN );
		echo "\n<div class=\"error\"><p>", $info['Name'], ": $msg</p></div>\n";
	}

	/**
	 * Get the action name of ajax for nonce
	 *
	 */
	private function get_ajax_action() {
		return IP_Geo_Block::PLUGIN_SLUG . '-ajax-action';
	}

	/**
	 * Register and enqueue admin-specific style sheet and JavaScript.
	 *
	 */
	public function enqueue_admin_cssjs() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) )
			return;

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			// css for option page
			wp_enqueue_style( IP_Geo_Block::PLUGIN_SLUG . '-admin-styles',
				plugins_url( 'css/admin.css', __FILE__ ),
				array(), IP_Geo_Block::VERSION
			);

			// js for google map
			wp_enqueue_script( IP_Geo_Block::PLUGIN_SLUG . '-google-map',
				'http://maps.google.com/maps/api/js?sensor=false',
				array( 'jquery' ), IP_Geo_Block::VERSION, TRUE
			);

			// js for option page
			$handle = IP_Geo_Block::PLUGIN_SLUG . '-admin-script';
			wp_enqueue_script( $handle,
				plugins_url( 'js/admin.js', __FILE__ ),
				array( 'jquery' ), IP_Geo_Block::VERSION, TRUE
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
			$title = __( 'Contribute on GitHub', IP_Geo_Block::TEXT_DOMAIN );
			array_push(
				$links,
				"<a href=\"https://github.com/tokkonopapa/WordPress-IP-Geo-Block\" title=\"$title\" target=_blank>$title</a>"
			);
		}
		return $links;

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 */
	public function add_plugin_admin_menu() {

		// Add a settings page for this plugin to the Settings menu.
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'IP Geo Block', IP_Geo_Block::TEXT_DOMAIN ),
			__( 'IP Geo Block', IP_Geo_Block::TEXT_DOMAIN ),
			'manage_options',
			IP_Geo_Block::PLUGIN_SLUG,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page( $tab = 0 ) {
		$tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$tab = min( 3, max( 0, $tab ) );
		$option_slug = $this->option_slug[ 1 === $tab ? 'statistics': 'settings' ]; ?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=0" class="nav-tab <?php echo $tab == 0 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings', IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=1" class="nav-tab <?php echo $tab == 1 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Statistics', IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=2" class="nav-tab <?php echo $tab == 2 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Search', IP_Geo_Block::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_Geo_Block::PLUGIN_SLUG; ?>&amp;tab=3" class="nav-tab <?php echo $tab == 3 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Attribution', IP_Geo_Block::TEXT_DOMAIN ); ?></a>
	</h2>
	<form method="post" action="options.php">
<?php
		settings_fields( $option_slug );
		do_settings_sections( $option_slug );
		if ( 0 === $tab )
			submit_button(); // @since 3.1
?>
	</form>
<?php if ( 2 === $tab ) { ?>
	<div id="ip-geo-block-map"></div>
<?php } else if ( 3 === $tab ) { ?>
	<p>This product includes GeoLite data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>.<br />This product includes IP2Location open source libraries available from <a href="http://www.ip2location.com">http://www.ip2location.com</a>.</p>
<?php } ?>
	<p><?php echo get_num_queries(); ?> queries. <?php timer_stop(1); ?> seconds. <?php echo memory_get_usage(); ?> bytes.</p>
</div>
<?php
	}

	/**
	 * Initializes the options page by registering the Sections, Fields, and Settings
	 *
	 */
	public function register_admin_settings() {
		$tab = isset( $_GET['tab'] ) ? (int)$_GET['tab'] : 0;
		$tab = min( 3, max( 0, $tab ) );

		/*========================================*
		 * Settings
		 *========================================*/
		if ( 0 === $tab ) {
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-settings.php' );
			tab_settings( $this );
		}

		/*========================================*
		 * Statistics
		 *========================================*/
		else if ( 1 === $tab ) {
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-statistics.php' );
			tab_statistics( $this );
		}

		/*========================================*
		 * Geolocation
		 *========================================*/
		else if ( 2 === $tab ) {
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-geolocation.php' );
			tab_geolocation( $this );
		}

		/*========================================*
		 * Attribution
		 *========================================*/
		else if ( 3 === $tab ) {
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/tab-attribution.php' );
			tab_attribution( $this );
		}
	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	public function callback_attribution() {
		echo "<p>" . __( 'Thanks for providing these great services for free.', IP_Geo_Block::TEXT_DOMAIN ) . "</p>\n";
		echo "<p>" . __( '(Most browsers will redirect you to each site without referrer when you click the link.)', IP_Geo_Block::TEXT_DOMAIN ) . "</p>\n";
	}

	/**
	 * Function that fills the field with the desired inputs as part of the larger form.
	 * The 'id' and 'name' should match the $id given in the add_settings_field().
	 * @param array $args A value to be given into the field.
	 * @link http://codex.wordpress.org/Function_Reference/checked
	 */
	public function callback_field( $args ) {
		if ( ! empty( $args['before'] ) )
			echo $args['before'], "\n";

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
		<label for="<?php echo $id; ?>" title="<?php echo $args['titles'][ $key ]; ?>"><?php echo $key; ?></label>
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
			foreach ( $args['list'] as $key => $val ) {
				echo "\t<option value=\"$val\"", 
					selected( $args['value'], $val, FALSE ), ">$key</option>\n";
			}
			echo "</select>\n";
			if ( 'select' === $args['type'] )
				break;
			echo "<br />\n";
			$sub_id   = '_msg';
			$sub_name = '[msg]';
			$args['value'] = $args['text'];

		  case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"<?php disabled( $args['disabled'], TRUE );
?> />
<?php
			break; // disabled @since 3.0

		  case 'checkbox': ?>
<input type="checkbox" id="<?php echo $id, $sub_id; ?>" name="<?php echo $name, $sub_name; ?>" value="1"<?php checked( esc_attr( $args['value'] ) ); ?> />
<label for="<?php echo $id, $sub_id; ?>"><?php _e( 'Enable', IP_Geo_Block::TEXT_DOMAIN ); ?></label>
<?php
			break;

		  case 'button': ?>
<input type="button" class="button-secondary" id="<?php echo $args['field']; ?>" value="<?php echo $args['value']; ?>" />
<?php
			break;

		  case 'html':
			echo "\n", $args['value'], "\n";
			break;
		}

		if ( ! empty( $args['after'] ) )
			echo $args['after'], "\n";
	}

	/**
	 * A callback function that validates the option's value.
	 *
	 * @param string $option_name The name of option table.
	 * @param array $input The values to be validated.
	 *
	 * @link http://codex.wordpress.org/Data_Validation
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_option
	 * @link http://codex.wordpress.org/Function_Reference/sanitize_text_field
	 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/sanitize_option_$option
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 */
	private function sanitize_options( $option_name, $input ) {
		$message = __( 'successfully updated', IP_Geo_Block::TEXT_DOMAIN );
		$status = 'updated';

		// setup base options
		$output = get_option( $option_name );

		// extract key with 'only-' on its top
		$only = array_shift( array_keys( array_diff_key( $input, $output ) ) );
		$only = strpos( $only, 'only-' ) === 0 ? substr( $only, 5 ) : FALSE;

		/**
		 * Sanitize a string from user input or from the db
		 *
		 * - check for invalid UTF-8,
		 * - convert single `<` characters to entity,
		 * - strip all tags,
		 * - remove line breaks, tabs and extra white space,
		 * - strip octets.
		 *
		 * @since 2.9.0
		 * @example sanitize_text_field( $str );
		 * @param string $str
		 * @return string
		 */
		foreach ( $output as $key => $value ) {
			// skip except specified key
			if ( $only && $only !== $key )
				continue;

			switch( $key ) {
			  case 'providers':
				require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );
				$providers = IP_Geo_Block_Provider::get_providers( 'key' );
				foreach ( $providers as $provider => $api ) {
					// need no key
					if ( NULL === $api ) {
						if ( isset( $input[ $key ][ $provider ] ) )
							unset( $output[ $key ][ $provider ] );
						else
							$output['providers'][ $provider ] = '';
					}

					// non-commercial
					else if ( FALSE === $api ) {
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
			  case 'flags':
			  case 'validation': // should be removed when it is implemented.
			  case 'ip2location':
				break;

			  default: // checkbox, select, text
				// single field
				if ( ! is_array( $value ) ) {
					// for checkbox
					if ( is_bool( $value ) ) {
						$output[ $key ] = ! empty( $input[ $key ] );
					}

					// otherwise if implicit
					else if ( isset( $input[ $key ] ) ) {
						$output[ $key ] = is_int( $value ) ?
							(int)$input[ $key ] :
							sanitize_text_field( trim( $input[ $key ] ) );
					}
				}

				// sub field
				else foreach ( array_keys( $value ) as $sub ) {
					// for checkbox
					if ( is_bool( $value[ $sub ] ) ) {
						$output[ $key ][ $sub ] = ! empty( $input[ $key ][ $sub ] );
					}

					// otherwise if implicit
					else if ( isset( $input[ $key ][ $sub ] ) ) {
						$output[ $key ][ $sub ] = is_int( $value[ $sub ] ) ?
							(int)$input[ $key ][ $sub ] :
							sanitize_text_field( trim( $input[ $key ][ $sub ] ) );
					}
				}
				break;
			}
		}

		// schedule auto updating
		IP_Geo_Block::schedule_cron_job( $output['update'], $output['maxmind'], TRUE );

		// This call is just for debug.
		// @param string $setting: Slug title of the setting to which this error applies.
		// @param string $code: Slug-name to identify the error.
		// @param string $message: The formatted message text to display to the user.
		// @param string $type: The type of message it is. 'error' or 'updated'.
		// @link: http://codex.wordpress.org/Function_Reference/add_settings_error
		add_settings_error(
			$this->option_slug
			, 'sanitize_' . $option_name
			, $message //.' : ' . print_r( $output, true )
			, $status
		);

		return $output;
	}

	/**
	 * Sanitize options.
	 *
	 */
	public function sanitize_settings( $input = array() ) {
		return $this->sanitize_options( $this->option_name['settings'], $input );
	}

	/**
	 * Ajax callback function
	 *
	 * @link http://codex.wordpress.org/AJAX_in_Plugins
	 * @link http://core.trac.wordpress.org/browser/trunk/wp-admin/admin-ajax.php
	 * @link http://codex.wordpress.org/Function_Reference/check_ajax_referer
	 */
	public function admin_ajax_callback() {

		// Check request origin, nonce, capability.
		if ( ! check_admin_referer( $this->get_ajax_action(), 'nonce' ) || // @since 2.5
		     ! current_user_can( 'manage_options' ) || empty( $_POST ) ) { // @since 2.0
			status_header( 403 ); // Forbidden @since 2.0.0
		}

		// download database
		else if ( isset( $_POST['download'] ) ) {
			// download now
			$res = IP_Geo_Block::get_instance();
			$res = $res->download_database( 'only-maxmind' );

			// respond
			wp_send_json( $res ); // @since 3.5.0
		}

		// Check ip address
		else if ( isset( $_POST['provider'] ) ) {
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

			// check format
			$ip = $_POST['ip'];
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			     filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				// get location
				$provider = $_POST['provider'];
				$name = IP_Geo_Block_API::get_class_name( $provider );

				if ( $name ) {
					// get option settings and compose request headers
					$options = get_option( $this->option_name['settings'] );
					$args = IP_Geo_Block::get_request_headers( $options );

					// create object for provider and get location
					$key = ! empty( $options['providers'][ $provider ] );
					$geo = new $name( $key ? $options['providers'][ $provider ] : NULL );
					$res = $geo->get_location( $ip, $args );
				}

				else {
					$res = array( 'errorMessage' => 'Unknown service.' );
				}
			}

			else {
				$res = array( 'errorMessage' => 'Invalid IP address.' );
			}

			// respond
			wp_send_json( $res ); // @since 3.5.0
		}

		// Clear statistics
		else if ( isset( $_POST['clear'] ) ) {
			// set default values
			update_option(
				$this->option_name['statistics'],
				IP_Geo_Block::get_defaults( 'statistics' )
			);

			// delete cache of IP address
			delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8

			// refresh page
			wp_send_json( array(
				'refresh' => 'options-general.php?page=ip-geo-block&tab=1',
			) );
		}

		else {
			wp_send_json( array( 'errorMessage' => 'Invalid command.' ) );
		}

		// End of ajax
		die();
	}

}