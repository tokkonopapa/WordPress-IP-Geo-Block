---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-dns
file: [class-ip-geo-lkup.php]
---

Specify the domain name server for reverse DNS lookup.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-dns**" can replace the reverse DNS lookup 
service on your server if it is slow.

### Parameters ###

- $resolvers  
  (array) An array of DNS lookup resolvers that can be primary and secondary
  name servers.

### Use case ###

The following code snippet in your theme's `functions.php` can use [APNIC 
public DNS resolver][APNIC-DNS] or [Google public DNS resolver][Google-DNS].

{% highlight ruby startinline %}
function my_gethostbyaddr( $resolvers ) {
    return array( '1.1.1.1', '1.0.0.1' ); // APNIC  public DNS (faster)
//  return array( '8.8.8.8', '8.8.4.4' ); // Google public DNS (slower)
}
add_filter( 'ip-geo-block-dns', 'my_gethostbyaddr' );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.14

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[APNIC-DNS]:    https://blog.cloudflare.com/dns-resolver-1-1-1-1/ "Introducing DNS Resolver, 1.1.1.1 (not a joke)"
[Google-DNS]:   https://developers.google.com/speed/public-dns/ "Public DNS  &nbsp;|&nbsp; Google Developers"
