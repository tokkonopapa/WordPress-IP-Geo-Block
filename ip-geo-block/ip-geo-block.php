<?php
/**
 * IP Geo Block
 *
 * A WordPress plugin that blocks any comments posted from outside your nation.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 *
 * Plugin Name:       IP Geo Block
 * Plugin URI:        http://wordpress.org/plugins/ip-geo-block/
 * Description:       It will block any spam comments posted from outside the specified countries.
 * Version:           1.0.3
 * Author:            tokkonopapa
 * Author URI:        http://tokkono.cute.coocan.jp/blog/slow/
 * Text Domain:       ip-geo-block
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/tokkonopapa/WordPress-IP-Geo-Block
 */

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