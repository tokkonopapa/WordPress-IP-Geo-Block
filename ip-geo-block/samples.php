<?php

if ( class_exists( 'IP_Geo_Block' ) ):

/**
 * substitute ip address
 *
 * @param array $validate
 * @param array $commentdata
 * @return array $validate
 */
function my_ip_address( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_action( 'ip-geo-block-addr', 'my_ip_address' );

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
add_action( 'ip-geo-block-addr', 'my_ip_proxy' );

/**
 * validate comment data
 *
 * @param array $validate
 * @param array $commentdata
 * @return array $validate
 * @see http://codex.wordpress.org/Plugin_API/Filter_Reference/preprocess_comment
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
add_action( 'ip-geo-block-comment', 'my_validate_comment' );

/**
 * validate login ip address
 *
 * @param array $validate
 * @param array $commentdata
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
add_action( 'ip-geo-block-login', 'my_validate_login' );

/**
 * validate login ip address
 *
 * @param array $validate
 * @param array $commentdata
 * @return array $validate
 */
function my_maxmind_path( $path ) {
	$upload = wp_upload_dir();
	return $upload['basedir'] . '/';
}
add_action( 'ip-geo-block-maxmind', 'my_maxmind_path' );

endif;