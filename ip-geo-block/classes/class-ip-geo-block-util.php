<?php
/**
 * IP Geo Block - Utilities
 *
 * @package   IP_Geo_Block
 * @author    tokkonopapa <tokkonopapa@yahoo.com>
 * @license   GPL-3.0
 * @link      https://www.ipgeoblock.com/
 * @copyright 2013-2019 tokkonopapa
 */

class IP_Geo_Block_Util {

	/**
	 * Return local time of day.
	 *
	 */
	public static function localdate( $timestamp = FALSE, $fmt = NULL ) {
		static $offset = NULL;
		static $format = NULL;

		NULL === $offset and $offset = wp_timezone_override_offset() * HOUR_IN_SECONDS; // @since 2.8.0
		NULL === $format and $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

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
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] && 'HEAD' !== $_SERVER['REQUEST_METHOD'] )
			return FALSE; // POST, PUT, DELETE

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
		return is_array( $string ) ? $string : array_filter( explode( $delimiters[0], str_replace( $delimiters, $delimiters[0], $string ) ) );
	}

	/**
	 * HTML/XHTML filter that only allows some elements and attributes
	 *
	 * @see wp-includes/kses.php
	 */
	public static function kses( $str, $allow_tags = TRUE ) {
		is_array( $allow_tags ) or $allow_tags = ( $allow_tags ? $GLOBALS['allowedtags'] : array() );

		// wp_kses() is unavailable on advanced-cache.php
		return wp_kses( $str, $allow_tags );
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
		if ( self::is_user_logged_in() && empty( $_REQUEST[ $nonce ] ) &&
		     self::retrieve_nonce( $nonce ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			// add nonce at add_admin_nonce() to handle the client side redirection.
			self::redirect( esc_url_raw( $_SERVER['REQUEST_URI'] ), 302 );
			exit;
		}
	}

	/**
	 * Retrieve or remove nonce and rebuild query strings.
	 *
	 */
	public static function rebuild_nonce( $location, $retrieve = TRUE ) {
		// check if the location is internal
		$url = parse_url( $location );
		$key = IP_Geo_Block::get_auth_key();

		if ( empty( $url['host'] ) || $url['host'] === parse_url( home_url(), PHP_URL_HOST ) ) {
			if ( $retrieve ) {
				// it doesn't care a nonce is valid or not, but must be sanitized
				if ( $nonce = self::retrieve_nonce( $key ) ) {
					return esc_url_raw( add_query_arg(
						array(
							$key => FALSE, // delete onece
							$key => $nonce // add again
						),
						$location
					) );
				}
			}

			else {
				// remove a nonce from existing query
				$location = esc_url_raw( add_query_arg( $key, FALSE, $location ) );
				wp_parse_str( isset( $url['query'] ) ? $url['query'] : '', $query );
				$args = array();
				foreach ( $query as $arg => $val ) { // $val is url decoded
					if ( FALSE !== strpos( $val, $key ) ) {
						$val = urlencode( add_query_arg( $key, FALSE, $val ) );
					}
					$args[] = "$arg=$val";
				}
				$url['query'] = implode( '&', $args );
				return self::unparse_url( $url );
			}
		}

		return $location;
	}

	/**
	 * Convert back to string from a parsed url.
	 *
	 * @source https://php.net/manual/en/function.parse-url.php#106731
	 */
	private static function unparse_url( $url ) {
		$scheme   = ! empty( $url['scheme'  ] ) ?       $url['scheme'  ] . '://' : '';
		$host     = ! empty( $url['host'    ] ) ?       $url['host'    ] : '';
		$port     = ! empty( $url['port'    ] ) ? ':' . $url['port'    ] : '';
		$user     = ! empty( $url['user'    ] ) ?       $url['user'    ] : '';
		$pass     = ! empty( $url['pass'    ] ) ? ':' . $url['pass'    ] : '';
		$pass     =        ( $user  ||  $pass ) ?       $pass . '@'      : '';
		$path     = ! empty( $url['path'    ] ) ?       $url['path'    ] : '';
		$query    = ! empty( $url['query'   ] ) ? '?' . $url['query'   ] : '';
		$fragment = ! empty( $url['fragment'] ) ? '#' . $url['fragment'] : '';

		return "$scheme$user$pass$host$port$path$query$fragment";
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
		if ( self::hash_equals( $expected, (string)$nonce ) )
			return 1;

		// Nonce generated 12-24 hours ago
		$expected = substr( self::hash_nonce( ( $exp - 1 ) . '|' . $action . '|' . $uid . '|' . $tok ), -12, 10 );
		if ( self::hash_equals( $expected, (string)$nonce ) )
			return 2;

		// Invalid nonce
		return FALSE;
	}

	/**
	 * WP alternative function of wp_hash() for mu-plugins
	 *
	 * Get hash of given string for nonce.
	 * @source wp-includes/pluggable.php
	 */
	private static function hash_nonce( $data, $scheme = 'nonce' ) {
		$salt = array(
			'auth'        => AUTH_KEY        . AUTH_SALT,
			'secure_auth' => SECURE_AUTH_KEY . SECURE_AUTH_SALT,
			'logged_in'   => LOGGED_IN_KEY   . LOGGED_IN_SALT,
			'nonce'       => NONCE_KEY       . NONCE_SALT,
		);

		return self::hash_hmac( 'md5', $data, apply_filters( 'salt', $salt[ $scheme ], $scheme ) );
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
		return ! empty( $cookie['token'] ) ? $cookie['token'] : NONCE_KEY . NONCE_SALT;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Parse a cookie into its components. It assumes the key including $scheme.
	 * @source wp-includes/pluggable.php
	 */
	private static function parse_auth_cookie( $scheme = 'logged_in' ) {
		static $cache_cookie = NULL;

		if ( NULL === $cache_cookie ) {
			$cache_cookie = FALSE;

			// @since 3.0.0 wp_cookie_constants() in wp-includes/default-constants.php
			if ( ! defined( 'COOKIEHASH' ) )
				wp_cookie_constants();

			switch ( $scheme ) {
			  case 'auth':
				$cookie_name = AUTH_COOKIE;
				break;

			  case 'secure_auth':
				$cookie_name = SECURE_AUTH_COOKIE;
				break;

			  case "logged_in":
				$cookie_name = LOGGED_IN_COOKIE;
				break;

			  default:
				if ( is_ssl() ) {
					$cookie_name = SECURE_AUTH_COOKIE;
					$scheme = 'secure_auth';
				} else {
					$cookie_name = AUTH_COOKIE;
					$scheme = 'auth';
				}
			}

			if ( empty( $_COOKIE[ $cookie_name ] ) )
				return FALSE;

			$cookie = $_COOKIE[ $cookie_name ];
			$n = count( $cookie_elements = explode( '|', $cookie ) );

			if ( 4 === $n ) { // @since 4.0.0
				list( $username, $expiration, $token, $hmac ) = $cookie_elements;
				$cache_cookie = compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
			}

			elseif ( 3 === $n ) { // @before 4.0.0
				list( $username, $expiration, $hmac ) = $cookie_elements;
				$cache_cookie = compact( 'username', 'expiration', 'hmac', 'scheme' );
			}

			else {
				return FALSE;
			}
		}

		return $cache_cookie;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieve user info by a given field
	 * @source wp-includes/pluggable.php @since 2.8.0
	 */
	public static function get_user_by( $field, $value ) {
		if ( function_exists( 'get_user_by' ) )
			return get_user_by( $field, $value );

		$userdata = WP_User::get_data_by( $field, $value ); // wp-includes/class-wp-user.php @since 3.3.0

		if ( ! $userdata )
			return FALSE;

		$user = new WP_User;
		$user->init( $userdata );

		return $user;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Validates authentication cookie.
	 * @source wp-includes/pluggable.php
	 */
	private static function validate_auth_cookie( $scheme = 'logged_in' ) {
		static $cache_user = NULL;

		if ( NULL === $cache_user ) {
			if ( ! ( $cookie = self::parse_auth_cookie( $scheme ) ) )
				return $cache_user = FALSE;

			$scheme   = $cookie['scheme'];
			$username = $cookie['username'];
			$hmac     = $cookie['hmac'];
			$token    = isset( $cookie['token'] ) ? $cookie['token'] : NULL;
			$expired  = $expiration = $cookie['expiration'];

			// Allow a grace period for POST and Ajax requests
			if ( defined( 'DOING_AJAX' ) || 'POST' === $_SERVER['REQUEST_METHOD'] )
				$expired += HOUR_IN_SECONDS;

			// Quick check to see if an honest cookie has expired
			if ( $expired < time() )
				return $cache_user = FALSE;

			if ( ! ( $cache_user = self::get_user_by( 'login', $username ) ) ) // wp-includes/pluggable.php @since 2.8.0
				return $cache_user = FALSE;

			$pass_frag = substr( $cache_user->user_pass, 8, 4 );

			if ( is_null( $token ) ) { // @before 4.0.0
				$key = self::hash_nonce( $username . $pass_frag . '|' . $expiration, $scheme );
				$hash = hash_hmac( 'md5', $username . '|' . $expiration, $key );
			}

			else { // @since 4.0.0
				// If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
				$key = self::hash_nonce( $username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );
				$algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
				$hash = self::hash_hmac( $algo, $username . '|' . $expiration . '|' . $token, $key );
			}

			if ( ! self::hash_equals( $hash, $hmac ) )
				return $cache_user = FALSE;

			if ( class_exists( 'WP_Session_Tokens', FALSE ) ) { // @since 4.0.0
				$manager = WP_Session_Tokens::get_instance( $cache_user->ID );
				if ( ! $manager->verify( $token ) )
					return $cache_user = FALSE;
			}
		}

		return $cache_user;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Get the time-dependent variable for nonce creation.
	 * @source wp_nonce_tick() in wp-includes/pluggable.php
	 */
	private static function nonce_tick() {
		return ceil( time() / ( apply_filters( 'nonce_life', DAY_IN_SECONDS ) / 2 ) );
	}

	/**
	 * WP alternative function of hash_equals() for mu-plugins
	 *
	 * Timing attack safe string comparison.
	 * @source https://php.net/manual/en/function.hash-equals.php#115635
	 * @see https://php.net/manual/en/language.operators.increment.php
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
	 * @source https://php.net/manual/en/function.hash-hmac.php#93440
	 */
	public static function hash_hmac( $algo, $data, $key, $raw_output = FALSE ) {
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
			){1,40}                               # ...one or more times
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
		// retrieve nonce from referer and add it to the location
		$location = self::rebuild_nonce( $location, TRUE );
		$location = self::sanitize_redirect( $location );

		if ( $location ) {
			if ( ! self::is_IIS() && PHP_SAPI != 'cgi-fcgi' )
				status_header( $status ); // This causes problems on IIS and some FastCGI setups

			header( "Location: $location", TRUE, $status );
			return TRUE;
		} else {
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
	public static function is_user_logged_in() {
		static $logged_in = NULL;

		if ( NULL === $logged_in ) {
			if ( did_action( 'init' ) ) {
				$logged_in = is_user_logged_in(); // @since 2.0.0
			} else {
				$user = self::validate_auth_cookie();
				$logged_in = $user ? $user->exists() : FALSE; // @since 3.4.0
			}
		}

		return $logged_in;
	}

	/**
	 * WP alternative function of get_current_user_id() for mu-plugins
	 *
	 * Get the current user's ID.
	 * @source wp-includes/user.php
	 */
	public static function get_current_user_id() {
		static $user_id = NULL;

		if ( NULL === $user_id ) {
			if ( did_action( 'init' ) ) {
				$user_id = get_current_user_id(); // @since MU 3.0.0
			} else {
				$user = self::validate_auth_cookie();
				$user_id = $user ? $user->ID : 0; // @since 2.0.0
			}
		}

		return $user_id;
	}

	/**
	 * WP alternative function current_user_can() for mu-plugins
	 *
	 * Whether the current user has a specific capability.
	 * @source wp-includes/capabilities.php
	 */
	public static function current_user_can( $capability ) {
		if ( did_action( 'init' ) )
			return current_user_can( $capability ); // @since 2.0.0

		return ( $user = self::validate_auth_cookie() ) ? $user->has_cap( $capability ) : FALSE; // @since 2.0.0
	}

	/**
	 * Check if the current user has the capabilities.
	 *
	 */
	public static function current_user_has_caps( $caps ) {
		$user = self::get_user_by( 'id', self::get_current_user_id() );
		if ( is_object( $user ) ) {
			foreach ( $caps as $cap ) {
				if ( $user->has_cap( $cap ) ) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * WP alternative function get_allowed_mime_types() for mu-plugins
	 *
	 * Retrieve the file type from the file name.
	 * @source wp-includes/functions.php @since 2.0.4
	 */
	public static function get_allowed_mime_types( $user = null ) {
		$type = wp_get_mime_types();

		unset( $type['swf'], $type['exe'] );
		if ( ! self::current_user_can( 'unfiltered_html' ) )
			unset( $type['htm|html'] );

		return apply_filters( 'upload_mimes', $type, $user );
	}

	/**
	 * WP alternative function wp_check_filetype_and_ext() for mu-plugins
	 *
	 * Attempt to determine the real file type of a file.
	 * @source wp-includes/functions.php @since 3.0.0
	 */
	public static function check_filetype_and_ext( $fileset, $mode, $mimeset ) {
		$src = @$fileset['tmp_name'];
		$dst = str_replace( "\0", '', urldecode( @$fileset['name'] ) );

		// We can't do any further validation without a file to work with
		if ( ! @file_exists( $src ) )
			return TRUE;

		// check extension at the tail in blacklist
		if ( 2 === (int)$mode ) {
			$type = pathinfo( $dst, PATHINFO_EXTENSION );
			if ( $type && FALSE !== stripos( $mimeset['black_list'], $type ) ) {
				return FALSE;
			}
		}

		// check extension at the tail in whitelist
		$type = wp_check_filetype( $dst, $mimeset['white_list'] );
		if ( 1 === (int)$mode ) {
			if ( ! $type['type'] ) {
				return FALSE;
			}
		}

		// check images using GD (it doesn't care about extension if it's a real image file)
		if ( 0 === strpos( $type['type'], 'image/' ) && function_exists( 'getimagesize' ) ) {
			$info = @getimagesize( $src ); // 0:width, 1:height, 2:type, 3:string
			if ( ! $info || $info[0] > 9000 || $info[1] > 9000 ) { // max: EOS 5Ds
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Arrange $_FILES array
	 *
	 * @see https://php.net/manual/features.file-upload.multiple.php#53240
	 */
	public static function arrange_files( $files ) {
		if ( ! is_array( $files['name'] ) )
			return array( $files );

		$file_array = array();
		$file_count = count( $files['name'] );
		$file_keys = array_keys( $files );

		for ( $i = 0; $i < $file_count; ++$i ) {
			foreach ( $file_keys as $key ) {
				$file_array[ $i ][ $key ] = $files[ $key ][ $i ];
			}
		}

		return $file_array;
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

	/**
	 * Remove `HOST` and `HOST=...` from `UA and qualification`
	 *
	 */
	public static function mask_qualification( $ua_list ) {
		return preg_replace( array( '/HOST[^,]*?/', '/\*[:#]!?\*,?/' ), array( '*', '' ), $ua_list );
	}

	/**
	 * https://codex.wordpress.org/WordPress_Feeds
	 *
	 */
	public static function is_feed( $request_uri ) {
		return /* function_exists( 'is_feed' ) ? is_feed() : */ ( isset( $_GET['feed'] ) ?
			( preg_match( '!(?:comments-)?(?:feed|rss|rss2|rdf|atom)$!', $_GET['feed'] ) ? TRUE : FALSE ) :
			( preg_match( '!(?:comments/)?(?:feed|rss|rss2|rdf|atom)/?$!', $request_uri ) ? TRUE : FALSE )
		);
	}

	/**
	 * Whether the server software is IIS or something else
	 *
	 * @source wp-includes/vers.php
	 */
	private static function is_IIS() {
		$_is_apache = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== FALSE || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== FALSE );
		$_is_IIS = ! $_is_apache && ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) !== FALSE || strpos( $_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer' ) !== FALSE );

		return $_is_IIS ? substr( $_SERVER['SERVER_SOFTWARE'], strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS/' ) + 14 ) : FALSE;
	}

	/**
	 * Check proxy variable
	 *
	 * @see https://developer.wordpress.org/reference/classes/wp_community_events/get_unsafe_client_ip/
	 */
	public static function get_proxy_var() {
		foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED' ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				return $var;
			}
		}

		return NULL;
	}

	/**
	 * Pick up all the IPs in HTTP_X_FORWARDED_FOR, HTTP_CLIENT_IP and etc.
	 *
	 * @param  array  $ips  array of candidate IP addresses
	 * @param  string $vars comma separated keys in $_SERVER for http header ('HTTP_...')
	 * @return array  $ips  array of candidate IP addresses
	 */
	public static function retrieve_ips( $ips = array(), $vars = NULL ) {
		foreach ( explode( ',', $vars ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				foreach ( explode( ',', $_SERVER[ $var ] ) as $ip ) {
					if ( ! in_array( $ip = trim( $ip ), $ips, TRUE ) && ! self::is_private_ip( $ip ) ) {
						array_unshift( $ips, $ip );
					}
				}
			}
		}

		return $ips;
	}

	/**
	 * Get client IP address
	 *
	 * @param  string $vars comma separated keys in $_SERVER for http header ('HTTP_...')
	 * @return string $ip   IP address
	 * @link   https://docs.aws.amazon.com/elasticloadbalancing/latest/classic/x-forwarded-headers.html
	 * @link   https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/RemoteAddress.php
	 */
	public static function get_client_ip( $vars = NULL ) {
		foreach ( explode( ',', $vars ) as $var ) {
			if ( isset( $_SERVER[ $var ] ) ) {
				$ips = array_map( 'trim', explode( ',', $_SERVER[ $var ] ) );
				while ( $var = array_pop( $ips ) ) {
					if ( ! self::is_private_ip( $var ) ) {
						return $var;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'; // for CLI
	}

	/**
	 * Check the client IP address behind the VPN proxy
	 *
	 */
	public static function get_proxy_ip( $ip ) {
		// Chrome datasaver
		if ( isset( $_SERVER['HTTP_VIA'], $_SERVER['HTTP_FORWARDED'] ) && FALSE !== strpos( $_SERVER['HTTP_VIA'], 'Chrome-Compression-Proxy' ) ) {
			// require_once IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php';
			// if ( FALSE !== strpos( 'google', IP_Geo_Block_Lkup::gethostbyaddr( $ip ) ) )
			$proxy = preg_replace( '/^for=.*?([a-f\d\.:]+).*$/', '$1', $_SERVER['HTTP_FORWARDED'] );
		}

		// Puffin browser
		elseif ( isset( $_SERVER['HTTP_X_PUFFIN_UA'], $_SERVER['HTTP_USER_AGENT'] ) && FALSE !== strpos( $_SERVER['HTTP_USER_AGENT'], 'Puffin' ) ) {
			$proxy = trim( end( $proxy = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ); // or trim( $proxy[0] )
		}

		return empty( $proxy ) ? $ip : $proxy;
	}

	/**
	 * Check the IP address is private or not
	 *
	 * @link https://en.wikipedia.org/wiki/Localhost
	 * @link https://en.wikipedia.org/wiki/Private_network
	 * @link https://en.wikipedia.org/wiki/Reserved_IP_addresses
	 *
	 * 10.0.0.0/8 reserved for Private-Use Networks [RFC1918]
	 * 127.0.0.0/8 reserved for Loopback [RFC1122]
	 * 172.16.0.0/12 reserved for Private-Use Networks [RFC1918]
	 * 192.168.0.0/16 reserved for Private-Use Networks [RFC1918]
	 */
	public static function is_private_ip( $ip ) {
		// https://php.net/manual/en/filter.filters.flags.php
		return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	/**
	 * Get IP address of the host server
	 *
	 * @link https://php.net/manual/en/reserved.variables.server.php#88418
	 */
	public static function get_server_ip() {
		return isset( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : ( (int)self::is_IIS() >= 7 ?
		     ( isset( $_SERVER['LOCAL_ADDR' ] ) ? $_SERVER['LOCAL_ADDR' ] : NULL ) : NULL );
	}

	/**
	 * Get the list of registered actions
	 *
	 */
	public static function get_registered_actions( $ajax = FALSE ) {
		$installed = array();

		global $wp_filter;
		foreach ( $wp_filter as $key => $val ) {
			if ( $ajax && FALSE !== strpos( $key, 'wp_ajax_' ) ) {
				if ( 0 === strpos( $key, 'wp_ajax_nopriv_' ) ) {
					$key = substr( $key, 15 ); // 'wp_ajax_nopriv_'
					$val = 2;                  // without privilege
				} else {
					$key = substr( $key, 8 );  // 'wp_ajax_'
					$val = 1;                  // with privilege
				}
				$installed[ $key ] = isset( $installed[ $key ] ) ? $installed[ $key ] | $val : $val;
			} elseif ( FALSE !== strpos( $key, 'admin_post_' ) ) {
				if ( 0 === strpos( $key, 'admin_post_nopriv_' ) ) {
					$key = substr( $key, 18 ); // 'admin_post_nopriv_'
					$val = 2;                  // without privilege
				} else {
					$key = substr( $key, 11 ); // 'admin_post_'
					$val = 1;                  // with privilege
				}
				$installed[ $key ] = isset( $installed[ $key ] ) ? $installed[ $key ] | $val : $val;
			}
		}

		unset( $installed['ip_geo_block'] );
		return $installed;
	}

	/**
	 * Get the list of multisite managed by the specific user
	 *
	 * This function should be called after 'init' hook is fired.
	 */
	public static function get_sites_of_user() {
		$sites = array( preg_replace( '/^https?:/', '', home_url() ) );

		foreach ( get_blogs_of_user( self::get_current_user_id(), current_user_can( 'manage_network_options' ) ) as $site ) { // @since 3.0.0
			if ( ! in_array( $url = preg_replace( '/^https?:/', '', $site->siteurl ), $sites, TRUE ) ) {
				$sites[] = $url;
			}
		}

		return $sites;
	}

	/**
	 * Anonymize IP address in string
	 *
	 */
	public static function anonymize_ip( $subject, $strict = TRUE ) {
		return $strict ?
			preg_replace( '/(:)*[0-9a-f\*]{0,4}$/', '$1***', $subject, 1 ) :
			preg_replace(
				array(
					'/([0-9a-f]{3,})[0-9a-f]{3,}/',           // loose pattern for IPv[4|6]
					'/((?:[0-9]{1,3}[-_x\.]){3,})[0-9]+/',    // loose pattern for IPv4
					'/((?:[0-9a-f]+[-:]+)+)[0-9a-f:\*]{2,}/', // loose pattern for IPv6
				),
				'$1***',
				$subject
			);
	}

	/**
	 * Generates cryptographically secure pseudo-random bytes
	 *
	 */
	private static function random_bytes( $length = 64 ) {
		if ( version_compare( PHP_VERSION, '7.0.0', '<' ) )
			require_once IP_GEO_BLOCK_PATH . 'includes/random_compat/random.php';

		// align length
		$length = max( 64, $length ) - ( $length % 2 );

		try {
			$str = bin2hex( random_bytes( $length / 2 ) );
		} catch ( TypeError $e ) {
			$str = NULL;
		} catch ( Exception $e ) {
			$str = NULL;
		}

		if ( empty( $str ) && function_exists( 'openssl_random_pseudo_bytes' ) )
			$str = bin2hex( openssl_random_pseudo_bytes( $length / 2 ) );

		if ( empty( $str ) ) {
			for( $i = 0; $i < $length; $i++ ) {
				$str .= chr( ( mt_rand( 1, 36 ) <= 26 ) ? mt_rand( 97, 122 ) : mt_rand( 48, 57 ) );
			}
		}

		return $str;
	}

	/**
	 * Manipulate emergency login link
	 *
	 */
	private static function hash_link( $link ) {
		return self::hash_hmac(
			function_exists( 'hash' ) ? 'sha256' /* 32 bytes (256 bits) */ : 'sha1' /* 20 bytes (160 bits) */,
			$link, NONCE_SALT, TRUE
		);
	}

	// used at `admin_ajax_callback()` in class-ip-geo-block-admin.php
	public static function generate_link( $context ) {
		$link = self::random_bytes();
		$hash = bin2hex( self::hash_link( $link ) );

		/**
		 * Verify the consistency of `self::hash_hmac()`
		 *   key from external: self::verify_link( $link )
		 *   key from internal: self::verify_link( 'link', 'hash' )
		 */
		$settings = IP_Geo_Block::get_option();
		$settings['login_link'] = array(
			'link' => $hash,
			'hash' => bin2hex( self::hash_link( $hash ) ),
		);

		if ( $context->is_network_admin() && $settings['network_wide'] )
			$context->update_multisite_settings( $settings );
		else
			IP_Geo_Block::update_option( $settings );

		return add_query_arg( IP_Geo_Block::PLUGIN_NAME . '-key', $link, wp_login_url() );
	}

	// used at `admin_ajax_callback()` in class-ip-geo-block-admin.php
	public static function delete_link( $context ) {
		$settings = IP_Geo_Block::get_option();
		$settings['login_link'] = array( 'link' => NULL, 'hash' => NULL );

		if ( $context->is_network_admin() && $settings['network_wide'] )
			$context->update_multisite_settings( $settings );
		else
			IP_Geo_Block::update_option( $settings );
	}

	// used at `tab_setup()` in tab-settings.php
	public static function get_link() {
		$settings = IP_Geo_Block::get_option();
		return $settings['login_link']['link'] ? $settings['login_link']['link'] : FALSE;
	}

	// used at `validate_login()` in class-ip-geo-block.php
	public static function verify_link( $link, $hash = NULL ) {
		return self::hash_equals( self::hash_link( $link ), pack( 'H*', $hash ? $hash : self::get_link() ) ); // hex2bin() for PHP 5.4+
	}

}

// Some plugins need this when this plugin is installed as mu-plugins
if ( ! function_exists( 'get_userdata' ) ) :
/**
 * Retrieve user info by user ID.
 *
 * @since 0.71
 *
 * @param int $user_id User ID
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_userdata( $user_id ) {
	return IP_Geo_Block_Util::get_user_by( 'id', $user_id );
}
endif;