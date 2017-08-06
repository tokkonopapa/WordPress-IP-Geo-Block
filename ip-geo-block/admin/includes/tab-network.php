<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		$section = IP_Geo_Block::PLUGIN_NAME . '-multisite';
		$field = 'multisite';

		global $wpdb;
		foreach ( $wpdb->get_col( "SELECT `blog_id` FROM `$wpdb->blogs`" ) as $id ) {
			switch_to_blog( $id );

			add_settings_section(
				$section . '-' . $id,
				get_bloginfo( 'name' ),
				array( __CLASS__, 'render_log' ),
				$option_slug
			);

			restore_current_blog();
		}
	}

	/**
	 * Render log data
	 *
	 * @param array $args  associative array of `id`, `title`, `callback`.
	 */
	public static function render_log( $args ) {
		switch_to_blog( str_replace( IP_Geo_Block::PLUGIN_NAME . '-multisite-', '', $args['id'] ) );

		echo '<p>';
		echo '[ <a href="', admin_url(), '" target="_self">', __( 'Dashboard' ), '</a> ] ';
		echo '[ <a href="', add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 1 ), admin_url( 'options-general.php' ) ), '" target="_self">', __( 'Statistics', 'ip-geo-block' ), '</a> ] ';
		echo '[ <a href="', add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 4 ), admin_url( 'options-general.php' ) ), '" target="_self">', __( 'Logs',       'ip-geo-block' ), '</a> ]';
		echo '</p>', "\n";

		// array of ( `time`, `ip`, `hook`, `code`, `method`, `data` )
		$logs = IP_Geo_Block_Logs::get_recent_logs( DAY_IN_SECONDS );

		// duration time
		$duration = MINUTE_IN_SECONDS * 15;

		// Blocked hooks by time
		$count = array();
		foreach( $logs as $val ) {
			$key = (int)( $val['time'] / $duration ) * $duration;
			$count[ $key ][] = $val['hook'];
		}

		// Add current time
		if ( ! empty( $count ) ) {
			$key = (int)( $_SERVER['REQUEST_TIME'] / $duration + 1 ) * $duration;
			if ( empty( $count[ $key ] ) ) {
				$count[ $key ] = array();
			}
		}

		// Count of hooks by time
		$logs = array();
		foreach ( $count as $key => $val ) {
			$logs[ $key ] = array_count_values( $val );
		}

		$zero = array(
			'comment' => 0,
			'xmlrpc'  => 0,
			'login'   => 0,
			'admin'   => 0,
			'public'  => 0,
		);

		$prev = 0;
		$count = array();

		// Make array( `time`, `comment`, `xlmrpc`, `login`, `admin`, `public` )
		foreach ( $logs as $key => $val ) {
			while ( $prev && $key - $prev > $duration ) {
				$count[] = array( $prev += $duration, 0, 0, 0, 0, 0 );
			}
			$count[] = array_merge(
				array( $prev = $key ),
				array_values( array_merge( $zero, $val ) )
			);
		}

		// Embed array into data attribute as json
		echo '<div class="', IP_Geo_Block::PLUGIN_NAME, '-multisite" id="', $args['id'], '" data-', $args['id'], '=\'', json_encode( $count ), '\'></div>';
		// http://jsdo.it/taw_yame_bury/tG9t
		// https://developers.google.com/chart/

		restore_current_blog();
	}

}