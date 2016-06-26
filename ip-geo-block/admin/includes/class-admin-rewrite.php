<?php
class IP_Geo_Block_Admin_Rewrite {

	/**
	 * Instance of this class.
	 */
	protected static $instance = NULL;

	// private values
	private $doc_root = NULL; // document root
	private $site_uri = NULL; // network site uri
	private $base_uri = NULL; // plugins base uri
	private $wp_dirs  = array();

	// template of rewrite rule in wp-content/(plugins|themes)/
	private $rewrite_rule = array(
		'apache' => array(
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
		'nginx' => array(
			'plugins' => array(
				'# BEGIN IP Geo Block',
				'location ~ %REWRITE_BASE%rewrite.php$ {}',
				'location %WP_CONTENT_DIR%/plugins/ {',
				'    rewrite ^%WP_CONTENT_DIR%/plugins/.*/.*\.php$ %REWRITE_BASE%rewrite.php break;',
				'}',
				'# END IP Geo Block',
			),
			'themes' => array(
				'# BEGIN IP Geo Block',
				'location %WP_CONTENT_DIR%/themes/ {',
				'    rewrite ^%WP_CONTENT_DIR%/themes/.*/.*\.php$ %REWRITE_BASE%rewrite.php break;',
				'}',
				'# END IP Geo Block',
			),
		),
	);

	public function __construct() {
		// http://stackoverflow.com/questions/25017381/setting-php-document-root-on-webserver
		$this->doc_root = str_replace( $_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME'] );
		$this->site_uri = untrailingslashit( parse_url( network_site_url(), PHP_URL_PATH ) );
		$this->base_uri = str_replace( $this->doc_root, '', IP_GEO_BLOCK_PATH );

		// target directories
		$condir = str_replace( $this->doc_root, '', WP_CONTENT_DIR );
		$this->wp_dirs = array(
			'plugins'   => $condir . '/plugins/',
			'themes'    => $condir . '/themes/',
		);
	}

	/**
	 * Return an instance of this class.
	 *
	 */
	private static function get_instance() {
		return self::$instance ? self::$instance : ( self::$instance = new self );
	}

	/**
	 * Get type of server
	 *
	 * @return string 'apache', 'nginx' or NULL
	 */
	private function get_server_type() {
		global $is_apache, $is_nginx; // wp-includes/vars.php
		return $is_apache ? 'apache' : ( $is_nginx ? 'nginx' : NULL );
	}

	/**
	 * Extract the block of rewrite rule
	 *
	 * @param array contents of configuration file
	 * @return array list of begin and end
	 */
	private function find_rewrite_block( $content ) {
		return preg_grep(
			'/^\s*?#\s*?(BEGIN|END)?\s*?IP Geo Block\s*?(BEGIN|END)?\s*?$/i',
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
		global $is_apache, $is_nginx; // wp-includes/vars.php

		if ( $is_apache ) {
			return $this->doc_root . $this->wp_dirs[ $which ] . '.htaccess';
		}

		elseif ( $is_nginx ) {
			return NULL; /* MUST FIX */
		}

		else {
			return NULL; /* NOT SUPPORTED */
		}
	}

	/**
	 * Get contents in .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @return array contents of configuration file
	 */
	private function get_rewrite_rule( $which ) {
		$file = $this->get_rewrite_file( $which );
		$exist = @file_exists( $file );

		// check permission
		if ( $exist ) {
			if ( ! @is_readable( $file ) )
				return FALSE;
		} else {
			if ( ! @is_readable( dirname( $file ) ) )
				return FALSE;
		}

		// http://php.net/manual/en/function.file.php#refsect1-function.file-returnvalues
		@ini_set( 'auto_detect_line_endings', TRUE );

		// get file contents as an array
		return $exist ? @file( $file, FILE_IGNORE_NEW_LINES ) : array();
	}

	/**
	 * Put contents to .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @param array contents of configuration file
	 */
	private function put_rewrite_rule( $which, $content ) {
		$file = $this->get_rewrite_file( $which );
		if ( ! $file || FALSE === file_put_contents( $file, implode( PHP_EOL, $content ), LOCK_EX ) )
			return FALSE;

		// if content is empty then remove file
		if ( empty( $content ) )
			unlink( $file );

		return TRUE;
	}

	/**
	 * Check if the block of rewrite rule exists
	 *
	 * @param string 'plugins' or 'themes'
	 * @return bool TRUE or FALSE
	 */
	private function get_rewrite_stat( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php

		if ( $is_apache ) {
			if ( FALSE === ( $content = $this->get_rewrite_rule( $which ) ) )
				return FALSE;

			$block = $this->find_rewrite_block( $content );
			return empty( $block ) ? FALSE : TRUE;
		}

		elseif ( $is_nginx ) {
			// https://www.wordfence.com/blog/2014/05/nginx-wordfence-falcon-engine-php-fpm-fastcgi-fast-cgi/
			return -1; /* CURRENTLY NOT SUPPORTED */
		}

		else {
			return -1; /* NOT SUPPORTED */
		}
	}

	/**
	 * Remove the block of rewrite rule
	 *
	 * @param array contents of configuration file
	 * @return array array of contents without rewrite rule
	 */
	private function remove_rewrite_block( $content, $block ) {
		$block = array_reverse( $block, TRUE );

		if ( 2 <= count( $block ) ) {
			reset( $block );
			while (
				( list( $key_end,   $val_end   ) = each( $block ) ) &&
				( list( $key_begin, $val_begin ) = each( $block ) )
			) {
				array_splice( $content, $key_begin, $key_end - $key_begin + 1 );
			}
		}

		return $content;
	}

	/**
	 * Append the block of rewrite rule
	 *
	 * @param string 'plugins' or 'themes'
	 * @param array contents of configuration file
	 * @return array array of contents with the block of rewrite rule
	 */
	private function append_rewrite_block( $which, $content ) {
		$server_type = $this->get_server_type();

		return $server_type ? array_merge(
			$content,
			str_replace(
				array( '%REWRITE_BASE%', '%WP_CONTENT_DIR%' ),
				array( $this->base_uri,    WP_CONTENT_DIR   ),
				$this->rewrite_rule[ $server_type ][ $which ]
			)
		) : array();
	}

	/**
	 * Add rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 */
	private function add_rewrite_rule( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php

		if ( $is_apache ) {
			if ( FALSE === ( $content = $this->get_rewrite_rule( $which ) ) )
				return FALSE;

			$block = $this->find_rewrite_block( $content );

			if ( empty( $block ) ) {
				$content = $this->remove_rewrite_block( $content, $block );
				$content = $this->append_rewrite_block( $which, $content );
				return $this->put_rewrite_rule( $which, $content );
			}
		}

		return TRUE;
	}

	/**
	 * Delete rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 */
	private function del_rewrite_rule( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php

		if ( $is_apache ) {
			if ( FALSE === ( $content = $this->get_rewrite_rule( $which ) ) )
				return FALSE;

			$block = $this->find_rewrite_block( $content );

			if ( ! empty( $block ) ) {
				$content = $this->remove_rewrite_block( $content, $block );
				return $this->put_rewrite_rule( $which, $content );
			}
		}

		return TRUE;
	}

	/**
	 * Check rewrite rules
	 *
	 */
	public static function check_rewrite_all() {
		$status = array();
		$rewrite = self::get_instance();

		foreach ( array_keys( $rewrite->rewrite_rule['apache'] ) as $key ) {
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

		foreach ( array_keys( $rewrite->rewrite_rule['apache'] ) as $key ) {
			if ( $options[ $key ] )
				$options[ $key ] = $rewrite->add_rewrite_rule( $key ) ? TRUE : FALSE;
			else
				$options[ $key ] = $rewrite->del_rewrite_rule( $key ) ? FALSE : TRUE;
		}

		return $options;
	}

	/**
	 * Deactivate all rewrite rules
	 *
	 */
	public static function deactivate_rewrite_all() {
		$rewrite = self::get_instance();

		foreach ( array_keys( $rewrite->rewrite_rule['apache'] ) as $key ) {
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
		return $rewrite->wp_dirs;
	}

}