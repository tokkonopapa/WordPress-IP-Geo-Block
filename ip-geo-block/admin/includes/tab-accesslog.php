<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );
require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );

function ip_geo_block_tab_accesslog( $context ) {
	$option_slug = $context->option_slug['settings'];
	$option_name = $context->option_name['settings'];

	register_setting(
		$option_slug,
		$option_name
	);

	/*----------------------------------------*
	 * Validation logs
	 *----------------------------------------*/
	$section = IP_Geo_Block::PLUGIN_SLUG . '-accesslog';
	add_settings_section(
		$section,
		__( 'Validation logs', IP_Geo_Block::TEXT_DOMAIN ),
		'ip_geo_block_list_accesslog',
		$option_slug
	);

	$field = 'clear_logs';
	add_settings_field(
		$option_name . "_$field",
		__( 'Clear logs', IP_Geo_Block::TEXT_DOMAIN ),
		array( $context, 'callback_field' ),
		$option_slug,
		$section,
		array(
			'type' => 'button',
			'option' => $option_name,
			'field' => $field,
			'value' => __( 'Clear now', IP_Geo_Block::TEXT_DOMAIN ),
			'after' => '<div id="ip-geo-block-loading"></div>',
		)
	);
}

function ip_geo_block_list_accesslog() {
	// same as in tab-settings.php
	$title = array(
		'comment' => __( '<dfn title="wp-comments-post.php">Comment post</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'login'   => __( '<dfn title="wp-login.php">Access to login form</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'admin'   => __( '<dfn title="wp-admin/admin.php">Access to admin area</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$list = IP_Geo_Block_Logs::read_log();
	foreach ( $list as $key => $val ) {
		echo "<h4>", $title[ $key ], "</h4>\n";
		if ( empty( $val ) ) continue;
		echo "<table class='fixed ", IP_Geo_Block::PLUGIN_SLUG, "-log' data-page-size='10' data-limit-navigation='2'><thead><tr>\n";
		echo "<th data-type='numeric'>", __( 'Time of date', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th>", __( 'IP address',   IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th>", __( 'Country code', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th>", __( 'Result',       IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='phone,tablet'>", __( 'User agent',  IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='all'>", __( 'Request URI', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='all'>", __( '$_POST data', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "</tr></thead><tbody>\n";

		foreach ( $val as $logs ) {
			$logs = explode( ",", htmlspecialchars( $logs ) );
			$log = array_shift( $logs );
			echo "<tr>\n<td data-value='", $log, "'>", ip_geo_block_localdate( $log, 'Y-m-d H:i:s' ), "</td>\n";
			foreach ( $logs as $log )
				echo "<td>$log</td>\n";
			echo "</tr>\n";
		}

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