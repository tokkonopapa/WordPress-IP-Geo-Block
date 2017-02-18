<?php
/**
 * IP Geo Block - Utilities
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-2.0+
 * @link      http://www.ipgeoblock.com/
 * @copyright 2013-2016 tokkonopapa
 */

class IP_Geo_Block_Util {

	/**
	 * Return local time of day.
	 *
	 */
	public static function localdate( $timestamp = FALSE, $fmt = NULL ) {
		static $offset = NULL;
		static $format = NULL;

		if ( NULL === $offset )
			$offset = wp_timezone_override_offset() * HOUR_IN_SECONDS;

		if ( NULL === $format )
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return date_i18n( $fmt ? $fmt : $format, $timestamp ? (int)$timestamp + $offset : FALSE );
	}

	/**
	 * Download zip/gz file, uncompress and save it to specified file
	 *
	 */
	public static function download_zip( $url, $args, $filename, $modified ) {
		require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-cron.php';
		return IP_Geo_Block_Cron::download_zip( $url, $args, $filename, $modified );
	}

	/**
	 * Simple comparison of urls
	 *
	 */
	public static function compare_url( $a, $b ) {
		if ( ! ( $a = @parse_url( $a ) ) ) return FALSE;
		if ( ! ( $b = @parse_url( $b ) ) ) return FALSE;

		// leave scheme to site configuration because is_ssl() doesn’t work behind some load balancers.
		unset( $a['scheme'] );
		unset( $b['scheme'] );

		// $_SERVER['HTTP_HOST'] can't be available in case of malicious url.
		$key = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
		if ( empty( $a['host'] ) ) $a['host'] = $key;
		if ( empty( $b['host'] ) ) $b['host'] = $key;

		$key = array_diff( $a, $b );
		return empty( $key ) ? TRUE : FALSE;
	}

	/**
	 * Explod with multiple delimiter.
	 *
	 */
	public static function multiexplode ( $delimiters, $string ) {
		return array_filter( explode( $delimiters[0], str_replace( $delimiters, $delimiters[0], $string ) ) );
	}

	/**
	 * HTML/XHTML filter that only allows some elements and attributes
	 *
	 * @see wp-includes/kses.php
	 */
	public static function kses( $str, $allow_tags = TRUE ) {
		// wp_kses() is unavailable on advanced-cache.php 
		return wp_kses( $str, $allow_tags ? $GLOBALS['allowedtags'] : array() );
	}

	/**
	 * Retrieve nonce from queries or referrer
	 *
	 */
	public static function retrieve_nonce( $key ) {
		if ( isset( $_REQUEST[ $key ] ) )
			return preg_replace( '/[^\w]/', '', $_REQUEST[ $key ] );

		if ( preg_match( "/$key(?:=|%3D)([\w]+)/", self::get_referer(), $matches ) )
			return preg_replace( '/[^\w]/', '', $matches[1] );

		return NULL;
	}

	public static function trace_nonce( $nonce ) {
		if ( self::may_be_logged_in() && empty( $_REQUEST[ $nonce ] ) &&
		     self::retrieve_nonce( $nonce ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			// add nonce at add_admin_nonce() to handle the client side redirection.
			self::redirect( esc_url_raw( $_SERVER['REQUEST_URI'] ), 302 );
			exit;
		}
	}

	/**
	 * Retrieve nonce and rebuild query strings.
	 *
	 */
	public static function rebuild_nonce( $location, $status = 302 ) {
		// check if the location is internal
		$host = parse_url( $location, PHP_URL_HOST );
		if ( ! $host || $host === parse_url( home_url(), PHP_URL_HOST ) ) {
			// it doesn't care about valid nonce or invalid nonce (must be sanitized)
			if ( $nonce = self::retrieve_nonce( $key = IP_Geo_Block::PLUGIN_NAME . '-auth-nonce' ) ) {
				$location = esc_url_raw( add_query_arg(
					array(
						$key => false, // delete onece
						$key => $nonce // add again
					),
					$location
				) );
			}
		}

		return $location;
	}

	/**
	 * WP alternative function of wp_create_nonce() for mu-plugins
	 *
	 * Creates a cryptographic tied to the action, user, session, and time.
	 * @source wp-includes/pluggable.php
	 */
	public static function create_nonce( $action = -1 ) {
		$uid = self::get_current_user_id();
		$tok = self::get_session_token();
		$exp = self::nonce_tick();

		return substr( self::hash_nonce( $exp . '|' . $action . '|' . $uid . '|' . $tok ), -12, 10 );
	}

	/**
	 * WP alternative function of wp_verify_nonce() for mu-plugins
	 *
	 * Verify that correct nonce was used with time limit.
	 * @source wp-includes/pluggable.php
	 */
	public static function verify_nonce( $nonce, $action = -1 ) {
		$uid = self::get_current_user_id();
		$tok = self::get_session_token();
		$exp = self::nonce_tick();

		// Nonce generated 0-12 hours ago
		$expected = substr( self::hash_nonce( $exp . '|' . $action . '|' . $uid . '|' . $tok ), -12, 10 );
		if ( self::hash_equals( $expected, (string)$nonce ) ) {
			return 1;
		}

		// Nonce generated 12-24 hours ago
		$expected = substr( self::hash_nonce( ( $exp - 1 ) . '|' . $action . '|' . $uid . '|' . $tok ), -12, 10 );
		if ( self::hash_equals( $expected, (string)$nonce ) ) {
			return 2;
		}

		// Invalid nonce
		return FALSE;
	}

	/**
	 * WP alternative function of wp_hash() for mu-plugins
	 *
	 * Get hash of given string for nonce.
	 * @source wp-includes/pluggable.php
	 */
	private static function hash_nonce( $data ) {
		return self::hash_hmac( 'md5', $data, NONCE_KEY . NONCE_SALT );
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieve the current session token from the logged_in cookie.
	 * @source wp-includes/user.php
	 */
	private static function get_session_token() {
		// Arrogating logged_in cookie never cause the privilege escalation.
		$cookie = self::parse_auth_cookie( 'logged_in' );
		return ! empty( $cookie['token'] ) ? $cookie['token'] : AUTH_KEY . AUTH_SALT;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Parse a cookie into its components. It assumes the key including $scheme.
	 * @source wp-includes/pluggable.php (after muplugins_loaded, it would be initialized)
	 */
	private static function parse_auth_cookie( $scheme ) {
		static $cookie = FALSE;

		if ( FALSE === $cookie ) {
			foreach ( array_keys( $_COOKIE ) as $key ) {
				if ( FALSE !== strpos( $key, $scheme ) ) {
					if ( count( $elements = explode( '|', $_COOKIE[ $key ] ) ) === 4 ) {
						@list( $username, $expiration, $token, $hmac ) = $elements;
						return $cookie = compact( 'username', 'expiration', 'token', 'hmac' );
					}
				}
			}
		}

		return $cookie;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Get the time-dependent variable for nonce creation.
	 * @source wp-includes/pluggable.php
	 */
	private static function nonce_tick() {
		return ceil( time() / ( DAY_IN_SECONDS / 2 ) );
	}

	/**
	 * WP alternative function of hash_equals() for mu-plugins
	 *
	 * Timing attack safe string comparison.
	 * @source http://php.net/manual/en/function.hash-equals.php#115635
	 * @see http://php.net/manual/en/language.operators.increment.php
	 * @see wp-includes/compat.php
	 */
	private static function hash_equals( $a, $b ) {
		// PHP 5 >= 5.6.0 or wp-includes/compat.php
		if ( function_exists( 'hash_equals' ) )
			return hash_equals( $a, $b );

		if( ( $i = strlen( $a ) ) !== strlen( $b ) )
			return FALSE;

		$exp = $a ^ $b; // length of both $a and $b are same
		$ret = 0;

		while ( --$i >= 0 ) {
			$ret |= ord( $exp[ $i ] );
		}

		return ! $ret;
	}

	/**
	 * WP alternative function of hash_hmac() for mu-plugins
	 *
	 * Generate a keyed hash value using the HMAC method.
	 * @source http://php.net/manual/en/function.hash-hmac.php#93440
	 */
	private static function hash_hmac( $algo, $data, $key, $raw_output = FALSE ) {
		// PHP 5 >= 5.1.2, PECL hash >= 1.1 or wp-includes/compat.php
		if ( function_exists( 'hash_hmac' ) )
			return hash_hmac( $algo, $data, $key, $raw_output );

		$packs = array( 'md5' => 'H32', 'sha1' => 'H40' );

		if ( ! isset( $packs[ $algo ] ) )
			return FALSE;

		$pack = $packs[ $algo ];

		if ( strlen( $key ) > 64 )
			$key = pack( $pack, $algo( $key ) );

		$key = str_pad( $key, 64, chr(0) );

		$ipad = ( substr( $key, 0, 64 ) ^ str_repeat( chr(0x36), 64 ) );
		$opad = ( substr( $key, 0, 64 ) ^ str_repeat( chr(0x5C), 64 ) );

		$hmac = $algo( $opad . pack( $pack, $algo( $ipad . $data ) ) );

		return $raw_output ? pack( $pack, $hmac ) : $hmac;
	}

	/**
	 * WP alternative function of wp_sanitize_redirect() for mu-plugins
	 *
	 * Sanitizes a URL for use in a redirect.
	 * @source wp-includes/pluggable.php
	 */
	private static function sanitize_utf8_in_redirect( $matches ) {
		return urlencode( $matches[0] );
	}

	private static function sanitize_redirect( $location ) {
		$regex = '/
			(
				(?: [\xC2-\xDF][\x80-\xBF]        # double-byte sequences   110xxxxx 10xxxxxx
				|   \xE0[\xA0-\xBF][\x80-\xBF]    # triple-byte sequences   1110xxxx 10xxxxxx * 2
				|   [\xE1-\xEC][\x80-\xBF]{2}
				|   \xED[\x80-\x9F][\x80-\xBF]
				|   [\xEE-\xEF][\x80-\xBF]{2}
				|   \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
				|   [\xF1-\xF3][\x80-\xBF]{3}
				|   \xF4[\x80-\x8F][\x80-\xBF]{2}
			){1,40}                              # ...one or more times
			)/x';
		$location = preg_replace_callback( $regex, array( __CLASS__, 'sanitize_utf8_in_redirect' ), $location );
		$location = preg_replace( '|[^a-z0-9-~+_.?#=&;,/:%!*\[\]()@]|i', '', $location );
		$location = self::kses_no_null( $location ); // wp-includes/kses.php
	 
		// remove %0d and %0a from location
		$strip = array( '%0d', '%0a', '%0D', '%0A' );
		return self::deep_replace( $strip, $location ); // wp-includes/formatting.php
	}

	/**
	 * WP alternative function of wp_redirect() for mu-plugins
	 *
	 * Redirects to another page.
	 * @source wp-includes/pluggable.php
	 */
	public static function redirect( $location, $status = 302 ) {
		$_is_apache = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== FALSE || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== FALSE );
		$_is_IIS = ! $_is_apache && ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) !== FALSE || strpos( $_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer' ) !== FALSE );

		// retrieve nonce from referer and add it to the location
		$location = self::rebuild_nonce( $location, $status );
		$location = self::sanitize_redirect( $location );

		if ( $location ) {
			if ( ! $_is_IIS && PHP_SAPI != 'cgi-fcgi' )
				status_header( $status ); // This causes problems on IIS and some FastCGI setups

			header( "Location: $location", true, $status );

			return TRUE;
		}

		else {
			return FALSE;
		}
	}

	/**
	 * WP alternative function of wp_redirect() for mu-plugins
	 *
	 * Performs a safe (local) redirect, using redirect().
	 * @source wp-includes/pluggable.php
	 */
	public static function safe_redirect( $location, $status = 302 ) {
		// Need to look at the URL the way it will end up in wp_redirect()
		$location = self::sanitize_redirect( $location );

		// Filters the redirect fallback URL for when the provided redirect is not safe (local).
		$location = self::validate_redirect( $location, apply_filters( 'wp_safe_redirect_fallback', admin_url(), $status ) );

		self::redirect( $location, $status );
	}

	/**
	 * WP alternative function of wp_validate_redirect() for mu-plugins
	 *
	 * Validates a URL for use in a redirect.
	 * @source wp-includes/pluggable.php
	 */
	private static function validate_redirect( $location, $default = '' ) {
		// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
		if ( substr( $location = trim( $location ), 0, 2 ) == '//' )
			$location = 'http:' . $location;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$test = ( $cut = strpos( $location, '?' ) ) ? substr( $location, 0, $cut ) : $location;

		// @-operator is used to prevent possible warnings in PHP < 5.3.3.
		$lp = @parse_url( $test );

		// Give up if malformed URL
		if ( FALSE === $lp )
			return $default;

		// Allow only http and https schemes. No data:, etc.
		if ( isset( $lp['scheme'] ) && ! ( 'http' == $lp['scheme'] || 'https' == $lp['scheme'] ) )
			return $default;

		// Reject if certain components are set but host is not. This catches urls like https:host.com for which parse_url does not set the host field.
		if ( ! isset( $lp['host'] ) && ( isset( $lp['scheme'] ) || isset( $lp['user'] ) || isset( $lp['pass'] ) || isset( $lp['port'] ) ) )
			return $default;

		// Reject malformed components parse_url() can return on odd inputs.
		foreach ( array( 'user', 'pass', 'host' ) as $component ) {
			if ( isset( $lp[ $component ] ) && strpbrk( $lp[ $component ], ':/?#@' ) )
				return $default;
		}

		$wpp = parse_url( home_url() );

		// Filters the whitelist of hosts to redirect to.
		$allowed_hosts = (array) apply_filters( 'allowed_redirect_hosts', array( $wpp['host'] ), isset( $lp['host'] ) ? $lp['host'] : '' );
		$allowed_hosts[] = 'blackhole.webpagetest.org';

		if ( isset( $lp['host'] ) && ( ! in_array( $lp['host'], $allowed_hosts ) && $lp['host'] != strtolower( $wpp['host'] ) ) )
			$location = $default;

		return $location;
	}

	/**
	 * WP alternative function of wp_get_raw_referer() for mu-plugins
	 *
	 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
	 * @source wp-includes/functions.php
	 * @uses wp_unslash() can be replaced with stripslashes() in this context because the target value is 'string'.
	 */
	private static function get_raw_referer() {
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
			return /*wp_unslash*/ stripslashes( $_REQUEST['_wp_http_referer'] ); // wp-includes/formatting.php

		elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) )
			return /*wp_unslash*/ stripslashes( $_SERVER['HTTP_REFERER'] ); // wp-includes/formatting.php

		return FALSE;
	}

	/**
	 * WP alternative function of wp_get_referer() for mu-plugins
	 *
	 * Retrieve referer from '_wp_http_referer' or HTTP referer.
	 * @source wp-includes/functions.php
	 */
	public static function get_referer() {
		$ref = self::get_raw_referer(); // wp-includes/functions.php
		$req = /*wp_unslash*/ stripslashes( $_SERVER['REQUEST_URI'] );

		if ( $ref && $ref !== $req && $ref !== home_url() . $req )
			return self::validate_redirect( $ref, FALSE );

		return FALSE;
	}

	/**
	 * WP alternative function of is_user_logged_in() for mu-plugins
	 *
	 * Checks if the current visitor is a logged in user.
	 * @source wp-includes/pluggable.php
	 */
	public static function may_be_logged_in() {
		// possibly logged in but should be verified after 'init' hook is fired.
		return did_action( 'init' ) ? is_user_logged_in() : ( self::parse_auth_cookie( 'logged_in' ) ? TRUE : FALSE );
	}

	/**
	 * WP alternative function of get_current_user_id() for mu-plugins
	 *
	 * Get the current user's ID.
	 * @source wp-includes/user.php
	 */
	public static function get_current_user_id() {
		static $uid = 0;

		if ( ! $uid ) {
			$uid = did_action( 'init' ) ? get_current_user_id() : 0;

			if ( ! $uid && isset( $_COOKIE ) ) {
				 foreach ( array_keys( $_COOKIE ) as $key ) {
					if ( 0 === strpos( $key, 'wp-settings-' ) ) {
						$uid = substr( $key, strrpos( $key, '-' ) + 1 ); // get numerical characters
						break;
					}
				}
			}
		}

		return $uid;
	}

	/**
	 * WP alternative function for advanced-cache.php
	 *
	 * Add / Remove slash at the end of string.
	 * @source wp-includes/formatting.php
	 */
	public static function unslashit( $string ) {
		return rtrim( $string, '/\\' );
	}

	public static function slashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}

	/**
	 * WP alternative function of wp_kses_no_null() for advanced-cache.php
	 *
	 * Removes any NULL characters in $string.
	 * @source wp-includes/kses.php
	 */
	private static function kses_no_null( $string ) {
		$string = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string );
		$string = preg_replace( '/\\\\+0+/', '', $string );

		return $string;
	}

	/**
	 * WP alternative function of _deep_replace() for advanced-cache.php
	 *
	 * Perform a deep string replace operation to ensure the values in $search are no longer present.
	 * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that str_replace would return
	 * @source wp-includes/formatting.php
	 */
	private static function deep_replace( $search, $subject ) {
		$subject = (string) $subject;

		$count = 1;
		while ( $count ) {
			$subject = str_replace( $search, '', $subject, $count );
		}

		return $subject;
	}

}