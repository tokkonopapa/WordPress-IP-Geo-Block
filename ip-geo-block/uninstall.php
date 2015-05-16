<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2015 tokkonopapa
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Define uninstall functionality here
if ( ! defined( 'IP_GEO_BLOCK_PATH' ) )
	define( 'IP_GEO_BLOCK_PATH', plugin_dir_path( __FILE__ ) ); // @since 2.8
include IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block.php';
IP_Geo_Block::uninstall();