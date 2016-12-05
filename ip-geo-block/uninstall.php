<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

define( 'IP_GEO_BLOCK_PATH', plugin_dir_path( __FILE__ ) ); // @since 2.8

require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php';

class IP_Geo_Block_Uninstall {

	/**
	 * Delete settings options, IP address cache, log.
	 *
	 */
	private static function delete_blog_options() {
		delete_option( IP_Geo_Block::OPTION_NAME ); // @since 1.2.0
		IP_Geo_Block_Logs::delete_tables();
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		$settings = IP_Geo_Block::get_option();

		if ( $settings['clean_uninstall'] ) {
			if ( ! is_multisite() ) {
				self::delete_blog_options( $settings );
			}

			else {
				global $wpdb;
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				$current_blog_id = get_current_blog_id();

				foreach ( $blog_ids as $id ) {
					switch_to_blog( $id );
					self::delete_blog_options();
				}

				switch_to_blog( $current_blog_id );
			}
		}

		IP_Geo_Block_Opts::delete_api( $settings );
		IP_Geo_Block_Opts::setup_validation_timing( FALSE );
	}

}

IP_Geo_Block_Uninstall::uninstall();
