<?php
/**
 * IP Geo Block - Handling validation log
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013, 2014 tokkonopapa
 */
define( 'IP_GEO_BLOCK_MAX_POST_LEN', 256 );

class IP_Geo_Block_Logs {

	const TABLE_NAME = 'ip_geo_block';

	/**
	 * Create, Delete, Clean logs
	 *
	 */
	public static function create_log() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;

		if ( $wpdb->get_var( "show tables like '$table'" ) != $table ) {
			$sql = "CREATE TABLE `$table` (
 `No` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `time` int(10) unsigned NOT NULL DEFAULT 0,
 `ip` varchar(40) NOT NULL,
 `hook` varchar(8) NOT NULL,
 `auth` int(10) unsigned NOT NULL DEFAULT 0,
 `code` varchar(2) NOT NULL DEFAULT 'ZZ',
 `result` varchar(8) NULL,
 `method` varchar(255) NOT NULL,
 `user_agent` varchar(255) NULL,
 `data` text NULL,
 PRIMARY KEY (`No`),
 KEY `time` (`time`),
 KEY `hook` (`hook`)
) CHARACTER SET 'utf8'";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	public static function delete_log() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query( "DROP TABLE `$table`" );
	}

	public static function clean_log( $hook = NULL ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;

		if ( ! $hook ) {
			$wpdb->query( "TRUNCATE TABLE `$table`" );
		} else {
			$sql = $wpdb->prepare(
				"DELETE FROM `$table` WHERE `hook` = '%s'",
				$hook
			);
			$wpdb->query( $sql );
		}
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
		static $is_utf8;
		if ( ! isset( $is_utf8 ) )
			$is_utf8 = in_array(
				strtolower( get_option( 'blog_charset' ) ),
				array( 'utf8', 'utf-8' )
			);

		// handle utf8 only
		if ( ! $is_utf8 )
			return '…';

		// Check support for utf8 in the installed PCRE library
		static $utf8_pcre;
		if ( ! isset( $utf8_pcre ) )
			$utf8_pcre = @preg_match( '/^./u', 'a' );

		// if no support then reject $str for safety
		if ( ! $utf8_pcre )
			return '…';

		// preg_match fails when it encounters invalid UTF8 in $str
		if ( 1 === @preg_match( '/^./us', $str ) )
			return $str;

		return '…';
	}

	/**
	 * Truncate string as utf8
	 *
	 * @see http://jetpack.wp-a2z.org/oik_api/mbstring_binary_safe_encoding/
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/functions.php
	 */
	private static function truncate_utf8( $str, $regexp, $replace = '', $len = IP_GEO_BLOCK_MAX_POST_LEN ) {
		// remove unnecessary characters ('/[^\t\n\f\r]/')
		$str = @preg_replace( '/[\x00-\x08\x0b\x0e-\x1f\x7f]/', '', $str );
		$str = @preg_replace( $regexp, $replace, $str );

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
							return '…' !== $str ? "${str}…" : '…';
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
	 * Record the validation log
	 *
	 * This function record the user agent string and post data.
	 * The security policy for these data is as follows.
	 *
	 *   1. Record only utf8 under the condition that the site charset is utf8
	 *   2. Record by limiting the length of the string
	 *   3. Mask the password regardless of a state of the authentication
	 *
	 * @param string $hook type of log name
	 * @param array $validate validation results
	 * @param array $settings option settings
	 */
	public static function record_log( $hook, $validate, $settings ) {
		// user agent string (should be sanitized before rendering)
		$agent = self::truncate_utf8( $_SERVER['HTTP_USER_AGENT'], '/[\t\n\f\r]/' );

		// XML-RPC
		if ( 'xmlrpc' === $hook ) {
			global $HTTP_RAW_POST_DATA;
			$posts = self::truncate_utf8( $HTTP_RAW_POST_DATA, '/\s+</', '<' );

			// mask the password
			if ( 'passed' === $validate['result'] &&
			     preg_match_all( '/<string>(\S*?)<\/string>/', $posts, $matches ) >= 2 &&
			     strpos( $matches[1][1], home_url() ) !== 0 ) { // except pingback
				$val = str_repeat( '*', strlen( $matches[1][1] ) );
				$posts = str_replace( $matches[1][1], $val, $posts );
			}
			/*if ( FALSE !== ( $xml = @simplexml_load_string( $HTTP_RAW_POST_DATA ) ) ) {
				// mask the password
				if ( 'passed' === $validate['result'] &&
				     'wp.' === substr( $xml->methodName, 0, 3 ) ) {
					$xml->params->param[1]->value->string =
						str_repeat(
							'*', strlen( $xml->params->param[1]->value->string )
						);
				}
				$posts = self::truncate_utf8( json_encode( $xml ), '/["\\\\]/' );
			} else {
				$posts = 'xml parse error: malformed xml';
			}*/
		}

		// post data (should be sanitized before rendering)
		else {
			$keys = array_fill_keys( array_keys( $_POST ), NULL );
			foreach ( explode( ',', $settings['validation']['postkey'] ) as $key ) {
				if ( array_key_exists( $key, $_POST ) ) {
					$keys[ $key ] = $_POST[ $key ];

					// mask the password
					if ( 'passed' === $validate['result'] && 'pwd' === $key )
						$keys[ $key ] = str_repeat( '*', strlen( $keys[ $key ] ) );
				}
			}

			// Join array elements
			$posts = array();
			foreach ( $keys as $key => $val )
				$posts[] = $val ? "$key:$val" : "$key";
			$posts = self::truncate_utf8( implode( ',', $posts ), '/\s+/', ' ' );
		}

		// limit the maximum number of rows
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		$rows = $settings['validation']['max_logs'];

		$sql = $wpdb->prepare(
			"SELECT count(*) FROM `$table` WHERE `hook` = '%s'",
			$hook
		);
		if ( ( $count = (int)$wpdb->get_var( $sql ) ) >= $rows ) {
			$sql = $wpdb->prepare(
				"DELETE FROM `$table` WHERE `hook` = '%s' LIMIT %d",
				$hook, $count - $rows + 1
			);
			$wpdb->query( $sql );
		}

		// insert into DB
		$sql = $wpdb->prepare(
			"INSERT INTO `$table`
			(`time`, `ip`, `hook`, `auth`, `code`, `result`, `method`, `user_agent`, `data`)
			values (%d, %s, %s, %d, %s, %s, %s, %s, %s)",
			$_SERVER['REQUEST_TIME'],
			$validate['ip'],
			$hook,
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			$agent,
			$_SERVER['REQUEST_METHOD'] . '[' . $_SERVER['SERVER_PORT'] . ']:' . basename( $_SERVER['REQUEST_URI'] ),
			$posts
		);
		$wpdb->query( $sql );
	}

	/**
	 * Restore the validation log
	 *
	 * @param string $hook type of log name
	 * return array log data
	 */
	public static function restore_log( $hook = NULL ) {
		$list = $hook ? array( $hook ) : array( 'comment', 'login', 'admin', 'xmlrpc' );
		$result = array();

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;

		foreach ( $list as $hook ) {
			$sql = $wpdb->prepare( "SELECT
				`time`, `ip`, `code`, `result`, `method`, `user_agent`, `data`
				FROM `$table` WHERE `hook` = '%s' ORDER BY `time` DESC",
				$hook
			);
			$result[ $hook ] = $wpdb->get_results( $sql, ARRAY_N, 0 );
		}

		return $result;
	}

}