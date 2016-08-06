---
layout: page
category: codex
section: blocking on front-end
title: Living with caching plugin
---

Definitely we need not only hardning security but also speed. So you may want 
to use [IP Geo Block][IP-Geo-Block] with a caching plugin.

But this is a big challenge to live with a caching plugin because it would try 
to respond the contents without excuting any PHP codes at the very begining of 
WordPress core proccess. That's why [Wordfence][Wordfence] equips their own 
caching system called [Falcon Engine][FalconEngine] inside of it.

In case of this plugin, there are some restrictions about caching plugins.

### Supported plugins ###

Currently, this plugin supports 
  [WP Super Cache][WPSuperCache],
  [W3 Total Cache][W3TotalCache] and
  **Basic Caching** (not Falcon!) of [Wordfence][Wordfence]
with the following configuration.

| Plugin Name        | Configuration                                                  |
|:-------------------|:---------------------------------------------------------------|
| WP Super Cache     | "**Use PHP to serve cache files**" and "**Late init**"         |
| W3 Total Cache     | "**Disk: Basic**" and "**Late initialization**" for page cache |
| Wordfence Security | "**Basic Caching**" and `ip-geo-block-mu.php` in `mu-plugins`  |

#### Installing `ip-geo-block-mu.php` ####

You can find `wp-content/mu-plugis/ip-geo-block-mu.php` in this plugin's 
package. This PHP file will be loaded prior to other plugins when you install 
it into your `wp-content/mu-plugins` directory which is known as 
[Must-use plugins][MU-Plugins].

This is recommended for WP Super Cache and W3 Total Cache. And it must be 
installed for Wordfence which doesn't support "**Late init**".

<div class="alert alert-warning">
	<strong>Warning</strong>: If you use WP Super Cache or W3 Total Cache 
	without enabling "<strong>Late init</strong>", blocking on front-end 
	nerver works because those plugins use <code>advanced-cache.php</code> as 
	"drop-in" which will be executed at the very bigining of WordPress core 
	processes.
</div>


### See also ###

- [The best practice of target settings][BestPractice]
- [Permitted UA string and qualification][PermitUA]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[PermitUA]:     {{ '/codex/permitted-ua-and-qualification.html'       | prepend: site.baseurl }} "Permitted UA string and qualification | IP Geo Block"
[WPSuperCache]: https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache &mdash; WordPress Plugins"
[W3TotalCache]: https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache &mdash; WordPress Plugins"
[Wordfence]:    https://wordpress.org/plugins/wordfence/ "Wordfence Security &mdash; WordPress Plugins"
[FalconEngine]: https://docs.wordfence.com/en/Falcon_Cache "Falcon Cache - Wordfence Documentation"
[MU-Plugins]:   https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex"
