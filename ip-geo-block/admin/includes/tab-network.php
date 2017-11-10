<?php
class IP_Geo_Block_Admin_Tab {

	// [0]:Section, [1]:Open a new window, [2]:Period to retrieve, [3]:Row, [4]:Column
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
		$default = array(
			'o', // [0]: Section
			'x', // [1]: Open a new window
			'0', // [2]: Period to retrieve
			'2', // [3]: Row 1, 2, 4
			'1', // [4]: Column 1, 2, 3, 4, 5
		);
		self::$cookie = $context->get_cookie();
		self::$cookie = empty( self::$cookie[ $tab ] ) ? $default : self::$cookie[ $tab ];

		for ( $i = 0; $i < 5; ++$i ) {
			if ( empty( self::$cookie[ $i ] ) )
				self::$cookie[ $i ] = $default[ $i ];
		}

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

		// selection for row and column
		$row = min( 4, max( 1, (int)self::$cookie[3] ) ) * 5;
		$col = min( 5, max( 1, (int)self::$cookie[4] ) );
		echo '<div class="ip-geo-block-container">', "\n";
		echo '<div class="ip-geo-block-row">', "\n",
		        '<div class="ip-geo-block-column column-25">', "\n",
		            __( 'Rows', 'ip-geo-block' ), ' : <select name="rows">', "\n",
		                '<option value="1"', selected(  5, $row, FALSE ), '> 5</option>', "\n",
		                '<option value="2"', selected( 10, $row, FALSE ), '>10</option>', "\n",
		                '<option value="4"', selected( 20, $row, FALSE ), '>20</option>', "\n",
		            '</select>', "\n",
		        '</div>', "\n",
		        '<div class="ip-geo-block-column column-25">', "\n",
		            __( 'Columns', 'ip-geo-block' ), ' : <select name="cols">', "\n",
		                '<option value="1"', selected( 1, $col, FALSE ), '>1</option>', "\n",
		                '<option value="2"', selected( 2, $col, FALSE ), '>2</option>', "\n",
		                '<option value="3"', selected( 3, $col, FALSE ), '>3</option>', "\n",
		                '<option value="4"', selected( 4, $col, FALSE ), '>4</option>', "\n",
		                '<option value="5"', selected( 5, $col, FALSE ), '>5</option>', "\n",
		            '</select>', "\n",
		        '</div>', "\n",
		      '</div>', "\n";

		// calculate max value on hAxis
		$max = 0;
		$num = count( $json );
		for ( $i = 0; $i < $num; ++$i ) {
			// [1]:site, [2]:comment, [3]:xmlrpc, [4]:login, [5]:admin, [6]:public
			$max = max( $max, array_sum( array_slice( $json[ $i ], 1, 6 ) ) );
		}
		echo '<div class="ip-geo-block-row" id="ip-geo-block-range"',
		     ' data-ip-geo-block-range="[0,', $max, ']">', "\n";

		// row and column
		$arr = array_chunk( $json, $row );
		$num = count( $arr );
		$page = max( 0, empty( $_SERVER['p']) ? 0 : (int)$_SERVER['p'] );

		// Embed array into data attribute as json
		for ( $i = $col * $page; $i < $num; $i += $col ) {
			for ( $j = 0; $i + $j < $num; ++$j ) {
				$k = $i + $j;
				echo '<div class="ip-geo-block-network ip-geo-block-column" ',
				     'id="',  $args['id'], '-', $k, '" ',
				     'data-', $args['id'], '-', $k, '=\'', json_encode( $arr[ $k ] ), '\'>',
				     '</div>', "\n";
			}
		}

		// end of wrapper for multi column
		echo '</div>', "\n"; // ip-geo-block-row

		// pagination
		echo '<div class="ip-geo-block-row">', "\n", '<div class="ip-geo-block-column">', "\n";
		echo '&laquo;'; // top
		echo '&rsaquo;'; // next
		$num = ceil( $num / ($row * $col));
		for ( $i = 0; $i < $num; ++$i ) {
			echo $i;
		}
		echo '&lsaquo;';
		echo '&raquo;', "\n";
		echo '</div>', "\n", '</div>', "\n", '</div>', "\n"; // row, column, container
	}

}