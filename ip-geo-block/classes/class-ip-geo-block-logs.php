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
	 * @todo save into mySQL DB
	 *
	 * @param string $hook type of log name
	 * @param array $validate validation results
	 * @param array $settings option settings
	 */
	public static function save_log( $hook, $validate, $settings ) {
		$file = IP_GEO_BLOCK_PATH . "database/log-${hook}.php";
		if ( $fp = @fopen( $file, "c+" ) ) {
			if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
				$fstat = fstat( $fp );
				$lines = $fstat['size'] ?
					explode( "\n", fread( $fp, $fstat['size'] ) ) : array();

				// replace separator (should be sanitized)
				$agent = preg_replace( "/\s+/", " ", $_SERVER['HTTP_USER_AGENT'] );

				// items to be saved into a log (should be sanitized)
				if ( empty( $settings['validation']['postkey'] ) ) {
					$items = array_keys( $_POST );
				} else {
					foreach ( explode( ",", $settings['validation']['postkey'] ) as $item ) {
						if ( isset( $_POST[ $item ] ) )
							$items[ $item ] = $_POST[ $item ];
					}
				}

				array_shift( $lines );
				array_pop  ( $lines );
				array_unshift(
					$lines,
					sprintf( "%d,%s,%s,%s,%s,%s,%s",
						time(),
						$validate['ip'],
						$validate['code'],
						$validate['result'],
						str_replace( ",", "‚", trim( $agent ) ), // &#044; --> &#130;
						basename( $_SERVER['REQUEST_URI'] ),
						str_replace( ",", "‚", json_encode( $items ) )
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