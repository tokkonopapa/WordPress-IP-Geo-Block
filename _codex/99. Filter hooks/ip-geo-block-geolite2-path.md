---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-geolite2-path
file: [ip-geo-api/maxmind/class-maxmind-geolite2.php]
---

The absolute path to MaxMind GeoLite2 database file for IPv4 and IPv6.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-geolite2-path**" assigns the absolute path 
to the installed [GeoLite2 Free Downloadable Database][GeoLite2] file.

### Parameters ###

- $path  
  (string) `/absolute/path/to/wp-content/ip-geo-api/maxmind/GeoLite2/GeoLite2-Country.mmdb`

### Use case ###

If you'd like to use GeoLite2 City database, put the following code snippet 
into the `functions.php` in your theme.

{% highlight ruby startinline %}
function my_geolite2_zip_ip( $url ) {
    return 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz';
}
function my_geolite2_path( $file ) {
    return '/absolute/path/to/wp-content/ip-geo-api/maxmind/GeoLite2/GeoLite2-City.mmdb';
}
add_filter( 'ip-geo-block-geolite2-zip-ip', 'my_geolite2_zip_ip' );
add_filter( 'ip-geo-block-geolite2-path',   'my_geolite2_path'   );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.17

### See also ###

- [ip-geo-block-api-dir][CodexApiDir]
- [ip-geo-block-geolite2-zip-ip][CodexMaxZip]
- [ip-geo-block-geolite2-dir][CodexMaxDir]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[GeoLite2]:     https://dev.maxmind.com/geoip/geoip2/geolite2/ "GeoLite2 Free Downloadable Databases &laquo; MaxMind Developer Site"
[CodexApiDir]:  {{ '/codex/ip-geo-block-api-dir.html'          | prepend: site.baseurl }} 'ip-geo-block-api-dir | IP Geo Block'
[CodexMaxZip]:  {{ '/codex/ip-geo-block-geolite2-zip-ipv.html' | prepend: site.baseurl }} 'ip-geo-block-geolite2-zip-ipv | IP Geo Block'
[CodexMaxDir]:  {{ '/codex/ip-geo-block-geolite2-dir.html'     | prepend: site.baseurl }} 'ip-geo-block-geolite2-dir | IP Geo Block'
[CodexMaxPath]: {{ '/codex/ip-geo-block-geolite2-path.html'    | prepend: site.baseurl }} 'ip-geo-block-geolite2-path | IP Geo Block'
