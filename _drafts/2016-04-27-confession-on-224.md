---
layout: post
title: "Confession of the problem on 2.2.4"
date: 2016-04-27 00:00:00
categories: article
published: true
script: []
inline:
---

I must make a confession that the release 2.2.4 caused a big problem which 
stopped the sites of my plugin's users. In this article, I will report why 
this problem happened and also how should I improve my developping process 
in order to prevent this kind of undesired accident again.

<!--more-->

### The problem ###

When you upgraded to 2.2.4 of [IP Geo Block][IP-Geo-Block], you encountered 
the following error:

{% highlight text %}
Warning: strpos(): Empty needle in /public_html/wp-content/plugins/ip-geo-block/classes/class-ip-geo-block.php on line 71.
{% endhighlight %}

And to solve this trouble, users should delete IGB manually through the FTP 
or similar tools like cpanel.

### The impact ###

Google indexed the above error message as a content of the site. This means 
not only the wrong index of the content, but also disclosure of the document 
root which potentially gives a chance of "[Full Path Disclosure][FPD-OWASP]" 
to the attackers. Even one week elapsed now, we can get the search results as 
follows:

![Google search results]({{ '/img/2016-04/indexed-result.png' | prepend: site.baseurl }}
 "Google search results"
)

Of course, disclosing the document root never lead to compromize the site 
immediately. And even [the license][LICENSE] declares "NO WARRANTY" loudly, 
I will apologize sincerely to my all users.

### How this happened ###

The code in trouble was as follows:

{% highlight ruby startinline %}
// normalize requested uri (RFC 2616 has been obsoleted by RFC 7230-7237)
// `parse_url()` is not suitable becase of https://bugs.php.net/bug.php?id=55511
// REQUEST_URI starts with path or scheme (https://tools.ietf.org/html/rfc2616#section-5.1.2)
$uri = preg_replace( '!(?://+|/\.+/)!', '/', $_SERVER['REQUEST_URI'] );
$uri = $this->request_uri = substr( $uri, strpos( $uri, self::$wp_dirs['home'] ) + strlen( self::$wp_dirs['home'] ) );
{% endhighlight %}

When the second argument passed to `strpos()` i.e. `self::$wp_dirs['home']` 
was an empty string that is always happened if the WordPress is installed 
into the document root, serious PHP error occured and stopped showing the 
contents. I could not predict this behavior because there's no mention in 
the [PHP manual][STRPOS] about empty string but just says:

> `mixed strpos ( string $haystack , mixed $needle [, int $offset = 0 ] )`
>
> If **needle** is not a string, it is converted to an integer and applied as 
> the ordinal value of a character.

### Why this happened ###

Whenever I release a new version, I follow the procedure as listed below:

1. Check all the functionality on my local environment by a [handmade tool]
[HandmadeTool] (for blocking functionality) and manual procedure (for 
"Activate", "Deactive", "Delete", "Download now", "Clear now", emergency 
recovery and clean uninstall).

2. Check compatibilities with other plugins/themes which had some issues in 
the past depending on the changing points.

3. Run continuously at least for 1 week or more on [my real site][MyRealSite]
and check if no error happens.

You can easily point out the problems of above. One thing is that a variety 
of servers will never be tested. For example, the type of HTTP server (apache 
and nginx are not enough?), the type of multisite (subdomain/subfolder), 
verious versions of PHP and the libraries, using SSL certificate...

And to make matters worse, both my local site and real site are "Sub-directory" 
installed type of WordPress. So speaking about the trouble on 2.2.4, I didn't 
test "Top-directory" type that caused the trouble.

### What should I do? ###

In order to see that it never happens again, I've equiped the followings with 
my application development environment on local PC.

- Multisite of both "Top-directory" and "Sub-directories"
- Verious versions of PHP (purchased MAMP Pro)

And the future plans are:

- Learn about [unit tests][UnitTest] and adopt test driven development
- Make a rule of version control and call for testing beta release

In order to announce "call for beta testing", I will equip an widget on the 
admin dashboard to distribute it. It might be provided with some useful 
information for users such as a summary of statistics and etc.

### Conclusion ###

[@jckuffner][JCKUFFNER] posted at the forum that:

> Perhaps WP needs higher standards and more stringent procedures for plugin 
> updates to keep this from happening.

I completely agree with him. Unit tests is necessary but not enough for a 
various type of servers. I think we definitely need something like [WordPress 
Beta Tester][BetaTester] that can test beta version online and easy to return 
to the stable version if something happens.

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[HandmadeTool]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/master/test "WordPress Post Simulator"
[MyRealSite]:   http://tokkono.cute.coocan.jp/blog/slow/ "Slow…"
[FPD-OWASP]:    https://www.owasp.org/index.php/Full_Path_Disclosure "Full Path Disclosure - OWASP"
[STRPOS]:       http://php.net/manual/en/function.strpos.php "PHP: strpos - Manual"
[LICENSE]:      https://plugins.svn.wordpress.org/ip-geo-block/trunk/LICENSE.txt
[UnitTest]:     http://wp-cli.org/docs/plugin-unit-tests/ "Plugin unit tests | WP-CLI"
[JCKUFFNER]:    https://wordpress.org/support/topic/error-after-update-to-newest-version#post-8299577 "WordPress › Support » Error after Update to newest version"
[BetaTester]:   https://wordpress.org/plugins/wordpress-beta-tester/ "WordPress Beta Tester - WordPress Plugins"
