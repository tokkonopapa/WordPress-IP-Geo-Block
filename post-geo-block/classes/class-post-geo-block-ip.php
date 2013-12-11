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
 * Default timeout in second
 * same as wordpress default
 * @link http://codex.wordpress.org/Function_Reference/wp_remote_get
 */
define( 'POST_GEO_BLOCK_IP_TIMEOUT', 5 );
define( 'POST_GEO_BLOCK_IP_IPV4', 1 );
define( 'POST_GEO_BLOCK_IP_IPV6', 3 );

/**
 * Abstract class
 *
 */
abstract class Post_Geo_Block_IP {

	/**
	 * These values must be instantiated in child class
	 *
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
		// test ip
//		$ip = '124.83.187.140'; // yahoo.co.jp
//		$ip = '208.80.154.225'; // wikipedia.org
//		$ip = '164.100.129.97'; // india.gov.in
//		$ip = '2a00:1450:400c:c00::6a';

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
				$tmp = strlower( $tmp ); // Content-Type, Content-type, ...
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
		$class_name = 'Post_Geo_Block_IP_' . preg_replace( '/[\W]/', '', $provider );
		return class_exists( $class_name ) ? $class_name : NULL;
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
 * Sample URL  : http://freegeoip.net/xml/124.83.187.140
 * Sample URL  : http://freegeoip.net/json/yahoo.co.jp
 * Sample URL  : http://freegeoip.net/xml/yahoo.co.jp
 * Input type  : IP address (IPv4, IPv6) / domain name
 * Output type : json
 * {
 *     "ip": "211.130.194.20",
 *     "country_code": "JP",
 *     "country_name": "Japan",
 *     "region_code": "40",
 *     "region_name": "Tokyo",
 *     "city": "Tokyo",
 *     "zipcode": "",
 *     "latitude": 35.69,
 *     "longitude": 139.69,
 *     "metro_code": "",
 *     "areacode": ""
 * }
 * Output type : xml
 * <?xml version="1.0" encoding="UTF-8"?>
 * <Response>
 *     <Ip>203.216.243.240</Ip>
 *     <CountryCode>JP</CountryCode>
 *     <CountryName>Japan</CountryName>
 *     <RegionCode>40</RegionCode>
 *     <RegionName>Tokyo</RegionName>
 *     <City>Tokyo</City>
 *     <ZipCode/>
 *     <Latitude>35.685</Latitude>
 *     <Longitude>139.7514</Longitude>
 *     <MetroCode/>
 *     <AreaCode/>
 * </Response>
 */
class Post_Geo_Block_IP_freegeoipnet extends Post_Geo_Block_IP {

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
 * {
 *     "ip": "124.83.187.140",
 *     "hostname": "No Hostname",
 *     "city": "Tokyo",
 *     "region": "Tokyo",
 *     "country": "JP",
 *     "loc": "35.69,139.69",
 *     "org": "AS0000 ISP NAME"
 * }
 */
class Post_Geo_Block_IP_ipinfoio extends Post_Geo_Block_IP {

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
 * {
 *     "timezone": "Asia/Tokyo",
 *     "isp": "ISP Name",
 *     "region_code": "40",
 *     "country": "Japan",
 *     "dma_code": "0",
 *     "area_code": "0",
 *     "region": "Tokyo",
 *     "ip": "124.83.187.140",
 *     "asn": "AS0000",
 *     "continent_code": "AS",
 *     "city": "Tokyo",
 *     "longitude": 139.7514,
 *     "latitude": 35.685,
 *     "country_code": "JP",
 *     "country_code3": "JPN"
 * }
 */
class Post_Geo_Block_IP_Telize extends Post_Geo_Block_IP {

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
 * Licence fee : free (linkware)
 * Rate limit  : 120 lookups per minute
 * Sample URL  : http://www.geoplugin.net/json.gp?ip=124.83.187.140
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json, xml
 * {
 *     "geoplugin_request": "124.83.187.140",
 *     "geoplugin_status": 200,
 *     "geoplugin_credit": "Some of the returned data includes GeoLite data ...",
 *     "geoplugin_city": "Tokyo",
 *     "geoplugin_region": "Tokyo",
 *     "geoplugin_areaCode": "0",
 *     "geoplugin_dmaCode": "0",
 *     "geoplugin_countryCode": "JP",
 *     "geoplugin_countryName": "Japan",
 *     "geoplugin_continentCode": "AS",
 *     "geoplugin_latitude": "35.689999",
 *     "geoplugin_longitude": "139.690002",
 *     "geoplugin_regionCode": "40",
 *     "geoplugin_regionName": "Tokyo",
 *     "geoplugin_currencyCode": "JPY",
 *     "geoplugin_currencySymbol": "&#165;",
 *     "geoplugin_currencySymbol_UTF8": "¥",
 *     "geoplugin_currencyConverter": 102.1873
 * }
 */
class Post_Geo_Block_IP_geoPlugin extends Post_Geo_Block_IP {

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
 * {
 *     "ip": "124.83.187.140",
 *     "country": "JP",
 *     "countryFullName": "Japan",
 *     "state": "40",
 *     "stateFullName": "Tokyo",
 *     "city": "Tokyo",
 *     "zip": "",
 *     "lat": 35.69,
 *     "lng": 139.69,
 *     "areacode": ""
 * }
 */
class Post_Geo_Block_IP_IPtoLatLng extends Post_Geo_Block_IP {

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
 * Sample URL  : http://ip-api.com/xml/124.83.187.140
 * Sample URL  : http://ip-api.com/json/yahoo.co.jp
 * Sample URL  : http://ip-api.com/xml/yahoo.co.jp
 * Input type  : IP address (IPv4, IPv6 with limited coverage) / domain name
 * Output type : json
 * {
 *     "status": "success",
 *     "country": "Japan",
 *     "countryCode": "JP",
 *     "region": "40",
 *     "regionName": "Tokyo",
 *     "city": "Tokyo",
 *     "zip": "",
 *     "lat": "35.69",
 *     "lon": "139.69",
 *     "timezone": "Asia/Tokyo",
 *     "isp": "ISP Name",
 *     "org": "ISP Name",
 *     "as": "AS0000 ISP Name",
 *     "query": "124.83.187.140"
 * }
 * Output type : xml
 * <?xml version="1.0" encoding="UTF-8"?>
 * <query>
 *     <status>success</status>
 *     <country><![CDATA[Japan]]></country>
 *     <countryCode><![CDATA[JP]]></countryCode>
 *     <region><![CDATA[40]]></region>
 *     <regionName><![CDATA[Tokyo]]></regionName>
 *     <city><![CDATA[Tokyo]]></city>
 *     <zip><![CDATA[]]></zip>
 *     <lat><![CDATA[35.69]]></lat>
 *     <lon><![CDATA[139.69]]></lon>
 *     <timezone><![CDATA[Asia/Tokyo]]></timezone>
 *     <isp><![CDATA[ISP Name]]></isp>
 *     <org><![CDATA[ISP Name]]></org>
 *     <as><![CDATA[AS0000 ISP Name]]></as>
 *     <query><![CDATA[124.83.187.140]]></query>
 * </query>
 */
class Post_Geo_Block_IP_ipapicom extends Post_Geo_Block_IP {

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
 * {
 *     "site": "http://ip-json.rhcloud.com",
 *     "city": null,
 *     "region_name": null,
 *     "region": null,
 *     "area_code": 0,
 *     "time_zone": "Asia/Tokyo",
 *     "longitude": 139.69000244140625,
 *     "metro_code": 0,
 *     "country_code3": "JPN",
 *     "latitude": 35.689998626708984,
 *     "postal_code": null,
 *     "dma_code": 0,
 *     "country_code": "JP",
 *     "country_name": "Japan",
 *     "q": "124.83.187.140"
 * }
 * Output type : xml
 * <?xml version="1.0" encoding="UTF-8"?>
 * <Response>
 *     <site>http://ip-json.rhcloud.com</site>
 *     <city>None</city>
 *     <region_name>None</region_name>
 *     <region>None</region>
 *     <area_code>0</area_code>
 *     <time_zone>Asia/Tokyo</time_zone>
 *     <longitude>139.690002441</longitude>
 *     <metro_code>0</metro_code>
 *     <country_code3>JPN</country_code3>
 *     <latitude>35.6899986267</latitude>
 *     <postal_code>None</postal_code>
 *     <dma_code>0</dma_code>
 *     <country_code>JP</country_code>
 *     <country_name>Japan</country_name>
 *     <q>124.83.187.140</q>
 * </Response>
 */
class Post_Geo_Block_IP_IPJson extends Post_Geo_Block_IP {

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
 * Output type : xml (ip-city)
 * <?xml version="1.0" encoding="UTF-8"?>
 * <Response>
 *   <statusCode>OK</statusCode>
 *   <statusMessage></statusMessage>
 *   <ipAddress>124.83.187.140</ipAddress>
 *   <countryCode>JP</countryCode>
 *   <countryName>JAPAN</countryName>
 *   <regionName>TOKYO</regionName>
 *   <cityName>TOKYO</cityName>
 *   <zipCode>214-0021</zipCode>
 *   <latitude>35.6149</latitude>
 *   <longitude>139.581</longitude>
 *   <timeZone>+09:00</timeZone>
 * </Response>
 * Output type : xml (ip-country)
 * <?xml version="1.0" encoding="UTF-8"?>
 * <Response>
 *   <statusCode>OK</statusCode>
 *   <statusMessage></statusMessage>
 *   <ipAddress>203.216.243.240</ipAddress>
 *   <countryCode>JP</countryCode>
 *   <countryName>JAPAN</countryName>
 * </Response>
 * Output type : json (ip-city)
 * {
 *     "statusCode": "OK",
 *     "statusMessage": "",
 *     "ipAddress": "124.83.187.140",
 *     "countryCode": "JP",
 *     "countryName": "JAPAN",
 *     "regionName": "TOKYO",
 *     "cityName": "TOKYO",
 *     "zipCode": "214-002",
 *     "latitude": "35.6149",
 *     "longitude": "139.5818",
 *     "timeZone": "+09:00"
 * }
 */
class Post_Geo_Block_IP_IPInfoDB extends Post_Geo_Block_IP {

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
 * Setup Provider table
 *
 */
class Post_Geo_Block_IP_Setup {
	protected static $providers = array(
		'freegeoip.net' => array(
			'url'  => 'http://freegeoip.net/',
			'key'  => NULL, // need no key (free)
			'type' => POST_GEO_BLOCK_IP_IPV4,
		),
		'ipinfo.io' => array(
			'url'  => 'http://ipinfo.io/',
			'key'  => NULL, // need no key (free)
			'type' => POST_GEO_BLOCK_IP_IPV4,
		),
		'Telize' => array(
			'url'  => 'http://www.telize.com/',
			'key'  => NULL, // need no key (free)
			'type' => POST_GEO_BLOCK_IP_IPV6,
		),
		'geoPlugin' => array(
			'url'  => 'http://www.geoplugin.com/',
			'key'  => NULL, // need no key but link (free)
			'type' => POST_GEO_BLOCK_IP_IPV6,
		),
		'IPtoLatLng' => array(
			'url'  => 'http://www.iptolatlng.com/',
			'key'  => NULL, // need no key (free)
			'type' => POST_GEO_BLOCK_IP_IPV6,
		),
		'ip-api.com' => array(
			'url'  => 'http://ip-api.com/',
			'key'  => NULL, // need no key (free for non-commercial use)
			'type' => POST_GEO_BLOCK_IP_IPV6,
		),
		'IP-Json' => array(
			'url'  => 'http://ip-json.rhcloud.com/',
			'key'  => NULL, // need no key (free)
			'type' => POST_GEO_BLOCK_IP_IPV6,
		),
		'IPInfoDB' => array(
			'url'  => 'http://ipinfodb.com/',
			'key'  => '', // need key (free for registered user)
			'type' => POST_GEO_BLOCK_IP_IPV4,
		),
	);

	public static function get_provider_keys() {
		$list = array();
		foreach ( self::$providers as $name => $val ) {
			$list += array( $name => $val['key'] );
		}
		return $list;
	}
}
/**
 * Test program for Post_Geo_Block_IP
 *
 * Resluts:
 *   freegeoip.net: 655[msec]
 *   ipinfo.io:     652[msec]
 *   Telize:        907[msec]
 *   geoPlugin:     950[msec]
 *   IPtoLatLng:   1163[msec]
 *   ip-api.com:   1169[msec]
 *   IP-Json:       654[msec]
 *   IPInfoDB:      493[msec]
?>
<!DOCTYPE html>
<html>
<body>
<pre>
<?php
require_once( 'class-post-geo-block-ip.php' );

$ip_list = array(
	'124.83.187.140',  // yahoo.co.jp
	'98.139.183.24',   // yahoo.com
	'74.125.225.63',   // google.co.jp
	'125.6.162.205',   // being.co.jp
	'108.175.7.24',    // being.com
	'208.80.154.225',  // wikipedia.org
	'210.131.4.217',   // nifty.com
	'119.63.198.132',  // baidu.jp
	'123.125.114.144', // baidu.cn
	'123.30.175.29',   // coccoc.com
	'173.252.110.27',  // facebook.com
	'199.59.149.230',  // twitter.com
	'212.174.189.120', // meb.gov.tr
	'85.111.24.135',   // trt.net.tr
	'83.111.117.101',  // awqaf.ae
	'164.100.129.97',  // india.gov.in
	'211.178.9.106',   // visitkorea.or.kr
	'152.99.202.101',  // kipo.go.kr
	'194.55.30.46',    // dw.de
	'201.54.48.105',   // senado.gov.br
//	'2a00:1450:400c:c00::6a',
//	'240f:62:76b3:1:5a55:caff:fef6:233',
);

$providers = array(
	'freegeoip.net' => '',
	'ipinfo.io'     => '',
	'Telize'        => '',
	'geoPlugin'     => '',
	'IPtoLatLng'    => '',
	'ip-api.com'    => '',
	'IP-Json'       => '',
	'IPInfoDB'      => 'adf0531f133f5110bc8212687ff6017139d190484fc286343b07f4778effaf0b',
);

$count = array();
$total = array();
foreach ( $providers as $name => $key ) {
	$count[$name] = 0;
	$total[$name] = 0;
}

shuffle( $ip_list );
foreach ( $ip_list as $ip ) {
	foreach ( $providers as $name => $key ) {
		$class_name = Post_Geo_Block_IP::get_class_name( $name );
		if ( $class_name ) {
			// start
			$start = microtime( TRUE );

			$ip_geoloc = new $class_name( $key );
			$res = $ip_geoloc->get_location( $ip );
//			$res = $ip_geoloc->get_country( $ip );

			// stop
			$count[$name]++;
			$total[$name] += microtime( TRUE ) - $start;

			echo "$name: ";
			var_dump( $res );
			flush();
		}
	}

	// interval
	sleep( 1 );
}

foreach ( $providers as $name => $key ) {
	if ( $count[$name] ) {
		$total[$name] *= (float) 1000;
		$total[$name] /= (float) $count[$name];
		echo "$name: ", intval( $total[$name] ), "[msec]\n";
	}
}
?>
</pre>
</body>
</html>
 */
