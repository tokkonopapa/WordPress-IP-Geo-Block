<?php
/**
 * IP Geo Block - IP Address Geolocation API Class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013-2015 tokkonopapa
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
	 *//*
	protected $api_type = IP_GEO_BLOCK_API_TYPE_[IPV4 | IPV6 | BOTH];
	protected $api_template = array(
		'%API_IP%'     => '', // should be set in build_url()
		'%API_KEY%'    => '', // should be set in __construct()
		'%API_FORMAT%' => '', // may be set in child class
		'%API_OPTION%' => '', // may be set in child class
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
	);*/

	/**
	 * Constructer & Destructer
	 *
	 */
	public function __construct( $api_key = NULL ) {
		if ( is_string( $api_key ) )
			$this->api_template['%API_KEY%'] = $api_key;
	}

	/**
	 * Build URL from template
	 *
	 */
	private function build_url( $ip ) {
		$this->api_template['%API_IP%'] = $ip;
		return str_replace(
			array_keys( $this->api_template ),
			array_values( $this->api_template ),
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
		$res = @wp_remote_get( $tmp, $args ); // @since 2.7
		if ( is_wp_error( $res ) )
			return array( 'errorMessage' => $res->get_error_message() );
		$tmp = wp_remote_retrieve_header( $res, 'content-type' );
		$res = wp_remote_retrieve_body( $res );

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
			if ( NULL === $data ) // ipinfo.io (get_country)
				$data[ $this->transform_table['countryCode'] ] = trim( $res );
			break;

		  // decode xml
		  case 'xml':
			$tmp = "/\<(.+?)\>(?:\<\!\[CDATA\[)?(.*?)(?:\]\]\>)?\<\/\\1\>/i";
			if ( @preg_match_all( $tmp, $res, $matches ) !== FALSE ) {
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
				$res[ $key ] = is_string( $data[ $val ] ) ? 
					esc_html( $data[ $val ] ) : $data[ $val ];
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
		if ( $res && ! empty( $res['countryCode'] ) )
			return @preg_match( '/^[A-Z]{2}/', $res['countryCode'], $matches ) ? $matches[0] : NULL;
		else
			return NULL;
	}

	/**
	 * Convert provider name to class name
	 *
	 */
	public static function get_class_name( $provider ) {
		$provider = 'IP_Geo_Block_API_' . @preg_replace( '/[\W]/', '', $provider );
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
		'%API_FORMAT%' => 'json',
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
		'%API_FORMAT%' => 'json',
		'%API_OPTION%' => '',
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
		$this->api_template['%API_FORMAT%'] = '';
		$this->api_template['%API_OPTION%'] = 'country';
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
	protected $api_template = array();
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
		'%API_FORMAT%' => 'json',
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
			$this->api_template['%API_FORMAT%'] = 'v6';
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
		'%API_FORMAT%' => 'json',
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
		'%API_FORMAT%' => 'json',
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
	protected $api_type = IP_GEO_BLOCK_API_TYPE_IPV4;
	protected $api_template = array(
		'%API_FORMAT%' => 'json',
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
		'%API_FORMAT%' => 'xml',
		'%API_OPTION%' => 'ip-city',
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

	public function __construct( $api_key = NULL ) {
		// sanitization
		parent::__construct( preg_replace( '/\W/', '', $api_key ) );
	}

	public function get_country( $ip, $args = array() ) {
		$this->api_template['%API_OPTION%'] = 'ip-country';
		return parent::get_country( $ip, $args );
	}
}

/**
 * Check if local database files are available
 */
if ( class_exists( 'IP_Geo_Block' ) ) :
	$options = IP_Geo_Block::get_option( 'settings' );

	// IP2Location
	$path = $options['ip2location']['ipv4_path'];
	$path = apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-ip2location-path', $path );
	if ( file_exists( $path ) )
		define( 'IP_GEO_BLOCK_IP2LOC_IPV4', $path );

	// Maxmind
	if ( file_exists( $options['maxmind']['ipv4_path'] ) &&
	     file_exists( $options['maxmind']['ipv6_path'] ) ) {
		define( 'IP_GEO_BLOCK_MAXMIND_IPV4', $options['maxmind']['ipv4_path'] );
		define( 'IP_GEO_BLOCK_MAXMIND_IPV6', $options['maxmind']['ipv6_path'] );
	}
endif;

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

	public function get_location( $ip, $args = array() ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/venders/ip2location/IP2Location.php' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$file = IP_GEO_BLOCK_IP2LOC_IPV4;
			$type = IP_GEO_BLOCK_API_TYPE_IPV4;
		}
		else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$file = IP_GEO_BLOCK_IP2LOC_IPV4; // currently, support only one file
			$type = IP_GEO_BLOCK_API_TYPE_IPV6;
		}
		else {
			return array( 'errorMessage' => 'illegal format' );
		}

		try {
			$geo = new IP2Location( $file );
			if ( $geo && ( $geo->get_database_type() & $type ) ) {
				$res = array();
				$data = $geo->lookup( $ip );
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
		}

		catch (Exception $e) {
			return array( 'errorMessage' => $e->getMessage() );
		}

		return array( 'errorMessage' => 'Not supported' );
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

	private function location_country( $record ) {
		return array( 'countryCode' => $record );
	}

	private function location_city( $record ) {
		return array(
			'countryCode' => $record->country_code,
			'latitude'    => $record->latitude,
			'longitude'   => $record->longitude,
		);
	}

	public function get_location( $ip, $args = array() ) {
		require_once( IP_GEO_BLOCK_PATH . 'includes/venders/maxmind/geoip.inc' );

		// setup database file and function
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
			$file = IP_GEO_BLOCK_MAXMIND_IPV4;
		else if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$file = IP_GEO_BLOCK_MAXMIND_IPV6;
		else
			return array( 'errorMessage' => 'illegal format' );

		// open database and fetch data
		if ( null == ( $geo = geoip_open( $file, GEOIP_STANDARD ) ) )
			return FALSE;

		switch ( $geo->databaseType ) {
		  case GEOIP_COUNTRY_EDITION:
			$res = $this->location_country( geoip_country_code_by_addr( $geo, $ip ) );
			break;
		  case GEOIP_COUNTRY_EDITION_V6:
			$res = $this->location_country( geoip_country_code_by_addr_v6( $geo, $ip ) );
			break;
		  case GEOIP_CITY_EDITION_REV1:
			require_once( IP_GEO_BLOCK_PATH . 'includes/venders/maxmind/geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr( $geo, $ip ) );
			break;
		  case GEOIP_CITY_EDITION_REV1_V6:
			require_once( IP_GEO_BLOCK_PATH . 'includes/venders/maxmind/geoipcity.inc' );
			$res = $this->location_city( geoip_record_by_addr_v6( $geo, $ip ) );
			break;
		  default:
			$res = array( 'errorMessage' => 'unknown database type' );
		}

		geoip_close( $geo );
		return $res;
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

	public static function update_cache( $hook, $validate, $settings ) {
		$time = time();
		$num = ! empty( $settings['cache_hold'] ) ? $settings['cache_hold'] : 10;
		$exp = ! empty( $settings['cache_time'] ) ? $settings['cache_time'] : HOUR_IN_SECONDS;

		// unset expired elements
		if ( FALSE !== ( $cache = get_transient( IP_Geo_Block::CACHE_KEY ) ) ) {
			foreach ( $cache as $key => $val ) {
				if ( $time - $val['time'] > $exp )
					unset( $cache[ $key ] );
			}
		}

		// $validate['fail'] is set in auth_fail()
		if ( isset( $cache[ $ip = $validate['ip'] ] ) ) {
			$fail = $cache[ $ip ]['fail'] + (int)isset( $validate['fail'] );
			$call = $cache[ $ip ]['call'] + (int)empty( $validate['fail'] );
		} else { // if new cache then reset these values
			$call = 1;
			$fail = 0;
		}

		// update elements
		$cache[ $ip ] = array(
			'time' => $time,
			'hook' => $hook,
			'code' => $validate['code'],
			'auth' => $validate['auth'], // get_current_user_id() > 0
			'fail' => $validate['auth'] ? 0 : $fail,
			'call' => $settings['save_statistics'] ? $call : 0,
		);

		// sort by 'time'
		foreach ( $cache as $key => $val )
			$hash[ $key ] = $val['time'];
		array_multisort( $hash, SORT_DESC, $cache );

		// keep the maximum number of entries, except for hidden elements
		$time = 0;
		foreach ( $cache as $key => $val ) {
			if ( ! $val['auth'] && ++$time > $num ) {
				--$time;
				unset( $cache[ $key ] );
			}
		}

		set_transient( IP_Geo_Block::CACHE_KEY, $cache, $exp ); // @since 2.8
	}

	public static function delete_cache() {
		delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8
	}

	public static function get_cache( $ip ) {
		$cache = get_transient( IP_Geo_Block::CACHE_KEY );
		if ( $cache && isset( $cache[ $ip ] ) )
			return $cache[ $ip ];
		else
			return NULL;
	}

	public function get_location( $ip, $args = array() ) {
		if ( $cache = $this->get_cache( $ip ) )
			return array( 'countryCode' => $cache['code'] );
		else
			return array( 'errorMessage' => 'not in the cache' );
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
			'link' => '<a class="ip-geo-block-link" href="http://freegeoip.net/" title="freegeoip.net: FREE IP Geolocation Web Service" rel=noreferrer target=_blank>http://freegeoip.net/</a>&nbsp;(IPv4 / free)',
		),

		'ipinfo.io' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ipinfo.io/" title="ip address information including geolocation, hostname and network details" rel=noreferrer target=_blank>http://ipinfo.io/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'Telize' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://www.telize.com/" title="Telize - JSON IP and GeoIP REST API" rel=noreferrer target=_blank>http://www.telize.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'IP-Json' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ip-json.rhcloud.com/" title="Free IP Geolocation Web Service" rel=noreferrer target=_blank>http://ip-json.rhcloud.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'Xhanch' => array(
			'key'  => NULL,
			'type' => 'IPv4 / free',
			'link' => '<a class="ip-geo-block-link" href="http://xhanch.com/xhanch-api-ip-get-detail/" title="Xhanch API &#8211; IP Get Detail | Xhanch Studio" rel=noreferrer target=_blank>http://xhanch.com/</a>&nbsp;(IPv4 / free)',
		),

		'geoPlugin' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://www.geoplugin.com/geolocation/" title="geoPlugin to geolocate your visitors" target="_new">IP Geolocation</a> by <a href="http://www.geoplugin.com/" title="plugin to geo-targeting and unleash your site\' potential." rel=noreferrer target=_blank>geoPlugin</a>&nbsp;(IPv4, IPv6 / free, need an attribution link)',
		),

		'ip-api.com' => array(
			'key'  => FALSE,
			'type' => 'IPv4 / free for non-commercial use',
			'link' => '<a class="ip-geo-block-link" href="http://ip-api.com/" title="IP-API.com - Free Geolocation API" rel=noreferrer target=_blank>http://ip-api.com/</a>&nbsp;(IPv4, IPv6 / free for non-commercial use)',
		),

		'IPInfoDB' => array(
			'key'  => '',
			'type' => 'IPv4, IPv6 / free for registered user',
			'link' => '<a class="ip-geo-block-link" href="http://ipinfodb.com/" title="IPInfoDB | Free IP Address Geolocation Tools" rel=noreferrer target=_blank>http://ipinfodb.com/</a>&nbsp;(IPv4, IPv6 / free for registered user)',
		),
	);

	// Internal DB
	protected static $internals = array(
		'IP2Location' => array(
			'key'  => NULL,
			'type' => 'IPv4 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://www.ip2location.com/free/plugins" title="Free Plugins | IP2Location.com" rel=noreferrer target=_blank>http://www.ip2location.com/</a>&nbsp;(IPv4 / free, need an attribution link)',
		),

		'Maxmind' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free, need an attribution link',
			'link' => '<a class="ip-geo-block-link" href="http://dev.maxmind.com/geoip/legacy/geolite/" title="GeoLite Free Downloadable Databases &laquo; Maxmind Developer Site" rel=noreferrer target=_blank>http://www.maxmind.com</a>&nbsp;(IPv4, IPv6 / free, need an attribution link)',
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
	public static function get_providers( $key = 'key', $rand = FALSE, $cache = FALSE ) {
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