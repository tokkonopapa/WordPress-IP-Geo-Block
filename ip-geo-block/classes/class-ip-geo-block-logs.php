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
	 * Save validation log
	 * @todo save into mySQL DB
	 *
	 * @param string $hook type of log name
	 * @param array $validate validation results
	 * @param array $settings option settings
	 */
	public static function save_log( $hook, $validate, $settings ) {
		// user agent string (should be sanitized)
		$sep = "\f\n\r\t\v\0"; // separator
		$agent = str_replace( $sep, "", $_SERVER['HTTP_USER_AGENT'] );
		$agent = substr( $agent, 0, IP_GEO_BLOCK_MAX_POST_LEN );

		// post data (should be sanitized)
		// https://core.trac.wordpress.org/browser/trunk/src/xmlrpc.php
		if ( defined( 'XMLRPC_REQUEST' ) && isset( $HTTP_RAW_POST_DATA ) ) {
			$posts = substr( $HTTP_RAW_POST_DATA, 0, IP_GEO_BLOCK_MAX_POST_LEN );
		} else {
			$postkey = "," . $settings['validation']['postkey'] . ",";
			foreach ( $_POST as $key => $val ) {
				if ( strpos( $postkey, ",$key," ) !== FALSE ) {
					// Mask password
					if ( 'pwd' === $key )
						$val = str_repeat( "*", min( IP_GEO_BLOCK_MAX_POST_LEN, strlen( $val ) ) );

					// Get truncated string with specified width
					// and set empty if it encounters invalid UTF8
					$val = substr( $val, 0, IP_GEO_BLOCK_MAX_POST_LEN );
					$val = ":" . wp_check_invalid_utf8( $val );
				} else {
					$val = "";
				}
				$posts .= " ${key}${val}";
			}
		}
		$posts = str_replace( $sep, "", $posts );

		$log = sprintf(
			"%d,%s,%d,%s,%s,%s,%s,%s",
			time(),
			$validate['ip'],
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			str_replace( ",", "‚", trim( $agent ) ), // &#044; --> &#130;
			$_SERVER['SERVER_PORT'] . ":" . basename( $_SERVER['REQUEST_URI'] ),
			str_replace( ",", "‚", trim( $posts ) )  // &#044; --> &#130;
		);

		$file = IP_GEO_BLOCK_PATH . "database/log-${hook}.php";
		if ( $fp = @fopen( $file, "c+" ) ) {
			if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
				$fstat = fstat( $fp );
				$lines = explode( "\n", fread( $fp, $fstat['size'] ) );

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
	 * Read validation log
	 *
	 * @param string $hook type of log name
	 * return array log data
	 */
	public static function read_log( $hook = NULL ) {
		$list = $hook ? array( $hook ) : array( 'comment', 'login', 'admin' );
		$result = array();

		foreach ( $list as $hook ) {
			$lines = array();
			$file = IP_GEO_BLOCK_PATH . "database/log-${hook}.php";
			if ( $fp = @fopen( $file, 'r' ) ) {
				$fstat = fstat( $fp );
				$lines = explode( "\n", esc_textarea( fread( $fp, $fstat['size'] ) ) );
				@fclose( $fp );
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