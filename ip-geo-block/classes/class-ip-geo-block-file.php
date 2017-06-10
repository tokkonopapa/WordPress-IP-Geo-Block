<?php
/**
 * IP Geo Block - Filesystem
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2017 tokkonopapa
 */

class IP_Geo_Block_FS {

	public static function init( $msg = NULL ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		if ( ! empty( $wp_filesystem ) ) // assigned in WP_Filesystem()
			return $wp_filesystem;

		if ( get_filesystem_method() === 'direct' ) { // @since 2.5.0
			/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', FALSE, FALSE, NULL ); // @since 2.5.0

			/* initialize the API @since 2.5.0 */
			if ( ! WP_Filesystem( $creds ) ) {
				/* any problems and we exit */
				return FALSE;
			}

			/* do file manipulations with $wp_filesystem */
			return $wp_filesystem;
		}

		elseif ( class_exists( 'IP_Geo_Block_Admin' ) ) {
			IP_Geo_Block_Admin::add_admin_notice(
				'error',
				sprintf( __( '%s: This plugin does not support FTP or SSH based file operations.', 'ip-geo-block' ), $msg ? $msg : __CLASS__ )
			);
		}

		return FALSE;
	}

}