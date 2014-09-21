<?php
/**
 * IP Address Geolocation Class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

/**
 * Service type
 *
 */
define( 'IP_GEO_BLOCK_API_TYPE_IPV4', 1 ); // can handle IPv4
define( 'IP_GEO_BLOCK_API_TYPE_IPV6', 2 ); // can handle IPv6
define( 'IP_GEO_BLOCK_API_TYPE_BOTH', 3 ); // can handle both IPv4 and IPv6

/**
 * Abstract class
 *
 */
abstract class IP_Geo_Block_API {

	/**
	 * These values must be instantiated in child class
	 *
	protected $api_type = IP_GEO_BLOCK_API_TYPE_[IPV4 | IPV6 | BOTH];
	protected $api_template = array(
		'api_key' => '', // %API_KEY%
		'format'  => '', // %API_FORMAT%
		'option'  => '', // %API_OPTION%
		'ip'      => '', // %API_IP%
	);
	protected $url_template = 'http://example.com/%API_KEY%/%API_FORMAT%/%API_OPTION%/%API_IP%';
	protected $transform_table = array(
		'errorMessage' => '',
		'countryCode'  => '',
		'countryName'  => '',
		'regionName'   => '',
		'cityName'     => '',
		'latitude'     => '',
		'longitude'    => '',
	);
	*/

	/**
	 * Constructer & Destructer
	 *
	 */
	public function __construct( $api_key = NULL ) {
		if ( is_string( $api_key ) )
			$this->api_template['api_key'] = $api_key;
	}

	/**
	 * Build URL from template
	 *
	 */
	private function build_url( $ip ) {
		$this->api_template['ip'] = $ip;
		return preg_replace(
			array(
				'/%API_KEY%/',
				'/%API_FORMAT%/',
				'/%API_OPTION%/',
				'/%API_IP%/',
			),
			$this->api_template,
			$this->url_template
		);
	}

	/**
	 * Get geolocation information from service provider
	 *
	 */
	public function get_location( $ip, $args = array() ) {

		// check supported type of IP address
		if ( ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ( $this->api_type & IP_GEO_BLOCK_API_TYPE_IPV4 ) ) &&
		     ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && ( $this->api_type & IP_GEO_BLOCK_API_TYPE_IPV6 ) ) ) {
			return FALSE;
		}

		// build query
		$tmp = $this->build_url( $ip );

		// http://codex.wordpress.org/Function_Reference/wp_remote_get
		if ( function_exists( "wp_remote_get" ) ) { // @since 2.7
			$res = @wp_remote_get( $tmp, $args );
			if ( is_wp_error( $res ) )
				return array( 'errorMessage' => $res->get_error_message() );
			$tmp = wp_remote_retrieve_header( $res, 'content-type' );
			$res = wp_remote_retrieve_body( $res );
		}

		// clear decoded data
		$data = array();

		// extract content type
		// ex: "Content-type: text/plain; charset=utf-8"
		if ( $tmp ) {
			$tmp = explode( "/", $tmp, 2 );
			$tmp = explode( ";", $tmp[1], 2 );
			$tmp = trim( $tmp[0] );
		}

		switch ( $tmp ) {

		  // decode json
		  case 'json':
		  case 'html':  // ipinfo.io, Xhanch
		  case 'plain': // geoPlugin
			$data = json_decode( $res, TRUE ); // PHP 5 >= 5.2.0, PECL json >= 1.2.0
			if ( NULL === $data ) { // ipinfo.io (get_country)
				$data[ $this->transform_table['countryCode'] ] = trim( $res );
			}
			break;

		  // decode xml
		  case 'xml':
			$tmp = "/\<(.+?)\>(?:\<\!\[CDATA\[)?(.*?)(?:\]\]\>)?\<\/\\1\>/i";
			if ( preg_match_all( $tmp, $res, $matches ) !== FALSE ) {
				if ( is_array( $matches[1] ) && ! empty( $matches[1] ) ) {
					foreach ( $matches[1] as $key => $val ) {
						$data[ $val ] = $matches[2][ $key ];
					}
				}
			}
			break;

		  // unknown format
		  default:
			return array( 'errorMessage' => "unsupported content type: $tmp" );
		}

		// transformation
		$res = array();
		foreach ( $this->transform_table as $key => $val ) {
			if ( ! empty( $val ) && ! empty( $data[ $val ] ) )
				$res[ $key ] = esc_html( $data[ $val ] );
		}

		return $res;
	}

	/**
	 * Get only country code
	 *
	 * Override this method if a provider supports this feature for quick response.
	 */
	public function get_country( $ip, $args = array() ) {

		$res = $this->get_location( $ip, $args );

		// if country code is '-' or 'UNDEFINED' then error.
		if ( ! empty( $res ) && ! empty( $res['countryCode'] ) )
			return strlen( $res['countryCode'] ) === 2 ? $res['countryCode'] : NULL;
		else
			return NULL;
	}

	/**
	 * Convert provider name to class name
	 *
	 */
	public static function get_class_name( $provider ) {
		$provider = 'IP_Geo_Block_API_' . preg_replace( '/[\W]/', '', $provider );
		return class_exists( $provider ) ? $provider : NULL;
	}
}

/**
 * Class for freegeoip.net
 *
 * URL         : http://freegeoip.net/
 * Term of use :
 * Licence fee : free (donationware)
 * Rate limit  : 10,000 queries per hour
 * Sample URL  : http://freegeoip.net/json/124.83.187.140
 * Sample URL  : http://freegeoip.net/xml/yahoo.co.jp
 * Input type  : IP address (IPv4) / domain name
 * Output type : json, jsonp, xml, csv
 */
class IP_Geo_Block_API_freegeoipnet extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_IPV4;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://freegeoip.net/%API_FORMAT%/%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'country_code',
		'countryName' => 'country_name',
		'regionName'  => 'region_name',
		'cityName'    => 'city',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);
}

/**
 * Class for ipinfo.io
 *
 * URL         : http://ipinfo.io/
 * Term of use : http://ipinfo.io/developers#terms
 * Licence fee : free
 * Rate limit  :
 * Sample URL  : http://ipinfo.io/124.83.187.140/json
 * Sample URL  : http://ipinfo.io/124.83.187.140/country
 * Input type  : IP address (IPv4)
 * Output type : json
 */
class IP_Geo_Block_API_ipinfoio extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://ipinfo.io/%API_IP%/%API_FORMAT%%API_OPTION%';
	protected $transform_table = array(
		'countryCode' => 'country',
		'countryName' => 'country',
		'regionName'  => 'region',
		'cityName'    => 'city',
		'latitude'    => 'loc',
		'longitude'   => 'loc',
	);

	public function get_location( $ip, $args = array() ) {
		$res = parent::get_location( $ip, $args );
		if ( ! empty( $res ) && ! empty( $res['latitude'] ) ) {
			$loc = explode( ',', $res['latitude'] );
			$res['latitude' ] = $loc[0];
			$res['longitude'] = $loc[1];
		}
		return $res;
	}

	public function get_country( $ip, $args = array() ) {
		$this->api_template['format'] = '';
		$this->api_template['option'] = 'country';
		return parent::get_country( $ip, $args );
	}
}

/**
 * Class for Telize
 *
 * URL         : http://www.telize.com/
 * Term of use : http://www.telize.com/disclaimer/
 * Licence fee : free for everyone to use
 * Rate limit  : none
 * Sample URL  : http://www.telize.com/geoip/2a00:1210:fffe:200::1
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json, jsonp
 */
class IP_Geo_Block_API_Telize extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => '',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://www.telize.com/geoip/%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'country_code',
		'countryName' => 'country',
		'regionName'  => 'region',
		'cityName'    => 'city',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);
}

/**
 * Class for IPtoLatLng
 *
 * URL         : http://www.iptolatlng.com/
 * Term of use : 
 * Licence fee : free
 * Rate limit  : none
 * Sample URL  : http://www.iptolatlng.com?ip=2a00:1210:fffe:200::1
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json
 */
class IP_Geo_Block_API_IPtoLatLng extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://www.iptolatlng.com/?type=%API_FORMAT%&ip=%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'country',
		'countryName' => 'countryFullName',
		'regionName'  => 'stateFullName',
		'cityName'    => 'city',
		'latitude'    => 'lat',
		'longitude'   => 'lng',
	);
}

/**
 * Class for IP-Json
 *
 * URL         : http://ip-json.rhcloud.com/
 * Term of use : 
 * Licence fee : free
 * Rate limit  : 
 * Sample URL  : http://ip-json.rhcloud.com/xml/124.83.187.140
 * Sample URL  : http://ip-json.rhcloud.com/v6/2a00:1210:fffe:200::1
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json, xml, csv
 */
class IP_Geo_Block_API_IPJson extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://ip-json.rhcloud.com/%API_FORMAT%/%API_IP%';
	protected $transform_table = array(
		'errorMessage' => 'error',
		'countryCode'  => 'country_code',
		'countryName'  => 'country_name',
		'regionName'   => 'region_name',
		'cityName'     => 'city',
		'latitude'     => 'latitude',
		'longitude'    => 'longitude',
	);

	public function get_location( $ip, $args = array() ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$this->api_template['format'] = 'v6';
		return parent::get_location( $ip, $args );
	}
}

/**
 * Class for Xhanch
 *
 * URL         : http://xhanch.com/xhanch-api-ip-get-detail/
 * Term of use : 
 * Licence fee : free (donationware)
 * Rate limit  : 
 * Sample URL  : http://api.xhanch.com/ip-get-detail.php?ip=124.83.187.140
 * Sample URL  : http://api.xhanch.com/ip-get-detail.php?ip=124.83.187.140&m=json
 * Input type  : IP address (IPv4)
 * Output type : xml, json
 */
class IP_Geo_Block_API_Xhanch extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_IPV4;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://api.xhanch.com/ip-get-detail.php?ip=%API_IP%&m=%API_FORMAT%';
	protected $transform_table = array(
		'countryCode' => 'country_code',
		'countryName' => 'country_name',
		'regionName'  => 'region',
		'cityName'    => 'city',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);
}

/**
 * Class for mshd.net
 *
 * URL         : http://mshd.net/documentation/geoip
 * Term of use : http://mshd.net/disclaimer
 * Licence fee : 
 * Rate limit  : 
 * Sample URL  : http://mshd.net/api/geoip?ip=2a00:1210:fffe:200::1&output=json
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json, php
 */
class IP_Geo_Block_API_mshdnet extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://mshd.net/api/geoip?ip=%API_IP%&output=%API_FORMAT%';
	protected $transform_table = array(
		'errorMessage' => 'error',
		'countryCode'  => 'country_code',
		'countryName'  => 'country_name',
		'regionName'   => 'region_name',
		'cityName'     => 'city_name',
		'latitude'     => 'latitude',
		'longitude'    => 'longitude',
	);
}

/**
 * Class for geoPlugin
 *
 * URL         : http://www.geoplugin.com/
 * Term of use : http://www.geoplugin.com/whyregister
 * Licence fee : free (need an attribution link)
 * Rate limit  : 120 lookups per minute
 * Sample URL  : http://www.geoplugin.net/json.gp?ip=2a00:1210:fffe:200::1
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json, xml, php, etc
 */
class IP_Geo_Block_API_geoPlugin extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://www.geoplugin.net/%API_FORMAT%.gp?ip=%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'geoplugin_countryCode',
		'countryName' => 'geoplugin_countryName',
		'regionName'  => 'geoplugin_region',
		'cityName'    => 'geoplugin_city',
		'latitude'    => 'geoplugin_latitude',
		'longitude'   => 'geoplugin_longitude',
	);
}

/**
 * Class for ip-api.com
 *
 * URL         : http://ip-api.com/
 * Term of use : http://ip-api.com/docs/#usage_limits
 * Licence fee : free for non-commercial use
 * Rate limit  : 240 requests per minute
 * Sample URL  : http://ip-api.com/json/2a00:1210:fffe:200::1
 * Sample URL  : http://ip-api.com/xml/yahoo.co.jp
 * Input type  : IP address (IPv4, IPv6 with limited coverage) / domain name
 * Output type : json, xml
 */
class IP_Geo_Block_API_ipapicom extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://ip-api.com/%API_FORMAT%/%API_IP%';
	protected $transform_table = array(
		'errorMessage' => 'error',
		'countryCode'  => 'countryCode',
		'countryName'  => 'country',
		'regionName'   => 'regionName',
		'cityName'     => 'city',
		'latitude'     => 'lat',
		'longitude'    => 'lon',
	);
}

/**
 * Class for IPInfoDB
 *
 * URL         : http://ipinfodb.com/
 * Term of use : http://ipinfodb.com/ipinfodb_agreement.pdf
 * Licence fee : free (need to regist to get API key)
 * Rate limit  : 2 queries/second for registered user
 * Sample URL  : http://api.ipinfodb.com/v3/ip-city/?key=...&format=xml&ip=124.83.187.140
 * Sample URL  : http://api.ipinfodb.com/v3/ip-country/?key=...&format=xml&ip=yahoo.co.jp
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json, xml
 */
class IP_Geo_Block_API_IPInfoDB extends IP_Geo_Block_API {
	protected $api_type = IP_GEO_BLOCK_API_TYPE_IPV4;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'xml',
		'option'  => 'ip-city',
		'ip'      => '',
	);
	protected $url_template = 'http://api.ipinfodb.com/v3/%API_OPTION%/?key=%API_KEY%&format=%API_FORMAT%&ip=%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'countryCode',
		'countryName' => 'countryName',
		'regionName'  => 'regionName',
		'cityName'    => 'cityName',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);

	public function get_country( $ip, $args = array() ) {
		$this->api_template['option'] = 'ip-country';
		return parent::get_country( $ip, $args );
	}
}

/**
 * Check if internal databases are available
 */
if ( function_exists( 'get_option' ) ) {
	$options = get_option( 'ip_geo_block_settings' );

	// IP2Location
	if ( file_exists( $options['ip2location']['ipv4_path'] ) ) {
		define( 'IP_GEO_BLOCK_IP2LOC_IPV4', $options['ip2location']['ipv4_path'] );
	}

	// Maxmind
	if ( file_exists( $options['maxmind']['ipv4_path'] ) &&
	     file_exists( $options['maxmind']['ipv6_path'] ) ) {
		define( 'IP_GEO_BLOCK_MAXMIND_IPV4', $options['maxmind']['ipv4_path'] );
		define( 'IP_GEO_BLOCK_MAXMIND_IPV6', $options['maxmind']['ipv6_path'] );
	}
}

/**
 * Class for IP2Location
 *
 * URL         : http://www.ip2location.com/
 * Term of use : http://www.ip2location.com/terms
 * Licence fee : free in case of WordPress plugin version
 * Input type  : IP address (IPv4)
 * Output type : array
 */
if ( defined( 'IP_GEO_BLOCK_IP2LOC_IPV4' ) ) :

class IP_Geo_Block_API_IP2Location extends IP_Geo_Block_API {
	protected $transform_table = array(
		'countryCode' => 'countryCode',
		'countryName' => 'countryName',
		'regionName'  => 'regionName',
		'cityName'    => 'cityName',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);

	public function __construct( $api_key = NULL ) {
		parent::__construct( $api_key );
		require_once( IP_GEO_BLOCK_PATH . 'includes/IP2Location.php' );
	}

	public function get_location( $ip, $args = array() ) {
		// http://stackoverflow.com/questions/18276757/php-convert-ipv6-to-number
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			try {
				$geo = new IP2Location( IP_GEO_BLOCK_IP2LOC_IPV4 );
				$data = $geo->lookup( $ip );
			} catch (Exception $e) {
				return array( 'errorMessage' => $e->getMessage() );
			}

			$res = array();
			foreach ( $this->transform_table as $key => $val ) {
				if ( ! empty( $val ) && ! empty( $data->$val ) )
					$res[ $key ] = $data->$val;
			}

			if ( strlen( $res['countryCode'] ) === 2 ) {
				if ( is_string( $res['latitude' ] ) ) unset( $res['latitude' ] );
				if ( is_string( $res['longitude'] ) ) unset( $res['longitude'] );
				return $res;
			}
		}

		return array( 'errorMessage' => 'Not supported' );
	}

	public function get_country( $ip, $args = array() ) {
		$res = $this->get_location( $ip, $args );
		return $res && ! empty( $res['countryCode'] ) ? $res['countryCode'] : FALSE;
	}
}

endif;

/**
 * Class for Maxmind
 *
 * URL         : http://dev.maxmind.com/geoip/legacy/geolite/
 * Term of use : http://dev.maxmind.com/geoip/legacy/geolite/#License
 * Licence fee : Creative Commons Attribution-ShareAlike 3.0 Unported License
 * Input type  : IP address (IPv4, IPv6)
 * Output type : array
 */
if ( defined( 'IP_GEO_BLOCK_MAXMIND_IPV4' ) ) :

class IP_Geo_Block_API_Maxmind extends IP_Geo_Block_API {

	public function __construct( $api_key = NULL ) {
		parent::__construct( $api_key );
		require_once( IP_GEO_BLOCK_PATH . 'includes/geoip.inc' );
	}

	public function get_location( $ip, $args = array() ) {
		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$file = IP_GEO_BLOCK_MAXMIND_IPV4;
			$func = geoip_country_code_by_addr;
		}
		else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$file = IP_GEO_BLOCK_MAXMIND_IPV6;
			$func = geoip_country_code_by_addr_v6;
		}
		else
			return FALSE;

		// open database and fetch data
		if ( null == ( $geo = geoip_open( $file, GEOIP_STANDARD ) ) )
			return FALSE;
		$res = $func( $geo, $ip );
		geoip_close( $geo );

		return array( 'countryCode' => $res );
	}

	public function get_country( $ip, $args = array() ) {
		$res = $this->get_location( $ip, $args );
		return $res && ! empty( $res['countryCode'] ) ? $res['countryCode'] : FALSE;
	}
}

endif;

/**
 * Class for Cache
 *
 * URL         : http://codex.wordpress.org/Transients_API
 * Input type  : IP address (IPv4, IPv6)
 * Output type : array
 */
if ( class_exists( 'IP_Geo_Block' ) ) :

class IP_Geo_Block_API_Cache extends IP_Geo_Block_API {
	protected $transform_table = array(
		'countryCode'   => 'code',
	);

	public function get_location( $ip, $args = array() ) {
		if ( false === ( $cache = get_transient( IP_Geo_Block::CACHE_KEY ) ) ||
		     false === array_key_exists( $ip, $cache ) )
			return array( 'errorMessage' => 'not in the cache' );
		else
			return array( 'countryCode' => $cache[ $ip ][ $this->transform_table['countryCode'] ] );
	}

	public function get_country( $ip, $args = array() ) {
		$val = $this->get_location( $ip, $args );
		return $val ? $val['countryCode'] : FALSE;
	}

	public static function update_cache( $ip, $code, $settings ) {
		$time = time();
		$num = ! empty( $settings['cache_hold'] ) ? $settings['cache_hold'] : 10;
		$exp = ! empty( $settings['cache_time'] ) ? $settings['cache_time'] : HOUR_IN_SECONDS;

		if ( $cache = get_transient( IP_Geo_Block::CACHE_KEY ) ) {
			$n = 0;
			foreach ( $cache as $key => $val ) {
				if ( $time - $val['time'] > $exp || ++$n >= $num )
					unset( $cache[ $key ] );
			}
		}

		if ( $settings['save_statistics'] ) {
			$count = ! empty( $cache[ $ip ]['call'] ) ? $cache[ $ip ]['call'] + 1 : 1;
			$cache[ $ip ] = array( 'time' => $time, 'code' => $code, 'call' => $count );
		} else {
			$cache[ $ip ] = array( 'time' => $time, 'code' => $code );
		}

		set_transient( IP_Geo_Block::CACHE_KEY, $cache, $exp ); // @since 2.8
	}

	public static function delete_cache() {
		delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8
	}
}

endif;

/**
 * Provider support class
 *
 */
class IP_Geo_Block_Provider {

	protected static $providers = array(

		'freegeoip.net' => array(
			'key'  => NULL,
			'type' => 'IPv4 / free',
			'link' => '<a class="ip-geo-block-link" href="http://freegeoip.net/" title="freegeoip.net: FREE IP Geolocation Web Service" target=_blank>http://freegeoip.net/</a>&nbsp;(IPv4 / free)',
		),

		'ipinfo.io' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ipinfo.io/" title="ip address information including geolocation, hostname and network details" target=_blank>http://ipinfo.io/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'Telize' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://www.telize.com/" title="Telize - JSON IP and GeoIP REST API" target=_blank>http://www.telize.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'IPtoLatLng' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://www.iptolatlng.com/" title="IP to Latitude, Longitude" target=_blank>http://www.iptolatlng.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'IP-Json' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ip-json.rhcloud.com/" title="Free IP Geolocation Web Service" target=_blank>http://ip-json.rhcloud.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'Xhanch' => array(
			'key'  => NULL,
			'type' => 'IPv4 / free',
			'link' => '<a class="ip-geo-block-link" href="http://xhanch.com/xhanch-api-ip-get-detail/" title="Xhanch API &#8211; IP Get Detail | Xhanch Studio" target=_blank>http://xhanch.com/</a>&nbsp;(IPv4 / free)',
		),

		'mshd.net' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://mshd.net/documentation/geoip" title="www.mshd.net - Geoip Documentation" target=_blank>http://mshd.net/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'geoPlugin' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://www.geoplugin.com/geolocation/" title="geoPlugin to geolocate your visitors" target="_new">IP Geolocation</a> by <a href="http://www.geoplugin.com/" title="plugin to geo-targeting and unleash your site\' potential." target="_new">geoPlugin</a>&nbsp;(IPv4, IPv6 / free, need an attribution link)',
		),

		'ip-api.com' => array(
			'key'  => FALSE,
			'type' => 'IPv4, IPv6 / free for non-commercial use',
			'link' => '<a class="ip-geo-block-link" href="http://ip-api.com/" title="IP-API.com - Free Geolocation API" target=_blank>http://ip-api.com/</a>&nbsp;(IPv4, IPv6 / free for non-commercial use)',
		),

		'IPInfoDB' => array(
			'key'  => '',
			'type' => 'IPv4, IPv6 / free for registered user',
			'link' => '<a class="ip-geo-block-link" href="http://ipinfodb.com/" title="IPInfoDB | Free IP Address Geolocation Tools" target=_blank>http://ipinfodb.com/</a>&nbsp;(IPv4, IPv6 / free for registered user)',
		),
	);

	// Internal DB
	protected static $internals = array(
		'IP2Location' => array(
			'key'  => NULL,
			'type' => 'IPv4 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://www.ip2location.com/free/plugins" title="Free Plugins | IP2Location.com" target=_blank>http://www.ip2location.com/</a>&nbsp;(IPv4 / free, need an attribution link)',
		),

		'Maxmind' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://dev.maxmind.com/geoip/legacy/geolite/" title="GeoLite Free Downloadable Databases &laquo; Maxmind Developer Site" target=_blank>http://www.maxmind.com</a>&nbsp;(IPv4, IPv6 / free, need an attribution link)',
		),

		'Cache' => array(
			'key' => NULL,
			'type' => 'IPv4, IPv6',
			'link' => NULL,
		),
	);

	/**
	 * Returns the pairs of provider name and API key
	 *
	 */
	public static function get_providers( $key, $rand = FALSE, $cache = FALSE ) {
		$tmp = array_keys( self::$providers );

		// randomize
		if ( $rand )
			shuffle( $tmp );

		$list = array();
		foreach ( $tmp as $name ) {
			$list += array( $name => self::$providers[ $name ][ $key ] );
		}

		// add Internal DB
		if ( class_exists( 'IP_Geo_Block_API_IP2Location' ) )
			$list = array(
				'IP2Location' => self::$internals['IP2Location'][ $key ]
			) + $list;

		if ( class_exists( 'IP_Geo_Block_API_Maxmind' ) )
			$list = array(
				'Maxmind' => self::$internals['Maxmind'][ $key ]
			) + $list;

		if ( $cache )
			$list = array(
				'Cache' => self::$internals['Cache'][ $key ]
			) + $list;

		return $list;
	}

}