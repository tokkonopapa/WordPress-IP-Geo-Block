---
layout: page
category: codex
section: blocking on front-end
title: Living with caching plugin
---

Definitely we need not only hardening security but also speeding up the site.
So you may want to use [IP Geo Block][IP-Geo-Block] with a caching plugin.

But there is a big challenge in living with a caching plugin because it would 
respond a requested content without excuting any PHP codes at the very begining
of WordPress core proccess. That's why [Wordfence][Wordfence] equips their own 
caching system called [Falcon Engine][FalconEngine] inside of it.

In case of this plugin, there are some restrictions about caching plugins.

<div class="alert alert-info">
	<a href="https://www.wordfence.com/blog/2016/10/removing-falcon-cache-wordfence-heres-need-know/" title="We are removing Falcon Cache from Wordfence. Here's what you need to know. - Wordfence">Wordfence stopped to provide Falcon Engine</a>
	from Wordfence 6.2.1. Alternatively, you can get a derivative version as 
	<a href="https://wordpress.org/plugins/vendi-cache/">Vendi Cache</a>.
</div>

### Supported plugins ###

Currently, this plugin supports 
  [WP Super Cache][WPSuperCache],
  [W3 Total Cache][W3TotalCache] and
  [Wordfence][Wordfence]
with the following configurations.

| Plugin Name        | Configuration                                                     |
|:-------------------|:------------------------------------------------------------------|
| WP Super Cache     | "**Use PHP to serve cache files**" and "**Late init**"            |
| W3 Total Cache     | "**Disk: Enhanced**" and "**Late initialization**" for page cache |
| Wordfence Security | "**Basic Caching**" + `"mu-plugins" (ip-geo-block-mu.php)`        |

### Installing MU-Plugins ###

A [must-use plugin][MU-Plugins] is a plugin that will always be activated by 
default and be loaded prior to other typical plugins when you install it into 
your `wp-content/mu-plugins/` directory.

You can install the PHP file `ip-geo-block/wp-content/mu-plugins/ip-geo-block-mu.php`
into the MU-Plugins directory in order to run at the early phase of WordPress 
core process.

![Validation Timing]({{ '/img/2016-08/ValidationRuleSettings.png' | prepend: site.baseurl }}
 "Validation Timing"
)

This is recommended for WP Super Cache and W3 Total Cache, and indispensable 
for Wordfence which doesn't support [deferred initialization][LazyInit].

<div class="alert alert-warning">
	If you configure WP Super Cache or W3 Total Cache without enabling 
	"<strong>Late init</strong>" or "<strong>Late initialization</strong>", 
	blocking on front-end doesn't work properly because these plugins provide 
	the requested contents at the very bigining of WordPress core process 
	without execution of other plugins.
</div>

#### Restrictions ####

The installing `ip-geo-block-mu.php` into MU-Plugins has some restrictions 
mainly because of its timing that is before `after_setup_theme` action hook :

- You should write your own code for [custom filter hooks][FilterHooks] not 
  in your theme's `functions.php` but `drop-in.php` in your [geolocation 
  database directory][GeoDB-Dir].

- [Human friendly error page][ErrorPage] should be saved as a static file.

Please refer to "[Validation timing][Validation]" for more details.

### See also ###

- [Validation timing][Validation]
- [Blocking on front-end - Overview][Overview]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Validation]:   {{ '/codex/validation-timing.html'           | prepend: site.baseurl }} "Validation timing | IP Geo Block"
[Overview]:     {{ '/codex/overview.html'                    | prepend: site.baseurl }} "Overview | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html' | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[FilterHooks]:  {{ '/codex/#filter-hooks'                    | prepend: site.baseurl }} "Codex | IP Geo Block"
[GeoDB-Dir]:    {{ '/codex/how-to-fix-permission-troubles.html#geolocation-database' | prepend: site.baseurl }} "How can I fix permission troubles? | IP Geo Block"
[ErrorPage]:    {{ '/codex/customizing-the-response.html#human-friendly-error-page'  | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[WPSuperCache]: https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache &mdash; WordPress Plugins"
[W3TotalCache]: https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache &mdash; WordPress Plugins"
[Wordfence]:    https://wordpress.org/plugins/wordfence/ "Wordfence Security &mdash; WordPress Plugins"
[FalconEngine]: https://docs.wordfence.com/en/Falcon_Cache "Falcon Cache - Wordfence Documentation"
[MU-Plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
[Action-Ref]:   https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference « WordPress Codex"
[LazyInit]:     https://en.wikipedia.org/wiki/Lazy_initialization "Lazy initialization - Wikipedia, the free encyclopedia"
