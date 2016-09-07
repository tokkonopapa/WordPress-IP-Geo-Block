<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context ) {
		$option_slug = IP_Geo_Block::PLUGIN_NAME;
		$option_name = IP_Geo_Block::OPTION_NAME;

		register_setting(
			$option_slug,
			$option_name
		);

		$section = IP_Geo_Block::PLUGIN_NAME . '-attribution';
		add_settings_section(
			$section,
			__( 'Attribution links', 'ip-geo-block' ),
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

}