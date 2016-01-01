---
layout: page
category: codex
title: The best practice of "Validation target settings"
---

At the "**Validation target settings**", there're several options that you 
should select the "**Block by country**" and "**Prevent Zero-day Exploit**" 
on each target. This document helps you how to configure those options to fit 
for your site.

<!--more-->

### Initial settings ###

At the time of your first installation and activation of this plugin, you can 
see the following configuration at "**Validation target settings**". This is a 
set of minimum configuration for a user who are interested in Geo-blocking by 
IP address.

![Initial settings]({{ "/img/2016-01/InitialSettings.png" }}
 "Initial settings"
)

### Setting for "Login form" ###

You can choose "**Block by country (register, lost password)**" which enables 
anyone to login from anywhere but disables to register a new user and reset 
one's password.

You should also know about the "limiting login attempts" functionality. If you 
enable this target (e.g. not "**Disable**"), the "limiting login attempts" 
will work for you. And it also affects the target "**XML-RPC**" which can be 
used for login attempts.

For example, [Sucuri News][SucuriNews] had already unvailed the 
[brute-force attempts using xmlrpc][BruteXMLRPC] which requires hundreds of 
authentication attempts only by one HTTP request. In this case, this plugin 
can protect your site against such attacks.

#### Changing the number of login attempts ####

Unfortunately, this plugin doesn't provide you the UI to change the number of 
login attempts which is 5 by default. But if you're familiar with phpMyAdmin, 
you can find the value at "**`login_failes`**" in "**`ip_geo_block_settings`**"
in the options table and can change it.

![login failed in phpMyAdmin]({{ "/img/2016-01/LoginAttempts.png" }}
 "login failed in phpMyAdmin"
)

### Setting for "Admin area" ###

Normally, php files under the `wp-admin/` directory (except Ajax) should be 
accessed only by the admin. So the best practice for this target is enabling 
both "**Block by country**" and "**Prevent Zero-day Exploit**" 
(e.g. [WP-ZEP][WP-ZEP]).

![Best setting for Admin area]({{ "/img/2016-01/AdminArea.png" }}
 "Best setting for Admin area"
)

But WP-ZEP has a possibility to block some of admin actions. For example, it 
can't track the multiple redirections. If you find such a case, you shall 
uncheck it.

### Setting for "Admin ajax/post" ###

The `wp-admin/admin-ajax.php` can provide some services to the visitors on the 
public facing pages. But sometimes it can be used as an entrance to exploit 
because of vulnerable plugins or themes. So the configuration for this target 
is slightly delicate than other targets.

![Best setting for Admin ajax/post]({{ "/img/2016-01/AdminAjaxPost.png" }}
 "Best setting for Admin ajax/post"
)

If you use some plugins or themes which provide ajax services to visitors on 
the public facing pages, and want to give them to every one (even requested 
from forbidden countries), you should **uncheck** the "**Block by country**".

On the other hand, WP-ZEP will block any services provided only for admin 
regardless of the country code. But still there's a possibility to block some 
of admin actions because of the same reason as the "**Admin area**". So unless 
you find such a service, **enabling** the "**Prevent Zero-day Exploit**" is 
recommended.

<div class="alert alert-info">
  <strong>NOTE:</strong>
  WP-ZEP will carefully identify the action which provides some service only 
  to admin. It means that if an action provides some services to both a 
  visitor on the public facing pages and an admin on the dashboard (e.g. back 
  end), WP-ZEP will not block it.
</div>

### Setting for "Plugins / Themes area" ###

Setting for this area is almost the same as "**Admin ajax/post**". The only 
difference is that WP-ZEP can't distinguish between a request for visitors 
and one for admin. It means that WP-ZEP will block all the request except 
from the admin dashboard.

![Best setting for Plugins/Themes area]({{ "/img/2016-01/PluginsThemesArea.png" }}
 "Best setting for Plugins/Themes area"
)

<div class="alert alert-warning">
  <strong>WARNING:</strong>
  You should put a specific <code>.htaccess</code> to those areas. Please refer
  to <a href="/article/exposure-of-wp-config-php.html" title="Prevent exposure 
  of wp-config.php | IP Geo Block">this article</a> for more details.
</div>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[SucuriNews]:   https://blog.sucuri.net/ "Sucuri Blog - Website Security News"
[BruteXMLRPC]:  https://blog.sucuri.net/2015/10/brute-force-amplification-attacks-against-wordpress-xmlrpc.html "Brute Force Amplification Attacks Against WordPress XMLRPC - Sucuri Blog"
[WP-ZEP]:       {{ "/article/how-wpzep-works.html" }} "How does WP-ZEP prevent zero-day attack? | IP Geo Block"
