---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-xxxxx-reason
file: [class-ip-geo-block.php]
---

The message of the reason when the blocking occurs.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-xxxxx-reason**" where `xxxxx` is one of 
`comment`, `xmlrpc`, `login` and `admin` assigns the human readable message 
according to the "**Response code**" on "**Validation rule settings**" when 
the blocking occurs.

### Default value ###

[`get_status_header_desc()`][GetStatus]

### Use case ###

The following picture shows a human readble message when a blocking occurs.

![403 error page]({{ '/img/2016-01/Simple403.png' | prepend: site.baseurl }}
 "403 error page"
)

You can change the message "Forbidden" as follows:

{% highlight ruby startinline %}
function my_comment_reason ( $msg  ) {
    return "Sorry, this service is unavailable.";
}
add_filter( 'ip-geo-block-comment-reason',  'my_comment_reason'  );
{% endhighlight %}

### Since ###

2.1.1

### See also ###

- [About the response code][BlogResponse]
- [ip-geo-block-xxxxx-status][CodexStatus]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[GetStatus]:    https://developer.wordpress.org/reference/functions/get_status_header_desc/ "WordPress › get_status_header_desc() | Function | WordPress Developer Resources"
[BlogResponse]: {{ 'about-the-response-code.html' | prepend: site.baseurl }} 'About the response code | IP Geo Block'
[CodexStatus]:  {{ '/codex/ip-geo-block-xxxxx-status.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-status | IP Geo Block'
