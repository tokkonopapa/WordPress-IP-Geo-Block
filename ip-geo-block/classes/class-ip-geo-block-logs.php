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
define( 'IP_GEO_BLOCK_MAX_LOG_LEN', 100 );
define( 'IP_GEO_BLOCK_MAX_POST_LEN', 256 );

class IP_Geo_Block_Logs {

	/**
	 * Create, Delete, Clean logs
	 *
	 */
	public static function create_log() {
	}

	public static function delete_log() {
	}

	public static function clean_log( $hook = NULL ) {
		$list = $hook ? array( $hook ) : array( 'comment', 'login', 'admin' );

		$dir = trailingslashit(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-maxmind-dir', IP_GEO_BLOCK_DB_DIR )
		);

		foreach ( $list as $hook ) {
			@unlink( "${dir}log-${hook}.php" );
		}
	}

	/**
	 * Validate string whether utf8
	 * (code from wp_check_invalid_utf8() in wp-includes/formatting.php)
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
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/formatting.php
	 * @link https://core.trac.wordpress.org/browser/trunk/src/wp-includes/functions.php
	 */
	private static function truncate_utf8( $str, $regexp, $replace = '', $len = IP_GEO_BLOCK_MAX_POST_LEN ) {
		// remove unnecessary characters
		$str = @preg_replace( $regexp, $replace, $str );

		// limit the length of the string
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
						$str = substr( $str, 0, $length - (int)($j > 0) - $i );
						// validate whole characters
						$str = self::validate_utf8( $str );
						return '…' !== $str ? "${str}…" : '…';
					}
				}
			}

			// $str may not fit utf8
			return '…';
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
		$agent = self::truncate_utf8( $_SERVER['HTTP_USER_AGENT'], '/[\f\n\r\t\v]/' );

		// XML-RPC
		// @link https://core.trac.wordpress.org/browser/trunk/src/xmlrpc.php
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			global $HTTP_RAW_POST_DATA;
			$posts = self::truncate_utf8( $HTTP_RAW_POST_DATA, '/\s+</', '<' );
			// mask the password
			if ( preg_match_all( '/<string>(\S*?)<\/string>/', $posts, $matches ) >= 2 &&
			     strpos( $matches[1][1], home_url() ) !== 0 ) { // except pingback
				$val = str_repeat( '*', strlen( $matches[1][1] ) );
				$posts = str_replace( $matches[1][1], $val, $posts );
			}
		}

		// post data (should be sanitized before rendering)
		else {
			$posts = implode( ',', array_keys( $_POST ) );
			foreach ( explode( ',', $settings['validation']['postkey'] ) as $key ) {
				$val = self::truncate_utf8( $_POST[ $key ], '/\s/' );
				// mask the password
				if ( 'pwd' === $key )
					$val = str_repeat( '*', strlen( $val ) );
				$posts = str_replace( $key, "$key:$val", $posts );
			}
		}

		$log = sprintf(
			'%s,%s,%d,%s,%s,%s,%s,%s',
			$_SERVER['REQUEST_TIME'],
			$validate['ip'],
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			str_replace( ',', '‚', $agent ), // &#044; --> &#130;
			$_SERVER['SERVER_PORT'] . '[' . $_SERVER['REQUEST_METHOD'] . ']:' . basename( $_SERVER['REQUEST_URI'] ),
			str_replace( ',', '‚', $posts )  // &#044; --> &#130;
		);

		$dir = trailingslashit(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-maxmind-dir', IP_GEO_BLOCK_DB_DIR )
		);

		if ( $fp = @fopen( "${dir}log-${hook}.php", "c+" ) ) {
			if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
				$fstat = fstat( $fp );
				$lines = $fstat['size'] ?
					explode( "\n", fread( $fp, $fstat['size'] ) ) : array();

				array_shift( $lines );
				array_pop( $lines );
				array_unshift( $lines, $log );
				$lines = array_slice( $lines, 0, IP_GEO_BLOCK_MAX_LOG_LEN );

				rewind( $fp );
				fwrite( $fp, "<?php/*\n" . implode( "\n", $lines ) . "\n*/?>" );
				ftruncate( $fp, ftell( $fp ) );
				@flock( $fp, LOCK_UN | LOCK_NB );
			}

			@fclose( $fp );
		}
	}

	/**
	 * Restore the validation log
	 *
	 * @param string $hook type of log name
	 * return array log data
	 */
	public static function restore_log( $hook = NULL ) {
		$list = $hook ? array( $hook ) : array( 'comment', 'login', 'admin' );
		$result = array();

		$dir = trailingslashit(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-maxmind-dir', IP_GEO_BLOCK_DB_DIR )
		);

		foreach ( $list as $hook ) {
			if ( $fp = @fopen( "${dir}log-${hook}.php", 'r' ) ) {
				$fstat = fstat( $fp );
				$data = $fstat['size'] ? fread( $fp, $fstat['size'] ) : NULL;
				@fclose( $fp );

				// execute htmlspecialchars()
				$data = esc_textarea( $data ); // @since 3.1.0

				// consider to check the $data being empty or not
				$lines = explode( "\n", $data );
			}

			if ( ! empty( $lines ) ) {
				array_shift( $lines );
				array_pop  ( $lines );
				$result[ $hook ] = $lines;
			}
		}

		return $result;
	}

}