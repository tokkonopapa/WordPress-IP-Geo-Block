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
		$settings = IP_Geo_Block::get_option();
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
			add_filter( IP_Geo_Block::PLUGIN_NAME . '-ip-addr', array( __CLASS__, 'extract_ip' ) );

			$validate = IP_Geo_Block::get_geolocation( NULL, $providers );
			$validate = IP_Geo_Block::validate_country( NULL, $validate, $settings );

			// if blocking may happen then disable validation
			if ( -1 !== (int)$settings['matching_rule'] && 'passed' !== $validate['result'] &&
			     ( empty( $_SERVER['HTTP_X_REQUESTED_FROM'] ) || FALSE === strpos( $_SERVER['HTTP_X_REQUESTED_FROM'], 'InfiniteWP' ) ) ) {
				$settings['matching_rule'] = -1;
			}

			// setup country code if it needs to be initialized
			if ( -1 === (int)$settings['matching_rule'] && 'ZZ' !== $validate['code'] ) {
				$settings['matching_rule'] = 0; // white list

				if ( FALSE === strpos( $settings['white_list'], $validate['code'] ) )
					$settings['white_list'] .= ( $settings['white_list'] ? ',' : '' ) . $validate['code'];
			}

			// update option settings
			self::update_settings( $settings, array( 'matching_rule', 'white_list', 'black_list' ) );

			// finished to update matching rule
			set_transient( IP_Geo_Block::CRON_NAME, 'done', 5 * MINUTE_IN_SECONDS );
		}

		return isset( $res ) ? $res : NULL;
	}

	/**
	 * Update setting data according to the site type.
	 *
	 */
	private static function update_settings( $src, $keys = array() ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) )
			require_once ABSPATH . '/wp-admin/includes/plugin.php';

		// for multisite
		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$current_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );
				$dst = IP_Geo_Block::get_option();

				foreach ( $keys as $key ) {
					$dst[ $key ] = $src[ $key ];
				}

				update_option( IP_Geo_Block::OPTION_NAME, $dst );
			}

			switch_to_blog( $current_blog_id );
		}

		// for single site
		else {
			update_option( IP_Geo_Block::OPTION_NAME, $src );
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
	 * Kick off a cron job to download database immediately in background on activation.
	 *
	 */
	public static function start_update_db( $settings ) {
		if ( ! function_exists( 'is_plugin_active' ) )
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// the status is still inactive when this plugin is activated on dashboard.
		if ( ! is_plugin_active( IP_GEO_BLOCK_BASE ) ) {
			set_transient( IP_Geo_Block::CRON_NAME, IP_Geo_Block::get_ip_address(), MINUTE_IN_SECONDS );
			self::schedule_cron_job( $settings['update'], NULL, TRUE );
		}
	}

	public static function stop_update_db() {
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( FALSE ) ); // @since 2.1.0
	}

	/**
	 * Kick off a cron job to garbage collection for IP address cache.
	 *
	 * Note: When the init action occurs in /wp-settings.php, wp_cron() runs.
	 */
	public static function exec_cache_gc( $settings ) {
		IP_Geo_Block_Logs::delete_expired_cache( $settings['cache_time'] );
		self::stop_cache_gc();
		self::start_cache_gc( $settings );
	}

	public static function start_cache_gc( $settings ) {
		if ( ! wp_next_scheduled( IP_Geo_Block::CACHE_NAME ) )
			wp_schedule_single_event( time() + $settings['cache_time_gc'], IP_Geo_Block::CACHE_NAME );
	}

	public static function stop_cache_gc() {
		wp_clear_scheduled_hook( IP_Geo_Block::CACHE_NAME ); // @since 2.1.0
	}

	/**
	 * Download zip/gz file, uncompress and save it to specified file
	 *
	 * @param string $url URL of remote file to be downloaded.
	 * @param array $args request headers.
	 * @param string $filename full path to the downloaded file.
	 * @param int $modified time of last modified on the remote server.
	 * @return array status message.
	 */
	public static function download_zip( $url, $args, $filename, $modified ) {
		if ( ! function_exists( 'download_url' ) )
			require_once ABSPATH . 'wp-admin/includes/file.php';

		// if the name of src file is changed, then update the dst
		if ( basename( $filename ) !== ( $base = pathinfo( $url, PATHINFO_FILENAME ) ) ) {
			$filename = dirname( $filename ) . '/' . $base;
		}

		// check file
		if ( ! file_exists( $filename ) )
			$modified = 0;

		// set 'If-Modified-Since' request header
		$args += array(
			'headers'  => array(
				'If-Modified-Since' => gmdate( DATE_RFC1123, (int)$modified ),
			),
		);

		// fetch file and get response code & message
		$src = wp_remote_head( ( $url = esc_url_raw( $url ) ), $args );

		if ( is_wp_error( $src ) )
			return array(
				'code' => $src->get_error_code(),
				'message' => $src->get_error_message(),
			);

		$code = wp_remote_retrieve_response_code   ( $src );
		$mssg = wp_remote_retrieve_response_message( $src );
		$data = wp_remote_retrieve_header( $src, 'last-modified' );
		$modified = $data ? strtotime( $data ) : $modified;

		if ( 304 == $code )
			return array(
				'code' => $code,
				'message' => __( 'Your database file is up-to-date.', 'ip-geo-block' ),
				'filename' => $filename,
				'modified' => $modified,
			);

		elseif ( 200 != $code )
			return array(
				'code' => $code,
				'message' => $code.' '.$mssg,
			);

		// downloaded and unzip
		try {
			// download file
			$src = download_url( $url );

			if ( is_wp_error( $src ) )
				throw new Exception(
					$src->get_error_code() . ' ' . $src->get_error_message()
				);

			// get extension
			$args = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );

			// unzip file
			if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
				if ( FALSE === ( $gz = gzopen( $src, 'r' ) ) )
					throw new Exception(
						sprintf( __( 'Unable to read %s. Please check the permission.', 'ip-geo-block' ), $src )
					);

				if ( FALSE === ( $fp = @fopen( $filename, 'cb' ) ) )
					throw new Exception(
						sprintf( __( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), $filename )
					);

				if ( ! flock( $fp, LOCK_EX ) )
					throw new Exception(
						sprintf( __( 'Can\'t lock %s. Please try again after a while.', 'ip-geo-block' ), $filename )
					);

				ftruncate( $fp, 0 ); // truncate file

				// same block size in wp-includes/class-http.php
				while ( $data = gzread( $gz, 4096 ) ) {
					fwrite( $fp, $data, strlen( $data ) );
				}
			}

			elseif ( 'zip' === $args && class_exists( 'ZipArchive' ) ) {
				// https://codex.wordpress.org/Function_Reference/unzip_file
				WP_Filesystem();
				$tmp = get_temp_dir(); // @since 2.5
				$ret = unzip_file( $src, $tmp ); // @since 2.5

				if ( is_wp_error( $ret ) )
					throw new Exception(
						$ret->get_error_code() . ' ' . $ret->get_error_message()
					);

				if ( FALSE === ( $gz = @fopen( $tmp .= basename( $filename ), 'r' ) ) )
					throw new Exception(
						sprintf( __( 'Unable to read %s. Please check the permission.', 'ip-geo-block' ), $src )
					);

				if ( FALSE === ( $fp = @fopen( $filename, 'cb' ) ) )
					throw new Exception(
						sprintf( __( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), $filename )
					);

				if ( ! flock( $fp, LOCK_EX ) )
					throw new Exception(
						sprintf( __( 'Can\'t lock %s. Please try again after a while.', 'ip-geo-block' ), $filename )
					);

				ftruncate( $fp, 0 ); // truncate file

				// same block size in wp-includes/class-http.php
				while ( $data = fread( $gz, 4096 ) ) {
					fwrite( $fp, $data, strlen( $data ) );
				}
			}

			if ( ! empty( $fp ) ) {
				fflush( $fp );          // flush output before releasing the lock
				flock ( $fp, LOCK_UN ); // release the lock
				fclose( $fp );
			}

			! empty( $gz  ) and gzclose( $gz  );
			! empty( $tmp ) && @is_file( $tmp ) and @unlink( $tmp );
			! is_wp_error( $src ) && @is_file( $src ) and @unlink( $src );
		}

		// error handler
		catch ( Exception $e ) {
			if ( ! empty( $fp ) ) {
				fflush( $fp );          // flush output before releasing the lock
				flock ( $fp, LOCK_UN ); // release the lock
				fclose( $fp );
			}

			! empty( $gz  ) and gzclose( $gz  );
			! empty( $tmp ) && @is_file( $tmp ) and @unlink( $tmp );
			! is_wp_error( $src ) && @is_file( $src ) and @unlink( $src );

			return array(
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}

		return array(
			'code' => $code,
			'message' => sprintf(
				__( 'Last update: %s', 'ip-geo-block' ),
				IP_Geo_Block_Util::localdate( $modified )
			),
			'filename' => $filename,
			'modified' => $modified,
		);
	}

}