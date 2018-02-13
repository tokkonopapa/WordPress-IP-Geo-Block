<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$cookie = $context->get_cookie();
		$options = IP_Geo_Block::get_option();
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		/*----------------------------------------*
		 * Validation logs
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-logs',
			__( 'Validation logs', 'ip-geo-block' ),
			( $options['validation']['reclogs'] ?
				array( __CLASS__, 'validation_logs' ) :
				array( __CLASS__, 'warn_accesslog'  )
			),
			$option_slug
		);

if ( $options['validation']['reclogs'] ):

		$html  = '<ul id="ip-geo-block-live-log">';
		$html .= '<li><input type="radio" name="ip-geo-block-live-log" id="ip-geo-block-live-log-start" value="start"><label for="ip-geo-block-live-log-start" title="Start"><span class="ip-geo-block-icon-play"></span></label></li>';
		$html .= '<li><input type="radio" name="ip-geo-block-live-log" id="ip-geo-block-live-log-pause" value="pause"><label for="ip-geo-block-live-log-pause" title="Pause"><span class="ip-geo-block-icon-pause"></span></label></li>';
		$html .= '<li><input type="radio" name="ip-geo-block-live-log" id="ip-geo-block-live-log-stop"  value="stop" checked><label for="ip-geo-block-live-log-stop" title="Stop"><span class="ip-geo-block-icon-stop"></span></label></li>';
		$html .= '</ul>';

		// Live update
		$field = 'live-log';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Live update', 'ip-geo-block' ) . '<div id="ip-geo-block-live-loading"><div></div><div></div></div>',
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
				'class' => isset( $cookie[ $tab ][1] ) && $cookie[ $tab ][1] === 'o' ? '' : 'ip-geo-block-hide',
			)
		);

		// make a list of target (same as in tab-accesslog.php)
		$target = array(
			'comment' => __( 'Comment post',        'ip-geo-block' ),
			'xmlrpc'  => __( 'XML-RPC',             'ip-geo-block' ),
			'login'   => __( 'Login form',          'ip-geo-block' ),
			'admin'   => __( 'Admin area',          'ip-geo-block' ),
			'public'  => __( 'Public facing pages', 'ip-geo-block' ),
		);

		$html = "\n".'<li><label><input type="radio" name="' . $plugin_slug . '-target" value="all" checked="checked" />' . __( 'All', 'ip-geo-block' ) . '</label></li>' . "\n";
		foreach ( $target as $key => $val ) {
			$html .= '<li><label><input type="radio" name="' . $plugin_slug . '-target" value="' . $key . '" />';
			$html .= '<dfn title="' . $val . '">' . $key . '</dfn>' . '</label></li>' . "\n";
		}

		// Select target
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
				'value' => '<ul id="' . $plugin_slug . '-select-target">' . $html . '</ul>',
			)
		);

		// Search in logs
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

		// Bulk action
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

		// Clear logs
		$field = 'clear_all';
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
				'class' => empty( $cookie[ $tab ][1] ) || $cookie[ $tab ][1] !== 'o' ? '' : 'ip-geo-block-hide',
			)
		);

		// Export logs
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
				'class' => empty( $cookie[ $tab ][1] ) || $cookie[ $tab ][1] !== 'o' ? '' : 'ip-geo-block-hide',
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
		$context = IP_Geo_Block_Admin::get_instance();
		$url = esc_url( add_query_arg( array( 'page' => 'ip-geo-block', 'tab' => '0', 'sec' => 5 ), $context->dashboard_url() ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-5' );
		echo '<p style="padding:0 1em">', sprintf( __( '[ %sRecord &#8220;Logs&#8221;%s ] is desabled.', 'ip-geo-block' ), '<a href="' . $url . '"><strong>', '</strong></a>' ), '</p>', "\n";
		echo '<p style="padding:0 1em">', __( 'Please set the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
//		self::validation_logs();
	}

}