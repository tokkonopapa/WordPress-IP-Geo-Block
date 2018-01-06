<?php
/**
 * IP Geo Block - Execute rewrited request
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
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
 * auto_prepend_file = "/wp-content/plugins/ip-geo-block/rewrite-userini.php"
 * ; END IP Geo Block
 *
 * The redirected requests will be verified against the certain attack patterns
 * such as null byte attack or directory traversal, and then load the WordPress
 * core module through wp-load.php to triger WP-ZEP. If it ends up successfully
 * this includes the originally requested php file to excute it.
 */

if ( ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) ):

class IP_Geo_Block_Rewrite {

	public static function search_user_ini() {
		$dir = dirname( dirname( __FILE__ ) ); // `/wp-content/plugins`
		$root = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ?
			$_SERVER['DOCUMENT_ROOT'] :
			substr( $_SERVER['SCRIPT_FILENAME'], 0, -strlen( $_SERVER['SCRIPT_NAME'] ) );

		do {
			$dir = dirname( $dir );
			if ( file_exists( "$dir/.user.ini" ) ) {
				$content = @file( "$dir/.user.ini" );
				$content = preg_grep( '/^\s*auto_prepend_file/', $content );
				$content = explode( '=', (string)array_pop( $content ), 2 );

				if ( ! empty( $content ) ) {
					$content = trim( $content[1], " \t\n\r\0\x0B\"\'" );
					if ( $content && file_exists( $content ) ) {
						@include_once( $content );
					}
				}

				break;
			}
		} while ( $dir !== $root );
	}

	// this function should be empty
	public static function exec( $context, $validate, $settings ) {}

}

// search and include `.user.ini` in other directory
IP_Geo_Block_Rewrite::search_user_ini();

// this will trigger `init` action hook
require_once '../../../wp-load.php';

endif; /* ! class_exists( 'IP_Geo_Block_Rewrite', FALSE ) */
