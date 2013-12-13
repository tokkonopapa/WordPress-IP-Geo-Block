# Post Geo Block

A WordPress plugin that blocks any comments posted from outside your nation.

### Features

1. This plugin uses free IP Geolocation REST APIs to get the country code 
from the posting author's IP address.

2. There are two types of API which support only IPv4 or both IPv4 and IPv6. 
This plugin will automatically select an appropriate API.

#### Attribution of IP Geolocation REST APIs used in this plugin

- [http://freegeoip.net/]      [API-1] (IPv4)
- [http://ipinfo.io/]          [API-2] (IPv4)
- [http://www.telize.com/]     [API-3] (IPv4, IPv6)
- [http://www.geoplugin.com/]  [API-4] (IPv4, IPv6)
- [http://www.iptolatlng.com/] [API-5] (IPv4, IPv6)
- [http://ip-api.com/]         [API-6] (IPv4, IPv6)
- [http://ip-json.rhcloud.com/][API-7] (IPv4, IPv6)
- [http://ipinfodb.com/]       [API-8] (IPv4)

Some of these services and APIs use GeoLite data created by [MaxMind][MaxMind].

### Notes

#### Milestones

- 0.1    Define Geolocation abstract class and child class.
- 0.2    Implement ajax.
- 0.3    Insert text message into comment form.
- 0.4    Make a selection of response header or redirection to black hole server.
- 0.5    Handle IPv6, timeout, and correspondence of service down.
- 0.6    Recording statistics and show them on the dashboard.
- 0.7    Refine data format into DB and form on the dashboard.
- 0.8    Localization.
- 0.9    Remove unneeded functions and comments.
- 1.0    Cooperation with W3C Geolocation.
- 1.1    Send post to Akismet.

#### Change log

- 0.9.2  Add a check of the supported type of IP address not to waste a request.
- 0.9.1  Delete functions for MU, test, debug and ugly comments.
- 0.9.0  Pre-release version.

### License

This plugin is licensed under the GPL v2 or later.

[API-1]: http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service"
[API-2]: http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details"
[API-3]: http://www.telize.com/ "Telize - JSON IP and GeoIP REST API"
[API-4]: http://www.geoplugin.com/ "geoPlugin to geolocate your visitors"
[API-5]: http://www.iptolatlng.com/ "IP to Latitude, Longitude"
[API-6]: http://ip-api.com/ "IP-API.com - Free Geolocation API"
[API-7]: http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service"
[API-8]: http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools"
[MaxMind]: http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"
