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
			array( __CLASS__, 'render_network' ),
			$option_slug
		);

		/*----------------------------------------*
		 * Period to retrieve
		 *----------------------------------------*/
		self::$cookie = $context->get_cookie();
		self::$cookie = isset( self::$cookie[ $tab ] ) ? self::$cookie[ $tab ] : [
			'o', // [0]: section
			'x', // [1]: open a new window
			'0', // [2]: Period to retrieve
			'0', // [3]: Row
			'0', // [4]: Column
		];

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
				. ($key == self::$cookie[2] ? ' checked="checked"' : '') . ' />' . $val . '</label></li>' . "\n";
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
	public static function render_network( $args ) {
		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php';
		$json = IP_Geo_Block_Admin_Ajax::restore_network( self::$cookie[2], FALSE );

		// calculate max value on hAxis
		$s = 0;
		$n = count( $json );
		for ( $i = 0; $i < $n; ++$i ) {
			$s = max( $s, array_sum( array_slice( $json[ $i ], 1, 5 ) ) );
		}

		// row and column
		$row = 1;
		$col = 3;
		$arr = array_chunk( $json, $row );
		$num = count( $arr );

		$p = max( 0, empty( $_SERVER['p']) ? 0 : (int)$_SERVER['p'] );

		// start of wrapper for multi column
		echo '<div class="ip-geo-block-container">', "\n", '<div class="ip-geo-block-row" id="ip-geo-block-range" data-ip-geo-block-range="[0,', $s, ']">', "\n";

		// Embed array into data attribute as json
		for ( $i = $col * $p; $i < $num; $i += $col ) {
			for ( $j = 0; $i + $j < $num; ++$j ) {
				$k = $i + $j;
				echo '<div class="ip-geo-block-network ip-geo-block-column" id="', $args['id'], '-', $k, '" data-', $args['id'], '-', $k, '=\'', json_encode( $arr[ $k ] ), '\'></div>', "\n";
			}
		}

		// end of wrapper for multi column
		echo '</div>', "\n", '</div>', "\n";

		// pagination
	}

}