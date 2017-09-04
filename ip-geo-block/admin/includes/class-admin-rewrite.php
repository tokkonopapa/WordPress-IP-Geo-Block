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
	private $wp_dirs = array();  // path to `plugins` and `themes`

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
				'; BEGIN IP Geo Block',
				'auto_prepend_file = "%ABSPATH%wp-load.php"',
				'; END IP Geo Block',
			),
			'themes' => array(
				'; BEGIN IP Geo Block',
				'auto_prepend_file = "%ABSPATH%wp-load.php"',
				'; END IP Geo Block',
			),
		),
//		https://www.wordfence.com/blog/2014/05/nginx-wordfence-falcon-engine-php-fpm-fastcgi-fast-cgi/
//		'nginx' => array(
//			'plugins' => array(
//				'# BEGIN IP Geo Block',
//				'location ~ %REWRITE_BASE%rewrite.php$ {}',
//				'location %WP_CONTENT_DIR%/plugins/ {',
//				'    rewrite ^%WP_CONTENT_DIR%/plugins/.*/.*\.php$ %REWRITE_BASE%rewrite.php break;',
//				'}',
//				'# END IP Geo Block',
//			'themes' => array(
//				'# BEGIN IP Geo Block',
//				'location %WP_CONTENT_DIR%/themes/ {',
//				'    rewrite ^%WP_CONTENT_DIR%/themes/.*/.*\.php$ %REWRITE_BASE%rewrite.php break;',
//				'}',
//				'# END IP Geo Block',
//			),
//		),
	);

	private function __construct() {
		// http://stackoverflow.com/questions/25017381/setting-php-document-root-on-webserver
		$this->doc_root = str_replace( $_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME'] );
		$this->base_uri = str_replace( $this->doc_root, '', IP_GEO_BLOCK_PATH );

		// target directories
		$path = str_replace( $this->doc_root, '', WP_CONTENT_DIR );
		$this->wp_dirs = array(
			'plugins'   => $path . '/plugins/',
			'themes'    => $path . '/themes/',
		);

		// Apache in wp-includes/vars.php
		global $is_apache;
		if ( $is_apache )
			$this->config_file = '.htaccess';

		// CGI/FastCGI SAPI (cgi, cgi-fcgi, fpm-fcgi)
//		elseif ( FALSE !== strpos( php_sapi_name(), 'cgi' ) )
//			$this->config_file = ini_get( 'user_ini.filename' );
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	private static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Extract the block of rewrite rule
	 *
	 * @param array contents of configuration file
	 * @return array list of begin and end
	 */
	private function find_rewrite_block( $content ) {
		return preg_grep(
			'/^\s*?[#;]\s*?(?:BEGIN|END)\s*?IP Geo Block\s*?$/i',
			$content
		);
	}

	/**
	 * Get the path of .htaccess in wp-content/plugins/themes/
	 *
	 * @param string 'plugins' or 'themes'
	 * @return string absolute path to the .htaccess
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
	 * @return array contents of configuration file
	 */
	private function get_rewrite_rule( $which ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( 'get_rewrite_rule' );

		$file = $this->get_rewrite_file( $which );
		$exist = $file ? $fs->exists( $file ) : FALSE;

		// check permission
		if ( $exist ) {
			if ( ! $fs->is_readable( $file ) ) {
				$this->show_message( sprintf( 
					__( 'Unable to read %s. Please check the permission.', 'ip-geo-block' ), $file
				) );
				return FALSE;
			}
		} else {
			if ( ! $fs->is_readable( dirname( $file ) ) ) {
				$this->show_message( sprintf( 
					__( 'Unable to read %s. Please check the permission.', 'ip-geo-block' ), dirname( $file )
				) );
				return FALSE;
			}
		}

		// get file contents as an array
		return $exist ? $fs->get_contents_array( $file ) : array();
	}

	/**
	 * Put contents to .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @param array  contents of configuration file
	 */
	private function put_rewrite_rule( $which, $content ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-file.php';
		$fs = IP_Geo_Block_FS::init( 'put_rewrite_rule' );

		$file = $this->get_rewrite_file( $which );
		if ( ! $file || FALSE === $fs->put_contents( $file, implode( PHP_EOL, $content ) ) ) {
			$this->show_message( sprintf( 
				__( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ), $file
			) );
			return FALSE;
		}

		// if content is empty then remove file
		if ( empty( $content ) )
			return $fs->delete( $file );

		return TRUE;
	}

	/**
	 * Check if the block of rewrite rule exists
	 *
	 * @param  string 'plugins' or 'themes'
	 * @return bool   TRUE (found), FALSE (not found or unavailable)
	 */
	private function get_rewrite_stat( $which ) {
		if ( $this->config_file ) {
			if ( FALSE === ( $content = $this->get_rewrite_rule( $which ) ) )
				return -1; // not readable

			$block = $this->find_rewrite_block( $content );

			if ( '.htaccess' === $this->config_file ) {
				return empty( $block ) ? FALSE : TRUE;
			}

			else {
				if ( empty( $block ) ) {
					$block = preg_grep( '/auto_prepend_file/i', $content );

					if ( ! empty( $block ) ) {
						$this->show_message( sprintf( 
							__( '&#8220;auto_prepend_file&#8221; already defined in %s.', 'ip-geo-block' ), $this->get_rewrite_file( $which )
						) );
						return -1; // not available
					}

					return FALSE; // rewrite rule is not found in configuration file
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
	 * @return array array of contents without rewrite rule
	 */
	private function remove_rewrite_block( $content, $block ) {
		$block = array_reverse( $block, TRUE );

		if ( 2 <= count( $block ) ) {
			reset( $block );
			while (
				( list( $key_end,   $val_end   ) = each( $block ) ) &&
				( list( $key_begin, $val_begin ) = each( $block ) ) ) {
				array_splice( $content, $key_begin, $key_end - $key_begin + 1 );
			}
		}

		return $content;
	}

	/**
	 * Append the block of rewrite rule
	 *
	 * @param  string 'plugins' or 'themes'
	 * @param  array  name of configuration file
	 * @return array  array of contents with the block of rewrite rule
	 */
	private function append_rewrite_block( $which, $content ) {
		if ( $type = $this->config_file ) {
			// in case `.user.ini` is configured differently
			if ( '.htaccess' !== $type && '.user.ini' !== $type )
				$type = '.user.ini';

			return array_merge(
				$content,
				str_replace(
					array( '%REWRITE_BASE%', '%WP_CONTENT_DIR%', '%ABSPATH%' ),
					array( $this->base_uri,    WP_CONTENT_DIR,     ABSPATH   ),
					$this->rewrite_rule[ $type ][ $which ]
				)
			);
		} else {
			return array();
		}
	}

	/**
	 * Add rewrite rule to server configration
	 *
	 * @param  string 'plugins' or 'themes'
	 * @return bool   TRUE (found), FALSE (not found or unavailable)
	 */
	private function add_rewrite_rule( $which ) {
		// if rewrite stat is not TRUE or FALSE
		switch ( $this->get_rewrite_stat( $which ) ) {
		  case TRUE:
			return TRUE;

		  case FALSE:
			$content = $this->get_rewrite_rule( $which );
			$content = $this->append_rewrite_block( $which, $content );
			return $this->put_rewrite_rule( $which, $content );
		}

		return -1; /* NOT SUPPORTED */
	}

	/**
	 * Delete rewrite rule to server configration
	 *
	 * @param  string 'plugins' or 'themes'
	 * @return bool   TRUE (found), FALSE (not found or unavailable)
	 */
	private function del_rewrite_rule( $which ) {
		// if rewrite stat is not TRUE or FALSE
		switch ( $this->get_rewrite_stat( $which ) ) {
		  case TRUE:
			$content = $this->get_rewrite_rule( $which );
			$block   = $this->find_rewrite_block( $content );
			$content = $this->remove_rewrite_block( $content, $block );
			return $this->put_rewrite_rule( $which, $content );

		  case FALSE:
			return TRUE;
		}

		return -1; /* NOT SUPPORTED */
	}

	/**
	 * Show notice message
	 *
	 */
	private function show_message( $type, $msg ) {
		if ( class_exists( 'IP_Geo_Block_Admin' ) )
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
			$status[ $key ] = $rewrite->get_rewrite_stat( $key );
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
			if ( FALSE === $rewrite->del_rewrite_rule( $key ) )
				return FALSE;
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