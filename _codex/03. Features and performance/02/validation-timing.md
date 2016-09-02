---
layout: page
category: codex
section: Features and performance
title: Validation timing
excerpt: Configuration to reduce load on server.
---

Normally, a plugin will be loaded at a certain phase during WordPress boot 
process and will typically do its jobs after `init` action hook. It means 
that a plugin will be kept waiting until almost all plugins have finished to 
be loaded.

But it's wasteful to give resources of your server to spams and attackers.

The "**Validation timing**" at "**Validation rule settings**" can help to 
drastically reduce load on server especially against brute-force attacks.

<!--more-->

### "mu-plugins" (ip-geo-block-mu.php) ###

![Validation timing]({{ '/img/2016-09/ValidationTiming.png' | prepend: site.baseurl }}
 "Validation timing"
)

When you select this mode as "**Validation timing**", this plugin will install 
`ip-geo-block-mu.php` into your `wp-content/mu-plugins/` which is for 
[must-use plugins][MU-plugins]. It means that this plugin will be loaded and 
execute validation prior to other typical plugins.

#### Restrictions ####

As you can find the order of execution in [action reference][ActionHook], 
mu-plugins are processed prior to theme setup. Consequently, the following 
two issues would be raised.

1. **[Custom filter hooks][FilterHooks] in `functions.php` does not work**  
   It is bacause the `functions.php` in the theme directory would not be parsed
   when this plugin do its jobs.

2. **[Human friendly error page][ErrorPage] is unavailable**  
   It is because the theme setup would not be finished when this plugin do its 
   jobs. In this case, you should save your human friendly error page as a 
   static file. Taking `403.php` as an example, once you configure 
   "**init action hook**" as "**Validation timing**" and visit that page, then 
   save its source code (actually, it's HTML!) as `403.php`.

![Human friendly error page]({{ '/img/2016-09/HumanFriendly.png' | prepend: site.baseurl }}
 "Human friendly error page"
)

#### Performance ####

The more plugins you have, the lower site speed you get.

You may be interested in the benchmark of the two "**Validation timing**".
As with [the previous report][LoadOnServer], the test environment and sample 
plugins are showen blow :

| Category  | Description                                          |
|:----------|:-----------------------------------------------------|
| Hardware  | MacBook Pro / 2.8GHz Core i7 / Memory 16GB           |
| Software  | OS X 10.9.5 / MAMP 3.5.2 (Apache 2.2.29, PHP 5.6.10) |
| WordPress | 4.6-ja / Site Language: English                      |

![Plugins Dashboard]({{ '/img/2016-09/PluginsDashboard.png' | prepend: site.baseurl }}
 "Plugins Dashboard"
)

The following shows a comparison between the two of "**Validation timing**" 
against the each target using [ApacheBench].

##### xmlrpc.php #####

|                   | init action hook | mu-plugins |
|:------------------|-----------------:|-----------:|
| Complete Reqs     |              271 |        595 |
| Reqs/sec [#/sec]  |             4.51 |       9.89 |
| Time/req [msec]   |          221.877 |    101.093 |

##### xmlrpc.php (sys.multicall) #####

|                   | init action hook | mu-plugins |
|:------------------|-----------------:|-----------:|
| Complete Reqs     |              273 |        593 |
| Reqs/sec [#/sec]  |             4.53 |       9.87 |
| Time/req [msec]   |          220.988 |    101.315 |

##### wp-login.php #####

|                   | init action hook | mu-plugins |
|:------------------|-----------------:|-----------:|
| Complete Reqs     |          280     |       619  |
| Reqs/sec [#/sec]  |          4.66    |     10.28  |
| Time/req [msec]   |          214.395 |    97.247  |

##### wp-admin/admin-ajax.php #####

|                   | init action hook | mu-plugins |
|:------------------|-----------------:|-----------:|
| Complete Reqs     |              229 |        551 |
| Reqs/sec [#/sec]  |             3.81 |       9.17 |
| Time/req [msec]   |          262.588 |    109.039 |

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[ApacheBench]:  http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool - Apache HTTP Server Version 2.4"
[MU-plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
[ActionHook]:   https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference &laquo; WordPress Codex"
[WP-ZEP]:       {{ '/article/how-wpzep-works.html'                                  | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack?"
[FilterHooks]:  {{ '/codex/#filter-hooks'                                           | prepend: site.baseurl }} "Filter hooks | IP Geo Block"
[ErrorPage]:    {{ '/codex/customizing-the-response.html#human-friendly-error-page' | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[LoadOnServer]: {{ '/article/impact-on-server-load.html'                            | prepend: site.baseurl }} "Impact on server load caused by brute-force attacks | IP Geo Block"
