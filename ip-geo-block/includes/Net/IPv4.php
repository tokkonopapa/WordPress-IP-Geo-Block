<?php
/**
 * Class to provide IPv4 calculations
 *
 * PHP versions 4, 5 and 7
 *
 * @link https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5#answer-594134
 */

class Net_IPv4 {
	public static function ipInNetwork( $ip, $cidr ) {
		list( $subnet, $bitmask ) = explode( '/', $cidr );
		$bitmask = -1 << ( 32 - $bitmask );
		return ( ip2long( $ip ) & $bitmask ) === ( ip2long( $subnet ) & $bitmask );
	}
}