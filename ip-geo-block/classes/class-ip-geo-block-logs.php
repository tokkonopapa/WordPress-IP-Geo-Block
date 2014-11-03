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
define( 'IP_GEO_BLOCK_LOG_LEN', 100 );

class IP_Geo_Block_Logs {

	/**
	 * Save validation log
	 * @todo implement sql
	 *
	 * @param string $hook type of log name
	 * @param array $validate validation results
	 */
	public static function save_log( $hook, $validate ) {
		$file = IP_GEO_BLOCK_PATH . "database/log-${hook}.php";
		if ( $fp = @fopen( $file, "c+" ) ) {
			if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
				$fstat = fstat( $fp );
				$lines = $fstat['size'] ?
					explode( "\n", fread( $fp, $fstat['size'] ) ) : array();

				// replace separator
				$agent = preg_replace( "/\s+/", " ", $_SERVER['HTTP_USER_AGENT'] );
				$posts = implode( " ", array_keys( $_POST ) );
				$agent = str_replace( ",",  "‚", trim( $agent ) ); // &#044; --> &#130;
				$posts = str_replace( ",",  "‚", trim( $posts ) ); // &#044; --> &#130;

				array_shift( $lines );
				array_pop  ( $lines );
				array_unshift(
					$lines,
					sprintf( "%d,%s,%s,%s,%s,%s,%s",
						time(),
						$validate['ip'],
						$validate['code'],
						$validate['result'],
						$agent, // should be sanitized on screen
						basename( $_SERVER['REQUEST_URI'] ),
						$posts  // should be sanitized on screen
					)
				);
				$lines = array_slice( $lines, 0, IP_GEO_BLOCK_LOG_LEN );

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
					explode( "\n", fread( $fp, $fstat['size'] ) ) : array();
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