<?php
/**
 * Test of regular expression for detecting bad signature
 *
 */
$sigs = "../,/wp-config.php,/passwd\ncurl,wget,eval,base64\nselect:.5,where:.5,union:.5\nload_file:.5,create:.6,password:.4";
$query = '?file=../wp-config.php&action=select&output=user_password&whereas=union1';

function multiexplode ( $delimiters, $string ) {
	return array_filter( explode( $delimiters[0], str_replace( $delimiters, $delimiters[0], $string ) ) );
}

$sigs = multiexplode( array( ",", "\n" ), $sigs );

/* OK */
foreach ( $sigs as $sig ) {
	$val = explode( ':', $sig, 2 );
	$sig = trim( $val[0] );
	$match[ $sig ] = 0;
/*
	if ( preg_match( '/\W/', $sig ) ) {
		if ( FALSE !== strpos( $query, $sig ) ) {
			$match[ $sig ] = 1;
		}
	}

	else {
		$sig = preg_quote( $sig, '!' );
		if ( preg_match( '!\b' . $sig . '\b!', $query ) ) {
			$match[ $sig ] = 1;
		}
	}
*/
	if ( FALSE !== strpos( $query, $sig ) ) {
		if ( preg_match( '!\W!', $sig ) ||
		     preg_match( '!\b' . preg_quote( $sig, '!' ) . '\b!', $query ) ) {
			$match[ $sig ] = 1;
		}
	}
}

/* NG */
$sig = preg_replace( '!:\.\d!', '', '(?:' . implode( '|', $sigs ) . ')' );
$match['sig'] = $sig;
$match['all'] = preg_match( '!' . preg_quote( $sig, '!' ) . '\b!', $query )
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>test of extracting bad signature</title>
</head>
<body>
<pre>
<?php print_r( $match ); ?>
</pre>
</body>
</html>
