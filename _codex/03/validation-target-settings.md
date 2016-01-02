---
layout: page
category: codex
title: The best practice of "Validation target settings"
---

At the "**Validation target settings**", you should select the options, 
"**Block by country**" and "**Prevent Zero-day Exploit**" on each target.
 This document helps you how to configure those options to fit for your site.

<!--more-->

### Initial settings ###

At the time of your first installation and activation of this plugin, you can 
see the following configuration at "**Validation target settings**". This is a 
set of minimum configuration for a user who are interested in Geo-blocking by 
IP address.

![Initial settings]({{ '/img/2016-01/InitialSettings.png' | prepend: site.baseurl }}
 "Initial settings"
)

Note that this minimum configuration doesn't protect your site against the 
attacks targeted at the vulnerble plugins and themes in your site.

### Setting for "XML-RPC" ###

The WordPress core file `xmlrpc.php` is used as a reciever of both 
[pingbacks][pingbacks] and [**R**emote **P**rocedure **C**all][XML-RPC].
Speaking about RPC, it's useful for [mobile apps][WP-Mobile] and 
[Jetpack][Jetpack]. But it also can be abused as login attempts by attackers.

"**Block by country**" for this target can accept those useful requests while 
reducing the risk of exploiting your site from forbidden countries.

<div class="alert alert-info">
  <strong>NOTE:</strong>
  If you give permission to the specific servers, put IP addresses with
  <a href="https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing" title="Classless Inter-Domain Routing - Wikipedia, the free encyclopedia">CIDR notation</a>
  into the
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

For example, [Sucuri News][SucuriNews] had already unvailed the 
[brute-force attempts using xmlrpc][BruteXMLRPC] which requires hundreds of 
authentication attempts only by one HTTP request. In this case, this plugin 
can also effectively protect your site against such attacks.

#### Changing the number of login attempts ####

Unfortunately, this plugin doesn't provide you the UI to change the number of 
login attempts which is 5 by default. But if you're familiar with phpMyAdmin, 
you can change the value of "**`login_failes`**" at 
"**`ip_geo_block_settings`**" in the options table.

![login failed in phpMyAdmin]({{ '/img/2016-01/LoginAttempts.png' | prepend: site.baseurl }}
 "login failed in phpMyAdmin"
)

### Setting for "Admin area" ###

Normally, php files under the `wp-admin/` directory should be accessed only by 
the admin except Ajax call. So the best practice for this target is enabling 
both "**Block by country**" and "**Prevent Zero-day Exploit**" 
(e.g. [WP-ZEP][WP-ZEP]).

![Best setting for Admin area]({{ '/img/2016-01/AdminArea.png' | prepend: site.baseurl }}
 "Best setting for Admin area"
)

But WP-ZEP potentially blocks some of admin actions because it can't track the 
multiple redirections. If you find such a case, you shall uncheck it.

### Setting for "Admin ajax/post" ###

The `wp-admin/admin-ajax.php` can provide some services to the visitors on the 
public facing pages. But sometimes it can be used as an entrance to exploit 
leading to the vulnerable plugins or themes. So the configuration for this 
target is slightly delicate than others.

![Best setting for Admin ajax/post]({{ '/img/2016-01/AdminAjaxPost.png' | prepend: site.baseurl }}
 "Best setting for Admin ajax/post"
)

If you use some plugins or themes which provide an ajax service to the 
visitors on the public facing pages, and want to give it to every one 
(even if requested from forbidden countries), you should **uncheck** the 
"**Block by country**".

On the other hand, WP-ZEP validates services only for admin regardless of the 
country code. But still there's a possibility to block some of admin actions 
because of the same reason as the "**Admin area**". Unless you find such an 
action, **enabling** the "**Prevent Zero-day Exploit**" is always recommended.

<div class="alert alert-info">
  <strong>NOTE:</strong>
  WP-ZEP will carefully identify the action which provides services only for 
  admin. It means that if an action serves both visitors on the public facing 
  pages and admins on the dashboard (e.g. back end), WP-ZEP will not block it.
</div>

### Setting for "Plugins / Themes area" ###

Setting for this area is almost the same as "**Admin ajax/post**". The only 
difference is that WP-ZEP can't distinguish the services whether for visitors 
or for admin. It means that WP-ZEP will block all the request except from the 
admin dashboard.

So if you use a plugin which provides download services on the public facing 
pages for example, and the download link is directly pointed to the php file 
in that plugin's directory, you should **uncheck** the 
"**Prevent Zero-day Exploit**"

![Best setting for Plugins/Themes area]({{ '/img/2016-01/PluginsThemesArea.png' | prepend: site.baseurl }}
 "Best setting for Plugins/Themes area"
)

<div class="alert alert-warning">
  <strong>WARNING:</strong>
  You should put a specific <code>.htaccess</code> to those areas. Please refer
  to <a href="http://www.ipgeoblock.com/article/exposure-of-wp-config-php.html" title="Prevent exposure 
  of wp-config.php | IP Geo Block">this article</a> for more details.
</div>

### Any questions? ###

If you have something to ask, please feel free to open your issue at the 
[support forum][SupportForum].

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[XML-RPC]:      https://en.wikipedia.org/wiki/XML-RPC "XML-RPC - Wikipedia, the free encyclopedia"
[pingbacks]:    http://codex.wordpress.org/Introduction_to_Blogging#Pingbacks "Introduction to Blogging « WordPress Codex"
[WP-Mobile]:    https://apps.wordpress.org/ "WordPress.org Mobile Apps"
[Jetpack]:      https://wordpress.org/support/topic/disabling-xml-rpc-may-damage-jetpack "WordPress › Support » Disabling XML-RPC may damage JetPack?"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block "WordPress › Support » IP Geo Block"
[SucuriNews]:   https://blog.sucuri.net/ "Sucuri Blog - Website Security News"
[BruteXMLRPC]:  https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html "Brute Force Amplification Attacks Against WordPress XMLRPC - Sucuri Blog"
[WP-ZEP]:       {{ '/article/how-wpzep-works.html' | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack? | IP Geo Block"
