---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-headers
file: [class-ip-geo-block.php]
---

HTTP headers which this plugin sends when getting the remote content.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-headers**" assigns the array of HTTP headers 
which is passed to [`wp_remote_get()`][WpRemoteGet].

### Default value ###

array( `'timeout' => 5, 'user-agent' => 'WordPress/4.4, ip-geo-block 2.2.2'` )

### Use case ###

The following code snippet in your theme's `functions.php` can set timeout to 
3 seconds.

{% highlight ruby startinline %}
function my_http_headers( $args ) {
    $args['timeout'] = 3;
    return $args;
}
add_filter( 'ip-geo-block-headers', 'my_http_headers' );
{% endhighlight %}

<div class="alert alert-info">
	<strong>NOTE:</strong>
	When you select <code>"mu-plugins" (ip-geo-block-mu.php)</code> as 
	<a href='/codex/validation-timing.html' title='Validation timing | IP Geo Block'><strong>Validation timing</strong></a>,
	you should put your code snippet into <code>drop-in.php</code> in your 
	geolocation API directory instead of <code>functions.php</code>.
</div>

### Since ###

1.1.1

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[WpRemoteGet]:  https://codex.wordpress.org/Function_Reference/wp_remote_get "Function Reference/wp remote get « WordPress Codex"
