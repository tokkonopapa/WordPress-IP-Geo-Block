---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-xxxxx
file: [class-ip-geo-block.php]
---

The associative array for validation.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-xxxxx**" where `xxxxx` is one of `comment`, 
`xmlrpc`, `login` and `admin` can affect the validation of this plugin.

### Default value ###

Here are the keys and values in this associative array.

| Key      | Value                |
|:---------|:---------------------|
| ip       | IP address           |
| auth     | authenticated or not |
| code     | country code         |
| time     | processing time      |
| provider | geolocation API      |
| result   | validation result    |

### Use case ###

{% highlight ruby startinline %}
function my_whitelist( $validate ) {
    $whitelist = array(
        'JP', // should be upper case
    );

    $validate['result'] = 'blocked';

    if ( in_array( $validate['code'], $whitelist ) ) {
        $validate['result'] = 'passed';
    }

    return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
add_filter( 'ip-geo-block-admin', 'my_whitelist' );
{% endhighlight %}

### Since ###

1.2.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
