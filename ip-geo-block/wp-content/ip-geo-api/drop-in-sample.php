<?php
/**
 * Drop-in for IP Geo Block custom filters
 *
 * This file should be renamed to `drop-in.php`.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 */
if ( ! class_exists( 'IP_Geo_Block' ) ) {
	die;
}

/**
 * Example: Returns "404 Not found" to hide login page.
 * Note: Use IP_Geo_Block::add_filter() instead of add_filter()
 */
/*
function my_login_status( $code ) {
	return 404;
}

IP_Geo_Block::add_filter( 'ip-geo-block-login-status', 'my_login_status', 10, 1 );
//*/