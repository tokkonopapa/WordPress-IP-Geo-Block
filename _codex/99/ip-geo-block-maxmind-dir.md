---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-maxmind-dir
file: [class-maxmind.php]
---

The absolute path to the directory where MaxMind database is installed.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-maxmind-dir**" assigns the absolute path to 
the directory where the [MaxMind][MaxMind] geolocation database is installed.

### Default value ###

`/absolute/path/to/wp-content/ip-geo-api/maxmind`

### Use case ###

If you want change it to your `/wp-content/uploads/` directory, put the 
following code snippet into the `functions.php` in your theme.

{% highlight php startinline %}
function my_maxmind_dir( $dir ) {
    $upload = wp_upload_dir();
    return $upload['basedir'];
}
add_filter( 'ip-geo-block-maxmind-dir', 'my_maxmind_dir' );
{% endhighlight %}

### Since ###

1.2.0

### See also ###

- [ip-geo-block-api-dir][CodexApiDir]
- [ip-geo-block-maxmind-zip-ipv4][CodexMaxZip4]
- [ip-geo-block-maxmind-zip-ipv6][CodexMaxZip6]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[MaxMind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[CodexApiDir]:  {{ '/codex/ip-geo-block-api-dir.html'          | prepend: site.baseurl }} 'ip-geo-block-api-dir | IP Geo Block'
[CodexMaxZip4]: {{ '/codex/ip-geo-block-maxmind-zip-ipv4.html' | prepend: site.baseurl }} 'ip-geo-block-maxmind-zip-ipv4 | IP Geo Block'
[CodexMaxZip6]: {{ '/codex/ip-geo-block-maxmind-zip-ipv6.html' | prepend: site.baseurl }} 'ip-geo-block-maxmind-zip-ipv6 | IP Geo Block'
