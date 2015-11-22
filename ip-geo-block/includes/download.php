<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );

if ( ! function_exists( 'download_url' ) )
	require_once( ABSPATH . 'wp-admin/includes/file.php' );

/**
 * Download zip file, uncompress and save it to specified file
 *
 * @param string $url URL of remote file to be downloaded.
 * @param array $args request headers.
 * @param string $filename full path to the downloaded file.
 * @param int $modified time of last modified on the remote server.
 * @return array status message.
 */
function ip_geo_block_download_zip( $url, $args, $filename, $modified ) {
	// check file
	if ( ! file_exists( $filename ) )
		$modified = 0;

	// set 'If-Modified-Since' request header
	$args += array(
		'headers'  => array(
			'If-Modified-Since' => gmdate( DATE_RFC1123, (int)$modified ),
		),
	);

	// fetch file and get response code & message
	$res = wp_remote_head( ( $url = esc_url_raw( $url ) ), $args );

	if ( is_wp_error( $res ) )
		return array(
			'code' => $res->get_error_code(),
			'message' => $res->get_error_message(),
		);

	$code = wp_remote_retrieve_response_code   ( $res );
	$mssg = wp_remote_retrieve_response_message( $res );
	$data = wp_remote_retrieve_header( $res, 'last-modified' );
	$modified = $data ? strtotime( $data ) : $modified;

	if ( 304 == $code )
		return array(
			'code' => $code,
			'message' => __( 'Your database file is up-to-date.', IP_Geo_Block::TEXT_DOMAIN ),
			'filename' => $filename,
			'modified' => $modified,
		);

	elseif ( 200 != $code )
		return array(
			'code' => $code,
			'message' => "$code $mssg",
		);

	// downloaded and unzip
	try {
		// download file
		$res = download_url( $url );

		if ( is_wp_error( $res ) )
			throw new Exception(
				$res->get_error_code() . ' ' . $res->get_error_message()
			);

		// get extension
		$args = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );

		// unzip file
		if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
			if ( FALSE === ( $gz = gzopen( $res, 'r' ) ) )
				throw new Exception(
					sprintf(
						__( 'Cannot open %s to read. Please check permission.', IP_Geo_Block::TEXT_DOMAIN ),
						$res
					)
				);

			if ( FALSE === ( $fp = fopen( $filename, 'wb' ) ) )
				throw new Exception(
					sprintf(
						__( 'Cannot open %s to write. Please check permission.', IP_Geo_Block::TEXT_DOMAIN ),
						$filename
					)
				);

			// same block size in wp-includes/class-http.php
			while ( $data = gzread( $gz, 4096 ) )
				fwrite( $fp, $data, strlen( $data ) );

			gzclose( $gz );
			fclose ( $fp );
		}

		elseif ( 'zip' === $args && class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive;
			if ( TRUE === $zip->open( $res ) ) {
				$zip->extractTo( dirname( $filename ) );
				$zip->close();
			}
			else {
				throw new Exception(
					sprintf(
						__( 'Failed to open %s. Please check permission.', IP_Geo_Block::TEXT_DOMAIN ),
						$res
					)
				);
			}
		}

		@unlink( $res );
	}

	// error handler
	catch ( Exception $e ) {
		if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
			! empty( $gz ) && gzclose( $gz );
			! empty( $fp ) && fclose ( $fp );
		}

		! is_wp_error( $res ) && @unlink( $res );

		return array(
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
		);
	}

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