<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2016 tokkonopapa
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define uninstall functionality here
define( 'IP_GEO_BLOCK_PATH', plugin_dir_path( __FILE__ ) ); // @since 2.8
include( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block.php' );
include( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
include( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );
include( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

class IP_Geo_Block_Uninstall {

	/**
	 * Delete settings options, IP address cache, log.
	 *
	 */
	private static function delete_all_options( $settings ) {
		delete_option( IP_Geo_Block::$option_keys['settings'] ); // @since 1.2.0
		IP_Geo_Block_API_Cache::clear_cache();
		IP_Geo_Block_Logs::delete_tables();
		IP_Geo_Block_Opts::delete_api( $settings );
	}

	/**
	 * Delete options from database when the plugin is uninstalled.
	 *
	 */
	public static function uninstall() {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );

		$settings = IP_Geo_Block::get_option( 'settings' );

		if ( $settings['clean_uninstall'] ) {
			if ( ! is_multisite() ) {
				self::delete_all_options( $settings );
			}

			else {
				global $wpdb;
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				$current_blog_id = get_current_blog_id();

				foreach ( $blog_ids as $id ) {
					switch_to_blog( $id );
					self::delete_all_options( $settings );
				}

				switch_to_blog( $current_blog_id );
			}
		}
	}

}

IP_Geo_Block_Uninstall::uninstall();
