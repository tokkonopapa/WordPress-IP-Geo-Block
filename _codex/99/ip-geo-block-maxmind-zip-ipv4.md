---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-maxmind-zip-ipv4
file: [class-maxmind.php]
---

The URI to MaxMind GeoLite Legacy database for IPv4.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-maxmind-zip-ipv4**" assigns the URI to 
[Free GeoLite Legacy database file][MaxMindGeoDB] for IPv4 which can be 
downloaded by GZIP format.

### Default value ###

{% highlight text %}
http://geolite.maxmind.com/download/geoip/database/GeoLiteCountry/GeoIP.dat.gz
{% endhighlight %}

### Use case ###

The following code snippet in your theme's `functions.php` can download the 
city database for IPv4.

{% highlight ruby startinline %}
function my_maxmind_ipv4( $url ) {
    return 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
}
add_filter( 'ip-geo-block-maxmind-zip-ipv4', 'my_maxmind_ipv4' );
{% endhighlight %}

### Since ###

1.2.0

### See also ###

- [ip-geo-block-api-dir][CodexApiDir]
- [ip-geo-block-maxmind-dir][CodexMaxDir]
- [ip-geo-block-maxmind-zip-ipv6][CodexMaxZip6]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[MaxMindGeoDB]: http://dev.maxmind.com/geoip/legacy/geolite/ "GeoLite Legacy Downloadable Databases « Maxmind Developer Site"
[CodexApiDir]:  {{ '/codex/ip-geo-block-api-dir.html'          | prepend: site.baseurl }} 'ip-geo-block-api-dir | IP Geo Block'
[CodexMaxDir]:  {{ '/codex/ip-geo-block-maxmind-dir.html'      | prepend: site.baseurl }} 'ip-geo-block-maxmind-dir | IP Geo Block'
[CodexMaxZip6]: {{ '/codex/ip-geo-block-maxmind-zip-ipv6.html' | prepend: site.baseurl }} 'ip-geo-block-maxmind-zip-ipv6 | IP Geo Block'
