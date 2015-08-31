IP Geo Block
==============

It blocks any spams, login attempts and malicious access to the admin area 
posted from outside your nation, and also prevents zero-day exploit.

There are some cases of a site being infected. The first one is the case 
that contaminated files are uploaded via FTP or some kind of uploaders. 
In this case, scaning and verifing integrity of files in your site is useful 
to detect the infection.

The second one is cracking of the login username and password. In this case, 
the rule of right is to strengthen the password.

The third one is caused by malicious access to the core files. The major issue 
in this case is that a plugin or theme in your site can potentially has some 
vulnerability such as XSS, CSRF, SQLi, LFI and so on. For example, if a plugin 
has vulnerability of Local File Inclusion (LFI), the attackers can easily 
download the `wp-config.php` without knowing the username and password by 
simply hitting 
    [wp-admin/admin-ajax.php?action=show&file=../wp-config.php](http://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html "Slider Revolution Plugin Critical Vulnerability Being Exploited | Sucuri Blog")
on their browser.

For these cases, the protection based on the IP address is not a perfect 
solution for everyone. But for some site owners or some certain cases such 
as 'zero-day attack', it can still reduce the risk of infection against the 
specific attacks.

That's why this plugin is here.

### Features:

This plugin will examine a country code based on the IP address. If a comment, 
pingback or trackback comes from the specific country, it will be blocked 
before Akismet validate it.

With the same mechanism, it will fight against burst access of brute-force 
and reverse-brute-force attacks to the login form, XML-RPC and admin area.

* **Immigration control:**  
  Access to the basic and important entrances into the back-end such as 
  `wp-comments-post.php`, `xmlrpc.php`, `wp-login.php`, `wp-admin/admin.php`,
  `wp-admin/admin-ajax.php`, `wp-admin/admin-post.php` will be validated by 
  means of a country code based on IP address.

* **Gate keeping:**  
  In order to prevent the invasion through the login form and XML-RPC against
  the brute-force and the reverse-brute-force attacks, the number of login 
  attempts will be limited per IP address. This feature works independently 
  from blocking by country code.

* **Zero-day Exploit Prevention:**  
  Besides blocking by country code, the original new feature '**Z**ero-day 
  **E**xploit **P**revention for wp-admin' (WP-ZEP) is now available to block 
  malicious access to `wp-admin/*.php`. It will protect against certain types 
  of attack such as CSRF, SQLi and so on even if you have some
    [vulnerable plugins](https://wpvulndb.com/ "WPScan Vulnerability Database")
  in your site. Because this is an experimental feature, please open an issue at
    [support forum](https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block")
  if you have any troubles. I'll be profoundly grateful your contribution to
  improve this feature. See more details on
    [this plugin's blog](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/ "Blog of IP Geo Block").

* **Supports of BuddyPress and bbPress:**  
  You can configure this plugin such that a registered user can login as the
  membership from anywhere, but a request such as a new user registration,
  lost password, creating a new topic, and subscribing comment is blocked by 
  the country code. It is suitable for
    [BuddyPress][BuddyPress]
    and [bbPress][bbPress]
  to help reducing spams.

* **Customizing response:**  
  HTTP Response code can be selectable as `403 Forbidden` to deny access pages,
  `404 Not Found` to hide pages or even `200 OK` to redirect to the top page.
  You can also have the custom error page (for example `403.php`) in your theme
  template directory or child theme directory to fit your theme.

* **Referer suppressor for external links:**  
  When you click an external hyperlink on admin screen, http referer will be 
  eliminated to hide a footprint of your site.

* **Validation logs:**  
  Logs will be recorded into MySQL data table to audit posting pattern under 
  the specified condition.

* **Multi source of IP Geolocation database:**  
  Free IP Geolocation database and REST APIs are installed into this plugin to
  get a country code from an IP address. There are two types of API which 
  support only IPv4 or both IPv4 and IPv6. This plugin will automatically 
  choose an appropriate API.

* **Cache mechanism:**  
  A cache mechanism with transient API for the fetched IP addresses has been 
  equipped to reduce load on the server against the burst accesses with a short
  period of time.

* **Database auto updater:**  
  [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention") 
  GeoLite free database for IPv4 and IPv6 will be downloaded and updated (once
  a month) automatically. And if you have correctly installed one of the
  IP2Location plugins (
    [IP2Location Tags][IP2Tag],
    [IP2Location Variables][IP2Var],
    [IP2Location Country Blocker][IP2Blk]
  ), this plugin uses its local database prior to the REST APIs.

* **Cooperation with full spec security plugin:**  
  This plugin is simple and lite enough to be able to cooperate with other full
  spec security plugin such as 
    [Wordfence Security][wordfence]
  (because the function of country bloking is available only for premium users).

* **Extensibility:**  
  You can customize the basic behavior of this plugin via `add_filter()` with
  pre-defined filter hook. See various use cases in
    [samples.php][sample]
  bundled within this package.

* **Self blocking prevention and easy rescue:**  
  Most of users do not prefer themselves to be blocked. This plugin prevents 
  such thing unless you force it.
    ([release 2.1.4](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/changelog/release-2.1.4.html "2.1.4 Release Note"))
  And futhermore, if such a situation occurs, you can rescue yourself easily.
    ([release 2.1.3](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/changelog/release-2.1.3.html "2.1.3 Release Note"))

* **Clean uninstallation:**  
  Nothing is left in your precious mySQL database after uninstallation. So you
  can feel free to install and activate to make a trial of this plugin's
  functionality. Several days later, you'll find many undesirable accesses in
  your validation logs if you make all validation targets enable.

### Requirement:

- WordPress 3.7+

### Attribution:

This package includes GeoLite data created by MaxMind, available from 
    [MaxMind][MaxMind],
and also includes IP2Location open source libraries available from 
    [IP2Location][IP2Loc].

Also thanks for providing the following great services and REST APIs for free.

    Provider                               | Supported type | Licence
    ---------------------------------------|----------------|--------
    [http://freegeoip.net/]    [freegeoip] | IPv4           | free
    [http://ipinfo.io/]           [ipinfo] | IPv4, IPv6     | free
    [http://www.telize.com/]      [Telize] | IPv4, IPv6     | free
    [http://ip-json.rhcloud.com/] [IPJson] | IPv4, IPv6     | free
    [http://ip.pycox.com/]         [Pycox] | IPv4, IPv6     | free
    [http://geoip.nekudo.com/]    [Nekudo] | IPv4, IPv6     | free
    [http://xhanch.com/]          [Xhanch] | IPv4           | free
    [http://www.geoplugin.com/][geoplugin] | IPv4, IPv6     | free, need an attribution link
    [http://ip-api.com/]           [ipapi] | IPv4, IPv6     | free for non-commercial use
    [http://ipinfodb.com/]      [IPInfoDB] | IPv4, IPv6     | free for registered user

### Installation:

1. Upload `ip-geo-block` directory to your plugins directory.
2. Activate the plugin on the Plugin dashboard.

#### Geolocation API settings

* **API selection and key settings**  
  If you wish to use `IPInfoDB`, you should register at 
    [their site][IPInfoDB]
  to get a free API key and set it into the textfield. And `ip-api.com` and 
  `Smart-IP.net` require non-commercial use.

#### Validation settings

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

* **Admin ajax/post**  
  Validate access to `wp-admin/admin-(ajax|post)*.php`.

* **Plugins area**  
  Validate direct access to plugins. Typically `wp-content/plugins/…/*.php`.

* **Themes area**  
  Validate direct access to themes. Typically `wp-content/themes/…/*.php`.

* **$_SERVER keys for extra IPs**  
  Additional IP addresses will be validated if some of keys in `$_SERVER` 
  variable are specified in this textfield. Typically `HTTP_X_FORWARDED_FOR`.

* **Matching rule**  
  Choose `White list` (recommended) or `Black list` to specify the countries
  from which you want to pass or block.

* **White list**, **Black list**  
  Specify the country code with two letters (see 
    [ISO 3166-1 alpha-2][ISO]
  ). Each of them should be separated by comma.

* **Response code**  
  Choose one of the 
    [response code][RFC]
  to be sent when it blocks a comment.
  The 2xx code will lead to your top page, the 3xx code will redirect to 
    [Black Hole Server][BHS],
  the 4xx code will lead to WordPress error page, and the 5xx will pretend 
  an server error.

#### Record settings

* **Record validation statistics**  
  If `Enable`, you can see `Statistics of validation` on Statistics tab.

* **Record validation logs**  
  If you choose anything but `Disable`, you can see `Validation logs` on 
  Logs tab.

* **$_POST keys in logs**  
  Normally, you can see just keys at `$_POST data:` on Logs tab. If you put 
  some of interested keys into this textfield, you can see the value of key 
  like `key=value`.

#### Maxmind GeoLite settings

* **Auto updating (once a month)**
  If `Enable`, Maxmind GeoLite database will be downloaded automatically by 
  WordPress cron job.

#### Submission settings

* **Text position on comment form**  
  If you want to put some text message on your comment form, please choose
  `Top` or `Bottom` and put text into the **Text message on comment form**
  textfield.

#### Cache settings

* **Number of entries**  
  Maximum number of IPs to be cached.

* **Expiration time [sec]**  
  Maximum time in sec to keep cache.

#### Plugin settings

* **Remove settings at uninstallation**  
  If you checked this option, all settings will be removed when this plugin
  is uninstalled for clean uninstalling.

### FAQ:

#### I was locked down. What shall I do? ####

Activate the following codes at the bottom of `ip-geo-block.php` and upload it 
via FTP.

```php
/**
 * Invalidate blocking behavior in case yourself is locked out.
 * @note: activate the following code and upload this file via FTP.
 */ //
function ip_geo_block_emergency( $validate ) {
    $validate['result'] = 'passed';
    return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */
```

Then `Clear cache` at `Statistics` tab on your dashborad. Remember that you 
should upload the original one to deactivate above feature.

#### How can I protect my `wp-config.php` against malicious access? ####

```php
function my_protectives( $validate ) {
    $blacklist = array(
        'wp-config.php',
        'passwd',
    );

    $req = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

    foreach ( $blacklist as $item ) {
        if ( strpos( $req, $item ) !== FALSE ) {
            $validate['result'] = 'blocked';
            break;
        }
    }

    return $validate; // should not set 'passed' to validate by country code
}
add_filter( 'ip-geo-block-admin', 'my_protectives' );
```

#### Are there any other filter hooks? ####

Yes, here is the list of all hooks.

* `ip-geo-block-ip-addr`          : IP address of accessor.
* `ip-geo-block-headers`          : compose http request headers.
* `ip-geo-block-comment`          : validate IP address at `wp-comments-post.php`.
* `ip-geo-block-xmlrpc`           : validate IP address at `xmlrpc.php`.
* `ip-geo-block-login`            : validate IP address at `wp-login.php`.
* `ip-geo-block-admin`            : validate IP address at `wp-admin/*.php`.
* `ip-geo-block-xxxxxx-status`    : http response status code for comment|xmlrpc|login|admin.
* `ip-geo-block-xxxxxx-reason`    : http response reason      for comment|xmlrpc|login|admin.
* `ip-geo-block-bypass-admins`    : array of admin queries which should bypass WP-ZEP.
* `ip-geo-block-bypass-plugins`   : array of plugin name which should bypass WP-ZEP.
* `ip-geo-block-bypass-themes`    : array of theme name which should bypass WP-ZEP.
* `ip-geo-block-backup-dir`       : full path where log files should be saved.
* `ip-geo-block-maxmind-dir`      : full path where Maxmind GeoLite DB files should be saved.
* `ip-geo-block-maxmind-zip-ipv4` : url to Maxmind GeoLite DB zip file for IPv4.
* `ip-geo-block-maxmind-zip-ipv6` : url to Maxmind GeoLite DB zip file for IPv6.
* `ip-geo-block-ip2location-path` : full path to IP2Location LITE DB file.

For more details, see 
    [samples.php][sample]
bundled within this package.

#### How does WP-ZEP prevent zero-day attack? ####

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
[this plugin's blog](http://tokkonopapa.github.io/WordPress-IP-Geo-Block/ "Blog of IP Geo Block").

#### Some admin function doesn't work when WP-ZEP is on. ####

There are a few cases that WP-ZEP would not work. One is redirection at server 
side (caused by PHP or `.htaccess`) and client side (by caused JavaScript 
location object or meta tag for refresh).

Another is the case related to the content type. This plugin will only support 
 `application/x-www-form-urlencoded` and `multipart/form-data`.

The other case is that a ajax/post request comes from not jQuery but flash or 
something.

In those cases, this plugin should bypass WP-ZEP. So please find the unique 
strings in the requested queries and add it into the safe query list via the 
filter hook `ip-geo-block-bypass-admins`.

If you can not figure out your troubles, please let me know about the plugin 
you are using at the support forum.

#### I want to use only WP-ZEP. ####

Uncheck the `Comment post`, `XML-RPC` and `Login form` in `Validation settings` 
on `Settings` tab. And choose `Prevent zero-day exploit` for `Admin area`.

At last empty the textfield of `White list` or `Black list` according to the 
 `Matching rule`.

### Other Notes:

After installing these IP2Location plugins, you should be once deactivated 
and then activated in order to set the path to `database.bin`.

If you do not want to keep the IP2Location plugins (
    [IP2Location Tags][IP2Tag],
    [IP2Location Variables][IP2Var],
    [IP2Location Country Blocker][IP2Blk]
) in `wp-content/plugins/` directory but just want to use its database, 
you can rename it to `ip2location` and upload it to `wp-content/`.

### License:

This plugin is licensed under the GPL v2 or later.

[freegeoip]:  http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service"
[ipinfo]:     http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details"
[Telize]:     http://www.telize.com/ "Telize - JSON IP and GeoIP REST API"
[IPJson]:     http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service"
[Pycox]:      http://ip.pycox.com/ "Free IP Geolocation Web Service"
[Nekudo]:     http://geoip.nekudo.com/ "eoip.nekudo.com | Free IP geolocation API"
[Xhanch]:     http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API - IP Get Detail | Xhanch Studio"
[geoplugin]:  http://www.geoplugin.com/ "geoPlugin to geolocate your visitors"
[ipapi]:      http://ip-api.com/ "IP-API.com - Free Geolocation API"
[IPInfoDB]:   http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools"
[MaxMind]:    http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"
[IP2Loc]:     http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[IP2Tag]:     http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"
[IP2Var]:     http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"
[IP2Blk]:     http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins"
[BHS]:        http://blackhole.webpagetest.org/
[ISO]:        http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia"
[RFC]:        http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1"
[sample]:     https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
[wordfence]:  https://wordpress.org/plugins/wordfence/ "WordPress › Wordfence Security « WordPress Plugins"
[BuddyPress]: https://wordpress.org/plugins/buddypress/ "WordPress › BuddyPress « WordPress Plugins"
[bbPress]   : https://wordpress.org/plugins/bbpress/ "WordPress › bbPress « WordPress Plugins"
