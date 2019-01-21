<?php
/**
 * IP Geo Block - Execute rewrited request
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 *
 * THIS IS FOR THE ADVANCED USERS:
 * This file is for WP-ZEP. If some php files in the plugins/themes directory
 * accept malicious requests directly without loading WP core, then validation
 * by WP-ZEP will be bypassed. To avoid such bypassing, those requests should
 * be redirected to this file in order to load WP core. The `.user.ini` in the
 * plugins/themes directory will help this redirection if it is configured as
 * follows on nginx for example:
 *
 * ; BEGIN IP Geo Block
 * auto_prepend_file = "/home/wp-content/plugins/ip-geo-block/rewrite-ini.php"
 * ; END IP Geo Block
 *
 * The redirected requests will be verified against the certain attack patterns
 * such as null byte attack or directory traversal, and then load the WordPress
 * core module through wp-load.php to triger WP-ZEP.
 */

if ( ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) ):

class IP_Geo_Block_Rewrite {

	public static function search_user_ini() {
		$dir = dirname( dirname( __FILE__ ) ); // `/wp-content/plugins`
		$ini = ini_get( 'user_ini.filename' );
		$doc = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ?
			$_SERVER['DOCUMENT_ROOT'] :
			substr( $_SERVER['SCRIPT_FILENAME'], 0, -strlen( $_SERVER['SCRIPT_NAME'] ) );

		do {
			// avoid loop just in case
			if ( ( $next = dirname( $dir ) ) !== $dir ) {
				$dir = $next;
			} else {
				break;
			}

			if ( file_exists( "$dir/$ini" ) ) {
				$tmp = @file( "$dir/$ini" );
				$tmp = preg_grep( '/^\s*auto_prepend_file/', $tmp );
				$tmp = explode( '=', (string)array_pop( $tmp ), 2 );

				if ( ! empty( $tmp ) ) {
					$tmp = trim( $tmp[1], " \t\n\r\0\x0B\"\'" );
					if ( $tmp && file_exists( $tmp ) ) {
						@include_once( $tmp );
					}
				}

				break;
			}
		} while ( $dir !== $doc );
	}

	// this function should be empty
	public static function exec( $context, $validate, $settings ) {}

}

// search and include `.user.ini` in other directory
IP_Geo_Block_Rewrite::search_user_ini();

// this will trigger `init` action hook
require_once substr( __FILE__, 0, strpos( __FILE__, '/wp-content/' ) ) . '/wp-load.php';

endif; /* ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) */