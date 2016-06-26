<?php
class IP_Geo_Block_Admin_Ajax {

	/**
	 * Admin ajax sub functions
	 *
	 */
	static public function search_ip( $which ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		// check format
		if ( filter_var( $ip = $_POST['ip'], FILTER_VALIDATE_IP ) ) {
			// get option settings and compose request headers
			$options = IP_Geo_Block::get_option( 'settings' );
			$args    = IP_Geo_Block::get_request_headers( $options );

			// create object for provider and get location
			if ( $geo = IP_Geo_Block_API::get_instance( $which, $options ) )
				$res = $geo->get_location( $ip, $args );
			else
				$res = array( 'errorMessage' => 'Unknown service.' );
		}

		else {
			$res = array( 'errorMessage' => 'Invalid IP address.' );
		}

		return $res;
	}

	/**
	 * Get country code from providers
	 *
	 */
	static public function scan_country() {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

		// scan all the country code using selected APIs
		$ip        = IP_Geo_Block::get_ip_address();
		$options   = IP_Geo_Block::get_option( 'settings' );
		$args      = IP_Geo_Block::get_request_headers( $options );
		$type      = IP_Geo_Block_Provider::get_providers( 'type', FALSE, FALSE );
		$providers = IP_Geo_Block_Provider::get_valid_providers( $options['providers'], FALSE, FALSE );

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
						__( 'UNKNOWN', 'ip-geo-block' ) ) )
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
	static private function array_insert( &$base_array, $insert_value, $position = null ) {
		if ( ! is_array( $insert_value ) )
			$insert_value = array( $insert_value );

		$position = is_null( $position ) ? count( $base_array ) : intval( $position );

		$base_array = array_merge(
			array_splice( $base_array, 0, $position ),
			$insert_value, $base_array
		);
	}

	/**
	 * Export logs from MySQL DB
	 *
	 */
	static public function export_logs( $which ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

		$csv = '';
		$which = IP_Geo_Block_Logs::restore_logs( $which );
		$date = isset( $which[0] ) ? $which[0][1] : $_SERVER['REQUEST_TIME'];
		$date = IP_Geo_Block_Util::localdate( $date, 'Y-m-d_H-i-s' );

		foreach ( $which as $data ) {
			$hook = array_shift( $data );
			self::array_insert( $data, $hook, 3 );
			$data[0] = IP_Geo_Block_Util::localdate( $data[0], 'Y-m-d H:i:s' );
			$csv .= implode( ',', $data ) . PHP_EOL;
		}

		// Send as file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_SLUG . '_' . $date . '.csv"' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Content-Length: ' . strlen( $csv ) );
		echo $csv;
	}

	/**
	 * Restore logs from MySQL DB
	 *
	 */
	static public function restore_logs( $which ) {
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
		include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

		// if js is slow then limit the number of rows
		$list = array();
		$limit = IP_Geo_Block_Logs::limit_rows( @$_POST['time'] );

		foreach ( IP_Geo_Block_Logs::restore_logs( $which ) as $row ) {
			$hook = array_shift( $row );
			$list[ $hook ][] = $row; // array_map( 'IP_Geo_Block_Logs::validate_utf8', $row );
		}

		// compose html with sanitization
		foreach ( $list as $hook => $rows ) {
			$html = '';
			$n = 0;
			foreach ( $rows as $row ) {
				$log = (int)array_shift( $row );
				$html .= '<tr><td data-value='.$log.'>';
				$html .= IP_Geo_Block_Util::localdate( $log, 'Y-m-d H:i:s' ) . "</td>";
				foreach ( $row as $log ) {
					$log = esc_html( $log );
					$html .= "<td>$log</td>";
				}
				$html .= "</tr>";
				if ( ++$n >= $limit ) break;
			}
			$res[ $hook ] = $html;
		}

		return isset( $res ) ? $res : NULL;
	}

	/**
	 * Validate json from the client and respond safe data
	 *
	 */
	static public function validate_settings( $parent ) {
		$json = str_replace(
			array( '\\"', '\\\\', "'"  ),
			array( '"',   '\\',   '\"' ),
			isset( $_POST['data'] ) ? $_POST['data'] : ''
		);

		if ( NULL === ( $data = json_decode( $json, TRUE ) ) )
			wp_die( 'Illegal JSON format.', '', array( 'response' => 500, 'back_link' => TRUE ) ); // @Since 2.0.4

		// Sanitize to fit the type of each field
		$temp = self::json_to_settings( $data );
		$temp = $parent->validate_options( 'settings', $temp );
		$data = self::settings_to_json( $temp );
		$json = self::json_unsafe_encode( $data );

		mbstring_binary_safe_encoding(); // @since 3.7.0
		$length = strlen( $json );
		reset_mbstring_encoding(); // @since 3.7.0

		// Send json as file
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_SLUG . '-settings.json"' );
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
	static private function json_to_settings( $input ) {
		$settings = array();
		$prfx = 'ip_geo_block_settings';

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
					} else {
						$settings[ $m[1] ][ $m[2] ][ $m[3] ] = $val;
					}
					break;
				}
			}
		}

		return $settings;
	}

	/**
	 * Convert settings array to json associative array
	 *
	 */
	static public function settings_to_json( $input, $overwrite = TRUE ) {
		$keys = array(
			'[version]',
			'[matching_rule]',
			'[white_list]',
			'[black_list]',
			'[extra_ips][white_list]',
			'[extra_ips][black_list]',
			'[signature]',
			'[response_code]',
			'[login_fails]',
			'[validation][proxy]',
			'[validation][comment]',
			'[validation][xmlrpc]',
			'[validation][login]',
			'[validation][admin][1]',
			'[validation][admin][2]',
			'[validation][ajax][1]',
			'[validation][ajax][2]',
			'[validation][plugins]',
			'[validation][themes]',
			'[rewrite][plugins]',
			'[rewrite][themes]',
			'[exception][plugins][*]',   // 2.2.5
			'[exception][themes][*]',    // 2.2.5
			'[providers][Maxmind]',
			'[providers][IP2Location]',
			'[providers][freegeoip.net]',
			'[providers][ipinfo.io]',
			'[providers][IP-Json]',
			'[providers][Nekudo]',
			'[providers][Xhanch]',
			'[providers][geoPlugin]',
			'[providers][ip-api.com]',
			'[providers][IPInfoDB]',
			'[save_statistics]',
			'[validation][reclogs]',
			'[validation][postkey]',
			'[update][auto]',
			'[anonymize]',
			'[cache_hold]',
			'[cache_time]',
			'[comment][pos]',
			'[comment][msg]',
			'[clean_uninstall]',
		);
		$json = array();
		$prfx = 'ip_geo_block_settings';

		foreach ( $keys as $key ) {
			if ( preg_match( "/\[(.+?)\](?:\[(.+?)\](?:\[(.+?)\])?)?/", $key, $m ) ) {
				switch ( count( $m ) ) {
				  case 2:
					if ( isset( $input[  $m[1]  ] ) ) {
						$json[ $prfx.'['.$m[1].']' ] = strval( $input[ $m[1] ] );
					}
					break;

				  case 3:
					if ( !@is_null( $input[ $m[1] ][ $m[2] ] ) || $overwrite ) {
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
						foreach ( $input[ $m[1] ][ $m[2] ] as $val ) {
							$json[ $prfx.'['.$m[1].']['.$m[2].']'.'['.$val.']' ] = 1;
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
	static public function preferred_to_json() {
		return self::settings_to_json(
			array(
				'validation'      => array(   // Action hook for validation
				    'comment'     => TRUE,    // Validate on comment post
				    'login'       => 1,       // Validate on login
				    'admin'       => 3,       // Validate on admin (1:country 2:ZEP)
				    'ajax'        => 3,       // Validate on ajax/post (1:country 2:ZEP)
				    'xmlrpc'      => 1,       // Validate on xmlrpc (1:country 2:close)
				    'postkey'     => 'action,comment,log,pwd', // Keys in $_POST
				    'plugins'     => 2,       // Validate on wp-content/plugins
				    'themes'      => 2,       // Validate on wp-content/themes
				),
				'signature'       => "..,/wp-config.php,/passwd,curl,wget\nselect:.5,where:.5,union:.5\ncreate:.6,password:.4,load_file:.5",
				'rewrite'         => array(   // Apply rewrite rule
				    'plugins'     => TRUE,    // for wp-content/plugins
				    'themes'      => TRUE,    // for wp-content/themes
				),
			), FALSE // should not overwrite the existing parameters
		);
	}

	// Encode json without JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	// Note: It should not be rendered via jQuery .html() at client side
	static private function json_unsafe_encode( $data ) {
		if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
			$opts = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
			if ( function_exists( 'wp_json_encode' ) ) // @since 4.1.0
				$json = wp_json_encode( $data, $opts );
			else
				$json = json_encode( $data, $opts );
		}
		else { // Some options are not supported in PHP 5.3 and under
			$json = self::json_unescaped_unicode( $data );
			$json = str_replace(
				array( '{"', '","', '"}', '\\/' ), // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				array( '{'.PHP_EOL.'    "', '",'.PHP_EOL.'    "', '"'.PHP_EOL.'}', '/' ),
				$json
			);
		}

		return $json;
	}

	// Fallback function for PHP 5.3 and under
	// @link http://qiita.com/keromichan16/items/5ff45a77fb0d48e046cc
	// @link http://stackoverflow.com/questions/16498286/why-does-the-php-json-encode-function-convert-utf-8-strings-to-hexadecimal-entit/
	static private function json_unescaped_unicode( $input ) {
		return preg_replace_callback(
			'/(?:\\\\u[0-9a-zA-Z]{4})++/',
			array( __CLASS__, 'convert_encoding' ),
			json_encode( $input )
		);
	}

	// Fallback function for PHP 5.3 and under
	static private function convert_encoding( $matches ) {
		return mb_convert_encoding(
			pack( 'H*', str_replace( '\\u', '', $matches[0] ) ),
			'UTF-8', 'UTF-16'
		);
	}

}