<?php
/**
 * This is the test vector for WordPress Access Emulator
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2015 tokkonopapa
 */

if ( isset( $_GET['wp-load'] ) && (int)$_GET['wp-load'] ) {
	$home = substr( __FILE__, 0, strpos( __FILE__, '/wp-content/' ) );
	include_once  "$home/wp-load.php";
}

if ( isset( $_GET['echo'] ) )
	echo htmlspecialchars( $_GET['echo'], ENT_QUOTES, 'UTF-8' );

exit;
