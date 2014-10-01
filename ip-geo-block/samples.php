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
 * Example1: usage of 'ip-geo-block-remote-ip'
 * Use case: replace ip address for test purpose
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_replace_ip( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-remote-ip', 'my_replace_ip' );


/**
 * Example2: usage of 'ip-geo-block-remote-ip'
 * Use case: retrieve ip address behind the proxy
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_retrieve_ip( $ip ) {
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$ip = trim( $ip[0] );
	}

	return $ip;
}
add_filter( 'ip-geo-block-remote-ip', 'my_retrieve_ip' );


/**
 * Example3: usage of 'ip-geo-block-maxmind-path'
 * Use case: change the path to Maxmind database files to writable directory
 *
 * @param  string $path original path to database files
 * @return string $path replaced path to database files
 */
function my_maxmind_path( $path ) {
	$upload = wp_upload_dir();
	return $upload['basedir'];
}
add_filter( 'ip-geo-block-maxmind-path', 'my_maxmind_path' );


/**
 * Example4: usage of 'ip-geo-block-maxmind-zip-ipv[46]'
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
 * Example5: usage of 'ip-geo-block-comment'
 * Use case: exclude specific ip addresses in the blacklist on comment post
 *
 * @param  array $validate ip address in 'ip'
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_ip_blacklist( $validate ) {
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
add_filter( 'ip-geo-block-comment', 'my_ip_blacklist' );


/**
 * Example6: validate ip address at login process to exclude Brute-force attack
 * Use case: allow login only from specific ip addresses in the whitelist
 *
 * @param  array $validate ip address in 'ip'
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_ip_whitelist( $validate ) {
	$whitelist = array(
		'123.456.789.',
	);

	$validate['result'] = 'blocked';

	foreach ( $whitelist as $ip ) {
		if ( strpos( $ip, $validate['ip'] ) === 0 ) {
			$validate['result'] = 'passed';
			break;
		}
	}

	return $validate;
}
add_filter( 'ip-geo-block-my-login', 'my_ip_whitelist' ); // custom filter hook

function my_validate_login() {
	if ( ! is_admin() && ! is_user_logged_in() )
		IP_Geo_Block::validate_ip( 'my-login' ); // apply custom filter
}
add_action( 'login_init', 'my_validate_login', 1 );


/**
 * Example7: validate ip address at admin process to exclude intruders
 * Use case: limit the ip addresses that can access the admin screen
 *
 */
function my_validate_admin() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
		IP_Geo_Block::validate_ip(); // validate country if no custom filter
}
add_action( 'admin_init', 'my_validate_admin', 1 );

endif;