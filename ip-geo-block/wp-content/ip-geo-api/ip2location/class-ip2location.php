<?php
if ( class_exists( 'IP_Geo_Block_API' ) ) :

/**
 * URL and Path for IP2Location database
 *
 */
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_DAT', 'IP2LOCATION-LITE-DB1.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_DAT', 'IP2LOCATION-LITE-DB1.IPV6.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_ZIP', 'http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_ZIP', 'http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.IPV6.BIN.ZIP' );

/**
 * Class for IP2Location (ver. 1.1.6)
 *
 * URL         : http://www.ip2location.com/
 * Term of use : http://www.ip2location.com/terms
 * Licence fee : Creative Commons Attribution-ShareAlike 4.0 Unported License
 * Input type  : IP address (IPv4)
 * Output type : array
 */
class IP_Geo_Block_API_IP2Location extends IP_Geo_Block_API {
	protected $transform_table = array(
		'countryCode' => 'countryCode',
		'countryName' => 'countryName',
		'regionName'  => 'regionName',
		'cityName'    => 'cityName',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);

	public function get_location( $ip, $args = array() ) {
		$settings = IP_Geo_Block::get_option();

		if ( ! extension_loaded('bcmath') )
			require_once( 'bcmath.php' );

		if ( ! class_exists( 'IP2Location' ) )
			require_once( 'IP2Location.php' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$type = IP_GEO_BLOCK_API_TYPE_IPV4;
			$file = apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-ip2location-path',
				empty( $settings['IP2Location']['ipv4_path'] ) ? 
					$this->get_db_dir() . IP_GEO_BLOCK_IP2LOC_IPV4_DAT :
					$settings['IP2Location']['ipv4_path']
			);
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$type = IP_GEO_BLOCK_API_TYPE_IPV6;
			$file = empty( $settings['IP2Location']['ipv6_path'] ) ? 
				$this->get_db_dir() . IP_GEO_BLOCK_IP2LOC_IPV6_DAT :
				$settings['IP2Location']['ipv6_path'];
		}

		else {
			return array( 'errorMessage' => 'illegal format' );
		}

		try {
			$geo = new IP2Location( $file );
			if ( $geo && ( $geo->get_database_type() & $type ) ) {
				$data = $geo->lookup( $ip );
				$geo->close(); // @since 1.1.6

				$res = array();

				foreach ( $this->transform_table as $key => $val ) {
					if ( isset( $data->$val ) && IP2Location::FIELD_NOT_SUPPORTED !== $data->$val )
						$res[ $key ] = $data->$val;
				}

				if ( isset( $res['countryCode'] ) && strlen( $res['countryCode'] ) === 2 )
					return $res;
			}
		}

		catch (Exception $e) {
			return array( 'errorMessage' => $e->getMessage() );
		}

		return array( 'errorMessage' => 'Not supported' );
	}

	private function get_db_dir() {
		return IP_Geo_Block_Util::slashit( apply_filters(
			IP_Geo_Block::PLUGIN_NAME . '-ip2location-dir', dirname( __FILE__ )
		) );
	}

	public function download( &$db, $args ) {
		$dir = $this->get_db_dir();

		if ( $dir !== dirname( $db['ipv4_path'] ) . '/' )
			$db['ipv4_path'] = $dir . IP_GEO_BLOCK_IP2LOC_IPV4_DAT;

		$res['ipv4'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-ip2location-zip-ipv4',
				IP_GEO_BLOCK_IP2LOC_IPV4_ZIP
			),
			$args,
			$db['ipv4_path'],
			$db['ipv4_last']
		);

		if ( $dir !== dirname( $db['ipv6_path'] ) . '/' )
			$db['ipv6_path'] = $dir . IP_GEO_BLOCK_IP2LOC_IPV6_DAT;

		$res['ipv6'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-ip2location-zip-ipv6',
				IP_GEO_BLOCK_IP2LOC_IPV6_ZIP
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
		return 'This site or product includes IP2Location LITE data available from <a class="ip-geo-block-link" href="http://www.ip2location.com" rel=noreferrer target=_blank>http://www.ip2location.com</a>. (CC BY-SA 4.0)';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		$dir = $this->get_db_dir();

		$path = apply_filters(
			IP_Geo_Block::PLUGIN_NAME . '-ip2location-path',
			empty( $options['IP2Location']['ipv4_path'] ) ? 
				$dir . IP_GEO_BLOCK_IP2LOC_IPV4_DAT :
				$options['IP2Location']['ipv4_path']
		);

		$date = empty( $options['IP2Location']['ipv4_path'] ) ||
			! @file_exists( $options['IP2Location']['ipv4_path'] ) ?
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
				'after' => '<br /><p id="ip-geo-block-' . $field . '-ipv4" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

		$path = empty( $options['IP2Location']['ipv6_path'] ) ?
			$dir . IP_GEO_BLOCK_IP2LOC_IPV6_DAT :
			$options['IP2Location']['ipv6_path'];

		$date = empty( $options['IP2Location']['ipv6_path'] ) ||
			! @file_exists( $options['IP2Location']['ipv6_path'] ) ?
			__( 'Database file does not exist.', 'ip-geo-block' ) :
			sprintf(
				$str_last,
				IP_Geo_Block_Util::localdate( $options[ $field ]['ipv6_last'] )
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
				'after' => '<br /><p id="ip-geo-block-' . $field . '-ipv6" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);
	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'IP2Location' => array(
		'key'  => NULL,
		'type' => 'IPv4, IPv6 / LGPLv3',
		'link' => '<a class="ip-geo-block-link" href="http://lite.ip2location.com/" title="Free IP Geolocation Database" rel=noreferrer target=_blank>http://lite.ip2location.com/</a>&nbsp;(IPv4, IPv6 / LGPLv3)',
	),
) );

endif;
?>