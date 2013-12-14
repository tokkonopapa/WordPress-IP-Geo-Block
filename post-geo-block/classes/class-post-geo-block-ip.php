<?php
/**
 * IP Address Geolocation Class
 *
 * @package   Post_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

/**
 * Default timeout in second (same as wordpress default)
 * @link http://codex.wordpress.org/Function_Reference/wp_remote_get
 */
define( 'POST_GEO_BLOCK_IP_TIMEOUT', 5 );
define( 'POST_GEO_BLOCK_IP_TYPE_IPV4', 1 ); // can handle IPv4
define( 'POST_GEO_BLOCK_IP_TYPE_IPV6', 2 ); // can handle IPv6
define( 'POST_GEO_BLOCK_IP_TYPE_BOTH', 3 ); // can handle both IPv4 and IPv6

/**
 * Abstract class
 *
 */
abstract class Post_Geo_Block_IP {

	/**
	 * These values must be instantiated in child class
	 *
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_[IPV4 | IPV6 | BOTH];
	protected $api_template = array(
		'api_key' => '', // %API_KEY%
		'format'  => '', // %API_FORMAT%
		'option'  => '', // %API_OPTION%
		'ip'      => '', // %API_IP%
	);
	protected $url_template = 'http://example.com/%API_KEY%/%API_FORMAT%/%API_OPTION%/%API_IP%';
	protected $transform_table = array(
		'countryCode' => '',
		'countryName' => '',
		'regionName'  => '',
		'cityName'    => '',
		'latitude'    => '',
		'longitude'   => '',
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

	public function __destruct() {}

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
	public function get_location( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {

		// check supported type of IP address
		if ( ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ( $this->api_type & POST_GEO_BLOCK_IP_TYPE_IPV4 ) ) &&
		     ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && ( $this->api_type & POST_GEO_BLOCK_IP_TYPE_IPV6 ) ) ) {
			return FALSE;
		}

		$tmp = $this->build_url( $ip );

		// for Wordpress
		if ( function_exists( "wp_remote_get" ) ) { // @since 2.7
			$res = @wp_remote_get( $tmp, array( 'timeout' => $timeout ) );

			if ( ! is_wp_error( $res ) && 200 == $res['response']['code'] ) {
				$tmp = $res['headers']['content-type'];
				$res = wp_remote_retrieve_body( $res ); // @since 2.7
			} else {
				return FALSE; // $res->get_error_message();
			}
		}

		// for standalone
		else {
			// save default timeout and set new timeout
			$ini_timeout = @ini_get( 'default_socket_timeout' );
			if ( $ini_timeout )
				@ini_set( 'default_socket_timeout', $timeout );

			$res = @file_get_contents( $tmp );

			// find content-type in response header
			foreach ( $http_response_header as $tmp ) {
				$tmp = strtolower( $tmp ); // Content-Type, Content-type, ...
				if ( strncmp( $tmp, 'content-type:', 13 ) === 0 ) {
					break;
				}
			}

			// restore default timeout
			if ( $ini_timeout )
				@ini_set( 'default_socket_timeout', $ini_timeout );
		}

		// clear decoded data
		$data = array();

		// extract content type
		// ex: "Content-type: text/plain; charset=utf-8"
		$tmp = explode( "/", $tmp );
		$tmp = explode( ";", $tmp[1] );
		$tmp = trim( $tmp[0] );

		switch ( $tmp ) {

		  // decode json
		  case 'json':
		  case 'plain': // geoPlugin
			$data = json_decode( $res, TRUE );
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

		  // just text
		  case 'html':
			return $res;

		  // unknown format
		  default:
			return FALSE;
		}

		// transformation
		$res = array();
		foreach ( $this->transform_table as $key => $val ) {
			if ( isset( $data[ $val ] ) )
				$res[ $key ] = $data[ $val ];
		}

		return $res;
	}

	/**
	 * Get only country code
	 *
	 * Override this method if a provider supports this feature for quick response.
	 */
	public function get_country( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {

		$res = $this->get_location( $ip, $timeout );

		if ( ! empty( $res ) && isset( $res['countryCode'] ) ) {
			// if country code is '-' or 'UNDEFINED' then error.
			return strlen( $res['countryCode'] ) === 2 ? $res['countryCode'] : NULL;
		} else {
			return NULL;
		}
	}

	/**
	 * Convert provider name to class name
	 *
	 */
	public static function get_class_name( $provider ) {
		$provider = 'Post_Geo_Block_IP_' . preg_replace( '/[\W]/', '', $provider );
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
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json, xml
 */
class Post_Geo_Block_IP_freegeoipnet extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_IPV4;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://freegeoip.net/%API_FORMAT%/%API_IP%';
	// xml
	/* protected $transform_table = array(
		'countryCode' => 'CountryCode',
		'countryName' => 'CountryName',
		'regionName'  => 'RegionName',
		'cityName'    => 'City',
		'latitude'    => 'Latitude',
		'longitude'   => 'Longitude',
	); */
	// json
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
 * Input type  : IP address (IPv4)
 * Output type : json
 */
class Post_Geo_Block_IP_ipinfoio extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_IPV4;
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

	public function get_location( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {
		$res = parent::get_location( $ip, $timeout );
		if ( ! empty( $res ) && isset( $res['latitude'] ) ) {
			$loc = explode( ',', $res['latitude'] );
			$res['latitude' ] = $loc[0];
			$res['longitude'] = $loc[1];
		}
		return $res;
	}

	public function get_country( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {
		$this->api_template['format'] = '';
		$this->api_template['option'] = 'country';
		$res = trim( $this->get_location( $ip, $timeout ) );
		return ! empty( $res ) && strlen( $res ) === 2 ? $res : NULL;
	}
}

/**
 * Class for Telize
 *
 * URL         : http://www.telize.com/
 * Term of use : http://www.telize.com/disclaimer/
 * Licence fee : free for everyone to use
 * Rate limit  : none
 * Sample URL  : http://www.telize.com/geoip/124.83.187.140
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json
 */
class Post_Geo_Block_IP_Telize extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_BOTH;
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
 * Class for geoPlugin
 *
 * URL         : http://www.geoplugin.com/
 * Term of use : http://www.geoplugin.com/whyregister
 * Licence fee : free (need to link)
 * Rate limit  : 120 lookups per minute
 * Sample URL  : http://www.geoplugin.net/json.gp?ip=124.83.187.140
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json, xml
 */
class Post_Geo_Block_IP_geoPlugin extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_BOTH;
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
 * Class for IPtoLatLng
 *
 * URL         : http://www.iptolatlng.com/
 * Term of use : 
 * Licence fee : free
 * Rate limit  : none
 * Sample URL  : http://www.iptolatlng.com?ip=124.83.187.140
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json
 */
class Post_Geo_Block_IP_IPtoLatLng extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_BOTH;
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
 * Class for ip-api.com
 *
 * URL         : http://ip-api.com/
 * Term of use : http://ip-api.com/docs/#usage_limits
 * Licence fee : free for non-commercial use
 * Rate limit  : 240 requests per minute
 * Sample URL  : http://ip-api.com/json/124.83.187.140
 * Sample URL  : http://ip-api.com/xml/yahoo.co.jp
 * Input type  : IP address (IPv4, IPv6 with limited coverage) / domain name
 * Output type : json, xml
 */
class Post_Geo_Block_IP_ipapicom extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://ip-api.com/%API_FORMAT%/%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'countryCode',
		'countryName' => 'country',
		'regionName'  => 'regionName',
		'cityName'    => 'city',
		'latitude'    => 'lat',
		'longitude'   => 'lon',
	);
}

/**
 * Class for IP-Json
 *
 * URL         : http://ip-json.rhcloud.com/
 * Term of use : 
 * Licence fee : free
 * Rate limit  : 
 * Sample URL  : http://ip-json.rhcloud.com/json/124.83.187.140
 * Sample URL  : http://ip-json.rhcloud.com/xml/124.83.187.140
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json, xml, csv
 */
class Post_Geo_Block_IP_IPJson extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_BOTH;
	protected $api_template = array(
		'api_key' => '',
		'format'  => 'json',
		'option'  => '',
		'ip'      => '',
	);
	protected $url_template = 'http://ip-json.rhcloud.com/%API_FORMAT%/%API_IP%';
	protected $transform_table = array(
		'countryCode' => 'country_code',
		'countryName' => 'country_name',
		'regionName'  => 'region_name',
		'cityName'    => 'city',
		'latitude'    => 'latitude',
		'longitude'   => 'longitude',
	);

	public function get_location( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$this->api_template['format'] = 'v6';
		}
		return parent::get_location( $ip, $timeout );
	}
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
class Post_Geo_Block_IP_IPInfoDB extends Post_Geo_Block_IP {
	protected $api_type = POST_GEO_BLOCK_IP_TYPE_IPV4;
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

	public function get_country( $ip, $timeout = POST_GEO_BLOCK_IP_TIMEOUT ) {

		$this->api_template['option'] = 'ip-country';
		$res = $this->get_location( $ip, $timeout );

		if ( ! empty( $res ) && isset( $res['countryCode'] ) ) {
			// if country code is '-' or 'UNDEFINED' then error.
			return strlen( $res['countryCode'] ) === 2 ? $res['countryCode'] : NULL;
		} else {
			return NULL;
		}
	}
}

/**
 * Infomation class about supported providers
 *
 */
class Post_Geo_Block_IP_Info {

	protected static $providers = array(
		'freegeoip.net' => array(
			'url'  => 'http://freegeoip.net/',
			'key'  => NULL, // need no key (free)
		),
		'ipinfo.io' => array(
			'url'  => 'http://ipinfo.io/',
			'key'  => NULL, // need no key (free)
		),
		'Telize' => array(
			'url'  => 'http://www.telize.com/',
			'key'  => NULL, // need no key (free)
		),
		'geoPlugin' => array(
			'url'  => 'http://www.geoplugin.com/',
			'key'  => NULL, // need no key but link (free)
		),
		'IPtoLatLng' => array(
			'url'  => 'http://www.iptolatlng.com/',
			'key'  => NULL, // need no key (free)
		),
		'ip-api.com' => array(
			'url'  => 'http://ip-api.com/',
			'key'  => NULL, // need no key (free for non-commercial use)
		),
		'IP-Json' => array(
			'url'  => 'http://ip-json.rhcloud.com/',
			'key'  => NULL, // need no key (free)
		),
		'IPInfoDB' => array(
			'url'  => 'http://ipinfodb.com/',
			'key'  => '', // need key (free for registered user)
		),
	);

	/**
	 * Returns the pairs of provider name and API key
	 *
	 */
	public static function get_provider_keys() {
		$list = array();
		foreach ( self::$providers as $name => $val ) {
			$list += array( $name => $val['key'] );
		}
		return $list;
	}
}
