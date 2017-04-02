---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-ip2location-zip-ipv4
file: [class-ip2location.php]
---

The URI to IP2Location LITE database for IPv4.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-ip2location-zip-ipv4**" assigns the URI to 
[Free IP2Location LITE database file][IP2LocLITE] for IPv4 which can be 
downloaded by ZIP format.

### Parameters ###

- $url  
  (string) `http://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP`

{% include alert-drop-in.html %}

### Use case ###

Currently, this plugin supports only [DB1.LITE][IP2LocDB1] database.

### Since ###

2.2.1

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[IP2LocLITE]:   https://lite.ip2location.com/ "Free IP Geolocation Database"
[IP2LocDB1]:    https://lite.ip2location.com/database-ip-country "Free IP2Location LITE IP-COUNTRY"
