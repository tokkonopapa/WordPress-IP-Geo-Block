---
layout: page
language: en
category: codex
section: Dashboard
title: Geolocation API settings
---

In this section, you can configure the Geolocation API to get the country code 
corresponding to the IP address. There are two types of APIs: a type that uses 
Geolocation databases downloaded to the own server and a type that hits an 
external REST API.

Please check the license and Terms & Use of each API.

<!--more-->

### API selection and key settings ###

The following API is a type that downloads the location information database 
to your server.

- [GeoLite2][GeoLite2]  
It's a database released by [MaxMind][MaxMind] for free. In this plugin, it is
written as __Geolite2__.

- [GeoLite Legacy][GeoLegacy]  
It's a database released by [MaxMind][MaxMind] for free. In order to migrate
to GeoLite2, it stops updating from March 2018 and will become impossible to
download after January 1, 2019. In this plugin, it is written as __Maxmind__.

- [IP2Location Lite][IP2Lite]  
It's a database released by [IP2Location][IP2Location] for free.
In this plugin, it is written as __IP2Location__.

![Geolocation API settings]({{ '/img/2018-09/GeolocationAPIs.png' | prepend: site.baseurl }}
 "Geolocation API settings"
)

In addition, the followings provide the REST API for free. For each service,
there are some restrictions on the number of calls per day and some require 
registration for API keys. Please check the terms of service of each service.

- [ipinfo.io][IpinfoIO]
- [Nekudo][Nekudo]
- [GeoIPLookup][GeoIPLookup]
- [ip-api.com][ip-api]
- [Ipdata.co][Ipdata]
- [ipstack][ipstack]
- [IPInfoDB][IPInfoDB]

<div class="alert alert-info">
When "<strong>Do not send IP address to external APIs</strong>" in "<strong>
Privacy and record settings</strong>" is enabled, those types are not 
selectable. But you can use "<strong>Search IP address geolocation</strong>".
In this case, an IP address is automatically anonymized before sending to the 
REST API.
<p><img src="/img/2018-09/SearchGeolocation.png" alt="Search IP address geolocation" /></p>
</div>

### See also ###

- [Geolocation API library][GeoAPILib]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[GeoAPILib]:    {{ '/codex/geolocation-api-library.html'               | prepend: site.baseurl }} "Geolocation API library | IP Geo Block"
[MaxMind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[GeoLite2]:     https://dev.maxmind.com/geoip/geoip2/geolite2/ "GeoLite2 Free Downloadable Databases &laquo; MaxMind Developer Site"
[GeoLegacy]:    https://dev.maxmind.com/geoip/legacy/geolite/ "GeoLite Legacy Downloadable Databases &laquo; MaxMind Developer Site"
[IP2Location]:  https://www.ip2location.com/ "IP Address to Identify Geolocation Information"
[IP2Lite]:      https://lite.ip2location.com/ "Free IP Geolocation Database | IP2Location LITE"
[IpinfoIO]:     https://ipinfo.io/ "IP Address API and Data Solutions - geolocation, company, carrier info, type and more - ipinfo.io"
[Nekudo]:       https://geoip.nekudo.com/ "Free IP GeoLocation/GeoIp API - geoip.nekudo.com"
[GeoIPLookup]:  http://geoiplookup.net/ "What Is My IP Address | Geo IP Lookup"
[ip-api]:       http://ip-api.com/ "IP-API.com - Free Geolocation API"
[Ipdata]:       https://ipdata.co/ "ipdata - Free IP Geolocation API"
[ipstack]:      https://ipstack.com/ "ipstack - Free IP Geolocation API"
[IPInfoDB]:     https://ipinfodb.com/ "Free IP Geolocation Tools and API| IPInfoDB"
