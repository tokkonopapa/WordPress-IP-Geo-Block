<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;

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

		// same as in tab-accesslog.php
		$duration = array(
			YEAR_IN_SECONDS  => __( 'All',             'ip-geo-block' ),
			HOUR_IN_SECONDS  => __( 'Latest 1 hour',   'ip-geo-block' ),
			DAY_IN_SECONDS   => __( 'Latest 24 hours', 'ip-geo-block' ),
			WEEK_IN_SECONDS  => __( 'Latest 1 week',   'ip-geo-block' ),
			MONTH_IN_SECONDS => __( 'Latest 1 month',  'ip-geo-block' ),
		);

		// make a list of duration
		$html = "\n";
		foreach ( $duration as $key => $val ) {
			$html .= '<li><label><input type="radio" name="' . $plugin_slug . '-duration" value="' . $key . '" />' . $val . '</label></li>' . "\n";
		}

		$field = 'select_duration';
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
				'value' => '<ul class="' . $plugin_slug . '-select-duration">' . $html . '</ul>',
			)
		);
	}

	/**
	 * Render log data
	 *
	 * @param array $args  associative array of `id`, `title`, `callback`.
	 */
	public static function render_log( $args ) {
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
			$logs = IP_Geo_Block_Logs::get_recent_logs(); // YEAR_IN_SECONDS

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