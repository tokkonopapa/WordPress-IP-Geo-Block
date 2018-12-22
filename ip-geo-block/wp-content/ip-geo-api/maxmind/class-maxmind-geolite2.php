<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.15
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */

class_exists( 'IP_Geo_Block_API', FALSE ) or die;

/**
 * URL and Path for Maxmind GeoLite2 database
 *
 * https://www.maxmind.com/en/open-source-data-and-api-for-ip-geolocation
 * https://stackoverflow.com/questions/9416508/php-untar-gz-without-exec
 * https://php.net/manual/phardata.extractto.php
 */
define( 'IP_GEO_BLOCK_GEOLITE2_DB_IP',    'GeoLite2-Country.mmdb' );
define( 'IP_GEO_BLOCK_GEOLITE2_DB_ASN',   'GeoLite2-ASN.mmdb'     );
define( 'IP_GEO_BLOCK_GEOLITE2_ZIP_IP',   'https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz' );
define( 'IP_GEO_BLOCK_GEOLITE2_ZIP_ASN',  'https://geolite.maxmind.com/download/geoip/database/GeoLite2-ASN.tar.gz'     );
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
class IP_Geo_Block_API_Geolite2 extends IP_Geo_Block_API {

	private function location_country( $record ) {
		return array( 'countryCode' => $record->country->isoCode );
	}

	private function location_city( $record ) {
		return array(
			'countryCode' => $record->country->isoCode,
			'countryName' => $record->country->names['en'],
			'cityName'    => $record->city->names['en'],
			'latitude'    => $record->location->latitude,
			'longitude'   => $record->location->longitude,
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
			$file = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-geolite2-path',
				( ! empty( $settings['Geolite2']['ip_path'] ) ?
					$settings['Geolite2']['ip_path'] :
					$this->get_db_dir() . IP_GEO_BLOCK_GEOLITE2_DB_IP
				)
			);

			try {
				$reader = new GeoIp2\Database\Reader( $file );
				if ( 'GeoLite2-Country' === $reader->metadata()->databaseType )
					$res = $this->location_country( $reader->country( $ip ) );
				else
					$res = $this->location_city( $reader->city( $ip ) );
			} catch ( Exception $e ) {
				$res = array( 'countryCode' => NULL );
			}
		}

		else {
			$file = ! empty( $settings['Geolite2']['asn_path'] ) ? $settings['Geolite2']['asn_path'] : $this->get_db_dir() . IP_GEO_BLOCK_GEOLITE2_DB_ASN;
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
			IP_Geo_Block::PLUGIN_NAME . '-geolite2-dir', dirname( __FILE__ ) . '/GeoLite2'
		) );
	}

	public function download( &$db, $args ) {
		$dir = $this->get_db_dir();

		// IPv4 & IPv6
		if ( $dir !== dirname( $db['ip_path'] ) . '/' )
			$db['ip_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_IP;

		// filter database file
		$db['ip_path'] = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-geolite2-path', $db['ip_path'] );

		$res['ip'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-geolite2-zip-ip',
				IP_GEO_BLOCK_GEOLITE2_ZIP_IP
			),
			$args + array( 'method' => 'GET' ),
			array( $db['ip_path'], 'COPYRIGHT.txt', 'LICENSE.txt' ), // 1st parameter should include absolute path
			$db['ip_last']
		);

		! empty( $res['ip']['filename'] ) and $db['ip_path'] = $res['ip']['filename'];
		! empty( $res['ip']['modified'] ) and $db['ip_last'] = $res['ip']['modified'];

if ( ! empty( $db['use_asn'] ) || ! empty( $db['asn_path'] ) ) :

		// ASN for IPv4 and IPv6
		if ( $dir !== dirname( $db['asn_path'] ) . '/' )
			$db['asn_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_ASN;

		$res['asn'] = IP_Geo_Block_Util::download_zip(
			apply_filters(
				IP_Geo_Block::PLUGIN_NAME . '-geolite2-zip-asn',
				IP_GEO_BLOCK_GEOLITE2_ZIP_ASN
			),
			$args + array( 'method' => 'GET' ),
			array( $db['asn_path'], 'COPYRIGHT.txt', 'LICENSE.txt' ), // 1st parameter should include absolute path
			$db['asn_last']
		);

		! empty( $res['asn']['filename'] ) and $db['asn_path'] = $res['asn']['filename'];
		! empty( $res['asn']['modified'] ) and $db['asn_last'] = $res['asn']['modified'];

endif; // ! empty( $db['use_asn'] ) || ! empty( $db['asn_path'] )

		return $res;
	}

	public function get_attribution() {
		return 'This product includes GeoLite2 data created by MaxMind, available from <a class="ip-geo-block-link" href="https://www.maxmind.com" rel=noreferrer target=_blank>https://www.maxmind.com</a>. (<a href="https://creativecommons.org/licenses/by-sa/4.0/" title="Creative Commons &mdash; Attribution-ShareAlike 4.0 International &mdash; CC BY-SA 4.0" rel=noreferrer target=_blank>CC BY-SA 4.0</a>)';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FILE__ . '(' . __FUNCTION__ . ')' );

		$db  = $options[ $field ];
		$dir = $this->get_db_dir();
		$msg = __( 'Database file does not exist.', 'ip-geo-block' );

		// IPv4 & IPv6
		if ( $dir !== dirname( $db['ip_path'] ) . '/' )
			$db['ip_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_IP;

		// filter database file
		$db['ip_path'] = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-geolite2-path', $db['ip_path'] );

		if ( $fs->exists( $db['ip_path'] ) )
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
				'value' => $db['ip_path'],
				'disabled' => TRUE,
				'after' => '<br /><p id="ip-geo-block-' . $field . '-ip" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

if ( ! empty( $db['use_asn'] ) || ! empty( $db['asn_path'] ) ) :

		// ASN for IPv4 and IPv6
		if ( $dir !== dirname( $db['asn_path'] ) . '/' )
			$db['asn_path'] = $dir . IP_GEO_BLOCK_GEOLITE2_DB_ASN;

		if ( $fs->exists( $db['asn_path'] ) )
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
				'value' => $db['asn_path'],
				'disabled' => TRUE,
				'after' => '<br /><p id="ip-geo-block-' . $field . '-asn" style="margin-left: 0.2em">' . $date . '</p>',
			)
		);

endif; // ! empty( $db['use_asn'] ) || ! empty( $db['asn_path'] )

	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'Geolite2' => array(
		'key'  => NULL,
		'type' => 'IPv4, IPv6 / Apache License, Version 2.0',
		'link' => '<a class="ip-geo-block-link" href="https://dev.maxmind.com/geoip/geoip2/" title="GeoIP2 &laquo; MaxMind Developer Site" rel=noreferrer target=_blank>https://dev.maxmind.com/geoip/geoip2/</a>&nbsp;(IPv4, IPv6 / Apache License, Version 2.0)',
	),
) );
