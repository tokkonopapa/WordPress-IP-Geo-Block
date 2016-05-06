---
layout: post
title: "Confession of the problem in 2.2.4"
date: 2016-04-27 00:00:00
categories: article
published: true
script: []
inline:
---

I must make a confession that the release 2.2.4 caused a big problem which 
stopped the sites of my plugin's users. In this article, I will report why 
this problem was caused and also how I should improve my developing process 
in order to prevent this kind of undesired accident again.

<!--more-->

### The problem ###

When you upgraded to 2.2.4 of [IP Geo Block][IP-Geo-Block], you encountered 
the following error:

{% highlight text %}
Warning: strpos(): Empty needle in /public_html/wp-content/plugins/ip-geo-block/classes/class-ip-geo-block.php on line 71.
{% endhighlight %}

The message said just "Warning" but actually this was "Fatal Error" which broke
the site. And to recover the site, users should delete IGB manually through the
FTP or cPanel file manager.

{% comment %}
### The impact ###

Google indexed the above error message as a content of the site. This means 
it was not only the wrong index of the content, but also disclosing the path 
of document root which potentially gave a chance of "[Full Path Disclosure]
[FPD-OWASP]" to attackers. Even a few weeks elapsed since then, the following 
results could be found.

![Google search results]({{ '/img/2016-05/indexed-result.png' | prepend: site.baseurl }}
 "Google search results"
)

Of course, disclosing the document root never lead to compromize the site 
immediately. And even [the license][LICENSE] declares "NO WARRANTY" loudly, 
I will apologize sincerely to my all users.
{% endcomment %}

### How was it caused? ###

The code in problem was as follows:

{% highlight ruby startinline %}
// normalize requested uri (RFC 2616 has been obsoleted by RFC 7230-7237)
// `parse_url()` is not suitable becase of https://bugs.php.net/bug.php?id=55511
// REQUEST_URI starts with path or scheme (https://tools.ietf.org/html/rfc2616#section-5.1.2)
$uri = preg_replace( '!(?://+|/\.+/)!', '/', $_SERVER['REQUEST_URI'] );
$uri = $this->request_uri = substr( $uri, strpos( $uri, self::$wp_dirs['home'] ) + strlen( self::$wp_dirs['home'] ) );
{% endhighlight %}

When the second argument `self::$wp_dirs['home']` passed to `strpos()` was an 
empty string, serious error occured and stopped showing the contents. This 
always happened if the WordPress is installed into the document root. I could 
not predict this error because there's no mention in the [PHP manual][STRPOS]
which just says:

> `mixed strpos ( string $haystack , mixed $needle [, int $offset = 0 ] )`
>
> If **needle** is not a string, it is converted to an integer and applied as 
> the ordinal value of a character.

### Why was it caused? ###

Whenever I release a new version, I follow the procedure as listed below:

1. Check all the functionality on my local environment by a [handmade tool]
[HandmadeTool] (for blocking functionality) and manual procedure (for 
"Activate", "Deactive", "Delete", "Download now", "Clear now", clean 
uninstall, [emergency recovery][EMERGENCY] and so on).

2. Check compatibilities with other plugins and themes which had some issues 
in the past depending on the changing points.

3. Run continuously at least for 1 week or more on [my real site][MyRealSite]
and check if no error happens.

You can easily point out that those are not enough. One thing is that a variety
of servers will never be tested. For example, the type of HTTP server (apache 
and nginx are not enough?), the type of multisite (sub-domain/sub-directories),
verious versions of PHP and the libraries, adopting SSL certificate, etc...

And to make matters worse, both my local and real site are "sub-directories" 
type of WordPress. So as for the problem in 2.2.4, I had never tested 
"top-directory" type that caused a serious error.

### What should I do? ###

In order to see that it never happens again, I've built up the application 
development environment on my local PC as follows:

- Multisite of both "top-directory" and "sub-directories"
- Multisite of "sub-domain" by Virtual Host
- SSL by [self-signed certificate][SELF-SSL]
- Verious versions of PHP

In order to make those setup easy, I purchased [MAMP Pro][MAMP-PRO] license.

![Sub-domain type of multisite]({{ '/img/2016-04/multisite.png' | prepend: site.baseurl }}
 "Sub-domain type of multisite"
)

And my future plans are:

- Learn about [unit tests][UnitTest] and adopt test driven development
- Make a rule of version control and call for testing beta / release candidate

In order to announce "call for testing", I will equip an widget on the admin 
dashboard to distribute it. It might be provided with some useful information 
for users such as a summary of statistics and etc.

### Conclusion ###

[@jckuffner][JCKUFFNER] posted at the forum that:

> Perhaps WP needs higher standards and more stringent procedures for plugin 
> updates to keep this from happening.

I completely agree with him. Unit tests is necessary but not enough for testing
a various type of servers. I think we definitely need something like [WordPress
Beta Tester][BetaTester] that can test developing version online and easy to 
return to the stable version if something happens <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f60e.png)
</span> .

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[HandmadeTool]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/master/test "WordPress Post Simulator"
[EMERGENCY]:    http://www.ipgeoblock.com/codex/what-should-i-do-when-i-m-locked-out.html "What should I do when I'm locked out? | IP Geo Block"
[MyRealSite]:   http://tokkono.cute.coocan.jp/blog/slow/ "Slow…"
[FPD-OWASP]:    https://www.owasp.org/index.php/Full_Path_Disclosure "Full Path Disclosure - OWASP"
[STRPOS]:       http://php.net/manual/en/function.strpos.php "PHP: strpos - Manual"
[SELF-SSL]:     https://en.wikipedia.org/wiki/Self-signed_certificate "Self-signed certificate - Wikipedia, the free encyclopedia"
[MAMP-PRO]:     https://www.mamp.info/en/mamp-pro/ "MAMP & MAMP PRO - One PC - multiple Servers"
[LICENSE]:      https://plugins.svn.wordpress.org/ip-geo-block/trunk/LICENSE.txt
[UnitTest]:     http://wp-cli.org/docs/plugin-unit-tests/ "Plugin unit tests | WP-CLI"
[JCKUFFNER]:    https://wordpress.org/support/topic/error-after-update-to-newest-version#post-8299577 "WordPress › Support » Error after Update to newest version"
[BetaTester]:   https://wordpress.org/plugins/wordpress-beta-tester/ "WordPress Beta Tester - WordPress Plugins"
