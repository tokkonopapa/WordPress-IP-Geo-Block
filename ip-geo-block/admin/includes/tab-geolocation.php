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
		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-search',
			__( 'Search IP address geolocation', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		// make providers list
		$list = array();
		$providers = IP_Geo_Block_Provider::get_providers( 'key' );
		foreach ( $providers as $provider => $key ) {
			if ( ! is_string( $key ) || // provider that does not need api key
			     ! empty( $options['providers'][ $provider ] ) ) { // provider that has api key
				$list += array( $provider => $provider );
			}
		}

		// get selected item
		$provider  = array();
		$providers = array_keys( $providers );
		$cookie = $context->get_cookie();
		if ( isset( $cookie[ $tab ] ) ) {
			foreach ( array_slice( (array)$cookie[ $tab ], 3 ) as $key => $val ) {
				if ( 'o' === $val ) {
					$provider[] = $providers[ $key ];
				}
			}
		}

		add_settings_field(
			$option_name.'_service',
			__( 'Geolocation API', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'attr' => 'multiple="multiple"',
				'option' => $option_name,
				'field' => 'service',
				'value' => ! empty( $provider ) ? $provider : $providers[0],
				'list' => $list,
			)
		);

		// preset IP address
		if ( isset( $_GET['s'] ) ) {
			$list = preg_replace(
				array( '/\.\*+$/', '/:\w*\*+$/', '/(::.*)::$/' ),
				array( '.0',       '::',         '$1'          ),
				trim( $_GET['s'] )
			); // de-anonymize if `***` exists
			$list = filter_var( $list, FILTER_VALIDATE_IP ) ? $list : '';
		} else {
			$list = '';
		}

		add_settings_field(
			$option_name.'_ip_address',
			__( 'IP address', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'ip_address',
				'value' => $list,
			)
		);

		// Anonymize IP address
		add_settings_field(
			$option_name.'_anonymize',
			__( '<dfn title="IP address is always encrypted on recording in Cache and Logs. Moreover, this option replaces the end of IP address with &#8220;***&#8221; to make it anonymous.">Anonymize IP address</dfn>', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'checkbox',
				'option' => $option_name,
				'field' => 'anonymize',
				'value' => ( ! empty( $options['anonymize'] ) || ! empty( $options['restrict_api'] ) ) ? TRUE : FALSE,
			)
		);

		// Search geolocation
		add_settings_field(
			$option_name.'_get_location',
			__( 'Search geolocation', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'get_location',
				'value' => __( 'Search now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-loading"></div>',
			)
		);
	}

}