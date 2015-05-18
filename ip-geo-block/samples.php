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
if ( ! function_exists( 'my_replace_ip' ) ):
function my_replace_ip( $ip ) {
	return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );
endif;


/**
 * Example 2: Usage of 'ip-geo-block-ip-addr'
 * Use case: Retrieve ip address behind the proxy
 *
 * @param  string $ip original ip address
 * @return string $ip replaced ip address
 */
if ( ! function_exists( 'my_retrieve_ip' ) ):
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
endif;


/**
 * Example 3: Validate ip address before authrization in admin area
 * Use case: When an emergency of yourself being locked out
 *
 */
if ( ! function_exists( 'my_emergency' ) ):
function my_emergency( $validate ) {
	// password is required even in this case
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_emergency' );
add_filter( 'ip-geo-block-admin', 'my_emergency' );
endif;


/**
 * Example 4: Usage of 'ip-geo-block-comment'
 * Use case: Block comment from specific IP addresses in the blacklist
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
if ( ! function_exists( 'my_blacklist' ) ):
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
endif;


/**
 * Example 5: Usage of 'ip-geo-block-login' and 'ip-geo-block-xmlrpc'
 * Use case: Allow from specific countries in the whitelist
 *
 * @param  string $validate['ip'] ip address
 * @param  string $validate['code'] country code
 * @return array $validate add 'result' as 'passed' or 'blocked' if possible
 */
if ( ! function_exists( 'my_whitelist' ) ):
function my_whitelist( $validate ) {
	$whitelist = array(
		'JP', // should be upper case
	);

	$validate['result'] = 'blocked';

	if ( in_array( $validate['code'], $whitelist ) ) {
		$validate['result'] = 'passed';
		break;
	}

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
add_filter( 'ip-geo-block-xmlrpc', 'my_whitelist' );
endif;


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
if ( ! function_exists( 'my_protectives' ) ):
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
endif;


/**
 * Example 7: Validate specific actions of admin-ajax.php at front-end
 * Use case: Give permission to ajax with specific action at public facing page
 *
 * @global array $_GET and $_POST requested queries
 * @param  array $validate
 * @return array $validate add 'result' as 'passed' when 'action' is OK
 */
if ( ! function_exists( 'my_permission' ) ):
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
endif;


/**
 * Example 8: Usage of 'ip-geo-block-admin-actions'
 * Use case: Give permission to prevent blocking by WP-ZEP
 *
 * @param  array $admin_actions array of permitted admin actions
 * @return array $admin_actions extended permitted admin actions
 */
if ( ! function_exists( 'my_admin_actions' ) ):
function my_admin_actions( $admin_actions ) {
	$whitelist = array(
		'do-plugin-action',
	);
	return $admin_actions + $whitelist;
}
add_filter( 'ip-geo-block-admin-actions', 'my_admin_actions' );
endif;


/**
 * Example 9: Usage of 'ip-geo-block-admin-pages'
 * Use case: Give permission to prevent blocking by WP-ZEP
 *
 * @param  array $admin_pages array of permitted admin pages
 * @return array $admin_pages extended permitted admin pages
 */
if ( ! function_exists( 'my_admin_pages' ) ):
function my_admin_pages( $admin_pages ) {
	// ex) wp-admin/upload.php?page=plugin-name
	$whitelist = array(
		'plugin-name',
	);
	return $admin_pages + $whitelist;
}
add_filter( 'ip-geo-block-admin-pages', 'my_admin_pages' );
endif;


/**
 * Example 10: Usage of 'ip-geo-block-wp-content'
 * Use case: Give permission to prevent blocking by WP-ZEP
 *
 * @param  array $names array of permitted plugins/themes
 * @return array $names extended permitted plugins/themes
 */
if ( ! function_exists( 'my_wp_content' ) ):
function my_wp_content( $names ) {
	// ex) wp-content/plugins/plugin-name/
	// ex) wp-content/themes/theme-name/
	$whitelist = array(
		'plugin-name',
		'theme-name',
	);
	return $names + $whitelist;
}
add_filter( 'ip-geo-block-wp-content', 'my_wp_content' );
endif;


/**
 * Example 11: Usage of 'ip-geo-block-headers'
 * Use case: Change the user agent strings when accessing geolocation API
 *
 * Notice: Be careful about HTTP header injection.
 * @param  string $args http request headers for `wp_remote_get()`
 * @return string $args http request headers for `wp_remote_get()`
 */
if ( ! function_exists( 'my_user_agent' ) ):
function my_user_agent( $args ) {
    $args['user-agent'] = 'my user agent strings';
    return $args;
}
add_filter( 'ip-geo-block-headers', 'my_user_agent' );
endif;


/**
 * Example 12: Usage of 'ip-geo-block-maxmind-dir'
 * Use case: Change the path of Maxmind database files to writable directory
 *
 * @param  string $dir original directory of database files
 * @return string $dir replaced directory of database files
 */
if ( ! function_exists( 'my_maxmind_dir' ) ):
function my_maxmind_dir( $dir ) {
	$upload = wp_upload_dir();
	return $upload['basedir'];
}
add_filter( 'ip-geo-block-maxmind-dir', 'my_maxmind_dir' );
endif;


/**
 * Example 13: Usage of 'ip-geo-block-maxmind-zip-ipv[46]'
 * Use case: Replace Maxmind database files to city edition
 *
 * @param  string $url original url to zip file
 * @return string $url replaced url to zip file
 */
if ( ! function_exists( 'my_maxmind_ipv4' ) ):
function my_maxmind_ipv4( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
}
function my_maxmind_ipv6( $url ) {
	return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCityv6-beta/GeoLiteCityv6.dat.gz';
}
add_filter( 'ip-geo-block-maxmind-zip-ipv4', 'my_maxmind_ipv4' );
add_filter( 'ip-geo-block-maxmind-zip-ipv6', 'my_maxmind_ipv6' );
endif;


/**
 * Example 14: Usage of 'ip-geo-block-ip2location-path'
 * Use case: Change the path to IP2Location database files
 *
 * @param  string $path original path to database files
 * @return string $path replaced path to database files
 */
if ( ! function_exists( 'my_ip2location_path' ) ):
function my_ip2location_path( $path ) {
	return WP_PLUGIN_DIR . '/ip2location-tags/IP2LOCATION-LITE-DB1.IPV6.BIN';
}
add_filter( 'ip-geo-block-ip2location-path', 'my_ip2location_path' );
endif;


/**
 * Example 15: Backup validation logs to text files
 * Use case: Keep verification logs selectively to text files
 *
 * @param  string $hook 'comment', 'login', 'admin' or 'xmlrpc'
 * @param  string $dir default path where text files should be saved
 * @return string should be absolute path out of the public_html.
 */
if ( ! function_exists( 'my_backup_dir' ) ):
function my_backup_dir( $dir, $hook ) {
	if ( 'login' === $hook )
		return '/absolute/path/to/';
	else
		return null;
}
add_filter( 'ip-geo-block-backup-dir', 'my_backup_dir', 10, 2 );
endif;


/**
 * Example 16: Usage of 'IP_Geo_Block::get_geolocation()'
 * Use case: Get geolocation of visitor's ip address with latitude and longitude
 *
 */
if ( ! function_exists( 'my_geolocation' ) ):
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
endif;

endif; /* class_exists( 'IP_Geo_Block' ) */
