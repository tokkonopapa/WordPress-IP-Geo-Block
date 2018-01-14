<?php
/**
 * IP Geo Block API class library for Maxmind
 *
 * @version   1.1.10
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2018 tokkonopapa
 */
class_exists( 'IP_Geo_Block_API', FALSE ) or die;

$settings = IP_Geo_Block::get_option();

require_once dirname( __FILE__ ) . '/class-maxmind-geolite2.php';
//require_once dirname( __FILE__ ) . '/class-maxmind-legacy.php';

unset( $settings );
