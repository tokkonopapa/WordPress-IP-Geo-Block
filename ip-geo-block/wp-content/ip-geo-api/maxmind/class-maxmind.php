<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.14
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */
if ( class_exists( 'IP_Geo_Block_API', FALSE ) ) {

	$dir = dirname( __FILE__ );

	// GeoLite2 requires PHP 5.4+ (WordPress 3.7 requires PHP 5.2.4)
	if ( PHP_VERSION_ID >= 50400 )
		require_once  $dir . '/class-maxmind-geolite2.php';

	// GeoLite Legacy
	if ( file_exists( $dir . '/class-maxmind-legacy.php' ) )
		require_once $dir . '/class-maxmind-legacy.php';
}