Post Geo Block
==============

A WordPress plugin that blocks any comments posted from outside your nation.

### Features:

1. This plugin uses free IP Geolocation REST APIs to get the country code 
from the posting author's IP address.

2. There are two types of API which support only IPv4 or both IPv4 and IPv6. 
This plugin will automatically select an appropriate API.

### Installation:

1. Upload `post-geo-block` directory to your plugins directory.
2. Activate the plugin on the Plugin dashboard.

#### Settings

- **Service provider and API key**  
    If you wish to use `IPInfoDB`, you should register from [here][IPInfoDB]
    to get a free API key and set it into the textfield.

- **Text position on comment form**  
    If you wish to put some text message on your comment form, please select
    `Top` or `Bottom` and put text into the **Text message on comment form**
    textfield.

- **Matching rule**  
    Select `White list` (recommended) or `Black list` to specify the countries
    from which you want to pass or block.

- **White list**, **Black list**  
    Specify the country code with two letters (see [ISO 3166-1 alpha-2][ISO])
    which is comma separated.

- **Response code**  
    Select one of the response code to decide behavior of this plugin when it 
    block a comment. The 2xx code will refresh to your top page, the 3xx code 
    will redirect to another domain, the 4xx code will lead to the WordPress 
    error page, and the 5xx will cause just an error.

- **Remove settings at uninstallation**  
    If you checked this option, all settings will be removed when this plugin
    is uninstalled for clean uninstalling.

### Requirement:

- WordPress 3.1+

### Attribution of IP Geolocation REST APIs:

    Provider                             | Supported type | Licence
    -------------------------------------|----------------|-------------------------------
    [http://freegeoip.net/]      [API-1] | IPv4           | free
    [http://ipinfo.io/]          [API-2] | IPv4           | free
    [http://www.telize.com/]     [API-3] | IPv4, IPv6     | free
    [http://www.geoplugin.com/]  [API-4] | IPv4, IPv6     | free, need an attribution link
    [http://www.iptolatlng.com/] [API-5] | IPv4, IPv6     | free
    [http://ip-api.com/]         [API-6] | IPv4, IPv6     | free for non-commercial use
    [http://ip-json.rhcloud.com/][API-7] | IPv4, IPv6     | free
    [http://ipinfodb.com/]       [API-8] | IPv4           | free for registered user

Some of these services and APIs use GeoLite data created by [MaxMind][MaxMind].

### Notes:

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
- 1.0    Simplify jQuery Google Map plugin.
- 1.1    Cooperation with W3C Geolocation to let a foreigner post a comment.
- 1.2    Send post to Akismet.

#### Change log

- 0.9.4  Add `post-geo-block-validate` hook and `apply_filters()` in order to 
         add another validation function.
- 0.9.3  Change action hook `pre_comment_on_post` to `preprocess_comment`.
         Add attribution links to appreciate providing the services.
- 0.9.2  Add a check of the supported type of IP address not to waste a request.
- 0.9.1  Delete functions for MU, test, debug and ugly comments.
- 0.9.0  Pre-release version.

### License:

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
[IPInfoDB]: http://ipinfodb.com/register.php
[ISO]: http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia"
[RFC]: http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1"
