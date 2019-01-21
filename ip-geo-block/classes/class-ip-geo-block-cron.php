<?php
/**
 * IP Geo Block - Cron Class
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 */

class IP_Geo_Block_Cron {

	/**
	 * Cron scheduler.
	 *
	 */
	private static function schedule_cron_job( &$update, $db, $immediate = FALSE ) {
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( $immediate ) );

		if ( $update['auto'] || $immediate ) {
			$now = time();
			$next = $now + ( $immediate ? 0 : DAY_IN_SECONDS );

			if ( FALSE === $immediate ) {
				++$update['retry'];
				$cycle = DAY_IN_SECONDS * (int)$update['cycle'];

				if ( isset( $db['ipv4_last'] ) ) {
					// in case of Maxmind Legacy or IP2Location
					if ( $now - (int)$db['ipv4_last'] < $cycle &&
					     $now - (int)$db['ipv6_last'] < $cycle ) {
						$update['retry'] = 0;
						$next = max( (int)$db['ipv4_last'], (int)$db['ipv6_last'] ) +
							$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 6 );
					}
				} else {
					// in case of Maxmind GeoLite2
					if ( $now - (int)$db['ip_last'] < $cycle ) {
						$update['retry'] = 0;
						$next = (int)$db['ip_last'] +
							$cycle + rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 6 );
					}
				}
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
	 *   A. Once per site when this plugin is activated on network wide
	 *   B. Multiple time for each blog when this plugin is individually activated
	 */
	public static function exec_update_db( $immediate = FALSE ) {
		$settings = IP_Geo_Block::get_option();

		// extract ip address from transient API to confirm the request source
		if ( $immediate ) {
			set_transient( IP_Geo_Block::CRON_NAME, IP_Geo_Block::get_ip_address( $settings ), MINUTE_IN_SECONDS );
			add_filter( IP_Geo_Block::PLUGIN_NAME . '-ip-addr', array( __CLASS__, 'extract_ip' ) );
		}

		$context = IP_Geo_Block::get_instance();
		$args = IP_Geo_Block::get_request_headers( $settings );

		// download database files (higher priority order)
		foreach ( $providers = IP_Geo_Block_Provider::get_addons( $settings['providers'] ) as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, $settings ) ) {
				$res[ $provider ] = $geo->download( $settings[ $provider ], $args );

				// re-schedule cron job
				self::schedule_cron_job( $settings['update'], $settings[ $provider ], FALSE );

				// update provider settings
				self::update_settings( $settings, array( 'update', $provider ) );

				// skip to update settings in case of InfiniteWP that could be in a different country
				if ( isset( $_SERVER['HTTP_X_REQUESTED_FROM'] ) && FALSE !== strpos( $_SERVER['HTTP_X_REQUESTED_FROM'], 'InfiniteWP' ) )
					continue;

				// update matching rule immediately
				if ( $immediate && 'done' !== get_transient( IP_Geo_Block::CRON_NAME ) ) {
					$validate = $context->validate_ip( 'admin', $settings );

					if ( 'ZZ' === $validate['code'] )
						continue;

					// matching rule should be reset when blocking happens
					if ( 'passed' !== $validate['result'] )
						$settings['matching_rule'] = -1;

					// setup country code in whitelist if it needs to be initialized
					if ( -1 === (int)$settings['matching_rule'] ) {
						$settings['matching_rule'] = 0; // white list

						// when the country code doesn't exist in whitelist, then add it
						if ( FALSE === strpos( $settings['white_list'], $validate['code'] ) )
							$settings['white_list'] .= ( $settings['white_list'] ? ',' : '' ) . $validate['code'];
					}

					// update option settings
					self::update_settings( $settings, array( 'matching_rule', 'white_list' ) );

					// finished to update matching rule
					set_transient( IP_Geo_Block::CRON_NAME, 'done', 5 * MINUTE_IN_SECONDS );

					// trigger update action
					do_action( IP_Geo_Block::PLUGIN_NAME . '-db-updated', $settings, $validate['code'] );
				}
			}
		}

		return isset( $res ) ? $res : NULL;
	}

	/**
	 * Update setting data according to the site type.
	 *
	 */
	private static function update_settings( $src, $keys = array() ) {
		// for multisite
		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
			global $wpdb;
			$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" );

			foreach ( $blog_ids as $id ) {
				switch_to_blog( $id );
				$dst = IP_Geo_Block::get_option( FALSE );

				foreach ( $keys as $key ) {
					$dst[ $key ] = $src[ $key ];
				}

				IP_Geo_Block::update_option( $dst, FALSE );
				restore_current_blog();
			}
		}

		// for single site
		else {
			IP_Geo_Block::update_option( $src );
		}
	}

	/**
	 * Extract ip address from transient API.
	 *
	 */
	public static function extract_ip( $ip ) {
		return filter_var(
			$ip_self = get_transient( IP_Geo_Block::CRON_NAME ), FILTER_VALIDATE_IP
		) ? $ip_self : $ip;
	}

	/**
	 * Kick off a cron job to download database immediately in background on activation.
	 *
	 */
	public static function start_update_db( $settings, $immediate = TRUE ) {
		// updating should be done by main site when this plugin is activated for network
		if ( is_main_site() || ! is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) )
			self::schedule_cron_job( $settings['update'], NULL, $immediate );
	}

	public static function stop_update_db() {
		wp_clear_scheduled_hook( IP_Geo_Block::CRON_NAME, array( FALSE ) ); // @since 2.1.0

		// wait until updating has finished to avoid race condition with IP_Geo_Block_Opts::install_api()
		$time = 0;
		while ( ( $stat = get_transient( IP_Geo_Block::CRON_NAME ) ) && 'done' !== $stat ) {
			sleep( 1 );

			if ( ++$time > 5 * MINUTE_IN_SECONDS ) {
				break;
			}
		}
	}

	/**
	 * Kick off a cron job to garbage collection for IP address cache.
	 *
	 */
	public static function exec_cache_gc( $settings ) {
		self::stop_cache_gc();

		IP_Geo_Block_Logs::delete_expired( array(
			min( 365, max( 1, (int)$settings['validation']['explogs'] ) ) * DAY_IN_SECONDS,
			(int)$settings['cache_time']
		) );

		self::start_cache_gc( $settings );
	}

	public static function start_cache_gc( $settings = FALSE ) {
		if ( ! wp_next_scheduled( IP_Geo_Block::CACHE_NAME ) ) {
			$settings or $settings = IP_Geo_Block::get_option();
			wp_schedule_single_event( time() + max( $settings['cache_time_gc'], MINUTE_IN_SECONDS ), IP_Geo_Block::CACHE_NAME );
		}
	}

	public static function stop_cache_gc() {
		wp_clear_scheduled_hook( IP_Geo_Block::CACHE_NAME ); // @since 2.1.0
	}

	/**
	 * Decompresses gz archive and output to the file.
	 *
	 * @param string $src full path to the downloaded file.
	 * @param string $dst full path to extracted file.
	 * @return TRUE or array of error code and message.
	 */
	private static function gzfile( $src, $dst ) {
		try {
			if ( FALSE === ( $gz = gzopen( $src, 'r' ) ) )
				throw new Exception(
					sprintf( __( 'Unable to read <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $src )
				);

			if ( FALSE === ( $fp = @fopen( $dst, 'cb' ) ) )
				throw new Exception(
					sprintf( __( 'Unable to write <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $filename )
				);

			if ( ! flock( $fp, LOCK_EX ) )
				throw new Exception(
					sprintf( __( 'Can\'t lock <code>%s</code>. Please try again after a while.', 'ip-geo-block' ), $filename )
				);

			ftruncate( $fp, 0 ); // truncate file

			// same block size in wp-includes/class-http.php
			while ( $data = gzread( $gz, 4096 ) ) {
				fwrite( $fp, $data, strlen( $data ) );
			}
		}

		catch ( Exception $e ) {
			$err = array(
				'code'    => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}

		if ( ! empty( $fp ) ) {
			fflush( $fp );          // flush output before releasing the lock
			flock ( $fp, LOCK_UN ); // release the lock
			fclose( $fp );
		}

		return empty( $err ) ? TRUE : $err;
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
	public static function download_zip( $url, $args, $files, $modified ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

		// get extension
		$ext = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );
		if ( 'tar' === strtolower( pathinfo( pathinfo( $url, PATHINFO_FILENAME ), PATHINFO_EXTENSION ) ) )
			$ext = 'tar';

		// check file (1st parameter includes absolute path in case of array)
		$filename = is_array( $files ) ? $files[0] : (string)$files;
		if ( ! $fs->exists( $filename ) )
			$modified = 0;

		// set 'If-Modified-Since' request header
		$args += array(
			'headers'  => array(
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Encoding' => 'gzip, deflate',
				'If-Modified-Since' => gmdate( DATE_RFC1123, (int)$modified ),
			),
		);

		// fetch file and get response code & message
		if ( isset( $args['method'] ) && 'GET' === $args['method'] )
			$src = wp_remote_get ( ( $url = esc_url_raw( $url ) ), $args );
		else
			$src = wp_remote_head( ( $url = esc_url_raw( $url ) ), $args );

		if ( is_wp_error( $src ) )
			return array(
				'code' => $src->get_error_code(),
				'message' => $src->get_error_message(),
			);

		$code = wp_remote_retrieve_response_code   ( $src );
		$mesg = wp_remote_retrieve_response_message( $src );
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
				'message' => $code.' '.$mesg,
			);

		try {
			// in case that the server which does not support HEAD method
			if ( isset( $args['method'] ) && 'GET' === $args['method'] ) {
				$data = wp_remote_retrieve_body( $src );

				if ( 'gz' === $ext ) {
					if ( function_exists( 'gzdecode') ) { // @since PHP 5.4.0
						if ( FALSE === $fs->put_contents( $filename, gzdecode( $data ) ) )
							throw new Exception(
								sprintf( __( 'Unable to write <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $filename )
							);
					}

					else {
						$src = get_temp_dir() . basename( $url ); // $src should be removed
						$fs->put_contents( $src, $data );
						if ( TRUE !== ( $ret = self::gzfile( $src, $filename ) ) ) {
							$err = $ret;
						}
					}
				}

				elseif ( 'tar' === $ext && class_exists( 'PharData', FALSE ) ) { // @since PECL phar 2.0.0
					$name = wp_remote_retrieve_header( $src, 'content-disposition' );
					$name = explode( 'filename=', $name );
					$name = array_pop( $name ); // e.g. GeoLite2-Country_20180102.tar.gz
					$src  = ( $tmp = get_temp_dir() ) . $name; // $src should be removed

					// CVE-2015-6833: A directory traversal when extracting ZIP files could be used to overwrite files
					// outside of intended area via a `..` in a ZIP archive entry that is mishandled by extractTo().
					if ( $fs->put_contents( $src, $data ) ) {
						$data = new PharData( $src, FilesystemIterator::SKIP_DOTS ); // get archives

						// make the list of contents to be extracted from archives.
						// when the list doesn't match the contents in archives, extractTo() may be crushed on windows.
						$dst = $data->getSubPathname(); // e.g. GeoLite2-Country_20180102
						foreach ( $files as $key => $val ) {
							$files[ $key ] = $dst.'/'.basename( $val );
						}

						// extract specific files from archives into temporary directory and copy it to the destination.
						$data->extractTo( $tmp .= $dst, $files /* NULL */, TRUE ); // $tmp should be removed

						// copy extracted files to Geolocation APIs directory
						$dst = dirname( $filename );
						foreach ( $files as $val ) {
							// should the destination be exclusive with LOCK_EX ?
							// $fs->put_contents( $dst.'/'.basename( $val ), $fs->get_contents( $tmp.'/'.$val ) );
							$fs->copy( $tmp.'/'.$val, $dst.'/'.basename( $val ), TRUE );
						}
					}
				}
			}

			// downloaded and unzip
			else {
				// download file
				$src = download_url( $url );

				if ( is_wp_error( $src ) )
					throw new Exception(
						$src->get_error_code() . ' ' . $src->get_error_message()
					);

				// unzip file
				if ( 'gz' === $ext ) {
					if ( TRUE !== ( $ret = self::gzfile( $src, $filename ) ) ) {
						$err = $ret;
					}
				}

				elseif ( 'zip' === $ext && class_exists( 'ZipArchive', FALSE ) ) {
					$tmp = get_temp_dir(); // @since 2.5
					$ret = $fs->unzip_file( $src, $tmp ); // @since 2.5

					if ( is_wp_error( $ret ) )
						throw new Exception(
							$ret->get_error_code() . ' ' . $ret->get_error_message()
						);

					if ( FALSE === ( $data = $fs->get_contents( $tmp .= basename( $filename ) ) ) )
						throw new Exception(
							sprintf( __( 'Unable to read <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $tmp )
						);

					if ( FALSE === $fs->put_contents( $filename, $data ) )
						throw new Exception(
							sprintf( __( 'Unable to write <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $filename )
						);
				}

				else {
					throw new Exception( __( 'gz or zip is not supported on your system.', 'ip-geo-block' ) );
				}
			}
		}

		// error handler
		catch ( Exception $e ) {
			$err = array(
				'code'    => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}

		! empty  ( $gz  ) and gzclose( $gz );
		! empty  ( $tmp ) and $fs->delete( $tmp, TRUE ); // should be removed recursively in case of directory
		is_string( $src ) && $fs->is_file( $src ) and $fs->delete( $src );

		return empty( $err ) ? array(
			'code' => $code,
			'message' => sprintf( __( 'Last update: %s', 'ip-geo-block' ), IP_Geo_Block_Util::localdate( $modified ) ),
			'filename' => $filename,
			'modified' => $modified,
		) : $err;
	}

}