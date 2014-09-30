<?php

if ( class_exists( 'IP_Geo_Block' ) ):

/**
 * replace ip address for test purpose
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_replace_ip( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-remote-ip', 'my_replace_ip' );


/**
 * retrieve ip address behind the proxy
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
 * set path to Maxmind database files (IPv4, IPv6)
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
 * set url to Maxmind database zip file
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
 * additional ip address validation on comment post
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
 * validate ip address on login form
 *
 * @param  array $validate ip address in 'ip'
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_ip_whitelist( $validate ) {
	$whitelist = array(
		'123.456.789.',
	);

	foreach ( $whitelist as $ip ) {
		if ( strpos( $ip, $validate['ip'] ) === 0 ) {
			$validate['result'] = 'passed';
			break;
		}
	}

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_ip_whitelist' );

function my_validate_login() {
	if ( ! is_admin() && ! is_user_logged_in() )
		IP_Geo_Block::validate_ip( 'login' ); // apply filter named 'ip-geo-block-login'
}
add_action( 'login_init', 'my_validate_login' );


/**
 * validate ip address on admin screen
 *
 */
function my_validate_admin() {
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
		IP_Geo_Block::validate_ip(); // no validation filter is applied
}
add_action( 'admin_init', 'my_validate_admin' );

endif;