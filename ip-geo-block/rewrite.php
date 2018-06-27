<?php
/**
 * IP Geo Block - Execute rewrited request
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 *
 * THIS IS FOR THE ADVANCED USERS:
 * This file is for WP-ZEP. If some php files in the plugins/themes directory
 * accept malicious requests directly without loading WP core, then validation
 * by WP-ZEP will be bypassed. To avoid such bypassing, those requests should
 * be redirected to this file in order to load WP core. The `.htaccess` in the
 * plugins/themes directory will help this redirection if it is configured as
 * follows on apache for example:
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
 * core module through wp-load.php to triger WP-ZEP. If it ends up successfully
 * this includes the originally requested php file to excute it.
 */

if ( ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) ):

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

		return self::normalize_path( realpath( $path ) );
	}

	/**
	 * WP alternative function for advanced-cache.php
	 *
	 * Normalize a filesystem path.
	 * @source: wp-includes/functions.php
	 */
	private static function normalize_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		if ( ':' === substr( $path, 1, 1 ) )
			$path = ucfirst( $path );

		return rtrim( $path, '/\\' );
	}

	/**
	 * Post process (never return)
	 *
	 */
	private static function abort( $context, $validate, $settings, $exist ) {

		// mark as malicious path
		$validate['result'] = 'badpath';

		// (1) blocked, unknown, (3) unauthenticated, (5) all
		IP_Geo_Block_Logs::record_logs( 'admin', $validate, $settings, TRUE );

		// update statistics
		if ( $settings['save_statistics'] )
			IP_Geo_Block_Logs::update_stat( 'admin', $validate, $settings );

		// compose status code and message
		if ( ! $exist && 404 != $settings['response_code'] ) {
			$settings['response_code'] = 404;
			$settings['response_msg' ] = 'Not Found';
		}

		// send response code to refuse
		$context->send_response( 'admin', $validate, $settings );
	}

	/**
	 * Validation of direct excution
	 *
	 * Note: This function doesn't care about malicious query string.
	 */
	public static function exec( $context, $validate, $settings ) {

		// get document root
		// Note: super global can not be infected even when `register_globals` is on.
		// @see wp-admin/network.php, get_home_path() in wp-admin/includes/file.php
		// @link http://php.net/manual/en/security.globals.php
		// @link http://php.net/manual/en/reserved.variables.php#63831
		// @link http://blog.fyneworks.com/2007/08/php-documentroot-in-iis-windows-servers.html
		// @link http://stackoverflow.com/questions/11893832/is-it-a-good-idea-to-use-serverdocument-root-in-includes
		// @link http://community.sitepoint.com/t/-server-document-root-injection-vulnerability/5274
		// @link http://www.securityfocus.com/archive/1/476274/100/0/threaded
		// @link http://www.securityfocus.com/archive/1/476437/100/0/threaded
		$root = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ?
			$_SERVER['DOCUMENT_ROOT'] :
			substr( $_SERVER['SCRIPT_FILENAME'], 0, -strlen( $_SERVER['SCRIPT_NAME'] ) );

		// get absolute path of requested uri
		// @link http://davidwalsh.name/iis-php-server-request_uri
		$path = ( $root = self::normalize_path( $root ) ) . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		// while malicios URI may be intercepted by the server,
		// null byte attack should be invalidated just in case.
		// Note: is_file(), is_readable(), file_exists() need a valid path.
		// @link http://php.net/releases/5_3_4.php, https://bugs.php.net/bug.php?id=39863
		// @example $path = "/etc/passwd\0.php"; is_file( $path ) === true (5.2.14), false (5.4.4)
		$path = self::realpath( str_replace( "\0", '', $path ) );

		// check default index
		if ( FALSE === strripos( strtolower( $path ), '.php', -4 ) )
			$path .= '/index.php';

		// check path if under the document root
		// This may be meaningless because the HTTP request is always inside the document root.
		// The only possibility is a symbolic link pointed to outside of the document root.
		if ( 0 !== strpos( $path, "$root/" ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		// check file extention
		// if it fails, rewrite rule may be misconfigured
		if ( FALSE === strripos( strtolower( $path ), '.php', -4 ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		// reconfirm permission for the requested URI
		if ( ! @chdir( dirname( $path ) ) || FALSE === ( @include basename( $path ) ) )
			self::abort( $context, $validate, $settings, file_exists( $path ) );

		exit;
	}

}

// this will trigger `init` action hook
require_once '../../../wp-load.php';

/**
 * Fallback execution
 *
 * Here's never reached if `Validate access to wp-content/(plugins|themes)/.../*.php`
 * is enable. But in case of disable, the requested uri should be executed indirectly
 * as a fallback.
 */
if ( ! class_exists( 'IP_Geo_Block', FALSE ) )
	require_once dirname( __FILE__ ) . '/ip-geo-block.php';

IP_Geo_Block_Rewrite::exec(
	IP_Geo_Block::get_instance(),
	IP_Geo_Block::get_geolocation(),
	IP_Geo_Block::get_option()
);

endif; /* ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) */

/**
 * Configuration samples of .htaccess for apache
 *
 * 1. `/wp-content/plugins/.htaccess`
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
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wp-content/plugins/ip-geo-block/
 * RewriteRule ^ip-geo-block/rewrite.php$ - [L]
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * # BEGIN IP Geo Block
 * # except `my-plugin/somthing.php`
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wp-content/plugins/ip-geo-block/
 * RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$
 * RewriteCond %{REQUEST_URI} !my-plugin/somthing.php$
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * # BEGIN IP Geo Block
 * # except `my-plugin/somthing.php`
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wp-content/plugins/ip-geo-block/
 * RewriteRule ^ip-geo-block/rewrite.php$ - [L]
 * RewriteRule ^my-plugin/something.php$ - [L]
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 *
 * 2. `/wp-content/themes/.htaccess`
 *
 * # BEGIN IP Geo Block
 * <IfModule mod_rewrite.c>
 * RewriteEngine on
 * RewriteBase /wp-content/plugins/ip-geo-block/
 * RewriteRule ^.*\.php$ rewrite.php [L]
 * </IfModule>
 * # END IP Geo Block
 */