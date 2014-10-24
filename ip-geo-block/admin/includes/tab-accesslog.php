<?php
require_once( IP_GEO_BLOCK_PATH . 'includes/localdate.php' );
require_once( IP_GEO_BLOCK_PATH . 'includes/accesslog.php' );

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
}

function ip_geo_block_list_accesslog() {
	// same as in tab-settings.php
	$title = array(
		'comment' => __( '<dfn title="Validate at wp-comments-post.php">Comments post</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'login'   => __( '<dfn title="Validate at wp-login.php">Access to login</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
		'admin'   => __( '<dfn title="Validate at wp-admin/admin.php">Access to admin (except ajax)</dfn>', IP_Geo_Block::TEXT_DOMAIN ),
	);

	$list = ip_geo_block_read_log();
	foreach ( $list as $key => $val ) {
		echo "<h4>", $title[ $key ], "</h4>\n";
		if ( empty( $val ) ) continue;
		echo "<table class='", IP_Geo_Block::PLUGIN_SLUG, "-log' data-page-size='10' data-limit-navigation='2'><thead><tr>\n";
		echo "<th data-type='numeric'>", __( 'Time of date', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th>", __( 'IP address',   IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th>", __( 'Country code', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='phone,tablet'>", __( 'Request URI', IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='phone,tablet'>", __( 'User agent',  IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "<th data-hide='phone,tablet'>", __( 'Cookies',     IP_Geo_Block::TEXT_DOMAIN ), "</th>\n";
		echo "</tr></thead><tbody>\n";

		foreach ( $val as $log ) {
			$log = explode( ",", htmlspecialchars( $log ) );
			echo "<tr>\n";
			echo "<td data-value='", $log[0], "'>", ip_geo_block_localdate( $log[0], 'Y-m-d H:i:s' ), "</td>\n";
			echo "<td>", $log[1], "</td>\n";
			echo "<td>", $log[2], "</td>\n";
			echo "<td>", $log[3], "</td>\n";
			echo "<td>", $log[4], "</td>\n";
			echo "<td>", $log[5], "</td>\n";
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