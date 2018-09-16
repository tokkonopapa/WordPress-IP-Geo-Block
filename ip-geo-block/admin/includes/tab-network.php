<?php
class IP_Geo_Block_Admin_Tab {

	// UI control parameters
	static $controls = array(
		'time' => 0, // Duration to retrieve
		'rows' => 2, // Rows
		'cols' => 1, // Columns
		'warn' => FALSE,
	);

	public static function tab_setup( $context, $tab ) {
		/*----------------------------------------*
		 * Control parameters in cookie
		 *----------------------------------------*/
		$options = IP_Geo_Block::get_option();
		$cookie = $context->get_cookie(); // [0]:Section, [1]:Open a new window, [2]:Duration to retrieve, [3]:Row, [4]:Column
		self::$controls['time'] = empty( $cookie[ $tab ][2] ) ? self::$controls['time'] : min( 3, max( 0, (int)$cookie[ $tab ][2] ) );
		self::$controls['rows'] = empty( $cookie[ $tab ][3] ) ? self::$controls['rows'] : min( 4, max( 1, (int)$cookie[ $tab ][3] ) );
		self::$controls['cols'] = empty( $cookie[ $tab ][4] ) ? self::$controls['cols'] : min( 5, max( 1, (int)$cookie[ $tab ][4] ) );
		self::$controls['warn'] = ! $options['validation']['reclogs'];

		/*----------------------------------------*
		 * Blocked by target in logs section
		 *----------------------------------------*/
		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-network',
			__( 'Blocked by target in logs', 'ip-geo-block' ),
			array( __CLASS__, 'render_network' ),
			$option_slug
		);

		/*----------------------------------------*
		 * Chart display layout
		 *----------------------------------------*/
		$html  = '<ul id="ip-geo-block-select-layout">';
		$html .= '<li>' . __( 'Rows', 'ip-geo-block' ) . ' : <select name="rows">';
		$html .= '<option value="1"' . selected( 1, self::$controls['rows'], FALSE ) . '> 5</option>';
		$html .= '<option value="2"' . selected( 2, self::$controls['rows'], FALSE ) . '>10</option>';
		$html .= '<option value="4"' . selected( 4, self::$controls['rows'], FALSE ) . '>20</option>';
		$html .=  '</select></li>';
		$html .= '<li>' .__( 'Columns', 'ip-geo-block' ) . ' : <select name="cols">';
		$html .= '<option value="1"' . selected( 1, self::$controls['cols'], FALSE ) . '>1</option>';
		$html .= '<option value="2"' . selected( 2, self::$controls['cols'], FALSE ) . '>2</option>';
		$html .= '<option value="3"' . selected( 3, self::$controls['cols'], FALSE ) . '>3</option>';
		$html .= '<option value="4"' . selected( 4, self::$controls['cols'], FALSE ) . '>4</option>';
		$html .= '<option value="5"' . selected( 5, self::$controls['cols'], FALSE ) . '>5</option>';
		$html .= '</select></li>';
		$html .= '<li><a id="ip-geo-block-apply-layout" class="button button-secondary" href="';
		$html .= esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 5 ), network_admin_url( 'admin.php' ) ) );
		$html .= '">' . __( 'Apply', 'ip-geo-block' ) . '</a></li>';
		$html .= '</ul>';

		add_settings_field(
			$option_name.'_chart-size',
			__( 'Chart display layout', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'value' => $html,
			)
		);

		/*----------------------------------------*
		 * Duration to retrieve
		 *----------------------------------------*/
		$time = array(
			__( 'All',             'ip-geo-block' ),
			__( 'Latest 1 hour',   'ip-geo-block' ),
			__( 'Latest 24 hours', 'ip-geo-block' ),
			__( 'Latest 1 week',   'ip-geo-block' ),
		);

		// make a list of duration
		$html = "\n";
		foreach ( $time as $key => $val ) {
			$html .= '<li><label><input type="radio" name="' . $option_slug . '-duration" value="' . $key . '"'
			      . ($key == self::$controls['time'] ? ' checked="checked"' : '') . ' />' . $val . '</label></li>' . "\n";
		}

		add_settings_field(
			$option_name.'_select_duration',
			__( 'Duration to retrieve', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'value' => '<ul id="' . $option_slug . '-select-duration">' . $html . '</ul>',
			)
		);
	}

	/**
	 * Render log data
	 *
	 * @param array $args associative array of `id`, `title`, `callback`.
	 */
	public static function render_network( $args ) {
		require_once IP_GEO_BLOCK_PATH . 'admin/includes/class-admin-ajax.php';

		if ( self::$controls['warn'] ) {
			$context = IP_Geo_Block_Admin::get_instance();
			$url = esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => '0', 'sec' => 5 ), $context->dashboard_url() ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-5' );
			echo '<p style="padding:0 1em">', sprintf( __( '[ %sRecord &#8220;Validation logs&#8221;%s ] is disabled.', 'ip-geo-block' ), '<a href="' . $url . '"><strong>', '</strong></a>' ), '</p>', "\n";
			echo '<p style="padding:0 1em">', __( 'Please set the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
		}

		$row   = self::$controls['rows'] * 5;
		$col   = self::$controls['cols'];
		$page  = empty( $_REQUEST['p']) ? 0 : (int)$_REQUEST['p'];
		$start = $page * ( $row * $col );
		$count = min( $total = IP_Geo_Block_Admin_Ajax::get_network_count(), $row * $col );

		// [0]:site, [1]:comment, [2]:xmlrpc, [3]:login, [4]:admin, [5]:public, [6]:link
		$json = IP_Geo_Block_Admin_Ajax::restore_network( self::$controls['time'], $start, $count, FALSE );

		// Max value on hAxis
		$max = 0;
		$num = count( $json );
		for ( $i = 0; $i < $num; ++$i ) {
			$max = max( $max, array_sum( array_slice( $json[ $i ], 1, 5 ) ) );
		}

		// Split the array into chunks
		$arr = array_chunk( $json, $row );
		$num = (int)floor( count( $arr ) / $col );

		// Embed array into data attribute as json
		echo '<div class="ip-geo-block-row ip-geo-block-range" data-ip-geo-block-range="[0,', $max, ']">', "\n";
		for ( $i = 0; $i < $col; ++$i ) {
			if ( isset( $arr[ $i ] ) ) {
				echo '<div class="ip-geo-block-network ip-geo-block-column" ',
				     'id="',  $args['id'], '-', $i, '" ',
				     'data-', $args['id'], '-', $i, '=\'', json_encode( $arr[ $i ] ), '\'>',
				     '</div>', "\n";
			} else {
				echo '<div class="ip-geo-block-column"></div>';
			}
		}
		echo '</div>', "\n";

		// pagination
		$url = esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 5 ), network_admin_url( 'admin.php' ) ) );
		echo '<div class="dataTables_wrapper"><div class="dataTables_paginate">', "\n",
		     '<a class="paginate_button first',    ($page === 0 ? ' disabled' : ''), '" href="', $url, '&p=', (0                      ), '">&laquo;</a>',
		     '<a class="paginate_button previous', ($page === 0 ? ' disabled' : ''), '" href="', $url, '&p=', (0 < $page ? $page-1 : 0), '">&lsaquo;</a><span>';

		$num = (int)ceil( $total / ( $row * $col ) );
		for ( $i = 0; $i < $num; ++$i ) {
			echo '<a class="paginate_button', ($i === $page ? ' current' : ''), '" href="', $url, '&p=', $i, '">', $i+1, '</a>';
		}
		$num -= 1;

		echo '</span>',
		     '<a class="paginate_button next', ($page === $num ? ' disabled' : ''), '" href="', $url, '&p=', ($num > $page ? $page+1 : $page), '">&rsaquo;</a>',
		     '<a class="paginate_button last', ($page === $num ? ' disabled' : ''), '" href="', $url, '&p=', ($num                          ), '">&raquo;</a>',
		     '</div></div>', "\n"; // paginate wrapper
	}

}