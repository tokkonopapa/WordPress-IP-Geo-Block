<?php
/**
 * IP Geo Block - Activate
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */

// Stuff for resources
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-opts.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php';
require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-rewrite.php';

class IP_Geo_Block_Activate {

	// initialize main blog
	private static function init_main_blog() {
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
	public static function activate_blog() {
		IP_Geo_Block_Logs::create_tables();
		IP_Geo_Block_Logs::delete_cache_entry();
		IP_Geo_Block_Opts::upgrade();
	}

	/**
	 * Register options into database table when the plugin is activated.
	 * @link https://wordpress.stackexchange.com/questions/181141/how-to-run-an-activation-function-when-plugin-is-network-activated-on-multisite
	 */
	public static function activate( $network_wide = FALSE ) {
		defined( 'IP_GEO_BLOCK_DEBUG' ) and IP_GEO_BLOCK_DEBUG and assert( 'is_main_site()', 'Not main blog.' );

		// Update main blog first.
		self::activate_blog();

		if ( $network_wide ) {
			// Get option of main blog.
			$option = IP_Geo_Block::get_option();

			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs` ORDER BY `blog_id` ASC" );

			// Skip the main blog.
			array_shift( $blog_ids );

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );

				if ( $option['network_wide'] ) {
					// individual data
					$opt = IP_Geo_Block::get_option();
					$option['api_key']['GoogleMap'] = $opt['api_key']['GoogleMap'];

					update_option( IP_Geo_Block::OPTION_NAME, $option );
				}

				else {
					self::activate_blog();
				}

				restore_current_blog();
			}
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

		// remove self ip address from cache
		IP_Geo_Block_Logs::delete_cache_entry();
	}

}