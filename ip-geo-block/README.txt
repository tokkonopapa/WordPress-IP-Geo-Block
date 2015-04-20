=== IP Geo Block ===
Contributors: tokkonopapa
Donate link:
Tags: comment, pingback, trackback, spam, IP address, geolocation, xmlrpc, login, wp-admin, ajax, security, brute force
Requires at least: 3.7
Tested up to: 4.1.1
Stable tag: 2.0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that will block any spams, login attempts and malicious 
access to the admin area posted from outside your nation.

== Description ==

There are some cases of a site being infected. The first one is the case 
that contaminated files are uploaded via FTP or some kind of uploaders. 
In this case, scaning and verifing integrity of files in your site is useful 
to detect the infection.

The second one is cracking of the login username and password. In this case, 
the rule of right is to strengthen the password.

The third one is caused by malicious access to the core files. The major issue 
in this case is that a plugin or theme in your site can potentially have some 
vulnerability such as XSS, CSRF, SQLi, LFI and so on. For example, if a plugin 
has vulnerability of Local File Inclusion (LFI), the attackers can easily 
download the `wp-config.php` without knowing the username and password by 
simply hitting 
    [wp-admin/admin-ajax.php?action=show-me&file=../wp-config.php](http://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html "Slider Revolution Plugin Critical Vulnerability Being Exploited | Sucuri Blog")
on their browser.

For these cases, the protection based on the IP address is not a perfect 
solution for everyone. But for some site owners or some certain cases such 
as 'zero-day attack', it can still reduce the risk of infection against the 
specific attacks.

That's why this plugin is here.

= Features =

This plugin will examine a country code based on the IP address. If a comment, 
pingback or trackback comes from the specific country, it will be blocked 
before Akismet validate it.

With the same mechanism, it will fight against burst access of brute-force 
and reverse-brute-force attacks to the login form, XML-RPC and admin area.

1. Access to the basic and important entrances into the back-end such as 
 `wp-comments-post.php`, `xmlrpc.php`, `wp-login.php`, `wp-admin/admin.php`,
 `wp-admin/admin-ajax.php`, `wp-admin/admin-post.php` will be validated by 
means of a country code based on IP address.

2. In order to prevent the invasion through the login form and XML-RPC against 
the brute-force and the reverse-brute-force attacks, the number of login 
attempts will be limited per IP address. This feature works independently from 
blocking by country code.

3. Besides blocking by country code, the original new feature '**Z**ero-day 
**E**xploit **P**revention for wp-admin' (WP-ZEP) is now available to block 
malicious access to `wp-admin/(admin|admin-ajax|admin-post).php`. It will 
protect against certain types of attack such as CSRF, SQLi and so on even 
if you have some 
    [vulnerable plugins](https://wpvulndb.com/ "WPScan Vulnerability Database")
in your site. Because this is an experimental feature, please open an issue at 
    [support forum](https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block")
if you have any troubles. I'll be profoundly grateful your contribution to 
improve this feature. See more details on 
    [the blog of this plugin](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/ "Blog of IP Geo Block").

4. HTTP Response code can be selected as `403 Forbidden` to deny access pages, 
 `404 Not Found` to hide pages or even `200 OK` to redirect to the top page.

5. Referer silencer for external link. When you click an external hyperlink on 
admin screen, http referer will be suppressed to hide a footprint of your site.

6. Validation logs will be recorded into MySQL data table to analyze posting 
pattern under the specified condition.

7. Free IP Geolocation database and REST APIs are installed into this plugin 
to get a country code from an IP address. There are two types of API which 
support only IPv4 or both IPv4 and IPv6. This plugin will automatically select 
an appropriate API.

8. A cache mechanism with transient API for the fetched IP addresses has been 
equipped to reduce load on the server against the burst accesses with a short 
period of time.

9. [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention") 
GeoLite free database for IPv4 and IPv6 will be downloaded and updated 
(once a month) automatically. And if you have correctly installed 
one of the IP2Location plugins (
    [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"),
    [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"),
    [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins")
), this plugin uses its local database prior to the REST APIs.

10. This plugin is simple and lite enough to be able to cooperate with other 
full spec security plugin such as 
    [Wordfence Security](https://wordpress.org/plugins/wordfence/ "WordPress › Wordfence Security « WordPress Plugins")
(because the function of country bloking is available only for premium users).

11. You can customize the basic behavior of this plugin via `add_filter()` with 
pre-defined filter hook. See various use cases in 
    [samples.php](https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub")
bundled within this package.

= Attribution =

This package includes GeoLite data created by MaxMind, available from 
    [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"),
and also includes IP2Location open source libraries available from 
    [IP2Location](http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location").

Also thanks for providing the following great services and REST APIs for free.

* [http://freegeoip.net/](http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service") (IPv4 / free)
* [http://ipinfo.io/](http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details") (IPv4, IPv6 / free)
* [http://www.telize.com/](http://www.telize.com/ "Telize - JSON IP and GeoIP REST API") (IPv4, IPv6 / free)
* [http://ip-json.rhcloud.com/](http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service") (IPv4, IPv6 / free)
* [http://xhanch.com/](http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API &#8211; IP Get Detail | Xhanch Studio") (IPv4 / free)
* [http://www.geoplugin.com/](http://www.geoplugin.com/ "geoPlugin to geolocate your visitors") (IPv4, IPv6 / free, need an attribution link)
* [http://ip-api.com/](http://ip-api.com/ "IP-API.com - Free Geolocation API") (IPv4, IPv6 / free for non-commercial use)
* [http://ipinfodb.com/](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") (IPv4, IPv6 / free for registered user, need API key)

= Development =

Development of this plugin is promoted on 
    [GitHub](https://github.com/tokkonopapa/WordPress-IP-Geo-Block "tokkonopapa/WordPress-IP-Geo-Block - GitHub").
All contributions will always be welcome.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'IP Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Geolocation API settings =

* **API selection and key settings**  
    If you wish to use `IPInfoDB`, you should register at 
    [their site](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") 
    to get a free API key and set it into the textfield.
    And `ip-api.com` and `Smart-IP.net` require non-commercial use.

= Validation settings =

* **Comment post**  
    Validate post to `wp-comment-post.php`. Comment post and trackback will be 
    validated.

* **XML-RPC**  
    Validate access to `xmlrpc.php`. Pingback and other remote command with 
    username and password will be validated.

* **Login form**  
    Validate access to `wp-login.php`.

* **Admin area**  
    Validate access to `wp-admin/*.php`.

* **$_SERVER keys for extra IPs**  
    Additional IP addresses will be validated if some of keys in `$_SERVER` 
    variable are specified in this textfield. Typically `HTTP_X_FORWARDED_FOR`.

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

= Record settings =

* **Record validation statistics**  
    If `Enable`, you can see `Statistics of validation` on Statistics tab.

* **Record validation logs**  
    If you select anything but `Disable`, you can see `Validation logs` on 
    Logs tab.

* **$_POST keys in logs**  
    Normally, you can see just keys at `$_POST data:` on Logs tab. If you put 
    some of interested keys into this textfield, you can see the value of key 
    like `key=value`.

= Maxmind GeoLite settings =

* **Auto updating (once a month)**
    If `Enable`, Maxmind GeoLite database will be downloaded automatically 
    by WordPress cron job.

= Submission settings =

* **Text position on comment form**  
    If you want to put some text message on your comment form, please select
    `Top` or `Bottom` and put text into the **Text message on comment form**
    textfield.

= Cache settings =

* **Number of entries**  
    Maximum number of IPs to be cached.

* **Expiration time [sec]**  
    Maximum time in sec to keep cache.

= Plugin settings =

* **Remove settings at uninstallation**  
    If you checked this option, all settings will be removed when this plugin
    is uninstalled for clean uninstalling.

== Frequently Asked Questions ==

= I was locked down. What shall I do? =

Add the following codes to `functions.php` in your theme and upload it via FTP.

`function my_emergency( $validate ) {
    $validate['result'] = 'passed';
    return $validate;
}
add_filter( 'ip-geo-block-login', 'my_emergency' );
add_filter( 'ip-geo-block-admin', 'my_emergency' );`

Then `Clear cache` at `Statistics` tab on your dashborad. After that, you can 
remove above codes.

= How can I protect my `wp-config.php` against malicious access? =

`function my_protectives( $validate ) {
    $protectives = array(
        'wp-config.php',
        'passwd',
    );

    $req = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

    foreach ( $protectives as $item ) {
        if ( strpos( $req, $item ) !== FALSE ) {
            $validate['result'] = 'blocked';
            break;
        }
    }

    return $validate; // should not set 'passed' to validate by country code
}
add_filter( 'ip-geo-block-admin', 'my_protectives' );`

= Are there any other filter hooks? =

Yes, here is the list of all hooks.

* `ip-geo-block-ip-addr`          : IP address of accessor.
* `ip-geo-block-headers`          : compose http request headers.
* `ip-geo-block-comment`          : validate IP address at `wp-comments-post.php`.
* `ip-geo-block-xmlrpc`           : validate IP address at `xmlrpc.php`.
* `ip-geo-block-login`            : validate IP address at `wp-login.php`.
* `ip-geo-block-admin`            : validate IP address at `wp-admin/*.php`.
* `ip-geo-block-admin-actions`    : array of actions for `wp-admin/(admin|admin-ajax|admin-post).php`.
* `ip-geo-block-backup-dir`       : full path where log files should be saved.
* `ip-geo-block-maxmind-dir`      : full path where Maxmind GeoLite DB files should be saved.
* `ip-geo-block-maxmind-zip-ipv4` : url to Maxmind GeoLite DB zip file for IPv4.
* `ip-geo-block-maxmind-zip-ipv6` : url to Maxmind GeoLite DB zip file for IPv6.
* `ip-geo-block-ip2location-path` : full path to IP2Location LITE DB file.

For more details, see 
    [samples.php](https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub")
bundled within this package.

= How does WP-ZEP prevent zero-day attack? =

A considerable number of vulnerable plugins are lacking in validating either 
the nonce and privilege or both. WP-ZEP will make up both of them embedding a 
nonce into the link, form and ajax request from jQuery on every admin screen.

This simple system will validate both of them on behalf of vulnerable plugins 
in your site and will block a request with a query parameter `action` through 
 `wp-admin/(admin|admin-ajax|admin-post).php` if it has no nonce and privilege.
Moreover, it doesn't affects a request from non-logged-in user.

On the other hand, the details of above process are slightly delicate. For 
example, it's incapable of preventing Privilege Escalation (PE) because it 
can't be decided which capabilities does the request need.

See more details on 
[the blog of this plugin](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/ "Blog of IP Geo Block").

= Some admin function doesn't work when WP-ZEP is on. =

There are a few cases that WP-ZEP would not work. One is redirection at server 
side (by PHP or `.htaccess`) and client side (by JavaScript location object or 
meta tag for refresh).

Another is the case related to the content type. This plugin will only support 
 `application/x-www-form-urlencoded` and `multipart/form-data`.

The other case is that a ajax/post request comes from not jQuery but flash or 
something.

In those cases, this plugin should bypass WP-ZEP. So please find the `action` 
in the requested queries and add its value into the safe action list via the 
filter hook `ip-geo-block-admin-actions`.

If you can not figure out your troubles, please let me know about the plugin 
you are using at the support forum.

= I want to use only WP-ZEP. =

Uncheck the `Comment post`, `XML-RPC` and `Login form` in `Validation settings` 
on `Settings` tab. And select `Prevent zero-day exploit` for `Admin area`.

At last empty the textfield of `White list` or `Black list` according to the 
 `Matching rule`.

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

= 2.0.8 =
* Fixed an issue that a certain type of attack vector to the admin area (
  [example](https://blog.sucuri.net/2014/08/database-takeover-in-custom-contact-forms.html "Critical Vulnerability Disclosed on WordPress Custom Contact Forms Plugin")
  ) could not be blocked by the reason that some plugins accept it on earlier 
  hook (ie `init`) than this plugin (ie `admin_init`).
* Optimized resource settings and loadings to avoid redundancy.
* Unified validation of admin area and admin ajax/post from the viewpoint of 
  preventing malicious access.
* Added re-creating DB table for validation logs in case of accidentally 
  failed at activation process.
* The time of day is shown with local time by adding GMT offset based on 
  the time zone setting.

= 2.0.7 =
* Avoid JavaScript error which occurs if an anchor link has no `href`.
* Improved UI on admin screen.
* Added a diagnosis for creation of database table.

= 2.0.6 =
* Sorry for urgent update but avoid an javascript error.

= 2.0.4 =
* Sorry for frequent update but added a function of showing admin notice 
  when none of the IP geolocation providers is selected. Because the user 
  will be locked out from admin screen when the cache expires.
* **Bug fix:** Fixed an issue of `get_geolocation()` method at a time of 
  when the cache of IP address is cleared.
* Referer silencer now supports [meta referrer](https://wiki.whatwg.org/wiki/Meta_referrer "Meta referrer - WHATWG Wiki")

= 2.0.3 =
* **Bug fix:** Fixed an issue that empty black list doesn't work correctly 
  when matching rule is black list.
* **New feature:** Added 'Zero-day Exploit Prevention for wp-admin'.
  Because it is an experimental feature, please open a new issue at 
  [support forum](https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block")
  if you have any troubles with it.
* **New feature:** Referer silencer for external link. When you click an 
  external hyperlink on admin screen, http referer will be suppressed to 
  hide a footprint of your site.
* Also added the filter hook `ip-geo-block-admin-actions` for safe actions 
  on back-end.

= 2.0.2 =
* **New feature:** Include `wp-admin/admin-post.php` as a validation target 
  in the `Admin area`. This feature is to protect against a vulnerability 
  such as 
  [Analysis of the Fancybox-For-WordPress Vulnerability](http://blog.sucuri.net/2015/02/analysis-of-the-fancybox-for-wordpress-vulnerability.html)
  on Sucuri Blog.
* Added a sample code snippet as a use case for 'Give ajax permission in 
  case of safe actions on front facing page'. See Example 10 in `sample.php`.

= 2.0.1 =
* Fixed the issue of improper scheme from the HTTPS site when loading js 
  for google map.
* In order to prevent accidental disclosure of the length of password, 
  changed the length of `*` (masked password) which is logged into the 
  database.

= 2.0.0 =
* **New feature:** Protection against brute-force and reverse-brute-force 
  attacks to `wp-login.php`, `xmlrpc.php` and admin area.
  This is an experimental function and can be enabled on `Settings` tab.
  Malicious access can try to login only 5 times per IP address. This retry 
  counter can be reset to zero by `Clear statistics` on `Statistics` tab.

= 1.4.0 =
* **New feature:** Added a new class for recording the validation logs to 
  analyze posting pattern.
* Fixed an issue of not being set the own country code at first installation.
* Fixed an error which occurs when ip address is unknown.

= 1.3.1 =
* **New feature:** Added validation of trackback spam.
* Added `$_SERVER keys for extra IPs` into options to validate additional 
  IP addresses.
* Removed some redundant codes and corrected all PHP notices and warnings 
  which had been suppressed by WordPress.

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
