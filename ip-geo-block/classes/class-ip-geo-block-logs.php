<?php
/**
 * IP Geo Block - Handling validation log
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2017 tokkonopapa
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

	// SQLite for Live update
	private static $pdo = NULL;
	private static $stm = NULL;

	/**
	 * Create
	 *
	 * @internal creating mixed storage engine may cause troubles with some plugins.
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
			`asn` varchar(8) NULL,
			`hook` varchar(8) NOT NULL,
			`auth` int(10) unsigned NOT NULL DEFAULT 0,
			`code` varchar(2) NOT NULL DEFAULT 'ZZ',
			`result` varchar(8) NULL,
			`method` varchar("     . IP_GEO_BLOCK_MAX_STR_LEN . ") NOT NULL,
			`user_agent` varchar(" . IP_GEO_BLOCK_MAX_STR_LEN . ") NULL,
			`headers` varchar("    . IP_GEO_BLOCK_MAX_TXT_LEN . ") NULL,
			`data` text NULL,
			PRIMARY KEY  (`No`),
			KEY `time` (`time`),
			KEY `hook` (`hook`)
			) CHARACTER SET $charset"
		) ) or self::error( __LINE__ ); // utf8mb4 ENGINE=InnoDB or MyISAM

		// Add column for AS Number @since 3.0.4
		if ( ! $wpdb->query( "DESCRIBE `$table` `asn`" ) ) {
			$wpdb->query(
				"ALTER TABLE `$table` ADD `asn` varchar(8) AFTER `ip`"
			) or self::error( __LINE__ );
		}

		// for statistics
		$table = $wpdb->prefix . self::TABLE_STAT;
		$result &= ( FALSE !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `$table` (
			`No` tinyint(4) unsigned NOT NULL AUTO_INCREMENT,
			`data` longtext NULL,
			PRIMARY KEY  (`No`)
			) CHARACTER SET $charset"
		) ) or self::error( __LINE__ ); // utf8mb4 ENGINE=InnoDB or MyISAM

		// Create 1 record if not exists
		$sql = $wpdb->prepare(
			"INSERT INTO `$table` (`No`, `data`) VALUES (%d, %s)
			ON DUPLICATE KEY UPDATE No = No", 1, serialize( self::$default )
		) and $wpdb->query( $sql );

		// for IP address cache
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;
		$result &= ( FALSE !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `$table` (
			`No` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`time` int(10) unsigned NOT NULL DEFAULT 0,
			`ip` varchar(40) NOT NULL,
			`asn` varchar(8) NULL,
			`hook` varchar(8) NOT NULL,
			`auth` int(10) unsigned NOT NULL DEFAULT 0,
			`code` varchar(2) NOT NULL DEFAULT 'ZZ',
			`fail` int(10) unsigned NOT NULL DEFAULT 0,
			`call` int(10) unsigned NOT NULL DEFAULT 0,
			`host` tinytext NOT NULL,
			PRIMARY KEY  (`No`),
			UNIQUE KEY (`ip`)
			) CHARACTER SET $charset"
		) ) or self::error( __LINE__ ); // utf8mb4 ENGINE=InnoDB or MyISAM

		// Add column for AS Number @since 3.0.4
		if ( ! $wpdb->query( "DESCRIBE `$table` `asn`" ) ) {
			$wpdb->query(
				"ALTER TABLE `$table` ADD `asn` varchar(8) AFTER `ip`"
			) or self::error( __LINE__ );
		}

		return $result;
	}

	/**
	 * Search table by specific IP address
	 *
	 */
	private static function search_table( $table, $ip, $type = TRUE ) {
		global $wpdb;
		$table = $wpdb->prefix . $table;

		$sql = $wpdb->prepare(
			"SELECT * FROM `$table` WHERE `ip` = '%s'", $ip
		) and $result = $wpdb->get_results( $sql, ARRAY_A ) or self::error( __LINE__ );

		if ( $type )
			return ! empty( $result[0] ) ? $result[0] : NULL; // for cache
		else
			return ! empty( $result ) ? $result : array(); // for logs
	}

	/**
	 * Delete
	 *
	 */
	public static function delete_tables( $which = 'all' ) {
		global $wpdb;
		$tables = array( self::TABLE_LOGS, self::TABLE_STAT, IP_Geo_Block::CACHE_NAME );

		foreach ( $tables as $table ) {
			if ( 'all' === $which || $table === $which ) {
				$table = $wpdb->prefix . $table;
				$wpdb->query( "DROP TABLE IF EXISTS `$table`" ) or self::error( __LINE__ );
			}
		}
	}

	/**
	 * Diagnose tables
	 *
	 */
	public static function diag_tables() {
		global $wpdb;
		$tables = array( self::TABLE_LOGS, self::TABLE_STAT, IP_Geo_Block::CACHE_NAME );

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
	 * Restore statistics data.
	 *
	 */
	public static function restore_stat( $default = FALSE ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_STAT;

		$data = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A ) or self::error( __LINE__ );
		return empty( $data ) ? ( $default ? self::$default : FALSE ) : unserialize( $data[0]['data'] );
	}

	/**
	 * Record statistics data.
	 *
	 */
	public static function record_stat( $stat ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_STAT;

		if ( ! is_array( $stat ) )
			$stat = self::$default;

		$sql = $wpdb->prepare(
			"UPDATE `$table` SET `data` = '%s'", serialize( $stat )
//			"REPLACE INTO `$table` (`No`, `data`) VALUES (%d, %s)", 1, serialize( $stat )
		) and $wpdb->query( $sql ) or self::error( __LINE__ );
	}

	/**
	 * Validate string whether utf8
	 *
	 * @see  wp_check_invalid_utf8() in wp-includes/formatting.php
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
				for ( $i = 0; $i < $len; ++$i ) {
					$c = ord( $str[$length-1 - $i] );
					for ( $j = $i; $j < 6; ++$j ) {
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
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? self::truncate_utf8( $_SERVER['HTTP_USER_AGENT'] ) : '';
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

		return self::truncate_utf8( implode( ',', $headers ) );
	}

	private static function get_post_data( $hook, $validate, $settings ) {
		// condition of masking password
		$mask_pwd = ( 'passed' === $validate['result'] );

		// XML-RPC
		if ( 'xmlrpc' === $hook ) {
			$posts = self::truncate_utf8(
				file_get_contents( 'php://input' ), '!\s*([<>])\s*!', '$1'
			);

			// mask the password
			if ( $mask_pwd &&
			     preg_match_all( '/<string>(\S*?)<\/string>/', $posts, $matches ) >= 2 &&
			     strpos( $matches[1][1], home_url() ) !== 0 ) { // except pingback
				$posts = str_replace( $matches[1][1], '***', $posts );
			}

			return $posts;
		}

		// post data
		else {
			$keys  = explode( ',', $settings['validation']['postkey'] );
			$data  = array();
			$posts = $_POST;

			// uploading files
			if ( ! empty( $_FILES ) ) {
				$posts['FILES'] = str_replace( PHP_EOL, ' ', print_r( $_FILES, TRUE ) );
				! in_array( 'FILES', $keys, TRUE ) and $keys[] = 'FILES';
			}

			// mask the password
			if ( ! empty( $posts['pwd'] ) && $mask_pwd )
				$posts['pwd'] = '***';

			// primaly: $_POST keys
			foreach ( $keys as $key ) {
				array_key_exists( $key, $posts ) and $data[] = $key . '=' . $posts[ $key ];
			}

			// secondary: rest of the keys in $_POST
			foreach ( array_keys( $_POST ) as $key ) {
				! in_array( $key, $keys, TRUE ) and $data[] = $key;
			}

			return self::truncate_utf8( implode( ',', $data ), '/\s+/', ' ' );
		}
	}

	/**
	 * Backup the validation log to text files
	 *
	 * $path should be absolute to the directory and should not be within the public_html.
	 */
	private static function backup_logs( $hook, $validate, $method, $agent, $heads, $posts, $path ) {
		if ( validate_file( $path ) === 0 ) {
			file_put_contents(
				IP_Geo_Block_Util::slashit( $path ) . IP_Geo_Block::PLUGIN_NAME . date('-Y-m') . '.log',
				sprintf( "%d,%s,%s,%s,%d,%s,%s,%s,%s,%s,%s\n",
					$_SERVER['REQUEST_TIME'],
					$validate['ip'],
					$validate['asn'],
					$hook,
					$validate['auth'],
					$validate['code'],
					$validate['result'],
					$method,
					str_replace( ',', '‚', $agent ), // &#044; --> &#130;
					str_replace( ',', '‚', $heads ), // &#044; --> &#130;
					str_replace( ',', '‚', $posts )  // &#044; --> &#130;
				),
				FILE_APPEND | LOCK_EX
			);
		}
	}

	/**
	 * Open and close sqlite database for live log
	 *
	 * The absolute path to the database can be set via filter hook `ip-geo-block-live-log`.
	 *
	 * @see http://php.net/manual/en/pdo.connections.php
	 * @see http://php.net/manual/en/features.persistent-connections.php
	 * @see https://www.sqlite.org/sharedcache.html#shared_cache_and_in_memory_databases
	 *
	 * @param int $id ID of the blog
	 * @param bool $dsn data source name for PDO, TRUE for `in_memory`, FALSE for file
	 * @return PDO $pdo instance of PDO class or WP_Error
	 */
	private static function open_sqlite_db( $id, $dsn = FALSE ) {
		// For the sake of emergency, register the shutdown function
		self::$pdo = self::$stm = NULL;
		register_shutdown_function( 'IP_Geo_Block_Logs::close_sqlite_db' );

		// Set data source name
		$id = apply_filters( IP_Geo_Block::PLUGIN_NAME . '-live-log', ($dsn ? ':memory:' : get_temp_dir() . IP_Geo_Block::PLUGIN_NAME . "-${id}.sqlite") );

		try {
			$pdo = new PDO( 'sqlite:' . $id, null, null, array(
				PDO::ATTR_PERSISTENT => (bool)$dsn, // https://www.sqlite.org/inmemorydb.html
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 3, // reduce `SQLSTATE[HY000]: General error: 5 database is locked`
			) );
		}

		catch ( PDOException $e ) {
			return new WP_Error(  'Warn', $e->getMessage() );
		}

		$pdo->exec( "CREATE TABLE IF NOT EXISTS " . self::TABLE_LOGS . " (
			No INTEGER PRIMARY KEY AUTOINCREMENT,
			blog_id integer DEFAULT 1 NOT NULL,
			time bigint NOT NULL,
			ip varchar(40) NOT NULL,
			asn varchar(8) NULL,
			hook varchar(8) NOT NULL,
			auth integer DEFAULT 0 NOT NULL,
			code varchar(2) DEFAULT 'ZZ' NOT NULL,
			result varchar(8) NULL,
			method varchar("     . IP_GEO_BLOCK_MAX_STR_LEN . ") NOT NULL,
			user_agent varchar(" . IP_GEO_BLOCK_MAX_STR_LEN . ") NULL,
			headers varchar("    . IP_GEO_BLOCK_MAX_TXT_LEN . ") NULL,
			data text NULL
		);" ); // int or FALSE

		return $pdo;
	}

	public static function close_sqlite_db() {
		if ( self::$pdo && ! is_wp_error( self::$pdo ) ) {
			@self::$pdo->rollBack(); // `@` is just for the exception without valid transaction
			self::$stm = NULL;
			self::$pdo = NULL;
		}
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
	 * @param boolean $record record logs (TRUE) or not
	 */
	public static function record_logs( $hook, $validate, $settings, $record = TRUE ) {
		// get data
		$agent = self::get_user_agent();
		$heads = self::get_http_headers();
		$posts = self::get_post_data( $hook, $validate, $settings );
		$method = $_SERVER['REQUEST_METHOD'] . '[' . $_SERVER['SERVER_PORT'] . ']:' . $_SERVER['REQUEST_URI'];

		// mark if malicious upload exists
		if ( isset( $validate['upload'] ) )
			$validate['result'] .= '*';

		// anonymize ip address
		if ( ! empty( $settings['anonymize'] ) )
			$validate['ip'] = preg_replace( '/\d{1,3}$/', '***', $validate['ip'] );

		if ( $record ) {
			// count the number of rows for each hook
			global $wpdb;
			$table = $wpdb->prefix . self::TABLE_LOGS;
			$count = (int)$wpdb->get_var( "SELECT count(*) FROM `$table`" );

			// Can't start transaction on the assumption that the storage engine is innoDB.
			// So there are some cases where logs are excessively deleted.
			$sql = $wpdb->prepare(
				"DELETE FROM `$table` ORDER BY `time` ASC LIMIT %d",
				max( 0, $count - (int)$settings['validation']['maxlogs'] + 1 )
			) and $wpdb->query( $sql ) or self::error( __LINE__ );

			// insert into DB
			$sql = $wpdb->prepare(
				"INSERT INTO `$table`
				(`time`, `ip`, `asn`, `hook`, `auth`, `code`, `result`, `method`, `user_agent`, `headers`, `data`)
				VALUES (%d, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s)",
				$_SERVER['REQUEST_TIME'],
				$validate['ip'],
				$validate['asn'],
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
				IP_Geo_Block::PLUGIN_NAME . '-backup-dir', $settings['validation']['backup'], $hook
			) ) {
				self::backup_logs(
					$hook, $validate, $method, $agent, $heads, $posts, $dir
				);
			}
		}

		if ( get_transient( IP_Geo_Block::PLUGIN_NAME . '-live-log' ) ) {
			// skip self command
			global $pagenow;
			if ( 'admin-ajax.php' === $pagenow && isset( $_POST['action'] ) && 'ip_geo_block' === $_POST['action'] && isset( $_POST['cmd'] ) && 0 === strpos( $_POST['cmd'], 'live-' ) )
				return;

			// database file not available
			if ( is_wp_error( self::$pdo = self::open_sqlite_db( $id = get_current_blog_id(), $settings['live_update']['in_memory'] ) ) ) {
				self::error( __LINE__, self::$pdo->get_error_message() );
				return;
			}

			try {
				self::$stm = self::$pdo->prepare( // possibly throw an PDOException
					'INSERT INTO ' . self::TABLE_LOGS . ' (blog_id, time, ip, asn, hook, auth, code, result, method, user_agent, headers, data) ' .
					'VALUES      ' .                    ' (      ?,    ?,  ?,   ?,    ?,    ?,    ?,      ?,      ?,          ?,       ?,    ?);'
				); // example: http://php.net/manual/en/pdo.lobs.php
				self::$stm->bindParam(  1, $id,                      PDO::PARAM_INT );
				self::$stm->bindParam(  2, $_SERVER['REQUEST_TIME'], PDO::PARAM_INT );
				self::$stm->bindParam(  3, $validate['ip'],          PDO::PARAM_STR );
				self::$stm->bindParam(  4, $validate['asn'],         PDO::PARAM_STR );
				self::$stm->bindParam(  5, $hook,                    PDO::PARAM_STR );
				self::$stm->bindParam(  6, $validate['auth'],        PDO::PARAM_INT );
				self::$stm->bindParam(  7, $validate['code'],        PDO::PARAM_STR );
				self::$stm->bindParam(  8, $validate['result'],      PDO::PARAM_STR );
				self::$stm->bindParam(  9, $method,                  PDO::PARAM_STR );
				self::$stm->bindParam( 10, $agent,                   PDO::PARAM_STR );
				self::$stm->bindParam( 11, $heads,                   PDO::PARAM_STR );
				self::$stm->bindParam( 12, $posts,                   PDO::PARAM_STR );
				self::$pdo->beginTransaction(); // possibly throw an PDOException
				self::$stm->execute();          // TRUE or FALSE
				self::$pdo->commit();           // possibly throw an PDOException
				self::$stm->closeCursor();      // TRUE or FALSE
			}

			catch ( PDOException $e ) {
				@self::$pdo->rollBack(); // `@` is just for the exception without valid transaction
				self::error( __LINE__, $e->getMessage() );
			}

			self::$stm = NULL; // explicitly close the connection
			self::$pdo = NULL; // explicitly close the connection
		}
	}

	/**
	 * Catch and release the authority for live log
	 *
	 * @return TRUE or WP_Error
	 */
	public static function catch_live_log() {
		$user = IP_Geo_Block_Util::get_current_user_id();
		$auth = get_transient( IP_Geo_Block::PLUGIN_NAME . '-live-log' );

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
	 * Restore the live log
	 *
	 * @return array or WP_Error
	 */
	public static function restore_live_log( $hook, $settings ) {
		if ( is_wp_error( $ret = self::catch_live_log() ) )
			return $ret;

		if ( is_wp_error( self::$pdo = self::open_sqlite_db( $id = get_current_blog_id(), $settings['live_update']['in_memory'] ) ) )
			return new WP_Error( 'Warn', self::$pdo->get_error_message() );

		try {
			self::$pdo->beginTransaction(); // possibly throw an PDOException
			if ( self::$stm = self::$pdo->query( "SELECT hook, time, ip, code, result, asn, method, user_agent, headers, data FROM " . self::TABLE_LOGS . " WHERE blog_id = ${id};" ) ) {
				$result = self::$stm->fetchAll( PDO::FETCH_NUM ); // array or FALSE
				self::$pdo->exec( "DELETE FROM " . self::TABLE_LOGS . " WHERE blog_id = ${id};" ); // int or FALSE
			}
			self::$pdo->commit();      // possibly throw an PDOException
			self::$stm->closeCursor(); // TRUE or FALSE
		}

		catch ( PDOException $e ) {
			@self::$pdo->rollBack(); // `@` is just for the exception without valid transaction
			$result = new WP_Error( 'Warn', __FILE__ . '(' . __LINE__ . ') ' . $e->getMessage() );
		}

		self::$stm = NULL; // explicitly close the connection
		self::$pdo = NULL; // explicitly close the connection

		return ! empty( $result ) ? $result : array();
	}

	/**
	 * Restore the validation log
	 *
	 * @param string $hook type of log name
	 * @return array log data
	 */
	public static function restore_logs( $hook = NULL ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_LOGS;

		$sql = "SELECT `hook`, `time`, `ip`, `code`, `result`, `asn`, `method`, `user_agent`, `headers`, `data` FROM `$table`";

		if ( ! $hook )
			$sql .= " ORDER BY `time` DESC"; // " ORDER BY `hook`, `time` DESC";
		else
			$sql .= $wpdb->prepare( " WHERE `hook` = '%s' ORDER BY `time` DESC", $hook );

		return $sql ? $wpdb->get_results( $sql, ARRAY_N ) : array();
	}

	/**
	 * Search logs by specific IP address
	 *
	 */
	public static function search_logs( $ip ) {
		return self::search_table( self::TABLE_LOGS, $ip, FALSE );
	}

	/**
	 * Get logs for a specified duration in the past
	 *
	 */
	public static function get_recent_logs( $duration = YEAR_IN_SECONDS ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_LOGS;

		$sql = $wpdb->prepare(
			"SELECT `time`, `ip`, `asn`, `hook`, `code`, `method`, `data` FROM `$table` WHERE `time` > %d", $_SERVER['REQUEST_TIME'] - $duration
		) and $result = $wpdb->get_results( $sql, ARRAY_A ) or self::error( __LINE__ );

		return $result;
	}

	/**
	 * Update statistics.
	 *
	 */
	public static function update_stat( $hook, $validate, $settings ) {
		// Restore statistics.
		if ( $stat = self::restore_stat() ) {

			$provider = isset( $validate['provider'] ) ? $validate['provider'] : 'ZZ';
			if ( empty( $stat['providers'][ $provider ] ) )
				$stat['providers'][ $provider ] = array( 'count' => 0, 'time' => 0.0 );

			$stat['providers'][ $provider ]['count']++; // undefined in auth_fail()
			$stat['providers'][ $provider ]['time' ] += (float)( isset( $validate['time'] ) ? $validate['time'] : 0 );

			if ( 'passed' !== $validate['result'] ) {
				// Blocked by type of IP address
				if ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
					++$stat['IPv4'];
				elseif ( filter_var( $validate['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
					++$stat['IPv6'];

				 ++$stat['blocked'  ];
				@++$stat['countries'][ $validate['code'] ];
				@++$stat['daystats' ][ mktime( 0, 0, 0 ) ][ $hook ];
			}

			if ( count( $stat['daystats'] ) > max( 30, min( 365, (int)$settings['validation']['recdays'] ) ) ) {
				reset( $stat['daystats'] ); // pointer to the top
				unset( $stat['daystats'][ key( $stat['daystats'] ) ] ); // unset at the top
			}

			// Record statistics.
			self::record_stat( $stat );
		}
	}

	/**
	 * Clear IP address cache.
	 *
	 */
	public static function clear_cache() {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;
		$wpdb->query( "TRUNCATE TABLE `$table`" ) or self::error( __LINE__ );
	}

	/**
	 * Search cache by specific IP address
	 *
	 */
	public static function search_cache( $ip ) {
		return self::search_table( IP_Geo_Block::CACHE_NAME, $ip, TRUE );
	}

	/**
	 * Restore cache
	 *
	 */
	public static function restore_cache() {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;
		$result = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A ) or self::error( __LINE__ );

		// transform DB to cache format
		$cache = $hash = array();
		foreach ( $result as $key => $val ) {
			$ip = $val['ip'];
			unset( $val['ip'] );
			$cache[ $ip ] = $val;
		}

		// sort by 'time'
		foreach ( $cache as $key => $val ) {
			$hash[ $key ] = $val['time'];
		}

		array_multisort( $hash, SORT_DESC, $cache );

		return $cache;
	}

	/**
	 * Update cache
	 *
	 */
	public static function update_cache( $cache ) {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;

		$sql = $wpdb->prepare(
			"INSERT INTO `$table`
			(`time`, `ip`, `asn`, `hook`, `auth`, `code`, `fail`, `call`, `host`)
			VALUES (%d, %s, %s, %s, %d, %s, %d, %d, %s)
			ON DUPLICATE KEY UPDATE 
			`time` = VALUES(`time`),
			`hook` = VALUES(`hook`),
			`auth` = VALUES(`auth`),
			`code` = VALUES(`code`),
			`fail` = VALUES(`fail`),
			`call` = VALUES(`call`)",
			$cache['time'],
			$cache['ip'  ],
			$cache['asn' ],
			$cache['hook'],
			$cache['auth'],
			$cache['code'],
			$cache['fail'],
			$cache['call'],
			$cache['host']
		) and $wpdb->query( $sql ) or self::error( __LINE__ );
	}

	/**
	 * Delete cache entry by IP address
	 *
	 */
	public static function delete_cache_entry( $entry ) {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;
		$result = TRUE;

		foreach ( $entry as $ip ) {
			$sql = $wpdb->prepare( "DELETE FROM `$table` WHERE `ip` = %s", $ip )
			and $result &= ( FALSE !== $wpdb->query( $sql ) ) or self::error( __LINE__ );
		}

		return $result;
	}

	/**
	 * Delete expired cache
	 *
	 */
	public static function delete_expired_cache( $cache_time ) {
		global $wpdb;
		$table = $wpdb->prefix . IP_Geo_Block::CACHE_NAME;

		$sql = $wpdb->prepare(
			"DELETE FROM `$table` WHERE `time` < %d", $_SERVER['REQUEST_TIME'] - $cache_time
		) and $result = ( FALSE !== $wpdb->query( $sql ) ) or self::error( __LINE__ );

		return $result;
	}

	/**
	 * SQL Error handling
	 *
	 */
	private static function error( $line, $msg = FALSE ) {
		if ( FALSE === $msg ) {
			global $wpdb;
			$msg = $wpdb->last_error;
		}

		if ( $msg ) {
			if ( class_exists( 'IP_Geo_Block_Admin', FALSE ) )
				IP_Geo_Block_Admin::add_admin_notice( 'error', __FILE__ . ' (' . $line . ') ' . $msg );

			error_log( __FILE__ . ' (' . $line . ') ' . $msg );
		}
	}

}