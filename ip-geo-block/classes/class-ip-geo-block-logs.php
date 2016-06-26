<?php
/**
 * IP Geo Block - Handling validation log
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

// varchar can not be exceeded over 255 before MySQL-5.0.3.
define( 'IP_GEO_BLOCK_MAX_STR_LEN', 255 );
define( 'IP_GEO_BLOCK_MAX_TXT_LEN', 511 );

class IP_Geo_Block_Logs {

	const TABLE_LOGS = 'ip_geo_block_logs';
	const TABLE_STAT = 'ip_geo_block_stat';

	// Initial statistics data
	private static $default = array(
		'blocked'   => 0,
		'unknown'   => 0,
		'IPv4'      => 0,
		'IPv6'      => 0,
		'countries' => array(),
		'providers' => array(),
		'daystats'  => array(),
	);

	/**
	 * Create
	 *
	 * @note creating mixed storage engine may cause troubles with some plugins.
	 */
	public static function create_tables() {
		global $wpdb;
		$result = TRUE;

		// Default charset
		$charset = 'utf8'; // MySQL 5.0+
		if ( preg_match( '/CHARACTER SET (\w+)/i', $wpdb->get_charset_collate(), $table ) &&
		     FALSE !== strpos( $table[1], 'utf8' ) ) {
			$charset = $table[1]; // ex) utf8mb4 MySQL 5.5+
		}

		// for logs
		$table = $wpdb->prefix . self::TABLE_LOGS;
		$result &= ( FALSE !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `$table` (
			`No` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`time` int(10) unsigned NOT NULL DEFAULT 0,
			`ip` varchar(40) NOT NULL,
			`hook` varchar(8) NOT NULL,
			`auth` int(10) unsigned NOT NULL DEFAULT 0,
			`code` varchar(2) NOT NULL DEFAULT 'ZZ',
			`result` varchar(8) NULL,
			`method` varchar("     . IP_GEO_BLOCK_MAX_STR_LEN . ") NOT NULL,
			`user_agent` varchar(" . IP_GEO_BLOCK_MAX_STR_LEN . ") NULL,
			`headers` varchar("    . IP_GEO_BLOCK_MAX_TXT_LEN . ") NULL,
			`data` text NULL,
			PRIMARY KEY (`No`),
			KEY `time` (`time`),
			KEY `hook` (`hook`)
			) CHARACTER SET " . $charset
		) ) or self::error( __LINE__ ); // utf8mb4 ENGINE=InnoDB or MyISAM

		// for statistics
		$table = $wpdb->prefix . self::TABLE_STAT;
		$result &= ( FALSE !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `$table` (
			`No` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
			`data` longtext NULL,
			PRIMARY KEY (`No`)
			) CHARACTER SET " . $charset
		) ) or self::error( __LINE__ ); // utf8mb4 ENGINE=InnoDB or MyISAM

		// Create 1 record if not exists
		$sql = $wpdb->prepare(
			"INSERT INTO `$table` (`No`, `data`) VALUES (%d, %s)
			ON DUPLICATE KEY UPDATE No = No", 1, serialize( self::$default )
		) and $wpdb->query( $sql );

		return $result;
	}

	/**
	 * Delete
	 *
	 */
	public static function delete_tables() {
		global $wpdb;
		$tables = array( self::TABLE_LOGS, self::TABLE_STAT, IP_Geo_Block::CACHE_KEY );

		foreach ( $tables as $table ) {
			$table = $wpdb->prefix . $table;
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" ) or self::error( __LINE__ );
		}
	}

	/**
	 * Diagnose tables
	 *
	 */
	public static function diag_tables() {
		global $wpdb;
		$tables = array( self::TABLE_LOGS, self::TABLE_STAT, IP_Geo_Block::CACHE_KEY );

		foreach ( $tables as $table ) {
			$table = $wpdb->prefix . $table;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
				return  sprintf(
					__( 'Creating a DB table %s had failed. Once de-activate this plugin, and then activate again.', 'ip-geo-block' ),
					$table
				);
			}
		}

		return NULL;
	}

	/**
	 * Clear log data
	 *
	 */
	public static function clear_logs( $hook = NULL ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_LOGS;

		if ( $hook )
			$sql = $wpdb->prepare(
				"DELETE FROM `$table` WHERE `hook` = '%s'", $hook
			) and $wpdb->query( $sql ) or self::error( __LINE__ );
		else
			$wpdb->query( "TRUNCATE TABLE `$table`" ) or self::error( __LINE__ );
	}

	/**
	 * Clear statistics data.
	 *
	 */
	public static function clear_stat() {
		self::record_stat( self::$default );
	}

	/**
	 * Clear IP address cache.
	 *
	 */
	public static function clear_cache() {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_KEY;
		$wpdb->query( "TRUNCATE TABLE `$table`" ) or self::error( __LINE__ );
	}

	/**
	 * Restore statistics data.
	 *
	 */
	public static function restore_stat( $ret = FALSE ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_STAT;

		$data = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A ) or self::error( __LINE__ );
		return empty( $data ) ? ( $ret ? self::$default : FALSE ) : unserialize( $data[0]['data'] );
	}

	/**
	 * Record statistics data.
	 *
	 */
	public static function record_stat( $statistics ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_STAT;

		if ( ! is_array( $statistics ) ) {
			$statistics = self::$default;
		}

		$sql = $wpdb->prepare(
			"UPDATE `$table` SET `data` = '%s'", serialize( $statistics )
//			"REPLACE INTO `$table` (`No`, `data`) VALUES (%d, %s)", 1, serialize( $statistics )
		) and $data = $wpdb->query( $sql ) or self::error( __LINE__ );

		return empty( $data ) ? FALSE : TRUE;
	}

	/**
	 * Limit the number of rows to send to the user agent
	 *
	 */
	public static function limit_rows( $time ) {
		$time = intval( $time );
		$options = IP_Geo_Block::get_option( 'settings' );

		if ( $time < 100 /* msec */ )
			return (int)$options['validation']['maxlogs'];

		elseif ( $time < 200 /* msec */ )
			return (int)($options['validation']['maxlogs'] / 2);

		return (int)($options['validation']['maxlogs'] / 5);
	}

	/**
	 * Validate string whether utf8
	 *
	 * @note code from wp_check_invalid_utf8() in wp-includes/formatting.php
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 */
	private static function validate_utf8( $str ) {
		$str = (string) $str;
		if ( 0 === strlen( $str ) )
			return '';

		// Store the site charset as a static to avoid multiple calls to get_option()
		static $is_utf8 = NULL;
		if ( $is_utf8 === NULL )
			$is_utf8 = array_key_exists(
				get_option( 'blog_charset' ),
				array( 'utf8' => NULL, 'utf-8' => NULL, 'UTF8' => NULL, 'UTF-8' => NULL )
			);

		// handle utf8 only
		if ( ! $is_utf8 )
			return '…';

		// Check support for utf8 in the installed PCRE library
		static $utf8_pcre = NULL;
		if ( $utf8_pcre === NULL )
			$utf8_pcre = preg_match( '/^./u', 'a' );

		// if no support then reject $str for safety
		if ( ! $utf8_pcre )
			return '…';

		// preg_match fails when it encounters invalid UTF8 in $str
		if ( 1 === preg_match( '/^./us', $str ) ) {
			// remove utf8mb4 4 bytes character
			// @see strip_invalid_text() in wp-includes/wp-db.php
			$regex = '/(?:\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})/';
			return preg_replace( $regex, '', $str );
		}

		return '…';
	}

	/**
	 * Truncate string as utf8
	 *
	 * @see http://jetpack.wp-a2z.org/oik_api/mbstring_binary_safe_encoding/
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/functions.php
	 */
	private static function truncate_utf8( $str, $regexp = NULL, $replace = '', $len = IP_GEO_BLOCK_MAX_STR_LEN ) {
		// remove unnecessary characters
		$str = preg_replace( '/[\x00-\x1f\x7f]/', '', $str );
		if ( $regexp )
			$str = preg_replace( $regexp, $replace, $str );

		// limit the length of the string
		if ( function_exists( 'mb_strcut' ) ) {
			mbstring_binary_safe_encoding(); // @since 3.7.0
			if ( strlen( $str ) > $len )
				$str = mb_strcut( $str, 0, $len ) . '…';
			reset_mbstring_encoding(); // @since 3.7.0
		}

		else { // https://core.trac.wordpress.org/ticket/25259
			mbstring_binary_safe_encoding(); // @since 3.7.0
			$original = strlen( $str );
			$str = substr( $str, 0, $len );
			$length = strlen( $str );
			reset_mbstring_encoding(); // @since 3.7.0

			if ( $length !== $original ) {
				// bit pattern from seems_utf8() in wp-includes/formatting.php
				static $code = array(
					array( 0x80, 0x00 ), // 1byte  0bbbbbbb
					array( 0xE0, 0xC0 ), // 2bytes 110bbbbb
					array( 0xF0, 0xE0 ), // 3bytes 1110bbbb
					array( 0xF8, 0xF0 ), // 4bytes 11110bbb
					array( 0xFC, 0xF8 ), // 5bytes 111110bb
					array( 0xFE, 0xFC ), // 6bytes 1111110b
				);

				// truncate extra characters
				$len = min( $length, 6 );
				for ( $i = 0; $i < $len; $i++ ) {
					$c = ord( $str[$length-1 - $i] );
					for ( $j = $i; $j < 6; $j++ ) {
						if ( ( $c & $code[$j][0] ) == $code[$j][1] ) {
							mbstring_binary_safe_encoding(); // @since 3.7.0
							$str = substr( $str, 0, $length - (int)($j > 0) - $i );
							reset_mbstring_encoding(); // @since 3.7.0

							// validate whole characters
							$str = self::validate_utf8( $str );
							return '…' !== $str ? $str . '…' : '…';
						}
					}
				}

				// $str may not fit utf8
				return '…';
			}
		}

		// validate whole characters
		return self::validate_utf8( $str );
	}

	/**
	 * Get data
	 *
	 * These data must be sanitized before rendering
	 */
	private static function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ?
			self::truncate_utf8( $_SERVER['HTTP_USER_AGENT'] ) : '';
	}

	private static function get_http_headers() {
		$exclusions = array(
			'HTTP_ACCEPT' => TRUE,
			'HTTP_ACCEPT_CHARSET' => TRUE,
			'HTTP_ACCEPT_ENCODING' => TRUE,
			'HTTP_ACCEPT_LANGUAGE' => TRUE,
			'HTTP_CACHE_CONTROL' => TRUE,
			'HTTP_CONNECTION' => TRUE,
			'HTTP_COOKIE' => TRUE,
			'HTTP_HOST' => TRUE,
			'HTTP_PRAGMA' => TRUE,
			'HTTP_USER_AGENT' => TRUE,
		);

		// select headers to hold in the logs
		$headers = array();
		foreach ( array_keys( $_SERVER ) as $key ) {
			if ( 'HTTP_' === substr( $key, 0, 5 ) && empty( $exclusions[ $key ] ) )
				$headers[] = $key . '=' . $_SERVER[ $key ];
		}

		return self::truncate_utf8(
			implode( ',', $headers ), NULL, '', IP_GEO_BLOCK_MAX_STR_LEN
		);
	}

	private static function get_post_data( $hook, $validate, $settings ) {
		// condition of masking password
		$mask_pwd = ( 'passed' === $validate['result'] );

		// XML-RPC
		if ( 'xmlrpc' === $hook ) {
			global $HTTP_RAW_POST_DATA; // already populated in xmlrpc.php

			// mask the password
			$posts = self::truncate_utf8(
				$HTTP_RAW_POST_DATA, '!\s*([<>])\s*!', '$1', IP_GEO_BLOCK_MAX_STR_LEN
			);
			if ( $mask_pwd &&
			     preg_match_all( '/<string>(\S*?)<\/string>/', $posts, $matches ) >= 2 &&
			     strpos( $matches[1][1], home_url() ) !== 0 ) { // except pingback
				$posts = str_replace( $matches[1][1], '***', $posts );
			}
			/*if ( FALSE !== ( $xml = @simplexml_load_string( $HTTP_RAW_POST_DATA ) ) ) {
				// mask the password
				if ( $mask_pwd && 'wp.' === substr( $xml->methodName, 0, 3 ) ) {
					$xml->params->param[1]->value->string = '***';
				}
				$posts = self::truncate_utf8( wp_json_encode( $xml ), '/["\\\\]/' );
			} else {
				$posts = 'xml parse error: malformed xml';
			}*/
		}

		// post data
		else {
			$keys = array_fill_keys( array_keys( $_POST ), NULL );
			foreach ( explode( ',', $settings['validation']['postkey'] ) as $key ) {
				if ( array_key_exists( $key, $_POST ) ) {
					// mask the password
					$keys[ $key ] = ( 'pwd' === $key && $mask_pwd ) ? '***' : $_POST[ $key ];
				}
			}

			// Join array elements
			$posts = array();
			foreach ( $keys as $key => $val )
				$posts[] = $val ? $key.'='.$val : $key;

			$posts = self::truncate_utf8(
				implode( ',', $posts ), '/\s+/', ' ', IP_GEO_BLOCK_MAX_STR_LEN
			);
		}

		return $posts;
	}

	/**
	 * Backup the validation log to text files
	 *
	 * @notice $path should not be in the public_html.
	 */
	private static function backup_logs( $hook, $validate, $method, $agent, $heads, $posts, $path ) {
		// $path should be absolute path to the directory
		if ( validate_file( $path ) !== 0 )
			return;

		$path = trailingslashit( $path ) .
			IP_Geo_Block::PLUGIN_SLUG . date('-Y-m') . '.log';

		if ( ( $fp = @fopen( $path, 'ab' ) ) === FALSE )
			return;

		fprintf( $fp, "%d,%s,%s,%d,%s,%s,%s,%s,%s,%s\n",
			$_SERVER['REQUEST_TIME'],
			$validate['ip'],
			$hook,
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			$method,
			str_replace( ',', '‚', $agent ), // &#044; --> &#130;
			str_replace( ',', '‚', $heads ), // &#044; --> &#130;
			str_replace( ',', '‚', $posts )  // &#044; --> &#130;
		);

		fclose( $fp );
	}

	/**
	 * Record the validation log
	 *
	 * This function record the user agent string and post data.
	 * The security policy for these data is as follows.
	 *
	 *   1. Record only utf8 under the condition that the site charset is utf8
	 *   2. Record by limiting the length of the string
	 *   3. Mask the password if it is authenticated
	 *
	 * @param string $hook type of log name
	 * @param array $validate validation results
	 * @param array $settings option settings
	 */
	public static function record_logs( $hook, $validate, $settings ) {
		// get data
		$agent = self::get_user_agent();
		$heads = self::get_http_headers();
		$posts = self::get_post_data( $hook, $validate, $settings );
		$method = $_SERVER['REQUEST_METHOD'] . '[' . $_SERVER['SERVER_PORT'] . ']:' . $_SERVER['REQUEST_URI'];

		// anonymize ip address
		if ( ! empty( $settings['anonymize'] ) )
			$validate['ip'] = preg_replace( '/\d{1,3}$/', '***', $validate['ip'] );

		// limit the maximum number of rows
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_LOGS;
		$rows = $settings['validation']['maxlogs'];

		// count the number of rows for each hook
		$sql = $wpdb->prepare(
			"SELECT count(*) FROM `$table` WHERE `hook` = '%s'", $hook
		) and $count = (int)$wpdb->get_var( $sql );

		if ( isset( $count ) && $count >= $rows ) {
			// Can't start transaction on the assumption that the storage engine is innoDB.
			// So there are some cases where logs are excessively deleted.
			$sql = $wpdb->prepare(
				"DELETE FROM `$table` WHERE `hook` = '%s' ORDER BY `No` ASC LIMIT %d",
				$hook, $count - $rows + 1
			) and $wpdb->query( $sql ) or self::error( __LINE__ );
		}

		// insert into DB
		$sql = $wpdb->prepare(
			"INSERT INTO `$table`
			(`time`, `ip`, `hook`, `auth`, `code`, `result`, `method`, `user_agent`, `headers`, `data`)
			VALUES (%d, %s, %s, %d, %s, %s, %s, %s, %s, %s)",
			$_SERVER['REQUEST_TIME'],
			$validate['ip'],
			$hook,
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			$method,
			$agent,
			$heads,
			$posts
		) and $wpdb->query( $sql ) or self::error( __LINE__ );

		// backup logs to text files
		if ( $dir = apply_filters(
			IP_Geo_Block::PLUGIN_SLUG . '-backup-dir',
			$settings['validation']['backup'], $hook
		) ) {
			self::backup_logs(
				$hook, $validate, $method, $agent, $heads, $posts, $dir
			);
		}
	}

	/**
	 * Restore the validation log
	 *
	 * @param string $hook type of log name
	 * return array log data
	 */
	public static function restore_logs( $hook = NULL ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_LOGS;

		$sql = ( "SELECT
			`hook`, `time`, `ip`, `code`, `result`, `method`, `user_agent`, `headers`, `data`
			FROM `$table`"
		);

		if ( ! $hook )
			$sql .= " ORDER BY `hook`, `No` DESC";
		else
			$sql .= $wpdb->prepare( " WHERE `hook` = '%s' ORDER BY `No` DESC", $hook );

		return $sql ? $wpdb->get_results( $sql, ARRAY_N ) : array();
	}

	/**
	 * Update statistics.
	 *
	 */
	public static function update_stat( $hook, $validate, $settings ) {
		// Restore statistics.
		if ( $statistics = self::restore_stat() ) {
			if ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
				$statistics['IPv4']++;
			elseif ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
				$statistics['IPv6']++;

			@$statistics[ 'passed' !== $validate['result'] ? 'blocked' : 'passed' ]++;
			@$statistics['countries'][ $validate['code'] ]++;

			$provider = isset( $validate['provider'] ) ? $validate['provider'] : 'ZZ';
			if ( empty( $statistics['providers'][ $provider ] ) )
				$statistics['providers'][ $provider ] = array( 'count' => 0, 'time' => 0.0 );

			$statistics['providers'][ $provider ]['count']++;
			$statistics['providers'][ $provider ]['time'] += (float)@$validate['time'];

			@$statistics['daystats'][ mktime( 0, 0, 0 ) ][ $hook ]++;
			if ( count( $statistics['daystats'] ) > 30 ) {
				reset( $statistics['daystats'] );
				unset( $statistics['daystats'][ key( $statistics['daystats'] ) ] );
			}

			// Record statistics.
			self::record_stat( $statistics );
		}
	}

	/**
	 * SQL Error handling
	 *
	 */
	private static function error( $line ) {
		if ( class_exists( 'IP_Geo_Block_Admin' ) ) {
			global $wpdb;
			if ( $wpdb->last_error )
				IP_Geo_Block_Admin::add_admin_notice( 'error', __FILE__ . ' (' . $line . ') ' . $wpdb->last_error );
		}
	}
}