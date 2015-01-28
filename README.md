IP Geo Block
==============

A WordPress plugin that will blocks any comment, pingback and trackback spams 
posted from outside your nation. And it will also protect against malicious 
access to the login form, admin area and XML-RPC from undesired countries.

This plugin will examine a country code based on the IP address.If a comment, 
pingback or trackback comes from specific country, it will be blocked before 
Akismet validate it.

With the same mechanism, it will fight against burst access of brute-force 
and reverse-brute-force attacks to the login form, admin area and XML-RPC.

### Features:

1. Access to the basic and important entrances such as `wp-comments-post.php`, 
`xmlrpc.php`, `wp-login.php`, `wp-admin/admin.php`, `wp-admin/admin-ajax.php` 
will be validated by means of a country code based on IP address. 

2. Free IP Geolocation database and REST APIs are installed into this plugin 
to get a country code from an IP address. There are two types of API which 
support only IPv4 or both IPv4 and IPv6. This plugin will automatically select 
an appropriate API.

3. In order to prevent the authentication through the login form and XML-RPC 
against the brute-force and the reverse-brute-force attacks, the number of 
login attempts will be limited per IP address.

4. A cache mechanism with transient API for the fetched IP addresses has been 
equipped to reduce load on the server against the burst accesses with a short 
period of time.

5. Validation logs will be recorded into MySQL data table to analyze posting 
pattern under the specified condition.

6. Custom validation function can be added by `add_filter()` with predefined 
filter hook.

7. [MaxMind][MaxMind] GeoLite free database for IPv4 and IPv6 will be 
downloaded and updated (once a month) automatically. And if you have 
correctly installed one of the IP2Location plugins (
    [IP2Location Tags][IP2Tag],
    [IP2Location Variables][IP2Var],
    [IP2Location Country Blocker][IP2Blk]
), this plugin uses its local database prior to the REST APIs.

8. This can be combined with other full spec security plugin such as 
    [Wordfence Security][wordfence]
(because the function of country bloking is available only for premium users).

### Installation:

1. Upload `ip-geo-block` directory to your plugins directory.
2. Activate the plugin on the Plugin dashboard.

#### Settings

- **Service provider and API key**  
    If you wish to use `IPInfoDB`, you should register from [here][register]
    to get a free API key and set it into the textfield.
    And `ip-api.com` and `Smart-IP.net` require non-commercial use.

- **Validation settings**  
    `XML-RPC` is for validation of pingback spam. Additional IP addresses will 
    be validated if some of keys for `$_SERVER` variable are specified in 
    `$_SERVER keys for extra IPs`.

- **Text position on comment form**  
    If you want to put some text message on your comment form, please select
    `Top` or `Bottom` and put text into the **Text message on comment form**
    textfield.

- **Matching rule**  
    Select `White list` (recommended) or `Black list` to specify the countries
    from which you want to pass or block.

- **White list**, **Black list**  
    Specify the country code with two letters (see [ISO 3166-1 alpha-2][ISO]).
    Each of them should be separated by comma.

- **Response code**  
    Select one of the [response code][RFC] to be sent when it blocks a comment.
    The 2xx code will lead to your top page, the 3xx code will redirect to
    [Black Hole Server][BHS], the 4xx code will lead to WordPress error page, 
    and the 5xx will pretend an server error.

- **Remove settings at uninstallation**  
    If you checked this option, all settings will be removed when this plugin
    is uninstalled for clean uninstalling.

### Requirement:

- WordPress 3.7+

### Attribution:

This package includes GeoLite data created by MaxMind, available from 
    [MaxMind][MaxMind],
and also includes IP2Location open source libraries available from 
    [IP2Location][IP2Loc].

And also thanks for providing these great services and REST APIs for free.

    Provider                               | Supported type | Licence
    ---------------------------------------|----------------|--------
    [http://freegeoip.net/]    [freegeoip] | IPv4           | free
    [http://ipinfo.io/]           [ipinfo] | IPv4, IPv6     | free
    [http://www.telize.com/]      [Telize] | IPv4, IPv6     | free
    [http://ip-json.rhcloud.com/] [IPJson] | IPv4, IPv6     | free
    [http://xhanch.com/]          [Xhanch] | IPv4           | free
    [http://www.geoplugin.com/][geoplugin] | IPv4, IPv6     | free, need an attribution link
    [http://ip-api.com/]           [ipapi] | IPv4, IPv6     | free for non-commercial use
    [http://ipinfodb.com/]      [IPInfoDB] | IPv4, IPv6     | free for registered user

### FAQ:

#### What is this plugin for? ####

It's for blocking spam comments. If you can not specify countries with white 
list or black list to protect your site against undesired access, you should 
choose other awesome plugins.

#### How can I check this plugin works? ####

Check `statistics` tab on this plugin's option page.

#### How can I test on the local site? ####

There are two ways. One is to add some code somewhere in your php (typically 
`functions.php` in your theme) to substitute local IP address through filter 
fook `ip-geo-block-ip-addr` as follows:

```php
function my_replace_ip( $ip ) {
    return '98.139.183.24'; // yahoo.com
}
add_filter( 'ip-geo-block-ip-addr', 'my_replace_ip' );
```

Another method is adding a country code into `White list` or `Black list` on 
the plugin settings page. Most of the IP Geolocation services return empty 
(with some status) if a local IP address (e.g. 127.0.0.0) is sent, but only 
`freegeoip.net` returns `RD`.

#### Can I add an additional validation function into this plugin? ####

Yes, you can use `add_filter()` with filter hook `ip-geo-block-comment` in 
somewhere (typically `functions.php` in your theme) as follows:

```php
function my_blacklist( $validate ) {
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
add_filter( 'ip-geo-block-comment', 'my_blacklist' );
```

#### Can I change user agent strings when fetching services ? ####

Yes. The default is something like `Wordpress/4.0; ip-geo-block 1.2.0`.
You can change it as follows:

```php
function my_user_agent( $args ) {
    $args['user-agent'] = 'my user agent strings';
    return $args;
}
add_filter( 'ip-geo-block-headers', 'my_user_agent' );
```

#### Are there any other filter hooks? ####

Yes, here is the list of all hooks.

* `ip-geo-block-ip-addr`          : IP address of accessor.
* `ip-geo-block-headers`          : compose http request headers.
* `ip-geo-block-comment`          : validate IP address at `wp-comments-post.php`.
* `ip-geo-block-login`            : validate IP adress at `wp-login.php`.
* `ip-geo-block-admin`            : validate IP adress at `wp-admin/admin.php` and `xmlrpc.php` except ajax.
* `ip-geo-block-backup-dir`       : absolute path where log files should be saved.
* `ip-geo-block-maxmind-dir`      : absolute path where Maxmind GeoLite DB files should be saved.
* `ip-geo-block-maxmind-zip-ipv4` : url to Maxmind GeoLite DB zip file for IPv4.
* `ip-geo-block-maxmind-zip-ipv6` : url to Maxmind GeoLite DB zip file for IPv6.
* `ip-geo-block-ip2location-path` : absolute path to IP2Location LITE DB file.

For more details, see `samples.php` bundled within this package.

### Other Notes:

After installing these IP2Location plugins, you should be once deactivated 
and then activated in order to set the path to `database.bin`.

If you do not want to keep the IP2Location plugins (
    [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"),
    [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"),
    [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins")
) in `wp-content/plugins/` directory but just want to use its database, 
you can rename it to `ip2location` and upload it to `wp-content/`.

#### Change log

- 2.0.0
    - **New feature:** Protection against brute-force and reverse-brute-force 
      attacks to the admin area, `wp-login.php` and `xmlrpc.php`. This is an 
      experimental function and can be enabled on `Settings` tab. Malicious 
      access can try to login only 5 times per IP address. This retry counter 
      can be reset to zero by `Clear statistics` on `Statistics` tab.
- 1.4.0
    - **New feature:** Added a new class for recording the validation logs to 
      analyze posting pattern.
    - Fixed an issue of not being set the own country code at first install.
    - Fixed an error which occurs when ip address is unknown.
- 1.3.1
    - **New feature:** Added validation of trackback spam.
    - Added `$_SERVER keys for extra IPs` into options to validate additional 
      IP addresses.
    - Removed some redundant codes and corrected all PHP notices and warnings 
      which had been suppressed by WordPress.
- 1.3.0
    - **New feature:** Added validation of pingback.ping through `xmlrpc.php` 
      and new option to validate all the IP addresses in HTTP_X_FORWARDED_FOR.
    - **Fixed an issue:** Maxmind database file may be downloaded automatically
      without deactivate/re-activate when upgrade is finished.
    - This is the final version on 1.x. On next release, accesses to `login.php`
      and admin area will be also validated for security purpose.
- 1.2.1
    - **Fixed an issue:** Option table will be updated automatically without
      deactivate/re-activate when this plugin is upgraded.
    - **A little bit performance improvement:**
      Less memory footprint at the time of downloading Maxmind database file.
      Less sql queries when `Save statistics` is enabled.
- 1.2.0
    - **New feature:** Added Maxmind GeoLite database auto downloader and updater.
    - The filter hook `ip-geo-block-validate` was discontinued.
      Instead of it, the new filter hook `ip-geo-block-comment` is introduced.
    - **Performance improvement:** IP address is verified at an earlier stage 
      than before.
    - **Others:** Fix a bug of handling cache, update status of some REST APIs.
- 1.1.1  Fixed issue of default country code.
         When activating this plugin for the first time, get the country code 
         from admin's IP address and set it into white list.
         Add number of calls in cache of IP address.
- 1.1.0  Implement the cache mechanism to reduce load on the server.
         Better handling of errors on the search tab so as to facilitate 
         the analysis of the service problems.
         Fixed a bug of setting user agent strings in 1.0.2. 
         Now the user agent strings (`WordPress/3.9.2; http://example.com/`) 
         becomes to its own (`WordPress/3.9.2; ip-geo-block 1.1.0`).
- 1.0.3  Temporarily stop setting user agent strings to supress a bug in 1.0.2.
- 1.0.2  Update provider settings (`class-ip-geo-block-api.php`).
         Set user agent strings for `WP_Http` (`class-ip-geo-block.php`).
- 1.0.1  Modify Plugin URL.
         Add `apply_filters()` to be able to change headers.
- 1.0.0  Ready to release.

### License:

This plugin is licensed under the GPL v2 or later.

[freegeoip]:http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service"
[ipinfo]:   http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details"
[Telize]:   http://www.telize.com/ "Telize - JSON IP and GeoIP REST API"
[IPJson]:   http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service"
[Xhanch]:   http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API - IP Get Detail | Xhanch Studio"
[geoplugin]:http://www.geoplugin.com/ "geoPlugin to geolocate your visitors"
[ipapi]:    http://ip-api.com/ "IP-API.com - Free Geolocation API"
[IPInfoDB]: http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools"
[MaxMind]:  http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"
[IP2Loc]:   http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[IP2Tag]:   http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"
[IP2Var]:   http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Tags - WordPress Plugins"
[IP2Blk]:   http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins"
[register]: http://ipinfodb.com/register.php
[codex]:    http://codex.wordpress.org/Plugin_API/Filter_Reference/preprocess_comment "Plugin API/Filter Reference/preprocess comment &laquo; WordPress Codex"
[BHS]:      http://blackhole.webpagetest.org/
[ISO]:      http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia"
[RFC]:      http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1"
[wordfence]:https://wordpress.org/plugins/wordfence/ "WordPress › Wordfence Security « WordPress Plugins"
