<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

function ip_geo_block_tab_accesslog( $context ) {
	$plugin_slug = IP_Geo_Block::PLUGIN_SLUG;
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];
	$settings = IP_Geo_Block::get_option( 'settings' );

	register_setting(
		$option_slug,
		$option_name
	);

if ( $settings['validation']['reclogs'] ) :

	/*----------------------------------------*
	 * Validation logs
	 *----------------------------------------*/
	$section = $plugin_slug . '-accesslog';
	add_settings_section(
		$section,
		__( 'Validation logs', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_list_accesslog',
		$option_slug
	);

	$field = 'clear_logs';
	add_settings_field(
		$option_name.'_'.$field,
		__( 'Clear logs', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Clear now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => '<div id="'.$plugin_slug.'-logs"></div>',
		)
	);

else:

	/*----------------------------------------*
	 * Warning
	 *----------------------------------------*/
	$section = $plugin_slug . '-accesslog';
	add_settings_section(
		$section,
		__( 'Validation logs', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_warn_accesslog',
		$option_slug
	);

	$field = 'warning';
	add_settings_field(
		$option_name.'_'.$field,
		'&hellip;',
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'none',
			'after' => '&hellip;',
		)
	);

endif;
}

/**
 * Function that fills the section with the desired content.
 *
 */
function ip_geo_block_list_accesslog() {
	// same as in tab-settings.php
	$dfn = __( '<dfn title="Validate access to %s.">%s</dfn>', IP_Geo_Block::TEXT_DOMAIN );
	$target = array(
		'comment' => sprintf( $dfn, 'wp-comments-post.php', __( 'Comment post', IP_Geo_Block::TEXT_DOMAIN ) ),
		'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',           __( 'XML-RPC',      IP_Geo_Block::TEXT_DOMAIN ) ),
		'login'   => sprintf( $dfn, 'wp-login.php',         __( 'Login form',   IP_Geo_Block::TEXT_DOMAIN ) ),
		'admin'   => sprintf( $dfn, 'wp-admin/*.php',       __( 'Admin area',   IP_Geo_Block::TEXT_DOMAIN ) ),
	);

	foreach ( $target as $key => $val ) {
		echo '<h4>', $val, '</h4>', "\n";
		echo '<table class="fixed ', IP_Geo_Block::PLUGIN_SLUG, '-log" data-page-size="10" data-limit-navigation="5"><thead><tr>', "\n";
		echo '<th data-type="numeric">', __( 'Date', IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th>', __( 'IP address', IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th>', __( 'Code',       IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th>', __( 'Result',     IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th data-hide="phone,tablet">', __( 'Request',      IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th data-hide="all">',          __( 'User agent',   IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th data-hide="all">',          __( 'HTTP headers', IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '<th data-hide="all">',          __( '$_POST data',  IP_Geo_Block::TEXT_DOMAIN ), '</th>', "\n";
		echo '</tr></thead><tbody id="', IP_Geo_Block::PLUGIN_SLUG, '-log-', $key, '">', "\n";
		echo <<<EOT
</tbody>
<tfoot class="hide-if-no-paging">
	<tr>
		<td colspan="5">
			<div class="pagination pagination-centered"></div>
		</td>
	</tr>
</tfoot>
</table>

EOT;
	}
}

function ip_geo_block_warn_accesslog() {
	echo '<p>', __( 'Current selection of [<strong>Record validation logs</strong>] on [<strong>Settings</strong>] tab is [<strong>Disable</strong>].', IP_Geo_Block::TEXT_DOMAIN ), '</p>', "\n";
	echo '<p>', __( 'Please select the proper condition to record and analyze the validation logs.', IP_Geo_Block::TEXT_DOMAIN ), '</p>', "\n";
}