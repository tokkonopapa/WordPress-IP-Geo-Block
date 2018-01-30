<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.11
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */
class_exists( 'IP_Geo_Block_API', FALSE ) or die;

if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ):

	require_once dirname( __FILE__ ) . '/class-maxmind-geolite2.php';

else:

	require_once dirname( __FILE__ ) . '/class-maxmind-legacy.php';

endif;
