<?php
/**
 * IP Geo Block - Options
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block_Opts {

	/**
	 * Default values of option table to be cached into options database table.
	 *
	 */
	private static $option_table = array(

		// settings (should be read on every page that has comment form)
		'ip_geo_block_settings' => array(
			'version'         => '2.2.6', // This table version (not package)
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
			'save_statistics' => TRUE,    // Record validation statistics
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
			    'xmlrpc'      => 1,       // Validate on xmlrpc (1:country 2:close)
			    'proxy'       => NULL,    // $_SERVER variables for IPs
			    'reclogs'     => 1,       // 1:blocked 2:passed 3:unauth 4:auth 5:all
			    'postkey'     => NULL,    // Keys in $_POST
			    // since version 1.3.1
			    'maxlogs'     => 100,     // Max number of rows of log
			    'backup'      => NULL,    // Absolute path to directory for backup
			    // since version 2.1.0
			    'plugins'     => 0,       // Validate on wp-content/plugins
			    'themes'      => 0,       // Validate on wp-content/themes
			    // since version 2.3.0
			    'includes'    => 3,       // for wp-includes/
			    'uploads'     => 3,       // for UPLOADS/uploads
			    'languages'   => 3,       // for wp-content/language
			),
			'update'          => array(   // Updating IP address DB
			    'auto'        => TRUE,    // Auto updating of DB file
			    'retry'       => 0,       // Number of retry to download
			    'cycle'       => 30,      // Updating cycle (days)
			),
			// since version 2.0.8
			'priority'        => 0,       // Action priority for WP-ZEP
			// since version 2.2.0
			'anonymize'       => FALSE,   // Anonymize IP address to hide privacy
			'signature'       => '/wp-config.php,/passwd', // malicious signature
			'extra_ips'       => array(   // Additional IP validation
			    'white_list'  => NULL,    // White list of IP addresses
			    'black_list'  => NULL,    // Black list of IP addresses
			),
			'rewrite'         => array(   // Apply rewrite rule
			    'plugins'     => FALSE,   // for wp-content/plugins
			    'themes'      => FALSE,   // for wp-content/themes
			),
			'Maxmind'         => array(   // Maxmind
			    // since version 2.2.2
			    'ipv4_path'   => NULL,    // Path to IPv4 DB file
			    'ipv6_path'   => NULL,    // Path to IPv6 DB file
			    // since version 2.2.1
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
			),
			'IP2Location'     => array(   // IP2Location
			    // since version 2.2.2
			    'ipv4_path'   => NULL,    // Path to IPv4 DB file
			    'ipv6_path'   => NULL,    // Path to IPv6 DB file
			    // since version 2.2.1
			    'ipv4_last'   => 0,       // Last-Modified of DB file
			    'ipv6_last'   => 0,       // Last-Modified of DB file
			),
			// since version 2.2.3
			'api_dir'         => NULL,    // Path to geo-location API
			// since version 2.2.5
			'exception'       => array(   // list of exceptional
			    'plugins'     => array(), // for pliugins
			    'themes'      => array(), // for themes
			),
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
			$version = $default[ $key[0] ]['version'] = IP_Geo_Block::VERSION;

			// create new option table
			$settings = $default[ $key[0] ];
			add_option( $key[0], $default[ $key[0] ] );
		}

		else {
			$version = $settings['version'];

			// refresh if it's too old
			if ( version_compare( $version, '2.0' ) < 0 ) {
				$settings = $default[ $key[0] ];
			}

			if ( version_compare( $version, '2.0.8' ) < 0 )
				$settings['priority'] = $default[ $key[0] ]['priority'];

			if ( version_compare( $version, '2.1.0' ) < 0 ) {
				foreach ( array( 'plugins', 'themes' ) as $tmp ) {
					$settings['validation'][ $tmp ] = $default[ $key[0] ]['validation'][ $tmp ];
				}
			}

			if ( version_compare( $version, '2.2.0' ) < 0 ) {
				foreach ( array( 'anonymize', 'signature', 'extra_ips', 'rewrite' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}

				foreach ( array( 'admin', 'ajax' ) as $tmp ) {
					if ( $settings['validation'][ $tmp ] == 2 )
						$settings['validation'][ $tmp ] = 3; // WP-ZEP + Block by country
				}
			}

			if ( version_compare( $version, '2.2.1' ) < 0 ) {
				foreach ( array( 'Maxmind', 'IP2Location' ) as $tmp ) {
					$settings[ $tmp ] = $default[ $key[0] ][ $tmp ];
				}
			}

			if ( version_compare( $version, '2.2.2' ) < 0 ) {
				$tmp = get_option( 'ip_geo_block_statistics' );
				$tmp['daystats'] = array();
				IP_Geo_Block_Logs::record_stat( $tmp );
				delete_option( 'ip_geo_block_statistics' ); // @since 1.2.0

				foreach ( array( 'maxmind', 'ip2location' ) as $tmp ) {
					unset( $settings[ $tmp ] );
				}
			}

			if ( version_compare( $version, '2.2.3' ) < 0 )
				$settings['api_dir'] = $default[ $key[0] ]['api_dir'];

			if ( version_compare( $version, '2.2.5' ) < 0 ) {
				// https://wordpress.org/support/topic/compatibility-with-ag-custom-admin
				$arr = array();

				foreach ( explode( ',', $settings['signature'] ) as $tmp ) {
					$tmp = trim( $tmp );
					if ( 'wp-config.php' === $tmp || 'passwd' === $tmp )
						$tmp = '/' . $tmp;
					array_push( $arr, $tmp );
				}

				$settings['signature'] = implode( ',', $arr );

				foreach ( array( 'plugins', 'themes' ) as $tmp ) {
					$settings['exception'][ $tmp ] = $default[ $key[0] ]['exception'][ $tmp ];
				}
			}

			if ( version_compare( $version, '2.2.6' ) < 0 ) {
				$settings['signature']               = str_replace( " ", "\n", $settings['signature'] );
				$settings['extra_ips']['white_list'] = str_replace( " ", "\n", $settings['extra_ips']['white_list'] );
				$settings['extra_ips']['black_list'] = str_replace( " ", "\n", $settings['extra_ips']['black_list'] );

				foreach ( array( 'plugins', 'themes' ) as $tmp ) {
					$arr = array_keys( $settings['exception'][ $tmp ] );
					if ( ! empty( $arr ) && ! is_numeric( $arr[0] ) ) {
						$settings['exception'][ $tmp ] = $arr;
					}
				}
			}

			// save package version number
			$settings['version'] = IP_Geo_Block::VERSION;
		}

		// install addons for IP Geolocation database API
		if ( ! $settings['api_dir'] || version_compare( $version, '2.2.6' ) < 0 )
			$settings['api_dir'] = self::install_api( $settings );

		// update option table
		update_option( $key[0], $settings );

		// return upgraded settings
		return $settings;
	}

	/**
	 * Install / Uninstall APIs
	 *
	 */
	public static function install_api( $settings ) {
		$src = IP_GEO_BLOCK_PATH . IP_Geo_Block::GEOAPI_NAME;
		$dst = self::get_api_dir( $settings );

		if ( $src !== $dst )
			self::recurse_copy( $src, $dst );

		return $dst;
	}

	public static function delete_api( $settings ) {
		if ( @is_writable( $dir = self::get_api_dir( $settings ) ) )
			self::recurse_rmdir( $dir );
	}

	private static function get_api_dir( $settings ) {
		// wp-content
		$dir = $settings['api_dir'] ? dirname( $settings['api_dir'] ) : WP_CONTENT_DIR;

		if ( ! @is_writable( $dir ) ) {
			// wp-content/plugins/ip-geo-block
			$dir = IP_GEO_BLOCK_PATH;

			if ( ! @is_writable( $dir ) ) {
				// wp-content/uploads
				$dir = wp_upload_dir();
				$dir = $dir['basedir'];
			}
		}

		// filter hook in `functions.php` doesn't work at activation
		return trailingslashit(
			apply_filters( IP_Geo_Block::PLUGIN_SLUG . '-api-dir', $dir )
		) . IP_Geo_Block::GEOAPI_NAME; // must add `ip-geo-api` for basename
	}

	// http://php.net/manual/function.copy.php#91010
	private static function recurse_copy( $src, $dst ) {
		$src = trailingslashit( $src );
		$dst = trailingslashit( $dst );
		! @is_dir( $dst ) and @mkdir( $dst );
		if ( $dir = @opendir( $src ) ) {
			while( FALSE !== ( $file = readdir( $dir ) ) ) {
				if ( '.' !== $file && '..' !== $file ) {
					if ( @is_dir( $src.$file ) )
						self::recurse_copy( $src.$file, $dst.$file );
					else
						@copy( $src.$file, $dst.$file );
				}
			}
			closedir( $dir );
		}
	}

	// http://php.net/manual/function.rmdir.php#110489
	private static function recurse_rmdir( $dir ) {
		$dir = trailingslashit( $dir );
		$files = array_diff( @scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			if ( is_dir( $dir.$file ) )
				self::recurse_rmdir( $dir.$file );
			else
				@unlink( $dir.$file );
		}
		return @rmdir( $dir );
	} 
}