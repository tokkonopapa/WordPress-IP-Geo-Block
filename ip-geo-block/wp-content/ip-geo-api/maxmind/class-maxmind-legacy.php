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
 * URL and Path for Maxmind GeoLite database
 *
 */
define( 'IP_GEO_BLOCK_MAXMIND_IPV4_DAT', 'GeoIP.dat' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_DAT', 'GeoIPv6.dat' );
define( 'IP_GEO_BLOCK_MAXMIND_ASN4_DAT', 'GeoIPASNum.dat' );
define( 'IP_GEO_BLOCK_MAXMIND_ASN6_DAT', 'GeoIPASNumv6.dat' );

define( 'IP_GEO_BLOCK_MAXMIND_IPV4_ZIP', 'https://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_ZIP', 'https://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_ASN4_ZIP', 'https://download.maxmind.com/download/geoip/database/asnum/GeoIPASNum.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_ASN6_ZIP', 'https://download.maxmind.com/download/geoip/database/asnum/GeoIPASNumv6.dat.gz' );

define( 'IP_GEO_BLOCK_MAXMIND_DOWNLOAD', 'https://dev.maxmind.com/geoip/legacy/geolite/' );

/**
 * Class for Maxmind
 *
 * URL         : https://dev.maxmind.com/geoip/legacy/geolite/
 * Term of use : https://dev.maxmind.com/geoip/legacy/geolite/#License
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

	private function location_asnumber( $record ) {
		return array( 'ASN' => $record );
	}

	public function get_location( $ip, $args = array() ) {
		$settings = IP_Geo_Block::get_option();

		if ( ! function_exists( 'geoip_open' ) )
			require_once( 'geoip.inc' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$file = empty( $args['ASN'] ) ?
				( empty( $settings['Maxmind']['ipv4_path'] ) ? $this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_IPV4_DAT : $settings['Maxmind']['ipv4_path'] ):
				( empty( $settings['Maxmind']['asn4_path'] ) ? $this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_ASN4_DAT : $settings['Maxmind']['asn4_path'] );
		}

		elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$file = empty( $args['ASN'] ) ?
				( empty( $settings['Maxmind']['ipv6_path'] ) ? $this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_IPV6_DAT : $settings['Maxmind']['ipv6_path'] ):
				( empty( $settings['Maxmind']['asn6_path'] ) ? $this->get_db_dir() . IP_GEO_BLOCK_MAXMIND_ASN6_DAT : $settings['Maxmind']['asn6_path'] );
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
			if ( ! class_exists( 'geoiprecord', FALSE ) )
				require_once( 'geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr( $geo, $ip ) );
			break;

		  case GEOIP_CITY_EDITION_REV1_V6:
			if ( ! class_exists( 'geoiprecord', FALSE ) )
				require_once( 'geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr_v6( $geo, $ip ) );
			break;

		  case GEOIP_ASNUM_EDITION:
			$res = $this->location_asnumber( geoip_name_by_addr( $geo, $ip ) );
			break;

		  case GEOIP_ASNUM_EDITION_V6:
			$res = $this->location_asnumber( geoip_name_by_addr_v6( $geo, $ip ) );
			break;

		  default:
			$res = array( 'errorMessage' => 'unknown database type' );
		}

		geoip_close( $geo );
		return $res;
	}

	private function get_db_dir() {
		return IP_Geo_Block_Util::slashit( apply_filters(
			IP_Geo_Block::PLUGIN_NAME . '-maxmind-dir', dirname( __FILE__ )
		) );
	}

	private function available( $exists ) {
		// GeoLite Legacy databases would be stopped to update and download
		return $exists ?
			$_SERVER['REQUEST_TIME'] < strtotime( '2018-04-01' ): // Update    until April   1, 2018
			$_SERVER['REQUEST_TIME'] < strtotime( '2019-01-02' ); // Available until January 2, 2019
	}

	public function download( &$db, $args ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FILE__ . '(' . __FUNCTION__ . ')' );

		$dir = $this->get_db_dir();

		// IPv4
		if ( $dir !== dirname( $db['ipv4_path'] ) . '/' )
			$db['ipv4_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV4_DAT;

		if ( $this->available( $fs->exists( $db['ipv4_path'] ) ) ) {
			$res['ipv4'] = IP_Geo_Block_Util::download_zip(
				apply_filters(
					IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-ipv4',
					IP_GEO_BLOCK_MAXMIND_IPV4_ZIP
				),
				$args + array( 'method' => 'GET' ),
				$db['ipv4_path'],
				$db['ipv4_last']
			);
		} else {
			$res['ipv4'] = array(
				'code' => 503,
				'message' => __( 'The download service of this database file had terminated.', 'ip-geo-block' )
			);
		}

		// IPv6
		if ( $dir !== dirname( $db['ipv6_path'] ) . '/' )
			$db['ipv6_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV6_DAT;

		if ( $this->available( $fs->exists( $db['ipv6_path'] ) ) ) {
			$res['ipv6'] = IP_Geo_Block_Util::download_zip(
				apply_filters(
					IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-ipv6',
					IP_GEO_BLOCK_MAXMIND_IPV6_ZIP
				),
				$args + array( 'method' => 'GET' ),
				$db['ipv6_path'],
				$db['ipv6_last']
			);
		} else {
			$res['ipv6'] = array(
				'code' => 503,
				'message' => __( 'The download service of this database file had terminated.', 'ip-geo-block' )
			);
		}

		! empty( $res['ipv4']['filename'] ) and $db['ipv4_path'] = $res['ipv4']['filename'];
		! empty( $res['ipv6']['filename'] ) and $db['ipv6_path'] = $res['ipv6']['filename'];
		! empty( $res['ipv4']['modified'] ) and $db['ipv4_last'] = $res['ipv4']['modified'];
		! empty( $res['ipv6']['modified'] ) and $db['ipv6_last'] = $res['ipv6']['modified'];

if ( ! empty( $db['use_asn'] ) || ! empty( $db['asn4_path'] ) ) :

		// ASN for IPv4
		if ( $dir !== dirname( $db['asn4_path'] ) . '/' )
			$db['asn4_path'] = $dir . IP_GEO_BLOCK_MAXMIND_ASN4_DAT;

		if ( $this->available( $fs->exists( $db['asn4_path'] ) ) ) {
			$res['asn4'] = IP_Geo_Block_Util::download_zip(
				apply_filters(
					IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-asn4',
					IP_GEO_BLOCK_MAXMIND_ASN4_ZIP
				),
				$args + array( 'method' => 'GET' ),
				$db['asn4_path'],
				$db['asn4_last']
			);
		} else {
			$res['asn4'] = array(
				'code' => 503,
				'message' => __( 'The download service of this database file had terminated.', 'ip-geo-block' )
			);
		}

		// ASN for IPv6
		if ( $dir !== dirname( $db['asn6_path'] ) . '/' )
			$db['asn6_path'] = $dir . IP_GEO_BLOCK_MAXMIND_ASN6_DAT;

		if ( $this->available( $fs->exists( $db['asn6_path'] ) ) ) {
			$res['asn6'] = IP_Geo_Block_Util::download_zip(
				apply_filters(
					IP_Geo_Block::PLUGIN_NAME . '-maxmind-zip-asn6',
					IP_GEO_BLOCK_MAXMIND_ASN6_ZIP
				),
				$args + array( 'method' => 'GET' ),
				$db['asn6_path'],
				$db['asn6_last']
			);
		} else {
			$res['asn6'] = array(
				'code' => 503,
				'message' => __( 'The download service of this database file had terminated.', 'ip-geo-block' )
			);
		}

		! empty( $res['asn4']['filename'] ) and $db['asn4_path'] = $res['asn4']['filename'];
		! empty( $res['asn6']['filename'] ) and $db['asn6_path'] = $res['asn6']['filename'];
		! empty( $res['asn4']['modified'] ) and $db['asn4_last'] = $res['asn4']['modified'];
		! empty( $res['asn6']['modified'] ) and $db['asn6_last'] = $res['asn6']['modified'];

endif; // ! empty( $db['use_asn'] ) || ! empty( $db['asn4_path'] )

		return isset( $res ) ? $res : NULL;
	}

	public function get_attribution() {
		return 'This product includes GeoLite data created by MaxMind, available from <a class="ip-geo-block-link" href="https://www.maxmind.com" rel=noreferrer target=_blank>https://www.maxmind.com</a>. (<a href="https://creativecommons.org/licenses/by-sa/4.0/" title="Creative Commons &mdash; Attribution-ShareAlike 4.0 International &mdash; CC BY-SA 4.0" rel=noreferrer target=_blank>CC BY-SA 4.0</a>)';
	}

	public function add_settings_field( $field, $section, $option_slug, $option_name, $options, $callback, $str_path, $str_last ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FILE__ . '(' . __FUNCTION__ . ')' );

		$db  = $options[ $field ];
		$dir = $this->get_db_dir();
		$msg = __( 'Database file does not exist.', 'ip-geo-block' );

		// IPv4
		if ( $dir !== dirname( $db['ipv4_path'] ) . '/' )
			$db['ipv4_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV4_DAT;

		if ( $exists = $fs->exists( $db['ipv4_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['ipv4_last'] ) );
		else
			$date = $msg;

		if ( $exists || $this->available( $exists ) ) {
			add_settings_field(
				$option_name . $field . '_ipv4',
				"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_MAXMIND_DOWNLOAD . "' title='" . IP_GEO_BLOCK_MAXMIND_IPV4_ZIP . "'>IPv4</a>)",
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
		}

		// IPv6
		if ( $dir !== dirname( $db['ipv6_path'] ) . '/' )
			$db['ipv6_path'] = $dir . IP_GEO_BLOCK_MAXMIND_IPV6_DAT;

		if ( $exists = $fs->exists( $db['ipv6_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['ipv6_last'] ) );
		else
			$date = $msg;

		if ( $exists || $this->available( $exists ) ) {
			add_settings_field(
				$option_name . $field . '_ipv6',
				"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_MAXMIND_DOWNLOAD . "' title='" . IP_GEO_BLOCK_MAXMIND_IPV6_ZIP . "'>IPv6</a>)",
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

if ( ! empty( $db['use_asn'] ) || ! empty( $db['asn4_path'] ) ) :

		// ASN for IPv4
		if ( $dir !== dirname( $db['asn4_path'] ) . '/' )
			$db['asn4_path'] = $dir . IP_GEO_BLOCK_MAXMIND_ASN4_DAT;

		if ( $exists = $fs->exists( $db['asn4_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['asn4_last'] ) );
		else
			$date = $msg;

		if ( $exists || $this->available( $exists ) ) {
			add_settings_field(
				$option_name . $field . '_asn4',
				"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_MAXMIND_DOWNLOAD . "' title='" . IP_GEO_BLOCK_MAXMIND_ASN4_ZIP . "'>ASN for IPv4</a>)",
				$callback,
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'sub-field' => 'asn4_path',
					'value' => $db['asn4_path'],
					'disabled' => TRUE,
					'after' => '<br /><p id="ip-geo-block-' . $field . '-asn4" style="margin-left: 0.2em">' . $date . '</p>',
				)
			);
		}

		// ASN for IPv6
		if ( $dir !== dirname( $db['asn6_path'] ) . '/' )
			$db['asn6_path'] = $dir . IP_GEO_BLOCK_MAXMIND_ASN6_DAT;

		if ( $exists = $fs->exists( $db['asn6_path'] ) )
			$date = sprintf( $str_last, IP_Geo_Block_Util::localdate( $db['asn6_last'] ) );
		else
			$date = $msg;

		if ( $exists || $this->available( $exists ) ) {
			add_settings_field(
				$option_name . $field . '_asn6',
				"$field $str_path<br />(<a rel='noreferrer' href='" . IP_GEO_BLOCK_MAXMIND_DOWNLOAD . "' title='" . IP_GEO_BLOCK_MAXMIND_ASN6_ZIP . "'>ASN for IPv6</a>)",
				$callback,
				$option_slug,
				$section,
				array(
					'type' => 'text',
					'option' => $option_name,
					'field' => $field,
					'sub-field' => 'asn6_path',
					'value' => $db['asn6_path'],
					'disabled' => TRUE,
					'after' => '<br /><p id="ip-geo-block-' . $field . '-asn6" style="margin-left: 0.2em">' . $date . '</p>',
				)
			);
		}

endif; // ! empty( $db['use_asn'] ) || ! empty( $db['asn4_path'] )

	}
}

/**
 * Register API
 *
 */
IP_Geo_Block_Provider::register_addon( array(
	'Maxmind' => array(
		'key'  => NULL,
		'type' => 'IPv4, IPv6 / LGPLv2',
		'link' => '<a class="ip-geo-block-link" href="https://dev.maxmind.com/geoip/" title="GeoIP Products &laquo; Maxmind Developer Site" rel=noreferrer target=_blank>https://dev.maxmind.com/geoip/</a>&nbsp;(IPv4, IPv6 / LGPLv2)',
	),
) );
