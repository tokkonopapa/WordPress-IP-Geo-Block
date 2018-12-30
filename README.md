IP Geo Block
==============

### Description:

It blocks any spams, login attempts and malicious access to the admin area 
posted from the specific countries, and also prevents zero-day exploit.

### Stable:

[IP Geo Block in WordPress.org][IPGB]

### Dependency:

[IP Geo API 1.1.15][IPGeoAPI]

### Requirement:

- WordPress 3.7+

### Attribution:

This package includes GeoLite2 data created by MaxMind, available from 
    [MaxMind][MaxMind] (it requires PHP 5.4.0+),
and also includes IP2Location open source libraries available from 
    [IP2Location][IP2Loc].

Also thanks for providing the following great services and REST APIs for free.

    Provider                                | Supported type | Licence
    ----------------------------------------|----------------|--------
    [http://ip-api.com/]        [ipapi-com] | IPv4, IPv6     | free for non-commercial use
    [http://geoiplookup.net/]   [geoiplkup] | IPv4, IPv6     | free
    [https://ipinfo.io/]           [ipinfo] | IPv4, IPv6     | free
    [https://ipapi.com/]            [ipapi] | IPv4, IPv6     | free for registered user, 10,000 requests/month
    [https://ipdata.co/]           [Ipdata] | IPv4, IPv6     | free for registered user,  1,500 requests/day
    [https://ipstack.com/]        [ipstack] | IPv4, IPv6     | free for registered user, 10,000 requests/month
    [https://ipinfodb.com/]      [IPInfoDB] | IPv4, IPv6     | free for registered user
    [https://www.geoplugin.com/][geoplugin] | IPv4, IPv6     | free, need an attribution link

### License:

This plugin is licensed under the GPL v3.

[IPGB]:       https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block — WordPress Plugins"
[ipapi-com]:  http://ip-api.com/ "IP-API.com - Free Geolocation API"
[geoiplkup]:  http://geoiplookup.net/ "What Is My IP Address | GeoIP Lookup"
[ipinfo]:     https://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details"
[ipapi]:      https://ipapi.com/ "ipapi - IP Address Lookup and Geolocation API"
[Ipdata]:     https://ipdata.co/ "ipdata.co - IP Geolocation and Threat Data API"
[ipstack]:    https://ipstack.com/ "ipstack - Free IP Geolocation API"
[IPInfoDB]:   https://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools"
[geoplugin]:  https://www.geoplugin.com/ "geoPlugin to geolocate your visitors"
[MaxMind]:    https://www.maxmind.com/ "MaxMind - IP Geolocation and Online Fraud Prevention"
[IP2Loc]:     https://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[IPGeoAPI]:   https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
