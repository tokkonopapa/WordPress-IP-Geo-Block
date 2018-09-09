---
layout: page
language: en
category: codex
section: Dashboard
title: Privacy and record settings
---

<!--more-->

### Anonymize IP address ###

In [GDPR][GDPR], IP address is regarded as [personal information][PII].
When this option is enabled, the end of IP address is masked with `***`
on recording so that the individuals can not be identifiable by itself.

### Do not send IP address to external APIs ###

Data Processing Agreement (DPA) is required regarding any relationship where 
one party transfer personal data from European Economic Area to another party 
in other countries. When this option is enabled, the obtained IP address shall
not be sent to the external geolocation APIs.

### Record "Statistics of validation" ###

Statistical data such as **Blocked**, **Blocked by countries**, **Blocked per 
day**, **Blocked by type of IP address**, **Average response time of each API**
and display graphically on "**Statistics**" tab.

![Statistics of validation]({{ '/img/2018-09/ValidationStat.png' | prepend: site.baseurl }}
 "Statistics of validation"
)


### Record "IP address cache" ###

![Statistics in IP address cache]({{ '/img/2018-09/IPAddressCache.png' | prepend: site.baseurl }}
 "Statistics in IP address cache"
)

- **Expiration time [sec] for each entry**  

### Record "Validation logs" ###

![Validation logs]({{ '/img/2018-09/ValidationLogs.png' | prepend: site.baseurl }}
 "Validation logs"
)

- **Expiration time [days] for each entry**  

- **$_POST key to record with value**  

### Interval [sec] to cleanup expired entries of IP address ###

### Remove all settings and records at uninstallation ###

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GDPR]:         https://en.wikipedia.org/wiki/General_Data_Protection_Regulation "General Data Protection Regulation - Wikipedia"
[PII]:          https://en.wikipedia.org/wiki/Personally_identifiable_information "Personally identifiable information - Wikipedia"
