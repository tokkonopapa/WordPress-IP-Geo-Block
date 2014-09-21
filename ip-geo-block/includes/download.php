<?php
include_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );

/**
 * Default path to database file
 */
define( 'IP_GEO_BLOCK_MAX_ZIP_SIZE', 1024 * 1024 * 2 ); // 2MB
define( 'IP_GEO_BLOCK_DB_PATH', IP_GEO_BLOCK_PATH . 'database/' );

/**
 * URL of Maxmind GeoLite database
 */
///*
define( 'IP_GEO_BLOCK_MAXMIND_IPV4_ZIP', 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_ZIP', 'http://geolite.maxmind.com/download/geoip/database/GeoIPv6.dat.gz' );
//*/

/**
 * For test purpose
 */
/*
define( 'IP_GEO_BLOCK_MAXMIND_IPV4_ZIP', 'http://localhost:8888/test/maxmind/db/GeoIP.dat.gz' );
define( 'IP_GEO_BLOCK_MAXMIND_IPV6_ZIP', 'http://localhost:8888/test/maxmind/db/GeoIPv6.dat.gz' );
//*/

/**
 * Check file and update last-modified
 *
 * @param  string $url URL of zip file of database.
 * @param  string $dir target path.
 * @param  string $filename pull path of uncompressed file.
 * @return array  set of filename and modified date of database.
 */
function ip_geo_block_revise_path( $url, $dir, $filename, $modified ) {
	if ( ! $filename || ! is_readable( $filename ) ) {
		$filename = trailingslashit( $dir ) . basename( $url, '.gz' );
		$modified = 0;
	}

	return array( $filename, $modified );
}

/**
 * Download zip file, uncompress and save it to specified file
 *
 * @param string $url URL of remote file to be downloaded.
 * @param array $args request headers.
 * @param string $filename path to downloaded file.
 * @param int $modified time of last modified on the remote server.
 * @return array status message.
 */
function ip_geo_block_download_zip( $url, $args, $filename, $modified ) {
	// set 'If-Modified-Since' request header
	$args['headers']['If-Modified-Since'] = date( DATE_RFC1123, $modified );

	// fetch file and get response code & message
	$res  = wp_remote_get( esc_url_raw( $url ), $args );
	$code = wp_remote_retrieve_response_code   ( $res );
	$msg  = wp_remote_retrieve_response_message( $res );
	$data = wp_remote_retrieve_header( $res, 'last-modified' );
	$modified = $data ? strtotime( $data ) : $modified;

	if ( is_wp_error( $res ) )
		return array(
			'code' => 999,
			'message' => $res->get_error_message(),
		);

	else if ( 304 == $code )
		return array(
			'code' => $code,
			'message' => "$code $msg",
			'filename' => $filename,
			'modified' => $modified,
		);

	else if ( 200 != $code )
		return array(
			'code' => $code,
			'message' => "$code $msg",
		);

	// unzip data
	try {
		if ( function_exists( 'gzdecode' ) )
			$data = gzdecode( wp_remote_retrieve_body( $res ) ); // PHP >= 5.4
		else {
			$fp = fopen( "${filename}.gz", "wb" );
			fwrite( $fp, wp_remote_retrieve_body( $res ) );
			fclose( $fp );

			$fp = gzopen( "${filename}.gz", "r" ); // PHP >= 4
			$data = gzread( $fp, IP_GEO_BLOCK_MAX_ZIP_SIZE );
			gzclose( $fp );

			unlink( "${filename}.gz" );
		}
	} catch ( Exception $e ) {
		return array(
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
		);
	}

	if ( FALSE === $data )
		return array(
			'code' => 999,
			'message' => __( 'Cannot decode zip.', IP_Geo_Block::TEXT_DOMAIN ),
		);

	$fp = @fopen( $filename, 'wb' );

	if ( FALSE === $fp )
		return array(
			'code' => 999,
			'message' => __( 'Cannot open file.', IP_Geo_Block::TEXT_DOMAIN ),
		);

	$data = fwrite( $fp, $data );
	fclose( $fp );

	if ( FALSE === $data )
		return array(
			'code' => 999,
			'message' => __( 'Cannot write to file.', IP_Geo_Block::TEXT_DOMAIN ),
		);

	return array(
		'code' => $code,
		'message' => sprintf(
			__( 'Last update: %s', IP_Geo_Block::TEXT_DOMAIN ),
			ip_geo_block_localdate( $modified )
		),
		'filename' => $filename,
		'modified' => $modified,
	);
}

/**
 * Download Maxmind database files for IPv4 and IPv6
 *
 * @param string $url URL of remote file to be downloaded.
 * @param array $args request headers.
 * @param string $filename path to downloaded file.
 * @param int $modified time of last modified on the remote server.
 * @return array status messages.
 */
function ip_geo_block_download( &$db, $dir, $args ) {
	// check path to the file
	list( $db['ipv4_path'], $db['ipv4_last'] ) = ip_geo_block_revise_path(
		IP_GEO_BLOCK_MAXMIND_IPV4_ZIP, $dir, $db['ipv4_path'], $db['ipv4_last'] );

	// IPv4
	$ipv4 = ip_geo_block_download_zip(
		IP_GEO_BLOCK_MAXMIND_IPV4_ZIP,
		$args,
		$db['ipv4_path'],
		$db['ipv4_last']
	);

	if ( ! empty( $ipv4['filename'] ) )
		$db['ipv4_path'] = $ipv4['filename'];

	if ( ! empty( $ipv4['modified'] ) )
		$db['ipv4_last'] = $ipv4['modified'];

	// check path to the file
	list( $db['ipv6_path'], $db['ipv6_last'] ) = ip_geo_block_revise_path(
		IP_GEO_BLOCK_MAXMIND_IPV6_ZIP, $dir, $db['ipv6_path'], $db['ipv6_last'] );

	// IPv6
	$ipv6 = ip_geo_block_download_zip(
		IP_GEO_BLOCK_MAXMIND_IPV6_ZIP,
		$args,
		$db['ipv6_path'],
		$db['ipv6_last']
	);

	if ( ! empty( $ipv6['filename'] ) )
		$db['ipv6_path'] = $ipv6['filename'];

	if ( ! empty( $ipv6['modified'] ) )
		$db['ipv6_last'] = $ipv6['modified'];

	return array( 'ipv4' => $ipv4, 'ipv6' => $ipv6 );
}