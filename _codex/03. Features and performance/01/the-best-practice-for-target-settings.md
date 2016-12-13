---
layout: page
category: codex
section: Features and performance
title: The best practice for target settings
excerpt: The best practice for target settings
---

At the "**Validation target settings**", you can enable the option, "**Block 
by country**" for each target as a basic configuration. Additionally, 
"**Prevent Zero-day Exploit**" can be enabled as a extended option.
This document helps you how to configure those options to fit for your site.

<!--more-->

### Initial settings ###

At the time of your first installation and activation of this plugin, you can 
see the following configuration at "**Validation target settings**". This is a 
set of minimum configuration which doesn't protect your site against the 
attacks targeted at the vulnerble plugins and themes in your site.

![Initial settings]({{ '/img/2016-01/InitialSettings.png' | prepend: site.baseurl }}
 "Initial settings"
)

### Setting for "XML-RPC" ###

The WordPress core file `xmlrpc.php` is an endpoint for not only 
[**R**emote **P**rocedure **C**all][XML-RPC] but also [pingbacks][pingbacks].
As for the RPC, it's used by [Jetpack][Jetpack] and [mobile apps][MobileApp] 
to provide their services. But it's also abused as login attempts by the 
attackers.

"**Block by country**" for this target can accept the useful requests while 
reducing the risk of exploiting your site from forbidden countries. And 
"**Completely close**" [works more effectively][Release223] than 
[WordPress 4.4 and later][Core#34336] or [Disable XML-RPC][DIS-XMLRPC] 
especially against the [brute force amplification attacks][BruteXMLRPC] 
by `system.multicall`.

<div class="alert alert-info">
  <strong>NOTE:</strong>
  If you want to accept the specific IP addresses, put them (with 
  <a href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia">CIDR notation</a>
  to specify the range of IP addresses) into the
  "<strong>White list of extra IP addresses prior to country code</strong>"
  at "<strong>Validation rule settings</strong>".
</div>

![White list of extra IP addresses]({{ '/img/2016-01/IP-WhiteList.png' | prepend: site.baseurl }}
 "White list of extra IP addresses"
)

### Setting for "Login form" ###

You can choose "**Block by country (register, lost password)**" which enables 
anyone to login from anywhere but disables registering a new user and reseting 
one's password.

You should also know about the "limiting login attempts" functionality. If you 
enable this target (e.g. not "**Disable**"), the "limiting login attempts" 
will work for you. It also affects the target "**XML-RPC**" which can be used 
for login attempts.

For example, [Sucuri News][SucuriNews] had already unvailed the [brute-force 
attempts using xmlrpc][BruteXMLRPC] which requires hundreds of authentication 
attempts only by one HTTP request. This plugin can also effectively protect 
your site against this kind of attacks.

#### Limit login attempts ####

You can change "**Max number of failed login attempts per IP address**" which 
is 5 by default at the bottom of "**Validation rule settings**". If you select 
"0" as for it, then users can not fail to login.

![login failed in phpMyAdmin]({{ '/img/2016-01/LoginAttempts.png' | prepend: site.baseurl }}
 "login failed in phpMyAdmin"
)

Note that the lockout time is same as the expiration time of cache.

![Expiration time at Cache settings]({{ '/img/2016-01/CacheSettings.png' | prepend: site.baseurl }}
 "Expiration time at Cache settings"
)

### Setting for "Admin area" ###

Normally, php files under the `wp-admin/` should be accessed only by the 
administrator except Ajax call. So the best practice for this target is 
enabling both "**Block by country**" and "**Prevent Zero-day Exploit**" 
(e.g. [WP-ZEP][WP-ZEP]).

![Best setting for Admin area]({{ '/img/2016-01/AdminArea.png' | prepend: site.baseurl }}
 "Best setting for Admin area"
)

But WP-ZEP potentially blocks some of admin actions because it can't track the 
multiple redirections. If you find such a case, you shall uncheck it.

### Setting for "Admin ajax/post" ###

The `wp-admin/admin-(ajax|post).php` can provide services for both visitors 
and admins, and those can be abused as an entrance to exploit aiming at the 
vulnerable plugins or themes. So the configuration for those is slightly 
delicate than others.

The corresponding set of validation can be shown as follows:

![Validation set by service]({{ '/img/2016-01/CoveredAdminAjaxPost.png' | prepend: site.baseurl }}
 "Validation set by service"
)

If you want to serve ajax to every one (even if requested from forbidden 
countries), you should uncheck the “**Block by country**”.
 
Note that WP-ZEP will carefully identify the request which provides services 
only for admin regardless of the country code. Although there's a possibility 
to block something because of the same reason as the "**Admin area**", 
enabling the "**Prevent Zero-day Exploit**" is always recommended.

![Best setting for Admin ajax/post]({{ '/img/2016-01/AdminAjaxPost.png' | prepend: site.baseurl }}
 "Best setting for Admin ajax/post"
)

### Setting for "Plugins / Themes area" ###

Setting for this area is almost the same as "**Admin ajax/post**". The only 
difference is that WP-ZEP can't distinguish the services whether for visitors 
or for admin. It means that WP-ZEP will block all the request except from the 
admin dashboard.

![Validation set by request]({{ '/img/2016-01/CoveredPluginsThemes.png' | prepend: site.baseurl }}
 "Validation set by request"
)

So if you use a plugin which provides download services on the public facing 
pages for example, and the download link is directly pointed to the php file 
in that plugin's directory, you should **uncheck** the 
"**Prevent Zero-day Exploit**"

![Best setting for Plugins/Themes area]({{ '/img/2016-01/PluginsThemesArea.png' | prepend: site.baseurl }}
 "Best setting for Plugins/Themes area"
)

#### Force to load WP core ####

It's better to configure rewrite rules for both "**Block by coutnry**" and 
"**Prevent Zero-day Exploit**" to block certain types of attack. Currently 
this plugin supports to setup a specific `.htaccess` into those areas for 
[Apache][Apache]. Please refer to [this article][PreventExp] for more details.

### Any questions? ###

If you have something to ask, please feel free to open your issue at the 
[support forum][SupportForum].

### See also ###

- [How does WP-ZEP prevent zero-day attack?][WP-ZEP]
- [Prevent exposure of wp-config.php][ExposeWPConf]
- [Analysis of Attack Vectors][AnalysisVec]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[XML-RPC]:      https://en.wikipedia.org/wiki/XML-RPC "XML-RPC - Wikipedia, the free encyclopedia"
[pingbacks]:    http://codex.wordpress.org/Introduction_to_Blogging#Pingbacks "Introduction to Blogging « WordPress Codex"
[MobileApp]:    https://apps.wordpress.org/ "WordPress.org Mobile Apps"
[Jetpack]:      https://wordpress.org/support/topic/disabling-xml-rpc-may-damage-jetpack "WordPress › Support » Disabling XML-RPC may damage JetPack?"
[DIS-XMLRPC]:   https://wordpress.org/plugins/disable-xml-rpc/ "WordPress › Disable XML-RPC « WordPress Plugins"
[Core#34336]:   https://core.trac.wordpress.org/ticket/34336 "#34336 (Disable XML-RPC system.multicall authenticated requests on the first auth failure) – WordPress Trac"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block "WordPress › Support » IP Geo Block"
[SucuriNews]:   https://blog.sucuri.net/ "Sucuri Blog - Website Security News"
[BruteXMLRPC]:  https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html "Brute Force Amplification Attacks Against WordPress XMLRPC - Sucuri Blog"
[WP-ZEP]:       {{ '/article/how-wpzep-works.html' | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack? | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[AnalysisVec]:  {{ '/codex/analysis-of-attack-vectors.html'  | prepend: site.baseurl }} "Analysis of Attack Vectors | IP Geo Block"
[PreventExp]:   {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[Apache]:       https://httpd.apache.org/ "Welcome! - The Apache HTTP Server Project"
[Release223]:   http://www.ipgeoblock.com/changelog/release-2.2.3.html "The best practice of target settings | IP Geo Block"
