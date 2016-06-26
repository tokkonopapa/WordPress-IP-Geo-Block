<?php
include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-logs.php' );
include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-util.php' );
include_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-apis.php' );

class IP_Geo_Block_Admin_Tab {

	public static function tab_setup( $context ) {
		$plugin_slug = IP_Geo_Block::PLUGIN_SLUG;
		$option_slug = $context->option_slug['statistics'];
		$option_name = $context->option_name['statistics'];
		$options = IP_Geo_Block::get_option( 'settings' );
		$statistics = IP_Geo_Block_Logs::restore_stat( TRUE );

		register_setting(
			$option_slug,
			$option_name
		);

if ( $options['save_statistics'] ) :

		/*----------------------------------------*
		 * Statistics of comment post
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
				'value' => esc_html( $statistics[ $field ] ),
			)
		);

		// Blocked by countries
		$field = 'countries';
		$html = '<div id="'.$plugin_slug.'-chart-countries"></div>';
		$html .= '<ul id="'.$plugin_slug.'-countries" class="'.$option_slug.'-'.$field.'">';

		arsort( $statistics['countries'] );
		foreach ( $statistics['countries'] as $key => $val ) {
			$html .= sprintf( '<li>%2s:%5d</li>', esc_html( $key ), (int)$val );
		}

		$html .= '</ul>';

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

		// Blocked on a daily basis
		$field = 'daily';
		$html = '<div id="'.$plugin_slug.'-chart-daily"><table id="'.$plugin_slug.'-targets">';

		$prev = 0;
		$targets = array( 'comment', 'xmlrpc', 'login', 'admin' );
		foreach ( $statistics['daystats'] as $key => $val ) {
			while( $prev && $key - $prev > DAY_IN_SECONDS ) {
				$prev += DAY_IN_SECONDS;
				$html .= '<tr><td>' . IP_Geo_Block_Util::localdate( $prev, 'Y-m-d' ) . '</td>'; // must be ISO 8601 or RFC 2822
				foreach ( $targets as $target ) {
					$html .= '<td>0</td>';
				}
			}
			$prev = $key;
			$html .= '<tr><td>' . date( 'Y-m-d', $key ) . '</td>'; // must be ISO 8601 or RFC 2822
			foreach ( $targets as $target ) {
				$html .= '<td>' . (isset( $val[ $target ] ) ? (int)$val[ $target ] : 0) . '</td>';
			}
			$html .= '</tr>';
		}

		$html .= '</table></div>';

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
				'value' => '<table class="'.$option_slug.'-'.$field.'">' .
					'<thead><tr><th>IPv4</th><th>IPv6</th></tr></thead><tbody><tr>' .
					'<td>' . esc_html( $statistics['IPv4'] ) . '</td>' .
					'<td>' . esc_html( $statistics['IPv6'] ) . '</td>' .
					'</tr></tbody></table>',
			)
		);

		$field = 'service';
		$html  = '<table class="'.$option_slug.'-'.$field.'"><thead><tr>';
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
		 * Statistics of cache
		 *----------------------------------------*/
		$section = $plugin_slug . '-cache';
		add_settings_section(
			$section,
			__( 'Statistics of cache', 'ip-geo-block' ),
			NULL, // array( $context, 'callback_cache_stat' ),
			$option_slug
		);

		$field = 'cache';
		$html  = '<table class="'.$option_slug.'-'.$field.'"><thead><tr>';
		$html .= '<th>' . __( 'IP address',            'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Country code / Access', 'ip-geo-block' ) . '</th>';
		$html .= '<th>' . __( 'Elapsed [sec] / Calls', 'ip-geo-block' ) . '</th>';
		$html .= '</tr></thead><tbody>';

		if ( $cache = IP_Geo_Block_API_Cache::get_cache_all() ) {
			$time = time();
			$debug = defined( 'IP_GEO_BLOCK_DEBUG' ) && IP_GEO_BLOCK_DEBUG;
			foreach ( $cache as $key => $val ) {
				if ( $options['anonymize'] )
					$key = preg_replace( '/\d{1,3}$/', '***', $key );
				if ( empty( $val['auth'] ) || $debug ) { // hide authenticated user
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
				}
			}
		}
		$html .= '</tbody></table>';

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