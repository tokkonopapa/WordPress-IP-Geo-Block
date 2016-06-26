<?php
/**
 * This block is for test purpose.
 *
 */
if ( ! empty( $_GET['wp-load'] ) )
	include_once substr( __FILE__, 0, strpos( __FILE__, '/wp-content/' ) ) . '/wp-load.php';

// Status same as admin-ajax.php
die( '0' );

/**
 * Samples/Snippets to extend functionality of IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2014-2016 tokkonopapa
 */
if ( class_exists( 'IP_Geo_Block' ) ):

define( 'IP_GEO_BLOCK_DEBUG', false );

/**
 * Example 1: Usage of 'ip-geo-block-ip-addr'
 * Use case: Replace ip address for test purpose
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_replace_ip( $ip ) {
	return '98.139.183.24'; // yahoo.com
}

add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );


/**
 * Example 2: Usage of 'ip-geo-block-ip-addr'
 * Use case: Retrieve ip address behind the proxy
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
function my_retrieve_ip( $ip ) {
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$tmp = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$tmp = trim( $tmp[0] );
		if ( filter_var( $tmp, FILTER_VALIDATE_IP ) ) {
			$ip = $tmp;
		}
	}

	return $ip;
}

add_filter( 'ip-geo-block-ip-addr', 'my_retrieve_ip' );


/**
 * Example 3: Validate ip address before authrization in admin area
 * Use case: When an emergency of yourself being locked out
 *
 */
function my_emergency( $validate ) {
	// password is required even in this case
	$validate['result'] = 'passed';
	return $validate;
}

add_filter( 'ip-geo-block-login', 'my_emergency' );
add_filter( 'ip-geo-block-admin', 'my_emergency' );


/**
 * Example 4: Usage of 'ip-geo-block-comment'
 * Use case: Block comment from specific IP addresses in the blacklist
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_blacklist( $validate ) {
	$blacklist = array(
		'123.456.789.',
	);

	foreach ( $blacklist as $ip ) {
		if ( strpos( $ip, $validate['ip'] ) === 0 ) {
			$validate['result'] = 'blocked';
			break;
		}
	}

	return $validate;
}

add_filter( 'ip-geo-block-comment', 'my_blacklist' );


/**
 * Example 5: Usage of 'ip-geo-block-login' and 'ip-geo-block-xmlrpc'
 * Use case: Allow from specific countries in the whitelist
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
function my_whitelist( $validate ) {
	$whitelist = array(
		'JP', // should be upper case
	);

	$validate['result'] = 'blocked';

	if ( in_array( $validate['code'], $whitelist, true ) ) {
		$validate['result'] = 'passed';
	}

	return $validate;
}

add_filter( 'ip-geo-block-login', 'my_whitelist' );
add_filter( 'ip-geo-block-xmlrpc', 'my_whitelist' );


/**
 * Example 6: Validate specific actions of admin-ajax.php at front-end
 * Use case: Give permission to ajax with specific action at public facing page
 *
 * @global array $_GET and $_POST requested queries
 * @param  array $validate validation results
 * @return array $validate add 'result' as 'passed' when 'action' is OK
 */
function my_permitted_ajax( $validate ) {
	$whitelist = array(
		'permitted_action',
	);

	if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $whitelist, true ) )
		$validate['result'] = 'passed';

	return $validate; // should not set 'passed' to validate by country code
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	add_filter( 'ip-geo-block-admin', 'my_permitted_ajax' );


/**
 * Example 7: Validate extra IP addresses with CIDR prior to other validations
 * Use case: Get IPs with CIDR from Amazon AWS and set them to the black list
 *
 * @param  array $extra_ips array of white list and black list
 * @param  string $hook 'comment', 'xmlrpc', 'login', 'admin'
 * @return array $extra_ips updated array
 */
define( 'MY_EXTRA_IPS_LIST', 'my_extra_ips_list' );
define( 'MY_EXTRA_IPS_CRON', 'my_extra_ips_cron' );

function my_extra_ips_get() {
	$list = json_decode(
		@file_get_contents( 'https://ip-ranges.amazonaws.com/ip-ranges.json' ),
		TRUE // convert object to array
	);

	//  keep the list in the cache
	if ( is_array( $list['prefixes'] ) ) {
		$list = implode( ',', array_column( $list['prefixes'], 'ip_prefix' ) );
		set_transient( MY_EXTRA_IPS_LIST, $list, DAY_IN_SECONDS );
	}

	if ( ! wp_next_scheduled( MY_EXTRA_IPS_CRON ) )
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, MY_EXTRA_IPS_CRON );

	return $list;
}

function my_extra_ips_hook( $extra_ips, $hook ) {
	// if the list does not exist, then update
	$list = get_transient( MY_EXTRA_IPS_LIST );

	if ( ! $list )
		wp_schedule_single_event( time(), MY_EXTRA_IPS_CRON );

	// restrict the target hook
	if ( in_array( $hook, array( 'xmlrpc', 'login' ), true ) ) {
		$extra_ips['black_list'] .= ( $extra_ips['black_list'] ? ',' : '' ) . $list;
	}

	return $extra_ips;
}

add_action( MY_EXTRA_IPS_CRON, 'my_extra_ips_get' );
add_filter( 'ip-geo-block-extra-ips', 'my_extra_ips_hook', 10, 2 );


/**
 * Example 8: Usage of `ip-geo-block-xxxxxx-(status|reason)`
 * Use case: Customize the http response status code and message at blocking
 *
 * @param  int $code or string $msg
 * @return int $code or string $msg
 */
function my_xmlrpc_status( $code ) { return 403; }
function my_login_status ( $code ) { return 503; }
function my_login_reason ( $msg  ) { return "Sorry, this service is unavailable."; }

add_filter( 'ip-geo-block-xmlrpc-status', 'my_xmlrpc_status' );
add_filter( 'ip-geo-block-login-status',  'my_login_status'  );
add_filter( 'ip-geo-block-login-reason',  'my_login_reason'  );


/**
 * Example 9: Usage of 'ip-geo-block-bypass-admins'
 * Use case: Specify the admin request with a specific queries to bypass WP-ZEP
 *
 * @param  array $queries array of admin queries which should bypass WP-ZEP.
 * @return array $queries array of admin queries which should bypass WP-ZEP.
 */
function my_bypass_admins( $queries ) {
	// <form method="POST" action="wp-admin/admin-post.php">
	// <input type="hidden" name="action" value="do-my-action" />
	// <input type="hidden" name="page" value="my-plugin-page" />
	// </form>
	$whitelist = array(
		'do-my-action',
		'my-plugin-page',
	);
	return array_merge( $queries, $whitelist );
}

add_filter( 'ip-geo-block-bypass-admins', 'my_bypass_admins' );


/**
 * Example 10: Usage of 'ip-geo-block-bypass-plugins'
 * Use case: Specify the plugin which should bypass WP-ZEP
 *
 * @param  array of plugin name which should bypass WP-ZEP.
 * @return array of plugin name which should bypass WP-ZEP.
 */
function my_bypass_plugins( $plugins ) {
	// ex) wp-content/plugins/my-plugin/something.php
	$whitelist = array(
		'my-plugin',
	);
	return array_merge( $plugins, $whitelist );
}

add_filter( 'ip-geo-block-bypass-plugins', 'my_bypass_plugins' );


/**
 * Example 11: Usage of 'ip-geo-block-bypass-themes'
 * Use case: Specify the theme which should bypass WP-ZEP
 *
 * @param  array of theme name which should bypass WP-ZEP.
 * @return array of theme name which should bypass WP-ZEP.
 */
function my_bypass_themes( $themes ) {
	// ex) wp-content/themes/my-theme/something.php
	$whitelist = array(
		'my-theme',
	);
	return array_merge( $themes, $whitelist );
}

add_filter( 'ip-geo-block-bypass-themes', 'my_bypass_themes' );


/**
 * Example 12: Usage of 'ip-geo-block-headers'
 * Use case: Change the user agent strings when accessing geolocation API
 *
 * Notice: Be careful about HTTP header injection.
 * @param  string $args http request headers for `wp_remote_get()`
 * @return string $args http request headers for `wp_remote_get()`
 */
function my_user_agent( $args ) {
    $args['user-agent'] = 'my user agent strings';
    return $args;
}

add_filter( 'ip-geo-block-headers', 'my_user_agent' );


/**
 * Example 13: Usage of 'ip-geo-block-maxmind-dir'
 * Use case: Change the path of Maxmind database files to writable directory
 *
 * @param  string $dir original directory of database files
 * @return string $dir replaced directory of database files
 */
function my_maxmind_dir( $dir ) {
	$upload = wp_upload_dir();
	return $upload['basedir'];
}

add_filter( 'ip-geo-block-maxmind-dir', 'my_maxmind_dir' );


/**
 * Example 14: Usage of 'ip-geo-block-maxmind-zip-ipv[46]'
 * Use case: Replace Maxmind database files to city edition
 *
 * @param  string $url original url to zip file
 * @return string $url replaced url to zip file
 */
function my_maxmind_ipv4( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
}

function my_maxmind_ipv6( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/GeoLiteCityv6.dat.gz';
}

add_filter( 'ip-geo-block-maxmind-zip-ipv4', 'my_maxmind_ipv4' );
add_filter( 'ip-geo-block-maxmind-zip-ipv6', 'my_maxmind_ipv6' );


/**
 * Example 15: Usage of 'ip-geo-block-ip2location-path'
 * Use case: Change the path to IP2Location database files
 *
 * @param  string $path original path to database files
 * @return string $path replaced path to database files
 */
function my_ip2location_path( $path ) {
	return WP_PLUGIN_DIR . '/ip2location-tags/IP2LOCATION-LITE-DB1.IPV6.BIN';
}

add_filter( 'ip-geo-block-ip2location-path', 'my_ip2location_path' );


/**
 * Example 16: Backup validation logs to text files
 * Use case: Keep verification logs selectively to text files
 *
 * @param  string $hook 'comment', 'xmlrpc', 'login', 'admin'
 * @param  string $dir default path where text files should be saved
 * @return string should be absolute path out of the public_html.
 */
function my_backup_dir( $dir, $hook ) {
	if ( in_array( $hook, array( 'login', 'admin' ) ) )
		return '/absolute/path/to/';
	else
		return null;
}

add_filter( 'ip-geo-block-backup-dir', 'my_backup_dir', 10, 2 );


/**
 * Example 17: Usage of 'IP_Geo_Block::get_geolocation()'
 * Use case: Get geolocation of visitor's ip address with latitude and longitude
 *
 */
function my_geolocation() {
	/**
	 * IP_Geo_Block::get_geolocation(
	 *    $ip = NULL, $providers = array(), $callback = 'get_country'
	 * );
	 *
	 * @param string $ip IP address / default: $_SERVER['REMOTE_ADDR']
	 * @param array  $providers list of providers / ex: array( 'ipinfo.io' )
	 * @param string $callback geolocation function / ex: 'get_location'
	 * @return array country code and so on
	 */
	$geolocation = IP_Geo_Block::get_geolocation();

	/**
	 * 'ip'       => string   validated ip address
	 * 'auth'     => int      authenticated or not
	 * 'code'     => string   country code
	 * 'time'     => unsinged int processing time
	 * 'provider' => string   IP geolocation service provider
	 */
	var_dump( $geolocation );

	if ( isset( $geolocation['errorMessage'] ) ) {
		echo 'error at getting geolocation'; // error handling
	}
}


/**
 * Example 18: Usage of 'ip-geo-block-record-logs'
 * Use case: Prevent recording logs when it requested from own country
 *
 * @param  int    $record   0:none 1:blocked 2:passed 3:unauth 4:auth 5:all
 * @param  string $hook     'comment', 'xmlrpc', 'login' or 'admin'
 * @param  array  $validate the result of validation which contains:
 *  'ip'       => string    ip address
 *  'auth'     => int       authenticated (>= 1) or not (0)
 *  'code'     => string    country code
 *  'time'     => unsinged  processing time for examining the country code
 *  'provider' => string    IP geolocation service provider
 *  'result'   => string    'passed' or the reason of blocking
 * @return int    $record   modified condition
 */
function my_record_logs( $record, $hook, $validate ) {
	/* if request is from my country and passed, then no record */
	if ( 'JP' === $validate['code'] && 'passed' === $validate['result'] )
		$record = 0;

	return $record;
}

add_filter( 'ip-geo-block-record-logs', 'my_record_logs', 10, 3 );

endif; /* class_exists( 'IP_Geo_Block' ) */

?>