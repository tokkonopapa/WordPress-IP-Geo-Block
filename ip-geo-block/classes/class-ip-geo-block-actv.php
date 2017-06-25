<?php
/**
 * IP Geo Block - Activate
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2017 tokkonopapa
 */

// Stuff for resources
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php';

class IP_Geo_Block_Activate {

	// initialize main blog
	public static function init_main_blog() {
		if ( current_user_can( 'manage_options' ) ) {
			$settings = IP_Geo_Block::get_option();

			// kick off a cron job to download database immediately
			IP_Geo_Block_Cron::start_update_db( $settings );
			IP_Geo_Block_Cron::start_cache_gc( $settings );

			// activate rewrite rules
			IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $settings['rewrite'] );

			// activate mu-plugins if needed
			IP_Geo_Block_Opts::setup_validation_timing( $settings );
		}
	}

	// initialize logs then upgrade and return new options
	public static function activate_blog( $blog_id = FALSE ) {
		// only multisite
		if ( FALSE !== $blog_id )
			switch_to_blog( $blog_id );

		IP_Geo_Block_Logs::create_tables();
		IP_Geo_Block_Opts::upgrade();
	}

	/**
	 * Register options into database table when the plugin is activated.
	 * @link https://wordpress.stackexchange.com/questions/181141/how-to-run-an-activation-function-when-plugin-is-network-activated-on-multisite
	 */
	public static function activate( $network_wide = FALSE ) {
		if ( $network_wide ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$current_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $id ) {
				self::activate_blog( $id );
			}

			switch_to_blog( $current_blog_id );
		}

		else {
			self::activate_blog();
		}

		// only after 'init' action hook for is_user_logged_in().
		if ( did_action( 'init' ) && is_user_logged_in() )
			self::init_main_blog(); // should be called with high priority
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = FALSE ) {
		// cancel schedule
		IP_Geo_Block_Cron::stop_update_db();
		IP_Geo_Block_Cron::stop_cache_gc();

		// deactivate rewrite rules
		IP_Geo_Block_Admin_Rewrite::deactivate_rewrite_all();

		// deactivate mu-plugins
		IP_Geo_Block_Opts::setup_validation_timing();
	}

}