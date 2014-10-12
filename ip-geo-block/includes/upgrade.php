<?php
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

function ip_geo_block_upgrade() {
	// find IP2Location DB
	$tmp = array(
		WP_CONTENT_DIR . '/ip2location/database.bin',
		WP_CONTENT_DIR . '/plugins/ip2location-tags/database.bin',
		WP_CONTENT_DIR . '/plugins/ip2location-variables/database.bin',
		WP_CONTENT_DIR . '/plugins/ip2location-blocker/database.bin',
	);

	// get path to IP2Location DB
	foreach ( $tmp as $name ) {
		if ( is_readable( $name ) ) {
			$ip2 = $name;
			break;
		}
	}

	$name = array_keys( IP_Geo_Block::$option_keys );
	$keys = array_values( IP_Geo_Block::$option_keys );

	$defaults[0] = IP_Geo_Block::get_defaults( $name[0] );
	$defaults[1] = IP_Geo_Block::get_defaults( $name[1] );

	if ( FALSE === ( $settings = get_option( $keys[0] ) ) ) {
		// get country code from admin's IP address and set it into white list
		$args = IP_Geo_Block::get_request_headers( $defaults[0] );
		foreach ( array( 'ipinfo.io', 'Telize', 'IP-Json' ) as $provider ) {
			if ( $provider = IP_Geo_Block_API::get_class_name( $provider ) ) {
				$tmp = new $provider( NULL );
				if ( $tmp = $tmp->get_country( $_SERVER['REMOTE_ADDR'], $args ) ) {
					$defaults[0]['white_list'] = $tmp;
					break;
				}
			}
		}

		// set IP2Location
		$defaults[0]['ip2location']['ipv4_path'] = $ip2;

		// create new option table
		$settings = $defaults[0];
		add_option( $keys[0], $defaults[0], '', 'yes' );
		add_option( $keys[1], $defaults[1], '', 'no'  );
	}

	else {
		// update format of option settings
		if ( version_compare( $settings['version'], '1.1' ) < 0 ) {
			foreach ( array( 'cache_hold', 'cache_time' ) as $tmp )
				$settings[ $tmp ] = $defaults[0][ $tmp ];
		}

		if ( version_compare( $settings['version'], '1.2' ) < 0 ) {
			foreach ( array( 'order', 'ip2location' ) as $tmp )
				unset( $settings[ $tmp ] );

			foreach ( explode( ' ', 'flags login_fails validation update maxmind ip2location' ) as $tmp )
				$settings[ $tmp ] = $defaults[0][ $tmp ];
		}

		// update IP2Location
		$settings['ip2location']['ipv4_path'] = $ip2;

		// finally update version number
		$settings['version'] = $defaults[0]['version'];

		// update option table
		update_option( $keys[0], $settings );
	}

	// return upgraded settings
	return $settings;
}