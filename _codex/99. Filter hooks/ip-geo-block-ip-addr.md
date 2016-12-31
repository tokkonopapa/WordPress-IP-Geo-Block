---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-ip-addr
file: [class-ip-geo-block.php]
---

The IP address of the server for the current requester.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-ip-addr**" assigns the IP address from which 
the current request comes. This plugin validate it by means of the country 
code and black / white list of extra IPs.

### Default value ###

`$_SERVER['REMOTE_ADDR']`

### Use case ###

The following code snippet in your theme's `functions.php` can replace the IP 
address according to your browser's user agent string. It's useful to test 
this plugin's functionality using [browser's addon][UA-SWITCHER] which can be 
change the user agent string.

{% highlight ruby startinline %}
function my_replace_ip( $ip ) {
    if ( FALSE !== stripos( $_SERVER['HTTP_USER_AGENT'], 'your unique string' ) )
        return '98.139.183.24'; // yahoo.com
    else
        return $ip;
}
add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );
{% endhighlight %}

<div class="alert alert-info">
  <strong>NOTE:</strong> It's also useful using 
  <a href="https://www.google.com/search?q=switch+browser+proxy+vpn+unblock+addon"
  title="switch browser proxy vpn unblock addon - Google search">VPN switcher addon</a>
  to fake your IP address.
</div>

<div class="alert alert-info">
	<strong>NOTE:</strong>
	When you select <code>"mu-plugins" (ip-geo-block-mu.php)</code> as 
	<a href='/codex/validation-timing.html' title='Validation timing | IP Geo Block'><strong>Validation timing</strong></a>,
	you should put your code snippet into <code>drop-in.php</code> in your 
	geolocation API directory instead of <code>functions.php</code>.
</div>

### Since ###

1.2.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[UA-SWITCHER]:  https://www.google.com/search?q=switch+browser+user+agent+string+addon "switch browser user agent string addon - Google search"
