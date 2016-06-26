<?php
/**
 * IP Geo Block - IP Address Geolocation API Class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_[IPV4 | IPV6 | BOTH],
		'url' => 'http://example.com/%API_KEY%/%API_FORMAT%/%API_OPTION%/%API_IP%';
		'api' => array(
			'%API_IP%'     => '', // should be set in build_url()
			'%API_KEY%'    => '', // should be set in __construct()
			'%API_FORMAT%' => '', // may be set in child class
			'%API_OPTION%' => '', // may be set in child class
		),
		'transform' => array(
			'errorMessage' => '',
			'countryCode'  => '',
			'countryName'  => '',
			'regionName'   => '',
			'cityName'     => '',
			'latitude'     => '',
			'longitude'    => '',
		)
	);*/

	/**
	 * Constructer & Destructer
	 *
	 */
	protected function __construct( $api_key = NULL ) {
		if ( is_string( $api_key ) )
			$this->template['api']['%API_KEY%'] = $api_key;
	}

	/**
	 * Build URL from template
	 *
	 */
	protected static function build_url( $ip, $template ) {
		$template['api']['%API_IP%'] = $ip;
		return str_replace(
			array_keys( $template['api'] ),
			array_values( $template['api'] ),
			$template['url']
		);
	}

	/**
	 * Fetch service provider to get geolocation information
	 *
	 */
	protected static function fetch_provider( $ip, $args, $template ) {

		// check supported type of IP address
		if ( ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && ( $template['type'] & IP_GEO_BLOCK_API_TYPE_IPV4 ) ) &&
		     ! ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) && ( $template['type'] & IP_GEO_BLOCK_API_TYPE_IPV6 ) ) ) {
			return FALSE;
		}

		// build query
		$tmp = self::build_url( $ip, $template );

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
			$tmp = explode( '/', $tmp,    2 );
			$tmp = explode( ';', $tmp[1], 2 );
			$tmp = trim( $tmp[0] );
		}

		switch ( $tmp ) {

		  // decode json
		  case 'json':
		  case 'html':  // ipinfo.io, Xhanch
		  case 'plain': // geoPlugin
			$data = json_decode( $res, TRUE ); // PHP 5 >= 5.2.0, PECL json >= 1.2.0
			if ( NULL === $data ) // ipinfo.io (get_country)
				$data[ $template['transform']['countryCode'] ] = trim( $res );
			break;

		  // decode xml
		  case 'xml':
			$tmp = '/\<(.+?)\>(?:\<\!\[CDATA\[)?(.*?)(?:\]\]\>)?\<\/\\1\>/i';
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
		foreach ( $template['transform'] as $key => $val ) {
			if ( ! empty( $val ) && ! empty( $data[ $val ] ) )
				$res[ $key ] = is_string( $data[ $val ] ) ? 
					esc_html( $data[ $val ] ) : $data[ $val ];
		}

		// if country code is '-' or 'UNDEFINED' then error.
		if ( isset( $res['countryCode'] ) && is_string( $res['countryCode'] ) )
			$res['countryCode'] = preg_match( '/^[A-Z]{2}/', $res['countryCode'], $matches ) ? $matches[0] : NULL;

		return $res;
	}

	/**
	 * Get geolocation information from service provider
	 *
	 */
	public function get_location( $ip, $args = array() ) {
		return self::fetch_provider( $ip, $args, $this->template );
	}

	/**
	 * Get only country code
	 *
	 * Override this method if a provider supports this feature for quick response.
	 */
	public function get_country( $ip, $args = array() ) {
		$res = $this->get_location( $ip, $args );
		return FALSE === $res ? FALSE : ( empty( $res['countryCode'] ) ? NULL : $res['countryCode'] );
	}

	/**
	 * Convert provider name to class name
	 *
	 */
	public static function get_class_name( $provider ) {
		$provider = 'IP_Geo_Block_API_' . preg_replace( '/[\W]/', '', $provider );
		return class_exists( $provider ) ? $provider : NULL;
	}

	/**
	 * Get option key
	 *
	 */
	public static function get_api_key( $provider, $options ) {
		return empty( $options['providers'][ $provider ] ) ? NULL : $options['providers'][ $provider ];
	}

	/**
	 * Instance of inherited object
	 *
	 */
	private static $instance = array();

	public static function get_instance( $provider, $options ) {
		if ( $name = self::get_class_name( $provider ) ) {
			if ( empty( self::$instance[ $name ] ) )
				return self::$instance[ $name ] = new $name( self::get_api_key( $provider, $options ) );
			else
				return self::$instance[ $name ];
		}

		return NULL;
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
 * Output type : json, jsonp, xml, csv
 */
class IP_Geo_Block_API_freegeoipnet extends IP_Geo_Block_API {
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://freegeoip.net/%API_FORMAT%/%API_IP%',
		'api' => array(
			'%API_FORMAT%' => 'json',
		),
		'transform' => array(
			'countryCode' => 'country_code',
			'countryName' => 'country_name',
			'regionName'  => 'region_name',
			'cityName'    => 'city',
			'latitude'    => 'latitude',
			'longitude'   => 'longitude',
		)
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://ipinfo.io/%API_IP%/%API_FORMAT%%API_OPTION%',
		'api' => array(
			'%API_FORMAT%' => 'json',
			'%API_OPTION%' => '',
		),
		'transform' => array(
			'countryCode' => 'country',
			'countryName' => 'country',
			'regionName'  => 'region',
			'cityName'    => 'city',
			'latitude'    => 'loc',
			'longitude'   => 'loc',
		)
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
		$this->template['api']['%API_FORMAT%'] = '';
		$this->template['api']['%API_OPTION%'] = 'country';
		return parent::get_country( $ip, $args );
	}
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://ip-json.rhcloud.com/%API_FORMAT%/%API_IP%',
		'api' => array(
			'%API_FORMAT%' => 'json',
		),
		'transform' => array(
			'errorMessage' => 'error',
			'countryCode'  => 'country_code',
			'countryName'  => 'country_name',
			'regionName'   => 'region_name',
			'cityName'     => 'city',
			'latitude'     => 'latitude',
			'longitude'    => 'longitude',
		)
	);

	public function get_location( $ip, $args = array() ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
			$this->template['api']['%API_FORMAT%'] = 'v6';
		return parent::get_location( $ip, $args );
	}
}

/**
 * Class for Nekudo
 *
 * URL         : http://geoip.nekudo.com/
 * Term of use : https://nekudo.com/blog/new-project-shiny-geoip
 * Licence fee : free to use the API
 * Rate limit  : none
 * Sample URL  : http://geoip.nekudo.com/api/2a00:1210:fffe:200::1
 * Input type  : IP address (IPv4, IPv6)
 * Output type : json
 */
class IP_Geo_Block_API_Nekudo extends IP_Geo_Block_API {
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://geoip.nekudo.com/api/%API_IP%',
		'api' => array(),
		'transform' => array(
			'countryCode' => 'country',
			'countryName' => 'country',
			'cityName'    => 'city',
			'latitude'    => 'location',
			'longitude'   => 'location',
		)
	);

	public function get_location( $ip, $args = array() ) {
		$res = parent::get_location( $ip, $args );
		if ( isset( $res['countryName'] ) && is_array( $res['countryName'] ) ) {
			$res['countryCode'] = esc_html( $res['countryCode']['code'] );
			$res['countryName'] = esc_html( $res['countryName']['name'] );
			$res['latitude'   ] = esc_html( $res['latitude'   ]['latitude' ] );
			$res['longitude'  ] = esc_html( $res['longitude'  ]['longitude'] );
			return $res;
		} else {
			return array( 'errorMessage' => 'Not Found' ); // 404
		}
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_IPV4,
		'url' => 'http://api.xhanch.com/ip-get-detail.php?ip=%API_IP%&m=%API_FORMAT%',
		'api' => array(
			'%API_FORMAT%' => 'json',
		),
		'transform' => array(
			'countryCode' => 'country_code',
			'countryName' => 'country_name',
			'regionName'  => 'region',
			'cityName'    => 'city',
			'latitude'    => 'latitude',
			'longitude'   => 'longitude',
		)
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://www.geoplugin.net/%API_FORMAT%.gp?ip=%API_IP%',
		'api' => array(
			'%API_FORMAT%' => 'json',
		),
		'transform' => array(
			'countryCode' => 'geoplugin_countryCode',
			'countryName' => 'geoplugin_countryName',
			'regionName'  => 'geoplugin_region',
			'cityName'    => 'geoplugin_city',
			'latitude'    => 'geoplugin_latitude',
			'longitude'   => 'geoplugin_longitude',
		)
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://ip-api.com/%API_FORMAT%/%API_IP%',
		'api' => array(
			'%API_FORMAT%' => 'json',
		),
		'transform' => array(
			'errorMessage' => 'error',
			'countryCode'  => 'countryCode',
			'countryName'  => 'country',
			'regionName'   => 'regionName',
			'cityName'     => 'city',
			'latitude'     => 'lat',
			'longitude'    => 'lon',
		)
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
	protected $template = array(
		'type' => IP_GEO_BLOCK_API_TYPE_BOTH,
		'url' => 'http://api.ipinfodb.com/v3/%API_OPTION%/?key=%API_KEY%&format=%API_FORMAT%&ip=%API_IP%',
		'api' => array(
			'%API_FORMAT%' => 'xml',
			'%API_OPTION%' => 'ip-city',
		),
		'transform' => array(
			'countryCode' => 'countryCode',
			'countryName' => 'countryName',
			'regionName'  => 'regionName',
			'cityName'    => 'cityName',
			'latitude'    => 'latitude',
			'longitude'   => 'longitude',
		)
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
 * Class for Cache
 *
 * URL         : http://codex.wordpress.org/Transients_API
 * Input type  : IP address (IPv4, IPv6)
 * Output type : array
 */
class IP_Geo_Block_API_Cache extends IP_Geo_Block_API {

	public static function update_cache( $hook, $validate, $settings ) {
		$time = $_SERVER['REQUEST_TIME']; // time();
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
			'host' => isset( $validate['host'] ) ? $validate['host'] : NULL,
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
		return $cache[ $ip ];
	}

	public static function clear_cache() {
		delete_transient( IP_Geo_Block::CACHE_KEY ); // @since 2.8
	}

	public static function get_cache_all() {
		return get_transient( IP_Geo_Block::CACHE_KEY );
	}

	public static function get_cache( $ip ) {
		$cache = get_transient( IP_Geo_Block::CACHE_KEY );
		return $cache && isset( $cache[ $ip ] ) ? $cache[ $ip ] : NULL;
	}

	public function get_location( $ip, $args = array() ) {
		if ( $cache = self::get_cache( $ip ) )
			return array( 'countryCode' => $cache['code'] );
		else
			return array( 'errorMessage' => 'not in the cache' );
	}

	public function get_country( $ip, $args = array() ) {
		return ( $cache = self::get_cache( $ip ) ) ? $cache['code'] : NULL;
	}
}

/**
 * Provider support class
 *
 */
class IP_Geo_Block_Provider {

	protected static $providers = array(

		'freegeoip.net' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://freegeoip.net/" title="freegeoip.net: FREE IP Geolocation Web Service" rel=noreferrer target=_blank>http://freegeoip.net/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'ipinfo.io' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ipinfo.io/" title="ip address information including geolocation, hostname and network details" rel=noreferrer target=_blank>http://ipinfo.io/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'IP-Json' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://ip-json.rhcloud.com/" title="Free IP Geolocation Web Service" rel=noreferrer target=_blank>http://ip-json.rhcloud.com/</a>&nbsp;(IPv4, IPv6 / free)',
		),

		'Nekudo' => array(
			'key'  => NULL,
			'type' => 'IPv4, IPv6 / free',
			'link' => '<a class="ip-geo-block-link" href="http://geoip.nekudo.com/" title="geoip.nekudo.com | Free IP to geolocation API" rel=noreferrer target=_blank>http://geoip.nekudo.com/</a>&nbsp;(IPv4, IPv6 / free)',
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
			'type' => 'IPv4, IPv6 / free for non-commercial use',
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
		'Cache' => array(
			'key' => NULL,
			'type' => 'IPv4, IPv6',
			'link' => NULL,
		),
	);

	/**
	 * Register and get addon provider class information
	 *
	 */
	public static function register_addon( $api ) {
		self::$internals += $api;
	}

	public static function get_addons() {
		$apis = array();

		foreach ( self::$internals as $key => $val ) {
			if ( 'Cache' !== $key )
				$apis[] = $key;
		}

		return $apis;
	}

	/**
	 * Returns the pairs of provider name and API key
	 *
	 */
	public static function get_providers( $key = 'key', $rand = FALSE, $cache = FALSE ) {
		// add internal DB
		$list = array();
		foreach ( self::$internals as $provider => $tmp ) {
			if ( 'Cache' !== $provider || $cache )
				$list[ $provider ] = $tmp[ $key ];
		}

		$tmp = array_keys( self::$providers );

		// randomize
		if ( $rand )
			shuffle( $tmp );

		foreach ( $tmp as $name )
			$list[ $name ] = self::$providers[ $name ][ $key ];

		return $list;
	}

	/**
	 * Returns providers name list which are checked in settings
	 *
	 */
	public static function get_valid_providers( $settings, $rand = TRUE, $cache = TRUE ) {
		$list = array();
		$geo = self::get_providers( 'key', $rand, $cache );

		foreach ( $geo as $provider => $key ) {
			if ( ! empty( $settings[ $provider ] ) || (
			     ! isset( $settings[ $provider ] ) && NULL === $key ) ) {
				$list[] = $provider;
			}
		}

		return $list;
	}

	/**
	 * Check status of provider selection
	 *
	 */
	public static function diag_providers( $settings = NULL ) {
		if ( ! $settings ) {
			$settings = IP_Geo_Block::get_option( 'settings' );
			$settings = $settings['providers'];
		}

		$field = 0;
		foreach ( self::get_providers( 'key' ) as $key => $val ) {
			if ( ( NULL   === $val   && ! isset( $settings[ $key ] ) ) ||
			     ( FALSE  === $val   && ! empty( $settings[ $key ] ) ) ||
			     ( is_string( $val ) && ! empty( $settings[ $key ] ) ) ) {
				$field++;
			}
		}

		if ( 0 === $field )
			return __(
				'You need to select at least one IP geolocation service. Otherwise <strong>you\'ll be blocked</strong> after the cache expires.',
				'ip-geo-block'
			);

		return NULL;
	}

}

/**
 * Load additional plugins
 *
 */
if ( class_exists( 'IP_Geo_Block' ) ) {

	// Get absolute path to the geo-location API
	$dir = IP_Geo_Block::get_option( 'settings' );
	$dir = trailingslashit(
		apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-api-dir',
			dirname( $dir['api_dir'] )
		)
	) . IP_Geo_Block::GEOAPI_NAME;

	// If not exists then use bundled API
	if ( ! is_dir( $dir ) )
		$dir = IP_GEO_BLOCK_PATH . IP_Geo_Block::GEOAPI_NAME;

	// Scan API directory
	$dir = trailingslashit( $dir );
	$plugins = is_dir( $dir ) ? scandir( $dir, 1 ) : FALSE; // SCANDIR_SORT_DESCENDING @since 5.4.0

	// Load addons by heigher priority order
	if ( FALSE !== $plugins ) {
		$exclude = array( '.', '..', 'index.php' );
		foreach ( $plugins as $plugin ) {
			if ( ! in_array( $plugin, $exclude, TRUE ) && is_dir( $dir.$plugin ) ) {
				@include_once( $dir.$plugin.'/class-'.$plugin.'.php' );
			}
		}
	}

}