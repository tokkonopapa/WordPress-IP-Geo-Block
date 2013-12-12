=== Post Geo Block ===
Contributors: tokkonopapa
Tags: comment, spam, geolocation
Requires at least: 3.1
Tested up to: 3.7.1
Stable tag: 0.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that blocks any comments posted from outside your nation.

== Description ==

This plugin will block any comments posted from outside the specified 
countries.

In order to check the county of the posting author by IP address, this plugin 
uses the following IP address Geolocation REST APIs.

* [http://freegeoip.net/][freegeoip]    : free, need no API key.
* [http://ipinfo.io/][ipinfo]           : free, need no API key.
* [http://www.telize.com/][Telize]      : free, need no API key.
* [http://www.geoplugin.com/][geo]      : free, need no API key, need link back.
* [http://www.iptolatlng.com/][IP2LL]   : free, need no API key.
* [http://ip-json.rhcloud.com/][IPJson] : free, need no API key.
* [http://ip-api.com/][ipapi]           : free for non-commercial use, need no API key.
* [http://ipinfodb.com/][IPInfoDB]      : free for registered user, need API key.

Some of these services and APIs include GeoLite data created by [MaxMind][MaxMind].

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Post Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

== Frequently Asked Questions ==

= How can I check this plugin works? =

Check `statistics` tab on this plugin's option page.

== Screenshots ==

1. Post Geo Plugin settings/statistics/search

== Changelog ==

= 0.9.0 =
Pre-release version.

== Upgrade Notice ==

== Arbitrary section ==

[freegeoip]: http://freegeoip.net/
    "freegeoip.net: FREE IP Geolocation Web Service"

[ipinfo]: http://ipinfo.io/
    "ipinfo.io - ip address information including geolocation, hostname and network details"

[Telize]: http://www.telize.com/
    "Telize - JSON IP and GeoIP REST API"

[geo]: http://www.geoplugin.com/
    "geoPlugin to geolocate your visitors"

[IP2LL]: http://www.iptolatlng.com/
    "IP to Latitude, Longitude"

[ipapi]: http://ip-api.com/
    "IP-API.com - Free Geolocation API"

[IPJson]: http://ip-json.rhcloud.com/
    "Free IP Geolocation Web Service"

[IPInfoDB]: http://ipinfodb.com/
    "IPInfoDB | Free IP Address Geolocation Tools"

[MaxMind]: http://www.maxmind.com
    "MaxMind - IP Geolocation and Online Fraud Prevention"
