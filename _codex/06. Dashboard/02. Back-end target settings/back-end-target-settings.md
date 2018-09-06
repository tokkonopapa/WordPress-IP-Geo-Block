---
layout: page
language: en
category: codex
section: Dashboard
title: Back-end target settings
---

WordPress has many important backend entrances (i.e. endpoint) that will 
affect on the website. In this section, you can set up rules to validate 
requests for particularly important endpoints among them.

<!--more-->

#### Comment post ####

It validates requests to `wp-comments-post.php`.

#### XML-RPC ####

It validates requests to `xmlrpc.php`.

The plugin [Jetpack by WordPress.com][Jetpack] will access this endpoint from 
their servers in United States. Therefore, cooperation with WordPress.com does
not work if the country code `US` is not in "[**Whitelist of country code**]
[CountryList]" or not in the blacklist.

In such a case, please put [IP addresses of Jetpack servers][JetpackHost] 
or the AS number [AS2635][AS2635] of [Automattic, Inc][Automattic] into 
"[**Whitelist of extra IP addresses prior to country code**][IP-Whitelist]".

#### Login form ####

It validates requests to `wp-login.php`.

- **Target actions**  
In addition to login, you can enable actions such as user registration, 
[password protected page][PassProtect] and so on.

#### Admin area ####

It validates requests to `wp-admin/*.php`.

Requests to this area would cause a redirection to the login page or 
unintentional affects on the website due to attacks that exploit 
vulnerabilities in themes and plugins (in case of being authenticated).

- **Prevent Zero-day Exploit**  
You can protect your site from these attacks that can not be prevented with 
"**Block by country**".

#### Admin ajax/post ####

It validates requests especially to `wp-admin/admin-ajax.php` and 
`wp-admin/admin-post.php`.

These endpoints are used as WordPress standard interfaces for themes and 
plugins to perform their specific tasks. But many vulnerable themes and 
plugins were out there due to lack of secure coding to use these endpoints.

- **Prevent Zero-day Exploit**  
You can protect your site from attacks targeted at those vulnerabilities 
that can not be prevented with "**Block by country**".

- **Exceptions**  
When "**Prevent Zero-day Exploit**" is enabled, unintentional blocking may 
occur depending on a theme or plugin. In such a case, please select the 
corresponded action / page in the list. You can easily find such blocking 
using a magnifying glass button (<span class="emoji">
![Find blocked requests in “Logs”]({{ '/img/2018-01/find.png' | prepend: site.baseurl }}
 "Find blocked requests in “Logs”")
</span>) then an alert button (<span class="emoji">
![Navigate to “Logs” tab]({{ '/img/2018-01/alert.png' | prepend: site.baseurl }}
 "Navigate to “Logs” tab")
</span>) can navigate you to the **Logs** tab to closely look up such blocking.
  
  Special care must be taken when you specify actions with only a lock icon 
(<span class="emoji">
![Unlock icon]({{ '/img/2017-08/lock.png' | prepend: site.baseurl }})
</span>) as exceptions, because those actions are for administrator only.

![Find blocked request button]({{ '/img/2018-01/FindLogsButton.png' | prepend: site.baseurl }}
 "Find blocked request button"
)

#### Plugins area ####

It validates requests to `wp-content/plugins/⋯/*.php`.

- **Prevent Zero-day Exploit**  
Many vulnerabilities are found in [plugins][ExposeWPConf] that are programmed 
to call PHP directly under their own directly. This option protects the site 
against attacks against these vulnerabilities that can not be prevented by 
"**Blocking by country**" alone.

- **Force to load WP core**  
Like [TimThumb][TimThumb], there are certain types of plugins which have PHP 
files that can be called independently of WordPress. This ends not to perform 
validation by this plugin. For such cases, this option can protect the site 
that can not be prevented by "**Blocking by country**".

- **Exception**  
It is almost the same as "**Admin ajax/post**", but plugin name should be 
specified.

#### Themes area ####

It validates requests to `wp-content/themes/⋯/*.php`.

"**Force to load WP core**" and "**Exception**" are almost the same as 
"**Plugins area**".

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[TimThumb]:     https://blog.sucuri.net/2014/06/timthumb-webshot-code-execution-exploit-0-day.html "TimThumb WebShot Code Execution Exploit (Zeroday)"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[Jetpack]:      https://wordpress.org/plugins/jetpack/ "Jetpack by WordPress.com &#124; WordPress.org"
[CountryList]:  {{ '/codex/validation-rule-settings-ja.html#国コードのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[IP-Whitelist]: {{ '/codex/validation-rule-settings-ja.html#国コードに優先して検証するipアドレスのホワイトリストブラックリスト' | prepend: site.baseurl }} "検証ルールの設定 | IP Geo Block"
[JetpackHost]:  https://github.com/Automattic/jetpack/issues/1719 "Automattic IP Ranges: offer IP list via API endpoint. - Issue #1719 - Automattic/jetpack"
[Automattic]:   https://automattic.com/ "Automattic"
[AS2635]:       https://ipinfo.io/AS2635 "AS2635 Automattic, Inc - ipinfo.io"
[PassProtect]:  https://codex.wordpress.org/Using_Password_Protection "Using Password Protection &laquo; WordPress Codex"
