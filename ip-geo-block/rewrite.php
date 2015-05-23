<?php
/**
 * IP Geo Block - Execute rewrited request
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2015 tokkonopapa
 */

if ( ! defined( 'IP_GEO_BLOCK_REWRITE' ) ):

/**
 * Define the API of this class
 *
 */
define( 'IP_GEO_BLOCK_REWRITE', 'IP_Geo_Block_Rewrite::exec' );

class IP_Geo_Block_Rewrite {

	/**
	 * Post process (never return)
	 *
	 */
	private static function abort( $validate, $settings, $exist ) {

		$context = IP_Geo_Block::get_instance();

		// mark as malicious
		$validate['result'] = 'blocked'; //'malice';

		// (1) blocked, unknown, (3) unauthenticated, (5) all
		if ( (int)$settings['validation']['reclogs'] & 1 ) {
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::record_log( 'admin', $validate, $settings );
		}

		// update statistics
		if ( $settings['save_statistics'] )
			$context->update_statistics( $validate );

		// send response code to refuse
		$context->send_response( $exist ? $settings['response_code'] : 404 );
	}

	/**
	 * Validate direct excution
	 *
	 * @note: This function doesn't care about malicious query string.
	 */
	public static function exec( $validate, $settings ) {

		// get document root
		// @see wp-admin/network.php, get_home_path() in wp-admin/includes/file.php
		// @link http://blog.fyneworks.com/2007/08/php-documentroot-in-iis-windows-servers.html
		// @link http://stackoverflow.com/questions/11893832/is-it-a-good-idea-to-use-serverdocument-root-in-includes
		if ( ! ( $path = $_SERVER['DOCUMENT_ROOT'] ) )
			$path = substr( $_SERVER['SCRIPT_FILENAME'], 0, -strlen( $_SERVER['SCRIPT_NAME'] ) );

		// get absolute path of requested uri
		// @link http://davidwalsh.name/iis-php-server-request_uri
		$path = str_replace( '\\', '/', realpath( $path ) ) .
		        parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		// check path
		if ( preg_match( "/.*\/([^\/]*?)$/", $path, $matches ) )
			if ( empty( $matches[1] ) )
				$path .= "index.php";

		// while malicios URI may be intercepted by the server,
		// null byte attack should be invalidated just in case.
		// ex) $path = "/etc/passwd\0.php"
		$path = str_replace( "\0", '', $path );

		// check file and extention
		// @note: is_readable() and is_file() need a valid path.
		// @link: http://php.net/releases/5_3_4.php, https://bugs.php.net/bug.php?id=39863
		// ex) is_file("/etc/passwd\0.php") === true (5.2.14), false (5.4.4)
		if ( ! @is_readable( $path ) || ! @is_file( $path ) ||
		     'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
			self::abort( $validate, $settings, file_exists( $path ) );
		}

		// reconfirm the requested URI is on the file system
		if ( chdir( dirname( $path ) ) )
			include_once basename( $path );

		exit;
	}

}

// this will trigger `init` action hook
include_once '../../../wp-load.php';

/**
 * Fallback execution
 *
 * Here's never reached when `Validate access to (plugins|themes)/*.php` is enable.
 * But when disable, the requested uri should be executed indirectly as a fallback.
 */

IP_Geo_Block_Rewrite::exec(
	IP_Geo_Block::get_geolocation(),
	IP_Geo_Block::get_option( 'settings' )
);

endif; /* ! defined( 'IP_GEO_BLOCK_REWRITE' ) */

/**
 * Configuration samples of .htaccess for apache
 *
 * 1. `wp-content/plugins/.htaccess`
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteRule ^ip-geo-block/rewrite.php$ - [L]
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$ [AND]
 * RewriteCond %{REQUEST_URI} !my-plugin/somthing.php$
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteRule ^ip-geo-block/rewrite.php$ - [L]
 * RewriteRule ^my-plugin/something.php$ - [L]
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 * 
 * 2. `wp-content/themes/.htaccess`
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 */
