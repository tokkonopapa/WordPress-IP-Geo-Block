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
class_exists( 'IP_Geo_Block_API', FALSE ) or die;

$dir = dirname( __FILE__ );

// GeoLite2 requires PHP 5.4+ (WordPress 3.7 requires PHP 5.2.4)
PHP_VERSION_ID >= 50400 and require_once "$dir/class-maxmind-geolite2.php";

// GeoLite Legacy
file_exists( "$dir/class-maxmind-legacy.php" ) and require_once "$dir/class-maxmind-legacy.php";

unset( $dir );
