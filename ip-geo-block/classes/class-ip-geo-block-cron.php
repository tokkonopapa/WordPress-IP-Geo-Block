<?php
/**
 * IP Geo Block - Cron Class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block_Cron {

	/**
	 * Cron scheduler.
	 *
	 */
	private static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( $immediate ) );

		if ( $update['auto'] ) {
			$now = time();
			$cycle = DAY_IN_SECONDS * (int)$update['cycle'];

			if ( FALSE === $immediate &&
				$now - (int)$db['ipv4_last'] < $cycle &&
				$now - (int)$db['ipv6_last'] < $cycle ) {
				$update['retry'] = 0;
				$next = max( (int)$db['ipv4_last'], (int)$db['ipv6_last'] ) +
					$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 6 );
			} else {
				++$update['retry'];
				$next = $now + ( $immediate ? 0 : DAY_IN_SECONDS );
			}

			wp_schedule_single_event( $next, IP_Geo_Block::CRON_NAME, array( $immediate ) );
		}
	}

	/**
	 * Database auto downloader.
	 *
	 * This function is called when:
	 *   1. Plugin is activated
	 *   2. WP Cron is kicked
	 * under the following condition:
	 *   A. Onece per site when this plugin is activated by network admin
	 *   B. Multiple time for each blog when this plugin is individually activated
	 */
	public static function exec_job( $immediate = FALSE ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		$settings = IP_Geo_Block::get_option( 'settings' );
		$args = IP_Geo_Block::get_request_headers( $settings );

		// download database files (higher priority order)
		foreach ( $providers = IP_Geo_Block_Provider::get_addons() as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) )
				$res[ $provider ] = $geo->download( $settings[ $provider ], $args );
		}

		// re-schedule cron job
		if ( ! empty( $providers ) )
			self::schedule_cron_job( $settings['update'], $settings[ $providers[0] ], FALSE );

		// update option settings
		self::update_settings( $settings, array_merge( array( 'update' ), $providers ) );

		// update matching rule immediately
		if ( $immediate && FALSE !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
			add_filter( IP_Geo_Block::PLUGIN_SLUG . '-ip-addr', array( __CLASS__, 'extract_ip' ) );

			$validate = IP_Geo_Block::get_geolocation( NULL, $providers );
			$validate = IP_Geo_Block::validate_country( NULL, $validate, $settings );

			// if blocking may happen then disable validation
			if ( -1 !== (int)$settings['matching_rule'] && 'passed' !== $validate['result'] )
				$settings['matching_rule'] = -1;

			// setup country code if it needs to be initialized
			if ( -1 === (int)$settings['matching_rule'] && 'ZZ' !== $validate['code'] ) {
				$settings['matching_rule'] = 0; // white list

				if ( FALSE === strpos( $settings['white_list'], $validate['code'] ) )
					$settings['white_list'] .= ( $settings['white_list'] ? ',' : '' ) . $validate['code'];
			}

			// update option settings
			self::update_settings( $settings, array( 'matching_rule', 'white_list', 'black_list' ) );

			// finished to update matching rule
			set_transient( IP_Geo_Block::CRON_NAME, 'done', 2 * MINUTE_IN_SECONDS );
		}

		return isset( $res ) ? $res : NULL;
	}

	/**
	 * Update setting data according to the site type.
	 *
	 */
	private static function update_settings( $src, $keys = array() ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		$slug = IP_Geo_Block::$option_keys['settings'];

		// for multisite
		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {

			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$current_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );
				$dst = IP_Geo_Block::get_option( 'settings' );

				foreach ( $keys as $key ) {
					$dst[ $key ] = $src[ $key ];
				}

				update_option( $slug, $dst );
			}

			switch_to_blog( $current_blog_id );
		}

		// for single site
		else {
			update_option( $slug, $src );
		}
	}

	/**
	 * Extract ip address from transient API.
	 *
	 */
	public static function extract_ip() {
		return filter_var(
			$ip_adrs = get_transient( IP_Geo_Block::CRON_NAME ), FILTER_VALIDATE_IP
		) ? $ip_adrs : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Kick off a cron job to download database immediately on background.
	 *
	 */
	public static function start_update_db( $immediate = TRUE, $ip_adrs ) {
		set_transient( IP_Geo_Block::CRON_NAME, $ip_adrs, 2 * MINUTE_IN_SECONDS );
		$settings = IP_Geo_Block::get_option( 'settings' );
		self::schedule_cron_job( $settings['update'], NULL, $immediate );
	}

	public static function stop_update_db() {
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( FALSE ) ); // @since 2.1.0
	}

}