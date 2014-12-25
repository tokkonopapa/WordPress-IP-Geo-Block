<?php
/**
 * IP Geo Block - Options
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
			'version'         => '1.3.1', // This table version (not package)
			// since version 1.0
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
			// since version 1.1
			'cache_hold'      => 10,      // Max entries in cache
			'cache_time'      => HOUR_IN_SECONDS, // @since 3.5
			// since version 1.2, 1.3
			'login_fails'     => 5,       // Limited number of login attempts
			'validation'      => array(   // Action hook for validation
			    'comment'     => TRUE,    // Validate on comment post
			    'login'       => FALSE,   // Validate on login
			    'admin'       => FALSE,   // Validate on admin
			    'ajax'        => FALSE,   // Validate on admin ajax
			    'xmlrpc'      => TRUE,    // Validate on xmlrpc
			    'proxy'       => NULL,    // $_SERVER variables for IPs
			    'reclogs'     => 0,       // 1:blocked 2:passed 3:unauth 4:auth 5:all
			    'postkey'     => '',      // Keys in $_POST
			    // since version 1.3.1
			    'maxlogs'     => 100,     // Max number of rows of log
			    'backup'      => NULL,    // Absolute path to backup file
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

		// statistics (autoloaded since version 1.2.1)
		'ip_geo_block_statistics' => array(
			'blocked'   => 0,
			'unknown'   => 0,
			'IPv4'      => 0,
			'IPv6'      => 0,
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
		// find IP2Location DB
		$ip2 = NULL;
		$tmp = array(
			WP_CONTENT_DIR . '/ip2location/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-tags/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-variables/database.bin',
			WP_CONTENT_DIR . '/plugins/ip2location-blocker/database.bin',
		);

		foreach ( $tmp as $name ) {
			if ( is_readable( $name ) ) {
				$ip2 = $name;
				break;
			}
		}

		$default = self::get_table();
		$key = array_keys( $default );

		if ( FALSE === ( $settings = get_option( $key[0] ) ) ) {
			// get country code from admin's IP address and set it into white list
			$name = array( 'ipinfo.io', 'Telize', 'IP-Json' ); shuffle( $name );
			$tmp = IP_Geo_Block::get_geolocation( $_SERVER['REMOTE_ADDR'], $name );
			$default[ $key[0] ]['white_list'] = $tmp['countryCode'];

			// update local goelocation database files
			$default[ $key[0] ]['ip2location']['ipv4_path'] = $ip2;

			// save package version number
			$default[ $key[0] ]['version'] = IP_Geo_Block::VERSION;

			// create new option table
			$settings = $default[ $key[0] ];
			add_option( $key[0], $default[ $key[0] ] );
			add_option( $key[1], $default[ $key[1] ] );
		}

		else {
			if ( version_compare( $settings['version'], '1.1' ) < 0 ) {
				foreach ( array( 'cache_hold', 'cache_time' ) as $tmp )
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
			}

			if ( version_compare( $settings['version'], '1.2' ) < 0 ) {
				foreach ( array( 'order', 'ip2location' ) as $tmp )
					unset( $settings[ $tmp ] );

				foreach ( explode( ' ', 'login_fails update maxmind ip2location' ) as $tmp )
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
			}

			if ( version_compare( $settings['version'], '1.2.1' ) < 0 ) {
				$tmp = get_option( $key[1] );
				delete_option( $key[1] );
				add_option( $key[1], $tmp ); // re-create as autoload
			}

			if ( version_compare( $settings['version'], '1.3.0' ) < 0 ) {
				unset( $settings['validation'] );
				$settings['validation'] = $default[ $key[0] ]['validation'];
			}

			if ( version_compare( $settings['version'], '1.3.1' ) < 0 ) {
				$settings['validation']['proxy'] =
				$settings['validation']['proxy'] ? 'HTTP_X_FORWARDED_FOR' : NULL;
				foreach ( array( 'maxlogs', 'backup' ) as $tmp )
					$settings['validation'][ $tmp ] = $default[ $key[0] ]['validation'][ $tmp ];
			}

			// update local goelocation database files
			$settings['ip2location']['ipv4_path'] = $ip2;

			// save package version number
			$settings['version'] = IP_Geo_Block::VERSION;

			// update option table
			update_option( $key[0], $settings );
		}

		// return upgraded settings
		return $settings;
	}

}