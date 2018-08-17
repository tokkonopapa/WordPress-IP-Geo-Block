<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		/*----------------------------------------*
		 * Geolocation
		 *----------------------------------------*/
		$section = IP_Geo_Block::PLUGIN_NAME . '-search';
		add_settings_section(
			$section,
			__( 'Search IP address geolocation', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		// make providers list
		$list = array();
		$providers = IP_Geo_Block_Provider::get_providers( 'key' );

		foreach ( $providers as $provider => $key ) {
			if ( ! is_string( $key ) ||
				 ! empty( $options['providers'][ $provider ] ) ) {
				$list += array( $provider => $provider );
			}
		}

		// get selected item
		$cookie = $context->get_cookie();
		$cookie = empty( $cookie[ $tab ] ) ? 0 : (int)end( $cookie[ $tab ] );

		$field = 'service';
		$provider = array_keys( $providers );
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Geolocation API', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => $provider[ $cookie ],
				'list' => $list,
			)
		);

		// preset IP address
		if ( isset( $_GET['s'] ) ) {
			$list = preg_replace(
				array( '/\.\*+$/', '/:\w*\*+$/' ),
				array( '.0',       '::'         ),
				trim( $_GET['s'] )
			); // de-anonymize if `***` exists
			$list = filter_var( $list, FILTER_VALIDATE_IP ) ? $list : '';
		} else {
			$list = '';
		}

		$field = 'ip_address';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'IP address', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => $list,
			)
		);

		// Anonymize IP address
		$field = 'anonymize';
		add_settings_field(
			$option_name.'_'.$field,
			__( '<dfn title="IP address is always encrypted on recording in Cache and Logs. Moreover, this option replaces the end of IP address with &#8220;***&#8221; to make it anonymous.">Anonymize IP address</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => $field,
				'value' => ( ! empty( $options[ $field ] ) || ! empty( $options['restrict_api'] ) ) ? TRUE : FALSE,
			)
		);

		// Search geolocation
		$field = 'get_location';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Search geolocation', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Search now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-loading"></div>',
			)
		);
	}

}