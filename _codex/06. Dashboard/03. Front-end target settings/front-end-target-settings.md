---
layout: page
language: en
category: codex
section: Dashboard
title: Front-end target settings
---

This plugin has a function that blocks access to the public facing pages (aka 
front-end) from undesired countries.

This feature would definitely reduce the risk of your website being hacked by 
malicious requests to the front-end, and also reduce comment spams because 
spammers (or bots) always must get the form on front-end page before they post
spam text. On the other hand, it's difficult to distinguish such requests from
all the requests when you don't want [regional content restrictions][GeoBlock].

In this section, you can find some solutions to fit your demands.

![Front-end target settings]({{ '/img/2016-08/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings"
)

### Public facing pages ###

Turn on "**Enable blocking**" if you want to block something on the front-end.

### Matching rule ###

You can select one of these:

- **Follow "Validation rule settings"**
- **Whitelist**
- **Blacklist**

When you select **Whitelist** or **Blacklist**, you can configure a different 
set of country code from "[**Validation rule settings**][RuleSettings]" 
section.

You can leave the text box of "**Witelist**" or "**Blacklist**" empty if you 
don't want [Geo-Blocking][GeoBlockEU]. In this case, only a set of rules under
"**UA string and qualification**" is applied.

### Validation target ###

You can select one of the followings:

- **All requests**  
  Every request to the front-end will be validated as a blocking target.
  This can be compatible with [some caching plugins under a certain condition]
  [LivingCache].

- **Specify the targets**  
  You can specify the requests for the page, post type, category and tag on a 
  single page or archive page as a blocking target. This ignores the setting 
  of "**Validation timing**" and thus it's always deferred util "wp" action 
  hook fires. Note that this would lose the compatibility with page caching.  
  
  ![Validation target]({{ '/img/2016-11/ValidationTarget.png' | prepend: site.baseurl }}
   "Validation target"
  )  
  
  Also note that even when you enable all the targets, attackers still can 
  access to your top page because those are not a single or archive page.
  So if you want to block any requests from undesired country including your 
  top page, you should select "**All requests**".

### UA string and qualification ###

In addition to the maching rule for country blocking, you must be sure to 
grant permission to search engine bots or crawlers such as google, yahoo and 
being. This feature is possible to fulfill your wishes by giving a pair of 
"**UA string**" and "**qualification**" separated by an applicable rule which 
can be "`:`" (pass) or "`#`" (block).

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

See "[UA string and qualification][UA-Qualify]" for more details.

### Simulation mode ###

This feature enables to simulate validation without deployment of blocking on 
front-end. The results can be found at "**Pubic facing pages**" in Logs. It's 
useful to check which pages would be blocked or passed.

![Logs for public faicing pages]({{ '/img/2016-08/Logs-Public.png' | prepend: site.baseurl }}
 "Logs for public faicing pages"
)

### See also ###

- [The best practice of target settings][BestPractice]
- [Living with caching plugin][LivingCache]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'            | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html'           | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[GeoBlock]:     https://en.wikipedia.org/wiki/Geo-blocking "Geo-blocking - Wikipedia"
[GeoBlockEU]:   https://ec.europa.eu/digital-single-market/en/faq/geo-blocking "Geo-blocking | Digital Single Market"
[RuleSettings]: {{ '/codex/validation-rule-settings.html'              | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
