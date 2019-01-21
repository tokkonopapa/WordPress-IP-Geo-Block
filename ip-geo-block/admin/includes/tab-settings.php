<?php
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php';

class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME; // 'ip-geo-block'

		// common descriptions
		$common = array(
			'<span class="ip-geo-block-sup">' . __( '(comma separated)', 'ip-geo-block' ) . '</span>',
			'<span class="ip-geo-block-sup">' . __( '(comma or RET separated)', 'ip-geo-block' ) . '</span>',
			'<span title="' . __( 'Toggle selection', 'ip-geo-block' ) . '"></span>',
			'<span title="' . __( 'Find blocked requests in &#8220;Logs&#8220;', 'ip-geo-block' ) . '"></span>',
			__( 'Help', 'ip-geo-block' ),
			__( 'Before adding as &#8220;Exception&#8221;, please click on &#8220;<a class="ip-geo-block-icon ip-geo-block-icon-alert" title="This button is just a sample."><span></span></a>&#8221; button (if exists) attached to the following list to confirm that the blocked request is not malicious.', 'ip-geo-block' ),
			__( 'Open CIDR calculator for IPv4 / IPv6.', 'ip-geo-block' ),
		);

		/**
		 * Register a setting and its sanitization callback.
		 * @link https://codex.wordpress.org/Function_Reference/register_setting
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
		 * @link https://codex.wordpress.org/Function_Reference/add_settings_section
		 *
		 * add_settings_section( $id, $title, $callback, $page );
		 * @param string $id String for use in the 'id' attribute of tags.
		 * @param string $title Title of the section.
		 * @param string $callback Function that fills the section with the desired content.
		 * @param string $page The menu page on which to display this section.
		 * @since 2.7.0
		 */
		/*----------------------------------------*
		 * Validation rules and behavior
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-validation-rule',
			array( __( 'Validation rules and behavior', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/validation-rule-settings.html" title="Validation rules and behavior | IP Geo Block">' . $common[4] . '</a>' ),
			NULL,
			$option_slug
		);

		/**
		 * Register a settings field to the settings page and section.
		 * @link https://codex.wordpress.org/Function_Reference/add_settings_field
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
		$key = IP_Geo_Block::get_geolocation( $val = IP_Geo_Block::get_ip_address( $options ) );

		add_settings_field(
			$option_name.'_ip_client',
			__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8220;Scan country code&#8221;.">Your IP address / Country</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'ip_client',
				'value' => '<span class="ip-geo-block-ip-addr">' . esc_html( $key['ip'] . ' / ' . ( $key['code'] && isset( $key['provider'] ) ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'n/a', 'ip-geo-block' ) ) ) . '</span>',
				'after' => '&nbsp;<a class="button-secondary" id="ip-geo-block-scan-ip_client" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', 'ip-geo-block' ) . '" href="#!">' . __( 'Scan country code', 'ip-geo-block' ) . '</a><div id="ip-geo-block-scanning-ip_client"></div>',
			)
		);

if ( $key = IP_Geo_Block_Util::get_server_ip() && $key !== $val && ! IP_Geo_Block_Util::is_private_ip( $key ) ):
		// Get the country code of server
		$key = IP_Geo_Block::get_geolocation( $_SERVER['SERVER_ADDR'] );

		add_settings_field(
			$option_name.'_ip_server',
			__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8220;Scan country code&#8221;.">Server IP address / Country</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'ip_server',
				'value' => '<span class="ip-geo-block-ip-addr">' . esc_html( $key['ip'] . ' / ' . ( $key['code'] && isset( $key['provider'] ) ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'n/a', 'ip-geo-block' ) ) ) . '</span>',
				'after' => '&nbsp;<a class="button-secondary" id="ip-geo-block-scan-ip_server" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', 'ip-geo-block' ) . '" href="#!">' . __( 'Scan country code', 'ip-geo-block' ) . '</a><div id="ip-geo-block-scanning-ip_server"></div>',
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
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. The special code &#8220;XX&#8221; is assigned as private IP address including localhost. And &#8220;ZZ&#8221; is for unknown IP address (i.e. not in the geolocation databases). Please use &#8220;YY&#8221; if you need the code that does not correspond to any of the countries.">Whitelist of country code</dfn>', 'ip-geo-block' ) . '<br />(<a rel="noreferrer" href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia">ISO 3166-1 alpha-2</a>)',
			__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. The special code &#8220;XX&#8221; is assigned as private IP address including localhost. And &#8220;ZZ&#8221; is for unknown IP address (i.e. not in the geolocation databases). Please use &#8220;YY&#8221; if you need the code that does not correspond to any of the countries.">Blacklist of country code</dfn>', 'ip-geo-block' ) . '<br />(<a rel="noreferrer" href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia">ISO 3166-1 alpha-2</a>)',
		);

		// Matching rule
		add_settings_field(
			$option_name.'_matching_rule',
			'<dfn title="' . $rule_desc[0] . '">' . __( 'Matching rule', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'matching_rule',
				'value' => $options['matching_rule'],
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
		add_settings_field(
			$option_name.'_white_list',
			$rule_desc[1],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'white_list',
				'value' => $options['white_list'],
				'after' => $common[0],
				'class' => $options['matching_rule'] == 0 ? '' : 'ip-geo-block-hide',
			)
		);

		add_settings_field(
			$option_name.'_black_list',
			$rule_desc[2],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'black_list',
				'value' => $options['black_list'],
				'after' => $common[0],
				'class' => $options['matching_rule'] == 1 ? '' : 'ip-geo-block-hide',
			)
		);

		// Use AS number
		add_settings_field(
			$option_name.'_Maxmind_use_asn',
			__( '<dfn title="It enables utilizing &#8220;AS number&#8221; in the &#8220;Whitelist/Blacklist of extra IP addresses&#8221; to specify a group of IP networks.">Use Autonomous System Number</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'Maxmind',
				'sub-field' => 'use_asn',
				'value' => 1 === (int)$options['Maxmind']['use_asn'],
				'after' => '<p class="ip-geo-block-desc">' . sprintf( __( 'Some useful tools to find ASN are introduced in &#8220;%s&#8221;.', 'ip-geo-block' ), '<a rel="noreferrer" href="https://www.ipgeoblock.com/codex/utilizing-asnumber.html" title="Utilizing AS number | IP Geo Block">Utilizing AS number</a>' ) . '</p>',
			)
		);

		// $_SERVER keys to retrieve extra IP addresses
		add_settings_field(
			$option_name.'_validation_proxy',
			__( '<dfn title="If your server is placed behind the proxy server or the load balancing server, you need to put the appropriate key such as &#8220;HTTP_X_FORWARDED_FOR&#8221;, &#8220;HTTP_X_REAL_IP&#8221; or something like that to retrieve the client IP address.">$_SERVER keys to retrieve extra IP addresses</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'proxy',
				'value' => $options['validation']['proxy'],
				'placeholder' => IP_Geo_Block_Util::get_proxy_var(),
				'after' => $common[0],
			)
		);

		// White list of extra IP addresses prior to country code (CIDR, ASN)
		add_settings_field(
			$option_name.'_extra_ips_white_list',
			__( '<dfn title="e.g. &#8220;192.0.64.0/18&#8221; for Jetpack server, &#8220;69.46.36.0/27&#8221; for WordFence server or &#8220;AS32934&#8221; for Facebook.">Whitelist of extra IP addresses prior to country code</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia">CIDR</a>' .
			', <a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)' .
			'<a class="ip-geo-block-icon ip-geo-block-icon-cidr" title="' . $common[6] . '"><span class="ip-geo-block-icon-calc"></span></a>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => 'extra_ips',
				'sub-field' => 'white_list',
				'value' => $options['extra_ips']['white_list'],
				'placeholder' => '192.168.0.0/16,2001:db8::/96,AS1234',
				'after' => $common[1],
			)
		);

		// Black list of extra IP addresses prior to country code (CIDR, ASN)
		add_settings_field(
			$option_name.'_extra_ips_black_list',
			__( '<dfn title="Server level access control is recommended (e.g. .htaccess).">Blacklist of extra IP addresses prior to country code</dfn>', 'ip-geo-block' ) .
			' (<a rel="noreferrer" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia">CIDR</a>' .
			', <a rel="noreferrer" href="https://en.wikipedia.org/wiki/Autonomous_system_(Internet)"   title="Autonomous system (Internet) - Wikipedia">ASN</a>)' .
			'<a class="ip-geo-block-icon ip-geo-block-icon-cidr" title="' . $common[6] . '"><span class="ip-geo-block-icon ip-geo-block-icon-calc"></span></a>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => 'extra_ips',
				'sub-field' => 'black_list',
				'value' => $options['extra_ips']['black_list'],
				'placeholder' => '192.168.0.0/16,2001:db8::/96,AS1234',
				'after' => $common[1],
			)
		);

		// Bad signatures
		add_settings_field(
			$option_name.'_signature',
			__( '<dfn title="It validates malicious signatures independently of &#8220;Block by country&#8221; and &#8220;Prevent Zero-day Exploit&#8221; for the target &#8220;Admin area&#8221;, &#8220;Admin ajax/post&#8221;, &#8220;Plugins area&#8221; and &#8220;Themes area&#8221;.">Bad signatures in query</dfn> <nobr>(<a class="ip-geo-block-icon ip-geo-block-icon-cycle" id="ip-geo-block-decode" title="When you find ugly character string in the text area, please click to restore."><span></span></a>)</nobr>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => 'signature',
				'value' => $options['signature'],
				'after' => $common[1],
			)
		);

		// Prevent malicious upload - white list of file extention and MIME type
		$list = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Select allowed MIME type.">Whitelist of allowed MIME type</dfn>', 'ip-geo-block' ) . "<a class=\"ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide\">" . $common[2] . "</a>\n<li class=\"ip-geo-block-hide\"><ul class=\"ip-geo-block-float\">\n";

		// get_allowed_mime_types() in wp-includes/functions.php @since 2.8.6
		foreach ( IP_Geo_Block_Util::get_allowed_mime_types() as $key => $val ) {
			$key = esc_attr( $key );
			$val = esc_attr( $val );
			$list .= '<li><input type="checkbox" id="ip_geo_block_settings_mimetype_white_list' . $key . '" name="ip_geo_block_settings[mimetype][white_list][' . $key . ']" value="' . $val . '"' . checked( isset( $options['mimetype']['white_list'][ $key ] ), TRUE, FALSE ) . '><label for="ip_geo_block_settings_mimetype_white_list' . $key . '"><dfn title="' . $val . '">' . $key . '</dfn></label></li>' . "\n";
		}

		// Prevent malicious upload - black list of file extension
		$list .= "</ul></li></ul>\n";
		$list .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Put forbidden file extensions.">Blacklist of forbidden file extensions</dfn>', 'ip-geo-block' ) . "\n" . '<li class="ip-geo-block-hide"><ul><li><input type="text" class="regular-text code" id="ip_geo_block_settings_mimetype_black_list" name="ip_geo_block_settings[mimetype][black_list]" value="' . esc_attr( $options['mimetype']['black_list'] ) . '"/></li>';
		$list .= "</ul></li></ul>\n";

		// Verify capability
		$list .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the capabilities to be verified. Depending on the particular type of uploader, certain capability may be required. Default is &#8220;upload_files&#8221; for Administrator, Editor and Author. This verification will be skipped if empty.">Capabilities to be verified</dfn>', 'ip-geo-block' ) . '&nbsp;<span class="ip-geo-block-desc">' . __( '( See &#8220;<a rel="noreferrer" href="https://codex.wordpress.org/Roles_and_Capabilities" title="Roles and Capabilities &laquo; WordPress Codex">Roles and Capabilities</a>&#8221; )', 'ip-geo-block' ) . '</span>' . "\n";
		$list .= '<li class="ip-geo-block-hide"><ul><li><input type="text" id="ip_geo_block_settings_mimetype_capability" name="ip_geo_block_settings[mimetype][capability]" class="regular-text code" placeholder="upload_files" value="' . esc_attr( implode( ',', $options['mimetype']['capability'] ) ) . '" />' . $common[0] . '</li></ul></li></ul>';

		// Prevent malicious file uploading
		add_settings_field(
			$option_name.'_validation_mimetype',
			__( '<dfn title="It restricts the file types on upload in order to block malware and backdoor via both back-end and front-end. Please consider to select &#8220;mu-plugins&#8221; (ip-geo-block-mu.php) at &#8220;Validation timing&#8221; so that other staff would not fetch the uploaded files before this validation.">Prevent malicious file uploading</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'mimetype',
				'value' => $options['validation']['mimetype'],
				'list' => array(
					0 => __( 'Disable',                             'ip-geo-block' ),
					1 => __( 'Verify file extension and MIME type', 'ip-geo-block' ),
					2 => __( 'Verify file extension only',          'ip-geo-block' ),
				),
				'after' => $list,
			)
		);

		// Metadata Exploit Protection
		add_settings_field(
			$option_name.'_validation_metadata',
			__( '<dfn title="It prevents important information in the database from being defaced and exploited.">Metadata Exploit Protection</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'metadata',
				'value' => $options['validation']['metadata'],
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Prevent metadata alteration
		$list  = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup" style="margin-top:0.4em">' . __( '<dfn title="Specify the table names to be verified for single site. This verification will be skipped if empty.">pre_update_option</dfn>', 'ip-geo-block' ) . "\n";
		$list .= '<li class="ip-geo-block-hide"><ul><li><input type="text" id="ip_geo_block_settings_metadata_pre_update_option" name="ip_geo_block_settings[metadata][pre_update_option]" class="regular-text code" value="' . esc_attr( implode( ',', $options['metadata']['pre_update_option'] ) ) . '" />' . $common[0] . '</li></ul></li></ul>' . "\n";
		$list .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the table names to be verified for multisite. This verification will be skipped if empty.">pre_update_site_option</dfn>', 'ip-geo-block' ) . "\n";
		$list .= '<li class="ip-geo-block-hide"><ul><li><input type="text" id="ip_geo_block_settings_metadata_pre_update_site_option" name="ip_geo_block_settings[metadata][pre_update_site_option]" class="regular-text code" value="' . esc_attr( implode( ',', $options['metadata']['pre_update_site_option'] ) ) . '" />' . $common[0] . '</li></ul></li></ul>' . "\n";

		add_settings_field(
			$option_name.'_metadata',
			__( '<dfn title="It prevents to manipulate metadata in database without admin privilege.">Prevent metadata alteration</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'metadata',
				'value' => $list,
			)
		);
endif;

		// Response code (RFC 2616)
		add_settings_field(
			$option_name.'_response_code',
			sprintf( __( '<dfn title="You can put your original 403.php and so on into your theme directory.">Response code</dfn> %s', 'ip-geo-block' ), '(<a rel="noreferrer" href="https://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1">RFC 2616</a>)' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'response_code',
				'value' => $options['response_code'],
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
		add_settings_field(
			$option_name.'_redirect_uri',
			__( '<dfn title="Specify the URL for response code 2xx and 3xx. If it is pointed to a public facing page, visitors would not be blocked on the page to prevent loop of redirection even when you enable [Block by country] in [Front-end target settings] section. Empty URL is altered to your home.">Redirect URL</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'redirect_uri',
				'value' => $options['redirect_uri'],
				'class' => $options['response_code'] < 400 ? '' : 'ip-geo-block-hide',
				'placeholder' => '/about/',
			)
		);

		// Response message
		add_settings_field(
			$option_name.'_response_msg',
			__( '<dfn title="Specify the message for response code 4xx and 5xx.">Response message</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'response_msg',
				'value' => $options['response_msg'],
				'class' => $options['response_code'] >= 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// Validation timing
		$options['validation']['timing'] = IP_Geo_Block_Opts::get_validation_timing();

		add_settings_field(
			$option_name.'_validation_timing',
			'<dfn title="' . __( 'Select when to run the validation.', 'ip-geo-block' ) . '">' . __( 'Validation timing', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'timing',
				'value' => $options['validation']['timing'],
				'list' => array(
					 0 => __( '&#8220;init&#8221; action hook',                 'ip-geo-block' ),
					 1 => __( '&#8220;mu-plugins&#8221; (ip-geo-block-mu.php)', 'ip-geo-block' ),
				),
				'desc' => array(
					 0 => __( 'Validate at &#8220;init&#8221; action hook in the same manner as typical plugins.', 'ip-geo-block' ),
					 1 => __( 'Validate at an earlier phase than other typical plugins. It can reduce load on server but has <a rel=\'noreferrer\' href=\'https://www.ipgeoblock.com/codex/validation-timing.html\' title=\'Validation timing | IP Geo Block\'>some restrictions</a>.', 'ip-geo-block' ),
				),
			)
		);

		// Simulation mode
		add_settings_field(
			$option_name.'_simulate',
			'<dfn title="' . __( 'It enables to simulate the validation rules without actual blocking in order to check the behavior of this plugin. The results can be found on &#8220;Logs&#8221; tab.', 'ip-geo-block' ) . '">' . __( 'Simulation mode', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'simulate',
				'value' => isset( $options['simulate'] ) ? $options['simulate'] : FALSE,
			)
		);

		/*----------------------------------------*
		 * Back-end target settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-validation-target',
			array( __( 'Back-end target settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/back-end-target-settings.html" title="Back-end target settings | IP Geo Block">' . $common[4] . '</a>' ),
			array( __CLASS__, 'note_target' ),
			$option_slug
		);

		// same as in tab-accesslog.php
		$dfn = __( '<dfn title="It enables to validate requests to %s.">%s</dfn>', 'ip-geo-block' );
		$target = array(
			'comment' => sprintf( $dfn, 'wp-comments-post.php',                      __( 'Comment post',        'ip-geo-block' ) ),
			'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',                                __( 'XML-RPC',             'ip-geo-block' ) ),
			'login'   => sprintf( $dfn, 'wp-login.php, wp-signup.php',               __( 'Login form',          'ip-geo-block' ) ),
			'admin'   => sprintf( $dfn, '/wp-admin/*.php',                           __( 'Admin area',          'ip-geo-block' ) ),
			'others'  => sprintf( $dfn, 'executable files',                          __( 'Other areas',         'ip-geo-block' ) ),
			'public'  => sprintf( $dfn, __( 'public facing pages', 'ip-geo-block' ), __( 'Public facing pages', 'ip-geo-block' ) ),
		);

		// Comment post
		add_settings_field(
			$option_name.'_validation_comment',
			$target['comment'],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'comment',
				'value' => $options['validation']['comment'],
				'text' => __( 'Block by country', 'ip-geo-block' ),
			)
		);

		$val = $GLOBALS['allowedtags'];
		unset( $val['blockquote'] );

		// Message on comment form
		add_settings_field(
			$option_name.'_comment',
			'<div class="ip-geo-block-subitem"><dfn title="' . __( 'The whole will be wrapped by &lt;p&gt; tag. Allowed tags: ', 'ip-geo-block' ) . implode( ', ', array_keys( $val ) ) . '">' . __( 'Message on comment form', 'ip-geo-block' ) . '</dfn></div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select-text',
				'option' => $option_name,
				'field' => 'comment',
				'sub-field' => 'pos',
				'txt-field' => 'msg',
				'value' => $options['comment']['pos'],
				'class' => 'ip-geo-block-subitem-parent',
				'list' => array(
					0 => __( 'None',   'ip-geo-block' ),
					1 => __( 'Top',    'ip-geo-block' ),
					2 => __( 'Bottom', 'ip-geo-block' ),
				),
				'text' => $options['comment']['msg'], // escaped by esc_attr() at 'text'
			)
		);

		// XML-RPC
		add_settings_field(
			$option_name.'_validation_xmlrpc',
			$target['xmlrpc'],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'xmlrpc',
				'value' => $options['validation']['xmlrpc'],
				'list' => array(
					0 => __( 'Disable',          'ip-geo-block' ),
					1 => __( 'Block by country', 'ip-geo-block' ),
					2 => __( 'Completely close', 'ip-geo-block' ),
				),
			)
		);

		$desc = array(
			'login'        => '<dfn title="' . __( 'Action to login as a registered user.',                                          'ip-geo-block' ) . '">' . __( 'Log in'             ) . '</dfn>',
			'register'     => '<dfn title="' . __( 'Action to register new users.',                                                  'ip-geo-block' ) . '">' . __( 'Register'           ) . '</dfn>',
			'resetpass'    => '<dfn title="' . __( 'Action to reset a password to create a new one.',                                'ip-geo-block' ) . '">' . __( 'Password Reset'     ) . '</dfn>',
			'lostpassword' => '<dfn title="' . __( 'Action to email a password to a registered user.',                               'ip-geo-block' ) . '">' . __( 'Lost Password'      ) . '</dfn>',
			'postpass'     => '<dfn title="' . __( 'Action to show prompt to enter a password on password protected post and page.', 'ip-geo-block' ) . '">' . __( 'Password protected' ) . '</dfn>',
		);

		$list = '';
		foreach ( $desc as $key => $val ) {
			$list .= '<li><input type="checkbox" id="ip_geo_block_settings_login_action_' . $key . '" name="ip_geo_block_settings[login_action][' . $key . ']" value="1"' . checked( ! empty( $options['login_action'][ $key ] ), TRUE, FALSE ) . ' /><label for="ip_geo_block_settings_login_action_' . $key . '">' . $val . "</label></li>\n";
		}

		// Login form
		add_settings_field(
			$option_name.'_validation_login',
			$target['login'],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'login',
				'value' => $options['validation']['login'],
				'text' => __( 'Block by country', 'ip-geo-block' ),
				'after' => '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual action as a blocking target.">Target actions</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide">' . $common[2] . '</a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n" . $list . "</ul></li></ul>\n",
			)
		);

		$list = array(
			1 => __( 'Block by country', 'ip-geo-block' ),
			2 => __( 'Prevent Zero-day Exploit', 'ip-geo-block' ),
		);

		$desc = array(
			1 => __( 'It will block a request related to the services for both &#8220;non-logged in user&#8221; and &#8220;logged-in user&#8221;.', 'ip-geo-block' ),
			2 => __( 'Regardless of the country code, it will block a malicious request related to the services only for &#8220;logged-in user&#8221;.', 'ip-geo-block' ),
		);

		// Max failed login attempts per IP address
		add_settings_field(
			$option_name.'_login_fails',
			'<div class="ip-geo-block-subitem"><dfn title="' . __( 'This is applied to &#8220;XML-RPC&#8221; and &#8220;Login form&#8221; when &#8220;IP address cache&#8221; in &#8220;Privacy and record settings&#8221; section is enabled. Lockout period is the same as expiration time of the cache.', 'ip-geo-block' ) . '">' . __( 'Max failed login attempts per IP address', 'ip-geo-block' ) . '</dfn></div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'login_fails',
				'value' => $options['login_fails'],
				'class' => 'ip-geo-block-subitem-parent',
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

		// Admin area
		add_settings_field(
			$option_name.'_validation_admin',
			$target['admin'],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkboxes',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'admin',
				'value' => $options['validation']['admin'],
				'list' => $list,
				'desc' => $desc,
			)
		);

		$tmp = array(
			__( 'admin post for logged-in user',     'ip-geo-block' ),
			__( 'admin post for non logged-in user', 'ip-geo-block' ),
		);

		// Get all the admin-post actions
		$exception = '';
		$installed = IP_Geo_Block_Util::get_registered_actions( FALSE );
		foreach ( $installed as $key => $val ) {
			$val = '';
			$val .= $installed[ $key ] & 1 ? '<dfn title="' . $tmp[0] . '"><span class="ip-geo-block-admin-post dashicons dashicons-lock">*</span></dfn>' : '';
			$val .= $installed[ $key ] & 2 ? '<dfn title="' . $tmp[1] . '"><span class="ip-geo-block-admin-post dashicons dashicons-unlock">*</span></dfn>' : '';
			$key = esc_attr( $key );
			$exception .= '<li>'
				. '<input id="ip_geo_block_settings_exception_admin_' . $key . '" type="checkbox" value="' . $key . '"' . checked( in_array( $key, $options['exception']['admin'] ), TRUE, FALSE ) . ' />'
				. '<label for="ip_geo_block_settings_exception_admin_' . $key . '">' . $key . '</label>' . $val
				. '</li>' . "\n";
		}

		// Admin ajax/post
		$path = IP_Geo_Block::get_wp_path();
		$val  = esc_html( $path['admin'] );
		add_settings_field(
			$option_name.'_validation_ajax',
			sprintf( $dfn, $val.'admin-(ajax|post).php', __( 'Admin ajax/post', 'ip-geo-block' ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkboxes',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'ajax',
				'value' => $options['validation']['ajax'],
				'list' => $list,
				'desc' => $desc,
				'after' =>
					'<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . "\n" .
					'	<dfn title="' . __( 'Specify the action name (&#8220;action=&hellip;&#8221;) or the page name (&#8220;page=&hellip;&#8221;) to prevent unintended blocking caused by &#8220;Block by country&#8221; (for non logged-in user) and &#8220;Prevent Zero-day Exploit&#8221; (for logged-in user).', 'ip-geo-block' ) . '">' . __( 'Exceptions', 'ip-geo-block' ) . "</dfn>\n" .
					'	<a class="ip-geo-block-hide ip-geo-block-icon ip-geo-block-icon-unlock"><span title="' . __( 'Toggle with non logged-in user', 'ip-geo-block' ) . '"></span></a><a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide" data-target="admin">' . $common[2] . '</a><a class="ip-geo-block-icon ip-geo-block-icon-find ip-geo-block-hide" data-target="admin">' . $common[3] . "</a>\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<input class="regular-text code" id="ip_geo_block_settings_exception_admin" name="ip_geo_block_settings[exception][admin]" type="text" value="' . esc_attr( implode( ',', $options['exception']['admin'] ) ) . '">' . $common[0] . "\n" .
					'		<h4>' . __( 'Candidate actions/pages', 'ip-geo-block' ) . "</h4>\n" .
					'		<p class="ip-geo-block-find-desc">' . $common[5] . '<span id="ip-geo-block-find-admin"></span></p>' . "\n" .
					'	</li>' . "\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<ul class="ip-geo-block-list-exceptions" id="ip-geo-block-list-admin">' . "\n" .
								$exception .
					'		</ul>' . "\n" .
					'	</li>' . "\n" .
					'</ul>'
			)
		);

		array_unshift( $list, __( 'Disable', 'ip-geo-block' ) );
		$desc = array(
			__( 'Regardless of the country code, it will block a malicious request to <code>%s&ctdot;/*.php</code>.', 'ip-geo-block' ),
			__( 'Select the item which causes unintended blocking in order to exclude from the validation target. Grayed item indicates &#8220;INACTIVE&#8221;.', 'ip-geo-block' ),
			__( 'It configures &#8220;%s&#8221; to validate a direct request to the PHP file which does not load WordPress core. Make sure to deny direct access to the hidden files beginning with a dot by the server\'s configuration.', 'ip-geo-block' ),
			__( 'Sorry, but your server type is not supported.', 'ip-geo-block' ),
			__( 'You need to click &#8220;Save Changes&#8221; button for imported settings to take effect.', 'ip-geo-block' ),
		);

		// Set rewrite condition
		$config = IP_Geo_Block_Admin_Rewrite::get_config_file();
		$options['rewrite'] = IP_Geo_Block_Admin_Rewrite::check_rewrite_all();

		// Get all the plugins
		$exception = '';
		$installed = get_plugins(); // @since 1.5.0

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
				. ']" value="' . $key . '"' . checked( in_array( $key, $options['exception']['plugins'] ), TRUE, FALSE )
				. ' /><label for="ip_geo_block_settings_exception_plugins_' . $key
				. ($active ? '">' : '" class="folding-inactive">') . esc_html( $val['Name'] ) . "</label></li>\n";
		}

		// Plugins area
		$val = esc_html( $path['plugins'] );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_plugins'
			. '" name="ip_geo_block_settings[rewrite][plugins]" '
			. ' value="1"' . checked( $options['rewrite']['plugins'], TRUE, FALSE )
			. disabled( $options['rewrite']['plugins'], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_plugins"><dfn title="'
			. ( $config ? sprintf( $desc[2], esc_html( $path['home'] . $path['plugins'] ) . $config ) : $desc[3] )
			. '">' . __( 'Force to load WP core', 'ip-geo-block' )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_validation_plugins',
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Plugins area', 'ip-geo-block' ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'plugins',
				'value' => $options['validation']['plugins'],
				'list' => $list,
				'desc' => array(
					2 => sprintf( $desc[0], $val ),
				),
				'after' => $tmp .
					'<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . "\n" .
					'	<dfn title="' . $desc[1] . '">' . __( 'Exceptions', 'ip-geo-block' ) . "</dfn>\n" .
					'	<a class="ip-geo-block-hide ip-geo-block-icon ip-geo-block-icon-cycle">' . $common[2] . '</a><a class="ip-geo-block-icon ip-geo-block-icon-find ip-geo-block-hide" data-target="plugins">' . $common[3] . "</a>\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<p class="ip-geo-block-find-desc">' . $common[5] . '<span id="ip-geo-block-find-plugins"></span></p>' . "\n" .
					'	</li>' . "\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<ul class="ip-geo-block-list-exceptions" id="ip-geo-block-list-plugins">' . "\n" .
								$exception .
					'		</ul>' . "\n" .
					'	</li>' . "\n" .
					'</ul>'
			)
		);

		// Get all the themes
		$exception = '';
		$installed = wp_get_themes(); // @since 3.4.0
		$activated = wp_get_theme();  // @since 3.4.0
		$activated = $activated->get( 'Name' );

		// List of installed themes
		foreach ( $installed as $key => $val ) {
			$key = esc_attr( $key );
			$active = ( ( $val = $val->get( 'Name' ) ) === $activated );
			$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_exception_themes_' . $key
				. '" name="ip_geo_block_settings[exception][themes][' . $key
				. ']" value="' . $key . '"' . checked( in_array( $key, $options['exception']['themes'] ), TRUE, FALSE )
				. ' /><label for="ip_geo_block_settings_exception_themes_' . $key
				. ($active ? '">' : '" class="folding-inactive">') . esc_html( $val ) . "</label></li>\n";
		}

		// Themes area
		$val = esc_html( $path['themes'] );
		$tmp =  '<input type="checkbox" id="ip_geo_block_settings_rewrite_themes'
			. '" name="ip_geo_block_settings[rewrite][themes]" '
			. ' value="1"' . checked( $options['rewrite']['themes'], TRUE, FALSE )
			. disabled( $options['rewrite']['themes'], -1, FALSE ) . ' />'
			. '<label for="ip_geo_block_settings_rewrite_themes"><dfn title="'
			. ( $config ? sprintf( $desc[2], esc_html( $path['home'] . $path['themes'] ) . $config ) : $desc[3] )
			. '">' . __( 'Force to load WP core', 'ip-geo-block' )
			. '</dfn></label><br />';

		add_settings_field(
			$option_name.'_validation_themes',
			sprintf( $dfn, $val.'&hellip;/*.php', __( 'Themes area', 'ip-geo-block' ) ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'themes',
				'value' => $options['validation']['themes'],
				'list' => $list,
				'desc' => array(
					2 => sprintf( $desc[0], $val ),
				),
				'after' => $tmp .
					'<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . "\n" .
					'	<dfn title="' . $desc[1] . '">' . __( 'Exceptions', 'ip-geo-block' ) . "</dfn>\n" .
					'	<a class="ip-geo-block-hide ip-geo-block-icon ip-geo-block-icon-cycle">' . $common[2] . '</a><a class="ip-geo-block-icon ip-geo-block-icon-find ip-geo-block-hide" data-target="themes">' . $common[3] . "</a>\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<p class="ip-geo-block-find-desc">' . $common[5] . '<span id="ip-geo-block-find-themes"></span></p>' . "\n" .
					'	</li>' . "\n" .
					'	<li class="ip-geo-block-hide">' . "\n" .
					'		<ul class="ip-geo-block-list-exceptions" id="ip-geo-block-list-themes">' . "\n" .
								$exception .
					'		</ul>' . "\n" .
					'	</li>' . "\n" .
					'</ul>'
			)
		);

		/*----------------------------------------*
		 * Front-end target settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-public',
			array( __( 'Front-end target settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/front-end-target-settings.html" title="Front-end target settings | IP Geo Block">' . $common[4] . '</a>' ),
			array( __CLASS__, 'note_public' ),
			$option_slug
		);

		// Public facing pages
		add_settings_field(
			$option_name.'_validation_public',
			$target['public'],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'public',
				'value' => $options['validation']['public'],
				'text' => __( 'Block by country', 'ip-geo-block' ),
			)
		);

		// Matching rule
		add_settings_field(
			$option_name.'_public_matching_rule',
			'<dfn title="' . $rule_desc[0] . '">' . __( 'Matching rule', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'matching_rule',
				'value' => $options['public']['matching_rule'],
				'list' => array( -1 => __( 'Follow &#8220;Validation rules and behavior&#8221;', 'ip-geo-block' ) ) + $rule,
			)
		);

		// Country code for matching rule (ISO 3166-1 alpha-2)
		add_settings_field(
			$option_name.'_public_white_list',
			$rule_desc[1],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'white_list',
				'value' => $options['public']['white_list'],
				'after' => $common[0],
				'class' => $options['public']['matching_rule'] == 0 ? '' : 'ip-geo-block-hide',
			)
		);

		add_settings_field(
			$option_name.'_public_black_list',
			$rule_desc[2],
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'black_list',
				'value' => $options['public']['black_list'],
				'after' => $common[0],
				'class' => $options['public']['matching_rule'] == 1 ? '' : 'ip-geo-block-hide',
			)
		);

		// Response code (RFC 2616)
		add_settings_field(
			$option_name.'_public_response_code',
			sprintf( __( '<dfn title="You can configure a different response code from the Back-end. This is useful to prevent violation against your affiliate program.">Response code</dfn> %s', 'ip-geo-block' ), '(<a rel="noreferrer" href="https://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1">RFC 2616</a>)' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'response_code',
				'value' => $options['public']['response_code'],
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
				'class' => $options['public']['matching_rule'] == -1 ? 'ip-geo-block-hide' :'',
			)
		);

		// Redirect URI
		add_settings_field(
			$option_name.'_public_redirect_uri',
			__( '<dfn title="Specify the URL for response code 2xx and 3xx. If it is pointed to a public facing page, visitors would not be blocked on the page to prevent loop of redirection even when you enable [Block by country] in [Front-end target settings] section. Empty URL is altered to your home.">Redirect URL</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'redirect_uri',
				'value' => $options['public']['redirect_uri' ],
				'class' => $options['public']['matching_rule'] != -1 && $options['public']['response_code'] < 400 ? '' : 'ip-geo-block-hide',
				'placeholder' => '/about/',
			)
		);

		// Response message
		add_settings_field(
			$option_name.'_public_response_msg',
			__( '<dfn title="Specify the message for response code 4xx and 5xx.">Response message</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'response_msg',
				'value' => $options['public']['response_msg' ],
				'class' => $options['public']['matching_rule'] != -1 && $options['public']['response_code'] >= 400 ? '' : 'ip-geo-block-hide',
			)
		);

		// List of page
		$exception = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual page as a blocking target.">Page</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide">' . $common[2] . '</a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_pages();
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->post_name );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_pages_' . $val . '" name="ip_geo_block_settings[public][target_pages][' . $val . ']" value="1"' . checked( isset( $options['public']['target_pages'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_pages_' . $val . '">' . esc_html( $key->post_title ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of post type
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual post type on a single page as a blocking target.">Post type</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide">' . $common[2] . '</a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_post_types( array( 'public' => TRUE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_posts_' . $val . '" name="ip_geo_block_settings[public][target_posts][' . $val . ']" value="1"' . checked( isset( $options['public']['target_posts'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_posts_' . $val . '">' . esc_html( $key ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of category
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual category on a single page or archive page as a blocking target.">Category</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide">' . $common[2] . '</a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_categories( array( 'hide_empty' => FALSE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->slug );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_cates_' . $val . '" name="ip_geo_block_settings[public][target_cates][' . $val . ']" value="1"' . checked( isset( $options['public']['target_cates'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_cates_' . $val . '">' . esc_html( $key->name ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// List of tag
		$exception .= '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the individual tag on a single page or archive page as a blocking target.">Tag</dfn>', 'ip-geo-block' ) . '<a class="ip-geo-block-icon ip-geo-block-icon-cycle ip-geo-block-hide">' . $common[2] . '</a>' . "\n<li class=\"ip-geo-block-hide\"><ul>\n";
		$tmp = get_tags( array( 'hide_empty' => FALSE ) );
		if ( ! empty( $tmp ) ) {
			foreach ( $tmp as $key ) {
				$val = esc_attr( $key->slug );
				$exception .= '<li><input type="checkbox" id="ip_geo_block_settings_public_target_tags_' . $val . '" name="ip_geo_block_settings[public][target_tags][' . $val . ']" value="1"' . checked( isset( $options['public']['target_tags'][ $val ] ), TRUE, FALSE ) . ' />';
				$exception .= '<label for="ip_geo_block_settings_public_target_tags_' . $val . '">' . esc_html( $key->name ) . '</label></li>' . "\n";
			}
		}
		$exception .= '</ul></li></ul>' . "\n";

		// Validation target
		add_settings_field(
			$option_name.'_public_target_rule',
			'<dfn title="' . __( 'Specify the validation target on front-end.', 'ip-geo-block' ) . '">' . __( 'Validation target', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'target_rule',
				'value' => $options['public']['target_rule'],
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

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Excluded action
		add_settings_field(
			$option_name.'_exception_public',
			'<dfn title="' . __( 'Specify the name of actions as exception that is invariably blocked.', 'ip-geo-block' ) . '">' . __( 'Excluded actions', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'exception',
				'sub-field' => 'public',
				'value' => implode( ',', $options['exception']['public'] ),
				'after' => $common[0],
			)
		);
endif;

		// Badly-behaved bots and crawlers
		$exception  = '<ul class="ip-geo-block-settings-folding ip-geo-block-dropup">' . __( '<dfn title="Specify the frequency of request for certain period of time.">Blocking condition</dfn>', 'ip-geo-block' ) . "\n<li class=\"ip-geo-block-hide\"><ul>\n<li>";
		$exception .= sprintf(
			__( 'More than %1$s page view (PV) in %2$s seconds', 'ip-geo-block' ),
			'<input type="number" id="ip_geo_block_settings_behavior_view" name="ip_geo_block_settings[behavior][view]" class="regular-text code" value="' . (int)$options['behavior']['view'] . '" placeholder="7" min="1" max="99" maxlength="3" />',
			'<input type="number" id="ip_geo_block_settings_behavior_time" name="ip_geo_block_settings[behavior][time]" class="regular-text code" value="' . (int)$options['behavior']['time'] . '" placeholder="5" min="1" max="99" maxlength="3" /> '
		);
		$exception .= "</li>\n</ul></li></ul>\n";

		add_settings_field(
			$option_name.'_public_behavior',
			__( '<dfn title="It will validate the frequency of request.">Block badly-behaved bots and crawlers</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'behavior',
				'value' => $options['public']['behavior'],
				'after' => $exception,
			)
		);

		// UA string and qualification
		add_settings_field(
			$option_name.'_public_ua_list',
			'<dfn title="' . __( 'A part of user agent string and a qualification connected with a separator that indicates an applicable rule and can be &#8220;:&#8221; (pass) or &#8220;#&#8221; (block). A &#8220;qualification&#8221; can be &#8220;DNS&#8221;, &#8220;FEED&#8221;, country code or IP address with CIDR. A negative operator &#8220;!&#8221; can be placed just before a &#8220;qualification&#8221;.', 'ip-geo-block' ) . '">' . __( 'UA string and qualification', 'ip-geo-block' ) . '</dfn>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'textarea',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'ua_list',
				'value' => $options['public']['ua_list'],
				'after' => $common[1],
			)
		);

		// Reverse DNS lookup
		add_settings_field(
			$option_name.'_public_dnslkup',
			'<div class="ip-geo-block-subitem"><dfn title="' . __( 'It enables to verify the host by reverse DNS lookup which would spend some server resources. If it is disabled, &#8220;HOST&#8221; and &#8220;HOST=&hellip;&#8221;in &#8220;UA string and qualification&#8221; will always return &#8220;true&#8221;.', 'ip-geo-block' ) . '">' . __( 'Reverse DNS lookup', 'ip-geo-block' ) . '</dfn></div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'public',
				'sub-field' => 'dnslkup',
				'value' => $options['public']['dnslkup'],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);

		/*----------------------------------------*
		 * Privacy and record settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-recording',
			array( __( 'Privacy and record settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/privacy-and-record-settings.html" title="Privacy and record settings | IP Geo Block">' . $common[4] . '</a>' ),
			array( __CLASS__, 'note_privacy' ),
			$option_slug
		);

		// Anonymize IP address
		add_settings_field(
			$option_name.'_anonymize',
			__( '<dfn title="IP address is always encrypted on recording in Cache and Logs. Moreover, this option replaces the end of IP address with &#8220;***&#8221; to make it anonymous.">Anonymize IP address</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'anonymize',
				'value' => ! empty( $options['anonymize'] ),
			)
		);

		// Do not send IP address to external APIs
		add_settings_field(
			$option_name.'_restrict_api',
			__( '<dfn title="This option restricts not to send IP address to the external Geolocation APIs.">Do not send IP address to external APIs</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'restrict_api',
				'value' => ! empty( $options['restrict_api'] ),
			)
		);

		// Record IP address cache
		add_settings_field(
			$option_name.'_cache_hold',
			__( '<dfn title="This option enables to record the IP address, country code and failure counter of login attempts into the cache on database to minimize the impact on site speed.">Record &#8220;IP address cache&#8221;</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'cache_hold',
				'value' => $options['cache_hold'],
			)
		);

		// Expiration time [sec] for each entry
		add_settings_field(
			$option_name.'_cache_time',
			'<div class="ip-geo-block-subitem">' . __( '<dfn title="If user authentication fails consecutively beyond &#8220;Max number of failed login attempts per IP address&#8221;, subsequent login will also be prohibited for this period.">Expiration time [sec] for each entry</dfn>', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'cache_time',
				'value' => $options['cache_time'],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);

		// Record Validation logs
		add_settings_field(
			$option_name.'_validation_reclogs',
			__( '<dfn title="This option enables to record the validation logs including IP addresses.">Record &#8220;Validation logs&#8221;</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'reclogs',
				'value' => $options['validation']['reclogs'],
				'list' => array(
					0 => __( 'Disable',                 'ip-geo-block' ),
					1 => __( 'When blocked',            'ip-geo-block' ),
					2 => __( 'When passed',             'ip-geo-block' ),
					6 => __( 'When &#8220;blocked&#8221; or &#8220;passed (not in whitelist)&#8221;', 'ip-geo-block' ),
					3 => __( 'Unauthenticated visitor', 'ip-geo-block' ),
					4 => __( 'Authenticated user',      'ip-geo-block' ),
					5 => __( 'All the validation',      'ip-geo-block' ),
				),
			)
		);

		// Expiration time [days] for each entry
		add_settings_field(
			$option_name.'_validation_explogs',
			'<div class="ip-geo-block-subitem">' . sprintf( __( '<dfn title="The maximum number of entries in the logs is also limited to %d.">Expiration time [days] for each entry</dfn>', 'ip-geo-block' ), $options['validation']['maxlogs'] ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'explogs',
				'value' => $options['validation']['explogs'],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);

		// $_POST key to record with value
		add_settings_field(
			$option_name.'_validation_postkey',
			'<div class="ip-geo-block-subitem">' . __( '<dfn title="e.g. action, comment, log, pwd, FILES">$_POST key to record with value</dfn>', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'postkey',
				'value' => $options['validation']['postkey'],
				'after' => $common[0],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Maximum entries in Logs
		add_settings_field(
			$option_name.'_validation_maxlogs',
			'<div class="ip-geo-block-subitem">' . __( 'Maximum entries in &#8220;Logs&#8221;', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'maxlogs',
				'value' => $options['validation']['maxlogs'],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);

		// Live update
		add_settings_field(
			$option_name.'_live_update',
			'<div class="ip-geo-block-subitem">' . __( '<dfn title="Select SQLite database source.">Database source of SQLite for &#8220;Live update&#8221;</dfn>', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'live_update',
				'sub-field' => 'in_memory',
				'value' => extension_loaded( 'pdo_sqlite' ) ? $options['live_update']['in_memory'] : -1,
				'class' => 'ip-geo-block-subitem-parent',
				'list' => array(
					-1 => NULL,
					 0 => __( 'Ordinary file', 'ip-geo-block' ),
					 1 => __( 'In-Memory', 'ip-geo-block' ),
				),
				'desc' => array(
					-1 => __( 'PDO_SQLITE driver not available','ip-geo-block' ),
					 0 => __( 'It takes a few tens of milliseconds as overhead. It can be safely used without conflict with other plugins.', 'ip-geo-block' ),
					 1 => __( 'It takes a few milliseconds as overhead. There is a possibility of conflict with other plugins using this method.', 'ip-geo-block' ),
				),
			)
		);

		// Reset data source of live log
		add_settings_field(
			$option_name.'_reset_live',
			'<div class="ip-geo-block-subitem">' . __( 'Reset database source of &#8220;Live update&#8221;', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'reset_live',
				'value' => __( 'Reset now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-reset-live"></div>',
				'class' => 'ip-geo-block-subitem-parent',
			)
		);
endif;

		// Get the next schedule of cron
		$tmp = wp_next_scheduled( IP_Geo_Block::CACHE_NAME );
		$tmp = $tmp ? IP_Geo_Block_Util::localdate( $tmp ) : '<span class="ip-geo-block-warn">' . __( 'Task could not be found in WP-Cron. Please try to deactivate this plugin once and activate again.', 'ip-geo-block' ). '</span>';

		// Interval [sec] to cleanup expired entries of IP address
		add_settings_field(
			$option_name.'_cache_time_gc',
			__( '<dfn title="This option enables to schedule the WP-Cron event to remove the expired entries from &#8220;IP address cache&#8221; and &#8220;Validation logs&#8221;.">Interval [sec] to cleanup expired entries of IP address</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'cache_time_gc',
				'value' => $options['cache_time_gc'],
				'after' => '<p class="ip-geo-block-desc">' . sprintf( __( 'Next schedule: %s', 'ip-geo-block'), $tmp ) . '</p>',
			)
		);

		// Record Statistics of validation
		add_settings_field(
			$option_name.'_save_statistics',
			__( '<dfn title="This option enables to record the number blocked countries and the number of blocked requests per day.">Record &#8220;Statistics of validation&#8221;</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'save_statistics',
				'value' => $options['save_statistics'],
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		add_settings_field(
			$option_name.'_validation_recdays',
			'<div class="ip-geo-block-subitem">' . __( 'Maximum period for &#8220;Statistics&#8221; [days]', 'ip-geo-block' ) . '</div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'validation',
				'sub-field' => 'recdays',
				'value' => $options['validation']['recdays'],
				'class' => 'ip-geo-block-subitem-parent',
			)
		);
endif;

		// Remove all settings and records at uninstallation
		add_settings_field(
			$option_name.'_clean_uninstall',
			__( 'Remove all settings and records at uninstallation', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'clean_uninstall',
				'value' => $options['clean_uninstall'],
			)
		);

		/*----------------------------------------*
		 * Geolocation API settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-provider',
			array( __( 'Geolocation API settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/geolocation-api-settings.html" title="Geolocation API settings | IP Geo Block">' . $common[4] . '</a>' ),
			array( __CLASS__, 'note_services' ),
			$option_slug
		);

		// Local DBs and APIs
		$provider  = IP_Geo_Block_Provider::get_providers( 'key' ); // all available providers
		$providers = IP_Geo_Block_Provider::get_addons( $options['providers'], TRUE ); // only local

		// Disable 3rd parties API
		if ( ! empty( $options['restrict_api'] ) ) {
			foreach ( array_keys( $provider ) as $key ) {
				if ( ! in_array( $key, $providers, TRUE ) ) {
					$provider[ $key ] = is_string( $provider[ $key ] ) ? '-1' : -1;
				}
			}
		}

		// API selection and key settings
		add_settings_field(
			$option_name.'_providers',
			__( '<dfn title="IP address cache and local databases are scanned at the top priority.">API selection and key settings</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'check-provider',
				'option' => $option_name,
				'field' => 'providers',
				'value' => $options['providers'],
				'local' => $providers,
				'providers' => $provider,
				'titles' => IP_Geo_Block_Provider::get_providers( 'type' ),
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// Timeout for network API
		add_settings_field(
			$option_name.'_timeout',
			__( 'Timeout for network API [sec]', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'timeout',
				'value' => $options['timeout'],
			)
		);
endif;

		/*----------------------------------------*
		 * Local database settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-database',
			array( __( 'Local database settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/geolocation-api-library.html" title="Geolocation API library | IP Geo Block">' . $common[4] . '</a>' ),
			array( __CLASS__, 'note_database' ),
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

		// Get the next schedule of cron
		if ( ! ( $tmp = wp_next_scheduled( IP_Geo_Block::CRON_NAME, array( FALSE ) ) ) ) {
			if ( is_multisite() ) {
				global $wpdb;
				$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs` ORDER BY `blog_id` ASC" );
				switch_to_blog( $blog_ids[0] ); // main blog
				$tmp = wp_next_scheduled( IP_Geo_Block::CRON_NAME, array( FALSE ) );
				restore_current_blog();
			} else {
				$tmp = wp_next_scheduled( IP_Geo_Block::CRON_NAME, array( FALSE ) );
			}
		}
		$tmp = $tmp ? IP_Geo_Block_Util::localdate( $tmp ) : '<span class="ip-geo-block-warn">' . __( 'Task could not be found in WP-Cron. Please try to deactivate this plugin once and activate again.', 'ip-geo-block' ). '</span>';

		// Auto updating (once a month)
		add_settings_field(
			$option_name.'_update_auto',
			__( 'Auto updating (once a month)', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'update',
				'sub-field' => 'auto',
				'value' => $options['update']['auto'],
				'disabled' => empty( $providers ),
				'after' => $options['update']['auto'] ? '<p class="ip-geo-block-desc">' . sprintf( __( 'Next schedule: %s', 'ip-geo-block'), $tmp ) . '</p>' : '',
			)
		);

		// Download database
		add_settings_field(
			$option_name.'_update_download',
			__( 'Download database', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'update',
				'value' => __( 'Download now', 'ip-geo-block' ),
				'disabled' => empty( $providers ),
				'after' => '<div id="ip-geo-block-download"></div>',
			)
		);

		/*----------------------------------------*
		 * Plugin settings
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-others',
			array( __( 'Plugin settings', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/plugin-settings.html" title="Plugin settings | IP Geo Block">' . $common[4] . '</a>' ),
			NULL,
			$option_slug
		);

		// @see https://vedovini.net/2015/10/using-the-wordpress-settings-api-with-network-admin-pages/
		if ( $context->is_network_admin() ) {
			add_action( 'network_admin_edit_' . IP_Geo_Block::PLUGIN_NAME, array( $context, 'validate_network_settings' ) );

			// Network wide configuration
			add_settings_field(
				$option_name.'_network_wide',
				__( '<dfn title="Synchronize all settings over the network wide.">Network wide settings</dfn>', 'ip-geo-block' ),
				array( $context, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'checkbox',
					'option' => $option_name,
					'field' => 'network_wide',
					'value' => $options['network_wide'],
					'disabled' => ! is_main_site(),
				)
			);
		}

		// Emergency login link
		$key = IP_Geo_Block_Util::get_link();
		add_settings_field(
			$option_name.'_login_link',
			__( '<dfn title="You can access to the login form with a specific key at emergency. Please add the generated link to favorites / bookmarks in your browser as this plugin does not keep the key itself.">Emergency login link</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' => empty( $key ) ?
					'<a class="button-secondary ip-geo-block-primary" id="ip-geo-block-login-link" href="#!">'. __( 'Generate new link',   'ip-geo-block' ) . '</a>&nbsp;' :
					'<a class="button-secondary'                  .'" id="ip-geo-block-login-link" href="#!">'. __( 'Delete current link', 'ip-geo-block' ) . '</a>&nbsp;',
				'after' => '<div id="ip-geo-block-login-loading"></div>',
			)
		);

		// Google Maps API key
		add_settings_field(
			$option_name.'_api_key',
			__( '<dfn title="Valid key for Google Maps JavaScript API. Maps Embed API in iframe can be available without key in case it\'s empty.">Google Maps API key</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'api_key',
				'sub-field' => 'GoogleMap',
				'value' => $options['api_key']['GoogleMap'],
			)
		);

		// Export / Import settings
		add_settings_field(
			$option_name.'_export-import',
			sprintf( '<dfn title="%s">' . __( 'Export / Import settings', 'ip-geo-block' ) . '</dfn>', $desc[4] ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button-secondary" id="ip-geo-block-export" title="' . __( 'Export to the local file',   'ip-geo-block' ) . '" href="#!">'. __( 'Export settings', 'ip-geo-block' ) . '</a>&nbsp;' .
					'<a class="button-secondary" id="ip-geo-block-import" title="' . __( 'Import from the local file', 'ip-geo-block' ) . '" href="#!">'. __( 'Import settings', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="ip-geo-block-export-import"></div>',
			)
		);

		// Pre-defined settings
		add_settings_field(
			$option_name.'_pre-defined',
			sprintf( '<dfn title="%s">' . __( 'Import pre-defined settings', 'ip-geo-block' ) . '</dfn>', $desc[4] ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button-secondary"'                  .' id="ip-geo-block-default" title="' . __( 'Import the default settings to revert to the &#8220;Right after installing&#8221; state', 'ip-geo-block' ) . '" href="#!">' . __( 'Default settings', 'ip-geo-block' ) . '</a>&nbsp;' .
					'<a class="button-secondary ip-geo-block-primary" id="ip-geo-block-preferred" title="' . __( 'Import the preferred settings mainly by enabling Zero-day Exploit Prevention for the &#8220;Back-end target settings&#8221;', 'ip-geo-block' ) . '" href="#!">' . __( 'Best for Back-end', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="ip-geo-block-pre-defined"></div>',
			)
		);

if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ):
		// DB tables for this plugin
		add_settings_field(
			$option_name.'_diag_tables',
			__( 'Diagnose all DB tables', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'diag_tables',
				'value' => __( 'Diagnose now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-init-table"></div>',
			)
		);
endif;

		// Diagnostic information
		add_settings_field(
			$option_name.'_show-info',
			__( '<dfn title="When you have some unexpected blocking experiences, please press the button to find the blocked requests at the end of dumped information which may help you to solve the issues.">Diagnostic information</dfn><br />[ <a rel="noreferrer" href="https://wordpress.org/support/plugin/ip-geo-block" title="[IP Geo Block] Support | WordPress.org">support forum</a> ]', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' =>
					'<a class="button-secondary" id="ip-geo-block-show-info" title="' . __( 'Please copy &amp; paste when submitting your issue to support forum', 'ip-geo-block' ) . '" href="#!">' . __( 'Show information', 'ip-geo-block' ) . '</a>&nbsp;',
				'after' => '<div id="ip-geo-block-wp-info"></div>',
			)
		);

	}

	/**
	 * Subsidiary note for the sections
	 *
	 */
	public static function note_target() {
	}

	public static function note_services() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', __( 'While Geolite2 / Maxmind and IP2Location will fetch the local databases, others will pass an IP address to the 3rd parties\' API via HTTP.', 'ip-geo-block' ), '</li>', "\n",
				'<li>', __( 'Please select the appropriate APIs to fit the privacy law / regulation in your country / region.', 'ip-geo-block' ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_database() {
		// https://pecl.php.net/package/phar
		if ( ! version_compare( PHP_VERSION, '5.4.0', '>=' ) || ! class_exists( 'PharData', FALSE ) ) {
			echo
				'<ul class="ip-geo-block-note">', "\n",
					'<li>', sprintf( __( 'Maxmind GeoLite2 databases and APIs need PHP version 5.4.0+ and %sPECL phar 2.0.0+%s.', 'ip-geo-block' ), '<a href="https://pecl.php.net/package/phar" title="PECL :: Package :: phar">', '</a>' ), '</li>', "\n",
				'</ul>', "\n";
		}
	}

	public static function note_public() {
		echo
			'<ul class="ip-geo-block-note">', "\n",
				'<li>', sprintf( __( 'Please refer to "%sLiving with cache plugin%s" for compatibility with cache plugins.', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/living-with-caching-plugin.html" title="Living with caching plugin | IP Geo Block">', '</a>' ), '</li>', "\n",
			'</ul>', "\n";
	}

	public static function note_privacy() {
	}

}