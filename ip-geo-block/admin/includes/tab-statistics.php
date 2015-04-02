<?php
function ip_geo_block_tab_statistics( $context ) {
	$option_slug = $context->option_slug['statistics'];
	$option_name = $context->option_name['statistics'];
	$options = IP_Geo_Block::get_option( 'statistics' );
	$setting = IP_Geo_Block::get_option( 'settings' );

	register_setting(
		$option_slug,
		$option_name
	);

if ( $setting['save_statistics'] ) :

	/*----------------------------------------*
	 * Statistics of comment post
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-statistics';
	add_settings_section(
		$section,
		__( 'Statistics of validation', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'blocked';
	add_settings_field(
		$option_name . "_$field",
		__( 'Blocked', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'html',
			'option' => $option_name,
			'field' => $field,
			'value' => esc_html( $options[ $field ] ),
		)
	);

	$field = 'unknown';
	add_settings_field(
		$option_name . "_$field",
		__( 'Unknown (blocked)', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'html',
			'option' => $option_name,
			'field' => $field,
			'value' => esc_html( $options[ $field ] ),
		)
	);

	$field = 'countries';
	$html = "<ul class=\"${option_slug}-${field}\">";
	foreach ( $options['countries'] as $key => $val ) {
		$html .= sprintf( "<li>%2s:%5d</li>", esc_html( $key ), (int)$val );
	}
	$html .= "</ul>";

	add_settings_field(
		$option_name . "_$field",
		__( 'Blocked by countries', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
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
		__( 'Blocked by type of IP address', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'html',
			'option' => $option_name,
			'field' => $field,
			'value' => "<table class=\"${option_slug}-${field}\">" .
				"<thead><tr><th>IPv4</th><th>IPv6</th></tr></thead><tbody><tr>" .
				"<td>" . esc_html( $options['IPv4'] ) . "</td>" .
				"<td>" . esc_html( $options['IPv6'] ) . "</td>" .
				"</tr></tbody></table>",
		)
	);

	$field = 'service';
	$html = "<table class=\"${option_slug}-${field}\"><thead><tr>";
	$html .= "<th>" . __( 'Name of API', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "<th>" . __( 'Calls', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "<th>" . __( 'Response [msec]', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "</tr></thead><tbody>";

	foreach ( $options['providers'] as $key => $val ) {
		$html .= "<tr><td>" . esc_html( $key ) . "</td>";
		$html .= "<td>" . sprintf( "%5d", (int)$val['count'] ) . "</td><td>";
		$html .= sprintf( "%5d", (int)(1000.0 * $val['time'] / $val['count']) );
		$html .= "</td></tr>";
	}
	$html .= "</tbody></table>";

	add_settings_field(
		$option_name . "_$field",
		__( 'Average response time of each API', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
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
		__( 'Clear statistics', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Clear now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => '<div id="ip-geo-block-loading"></div>',
		)
	);

else:

	/*----------------------------------------*
	 * Warning
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-statistics';
	add_settings_section(
		$section,
		__( 'Statistics of validation', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_warn_statistics',
		$option_slug
	);

endif;

	/*----------------------------------------*
	 * Statistics of cache
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-cache';
	add_settings_section(
		$section,
		__( 'Statistics of cache', IP_Geo_Block::TEXT_DOMAIN ),
		NULL, // array( $context, 'callback_cache_stat' ),
		$option_slug
	);

	$field = 'cache';
	$html = "<table class=\"${option_slug}-${field}\"><thead><tr>";
	$html .= "<th>" . __( 'IP address', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "<th>" . __( 'Country code / Access', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "<th>" . __( 'Elapsed [sec] / Calls', IP_Geo_Block::TEXT_DOMAIN ) . "</th>";
	$html .= "</tr></thead><tbody>";

	if ( $transient = get_transient( IP_Geo_Block::CACHE_KEY ) ) {
		$time = time();
		$debug = defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG;
		foreach ( $transient as $key => $val ) {
			if ( empty( $val['auth'] ) || $debug ) { // hide authenticated user
				$html .= "<tr><td>" . esc_html( $key ) . "</td>";
				$html .= "<td>"     . esc_html( $val['code'] ) . " / ";
				$html .= "<small>"  . esc_html( $val['hook'] ) . "</small></td>";
				$html .= "<td>" . ( $time - (int)$val['time'] ) . " / ";
				$html .= $setting['save_statistics'] ? (int)$val['call'] : "-";
				if ( $debug ) {
					$user = get_user_by( 'id', intval( $val['auth'] ) );
					$html .= " " . esc_html( $user ? $user->get( 'user_login' ) : "" );
					$html .= " / fail:" . intval( $val['fail'] );
				}
				$html .= "</td></tr>";
			}
		}
	}
	$html .= "</tbody></table>";

	add_settings_field(
		$option_name . "_$field",
		__( 'IP address in cache', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'html',
			'option' => $option_name,
			'field' => $field,
			'value' => $html,
		)
	);

	$field = 'clear_cache';
	add_settings_field(
		$option_name . "_$field",
		__( 'Clear cache', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Clear now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => '<div id="ip-geo-block-loading"></div>',
		)
	);
}

/**
 * Function that fills the section with the desired content.
 *
 */
function ip_geo_block_warn_statistics() {
	echo "<p>", __( 'Current setting of [<strong>Record validation statistics</strong>] on [<strong>Settings</strong>] tab is not selected [<strong>Enable</strong>].', IP_Geo_Block::TEXT_DOMAIN ), "</p>\n";
	echo "<p>", __( 'Please set the proper condition to record and analyze the validation statistics.', IP_Geo_Block::TEXT_DOMAIN ), "</p>\n";
}