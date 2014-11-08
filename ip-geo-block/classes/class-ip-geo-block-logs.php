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
		// user agent string (should be sanitized before rendering)
		$agent = wp_check_invalid_utf8( $_SERVER['HTTP_USER_AGENT'] );
		$agent = preg_replace( '/[\f\n\r\t\v\0]/', '', $agent );
		$agent = substr( $agent, 0, IP_GEO_BLOCK_MAX_POST_LEN );

		// post data (should be sanitized before rendering)
		// @link https://core.trac.wordpress.org/browser/trunk/src/xmlrpc.php
		if ( defined( 'XMLRPC_REQUEST' ) ) {
			global $HTTP_RAW_POST_DATA;
			$posts = wp_check_invalid_utf8( $HTTP_RAW_POST_DATA );
			$posts = substr( $posts, 0, IP_GEO_BLOCK_MAX_POST_LEN );
		} else {
			$posts = implode( ',', array_keys( $_POST ) );
			foreach ( explode( ',', $settings['validation']['postkey'] ) as $key ) {
				$val = wp_check_invalid_utf8( $_POST[ $key ] ); // @since 2.8.0
				$val = substr( $val, 0, IP_GEO_BLOCK_MAX_POST_LEN );
				if ( 'pwd' === $key ) // mask password
					$val = str_repeat( '*', strlen( $val ) );
				$posts = str_replace( $key, "$key:$val", $posts );
			}
		}
		$posts = preg_replace( '/[\s\0]/', '', $posts );

		$log = sprintf(
			'%d,%s,%d,%s,%s,%s,%s,%s',
			time(),
			$validate['ip'],
			$validate['auth'],
			$validate['code'],
			$validate['result'],
			str_replace( ',', '‚', $agent ), // &#044; --> &#130;
			$_SERVER['SERVER_PORT'] . ':' . basename( $_SERVER['REQUEST_URI'] ),
			str_replace( ',', '‚', $posts )  // &#044; --> &#130;
		);

		$file = IP_GEO_BLOCK_PATH . "database/log-${hook}.php";
		if ( $fp = @fopen( $file, "c+" ) ) {
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
				$lines = $fstat['size'] ?
					explode( "\n", esc_textarea( fread( $fp, $fstat['size'] ) ) ) : array();
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