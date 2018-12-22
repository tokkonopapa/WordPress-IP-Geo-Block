<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.15
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */
class_exists( 'IP_Geo_Block_API', FALSE ) or die;

function ip_geo_block_setup_maxmind() {
	$path = dirname( __FILE__ );

	// GeoLite2 requires PHP 5.4+ (WordPress 3.7 requires PHP 5.2.4)
	if ( version_compare( PHP_VERSION, '5.4' ) >= 0 )
		require_once  $path . '/class-maxmind-geolite2.php';

	// GeoLite Legacy
	if ( file_exists( $path . '/class-maxmind-legacy.php' ) )
		require_once $path . '/class-maxmind-legacy.php';
}

ip_geo_block_setup_maxmind();
