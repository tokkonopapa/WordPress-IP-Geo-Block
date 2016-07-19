---
layout: post
title: "Call for testing 3.0.0 beta"
date: 2016-07-15 00:00:00
categories: changelog
published: true
script: []
inline:
---

I've developed my plugin [IP Geo Block][IP-Geo-Block] as a security purpose 
plugin which protect the back-end of WordPress since version 2.0.0 on 2015.
But since then, I've received many requests and suggestion that it would be 
better to have blocking ability on the front-end.

At the time when I released version 2.0.0, I though that the most robust and 
secured state for WordPress is a state just after clean installation which has 
a default theme and no plugin (of course, with strong password). Actually, 
more themes and plugins we have, more chances to be compromised we have.

But in reality like other plugins, I can't provide a perfect solution to my 
users. The only approach I should aim is to reduce chances to be attacked and 
compromised. So I decided to equip this plugin with the ability of "**Blocking 
on front-end**".

<!--more-->

### New feature: Blocking on front-end ###

You can find the "**Front-end target settings**" section on the **Settings** tab.

![Front-end target settings]({{ '/img/2016-07/FrontendSettings.png' | prepend: site.baseurl }}
 "Front-end target settings")

#### Matching rule ####

You can follow the rule at "**Validation rule settings**" or set a rule that is 
different from the rule for back-end.

#### Permitted user agent string and qualification ####

You must be sure to grant permission to search engine bots or crawlers such as 
google, yahoo and being. This feature is possible to fulfill your wishes by 
giving a particle pair of "**user agent string**" and "**qualification**" 
separated by a colon "**:**". These are described as follows:

| Particle          | Description                                                                  |
|:------------------|:-----------------------------------------------------------------------------|
| user agent string | A part of user agent string. Uppercase and lowercase letters are distinguished. An asterisk "*" matches all strings. |
| qualification     | **DNS**               : Permit if its reverse DNS lookup is available.       |
|                   | **FEED**              : Permit if it requests the feed url.                  |
|                   | **Country code**      : Permit if it comes from the specified country.       |
|                   | **IP address (CIDR)** : Permit if it's in the range of specified IP address. |

### New feature: IP address cache by cookie ###

Previously, this plugin would keep the IP address and country code in the 
option table using [transients API][Transients_API] as a cache mechanism to 
reduce the load of fetching IP address databases. Now in this release, those 
will be kept in the independent table named `ip_geo_block_cache` and also in 
the cookie of user agents. A nonce will be embedded in the cookie so that the 
attackers can't sent out a forged country code.

Using cookie cache can be enabled at "**Cache settings**" (default is 
"**Enable**").

![Cache by cookie]({{ '/img/2016-07/CacheByCookie.png' | prepend: site.baseurl }}
 "Cache by cookie")

### Setup for testing ###

<div class="alert alert-info">
	<strong>An important notice</strong>: If you are a user of caching plugin 
	such as
	<a href="https://wordpress.org/plugins/wp-super-cache/" title="WP Super Cache - WordPress Plugins">WP Super Cache</a>
	or
	<a href="https://wordpress.org/plugins/w3-total-cache/" title="W3 Total Cache - WordPress Plugins">W3 Total Cache</a>,
	you should configure these plugins to use "**PHP mode**" (in case of W3TC, 
	"**Disk: Basic**") and turn on "<strong>late initialization</strong>" 
	option. If your caching plugin doesn't support similar options, that's the 
	case of redirecting by <code>mod_rewrite</code> in <code>.htaccess</code> 
	(<a href="https://wordpress.org/plugins/wp-fastest-cache/" title="WP Fastest Cache - WordPress Plugins">WP Fastest Cache</a>)
	or <code>advanced-cache.php</code>
	(<a href="https://wordpress.org/plugins/comet-cache/" title="Comet Cache - WordPress Plugins">Comet Cache</a>)
	, "<strong>Blocking on front-end</strong>" feature might lead to generate 
	inconsistent pages.
</div>

Before installing the beta version, you may backup your settings by "**Export 
settings**" at "**Plugin settings**". Then please download 
[zip archive][3.0.0Beta-ZIP], unzip it and follow the next steps:

1. Deactivate your IP Geo Block.
2. Upload whole of `ip-geo-block` in the unzipped archive into your plugin's 
   directory on your server. The previous version can be overwritten.
3. Activate IP Geo Block again.

### How to test ###

You can find "**Public facing pages**" on "**Logs**" tab. The option "**Record 
validation logs**" at "**Record settings**" is better to be set as 
"**Unauthenticated user**" to check the functionality.

![Logs on front-end]({{ '/img/2016-07/FrontendLogs.png' | prepend: site.baseurl }}
 "Logs on front-end")

You can also get the result of reversed DNS lookup on "**Search**" tab.

![Reverse DNS lookup]({{ '/img/2016-07/ReverseDNS.png' | prepend: site.baseurl }}
 "Reverse DNS lookup")

### May have something to feedback ###

Although I've made sure that it would not break anything in an obvious way, 
please keep in mind that this is a developing version. It might still contain 
undiscovered issues. When you find or notice something, please let me know 
about them in the comment of this post or at the [forum][forum].

So I would deeply appreciate for your kind cooperation <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span> .

[IP-Geo-Block]:   https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Transients_API]: https://codex.wordpress.org/Transients_API
[WP-Super-Cache]: https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache - WordPress Plugins"
[W3-Total-Cache]: https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache - WordPress Plugins"
[Fastest-Cache]:  https://wordpress.org/plugins/wp-fastest-cache/ "WP Fastest Cache - WordPress Plugins"
[Comet-Cache]:    https://wordpress.org/plugins/comet-cache/ "Comet Cache - WordPress Plugins"
[3.0.0Beta-ZIP]:  https://github.com/tokkonopapa/WordPress-IP-Geo-Block/archive/3.0.0b.zip "GitHub - tokkonopapa/WordPress-IP-Geo-Block at 3.0.0b"
[3.0.0BetaDiff]:  https://github.com/tokkonopapa/WordPress-IP-Geo-Block/compare/2.2.6...3.0.0b "Comparing 2.2.6...3.0.0b - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
[forum]:          https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block"
