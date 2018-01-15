<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.10
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */

class_exists( 'IP_Geo_Block_API', FALSE ) or die;

/**
 * URL and Path for Maxmind GeoLite2 database
 *
 * https://www.maxmind.com/en/open-source-data-and-api-for-ip-geolocation
 * https://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
 * http://php.net/manual/phardata.extractto.php
 */
define( 'IP_GEO_BLOCK_GEOLITE2_DB_IP',    'GeoLite2-Country.mmdb' );
define( 'IP_GEO_BLOCK_GEOLITE2_DB_ASN',   'GeoLite2-ASN.mmdb'     );
define( 'IP_GEO_BLOCK_GEOLITE2_ZIP_IP',   'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz' );
define( 'IP_GEO_BLOCK_GEOLITE2_ZIP_ASN',  'http://geolite.maxmind.com/download/geoip/database/GeoLite2-ASN.tar.gz'     );
define( 'IP_GEO_BLOCK_GEOLITE2_DOWNLOAD', 'https://dev.maxmind.com/geoip/geoip2/geolite2/' );

/**
 * Class for Maxmind
 *
 * URL         : https://dev.maxmind.com/geoip/geoip2/
 * Term of use : https://dev.maxmind.com/geoip/geoip2/geolite2/#License
 * Licence fee : Creative Commons Attribution-ShareAlike 4.0 International License
 * Input type  : IP address (IPv4, IPv6)
 * Output type : array
 */
class IP_Geo_Block_API_Maxmind extends IP_Geo_Block_API {

	private function location_country( $record ) {
		return array( 'countryCode' => $record->country->isoCode );
	}

	private function location_city( $record ) {
		return array(
			'countryCode' => $record->isoCode,
			'cityName'    => $record->city,
			'latitude'    => $record->latitude,
			'longitude'   => $record->longitude,
		);
	}

	private function location_asnumber( $record ) {
		return array( 'ASN' => 'AS' . $record->autonomousSystemNumber );
	}

	public function get_location( $ip, $args = array() ) {
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) )
			return array( 'errorMessage' => 'illegal format' );

		require IP_GEO_BLOCK_PATH . 'wp-content/ip-geo-api/maxmind/vendor/autoload.php';

		// setup database file and function
		$settings = IP_Geo_Block::get_option();

		if ( empty( $args['ASN'] ) ) {
			$file = ! empty( $settings['Maxmind']['ip_path'] ) ? $settings['Maxmind']['ip_path'] : $this->get_db_dir() . IP_GEO_BLOCK_GEOLITE2_DB_IP;
			try {
				$reader = new GeoIp2\Database\Reader( $file );
				$res = $this->location_country( $reader->country( $ip ) );
			} catch ( Exception $e ) {
				$res = array( 'countryCode' => 'ZZ' );
			}
		}

		else {
			$file = ! empty( $settings['Maxmind']['asn_path'] ) ? $settings['Maxmind']['asn_path'] : $this->get_db_dir() . IP_GEO_BLOCK_GEOLITE2_DB_ASN;
			try {
				$reader = new GeoIp2\Database\Reader( $file );
				$res = $this->location_asnumber( $reader->asn( $ip ) );
			} catch ( Exception $e ) {
				$res = array( 'ASN' => NULL );
			}
		}

		return $res;
	}

	private function get_db_dir() {
		return IP_Geo_Block_Util::slashit( apply_filters(
			IP_Geo_Block::PLUGIN_NAME . '-maxmind-dir', dirname( __FILE__ ) . '/GeoLite2'
		) );
	}

	public function download( &$db, $args ) {
		$dir = $this->get_db_dir();

		// IPv4 & IPv6
		if ( $dir !== dirname( $db['ip_path'] ) . '/' )
			$db['ip_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_IP;

		$res['ip'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-ip',
				IP_GEO_BLOCK_GEOLITE2_ZIP_IP
			),
			$args + array( 'method' => 'GET' ),
			$db['ip_path'],
			$db['ip_last']
		);

		! empty( $res['ip']['filename'] ) and $db['ip_path'] = $res['ip']['filename'];
		! empty( $res['ip']['modified'] ) and $db['ip_last'] = $res['ip']['modified'];

if ( $db['use_asn'] || ! empty( $db['asn_path'] ) ) :

		// ASN for IPv4 and IPv6
		if ( $dir !== dirname( $db['asn_path'] ) . '/' )
			$db['asn_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_ASN;

		$res['asn'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-asn',
				IP_GEO_BLOCK_GEOLITE2_ZIP_ASN
			),
			$args + array( 'method' => 'GET' ),
			$db['asn_path'],
			$db['asn_last']
		);

		! empty( $res['asn']['filename'] ) and $db['asn_path'] = $res['asn']['filename'];
		! empty( $res['asn']['modified'] ) and $db['asn_last'] = $res['asn']['modified'];

endif; // $db['use_asn'] || ! empty( $db['asn_path'] )

		return $res;
	}

	public function get_attribution() {
		return 'This product includes GeoLite2 data created by MaxMind, available from <a class="ip-geo-block-link" href="http://www.maxmind.com" rel=noreferrer target=_blank>http://www.maxmind.com</a>. (<a href="https://creativecommons.org/licenses/by-sa/4.0/" title="Creative Commons &mdash; Attribution-ShareAlike 4.0 International &mdash; CC BY-SA 4.0" rel=noreferrer target=_blank>CC BY-SA 4.0</a>)';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		$db  = $options[ $field ];
		$dir = $this->get_db_dir();
		$msg = __( 'Database file does not exist.', 'ip-geo-block' );

		// IPv4 and IPv6
		if ( $db['ip_path'] )
			$path = $db['ip_path'];
		else
			$path = $dir . IP_GEO_BLOCK_GEOLITE2_DB_IP;

		if ( @file_exists( $path ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['ip_last'] ) );
		else
			$date = $msg;

		add_settings_field(
			$option_name . $field . '_ip',
			"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_GEOLITE2_DOWNLOAD . "' title='" . IP_GEO_BLOCK_GEOLITE2_ZIP_IP . "'>IPv4 and IPv6</a>)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'ip_path',
				'value' => $path,
				'disabled' => TRUE,
				'after' => '<br /><p id="ip-geo-block-' . $field . '-ip" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

if ( $db['use_asn'] || ! empty( $db['asn_path'] ) ) :

		// ASN for IPv4 and IPv6
		if ( $db['asn_path'] )
			$path = $db['asn_path'];
		else
			$path = $dir . IP_GEO_BLOCK_GEOLITE2_DB_ASN;

		if ( @file_exists( $path ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['asn_last'] ) );
		else
			$date = $msg;

		add_settings_field(
			$option_name . $field . '_asn',
			"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_GEOLITE2_DOWNLOAD . "' title='" . IP_GEO_BLOCK_GEOLITE2_ZIP_ASN . "'>ASN for IPv4 and IPv6</a>)",
			$callback,
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'sub-field' => 'asn_path',
				'value' => $path,
				'disabled' => TRUE,
				'after' => '<br /><p id="ip-geo-block-' . $field . '-asn" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

endif; // $db['use_asn'] || ! empty( $db['asn_path'] )

	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'Maxmind' => array(
		'key'  => NULL,
		'type' => 'IPv4, IPv6 / Apache License, Version 2.0',
		'link' => '<a class="ip-geo-block-link" href="https://dev.maxmind.com/geoip/geoip2/" title="GeoIP2 &laquo; MaxMind Developer Site" rel=noreferrer target=_blank>https://dev.maxmind.com/geoip/geoip2/</a>&nbsp;(IPv4, IPv6 / Apache License, Version 2.0)',
	),
) );
