---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-auth-key
file: [class-ip-geo-block.php]
---

Specify the name of authentication key for nonce.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-auth-key**" assigns the name of authentication 
key for nonce.

### Parameters ###

- $key  
  (string) `ip-geo-block-auth-nonce`

### Use case ###

If you want change it to shorter name, put the following code snippet into the
`functions.php` in your theme.

{% highlight ruby startinline %}
/**
 * Replace authentication key
 *
 * You can safely use `0-9A-Za-z`, `_` and `-` as $key based on RFC2396 and RFC3986.
 * Only `_` might be conflict with others such as WP Fastest Cache.
 *
 * @param string $key
 * @return string $key
 */
function my_auth_key( $key ) {
    return '-';
}
IP_Geo_Block::add_filter( 'ip-geo-block-auth-key', 'my_auth_key' );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###
3.0.16
