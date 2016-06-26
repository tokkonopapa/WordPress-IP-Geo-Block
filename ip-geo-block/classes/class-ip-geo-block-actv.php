<?php
/**
 * IP Geo Block - Activate
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2016 tokkonopapa
 */

class IP_Geo_Block_Activate {

	// initialize logs then upgrade and return new options
	private static function activate_blog() {
		IP_Geo_Block_Logs::create_tables();
		IP_Geo_Block_Opts::upgrade();
	}

	/**
	 * Register options into database table when the plugin is activated.
	 *
	 */
	public static function activate( $network_wide = FALSE ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );

		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$current_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );
				self::activate_blog();
			}

			switch_to_blog( $current_blog_id );
		} else {
			self::activate_blog();
		}

		// only for main blog
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php' );
			include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );

			$settings = IP_Geo_Block::get_option( 'settings' );

			// kick off a cron job to download database immediately
			IP_Geo_Block_Cron::start_update_db( TRUE, IP_Geo_Block::get_ip_address() );

			// activate rewrite rules
			IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $settings['rewrite'] );
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = FALSE ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php' );
		include_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );

		// cancel schedule
		IP_Geo_Block_Cron::stop_update_db();

		// deactivate rewrite rules
		IP_Geo_Block_Admin_Rewrite::deactivate_rewrite_all();
	}

}