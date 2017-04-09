<?php
/**
 * Class to provide IPv4 calculations
 *
 * PHP versions 4, 5 and 7
 *
 * @link http://php.net/manual/en/function.ip2long.php#82397
 * @link http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5#answer-14841828
 */

class Net_IPv4 {
	public static function ipInNetwork( $ip, $cidr ) {
		list ( $net, $mask ) = explode ( '/', $cidr );
		return ( ip2long( $ip ) & ~ ( ( 1 << ( 32 - $mask ) ) - 1 ) ) == ip2long( $net );
	}
}