=== IP Geo Block ===
Contributors: tokkonopapa
Donate link:
Tags: buddypress, bbPress, comment, pingback, trackback, spam, IP address, geolocation, xmlrpc, login, wp-admin, admin, ajax, security, brute force, firewall, vulnerability
Requires at least: 3.7
Tested up to: 4.5.2
Stable tag: 2.2.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

It blocks any spams, login attempts and malicious access to the admin area 
posted from the specific countries, and also prevents zero-day exploit.

== Description ==

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
as 'zero-day attack', combination with WP-ZEP can still reduce the risk of 
infection against the specific attacks.

That's why this plugin is here.

= Features =

This plugin will examine a country code based on the IP address. If a comment, 
pingback or trackback comes from the specific country, it will be blocked 
before Akismet validate it.

With the same mechanism, it will fight against burst access of brute-force 
and reverse-brute-force attacks to the login form and XML-RPC.

* **Immigration control:**  
  Access to the basic and important entrances into the back-end such as 
  `wp-comments-post.php`, `xmlrpc.php`, `wp-login.php`, `wp-signup.php`, 
  `wp-admin/admin.php`, `wp-admin/admin-ajax.php`, `wp-admin/admin-post.php` 
  will be validated by means of a country code based on IP address. It allows 
  you to configure either whitelisting or blacklisting to specify the countires.

* **Zero-day Exploit Prevention:**  
  The original feature "**Z**ero-day **E**xploit **P**revention for WP" (WP-ZEP)
  is simple but still smart and strong enough to block any malicious accesses 
  to `wp-admin/*.php`, `plugins/*.php` and `themes/*.php` even from the permitted 
  countries. It will protect your site against certain types of attack such as 
  CSRF, LFI, SQLi, XSS and so on, **even if you have some 
    [vulnerable plugins or themes](https://wpvulndb.com/ "WPScan Vulnerability Database")
  in your site**. Find more details in 
    [FAQ](https://wordpress.org/plugins/ip-geo-block/faq/ "IP Geo Block - WordPress Plugins")
  and 
    [this plugin's blog](http://www.ipgeoblock.com/article/how-wpzep-works.html "How does WP-ZEP prevent zero-day attack? | IP Geo Block").

* **Guard against login attempts:**  
  In order to prevent the invasion through the login form and XML-RPC against
  the brute-force and the reverse-brute-force attacks, the number of login 
  attempts will be limited per IP address even from the permitted countries.

* **Protection of wp-config.php:**  
  A malicious request to try to expose `wp-config.php` via vulnerable plugins 
  or themes can be blocked. A numerous such attacks can be found in 
    [this article](http://www.ipgeoblock.com/article/exposure-of-wp-config-php.html "Prevent exposure of wp-config.php").

* **Support of BuddyPress and bbPress:**  
  You can configure this plugin such that a registered user can login as the
  membership from anywhere, but a request such as a new user registration,
  lost password, creating a new topic, and subscribing comment is blocked by 
  the country code. It is suitable for
    [BuddyPress](https://wordpress.org/plugins/buddypress/ "WordPress › BuddyPress « WordPress Plugins")
    and [bbPress](https://wordpress.org/plugins/bbpress/ "WordPress › bbPress « WordPress Plugins")
  to help reducing spams.

* **Referrer suppressor for external links:**  
  When you click an external hyperlink on admin screen, http referrer will be 
  eliminated to hide a footprint of your site.

* **Multiple source of IP Geolocation databases:**  
  Free IP Geolocation database and REST APIs are installed into this plugin to
  get a country code from an IP address. There are two types of API which 
  support only IPv4 or both IPv4 and IPv6. This plugin will automatically 
  choose an appropriate API.

* **Database auto updater:**  
  [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention") 
  GeoLite free databases and 
  [IP2Location](http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location") 
  LITE databases can be incorporated with this plugin. Those will be downloaded
  and updated (once a month) automatically.

* **Cache mechanism:**  
  A cache mechanism with transient API for the fetched IP addresses has been 
  equipped to reduce load on the server against the burst accesses with a short
  period of time.

* **Customizing response:**  
  HTTP Response code can be selectable as `403 Forbidden` to deny access pages,
  `404 Not Found` to hide pages or even `200 OK` to redirect to the top page.
  You can also have the custom error page (for example `403.php`) in your theme
  template directory or child theme directory to fit your theme.

* **Validation logs:**  
  Logs will be recorded into MySQL data table to audit posting pattern under 
  the specified condition.

* **Cooperation with full spec security plugin:**  
  This plugin is simple and lite enough to be able to cooperate with other full
  spec security plugin such as 
    [Wordfence Security](https://wordpress.org/plugins/wordfence/ "WordPress › Wordfence Security « WordPress Plugins")
  (because the function of country bloking is available only for premium users).

* **Extendability:**  
  "Settings minimum, Customizability maximum" is the basic concept of this 
  plugin. You can customize the behavior of this plugin via `add_filter()`
  with pre-defined filter hook. See various use cases in 
    [the documents](http://www.ipgeoblock.com/codex/ "Codex | IP Geo Block")
  and 
    [samples.php](https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub")
  bundled within this package.

* **Self blocking prevention and easy rescue:**  
  Most of users do not prefer themselves to be blocked. This plugin prevents 
  such thing unless you force it.
    ([release 2.1.4](http://www.ipgeoblock.com/changelog/release-2.1.4.html "2.1.4 Release Note"))
  And futhermore, if such a situation occurs, you can rescue yourself easily.
    ([release 2.1.3](http://www.ipgeoblock.com/changelog/release-2.1.3.html "2.1.3 Release Note"))

* **Clean uninstallation:**  
  Nothing is left in your precious mySQL database after uninstallation. So you
  can feel free to install and activate to make a trial of this plugin's
  functionality. Several days later, you'll find many undesirable accesses in
  your validation logs if all validation targets are enabled.

= Attribution =

This package includes GeoLite data created by MaxMind, available from 
    [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"),
and also includes IP2Location open source libraries available from 
    [IP2Location](http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location").

Also thanks for providing the following great services and REST APIs for free.

* [http://freegeoip.net/](http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service") (IPv4 / free)
* [http://ipinfo.io/](http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details") (IPv4, IPv6 / free)
* [http://geoip.nekudo.com/](http://geoip.nekudo.com/ "Free IP GeoLocation/GeoIp API - geoip.nekudo.com") (IPv4, IPv6 / free)
* [http://ip-json.rhcloud.com/](http://ip-json.rhcloud.com/ "Free IP Geolocation Web Service") (IPv4, IPv6 / free)
* [http://xhanch.com/](http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API &#8211; IP Get Detail | Xhanch Studio") (IPv4 / free)
* [http://www.geoplugin.com/](http://www.geoplugin.com/ "geoPlugin to geolocate your visitors") (IPv4, IPv6 / free, need an attribution link)
* [http://ip-api.com/](http://ip-api.com/ "IP-API.com - Free Geolocation API") (IPv4, IPv6 / free for non-commercial use)
* [http://ipinfodb.com/](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") (IPv4, IPv6 / free for registered user, need API key)

= Development =

Development of this plugin is promoted on 
    [GitHub](https://github.com/tokkonopapa/WordPress-IP-Geo-Block "tokkonopapa/WordPress-IP-Geo-Block - GitHub").
All contributions will always be welcome. Or visit my 
    [development blog](http://www.ipgeoblock.com/ "IP Geo Block").

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'IP Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Validation rule settings =

* **Matching rule**  
  Choose either `White list` (recommended) or `Black list` to specify the 
  countries from which you want to pass or block.

* **Country code for matching rule**  
  Specify the country code with two letters (see 
    [ISO 3166-1 alpha-2](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia")
  ). Each of them should be separated by comma.

* **White/Black list of extra IPs for prior validation**  
  The list of extra IP addresses prior to the validation of country code.
  [CIDR notation](https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing "Classless Inter-Domain Routing - Wikipedia, the free encyclopedia")
  is acceptable to specify the range.

* **$_SERVER keys for extra IPs**  
  Additional IP addresses will be validated if some of keys in `$_SERVER` 
  variable are specified in this textfield. Typically `HTTP_X_FORWARDED_FOR`.

* **Bad signatures in query**
  It validates malicious signatures independently of **Block by country** and 
  **Prevent Zero-day Exploit** for the target **Admin area**, 
  **Admin ajax/post**, **Plugins area** and **Themes area**.
  Typically, `/wp-config.php` and `/passwd`.

* **Response code**  
  Choose one of the 
    [response code](http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1")
  to be sent when it blocks a comment.
  The 2xx code will lead to your top page, the 3xx code will redirect to 
    [Black Hole Server](http://blackhole.webpagetest.org/),
  the 4xx code will lead to WordPress error page, and the 5xx will pretend 
  an server error.

= Validation target settings =

* **Comment post**  
  Validate post to `wp-comment-post.php`. Comment post and trackback will be 
  validated.

* **XML-RPC**  
  Validate access to `xmlrpc.php`. Pingback and other remote command with 
  username and password will be validated.

* **Login form**  
  Validate access to `wp-login.php` and `wp-signup.php`.

* **Admin area**  
  Validate access to `wp-admin/*.php`.

* **Admin ajax/post**  
  Validate access to `wp-admin/admin-(ajax|post)*.php`.

* **Plugins area**  
  Validate direct access to plugins. Typically `wp-content/plugins/…/*.php`.

* **Themes area**  
  Validate direct access to themes. Typically `wp-content/themes/…/*.php`.

= Geolocation API settings =

* **API selection and key settings**  
  If you wish to use `IPInfoDB`, you should register at 
    [their site](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") 
  to get a free API key and set it into the textfield. And `ip-api.com` and 
  `Smart-IP.net` require non-commercial use.

= Local database settings settings =

* **Auto updating (once a month)**  
  If `Enable`, Maxmind GeoLite database will be downloaded automatically by 
  WordPress cron job.

= Record settings =

* **Record validation statistics**  
  If `Enable`, you can see `Statistics of validation` on Statistics tab.

* **Record validation logs**  
  If you choose anything but `Disable`, you can see `Validation logs` on 
  Logs tab.

* **$_POST keys in logs**  
  Normally, you can see just keys at `$_POST data:` on Logs tab. If you put 
  some of interested keys into this textfield, you can see the value of key 
  like `key=value`.

* **Anonymize IP address**  
  It will mask the last three digits of IP address when it is recorded into 
  the log.

= Cache settings =

* **Number of entries**  
  Maximum number of IPs to be cached.

* **Expiration time [sec]**  
  Maximum time in sec to keep cache.

= Submission settings =

* **Text position on comment form**  
  If you want to put some text message on your comment form, please choose
  `Top` or `Bottom` and put text with some tags into the **Text message on 
  comment form** textfield.

= Plugin settings =

* **Remove settings at uninstallation**  
  If you checked this option, all settings will be removed when this plugin
  is uninstalled for clean uninstalling.

== Frequently Asked Questions ==

= I was locked down. What shall I do? =

Activate the following codes at the bottom of `ip-geo-block.php` and upload it 
via FTP.

`/**
 * Invalidate blocking behavior in case yourself is locked out.
 * @note: activate the following code and upload this file via FTP.
 */ /* -- EDIT THIS LINE AND ACTIVATE THE FOLLOWING FUNCTION -- */
function ip_geo_block_emergency( $validate ) {
    $validate['result'] = 'passed';
    return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */`

Then "**Clear cache**" at "**Statistics**" tab on your dashborad. Remember 
that you should upload the original one to deactivate above feature.

[This release note](http://www.ipgeoblock.com/changelog/release-2.1.3.html "2.1.3 Release Note")
can also help you.

= Do I have to turn on all the selection to enhance security? =

Yes. Roughly speaking, the strategy of this plugin has been constructed as 
follows:

- **Block by country**  
  It blocks malicious requests from outside your country.

- **Prevent Zero-day Exploit**  
  It blocks malicious requests from your country.

- **Force to load WP core**  
  It blocks the request which has not been covered in the above two.

- **Bad signatures in query**  
  It blocks the request which has not been covered in the above three.

See more details in "
[The best practice of target settings](http://www.ipgeoblock.com/codex/the-best-practice-of-target-settings.html 'The best practice of target settings | IP Geo Block')
".

= How can I test that this plugin works? =

The easiest way is to use 
  [free proxy browser addon](https://www.google.com/search?q=free+proxy+browser+addon "free proxy browser addon - Google Search").
Another one is to use 
  [http header browser addon](https://www.google.com/search?q=browser+add+on+modify+http+header "browser add on modify http header - Google Search").
You can add an IP address to the `X-Forwarded-For` header to emulate the 
access behind the proxy. In this case, you should add `HTTP_X_FORWARDED_FOR` 
into the "**$_SERVER keys for extra IPs**" on "**Settings**" tab.

See more details in "
[Using VPN browser addon](http://www.ipgeoblock.com/codex/using-vpn-browser-addon.html 'Using VPN browser addon | IP Geo Block')
" and "
[Using WordPress post simulator](http://www.ipgeoblock.com/codex/using-post-simulator.html 'Using WordPress post simulator | IP Geo Block')
".

= Some admin function doesn't work when WP-ZEP is enabled. =

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

= Are there any other useful filter hooks? =

Yes, here is the list of all hooks to extend the feature of this plugin.

* `ip-geo-block-ip-addr`          : IP address of accessor.
* `ip-geo-block-headers`          : compose http request headers.
* `ip-geo-block-comment`          : validate IP address at `wp-comments-post.php`.
* `ip-geo-block-xmlrpc`           : validate IP address at `xmlrpc.php`.
* `ip-geo-block-login`            : validate IP address at `wp-login.php`.
* `ip-geo-block-admin`            : validate IP address at `wp-admin/*.php`.
* `ip-geo-block-extra-ips`        : white/black list of extra IPs for prior validation.
* `ip-geo-block-xxxxxx-status`    : http response status code for comment|xmlrpc|login|admin.
* `ip-geo-block-xxxxxx-reason`    : http response reason      for comment|xmlrpc|login|admin.
* `ip-geo-block-bypass-admins`    : array of admin queries which should bypass WP-ZEP.
* `ip-geo-block-bypass-plugins`   : array of plugin name which should bypass WP-ZEP.
* `ip-geo-block-bypass-themes`    : array of theme name which should bypass WP-ZEP.
* `ip-geo-block-backup-dir`       : full path where log files should be saved.
* `ip-geo-block-api-dir`          : full path to the API class libraries and local DB files.
* `ip-geo-block-maxmind-dir`      : full path where Maxmind GeoLite DB files should be saved.
* `ip-geo-block-maxmind-zip-ipv4` : url to Maxmind GeoLite DB zip file for IPv4.
* `ip-geo-block-maxmind-zip-ipv6` : url to Maxmind GeoLite DB zip file for IPv6.
* `ip-geo-block-ip2location-dir`  : full path where IP2Location LITE DB files should be saved.
* `ip-geo-block-ip2location-path` : full path to IP2Location LITE DB file (IPv4).
* `ip-geo-block-record-logs`      : change the condition of recording logs

For more details, see 
[the documents](http://www.ipgeoblock.com/codex/ "Codex | IP Geo Block").

== Other Notes ==

== Screenshots ==

1. **IP Geo Plugin** - Settings.
2. **IP Geo Plugin** - Statistics.
3. **IP Geo Plugin** - Logs.
4. **IP Geo Plugin** - Search.
5. **IP Geo Plugin** - Attribution.

== Changelog ==

= 2.2.6 =
* **New feature:** Add saving csv file of logs in "Logs" tab.
* **New feature:** Add filter hook `ip-geo-block-record-log` to control over 
  the conditions of recording in more detail.
* **Bug fix:** Fixed the issue that "Exceptions" for Plugins/Themes area does 
  not work properly. Please confirm your settings again.
* See details at [release 2.2.6](http://www.ipgeoblock.com/changelog/release-2.2.6.html "2.2.6 Release Note").

= 2.2.5 =
* **New feature:** On the settings page, you can specify the pliugin or theme 
  which would cause undesired blocking in order to exclude it from the 
  validation target without embedding any codes into `functions.php`.
* **Improvement:** Optimize resource loading on admin dashboard.
* **Improvement:** Support clean uninstall for network / multisite.
* **Improvement:** Improve the compatibility of downloading IP address 
  databases for Microsoft IIS.
* **Bug fix:** Support `FORCE_SSL_ADMIN`.
* **Bug fix:** Fix the issue of 
  [@](https://wordpress.org/support/topic/compatibility-with-ag-custom-admin "WordPress › Support » Compatibility with AG Custom Admin")
  and change the option name 
  "**Important files**" to "**Bad signatures in query**" to avoid misuse.
* **Bug fix:** Fix the issue of 
  [@](https://wordpress.org/support/topic/gb-added-to-whitelist "WordPress › Support » GB added to whitelist")
  which might be caused by some race condition.
* **Bug fix:** Fix the issue of restoring post revisions which was blocked.

= 2.2.4.1 =
Sorry for frequent updating.

* **Bug fix:** Fixed the issue of `Warning: strpos(): Empty needle in...` that 
  was reported in 
    [@](https://wordpress.org/support/topic/version-224-produces-warning-message "WordPress › Support » Version 2.2.4 Produces Warning Message")
  and
    [@](https://wordpress.org/support/topic/error-after-update-to-newest-version "WordPress › Support » Error after Update to newest version").

= 2.2.4 =
* **Bug fix:** Fixed the issue that some links on network admin of multisite 
  were blocked when WP-ZEP for `admin area` or `admin ajax/post` was enabled.
* **New feature:** Added configure of `.htaccess` for the plugins/themes area.
* **Enhancement:** Added `wp-signup.php` to the list of validation target.
* **Enhancement:** Added exporting and importing the setting parameters.
* **Improvement:** Made the logout url compatible with 
  [Rename wp-login.php](https://wordpress.org/plugins/rename-wp-login/).
* **Improvement:** Made condition of validation more strictly at admin 
  diagnosis to prevent unnecessary notice of self blocking.
  ([@](https://wordpress.org/support/topic/youll-be-blocked-after-you-log-out-notice-doesnt-disappear "[resolved] &quot;You'll be blocked after you log out&quot; notice doesn't disappear"))
* **Improvement:** Improved some of UI.
  ([@](https://wordpress.org/support/topic/possible-to-select-which-countries-are-blocked "[resolved] Possible to select which countries are blocked?"),
   [@](https://wordpress.org/support/topic/ip-geo-block-black-list "IP Geo Block Black List"))
* See some details at [release 2.2.4](http://www.ipgeoblock.com/changelog/release-2.2.4.html "2.2.4 Release Note").

= 2.2.3.1 =
* **Bug fix:** Fixed the issue that disabled validation target was still 
  blocked by country.
  ([@](https://wordpress.org/support/topic/logs-whitelist-comments-still-blocked "[resolved] logs whitelist comments still blocked?"))
* **Improvement:** Better handling of charset and errors for MySQL.
  ([@](https://wordpress.org/support/topic/whitelist-log "[resolved] Whitelist + Log"))

= 2.2.3 =
* **Improvement:** Since WordPress 4.4, XML-RPC system.multicall is disabled 
  when the authentication fails, but still processed all the methods to the 
  end. Now this plugin immediately blocks the request when the authentication 
  fails without processing the rest of the methods.
* **Improvement:** Add UI to change the maximum number of login attempts.
* **Improvement:** Add a fallback process of setting up the directory where 
  the geo location database APIs should be installed. It will be set as 
  `wp-content/uploads/` instead of `wp-content/plugins/ip-geo-block/` or 
  `wp-content/` in case of being unable to obtain proper permission.
  ([@](https://wordpress.org/support/topic/deactivated-after-updte-why "[resolved] Deactivated after update - why?"),
   [@](https://wordpress.org/support/topic/the-plugin-caused-an-error-message "[resolved] The plugin caused an error message"))
* **Improvement:** Moderate the conditions of redirection after logout.
  ([@](https://wordpress.org/support/topic/logout-redirect-doesnt-work-when-plugin-is-active "[resolved] Logout redirect doesn't work when plugin is active"))
* **Improvement:** Prevent self blocking caused by irrelevant signature.
  ([@](https://wordpress.org/support/topic/works-too-well-blocked-my-wp-admin-myself "[resolved] Works too well - Blocked my wp-admin myself"))
* **Bug fix:** Fixed the issue of conflicting with certain plugins due to the 
  irrelevant handling of js event.
  ([@](https://wordpress.org/support/topic/cannot-edit-pages-when-ip-geo-block-is-enabled "[resolved] Cannot edit pages when ip-geo-block is enabled."))
* **New feature:** Add "Blocked per day" graph for the daily statistics.
* See some details at [2.2.3 release note](http://www.ipgeoblock.com/changelog/release-2.2.3.html "2.2.3 Release Note").

= 2.2.2.3 =
Sorry for frequent update again but the following obvious bugs should be fixed.

* **Bug fix:** Fixed the issue of not initializing country code at activation.
* **Bug fix:** Fixed the issue that scheme less notation like '//example.com' 
  could not be handled correctly.

= 2.2.2.2 =
Sorry for frequent update.

* **Bug fix:** Fixed the issue of race condition at activation. This fix is 
  related to the urgent security update at **2.2.2.1 which was not actually
  the security issue but a bug**.
  See [this thread](https://wordpress.org/support/topic/white-list-hack "white list hack")
  about little more details.
* **Improvement:** Improved the compatibility with Jetpack.

= 2.2.2.1 =
* **Urgent security update:** Killed the possibility of the options being 
  altered.

= 2.2.2 =
* **Enhancement:** Refactored some codes and components. The number of attacks 
  that can be proccessed per second has been improved by 25% at the maximum.
* **Improvement:** In the previous version, the statistical data was recorded 
  into `wp_options`. It caused the uncertainty of recording especially in case 
  of burst attacks. Now the data will be recorded in an independent table to 
  improve this issue.
* **Bug fix:** Fixed conflict with NextGEN Gallary Pro.
  Thanks to [bodowewer](https://wordpress.org/support/profile/bodowewer).
* **Bug fix:** Fixed some filter hooks that did not work as intended.
* See more details at [2.2.2 release note](http://www.ipgeoblock.com/changelog/release-2.2.2.html "2.2.2 Release Note").

= 2.2.1.1 =
* **Bug fix:** Fixed "open_basedir restriction" issue caused by `file_exists()`.

= 2.2.1 =
* **Enhancement:** In previous version, local geolocation databases will always
  be removed and downloaded again at every upgrading. Now, the class library 
  for Maxmind and IP2Location have become independent of this plugin and you 
  can put them outside this plugin in order to cut the above useless process.
  The library can be available from 
  [WordPress-IP-Geo-API](https://github.com/tokkonopapa/WordPress-IP-Geo-API).
* **Deprecated:** Cooperation with IP2Location plugins such as 
  [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"),
  [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"),
  [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins")
  is out of use. Instead of it, free [IP2Location LITE databases for IPv4 and 
  IPv6](http://lite.ip2location.com/ "Free IP Geolocation Database") will be 
  downloaded.
* **Improvement:** Improved connectivity with Jetpack.
* **Improvement:** Improved immediacy of downloading databases at upgrading.
* **Improvement:** Replaced a terminated RESTful API service with a new stuff.
* **Bug fix:** Fixed issue that clicking a link tag without href always 
  refreshed the page. Thanks to 
  [wyclef](https://wordpress.org/support/topic/conflict-with-menu-editor-plugin "WordPress › Support » Conflict with Menu Editor plugin?").
* **Bug fix:** Fixed issue that deactivating and activating repeatedly caused 
  to show the welcome message.
* **Bug fix:** Fixed issue that a misaligned argument in the function caused 
  500 internal server error when a request to the php files in plugins/themes 
  area was rewrited to `rewrite.php`.

= 2.2.0.1 =
Sorry for frequent update.

* **Fix:** Fixed the issue that some actions of other plugins were blocked.

= 2.2.0 =
* **Important:** Now **Block by country** and **Prevent Zero-day Exploit** 
  become to work independently on **Admin area**, **Admin ajax/post** at 
  **Validation target settings**. Please reconfirm them.
* **Important:** Previously, a request whose country code can't be available 
  was always blocked. But from this release, such a request is considered as 
  comming from the country whose code is `ZZ`. It means that you can put `ZZ` 
  into the white list and black list.
* **New feature:** White list and Black list of extra IP addresses prior to 
  the validation of country code. Thanks to Fabiano for good suggestions at 
  [support forum](https://wordpress.org/support/topic/white-list-of-ip-addresses-or-ranges "WordPress › Support » White list of IP addresses or ranges?")
* **New feature:** Malicious signatures to prevent disclosing the important 
  files via vulnerable plugins or themes. A malicious request to try to expose 
  `wp-config.php` or `passwd` can be blocked.
* **New feature:** Add privacy considerations related to IP address. Add 
  **Anonymize IP address** at **Record settings**.
* **Bug fix:** Fix the issue that spaces in **Text message on comment form** 
  are deleted.
* See details at [2.2.0 release note](http://www.ipgeoblock.com/changelog/release-2.2.0.html "2.2.0 Release Note").

= 2.1.5.1 =
* **Bug fix:** Fixed the issue that the Blacklist did not work properly. Thanks
  to TJayYay for reporting this issue at
  [support forum](https://wordpress.org/support/topic/hackers-from-country-in-blocked-list-of-countries-trying-to-login "WordPress › Support » Hackers from country in Blocked List of Countries trying to login").

= 2.1.5 =
* **Enhancement:** Enforce preventing self blocking at the first installation.
  And add the scan button to get all the country code using selected API.
  Thanks to **Nils** for a nice idea at 
  [support forum](https://wordpress.org/support/topic/locked-out-due-to-eu-vs-country "WordPress › Support » Locked out due to EU vs. Country").
* **New feature:** Add pie chart to display statistics of "Blocked by country".
* **Enhancement:** WP-ZEP is reinforced against CSRF.
* **Bug fix:** Fix illegal handling of the fragment in a link.
* See details at [2.1.5 release note](http://www.ipgeoblock.com/changelog/release-2.1.5.html "2.1.5 Release Note").

= 2.1.4 =
* **Bug fix:** Fix the issue that this plugin broke functionality of a certain 
  plugin. Thanks to **opsec** for reporting this issue at 
  [support forum](https://wordpress.org/support/topic/blocks-saves-in-types-or-any-plugins-from-wp-typescom "WordPress › Support » Blocks saves in Types or any plugins from wp-types.com").
* **Improvement:** Add checking process for validation rule to prevent being 
  blocked itself. Thanks to **internationals** for proposing at 
  [support forum](https://wordpress.org/support/topic/locked-out-due-to-eu-vs-country "WordPress › Support » Locked out due to EU vs. Country")
* **Improvement:** Arrage the order of setting sections to focus the goal of 
  this plugin.
* See details at [2.1.4 release note](http://www.ipgeoblock.com/changelog/release-2.1.4.html "2.1.4 Release Note").

= 2.1.3 =
* **New feature:** Add "show" / "hide" at each section on the "Settings" tab.
* **New feature:** Add an emergency function that invalidate blocking behavior 
  in case yourself is locked out. This feature is commented out by default at 
  the bottom of `ip-geo-block.php`.
* **Improvement:** Prevent adding query strings to the static resources when 
  users logged in.
* **Improvement:** Improved the compatibility with Autoptimize.
* **Bug fix:** Fix the issue related to showing featured themes on dashboard.
* **Bug fix:** Fix minor bug in `rewrite.php` for the advanced use case.
* See details at [2.1.3 release note](http://www.ipgeoblock.com/changelog/release-2.1.3.html "2.1.3 Release Note").

= 2.1.2 =
This is a maintenance release.

* **Bug fix:** Fix the issue that the login-fail-counter didn't work when the 
  validation at `Login form` was `block by country (register, lost password)`.
  In this release, the login-fail-counter works correctly.
* **Bug fix:** Fix the issue that the validation settings of `Admin area` and 
  `Admin ajax/post` were influential with each other. Now each of those works 
  individually.
* **Bug fix:** "Site Stats" of Jetpack is now shown on the admin bar which 
  issue was reported on [support forum](https://wordpress.org/support/topic/admin-area-prevent-zero-day-exploit-incompatible-with-jetpack-site-stats-in-a "WordPress › Support » Admin area - Prevent zero-day exploit: Incompatible with Jetpack Site Stats in A").
* **Improvement:** Hide checking the existence of log db behind the symbol 
  `IP_GEO_BLOCK_DEBUG` to reduce 1 query on admin screen.
* **Improvement:** Add alternative functions of BCMath extension to avoid 
  `PHP Fatal error: Call to undefined function` in `IP2Location.php` when 
  IPv6 is specified.
* **Improvement:** Use MaxMind database at the activating process not to be 
  locked out by means of inconsistency of database at the activation and after.
* See more details at [2.1.2 release note](http://www.ipgeoblock.com/changelog/release-2.1.2.html "2.1.2 Release Note").

= 2.1.1 =
* **New feature:** Added `Block by country (register, lost password)` at 
  `Login form` on `Settings` tab in order to accept the registered users as 
  membership from anywhere but block the request of new user ragistration and 
  lost password by the country code. Is't suitable for BuddyPress and bbPress.
* **Improvement:** Added showing the custom error page for http response code 
  4xx and 5xx. For example the `403.php` in the theme template directory or in 
  the child theme directory is used if it exists. And new filter hooks 
  `ip-geo-block-(comment|xmlrpc|login|admin)-(status|reason)` are available 
  to customize the response code and reason for human.
* **Obsoleted:** Obsoleted the filter hooks 
  `ip-geo-block-(admin-actions|admin-pages|wp-content)`. Alternatively new 
  filter hooks `ip-geo-block-bypass-(admins|plugins|themes)` are added to 
  bypass WP-ZEP.
* Find out more details in the [2.1.1 release note](http://www.ipgeoblock.com/changelog/release-2.1.1.html "2.1.1 Release Note").

= 2.1.0 =
* **New feature:** Expanded the operating range of ZP-ZEP, that includes admin 
  area, plugins area, themes area. Now it can prevent a direct malicios attack 
  to the file in plugins and themes area. Please go to the "Validation Settings"
  on "Settings" tab and check it. Also check my article in 
  "[Analysis of Attack Vector against WP Plugins](http://www.ipgeoblock.com/article/analysis-attack-vector.html)".
* **Bug fix:** Fixed the issue that action hook `ip-geo-block-backup-dir` did 
  not work correctly because the order of argument was mismatched.
* **Bug fix:** Fixed the issue that a record including utf8 4 bytes character 
  in its columns was not logged into DB in WordPress 4.2.
* **Improvement:** Fixed the issue that Referrer Suppressor do nothing with a 
  new element which is added into DOM after DOM ready. The event handler is 
  now delegated at the `body`.

= 2.0.8 =
* Fixed an issue that a certain type of attack vector to the admin area (
  [example](https://blog.sucuri.net/2014/08/database-takeover-in-custom-contact-forms.html "Critical Vulnerability Disclosed on WordPress Custom Contact Forms Plugin")
  ) could not be blocked by the reason that some plugins accept it on earlier 
  hook (ie `init`) than this plugin (previously `admin_init`).
* Added re-creating DB table for validation logs in case of accidentally 
  failed at activation process.
* The time of day is shown with local time by adding GMT offset based on 
  the time zone setting.
* Optimized resource loading and settings to avoid redundancy.
* See details at [this plugin's blog](http://www.ipgeoblock.com/changelog/release-2.0.8.html "2.0.8 Release Note").

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
* Referrer suppressor now supports [meta referrer](https://wiki.whatwg.org/wiki/Meta_referrer "Meta referrer - WHATWG Wiki")

= 2.0.3 =
* **Bug fix:** Fixed an issue that empty black list doesn't work correctly 
  when matching rule is black list.
* **New feature:** Added 'Zero-day Exploit Prevention for wp-admin'.
  Because it is an experimental feature, please open a new issue at 
  [support forum](https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block")
  if you have any troubles with it.
* **New feature:** Referrer suppressor for external link. When you click an 
  external hyperlink on admin screen, http referrer will be suppressed to 
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
