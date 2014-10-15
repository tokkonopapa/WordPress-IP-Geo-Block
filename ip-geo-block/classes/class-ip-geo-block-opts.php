<?php
/**
 * IP Geo Block Options
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://tokkono.cute.coocan.jp/blog/slow/
 * @copyright 2013, 2014 tokkonopapa
 */

class IP_Geo_Block_Options {

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 */
	private static $option_table = array(

		// settings (should be read on every page that has comment form)
		'ip_geo_block_settings' => array(
			'version'         => '1.2.1', // Version of option data
			// from version 1.0
			'providers'       => array(), // List of providers and API keys
			'comment'         => array(   // Message on the comment form
				'pos'         => 0,       // Position (0:none, 1:top, 2:bottom)
				'msg'         => NULL,    // Message text on comment form
			),
			'matching_rule'   => 0,       // 0:white list, 1:black list
			'white_list'      => NULL,    // Comma separeted country code
			'black_list'      => NULL,    // Comma separeted country code
			'timeout'         => 5,       // Timeout in second
			'response_code'   => 403,     // Response code
			'save_statistics' => FALSE,   // Save statistics
			'clean_uninstall' => FALSE,   // Remove all savings from DB
			// from version 1.1
			'cache_hold'      => 10,      // Max entries in cache
			'cache_time'      => HOUR_IN_SECONDS, // @since 3.5
			// from version 1.2
			'flags'           => array(), // Multi purpose flags
			'login_fails'     => 5,       // Max counts of login fail
			'validation'      => array(   // Action hook for validation
				'comment'     => TRUE,    // For comment spam
				'login'       => FALSE,   // For login intrusion
				'admin'       => FALSE,   // For admin intrusion
			),
			'update'          => array(   // Updating IP address DB
				'auto'        => TRUE,    // Auto updating of DB file
				'retry'       => 0,       // Number of retry to download
				'cycle'       => 30,      // Updating cycle (days)
			),
			'maxmind'         => array(   // Maxmind
				'ipv4_path'   => NULL,    // Path to IPv4 DB file
				'ipv6_path'   => NULL,    // Path to IPv6 DB file
				'ipv4_last'   => NULL,    // Last-Modified of DB file
				'ipv6_last'   => NULL,    // Last-Modified of DB file
			),
			'ip2location'     => array(   // IP2Location
				'ipv4_path'   => NULL,    // Path to IPv4 DB file
				'ipv6_path'   => NULL,    // Path to IPv6 DB file
				'ipv4_last'   => NULL,    // Last-Modified of DB file
				'ipv6_last'   => NULL,    // Last-Modified of DB file
			),
		),

		// statistics (should be read when comment has posted)
		'ip_geo_block_statistics' => array(
			'passed'    => NULL,
			'blocked'   => NULL,
			'unknown'   => NULL,
			'IPv4'      => NULL,
			'IPv6'      => NULL,
			'countries' => array(),
			'providers' => array(),
		),
	);

	/**
	 * I/F for option table
	 *
	 */
	public static function get_table( $key = NULL ) {
		return $key ? self::$option_table[ $key ] : self::$option_table;
	}

	/**
	 * Upgrade option table
	 *
	 */
	public static function upgrade() {
		require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-api.php' );

		// find IP2Location DB
		$tmp = array(
			WP_CONTENT_DIR . '/ip2location/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-tags/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-variables/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-blocker/database.bin',
		);

		// get path to IP2Location DB
		$ip2 = NULL;
		foreach ( $tmp as $name ) {
			if ( is_readable( $name ) ) {
				$ip2 = $name;
				break;
			}
		}

		$default = self::get_table();
		$key = array_keys( $default );

		if ( FALSE === ( $settings = get_option( $key[0] ) ) ) {
			$ip = apply_filters(
				IP_Geo_Block::PLUGIN_SLUG . '-ip-addr', $_SERVER['REMOTE_ADDR']
			);
			$args = IP_Geo_Block::get_request_headers( $default[ $key[0] ] );

			// get country code from admin's IP address and set it into white list
			foreach ( array( 'ipinfo.io', 'Telize', 'IP-Json' ) as $provider ) {
				if ( $provider = IP_Geo_Block_API::get_class_name( $provider ) ) {
					$name = new $provider( NULL );
					if ( $tmp = $name->get_country( $ip, $args ) ) {
						$default[ $key[0] ]['white_list'] = $tmp;
						break;
					}
				}
			}

			// set IP2Location
			$default[ $key[0] ]['ip2location']['ipv4_path'] = $ip2;

			// create new option table
			$settings = $default[ $key[0] ];
			add_option( $key[0], $default[ $key[0] ] );
			add_option( $key[1], $default[ $key[1] ] );
		}

		else {
			// update format of option settings
			if ( version_compare( $settings['version'], '1.1' ) < 0 ) {
				foreach ( array( 'cache_hold', 'cache_time' ) as $tmp )
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
			}

			if ( version_compare( $settings['version'], '1.2' ) < 0 ) {
				foreach ( array( 'order', 'ip2location' ) as $tmp )
					unset( $settings[ $tmp ] );

				foreach ( explode( ' ', 'flags login_fails validation update maxmind ip2location' ) as $tmp )
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
			}

			if ( version_compare( $settings['version'], '1.2.1' ) < 0 ) {
				$tmp = get_option( $key[1] );
				delete_option( $key[1] );
				add_option( $key[1], $tmp ); // re-create as autoload
			}

			// update IP2Location
			$settings['ip2location']['ipv4_path'] = $ip2;

			// finally update version number
			$settings['version'] = $default[ $key[0] ]['version'];

			// update option table
			update_option( $key[0], $settings );
		}

		// return upgraded settings
		return $settings;
	}

}