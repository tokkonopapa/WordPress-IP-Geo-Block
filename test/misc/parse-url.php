<?php
/**
 * Test of parse_url() and substitutions
 *
 * @result
 *    The `parse_url()` can't handle the uri correctly which doen't have a scheme.
 *    Neither the regular expression in `https://tools.ietf.org/html/rfc3986#appendix-B`.
 */

define( 'HOME', '' );
define( 'LOOP', 10000 );

$list = array(
	'http://example.com',
	'http://example.com/',
	'http://example.com/path',
	'http://example.com/path/',
	'http://example.com/?value=1',
	'http://example.com/path?value=1',
	'http://example.com/path?value:1',
	'http://example.com/path?value:1#fragment',
	'http://example.com/path//?value=file://',
	'http://example.co.jp/path/../cmd.php?value=file://',
	'//example.co.jp/path//././cmd.php?value=file://',
	'?',
	'/',
	'/path',
	'/path/',
	'/?value=1',
	'/path?value=1',
	'/path?value:1',
	'/path?value:1#fragment',
	'//path?value:1#fragment',
	'/path/../?value:1#fragment',
	'/path/..//.///?value:1#fragment',
	'....path/..//./cmd.php?value:1#fragment',
	'..../path/..//./cmd.php?value:1#fragment',
	'/..../path/..//./cmd.php?value:1#fragment',
);

$test = array(
	array( 'version' => '2.2.3.1', 'function' => 'parse_url_1' ),
//	array( 'version' => '2.2.4',   'function' => 'parse_url_2' ), // strpos(): Empty needle
	array( 'version' => '3.0.0',   'function' => 'parse_url_3' ),
);

$result = array();
foreach ( $test as $i ) {
	$start = microtime( TRUE );
	$result[$i['version']] = $i['function']( $list );
	$result[$i['version']]['time'] = microtime( TRUE ) - $start;
}

echo <<<EOT
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>test of parse url</title>
</head>
<body>
	<table>
		<thead>
			<tr>
				<th>REQUEST_URI</th>

EOT;
foreach ( $test as $i ) {
	echo "\t\t\t\t<th>", $i['version'], "</th>\n";
}
echo <<<EOT
			</tr>
		</thead>
		<tbody>

EOT;

foreach ( $list as $uri ) {
	echo "\t\t\t<tr>\n";
	echo "\t\t\t\t<td><code>", $uri, "</code></td>\n";
	foreach ( $test as $i ) {
		$j = $result[$i['version']][ $uri ];
		echo "\t\t\t\t<td><code>", $j ? $j : '(null)', "</code></td>\n";
	}
	echo "\t\t\t</tr>\n";
}
echo "\t\t\t<tr>\n", "\t\t\t\t<th>Time</th>\n";
foreach ( $test as $i ) {
	echo "\t\t\t\t<td><code>", $result[$i['version']]['time'], "</code></td>\n";
}
echo "\t\t\t</tr>\n";

/**
 * 2.2.3.1
 *
 */
function parse_url_1( $list ) {
	$result = array();
	foreach ( $list as $uri ) {
if (1) {
		for ( $i = 0; $i < LOOP; $i++ ) {
			// requested path (original)
			$ret = preg_replace( '!(//+|/\.+/)!', '/', $uri );
			$ret = substr( parse_url( $uri, PHP_URL_PATH ), strlen( HOME ) );
		}
} else {
		for ( $i = 0; $i < LOOP; $i++ ) {
			// requested path (improved)
			$ret = parse_url( $uri, PHP_URL_PATH );
			$ret = substr( preg_replace( '!(//+|/\.+/)!', '/', $ret ), strlen( HOME ) );
		}
}
		$result[$uri] = $ret;
	}
	return $result;
}

/**
 * 2.2.4
 *
 */
function parse_url_2( $list ) {
	$result = array();
	foreach ( $list as $uri ) {
		try {
			for ( $i = 0; $i < LOOP; $i++ ) {
				// normalize requested uri (RFC 2616 has been obsoleted by RFC 7230-7237)
				// `parse_url()` is not suitable becase of https://bugs.php.net/bug.php?id=55511
				// REQUEST_URI starts with path or scheme (https://tools.ietf.org/html/rfc2616#section-5.1.2)
				$ret = preg_replace( '!(?://+|/\.+/)!', '/', $uri );
				$ret = substr( $ret, strpos( $ret, HOME ) + strlen( HOME ) );
			}
			$result[$uri] = $ret;
		} catch ( Exception $e ) {
			$result[$uri] = $e->getMessage();
		}
	}
	return $result;
}

/**
 * 3.0.0
 *
 */
function parse_url_3( $list ) {
	$result = array();
	foreach ( $list as $uri ) {
if (1) {
		for ( $i = 0; $i < LOOP; $i++ ) {
			if ( FALSE === ( $ret = parse_url( $uri, PHP_URL_PATH ) ) )
				$ret = $_SERVER['SCRIPT_NAME'];
			$ret = substr( preg_replace(
				array( '!\.+/!', '!//+!' ), array( '/', '/' ), $ret
			), strlen( HOME ) );
		}
} else {
		for ( $i = 0; $i < LOOP; $i++ ) {
			$ret = preg_replace(
//				array( '!^(?:[^:/?#]+:)?(?://.*\.[a-zA-Z]{2,3})?([^?#]*).*$!' ),
//				array( '$1' ),
				array( '!^(?:[^:/?#]+:)?(?://.*\.[\w]*)?([^?#]*).*$!', '!\.+/!', '!//+!' ),
				array( '$1', '/', '/' ),
				$uri
			);
		}
}
		$result[$uri] = $ret;
	}
	return $result;
}
?>
		</tbody>
	</table>
</body>
</html>
