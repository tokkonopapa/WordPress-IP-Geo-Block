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

	// activate and deactivate main blog
	private static function activate_main_blog( $settings ) {
		IP_Geo_Block_Cron::start_update_db( $settings );
		IP_Geo_Block_Admin_Rewrite::activate_rewrite_all( $settings['rewrite'] );
		IP_Geo_Block_Opts::setup_validation_timing( $settings );
	}

	private static function deactivate_main_blog() {
		IP_Geo_Block_Cron::stop_update_db();
		IP_Geo_Block_Opts::setup_validation_timing();
		IP_Geo_Block_Admin_Rewrite::deactivate_rewrite_all();
	}

	// activate and deactivate each blog
	public static function activate_blog() {
		IP_Geo_Block_Opts::upgrade();
		IP_Geo_Block_Logs::create_tables();
		IP_Geo_Block_Logs::delete_cache_entry();
		IP_Geo_Block_Cron::start_cache_gc();
	}

	private static function deactivate_blog() {
		IP_Geo_Block_Cron::stop_cache_gc();
		IP_Geo_Block_Logs::delete_cache_entry();
	}

	/**
	 * Register options into database table when the plugin is activated.
	 *
	 * @link https://wordpress.stackexchange.com/questions/181141/how-to-run-an-activation-function-when-plugin-is-network-activated-on-multisite
	 */
	public static function activate( $network_wide = FALSE ) {
		// Update main blog first.
		self::activate_blog();

		// Get option of main blog.
		$settings = IP_Geo_Block::get_option();

		if ( $network_wide ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs` ORDER BY `blog_id` ASC" );

			// Skip the main blog.
			array_shift( $blog_ids );

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );

				if ( $settings['network_wide'] ) {
					// copy settings of main site to individual site
					$opts = IP_Geo_Block::get_option();
					$settings['api_key']['GoogleMap'] = $opts['api_key']['GoogleMap'];
					update_option( IP_Geo_Block::OPTION_NAME, $settings );
				}

				// initialize inidivisual site
				self::activate_blog();

				restore_current_blog();
			}
		}

		// only after 'init' action hook for is_user_logged_in().
		if ( did_action( 'init' ) && current_user_can( 'manage_options' ) )
			self::activate_main_blog( $settings );
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 */
	public static function deactivate( $network_wide = FALSE ) {
		add_action( 'shutdown', 'IP_Geo_Block_Activate::deactivate_plugin' );
	}

	public static function deactivate_plugin() {
		global $wpdb;
		$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" );

		$count = 0;
		foreach ( $blog_ids as $id ) {
			switch_to_blog( $id );

			if ( ! is_plugin_active            ( IP_GEO_BLOCK_BASE ) &&
			     ! is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
				$count++;
				self::deactivate_blog();
			}

			restore_current_blog();
		}

		// when all site deactivate this plugin
		if ( count( $blog_ids ) === $count )
			self::deactivate_main_blog();
	}

}