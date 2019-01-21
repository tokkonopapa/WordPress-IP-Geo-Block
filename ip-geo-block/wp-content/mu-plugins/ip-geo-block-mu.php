<?php
/**
 * IP Geo Block
 *
 * A WordPress plugin that blocks undesired access based on geolocation of IP address.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 *
 * Plugin Name:       IP Geo Block (mu)
 * Plugin URI:        https://wordpress.org/plugins/ip-geo-block/
 * Description:       It blocks any spams, login attempts and malicious access to the admin area posted from outside your nation, and also prevents zero-day exploit.
 * Version:           3.0.0
 * Author:            tokkonopapa
 * Author URI:        https://www.ipgeoblock.com/
 * Text Domain:       ip-geo-block
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 */

// If this file is called directly, abort.
defined( 'WPINC' ) or die;

if ( ! class_exists( 'IP_Geo_Block', FALSE ) ):

// Avoud redirection loop
if ( 'wp-login.php' === basename( $_SERVER['SCRIPT_NAME'] ) && site_url() !== home_url() )
	return;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$ipgeoblock = 'ip-geo-block/ip-geo-block.php';

if ( is_plugin_active( $ipgeoblock ) || is_plugin_active_for_network( $ipgeoblock ) ) {

	// Load plugin class
	if ( file_exists( WP_PLUGIN_DIR . '/' . $ipgeoblock ) ) {
		require WP_PLUGIN_DIR . '/' . $ipgeoblock;

		$ipgeoblock = IP_Geo_Block::get_option();

		// check setup had already done
		if ( version_compare( $ipgeoblock['version'], IP_Geo_Block::VERSION ) >= 0 && $ipgeoblock['matching_rule'] >= 0 ) {

			// Remove instanciation
			remove_action( 'plugins_loaded', 'ip_geo_block_update' );
			remove_action( 'plugins_loaded', array( 'IP_Geo_Block', 'get_instance' ) );

			// Upgrade then instanciate immediately
			IP_Geo_Block::get_instance();
		}
	}

	else {
		add_action( 'admin_notices', 'ip_geo_block_mu_notice' );
	}

}

unset( $ipgeoblock );

/**
 * Show global notice.
 *
 */
function ip_geo_block_mu_notice() {
	echo '<div class="notice notice-error is-dismissible"><p>';
	echo sprintf(
		__( 'Can\'t find IP Geo Block in your plugins directory. Please remove <code>%s</code> or re-install %s.', 'ip-geo-block' ),
		__FILE__,
		'<a href="https://wordpress.org/plugins/ip-geo-block/" title="IP Geo Block &mdash; WordPress Plugins">IP Geo Block</a>'
	);
	echo '</p></div>' . "\n";
}

endif; // ! class_exists( 'IP_Geo_Block', FALSE )
