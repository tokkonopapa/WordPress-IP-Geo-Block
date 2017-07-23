<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;
		$option_slug = IP_Geo_Block::PLUGIN_NAME;
		$option_name = IP_Geo_Block::OPTION_NAME;
		$options = IP_Geo_Block::get_option();
		$statistics = IP_Geo_Block_Logs::restore_stat( TRUE );

		register_setting(
			$option_slug,
			$option_name
		);

if ( $options['save_statistics'] ) :

		/*----------------------------------------*
		 * Statistics of validation
		 *----------------------------------------*/
		$section = $plugin_slug . '-statistics';
		add_settings_section(
			$section,
			__( 'Statistics of validation', 'ip-geo-block' ),
			NULL,
			$option_slug
		);

		// Number of blocked access
		$field = 'blocked';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Blocked', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => (int)$statistics[ $field ],
			)
		);

		// Blocked by countries
		$count = array();

		arsort( $statistics['countries'] );
		foreach ( $statistics['countries'] as $key => $val ) {
			$count[] = array( esc_html( $key ), (int)$val );
		}

		$html = '<div id="' . $plugin_slug . '-chart-countries" data-' . $plugin_slug . '-chart-countries=\'' . json_encode( $count ) . '\'></div>';

		$field = 'countries';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Blocked by countries', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
			)
		);

		// Blocked hooks by date
		$zero = array(
			'comment' => 0,
			'xmlrpc'  => 0,
			'login'   => 0,
			'admin'   => 0,
			'public'  => 0,
		);

		$prev = 0;
		$count = array();

		// make array( `time`, `comment`, `xlmrpc`, `login`, `admin`, `public` )
		foreach ( $statistics['daystats'] as $key => $val ) {
			while ( $prev && $key - $prev > DAY_IN_SECONDS ) {
				$count[] = array( $prev += DAY_IN_SECONDS, 0, 0, 0, 0, 0 );
			}
			$count[] = array_merge(
				array( $prev = $key ),
				array_values( array_merge( $zero, $val ) )
			);
		}

		// embed array into data attribute as json
		$html = '<div id="' . $plugin_slug . '-chart-daily" data-' . $plugin_slug . '-chart-daily=\'' . json_encode( $count ) . '\'></div>';

		$field = 'daily';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Blocked per day', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
			)
		);

		// Blocked by type of IP address
		$field = 'type';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Blocked by type of IP address', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => '<table class="'.$option_slug.'-statistics-table">' .
					'<thead><tr><th>IPv4</th><th>IPv6</th></tr></thead><tbody><tr>' .
					'<td>' . esc_html( $statistics['IPv4'] ) . '</td>' .
					'<td>' . esc_html( $statistics['IPv6'] ) . '</td>' .
					'</tr></tbody></table>',
			)
		);

		$field = 'service';
		$html  = '<table class="'.$option_slug.'-statistics-table"><thead><tr>';
		$html .= '<th>' . __( 'Name of API',     'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Calls',           'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Response [msec]', 'ip-geo-block' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		foreach ( $statistics['providers'] as $key => $val ) {
			$html .= '<tr><td>' . esc_html( $key ) . '</td>';
			$html .= '<td>' . sprintf( '%5d', (int)$val['count'] ) . '</td><td>';
			$html .= sprintf( '%4.1f', (float)(1000.0 * $val['time'] / $val['count']) );
			$html .= '</td></tr>';
		}
		$html .= "</tbody></table>";

		// Average response time of each API
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Average response time of each API', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
			)
		);

		// Clear statistics
		$field = 'clear_statistics';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Clear statistics', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Clear now', 'ip-geo-block' ),
				'after' => '<div id="'.$plugin_slug.'-statistics"></div>',
			)
		);

else:

		/*----------------------------------------*
		 * Warning
		 *----------------------------------------*/
		$section = $plugin_slug . '-statistics';
		add_settings_section(
			$section,
			__( 'Statistics of validation', 'ip-geo-block' ),
			array( __CLASS__, 'warn_statistics' ),
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

		/*----------------------------------------*
		 * Statistics in cache
		 *----------------------------------------*/
		$section = $plugin_slug . '-cache';
		add_settings_section(
			$section,
			__( 'Statistics in cache', 'ip-geo-block' ),
			NULL, // array( $context, 'callback_cache_stat' ),
			$option_slug
		);

		$field = 'cache';
		$html  = '<table class="'.$option_slug.'-statistics-table"><thead><tr>';
		$html .= '<th>' . __( 'IP address',            'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Country code / Access', 'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Elapsed [sec] / Calls', 'ip-geo-block' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		if ( $cache = IP_Geo_Block_API_Cache::get_cache_all() ) {
			$count = 0;
			$time = time();
			$debug = defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG;
			foreach ( $cache as $key => $val ) {
				if ( $options['anonymize'] )
					$key = preg_replace( '/\d{1,3}$/', '***', $key );
				$html .= '<tr><td>' .  esc_html( $key         ) . '</td>';
				$html .= '<td>'     .  esc_html( $val['code'] ) . ' / ';
				$html .= '<small>'  .  esc_html( $val['hook'] ) . '</small></td>';
				$html .= '<td>' . ( $time - (int)$val['time'] ) . ' / ';
				$html .= $options['save_statistics'] ? (int)$val['call'] : '-';
				if ( $debug ) {
					$user = get_user_by( 'id', intval( $val['auth'] ) );
					$html .= ' ' . esc_html( $user ? $user->get( 'user_login' ) : '' );
					$html .= ' / fail:' . intval( $val['fail'] );
				}
				$html .= '</td></tr>';
				if ( ++$count >= $options['cache_hold'] )
					break;
			}
		}

		$html .= '</tbody></table>';

		if ( ! empty( $count ) )
			$html .= '<span style="float:right">[ ' . $count . ' / ' . count( $cache ) . ' ]</span>';

		add_settings_field(
			$option_name.'_'.$field,
			__( 'IP address in cache', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => $field,
				'value' => $html,
			)
		);

		$field = 'clear_cache';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Clear cache', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Clear now', 'ip-geo-block' ),
				'after' => '<div id="'.$plugin_slug.'-cache"></div>',
			)
		);
	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	public static function warn_statistics() {
		echo '<p>', __( 'Current setting of [<strong>Record validation statistics</strong>] on [<strong>Settings</strong>] tab is not selected [<strong>Enable</strong>].', 'ip-geo-block' ), '</p>', "\n";
		echo '<p>', __( 'Please set the proper condition to record and analyze the validation statistics.', 'ip-geo-block' ), '</p>', "\n";
	}

}