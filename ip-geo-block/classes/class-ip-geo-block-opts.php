<?php
/**
 * IP Geo Block - Options
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2013-2015 tokkonopapa
 */

class IP_Geo_Block_Options {

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 */
	private static $option_table = array(

		// settings (should be read on every page that has comment form)
		'ip_geo_block_settings' => array(
			'version'         => '2.2.1', // This table version (not package)
			// since version 1.0
			'providers'       => array(), // List of providers and API keys
			'comment'         => array(   // Message on the comment form
			    'pos'         => 0,       // Position (0:none, 1:top, 2:bottom)
			    'msg'         => NULL,    // Message text on comment form
			),
			'matching_rule'   => -1,      // -1:neither, 0:white list, 1:black list
			'white_list'      => NULL,    // Comma separeted country code
			'black_list'      => 'ZZ',    // Comma separeted country code
			'timeout'         => 5,       // Timeout in second
			'response_code'   => 403,     // Response code
			'save_statistics' => TRUE,    // Save statistics
			'clean_uninstall' => FALSE,   // Remove all savings from DB
			// since version 1.1
			'cache_hold'      => 10,      // Max entries in cache
			'cache_time'      => HOUR_IN_SECONDS, // @since 3.5
			// since version 1.2, 1.3
			'login_fails'     => 5,       // Limited number of login attempts
			'validation'      => array(   // Action hook for validation
			    'comment'     => TRUE,    // Validate on comment post
			    'login'       => 1,       // Validate on login
			    'admin'       => 1,       // Validate on admin (1:country 2:ZEP)
			    'ajax'        => 0,       // Validate on ajax/post (1:country 2:ZEP)
			    'xmlrpc'      => TRUE,    // Validate on xmlrpc
			    'proxy'       => NULL,    // $_SERVER variables for IPs
			    'reclogs'     => 1,       // 1:blocked 2:passed 3:unauth 4:auth 5:all
			    'postkey'     => '',      // Keys in $_POST
			    // since version 1.3.1
			    'maxlogs'     => 100,     // Max number of rows of log
			    'backup'      => NULL,    // Absolute path to directory for backup
			    // since version 2.1.0
			    'plugins'     => 0,       // Validate on wp-content/plugins
			    'themes'      => 0,       // Validate on wp-content/themes
			),
			'update'          => array(   // Updating IP address DB
			    'auto'        => TRUE,    // Auto updating of DB file
			    'retry'       => 0,       // Number of retry to download
			    'cycle'       => 30,      // Updating cycle (days)
			),
			'maxmind'         => array(   // Maxmind
			    'ipv4_path'   => NULL,    // Path to IPv4 DB file
			    'ipv6_path'   => NULL,    // Path to IPv6 DB file
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
			),
			'ip2location'     => array(   // IP2Location
			    'ipv4_path'   => NULL,    // Path to IPv4 DB file
			    'ipv6_path'   => NULL,    // Path to IPv6 DB file
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
			),
			// since version 2.0.8
			'priority'        => 0,       // Action priority for WP-ZEP
			// since version 2.2.0
			'anonymize'       => FALSE,   // Anonymize IP address to hide privacy
			'signature'       => 'wp-config.php,passwd', // malicious signature
			'extra_ips'       => array(   // Additional IP validation
			    'white_list'  => NULL,    // White list of IP addresses
			    'black_list'  => NULL,    // Black list of IP addresses
			),
			'rewrite'         => array(   // Backup of rewrite rule
			    'plugins'     => NULL,    // for wp-content/plugins
			    'themes'      => NULL,    // for wp-content/themes
			),
			// since version 2.2.1
			'Maxmind'         => array(   // Maxmind
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
			),
			'IP2Location'     => array(   // IP2Location
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
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
			// since version 2.1.5
			'daystats'  => array(),
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
		$default = self::get_table();
		$key = array_keys( $default );

		if ( FALSE === ( $settings = get_option( $key[0] ) ) ) {
			// save package version number
			$default[ $key[0] ]['version'] = IP_Geo_Block::VERSION;

			// create new option table
			$settings = $default[ $key[0] ];
			add_option( $key[0], $default[ $key[0] ] );
			add_option( $key[1], $default[ $key[1] ] );
		}

		else {
			if ( version_compare( $settings['version'], '1.1' ) < 0 ) {
				foreach ( array( 'cache_hold', 'cache_time' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}
			}

			if ( version_compare( $settings['version'], '1.2' ) < 0 ) {
				foreach ( array( 'order', 'ip2location' ) as $tmp ) {
					unset( $settings[ $tmp ] );
				}

				foreach ( explode( ' ', 'login_fails update maxmind ip2location' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}
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
				foreach ( array( 'maxlogs', 'backup' ) as $tmp ) {
					$settings['validation'][ $tmp ] = $default[ $key[0] ]['validation'][ $tmp ];
				}
			}

			if ( version_compare( $settings['version'], '2.0.8' ) < 0 )
				$settings['priority'] = $default[ $key[0] ]['priority'];

			if ( version_compare( $settings['version'], '2.1.0' ) < 0 ) {
				foreach ( array( 'plugins', 'themes' ) as $tmp ) {
					$settings['validation'][ $tmp ] = $default[ $key[0] ]['validation'][ $tmp ];
				}
			}

			if ( version_compare( $settings['version'], '2.2.0' ) < 0 ) {
				foreach ( array( 'anonymize', 'signature', 'extra_ips', 'rewrite' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}

				foreach ( array( 'admin', 'ajax' ) as $tmp ) {
					if ( $settings['validation'][ $tmp ] == 2 )
						$settings['validation'][ $tmp ] = 3; // WP-ZEP + Block by country
				}
			}

			if ( version_compare( $settings['version'], '2.2.1' ) < 0 ) {
				foreach ( array( 'maxmind', 'ip2location', 'Maxmind', 'IP2Location' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}
			}

			// save package version number
			$settings['version'] = IP_Geo_Block::VERSION;

			// update option table
			update_option( $key[0], $settings );
		}

		// put addons for IP Geolocation database API to wp-content/
		if ( ! file_exists( WP_CONTENT_DIR . '/ip-geo-api' ) )
			self::recurse_copy( IP_GEO_BLOCK_PATH . 'ip-geo-api', WP_CONTENT_DIR . '/ip-geo-api' );

		// return upgraded settings
		return $settings;
	}

	// http://php.net/manual/en/function.copy.php#91010
	// permission in mkdir() will be masked by umask()
	private static function recurse_copy( $src, $dst ) {
		if ( @mkdir( $dst ) and $dir = @opendir( $src ) ) {
			while( false !== ( $file = readdir( $dir ) ) ) {
				if ( '.' !== $file && '..' !== $file ) {
					if ( is_dir( "${src}/${file}" ) ) {
						self::recurse_copy( "${src}/${file}", "${dst}/${file}" );
					} else {
						copy( "${src}/${file}", "${dst}/${file}" );
					}
				}
			}
			closedir( $dir );
		}
	}
}