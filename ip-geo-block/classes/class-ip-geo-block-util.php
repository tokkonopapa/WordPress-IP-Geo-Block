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
	 * @param string $url URL of remote file to be downloaded.
	 * @param array $args request headers.
	 * @param string $filename full path to the downloaded file.
	 * @param int $modified time of last modified on the remote server.
	 * @return array status message.
	 */
	public static function download_zip( $url, $args, $filename, $modified ) {
		if ( ! function_exists( 'download_url' ) )
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

		// if the name of src file is changed, then update the dst
		if ( basename( $filename ) !== ( $base = pathinfo( $url, PATHINFO_FILENAME ) ) ) {
			$filename = dirname( $filename ) . '/' . $base;
		}

		// check file
		if ( ! file_exists( $filename ) )
			$modified = 0;

		// set 'If-Modified-Since' request header
		$args += array(
			'headers'  => array(
				'If-Modified-Since' => gmdate( DATE_RFC1123, (int)$modified ),
			),
		);

		// fetch file and get response code & message
		$src = wp_remote_head( ( $url = esc_url_raw( $url ) ), $args );

		if ( is_wp_error( $src ) )
			return array(
				'code' => $src->get_error_code(),
				'message' => $src->get_error_message(),
			);

		$code = wp_remote_retrieve_response_code   ( $src );
		$mssg = wp_remote_retrieve_response_message( $src );
		$data = wp_remote_retrieve_header( $src, 'last-modified' );
		$modified = $data ? strtotime( $data ) : $modified;

		if ( 304 == $code )
			return array(
				'code' => $code,
				'message' => __( 'Your database file is up-to-date.', 'ip-geo-block' ),
				'filename' => $filename,
				'modified' => $modified,
			);

		elseif ( 200 != $code )
			return array(
				'code' => $code,
				'message' => $code.' '.$mssg,
			);

		// downloaded and unzip
		try {
			// download file
			$src = download_url( $url );

			if ( is_wp_error( $src ) )
				throw new Exception(
					$src->get_error_code() . ' ' . $src->get_error_message()
				);

			// get extension
			$args = strtolower( pathinfo( $url, PATHINFO_EXTENSION ) );

			// unzip file
			if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
				if ( FALSE === ( $gz = gzopen( $src, 'r' ) ) )
					throw new Exception(
						sprintf(
							__( 'Unable to read %s. Please check the permission.', 'ip-geo-block' ),
							$src
						)
					);

				if ( FALSE === ( $fp = @fopen( $filename, 'wb' ) ) )
					throw new Exception(
						sprintf(
							__( 'Unable to write %s. Please check the permission.', 'ip-geo-block' ),
							$filename
						)
					);

				// same block size in wp-includes/class-http.php
				while ( $data = gzread( $gz, 4096 ) )
					fwrite( $fp, $data, strlen( $data ) );

				gzclose( $gz );
				fclose ( $fp );
			}

			elseif ( 'zip' === $args && class_exists( 'ZipArchive' ) ) {
				// https://codex.wordpress.org/Function_Reference/unzip_file
				WP_Filesystem();
				$ret = unzip_file( $src, dirname( $filename ) ); // @since 2.5

				if ( is_wp_error( $ret ) )
					throw new Exception(
						$ret->get_error_code() . ' ' . $ret->get_error_message()
					);
			}

			@unlink( $src );
		}

		// error handler
		catch ( Exception $e ) {
			if ( 'gz' === $args && function_exists( 'gzopen' ) ) {
				! empty( $gz ) and gzclose( $gz );
				! empty( $fp ) and fclose ( $fp );
			}

			! is_wp_error( $src ) and @unlink( $src );

			return array(
				'code' => $e->getCode(),
				'message' => $e->getMessage(),
			);
		}

		return array(
			'code' => $code,
			'message' => sprintf(
				__( 'Last update: %s', 'ip-geo-block' ),
				self::localdate( $modified )
			),
			'filename' => $filename,
			'modified' => $modified,
		);
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
	 */
	public static function kses( $str, $allow_tags = TRUE ) {
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
		if ( self::is_user_logged_in() && empty( $_REQUEST[ $nonce ] ) &&
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
		$key = IP_Geo_Block::PLUGIN_NAME . '-auth-nonce';

		if ( $nonce = self::retrieve_nonce( $key ) ) { // must be sanitized
			$host = parse_url( $location, PHP_URL_HOST );

			// check if the location is internal
			if ( ! $host || $host === parse_url( home_url(), PHP_URL_HOST ) ) {
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
	 * WP alternative function for mu-plugins
	 *
	 * Creates a cryptographic tied to the action, user, session, and time.
	 * @source: wp-includes/pluggable.php
	 */
	public static function create_nonce( $action = -1, $ip_addr = NULL ) {
		$uid = self::get_current_user( $ip_addr );
		$tok = self::get_session_token();
		$exp = self::nonce_tick();

		return substr( self::hash_nonce( $exp . '|' . $action . '|' . $uid . '|' . $tok ), -12, 10 );
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Verify that correct nonce was used with time limit.
	 * @source: wp-includes/pluggable.php
	 */
	public static function verify_nonce( $nonce, $action = -1, $ip_addr = NULL ) {
		$uid = self::get_current_user( $ip_addr );
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
		return false;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Get hash of given string for nonce.
	 * @source: wp-includes/pluggable.php
	 */
	private static function hash_nonce( $data ) {
		return self::hash_hmac( 'md5', $data, NONCE_KEY . NONCE_SALT );
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieve the current session token from the logged_in cookie.
	 * @source: wp-includes/user.php
	 */
	private static function get_session_token() {
		// Arrogating logged_in cookie never cause the privilege escalation.
		$cookie = self::parse_auth_cookie( 'logged_in' );
		return ! empty( $cookie['token'] ) ? $cookie['token'] : AUTH_KEY . AUTH_SALT;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Parse a cookie into its components.
	 * @source: wp-includes/pluggable.php
	 */
	private static function parse_auth_cookie( $scheme ) {
		static $cookie = NULL;

		if ( NULL === $cookie ) {
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
	 * @source: wp-includes/pluggable.php
	 */
	private static function nonce_tick() {
		return ceil( time() / ( DAY_IN_SECONDS / 2 ) );
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieve the current user identification.
	 * @source: wp-includes/user.php
	 */
	private static function get_current_user( $ip_addr ) {
		if ( $ip_addr ) {
			require_once( IP_GEO_BLOCK_PATH . 'classes/class-ip-geo-block-lkup.php' );

			$num = '';
			$sum = 0;

			foreach ( unpack( 'C*', IP_Geo_Block_Lkup::inet_pton( $ip_addr ) ) as $byte ) {
				$sum += $byte;
				$num .= (string)( $byte % 10 );
			}

			$num += $sum;
		}

		elseif ( isset( $_COOKIE ) ) {
			 foreach ( array_keys( $_COOKIE ) as $key ) {
				if ( 0 === strpos( $key, 'wp-settings-' ) ) {
					$num = preg_replace( '/\D/', '', $key ); // get numerical characters
					break;
				}
			}
		}
/*
		// add something which a visitor can't control
		$num .= substr( SECURE_AUTH_KEY, 1, 6 ); // @since 2.6

		// add something unique
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && is_string( $_SERVER['HTTP_USER_AGENT'] ) )
			$num .= preg_replace( '/[^-,:!*+\.\/\w\s]/', '', $_SERVER['HTTP_USER_AGENT'] );
*/
		return isset( $num ) ? $num : '0';
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Timing attack safe string comparison.
	 */
	private static function hash_equals( $a, $b ) {
		// PHP 5 >= 5.6.0 or wp-includes/compat.php
		if ( function_exists( 'hash_equals' ) )
			return hash_equals( $a, $b );

		// http://php.net/manual/en/function.hash-equals.php#115635
		if( ( $i = strlen( $a ) ) !== strlen( $b ) )
			return FALSE;

		$exp = $a ^ $b; // 1 === strlen( 'a' ^ 'ab' )
		$ret = 0;

		while ( --$i >= 0 )
			$ret |= ord( $exp[ $i ] );

		return 0 === $ret;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Generate a keyed hash value using the HMAC method.
	 */
	private static function hash_hmac( $algo, $data, $key, $raw_output = FALSE ) {
		// PHP 5 >= 5.1.2, PECL hash >= 1.1 or wp-includes/compat.php
		if ( function_exists( 'hash_hmac' ) )
			return hash_hmac( $algo, $data, $key, $raw_output );

		// http://php.net/manual/en/function.hash-hmac.php#93440
		$packs = array( 'md5' => 'H32', 'sha1' => 'H40' );

		if ( ! isset( $packs[ $algo ] ) )
			return FALSE;

		$pack = $packs[ $algo ];

		if ( strlen( $key ) > 64 )
			$key = pack( $pack, $algo( $key ) );

		$key = str_pad( $key, 64, chr(0) );

		$ipad = substr( $key, 0, 64 ) ^ str_repeat( chr(0x36), 64 );
		$opad = substr( $key, 0, 64 ) ^ str_repeat( chr(0x5C), 64 );

		$hmac = $algo( $opad . pack( $pack, $algo( $ipad . $data ) ) );

		return $raw_output ? pack( $pack, $hmac ) : $hmac;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Sanitizes a URL for use in a redirect.
	 * @source: wp-includes/pluggable.php
	 */
	private static function sanitize_utf8_in_redirect( $matches ) {
		return urlencode( $matches[0] );
	}

	private static function sanitize_redirect($location) {
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
		$location = wp_kses_no_null( $location ); // wp-includes/kses.php
	 
		// remove %0d and %0a from location
		$strip = array( '%0d', '%0a', '%0D', '%0A' );
		return _deep_replace( $strip, $location ); // wp-includes/formatting.php
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Redirects to another page.
	 * @source: wp-includes/pluggable.php
	 */
	public static function redirect( $location, $status = 302 ) {
		$_is_apache = (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false);
		$_is_IIS = !$_is_apache && (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false || strpos($_SERVER['SERVER_SOFTWARE'], 'ExpressionDevServer') !== false);

		// retrieve nonce from referer and add it to the location
		$location = self::rebuild_nonce( $location, $status );
		$location = self::sanitize_redirect( $location );

		if ( $location ) {
			if ( ! $_is_IIS && PHP_SAPI != 'cgi-fcgi' )
				status_header( $status ); // This causes problems on IIS and some FastCGI setups

			header( "Location: $location", true, $status );

			return true;
		}

		else {
			return false;
		}
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Validates a URL for use in a redirect.
	 * @source: wp-includes/pluggable.php
	 */
	private static function validate_redirect( $location, $default = '' ) {
		$location = trim( $location );
		// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
		if ( substr( $location, 0, 2 ) == '//' )
			$location = 'http:' . $location;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$test = ( $cut = strpos( $location, '?' ) ) ? substr( $location, 0, $cut ) : $location;

		// @-operator is used to prevent possible warnings in PHP < 5.3.3.
		$lp = @parse_url( $test );

		// Give up if malformed URL
		if ( false === $lp )
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

		/**
		 * Filters the whitelist of hosts to redirect to.
		 *
		 * @since 2.3.0
		 *
		 * @param array       $hosts An array of allowed hosts.
		 * @param bool|string $host  The parsed host; empty if not isset.
		 */
		$allowed_hosts = (array) apply_filters( 'allowed_redirect_hosts', array( $wpp['host'] ), isset( $lp['host'] ) ? $lp['host'] : '' );

		if ( isset( $lp['host'] ) && ( ! in_array( $lp['host'], $allowed_hosts ) && $lp['host'] != strtolower( $wpp['host'] ) ) )
			$location = $default;

		return $location;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieves unvalidated referer from '_wp_http_referer' or HTTP referer.
	 * @source: wp-includes/functions.php
	 */
	private static function get_raw_referer() {
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
			return wp_unslash( $_REQUEST['_wp_http_referer'] ); // wp-includes/formatting.php

		elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) )
			return wp_unslash( $_SERVER['HTTP_REFERER'] ); // wp-includes/formatting.php

		return false;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Retrieve referer from '_wp_http_referer' or HTTP referer.
	 * @source: wp-includes/functions.php
	 */
	public static function get_referer() {
		$ref = self::get_raw_referer(); // wp-includes/functions.php

		if ( $ref && $ref !== wp_unslash( $_SERVER['REQUEST_URI'] ) && $ref !== home_url() . wp_unslash( $_SERVER['REQUEST_URI'] ) )
			return self::validate_redirect( $ref, false );

		return false;
	}

	/**
	 * WP alternative function for mu-plugins
	 *
	 * Checks if the current visitor is a logged in user.
	 * @source: wp-includes/pluggable.php
	 */
	public static function is_user_logged_in() {
		if ( function_exists( 'is_user_logged_in' ) )
			return is_user_logged_in();

		// possibly logged in but should be verified after is_user_logged_in() is available
		return self::parse_auth_cookie( 'logged_in' ) ? TRUE : FALSE;
	}

	/**
	 * WP alternative function for advanced-cache.php
	 *
	 * Add / Remove slash at the end of string.
	 * @source: wp-includes/formatting.php
	 */
	public static function unslashit( $string ) {
		return rtrim( $string, '/\\' );
	}

	public static function slashit( $string ) {
		return self::unslashit( $string ) . '/';
	}

}