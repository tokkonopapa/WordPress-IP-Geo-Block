<?php
/**
 * Samples/Snippets to extend functionality of IP Geo Block
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      https://github.com/tokkonopapa
 * @copyright 2014-2015 tokkonopapa
 */

if ( class_exists( 'IP_Geo_Block' ) ):

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

	if ( in_array( $validate['code'], $whitelist ) ) {
		$validate['result'] = 'passed';
	}

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
add_filter( 'ip-geo-block-xmlrpc', 'my_whitelist' );


/**
 * Example 6: Validate requested queries via admin-ajax.php
 * Use case: Block malicious access such as `File Inclusion`
 *
 * @link http://hakipedia.com/index.php/File_Inclusion
 * @link http://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html
 *
 * @global array $_GET and $_POST requested queries
 * @param  array $validate
 * @return array $validate add 'result' as 'blocked' when NG word was found
 */
function my_protectives( $validate ) {
	$blacklist = array(
		'wp-config.php',
		'passwd',
	);

	$req = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

	foreach ( $blacklist as $item ) {
		if ( strpos( $req, $item ) !== FALSE ) {
			$validate['result'] = 'blocked';
			break;
		}
	}

	return $validate; // should not set 'passed' to validate by country code
}
add_filter( 'ip-geo-block-admin', 'my_protectives' );


/**
 * Example 7: Validate specific actions of admin-ajax.php at front-end
 * Use case: Give permission to ajax with specific action at public facing page
 *
 * @global array $_GET and $_POST requested queries
 * @param  array $validate
 * @return array $validate add 'result' as 'passed' when 'action' is OK
 */
function my_permission( $validate ) {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$whitelist = array(
			'something',
		);

		if ( in_array( $_REQUEST['action'], $permitted ) ) {
			$validate['result'] = 'passed';
		}
	}

	return $validate; // should not set 'passed' to validate by country code
}
add_filter( 'ip-geo-block-admin', 'my_permission' );


/**
 * Example 8: Usage of `ip-geo-block-xxxxxx-(response|message)`
 * Use case: Customize the http response status code and message at blocking
 *
 * @param  int $code or string $msg
 * @return int $code or string $msg
 */
function my_xmlrpc_response( $code ) { return 403; }
function my_login_response ( $code ) { return 503; }
function my_login_message  ( $msg  ) { return "Sorry, this service is unavailable."; }
add_filter( 'ip-geo-block-xmlrpc-response', 'my_xmlrpc_response' );
add_filter( 'ip-geo-block-login-response', 'my_login_response' );
add_filter( 'ip-geo-block-login-message', 'my_login_message' );


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
	return $queries + $whitelist;
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
	return $plugins + $whitelist;
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
	return $themes + $whitelist;
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
 * @param  string $hook 'comment', 'login', 'admin' or 'xmlrpc'
 * @param  string $dir default path where text files should be saved
 * @return string should be absolute path out of the public_html.
 */
function my_backup_dir( $dir, $hook ) {
	if ( 'login' === $hook )
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
	 * get_geolocation( $ip = NULL, $providers = array(), $callback = 'get_county' )
	 *
	 * @param string $ip IP address / default: $_SERVER['REMOTE_ADDR']
	 * @param array  $providers list of providers / ex: array( 'ipinfo.io' )
	 * @param string $callback geolocation function / ex: 'get_location'
	 * @return array country code and so on
	 */
	$geolocation = IP_Geo_Block::get_geolocation();

	/**
	 * 'ip'       => validated ip address
	 * 'auth'     => authenticated or not
	 * 'time'     => processing time
	 * 'code'     => country code
	 * 'provider' => IP geolocation service provider
	 */
	var_dump( $geolocation );

	if ( isset( $geolocation['errorMessage'] ) ) {
		// error handling
	}
}

endif; /* class_exists( 'IP_Geo_Block' ) */
