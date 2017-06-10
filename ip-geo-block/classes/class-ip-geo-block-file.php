<?php
/**
 * IP Geo Block - Filesystem
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @link      https://codex.wordpress.org/Filesystem_API
 * @copyright 2017 tokkonopapa
 */

class IP_Geo_Block_FS {

	/**
	 * Private variables of this class.
	 *
	 */
	private static $method;
	private static $instance = NULL;

	/**
	 * Create an instance of this class.
	 *
	 */
	private static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Initialize and return instance of this class.
	 *
	 */
	public static function init( $msg = NULL ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		if ( ! empty( $wp_filesystem ) ) // assigned in WP_Filesystem()
			return self::get_instance();

		if ( 'direct' === ( self::$method = get_filesystem_method() ) ) { // @since 2.5.0
			// request_filesystem_credentials() can be run without any issues and don't need to worry about passing in a URL
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', FALSE, FALSE, NULL ); // @since 2.5.0

			// initialize the API @since 2.5.0
			if ( ! WP_Filesystem( $creds ) )
				return FALSE; // any problems and we exit

			return self::get_instance();
		}

		elseif ( class_exists( 'IP_Geo_Block_Admin' ) ) {
			IP_Geo_Block_Admin::add_admin_notice(
				'error',
				sprintf( __( '%s: This plugin does not support FTP or SSH based file operations.', 'ip-geo-block' ), $msg ? $msg : __CLASS__ )
			);
		}

		return FALSE;
	}

	// Add slash at the end of string.
	private function slashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}

	// Get absolute path.
	private function absolute_path( $file ) {
		global $wp_filesystem;
		$path = str_replace( ABSPATH, $wp_filesystem->abspath(), dirname( $file ) );
		return $this->slashit( $path ) . basename( $file );
	}

	/**
	 * Delete a file.
	 *
	 * @param  string $file
	 * @param  bool   $recursive
	 * @param  string $type
	 * @return bool
	 */
	public function delete( $file, $recursive = FALSE, $type = FALSE ) {
		if ( 'direct' !== self::$method ) {
			$file = $this->absolute_path( $file );
		}

		global $wp_filesystem;
		return $wp_filesystem->delete( $file, $recursive, $type );
	}

	/**
	 * Copy a file.
	 *
	 * @param  string $src
	 * @param  string $dst
	 * @param  bool   $overwrite
	 * @param  int    $mode
	 * @return bool
	 */
	public function copy( $src, $dst, $overwrite = FALSE, $mode = FALSE ) {
		if ( 'direct' !== self::$method ) {
			$src = $this->absolute_path( $src );
			$dst = $this->absolute_path( $dst );
		}

		global $wp_filesystem;
		return $wp_filesystem->copy( $src, $dst, $overwrite, $mode );
	}

	/**
	 * Write a string to a file with an exclusive lock.
	 *
	 * @param  string $file     Remote path to the file where to write the data.
	 * @param  string $contents The data to write.
	 * @param  int    $mode     The file permissions as octal number, usually 0644. Default false.
	 * @return bool
	 */
	public function put_contents( $file, $contents, $mode = FALSE ) {
		if ( 'direct' !== self::$method ) {
			$file = $this->absolute_path( $file );
		}

		if ( ! ( $fp = @fopen( $file, 'wb' ) ) ) {
			return FALSE;
		}

		if ( ! flock( $fp, LOCK_EX ) ) {
			fclose( $fp );
			return FALSE;
		}

		mbstring_binary_safe_encoding(); // @since 3.7.0 in wp-includes/functions.php
		$data_length   = strlen( $contents );
		$bytes_written = fwrite( $fp, $contents );
		reset_mbstring_encoding();       // @since 3.7.0 in wp-includes/functions.php

		flock ( $fp, LOCK_UN );
		fclose( $fp );

		if ( $data_length !== $bytes_written ) {
			return FALSE;
		}

		global $wp_filesystem;
		$wp_filesystem->chmod( $file, $mode );

		return TRUE;
	}

}