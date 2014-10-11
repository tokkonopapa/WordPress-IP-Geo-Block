<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

function tab_attribution( $context ) {
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];

	register_setting(
		$option_slug,
		$option_name
	);

	$section = IP_Geo_Block::PLUGIN_SLUG . '-attribution';
	add_settings_section(
		$section,
		__( 'Attribution links', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_attribution' ),
		$option_slug
	);

	$field = 'attribution';
	$providers = IP_Geo_Block_Provider::get_providers( 'link' );

	foreach ( $providers as $provider => $key ) {
		add_settings_field(
			$option_name . "_${field}_${provider}",
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