---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-ip2location-path
file: [class-ip2location.php]
---

The absolute path to IP2location database file for IPv4.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-ip2location-path**" assigns the absolute path 
to [IP2Location][IP2Location] database file for IPv4.

### Default value ###

`/absolute/path/to/wp-content/ip-geo-api/ip2location/IP2LOCATION-LITE-DB1.BIN`

### Use case ###

If you'd like to share the database file with the WordPress plugin 
[IP2Location Tags][IP2Tag] (or [other free plugins][IP2Free]), the following 
code snippet in your theme's `functions.php` may help you.

{% highlight ruby startinline %}
function my_ip2location_path( $path ) {
    return WP_PLUGIN_DIR . '/ip2location-tags/IP2LOCATION-LITE-DB1.BIN';
}
add_filter( 'ip-geo-block-ip2location-path', 'my_ip2location_path' );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

1.2.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[IP2Location]:  http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[IP2Tag]:       https://wordpress.org/plugins/ip2location-tags/ "WordPress › IP2Location Tags « WordPress Plugins"
[IP2Free]:      https://www.ip2location.com/free/plugins "Free Plugins | IP2Location.com"
