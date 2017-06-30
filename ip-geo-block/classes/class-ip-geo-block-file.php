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
		require_once ABSPATH . 'wp-admin/includes/template.php'; // for submit_button() in request_filesystem_credentials()
		require_once ABSPATH . 'wp-admin/includes/file.php'; // for get_filesystem_method(), request_filesystem_credentials()
		global $wp_filesystem;

		// check already assigned by WP_Filesystem()
		if ( empty( $wp_filesystem ) ) {
if (0) {
			// https://codex.wordpress.org/Filesystem_API#Tips_and_Tricks
			if ( 'direct' === ( self::$method = get_filesystem_method() ) ) { // @since 2.5.0
				// request_filesystem_credentials() can be run without any issues and don't need to worry about passing in a URL
				$creds = request_filesystem_credentials( admin_url(), '', FALSE, FALSE, NULL ); // @since 2.5.0

				// initialize the API @since 2.5.0
				WP_Filesystem( $creds );
			}

			elseif ( class_exists( 'IP_Geo_Block_Admin' ) ) {
				IP_Geo_Block_Admin::add_admin_notice(
					'error',
					sprintf( __( 'This plugin does not support method &#8220;%s&#8221; for FTP or SSH based file operations. Please refer to <a href="https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants" title="Editing wp-config.php &laquo; WordPress Codex">this document</a> for more details.', 'ip-geo-block' ), self::$method )
				);
			}
} else {
			// Determines the method on the filesystem.
			self::$method = get_filesystem_method();

			if ( FALSE === ( $creds = request_filesystem_credentials( admin_url(), '', FALSE, FALSE, NULL ) ) ) {
				if ( class_exists( 'IP_Geo_Block_Admin' ) ) {
					IP_Geo_Block_Admin::add_admin_notice(
						'error',
						__( 'You should define some constants in your <code>wp-config.php</code> for FTP or SSH based file operations. Please refer to <a href="https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants" title="Editing wp-config.php &laquo; WordPress Codex">this document</a> for more details.', 'ip-geo-block' )
					);
				}
			}

			else {
				WP_Filesystem( $creds );
			}
}
		}

		return self::get_instance();
	}

	// Add slash at the end of string.
	private function slashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}

	// Get absolute path.
	private function absolute_path( $file ) {
		if ( 'direct' !== self::$method ) {
			global $wp_filesystem;
			$path = str_replace( ABSPATH, $wp_filesystem->abspath(), dirname( $file ) );
			$file = $this->slashit( $path ) . basename( $file );
		}

		return $file;
	}

	/**
	 * Validate if path is file
	 *
	 * @param string $path
	 * @return bool
	 */
	public function is_file( $path ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->is_file( $this->absolute_path( $path ) );
	}

	/**
	 * Validate if path is directory
	 *
	 * @param string $path
	 * @return bool
	 */
	public function is_dir( $path ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->is_dir( $this->absolute_path( $path ) );
	}

	/**
	 * Make a directory
	 *
	 * @param string $path
	 * @param mixed  $chmod
	 * @param mixed  $chown
	 * @param mixed  $chgrp
	 * @return bool
	 */
	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->mkdir( $this->absolute_path( $path ), $chmod, $chown, $chgrp );
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
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->delete( $this->absolute_path( $file ), $recursive, $type );
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
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->copy(
			$this->absolute_path( $src ),
			$this->absolute_path( $dst ),
			$overwrite, $mode
		);
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
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		$file = $this->absolute_path( $file );

		if ( ! ( $fp = @fopen( $file, 'wb' ) ) )
			return FALSE;

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

		if ( $data_length !== $bytes_written )
			return FALSE;

		return $wp_filesystem->chmod( $file, $mode );
	}

	/**
	 * Read entire file into a string.
	 *
	 * @param  string $file  Filename.
	 * @return string|false  File contents on success, false if no file could be opened.
	 */
	public function get_contents( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->get_contents( $file );
	}

	/**
	 * Read entire file into an array.
	 *
	 * @param  string $file  Filename.
	 * @return array
	 */
	public function get_contents_array( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return array();

		return $wp_filesystem->get_contents_array( $file );
	}

	/**
	 * Unzips a specified ZIP file to a location on the Filesystem via the WordPress Filesystem Abstraction.
	 *
	 * @param  string $src Full path and filename of zip archive.
	 * @param  string $dst Full path on the filesystem to extract archive to.
	 * @return WP_Error on failure, True on success 
	 */
	public function unzip_file( $src, $dst ) {
		return unzip_file( $src, $this->absolute_path( $dst ) );
	}

}