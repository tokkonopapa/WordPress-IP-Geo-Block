<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/handlelog.php' );

function ip_geo_block_tab_accesslog( $context ) {
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];

	register_setting(
		$option_slug,
		$option_name
	);

	/*----------------------------------------*
	 * Statistics of comment post
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-accesslog';
	add_settings_section(
		$section,
		__( 'Access log', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_list_accesslog',
		$option_slug
	);
}

function ip_geo_block_list_accesslog() {
	$list = ip_geo_block_read_log();
	foreach ( $list as $key => $val ) {
		echo "<pre>", htmlspecialchars( print_r( $val, true ) ), "</pre>\n";
	}
}