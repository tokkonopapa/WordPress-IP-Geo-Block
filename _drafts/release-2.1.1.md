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

A new choice of `Block by country at registration` was added on `Login form`.
Actually, this feature allows you to `login` and `logout` from anywhere, but 
prohibit anything else including `register`, `resetpass` and `lostpassword`.

One thing you should know about this feature before using it is that it changes 
the priority of validation methods. Basically, this plugin has 3 methods for 
all validations, i.e. **authentication**, **country code** and **WP-ZEP**.
In the previous version, the priority of those are as follows ("BBC" means 
"Block by country") :

| Login form   |  1st priority  |  2nd priority  | 3rd priofity   |
|:-----------  |:--------------:|:--------------:|:--------------:|
| `Disable`    |     WP-ZEP     |  country code  | authentication |
| `BBC`        |     WP-ZEP     |  country code  | authentication |

In this version :

| Login form            |  1st priority  |  2nd priority  | 3rd priofity   |
|:----------------------|:--------------:|:--------------:|:--------------:|
| `Disable`             |     WP-ZEP     |  country code  | authentication |
| `BBC at registration` |     WP-ZEP     | authentication |  country code  |
| `BBC`                 |     WP-ZEP     |  country code  | authentication |

The reason why only the `BBC at registration` is so specialized is that I 
wouldn't like to change the priority of other choices. So you have nothing to 
do if you wouldn't choose this feature. If you choose it and want to add more 
permitted countries for login, you can embed the following codes into your 
`functions.php` :

{% highlight php startinline %}
function my_whitelist( $validate ) {
	$whitelist = array(
		'JP', 'US', // should be upper case
	);

	$validate['result'] = 'blocked';

	if ( in_array( $validate['code'], $whitelist ) ) {
		$validate['result'] = 'passed';
		break;
	}

	return $validate;
}
add_filter( 'ip-geo-block-login', 'my_whitelist' );
{% endhighlight %}

### A 404 page ###

The `404.php` in the theme template directory is used (if it exists) when this 
plugin blocks access to some pages.

[![title]({{ "/img/2015-xx/sample.png" | prepend: site.baseurl }}
  "title"
)][link]

### Obsoleted filter hooks ###

With the improvement of the internal logic, 
`ip-geo-block-(admin-actions|admin-pages|wp-content)` were obsoluted.
Alternatively new filter hooks `ip-geo-block-bypass-(admins|plugins|themes)` 
are added to bypass WP-ZEP.

Please check out [samples.php][samples.php] about the usage of these hooks.

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Asking-for-extending]: https://wordpress.org/support/topic/asking-for-extending "WordPress › Support » Asking for extending"
[samples.php]:        https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
