<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

function ip_geo_block_tab_attribution( $context ) {
	$plugin_slug = IP_Geo_Block::PLUGIN_SLUG;
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];

	register_setting(
		$option_slug,
		$option_name
	);

	$section = $plugin_slug . '-attribution';
	add_settings_section(
		$section,
		__( 'Attribution links', IP_Geo_Block::TEXT_DOMAIN ),
		NULL,
		$option_slug
	);

	$field = 'attribution';
	$providers = IP_Geo_Block_Provider::get_providers( 'link' );

	foreach ( $providers as $provider => $key ) {
		add_settings_field(
			$option_name.'_'.$field.'_'.$provider,
			$provider,
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $key,
			)
		);
	}
}