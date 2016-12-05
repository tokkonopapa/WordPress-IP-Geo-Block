<?php
/**
 * Test of regular expression for detecting script tags
 *
 */

define( 'LOOP', 100000 );

$list = array(
	'<script>alert(document.cookie);</script><script>alert("XSS")</script>',
	'<script>alert(document.cookie);<\/script><script>alert("XSS")<\/script>',
	'<script>alert(document.cookie);<\\\/script><script>alert("XSS")<\/script>',
);

$test = array(
	array( 'version' => 'A', 'function' => 'pattern_A' ),
	array( 'version' => 'B', 'function' => 'pattern_B' ),
	array( 'version' => 'C', 'function' => 'pattern_C' ),
);

$result = array();

foreach ( $test as $i ) {
	$start = microtime( TRUE );
	$result[$i['version']] = $i['function']( $list );
	$result[$i['version']]['time'] = microtime( TRUE ) - $start;
}

/**
 * Pattern A ... Shortest possible matching --> stripslashes() takes time.
 * Restul: 0.652669906616
 */
function pattern_A( $list ) {
	$result = array();
	foreach ( $list as $pat ) {
		for ( $i = 0; $i < LOOP; $i++ ) {
			preg_match( '!<script[^>]*?>(.*?)</script[^>]*?>!', stripslashes( $pat ), $ret );
		}
		$result[ $pat ] = $ret;
	}
	return $result;
}

/**
 * Pattern B ... Shortest possible matching
 * Restul: 0.56139588356
 */
function pattern_B( $list ) {
	$result = array();
	foreach ( $list as $pat ) {
		for ( $i = 0; $i < LOOP; $i++ ) {
			preg_match( '!<script[^>]*?>(.*?)<\\\\*?/script[^>]*?>!', $pat, $ret );
		}
		$result[ $pat ] = $ret;
	}
	return $result;
}

/**
 * Pattern C ... Longest possible matching --> Simple is the best!!
 * Restul: 0.548903942108
 */
function pattern_C( $list ) {
	$result = array();
	foreach ( $list as $pat ) {
		for ( $i = 0; $i < LOOP; $i++ ) {
			preg_match( '!<script[^>]*>(.*?)<\\\\*/script[^>]*>!', $pat, $ret );
		}
		$result[ $pat ] = $ret;
	}
	return $result;
}

function sanitize( $str ) {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8', true );
}

?>
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
				<th>INPUT PATTERN</th>

<?php
foreach ( $test as $i ) {
	echo "\t\t\t\t<th>", $i['version'], "</th>\n";
}
?>
			</tr>
		</thead>
		<tbody>
<?php
foreach ( $list as $pat ) {
	echo "\t\t\t<tr>\n";
	echo "\t\t\t\t<td><pre><code>", sanitize( $pat ), "</code></pre></td>\n";
	foreach ( $test as $i ) {
		$j = sanitize( print_r( $result[$i['version']][ $pat ], true ) );
		echo "\t\t\t\t<td><pre><code>", $j ? $j : '(null)', "</code></pre></td>\n";
	}
	echo "\t\t\t</tr>\n";
}

echo "\t\t\t<tr>\n";
echo "\t\t\t\t<th>Time</th>\n";
foreach ( $test as $i ) {
	echo "\t\t\t\t<td><pre><code>", $result[$i['version']]['time'], "</code></pre></td>\n";
}
echo "\t\t\t</tr>\n";
?>
		</tbody>
	</table>
</body>
</html>
