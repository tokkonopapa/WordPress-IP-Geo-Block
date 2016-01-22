---
layout: page
category: codex
title: How to test the functionality
---

You may want to test the blocking behavior of this plugin. This document 
shows you how to do it especially arround the admin, plugins and themes 
area based on [version 2.2.2][Ver2.2.2] and later.

<!--more-->

### Preparation ###

The most easy way to simulate submitting a request from outside of your nation 
is using [the browser addon for VPN service][VPN-ADDON].

![VPN addon]({{ '/img/2016-01/VPN-Addon.png' | prepend: site.baseurl }}
 "VPN addon"
)

To test the blocking behavior, submit the following links to your post. The 
first 2 lines are for admin ajax, and the last 4 lines are for direct access 
to the PHP file in plugins area. In particular, the last 2 lines will include 
`wp-load.php` to load the WordPress core functions.

Ensure that `http://example.com` is replaced to your WordPress home.

{% highlight html %}
<ol>
    <li><a href="http://example.com/wp-admin/admin-ajax.php?action=my-ajax">/wp-admin/admin-ajax-php?action=my-ajax</a>
    <li><a href="http://example.com/wp-admin/admin-ajax.php?action=my-ajax&file=../../../wp-config.php">/wp-admin/admin-ajax-php?action=my-ajax&file=../../../wp-config.php</a></li>
    <li><a href="http://example.com/wp-content/plugins/ip-geo-block/samples.php">/wp-content/plugins/ip-geo-block/samples.php</a></li>
    <li><a href="http://example.com/wp-content/plugins/ip-geo-block/samples.php?file=../../../wp-config.php">/wp-content/plugins/ip-geo-block/samples.php?file=../../../wp-config.php</a></li>
    <li><a href="http://example.com/wp-content/plugins/ip-geo-block/samples.php?wp-load=1">/wp-content/plugins/ip-geo-block/samples.php?wp-load=1</a></li>
    <li><a href="http://example.com/wp-content/plugins/ip-geo-block/samples.php?wp-load=1&file=../../../wp-config.php">/wp-content/plugins/ip-geo-block/samples.php?wp-load=1&file=../../../wp-config.php</a></li>
</ol>
{% endhighlight %}

As you can see, an even line is a malicious request to attempt to expose 
`wp-config.php`.

![Sample page]({{ '/img/2016-01/TestSamplePage.png' | prepend: site.baseurl }}
 "Sample page"
)

Also to handle a ajax request properly, put the following code into your 
`functions.php`.

{% highlight php startinline %}
/**
 * Ajax for non privileged user
 *
 */
add_action( 'wp_ajax_nopriv_my-ajax', 'my_ajax_handler' );
function my_ajax_handler() {
    ;
}
{% endhighlight %}

### Blocking malicious request ###

Now at first, uncheck and disable all the settings for "**Admin ajax/post**" 
and "**Plugins area**".

![Setting for admin and plugins]({{ '/img/2016-01/TestAdminPluginsOff.png' | prepend: site.baseurl }}
 "Setting for admin and plugins"
)

When you assess the above links as a visitor on the public facing page, you'll 
see `0` in case your request are success, otherwise you'll be blocked.

<div class="alert alert-warning">
  <strong>Important:</strong>
  If you click the 4th link and see <code>0</code> (means success), then you 
  should properly configure the `.htaccess` in your plugins area. Please refer 
  to <a href="/article/exposure-of-wp-config-php.html"
  title="Prevent exposure of wp-config.php | IP Geo Block">this article</a>.
</div>

### Block by country ###

OK then, check and enable "**Block by country**".

![Block by country]({{ '/img/2016-01/TestBlockCountryOn.png' | prepend: site.baseurl }}
 "Block by country"
)

All the links will be blocked when you're behind the VPN proxy and `.htaccess` 
is set properly. And when you turn off the VPN addon, then only the malicious 
links at even lines will be blocked.

### Prevent Zero-day Exploit ###

Yeah, the last one is "**Prevent Zero-day Exploit**".

![Prevent Zero-day Exploit]({{ '/img/2016-01/TestWPZepOn.png' | prepend: site.baseurl }}
 "Prevent Zero-day Exploit"
)

All the links except the 1st one will be blocked. It is because the 1st link 
is a service for the visitors. If you add the action hook for the admin as 
follows, then the 1st link is also blocked.

{% highlight php startinline %}
/**
 * Ajax for admin
 *
 */
add_action( 'wp_ajax_my-ajax', 'my_ajax_admin_handler' );
function my_ajax_admin_handler() {
    ;
}
{% endhighlight %}

It means that non privileged user never succeed zero-day attacks via Admin 
ajax and plugins / themes area. On the other hand, if you're logged in as an 
admin, all the links at odd lines will not be blocked.

<div class="alert alert-warning">
  <strong>Important:</strong>
  If the links are submitted as comments, then the WordPress commenting system 
  will add <code>rel="nofollow"</code> into each anchor tag. In this case, 
  WP-ZEP will block every link to prevent
  <a href="https://en.wikipedia.org/wiki/Cross-site_request_forgery"
  title="Cross-site request forgery - Wikipedia, the free encyclopedia">CSRF</a>.
</div>

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[VPN-ADDON]:    https://www.google.co.jp/search?q=browser+addon+vpn+service "browser addon vpn service - Google search"
[Ver2.2.2]:     {{ '/changelog/release-2.2.2.html' | prepend: site.baseurl }} "2.2.1 Release Note | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
