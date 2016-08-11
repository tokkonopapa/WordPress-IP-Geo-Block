---
layout: page
category: codex
section: blocking on front-end
title: Living with caching plugin
---

Definitely we need not only hardening security but also speed. So you may want 
to use [IP Geo Block][IP-Geo-Block] with a caching plugin.

But There is a big challenge in living with a caching plugin because it would 
try to respond the contents without excuting any PHP codes at the very begining
of WordPress core proccess. That's why [Wordfence][Wordfence] equips their own 
caching system called [Falcon Engine][FalconEngine] inside of it.

In case of this plugin, there are some restrictions about caching plugins.

### Supported plugins ###

Currently, this plugin supports 
  [WP Super Cache][WPSuperCache],
  [W3 Total Cache][W3TotalCache] and
  **Basic Caching** (not Falcon Engine!) of [Wordfence][Wordfence]
with the following configurations.

| Plugin Name        | Configuration                                                  |
|:-------------------|:---------------------------------------------------------------|
| WP Super Cache     | "**Use PHP to serve cache files**" and "**Late init**"         |
| W3 Total Cache     | "**Disk: Basic**" and "**Late initialization**" for page cache |
| Wordfence Security | "**Basic Caching**" and `ip-geo-block-mu.php` in `mu-plugins`  |

### Installing MU-Plugins ###

A [Must-use plugin][MU-Plugins] is a plugin that will always be activated by 
default and be loaded prior to other regular plugins when you install it into 
your `wp-content/mu-plugins` directory.

You can install the PHP file `ip-geo-block-mu.php` in 
`ip-geo-block/wp-content/mu-plugins` into the MU-Plugins directory in order to 
run at the early stage of WordPress core process.

![Validation Timing]({{ '/img/2016-08/ValidationRuleSettings.png' | prepend: site.baseurl }}
 "Validation Timing"
)

This is recommended for WP Super Cache and W3 Total Cache, and indispensable 
for Wordfence which doesn't support "**Late init**".

<div class="alert alert-warning">
	<strong>Warning</strong>: If you use WP Super Cache or W3 Total Cache 
	without enabling "<strong>Late init</strong>", blocking on front-end 
	nerver works because those plugins use <code>advanced-cache.php</code> as 
	"drop-in" which will be executed at the very bigining of WordPress core 
	processes.
</div>

#### Restrictions ####

Installing into MU-Plugins has some restrictions :

- You can't use some of [custom filter hooks][FilterHooks] of this plugin in 
  your theme's `functions.php`.
- [Human friendly error page][ErrorPage] does not work because [must-use 
  plugins are loaded][Action-Ref] before `after_setup_theme` action hook.
- Every activity will be recorded in logs when you select "**Unauthenticated 
  user**" for "**Record validation logs**" at "**Record settings**" even if 
  you're logged in.

### See also ###

- [The best practice of target settings][BestPractice]
- [Blocking on front-end - Overview][Overview]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[Overview]:     {{ '/codex/overview.html'                             | prepend: site.baseurl }} "Overview | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html'          | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[FilterHooks]:  {{ '/codex/#filter-hooks'                             | prepend: site.baseurl }} "Codex | IP Geo Block"
[ErrorPage]:    {{ '/codex/customizing-the-response.html#human-friendly-error-page' | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[WPSuperCache]: https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache &mdash; WordPress Plugins"
[W3TotalCache]: https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache &mdash; WordPress Plugins"
[Wordfence]:    https://wordpress.org/plugins/wordfence/ "Wordfence Security &mdash; WordPress Plugins"
[FalconEngine]: https://docs.wordfence.com/en/Falcon_Cache "Falcon Cache - Wordfence Documentation"
[MU-Plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
[Action-Ref]:   https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference « WordPress Codex"
