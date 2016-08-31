---
layout: page
category: codex
section: Features and performance
title: Customizing the response
excerpt: Customizing the response
---

When this plugin blocks a request, it behaves differently depending on the 
setting of the "**Response code**". This document explains about the details 
and also how to customize the error page for human.

<!--more-->

### Response code and behavior ###

You can select a [HTTP status code][StatusCode] which is returned to the 
requester when blocking occurs.

![Response code]({{ '/img/2016-01/ResponseCode.png' | prepend: site.baseurl }}
 "Response code"
)

This plugin behaves differently according to the code you selected as follows :

- Successful 2xx  
  Returns [refresh http header][Refresh] which encourages a browser to jump to 
  your home derived from [`home_url()`][HomeURL].

- Redirection 3xx  
  Returns the URL to the "[black hole server][BlackHole]". If the attackers 
  actually redirect to that URL, they never get the result.

- Client Error 4xx / Server Error 5xx  
  Returns a simple html given by [`get_status_header_desc()`][GetStatus] and 
  [`wp_die()`][WP_DIE].
  ![403 error page]({{ '/img/2016-01/Simple403.png' | prepend: site.baseurl }}
   "403 error page"
  )

### Customizing code and reason ###

Through the filter hooks `ip-geo-block-xxxxxx-(status|reason)` where `xxxxxx` 
is one of `comment`, `xmlrpc`, `login` and `admin`, you can customize the 
status code and reason. For example, the following code in your `functions.php`
can hide your login page.

{% highlight ruby startinline %}
function my_login_status ( $code ) {
    return 404;
}
add_filter( 'ip-geo-block-login-status', 'my_login_status' );
{% endhighlight %}

### Human friendly error page ###

You can find `404.php` in your theme directory. Please copy it and give it a 
name according to your setting at "**Response code**". For example, the 
following picture is a sample of `403.php` in [Twenty Twelve][TwentyTwelve] 
with [BuddyPress][BuddyPress].

![403 error page]({{ '/img/2015-06/403-page.png' | prepend: site.baseurl }}
 "403 error page"
)

### See also ###

- [ip-geo-block-xxxxx-reason][CodexReason]
- [ip-geo-block-xxxxx-status][CodexStatus]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[StatusCode]:   http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1"
[Refresh]:      http://stackoverflow.com/questions/283752/refresh-http-header "'Refresh' HTTP header - Stack Overflow"
[HomeURL]:      https://codex.wordpress.org/Function_Reference/home_url "Function Reference/home url « WordPress Codex"
[BlackHole]:    http://blackhole.webpagetest.org/ "blackhole.webpagetest.org"
[WP_DIE]:       https://codex.wordpress.org/Function_Reference/wp_die "Function Reference/wp die « WordPress Codex"
[GetStatus]:    https://developer.wordpress.org/reference/functions/get_status_header_desc/ "WordPress › get_status_header_desc() | Function | WordPress Developer Resources"
[TwentyTwelve]: https://wordpress.org/themes/twentytwelve/ "WordPress › Twenty Twelve « Free WordPress Themes"
[BuddyPress]:   https://buddypress.org/ "BuddyPress.org"
[CodexReason]:  {{ '/codex/ip-geo-block-xxxxx-reason.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-reason | IP Geo Block'
[CodexStatus]:  {{ '/codex/ip-geo-block-xxxxx-status.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-status | IP Geo Block'
