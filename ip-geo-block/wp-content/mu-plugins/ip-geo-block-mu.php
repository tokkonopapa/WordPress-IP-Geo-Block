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
 * @copyright 2013-2016 tokkonopapa
 *
 * Plugin Name:       IP Geo Block (mu)
 * Plugin URI:        http://wordpress.org/plugins/ip-geo-block/
 * Description:       It blocks any spams, login attempts and malicious access to the admin area posted from outside your nation, and also prevents zero-day exploit.
 * Version:           3.0.0
 * Author:            tokkonopapa
 * Author URI:        http://www.ipgeoblock.com/
 * Text Domain:       ip-geo-block
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'IP_Geo_Block' ) ):

/*----------------------------------------------------------------------------*
 * Detect plugin. For use on Front End only.
 *----------------------------------------------------------------------------*/
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

$plugin = 'ip-geo-block/ip-geo-block.php';

if ( is_plugin_active( $plugin ) || is_plugin_active_for_network( $plugin ) ) {

	// Load plugin class
	require( WP_PLUGIN_DIR . '/' . $plugin );

	$plugin = IP_Geo_Block::get_option();

	// check setup had already done
	if ( version_compare( $plugin['version'], IP_Geo_Block::VERSION ) >= 0 && $plugin['matching_rule'] >= 0 ) {

		// Remove instanciation
		remove_action( 'plugins_loaded', array( 'IP_Geo_Block', 'get_instance' ) );

		// Instanciate immediately
		IP_Geo_Block::get_instance();
//		add_action( 'muplugins_loaded', array( 'IP_Geo_Block', 'get_instance' ) );
	}
}

unset( $plugin );

endif; // ! class_exists( 'IP_Geo_Block' )
