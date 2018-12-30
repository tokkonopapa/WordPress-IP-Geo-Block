<?php
class IP_Geo_Block_Admin_Rewrite {

	/**
	 * Instance of this class.
	 */
	private static $instance = NULL;

	// private values
	private $doc_root = NULL;    // document root
	private $base_uri = NULL;    // plugins base uri
	private $config_file = NULL; // `.htaccess` or `.user.ini`
	private $wp_dirs = array();  // path to `plugins` and `themes` from document root

	// template of rewrite rule in wp-content/(plugins|themes)/
	private $rewrite_rule = array(
		'.htaccess' => array(
			'plugins' => array(
				'# BEGIN IP Geo Block',
				'<IfModule mod_rewrite.c>',
				'RewriteEngine on',
				'RewriteBase %REWRITE_BASE%',
				'RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$',
				'RewriteRule ^.*\.php$ rewrite.php [L]',
				'</IfModule>',
				'# END IP Geo Block',
			),
			'themes' => array(
				'# BEGIN IP Geo Block',
				'<IfModule mod_rewrite.c>',
				'RewriteEngine on',
				'RewriteBase %REWRITE_BASE%',
				'RewriteRule ^.*\.php$ rewrite.php [L]',
				'</IfModule>',
				'# END IP Geo Block',
			),
		),
		'.user.ini' => array(
			'plugins' => array(
				'; BEGIN IP Geo Block%ADDITIONAL%',
				'auto_prepend_file = "%IP_GEO_BLOCK_PATH%rewrite-ini.php"',
				'; END IP Geo Block',
			),
			'themes' => array(
				'; BEGIN IP Geo Block%ADDITIONAL%',
				'auto_prepend_file = "%IP_GEO_BLOCK_PATH%rewrite-ini.php"',
				'; END IP Geo Block',
			),
		),
//		https://www.wordfence.com/blog/2014/05/nginx-wordfence-falcon-engine-php-fpm-fastcgi-fast-cgi/
//		'nginx' => array(
//			'plugins' => array(
//				'# BEGIN IP Geo Block',
//				'location ~ %REWRITE_BASE%rewrite.php$ {}',
//				'location %WP_CONTENT_DIR%/plugins/ {',
//				'    rewrite ^%WP_CONTENT_DIR%/plugins/.*\.php$ %REWRITE_BASE%rewrite.php break;',
//				'}',
//				'# END IP Geo Block',
//			'themes' => array(
//				'# BEGIN IP Geo Block',
//				'location %WP_CONTENT_DIR%/themes/ {',
//				'    rewrite ^%WP_CONTENT_DIR%/themes/.*\.php$ %REWRITE_BASE%rewrite.php break;',
//				'}',
//				'# END IP Geo Block',
//			),
//		),
	);

	private function __construct() {
		// https://stackoverflow.com/questions/25017381/setting-php-document-root-on-webserver
		$this->doc_root = str_replace( DIRECTORY_SEPARATOR, '/', str_replace( $_SERVER['SCRIPT_NAME'], '',  $_SERVER['SCRIPT_FILENAME'] ) );
		$this->base_uri = str_replace( $this->doc_root,     '',  str_replace( DIRECTORY_SEPARATOR,     '/', IP_GEO_BLOCK_PATH           ) );

		// target directories (WP_CONTENT_DIR can be defined in wp-config.php as an aliased or symbolic linked path)
		$path = str_replace( $this->doc_root, '', str_replace( DIRECTORY_SEPARATOR, '/', realpath( WP_CONTENT_DIR ) ) );
		$this->wp_dirs = array(
			'plugins'   => $path . '/plugins/',
			'themes'    => $path . '/themes/',
		);

		// Apache in wp-includes/vars.php
		global $is_apache;
		if ( ! empty( $is_apache ) )
			$this->config_file = '.htaccess';

		// CGI/FastCGI SAPI (cgi, cgi-fcgi, fpm-fcgi)
		elseif ( version_compare( PHP_VERSION, '5.3' ) >= 0 && FALSE !== strpos( php_sapi_name(), 'cgi' ) )
			$this->config_file = ini_get( 'user_ini.filename' );
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	private static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Remove empty element from the array
	 *
	 * @param  array contents of configuration file
	 * @return array updated array of contents
	 */
	private function remove_empty( $content ) {
		while ( FALSE !== ( $tmp = reset( $content ) ) ) {
			if ( strlen( trim( $tmp ) ) ) {
				break;
			} else {
				array_shift( $content );
			}
		}

		while ( FALSE !== ( $tmp = end( $content ) ) ) {
			if ( strlen( trim( $tmp ) ) ) {
				break;
			} else {
				array_pop( $content );
			}
		}

		return $content;
	}

	/**
	 * Extract the block of rewrite rule
	 *
	 * @param  array contents of configuration file
	 * @return array list of begin and end
	 */
	private function find_rewrite_block( $content ) {
		return preg_grep(
			'/^\s*?[#;]\s*?(?:BEGIN|END)\s*?IP Geo Block\s*?$/i', (array)$content
		);
	}

	/**
	 * Get the path of .htaccess in wp-content/plugins/themes/
	 *
	 * @param  string 'plugins' or 'themes'
	 * @return string absolute path to the .htaccess or NULL
	 */
	private function get_rewrite_file( $which ) {
		if ( $this->config_file )
			return $this->doc_root . $this->wp_dirs[ $which ] . $this->config_file;
		else
			return NULL; /* NOT SUPPORTED */
	}

	/**
	 * Get contents in .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @return array contents of configuration file or WP_Error
	 */
	private function get_rewrite_rule( $which ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

		// check the existence of configuration file
		$file = $this->get_rewrite_file( $which );
		$exist = $file ? $fs->exists( $file ) : FALSE;

		// check permission
		if ( $exist ) {
			if ( ! $fs->is_readable( $file ) ) {
				return new WP_Error( 'Error',
					sprintf( __( 'Unable to read <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $file ) . ' ' .
					sprintf( __( 'Or please refer to %s to set it manually.', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How to fix permission troubles? | IP Geo Block">How to fix permission troubles?</a>' )
				);
			}
		}

		// get file contents as an array
		$exist = $fs->get_contents_array( $file );
		return FALSE !== $exist ? $exist : array();
	}

	/**
	 * Put contents to .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string $which 'plugins' or 'themes'
	 * @param  array contents of configuration file
	 * @return  bool TRUE (success) or FALSE (failure)
	 */
	private function put_rewrite_rule( $which, $content ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

		$file = $this->get_rewrite_file( $which );

		if ( ! $file || FALSE === $fs->put_contents( $file, implode( PHP_EOL, $content ) ) ) {
			$this->show_message(
				sprintf( __( 'Unable to write <code>%s</code>. Please check the permission.', 'ip-geo-block' ), $file ) . ' ' .
				sprintf( __( 'Or please refer to %s to set it manually.', 'ip-geo-block' ), '<a href="https://www.ipgeoblock.com/codex/how-to-fix-permission-troubles.html" title="How to fix permission troubles? | IP Geo Block">How to fix permission troubles?</a>' )
			);
			return FALSE;
		}

		// if content is empty then remove file
		$content = $this->remove_empty( $content );
		return empty( $content ) ? $fs->delete( $file ) : TRUE;
	}

	/**
	 * Check if the block of rewrite rule exists
	 *
	 * @param string 'plugins' or 'themes'
	 * @return  bool TRUE (found), FALSE (not found or unavailable) or WP_Error
	 */
	private function get_rewrite_stat( $which ) {
		if ( $this->config_file ) {
			$content = $this->get_rewrite_rule( $which );

			if ( is_wp_error( $content ) )
				return $content;

			$block = $this->find_rewrite_block( $content );

			if ( '.htaccess' === $this->config_file ) {
				return empty( $block ) ? FALSE : TRUE;
			}

			else {
				if ( empty( $block ) ) {
					$block = preg_grep( '/auto_prepend_file/i', $content );

					if ( empty( $block ) ) {
						return FALSE; // rewrite rule is not found in configuration file
					}

					else {
						return new WP_Error( 'Error', sprintf(
							__( '&#8220;auto_prepend_file&#8221; already defined in %s.', 'ip-geo-block' ),
							$this->get_rewrite_file( $which )
						) );
					}
				}

				else {
					return TRUE; // rewrite rule already exists in configuration file
				}
			}
		}

		return -1; /* NOT SUPPORTED */
	}

	/**
	 * Remove the block of rewrite rule
	 *
	 * @param  array contents of configuration file
	 * @param  array contents to be removed
	 * @return array array of contents without rewrite rule
	 */
	private function remove_rewrite_block( $content, $block ) {
		$block = array_reverse( $block, TRUE );

		reset( $block );
		while( FALSE !== current( $block ) ) {
			$key_end   = key( $block ); $val_end   = current( $block ); next( $block );
			$key_begin = key( $block ); $val_begin = current( $block ); next( $block );
			if ( NULL !== $key_end && NULL !== $key_begin ) {
				array_splice( $content, $key_begin, $key_end - $key_begin + 1 );
			}
		}

		return $content;
	}

	/**
	 * Append the block of rewrite rule
	 *
	 * @param string 'plugins' or 'themes'
	 * @param  array contents of configuration file
	 * @return array array of contents with the block of rewrite rule
	 */
	private function append_rewrite_block( $which, $content ) {
		if ( $type = $this->config_file ) {
			// in case that `.user.ini` is configured differently
			if ( '.htaccess' !== $type && '.user.ini' !== $type )
				$type = '.user.ini';

			// in case that another `.user.ini` in ascendant directory
			$additional = '';
			if ( '.user.ini' === $type ) {
				require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
				$fs = IP_Geo_Block_FS::init( __FUNCTION__ );

				$dir = dirname( IP_GEO_BLOCK_PATH ); // `/wp-content/plugins`
				$ini = $this->config_file;
				$doc = $this->doc_root;

				do {
					// avoid loop just in case
					if ( ( $next = dirname( $dir ) ) !== $dir )
						$dir = $next;
					else
						break;

					if ( $fs->exists( "$dir/$ini" ) ) {
						$tmp = $fs->get_contents_array( "$dir/$ini" );
						$tmp = preg_replace( '/^\s*(auto_prepend_file.*)$/', '; $1', $tmp );
						$tmp = $this->remove_empty( $tmp );

						if ( ! empty( $tmp ) )
							$additional = PHP_EOL . PHP_EOL . implode( PHP_EOL, $tmp ) . PHP_EOL;

						break;
					}
				} while ( $dir !== $doc );
			}

			return array_merge(
				$content,
				str_replace(
					array( '%REWRITE_BASE%', '%WP_CONTENT_DIR%', '%IP_GEO_BLOCK_PATH%', '%ADDITIONAL%' ),
					array( $this->base_uri,    WP_CONTENT_DIR,     IP_GEO_BLOCK_PATH,    $additional   ),
					$this->rewrite_rule[ $type ][ $which ]
				)
			);
		}

		return array();
	}

	/**
	 * Add rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 * @return  bool TRUE (found), FALSE (not found or unavailable)
	 */
	private function add_rewrite_rule( $which ) {
		$stat = $this->get_rewrite_stat( $which );

		if ( is_wp_error( $stat ) ) {
			$this->show_message( $stat->get_error_message() );
			return FALSE;
		}

		elseif ( TRUE === $stat ) {
			return TRUE;
		}

		elseif ( FALSE === $stat ) {
			$content = $this->get_rewrite_rule( $which );

			if ( is_wp_error( $content ) ) {
				$this->show_message( $content->get_error_message() );
				return FALSE;
			}

			$content = $this->append_rewrite_block( $which, $content );
			return $this->put_rewrite_rule( $which, $content );
		}

		return -1; /* NOT SUPPORTED */
	}

	/**
	 * Delete rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 * @return  bool TRUE (found), FALSE (not found or unavailable)
	 */
	private function del_rewrite_rule( $which ) {
		$stat = $this->get_rewrite_stat( $which );

		if ( is_wp_error( $stat ) ) {
			$this->show_message( $stat->get_error_message() );
			return FALSE;
		}

		elseif ( FALSE === $stat ) {
			return TRUE;
		}

		elseif ( TRUE === $stat ) {
			$content = $this->get_rewrite_rule( $which );

			if ( is_wp_error( $content ) ) {
				$this->show_message( $content->get_error_message() );
				return FALSE;
			}

			$block = $this->find_rewrite_block( $content );
			$content = $this->remove_rewrite_block( $content, $block );
			return $this->put_rewrite_rule( $which, $content );
		}

		return -1; /* NOT SUPPORTED */
	}

	/**
	 * Show notice message
	 *
	 */
	private function show_message( $msg ) {
		if ( class_exists( 'IP_Geo_Block_Admin', FALSE ) )
			IP_Geo_Block_Admin::add_admin_notice( 'error', $msg );
	}

	/**
	 * Check rewrite rules
	 *
	 */
	public static function check_rewrite_all() {
		$rewrite = self::get_instance();

		$status = array();
		foreach ( array_keys( $rewrite->rewrite_rule['.htaccess'] ) as $key ) {
			$stat = $rewrite->get_rewrite_stat( $key );
			$status[ $key ] = is_wp_error( $stat ) ? FALSE : $stat;
		}

		return $status;
	}

	/**
	 * Activate all rewrite rules according to the settings
	 *
	 */
	public static function activate_rewrite_all( $options ) {
		$rewrite = self::get_instance();

		foreach ( array_keys( $rewrite->rewrite_rule['.htaccess'] ) as $key ) {
			if ( $options[ $key ] )
				// if it fails to write, then return FALSE
				$options[ $key ] = $rewrite->add_rewrite_rule( $key ) ? TRUE : FALSE;
			else
				// regardless of the result, return FALSE
				$options[ $key ] = $rewrite->del_rewrite_rule( $key ) ? FALSE : FALSE;
		}

		return $options;
	}

	/**
	 * Deactivate all rewrite rules
	 *
	 */
	public static function deactivate_rewrite_all() {
		$rewrite = self::get_instance();

		foreach ( array_keys( $rewrite->rewrite_rule['.htaccess'] ) as $key ) {
			$rewrite->del_rewrite_rule( $key );
		}

		return TRUE;
	}

	/**
	 * Return list of target directories.
	 *
	 */
	public static function get_dirs() {
		$rewrite = self::get_instance();
		return str_replace( $rewrite->doc_root, '', $rewrite->wp_dirs );
	}

	/**
	 * Return configuration file type.
	 *
	 */
	public static function get_config_file() {
		$rewrite = self::get_instance();
		return $rewrite->config_file;
	}

}