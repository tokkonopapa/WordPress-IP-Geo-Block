---
layout: page
language: en
category: codex
section: Dashboard
title: Local database settings
---

This plugin has multiple IP address geolocation databases distributed by 
[Maxmind][Maxmind] and [IP2location][IP2location]. Utilizing multiple data 
sources is an important mechanism that can complement each other when data 
is missing. These databases are managed by the Geolocation API library named 
[IP Geo API][GitGeoAPI] which which has been separately developed as an another
project.

### Geolocation API Library ###

IP Geo API can be installed with the geolocation databases in one of the 
following directories:

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

The actual storage location depends on the permission setting of the WordPress 
tree. If you find it’s 3. then it is necessary to adjust the permissions so 
that it becomes 1 or 2. to prevent the geolocation databases being removed on 
updating this plugin.

![Local database settings]({{ '/img/2017-03/LocalDatabaseSettings.png' | prepend: site.baseurl }}
 "Local database settings"
)

In some cases, you might see the following error message right after your first
installation. This would be caused by a permission touble due to your server's 
security configurations.

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

In this case, you have to install [IP Geo API][GitGeoAPI] by your own hand and 
once deactivate this plugin then activate it again. Please find how to do it 
in the codex "[How can I fix permission troubles?][Permission]".

### Type of geolocation database ###

In the location information database downloaded by default, only the IP address
and the corresponding country code are stored. But when you switch the type of 
database to another, you will be able to get the city name, coodinates of 
longitude and latitude.

Please refer to
[ip-geo-block-maxmind-zip-ipv4][MaxmindIPv4] and 
[ip-geo-block-maxmind-zip-ipv6][MaxmindIPv6] to know how to change the source 
of databases.

### CloudFlare & CloudFront API library ###

If you are using a reverse proxy or load balancing service provided by 
[CloudFlare][CloudFlare] or [CloudFront][CloudFront], you can obtain the 
country code of the access source through special environment variables.

To use this, it is necessary to install a dedicated API library. Please 
refer to [CloudFlare & CloudFront API class library][APILibrary].

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  https://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[Permission]:   {{ '/codex/how-to-fix-permission-troubles.html' | prepend: site.baseurl }} "How can I fix permission troubles? | IP Geo Block"
[MaxmindIPv4]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv4.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv4 | IP Geo Block"
[MaxmindIPv6]:  {{ '/codex/ip-geo-block-maxmind-zip-ipv6.html'  | prepend: site.baseurl }} "ip-geo-block-maxmind-zip-ipv6 | IP Geo Block"
[APILibrary]:   {{ '/article/api-class-library.html'            | prepend: site.baseurl }} "CloudFlare & CloudFront API class library | IP Geo Block"
[CloudFlare]:   https://www.cloudflare.com/ "Cloudflare - The Web Performance & Security Company | Cloudflare"
[CloudFront]:   https://aws.amazon.com/cloudfront/ "Amazon CloudFront – Content Delivery Network (CDN)"

