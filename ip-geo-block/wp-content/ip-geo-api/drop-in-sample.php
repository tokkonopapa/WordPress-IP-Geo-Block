<?php
/**
 * Drop-in for IP Geo Block custom filters
 *
 * This file should be renamed to `drop-in.php`.
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @see       http://www.ipgeoblock.com/codex/#filter-hooks
 * @example   Use `IP_Geo_Block::add_filter()` instead of `add_filter()`
 */
if ( ! class_exists( 'IP_Geo_Block' ) ) {
	die;
}

/**
 * Enables some debug features on dashboard
 *
 */
// define( 'IP_GEO_BLOCK_DEBUG', true );

/**
 * Example: Returns "404 Not found" to hide login page.
 *
 * @param  int $code HTTP status code.
 * @return int modified HTTP status code.
 */
/* -- EDIT THIS LINE AND ACTIVATE THE FOLLOWING FUNCTIONS -- *
function my_login_status( $code ) {
	return 404;
}

IP_Geo_Block::add_filter( 'ip-geo-block-login-status', 'my_login_status', 10, 1 );
//*/

/**
 * Example: Change mode of recording log according to the target.
 *
 * @param  int    $mode 1:blocked 2:passed 3:unauth 4:auth 5:all
 * @param  string $hook 'comment', 'xmlrpc', 'login', 'admin', 'public'
 * @param  array  'ip', 'auth', 'code', 'result'
 * @return int    $mode modefied recording mode.
 */
/* -- EDIT THIS LINE AND ACTIVATE THE FOLLOWING FUNCTIONS -- *
function my_record_logs( $mode, $hook, $validate ) {
	// Countries where you want to supress recording logs.
	$whitelist = array(
		'JP',
	);

	// Suppress recording logs in case of whitelisted countries on public facing pages.
	if ( 'public' !== $hook || in_array( $validate['code'], $whitelist, TRUE ) ) {
		return 1; // Only when blocked
	}
	else {
		return 3; // Unauthenticated user
	}
}

IP_Geo_Block::add_filter( 'ip-geo-block-record-logs', 'my_record_logs', 10, 3 );
//*/
