<?php
require_once ABSPATH           . 'wp-admin/includes/plugin.php'; // for get_plugins()
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php';

class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME; // 'ip-geo-block'

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
			$option_slug = IP_Geo_Block::PLUGIN_NAME, // 'ip-geo-block'
			$option_name = IP_Geo_Block::OPTION_NAME, // 'ip_geo_block_settings'
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
			__( 'Validation rule settings', 'ip-geo-block' ),
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

		// Get the country code of client
		$key = IP_Geo_Block::get_geolocation( $val = IP_Geo_Block::get_ip_address() );

		$field = 'ip_client';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8220;Scan country code&#8221;.">Your IP address / Country</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => '<span class="ip-geo-block-ip-addr">' . esc_html( $key['ip'] . ' / ' . ( $key['code'] && isset( $key['provider'] ) ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'UNKNOWN', 'ip-geo-block' ) ) ) . '</span>',
				'after' => '&nbsp;<a class="button button-secondary" id="ip-geo-block-scan-' . $field . '" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', 'ip-geo-block' ) . '" href="javascript:void(0)">' . __( 'Scan country code', 'ip-geo-block' ) . '</a><div id="ip-geo-block-scanning-' . $field . '"></div>',
			)
		);

if ( $key = IP_Geo_Block_Util::get_server_ip() && $key !== $val && ! IP_Geo_Block_Util::is_private_ip( $key ) ):
		// Get the country code of server
		$key = IP_Geo_Block::get_geolocation( $_SERVER['SERVER_ADDR'] );

		$field = 'ip_server';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8220;Scan country code&#8221;.">Server IP address / Country</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => '<span class="ip-geo-block-ip-addr">' . esc_html( $key['ip'] . ' / ' . ( $key['code'] && isset( $key['provider'] ) ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'UNKNOWN', 'ip-geo-block' ) ) ) . '</span>',
				'after' => '&nbsp;<a class="button button-secondary" id="ip-geo-block-scan-' . $field . '" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', 'ip-geo-block' ) . '" href="javascript:void(0)">' . __( 'Scan country code', 'ip-geo-block' ) . '</a><div id="ip-geo-block-scanning-' . $field . '"></div>',
			)
		);
endif;

		// If the matching rule is not initialized, then add a caution
		$rule = array(
			-1 => NULL,
			 0 => __( 'Whitelist', 'ip-geo-block' ),
			 1 => __( 'Blacklist', 'ip-geo-block' ),
		);

		$rule_desc = array(
			__( 'Please select either &#8220;Whitelist&#8221; or &#8220;Blacklist&#8221;.', 'ip-geo-block' ),
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. The special code &#8220;XX&#8221; is assigned as private IP address including localhost. And &#8220;ZZ&#8221; is for unknown IP address (i.e. not in the geolocation databases). Please use &#8220;YY&#8221; if you need the code that does not correspond to any of the countries.">Whitelist of country code</dfn>', 'ip-geo-block' ) . '<br />(<a rel="noreferrer" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia">ISO 3166-1 alpha-2</a>)',
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. The special code &#8220;XX&#8221; is assigned as private IP address including localhost. And &#8220;ZZ&#8221; is for unknown IP address (i.e. not in the geolocation databases). Please use &#8220;YY&#8221; if you need the code that does not correspond to any of the countries.">Blacklist of country code</dfn>', 'ip-geo-block' ) . '<br />(<a rel="noreferrer" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia">ISO 3166-1 alpha-2</a>)',
		);

		$comma = array(
			'<span class="ip-geo-block-sup">' . __( '(comma separated)', 'ip-geo-block' ) . '</span>',
			'<span class="ip-geo-block-sup">' . __( '(comma or RET separated)', 'ip-geo-block' ) . '</span>',
		);

		// Matching rule
		$field = 'matching_rule';
		add_settings_field(
			$option_name.'_'.$field,
			'<dfn title="' . $rule_desc[0] . '">' . __( 'Matching rule', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'list' => $rule,
				'desc' => array(
					-1 => $rule_desc[0],
					 0 => __( 'A request from which the country code or IP address is <strong>NOT</strong> in the whitelist will be blocked.', 'ip-geo-block' ),
					 1 => __( 'A request from which the country code or IP address is in the blacklist will be blocked.', 'ip-geo-block' ),
				),
				'before' => '<input type="hidden" name="ip_geo_block_settings[version]" value="' . esc_html( $options['version'] ) . '" />',
			)
		);

		// Country code for matching rule (ISO 3166-1 alpha-2)
		$field = 'white_list';
		add_settings_field(
			$option_name.'_'.$field,
			$rule_desc[1],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'after' => $comma[0],
				'class' => $options['matching_rule'] == 0 ? '' : 'ip-geo-block-hide',
			)
		);

		$field = 'black_list';
		add_settings_field(
			$option_name.'_'.$field,
			$rule_desc[2],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'after' => $comma[0],
				'class' => $options['matching_rule'] == 1 ? '' : 'ip-geo-block-hide',
			)
		);

		// Use AS number
		$field = 'Maxmind';
		$key = 'use_asn';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="It enables utilizing &#8220;AS number&#8221; in the &#8220;Whitelist/Blacklist of extra IP addresses&#8221; to specify a group of IP networks.">Use Autonomous System Number</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => 1 === (int)$options[ $field ][ $key ],
				'after' => '<p>[ <a rel="noreferrer" href="//ipinfo.io/countries/" title="Country IP Address Report - ipinfo.io">ASN by country</a> ] [ <a rel="noreferrer" href="https://www.ultratools.com/tools/asnInfo" title="ASN Lookup Tool | UltraTools">ASN lookup</a> ]</p>',
			)
		);

		// White list of extra IP addresses prior to country code (CIDR, ASN)
		$field = 'extra_ips';
		$key = 'white_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="e.g. &#8220;192.0.64.0/18&#8221; for Jetpack server, &#8220;69.46.36.0/27&#8221; for WordFence server or &#8220;AS32934&#8221; for Facebook.">Whitelist of extra IP addresses prior to country code</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia">CIDR</a>'.
			', <a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma[1],
			)
		);

		// Black list of extra IP addresses prior to country code (CIDR, ASN)
		$key = 'black_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="Server level access control is recommended (e.g. .htaccess).">Blacklist of extra IP addresses prior to country code</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia">CIDR</a>'.
			', <a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma[1],
			)
		);

		// $_SERVER keys to retrieve extra IP addresses
		$field = 'validation';
		$key = 'proxy';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="If your server is placed behind the proxy server or the load balancing server, you need to put the appropriate key such as &#8220;HTTP_X_FORWARDED_FOR&#8221;, &#8220;HTTP_X_REAL_IP&#8221; or something like that to retrieve the client IP address.">$_SERVER keys to retrieve extra IP addresses</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'placeholder' => IP_Geo_Block_Util::get_proxy_var(),
				'after' => $comma[0],
			)
		);

		// Bad signatures
		$field = 'signature';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="It validates malicious signatures independently of &#8220;Block by country&#8221; and &#8220;Prevent Zero-day Exploit&#8221; for the target &#8220;Admin area&#8221;, &#8220;Admin ajax/post&#8221;, &#8220;Plugins area&#8221; and &#8220;Themes area&#8221;.">Bad signatures in query</dfn> <nobr>(<a class="ip-geo-block-cycle" id="ip-geo-block-decode" title="When you find ugly character string in the text area, please click to restore."><span></span></a>)</nobr>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'after' => $comma[1],
			)
		);

		// Prevent malicious upload - white list of file extention and MIME type
		$list = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Select allowed MIME type.">Whitelist of allowed MIME type</dfn>', 'ip-geo-block' ) . "<a class=\"ip-geo-block-cycle ip-geo-block-hide\"><span></span></a>\n<li  class=\"ip-geo-block-hide\"><ul class=\"ip-geo-block-float\">\n";

		// get_allowed_mime_types() in wp-includes/functions.php @since 2.8.6
		foreach ( IP_Geo_Block_Util::get_allowed_mime_types() as $key => $val ) {
			$key = esc_attr( $key );
			$val = esc_attr( $val );
			$list .= '<li><input type="checkbox" id="ip_geo_block_settings_mimetype_white_list' . $key . '" name="ip_geo_block_settings[mimetype][white_list][' . $key . ']" value="' . $val . '"' . checked( isset( $options['mimetype']['white_list'][ $key ] ), TRUE, FALSE ) . '><label for="ip_geo_block_settings_mimetype_white_list' . $key . '"><dfn title="' . $val . '">' . $key . '</dfn></label></li>' . "\n";
		}
		$list .= "</ul></li></ul>\n";

		// Prevent malicious upload - black list of file extension
		$list .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Put forbidden file extensions.">Blacklist of forbidden file extensions</dfn>', 'ip-geo-block' ) . "\n" . '<li class="ip-geo-block-hide"><ul><li><input type="text" class="regular-text code" id="ip_geo_block_settings_mimetype_black_list" name="ip_geo_block_settings[mimetype][black_list]" value="' . esc_attr( $options['mimetype']['black_list'] ) . '"/></li>';
		$list .= "</ul></li></ul>\n";

		$field = 'validation';
		$key = 'mimetype';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="It restricts the file types on upload to block malware and backdoor via both back-end and front-end. Please consider to select &#8220;mu-plugins&#8221; (ip-geo-block-mu.php) at &#8220;Validation timing&#8221; so that other staff would not fetch uploaded files before this validation.">Prevent malicious file uploading</dfn>', 'ip-geo-block' ),
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
					0 => __( 'Disable',                         'ip-geo-block' ),
					1 => __( 'Verify capability and MIME type', 'ip-geo-block' ),
					2 => __( 'Verify only file extension',      'ip-geo-block' ),
				),
				'after' => $list,
			)
		);

		// Response code (RFC 2616)
		$field = 'response_code';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( __( '<dfn title="You can put your original 403.php and so on into your theme directory.">Response code</dfn> %s', 'ip-geo-block' ), '(<a rel="noreferrer" href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1">RFC 2616</a>)' ),
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
					301 => '301 Moved Permanently',
					302 => '302 Found',
					303 => '303 See Other',
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

		// Redirect URI
		$field = 'redirect_uri';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Specify the URL for response code 2xx and 3xx. If it is pointed to a public facing page, visitors would not be blocked on the page to prevent loop of redirection even when you enable [Block by country] in [Front-end target settings] section. Empty URL is altered to your home.">Redirect URL</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'class' => $options['response_code'] < 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// Response message
		$field = 'response_msg';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Specify the message for response code 4xx and 5xx.">Response message</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'class' => $options['response_code'] >= 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// Max number of failed login attempts per IP address
		$field = 'login_fails';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="This is applied to &#8220;XML-RPC&#8221; and &#8220;Login form&#8221;. Lockout period is defined as expiration time at &#8220;Cache settings&#8221;.">Max number of failed login attempts per IP address</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $options[ $field ],
				'list' => array(
					-1 => 'Disable',
					 0 =>  0,
					 1 =>  1,
					 3 =>  3,
					 5 =>  5,
					 7 =>  7,
					10 => 10,
				),
			)
		);

		// Validation timing
		$field = 'validation';
		$key = 'timing';
		$options[ $field ][ $key ] = IP_Geo_Block_Opts::get_validation_timing();

		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . __( 'Select when to run the validation.', 'ip-geo-block' ) . '">' . __( 'Validation timing', 'ip-geo-block' ) . '</dfn>',
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
					 0 => __( '&#8220;init&#8221; action hook',                 'ip-geo-block' ),
					 1 => __( '&#8220;mu-plugins&#8221; (ip-geo-block-mu.php)', 'ip-geo-block' ),
				),
				'desc' => array(
					 0 => __( 'Validate at &#8220;init&#8221; action hook in the same manner as typical plugins.', 'ip-geo-block' ),
					 1 => __( 'Validate at an earlier phase than other typical plugins. It can reduce load on server but has <a rel=\'noreferrer\' href=\'http://www.ipgeoblock.com/codex/validation-timing.html\' title=\'Validation timing | IP Geo Block\'>some restrictions</a>.', 'ip-geo-block' ),
				),
			)
		);

		/*----------------------------------------*
		 * Back-end target settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-validation-target';
		add_settings_section(
			$section,
			__( 'Back-end target settings', 'ip-geo-block' ),
			array( __CLASS__, 'note_target' ),
			$option_slug
		);

		// same as in tab-accesslog.php
		$dfn = __( '<dfn title="Validate request to %s.">%s</dfn>', 'ip-geo-block' );
		$target = array(
			'comment' => sprintf( $dfn, 'wp-comments-post.php', __( 'Comment post', 'ip-geo-block' ) ),
			'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',           __( 'XML-RPC',      'ip-geo-block' ) ),
			'login'   => sprintf( $dfn, 'wp-login.php',         __( 'Login form',   'ip-geo-block' ) ),
			'admin'   => sprintf( $dfn, 'wp-admin/*.php',       __( 'Admin area',   'ip-geo-block' ) ),
			'others'  => sprintf( $dfn, 'executable files',     __( 'Other areas',  'ip-geo-block' ) ),
			'public'  => sprintf( $dfn, __( 'public facing pages', 'ip-geo-block' ), __( 'Public facing pages', 'ip-geo-block' ) ),
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
				'text' => __( 'Block by country', 'ip-geo-block' ),
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
					0 => __( 'Disable',          'ip-geo-block' ),
					1 => __( 'Block by country', 'ip-geo-block' ),
					2 => __( 'Completely close', 'ip-geo-block' ),
				),
			)
		);

		$desc = array(
			'login'        => __( 'Log in' ),
			'register'     => __( 'Register' ),
			'resetpass'    => __( 'Password Reset' ),
			'lostpassword' => __( 'Lost Password' ),
			'postpass'     => __( 'Password protected' ),
		);

		$list = '';
		foreach ( $desc as $key => $val ) {
			$list .= '<li><input type="checkbox" id="ip_geo_block_settings_login_action_' . $key . '" name="ip_geo_block_settings[login_action][' . $key . ']" value="1"' . checked( ! empty( $options['login_action'][ $key ] ), TRUE, FALSE ) . ' /><label for="ip_geo_block_settings_login_action_' . $key . '">' . $val . "</label></li>\n";
		}

		// Login form
		$key = 'login';
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
				'text' => __( 'Block by country', 'ip-geo-block' ),
				'after' => '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual action as a blocking target.">Target actions</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n" . $list . "</ul></li></ul>\n",
			)
		);

		$list = array(
			1 => __( 'Block by country', 'ip-geo-block' ),
			2 => __( 'Prevent Zero-day Exploit', 'ip-geo-block' ),
		);

		$desc = array(
			1 => __( 'It will block a request related to the services for both public facing pages and the dashboard.', 'ip-geo-block' ),
			2 => __( 'Regardless of the country code, it will block a malicious request related to the services only for the dashboard.', 'ip-geo-block' ),
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

		// Get all the ajax/post actions
		$exception = '';
		$installed = array();

		global $wp_filter;
		foreach ( $wp_filter as $key => $val ) {
			if ( FALSE !== strpos( $key, 'wp_ajax_' ) ) {
				if ( 0 === strpos( $key, 'wp_ajax_nopriv_' ) ) {
					$key = substr( $key, 15 );
					$val = 2;
				} else {
					$key = substr( $key, 8 );
					$val = 1;
				}
				$installed[ $key ] = isset( $installed[ $key ] ) ? $installed[ $key ] | $val : $val;
			} elseif ( FALSE !== strpos( $key, 'admin_post_' ) ) {
				if ( 0 === strpos( $key, 'admin_post_nopriv_' ) ) {
					$key = substr( $key, 18 );
					$val = 2;
				} else {
					$key = substr( $key, 11 );
					$val = 1;
				}
				$installed[ $key ] = isset( $installed[ $key ] ) ? $installed[ $key ] | $val : $val;
			}
		}
		unset( $installed['ip_geo_block'] );

		$tmp = array(
			__( 'for logged-in user',     'ip-geo-block' ),
			__( 'for non logged-in user', 'ip-geo-block' ),
		);

		foreach ( $installed as $key => $val ) {
			$val = '';
			$val .= $installed[ $key ] & 1 ? '<dfn title="' . $tmp[0] . '"><span class="dashicons dashicons-lock"></span></dfn>' : '';
			$val .= $installed[ $key ] & 2 ? '<dfn title="' . $tmp[1] . '"><span class="dashicons dashicons-unlock"></span></dfn>' : '';
			$key = esc_attr( $key );
			$exception .= '<li>'
				. '<input id="ip_geo_block_' . $key . '" type="checkbox" value="1"' . checked( in_array( $key, $options['exception']['admin'] ), TRUE, FALSE ) . ' />'
				. '<label for="ip_geo_block_' . $key . '">' . $key . '</label>' . $val
				. '</li>' . "\n";
		}

		$path = IP_Geo_Block::get_wp_path();

		// Admin ajax/post
		$key = 'ajax';
		$val = esc_html( substr( $path['admin'], 1 ) );
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'admin-(ajax|post).php', __( 'Admin ajax/post', 'ip-geo-block' ) ),
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
				'after' => '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">'
					. __( '<dfn title="Specify the page name (&#8220;page=&hellip;&#8221;) or the action name (&#8220;action=&hellip;&#8221;) to prevent undesired blocking caused by &#8220;Block by country&#8221; for non logged-in user and &#8220;Prevent Zero-day Exploit&#8221; for logged-in user.">Exceptions</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>'
					. "\n<li class=\"ip-geo-block-hide\"><ul><li>\n"
					. '<input class="regular-text code" id="ip_geo_block_settings_exception_admin" name="ip_geo_block_settings[exception][admin]" type="text" value="' . esc_attr( implode( ',', $options['exception']['admin'] ) ) . '">' . "\n"
					. $comma[0]
					. '</li><li><ul id="ip-geo-block-actions">'
					. '<h4>' . __( 'Candidate actions', 'ip-geo-block' ) . '</h4>'
					. $exception
					. '</ul></li></ul></li></ul>' . "\n",
			)
		);

		array_unshift( $list, __( 'Disable', 'ip-geo-block' ) );
		$desc = array(
			__( 'Regardless of the country code, it will block a malicious request to <code>%s&ctdot;/*.php</code>.', 'ip-geo-block' ),
			__( 'It configures &#8220;%s&#8221; to validate a request to the PHP file which does not load WordPress core.', 'ip-geo-block' ),
			__( '<dfn title="Select the item which causes undesired blocking in order to exclude from the validation target. Grayed item indicates &#8220;INACTIVE&#8221;.">Exceptions</dfn>', 'ip-geo-block' ),
		);

		// Set rewrite condition
		$options['rewrite'] = IP_Geo_Block_Admin_Rewrite::check_rewrite_all();

		// Get all the plugins
		$exception = '';
		$installed = get_plugins(); // @since 1.5.0
		unset( $installed[ IP_GEO_BLOCK_BASE ] ); // exclude myself

		$activated = get_site_option( 'active_sitewide_plugins' ); // @since 2.8.0
		! is_array( $activated ) and $activated = array();
		$activated = array_merge( $activated, array_fill_keys( get_option( 'active_plugins' ), TRUE ) );

		// Make a list of installed plugins
		foreach ( $installed as $key => $val ) {
			$active = isset( $activated[ $key ] );
			$key = explode( '/', $key, 2 );
			$key = esc_attr( $key[0] );
			$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_exception_plugins_' . $key
				. '" name="ip_geo_block_settings[exception][plugins][' . $key
				. ']" value="1"' . checked( in_array( $key, $options['exception']['plugins'] ), TRUE, FALSE )
				. ' /><label for="ip_geo_block_settings_exception_plugins_' . $key
				. ($active ? '">' : '" class="folding-inactive">') . esc_html( $val['Name'] ) . "</label></li>\n";
		}

		// Plugins area
		$key = 'plugins';
		$val = esc_html( $path[ $key ] );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_' . $key
			. '" name="ip_geo_block_settings[rewrite][' . $key . ']" '
			. ' value="1"' . checked( $options['rewrite'][ $key ], TRUE, FALSE )
			. disabled( $options['rewrite'][ $key ], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_' . $key . '"><dfn title="'
			. sprintf( $desc[1], $val . '.htaccess' )
			. '">' . __( 'Force to load WP core', 'ip-geo-block' )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Plugins area', 'ip-geo-block' ) ),
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
				'after' => '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . $desc[2] . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n" . $exception . "</ul></li></ul>\n",
			)
		);

		// Get all the themes
		$exception = '';
		$installed = wp_get_themes( NULL ); // @since 3.4.0
		$activated = wp_get_theme(); // @since 3.4.0
		$activated = $activated->get( 'Name' );

		// List of installed themes
		foreach ( $installed as $key => $val ) {
			$key = esc_attr( $key );
			$active = ( ( $val = $val->get( 'Name' ) ) === $activated );
			$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_exception_themes_' . $key
				. '" name="ip_geo_block_settings[exception][themes][' . $key
				. ']" value="1"' . checked( in_array( $key, $options['exception']['themes'] ), TRUE, FALSE )
				. ' /><label for="ip_geo_block_settings_exception_themes_' . $key
				. ($active ? '">' : '" class="folding-inactive">') . esc_html( $val ) . "</label></li>\n";
		}

		// Themes area
		$key = 'themes';
		$val = esc_html( $path[ $key ] );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_' . $key
			. '" name="ip_geo_block_settings[rewrite][' . $key . ']" '
			. ' value="1"' . checked( $options['rewrite'][ $key ], TRUE, FALSE )
			. disabled( $options['rewrite'][ $key ], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_' . $key . '"><dfn title="'
			. sprintf( $desc[1], $val . '.htaccess' )
			. '">' . __( 'Force to load WP core', 'ip-geo-block' )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Themes area', 'ip-geo-block' ) ),
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
				'after' => '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . $desc[2] . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n" . $exception . "</ul></li></ul>\n",
			)
		);

		/*----------------------------------------*
		 * Front-end settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-public';
		add_settings_section(
			$section,
			__( 'Front-end target settings', 'ip-geo-block' ),
			array( __CLASS__, 'note_public' ),
			$option_slug
		);

		// Public facing pages
		$key = 'public';
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
				'text' => __( 'Block by country', 'ip-geo-block' ),
			)
		);

		// Default for matching rule on front-end
		$rule[-1] = __( 'Follow &#8220;Validation rule settings&#8221;', 'ip-geo-block' );

		// Matching rule
		$field = 'public';
		$key = 'matching_rule';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . $rule_desc[0] . '">' . __( 'Matching rule', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'list' => $rule,
			)
		);

		// Country code for matching rule (ISO 3166-1 alpha-2)
		$key = 'white_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$rule_desc[1],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma[0],
				'class' => $options[ $field ]['matching_rule'] == 0 ? '' : 'ip-geo-block-hide',
			)
		);

		$key = 'black_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$rule_desc[2],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma[0],
				'class' => $options[ $field ]['matching_rule'] == 1 ? '' : 'ip-geo-block-hide',
			)
		);

		// Response code (RFC 2616)
		$key = 'response_code';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			sprintf( __( '<dfn title="You can configure a different response code from the Back-end. This is useful to prevent violation against your affiliate program.">Response code</dfn> %s', 'ip-geo-block' ), '(<a rel="noreferrer" href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1">RFC 2616</a>)' ),
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
					200 => '200 OK',
					301 => '301 Moved Permanently',
					302 => '302 Found',
					303 => '303 See Other',
					307 => '307 Temporary Redirect',
					400 => '400 Bad Request',
					403 => '403 Forbidden',
					404 => '404 Not Found',
					406 => '406 Not Acceptable',
					410 => '410 Gone',
					500 => '500 Internal Server Error',
					503 => '503 Service Unavailable',
				),
				'class' => $options[ $field ]['matching_rule'] == -1 ? 'ip-geo-block-hide' :'',
			)
		);

		// Redirect URI
		$key = 'redirect_uri';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="Specify the URL for response code 2xx and 3xx. If it is pointed to a public facing page, visitors would not be blocked on the page to prevent loop of redirection even when you enable [Block by country] in [Front-end target settings] section. Empty URL is altered to your home.">Redirect URL</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'class' => $options[ $field ]['matching_rule'] != -1 && $options[ $field ]['response_code'] < 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// Response message
		$key = 'response_msg';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( '<dfn title="Specify the message for response code 4xx and 5xx.">Response message</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'class' => $options[ $field ]['matching_rule'] != -1 && $options[ $field ]['response_code'] >= 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// List of page
		$exception = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual page as a blocking target.">Page</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_pages();
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->post_name );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_pages_' . $val . '" name="ip_geo_block_settings[public][target_pages][' . $val . ']" value="1"' . checked( isset( $options[ $field ]['target_pages'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_pages_' . $val . '">' . esc_html( $key->post_title ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of post type
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual post type on a single page as a blocking target.">Post type</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_post_types( array( 'public' => TRUE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_posts_' . $val . '" name="ip_geo_block_settings[public][target_posts][' . $val . ']" value="1"' . checked( isset( $options[ $field ]['target_posts'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_posts_' . $val . '">' . esc_html( $key ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of category
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual category on a single page or archive page as a blocking target.">Category</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_categories( array( 'hide_empty' => FALSE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->slug );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_cates_' . $val . '" name="ip_geo_block_settings[public][target_cates][' . $val . ']" value="1"' . checked( isset( $options[ $field ]['target_cates'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_cates_' . $val . '">' . esc_html( $key->name ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of tag
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual tag on a single page or archive page as a blocking target.">Tag</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-cycle ip-geo-block-hide"><span></span></a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_tags( array( 'hide_empty' => FALSE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->slug );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_tags_' . $val . '" name="ip_geo_block_settings[public][target_tags][' . $val . ']" value="1"' . checked( isset( $options[ $field ]['target_tags'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_tags_' . $val . '">' . esc_html( $key->name ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// Validation target
		$key = 'target_rule';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . __( 'Specify the validation target on front-end.', 'ip-geo-block' ) . '">' . __( 'Validation target', 'ip-geo-block' ) . '</dfn>',
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
					0 => __( 'All requests', 'ip-geo-block' ),
					1 => __( 'Specify the targets', 'ip-geo-block' ),
				),
				'desc' => array(
					1 => __( "Notice that &#8220;Validation timing&#8221; is deferred till &#8220;wp&#8221; action hook. It means that this feature would not be compatible with any page caching.", 'ip-geo-block' ),
				),
				'after' => $exception,
			)
		);

		// UA string and qualification
		$key = 'ua_list';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . __( 'A part of user agent string and a qualification connected with a separator that indicates an applicable rule and can be &#8220;:&#8221; (pass) or &#8220;#&#8221; (block). A &#8220;qualification&#8221; can be &#8220;DNS&#8221;, &#8220;FEED&#8221;, country code or IP address with CIDR. A negative operator &#8220;!&#8221; can be placed just before a &#8220;qualification&#8221;.', 'ip-geo-block' ) . '">' . __( 'UA string and qualification', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
				'after' => $comma[1],
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Excluded action
		$key = 'exception';
		add_settings_field(
			$option_name.'_'.$key.'_'.$field,
			'<dfn title="' . __( 'Specify the name of action that is invariably blocked.', 'ip-geo-block' ) . '">' . __( 'Excluded actions', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $key,
				'sub-field' => $field,
				'value' => implode( ',', $options[ $key ][ $field ] ),
				'after' => $comma[0],
			)
		);
endif;

		// DNS reverse lookup
		$key = 'dnslkup';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . __( 'It enables to verify the host by reverse DNS lookup which would spend some server resources. If it is disabled, &#8220;HOST&#8221; and &#8220;HOST=&hellip;&#8221;in &#8220;UA string and qualification&#8221; will always return &#8220;true&#8221;.', 'ip-geo-block' ) . '">' . __( 'DNS reverse lookup', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
			)
		);

		// Simulation mode
		$key = 'simulate';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			'<dfn title="' . __( 'It enables to simulate validation without deployment. The results can be found at &#8220;Public facing pages&#8221; in Logs.', 'ip-geo-block' ) . '">' . __( 'Simulation mode', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
			)
		);

		/*----------------------------------------*
		 * Geolocation service settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-provider';
		add_settings_section(
			$section,
			__( 'Geolocation API settings', 'ip-geo-block' ),
			array( __CLASS__, 'note_services' ),
			$option_slug
		);

		// API selection and key settings
		$field = 'providers';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Cache and local database are scanned at the top priority.">API selection and key settings</dfn>', 'ip-geo-block' ),
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

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Timeout for network API
		$field = 'timeout';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Timeout for network API [sec]', 'ip-geo-block' ),
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
endif;

		/*----------------------------------------*
		 * Local database settings
		 *----------------------------------------*/
		// Local DBs for each API
		$providers = IP_Geo_Block_Provider::get_addons();
		if ( empty( $providers ) ) {
			$context->add_admin_notice( 'error',
				sprintf(
					__( 'Can not find geolocation API libraries in <code>%s</code>. It seems to have failed downloading <a rel="noreferrer" href="https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip" title="Download the contents of tokkonopapa/WordPress-IP-Geo-API as a zip file">ZIP file</a> from <a rel="noreferrer" href="https://github.com/tokkonopapa/WordPress-IP-Geo-API" title="tokkonopapa/WordPress-IP-Geo-API - GitHub">WordPress-IP-Geo-API</a>. Please install <code>ip-geo-api</code> with write permission according to <a rel="noreferrer" href="http://www.ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How can I fix permission troubles? | IP Geo Block">this instruction</a>.', 'ip-geo-block' ),
					apply_filters( 'ip-geo-block-api-dir', basename( WP_CONTENT_DIR ) )
				)
			);
		}

		$section = $plugin_slug . '-database';
		add_settings_section(
			$section,
			__( 'Local database settings', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		foreach ( $providers as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, NULL ) ) {
				$geo->add_settings_field(
					$provider,
					$section,
					$option_slug,
					$option_name,
					$options,
					array( $context, 'callback_field' ),
					__( 'database', 'ip-geo-block' ),
					__( 'Last update: %s', 'ip-geo-block' )
				);
			}
		}

		// Auto updating (once a month)
		$field = 'update';
		add_settings_field(
			$option_name.'_'.$field.'_auto',
			__( 'Auto updating (once a month)', 'ip-geo-block' ),
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
			__( 'Download database', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Download now', 'ip-geo-block' ),
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
			__( 'Record settings', 'ip-geo-block' ),
			array( __CLASS__, 'note_record' ),
			$option_slug
		);

		// Record validation statistics
		$field = 'save_statistics';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Record validation statistics', 'ip-geo-block' ),
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
			__( 'Record validation logs', 'ip-geo-block' ),
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
					0 => __( 'Disable',              'ip-geo-block' ),
					1 => __( 'Only when blocked',    'ip-geo-block' ),
					2 => __( 'Only when passed',     'ip-geo-block' ),
					3 => __( 'Unauthenticated user', 'ip-geo-block' ),
					4 => __( 'Authenticated user',   'ip-geo-block' ),
					5 => __( 'All of validation',    'ip-geo-block' ),
				),
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		$key = 'recdays';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( 'Recording period of the logs (days)', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
			)
		);

		$key = 'maxlogs';
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			__( 'Maximum length of logs for each target', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => $key,
				'value' => $options[ $field ][ $key ],
			)
		);
endif;

		// $_POST keys to be recorded with their values in logs
		add_settings_field(
			$option_name.'_'.$field.'_postkey',
			__( '<dfn title="e.g. action, comment, log, pwd, FILES">$_POST keys to be recorded with their values in logs</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'postkey',
				'value' => $options[ $field ]['postkey'],
				'after' => $comma[0],
			)
		);

		// Anonymize IP address
		$field = 'anonymize';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="e.g. 123.456.789.***">Anonymize IP address</dfn>', 'ip-geo-block' ),
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
			__( 'IP address cache settings', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		// Expiration time [sec]
		$field = 'cache_time';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( __( '<dfn title="If user authentication fails consecutively %d times, subsequent login will also be prohibited for this and garbage collection period.">Expiration time [sec]</dfn>', 'ip-geo-block' ), (int)$options['login_fails'] ),
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

		// Garbage collection period [sec]
		$field = 'cache_time_gc';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Garbage collection period [sec]', 'ip-geo-block' ),
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

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Number of entries
		$field = 'cache_hold';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Number of entries to be displayed in cache', 'ip-geo-block' ),
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
endif;

		/*----------------------------------------*
		 * Submission settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-submission';
		add_settings_section(
			$section,
			__( 'Submission settings', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		$val = $GLOBALS['allowedtags'];
		unset( $val['blockquote'] );

		// Message on comment form
		$field = 'comment';
		add_settings_field(
			$option_name.'_'.$field,
			'<dfn title="' . __( 'The whole will be wrapped by &lt;p&gt; tag. Allowed tags: ', 'ip-geo-block' ) . implode( ', ', array_keys( $val ) ) . '">' . __( 'Message on comment form', 'ip-geo-block' ) . '</dfn>',
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
					0 => __( 'None',   'ip-geo-block' ),
					1 => __( 'Top',    'ip-geo-block' ),
					2 => __( 'Bottom', 'ip-geo-block' ),
				),
				'text' => $options[ $field ]['msg'], // escaped by esc_attr() at 'text'
			)
		);

		/*----------------------------------------*
		 * Plugin settings
		 *----------------------------------------*/
		$section = $plugin_slug . '-others';
		add_settings_section(
			$section,
			__( 'Plugin settings', 'ip-geo-block' ),
			NULL,
			$option_slug
		);
if (0):
		// @see https://vedovini.net/2015/10/using-the-wordpress-settings-api-with-network-admin-pages/
		if ( is_main_site() && is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
			add_action( 'network_admin_edit_' . IP_Geo_Block::PLUGIN_NAME, array( $context, 'validate_network_settings' ) );

			// Network wide configuration
			$field = 'network_wide';
			add_settings_field(
				$option_name.'_'.$field,
				__( '<dfn title="Synchronize all settings over the network wide.">Network wide settings</dfn>', 'ip-geo-block' ),
				array( $context, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'checkbox',
					'option' => $option_name,
					'field' => $field,
					'value' => $options[ $field ],
					'disabled' => ! current_user_can( 'manage_network_options' ),
				)
			);
		}
endif;
		// Remove all settings at uninstallation
		$field = 'clean_uninstall';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Remove all settings at uninstallation', 'ip-geo-block' ),
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

		// Google Maps API key
		$field = 'api_key';
		$key = 'GoogleMap';
		if ( 'default' !== $options[ $field ][ $key ] or defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
			add_settings_field(
				$option_name.'_'.$field,
				__( '<dfn title="Valid key for Google Maps JavaScript API">Google Maps API key</dfn>', 'ip-geo-block' ),
				array( $context, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'sub-field' => $key,
					'value' => $options[ $field ][ $key ],
				)
			);
		}

		$desc = __( 'You need to click the &#8220;Save Changes&#8221; button for imported settings to take effect.', 'ip-geo-block' );

		// Export / Import settings
		$field = 'export-import';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( '<dfn title="%s">' . __( 'Export / Import settings', 'ip-geo-block' ) . '</dfn>', $desc ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button button-secondary" id="ip-geo-block-export" title="' . __( 'Export to the local file',   'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Export settings', 'ip-geo-block' ) . '</a>&nbsp;' .
					'<a class="button button-secondary" id="ip-geo-block-import" title="' . __( 'Import from the local file', 'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Import settings', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="ip-geo-block-export-import"></div>',
			)
		);

		// Pre-defined settings
		$field = 'pre-defined';
		add_settings_field(
			$option_name.'_'.$field,
			sprintf( '<dfn title="%s">' . __( 'Import pre-defined settings', 'ip-geo-block' ) . '</dfn>', $desc ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button button-secondary" id="ip-geo-block-default" title="' . __( 'Import the default settings to revert to the &#8220;Right after installing&#8221; state', 'ip-geo-block' ) . '" href="javascript:void(0)">' . __( 'Default settings', 'ip-geo-block' ) . '</a>&nbsp;' .
					'<a class="button button-secondary" id="ip-geo-block-preferred" title="' . __( 'Import the preferred settings mainly by enabling Zero-day Exploit Prevention for the &#8220;Back-end target settings&#8221;', 'ip-geo-block' ) . '" href="javascript:void(0)">' . __( 'Best for Back-end', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="ip-geo-block-pre-defined"></div>',
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Manipulate DB table for validation logs
		$field = 'delete_table';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Delete DB table for validation logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Delete now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-delete-table"></div>',
			)
		);

		$field = 'create_table';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Create DB table for validation logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Create now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-create-table"></div>',
			)
		);
endif;

		// Show WordPress installation info
		$field = 'show-info';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="Please copy &amp; paste when submitting your issue to support forum.">Installation information</dfn><br />[ <a rel="noreferrer" href="https://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block">support forum</a> ]', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button button-secondary" id="ip-geo-block-show-info" title="' . __( 'Show PHP, WordPress, theme and plugins information.', 'ip-geo-block' ) . '" href="javascript:void(0)">' . __( 'Show information', 'ip-geo-block' ) . '</a>&nbsp;',
				'after' => '<div id="ip-geo-block-wp-info"></div>',
			)
		);

	}

	/**
	 * Subsidiary note for the sections
	 *
	 * @param array $section settings of section added to admin pages
	 * @param bool  $stat    TRUE:open ('o') or FALSE:close ('x')
	 */
	public static function note_target() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'To enhance the protection ability, please refer to &#8220;<a rel="noreferrer" href="http://www.ipgeoblock.com/codex/the-best-practice-for-target-settings.html" title="The best practice for target settings | IP Geo Block">The best practice for target settings</a>&#8221;.', 'ip-geo-block' ), '</li>', "\n",
				'<li>', __( 'If you have any troubles with these, please check FAQ at <a rel="noreferrer" href="https://wordpress.org/plugins/ip-geo-block/faq/" title="IP Geo Block &mdash; WordPress Plugins">WordPress.org</a> and <a rel="noreferrer" href="http://www.ipgeoblock.com/codex/#faq" title="Codex | IP Geo Block">Codex</a>.', 'ip-geo-block' ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_services() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'While Maxmind and IP2Location will fetch the local database, others will pass an IP address to the APIs via HTTP.', 'ip-geo-block' ), '</li>', "\n",
				'<li>', __( 'Please select the appropriate APIs to fit the privacy law in your country.', 'ip-geo-block' ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_public() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'Please refer to the document &#8220;<a rel="noreferrer" href="http://www.ipgeoblock.com/codex/#blocking-on-front-end" title="Codex | IP Geo Block">Blocking on front-end</a>&#8221; for details, including restrictions on cache plugin.', 'ip-geo-block' ), '</li>', "\n",
				'<li>', __( 'If you find any issues or have something to suggest, please feel free to open an issue at <a rel="noreferrer" href="https://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block">support forum</a>.', 'ip-geo-block' ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_record() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'Please refer to the document &#8220;<a rel="noreferrer" href="http://www.ipgeoblock.com/codex/record-settings-and-logs.html" title="Codex | IP Geo Block">Record settings and logs</a>&#8221; for details.', 'ip-geo-block' ), '</li>', "\n",
			'</ul>', "\n";
	}

}