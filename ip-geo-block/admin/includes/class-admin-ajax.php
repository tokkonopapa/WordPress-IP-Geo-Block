<?php

// Test for restore_network()
define( 'TEST_RESTORE_NETWORK', FALSE );
define( 'TEST_NETWORK_BLOG_COUNT', 30 );

class IP_Geo_Block_Admin_Ajax {

	/**
	 * Admin ajax sub functions
	 *
	 * @param string $which name of the geolocation api provider (should be validated by whitelist)
	 */
	public static function search_ip( $which ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';

		// check format
		if ( filter_var( $ip = trim( $_POST['ip'] ), FILTER_VALIDATE_IP ) ) {
			// get option settings and compose request headers
			$options = IP_Geo_Block::get_option();
			$tmp     = IP_Geo_Block::get_request_headers( $options );

			// create object for provider and get location
			if ( $geo = IP_Geo_Block_API::get_instance( $which, $options ) )
				$res = $geo->get_location( $ip, $tmp );
			else
				$res = array( 'errorMessage' => 'Unknown service.' );
		} else {
			$res = array( 'errorMessage' => 'Invalid IP address.' );
		}

		if ( empty( $res['errorMessage'] ) ) {
			if ( $geo = IP_Geo_Block_API::get_instance( 'Maxmind', $options ) ) {
				$tmp = microtime( TRUE );
				$geo = $geo->get_location( $ip, array( 'ASN' => TRUE ) );
				$tmp = microtime( TRUE ) - $tmp;

				$res['AS number']  = isset( $geo['ASN'] ) ? esc_html( $geo['ASN'] ) : '';
				$res['AS number'] .= sprintf( ' (%.1f [msec])', $tmp * 1000.0 );
			}

			$tmp = microtime( TRUE );
			$res['host (DNS)'] = esc_html( IP_Geo_Block_Lkup::gethostbyaddr( $ip ) );
			$tmp = microtime( TRUE ) - $tmp;
			$res['host (DNS)'] .= sprintf( ' (%.1f [msec])', $tmp * 1000.0 );
		}

		return $res;
	}

	/**
	 * Get country code from providers
	 *
	 * @param string $which 'ip_client' or 'ip_server' (not in use)
	 */
	public static function scan_country( $which ) {
		// scan all the country code using selected APIs
		$options   = IP_Geo_Block::get_option();
		$ip        = IP_Geo_Block::get_ip_address();
		$args      = IP_Geo_Block::get_request_headers( $options );
		$type      = IP_Geo_Block_Provider::get_providers( 'type', FALSE, FALSE );
		$providers = IP_Geo_Block_Provider::get_valid_providers( $options, FALSE, FALSE );

		$res['IP address'] = esc_html( $ip );

		foreach ( $providers as $provider ) {
			if ( $geo = IP_Geo_Block_API::get_instance( $provider, $options ) ) {
				$ret = $geo->get_location( $ip, $args );
				$res[ $provider ] = array(
					'type' => $type[ $provider ],
					'code' => esc_html(
						FALSE === $ret ? __( 'n/a', 'ip-geo-block' ) : (
						! empty( $ret['errorMessage'] ) ? $ret['errorMessage'] : (
						! empty( $ret['countryCode' ] ) ? $ret['countryCode' ] :
						__( 'n/a', 'ip-geo-block' ) ) )
					),
				);
			}
		}

		return $res;
	}

	/**
	 * Insert array
	 *
	 */
	private static function array_insert( &$base_array, $insert_value, $position = null ) {
		if ( ! is_array( $insert_value ) )
			$insert_value = array( $insert_value );

		$position = is_null( $position ) ? count( $base_array ) : intval( $position );
		$base_array = array_merge( array_splice( $base_array, 0, $position ), $insert_value, $base_array );
	}

	/**
	 * Export logs from MySQL DB
	 *
	 * @param string $which 'comment', 'xmlrpc', 'login', 'admin' or 'public'
	 */
	public static function export_logs( $which ) {
		$csv = '#';
		$time = $_SERVER['REQUEST_TIME'];

		$csv .= implode( ',', array(
			__( 'Time',         'ip-geo-block' ),
			__( 'IP address',   'ip-geo-block' ),
			__( 'Code',         'ip-geo-block' ),
			__( 'ASN',          'ip-geo-block' ),
			__( 'Target',       'ip-geo-block' ),
			__( 'Result',       'ip-geo-block' ),
			__( 'Request',      'ip-geo-block' ),
			__( 'User agent',   'ip-geo-block' ),
			__( 'HTTP headers', 'ip-geo-block' ),
			__( '$_POST data',  'ip-geo-block' ),
		) ) . PHP_EOL;

		foreach ( IP_Geo_Block_Logs::restore_logs( $which ) as $data ) {
			$hook = array_shift( $data ); // remove `No`
			$hook = array_shift( $data ); // extract `hook`
			self::array_insert( $data, $hook, 3 );
			$data[0] = IP_Geo_Block_Util::localdate( $data[0], 'Y-m-d H:i:s' );
			$data[7] = str_replace( ',', '‚', $data[7] ); // &#044; --> &#130;
			$data[8] = str_replace( ',', '‚', $data[8] ); // &#044; --> &#130;
			$data[9] = str_replace( ',', '‚', $data[9] ); // &#044; --> &#130;
			$csv .= implode( ',', $data ) . PHP_EOL;
		}

		// Send as file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_NAME . '_' . IP_Geo_Block_Util::localdate( $time, 'Y-m-d_H-i-s' ) . '.csv"' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
	}

	/**
	 * Format logs from rows array
	 *
	 */
	private static function format_logs( $rows ) {
		$options = IP_Geo_Block::get_option();
		$res = array();

		foreach ( $rows as $row ) {
			array_shift( $row ); // remove `No`
			$row = array_map( 'esc_html', $row );

			if ( $options['anonymize'] && FALSE === strpos( $row[2], '***' ) ) {
				$row[2] =  IP_Geo_Block_Util::anonymize_ip( $row[2], TRUE  );
				$row[8] =  IP_Geo_Block_Util::anonymize_ip( $row[8], FALSE );
			}

			$res[] = array(
				/*  0 Checkbox     */ '',
				/*  1 Time (raw)   */ $row[1],
				/*  2 Date         */ '&rsquo;' . IP_Geo_Block_Util::localdate( $row[1], 'y-m-d H:i:s' ),
				/*  3 IP address   */ '<span><a href="#!">' . $row[2] . '</a></span>',
				/*  4 Country code */ '<span>' . $row[3] . '</span>',
				/*  5 AS number    */ '<span>' . $row[5] . '</span>',
				/*  6 Target       */ '<span>' . $row[0] . '</span>',
				/*  7 Status       */ '<span>' . $row[4] . '</span>',
				/*  8 Request      */ '<span>' . $row[6] . '</span>',
				/*  9 User agent   */ '<span>' . $row[7] . '</span>',
				/* 10 HTTP headers */ '<span>' . $row[8] . '</span>',
				/* 11 $_POST data  */ '<span>' . $row[9] . '</span>',
			);
		}

		return $res;
	}

	/**
	 * Restore logs from MySQL DB
	 *
	 * @param string $which 'comment', 'xmlrpc', 'login', 'admin' or 'public'
	 */
	public static function restore_logs( $which ) {
		return array( 'data' => self::format_logs(
			apply_filters( IP_Geo_Block::PLUGIN_NAME . '-logs', IP_Geo_Block_Logs::restore_logs( $which ) )
		) ); // DataTables requires `data`
	}

	/**
	 * Catch and release the authority for live log
	 *
	 * @return TRUE or WP_Error
	 */
	public static function catch_live_log() {
		$user = IP_Geo_Block_Util::get_current_user_id();
		$auth = IP_Geo_Block::get_live_log();

		if ( $auth === FALSE || $user === (int)$auth ) {
			set_transient( IP_Geo_Block::PLUGIN_NAME . '-live-log', $user, IP_Geo_Block_Admin::TIMEOUT_LIVE_UPDATE );
			return TRUE;
		} else {
			$info = get_userdata( $auth );
			return new WP_Error( 'Warn', sprintf( __( 'The user %s (user ID: %d) is in use.', 'ip-geo-block' ), $info->user_login, $auth ) );
		}
	}

	public static function release_live_log() {
		if ( is_wp_error( $result = self::catch_live_log() ) )
			return $result;

		delete_transient( IP_Geo_Block::PLUGIN_NAME . '-live-log' );
		return TRUE;
	}

	/**
	 * Restore and reset live log in SQLite
	 *
	 */
	public static function reset_live_log() {
		return IP_Geo_Block_Logs::reset_sqlite_db();
	}

	public static function restore_live_log( $hook, $settings ) {
		if ( is_wp_error( $ret = self::catch_live_log() ) )
			return $ret;

		if ( ! is_wp_error( $res = IP_Geo_Block_Logs::restore_live_log( $hook, $settings ) ) )
			return array( 'data' => self::format_logs( apply_filters( IP_Geo_Block::PLUGIN_NAME . '-logs', $res ) ) );
		else
			return array( 'error' => $res->get_error_message() );
	}

	/**
	 * Export IP address in cache from MySQL DB
	 *
	 */
	public static function export_cache( $anonymize = TRUE ) {
		$csv = '#';
		$time = $_SERVER['REQUEST_TIME'];

		$csv .= implode( ',', array(
			__( 'IP address',      'ip-geo-block' ),
			__( 'Code',            'ip-geo-block' ),
			__( 'ASN',             'ip-geo-block' ),
			__( 'Host name',       'ip-geo-block' ),
			__( 'Target',          'ip-geo-block' ),
			__( 'Failure / Total', 'ip-geo-block' ),
			__( 'Elapsed[sec]',    'ip-geo-block' ),
		) ) . PHP_EOL;

		foreach ( IP_Geo_Block_Logs::restore_cache() as $key => $val ) {
			if ( $anonymize ) {
				$key         = IP_Geo_Block_Util::anonymize_ip( $key,         TRUE  );
				$val['host'] = IP_Geo_Block_Util::anonymize_ip( $val['host'], FALSE );
			}

			$csv .= implode( ',', array(
				/* IP address      */ $key,
				/* Country code    */ $val['code'],
				/* AS number       */ $val['asn' ],
				/* Host name       */ $val['host'],
				/* Target          */ $val['hook'],
				/* Failure / Total */ sprintf( '%d / %d', (int)$val['fail'], (int)$val['reqs'] ),
				/* Elapsed[sec]    */ $time - (int)$val['time'],
			) ) . PHP_EOL;
		}

		// Send as file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_NAME . '_' . IP_Geo_Block_Util::localdate( $time, 'Y-m-d_H-i-s' ) . '.csv"' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
	}

	/**
	 * Restore cache from MySQL DB
	 *
	 */
	public static function restore_cache( $anonymize = TRUE ) {
		$res = array();
		$time = $_SERVER['REQUEST_TIME'];

		foreach ( IP_Geo_Block_Logs::restore_cache() as $key => $val ) {
			if ( $anonymize ) {
				$key         = IP_Geo_Block_Util::anonymize_ip( $key,         TRUE  );
				$val['host'] = IP_Geo_Block_Util::anonymize_ip( $val['host'], FALSE );
			}

			$res[] = array(
				/* Checkbox     */ '',
				/* IP address   */ '<span><a href="#!" data-hash="' . esc_attr( $val['hash'] ). '">' . esc_html( $key ) . '</a></span>',
				/* Country code */ '<span>' . esc_html( $val['code'] ) . '</span>',
				/* AS number    */ '<span>' . esc_html( $val['asn' ] ) . '</span>',
				/* Host name    */ '<span>' . esc_html( $val['host'] ) . '</span>',
				/* Target       */ '<span>' . esc_html( $val['hook'] ) . '</span>',
				/* Fails/Calls  */ '<span>' . sprintf( '%d / %d', (int)$val['fail'], (int)$val['reqs'] ) . '</span>',
				/* Elapsed[sec] */ '<span>' . ( $time - (int)$val['time'] ) . '</span>',
			);
		}

		return array( 'data' => $res ); // DataTables requires `data`
	}

	/**
	 * Get the number of active sites on your installation
	 */
	public static function get_network_count() {
if ( ! defined( 'TEST_RESTORE_NETWORK' ) or ! TEST_RESTORE_NETWORK ):
		if ( is_plugin_active_for_network( IP_GEO_BLOCK_BASE ) ) {
			return get_blog_count(); // get_sites( array( 'count' => TRUE ) ) @since 4.6
		} else {
			$count = 0;
			global $wpdb;
			foreach ( $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" ) as $id ) {
				switch_to_blog( $id );
				is_plugin_active( IP_GEO_BLOCK_BASE ) and ++$count;
				restore_current_blog();
			}
			return $count;
		}
else:
		return TEST_NETWORK_BLOG_COUNT;
endif;
	}

	/**
	 * Restore blocked per target in logs
	 *
	 * @param string $duration the number of selected duration
	 * @param int    $offset the start of blog to restore logs
	 * @param int    $length the number of blogs to restore logs from $offset
	 * @param int    $literal JavaScript literal notation
	 */
	public static function restore_network( $duration, $offset = 0, $length = 100, $literal = FALSE ) {
		$zero = array(
			'comment' => 0,
			'xmlrpc'  => 0,
			'login'   => 0,
			'admin'   => 0,
			'public'  => 0,
		);

		$time = array(
			YEAR_IN_SECONDS,    // All
			HOUR_IN_SECONDS,    // Latest 1 hour
			DAY_IN_SECONDS,     // Latest 24 hours
			WEEK_IN_SECONDS,    // Latest 1 week
			30 * DAY_IN_SECONDS // Latest 1 month (MONTH_IN_SECONDS is since WP 4.4+)
		);

		$i = 0;
		$length += $offset;
		$json = $count = array();
		$duration = isset( $time[ $duration ] ) ? $time[ $duration ] : $time[0];

if ( ! defined( 'TEST_RESTORE_NETWORK' ) or ! TEST_RESTORE_NETWORK ):
		global $wpdb;
		foreach ( $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" ) as $id ) {
			switch_to_blog( $id );

			if ( is_plugin_active( IP_GEO_BLOCK_BASE ) && $offset <= $i && $i < $length ) {
				// array of ( `time`, `ip`, `hook`, `code`, `method`, `data` )
				$name = get_bloginfo( 'name' );
				$logs = IP_Geo_Block_Logs::get_recent_logs( $duration );

				$count[ $name ] = $zero;

				// Blocked hooks by time
				foreach( $logs as $val ) {
					++$count[ $name ][ $val['hook'] ];
				}

				// link over network
				$count[ $name ]['link'] = esc_url( add_query_arg(
					array( 'page' => IP_Geo_Block::PLUGIN_NAME ),
					admin_url( 'options-general.php' )
				) );
			}

			restore_current_blog();
		}
else:
		for ( $i = 0; $i < TEST_NETWORK_BLOG_COUNT; ++$i ) {
			if ( $offset <= $i && $i < $length ) {
				$count[ 'site-' . $i ] = array(
					$i, $i, $i, $i, $i,
					esc_url( add_query_arg(
						array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1 ),
						admin_url( 'options-general.php' )
					) )
				);
			}
		}
endif; // TEST_RESTORE_NETWORK

		if ( $literal ) {
			// https://stackoverflow.com/questions/17327022/create-line-chart-using-google-chart-api-and-json-for-datatable
			foreach ( $count as $key => $val ) {
				$json['rows'][]['c'] = array(
					array( 'v' => $key ),
					array( 'v' => $val['comment'] ),
					array( 'v' => $val['xmlrpc' ] ),
					array( 'v' => $val['login'  ] ),
					array( 'v' => $val['admin'  ] ),
					array( 'v' => $val['public' ] ),
					array( 'v' => $val['link'   ] ),
				);
			}
		}

		else {
			// https://developers.google.com/chart/interactive/docs/datatables_dataviews#arraytodatatable
			foreach ( $count as $key => $val ) {
				array_push( $json, array_merge( array( $key ), array_values( $val ) ) );
			}
		}

		return $json;
	}

	/**
	 * Validate json from the client and respond safe data
	 *
	 */
	public static function validate_settings( $parent ) {
		// restore escaped characters (see wp_magic_quotes() in wp-includes/load.php)
		$json = json_decode(
			str_replace(
				array( '\\"', '\\\\', "\'" ),
				array( '"',   '\\',   "'"  ),
				isset( $_POST['data'] ) ? $_POST['data'] : ''
			), TRUE
		);

		if ( NULL === $json )
			wp_die( 'Illegal JSON format.', '', array( 'response' => 500, 'back_link' => TRUE ) ); // @Since 2.0.4

		// Convert json to setting data
		$input = self::json_to_settings( $json );

		// Integrate posted data into current settings because if can be a part of hole data
		$input = $parent->array_replace_recursive(
			$parent->preprocess_options( IP_Geo_Block::get_option(), IP_Geo_Block::get_default() ), $input
		);

		// Validate options and convert to json
		$output = $parent->sanitize_options( $input );
		$json = self::json_unsafe_encode( self::settings_to_json( $output ) );

		mbstring_binary_safe_encoding(); // @since 3.7.0
		$length = strlen( $json );
		reset_mbstring_encoding(); // @since 3.7.0

		// Send json as file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_NAME . '-settings.json"' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Content-Length: ' . $length );
		echo $json;
	}

	/**
	 * Convert json associative array to settings array
	 *
	 */
	private static function json_to_settings( $input ) {
		$settings = $m = array();
		$prfx = IP_Geo_Block::OPTION_NAME;

		try {
			foreach ( $input as $key => $val ) {
				if ( preg_match( "/${prfx}\[(.+?)\](?:\[(.+?)\](?:\[(.+?)\])?)?/", $key, $m ) ) {
					switch ( count( $m ) ) {
					  case 2:
						$settings[ $m[1] ] = $val;
						break;

					  case 3:
						$settings[ $m[1] ][ $m[2] ] = $val;
						break;

					  case 4:
						if ( is_numeric( $m[3] ) ) {
							if ( empty( $settings[ $m[1] ][ $m[2] ] ) )
								$settings[ $m[1] ][ $m[2] ] = 0;
							$settings[ $m[1] ][ $m[2] ] |= $val;
						} else { // [*]:checkbox
							$settings[ $m[1] ][ $m[2] ][ $m[3] ] = $val;
						}
						break;

					  default:
						throw new Exception();
					}
				}
			}
		}

		catch ( Exception $e ) { // should be returned as ajax response
			wp_die( sprintf( __( 'illegal format at %s. Please delete the corresponding line and try again.', 'ip-geo-block' ), print_r( @$m[0], TRUE ) ) );
		}

		return $settings;
	}

	/**
	 * Convert settings array to json associative array
	 *
	 */
	public static function settings_to_json( $input, $overwrite = TRUE ) {
		// [*]:list of checkboxes, [$]:comma separated text to array, [%]:associative array
		$keys = array(
			'[version]',
			'[matching_rule]',
			'[white_list]',
			'[black_list]',
			'[extra_ips][white_list]',
			'[extra_ips][black_list]',
			'[anonymize]',
			'[restrict_api]',  // 3.0.13
			'[simulate]',      // 3.0.14
			'[signature]',
			'[login_fails]',
			'[response_code]',
			'[response_msg]',       // 3.0.0
			'[redirect_uri]',       // 3.0.0
			'[validation][timing]', // 2.2.9
			'[validation][proxy]',
			'[validation][comment]',
			'[validation][xmlrpc]',
			'[validation][login]',
			'[login_action][login]',        // 2.2.8
			'[login_action][register]',     // 2.2.8
			'[login_action][resetpass]',    // 2.2.8
			'[login_action][lostpassword]', // 2.2.8
			'[login_action][postpass]',     // 2.2.8
			'[validation][admin][1]',
			'[validation][admin][2]',
			'[validation][ajax][1]',
			'[validation][ajax][2]',
			'[validation][plugins]',
			'[validation][themes]',
			'[validation][includes]',    // 3.0.0
			'[validation][uploads]',     // 3.0.0
			'[validation][languages]',   // 3.0.0
			'[validation][public]',      // 3.0.0
			'[validation][restapi]',     // 3.0.3
			'[validation][mimetype]',    // 3.0.3
			'[rewrite][plugins]',
			'[rewrite][themes]',
			'[rewrite][includes]',       // 3.0.0
			'[rewrite][uploads]',        // 3.0.0
			'[rewrite][languages]',      // 3.0.0
			'[exception][plugins][*]',   // 2.2.5
			'[exception][themes][*]',    // 2.2.5
			'[exception][admin][$]',     // 3.0.0
			'[exception][public][$]',    // 3.0.0
			'[exception][includes][$]',  // 3.0.0
			'[exception][uploads][$]',   // 3.0.0
			'[exception][languages][$]', // 3.0.0
			'[exception][restapi][$]',   // 3.0.3
			'[public][matching_rule]',   // 3.0.0
			'[public][white_list]',      // 3.0.0
			'[public][black_list]',      // 3.0.0
			'[public][target_rule]',     // 3.0.0
			'[public][target_pages][$]', // 3.0.0
			'[public][target_posts][$]', // 3.0.0
			'[public][target_cates][$]', // 3.0.0
			'[public][target_tags][$]',  // 3.0.0
			'[public][ua_list]',         // 3.0.0
			'[public][dnslkup]',         // 3.0.3
			'[public][response_code]',   // 3.0.3
			'[public][response_msg]',    // 3.0.3
			'[public][redirect_uri]',    // 3.0.3
			'[public][behavior]',        // 3.0.10
			'[behavior][time]',          // 3.0.10
			'[behavior][view]',          // 3.0.10
			'[save_statistics]',
			'[validation][recdays]',     // 2.2.9
			'[validation][reclogs]',
			'[validation][maxlogs]',
			'[validation][explogs]',     // 3.0.12
			'[validation][postkey]',
			'[update][auto]',
			'[cache_time_gc]',           // 3.0.0
			'[cache_hold]',
			'[cache_time]',
			'[comment][pos]',
			'[comment][msg]',
			'[clean_uninstall]',
			'[api_key][GoogleMap]',      // 2.2.7
			'[network_wide]',            // 3.0.0
			'[mimetype][white_list][%]', // 3.0.3
			'[mimetype][black_list]',    // 3.0.3
			'[mimetype][capability][$]', // 3.0.4
			'[Maxmind][use_asn]',        // 3.0.4
			'[live_update][in_memory]',  // 3.0.5
			'[monitor][updated_option]',             // 3.0.18
			'[monitor][update_site_option]',         // 3.0.18
			'[metadata][pre_update_option][$]',      // 3.0.17
			'[metadata][pre_update_site_option][$]', // 3.0.17
		);
		$json = array();
		$prfx = IP_Geo_Block::OPTION_NAME;

		// add providers
		foreach ( array_keys( IP_Geo_Block_Provider::get_providers( 'key' ) ) as $key ) {
			$keys[] = '[providers][' . $key . ']';
		}

		foreach ( $keys as $key ) {
			if ( preg_match( "/\[(.+?)\](?:\[(.+?)\](?:\[(.+?)\])?)?/", $key, $m ) ) {
				switch ( count( $m ) ) {
				  case 2:
					if ( isset( $input[  $m[1]  ] ) ) {
						$json[ $prfx.'['.$m[1].']' ] = strval( $input[ $m[1] ] );
					}
					break;

				  case 3:
					if ( '%' === $m[2] ) { // [%]:associative array
						foreach ( isset( $input[ $m[1] ] ) ? $input[ $m[1] ] : array() as $key => $val ) {
							$json[ $prfx.'['.$m[1].']['.$key.']' ] = $val;
						}
						break;
					}
					if ( isset( $input[  $m[1]  ][  $m[2]  ] ) || $overwrite ) {
						$json[ $prfx.'['.$m[1].']['.$m[2].']' ] = (
							isset(  $input[ $m[1] ][ $m[2] ] ) &&
							'@' !== $input[ $m[1] ][ $m[2] ] ?
							strval( $input[ $m[1] ][ $m[2] ] ) : ''
						);
					}
					break;

				  case 4:
					if ( is_numeric( $m[3] ) ) {
						if ( isset( $input[  $m[1]  ][  $m[2]  ] ) )
							$json[ $prfx.'['.$m[1].']['.$m[2].']'.'['.$m[3].']' ] =
							strval( $input[  $m[1]  ][  $m[2]  ] ) & (int)$m[3];
					}
					elseif ( isset( $input[ $m[1] ][ $m[2] ] ) ) {
						if ( '*' === $m[3] ) { // [*]:checkbox
							foreach ( $input[ $m[1] ][ $m[2] ] as $val ) {
								$json[ $prfx.'['.$m[1].']['.$m[2].']'.'['.$val.']' ] = '1';
							}
						} elseif ( '%' === $m[3] ) { // [%]:associative array
							foreach ( $input[ $m[1] ][ $m[2] ] as $key => $val ) {
								$json[ $prfx.'['.$m[1].']['.$m[2].']'.'['.$key.']' ] = $val;
							}
						} elseif ( is_array( $input[ $m[1] ][ $m[2] ] ) ) { // [$]:comma separated text to array
							$json[ $prfx.'['.$m[1].']['.$m[2].']' ] = implode( ',', $input[ $m[1] ][ $m[2] ] );
						}
					}
					break;
				}
			}
		}

		return $json;
	}

	/**
	 * Make preferred settings with formatted json
	 *
	 */
	public static function preferred_to_json() {
		return self::settings_to_json(
			array(
				'login_fails'     => 10,      // Limited number of login attempts
				'validation'      => array(   // Action hook for validation
				    'comment'     => TRUE,    // Validate on comment post
				    'login'       => 1,       // Validate on login
				    'admin'       => 3,       // Validate on admin (1:country 2:ZEP)
				    'ajax'        => 3,       // Validate on ajax/post (1:country 2:ZEP)
				    'xmlrpc'      => 1,       // Validate on xmlrpc (1:country 2:close)
				    'postkey'     => 'action,comment,log,pwd,FILES', // Keys in $_POST and $_FILES
				    'plugins'     => 2,       // Validate on wp-content/plugins
				    'themes'      => 2,       // Validate on wp-content/themes
				    'timing'      => 1,       // 0:init, 1:mu-plugins, 2:drop-in
				    'mimetype'    => 1,       // 0:disable, 1:white_list, 2:black_list
				),
				'signature'       => "../,/wp-config.php,/passwd\ncurl,wget,eval,base64\nselect:.5,where:.5,union:.5\nload_file:.5,create:.6,password:.4",
				'rewrite'         => array(   // Apply rewrite rule
				    'plugins'     => TRUE,    // for wp-content/plugins
				    'themes'      => TRUE,    // for wp-content/themes
				),
			),
			FALSE // should not overwrite the existing parameters
		);
	}

	// Encode json without JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	// Note: It should not be rendered via jQuery .html() at client side
	private static function json_unsafe_encode( $data ) {
		if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
			$opts = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
			if ( function_exists( 'wp_json_encode' ) ) // @since 4.1.0
				$json = wp_json_encode( $data, $opts );
			else
				$json = json_encode( $data, $opts );
		}

		else { // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES are not supported in PHP 5.3 and under
			$json = self::json_unescaped_unicode( $data );
			$json = preg_replace(
				array( '!{"!',              '!":!', '!("?),"!',            '!"}!',          '!\\\\/!' ),
				array( '{'.PHP_EOL.'    "', '": ',  '$1,'.PHP_EOL.'    "', '"'.PHP_EOL.'}', '/'       ),
				$json
			);
		}

		return $json;
	}

	// Fallback function for PHP 5.3 and under
	// @link https://qiita.com/keromichan16/items/5ff45a77fb0d48e046cc
	// @link https://stackoverflow.com/questions/16498286/why-does-the-php-json-encode-function-convert-utf-8-strings-to-hexadecimal-entit/
	private static function json_unescaped_unicode( $input ) {
		return preg_replace_callback(
			'/(?:\\\\u[0-9a-zA-Z]{4})++/',
			array( __CLASS__, 'convert_encoding' ),
			json_encode( $input )
		);
	}

	// Fallback function for PHP 5.3 and under
	private static function convert_encoding( $matches ) {
		return mb_convert_encoding(
			pack( 'H*', str_replace( '\\u', '', $matches[0] ) ), 'UTF-8', 'UTF-16'
		);
	}

	/**
	 * Get blocked action and pages
	 *
	 * @param string $which 'page', 'action', 'plugin', 'theme'
	 * @return array of the name of action/page, plugin or theme
	 */
	private static function get_blocked_queries( $which ) {
		$result = array();

		switch ( $which ) {
		  case 'page':
		  case 'action':
			$dir = IP_Geo_Block_Util::slashit( str_replace( site_url(), '', admin_url() ) ); /* `/wp-admin/` */

			foreach ( IP_Geo_Block_Logs::search_blocked_logs( 'method', $dir ) as $log ) {
				foreach ( array( 'method', 'data' ) as $key ) {
					if ( preg_match( '!' . $which . '=([\-\w]+)!', $log[ $key ], $matches ) ) {
						$result += array( $matches[1] => $which );
					}
				}
			}
			break;

		  case 'plugins':
		  case 'themes':
			// make a list of installed plugins/themes
			if ( 'plugins' === $which ) {
				$key = array();
				foreach ( get_plugins() as $pat => $log ) {
					$pat = explode( '/', $pat, 2 );
					$key[] = $pat[0];
				}
			} else {
				$key = wp_get_themes();
			}

			$dir = 'plugins' === $which ? plugins_url() : get_theme_root_uri();
			$dir = IP_Geo_Block_Util::slashit( str_replace( site_url(), '', $dir ) );
			$pat = preg_quote( $dir, '!' ); /* `/wp-content/(plugins|themes)/` */

			foreach ( IP_Geo_Block_Logs::search_blocked_logs( 'method', $dir ) as $log ) {
				if ( preg_match( '!' . $pat . '(.+?)/!', $log['method'], $matches ) && in_array( $matches[1], $key, TRUE ) ) {
					$result += array( $matches[1] => $which );
				}
			}
			break;
		}

		return $result;
	}

	/**
	 * Get slug in blocked requests for exceptions
	 *
	 */
	public static function find_exceptions( $target ) {
		switch ( $target ) {
		  case 'find-admin':
			$res = array();
			foreach ( array( 'action', 'page' ) as $which ) {
				$res += self::get_blocked_queries( $which );
			}
			return $res;

		  case 'find-plugins':
			return self::get_blocked_queries( 'plugins' );

		  case 'find-themes':
			return self::get_blocked_queries( 'themes' );
		}

		return array();
	}

	/**
	 * Get debug information related to WordPress
	 *
	 */
	public static function get_wp_info() {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

		// DNS reverse lookup
		$key = microtime( TRUE );
		$val = IP_Geo_Block_Lkup::gethostbyaddr( '8.8.8.8' );
		$key = microtime( TRUE ) - $key;

		// MySQL (supress WordPress error: Unknown system variable 'block_encryption_mode')
		$buf = @ini_set( 'output_buffering', 0 );
		$dsp = @ini_set( 'display_errors', 0 );
		$log = @ini_set( 'error_log', '/' . 'dev' . '/' . 'null' );
		$err = @error_reporting( 0 );
		$ver = $GLOBALS['wpdb']->get_var( 'SELECT @@GLOBAL.version' );
		$bem = $GLOBALS['wpdb']->get_var( 'SELECT @@GLOBAL.block_encryption_mode' ); // `aes-128-ecb` @since MySQL 5.6.17
		@ini_set( 'output_buffering', $buf );
		@ini_set( 'display_errors', $dsp );
		@ini_set( 'error_log', $log );
		@error_reporting( $err );

		// Human readable size, Proces owner
		// https://gist.github.com/mehdichaouch/341a151dd5f469002a021c9396aa2615
		// https://secure.php.net/manual/function.get-current-user.php#57624
		// https://secure.php.net/manual/function.posix-getpwuid.php#82387
		$siz = array( 'B', 'K', 'M', 'G', 'T', 'P' );
		$usr = function_exists( 'posix_getpwuid' ) ? posix_getpwuid( posix_geteuid() ) : array( 'name' => getenv( 'USERNAME' ) );

		// Server, PHP, WordPress
		$res = array_map( 'esc_html', array(
			'Server:'        => $_SERVER['SERVER_SOFTWARE'],
			'MySQL:'         => $ver . ( defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG && $bem ? ' (' . $bem . ')' : '' ),
			'PHP:'           => PHP_VERSION,
			'PHP SAPI:'      => php_sapi_name(),
			'Memory limit:'  => ini_get( 'memory_limit' ),
			'Peak usage:'    => @round( ( $m = memory_get_peak_usage() ) / pow( 1024, ( $i = floor( log( $m, 1024 ) ) ) ), 2 ) . $siz[ $i ],
			'WordPress:'     => $GLOBALS['wp_version'],
			'Multisite:'     => is_multisite() ? 'yes' : 'no',
			'File system:'   => $fs->get_method(),
			'Temp folder:'   => get_temp_dir(),
			'Process owner:' => $usr['name'],
			'File owner:'    => get_current_user(), // Gets the name of the owner of the current PHP script
			'Umask:'         => sprintf( '%o', umask() ^ 511 /* 0777 */ ),
			'Zlib:'          => function_exists( 'gzopen' ) ? 'yes' : 'no',
			'ZipArchive:'    => class_exists( 'ZipArchive', FALSE ) ? 'yes' : 'no',
			'PECL phar:'     => class_exists( 'PharData',   FALSE ) ? 'yes' : 'no',
			'BC Math:'       => (extension_loaded('gmp') ? 'gmp ' : '') . (function_exists('bcadd') ? 'yes' : 'no'),
			'mb_strcut:'     => function_exists( 'mb_strcut' ) ? 'yes' : 'no', // @since PHP 4.0.6
			'OpenSSL:'       => defined( 'OPENSSL_RAW_DATA'  ) ? 'yes' : 'no', // @since PHP 5.3.3
			'SQLite(PDO):'   => extension_loaded( 'pdo_sqlite' ) ? 'yes' : 'no',
			'DNS lookup:'    => ('8.8.8.8' !== $val ? 'available' : 'n/a') . sprintf( ' [%.1f msec]', $key * 1000.0 ),
			'User agent:'    => $_SERVER['HTTP_USER_AGENT'],
		) );

		// Child and parent themes
		$activated = wp_get_theme(); // @since 3.4.0
		$res += array( esc_html( $activated->get( 'Name' ) ) => esc_html( $activated->get( 'Version' ) ) );

		if ( $installed = $activated->get( 'Template' ) ) {
			$activated = wp_get_theme( $installed );
			$res += array( esc_html( $activated->get( 'Name' ) ) => esc_html( $activated->get( 'Version' ) ) );
		}

		// Plugins
		$installed = get_plugins(); // @since 1.5.0
		$activated = get_site_option( 'active_sitewide_plugins' ); // @since 2.8.0
		! is_array( $activated ) and $activated = array();
		$activated = array_merge( $activated, array_fill_keys( get_option( 'active_plugins' ), TRUE ) );

		foreach ( $installed as $key => $val ) {
			if ( isset( $activated[ $key ] ) ) {
				$res += array( esc_html( $val['Name'] ) => esc_html( $val['Version'] ) );
			}
		}

		// Blocked self requests
		$installed = array_reverse( IP_Geo_Block_Logs::search_logs( IP_Geo_Block::get_ip_address(), IP_Geo_Block::get_option() ) );
		foreach ( $installed as $val ) {
			if ( IP_Geo_Block::is_blocked( $val['result'] ) ) {
				// hide port and nonce
				$method = preg_replace( '/\[\d+\]/', '', $val['method'] );
				$method = preg_replace( '/(' . IP_Geo_Block::get_auth_key() . ')(?:=|%3D)([\w]+)/', '$1=...', $method );

				// add post data
				$query = array();
				foreach ( explode( ',', $val['data'] ) as $str ) {
					FALSE !== strpos( $str, '=' ) and $query[] = $str;
				}

				if ( ! empty( $query ) )
					$method .= '(' . implode( ',', $query ) . ')';

				$res += array(
					esc_html( IP_Geo_Block_Util::localdate( $val['time'], 'Y-m-d H:i:s' ) ) =>
					esc_html( str_pad( $val['result'], 8 ) . $method )
				);
			}
		}

		return $res;
	}

}