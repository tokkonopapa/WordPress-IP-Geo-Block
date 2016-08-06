---
layout: page
category: codex
section: blocking on front-end
title: Permitted UA string and qualification
---

You must be sure to grant permission to search engine bots or crawlers such as 
google, yahoo and being. This feature is possible to fulfill your wishes by 
giving a pair of "**UA string**" and "**qualification**" separated by a colon 
"`:`". These are described as follows:

### UA string ###

You can specify a part of user agent string (case sensitive). An asterisk "`*`"
matches all user agents.

### Qualification ###

Currently, you can obtain four types of qualification listed bellow :

| Qualification     | Description                                              |
|:------------------|:---------------------------------------------------------|
| "DNS"             | Permit if the result of reverse DNS lookup is available. |
| "FEED"            | Permit if the request is feed url.                       |
| Country code      | Permit if the request comes from the specified country.  |
| IP address (CIDR) | Permit if the IP address is within the specific range.   |

### Examples ###

![Permitted UA]({{ '/img/2016-08/PermittedUA.png' | prepend: site.baseurl }}
 "Permitted UA"
)

### Getting host name by IP address ###

You can get host name by reversed DNS lookup on "**Search**" tab.

![Reverse DNS lookup]({{ '/img/2016-07/ReverseDNS.png' | prepend: site.baseurl }}
 "Reverse DNS lookup")

### See also ###

- [The best practice of target settings][BestPractice]
- [Living with caching plugin][LivingCache]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'           | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
