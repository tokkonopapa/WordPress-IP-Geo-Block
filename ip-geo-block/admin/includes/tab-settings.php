<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );
require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context ) {
		$plugin_slug = IP_Geo_Block::PLUGIN_SLUG;         // 'ip-geo-block'
		$option_slug = $context->option_slug['settings']; // 'ip-geo-block-settings'
		$option_name = $context->option_name['settings']; // 'ip_geo_block_settings'
		$options = IP_Geo_Block::get_option( 'settings' );

		// Get the country code
		$key = IP_Geo_Block::get_geolocation();

		/**
		 * Register a setting and its sanitization callback.
		 * @link http://codex.wordpress.org/Function_Reference/register_setting
		 *
		 * register_setting( $option_group, $option_name, $sanitize_callback );
		 * @param string $option_group A settings group name.
		 * @param string $option_name The name of an option to sanitize and save.
		 * @param string $sanitize_callback A callback function that sanitizes option values.
		 * @since 2.7.0
		 */
		register_setting(
			$option_slug,
			$option_name,
			array( $context, 'validate_settings' )
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
		 * Validation rule settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-validation-rule';
		add_settings_section(
			$section,
			__( 'Validation rule settings', IP_Geo_Block::TEXT_DOMAIN ),
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
		$field = 'ip_country';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8220;Scan your country code&#8221;.">Your IP address / Country</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => esc_html( $key['ip'] . ' / ' . ( $key['code'] ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'UNKNOWN', IP_Geo_Block::TEXT_DOMAIN ) ) ),
				'after' => '&nbsp;<a class="button button-secondary" id="ip-geo-block-scan-code" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ) . '" href="javascript:void(0)">' . __( 'Scan your country code', IP_Geo_Block::TEXT_DOMAIN ) . '</a><div id="ip-geo-block-scanning"></div>',
			)
		);

		// If the matching rule is not initialized, then add a caution
		$list = array(
			-1 => NULL,
			 0 => __( 'Whitelist', IP_Geo_Block::TEXT_DOMAIN ),
			 1 => __( 'Blacklist', IP_Geo_Block::TEXT_DOMAIN ),
		);

		$comma = '<span class="ip-geo-block-sup">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>';
		$desc = __( 'Please select either &#8220;Whitelist&#8221; or &#8220;Blacklist&#8221;.', IP_Geo_Block::TEXT_DOMAIN );
		$dfn = array(
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. All the countries will be blocked in case you put &#8220;XX&#8221; only.">Whitelist of country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) . '<br/>(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)',
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. Please consider to include &#8220;ZZ&#8221; which means UNKNOWN country.">Blacklist of country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) . '<br/>(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)',
		);

		// Matching rule
		$field = 'matching_rule';
		add_settings_field(
			$option_name.'_'.$field,
			'<dfn title="' . $desc . '">' . __( 'Matching rule', IP_Geo_Block::TEXT_DOMAIN ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'list' => $list,
				'desc' => array(
					-1 => $desc,
					 0 => __( 'A request from which the country code or IP address is <strong>NOT</strong> in the whitelist will be blocked.', IP_Geo_Block::TEXT_DOMAIN ),
					 1 => __( 'A request from which the country code or IP address is in the blacklist will be blocked.', IP_Geo_Block::TEXT_DOMAIN ),
				),
				'before' => '<input type="hidden" name="ip_geo_block_settings[version]" value="' . esc_html( $options['version'] ) . '" />',
				'after' => '<div class="ip-geo-block-desc"></div>',
			)
		);

		// Country code for matching rule (ISO 3166-1 alpha-2)
		$field = 'white_list';
		add_settings_field(
			$option_name.'_'.$field,
			$dfn[0],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'display' => $options['matching_rule'] !== 1,
				'after' => $comma,
			)
		);

		$field = 'black_list';
		add_settings_field(
			$option_name.'_'.$field,
			$dfn[1],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'display' => $options['matching_rule'] !== 0,
				'after' => $comma,
			)
		);

		$desc = '<span class="ip-geo-block-sup">' . __( '(comma or RET separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>';

		// White list of extra IP addresses prior to country code (CIDR)
		$field = 'extra_ips';
		$key = 'white_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="e.g. &#8220;192.0.64.0/18&#8221; for Jetpack server, &#8220;69.46.36.0/27&#8221; for WordFence server">Whitelist of extra IP addresses prior to country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) .
			' (<a class="ip-geo-block-link" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia" target=_blank>CIDR</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $desc,
			)
		);

		// Black list of extra IP addresses prior to country code (CIDR)
		$key = 'black_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="Server level access control is recommended (e.g. .htaccess).">Blacklist of extra IP addresses prior to country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) .
			' (<a class="ip-geo-block-link" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia" target=_blank>CIDR</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $desc,
			)
		);

		// $_SERVER keys to retrieve extra IP addresses
		$field = 'validation';
		$key = 'proxy';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="e.g. HTTP_X_FORWARDED_FOR">$_SERVER keys to retrieve extra IP addresses</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma,
			)
		);

		// Bad signatures
		$field = 'signature';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="It validates malicious signatures independently of &#8220;Block by country&#8221; and &#8220;Prevent Zero-day Exploit&#8221; for the target &#8220;Admin area&#8221;, &#8220;Admin ajax/post&#8221;, &#8220;Plugins area&#8221; and &#8220;Themes area&#8221;.">Bad signatures in query</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'after' => $desc,
			)
		);

		// Response code (RFC 2616)
		$field = 'response_code';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( __( 'Response code %s', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1" target=_blank>RFC 2616</a>)' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'list' => array(
					200 => '200 OK',
					205 => '205 Reset Content',
					301 => '301 Moved Permanently',
					302 => '302 Found',
					307 => '307 Temporary Redirect',
					400 => '400 Bad Request',
					403 => '403 Forbidden',
					404 => '404 Not Found',
					406 => '406 Not Acceptable',
					410 => '410 Gone',
					500 => '500 Internal Server Error',
					503 => '503 Service Unavailable',
				),
			)
		);

		// Max number of failed login attempts per IP address
		$field = 'login_fails';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Applied to &#8220;XML-RPC&#8221; and &#8220;Login form&#8221;. Lockout period is defined as expiration time at &#8220;Cache settings&#8221;.">Max number of failed login attempts per IP address</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'list' => array(
					 0 =>  0,
					 1 =>  1,
					 3 =>  3,
					 5 =>  5,
					 7 =>  7,
					10 => 10,
				),
			)
		);

		/*----------------------------------------*
		 * Validation target settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-validation-target';
		add_settings_section(
			$section,
			__( 'Validation target settings', IP_Geo_Block::TEXT_DOMAIN ),
			array( __CLASS__, 'note_target' ),
			$option_slug
		);

		// same as in tab-accesslog.php
		$dfn = __( '<dfn title="Validate request to %s.">%s</dfn>', IP_Geo_Block::TEXT_DOMAIN );
		$target = array(
			'comment' => sprintf( $dfn, 'wp-comments-post.php', __( 'Comment post', IP_Geo_Block::TEXT_DOMAIN ) ),
			'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',           __( 'XML-RPC',      IP_Geo_Block::TEXT_DOMAIN ) ),
			'login'   => sprintf( $dfn, 'wp-login.php',         __( 'Login form',   IP_Geo_Block::TEXT_DOMAIN ) ),
			'admin'   => sprintf( $dfn, 'wp-admin/*.php',       __( 'Admin area',   IP_Geo_Block::TEXT_DOMAIN ) ),
		);

		// Comment post
		$field = 'validation';
		$key = 'comment';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$target[ $key ],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'text' => __( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ),
			)
		);

		// XML-RPC
		$key = 'xmlrpc';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$target[ $key ],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => array(
					0 => __( 'Disable',          IP_Geo_Block::TEXT_DOMAIN ),
					1 => __( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ),
					2 => __( 'Completely close', IP_Geo_Block::TEXT_DOMAIN ),
				),
			)
		);

		// Login form
		$key = 'login';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$target[ $key ],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => array(
					0 => __( 'Disable', IP_Geo_Block::TEXT_DOMAIN ),
					2 => __( 'Block by country (register, lost password)', IP_Geo_Block::TEXT_DOMAIN ),
					1 => __( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ),
				),
				'desc' => array(
					2 => __( 'Registered users can login as membership from anywhere, but the request of new user registration and lost password is blocked by the country code.', IP_Geo_Block::TEXT_DOMAIN ),
				),
				'after' => '<div class="ip-geo-block-desc"></div>',
			)
		);

		$list = array(
			1 => __( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ),
			2 => __( 'Prevent Zero-day Exploit', IP_Geo_Block::TEXT_DOMAIN ),
		);

		$desc = array(
			1 => __( 'It will block a request related to the services for both public facing pages and the dashboard.', IP_Geo_Block::TEXT_DOMAIN ),
			2 => __( 'Regardless of the country code, it will block a malicious request related to the services only for the dashboard.', IP_Geo_Block::TEXT_DOMAIN ),
		);

		// Admin area
		$key = 'admin';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$target[ $key ],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkboxes',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => $list,
				'desc' => $desc,
			)
		);

		// Admin ajax/post
		$key = 'ajax';
		$val = esc_html( substr( IP_Geo_Block::$wp_dirs['admin'], 1 ) );
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'admin-(ajax|post).php', __( 'Admin ajax/post', IP_Geo_Block::TEXT_DOMAIN ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkboxes',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => $list,
				'desc' => $desc,
			)
		);

		array_unshift( $list, __( 'Disable', IP_Geo_Block::TEXT_DOMAIN ) );
		$desc = array(
			__( 'Regardless of the country code, it will block a malicious request to <code>%s&hellip;/*.php</code>.', IP_Geo_Block::TEXT_DOMAIN ),
			__( 'It configures &#8220%s&#8221 to validate a request to the PHP file which does not load WordPress core.', IP_Geo_Block::TEXT_DOMAIN ),
			__( '<dfn title="Select the item which would cause undesired blocking in order to exclude it from the validation target. The &#8220;*&#8221; indicates &#8220;active&#8221;.">Exceptions</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		);

		// Set rewrite condition
		$options['rewrite'] = IP_Geo_Block_Admin_Rewrite::check_rewrite_all();

		// Get all the plugins
		$exception = "\n<ul class='ip_geo_block_settings_exception ip-geo-block-dropup'>" . $desc[2] . "<li style='display:none'><ul>\n";
		$installed = get_plugins(); // @since 1.5.0
		$activated = get_site_option( 'active_sitewide_plugins' ); // @since 2.8.0
		! is_array( $activated ) and $activated = array();
		$activated = array_keys( $activated );
		$activated = array_merge( $activated, get_option( 'active_plugins' ) );
		unset( $installed[ IP_GEO_BLOCK_BASE ] ); // exclude myself

		// Make a list of installed plugins
		foreach ( $installed as $key => $val ) {
			$active = in_array( $key, $activated, TRUE );
			$key = explode( '/', $key, 2 );
			$key = esc_attr( $key[0] );
			$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_exception_plugins_' . $key . '"'
				. ' name="ip_geo_block_settings[exception][plugins][' . $key . ']"'
				. ' value="1" ' . checked( isset( $options['exception']['plugins'][ $key ] ), TRUE, FALSE ) . ' />'
				. '<label for="ip_geo_block_settings_exception_plugins_' . $key . '">'
				. ($active ? '* ' : '') . esc_html( $val['Name'] ) . "</label></li>\n";
		}
		$exception .= "</ul>\n";

		// Plugins area
		$key = 'plugins';
		$val = esc_html( substr( IP_Geo_Block::$wp_dirs[ $key ], 1 ) );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_' . $key
			. '" value="1"' . checked( $options['rewrite'][ $key ], TRUE, FALSE )
			. ' name="ip_geo_block_settings[rewrite]['.$key.']" '
			. disabled( $options['rewrite'][ $key ], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_'.$key.'"><dfn title="'
			. sprintf( $desc[1], $val . '.htaccess' )
			. '">' . __( 'Force to load WP core', IP_Geo_Block::TEXT_DOMAIN )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Plugins area', IP_Geo_Block::TEXT_DOMAIN ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => $list,
				'desc' => array(
					2 => sprintf( $desc[0], $val ),
				),
				'before' => $tmp,
				'after' => '<div class="ip-geo-block-desc"></div>' . $exception,
			)
		);

		// Get all the themes
		$exception = "\n<ul class='ip_geo_block_settings_exception ip-geo-block-dropup'>" . $desc[2] . "<li style='display:none'><ul>\n";
		$installed = wp_get_themes( NULL ); // @since 3.4.0
		$activated = wp_get_theme(); // @since 3.4.0

		// List of installed themes
		foreach ( $installed as $key => $val ) {
			$key = esc_attr( $key );
			$val = $val->get( 'Name' );
			$active = ( $val === $activated->get( 'Name' ) );
			$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_exception_themes_' . $key . '"'
				. ' name="ip_geo_block_settings[exception][themes][' . $key . ']"'
				. ' value="1" ' . checked( isset( $options['exception']['themes'][ $key ] ), TRUE, FALSE ) . ' />'
				. '<label for="ip_geo_block_settings_exception_themes_' . $key . '">'
				. ($active ? '* ' : '') . esc_html( $val ) . "</label></li>\n";
		}
		$exception .= "</ul></li></ul>\n";

		// Themes area
		$key = 'themes';
		$val = esc_html( substr( IP_Geo_Block::$wp_dirs[ $key ], 1 ) );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_' . $key
			. '" value="1"' . checked( $options['rewrite'][ $key ], TRUE, FALSE )
			. ' name="ip_geo_block_settings[rewrite]['.$key.']" '
			. disabled( $options['rewrite'][ $key ], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_'.$key.'"><dfn title="'
			. sprintf( $desc[1], $val . '.htaccess' )
			. '">' . __( 'Force to load WP core', IP_Geo_Block::TEXT_DOMAIN )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Themes area', IP_Geo_Block::TEXT_DOMAIN ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => $list,
				'desc' => array(
					2 => sprintf( $desc[0], $val ),
				),
				'before' => $tmp,
				'after' => '<div class="ip-geo-block-desc"></div>' . $exception,
			)
		);

		/*----------------------------------------*
		 * Geolocation service settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-provider';
		add_settings_section(
			$section,
			__( 'Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ),
			array( __CLASS__, 'note_services' ),
			$option_slug
		);

		// API selection and key settings
		$field = 'providers';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Cache and local database are scaned at the top priority.">API selection and key settings</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
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
		 * Local database settings
		 *----------------------------------------*/
		// higher priority order
		$providers = IP_Geo_Block_Provider::get_addons(); 
		if ( empty( $providers ) ) {
			$context->add_admin_notice( 'error',
				sprintf(
					__( 'Please download <a href="https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip" title="Download the contents of tokkonopapa/WordPress-IP-Geo-API as a zip file">ZIP file</a> from <a href="https://github.com/tokkonopapa/WordPress-IP-Geo-API" title="tokkonopapa/WordPress-IP-Geo-API - GitHub">WordPress-IP-Geo-API</a> and upload <code>ip-geo-api</code> to <code>%s</code> with write permission.', IP_Geo_Block::TEXT_DOMAIN ),
					apply_filters( 'ip-geo-block-api-dir', dirname( $options['api_dir'] ) )
				)
			);
		}

		$section = $plugin_slug . '-database';
		add_settings_section(
			$section,
			__( 'Local database settings', IP_Geo_Block::TEXT_DOMAIN ),
			NULL,
			$option_slug
		);

		// Local DBs for each API
		foreach ( $providers as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
				$geo->add_settings_field(
					$provider,
					$section,
					$option_slug,
					$option_name,
					$options,
					array( $context, 'callback_field' ),
					__( 'database', IP_Geo_Block::TEXT_DOMAIN ),
					__( 'Last update: %s', IP_Geo_Block::TEXT_DOMAIN )
				);
			}
		}

		// Auto updating (once a month)
		$field = 'update';
		add_settings_field(
			$option_name.'_'.$field.'_auto',
			__( 'Auto updating (once a month)', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'auto',
				'value' => $options[ $field ]['auto'],
				'disabled' => empty( $providers ),
			)
		);

		// Download database
		add_settings_field(
			$option_name.'_'.$field.'_download',
			__( 'Download database', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Download now', IP_Geo_Block::TEXT_DOMAIN ),
				'disabled' => empty( $providers ),
				'after' => '<div id="ip-geo-block-download"></div>',
			)
		);

		/*----------------------------------------*
		 * Record settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-recording';
		add_settings_section(
			$section,
			__( 'Record settings', IP_Geo_Block::TEXT_DOMAIN ),
			NULL,
			$option_slug
		);

		// Record validation statistics
		$field = 'save_statistics';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Record validation statistics', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
			)
		);

		// Record validation logs
		$field = 'validation';
		add_settings_field(
			$option_name.'_'.$field.'_reclogs',
			__( 'Record validation logs', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'reclogs',
				'value' => $options[ $field ]['reclogs'],
				'list' => array(
					0 => __( 'Disable',              IP_Geo_Block::TEXT_DOMAIN ),
					1 => __( 'Only when blocked',    IP_Geo_Block::TEXT_DOMAIN ),
					2 => __( 'Only when passed',     IP_Geo_Block::TEXT_DOMAIN ),
					3 => __( 'Unauthenticated user', IP_Geo_Block::TEXT_DOMAIN ),
					4 => __( 'Authenticated user',   IP_Geo_Block::TEXT_DOMAIN ),
					5 => __( 'All of validation',    IP_Geo_Block::TEXT_DOMAIN ),
				),
			)
		);

		// $_POST keys to be recorded with their values in logs
		add_settings_field(
			$option_name.'_'.$field.'_postkey',
			__( '<dfn title="e.g. action, comment, log, pwd">$_POST keys to be recorded with their values in logs</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'postkey',
				'value' => $options[ $field ]['postkey'],
				'after' => $comma,
			)
		);

		// Anonymize IP address
		$field = 'anonymize';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="e.g. 123.456.789.***">Anonymize IP address</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'value' => ! empty( $options[ $field ] ) ? TRUE : FALSE,
			)
		);

		/*----------------------------------------*
		 * Cache settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-cache';
		add_settings_section(
			$section,
			__( 'Cache settings', IP_Geo_Block::TEXT_DOMAIN ),
			NULL,
			$option_slug
		);

		// Number of entries
		$field = 'cache_hold';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Number of entries', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
			)
		);

		// Expiration time [sec]
		$field = 'cache_time';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( __( '<dfn title="If user authentication fails consecutively %d times, subsequent login will also be prohibited for this period.">Expiration time [sec]</dfn>', IP_Geo_Block::TEXT_DOMAIN ), (int)$options['login_fails'] ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
			)
		);

		/*----------------------------------------*
		 * Submission settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-submission';
		add_settings_section(
			$section,
			__( 'Submission settings', IP_Geo_Block::TEXT_DOMAIN ),
			NULL,
			$option_slug
		);

		$val = $GLOBALS['allowedtags'];
		unset( $val['blockquote'] );

		// Message on comment form
		$field = 'comment';
		add_settings_field(
			$option_name.'_'.$field,
			'<dfn title="' . __( 'The whole will be wrapped by &lt;p&gt; tag. Allowed tags: ', IP_Geo_Block::TEXT_DOMAIN ) . implode( ', ', array_keys( $val ) ) . '">' . __( 'Message on comment form', IP_Geo_Block::TEXT_DOMAIN ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select-text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'pos',
				'txt-field' => 'msg',
				'value' => $options[ $field ]['pos'],
				'list' => array(
					0 => __( 'None',   IP_Geo_Block::TEXT_DOMAIN ),
					1 => __( 'Top',    IP_Geo_Block::TEXT_DOMAIN ),
					2 => __( 'Bottom', IP_Geo_Block::TEXT_DOMAIN ),
				),
				'text' => $options[ $field ]['msg'], // sanitized at 'select-text'
			)
		);

		/*----------------------------------------*
		 * Plugin settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-others';
		add_settings_section(
			$section,
			__( 'Plugin settings', IP_Geo_Block::TEXT_DOMAIN ),
			NULL,
			$option_slug
		);

		$desc = __( 'You need to click the &#8220;Save Changes&#8221; button for imported settings to take effect.', IP_Geo_Block::TEXT_DOMAIN );

		// Export / Import settings
		$field = 'export-import';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( '<dfn title="%s">' . __( 'Export / Import settings', IP_Geo_Block::TEXT_DOMAIN ) . '</dfn>', $desc ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button button-secondary" id="ip-geo-block-export" title="' . __( 'Export to the local file',   IP_Geo_Block::TEXT_DOMAIN ) . '" href="javascript:void(0)">'. __( 'Export settings', IP_Geo_Block::TEXT_DOMAIN ) . '</a>&nbsp;' .
					'<a class="button button-secondary" id="ip-geo-block-import" title="' . __( 'Import from the local file', IP_Geo_Block::TEXT_DOMAIN ) . '" href="javascript:void(0)">'. __( 'Import settings', IP_Geo_Block::TEXT_DOMAIN ) . '</a>',
				'after' => '<div id="ip-geo-block-export-import"></div>',
			)
		);

		// Pre-defined settings
		$field = 'pre-defined';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( '<dfn title="%s">' . __( 'Import pre-defined settings', IP_Geo_Block::TEXT_DOMAIN ) . '</dfn>', $desc ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button button-secondary" id="ip-geo-block-default"   title="' . __( 'Import the default settings to revert to the &#8220;Right after installing&#8221; state', IP_Geo_Block::TEXT_DOMAIN ) . '" href="javascript:void(0)">' . __( 'Default settings', IP_Geo_Block::TEXT_DOMAIN ) . '</a>&nbsp;' .
					'<a class="button button-secondary" id="ip-geo-block-preferred" title="' . __( 'Import the preferred settings mainly for the &#8220;Validation target settings&#8221;',   IP_Geo_Block::TEXT_DOMAIN ) . '" href="javascript:void(0)">' . __( 'Best practice',    IP_Geo_Block::TEXT_DOMAIN ) . '</a>',
				'after' => '<div id="ip-geo-block-pre-defined"></div>',
			)
		);

		// Remove all settings at uninstallation
		$field = 'clean_uninstall';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Remove all settings at uninstallation', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):

		// Manipulate DB table for validation logs
		$field = 'delete_table';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Delete DB table for validation logs', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Delete now', IP_Geo_Block::TEXT_DOMAIN ),
				'after' => '<div id="ip-geo-block-delete-table"></div>',
			)
		);

		$field = 'create_table';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Create DB table for validation logs', IP_Geo_Block::TEXT_DOMAIN ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Create now', IP_Geo_Block::TEXT_DOMAIN ),
				'after' => '<div id="ip-geo-block-create-table"></div>',
			)
		);

endif;

	}

	/**
	 * Subsidiary note
	 *
	 */
	public static function note_target() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'To enhance the protection ability, please refer to &#8220;<a href="http://www.ipgeoblock.com/codex/the-best-practice-of-target-settings.html" title="Prevent exposure of wp-config.php | IP Geo Block">The best practice of target settings</a>&#8221;.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
				'<li>', __( 'If you have any troubles with these, please open an issue at <a class="ip-geo-block-link" href="http://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block" target=_blank>support forum</a>.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_services() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __('While Maxmind and IP2Location will fetch the local database, others will pass an IP address to the APIs via HTTP.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
				'<li>', __('Please select the appropriate APIs to fit the privacy law in your country.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
			'</ul>', "\n";
	}

}