<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context ) {
		$option_slug = IP_Geo_Block::PLUGIN_NAME;
		$option_name = IP_Geo_Block::OPTION_NAME;
		$settings = IP_Geo_Block::get_option();

		register_setting(
			$option_slug,
			$option_name
		);

if ( $settings['validation']['reclogs'] ) :

		/*----------------------------------------*
		 * Validation logs
		 *----------------------------------------*/
		$section = IP_Geo_Block::PLUGIN_NAME . '-accesslog';
		add_settings_section(
			$section,
			__( 'Validation logs', 'ip-geo-block' ),
			array( __CLASS__, 'list_accesslog' ),
			$option_slug
		);

		// footable filter
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
				'value' => '',
				'after' => '<a class="button button-secondary" id="ip-geo-block-reset-filter" title="' . __( 'Reset', 'ip-geo-block' ) . '" href="javascript:void(0)">'. __( 'Reset', 'ip-geo-block' ) . '</a>',
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
				'value' => __( 'Clear now', 'ip-geo-block' ),
				'after' => '<div id="ip-geo-block-logs"></div>',
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
				'after' => '<div id="ip-geo-block-export"></div>',
			)
		);

else:

		/*----------------------------------------*
		 * Warning
		 *----------------------------------------*/
		$section = IP_Geo_Block::PLUGIN_NAME . '-accesslog';
		add_settings_section(
			$section,
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

endif;
	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	public static function list_accesslog() {
		// same as in tab-settings.php
		$dfn = __( '<dfn title="Validation log of request to %s.">%s</dfn>', 'ip-geo-block' );
		$target = array(
			'comment' => sprintf( $dfn, 'wp-comments-post.php', __( 'Comment post', 'ip-geo-block' ) ),
			'xmlrpc'  => sprintf( $dfn, 'xmlrpc.php',           __( 'XML-RPC',      'ip-geo-block' ) ),
			'login'   => sprintf( $dfn, 'wp-login.php',         __( 'Login form',   'ip-geo-block' ) ),
			'admin'   => sprintf( $dfn, 'wp-admin/*.php',       __( 'Admin area',   'ip-geo-block' ) ),
			'public'  => sprintf( $dfn, __( 'public facing pages', 'ip-geo-block' ), __( 'Public facing pages', 'ip-geo-block' ) ),
		);

		foreach ( $target as $key => $val ) {
			echo '<h4>', $val, '</h4>', "\n";
			echo '<table class="fixed ', IP_Geo_Block::PLUGIN_NAME, '-log" data-page-size="10" data-limit-navigation="5" data-filter="#', IP_Geo_Block::OPTION_NAME, '_filter_logs" data-filter-text-only="true"><thead><tr>', "\n";
			echo '<th data-type="numeric">', __( 'Date', 'ip-geo-block' ), '</th>', "\n";
			echo '<th>', __( 'IP address', 'ip-geo-block' ), '</th>', "\n";
			echo '<th>', __( 'Code',       'ip-geo-block' ), '</th>', "\n";
			echo '<th>', __( 'Result',     'ip-geo-block' ), '</th>', "\n";
			echo '<th data-hide="phone,tablet">', __( 'Request',      'ip-geo-block' ), '</th>', "\n";
			echo '<th data-hide="all">',          __( 'User agent',   'ip-geo-block' ), '</th>', "\n";
			echo '<th data-hide="all">',          __( 'HTTP headers', 'ip-geo-block' ), '</th>', "\n";
			echo '<th data-hide="all">',          __( '$_POST data',  'ip-geo-block' ), '</th>', "\n";
			echo '</tr></thead><tbody id="', IP_Geo_Block::PLUGIN_NAME, '-log-', $key, '">', "\n";
			echo <<<EOT
</tbody>
<tfoot class="hide-if-no-paging">
	<tr>
		<td colspan="5">
			<div class="pagination pagination-centered"></div>
		</td>
	</tr>
</tfoot>
</table>

EOT;
		}
	}

	public static function warn_accesslog() {
		echo '<p>', __( 'Current selection of [<strong>Record validation logs</strong>] on [<strong>Settings</strong>] tab is [<strong>Disable</strong>].', 'ip-geo-block' ), '</p>', "\n";
		echo '<p>', __( 'Please select the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
	}

}