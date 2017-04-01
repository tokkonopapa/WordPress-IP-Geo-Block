---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-ip2location-zip-ipv6
file: [class-ip2location.php]
---

The URI to IP2Location LITE database for IPv6.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-ip2location-zip-ipv6**" assigns the URI to 
[Free IP2Location LITE database file][IP2LocLITE] for IPv6 which can be 
downloaded by ZIP format.

### Default value ###

{% highlight text %}
http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.IPV6.BIN.ZIP`
{% endhighlight %}

### Use case ###

Currently, this plugin supports only [DB1.LITE][IP2LocDB1] database.

{% include alert-drop-in.html %}

### Since ###

2.2.1

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[IP2LocLITE]:   https://lite.ip2location.com/ "Free IP Geolocation Database"
[IP2LocDB1]:    https://lite.ip2location.com/database-ip-country "Free IP2Location LITE IP-COUNTRY"
