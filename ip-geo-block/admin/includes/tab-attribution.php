<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-attribution',
			__( 'Attribution links', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		foreach ( IP_Geo_Block_Provider::get_providers( 'link' ) as $provider => $key ) {
			add_settings_field(
				$option_name.'_attribution_'.$provider,
				$provider,
				array( $context, 'callback_field' ),
				$option_slug,
				$section,
				array(
					'type' => 'html',
					'option' => $option_name,
					'field' => 'attribution',
					'value' => $key,
				)
			);
		}
	}

}