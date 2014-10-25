<?php
/**
 * IP Geo Block
 *
 * A WordPress plugin that blocks undesired access base on geolocation of IP address.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 *
 * Plugin Name:       IP Geo Block
 * Plugin URI:        http://wordpress.org/plugins/ip-geo-block/
 * Description:       A WordPress plugin that will protect against malicious access to the login form and admin area, and will also block any spam comments posted from undesired countries based on geolocation of IP address.
 * Version:           1.3.0
 * Author:            tokkonopapa
 * Author URI:        https://github.com/tokkonopapa/WordPress-IP-Geo-Block
 * Text Domain:       ip-geo-block
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */
define( 'DEBUG_LEN', 500 ); // Ring buffer length

date_default_timezone_set( 'Asia/Tokyo' );

function debug_log( $msg, $file = null, $line = null, $trace = false ) {
	$file = basename( $file );
	$msg = date( "Y/m/d,D,H:i:s" )
		. ( $file ? " $file"  : '' )
		. ( $line ? "($line)" : '' )
		. ' ' . trim( $msg );
	if ( $trace ) $msg .= ' ' . print_r( debug_backtrace(), true );
	$dir = __DIR__ . '/';
	$fp = @fopen( $dir . basename( __FILE__, '.php' ) . '.log', 'c+' ); // PHP 5 >= 5.2.6
	$stat = @fstat( $fp );
	$buff = explode( "\n", @fread( $fp, $stat['size'] ) . $msg );
	$buff = array_slice( $buff, -DEBUG_LEN );
	@rewind( $fp );
	@fwrite( $fp, implode( "\n", $buff ) . "\n" );
	@fflush( $fp );
	@ftruncate( $fp, ftell( $fp ) );
	@fclose( $fp );
}

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Global definition
 *----------------------------------------------------------------------------*/
define( 'IP_GEO_BLOCK_PATH', plugin_dir_path( __FILE__ ) ); // @since 2.8
define( 'IP_GEO_BLOCK_BASE', plugin_basename( __FILE__ ) ); // @since 1.5

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/**
 * Load class
 *
 */
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block.php' );

/**
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'IP_Geo_Block', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'IP_Geo_Block', 'deactivate' ) );

/**
 * Instantiate class
 *
 */
add_action( 'plugins_loaded', array( 'IP_Geo_Block', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/**
 * Load class in case of admin
 *
 */
if ( is_admin() ) {
	require_once( IP_GEO_BLOCK_PATH . 'admin/class-ip-geo-block-admin.php' );
	add_action( 'plugins_loaded', array( 'IP_Geo_Block_Admin', 'get_instance' ) );
}