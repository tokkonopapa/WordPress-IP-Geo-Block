<?php
/**
 * IP Geo Block
 *
 * A WordPress plugin that blocks undesired access based on geolocation of IP address.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2017 tokkonopapa
 *
 * Plugin Name:       IP Geo Block
 * Plugin URI:        http://wordpress.org/plugins/ip-geo-block/
 * Description:       It blocks any spams, login attempts and malicious access to the admin area posted from outside your nation, and also prevents zero-day exploit.
 * Version:           3.0.4
 * Author:            tokkonopapa
 * Author URI:        http://www.ipgeoblock.com/
 * Text Domain:       ip-geo-block
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
defined( 'WPINC' ) or die;

if ( ! class_exists( 'IP_Geo_Block', FALSE ) ):

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
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-load.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php';
require IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php';

/**
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
function ip_geo_block_activate( $network_wide = FALSE ) {
	require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php';
	IP_Geo_Block_Activate::activate( $network_wide );
}

function ip_geo_block_deactivate( $network_wide = FALSE ) {
	require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-actv.php';
	IP_Geo_Block_Activate::deactivate( $network_wide );
}

register_activation_hook( __FILE__, 'ip_geo_block_activate' );
register_deactivation_hook( __FILE__, 'ip_geo_block_deactivate' );

/**
 * Instantiate class
 *
 */
add_action( 'plugins_loaded', array( 'IP_Geo_Block', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/**
 * Load class in case of wp-admin/*.php
 *
 */
if ( is_admin() ) {
	require IP_GEO_BLOCK_PATH . 'admin/class-ip-geo-block-admin.php';
	add_action( 'plugins_loaded', array( 'IP_Geo_Block_Admin', 'get_instance' ) );
}

/*----------------------------------------------------------------------------*
 * Emergent Functionality
 *----------------------------------------------------------------------------*/

/**
 * Invalidate blocking behavior in case yourself is locked out.
 *
 * How to use: Activate the following code and upload this file via FTP.
 */
/* -- ADD `/` TO THE TOP OR END OF THIS LINE TO ACTIVATE THE FOLLOWINGS -- *
function ip_geo_block_emergency( $validate ) {
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */

endif; // ! class_exists( 'IP_Geo_Block', FALSE )