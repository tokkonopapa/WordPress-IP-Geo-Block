---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-xxxxx-status
file: [class-ip-geo-block.php]
---

The HTTP status code when the blocking occurs.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-xxxxx-status**" where `xxxxx` is one of 
`comment`, `xmlrpc`, `login` and `admin` assigns the HTTP status code when 
the blocking occurs.

### Default value ###

It depends on your setting at "**Response code**" on 
"**Validation rule settings**".

### Use case ###

The following code in your `functions.php` can hide your login page.

{% highlight ruby startinline %}
function my_login_status ( $code ) {
    return 404;
}
add_filter( 'ip-geo-block-login-status', 'my_login_status' );
{% endhighlight %}

The status code `404` leads to the "404 page" in your theme. For example, the 
following picture shows the page of [Twenty Twelve][TwentyTwelve].

![404 error page]({{ '/img/2016-01/Theme404.png' | prepend: site.baseurl }}
 "404 error page"
)

{% include alert-drop-in.html %}

### Since ###

2.1.1

### See also ###

- [About the response code][BlogResponse]
- [ip-geo-block-xxxxx-reason][CodexReason]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[GetStatus]:    https://developer.wordpress.org/reference/functions/get_status_header_desc/ "WordPress › get_status_header_desc() | Function | WordPress Developer Resources"
[BlogResponse]: {{ 'about-the-response-code.html' | prepend: site.baseurl }} 'About the response code | IP Geo Block'
[CodexReason]:  {{ '/codex/ip-geo-block-xxxxx-reason.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-reason | IP Geo Block'
[TwentyTwelve]: https://wordpress.org/themes/twentytwelve/ "WordPress › Twenty Twelve « Free WordPress Themes"
