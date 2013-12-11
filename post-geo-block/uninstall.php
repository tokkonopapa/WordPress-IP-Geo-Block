<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Post_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define uninstall functionality here
include plugin_dir_path( __FILE__ ) . 'classes/class-post-geo-block.php';
Post_Geo_Block::uninstall();
