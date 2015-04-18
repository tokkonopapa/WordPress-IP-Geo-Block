---
layout: post
title:  "How does WP-ZEP prevent zero-day attack?"
date:   2015-04-19 00:00:00
categories: article
---

["IP Geo Block"][IP-Geo-Block] is the only plugin which has an ability to 
prevent zero-day attack even if some of plugins in a WordPress site have 
unveiled vulnerability. I call this ability "**Z**ero-day **E**xploit 
**P**revention for wp-admin" (WP-ZEP).

In this article, I'll explain about its mechanism and also its limitations.
<!--more-->

{% highlight ruby %}
def print_hi(name)
  puts "Hi, #{name}"
end
print_hi('Tom')
#=> prints 'Hi, Tom' to STDOUT.
{% endhighlight %}

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress &#8250; IP Geo Block &laquo; WordPress Plugins"
