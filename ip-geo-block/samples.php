<?php
/**
 * Code samples/snippets for functions.php
 * to extend functionality of IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2014 tokkonopapa
 */

if ( class_exists( 'IP_Geo_Block' ) ):

/**
 * Example 1: usage of 'ip-geo-block-ip-addr'
 * Use case: replace ip address for test purpose
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_replace_ip( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );


/**
 * Example 2: usage of 'ip-geo-block-ip-addr'
 * Use case: retrieve ip address behind the proxy
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_retrieve_ip( $ip ) {
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$tmp = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$tmp = trim( $tmp[0] );
		if ( filter_var( $tmp, FILTER_VALIDATE_IP ) ) {
			$ip = $tmp;
		}
	}

	return $ip;
}
add_filter( 'ip-geo-block-ip-addr', 'my_retrieve_ip' );


/**
 * Example 3: usage of 'ip-geo-block-headers'
 * Use case: change the user agent strings when accessing geolocation API
 *
 * Notice: Be careful about HTTP header injection.
 * @param  string $args http request headers for `wp_remote_get()`
 * @return string $args http request headers for `wp_remote_get()`
 */
function my_user_agent( $args ) {
    $args['user-agent'] = 'my user agent strings';
    return $args;
}
add_filter( 'ip-geo-block-headers', 'my_user_agent' );


/**
 * Example 4: usage of 'ip-geo-block-maxmind-dir'
 * Use case: change the path of Maxmind database files to writable directory
 *
 * @param  string $dir original directory of database files
 * @return string $dir replaced directory of database files
 */
function my_maxmind_dir( $dir ) {
	$upload = wp_upload_dir();
	return $upload['basedir'];
}
add_filter( 'ip-geo-block-maxmind-dir', 'my_maxmind_dir' );


/**
 * Example 5: usage of 'ip-geo-block-maxmind-zip-ipv[46]'
 * Use case: replace Maxmind database files to city edition
 *
 * @param  string $url original url to zip file
 * @return string $url replaced url to zip file
 */
function my_maxmind_ipv4( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
}
function my_maxmind_ipv6( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/GeoLiteCityv6.dat.gz';
}
add_filter( 'ip-geo-block-maxmind-zip-ipv4', 'my_maxmind_ipv4' );
add_filter( 'ip-geo-block-maxmind-zip-ipv6', 'my_maxmind_ipv6' );


/**
 * Example 6: usage of 'ip-geo-block-ip2location-path'
 * Use case: change the path to IP2Location database files
 *
 * @param  string $path original path to database files
 * @return string $path replaced path to database files
 */
function my_ip2location_path( $path ) {
	return WP_CONTENT_DIR . '/ip2location/IP2LOCATION-LITE-DB1.IPV6.BIN';
}
add_filter( 'ip-geo-block-ip2location-path', 'my_ip2location_path' );


/**
 * Example 7: usage of 'ip-geo-block-comment'
 * Use case: block comment post from specific IP addresses in the blacklist
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_blacklist( $validate ) {
	$blacklist = array(
		'123.456.789.',
	);

	foreach ( $blacklist as $ip ) {
		if ( strpos( $ip, $validate['ip'] ) === 0 ) {
			$validate['result'] = 'blocked';
			break;
		}
	}

	return $validate;
}
add_filter( 'ip-geo-block-comment', 'my_blacklist' );


/**
 * Example 8: usage of 'ip-geo-block-login' and 'ip-geo-block-xmlrpc'
 * Use case: allow authentication only from specific countries in the whitelist
 * (validate ip address to exclude Brute-force attack on login process)
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_whitelist( $validate ) {
	$whitelist = array(
		'jp',
	);

	$validate['result'] = 'blocked';

	foreach ( $whitelist as $country ) {
		if ( strtoupper( $country ) === $validate['code'] ) {
			$validate['result'] = 'passed';
			break;
		}
	}

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
add_filter( 'ip-geo-block-xmlrpc', 'my_whitelist' );


/**
 * Example 9: validate ip address before authrization in admin area
 * Use case: When an emergency situation of your self being locked out
 *
 */
function my_emergency( $validate ) {
	// password is required even in this case
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_emergency' );
add_filter( 'ip-geo-block-admin', 'my_emergency' );


/**
 * Example 10: backup validation logs to text files
 * Use case: keep verification logs selectively to text files
 *
 * @param  string $hook 'comment', 'login', 'admin' or 'xmlrpc'
 * @param  string $dir default path where text files should be saved
 * @return string should be absolute path out of the public_html.
 */
function my_backup_dir( $hook, $dir ) {
	if ( 'login' === $hook )
		return '/absolute/path/to/';
	else
		return null;
}
add_filter( 'ip-geo-block-backup-dir', 'my_backup_dir', 10, 2 );


/**
 * Example 11: usage of 'IP_Geo_Block::get_geolocation()'
 * Use case: get geolocation of ip address with latitude and longitude
 *
 * @param  string $ip ip address
 * @param  array $providers list of providers
 * @return array $geolocation array of 'countryCode', 'latitude', 'longitude'
 */
function my_geolocation( $ip ) {
	$providers = array( 'ipinfo.io', 'Telize', 'IP-Json' );
	$geolocation = IP_Geo_Block::get_geolocation( $ip, $providers );

	if ( empty( $geolocation['errorMessage'] ) )
		echo va_dump( $geolocation );
	else
		echo $geolocation['errorMessage'];
}

endif;