<?php
/**
 * IP Geo Block - Execute rewrited request
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2016 tokkonopapa
 *
 * THIS IS FOR THE ADVANCED USERS:
 * This file is for WP-ZEP. If a php file in the plugin/theme directory accepts
 * some malicious requests directly without loading WP core, then validation by
 * WP-ZEP will be bypassed. To avoid such a bypassing, those requests should be
 * redirected to this file in order to load WP core. The `.htaccess` in the
 * plugin/theme directory will help this redirection if it will be configured
 * as follows (for apache):
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wp-content/plugins/ip-geo-block/
 * RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * The redirected requests will be verified against the certain attack patterns
 * such as null byte attack or directory traversal, and then load the WordPress
 * core module through wp-load.php to triger WP-ZEP. If it ends up successfully,
 * this includes the originally requested php file to excute it.
 */

if ( ! class_exists( 'IP_Geo_Block_Rewrite' ) ):

class IP_Geo_Block_Rewrite {

	/**
	 * Virtual requested uri to real path for multisite
	 *
	 */
	private static function realpath( $path ) {

		if ( is_multisite() ) {
			$site = str_replace(
				parse_url( network_site_url(), PHP_URL_PATH ), '',
				parse_url( site_url(),         PHP_URL_PATH )
			);
			$path = str_replace( $site, '', $path );
		}

		return realpath( $path );
	}

	/**
	 * Post process (never return)
	 *
	 */
	private static function abort( $context, $validate, $settings, $exist ) {

		// mark as malicious
		$validate['result'] = 'blocked'; //'malice';

		// (1) blocked, unknown, (3) unauthenticated, (5) all
		if ( (int)$settings['validation']['reclogs'] & 1 ) {
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::record_logs( 'admin', $validate, $settings );
		}

		// update statistics
		if ( $settings['save_statistics'] ) {
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
			IP_Geo_Block_Logs::update_stat( 'admin', $validate, $settings );
		}

		// send response code to refuse
		$context->send_response( 'admin', $exist ? $settings['response_code'] : 404 );
	}

	/**
	 * Validation of direct excution
	 *
	 * @note: This function doesn't care about malicious query string.
	 */
	public static function exec( $context, $validate, $settings ) {

		// get document root
		// @note: super global can not be infected even when `register_globals` is on.
		// @see wp-admin/network.php, get_home_path() in wp-admin/includes/file.php
		// @link http://php.net/manual/en/security.globals.php
		// @link http://php.net/manual/en/reserved.variables.php#63831
		// @link http://blog.fyneworks.com/2007/08/php-documentroot-in-iis-windows-servers.html
		// @link http://stackoverflow.com/questions/11893832/is-it-a-good-idea-to-use-serverdocument-root-in-includes
		// @link http://community.sitepoint.com/t/-server-document-root-injection-vulnerability/5274
		// @link http://www.securityfocus.com/archive/1/476274/100/0/threaded
		// @link http://www.securityfocus.com/archive/1/476437/100/0/threaded
		$root = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] :
			substr( $_SERVER['SCRIPT_FILENAME'], 0, -strlen( $_SERVER['SCRIPT_NAME'] ) );

		// get absolute path of requested uri
		// @link http://davidwalsh.name/iis-php-server-request_uri
		$path = ( $root = wp_normalize_path( $root ) ) . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		// while malicios URI may be intercepted by the server,
		// null byte attack should be invalidated just in case.
		// @note: is_file(), is_readable(), file_exists() need a valid path.
		// @link: http://php.net/releases/5_3_4.php, https://bugs.php.net/bug.php?id=39863
		// ex) $path = "/etc/passwd\0.php"; is_file( $path ) === true (5.2.14), false (5.4.4)
		$path = self::realpath( str_replace( "\0", '', $path ) );

		// check path if under the document root
		// This may be meaningless because the HTTP request is always inside the document root.
		// The only possibility is a symbolic link pointed to outside of the document root.
		if ( 0 !== strpos( $path, "$root/" ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		// check default index
		if ( 0 === preg_match( "/\/([^\/]+)$/", $path, $matches ) )
			$path .= 'index.php';

		// check file extention
		// @note: if it fails, rewrite rule may be misconfigured
		if ( FALSE === strripos( strtolower( $path ), '.php', -4 ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		// reconfirm permission for the requested URI
		if ( ! @chdir( dirname( $path ) ) || FALSE === ( @include basename( $path ) ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		exit;
	}

}

// this will trigger `init` action hook
include_once '../../../wp-load.php';

/**
 * Fallback execution
 *
 * Here's never reached if `Validate access to wp-content/(plugins|themes)/.../*.php`
 * is enable. But in case of disable, the requested uri should be executed indirectly
 * as a fallback.
 */
if ( ! class_exists( 'IP_Geo_Block' ) )
	include_once dirname( __FILE__ ) . '/ip-geo-block.php';

IP_Geo_Block_Rewrite::exec(
	IP_Geo_Block::get_instance(),
	IP_Geo_Block::get_geolocation(),
	IP_Geo_Block::get_option( 'settings' )
);

endif; /* ! class_exists( 'IP_Geo_Block_Rewrite' ) */

/**
 * Configuration samples of .htaccess for apache
 *
 * 1. `/wordpress/wp-content/plugins/.htaccess`
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
 * # Bypass `my-plugin/somthing.php`
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
 * # Bypass `my-plugin/somthing.php`
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteRule ^ip-geo-block/rewrite.php$ - [L]
 * RewriteRule ^my-plugin/something.php$ - [L]
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 * 
 * 2. `/wordpress/wp-content/themes/.htaccess`
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wordpress/wp-content/plugins/ip-geo-block/
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 */