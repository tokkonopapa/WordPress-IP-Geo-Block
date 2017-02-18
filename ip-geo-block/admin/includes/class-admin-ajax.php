<?php
class IP_Geo_Block_Admin_Ajax {

	/**
	 * Admin ajax sub functions
	 *
	 */
	static public function search_ip( $which ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';

		// check format
		if ( filter_var( $ip = $_POST['ip'], FILTER_VALIDATE_IP ) ) {
			// get option settings and compose request headers
			$options = IP_Geo_Block::get_option();
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

		if ( empty( $res['errorMessage'] ) )
			$res['host'] = IP_Geo_Block_Lkup::gethostbyaddr( $ip );

		return $res;
	}

	/**
	 * Get country code from providers
	 *
	 */
	static public function scan_country() {
		// scan all the country code using selected APIs
		$ip        = IP_Geo_Block::get_ip_address();
		$options   = IP_Geo_Block::get_option();
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
		header( 'Content-Disposition: attachment; filename="' . IP_Geo_Block::PLUGIN_NAME . '_' . $date . '.csv"' );
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

				$log = array_shift( $row );
				$html .= '<td><a href="#!">' . esc_html( $log ) . '</a></td>';

				foreach ( $row as $log ) {
					$html .= '<td>' . esc_html( $log ) . '</td>';
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
		// restore escaped characters (see wp_magic_quotes() in wp-includes/load.php)
		$json = str_replace(
			array( '\\"', '\\\\', "'"  ),
			array( '"',   '\\',   '\"' ),
			isset( $_POST['data'] ) ? $_POST['data'] : ''
		);

		if ( NULL === ( $data = json_decode( $json, TRUE ) ) )
			wp_die( 'Illegal JSON format.', '', array( 'response' => 500, 'back_link' => TRUE ) ); // @Since 2.0.4

		// Sanitize to fit the type of each field
		$temp = self::json_to_settings( $data );

		// Integrate posted data into current settings because if can be a part of hole data
		unset( $temp['version'] );
		$temp = array_replace_recursive( IP_Geo_Block::get_option(), $temp );

		// Validate options and convert to json
		$temp = $parent->validate_options( $temp );
		$data = self::settings_to_json( $temp );
		$json = self::json_unsafe_encode( $data );

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
	static private function json_to_settings( $input ) {
		$settings = array();
		$prfx = IP_Geo_Block::OPTION_NAME;

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
			'[login_fails]',
			'[response_code]',
			'[response_msg]',            // 3.0.0
			'[redirect_uri]',            // 3.0.0
			'[validation][timing]',      // 2.2.9
			'[validation][proxy]',
			'[validation][comment]',
			'[validation][xmlrpc]',
			'[validation][login]',
			'[login_action][login]',        // 2.2.8
			'[login_action][register]',     // 2.2.8
			'[login_action][resetpasss]',   // 2.2.8
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
			'[public][matching_rule]',   // 3.0.0
			'[public][white_list]',      // 3.0.0
			'[public][black_list]',      // 3.0.0
			'[public][target_rule]',     // 3.0.0
			'[public][target_pages][$]', // 3.0.0
			'[public][target_posts][$]', // 3.0.0
			'[public][target_cates][$]', // 3.0.0
			'[public][target_tags][$]',  // 3.0.0
			'[public][ua_list]',         // 3.0.0
			'[public][simulate]',        // 3.0.0
			'[providers][Maxmind]',
			'[providers][IP2Location]',
			'[providers][freegeoip.net]',
			'[providers][ipinfo.io]',
			'[providers][IP-Json]',
			'[providers][Nekudo]',
			'[providers][Xhanch]',
			'[providers][GeoIPLookup]',  // 2.2.8
			'[providers][ip-api.com]',
			'[providers][IPInfoDB]',
			'[save_statistics]',
			'[validation][reclogs]',
			'[validation][recdays]',     // 2.2.9
			'[validation][maxlogs]',
			'[validation][postkey]',
			'[update][auto]',
			'[anonymize]',
			'[cache_time_gc]',           // 3.0.0
			'[cache_hold]',
			'[cache_time]',
			'[comment][pos]',
			'[comment][msg]',
			'[clean_uninstall]',
			'[api_key][GoogleMap]',      // 2.2.7
			'[network_wide]',            // 3.0.0
		);
		$json = array();
		$prfx = IP_Geo_Block::OPTION_NAME;

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
						if ( '*' === $m[3] ) {
							foreach ( $input[ $m[1] ][ $m[2] ] as $val ) {
								$json[ $prfx.'['.$m[1].']['.$m[2].']'.'['.$val.']' ] = 1;
							}
						} elseif ( is_array( $input[ $m[1] ][ $m[2] ] ) ) {
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
				    'timing'      => 1,       // 0:init, 1:mu-plugins, 2:drop-in
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
	static private function json_unsafe_encode( $data ) {
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
			pack( 'H*', str_replace( '\\u', '', $matches[0] ) ), 'UTF-8', 'UTF-16'
		);
	}

	static public function get_wp_info() {
		// PHP, WordPress
		$res = array();
		$res[] = array( 'PHP' => PHP_VERSION );
		$res[] = array( 'BC Math' => (extension_loaded('gmp') ? 'gmp ' : '') . (function_exists('bcadd') ? 'yes' : 'no') );
		$res[] = array( 'mb_strcut' => function_exists( 'mb_strcut' ) ? 'yes' : 'no' );
		$res[] = array( 'WordPress' => $GLOBALS['wp_version'] );
		$res[] = array( 'Multisite' => is_multisite() ? 'yes' : 'no' );

		// Child and parent themes
		$activated = wp_get_theme(); // @since 3.4.0
		$res[] = array( esc_html( $activated->get( 'Name' ) ) => esc_html( $activated->get( 'Version' ) ) );

		if ( $installed = $activated->get( 'Template' ) ) {
			$activated = wp_get_theme( $installed );
			$res[] = array( esc_html( $activated->get( 'Name' ) ) => esc_html( $activated->get( 'Version' ) ) );
		}

		// Plugins
		$installed = get_plugins(); // @since 1.5.0
		$activated = get_site_option( 'active_sitewide_plugins' ); // @since 2.8.0
		! is_array( $activated ) and $activated = array();
		$activated = array_merge( $activated, array_fill_keys( get_option( 'active_plugins' ), TRUE ) );

		foreach ( $installed as $key => $val ) {
			if ( isset( $activated[ $key ] ) ) {
				$res[] = array(
					esc_html( $val['Name'] ) => esc_html( $val['Version'] )
				);
			}
		}

		// Logs (hook, time, ip, code, result, method, user_agent, headers, data)
		$installed = IP_Geo_Block_Logs::search_logs( IP_Geo_Block::get_ip_address() );

		foreach ( array_reverse( $installed ) as $val ) {
			// hide port and nonce
			$method = preg_replace( '/\[\d+\]/', '', $val['method'] );
			$method = preg_replace( '/(' . IP_Geo_Block::PLUGIN_NAME . '-auth-nonce)(?:=|%3D)([\w]+)/', '$1=...', $method );

			// add post data
			$query = array();
			foreach ( explode( ',', $val['data'] ) as $str ) {
				if ( FALSE !== strpos( $str, '=' ) )
					$query[] = $str;
			}

			if ( ! empty( $query ) )
				$method .= '(' . implode( ',', $query ) . ')';

			$res[] = array(
				esc_html( IP_Geo_Block_Util::localdate( $val['time'], 'Y-m-d H:i:s' ) ) =>
				esc_html( str_pad( $val['result'], 8 ) . $method )
			);
		}

		return $res;
	}

}