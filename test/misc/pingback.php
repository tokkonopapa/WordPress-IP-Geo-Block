<?php
/**
 * Pinback Tester for IP Geo Block
 *
 * @example Put "HTTP_X_CLIENT_ADDR" in "$_SERVER keys to retrieve extra IP addresses".
 */

$home = "http://localhost/";
$addr = "98.137.149.56"; // yahoo.com
$name = "Pingback Tester";

// Get feed
$feed = file_get_contents( rtrim( $home, '/' ) . '/feed/' );
$xml  = new SimpleXMLElement( $feed );

// Get the latest post
if ( is_object( $xml->channel->item[0]->link ) )
	$page = $xml->channel->item[0]->link;
else
	$page = $home;

$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<methodCall>
    <methodName>
        pingback.ping
    </methodName>
    <params>
        <param>
            <value>
                <string>
                    http://example.com/
                </string>
            </value>
        </param>
        <param>
            <value>
                <string>
                    $page
                </string>
            </value>
        </param>
    </params>
</methodCall>
XML;

$options = array(
	CURLOPT_URL => trim( $home, "/" ) . "/xmlrpc.php",
	CURLOPT_POST => TRUE,
	CURLOPT_RETURNTRANSFER => TRUE,
	CURLOPT_HTTPHEADER => array(
		"Content-Type: text/xml",
		"X-Client-Addr: $addr",
		"user-agent: $name",
	),
	CURLOPT_POSTFIELDS => $xml,
);
 
$ch = curl_init();
curl_setopt_array( $ch, $options );
$result = curl_exec( $ch );
$output = curl_getinfo( $ch );

if ( FALSE === $result )
	trigger_error( curl_error( $ch ) );

curl_close( $ch );
 
print_r( $output['http_code'] );
