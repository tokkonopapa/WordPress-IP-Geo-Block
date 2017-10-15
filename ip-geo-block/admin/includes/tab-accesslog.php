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
		$target = array(
			'comment' => __( 'Comment post',        'ip-geo-block' ),
			'xmlrpc'  => __( 'XML-RPC',             'ip-geo-block' ),
			'login'   => __( 'Login form',          'ip-geo-block' ),
			'admin'   => __( 'Admin area',          'ip-geo-block' ),
			'public'  => __( 'Public facing pages', 'ip-geo-block' ),
		);

		// make a list of target
		$html = "\n".'<li><label><input type="radio" name="' . $plugin_slug . '-target" value="all" />' . __( 'All', 'ip-geo-block' ) . '</label></li>' . "\n";
		foreach ( $target as $key => $val ) {
			$html .= '<li><label><input type="radio" name="' . $plugin_slug . '-target" value="' . $key . '" />';
			$html .= '<dfn title="' . $val . '">' . $key . '</dfn>' . '</label></li>' . "\n";
		}

		$field = 'select_target';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Select target', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => '<ul class="' . $plugin_slug . '-select-target">' . $html . '</ul>',
			)
		);

		$field = 'search_filter';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Search in logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => $field,
				'value' => isset( $_GET['s'] ) ? esc_html( $_GET['s'] ) : '', // preset filter
				'after' => '<a class="button button-secondary" id="ip-geo-block-reset-filter" title="' . __( 'Reset', 'ip-geo-block' ) . '" href="#!">'. __( 'Reset', 'ip-geo-block' ) . '</a>',
			)
		);

		$field = 'bulk_action';
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
					'bulk-action-ip-white' => __( 'Add IP address to &#8220;Whitelist&#8221;', 'ip-geo-block' ),
					'bulk-action-ip-black' => __( 'Add IP address to &#8220;Blacklist&#8221;', 'ip-geo-block' ), ) + ( $options['Maxmind']['use_asn'] <= 0 ? array() : array(
					'bulk-action-as-white' => __( 'Add AS number to &#8220;Whitelist&#8221;',  'ip-geo-block' ),
					'bulk-action-as-black' => __( 'Add AS number to &#8220;Blacklist&#8221;',  'ip-geo-block' ),
				) ),
				'after' => '<a class="button button-secondary" id="ip-geo-block-bulk-action" title="' . __( 'Apply', 'ip-geo-block' ) . '" href="#!">'. __( 'Apply', 'ip-geo-block' ) . '</a>' . '<div id="'.$plugin_slug.'-loading"></div>',
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
				'before' => '<a class="button button-secondary" id="ip-geo-block-export-logs" title="' . __( 'Export to the local file',   'ip-geo-block' ) . '" href="#!">'. __( 'Export csv', 'ip-geo-block' ) . '</a>',
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
		echo '<table id="', IP_Geo_Block::PLUGIN_NAME, '-validation-logs" class="dataTable display" cellspacing="0" width="100%">', "\n", '<thead></thead><tbody></tbody></table>', "\n";
	}

	public static function warn_accesslog() {
		echo '<p>', __( 'Current selection of [<strong>Record validation logs</strong>] on [<strong>Settings</strong>] tab is [<strong>Disable</strong>].', 'ip-geo-block' ), '</p>', "\n";
		echo '<p>', __( 'Please select the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
	}

}