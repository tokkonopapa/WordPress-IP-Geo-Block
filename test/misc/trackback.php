<?php
/**
 * Trackback Tester for IP Geo Block
 *
 */

$home = "http://localhost/";

// Get feed
$feed = file_get_contents( rtrim( $home, '/' ) . '/feed/' );
$xml  = new SimpleXMLElement( $feed );

// Get the latest post
if ( is_object( $xml->channel->item[0]->link ) )
	$home = rtrim( $xml->channel->item[0]->link, '/' ) . '/trackback/';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Trackback Test</title>
<style>
</style>
</head>
<body>

<!-- form -->
<form action="<?php echo $home; ?>" method="POST">
	<table>
		<tr>
			<td>trackback url</td>
			<td><code><?php echo $home; ?></code></td>
		</tr>
		<tr>
			<td>title</td>
			<td><input type="text" name="title" value="Trackback test"></td>
		</tr>
		<tr>
			<td>excerpt</td>
			<td><textarea name="excerpt" rows="4" cols="40">This is a trackback.</textarea></td>
		</tr>
		<tr>
			<td>url</td>
			<td><input type="text" name="url" value="http://example.com/" /></td>
		</tr>
		<tr>
			<td>blog_name</td>
			<td><input type="text" name="blog_name" value="Example" /></td>
		</tr>
	</table>
	<p><input type="submit" value="Post trackback" /></p>
</form>
<!-- end of form -->

</body>
</html>