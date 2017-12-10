---
layout: page
category: codex
section: blocking on front-end
title: UA string and Qualification
---

For [SEO][SEO-WIKI], you must be sure to grant permission against search engine 
bots or crawlers such as google, yahoo and being while shut out the bad bots.
This feature is possible to fulfill your wishes by giving a pair of 
"**UA string**" and "**Qualification**" separated by an applicable rule which 
can be "`:`" (pass) or "`#`" (block).

### Syntax and Synopsis ###

* <code><em>UA string</em> : [ ! ] <em>Qualification</em></code>
* <code><em>UA string</em> # [ ! ] <em>Qualification</em></code>

#### UA string ####

You can specify a part of user agent string (case sensitive). An asterisk "`*`"
matches all user agents.

#### Qualification ####

Currently, you can obtain 7 types of qualification listed bellow:

| Qualification       | Description                                           |
|:--------------------|:------------------------------------------------------|
| FEED                | True if the request is the feed url.                  |
| HOST                | True if the result of host name is available.         |
| HOST=_string_       | True if the host name includes _string_.              |
| REF=_string_        | True if the HTTP referer includes _string_.           |
| _Country code_      | True if the request comes from the specified country. |
| _IP address (CIDR)_ | True if the IP address is within the specific range.  |
| *                   | Always true                                           |

The host name `HOST` corresponding IP address will be retrieved via DNS reverse
lookup which is disabled by default.

#### Negative operation ####

A negative operation "`!`" can be placed just before a qualification. It inverts
the meaning of qualification.

### DNS reverse lookup ###

DNS reverse lookup will attempt to fetch a dns PTR record for an IP address.
Its cost against the server's resources is relatively high. For example, the 
following picture shows the result of 1st and 2nd time which is available at 
"**Installation information**" in "**Plugin settings**" section. It means that 
the fetched result would be cached in your server.

![DNS reverse lookup]({{ '/img/2017-05/DNSLookupCache.png' | prepend: site.baseurl }}
 "DNS reverse lookup"
)

If "**DNS reverse lookup**" is **disabled**, then `HOST` and `HOST=...` will 
always return **true**. For example, `Google:HOST` would be converted to 
`Google:*`. In this case, only user agent string would be checked. If the 
converted result is one of `*:*`, `*:!*`, `*#*` and `*#!*`, it would be 
eliminated.

### Examples ###

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

| Sample               | Description                                                 |
|:---------------------|:------------------------------------------------------------|
| *:2620:101:4000::/42 | Pass  if an IP address is within the range.                 |
| Google:HOST          | Pass  if UA includes "Google" and host name is available.   |
| Google#!HOST         | Block if UA includes "Google" and host name is unavailable. |
| Yandex#*             | Block if UA includes "Yandex".                              |
| *#HOST=amazonaws     | Block all UA if host name includes "amazonaws".             |

<div class="alert alert-info">
	<strong>Note:</strong> The rules in "<strong>UA string and Qualification</strong>"
	will be processed in order. For example, when <code>bot:HOST</code> appears
	before <code>xyz-bot#HOST</code>, then all the bots will be passed whose
	user agent string includes "bot" and its host name is available.
</div>

### References ###

- [Verifying Googlebot](https://support.google.com/webmasters/answer/80553?hl=en "Verifying Googlebot - Search Console Help")
- [Verifying Bingbot](https://www.bing.com/webmaster/help/how-to-verify-bingbot-3905dc26 "How to Verify Bingbot - Bing Webmaster Tools")
  - [Which Crawlers Does Bing Use?](https://www.bing.com/webmaster/help/which-crawlers-does-bing-use-8c184ec0 "Which Crawlers Does Bing Use? - Bing Webmaster Tools")
  - [How to Verify Bingbot](https://www.bing.com/webmaster/help/how-to-verify-bingbot-3905dc26 "How to Verify Bingbot - Bing Webmaster Tools")
- [Why is Slurp crawling my page?](https://help.yahoo.com/kb/SLN22600.html "Why is Slurp crawling my page? - Yahoo Help - SLN22600")
- [User-Agent Yandex](https://yandex.com/support/search/robots/user-agent.html "User-Agent Yandex - Search - Yandex.Support")
  - [How to check that a robot belongs to Yandex](https://yandex.com/support/webmaster/robot-workings/check-yandex-robots.xml "How to check that a robot belongs to Yandex — Webmaster — Yandex.Support")
- [FAQs of Baiduspider](http://help.baidu.com/question?prod_en=master&class=Baiduspider#title_2 "Baidu customer service center - Master platform")
- [The Facebook Crawler](https://developers.facebook.com/docs/sharing/webmasters/crawler "Facebook Crawler - Sharing - Documentation - Facebook for Developers")
- [URL Crawling &amp; Caching - Twitter Developers](https://dev.twitter.com/cards/getting-started#crawling "Getting Started Guide - Twitter Developers")

### See also ###

- [The best practice of target settings][BestPractice]
- [Blocking on front-end - Overview][Overview]
- [Living with caching plugin][LivingCache]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[SEO-WIKI]:     https://en.wikipedia.org/wiki/Search_engine_optimization "Search engine optimization - Wikipedia"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[Overview]:     {{ '/codex/overview.html'                              | prepend: site.baseurl }} "Overview | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'            | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
