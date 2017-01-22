---
layout: post
title: "CloudFlare &amp; CloudFront API class library"
date: 2017-01-22 01:00:00
categories: article
published: true
script: []
inline:
---

[IP Geo Block][IP-Geo-Block] has been developed separately from the API class 
library [IP Geo API][IP-Geo-API] which supports local database provided by 
[Maxmind][Maxmind] and [IP2Location][IP2Location].

But if you use a CDN service provided by [CloudFlare][CloudFlare] or 
[CloudFront][CloudFront], you also have a chance to use their reverse proxy 
services. In this case, you can retrieve a visitor's country code from their 
services.

In this article, I'll show you how to make use of their services.

<!--more-->

### CloudFlare ###

CloudFlare provides the verious client information via [HTTP request headers]
[CF-Headers]. An IP address can be retrieved from `CF-Connecting-IP` and the 
country code from `CF-IPCountry`.

So here is an example of API class library:

<script src="https://gist.github.com/tokkonopapa/d467976c4628e0be1fda6f57cf721c21.js"></script>

The key point here is that the above PHP script should be named as 
`class-zcloudfront.php` and typically placed under the directory named 
`/wp-content/ip-geo-api/zcloudfront/`.

You might wonder why `z`?

It's just a matter of convenience of this plugin to give it a highest priority!

### CloudFront ###

CloudFront also provides the client information via [HTTP request headers]
[AM-Headers]. So the code is almost same as CloudFlare. You can find it at 
[tokkonopapa/class-zcloudfront.php][CF-ClassLib].

### Pros and Cons ###

The advantage of using these libraries is definitely the response time. It's 
almost zero. This is important especially for front-end.

![Response time of each API]({{ '/img/2017-01/ResponseTime.png' | prepend: site.baseurl }}
 "Response time of each API"
)

On the other hand, the disadvantage is disabling the search for any IP address 
on "**Search**" tab. In this case, you should just select other API 
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f60c.png)
</span>.

![Search tab]({{ '/img/2017-01/SearchTab.png' | prepend: site.baseurl }}
 "Search tab"
)

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[IP-Geo-API]:   https://github.com/tokkonopapa/WordPress-IP-Geo-API "tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  http://www.ip2location.com/ "IP Address to Identify Geolocation Information"
[CloudFlare]:   https://www.cloudflare.com/ "Cloudflare - The Web Performance & Security Company | Cloudflare"
[CloudFront]:   https://aws.amazon.com/cloudfront/ "Amazon CloudFront – Content Delivery Network (CDN)"
[CF-Headers]:   https://support.cloudflare.com/hc/en-us/articles/200170986-How-does-CloudFlare-handle-HTTP-Request-headers- "How does CloudFlare handle HTTP Request headers? &ndash; Cloudflare Support"
[AM-Headers]:   http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/RequestAndResponseBehaviorCustomOrigin.html "Request and Response Behavior for Custom Origins - Amazon CloudFront"
[AM-ClassLib]:  https://gist.github.com/tokkonopapa/15c2175870ad646f6989efbe59a1e211 "IP Geo Block api class library for CloudFront"
[CF-ClassLib]:  https://gist.github.com/tokkonopapa/d467976c4628e0be1fda6f57cf721c21 "IP Geo Block api class library for CloudFlare"
