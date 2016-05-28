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
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php' );

		if ( $network_wide ) {
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
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php' );
			require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );

			// kick off a cron job to download database immediately
			IP_Geo_Block_Cron::spawn_job( TRUE, IP_Geo_Block::get_ip_address() );

			// activate rewrite rules
			$settings = IP_Geo_Block::get_option( 'settings' );
			IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $settings['rewrite'] );
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = FALSE ) {
		// cancel schedule
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( FALSE ) ); // @since 2.1.0

		// deactivate rewrite rules
		require_once( IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php' );
		IP_Geo_Block_Admin_Rewrite::deactivate_rewrite_all();
	}

}