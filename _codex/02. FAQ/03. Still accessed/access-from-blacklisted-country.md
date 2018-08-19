---
layout: page
category: codex
section: FAQ
title: I still have access from blacklisted country.
excerpt: I still have access from blacklisted country.
---

### Does this plugin work properly? ###

Absolutely, **YES**.

However, there are some reasons why users have such an impression.

#### 1. Wordfence Live Traffic ####

Sometimes, a [Wordfence Security][Wordfence] user who found some accesses in 
its Live Traffic view would claim that:

> Hey, this plugin seems to block nothing!

![Wordfence Live Traffic]({{ '/img/2017-06/WordfenceLiveTraffic.png' | prepend: site.baseurl }}
 "Wordfence Live Traffic"
)

But please do not get ahead of yourself, there's a proper order for everything!

Before WordPress runs, Wordfence ingeniously filters out malicious requests 
to your site by enabling [auto_prepend_file][AUTO_PREPEND] directive to include
PHP based Web Application Firewall. Then this plugin validates the rest of 
the requests that pass over Wordfence because those were not in WAF rules, 
especially you enables "**Prevent Zero-day Exploit**".

#### 2. Confused Country Code ####

Unfortunately, accuracy of country code depends on the geolocation databases.
Actually, there is a case that a same IP address has different country code.

{% comment %} 185.89.100.9 {% endcomment %}
![Different country code]({{ '/img/2015-09/ScanCountry.png' | prepend: site.baseurl }}
 "Different country code"
)

Here are other examples:

{% comment %} 109.73.235.8 {% endcomment %}
![Confused country code]({{ '/img/2017-03/ConfusedCountryCode.png' | prepend: site.baseurl }}
 "Confused country code"
)

{% comment %} 154.41.66.16 {% endcomment %}
![Strange country code]({{ '/img/2017-06/StrangeCountryCode.png' | prepend: site.baseurl }}
 "Strange country code"
)

In such a case, please consider to select more reliable databases.

### Considering the execution order ###

Please consider to set `"mu-plugins" (ip-geo-block-mu.php)` as **Validation 
timing** in **Validation rule settings**. It enables to capture the requests 
prior to other plugins.

![Validation timing]({{ '/img/2016-09/ValidationTiming.png' | prepend: site.baseurl }}
 "Validation timing"
)

Find more details at [Validation timing][Validation].

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Wordfence]:    https://wordpress.org/plugins/wordfence/ "Wordfence Security &mdash; WordPress Plugins"
[AUTO_PREPEND]: https://php.net/manual/en/ini.core.php#ini.auto-prepend-file "PHP: Description of core php.ini directives - Manual"
[Validation]:   {{ '/codex/validation-timing.html' | prepend: site.baseurl }} "Validation timing | IP Geo Block"
