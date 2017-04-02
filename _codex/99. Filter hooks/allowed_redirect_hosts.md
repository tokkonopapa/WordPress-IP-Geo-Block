---
layout: page
category: codex
section: Filter Hooks
title: allowed_redirect_hosts
file: [class-ip-geo-block.php]
---

Filters the whitelist of hosts to redirect to.

<!--more-->

### Description ###

The filter hook "[**allowed_redirect_hosts**][AllowedHost]" is to prevent 
redirection to external sites. It's a countermeasure especially against the 
infection of option tables in MySQL database by SQL injection.

### Parameters ###

- $hosts  
  (array) An array of allowed hosts.

### Use case ###

{% highlight ruby startinline %}
function my_allowed_redirect_hosts( $hosts ){
    // wrong: $hosts[] = 'http://codex.example.com';
    $hosts[] = 'blog.example.com';
    $hosts[] = 'codex.example.com';

    return $hosts;
}
add_filter( 'allowed_redirect_hosts', 'my_allowed_redirect_hosts', 10 );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block &laquo; WordPress Plugins"
[AllowedHost]:  https://codex.wordpress.org/Plugin_API/Filter_Reference/allowed_redirect_hosts "Plugin API/Filter Reference/allowed redirect hosts &laquo; WordPress Codex"
