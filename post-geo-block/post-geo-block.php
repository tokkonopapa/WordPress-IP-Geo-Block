<?php
/**
 * The WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   Post_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013 tokkonopapa
 *
 * @wordpress-plugin
 * Plugin Name:       Post Geo Block
 * Plugin URI:        https://github.com/tokkonopapa/Wordpress-Post-Geo-Block
 * Description:       It will block the comments posted from outside the specified countries.
 * Version:           0.9.0
 * Author:            tokkonopapa
 * Author URI:        http://tokkono.cute.coocan.jp/blog/slow/
 * Text Domain:       post-geo-block
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/tokkonopapa/Wordpress-Post-Geo-Block
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Global definition
 *----------------------------------------------------------------------------*/
define( 'POST_GEO_BLOCK_DEBUG', FALSE ); // output log
define( 'POST_GEO_BLOCK_PATH', plugin_dir_path( __FILE__ ) ); // @since 2.8
define( 'POST_GEO_BLOCK_BASE', plugin_basename( __FILE__ ) ); // @since 1.5

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/**
 * Load class
 *
 */
require_once( POST_GEO_BLOCK_PATH . 'classes/class-post-geo-block.php' );

/**
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Post_Geo_Block', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Post_Geo_Block', 'deactivate' ) );

/**
 * Instantiate class
 *
 */
add_action( 'plugins_loaded', array( 'Post_Geo_Block', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/**
 * The code below is intended to to give the lightest footprint possible.
 *
 */
if ( is_admin() ) {

	require_once( POST_GEO_BLOCK_PATH . 'admin/class-post-geo-block-admin.php' );
	add_action( 'plugins_loaded', array( 'Post_Geo_Block_Admin', 'get_instance' ) );
}
