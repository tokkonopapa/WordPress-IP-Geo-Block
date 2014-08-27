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
	protected $option_slug = array();
	protected $option_name = array();

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {

		// Set unique slug for admin page.
		$plugin = IP_Geo_Block::get_instance();
		foreach ( $plugin->get_option_keys() as $key => $val) {
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
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Display notice
	 *
	 */
	public function admin_notice() {
		$info = $this->get_plugin_info();
		$msg = __( 'You need WordPress 3.5+', IP_GEO_BLOCK::TEXT_DOMAIN );
		echo "\n<div class=\"error\"><p>", $info['Name'], ": $msg</p></div>\n";
	}

	/**
	 * Get the action name of ajax for nonce
	 *
	 */
	private function get_ajax_action() {
		return IP_GEO_BLOCK::PLUGIN_SLUG . '-ajax-action';
	}

	/**
	 * Register and enqueue admin-specific style sheet and JavaScript.
	 *
	 */
	public function enqueue_admin_cssjs() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			// css for option page
			wp_enqueue_style( IP_GEO_BLOCK::PLUGIN_SLUG . '-admin-styles',
				plugins_url( 'css/admin.css', __FILE__ ),
				array(), IP_Geo_Block::VERSION
			);

			// js for google map
			wp_enqueue_script( IP_GEO_BLOCK::PLUGIN_SLUG . '-google-map',
				'http://maps.google.com/maps/api/js?sensor=false',
				array( 'jquery' ), IP_Geo_Block::VERSION, TRUE
			);

			// js for option page
			$handle = IP_GEO_BLOCK::PLUGIN_SLUG . '-admin-script';
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
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . IP_GEO_BLOCK::PLUGIN_SLUG ) . '">' . __( 'Settings' ) . '</a>'
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
			$title = __( 'Contribute on GitHub', IP_GEO_BLOCK::TEXT_DOMAIN );
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
			__( 'IP Geo Block', IP_GEO_BLOCK::TEXT_DOMAIN ),
			__( 'IP Geo Block', IP_GEO_BLOCK::TEXT_DOMAIN ),
			'manage_options',
			IP_GEO_BLOCK::PLUGIN_SLUG,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page( $tab = 0 ) {
		$tab = isset( $_GET['tab'] ) ? intval( $_GET['tab'] ) : 0;
		$tab = min( 3, max( 0, $tab ) );
		$option_slug = $this->option_slug[ 1 === $tab ? 'statistics': 'settings' ];
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo IP_GEO_BLOCK::PLUGIN_SLUG; ?>&amp;tab=0" class="nav-tab <?php echo $tab == 0 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings', IP_GEO_BLOCK::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_GEO_BLOCK::PLUGIN_SLUG; ?>&amp;tab=1" class="nav-tab <?php echo $tab == 1 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Statistics', IP_GEO_BLOCK::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_GEO_BLOCK::PLUGIN_SLUG; ?>&amp;tab=2" class="nav-tab <?php echo $tab == 2 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Search', IP_GEO_BLOCK::TEXT_DOMAIN ); ?></a>
		<a href="?page=<?php echo IP_GEO_BLOCK::PLUGIN_SLUG; ?>&amp;tab=3" class="nav-tab <?php echo $tab == 3 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Attribution', IP_GEO_BLOCK::TEXT_DOMAIN ); ?></a>
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
	<p>Some of these services and APIs use GeoLite data created by <a class="ip-geo-block-link" href="http://www.maxmind.com" title="MaxMind - IP Geolocation and Online Fraud Prevention">MaxMind</a>,<br />and some include IP2Location LITE data available from <a class="ip-geo-block-link" href="http://www.ip2location.com" title="IP Address Geolocation to Identify Website Visitor's Geographical Location">IP2Location</a>.</p>
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
		require_once( IP_GEO_BLOCK_PATH . '/classes/class-ip-geo-block-api.php' );

		$tab = isset( $_GET['tab'] ) ? intval( $_GET['tab'] ) : 0;
		$tab = min( 3, max( 0, $tab ) );

		/*========================================*
		 * Settings
		 *========================================*/
		if ( 0 === $tab ) {
			$option_slug = $this->option_slug['settings'];
			$option_name = $this->option_name['settings'];
			$options = get_option( $option_name );

			/**
			 * Register a setting and its sanitization callback.
			 * @link http://codex.wordpress.org/Function_Reference/register_setting
			 *
			 * register_setting( $option_group, $option_name, $sanitize_callback );
			 * @param string $option_group A settings group name.
			 * @param string $option_name The name of an option to sanitize and save.
			 * @param string $sanitize_callback A callback function that sanitizes the option's value.
			 * @since 2.7.0
			 */
			register_setting(
				$option_slug,
				$option_name,
				array( $this, 'sanitize_settings' )
			);

			/**
			 * Add new section to a new page inside the existing page.
			 * @link http://codex.wordpress.org/Function_Reference/add_settings_section
			 *
			 * add_settings_section( $id, $title, $callback, $page );
			 * @param string $id String for use in the 'id' attribute of tags.
			 * @param string $title Title of the section.
			 * @param string $callback Function that fills the section with the desired content.
			 * @param string $page The menu page on which to display this section.
			 * @since 2.7.0
			 */
			/*----------------------------------------*
			 * Geolocation service settings
			 *----------------------------------------*/
			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-provider';
			add_settings_section(
				$section,
				__( 'Geolocation service settings', IP_GEO_BLOCK::TEXT_DOMAIN ),
				NULL,
				$option_slug
			);

			/**
			 * Register a settings field to the settings page and section.
			 * @link http://codex.wordpress.org/Function_Reference/add_settings_field
			 *
			 * add_settings_field( $id, $title, $callback, $page, $section, $args );
			 * @param string $id String for use in the 'id' attribute of tags.
			 * @param string $title Title of the field.
			 * @param string $callback Function that fills the field with the desired inputs.
			 * @param string $page The menu page on which to display this field.
			 * @param string $section The section of the settings page in which to show the box.
			 * @param array $args Additional arguments that are passed to the $callback function.
			 */
			$field = 'providers';
			add_settings_field(
				$option_name . "_$field",
				__( 'Selection and API key settings', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'check-provider',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'providers' => IP_Geo_Block_Provider::get_providers( 'key' ),
					'titles' => IP_Geo_Block_Provider::get_providers( 'type' ),
				)
			);

			/*----------------------------------------*
			 * Submission settings
			 *----------------------------------------*/
			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-submission';
			add_settings_section(
				$section,
				__( 'Submission settings', IP_GEO_BLOCK::TEXT_DOMAIN ),
				NULL,
				$option_slug
			);

			$field = 'comment';
			add_settings_field(
				$option_name . "_$field",
				__( 'Text message on comment form', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'comment-msg',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ]['pos'],
					'list' => array(
						__( 'None',   IP_GEO_BLOCK::TEXT_DOMAIN ) => 0,
						__( 'Top',    IP_GEO_BLOCK::TEXT_DOMAIN ) => 1,
						__( 'Bottom', IP_GEO_BLOCK::TEXT_DOMAIN ) => 2,
					),
					'text' => $options[ $field ]['msg'],
				)
			);

			$field = 'matching_rule';
			add_settings_field(
				$option_name . "_$field",
				__( 'Matching rule', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'list' => array(
						__( 'White list', IP_GEO_BLOCK::TEXT_DOMAIN ) => 0,
						__( 'Black list', IP_GEO_BLOCK::TEXT_DOMAIN ) => 1,
					),
				)
			);

			$field = 'white_list';
			add_settings_field(
				$option_name . "_$field",
				sprintf( __( 'White list %s', IP_GEO_BLOCK::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'after' => '<span>&nbsp;' . __( '(comma separated)', IP_GEO_BLOCK::TEXT_DOMAIN ) . '</span>',
				)
			);

			$field = 'black_list';
			add_settings_field(
				$option_name . "_$field",
				sprintf( __( 'Black list %s', IP_GEO_BLOCK::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'after' => '<span>&nbsp;' . __( '(comma separated)', IP_GEO_BLOCK::TEXT_DOMAIN ) . '</span>',
				)
			);

			$field = 'response_code';
			add_settings_field(
				$option_name . "_$field",
				sprintf( __( 'Response code %s', IP_GEO_BLOCK::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1" target=_blank>RFC 2616</a>)' ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'list' => array(
						'200 OK' => 200,
						'205 Reset Content' => 205,
						'301 Moved Permanently' => 301,
						'302 Found' => 302,
						'307 Temporary Redirect' => 307,
						'400 Bad Request' => 400,
						'403 Forbidden' => 403,
						'404 Not Found' => 404,
						'406 Not Acceptable' => 406,
						'410 Gone' => 410,
						'500 Internal Server Error' => 500,
						'503 Service Unavailable' => 503,
					),
				)
			);

			/*----------------------------------------*
			 * Plugin settings
			 *----------------------------------------*/
			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-others';
			add_settings_section(
				$section,
				__( 'Plugin settings', IP_GEO_BLOCK::TEXT_DOMAIN ),
				NULL,
				$option_slug
			);

			$field = 'save_statistics';
			add_settings_field(
				$option_name . "_$field",
				__( 'Save statistics', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'checkbox',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);

			$field = 'clean_uninstall';
			add_settings_field(
				$option_name . "_$field",
				__( 'Remove settings at uninstallation', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'checkbox',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);
		}

		/*========================================*
		 * Statistics
		 *========================================*/
		else if ( 1 === $tab ) {
			$option_slug = $this->option_slug['statistics'];
			$option_name = $this->option_name['statistics'];
			$options = get_option( $option_name );

			register_setting(
				$option_slug,
				$option_name
			);

			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-statistics';
			add_settings_section(
				$section,
				__( 'Statistics of posts', IP_GEO_BLOCK::TEXT_DOMAIN ),
				NULL,
				$option_slug
			);

			$field = 'passed';
			add_settings_field(
				$option_name . "_$field",
				__( 'Passed', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);

			$field = 'blocked';
			add_settings_field(
				$option_name . "_$field",
				__( 'Blocked', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);

			$field = 'unknown';
			add_settings_field(
				$option_name . "_$field",
				__( 'Unknown (passed)', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);

			$field = 'countries';
			$html = "<ul class=\"${option_slug}-${field}\">";
			foreach ( $options['countries'] as $key => $val ) {
				$html .= sprintf( "<li>%2s:%5d</li>", $key, $val );
			}
			$html .= "</ul>";

			add_settings_field(
				$option_name . "_$field",
				__( 'Blocked by countries', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => $html,
				)
			);

			$field = 'type';
			add_settings_field(
				$option_name . "_$field",
				__( 'Blocked by type of IP address', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => "<table class=\"${option_slug}-${field}\">" .
						"<thead><tr><th>IPv4</th><th>IPv6</th></tr></thead><tbody>" .
						"<td>" . $options['IPv4'] . "</td>" .
						"<td>" . $options['IPv6'] . "</td></tbody></table>",
				)
			);

			$field = 'providers';
			$html = "<table class=\"${option_slug}-${field}\"><thead><tr>";
			$html .= "<th>" . __( 'Provider', IP_GEO_BLOCK::TEXT_DOMAIN ) . "</th>";
			$html .= "<th>" . __( 'Calls', IP_GEO_BLOCK::TEXT_DOMAIN ) . "</th>";
			$html .= "<th>" . __( 'Response [msec]', IP_GEO_BLOCK::TEXT_DOMAIN ) . "</th>";
			$html .= "</tr></thead><tbody>";

			foreach ( $options['providers'] as $key => $val ) {
				$html .= "<tr><td>$key</td>";
				$html .= "<td>" . sprintf( "%5d", $val['count'] ) . "</td><td>";
				$html .= sprintf( "%5d", 1000.0 * $val['time'] / $val['count'] );
				$html .= "</td></tr>";
			}
			$html .= "</tbody></table>";

			add_settings_field(
				$option_name . "_$field",
				__( 'Average response time of each provider', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => $field,
					'value' => $html,
				)
			);

			$field = 'clear_statistics';
			add_settings_field(
				$option_name . "_$field",
				__( 'Clear statistics', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'button',
					'option' => $option_name,
					'field' => $field,
					'value' => __( 'Clear now', IP_GEO_BLOCK::TEXT_DOMAIN ),
					'after' => '<div id="ip-geo-block-loading"></div>',
				)
			);
		}

		/*========================================*
		 * Geolocation
		 *========================================*/
		else if ( 2 === $tab ) {
			$option_slug = $this->option_slug['settings'];
			$option_name = $this->option_name['settings'];
			$options = get_option( $option_name );

			register_setting(
				$option_slug,
				$option_name
			);

			/*----------------------------------------*
			 * Geolocation
			 *----------------------------------------*/
			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-search';
			add_settings_section(
				$section,
				__( 'Search IP address geolocation', IP_GEO_BLOCK::TEXT_DOMAIN ),
				NULL,
				$option_slug
			);

			// make providers list
			$list = array();
			$providers = IP_Geo_Block_Provider::get_providers( 'key' );

			foreach ( $providers as $provider => $key ) {
				if ( ! is_string( $key ) ||
				     ! empty( $options['providers'][ $provider ] ) ) {
					$list += array( $provider => $provider );
				}
			}

			$field = 'service';
			$provider = array_keys( $providers );
			add_settings_field(
				$option_name . "_$field",
				__( 'Geolocation service', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $provider[0],
					'list' => $list,
				)
			);

			$field = 'ip_address';
			add_settings_field(
				$option_name . "_$field",
				__( 'IP address', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => '',
				)
			);

			$field = 'get_location';
			add_settings_field(
				$option_name . "_$field",
				__( 'Find geolocation', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'button',
					'option' => $option_name,
					'field' => $field,
					'value' => __( 'Search now', IP_GEO_BLOCK::TEXT_DOMAIN ),
					'after' => '<div id="ip-geo-block-loading"></div>',
				)
			);
		}

		/*========================================*
		 * Attribution
		 *========================================*/
		else if ( 3 === $tab ) {
			$option_slug = $this->option_slug['settings'];
			$option_name = $this->option_name['settings'];

			register_setting(
				$option_slug,
				$option_name
			);

			$section = IP_GEO_BLOCK::PLUGIN_SLUG . '-attribution';
			add_settings_section(
				$section,
				__( 'Attribution links', IP_GEO_BLOCK::TEXT_DOMAIN ),
				array( $this, 'callback_attribution' ),
				$option_slug
			);

			$field = 'attribution';
			$providers = IP_Geo_Block_Provider::get_providers( 'link' );

			foreach ( $providers as $provider => $key ) {
				add_settings_field(
					$option_name . "_${field}_${provider}",
					$provider,
					array( $this, 'callback_field' ),
					$option_slug,
					$section,
					array(
						'type' => 'html',
						'option' => $option_name,
						'field' => $field,
						'value' => $key,
					)
				);
			}
		}
	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	public function callback_attribution() {
		echo "<p>" . __( 'Thanks for providing these great services for free.', IP_GEO_BLOCK::TEXT_DOMAIN ) . "</p>\n";
		echo "<p>" . __( '(Most browsers will redirect you to each site without referrer when you click the link.)', IP_GEO_BLOCK::TEXT_DOMAIN ) . "</p>\n";
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

		switch ( $args['type'] ) {

			case 'check-provider':
				echo "\n<ul id=\"check-provider\">\n";
				foreach ( $args['providers'] as $key => $val ) {
					$id   = "${args['option']}_providers_$key";
					$name = "${args['option']}[providers][$key]"; ?>
	<li>
		<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo $val; ?>"<?php
			checked(
				( NULL === $val && ! isset( $args['value'][ $key ] ) ) ||
				( FALSE === $val && ! empty( $args['value'][ $key ] ) ) ||
				( is_string( $val ) && ! empty( $args['value'][ $key ] ) )
			); ?> />
		<label for="<?php echo $id; ?>" title="<?php echo $args['titles'][ $key ]; ?>"><?php echo $key; ?></label>
<?php
					if ( is_string( $val ) ) { ?>
		<input type="text" class="regular-text code" name="<?php echo $name; ?>" value="<?php echo esc_attr( isset( $args['value'][ $key ] ) ? $args['value'][ $key ] : '' ); ?>"<?php
			if ( ! isset( $val ) ) disabled( TRUE, TRUE );
		?> />
	</li>
<?php
					}
				}
				echo "</ul>\n";
				break;

			case 'comment-msg':
				echo "\n<select name=\"${name}[pos]\" id=\"${id}_pos\">\n";
				foreach ( $args['list'] as $key => $val ) {
					echo "\t<option value=\"$val\"", selected( $args['value'], $val, FALSE ), ">$key</option>\n";
				}
				echo "</select><br />\n"; ?>
<input type="text" class="regular-text" id="<?php echo $id, '_msg'; ?>" name="<?php echo $name, '[msg]'; ?>" value="<?php echo esc_attr( $args['text'] ); ?>"<?php
	if ( NULL === $args['text'] ) disabled( TRUE, TRUE );
?> />
<?php
				break;

			case 'select':
				echo "\n<select name=\"$name\" id=\"$id\">\n";
				foreach ( $args['list'] as $key => $val ) {
					echo "\t<option value=\"$val\"", selected( $args['value'], $val, FALSE ), ">$key</option>\n";
				}
				echo "</select>\n";
				break;

			case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"<?php
	if ( NULL === $args['value'] ) disabled( TRUE, TRUE );
?> />
<?php
				break; // disabled @since 3.0

			case 'checkbox': ?>
<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="1"<?php checked( esc_attr( $args['value'] ) ); ?> />
<label for="<?php echo $id; ?>"><?php _e( 'Enable', IP_GEO_BLOCK::TEXT_DOMAIN ); ?></label>
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
	 * @link http://core.trac.wordpress.org/browser/tags/3.5/wp-includes/formatting.php
	 */
	private function sanitize_options( $option_name, $input ) {
		$message = __( 'successfully updated', IP_GEO_BLOCK::TEXT_DOMAIN );
		$status = 'updated';

		require_once( IP_GEO_BLOCK_PATH . '/classes/class-ip-geo-block-api.php' );
		$providers = IP_Geo_Block_Provider::get_providers( 'key' );

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
		$output = get_option( $option_name );
		foreach ( $output as $key => $value ) {
			switch( $key ) {
				case 'providers':
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

				case 'comment':
					$output[ $key ]['pos'] = intval( $input[ $key ]['pos'] );
					$output[ $key ]['msg'] = sanitize_text_field( $input[ $key ]['msg'] );
					break;

				case 'matching_rule':
					$output[ $key ] = isset( $input[ $key ] ) ?
						intval( $input[ $key ] ) : 0; // white list
					break;

				case 'white_list':
				case 'black_list':
					$output[ $key ] = isset( $input[ $key ] ) ?
						sanitize_text_field(
							preg_replace( '/[^A-Z,]/', '', strtoupper( $input[ $key ] ) )
						) : '';
					break;

				case 'save_statistics':
				case 'clean_uninstall':
					// pass through to default in case of checkbox
					if ( ! isset( $input[ $key ] ) )
						$value = false;

				default: // text, select
					$output[ $key ] = isset( $input[ $key ] ) ?
						sanitize_text_field( trim( $input[ $key ] ) ) : $value;
					break;
			}
		}

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

		// Check ip address
		else if ( isset( $_POST['provider'] ) ) {
			$ip = $_POST['ip'];
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			     filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				// include utility class
				require_once( IP_GEO_BLOCK_PATH . '/classes/class-ip-geo-block-api.php' );

				// get location
				$provider = $_POST['provider'];
				$name = IP_Geo_Block_API::get_class_name( $provider );

				if ( $name ) {
					$options = get_option( $this->option_name['settings'] );
					global $wp_version;
					$args = apply_filters(
						IP_GEO_BLOCK::PLUGIN_SLUG . '-headers',
						array(
							'timeout' => $options['timeout'],
							'user-agent' => "WordPress/$wp_version; " . IP_GEO_BLOCK::PLUGIN_SLUG . ' ' . IP_Geo_Block::VERSION
						)
					);
					$key = ! empty( $options['providers'][ $provider ] );
					$geo = new $name( $key ? $options['providers'][ $provider ] : NULL );
					$res = $geo->get_location( $ip, $args );
				}

				else {
					$res = array(
						'statusCode' => 'ERROR',
						'statusMessage' => 'Invalid provider.',
					);
				}
			}

			else {
				$res = array(
					'statusCode' => 'ERROR',
					'statusMessage' => 'Invalid IP address.',
				);
			}

			// respond
			wp_send_json( $res ); // @since 3.5.0
		}

		// Clear statistics
		else if ( isset( $_POST['clear'] ) ) {
			update_option(
				$this->option_name['statistics'],
				IP_Geo_Block::get_defaults( 'statistics' )
			);
			wp_send_json( array(
				'refresh' => 'options-general.php?page=ip-geo-block&tab=1',
			) );
		}

		else {
			wp_send_json( array(
				'statusCode' => 'ERROR',
				'statusMessage' => 'Invalid command.',
			) );
		}

		// End of ajax
		die();
	}

}