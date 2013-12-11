<?php
/**
 * Post Geo Lock Admin
 *
 * @package   Post_Geo_Block_Admin
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-post-geo-block.php`
 *
 * @package Post_Geo_Block_Admin
 * @author  tokkonopapa <tokkonopapa@yahoo.com>
 */
class Post_Geo_Block_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Slug of the plugin menu.
	 *
	 * @var      string
	 */
	protected $text_domain;
	protected $plugin_base;
	protected $plugin_slug;
	protected $option_slug = array();
	protected $option_name = array();

	/**
	 * Initialize the plugin by loading admin scripts & styles
	 * and adding a settings page and menu.
	 */
	private function __construct() {

		// Get unique values from public plugin class.
		$plugin = Post_Geo_Block::get_instance();
		$this->text_domain = $plugin->get_text_domain(); // post-geo-block
		$this->plugin_base = $plugin->get_plugin_base(); // post-geo-block/post-geo-block.php
		$this->plugin_slug = $plugin->get_plugin_slug(); // post-geo-block

		foreach ( $plugin->get_option_keys() as $key => $val) {
			$this->option_slug[ $key ] = $val;
			$this->option_name[ $key ] = $val;
		}

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_cssjs' ) );
		add_action( 'wp_ajax_post_geo_block', array( $this, 'post_geo_block_ajax' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_admin_settings' ) );

		// Add an action link pointing to the options page. @since 2.7
		add_filter( 'plugin_action_links_' . $this->plugin_base, array( $this, 'add_action_links' ), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );

		// Check version and compatibility
		if ( version_compare( get_bloginfo( 'version' ), '3.1' ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
		}

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
	 * Display notice
	 *
	 */
	public function admin_notice() {
		$info = $this->get_plugin_info();
		$msg = __( 'You need WordPress 3.1+', $this->text_domain );
		echo "\n<div class=\"error\"><p>", $info['Name'], ": $msg</p></div>\n";
	}

	/**
	 * Get the action name of ajax for nonce
	 *
	 */
	private function get_ajax_action() {
		return $this->plugin_slug . '-ajax-action';
	}

	/**
	 * Register and enqueue admin-specific style sheet and JavaScript.
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_cssjs() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			// css for option page
			wp_enqueue_style( $this->plugin_slug . '-admin-styles',
				plugins_url( 'css/admin.css', __FILE__ ),
				array(), Post_Geo_Block::VERSION
			);

			// js for google map
			wp_enqueue_script( $this->plugin_slug . '-google-map',
				'http://maps.google.com/maps/api/js?sensor=false',
				array( 'jquery' ), Post_Geo_Block::VERSION, TRUE
			);

			// js for option page
			$handle = $this->plugin_slug . '-admin-script';
			wp_enqueue_script( $handle,
				plugins_url( 'js/admin.js', __FILE__ ),
				array( 'jquery' ), Post_Geo_Block::VERSION, TRUE
			);

			// global value for ajax @since r16
			wp_localize_script( $handle,
				'POST_GEO_BLOCK',
				array(
					'action' => 'post_geo_block',
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
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings' ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Add plugin meta links
	 *
	 */
	public function add_plugin_meta_links( $links, $file ) {

		if ( $file === $this->plugin_base ) {
			$title = __( 'Contribute on GitHub', $this->text_domain );
			array_push(
				$links,
				"<a href=\"https://github.com/tokkonopapa/Wordpress-Post-Geo-Block\" title=\"$title\" target=_blank>$title</a>"
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
			__( 'Post Geo Block', $this->text_domain ),
			__( 'Post Geo Block', $this->text_domain ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 */
	public function display_plugin_admin_page( $tab = 0 ) {
		$tab = isset( $_GET['tab'] ) ? intval( $_GET['tab'] ) : 0;
		$tab = min( 2, max( 0, $tab ) );
		$option_slug = $this->option_slug[ 1 === $tab ? 'statistics': 'settings' ];
?>
<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo $this->plugin_slug; ?>&amp;tab=0" class="nav-tab <?php echo $tab == 0 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Settings', $this->text_domain ); ?></a>
		<a href="?page=<?php echo $this->plugin_slug; ?>&amp;tab=1" class="nav-tab <?php echo $tab == 1 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Statistics', $this->text_domain ); ?></a>
		<a href="?page=<?php echo $this->plugin_slug; ?>&amp;tab=2" class="nav-tab <?php echo $tab == 2 ? 'nav-tab-active' : ''; ?>"><?php _e( 'Geolocation', $this->text_domain ); ?></a>
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
	<div id="post-geo-block-info"></div>
	<div id="post-geo-block-map"></div>
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
		require_once( POST_GEO_BLOCK_PATH . '/classes/class-post-geo-block-ip.php' );

		$tab = isset( $_GET['tab'] ) ? intval( $_GET['tab'] ) : 0;
		$tab = min( 2, max( 0, $tab ) );

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
			 * Geolocation provider
			 *----------------------------------------*/
			$section = $this->plugin_slug . '-provider';
			add_settings_section(
				$section,
				__( 'IP address geolocation provider', $this->text_domain ),
				array( $this, 'callback_provider' ),
				$option_slug
			);

			// make providers list
			$list = array();
			$providers = Post_Geo_Block_IP_Setup::get_provider_keys();

			foreach ( $providers as $provider => $key ) {
				if ( isset( $options['api_key'][ $provider ] ) ) {
					$key = $options['api_key'][ $provider ];
				}
				$list += array( $provider => $key );
			}

			// API key for primary provider
			$key = ! empty( $options['provider'] ) &&
				isset( $options['api_key'][ $options['provider'] ] ) ?
				$options['api_key'][ $options['provider'] ] : NULL;
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
			$field = 'provider';
			add_settings_field(
				$option_name . "_$field",
				__( 'Service provider and API key', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select-provider',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'api_key' => $list, // $options['api_key']
					'text_name' => $option_name,
					'text_field' => 'api_key',
					'text_value' => $key, //$options['api_key'][ $options[ $field ] ],
				)
			);

			/*----------------------------------------*
			 * Post options
			 *----------------------------------------*/
			$section = $this->plugin_slug . '-matching';
			add_settings_section(
				$section,
				__( 'Post options', $this->text_domain ),
				array( $this, 'callback_comment' ),
				$option_slug
			);

			$field = 'comment_pos';
			add_settings_field(
				$option_name . "_$field",
				__( 'Text position on comment form', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'list' => array(
						__( 'None',   $this->text_domain ) => 0,
						__( 'Top',    $this->text_domain ) => 1,
						__( 'Bottom', $this->text_domain ) => 2,
					),
				)
			);

			$field = 'comment_msg';
			add_settings_field(
				$option_name . "_$field",
				__( 'Text message on comment form', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
				)
			);

			$field = 'matching_rule';
			add_settings_field(
				$option_name . "_$field",
				__( 'Matching rule', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'list' => array(
						__( 'White list', $this->text_domain ) => 0,
						__( 'Black list', $this->text_domain ) => 1,
					),
				)
			);

			$field = 'white_list';
			add_settings_field(
				$option_name . "_$field",
				__( 'White list', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'after' => '<span>&nbsp;' . sprintf( __( 'comma separated county code %s', $this->text_domain ), '(<a href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ) . '</span>',
				)
			);

			$field = 'black_list';
			add_settings_field(
				$option_name . "_$field",
				__( 'Black list', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'after' => '<span>&nbsp;' . sprintf( __( 'comma separated county code %s', $this->text_domain ), '(<a href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ) . '</span>',
				)
			);

			$field = 'response_code';
			add_settings_field(
				$option_name . "_$field",
				sprintf( __( 'Response code %s', $this->text_domain ), '(<a href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1" target_blank>RFC 2616</a>)' ),
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
			 * Plugin options
			 *----------------------------------------*/
			$section = $this->plugin_slug . '-others';
			add_settings_section(
				$section,
				__( 'Plugin options', $this->text_domain ),
				NULL,
				$option_slug
			);

			$field = 'check_ipv6';
			add_settings_field(
				$option_name . "_$field",
				__( 'Check IP in case of IPV6', $this->text_domain ),
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
				__( 'Remove settings at uninstallation', $this->text_domain ),
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
				$option_name/*,
				array( $this, 'sanitize_statistics' )*/
			);

			$section = $this->plugin_slug . '-statistics';
			add_settings_section(
				$section,
				__( 'Statistics of posts', $this->text_domain ),
				NULL,
				$option_slug
			);

			$field = 'passed';
			add_settings_field(
				$option_name . "_$field",
				__( 'Passed', $this->text_domain ),
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
				__( 'Blocked', $this->text_domain ),
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
				__( 'Unknown (passed)', $this->text_domain ),
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
				__( 'Blocked by countries', $this->text_domain ),
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
				__( 'Type of IP address', $this->text_domain ),
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
			$html .= "<th>" . __( 'Provider', $this->text_domain ) . "</th>";
			$html .= "<th>" . __( 'Calls', $this->text_domain ) . "</th>";
			$html .= "<th>" . __( 'Response [msec]', $this->text_domain ) . "</th>";
			$html .= "</tr></thead><tbody>";

			foreach ( $options['providers'] as $key => $val ) {
				$html .= "<tr><td>$key</td>";
				$html .= "<td>" . sprintf( "%5d", $val['total_count'] ) . "</td><td>";
				$html .= sprintf( "%5d", 1000.0 * $val['total_time'] / $val['total_count'] );
				$html .= "</td></tr>";
			}
			$html .= "</tbody></table>";

			add_settings_field(
				$option_name . "_$field",
				__( 'Response time by providers', $this->text_domain ),
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
				__( 'Clear statistics', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'button',
					'option' => $option_name,
					'field' => $field,
					'value' => __( 'Clear now', $this->text_domain ),
				)
			);
		}

		/*========================================*
		 * Geolocation
		 *========================================*/
		else {
			$option_slug = $this->option_slug['settings'];
			$option_name = $this->option_name['settings'];
			$options = get_option( $option_name );

			register_setting(
				$option_slug,
				$option_name
			);

			/*----------------------------------------*
			 * IP geolocation
			 *----------------------------------------*/
			$section = $this->plugin_slug . '-geolocation';
			add_settings_section(
				$section,
				__( 'Search geolocation of IP address', $this->text_domain ),
				array( $this, 'callback_location' ),
				$option_slug
			);

			// make providers list
			$list = array();
			$providers = Post_Geo_Block_IP_Setup::get_provider_keys();

			foreach ( $providers as $provider => $key ) {
				if ( NULL === $key || ! empty( $options['api_key'][ $provider ] ) ) {
					$list += array( $provider => $provider );
				}
			}

			$field = 'service';
			add_settings_field(
				$option_name . "_$field",
				__( 'Service provider', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'select',
					'option' => $option_name,
					'field' => $field,
					'value' => $options['provider'],
					'list' => $list,
				)
			);

			$field = 'ip_address';
			add_settings_field(
				$option_name . "_$field",
				__( 'IP address', $this->text_domain ),
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
				__( 'Find geolocation', $this->text_domain ),
				array( $this, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'button',
					'option' => $option_name,
					'field' => $field,
					'value' => __( 'Search now', $this->text_domain ),
				)
			);
		}
	}

	/**
	 * Function that fills the section with the desired content.
	 * The function should echo its output.
	 */
	public function callback_provider() {
		// echo "<p>" . __( 'Select geolocation service provider and put API key.', $this->text_domain ) . "</p>";
	}

	public function callback_comment() {
		// echo "<p>" . sprintf( __( 'Select matching rule and put comma separated county code %s.', $this->text_domain ), '<a href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>(ISO 3166-1 alpha-2)</a>' ) . "</p>";
	}

	public function callback_location() {
		// echo "<p>" . __( 'Put IP address and find location.', $this->text_domain ) . "</p>";
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
			case 'select-provider':
				// 1st segment
				$current = esc_attr( $args['value'] );
				echo "\n<select id=\"$id\" name=\"$name\">\n";
				foreach ( $args['api_key'] as $key => $val ) {
					echo "\t<option value=\"$key\"";
					if ( NULL !== $val ) echo " data-api-key=\"" . $val . "\"";
					echo selected( $current, $key, FALSE ), ">$key</option>\n";
				}
				echo "</select>\n";

				// 2nd segment
				$id   = "${args['text_name']}_${args['text_field']}";
				$name = "${args['text_name']}[${args['text_field']}]"; ?>
<span>&nbsp;=&gt;&nbsp;</span>
<input type="text" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo esc_attr( $args['text_value'] ); ?>"<?php if ( NULL === $args['text_value'] ) disabled( TRUE, TRUE ); ?> />
<?php
				break;

			case 'select':
				echo "\n<select name=\"$name\" id=\"$id\">\n";
				foreach ( $args['list'] as $key => $val ) {
					echo "\t<option value=\"$val\"",
						selected( $args['value'], $val, FALSE ),
						">$key</option>\n";
				}
				echo "</select>\n";
				break;

			case 'text': ?>
<input type="text" class="regular-text code" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="<?php echo esc_attr( $args['value'] ); ?>"<?php if ( NULL === $args['value'] ) disabled( TRUE, TRUE ); ?> />
<?php
				break; // disabled @since 3.0

			case 'checkbox': ?>
<input type="checkbox" id="<?php echo $id; ?>" name="<?php echo $name; ?>" value="1"<?php checked( esc_attr( $args['value'] ) ); ?> />
<label for="<?php echo $id; ?>"><?php _e( 'Enable', $this->text_domain ); ?></label>
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
		$message = __( 'successfully updated: ', $this->text_domain );
		$status = 'updated';

		$output = get_option( $option_name );
		$provider = $output['provider'];

		/**
		 * Sanitize a string from user input or from the db
		 *
		 * check for invalid UTF-8,
		 * Convert single < characters to entity,
		 * strip all tags,
		 * remove line breaks, tabs and extra white space,
		 * strip octets.
		 *
		 * @since 2.9.0
		 * @example sanitize_text_field( $str );
		 * @param string $str
		 * @return string
		 */
		foreach ( $output as $key => $value ) {
			switch( $key ) {
				case 'provider':
					$provider = $output[ $key ] = isset( $input[ $key ] ) ?
						sanitize_text_field( $input[ $key ] ) : $value;
					break;

				case 'api_key':
					if ( isset( $input[ $key ] ) )
						$output[ $key ][ $provider ] =
							sanitize_text_field( $input[ $key ] );
					break;

				case 'matching_rule':
					$output[ $key ] = isset( $input[ $key ] ) ?
						intval( $input[ $key ] ) : 0;
					break;

				case 'white_list':
				case 'black_list':
					$output[ $key ] = isset( $input[ $key ] ) ?
						sanitize_text_field(
							preg_replace( '/[^A-Z,]/', '', strtoupper( $input[ $key ] ) )
						) : '';
					break;

				case 'check_ipv6':
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
			$this->option_slug,
			'sanitize_' . $option_name,
			$message . print_r( $output, true ),
			$status
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

	public function sanitize_statistics( $input = array() ) {
		return $this->sanitize_options( $this->option_name['statistics'], $input );
	}

	/**
	 * Ajax callback function
	 *
	 * @link http://codex.wordpress.org/AJAX_in_Plugins
	 * @link http://core.trac.wordpress.org/browser/trunk/wp-admin/admin-ajax.php
	 * @link http://codex.wordpress.org/Function_Reference/check_ajax_referer
	 */
	public function post_geo_block_ajax() {

		// Check request origin, nonce, capability.
		if ( ! check_admin_referer( $this->get_ajax_action(), 'nonce' ) || // @since 2.5
		     ! current_user_can( 'manage_options' ) || empty( $_POST ) ) { // @since 2.0
			// Forbidden
			status_header( 403 ); // @since 2.0.0
		}

		// Check ip address
		else if ( isset( $_POST['provider'] ) ) {
			$ip = $_POST['ip'];
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			     filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {

				// include utility class
				require_once( POST_GEO_BLOCK_PATH . '/classes/class-post-geo-block-ip.php' );

				// get location
				$provider = $_POST['provider'];
				$name = Post_Geo_Block_IP::get_class_name( $provider );

				if ( $name ) {
					$options = get_option( $this->option_name['settings'] );
					$key = isset( $options['api_key'][ $provider ] );
					$ip_geoloc = new $name( $key ? $options['api_key'][ $provider ] : NULL );
					$result = $ip_geoloc->get_location( $ip );
				}

				else {
					$result = array(
						'statusCode' => 'ERROR',
						'statusMessage' => 'Invalid provider.',
					);
				}
			}

			else {
				$result = array(
					'statusCode' => 'ERROR',
					'statusMessage' => 'Invalid IP address.',
				);
			}

			// respond
			@header( 'Content-Type: application/json;' .
				' charset=' . get_option( 'blog_charset' ) );
			echo json_encode( $result );
		}

		// Clear statistics
		else if ( isset( $_POST['clear'] ) ) {
			update_option( $this->option_name['statistics'],
				Post_Geo_Block::get_defaults( 'statistics' ) );

			$result = array(
				'refresh' => 'options-general.php?page=post-geo-block&tab=1',
			);

			@header( 'Content-Type: application/json;' .
				' charset=' . get_option( 'blog_charset' ) );
			echo json_encode( $result );
		}

		else {
			$result = array(
				'statusCode' => 'ERROR',
				'statusMessage' => 'Invalid command.',
			);

			@header( 'Content-Type: application/json;' .
				' charset=' . get_option( 'blog_charset' ) );
			echo json_encode( $result );
		}

		// End of ajax
		die();
	}

}
