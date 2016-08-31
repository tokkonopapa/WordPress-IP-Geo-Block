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
drastically reduce load on server escecially against brute-force attacks.

<!--more-->

### "mu-plugins" (ip-geo-block-mu.php) ###

![Validation timing]({{ '/img/2016-09/ValidationTiming.png' | prepend: site.baseurl }}
 "Validation timing"
)

When you select this mode as "**Validation timing**", this plugin will install 
`ip-geo-block-mu.php` into your `/wp-content/mu-plugins/` directory which is 
for [must-use plugins][MU-plugins]. It means that this plugin will be loaded 
and execute validation prior to other typical plugins.

#### Restrictions ####

As you can find the order of execution in [action reference][ActionHook], 
mu-plugins are processed prior to theme setup. Consequently, the following 
two issues would be raised.

1. [Custom filter hooks][FilterHooks] in `functions.php` does not work  
   It is bacause the `functions.php` in the theme directory would not be parsed
   when this plugin do its jobs.

2. [Human friendly error page][ErrorPage] is unavailable  
   It is because the theme setup would not be finished when this plugin do its 
   jobs. In this case, you should save your human friendly error page as a 
   static file. Taking `403.php` as an example, once you configure 
   "**init action hook**" as "**Validation timing**" and visit that page, then 
   save its source code as `403.php`.

![Human friendly error page]({{ '/img/2016-09/HumanFriendly.png' | prepend: site.baseurl }}
 "Human friendly error page"
)

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[WP-ZEP]:       {{ '/article/how-wpzep-works.html' | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack?"
[MU-plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
[ActionHook]:   https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference &laquo; WordPress Codex"
[FilterHooks]:  {{ '/codex/#filter-hooks' | prepend: site.baseurl }} "Filter hooks | IP Geo Block"
[ErrorPage]:    {{ '/codex/customizing-the-response.html#human-friendly-error-page' | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
