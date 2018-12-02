---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-geolite2-dir
file: [ip-geo-api/maxmind/class-maxmind-geolite2.php]
---

The absolute path to the directory where MaxMind GeoLite2 database is installed.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-geolite2-dir**" assigns the absolute path to 
the directory where the [GeoLite2 Free Downloadable Database][GeoLite2] is 
installed.

### Parameters ###

- $dir  
  (string) `/absolute/path/to/wp-content/ip-geo-api/maxmind/GeoLite2`

### Use case ###

If you want change it to your `/wp-content/uploads/` directory, put the 
following code snippet into the `functions.php` in your theme.

{% highlight ruby startinline %}
function my_geolite2_dir( $dir ) {
    $upload = wp_upload_dir();
    return $upload['basedir'];
}
add_filter( 'ip-geo-block-geolite2-dir', 'my_geolite2_dir' );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.17

### See also ###

- [ip-geo-block-api-dir][CodexApiDir]
- [ip-geo-block-geolite2-zip-ip][CodexMaxZip]
- [ip-geo-block-geolite2-path][CodexMaxPath]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[GeoLite2]:     https://dev.maxmind.com/geoip/geoip2/geolite2/ "GeoLite2 Free Downloadable Databases &laquo; MaxMind Developer Site"
[CodexApiDir]:  {{ '/codex/ip-geo-block-api-dir.html'          | prepend: site.baseurl }} 'ip-geo-block-api-dir | IP Geo Block'
[CodexMaxZip]:  {{ '/codex/ip-geo-block-geolite2-zip-ipv.html' | prepend: site.baseurl }} 'ip-geo-block-geolite2-zip-ipv | IP Geo Block'
[CodexMaxDir]:  {{ '/codex/ip-geo-block-geolite2-dir.html'     | prepend: site.baseurl }} 'ip-geo-block-geolite2-dir | IP Geo Block'
[CodexMaxPath]: {{ '/codex/ip-geo-block-geolite2-path.html'    | prepend: site.baseurl }} 'ip-geo-block-geolite2-path | IP Geo Block'
