<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

function ip_geo_block_tab_settings( $context ) {
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];
	$options = IP_Geo_Block::get_option( 'settings' );

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
	 * Geolocation service settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-provider';
	add_settings_section(
		$section,
		__( 'Geolocation API settings', IP_Geo_Block::TEXT_DOMAIN ),
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
		__( 'API selection and key settings', IP_Geo_Block::TEXT_DOMAIN ),
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
	 * Validation settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-validation';
	add_settings_section(
		$section,
		__( 'Validation settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	// same as in tab-accesslog.php
	$title = array(
		'comment' => __( '<dfn title="Validate post to wp-comments-post.php">Comment post</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'xmlrpc'  => __( '<dfn title="Validate access to xmlrpc.php">XML-RPC</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'login'   => __( '<dfn title="Validate access to wp-login.php">Login form</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'admin'   => __( '<dfn title="Validate access to wp-admin/*.php">Admin area</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
	);
	$admin = array_pop( $title );

	$field = 'validation';
	foreach ( $title as $key => $val ) {
		add_settings_field(
			$option_name . "_${field}_${key}",
			$title[ $key ],
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
	}

	$key = 'admin';
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
			'list' => array(
				__( 'Disable',                  IP_Geo_Block::TEXT_DOMAIN ) => 0,
				__( 'Enable',                   IP_Geo_Block::TEXT_DOMAIN ) => 1,
				__( 'Prevent zero-day exploit', IP_Geo_Block::TEXT_DOMAIN ) => 2,
			),
			'after' => '<div style="display:none" id="ip-geo-block-admin-desc">' . __( 'It will block a malicious request to <code>wp-admin/(admin|admin-ajax|admin-post).php</code> besides the country code. Because this is an experimental feature, please open a new issue at <a class="ip-geo-block-link" href="http://wordpress.org/support/plugin/ip-geo-block" title="WordPress &#8250; Support &raquo; IP Geo Block" target=_blank>support forum</a> if you have any troubles with it.', IP_Geo_Block::TEXT_DOMAIN ) . '</div>',
		)
	);

	$field = 'matching_rule';
	add_settings_field(
		$option_name . "_$field",
		__( 'Matching rule', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'select',
			'option' => $option_name,
			'field' => $field,
			'value' => $options[ $field ],
			'list' => array(
				__( 'White list', IP_Geo_Block::TEXT_DOMAIN ) => 0,
				__( 'Black list', IP_Geo_Block::TEXT_DOMAIN ) => 1,
			),
		)
	);

	$field = 'white_list';
	add_settings_field(
		$option_name . "_$field",
		sprintf( __( '<dfn title="If empty then pass through">White list</dfn> %s', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
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
		sprintf( __( '<dfn title="If empty then pass through">Black list</dfn> %s', IP_Geo_Block::TEXT_DOMAIN ), '(<a class="ip-geo-block-link" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements" title="ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia" target=_blank>ISO 3166-1 alpha-2</a>)' ),
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
	 * Record settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-recording';
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
		__( '<dfn title="ex) log, pwd, comment">$_POST keys to be recorded with their values in logs</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
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
	 * Maxmind settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-maxmind';
	add_settings_section(
		$section,
		__( 'Maxmind GeoLite settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'maxmind';
	add_settings_field(
		$option_name . "_${field}_ipv4",
		__( 'Path to database (IPv4)', IP_Geo_Block::TEXT_DOMAIN ),
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
		__( 'Path to database (IPv6)', IP_Geo_Block::TEXT_DOMAIN ),
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
			'after' => '<div id="ip-geo-block-download"></div>',
		)
	);

	/*----------------------------------------*
	 * Submission settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-submission';
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
	 * Cache settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-cache';
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
		__( 'Expiration time [sec]', IP_Geo_Block::TEXT_DOMAIN ),
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
	 * Plugin settings
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-others';
	add_settings_section(
		$section,
		__( 'Plugin settings', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'clean_uninstall';
	add_settings_field(
		$option_name . "_$field",
		__( 'Remove settings at uninstallation', IP_Geo_Block::TEXT_DOMAIN ),
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

	if ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

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
				'after' => '<div id="ip-geo-block-loading"></div>',
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
				'after' => '<div id="ip-geo-block-loading"></div>',
			)
		);
	}
}