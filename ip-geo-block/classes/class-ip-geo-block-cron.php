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
	 */
	public static function exec_job( $immediate = FALSE ) {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

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
		update_option( IP_Geo_Block::$option_keys['settings'], $settings );

		// update matching rule immediately
		if ( $immediate && FALSE !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
			add_filter( IP_Geo_Block::PLUGIN_SLUG . '-ip-addr', array( __CLASS__, 'extract_ip' ) );

			$validate = IP_Geo_Block::get_geolocation( NULL, $providers );
			$validate = IP_Geo_Block::validate_country( $validate, $settings );

			// if blocking may happen then disable validation
			if ( -1 !== (int)$settings['matching_rule'] && 'passed' !== $validate['result'] )
				$settings['matching_rule'] = -1;

			// setup country code if it needs to be initialized
			if ( -1 === (int)$settings['matching_rule'] && 'ZZ' !== $validate['code'] ) {
				$settings['matching_rule'] = 0; // white list

				if ( FALSE === strpos( $settings['white_list'], $validate['code'] ) )
					$settings['white_list'] .= ( $settings['white_list'] ? ',' : '' ) . $validate['code'];
			}

			update_option( IP_Geo_Block::$option_keys['settings'], $settings );

			// finished to update matching rule
			set_transient( IP_Geo_Block::CRON_NAME, 'done', 2 * MINUTE_IN_SECONDS );
		}

		return isset( $res ) ? $res : NULL;
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
	 * Kick off a cron job to download database immediately
	 *
	 */
	public static function spawn_job( $immediate = TRUE, $ip_adrs ) {
		set_transient( IP_Geo_Block::CRON_NAME, $ip_adrs, 2 * MINUTE_IN_SECONDS );
		$settings = IP_Geo_Block::get_option( 'settings' );
		self::schedule_cron_job( $settings['update'], NULL, $immediate );
	}

}