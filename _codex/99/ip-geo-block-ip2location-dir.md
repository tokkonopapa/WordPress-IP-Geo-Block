---
layout: page
category: codex
section: customizing
title: ip-geo-block-ip2location-dir
file: [class-ip2location.php]
---

The absolute path to the directory where IP2location database is installed.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-ip2location-dir**" assigns the absolute path 
to the directory where the [IP2Location][IP2Location] geolocation database is 
installed.

### Default value ###

`/absolute/path/to/wp-content/ip-geo-api/ip2location`

### Use case ###

If you want change it to your `/wp-content/uploads/` directory, put the 
following code snippet into the `functions.php` in your theme.

{% highlight php startinline %}
function my_ip2location_dir( $dir ) {
    $upload = wp_upload_dir();
    return $upload['basedir'];
}
add_filter( 'ip-geo-block-ip2location-dir', 'my_ip2location_dir' );
{% endhighlight %}

### Since ###

2.2.1

### See also ###

- [ip-geo-block-api-dir][CodexApiDir]
- [ip-geo-block-ip2location-path][CodexIP2Path]
- [ip-geo-block-ip2location-zip-ipv4][CodexIP2Zip4]
- [ip-geo-block-ip2location-zip-ipv6][CodexIP2Zip6]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[IP2Location]:  http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[CodexApiDir]:  {{ '/codex/ip-geo-block-api-dir.html'              | prepend: site.baseurl }} 'ip-geo-block-api-dir | IP Geo Block'
[CodexIP2Path]: {{ '/codex/ip-geo-block-ip2location-path.html'     | prepend: site.baseurl }} 'ip-geo-block-ip2location-path | IP Geo Block'
[CodexIP2Zip4]: {{ '/codex/ip-geo-block-ip2location-zip-ipv4.html' | prepend: site.baseurl }} 'ip-geo-block-ip2location-zip-ipv4 | IP Geo Block'
[CodexIP2Zip6]: {{ '/codex/ip-geo-block-ip2location-zip-ipv6.html' | prepend: site.baseurl }} 'ip-geo-block-ip2location-zip-ipv6 | IP Geo Block'
