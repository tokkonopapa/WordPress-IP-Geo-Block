---
layout: post
title:  "Living with Jetpack"
date:   2015-08-02 01:00:00
categories: article
published: true
---

[Jetpack][Jetpack] is the Swiss army knife for your WordPress site. A plentiful 
awesome features are served for free by hooking to the WordPress.com.

In this article, I show some notes related to living with [Jetpack][Jetpack2] 
and [IP Geo Block][IP-Geo-Block].

<!--more-->

### Connecting to WordPress.com ###

When IP Geo Block inhibits accessing to the `xmlrpc.php` by country code, 
connecting to WordPress.com will fail at the activation process.

![Connect to WordPress.com]({{ "/img/2015-08/Jetpack-connect.png" | prepend: site.baseurl }}
 "Connect to WordPress.com"
)

And also accessing to the `Admin area` should not be `Prevent Zero-day exploit` 
to lead the activation process to success.

![Validation Settings]({{ "/img/2015-08/ValidationSettings.png" | prepend: site.baseurl }}
 "Validation Settings"
)

Once the self-hosted Jetpack successfully connects to your WordPress.com account,
those settings can be configured as you like.

### Login protection ###

Jetpack has a cloud-based brute force protection module called "[**Jetpack 
Protect**][JPP]" which collects malicios IPs from Jetpack users in the world.
But if another plugin that has same functionality ([Limit Login Attempts][LLA], 
[Wordfence Security][WFS], [BulletProof Security][BPS] and so on) is already in 
your site, Jetpack Protect will not be activated to avoid conflict.

IP Geo Block has also a same functionality but it blocks accesses to the 
`wp-login.php` itself. It means that IP Geo Block works at more early stage 
than Jetpack.

And if a malicios login attempt comes from your own country, Jetpack Protect 
serves its function so well.

So the conflict never happnes <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f44d.png)
</span>.

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Jetpack]: http://jetpack.me/ "Jetpack for WordPress"
[Jetpack2]: https://wordpress.org/plugins/jetpack/ "WordPress › Jetpack by WordPress.com « WordPress Plugins"
[JPP]: http://jetpack.me/support/security-features/ "Security Features - Jetpack for WordPress"
[LLA]: https://wordpress.org/plugins/limit-login-attempts/ "WordPress › Limit Login Attempts « WordPress Plugins"
[WFS]: https://wordpress.org/plugins/wordfence/ "WordPress › Wordfence Security « WordPress Plugins"
[BPS]: https://wordpress.org/plugins/bulletproof-security/ "WordPress › BulletProof Security « WordPress Plugins" 
