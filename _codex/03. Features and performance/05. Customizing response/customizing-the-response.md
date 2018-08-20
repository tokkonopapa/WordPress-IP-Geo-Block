---
layout: page
category: codex
section: Features and performance
title: Response code and message
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

This plugin behaves differently according to the code you selected as follows:

- Successful 2xx  
  Returns [refresh Meta tag][Refresh] which encourages a browser to jump to 
  the specific URL. Note that [W3C does not recommend this tag][W3C-Refresh] 
  because of [back button][RefreshIssue].

- Redirection 3xx  
  Returns the [location header with the specific URL][URL-redirect] for URL 
  forwarding.

- Client Error 4xx / Server Error 5xx  
  Returns a simple html given by [`wp_die()`][WP_DIE].  
  
  ![403 error page]({{ '/img/2016-01/Simple403.png' | prepend: site.baseurl }}  
   "403 error page"  
  )
  
  See also [Human friendly error page][FriendlyPage].

#### Redirect URL ####

You can specify the URL for response code 2xx and 3xx. Front-end URL on your 
site would not be blocked to prevent loop of redirection even when you enable 
"**Front-end target settings**". Empty URL is altered to your [home][HOME-URL].

The default value is pointed to the "[black hole server][BlackHole]" where 
if the attackers actually redirect to that URL, they never get the result.

#### Response message ####

You can specify the message for response code 4xx and 5xx. The default value 
is:

> Sorry, your request cannot be accepted.

Empty message is altered to what given by 
[`get_status_header_desc()`][GetStatus].

### Human friendly error page ###

A human friendly error page is available instead of a dreary page by `wp_die()`
if you select 4xx for client error and 5xx for server error as a response code.

You can find `404.php` in your theme directory. Please copy it and give it a 
name according to the setting of "**Response code**". The following picture is 
a sample of `403.php` in [Twenty Twelve][TwentyTwelve] with 
[BuddyPress][BuddyPress].

![403 error page]({{ '/img/2015-06/403-page.png' | prepend: site.baseurl }}
 "403 error page"
)

<div class="alert alert-warning">
	<strong>NOTICE:</strong>
	If you select <code>"mu-plugins" (ip-geo-block-mu.php)</code> as 
	"<strong>Vaidation timing</strong>" at 
	"<strong>Validation rule settings</strong>", you have some restrictions on 
	using custom filter hooks and human friendly error page. See also 
	<a href="/codex/validation-timing.html" title="Validation timing | IP Geo Block">this codex</a>.
</div>

### See also ###

- [ip-geo-block-xxxxx-reason][CodexReason]
- [ip-geo-block-xxxxx-status][CodexStatus]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[StatusCode]:   https://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1"
[Refresh]:      https://stackoverflow.com/questions/283752/refresh-http-header "'Refresh' HTTP header - Stack Overflow"
[W3C-Refresh]:  https://www.w3.org/TR/WCAG10-HTML-TECHS/#meta-element "HTML Techniques for Web Content Accessibility Guidelines 1.0"
[RefreshIssue]: https://www.w3.org/QA/Tips/reback "Use standard redirects - don't break the back button! - Quality Web Tips"
[HOME-URL]:     https://codex.wordpress.org/Function_Reference/home_url "Function Reference/home url &laquo; WordPress Codex"
[BlackHole]:    https://blackhole.webpagetest.org/ "blackhole.webpagetest.org"
[WP_DIE]:       https://codex.wordpress.org/Function_Reference/wp_die "Function Reference/wp die « WordPress Codex"
[GetStatus]:    https://developer.wordpress.org/reference/functions/get_status_header_desc/ "WordPress › get_status_header_desc() | Function | WordPress Developer Resources"
[TwentyTwelve]: https://wordpress.org/themes/twentytwelve/ "WordPress › Twenty Twelve « Free WordPress Themes"
[URL-redirect]: https://en.wikipedia.org/wiki/URL_redirection#HTTP_status_codes_3xx "URL redirection - Wikipedia"
[BuddyPress]:   https://buddypress.org/ "BuddyPress.org"
[FriendlyPage]: {{ '#human-friendly-error-page'            | prepend: site.baseurl }} 'Response code and message | IP Geo Block'
[CodexReason]:  {{ '/codex/ip-geo-block-xxxxx-reason.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-reason | IP Geo Block'
[CodexStatus]:  {{ '/codex/ip-geo-block-xxxxx-status.html' | prepend: site.baseurl }} 'ip-geo-block-xxxxx-status | IP Geo Block'
