<?php
class IP_Geo_Block_Admin_Rewrite {

	// private values
	private $doc_root = NULL;
	private $base_uri = NULL;

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
		$this->base_uri = str_replace( $this->doc_root, '', IP_GEO_BLOCK_PATH );
	}

	/**
	 * Get type of server
	 *
	 * @return string 'apache', 'nginx' or NULL
	 */
	public function get_server_type() {
		global $is_apache, $is_nginx; // wp-includes/vars.php
		return $is_apache ? 'apache' : ( $is_nginx ? 'nginx' : NULL );
	}

	/**
	 * Check if the block of rewrite rule exists
	 *
	 * @param string 'plugins' or 'themes'
	 * @return bool TRUE or FALSE
	 */
	public function check_rewrite_rule( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php
		if ( $is_apache ) {
			$block = $this->find_rewrite_block( $this->get_rewrite_rule( $which ) );
			return empty( $block ) ? FALSE : TRUE;
		}
		elseif ( $is_nginx ) {
			return FALSE; /* CURRENTLY NOT SUPPORTED */
		}
		else {
			return FALSE; /* CURRENTLY NOT SUPPORTED */
		}
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
				array( $this->base_uri, WP_CONTENT_DIR ),
				$this->rewrite_rule[ $server_type ][ $which ]
			)
		) : array();
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
			return $this->doc_root
				. parse_url( network_site_url(), PHP_URL_PATH )
				. IP_Geo_Block::$wp_dirs[ $which ] . '.htaccess';
		}
		elseif ( $is_nginx ) {
			return NULL; /* MUST FIX */
		}
		else {
			return NULL;
		}
	}

	/**
	 * Get contents in .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @return array contents of configuration file
	 */
	private function get_rewrite_rule( $which ) {
		if ( @file_exists( $file = $this->get_rewrite_file( $which ) ) ) {
			// http://php.net/manual/en/function.file.php#refsect1-function.file-returnvalues
			@ini_set( 'auto_detect_line_endings', TRUE );

			// get file contents as an array
			if ( FALSE !== ( $content = @file( $file, FILE_IGNORE_NEW_LINES ) ) )
				return $content;
		}

		return array();
	}

	/**
	 * Put contents to .htaccess in wp-content/(plugins|themes)/
	 *
	 * @param string 'plugins' or 'themes'
	 * @param array contents of configuration file
	 */
	private function put_rewrite_rule( $which, $content ) {
		if ( $file = $this->get_rewrite_file( $which ) ) {
			file_put_contents( $file, implode( PHP_EOL, $content ), LOCK_EX );
			if ( empty( $content ) )
				unlink( $file );
		}
	}

	/**
	 * Add rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 */
	public function add_rewrite_rule( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php
		if ( $is_apache ) {
			$content = $this->get_rewrite_rule( $which );
			$block = $this->find_rewrite_block( $content );
			if ( empty( $block ) ) {
				$content = $this->remove_rewrite_block( $content, $block );
				$content = $this->append_rewrite_block( $which, $content );
				$this->put_rewrite_rule( $which, $content );
			}
		}
	}

	/**
	 * Delete rewrite rule to server configration
	 *
	 * @param string 'plugins' or 'themes'
	 */
	public function remove_rewrite_rule( $which ) {
		global $is_apache, $is_nginx; // wp-includes/vars.php
		if ( $is_apache ) {
			$content = $this->get_rewrite_rule( $which );
			$block = $this->find_rewrite_block( $content );
			if ( ! empty( $block ) ) {
				$content = $this->remove_rewrite_block( $content, $block );
				$this->put_rewrite_rule( $which, $content );
			}
		}
	}
}