<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

function ip_geo_block_tab_settings( $context ) {
	$plugin_slug = IP_Geo_Block::PLUGIN_SLUG;
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];
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
			'after' => '&nbsp;<a id="'.$plugin_slug.'-scan-code" class="button button-secondary" href="javascript:void(0)" title="' . __( 'Scan all the APIs you selected at Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ) . '">' . __( 'Scan your country code', IP_Geo_Block::TEXT_DOMAIN ) . '</a><div id="'.$plugin_slug.'-scanning"></div>',
		)
	);

	// If the matching rule is not initialized, then add a caution
	$list = ( 0 <= $options['matching_rule'] ? array() : array( -1 => __( 'Disable', IP_Geo_Block::TEXT_DOMAIN ) ) );
	$list += array(
		0 => __( 'White list', IP_Geo_Block::TEXT_DOMAIN ),
		1 => __( 'Black list', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$comma = '<span style="margin-left: 0.2em">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>';
	$dfn = sprintf(
		__( '<dfn title="&#8220;Block by country&#8221; will be bypassed in case of empty. Please consider to include &#8220;ZZ&#8221; which means UNKNOWN especially in case of black list.">Country code for matching rule</dfn>%s', IP_Geo_Block::TEXT_DOMAIN ),
		' (<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)'
	);

	$field = 'matching_rule';
	add_settings_field(
		$option_name.'_'.$field,
		__( '<dfn title="Please select either &#8220;White list&#8221; or &#8220;Black list&#8221;.">Matching rule</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'select',
			'option' => $option_name,
			'field' => $field,
			'value' => $options[ $field ],
			'list' => $list,
		)
	);

	$field = 'white_list';
	add_settings_field(
		$option_name.'_'.$field,
		$dfn,
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
		$dfn,
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

	$field = 'extra_ips';
	$key = 'white_list';
	add_settings_field(
		$option_name.'_'.$field.'_'.$key,
		__( '<dfn title="e.g. &#8220;192.0.64.0/18&#8221; for Jetpack server, &#8220;69.46.36.0/27&#8221; for WordFence server">White list of extra IP addresses prior to country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) .
		' (<a class="ip-geo-block-link" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia" target=_blank>CIDR</a>)',
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

	$key = 'black_list';
	add_settings_field(
		$option_name.'_'.$field.'_'.$key,
		__( '<dfn title="Server level access control is recommended (e.g. .htaccess).">Black list of extra IP addresses prior to country code</dfn>', IP_Geo_Block::TEXT_DOMAIN ) .
		' (<a class="ip-geo-block-link" href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia" target=_blank>CIDR</a>)',
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

	$field = 'response_code';
	add_settings_field(
		$option_name.'_'.$field,
		sprintf( __( 'Response code %s', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="'.$plugin_slug.'-link" href="http://tools.ietf.org/html/rfc2616#section-10" title="RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1" target=_blank>RFC 2616</a>)' ),
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

	/*----------------------------------------*
	 * Validation target settings
	 *----------------------------------------*/
	$section = $plugin_slug . '-validation-target';
	add_settings_section(
		$section,
		__( 'Validation target settings', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_note_target',
		$option_slug
	);

	// same as in tab-accesslog.php
	$dfn = __( '<dfn title="Validate access to %s.">%s</dfn>', IP_Geo_Block::TEXT_DOMAIN );
	$list = array(
		'comment' => sprintf( $dfn, 'wp-comments-post.php', __( 'Comment post', IP_Geo_Block::TEXT_DOMAIN ) ),
		'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',           __( 'XML-RPC',      IP_Geo_Block::TEXT_DOMAIN ) ),
		'login'   => sprintf( $dfn, 'wp-login.php',         __( 'Login form',   IP_Geo_Block::TEXT_DOMAIN ) ),
		'admin'   => sprintf( $dfn, 'wp-admin/*.php',       __( 'Admin area',   IP_Geo_Block::TEXT_DOMAIN ) ),
	);

	$admin = array_pop( $list );
	$login = array_pop( $list );

	$field = 'validation';
	foreach ( $list as $key => $val ) {
		add_settings_field(
			$option_name.'_'.$field.'_'.$key,
			$list[ $key ],
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
	}

	$key = 'login';
	add_settings_field(
		$option_name.'_'.$field.'_'.$key,
		$login,
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
				2 => __( 'Registered users can login as membership from anywhere, but the request for new user registration and lost password is blocked by the country code.', IP_Geo_Block::TEXT_DOMAIN ),
			),
			'after' => '<div class="ip_geo_block_settings_desc"></div>',
		)
	);

	$list = array(
		1 => __( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ),
		2 => __( 'Prevent Zero-day Exploit', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$login = array(
		1 => __( 'It will block a request related to the services for both public facing pages and the dashboard.', IP_Geo_Block::TEXT_DOMAIN ),
		2 => __( 'Regardless of the country code, it will block a malicious request related to the services only for the dashboard.', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$key = 'admin';
	add_settings_field(
		$option_name.'_'.$field.'_'.$key,
		$admin,
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
			'desc' => $login,
		)
	);

	$key = 'ajax';
	$val = esc_html( substr( IP_Geo_Block::$content_dir['admin'], 1 ) );
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
			'desc' => $login,
		)
	);

	array_unshift( $list, __( 'Disable', IP_Geo_Block::TEXT_DOMAIN ) );
	$admin = __( 'Regardless of the country code, it will block a malicious request to <code>%s&hellip;/*.php</code>.', IP_Geo_Block::TEXT_DOMAIN );

	$key = 'plugins';
	$val = esc_html( substr( IP_Geo_Block::$content_dir['plugins'], 1 ) );
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
				2 => sprintf( $admin, $val ),
			),
			'after' => '<div class="ip_geo_block_settings_desc"></div>',
		)
	);

	$key = 'themes';
	$val = esc_html( substr( IP_Geo_Block::$content_dir['themes'], 1 ) );
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
				2 => sprintf( $admin, $val ),
			),
			'after' => '<div class="ip_geo_block_settings_desc"></div>',
		)
	);

	$field = 'signature';
	add_settings_field(
		$option_name.'_'.$field,
		__( '<dfn title="This works independently of &#8220;Block by country&#8221; and &#8220;Prevent Zero-day Exploit&#8221;, and validates malicious signature to prevent disclosing the important files via vulnerable plugins or themes.">Important files</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'value' => $options[ $field ],
			'after' => $comma,
		)
	);

	/*----------------------------------------*
	 * Geolocation service settings
	 *----------------------------------------*/
	$section = $plugin_slug . '-provider';
	add_settings_section(
		$section,
		__( 'Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_note_services',
		$option_slug
	);

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

	$section = $plugin_slug . '-database';
	add_settings_section(
		$section,
		__( 'Local database settings', IP_Geo_Block::TEXT_DOMAIN ),
		empty( $providers ) ? 'ip_geo_block_note_database' : NULL,
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
				__( 'database', IP_Geo_Block::TEXT_DOMAIN ),
				__( 'Last update: %s', IP_Geo_Block::TEXT_DOMAIN )
			);
		}
	}

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
			'after' => '<div id="'.$plugin_slug.'-download"></div>',
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
			'after' => '<div id="'.$plugin_slug.'-delete_table"></div>',
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
			'after' => '<div id="'.$plugin_slug.'-create_table"></div>',
		)
	);
endif;
}

/**
 * Subsidiary note
 *
 */
function ip_geo_block_note_target() {
	echo
		'<ul class="ip-geo-block-note">', "\n",
			'<li>', __( 'To enhance the protection ability, please refer to &#8220;<a href="http://www.ipgeoblock.com/codex/the-best-practice-of-target-settings.html" title="Prevent exposure of wp-config.php | IP Geo Block">The best practice of target settings</a>&#8220;.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
			'<li>', __( 'If you have any troubles with these, please open an issue at <a class="ip-geo-block-link" href="http://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block" target=_blank>support forum</a>.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
		'</ul>', "\n";
}

function ip_geo_block_note_services() {
	echo
		'<ul class="ip-geo-block-note">', "\n",
			'<li>', __('While Maxmind and IP2Location will fetch the local database, others will pass an IP address to the APIs via HTTP.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
			'<li>', __('Please select the appropriate APIs to fit the privacy law in your country.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
		'</ul>', "\n";
}

function ip_geo_block_note_database() {
	echo
		'<ul class="ip-geo-block-note">', "\n",
			'<li>', __( 'Please download <a href="https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip" title="Download the contents of tokkonopapa/WordPress-IP-Geo-API as a zip file">ZIP file</a> from <a href="https://github.com/tokkonopapa/WordPress-IP-Geo-API" title="tokkonopapa/WordPress-IP-Geo-API - GitHub">WordPress-IP-Geo-API</a> and upload <code>ip-geo-api</code> to your <code>wp-content</code>.', IP_Geo_Block::TEXT_DOMAIN ), '</li>', "\n",
		'</ul>', "\n";
}