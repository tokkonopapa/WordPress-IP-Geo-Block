---
layout: post
title:  "2.1.1 Release Note"
date:   2015-07-01 00:00:00
categories: changelog
published: true
---

I sometimes go abroad on bussiness. In such a case, I will want to manage 
WordPress as an administrator. In order to allow such a thing, I implemented 
a new feature which was proposed by one of my users at the [support forum]
[Asking-for-extending].

<!--more-->

### A new feature ###

A new choice `Block by country (register, lost password)` has been equipped at 
"**Login form**" on "**Settings**" tab. This feature allows you to "**login**" 
and "**logout**" from anywhere, but any other request to `wp-login.php` will be 
validated by the country code.

It's suitable for "**Membership: Anyone can register**" on 
"[Settings General Screen][general-settings]" and also [BuddPress][BuddyPress].

One thing you should know about this feature before using it is that it changes 
the priority of validation methods. Basically, this plugin has 3 methods for 
all validations, i.e. **authentication**, **country code** and **WP-ZEP**.
In the previous version, the priority of those are as follows ("BBC" means 
"Block by country") :

| Login form   |  1st priority  |  2nd priority  | 3rd priofity   |
|:-----------  |:--------------:|:--------------:|:--------------:|
| `BBC`        |     WP-ZEP     |  country code  | authentication |

In this version :

| Login form       |  1st priority  |  2nd priority  | 3rd priofity   |
|:-----------------|:--------------:|:--------------:|:--------------:|
| `BBC (reg, pass)`|     WP-ZEP     | authentication |  country code  |
| `BBC`            |     WP-ZEP     |  country code  | authentication |

The reason why the `BBC (reg, pass)` has different priority from `BBC` is that 
I would not like to change the priority of the previous version. So you have 
nothing to do if you would not use this feature.

If you choose it and want to add more permitted countries for login, you can 
embed the following codes into your `functions.php` :

{% highlight php startinline %}
function my_whitelist( $validate ) {
	$whitelist = array(
		'JP', 'US', // should be upper case
	);

	$validate['result'] = 'blocked';

	if ( in_array( $validate['code'], $whitelist ) )
		$validate['result'] = 'passed';

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
{% endhighlight %}

### An error page ###

For example, the `404.php` in the theme template directory or child theme 
directory is used (if it exists) when this plugin blocks specific requests.

And also some new filter hooks to customize the http response status code and 
reason are available :

* `ip-geo-block-comment-status`, `ip-geo-block-comment-reason`
* `ip-geo-block-xmlrpc-status`, `ip-geo-block-xmlrpc-reason`
* `ip-geo-block-login-status`, `ip-geo-block-login-reason`
* `ip-geo-block-admin-status`, `ip-geo-block-admin-reason`

Use those hooks as follows :

{% highlight php startinline %}
function my_login_status( $code ) {
	return 503;
}
function my_login_reason( $msg ) {
	return "Sorry, this service is unavailable.";
}
add_filter( 'ip-geo-block-login-status', 'my_login_status' );
add_filter( 'ip-geo-block-login-reason', 'my_login_reason' );
{% endhighlight %}

### Obsoleted filter hooks ###

With the improvement of the internal logic, 
`ip-geo-block-(admin-actions|admin-pages|wp-content)` were obsoluted.
Alternatively new filter hooks `ip-geo-block-bypass-(admins|plugins|themes)` 
are added to bypass WP-ZEP.

Please check out [samples.php][samples.php] about the usage of these hooks.

### Capturing malicious access to the plugins/themes ###

Some silly attackers (or tools) send invalid requests to the plugins/themes 
area whose path are like this :

![silly access]({{ "/img/2015-06/invalid-plugins.png" | prepend: site.baseurl }}
 "silly access")

This kind of access seems to be aimed at the contaminated sites. Normally it 
fails on "404 Not Found" and don't matter if we leave them alone. However, I 
think it's a good chance to know the malicious post pattern if this plugin 
records such a footprint. So I made the condition of capturing a malicious 
access to the plugins/themes area in a loose manner.

Although it's a little annoying, please be patient !! <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f609.png)
</span>


[general-settings]: https://codex.wordpress.org/Settings_General_Screen "Settings General Screen ﾂｫ WordPress Codex"
[BuddyPress]: https://wordpress.org/plugins/buddypress/ "WordPress 窶ｺ BuddyPress ﾂｫ WordPress Plugins"
[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress 窶ｺ IP Geo Block ﾂｫ WordPress Plugins"
[Asking-for-extending]: https://wordpress.org/support/topic/asking-for-extending "WordPress 窶ｺ Support ﾂｻ Asking for extending"
[samples.php]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
