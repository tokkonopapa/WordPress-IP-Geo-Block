<?php
if ( class_exists( 'IP_Geo_Block_API' ) ) :

/**
 * URL and Path for Maxmind GeoLite database
 *
 */
define( 'IP_GEO_BLOCK_MAXMIND_IPV4_DAT', 'GeoIP.dat' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_DAT', 'GeoIPv6.dat' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV4_ZIP', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_ZIP', 'http://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz' );

/**
 * Class for Maxmind (ver. 1.1.3)
 *
 * URL         : http://dev.maxmind.com/geoip/legacy/geolite/
 * Term of use : http://dev.maxmind.com/geoip/legacy/geolite/#License
 * Licence fee : Creative Commons Attribution-ShareAlike 3.0 Unported License
 * Input type  : IP address (IPv4, IPv6)
 * Output type : array
 */
class IP_Geo_Block_API_Maxmind extends IP_Geo_Block_API {

	private function location_country( $record ) {
		return array( 'countryCode' => $record );
	}

	private function location_city( $record ) {
		return array(
			'countryCode' => $record->country_code,
			'cityName'    => $record->city,
			'latitude'    => $record->latitude,
			'longitude'   => $record->longitude,
		);
	}

	public function get_location( $ip, $args = array() ) {
		$settings = IP_Geo_Block::get_option( 'settings' );

		if ( ! function_exists( 'geoip_open' ) )
			require_once( 'geoip.inc' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$file = empty( $settings['Maxmind']['ipv4_path'] ) ?
				$this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_IPV4_DAT :
				$settings['Maxmind']['ipv4_path'];
		}
		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$file = empty( $settings['Maxmind']['ipv6_path'] ) ?
				$this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_IPV6_DAT :
				$settings['Maxmind']['ipv6_path'];
		}
		else {
			return array( 'errorMessage' => 'illegal format' );
		}

		// open database and fetch data
		if ( ! is_readable( $file ) || ! ( $geo = geoip_open( $file, GEOIP_STANDARD ) ) )
			return FALSE;

		switch ( $geo->databaseType ) {
		  case GEOIP_COUNTRY_EDITION:
			$res = $this->location_country( geoip_country_code_by_addr( $geo, $ip ) );
			break;
		  case GEOIP_COUNTRY_EDITION_V6:
			$res = $this->location_country( geoip_country_code_by_addr_v6( $geo, $ip ) );
			break;
		  case GEOIP_CITY_EDITION_REV1:
			if ( ! class_exists( 'geoiprecord' ) )
				require_once( 'geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr( $geo, $ip ) );
			break;
		  case GEOIP_CITY_EDITION_REV1_V6:
			if ( ! class_exists( 'geoiprecord' ) )
				require_once( 'geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr_v6( $geo, $ip ) );
			break;
		  default:
			$res = array( 'errorMessage' => 'unknown database type' );
		}

		geoip_close( $geo );
		return $res;
	}

	private function get_db_dir() {
		return trailingslashit( apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-maxmind-dir', dirname( __FILE__ )
		) );
	}

	public function download( &$db, $args ) {
		$dir = $this->get_db_dir();

		if ( $dir !== dirname( $db['ipv4_path'] ) . '/' )
			$db['ipv4_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV4_DAT;

		$res['ipv4'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_SLUG . '-maxmind-zip-ipv4',
				IP_GEO_BLOCK_MAXMIND_IPV4_ZIP
			),
			$args,
			$db['ipv4_path'],
			$db['ipv4_last']
		);

		if ( $dir !== dirname( $db['ipv6_path'] ) . '/' )
			$db['ipv6_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV6_DAT;

		$res['ipv6'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_SLUG . '-maxmind-zip-ipv6',
				IP_GEO_BLOCK_MAXMIND_IPV6_ZIP
			),
			$args,
			$db['ipv6_path'],
			$db['ipv6_last']
		);

		! empty( $res['ipv4']['filename'] ) and $db['ipv4_path'] = $res['ipv4']['filename'];
		! empty( $res['ipv6']['filename'] ) and $db['ipv6_path'] = $res['ipv6']['filename'];
		! empty( $res['ipv4']['modified'] ) and $db['ipv4_last'] = $res['ipv4']['modified'];
		! empty( $res['ipv6']['modified'] ) and $db['ipv6_last'] = $res['ipv6']['modified'];

		return $res;
	}

	public function get_attribution() {
		return 'This product includes GeoLite data created by MaxMind, available from <a class="ip-geo-block-link" href="http://www.maxmind.com" rel=noreferrer target=_blank>http://www.maxmind.com</a>.';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		$dir = $this->get_db_dir();

		$path = empty( $options['Maxmind']['ipv4_path'] ) ?
			$dir . IP_GEO_BLOCK_MAXMIND_IPV4_DAT :
			$options['Maxmind']['ipv4_path'];

		$date = empty( $options['Maxmind']['ipv4_path'] ) ||
			! @file_exists( $options['Maxmind']['ipv4_path'] ) ?
			__( 'Database file does not exist.', 'ip-geo-block' ) :
			sprintf(
				$str_last,
				IP_Geo_Block_Util::localdate( $options[ $field ]['ipv4_last'] )
			);

		add_settings_field(
			$option_name . $field . '_ipv4',
			"$field $str_path (IPv4)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'ipv4_path',
				'value' => $path,
				'disabled' => TRUE,
				'after' => '<br /><p id="ip_geo_block_' . $field . '_ipv4" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

		$path = empty( $options['Maxmind']['ipv4_path'] ) ?
			$dir . IP_GEO_BLOCK_MAXMIND_IPV4_DAT :
			$options['Maxmind']['ipv4_path'];

		$date = empty( $options['Maxmind']['ipv4_path'] ) ||
			! @file_exists( $options['Maxmind']['ipv4_path'] ) ?
			__( 'Database file does not exist.', 'ip-geo-block' ) :
			sprintf(
				$str_last,
				IP_Geo_Block_Util::localdate( $options[ $field ]['ipv4_last'] )
			);

		add_settings_field(
			$option_name . $field . '_ipv6',
			"$field $str_path (IPv6)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'ipv6_path',
				'value' => $path,
				'disabled' => TRUE,
				'after' => '<br /><p id="ip_geo_block_' . $field . '_ipv6" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);
	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'Maxmind' => array(
		'key'  => NULL,
		'type' => 'IPv4, IPv6 / CC BY-SA 3.0',
		'link' => '<a class="ip-geo-block-link" href="http://dev.maxmind.com/geoip/" title="GeoIP Products &laquo; Maxmind Developer Site" rel=noreferrer target=_blank>http://dev.maxmind.com/geoip/</a>&nbsp;(IPv4, IPv6 / CC BY-SA 3.0)',
	),
) );

endif;
?>