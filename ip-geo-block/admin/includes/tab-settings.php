<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );
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
	$section = "${plugin_slug}-validation-rule";
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
		$option_name . "_$field",
		__( '<dfn title="You can confirm the appropriate Geolocation APIs and country code by referring &#8217;Scan your country code&#8217;.">Your IP address / Country</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'html',
			'option' => $option_name,
			'field' => $field,
			'value' => esc_html( $key['ip'] . ' / ' . ( $key['code'] ? $key['code'] . ' (' . $key['provider'] . ')' : __( 'UNKNOWN', IP_Geo_Block::TEXT_DOMAIN ) ) ),
			'after' => "&nbsp;<a id=\"${plugin_slug}-scan-code\" class=\"button button-secondary\" href=\"javascript:void(0)\" title=\"" . __( 'Scan all the APIs you selected at Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ) . '">' . __( 'Scan your country code', IP_Geo_Block::TEXT_DOMAIN ) . "</a><div id=\"${plugin_slug}-scanning\"></div>",
		)
	);

	// If the matching rule is not initialized, then add a caution
	$list = array();
	if ( -1 == $options['matching_rule'] )
		$list = array( __( 'Disable', IP_Geo_Block::TEXT_DOMAIN ) => -1 );

	$list += array(
		__( 'White list', IP_Geo_Block::TEXT_DOMAIN ) => 0,
		__( 'Black list', IP_Geo_Block::TEXT_DOMAIN ) => 1,
	);

	$field = 'matching_rule';
	add_settings_field(
		$option_name . "_$field",
		__( '<dfn title="Please select either &#8217;White list&#8217; or &#8217;Black list&#8217;.">Matching rule</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
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
		$option_name . "_$field",
		sprintf( __( '<dfn title="&#8217;Block by country&#8217; will be bypassed in case of empty.">%s</dfn> %s', IP_Geo_Block::TEXT_DOMAIN ), __( 'White list', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'value' => $options[ $field ],
			'after' => '<span style="margin-left: 0.2em">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>',
		)
	);

	$field = 'black_list';
	add_settings_field(
		$option_name . "_$field",
		sprintf( __( '<dfn title="&#8217;Block by country&#8217; will be bypassed in case of empty.">%s</dfn> %s', IP_Geo_Block::TEXT_DOMAIN ), __( 'Black list', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'value' => $options[ $field ],
			'after' => '<span style="margin-left: 0.2em">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>',
		)
	);

	$key = 'proxy';
	$field = 'validation';
	add_settings_field(
		$option_name . "_${field}_${key}",
		__( '<dfn title="ex) HTTP_X_FORWARDED_FOR">$_SERVER keys for extra IPs</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'sub-field' => $key,
			'value' => $options[ $field ][ $key ],
			'after' => '<span style="margin-left: 0.2em">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>',
		)
	);

	$field = 'response_code';
	add_settings_field(
		$option_name . "_$field",
		sprintf( __( 'Response code %s', IP_Geo_Block::TEXT_DOMAIN ), "(<a class=\"${plugin_slug}-link\" href=\"http://tools.ietf.org/html/rfc2616#section-10\" title=\"RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1\" target=_blank>RFC 2616</a>)" ),
		array( $context, 'callback_field' ),
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
	 * Validation target settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-validation-target";
	add_settings_section(
		$section,
		__( 'Validation target settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	// same as in tab-accesslog.php
	$list = array(
		'comment' => __( '<dfn title="Validate post to wp-comments-post.php">Comment post</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'xmlrpc'  => __( '<dfn title="Validate access to xmlrpc.php">XML-RPC</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'login'   => __( '<dfn title="Validate access to wp-login.php">Login form</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'admin'   => __( '<dfn title="Validate access to wp-admin/*.php">Admin area</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$admin = array_pop( $list );
	$login = array_pop( $list );

	$field = 'validation';
	foreach ( $list as $key => $val ) {
		add_settings_field(
			$option_name . "_${field}_${key}",
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
		$option_name . "_${field}_${key}",
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
				__( 'Disable', IP_Geo_Block::TEXT_DOMAIN ) => 0,
				__( 'Block by country (register, lost password)', IP_Geo_Block::TEXT_DOMAIN ) => 2,
				__( 'Block by country', IP_Geo_Block::TEXT_DOMAIN ) => 1,
			),
			'after' => '<div style="display:none" class="ip_geo_block_settings_validation_desc">' . __( 'Registered users can login as membership from anywhere, but the request for new user registration and lost password is blocked by the country code.', IP_Geo_Block::TEXT_DOMAIN ) . '</div>',
		)
	);

	$list = array(
		__( 'Disable',                  IP_Geo_Block::TEXT_DOMAIN ) => 0,
		__( 'Block by country',         IP_Geo_Block::TEXT_DOMAIN ) => 1,
		__( 'Prevent zero-day exploit', IP_Geo_Block::TEXT_DOMAIN ) => 2,
	);

	$desc = array(
		'<div style="display:none" class="ip_geo_block_settings_validation_desc">',
		__( '<dfn title="Validate access to %s">%s</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		__( 'Besides the country code, it will block malicious accesses to the PHP files under <code>%s</code>.', IP_Geo_Block::TEXT_DOMAIN ),
		__( 'Besides the country code, it will block malicious accesses to <code>%s</code>.', IP_Geo_Block::TEXT_DOMAIN ),
		__( 'Because this is an experimental feature, please open an issue at <a class="ip-geo-block-link" href="http://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block" target=_blank>support forum</a> if you have any troubles with it.</div>', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$key = 'admin';
	$val = substr( IP_Geo_Block::$content_dir['admin'], 1 );
	add_settings_field(
		$option_name . "_${field}_${key}",
		$admin,
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
			'after' => $desc[0] . sprintf( $desc[2], $val ) . $desc[4],
		)
	);

	$key = 'ajax';
	add_settings_field(
		$option_name . "_${field}_${key}",
		sprintf( $desc[1], "{$val}admin-(ajax|post).php", __( 'Admin ajax/post', IP_Geo_Block::TEXT_DOMAIN ) ),
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
			'after' => $desc[0] . sprintf( $desc[3], "{$val}admin-(ajax|post).php" ) . $desc[4],
		)
	);

	$key = 'plugins';
	$val = substr( IP_Geo_Block::$content_dir['plugins'], 1 );
	add_settings_field(
		$option_name . "_${field}_${key}",
		sprintf( $desc[1], "{$val}&hellip;/*.php", __( 'Plugins area', IP_Geo_Block::TEXT_DOMAIN ) ),
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
			'after' => $desc[0] . sprintf( $desc[2], $val ) . $desc[4],
		)
	);

	$key = 'themes';
	$val = substr( IP_Geo_Block::$content_dir['themes'], 1 );
	add_settings_field(
		$option_name . "_${field}_${key}",
		sprintf( $desc[1], "{$val}&hellip;/*.php", __( 'Themes area', IP_Geo_Block::TEXT_DOMAIN ) ),
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
			'after' => $desc[0] . sprintf( $desc[2], $val ) . $desc[4],
		)
	);

	/*----------------------------------------*
	 * Geolocation service settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-provider";
	add_settings_section(
		$section,
		__( 'Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'providers';
	add_settings_field(
		$option_name . "_$field",
		__( '<dfn title="Cache and Maxmind are scaned at the top priority">API selection and key settings</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
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
	 * Maxmind settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-maxmind";
	add_settings_section(
		$section,
		__( 'Maxmind GeoLite settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'maxmind';
	add_settings_field(
		$option_name . "_${field}_ipv4",
		__( 'Path to database', IP_Geo_Block::TEXT_DOMAIN ) . ' (IPv4)',
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'sub-field' => 'ipv4_path',
			'value' => $options[ $field ]['ipv4_path'],
			'disabled' => TRUE,
			'after' => '<br /><p id="ip_geo_block_ipv4" style="margin-left: 0.2em">' .
			sprintf(
				__( 'Last update: %s', IP_Geo_Block::TEXT_DOMAIN ),
				ip_geo_block_localdate( $options[ $field ]['ipv4_last'] )
			) . '</p>',
		)
	);

	add_settings_field(
		$option_name . "_${field}_ipv6",
		__( 'Path to database', IP_Geo_Block::TEXT_DOMAIN ) . ' (IPv6)',
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'sub-field' => 'ipv6_path',
			'value' => $options[ $field ]['ipv6_path'],
			'disabled' => TRUE,
			'after' => '<br /><p id="ip_geo_block_ipv6" style="margin-left: 0.2em">' .
			sprintf(
				__( 'Last update: %s', IP_Geo_Block::TEXT_DOMAIN ),
				ip_geo_block_localdate( $options[ $field ]['ipv6_last'] )
			) . '</p>',
		)
	);

	$field = 'update';
	add_settings_field(
		$option_name . "_${field}_auto",
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
		)
	);

	add_settings_field(
		$option_name . "_${field}_download",
		__( 'Download database', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Download now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => "<div id=\"${plugin_slug}-download\"></div>",
		)
	);

	/*----------------------------------------*
	 * Record settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-recording";
	add_settings_section(
		$section,
		__( 'Record settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'save_statistics';
	add_settings_field(
		$option_name . "_$field",
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
		$option_name . "_${field}_reclogs",
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
				__( 'Disable',              IP_Geo_Block::TEXT_DOMAIN ) => 0,
				__( 'Only when blocked',    IP_Geo_Block::TEXT_DOMAIN ) => 1,
				__( 'Only when passed',     IP_Geo_Block::TEXT_DOMAIN ) => 2,
				__( 'Unauthenticated user', IP_Geo_Block::TEXT_DOMAIN ) => 3,
				__( 'Authenticated user',   IP_Geo_Block::TEXT_DOMAIN ) => 4,
				__( 'All of validation',    IP_Geo_Block::TEXT_DOMAIN ) => 5,
			),
		)
	);

	add_settings_field(
		$option_name . "_${field}_postkey",
		__( '<dfn title="ex) action, comment, log, pwd">$_POST keys to be recorded with their values in logs</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'text',
			'option' => $option_name,
			'field' => $field,
			'sub-field' => 'postkey',
			'value' => $options[ $field ]['postkey'],
			'after' => '<span style="margin-left: 0.2em">' . __( '(comma separated)', IP_Geo_Block::TEXT_DOMAIN ) . '</span>',
		)
	);

	/*----------------------------------------*
	 * Cache settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-cache";
	add_settings_section(
		$section,
		__( 'Cache settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'cache_hold';
	add_settings_field(
		$option_name . "_$field",
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
		$option_name . "_$field",
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
	$section = "${plugin_slug}-submission";
	add_settings_section(
		$section,
		__( 'Submission settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'comment';
	add_settings_field(
		$option_name . "_$field",
		__( 'Text message on comment form', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'comment-msg',
			'option' => $option_name,
			'field' => $field,
			'sub-field' => 'pos',
			'value' => $options[ $field ]['pos'],
			'list' => array(
				__( 'None',   IP_Geo_Block::TEXT_DOMAIN ) => 0,
				__( 'Top',    IP_Geo_Block::TEXT_DOMAIN ) => 1,
				__( 'Bottom', IP_Geo_Block::TEXT_DOMAIN ) => 2,
			),
			'text' => $options[ $field ]['msg'], // sanitized at 'comment-msg'
		)
	);

	/*----------------------------------------*
	 * Plugin settings
	 *----------------------------------------*/
	$section = "${plugin_slug}-others";
	add_settings_section(
		$section,
		__( 'Plugin settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'clean_uninstall';
	add_settings_field(
		$option_name . "_$field",
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
		$option_name . "_$field",
		__( 'Delete DB table for validation logs', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Delete now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => "<div id=\"${plugin_slug}-delete_table\"></div>",
		)
	);

	$field = 'create_table';
	add_settings_field(
		$option_name . "_$field",
		__( 'Create DB table for validation logs', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Create now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => "<div id=\"${plugin_slug}-create_table\"></div>",
		)
	);
endif;
}