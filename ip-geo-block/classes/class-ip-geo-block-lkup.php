<?php
/**
 * IP Geo Block - DNS lookup
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2016 tokkonopapa
 */

class IP_Geo_Block_Lkup {

	/**
	 * Converts IP address to in_addr representation
	 *
	 */
	public static function inet_pton( $ip ) {
		// available on Windows platforms after PHP 5.3.0
		if ( function_exists( 'inet_pton' ) )
			return inet_pton( $ip );

		// http://stackoverflow.com/questions/14459041/inet-pton-replacement-function-for-php-5-2-17-in-windows
		else {
			// ipv4
			if ( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				if ( FALSE === strpos( $ip, ':' ) ) {
					$ip = pack( 'N', ip2long( $ip ) );
				} else {
					$ip = explode( ':', $ip );
					$ip = pack( 'N', ip2long( $ip[ count( $ip ) - 1 ] ) );
				}
			}

			// ipv6
			elseif ( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
				$ip = explode( ':', $ip );
				$parts = 8 - count( $ip );
				$res = '';
				$replaced = 0;
				foreach ( $ip as $seg ) {
					if ( $seg != '' ) {
						$res .= str_pad( $seg, 4, '0', STR_PAD_LEFT );
					} elseif ( $replaced == 0 ) {
						for ( $i = 0; $i <= $parts; $i++ )
							$res .= '0000';
						$replaced = 1;
					} elseif ( $replaced == 1 ) {
						$res .= '0000';
					}
				}
				$ip = pack( 'H' . strlen( $res ), $res );
			}
		}

		return $ip;
	}

	/**
	 * DNS lookup
	 *
	 */
	public static function gethostbyaddr( $ip ) {
		// available on Windows platforms after PHP 5.3.0
		if ( function_exists( 'gethostbyaddr' ) )
			$host = gethostbyaddr( $ip );

		// if not available
		if ( empty( $host ) ) {
			if ( function_exists( 'dns_get_record' ) ) {
				// generate in-addr.arpa notation
				if ( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
					$ptr = implode( ".", array_reverse( explode( ".", $ip ) ) ) . ".in-addr.arpa";
				}

				elseif ( FALSE !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
					$ptr = self::inet_pton( $ip );
					$ptr = implode(".", array_reverse( str_split( bin2hex( $ptr ) ) ) ) . ".ip6.arpa";
				}

				if ( isset( $ptr ) and $ptr = @dns_get_record( $ptr, DNS_PTR ) ) {
					$host = $ptr[0]['target'];
				}
			}
		}

		// For compatibility with versions before PHP 5.3.0
		// on some operating systems, try the PEAR class Net_DNS
		if ( empty( $host ) ) {
			set_include_path( IP_GEO_BLOCK_PATH . 'includes' . PATH_SEPARATOR . get_include_path() );
			require_once IP_GEO_BLOCK_PATH . 'includes/Net/DNS2.php';

			// use google public dns
			$r = new Net_DNS2_Resolver(
				array( 'nameservers' => array( '8.8.8.8' ) )
			);

			try {
				$result = $r->query( $ip, 'PTR' );
			}
			catch ( Net_DNS2_Exception $e ) {
				$result = $e->getMessage();
			}

			if ( isset( $result->answer ) ) {
				foreach ( $result->answer as $obj ) {
					if ( 'PTR' === $obj->type ) {
						$host = $obj->ptrdname;
						break;
					}
				}
			}
		}

		return isset( $host ) ? $host : $ip;
	}

	/**
	 * https://codex.wordpress.org/WordPress_Feeds
	 *
	 */
	public static function is_feed( $request_uri ) {
		return isset( $_GET['feed'] ) ?
			( preg_match( '!(?:comments-)?(?:feed|rss|rss2|rdf|atom)$!', $_GET['feed'] ) ? TRUE : FALSE ) :
			( preg_match( '!(?:comments/)?(?:feed|rss|rss2|rdf|atom)/?$!', $request_uri ) ? TRUE : FALSE );
	}

}