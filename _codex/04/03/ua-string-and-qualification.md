---
layout: page
category: codex
section: blocking on front-end
title: UA string and qualification
---

You must be sure to grant permission to search engine bots or crawlers such as 
google, yahoo and being. Of course, bad bot should be blocked. This feature is 
possible to fulfill your wishes by giving a pair of "**UA string**" and 
"**Qualification**" separated by an applicable rule which can be "`:`" (pass) 
or "`#`" (block).

### UA string ###

You can specify a part of user agent string (case sensitive). An asterisk "`*`"
matches all user agents.

### Qualification ###

Currently, you can obtain four types of qualification listed bellow :

| Qualification     | Description                                                      |
|:------------------|:-----------------------------------------------------------------|
| FEED              | True if the request is the feed url.                             |
| HOST              | True if the result of reverse DNS lookup is available.           |
| HOST=__string__   | True if the host name by reverse DNS lookup includes __string__. |
| Country code      | True if the request comes from the specified country.            |
| IP address (CIDR) | True if the IP address is within the specific range.             |

### Negative operation ###

A negative operation "`!`" can be placed just before a qualification. It inverts
the meaning of qualification.

### Examples ###

| Sample       | Description                                                         |
|:-------------|:--------------------------------------------------------------------|
| Google:HOST  | Pass  if UA includes "Google" and reverse DNS lookup is available   |
| Google#!HOST | Block if UA includes "Google" and reverse DNS lookup is unavailable |

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

### See also ###

- [The best practice of target settings][BestPractice]
- [Blocking on front-end - Overview][Overview]
- [Living with caching plugin][LivingCache]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[Overview]:     {{ '/codex/overview.html'                             | prepend: site.baseurl }} "Overview | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'           | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
