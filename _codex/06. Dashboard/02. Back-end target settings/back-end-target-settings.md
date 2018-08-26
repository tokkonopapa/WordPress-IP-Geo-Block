---
layout: page
language: en
category: codex
section: Dashboard
title: Back-end target settings
---

WordPress has important backend entrances (e.g. endpoint) that has some impact 
on the site. In this section, you can set up rules to validate requests for 
particularly important entrances of them.

<!--more-->

#### Comment post ####

It validates requests to `wp-comments-post.php`.

#### XML-RPC ####

It validates requests to `xmlrpc.php`.

The plugin [Jetpack by WordPress.com][Jetpack] will access this endpoint from 
the servers in United States. Therefore, cooperation with WordPress.com does 
not work if the country code `US` is not in the "[**Whitelist of country code**]
[CountryList]" or is in the blacklist.

In such a case, please put [IP addresses of Jetpack servers][JetpackHost] 
or the AS number [AS2635][AS2635] of [Automattic, Inc][Automattic] into 
"[**Whitelist of extra IP addresses prior to country code**][IP-Whitelist]".

#### Login form ####

It validates requests to `wp-login.php`.

In addition to login, you can enable each action such as user registration, 
[password protected page][PassProtect], etc.

#### Admin area ####

It validates requests to `wp-admin/*.php`.

Requests to this area would cause redirection to the login page (in case of 
unauthenticated), or unintentional affects on the site due to attacks that 
exploit vulnerabilities in themes and plugins (in case of being authenticated).
You can enable "**Prevent Zero-day Exploit**" to defend these attacks.

#### Admin ajax/post ####

It validates requests especially to `wp-admin/admin-ajax.php` and 
`wp-admin/admin-post.php`.

These endpoints are WordPress standard interfaces to perform specific tasks.
And many themes and plugins that include vulnerabilities related to those 
endpoints have been outthere.

You can select ether "**Block by country**" or "**Prevent Zero-day Exploit**" 
to block requests targeted at those vulnerabilities.

When "**Prevent Zero-day Exploit**" is enabled, unintentional blocking may 
occur depending on a theme or plugin. In such a case, please select the 
corresponded action / page in "**Exceptions**". You can easily find such 
blocking using a magnifying glass button <span class="emoji">
![Find blocked requests in “Logs”]({{ '/img/2018-01/find.png' | prepend: site.baseurl }}
 "Find blocked requests in “Logs”")
</span>. An alert button <span class="emoji">
![Navigate to “Logs” tab]({{ '/img/2018-01/alert.png' | prepend: site.baseurl }}
 "Navigate to “Logs” tab")
</span> can navigate you to the **Logs** tab to closely look up such blocking.

Special care must be taken when you specify actions with only a "Lock icon"
<span class="emoji">
![Unlock icon]({{ '/img/2017-08/lock.png' | prepend: site.baseurl }})
</span> as exceptions, because those actions are for administrator only.

![Find blocked request button]({{ '/img/2018-01/FindLogsButton.png' | prepend: site.baseurl }}
 "Find blocked request button"
)

#### Plugins area ####

It validates requests to `wp-content/plugins/⋯/*.php`.

Some plugins may be programmed to call PHP directly under the plugin directly.
Since many vulnerabilities are found in such plugins, it is possible to select 
"**Prevent Zero-day Exploit**" to block attacks aimed at those vulnerabilities.

Also there are certain types of plugins which run a PHP file independently 
regardless of WordPress. This ends not to perform validation. In this case 
you can specify "**Force to load WP core**".

"**Exception**" is almost the same as "**Admin ajax/post**", but plugin should 
be specified.

#### Themes area ####

It validates requests to `wp-content/themes/⋯/*.php`.

"**Force to load WP core**" and "**Exception**" are almost the same as 
"**Plugins area**".

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[Jetpack]:      https://wordpress.org/plugins/jetpack/ "Jetpack by WordPress.com &#124; WordPress.org"
[CountryList]:  {{ '/codex/validation-rule-settings.html#whitelistblacklist-of-country-code' | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[IP-Whitelist]: {{ '/codex/validation-rule-settings.html#whitelistblacklist-of-extra-ip-addresses-prior-to-country-code' | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[JetpackHost]:  https://github.com/Automattic/jetpack/issues/1719 "Automattic IP Ranges: offer IP list via API endpoint. - Issue #1719 - Automattic/jetpack"
[Automattic]:   https://automattic.com/ "Automattic"
[AS2635]:       https://ipinfo.io/AS2635 "AS2635 Automattic, Inc - ipinfo.io"
[PassProtect]:  https://codex.wordpress.org/Using_Password_Protection "Using Password Protection &laquo; WordPress Codex"
