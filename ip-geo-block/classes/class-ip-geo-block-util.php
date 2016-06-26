<?php
/**
 * IP Geo Block - Utilities
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block_Util {

	/**
	 * Return local time of day.
	 *
	 */
	public static function localdate( $timestamp = FALSE, $fmt = NULL ) {
		static $offset = NULL;
		static $format = NULL;

		if ( NULL === $offset )
			$offset = wp_timezone_override_offset() * HOUR_IN_SECONDS;

		if ( NULL === $format )
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return date_i18n( $fmt ? $fmt : $format, $timestamp ? (int)$timestamp + $offset : FALSE );
	}

	/**
	 * Download zip/gz file, uncompress and save it to specified file
	 *
	 * @param string $url URL of remote file to be downloaded.
	 * @param array $args request headers.
	 * @param string $filename full path to the downloaded file.
	 * @param int $modified time of last modified on the remote server.
	 * @return array status message.
	 */
	public static function download_zip( $url, $args, $filename, $modified ) {
		if ( ! function_exists( 'download_url' ) )
			include_once( ABSPATH . 'wp-admin/includes/file.php' );

		// if the name of src file is changed, then update the dst
		if ( basename( $filename ) !== ( $base = pathinfo( $url, PATHINFO_FILENAME ) ) ) {
			$filename = dirname( $filename ) . '/' . $base;
		}

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
		$src = wp_remote_head( ( $url = esc_url_raw( $url ) ), $args );

		if ( is_wp_error( $src ) )
			return array(
				'code' => $src->get_error_code(),
				'message' => $src->get_error_message(),
			);

		$code = wp_remote_retrieve_response_code   ( $src );
		$mssg = wp_remote_retrieve_response_message( $src );
		$data = wp_remote_retrieve_header( $src, 'last-modified' );
		$modified = $data ? strtotime( $data ) : $modified;

		if ( 304 == $code )
			return array(
				'code' => $code,
				'message' => __( 'Your database file is up-to-date.', 'ip-geo-block' ),
				'filename' => $filename,
				'modified' => $modified,
			);

		elseif ( 200 != $code )
			return array(
				'code' => $code,
				'message' => $code.' '.$mssg,
			);

		// downloaded and unzip
		try {
			// download file
			$src = download_url( $url );

			if ( is_wp_error( $src ) )
				throw new Exception(
					$src->get_error_code() . ' ' . $src->get_error_message()
				);

			// get extension
			$args = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );

			// unzip file
			if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
				if ( FALSE === ( $gz = gzopen( $src, 'r' ) ) )
					throw new Exception(
						sprintf(
							__( 'Unable to read %s. Please check permission.', 'ip-geo-block' ),
							$src
						)
					);

				if ( FALSE === ( $fp = @fopen( $filename, 'wb' ) ) )
					throw new Exception(
						sprintf(
							__( 'Unable to write %s. Please check permission.', 'ip-geo-block' ),
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
				// https://codex.wordpress.org/Function_Reference/unzip_file
				WP_Filesystem();
				$ret = unzip_file( $src, dirname( $filename ) ); // @since 2.5

				if ( is_wp_error( $ret ) )
					throw new Exception(
						$ret->get_error_code() . ' ' . $ret->get_error_message()
					);
			}

			@unlink( $src );
		}

		// error handler
		catch ( Exception $e ) {
			if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
				! empty( $gz ) and gzclose( $gz );
				! empty( $fp ) and fclose ( $fp );
			}

			! is_wp_error( $src ) and @unlink( $src );

			return array(
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}

		return array(
			'code' => $code,
			'message' => sprintf(
				__( 'Last update: %s', 'ip-geo-block' ),
				self::localdate( $modified )
			),
			'filename' => $filename,
			'modified' => $modified,
		);
	}

}