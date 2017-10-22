<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		add_settings_section(
			$section = IP_Geo_Block::PLUGIN_NAME . '-network',
			__( 'Blocked per target in logs', 'ip-geo-block' ),
			array( __CLASS__, 'render_log' ),
			$option_slug
		);

		// Select period
		$cookie = preg_match( '/5=.(\d)/', $_COOKIE[ IP_Geo_Block::PLUGIN_NAME ], $matches );
		$cookie = min( 4, max( 0, isset( $matches[1] ) ? (int)$matches[1] : 0 ) );
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
				. ($key === $cookie ? ' checked="checked"' : '') . ' />' . $val . '</label></li>' . "\n";
		}

		$field = 'select_period';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Period to extract', 'ip-geo-block' ),
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
		// $_COOKIE[ip-geo-block] => 0=&1=&2=oooo&3=&4=&5=o1
		$cookie = preg_match( '/5=.(\d)/', $_COOKIE[ IP_Geo_Block::PLUGIN_NAME ], $matches );
		$cookie = min( 4, max( 0, isset( $matches[1] ) ? (int)$matches[1] : 0 ) );

		// Peroid to extract
		$period = array(
			YEAR_IN_SECONDS,  // All
			HOUR_IN_SECONDS,  // Latest 1 hour
			DAY_IN_SECONDS,   // Latest 24 hours
			WEEK_IN_SECONDS,  // Latest 1 week
			MONTH_IN_SECONDS, // Latest 1 month
		);

		$zero = array(
			'comment' => 0,
			'xmlrpc'  => 0,
			'login'   => 0,
			'admin'   => 0,
			'public'  => 0,
		);

		global $wpdb;
		foreach ( $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" ) as $id ) {
			switch_to_blog( $id );

			// array of ( `time`, `ip`, `hook`, `code`, `method`, `data` )
			$name = get_bloginfo( 'name' );
			$logs = IP_Geo_Block_Logs::get_recent_logs( $period[ $cookie ] );

			$count[ $name ] = $zero;

			// Blocked hooks by time
			foreach( $logs as $val ) {
				++$count[ $name ][ $val['hook'] ];
			}

			$count[ $name ]['link'] = esc_url( add_query_arg(
				array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1 ),
				admin_url( 'options-general.php' )
			) );

			restore_current_blog();
		}

		$result = array();
		foreach ( $count as $key => $val ) {
			array_push( $result, array_merge( array( $key ), array_values( $val ) ) );
		}

		// Embed array into data attribute as json
		echo '<div class="', IP_Geo_Block::PLUGIN_NAME, '-multisite" id="', $args['id'], '" data-', $args['id'], '=\'', json_encode( $result ), '\'></div>';
	}

}