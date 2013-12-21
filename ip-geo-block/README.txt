=== IP Geo Block ===
Contributors: tokkonopapa
Tags: comment, spam, geolocation
Requires at least: 3.1
Tested up to: 3.7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that blocks any comments posted from outside your nation.

== Description ==

This plugin will block any comments posted from outside the specified countries.

In order to check the county of the posting author by IP address, this plugin 
uses the following IP address Geolocation REST APIs.

* [http://freegeoip.net/][freegeoip]    : free
* [http://ipinfo.io/][ipinfo]           : free
* [http://www.telize.com/][Telize]      : free
* [http://www.iptolatlng.com/][IP2LL]   : free
* [http://ip-json.rhcloud.com/][IPJson] : free
* [http://xhanch.com/][Xhanch]          : free
* [http://mshd.net/][mshd]              : free
* [http://www.geoplugin.com/][geoplugin]: free, need an attribution link
* [http://ip-api.com/][ipapi]           : free for non-commercial use
* [http://smart-ip.net/][smartip]       : free for personal and non-commercial use
* [http://ipinfodb.com/][IPInfoDB]      : free for registered user, need API key

Some of these services and APIs include GeoLite data created by [MaxMind][MaxMind].

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'IP Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

== Frequently Asked Questions ==

= What is this plugin for? =

It's for blocking spam comments. If you can not specify countries with white 
list or black list to protect your site against spam comments, you should 
choose other awesome plugins.

= How can I check this plugin works? =

Check `statistics` tab on this plugin's option page.

= How can I test on the local site? =

Well, most of all the IP Geolocation services return empty (with some status) 
if a local IP address (e.g. 127.0.0.0) is sent, but freegeoip.net returns `RD` 
for country code. So you can add `RD` into `White list` or `Black list` on the 
plugin settings page for test purpose.

= Can I add an additional spam validation function into this plugin? =

Yes, you can use `add_filter()` with filter hook `ip-geo-block-validate` in 
somewhere (typically `functions.php`) as follows:

    function your_validation( $commentdata ) {
        // your validation code here
        ...;

        if ( ... /* if your validation fails */ ) {
            // tell the plugin this comment should be blocked!!
            $commentdata['ip-geo-block']['result'] = 'blocked';
        }

        return $commentdata;
    }
    add_filter( 'ip-geo-block-validate', 'your_validation' );

Then you can find `ZZ` as a country code in the list of `Blocked by countries` 
on the `statistics` tab of this plugin's option page.

== Screenshots ==

1. IP Geo Plugin settings/statistics/search

== Changelog ==

= 1.0.0 =
* Change all class names and file names.
* Simplify jQuery Google Map plugin.
* Add some providers.
* Add `ip-geo-block-addr` for testing.
* Add `enables` to option table for the future usage.

= 0.9.5 =
* Fix garbage characters of `get_country()` for ipinfo.io.

= 0.9.4 =
* Add `ip-geo-block-validate` hook and `apply_filters()` in order to add
  another validation function.

= 0.9.3 =
* Change action hook `pre_comment_on_post` to `preprocess_comment`.
* Add attribution links to appreciate providing the services. 

= 0.9.2 =
* Add a check of the supported type of IP address not to waste a request.

= 0.9.1 =
* Delete functions for MU, test, debug and ugly comments.

= 0.9.0 =
* Pre-release version.

== Upgrade Notice ==

== Arbitrary section ==

[freegeoip]: http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service"
[ipinfo]:    http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details"
[Telize]:    http://www.telize.com/ "Telize - JSON IP and GeoIP REST API"
[IP2LL]:     http://www.iptolatlng.com/ "IP to Latitude, Longitude"
[IPJson]:    http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service"
[Xhanch]:    http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API &#8211; IP Get Detail | Xhanch Studio"
[mshd]:      http://mshd.net/documentation/geoip "www.mshd.net - Geoip Documentation"
[geoplugin]: http://www.geoplugin.com/ "geoPlugin to geolocate your visitors"
[ipapi]:     http://ip-api.com/ "IP-API.com - Free Geolocation API"
[smartip]:   http://smart-ip.net/geoip-api "Geo-IP API Documentation"
[IPInfoDB]:  http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools"
[MaxMind]:   http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"
