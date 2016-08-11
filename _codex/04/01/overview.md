---
layout: page
category: codex
section: blocking on front-end
title: Overview
---

From 3.0.0, this plugin has a function that blocks undesired requests to the 
public facing pages aka front-end.

This feature would definitely reduce spams, and also increases chances to 
protect your site against malicious requests to the front-end.

![Front-end target settings]({{ '/img/2016-08/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings"
)

### Public facing pages ###

Please turn on **Block by country** if you want to use this function.

### Matching rule ###

You can select one of these:

- **Follow "Validation rule settings"**
- **Whitelist**  
- **Blacklist**  

If you select **Whitelist** or **Blacklist**, you can configure a different set
of country code from "Validation rule settings".

### UA string and qualification ###

In addition to the maching rule, you must be sure to grant permission to search 
engine bots or crawlers such as google, yahoo and being. This feature is 
possible to fulfill your wishes by giving a pair of "**UA string**" and 
"**qualification**" separated by an applicable rule which can be "`:`" (pass) 
or "`#`" (block).

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

### Simulation mode ###

This feature enables to simulate validation without deployment of blocking on 
front-end. The results can be found at "Pubic facing pages" in Logs. It's 
useful to check which pages would be blocked or passed.

![Logs at public faicing pages]({{ '/img/2016-08/PublicLogs.png' | prepend: site.baseurl }}
 "Logs at public faicing pages"
)

### See also ###

- [The best practice of target settings][BestPractice]
- [Living with caching plugin][LivingCache]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'           | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html'          | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[Ver3.0.0]:     {{ '/changelog/release-3.0.0.html'                    | prepend: site.baseurl }}
