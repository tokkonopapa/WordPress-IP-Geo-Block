<?php
/**
 * Handling of access log
 * @todo implement sql
 */
define( 'IP_GEO_BLOCK_LOG_LEN', 100 );

function ip_geo_block_save_log( $ip, $hook, $validate ) {
	$file = IP_GEO_BLOCK_PATH . "etc/log-${hook}.php";
	$size = @filesize( $file );

	if ( $fp = @fopen( $file, "c+" ) ) {
		if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
			$lines = $size ? explode( "\n", fread( $fp, $size ) ) : array();

			array_shift( $lines );
			array_pop  ( $lines );
			$lines = array_slice( $lines, -(IP_GEO_BLOCK_LOG_LEN-1) );

			array_push(
				$lines, 
				sprintf( "%d,%s,%s,%s,%s,%s\n",
					time(),
					$ip,
					$validate['code'],
					basename( $_SERVER['REQUEST_URI'] ),
					str_replace( ',', '‚', $_SERVER['HTTP_USER_AGENT'] ), // &#044; --> &#130;
					json_encode( $_COOKIE ) // should be sanitized
				)
			);

			rewind( $fp );
			ftruncate( $fp, 0 );
			fwrite( $fp, "<?php/*\n" . implode( "\n", $lines ) . "*/?>" );
			@flock( $fp, LOCK_UN | LOCK_NB );
		}

		@fclose( $fp );
	}
}

function ip_geo_block_read_log( $hook = NULL ) {
	$list = $hook ? array( $hook ) : array( 'comment', 'login', 'admin' );
	$result = array();

	foreach ( $list as $hook ) {
		$lines = array();
		$file = IP_GEO_BLOCK_PATH . "etc/log-${hook}.php";
		$size = @filesize( $file );

		if ( $fp = @fopen( $file, 'r' ) ) {
			if ( @flock( $fp, LOCK_EX | LOCK_NB ) ) {
				$lines = $size ? explode( "\n", fread( $fp, $size ) ) : array();
				@flock( $fp, LOCK_UN | LOCK_NB );
			}
			@fclose( $fp );
		}

		array_shift( $lines );
		array_pop  ( $lines );
		$result[ $hook ] = $lines;
	}

	return $result;
}