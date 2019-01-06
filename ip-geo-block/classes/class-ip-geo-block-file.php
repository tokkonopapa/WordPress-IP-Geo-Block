<?php
/**
 * IP Geo Block - Filesystem
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @link      https://codex.wordpress.org/Filesystem_API
 * @copyright 2013-2019 tokkonopapa
 */

class IP_Geo_Block_FS {

	/**
	 * Private variables of this class.
	 *
	 */
	private static $instance = NULL;
	private static $method = 'direct';

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

			elseif ( class_exists( 'IP_Geo_Block_Admin', FALSE ) ) {
				IP_Geo_Block_Admin::add_admin_notice(
					'error',
					sprintf( __( 'This plugin does not support method &#8220;%s&#8221; for FTP or SSH based file operations. Please refer to <a href="https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants" title="Editing wp-config.php &laquo; WordPress Codex">this document</a> for more details.', 'ip-geo-block' ), self::$method )
				);
			}
} else {
			// Determines the method on the filesystem.
			self::$method = get_filesystem_method();

			if ( FALSE !== ( $creds = request_filesystem_credentials( admin_url(), '', FALSE, FALSE, NULL ) ) ) {
				WP_Filesystem( $creds );
			}

			elseif ( class_exists( 'IP_Geo_Block_Admin', FALSE ) ) {
				IP_Geo_Block_Admin::add_admin_notice(
					'error',
					__( 'You should define some constants in your <code>wp-config.php</code> for FTP or SSH based file operations. Please refer to <a href="https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants" title="Editing wp-config.php &laquo; WordPress Codex">this document</a> for more details.', 'ip-geo-block' )
				);
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
		global $wp_filesystem;
		$path = str_replace( ABSPATH, $wp_filesystem->abspath(), dirname( $file ) );

		return $this->slashit( $path ) . basename( $file );
	}

	/**
	 * Get method of the file system;
	 *
	 * @return string method of file system
	 */
	public function get_method() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return self::$method;
	}

	/**
	 * Check if a file or directory exists.
	 *
	 * @param  string $file Path to file/directory.
	 * @return bool   Whether $file exists or not.
	 */
	public function exists( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->exists( $this->absolute_path( $file ) );
	}

	/**
	 * Validate if path is file
	 *
	 * @param  string $path
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
	 * @param  string $path
	 * @return bool
	 */
	public function is_dir( $path ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->is_dir( $this->absolute_path( $path ) );
	}

	/**
	 * Check if a file is readable.
	 *
	 * @param  string $file Path to file.
	 * @return bool   Whether $file is readable.
	 */
	public function is_readable( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->is_readable( $this->absolute_path( $file ) );
	}

	/**
	 * Check if a file or directory is writable.
	 *
	 * @param  string $file Path to file.
	 * @return bool   Whether $file is writable.
	 */
	public function is_writable( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->is_writable( $this->absolute_path( $file ) );
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
	 * Delete a file or directory.
	 *
	 * @param  string $file      Path to the file.
	 * @param  bool   $recursive If set True changes file group recursively. 
	 * @param  bool   $type      Type of resource. 'f' for file, 'd' for directory.
	 * @return bool   True if the file or directory was deleted, false on failure.
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

		if ( 'direct' === self::$method )
			return @file_put_contents( $this->absolute_path( $file ), $contents, LOCK_EX );
		else
			return $wp_filesystem->put_contents( $this->absolute_path( $file ), $contents, $mode );
	}

	/**
	 * Reads entire file into a string
	 *
	 * @access public
	 *
	 * @param string $file Name of the file to read.
	 * @return string|bool The data of contents or false on failure.
	 */
	public function get_contents( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		if ( 'direct' === self::$method )
			return @file_get_contents( $this->absolute_path( $file ) );
		else
			return $wp_filesystem->get_contents( $this->absolute_path( $file ) );
	}

	/**
	 * Read entire file into an array.
	 *
	 * @param string $file Filename.
	 * @return array|bool  An array of contents or false on failure.
	 */
	public function get_contents_array( $file ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return array();

		// https://php.net/manual/en/function.file.php#refsect1-function.file-returnvalues
		@ini_set( 'auto_detect_line_endings', TRUE );

		if ( ! $this->is_file( $file ) || ! $this->is_readable( $file ) )
			return FALSE;

		if ( 'direct' === self::$method )
			return file( $this->absolute_path( $file ), FILE_IGNORE_NEW_LINES );

		$file = $wp_filesystem->get_contents_array( $this->absolute_path( $file ) );
		return FALSE !== $file ? array_map( 'rtrim', $file ) : FALSE;
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

	/**
	 * Get details for files in a directory or a specific file.
	 *
	 * @since 2.5.0
	 *
	 * @param string $path
	 * @param bool $include_hidden
	 * @param bool $recursive
	 * @return array|bool {
	 *     Array of files. False if unable to list directory contents.
	 *     @type string 'name'        Name of the file/directory.
	 *     @type string 'perms'       *nix representation of permissions.
	 *     @type int    'permsn'      Octal representation of permissions.
	 *     @type string 'owner'       Owner name or ID.
	 *     @type int    'size'        Size of file in bytes.
	 *     @type int    'lastmodunix' Last modified unix timestamp.
	 *     @type mixed  'lastmod'     Last modified month (3 letter) and day (without leading 0).
	 *     @type int    'time'        Last modified time.
	 *     @type string 'type'        Type of resource. 'f' for file, 'd' for directory.
	 *     @type mixed  'files'       If a directory and $recursive is true, contains another array of files.
	 * }
	 */
	public function dirlist( $path, $include_hidden = FALSE, $recursive = FALSE ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) )
			return FALSE;

		return $wp_filesystem->dirlist( $path, $include_hidden, $recursive );
	}

}