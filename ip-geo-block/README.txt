=== IP Geo Block ===
Contributors: tokkonopapa
Donate link:
Tags: comment, pingback, spam, IP address, geolocation, xmlrpc
Requires at least: 3.7
Tested up to: 4.0.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that will block any comment and pingback spams posted from 
outside of your nation.

== Description ==

This plugin will examine a country code based on the IP address. If the 
comment or pingback comes from specific country, it will be blocked before 
Akismet validate it.

= Features =

1. Access to the `wp-comments-post.php` and `xmlrpc.php` will be validated by 
means of IP address. Free IP Geolocation database and REST APIs are installed 
in this plugin to get a country code from an IP address. There are two types 
of API which support only IPv4 or both IPv4 and IPv6. This plugin will 
automatically select an appropriate API.

2. Cache mechanism with transient API for the fetched IP addresses has been 
equipped to reduce load on the server against the burst accesses with a short 
period of time.

3. Validation logs will be recorded into MySQL data table to analyze posting 
pattern under the specified condition.

4. Custom validation function can be added by `add_filter()` with predefined 
filter hook.

5. [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention") 
GeoLite free database for IPv4 and IPv6 will be downloaded and updated 
(once a month) automatically. And if you have correctly installed 
one of the IP2Location plugins (
    [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"),
    [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"),
    [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins")
), this plugin uses its local database prior to the REST APIs.

= Development =

Development of this plugin is promoted on 
    [GitHub](https://github.com/tokkonopapa/WordPress-IP-Geo-Block "tokkonopapa/WordPress-IP-Geo-Block - GitHub").
All contributions will always be welcome.

= Attribution =

This package includes GeoLite data created by MaxMind, available from 
    [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"),
and also includes IP2Location open source libraries available from 
    [IP2Location](http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location").

And also thanks for providing these great services and REST APIs for free.

* [http://freegeoip.net/](http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service") (IPv4 / free)
* [http://ipinfo.io/](http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details") (IPv4, IPv6 / free)
* [http://www.telize.com/](http://www.telize.com/ "Telize - JSON IP and GeoIP REST API") (IPv4, IPv6 / free)
* [http://ip-json.rhcloud.com/](http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service") (IPv4, IPv6 / free)
* [http://xhanch.com/](http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API &#8211; IP Get Detail | Xhanch Studio") (IPv4 / free)
* [http://www.geoplugin.com/](http://www.geoplugin.com/ "geoPlugin to geolocate your visitors") (IPv4, IPv6 / free, need an attribution link)
* [http://ip-api.com/](http://ip-api.com/ "IP-API.com - Free Geolocation API") (IPv4, IPv6 / free for non-commercial use)
* [http://ipinfodb.com/](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") (IPv4, IPv6 / free for registered user, need API key)

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'IP Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Settings =

* **Service provider and API key**  
    If you wish to use `IPInfoDB`, you should register from 
    [their site](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") 
    to get a free API key and set it into the textfield.
    And `ip-api.com` and `Smart-IP.net` require non-commercial use.

* **Validation settings**  
    `XML-RPC` is for validation of pingback spam. Additional IP addresses will 
    be validated if some of keys for `$_SERVER` variable are specified in 
    `$_SERVER keys for extra IPs`.

* **Text position on comment form**  
    If you want to put some text message on your comment form, please select
    `Top` or `Bottom` and put text into the **Text message on comment form**
    textfield.

* **Matching rule**  
    Select `White list` (recommended) or `Black list` to specify the countries
    from which you want to pass or block.

* **White list**, **Black list**  
    Specify the country code with two letters (see 
    [ISO 3166-1 alpha-2](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia")
    ). Each of them should be separated by comma.

* **Response code**  
    Select one of the 
    [response code](http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1")
    to be sent when it blocks a comment.
    The 2xx code will lead to your top page, the 3xx code will redirect to 
    [Black Hole Server](http://blackhole.webpagetest.org/),
    the 4xx code will lead to WordPress error page, and the 5xx will pretend 
    an server error.

* **Remove settings at uninstallation**  
    If you checked this option, all settings will be removed when this plugin
    is uninstalled for clean uninstalling.

== Frequently Asked Questions ==

= What is this plugin for? =

It's for blocking spam comments. If you can not specify countries with white 
list or black list to protect your site against undesired access, you should 
choose other awesome plugins.

= How can I check this plugin works? =

Check `statistics` tab on this plugin's option page.

= How can I test on the local site? =

There are two ways. One is to add some code somewhere in your php (typically 
`functions.php` in your theme) to substitute local IP address through filter 
fook `ip-geo-block-ip-addr` as follows:

`function my_replace_ip( $ip ) {
    return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );`

Another method is adding a country code into `White list` or `Black list` on 
the plugin settings page. Most of the IP Geolocation services return empty 
(with some status) if a local IP address (e.g. 127.0.0.0) is sent, but only 
`freegeoip.net` returns `RD`.

= Can I add an additional validation function into this plugin? =

Yes, you can use `add_filter()` with filter hook `ip-geo-block-comment` in 
somewhere (typically `functions.php` in your theme) as follows:

`function my_blacklist( $validate ) {
    $blacklist = array(
        '123.456.789.',
    );

    foreach ( $blacklist as $ip ) {
        if ( strpos( $ip, $validate['ip'] ) === 0 ) {
            $validate['result'] = 'blocked';
            break;
        }
    }

    return $validate;
}
add_filter( 'ip-geo-block-comment', 'my_blacklist' );`

= Can I change user agent strings when fetching services? =

Yes. The default is something like `Wordpress/4.0; ip-geo-block 1.2.0`.
You can change it as follows:

`function my_user_agent( $args ) {
    $args['user-agent'] = 'my user agent strings';
    return $args;
}
add_filter( 'ip-geo-block-headers', 'my_user_agent' );`

= Are there any other filter hooks? =

Yes, here is the list of all hooks.

* `ip-geo-block-ip-addr`          : IP address of accessor.
* `ip-geo-block-headers`          : compose http request headers.
* `ip-geo-block-comment`          : validate IP adress at `wp-comments-post.php`.
* `ip-geo-block-xmlrpc`           : validate IP adress at `xmlrpc.php`.
* `ip-geo-block-maxmind-dir`      : absolute path where Maxmind GeoLite DB files should be saved.
* `ip-geo-block-maxmind-zip-ipv4` : url to Maxmind GeoLite DB zip file for IPv4.
* `ip-geo-block-maxmind-zip-ipv6` : url to Maxmind GeoLite DB zip file for IPv6.
* `ip-geo-block-ip2location-path` : absolute path to IP2Location LITE DB file.

For more details, see `samples.php` bundled within this package.

== Other Notes ==

After installing these IP2Location plugins, you should be once deactivated 
and then activated in order to set the path to `database.bin`.

If you do not want to keep the IP2Location plugins (
    [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"),
    [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"),
    [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins")
) in `wp-content/plugins/` directory but just want to use its database, 
you can rename it to `ip2location` and upload it to `wp-content/`.

== Screenshots ==

1. **IP Geo Plugin** - Settings.
2. **IP Geo Plugin** - Statistics.
3. **IP Geo Plugin** - Logs.
4. **IP Geo Plugin** - Search.
5. **IP Geo Plugin** - Attribution.

== Changelog ==

= 1.4.0 =
* **New feature:** Added a new class for recording the validation logs to 
  analyze posting pattern.
* Added `$_SERVER keys for extra IPs` into options to validate additional 
  IP addresses.
* Removed some redundant codes and corrected some PHP errors and warnings.

= 1.3.0 =
* **New feature:** Added validation of pingback.ping through `xmlrpc.php` and
  new option to validate all the IP addresses in HTTP_X_FORWARDED_FOR.
* **Fixed an issue:** Maxmind database file may be downloaded automatically
  without deactivate/re-activate when upgrade is finished.
* This is the final version on 1.x. On next release, accesses to `login.php`
  and admin area will be also validated for security purpose.

= 1.2.1 =
* **Fixed an issue:** Option table will be updated automatically without
  deactivate/re-activate when this plugin is upgraded.
* **A little bit performance improvement:**
  Less memory footprint at the time of downloading Maxmind database file.
  Less sql queries when `Save statistics` is enabled.

= 1.2.0 =
* **New feature:** Added Maxmind GeoLite database auto downloader and updater.
* The filter hook `ip-geo-block-validate` was discontinued.
  Instead of it, the new filter hook `ip-geo-block-comment` is introduced.
* **Performance improvement:** IP address is verified at an earlier stage 
  than before.
* **Others:** Fix a bug of handling cache, update status of some REST APIs.

= 1.1.1 =
* Fixed issue of default country code.
  When activating this plugin for the first time, get the country code 
  from admin's IP address and set it into white list.
* Add number of calls in cache of IP address.

= 1.1.0 =
* Implement the cache mechanism to reduce load on the server.
* Better handling of errors on the search tab so as to facilitate the 
  analysis of the service problems.
* Fixed a bug of setting user agent strings in 1.0.2.
  Now the user agent strings (`WordPress/3.9.2; http://example.com/`) 
  becomes to its own (`WordPress/3.9.2; ip-geo-block 1.1.0`).

= 1.0.3 =
* Temporarily stop setting user agent strings to supress a bug in 1.0.2.

= 1.0.2 =
* Update provider settings. Smart-IP.net was terminated, ipinfo.io is now
  available for IPv6.
* Set the own user agent strings for `WP_Http`.

= 1.0.1 =
* Modify Plugin URL.
* Add `apply_filters()` to be able to change headers.

= 1.0.0 =
* Ready to release.

== Upgrade Notice ==
