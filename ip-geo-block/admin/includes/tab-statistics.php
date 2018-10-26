<?php
class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context, $tab ) {
		$options = IP_Geo_Block::get_option();
		$plugin_slug = IP_Geo_Block::PLUGIN_NAME;

		register_setting(
			$option_slug = IP_Geo_Block::PLUGIN_NAME,
			$option_name = IP_Geo_Block::OPTION_NAME
		);

		/*----------------------------------------*
		 * Statistics of validation
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-statistics',
			__( 'Statistics of validation', 'ip-geo-block' ),
			( $options['save_statistics'] ?
				NULL :
				array( __CLASS__, 'warn_statistics' )
			),
			$option_slug
		);

if ( $options['save_statistics'] ) :
		$statistics = IP_Geo_Block_Logs::restore_stat();

		// Number of blocked access
		add_settings_field(
			$option_name.'_blocked',
			__( 'Blocked', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'blocked',
				'value' => (int)$statistics['blocked'],
			)
		);

		// Blocked by countries
		$count = array();

		arsort( $statistics['countries'] );
		foreach ( $statistics['countries'] as $key => $val ) {
			$count[] = array( esc_html( $key ), (int)$val );
		}

		$html = '<div id="' . $plugin_slug . '-chart-countries" data-' . $plugin_slug . '-chart-countries=\'' . json_encode( $count ) . '\'></div>';

		add_settings_field(
			$option_name.'_countries',
			__( 'Blocked by countries', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'countries',
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

		add_settings_field(
			$option_name.'_daily',
			__( 'Blocked per day', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'daily',
				'value' => $html,
			)
		);

		// Blocked by type of IP address
		add_settings_field(
			$option_name.'_type',
			__( 'Blocked by type of IP address', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'type',
				'value' => '<table class="'.$option_slug.'-statistics-table">' .
					'<thead><tr><th>IPv4</th><th>IPv6</th></tr></thead><tbody><tr>' .
					'<td>' . esc_html( $statistics['IPv4'] ) . '</td>' .
					'<td>' . esc_html( $statistics['IPv6'] ) . '</td>' .
					'</tr></tbody></table>',
			)
		);

		$html  = '<table class="'.$option_slug.'-statistics-table"><thead><tr>';
		$html .= '<th>' . __( 'Name of API',     'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Call',            'ip-geo-block' ) . '</th>';
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
			$option_name.'_service',
			__( 'Average response time of each API', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'html',
				'option' => $option_name,
				'field' => 'service',
				'value' => $html,
			)
		);

		// Clear statistics
		add_settings_field(
			$option_name.'_clear_statistics',
			__( 'Clear statistics', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'clear_statistics',
				'value' => __( 'Clear all', 'ip-geo-block' ),
				'after' => '<div id="'.$plugin_slug.'-statistics"></div>',
			)
		);

endif;

		/*----------------------------------------*
		 * Statistics in Validation logs
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-stat-logs',
			__( 'Statistics in validation logs', 'ip-geo-block' ),
			( $options['validation']['reclogs'] ?
				array( __CLASS__, 'statistics_logs' ) :
				array( __CLASS__, 'warn_validation' )
			),
			$option_slug
		);

if ( $options['validation']['reclogs'] ) :

		add_settings_field(
			$option_name.'_clear_logs',
			__( 'Clear logs', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'clear_logs',
				'value' => __( 'Clear all', 'ip-geo-block' ),
			)
		);

endif;

		/*----------------------------------------*
		 * Statistics in IP address cache
		 *----------------------------------------*/
		add_settings_section(
			$section = $plugin_slug . '-cache',
			__( 'Statistics in IP address cache', 'ip-geo-block' ),
			( $options['cache_hold'] ?
				array( __CLASS__, 'statistics_cache' ) :
				array( __CLASS__, 'warn_ipadr_cache' )
			),
			$option_slug
		);

if ( $options['cache_hold'] ) :

		add_settings_field(
			$option_name.'_search_filter',
			__( 'Search in cache', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'text',
				'option' => $option_name,
				'field' => 'search_filter',
				'value' => '',
				'after' => '<a class="button button-secondary" id="ip-geo-block-reset-filter" title="'
				. __( 'Reset', 'ip-geo-block' ) . '" href="#!">'. __( 'Reset', 'ip-geo-block' ) . '</a>',
			)
		);

		add_settings_field(
			$option_name.'_bulk_action',
			__( 'Bulk action', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'select',
				'option' => $option_name,
				'field' => 'bulk_action',
				'value' => 0,
				'list' => array(
					0 => NULL,
					'bulk-action-remove'   => __( 'Remove entries by IP address',              'ip-geo-block' ),
					'bulk-action-ip-white' => __( 'Add IP address to &#8220;Whitelist&#8221;', 'ip-geo-block' ),
					'bulk-action-ip-black' => __( 'Add IP address to &#8220;Blacklist&#8221;', 'ip-geo-block' ), ) + ( $options['Maxmind']['use_asn'] <= 0 ? array() : array(
					'bulk-action-as-white' => __( 'Add AS number to &#8220;Whitelist&#8221;',  'ip-geo-block' ),
					'bulk-action-as-black' => __( 'Add AS number to &#8220;Blacklist&#8221;',  'ip-geo-block' ),
				) ),
				'after' => '<a class="button button-secondary" id="ip-geo-block-bulk-action" title="' . __( 'Apply', 'ip-geo-block' ) . '" href="#!">' . __( 'Apply', 'ip-geo-block' ) . '</a>' . '<div id="'.$plugin_slug.'-loading"></div>',
			)
		);

		add_settings_field(
			$option_name.'_clear_all',
			__( 'Clear cache', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'button',
				'option' => $option_name,
				'field' => 'clear_all',
				'value' => __( 'Clear all', 'ip-geo-block' ),
				'after' => '<div id="'.$plugin_slug.'-cache"></div>',
			)
		);

		// Export cache
		add_settings_field(
			$option_name.'_export_cache',
			__( 'Export cache', 'ip-geo-block' ),
			array( $context, 'callback_field' ),
			$option_slug,
			$section,
			array(
				'type' => 'none',
				'before' => '<a class="button button-secondary" id="ip-geo-block-export-cache" title="' . __( 'Export to the local file',   'ip-geo-block' ) . '" href="#!">'. __( 'Export csv', 'ip-geo-block' ) . '</a>',
				'after' => '<div id="'.$plugin_slug.'-export"></div>',
			)
		);

endif;

	}

	/**
	 * Render top list in logs
	 *
	 */
	public static function statistics_logs() {
		// Count by key
		$count = array();
		$keys = array(
			'code' => __( 'Country (Top 10)',    'ip-geo-block' ),
			'asn'  => __( 'AS number (Top 10)',  'ip-geo-block' ),
			'ip'   => __( 'IP address (Top 10)', 'ip-geo-block' ),
			'slug' => __( 'Slug in back-end',    'ip-geo-block' ),
		);

		// Count by keys ($log: `time`, `ip`, `hook`, `code`, `method`, `data`)
		foreach( IP_Geo_Block_Logs::get_recent_logs( YEAR_IN_SECONDS ) as $log ) {
			$log['ip'] = '[' . $log['code'] . '] ' . $log['ip'];
			$key = $log['method'] . ' ' . $log['data'];

			// <methodName>...</methodName>
			if ( preg_match( '#<methodName>(.*?)</methodName>#', $key, $matches ) )
				$log['slug'] = '/xmlrpc.php ' . $matches[1];

			// /wp-content/(plugins|themes)/...
			elseif ( preg_match( '#(/wp-content/(?:plugins|themes)/.*?/)#', $key, $matches ) )
				$log['slug'] = $matches[1];

			// /wp-admin/admin*.php?action=...
			elseif ( preg_match( '#(/wp-admin/admin.*?\.php).*((?:page|action)=[-\w]+)#', $key, $matches ) )
				$log['slug'] = $matches[1] . ' ' . $matches[2];

			// /wp-admin/*.php
			elseif ( preg_match( '#(/wp-admin/(?!admin).*?\.php)#', $key, $matches ) )
				$log['slug'] = $matches[1];

			// file uploading *.(zip|tar|rar|gz|php|...)
			elseif ( preg_match( '#(\[name\]\s*?=>\s*?[^\s]+)#', $key, $matches ) )
				$log['slug'] = $matches[1];

			// other *.php file with or without query string
			elseif ( preg_match( '#(/[^/]*\.php)[^/\w]#', $key, $matches ) && FALSE === strpos( $key, '/wp-admin/' ) )
				$log['slug'] = $matches[1];

			foreach ( array_keys( $keys ) as $key ) {
				if ( ! empty( $log[ $key ] ) )
					$count[ $key ][] = $log[ $key ];
			}
		}

		$options = IP_Geo_Block::get_option();

		// Statistics by keys
		foreach ( $keys as $slug => $log ) {
			if ( 'slug' !== $slug )
				echo '<ol class="ip-geo-block-top-list"><h4>', esc_html( $log ), '</h4>';
			else
				echo '<ol class="ip-geo-block-top-list"><h4>', esc_html( $log ), ' <a class="ip-geo-block-icon ip-geo-block-icon-cycle" id="ip-geo-block-sort-slug" title="', __( 'Toggle sorting order', 'ip-geo-block' ) ,'"><span></span></a></h4>';

			if ( isset( $count[ $slug ] ) ) {
				$logs = array_count_values( $count[ $slug ] );
				arsort( $logs );

				if ( 'slug' !== $slug )
					$logs = array_slice( $logs, 0, 10 ); // Make list of top 10

				foreach ( $logs as $key => $log ) {
					$link = explode( ' ', $key );
					$link = esc_html( end( $link ) );
					$key  = esc_html( $key );

					if ( 'ip' === $slug && $options['anonymize'] )
						$link = $key = IP_Geo_Block_Util::anonymize_ip( $link );

					echo '<li><code>';
					echo str_replace(
						$link,
						'<a href="' .
							esc_url( add_query_arg(
								array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => 4, 's' => $link ),
								admin_url( 'options-general.php' )
							) ) .
						'" target=_blank>' . $link . '</a>',
						$key
					);
					echo '</code> (', (int)$log, ')</li>', "\n";
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
		echo '<table id="', IP_Geo_Block::PLUGIN_NAME, '-statistics-cache" class="', IP_Geo_Block::PLUGIN_NAME, '-dataTable display" cellspacing="0" width="100%">', "\n", '<thead></thead><tbody></tbody></table>', "\n";
	}

	/**
	 * Function that fills the section with the desired content.
	 *
	 */
	private static function dashboard_url() {
		$options = IP_Geo_Block::get_option();
		$context = IP_Geo_Block_Admin::get_instance();
		return $context->dashboard_url( $options['network_wide'] );
	}

	public static function warn_statistics() {
		$url = esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => '0', 'sec' => 3 ), self::dashboard_url() ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-3' );
		echo '<p>', sprintf( __( '[ %sRecord &#8220;Statistics of validation&#8221;%s ] is disabled.', 'ip-geo-block' ), '<a href="' . $url . '">', '</a>' ), '</p>', "\n";
		echo '<p>', __( 'Please set the proper condition to record and analyze the validation statistics.', 'ip-geo-block' ), '</p>', "\n";
	}

	public static function warn_validation() {
		$url = esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => '0', 'sec' => 3 ), self::dashboard_url() ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-3' );
		echo '<p>', sprintf( __( '[ %sRecord &#8220;Validation logs&#8221;%s ] is disabled.', 'ip-geo-block' ), '<a href="' . $url . '">', '</a>' ), '</p>', "\n";
		echo '<p>', __( 'Please set the proper condition to record and analyze the validation logs.', 'ip-geo-block' ), '</p>', "\n";
	}

	public static function warn_ipadr_cache() {
		$url = esc_url( add_query_arg( array( 'page' => IP_Geo_Block::PLUGIN_NAME, 'tab' => '0', 'sec' => 3 ), self::dashboard_url() ) . '#' . IP_Geo_Block::PLUGIN_NAME . '-section-3' );
		echo '<p style="padding:0 1em">', sprintf( __( '[ %sRecord &#8220;IP address cache&#8221;%s ] is disabled.', 'ip-geo-block' ), '<a href="' . $url . '">', '</a>' ), '</p>', "\n";
		echo '<p style="padding:0 1em">', __( 'Please set the proper condition to record IP address in cache.', 'ip-geo-block' ), '</p>', "\n";
	}

}