<?php
/**
 * This is the test vector for WordPress Access Emulator
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2015 tokkonopapa
 */

if ( isset( $_GET['wp-load'] ) )
	include_once('../../../../wp-load.php');
if ( isset( $_GET['echo'] ) )
	echo htmlspecialchars( $_GET['echo'] );
exit;
