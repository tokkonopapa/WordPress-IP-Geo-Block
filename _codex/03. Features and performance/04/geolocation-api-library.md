---
layout: page
category: codex
section: Features and performance
title: Geolocation API library
excerpt: Geolocation API library
---

[IP Geo Block][IP-Geo-Block] has multi source of geolocation database not only 
via internet but also on site DBs that are distributed from [Maxmind][Maxmind] 
and [IP2location][IP2location]. These on site DBs are managed by the dedicated 
class libraries named [IP Geo API][GitGeoAPI] which has been separately 
developed as an another project.

### Location of IP Geo API ###

IP Geo API can be installed one of the following directories:

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

The location depends on the permission of your WordPress tree. Actual location 
can be found at "**Local database settings**" section. If you find it to be the
3rd one, please consider to change to another location. Because at every time 
this plugin updates, files in it will be removed.

![Local database settings]({{ '/img/2017-03/LocalDatabaseSettings.png' | prepend: site.baseurl }}
 "Local database settings"
)

In some cases, you might have [a permission touble][Permission] because of your 
server's security configurations. In this case, you have to upload the library 
by your own hand.

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

### Type of database ###

This library provides the functionality of retrieving a country code from an 
IP address. You can get only a country code by default. But when you switch 
the type of database to another, you will be able to get the city name, 
coodinates of longitude and latitude.

Please refer to
[ip-geo-block-maxmind-zip-ipv4][MaxmindIPv4] and 
[ip-geo-block-maxmind-zip-ipv6][MaxmindIPv6] to know how to change the source 
of databases.

### CloudFlare & CloudFront API class library ###

If you are a user of [CloudFlare][CloudFlare] or [CloudFront][CloudFront] as a 
reverse proxy service, you might want to make use of the country code in their 
special offered environment variables.

Yes, you can do that if you install additional API library for those service.
Please refer to [CloudFlare & CloudFront API class library][APILibrary].

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[Permission]:   {{ '/codex/how-to-fix-permission-troubles.html' | prepend: site.baseurl }} "How can I fix permission troubles? | IP Geo Block"
[MaxmindIPv4]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv4.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv4 | IP Geo Block"
[MaxmindIPv6]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv6.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv6 | IP Geo Block"
[APILibrary]:   {{ '/article/api-class-library.html'            | prepend: site.baseurl }} "CloudFlare & CloudFront API class library | IP Geo Block"
[CloudFlare]:   https://www.cloudflare.com/ "Cloudflare - The Web Performance & Security Company | Cloudflare"
[CloudFront]:   https://aws.amazon.com/cloudfront/ "Amazon CloudFront – Content Delivery Network (CDN)"

