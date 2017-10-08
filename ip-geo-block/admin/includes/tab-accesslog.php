<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

if ( $options['validation']['reclogs'] ) :

		/*----------------------------------------*
		 * Validation logs
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-logs',
			__( 'Validation logs', 'ip-geo-block' ),
			array( __CLASS__, 'validation_logs' ),
			$plugin_slug
		);

		// same as in tab-accesslog.php
		$dfn = __( '<dfn title="Validate request to %s.">%s</dfn>', 'ip-geo-block' );
		$target = array(
			'comment' => sprintf( $dfn, 'wp-comments-post.php',                      __( 'Comment post',        'ip-geo-block' ) ),
			'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',                                __( 'XML-RPC',             'ip-geo-block' ) ),
			'login'   => sprintf( $dfn, 'wp-login.php',                              __( 'Login form',          'ip-geo-block' ) ),
			'admin'   => sprintf( $dfn, 'wp-admin/*.php',                            __( 'Admin area',          'ip-geo-block' ) ),
			'public'  => sprintf( $dfn, __( 'public facing pages', 'ip-geo-block' ), __( 'Public facing pages', 'ip-geo-block' ) ),
		);

		$html = '<ul class="' . $plugin_slug . '-filter-target">' . "\n";
		foreach ( $target as $key => $val ) {
			$html .= '<li><input type="checkbox" id="' . $plugin_slug . '-' . $key . '" value="' . $key . '">';
			$html .= '<label for="' . $plugin_slug . '-' . $key . '">' . $val . '</label></li>' . "\n";
		}
		$html .= '</ul>';

		$field = 'filter_target';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Filter target', 'ip-geo-block' ) . '&nbsp;' . '(<a class="ip-geo-block-cycle" id="ip-geo-block-filter-reset">' . '<span title="' . __( 'Toggle selection', 'ip-geo-block' ) . '"></span></a>)',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
			)
		);

		$field = 'filter_logs';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Filter logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => isset( $_GET['s'] ) ? esc_html( $_GET['s'] ) : '', // preset filter
				'after' => '<a class="button button-secondary" id="ip-geo-block-reset-filter" title="' . __( 'Reset', 'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Reset', 'ip-geo-block' ) . '</a>',
			)
		);

		$field = 'bulk_logs';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Bulk action', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => $field,
				'value' => 0,
				'list' => array(
					0 => NULL,
					'bulk-logs-ip-white' => __( 'Add IP address to &#8220;Whitelist&#8221;', 'ip-geo-block' ),
					'bulk-logs-ip-black' => __( 'Add IP address to &#8220;Blacklist&#8221;', 'ip-geo-block' ), ) + ( $options['Maxmind']['use_asn'] <= 0 ? array() : array(
					'bulk-logs-as-white' => __( 'Add AS number to &#8220;Whitelist&#8221;',  'ip-geo-block' ),
					'bulk-logs-as-black' => __( 'Add AS number to &#8220;Blacklist&#8221;',  'ip-geo-block' ),
				) ),
				'after' => '<a class="button button-secondary" id="ip-geo-block-bulk-action" title="' . __( 'Apply', 'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Apply', 'ip-geo-block' ) . '</a>' . '<div id="'.$plugin_slug.'-bulk-loading"></div>',
			)
		);

		$field = 'clear_logs';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Clear logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Clear all', 'ip-geo-block' ),
				'after' => '<div id="'.$plugin_slug.'-logs"></div>',
			)
		);

		$field = 'export_logs';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Export logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' => '<a class="button button-secondary" id="ip-geo-block-export-logs" title="' . __( 'Export to the local file',   'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Export csv', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="'.$plugin_slug.'-export"></div>',
			)
		);

else: // $options['validation']['reclogs']

		/*----------------------------------------*
		 * Warning
		 *----------------------------------------*/
		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-accesslog',
			__( 'Validation logs', 'ip-geo-block' ),
			array( __CLASS__, 'warn_accesslog' ),
			$option_slug
		);

		$field = 'warning';
		add_settings_field(
			$option_name.'_'.$field,
			'&hellip;',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'after' => '&hellip;',
			)
		);

endif; // $options['validation']['reclogs']

	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	public static function validation_logs() {
		echo '<table id="', IP_Geo_Block::PLUGIN_NAME, '-validation-logs" class="dataTable display" cellspacing="0" width="100%">', "\n", '<tbody></tbody></table>', "\n";
	}

	public static function warn_accesslog() {
		echo '<p>', __( 'Current selection of [<strong>Record validation logs</strong>] on [<strong>Settings</strong>] tab is [<strong>Disable</strong>].', 'ip-geo-block' ), '</p>', "\n";
		echo '<p>', __( 'Please select the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
	}

}