<?php
class IP_Geo_Block_Admin_Tab {

	// selected period
	static $cookie;

	public static function tab_setup( $context, $tab ) {
		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		/*----------------------------------------*
		 * Graph section
		 *----------------------------------------*/
		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-network',
			__( 'Blocked per target in logs', 'ip-geo-block' ),
			array( __CLASS__, 'render_log' ),
			$option_slug
		);

		/*----------------------------------------*
		 * Period to retrieve
		 *----------------------------------------*/
		self::$cookie = $context->get_cookie();
		self::$cookie = isset( self::$cookie[ $tab ][2] ) ? self::$cookie[ $tab ][2] : 0;

		$period = array(
			__( 'All',             'ip-geo-block' ),
			__( 'Latest 1 hour',   'ip-geo-block' ),
			__( 'Latest 24 hours', 'ip-geo-block' ),
			__( 'Latest 1 week',   'ip-geo-block' ),
			__( 'Latest 1 month',  'ip-geo-block' ),
		);

		// make a list of period
		$html = "\n";
		foreach ( $period as $key => $val ) {
			$html .= '<li><label><input type="radio" name="' . $option_slug . '-period" value="' . $key . '"'
				. ($key == self::$cookie ? ' checked="checked"' : '') . ' />' . $val . '</label></li>' . "\n";
		}

		$field = 'select_period';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Period to retrieve', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => '<ul id="' . $option_slug . '-select-period">' . $html . '</ul>',
			)
		);
	}

	/**
	 * Render log data
	 *
	 * @param array $args  associative array of `id`, `title`, `callback`.
	 */
	public static function render_log( $args ) {
		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php';
		$json = IP_Geo_Block_Admin_Ajax::restore_multisite( self::$cookie, FALSE );

		$row = 1;
		$col = 3;
		$arr = array_chunk( $json, $row );
		$num = count( $arr );

		// start of wrapper for multi column
		echo '<div class="ip-geo-block-container">', "\n", '<div class="ip-geo-block-row">', "\n";

		// Embed array into data attribute as json
		for ( $i = 0; $i < $num; $i += $col ) {
			for ( $j = 0; $i + $j < $num; ++$j ) {
				$k = $i + $j;
//				echo '<div class="', IP_Geo_Block::PLUGIN_NAME, '-multisite ', IP_Geo_Block::PLUGIN_NAME, '-column" id="', $args['id'], $k, '"></div>', "\n";
				echo '<div class="', IP_Geo_Block::PLUGIN_NAME, '-multisite ', IP_Geo_Block::PLUGIN_NAME, '-column" id="', $args['id'], $k, '" data-', $args['id'], $k, '=\'', json_encode( $arr[ $k ] ), '\'></div>', "\n";
			}
		}

		// end of wrapper for multi column
		echo '</div>', "\n", '</div>', "\n";
	}

}