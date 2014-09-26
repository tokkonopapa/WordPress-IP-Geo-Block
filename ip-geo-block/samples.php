<?php

if ( class_exists( 'IP_Geo_Block' ) ):

/**
 * substitute ip address
 *
 * @param string $ip
 * @return string $ip
 */
function my_ip_address( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-addr', 'my_ip_address' );

/**
 * REMOTE_ADDR              : global IP or local IP
 * HTTP_X_FORWARDED_FOR     : global IP
 * HTTP_X_REAL_IP           : global IP
 * HTTP_CLIENT_IP           : possible forgery
 * HTTP_X_REAL_FORWARDED_FOR: Possible forgery
 * @link http://d.hatena.ne.jp/Kenji_s/20111227/1324977925
 * @link http://www.nurs.or.jp/~sug/homep/proxy/proxy7.htm
 */
function my_ip_proxy( $ip ) {
	if ( empty( $_SERVER['HTTP_CLIENT_IP'] ) &&
		! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$proxy = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$proxy = trim( $proxy[0] );
	}
	else if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		$proxy = $_SERVER['HTTP_X_REAL_IP'];
	}
	else if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$proxy = $_SERVER['HTTP_CLIENT_IP'];
	}

	if ( isset( $proxy ) && (
		filter_var( $proxy, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
		filter_var( $proxy, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) ) {
		return $proxy;
	} else {
		return $_SERVER['REMOTE_ADDR'];
	}
}
add_filter( 'ip-geo-block-addr', 'my_ip_proxy' );

/**
 * validate comment data
 *
 * @param array $validate
 * @return array $validate
 */
function my_validate_comment( $validate ) {
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
add_filter( 'ip-geo-block-comment', 'my_validate_comment' );

/**
 * validate login ip address
 *
 * @param array $validate
 * @return array $validate
 */
function my_validate_login( $validate ) {
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
add_filter( 'ip-geo-block-login', 'my_validate_login' );

/**
 * set path to Maxmind database files
 *
 * @param string $path
 * @return string $path
 */
function my_maxmind_path( $path ) {
	$upload = wp_upload_dir();
	return $upload['basedir'];
}
add_filter( 'ip-geo-block-maxmind-path', 'my_maxmind_path' );

/**
 * set url to Maxmind database zip file
 *
 * @param string $url
 * @return string $url
 */
function my_maxmind_ipv4( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
}
function my_maxmind_ipv6( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/GeoLiteCityv6.dat.gz';
}
add_filter( 'ip-geo-block-maxmind-zip-ipv4', 'my_maxmind_ipv4' );
add_filter( 'ip-geo-block-maxmind-zip-ipv6', 'my_maxmind_ipv6' );

endif;