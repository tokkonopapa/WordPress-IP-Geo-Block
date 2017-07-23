<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		$section = IP_Geo_Block::PLUGIN_NAME . '-attribution';
		$field = 'attribution';

		add_settings_section(
			$section,
			__( 'Attribution links', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

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