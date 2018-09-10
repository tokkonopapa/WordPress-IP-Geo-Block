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
and display graphically on "**Statistics**" screen.

![Statistics of validation]({{ '/img/2018-09/ValidationStat.png' | prepend: site.baseurl }}
 "Statistics of validation"
)


### Record "IP address cache" ###

This plugin executes the validation by associating an IP address with its 
country code, host name and login failure count. If you enable this option, you
can keep them in cache for a certain period of time to avoid duplicate searches
of country codes and host names. This can reduce server load and perform the 
validation at high speed.

![Statistics in IP address cache]({{ '/img/2018-09/IPAddressCache.png' | prepend: site.baseurl }}
 "Statistics in IP address cache"
)

- **Expiration time [sec] for each entry**  
Specify the time to hold the cache in seconds. The default is 3600 seconds 
(1 hour).  
  
  If the number of login failures exceeds "[**Max number of failed login 
  attempts per IP address**][LoginFail]", access to the login form will be 
  blocked for this period. To salvage someone from this accident, please 
  select the corresponding IP address and apply "**Remove entries by IP 
  address**".
  
  ![Remove entries by IP address]({{ '/img/2018-09/LoginFailure.png' | prepend: site.baseurl }}
   "Remove entries by IP address"
  )

### Record "Validation logs" ###

This option enables you to view the history of validation results on "**Logs**"
screen.

![Validation logs]({{ '/img/2018-09/ValidationLogs.png' | prepend: site.baseurl }}
 "Validation logs"
)

- **Expiration time [days] for each entry**  
Each entry in Logs is deleted automatically when it expires at this option or 
when it exceeds the maximum number of entries (500 by default).

- **$_POST key to record with value**  
When this plugin fetches a request by HTTP method `POST`, the data in the 
message body corresponding to the specified key is expanded and recorded 
securely. In the following example, `log` for login name and `pwd` for 
password are recorded when those are posted to the login form.

![$_POST data]({{ '/img/2018-09/PostData.png' | prepend: site.baseurl }}
 "$_POST data"
)

### Interval [sec] to cleanup expired entries of IP address ###

This option specifies the period to remove the expired entries in the IP 
address cache and verification log. The default is 900 seconds (15 minutes).

### Remove all settings and records at uninstallation ###

When uninstalling, it removes all the data including the recorded IP addresses 
from the database as well as setting of this plugin.

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[LoginFail]:    {{ '/codex/validation-rule-settings.html#max-number-of-failed-login-attempts-per-ip-address' | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GDPR]:         https://en.wikipedia.org/wiki/General_Data_Protection_Regulation "General Data Protection Regulation - Wikipedia"
[PII]:          https://en.wikipedia.org/wiki/Personally_identifiable_information "Personally identifiable information - Wikipedia"
