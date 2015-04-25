---
layout: post
title:  "Referer Suppressor for external link"
date:   2015-04-25 00:00:00
categories: article
published: true
---

"Referer Suppressor" which eliminate the browser's referer is one of my 
favorite feature of [IP Geo Block][IP-Geo-Block] <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>.

It came to this plugin as a logical consequence of WP-ZEP. In this article, 
I'll tell you the story.

<!--more-->

### A possibility of nonce disclosure ###

A nonce is the secret information which can be known only by a user who 
accessed to a certain page. It is very important to prevent 
<abbr title="Cross Site Request Forgeries">CSRF</abbr> vulnerability.

WP-ZEP will embed a nonce into hyperlinks and forms on admin screen in place 
of vulnerable plugins, and should prevent to disclose it.

One possibility of nonce disclosure lies in a referer string that was left via 
a hyperlink as footprint of your visit to other site. So WP-ZEP should also 
kill this possibility.

### How to suppress a referer? ###

Old school method is using [meta refresh][meta-refresh] that is not part of 
HTTP standard like this:

{% highlight html startinline=true %}
<meta http-equiv="refresh" content="0; url=http://example.com/">
{% endhighlight %}

[Meta referrer][meta-referrer] is a new school:

{% highlight html startinline=true %}
<meta name="referrer" content="no-referrer">
{% endhighlight %}

or

{% highlight html startinline=true %}
<a href="http://example.com" referrer="no-referrer">
{% endhighlight %}

#### Note: ####

The keywords `never`, `default`, `always` are [obsolete][WHATWG-Wiki].

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress &#8250; IP Geo Block &laquo; WordPress Plugins"
[meta-refresh]:  http://en.wikipedia.org/wiki/Meta_refresh "Meta refresh - Wikipedia, the free encyclopedia"
[meta-referrer]: http://w3c.github.io/webappsec/specs/referrer-policy/#referrer-policy-delivery-meta "Referrer Policy - W3C Editor's Draft"
[WHATWG-Wiki]:   https://wiki.whatwg.org/wiki/Meta_referrer "Meta referrer - WHATWG Wiki"
