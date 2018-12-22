<?php
/**
 * IP Geo Block API class library for IP2Location
 *
 * @version   1.1.15
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */

class_exists( 'IP_Geo_Block_API', FALSE ) or die;

/**
 * URL and Path for IP2Location database
 *
 */
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_DAT', 'IP2LOCATION-LITE-DB1.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_DAT', 'IP2LOCATION-LITE-DB1.IPV6.BIN' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV4_ZIP', 'https://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP' );
define( 'IP_GEO_BLOCK_IP2LOC_IPV6_ZIP', 'https://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.IPV6.BIN.ZIP' );
define( 'IP_GEO_BLOCK_IP2LOC_DOWNLOAD', 'https://lite.ip2location.com/database/ip-country' );

/**
 * Class for IP2Location
 *
 * URL         : https://www.ip2location.com/
 * Term of use : https://www.ip2location.com/terms
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

		if ( ! class_exists( 'IP2Location', FALSE ) )
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

		// IPv4
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

		// IPv6
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
		return 'This site or product includes IP2Location LITE data available from <a class="ip-geo-block-link" href="https://lite.ip2location.com" rel=noreferrer target=_blank>https://lite.ip2location.com</a>. (<a href="https://creativecommons.org/licenses/by-sa/4.0/" title="Creative Commons &mdash; Attribution-ShareAlike 4.0 International &mdash; CC BY-SA 4.0" rel=noreferrer target=_blank>CC BY-SA 4.0</a>)';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FILE__ . '(' . __FUNCTION__ . ')' );

		$db  = $options[ $field ];
		$dir = $this->get_db_dir();
		$msg = __( 'Database file does not exist.', 'ip-geo-block' );

		// IPv4
		if ( $dir !== dirname( $db['ipv4_path'] ) . '/' )
			$db['ipv4_path'] = $dir . IP_GEO_BLOCK_IP2LOC_IPV4_DAT;

		// filter database file
		$db['ipv4_path'] = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-ip2location-path', $db['ipv4_path'] );

		if ( $fs->exists( $db['ipv4_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['ipv4_last'] ) );
		else
			$date = $msg;

		add_settings_field(
			$option_name . $field . '_ipv4',
			"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_IP2LOC_DOWNLOAD . "' title='" . IP_GEO_BLOCK_IP2LOC_IPV4_ZIP . "'>IPv4</a>)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'ipv4_path',
				'value' => $db['ipv4_path'],
				'disabled' => TRUE,
				'after' => '<br /><p id="ip-geo-block-' . $field . '-ipv4" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

		// IPv6
		if ( $dir !== dirname( $db['ipv6_path'] ) . '/' )
			$db['ipv6_path'] = $dir . IP_GEO_BLOCK_IP2LOC_IPV6_DAT;

		// filter database file
		$db['ipv6_path'] = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-ip2location-path-ipv6', $db['ipv6_path'] );

		if ( $fs->exists( $db['ipv6_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['ipv6_last'] ) );
		else
			$date = $msg;

		add_settings_field(
			$option_name . $field . '_ipv6',
			"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_IP2LOC_DOWNLOAD . "' title='" . IP_GEO_BLOCK_IP2LOC_IPV6_ZIP . "'>IPv6</a>)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'ipv6_path',
				'value' => $db['ipv6_path'],
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
		'link' => '<a class="ip-geo-block-link" href="https://lite.ip2location.com/" title="Free IP Geolocation Database" rel=noreferrer target=_blank>https://lite.ip2location.com/</a>&nbsp;(IPv4, IPv6 / LGPLv3)',
	),
) );
