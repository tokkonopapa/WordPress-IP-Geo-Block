<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();
		$statistics = IP_Geo_Block_Logs::restore_stat( TRUE );
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

if ( $options['save_statistics'] ) :

		/*----------------------------------------*
		 * Statistics of validation
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-statistics',
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

		/*----------------------------------------*
		 * Statistics in logs
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-stat-logs',
			__( 'Statistics in logs', 'ip-geo-block' ),
			array( __CLASS__, 'statistics_logs' ),
			$option_slug
		);

		$field = 'clear_logs';
		add_settings_field(
			$option_name.'_'.$field,
			__( 'Clear logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => $field,
				'value' => __( 'Clear now', 'ip-geo-block' ),
			)
		);

else:

		/*----------------------------------------*
		 * Warning
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-statistics',
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
		add_settings_section(
			$section = $plugin_slug . '-cache',
			__( 'Statistics in cache', 'ip-geo-block' ),
			array( __CLASS__, 'statistics_cache' ),
			$option_slug
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

	/**
	 * Render top list in logs
	 *
	 */
	public static function statistics_logs() {
		// array of ( `time`, `ip`, `hook`, `code`, `method`, `data` )
		$logs = IP_Geo_Block_Logs::get_recent_logs( YEAR_IN_SECONDS );

		// Count by key
		$count = array();
		$keys = array(
			'code' => __( 'Country (Top 10)',    'ip-geo-block' ),
			'asn'  => __( 'AS number (Top 10)',  'ip-geo-block' ),
			'ip'   => __( 'IP address (Top 10)', 'ip-geo-block' ),
			'slug' => __( 'Slug in back-end',    'ip-geo-block' ),
		);

		foreach( $logs as $val ) {
			$val['ip'] = '[' . $val['code'] . '] ' . $val['ip'];
			$key = $val['method'] . ' ' . $val['data'];

			// <methodName>...</methodName>
			if ( preg_match( '#<methodName>(.*?)</methodName>#', $key, $matches ) ) {
				$val['slug'] = '/xmlrpc.php ' . $matches[1];
			}

			// /wp-content/(plugins|themes)/...
			elseif ( preg_match( '#(/wp-content/(?:plugins|themes)/.*?/)#', $key, $matches ) ) {
				$val['slug'] = $matches[1];
			}

			// /wp-admin/admin*.php?action=...
			elseif ( preg_match( '#(/wp-admin/admin.*?\.php).*((?:page|action)=[\w-]+)#', $key, $matches ) ) {
				$val['slug'] = $matches[1] . (isset( $matches[2] ) ? ' ' . $matches[2] : '');
			}

			// /wp-admin/*.php
			elseif ( preg_match( '#(/wp-admin/(?!admin).*?\.php)#', $key, $matches ) ) {
				$val['slug'] = $matches[1];
			}

			// file uploading *.(zip|tar|rar|gz|php|...)
			elseif ( preg_match( '#(\[name\]\s*?=>.*\.\w+?)\b#', $key, $matches ) ) {
				$val['slug'] = $matches[1];
			}

			// /*.php
			elseif ( preg_match( '#^\w+?\[\d+?\]:(/[^/]+?\.php)#', $key, $matches ) ) {
				$val['slug'] = $matches[1];
			}

			foreach ( array_keys( $keys ) as $key ) {
				if ( ! empty( $val[ $key ] ) ) {
					$count[ $key ][] = $val[ $key ];
				}
			}
		}

		foreach ( $keys as $slug => $val ) {
			echo '<ol class="ip-geo-block-top-list"><h4>', esc_html( $val ), '</h4>';

			if ( isset( $count[ $slug ] ) ) {
				$logs = array_count_values( $count[ $slug ] );
				arsort( $logs );

				if ( 'slug' !== $slug )
					$logs = array_slice( $logs, 0, 10 );

				foreach ( $logs as $key => $val ) {
					$link = explode( ' ', $key );
					$link = esc_html( end( $link ) );
					$key =  esc_html( $key ) ;

					echo '<li><code>';
					echo 'code' === $slug ?
						$key :
						str_replace(
							$link,
							'<a href="' .
							esc_url( add_query_arg(
								array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 4, 's' => $link ),
								admin_url( 'options-general.php' )
							) ) .
							'" target=_blank>' . $link . '</a>',
							$key
						);
					echo '</code> (', (int)$val, ')</li>';
				}
			}

			echo '</ol>', "\n";
		}
	}

	/**
	 * Render IP address cache
	 *
	 */
	public static function statistics_cache() {
		$option_slug = IP_Geo_Block::PLUGIN_NAME;
		echo
			'<table id="', $option_slug, '-statistics-cache" class="', $option_slug, '-statistics-table dataTable display nowrap" cellspacing="0" width="100%">', "\n",
			'<thead><tr>', "\n",
			'<th>', '<input type="checkbox" class="', $option_slug, '-select-all"></th>', "\n",
			'<th>', __( 'IP address',    'ip-geo-block' ), '</th>', "\n",
			'<th>', __( 'Country code',  'ip-geo-block' ), '</th>', "\n",
			'<th>', __( 'AS number',     'ip-geo-block' ), '</th>', "\n",
			'<th>', __( 'Target',        'ip-geo-block' ), '</th>', "\n",
			'<th>', __( 'Elapsed [sec]', 'ip-geo-block' ), '</th>', "\n",
			'<th>', __( 'Fails / Calls', 'ip-geo-block' ), '</th>', "\n",
			'</tr></thead>', "\n", '<tbody></tbody></table>', "\n";
	}

}