---
layout: page
category: codex
section: Features and performance
title: Compatibility with cache plugins
---

Definitely we need not only hardening security but also speeding up the site.
So you may want to use [IP Geo Block][IP-Geo-Block] with a caching plugin.

This is a big challenge to make this plugin compatible with cache plugins, 
because they would respond the requested content without executing any PHP 
codes at the very beginning of WordPress core process or even before the core
starts.

### Requirements for compatibility  ###

To achieve the demand for both security and speed, the cache plugins need to 
support the following requirements.

#### Do not cache page ####

One of the most important thing for this plugin is to prevent caching an error
page where an access denied message is rendered. For this purpose, this plugin 
defines `DONOTCACHEPAGE` constant and set the flag for [`is_404()`][IS-404].

On a cache plugin side, one of the followings needs to be supported.

1. Support `DONOTCACHEPAGE`
2. Support "Do not cache 404 page"

For example, [WP Super Cache][WPSuperCache] supports [both of them][Cache-404]
by default, while many other plugins have 2. in their setting options.

#### Deferred execution ####

IP Geo Block provides the option "[Validation timing][ValidTiming]" which kick 
off this plugin at an earlier phase than other typical plugins.

In correspondence with it, a cache plugin need to support the option for 
"**deferred execution**" or "**late initialization**" to give this plugin a 
chance to render an error page before the cached page is responded against the
requests from blacklisted countries (or IPs).

### Supported plugins ###

Here's a list of supported requirements mentioned above.

| Plugin Name                         | Do not cache page | Deferred execution |
|:------------------------------------|-------------------|--------------------|
| [WP Fastest Cache][FastestCache]    |     &#x02713;     |         N/A        |
| [Comet Cache][CometCache]           |     &#x02713;     |         N/A        |
| [Hyper Cache][HyperCache]           |     &#x02713;     |         N/A        |
| [WP Rocket][WP-Rocket]              |     &#x02713;     |         N/A        |
| [WP Super Cache][WPSuperCache]      |     &#x02713;     |      &#x02713;     |
| [W3 Total Cache][W3TotalCache]      |     &#x02713;     |      &#x02713;     |
| [Swift Performance Lite][SwiftLite] |     &#x02713;     |      &#x02713;     |
| [Vendi Cache][VendiCache]           |     &#x02713;     |      &#x02713;     |

This list shows that:

- [WP Fastest Cache][FastestCache], [Comet Cache][CometCache],
  [Hyper Cache][HyperCache] and [WP Rocket][WP-Rocket] can be used with 
  IP Geo Block but do not have full compatibility.
- [WP Super Cache][WPSuperCache], [W3 Total Cache][W3TotalCache], [Swift 
  Performance Lite][SwiftLite] and [Vendi Cache][VendiCache] can be fully 
  compatible with IP Geo Block by their certain setting of options.

The followings are the options setting in each plugin.

#### WP Super Cache ####

![WP Super Cache]({{ '/img/2018-11/WPSuperCache.png' | prepend: site.baseurl }}
 "WP Super Cache"
)

#### W3 Total Cache ####

![W3 Total Cache - Page Cache Method]({{ '/img/2018-11/W3TC-DiskBasic.png' | prepend: site.baseurl }}
 "W3 Total Cache - Page Cache Method"
)

![W3 Total Cache - Late Initialization]({{ '/img/2018-11/W3TC-LateInit.png' | prepend: site.baseurl }}
 "W3 Total Cache - Late Initialization"
)

#### Swift Performance Lite ####

![Swift Performance Lite - Caching Mode]({{ '/img/2018-11/SwiftPerformance.png' | prepend: site.baseurl }}
 "Swift Performance Lite - Caching Mode"
)

#### Vendi Cache ####

![Vendi Cache - Cache Mode]({{ '/img/2018-11/VendiCache.png' | prepend: site.baseurl }}
 "Vendi Cache - Cache Mode"
)

### Installing MU-Plugins ###

A [must-use plugin][MU-Plugins] is a plugin that will always be activated by 
default and be loaded prior to other typical plugins when you install it into 
your `wp-content/mu-plugins/` directory.

You **must** select `"mu-plugins" (ip-geo-block-mu.php)` as [Validation Timing]
[ValidTiming] in "**Validation rule settings**" section to install this plugin
as "must-use plugin".

![Validation Timing]({{ '/img/2016-08/ValidationRuleSettings.png' | prepend: site.baseurl }}
 "Validation Timing"
)

#### Restrictions ####

Installing `ip-geo-block-mu.php` has following restrictions mainly because of 
its execution timing which is before `after_setup_theme` action hook:

- You should write your own code for [custom filter hooks][FilterHooks] not 
  in your theme's `functions.php` but `drop-in.php` in your [geolocation 
  database directory][GeoDB-Dir].
- [Human friendly error page][ErrorPage] should be saved as a static file.

Please refer to "[Validation timing][ValidTiming]" for more details.

### What will become of my site if I use other plugin? ###

Well, it would not be so serious. Let's think about [WP Fastest Cache]
[FastestCache] for example.

If someone requests a page where a cache hit occurs, no PHP code would be 
executed but static contents in the cache would be responded. In this case, 
this plugin has no chance to block anything.

If someone requests a page where a cache miss occurs, then WordPress would 
start to handle the request. In this case, this plugin would have a chance 
to validate the request.

So a visitor from forbidden countries sometimes gets cached contents and 
sometimes gets blocked. This means attack from forbedden countires would fail.
As a consequence, blocking by country can still reduce the risk of infection.

### How about Object Cache plugins? ###

[WP_Object_Cache][ObjectCache] is a core class that implements an object cache.
It stores all of the cache data to memory and makes them reusable within a 
request, but it does not make them reusable between different user agents even 
for the same content.

Unlike the full page cache plugins mentioned above, object cache plugins like 
[LiteSpeed Cache][LiteSpeed] on [OpenLiteSpeed Web Server][OpenLiteSpeed] and 
[Redis Object Cache][RedisCache] using [Redis][Redis] make the "object"
__persistent__. So the mechanism of persistent object cache is suitable for 
dynamic contents, and should be compatible with IP Geo Block.

### See also ###

- [Validation timing][ValidTiming]
- [Dashboard - Front-end target settings][FrontEnd]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[ValidTiming]:  {{ '/codex/validation-timing.html'           | prepend: site.baseurl }} "Validation timing | IP Geo Block"
[FrontEnd]:     {{ '/codex/front-end-target-settings.html'   | prepend: site.baseurl }} "Front-end target settings | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html' | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[FilterHooks]:  {{ '/codex/#filter-hooks'                    | prepend: site.baseurl }} "Codex | IP Geo Block"
[GeoDB-Dir]:    {{ '/codex/how-to-fix-permission-troubles.html#geolocation-database' | prepend: site.baseurl }} "How can I fix permission troubles? | IP Geo Block"
[ErrorPage]:    {{ '/codex/customizing-the-response.html#human-friendly-error-page'  | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[FastestCache]: https://wordpress.org/plugins/wp-fastest-cache/ "WP Fastest Cache &#124; WordPress.org"
[CometCache]:   https://wordpress.org/plugins/comet-cache/ "Comet Cache &#124; WordPress.org"
[HyperCache]:   https://wordpress.org/plugins/hyper-cache/ "Hyper Cache &#124; WordPress.org"
[WP-Rocket]:    https://github.com/wp-media/wp-rocket "wp-media/wp-rocket: Performance optimization plugin for WordPress"
[WPSuperCache]: https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache &mdash; WordPress Plugins"
[W3TotalCache]: https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache &mdash; WordPress Plugins"
[SwiftLite]:    https://wordpress.org/plugins/swift-performance-lite/ "Swift Performance Lite &#124; WordPress.org"
[VendiCache]:   https://wordpress.org/plugins/vendi-cache/ "Vendi Cache &#124; WordPress.org"
[IS-404]:       https://codex.wordpress.org/Function_Reference/is_404 "Function Reference/is 404 &laquo; WordPress Codex"
[Cache-404]:    https://wordpress.org/support/topic/caching-of-404-pages/ "Topic: Caching of 404 pages &#124; WordPress.org"
[MU-Plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
[ObjectCache]:  https://developer.wordpress.org/reference/classes/wp_object_cache/ "WP_Object_Cache | Class | WordPress Developer Resources"
[LiteSpeed]:    https://wordpress.org/plugins/litespeed-cache/ "LiteSpeed Cache &#124; WordPress.org"
[OpenLiteSpeed]:https://www.litespeedtech.com/open-source "Open Source - LiteSpeed Technologies"
[RedisCache]:   https://wordpress.org/plugins/redis-cache/ "Redis Object Cache &#124; WordPress.org"
[Redis]:        https://redis.io/ "Redis"
