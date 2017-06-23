=== IP Geo Block ===
Contributors: tokkonopapa
Donate link:
Tags: security, firewall, brute force, vulnerability, login, wp-admin, admin, ajax, xmlrpc, comment, pingback, trackback, spam, IP address, geo, geolocation, buddypress, bbPress
Requires at least: 3.7
Tested up to: 4.8
Stable tag: 3.0.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

It blocks spam posts, login attempts and malicious access to the back-end requested from the specific countries, and also prevents zero-day exploit.

== Description ==

A considerable number of WordPress vulnerabilities in plugins and themes have been disclosed every month. You can easily find them at [WPScan Vulnerability Database](https://wpvulndb.com/ "WPScan Vulnerability Database") and [Exploits Database](https://www.exploit-db.com/ "Exploits Database by Offensive Security"). It means that many WordPress sites can be always exposed to the threats of being exploited caused by those vulnerabilities.

This plugin protects your site against such threats of attack to the back-end of your site such as login form (login attempt) and admin area. And it also blocks undesired requests to the comment form (spam and trackback), XML-RPC (login attempt and pingback) and even public facing pages aka front-end.

= Features =

* **Immigration control:**  
  Access to the basic and important entrances into the back-end such as `wp-comments-post.php`, `xmlrpc.php`, `wp-login.php`, `wp-signup.php`, `wp-admin/admin.php`, `wp-admin/admin-ajax.php`, `wp-admin/admin-post.php` will be validated by means of a country code based on IP address. It allows you to configure either whitelist or blacklist to specify the countires.

* **Zero-day Exploit Prevention:**  
  Unlike other security plugins and firewalls based on attack patterns, the original feature "**W**ord**P**ress **Z**ero-day **E**xploit **P**revention" (WP-ZEP) is focused on vulnerability patterns. It is simple but still smart and strong enough to block any malicious accesses to `wp-admin/*.php`, `plugins/*.php` and `themes/*.php` even from the permitted countries. It will protect your site against certain types of attack such as CSRF, LFI, SQLi, XSS and so on, **even if you have some vulnerable plugins and themes in your site**.

* **Guard against login attempts:**  
  In order to prevent hacking through the login form and XML-RPC by brute-force and the reverse-brute-force attacks, the number of login attempts will be limited per IP address even from the permitted countries.

* **Minimize server load against brute-force attacks:**  
  You can configure this plugin as a [Must Use Plugins](https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex") which would be loaded prior to regular plugins and can massively [reduce the load on server](http://www.ipgeoblock.com/codex/validation-timing.html "Validation timing | IP Geo Block").

* **Prevent malicious down/uploading:**  
  A malicious request such as exposing `wp-config.php` and uploading undesired files via vulnerable plugins or themes can be blocked.

* **Support of BuddyPress and bbPress:**  
  You can configure this plugin such that a registered user can login as the membership from anywhere, but a request such as a new user registration, lost password, creating a new topic, and subscribing comment is blocked by the country code. It is suitable for [BuddyPress](https://wordpress.org/plugins/buddypress/ "BuddyPress &mdash; WordPress Plugins") and [bbPress](https://wordpress.org/plugins/bbpress/ "WordPress › bbPress « WordPress Plugins") to help reducing spams.

* **Referrer suppressor for external links:**  
  When you click an external hyperlink on admin screens, http referrer will be eliminated to hide a footprint of your site.

* **Multiple source of IP Geolocation databases:**  
  [MaxMind GeoLite free databases](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention") and [IP2Location LITE databases](http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location") can be installed in this plugin. Those will be downloaded and updated (once a month) automatically. Also free Geolocation REST APIs and whois information can be available for audit purposes.  
  Father more, if you use CloudFlare or CloudFront as a reverse proxy service, you can get [dedicated API class libraries](http://www.ipgeoblock.com/article/api-class-library.html "CloudFlare & CloudFront API class library | IP Geo Block") for these services.

* **Customizing response:**  
  HTTP response code can be selectable as `403 Forbidden` to deny access pages, `404 Not Found` to hide pages or even `200 OK` to redirect to the top page.
  You can also have the custom error page (for example `403.php`) in your theme template directory or child theme directory to fit your theme.

* **Validation logs:**  
  Validation logs can be recorded with useful information to audit attack patterns.

* **Cooperation with full spec security plugin:**  
  This plugin is simple and lite enough to be able to cooperate with other full spec security plugin such as [Wordfence Security](https://wordpress.org/plugins/wordfence/ "Wordfence Security &mdash; WordPress Plugins"). See [this report](http://www.ipgeoblock.com/codex/page-speed-performance.html "Page speed performance | IP Geo Block") about page speed performance.

* **Extendability:**  
  You can customize the behavior of this plugin via `add_filter()` with [pre-defined filter hook](http://www.ipgeoblock.com/codex/ "Codex | IP Geo Block"). See various use cases in [samples.php](https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub") bundled within this package.
  You can also get the extension [IP Geo Allow](https://github.com/ddur/WordPress-IP-Geo-Allow "GitHub - ddur/WordPress-IP-Geo-Allow: WordPress Plugin Exension for WordPress-IP-Geo-Block Plugin") by [Dragan](https://github.com/ddur "ddur (Dragan) - GitHub"). It makes admin screens strictly private with more flexible way than specifying IP addresses.

* **Self blocking prevention and easy rescue:**  
  Website owners do not prefer themselves to be blocked. This plugin prevents such a sad thing unless you force it. And futhermore, if such a situation occurs, you can [rescue yourself](http://www.ipgeoblock.com/codex/what-should-i-do-when-i-m-locked-out.html "What should I do when I'm locked out? | IP Geo Block") easily.

* **Clean uninstallation:**  
  Nothing is left in your precious mySQL database after uninstallation. So you can feel free to install and activate to make a trial of this plugin's functionality. Several days later, you'll find many undesirable accesses in your validation logs if all validation targets are enabled.

= Attribution =

This package includes GeoLite library distributed by MaxMind, available from [MaxMind](http://www.maxmind.com "MaxMind - IP Geolocation and Online Fraud Prevention"), and also includes IP2Location open source libraries available from [IP2Location](http://www.ip2location.com "IP Address Geolocation to Identify Website Visitor's Geographical Location").

Also thanks for providing the following great services and REST APIs for free.

* [http://freegeoip.net/](http://freegeoip.net/ "freegeoip.net: FREE IP Geolocation Web Service") (IPv4 / free)
* [http://ipinfo.io/](http://ipinfo.io/ "ipinfo.io - ip address information including geolocation, hostname and network details") (IPv4, IPv6 / free)
* [http://geoip.nekudo.com/](http://geoip.nekudo.com/ "Free IP GeoLocation/GeoIp API - geoip.nekudo.com") (IPv4, IPv6 / free)
* [http://xhanch.com/](http://xhanch.com/xhanch-api-ip-get-detail/ "Xhanch API &#8211; IP Get Detail | Xhanch Studio") (IPv4 / free)
* [http://geoiplookup.net/](http://geoiplookup.net/ "What Is My IP Address | GeoIP Lookup") (IPv4, IPv6 / free)
* [http://ip-api.com/](http://ip-api.com/ "IP-API.com - Free Geolocation API") (IPv4, IPv6 / free for non-commercial use)
* [http://ipinfodb.com/](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") (IPv4, IPv6 / free for registered user, need API key)

= Development =

Development of this plugin is promoted at [WordPress-IP-Geo-Block](https://github.com/tokkonopapa/WordPress-IP-Geo-Block "tokkonopapa/WordPress-IP-Geo-Block - GitHub") and class libraries to handle geo-location database are developed separately as "add-in"s at [WordPress-IP-Geo-API](https://github.com/tokkonopapa/WordPress-IP-Geo-API "tokkonopapa/WordPress-IP-Geo-API - GitHub").

All contributions will always be welcome. Or visit my [development blog](http://www.ipgeoblock.com/ "IP Geo Block").

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'IP Geo Block'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard
5. Try 'Best settings' button for easy setup at the bottom of this plugin's setting page.

Please refer to [the document](http://www.ipgeoblock.com/codex/ "Codex | IP Geo Block") 
or following descriptions for your best setup.

= Validation rule settings =

* **Matching rule**  
  Choose either `White list` (recommended) or `Black list` to specify the countries from which you want to pass or block.

* **Country code for matching rule**  
  Specify the country code with two letters (see [ISO 3166-1 alpha-2](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "ISO 3166-1 alpha-2 - Wikipedia, the free encyclopedia")). Each of them should be separated by comma.

* **White/Black list of extra IPs for prior validation**  
  The list of extra IP addresses prior to the validation of country code. [CIDR notation](https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing "Classless Inter-Domain Routing - Wikipedia, the free encyclopedia") is acceptable to specify the range.

* **$_SERVER keys for extra IPs**  
  Additional IP addresses will be validated if some of keys in `$_SERVER` variable are specified in this textfield. Typically `HTTP_X_FORWARDED_FOR`.

* **Bad signatures in query**
  It validates malicious signatures independently of **Block by country** and **Prevent Zero-day Exploit** for the target **Admin area**, **Admin ajax/post**, **Plugins area** and **Themes area**. Typically, `/wp-config.php` and `/passwd`.

* **Response code**  
  Choose one of the [response code](http://tools.ietf.org/html/rfc2616#section-10 "RFC 2616 - Hypertext Transfer Protocol -- HTTP/1.1") to be sent when it blocks a comment.
  The 2xx code will lead to your top page, the 3xx code will redirect to [Black Hole Server](http://blackhole.webpagetest.org/), the 4xx code will lead to WordPress error page, and the 5xx will pretend an server error.

* **Validation timing**  
  Choose **"init" action hook** or **"mu-plugins" (ip-geo-block-mu.php)** to specify the timing of validation.

= Back-end target settings =

* **Comment post**  
  Validate post to `wp-comment-post.php`. Comment post and trackback will be validated.

* **XML-RPC**  
  Validate access to `xmlrpc.php`. Pingback and other remote command with username and password will be validated.

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

= Front-end target settings =

* **Block by country**  
  Enables validation of country code on public facing pages.

* **Matching rule**  
  Same as **Validation target settings** but can be set independently.

* **Validation target**  
  Specify the single and archive page by post type, category and tag as blocking target.

* **UA string and qualification**  
  Additional rules targeted at SEO which can specify acceptable requests based on user agent.

* **Simulation mode**  
  You can simulate the 'blocking on front-end' functionality before deploying.

= Geolocation API settings =

* **API selection and key settings**  
  If you wish to use `IPInfoDB`, you should register at [their site](http://ipinfodb.com/ "IPInfoDB | Free IP Address Geolocation Tools") to get a free API key and set it into the textfield. And `ip-api.com` and `Smart-IP.net` require non-commercial use.

= Local database settings settings =

* **Auto updating (once a month)**  
  If `Enable`, Maxmind GeoLite database will be downloaded automatically by WordPress cron job.

= Record settings =

* **Record validation statistics**  
  If `Enable`, you can see `Statistics of validation` on Statistics tab.

* **Record validation logs**  
  If you choose anything but `Disable`, you can see `Validation logs` on Logs tab.

* **$_POST keys in logs**  
  Normally, you can see just keys at `$_POST data:` on Logs tab. If you put some of interested keys into this textfield, you can see the value of key like `key=value`.

* **Anonymize IP address**  
  It will mask the last three digits of IP address when it is recorded into the log.

= Cache settings =

* **Expiration time [sec]**  
  Maximum time in sec to keep cache.

* **Garbage collection period [sec]**  
  Period of garbage collection to clean cache.

= Submission settings =

* **Text position on comment form**  
  If you want to put some text message on your comment form, please choose `Top` or `Bottom` and put text with some tags into the **Text message on comment form** textfield.

= Plugin settings =

* **Remove settings at uninstallation**  
  If you checked this option, all settings will be removed when this plugin is uninstalled for clean uninstalling.

== Frequently Asked Questions ==

= Does it support multisite? =

It works on multisite, but there's no network setting at this moment.

= Does this plugin works well with caching? =

The short answer is **YES**, especially for the purpose of security e.g. blocking malicious access both on the back-end and on the front-end.

The long answer is as follows:

For the back-end protection, both blocking malicious access and blocking by country works fine, if you disable caching on the back-end. As for the front-end, there are 2 scenarios.

The first one is the case that there's no cached page against a request to the specific page. In this scenario, this plugin responds a specific HTTP status code (including redirection) and defines the symbol `DONOTCACHEPAGE` when the request comes from blacklisted countries (or IPs). When the request comes from the whitelisted countries (or IPs), this plugin passes it to the caching plugin in order to generate a new cache.

The second scenario is the case that there's a cached page. In this case, the response depends on the caching method you are employing. Currently, the following plugins and configurations can be supported if you want to [restrict content by geo-blocking](https://en.wikipedia.org/wiki/Geo-blocking "Geo-blocking - Wikipedia"):

- [WP Super Cache](https://wordpress.org/plugins/wp-super-cache/ "WP Super Cache &mdash; WordPress Plugins")  
  Select "**Use PHP to serve cache files**" and enable "**Late init**".

- [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/ "W3 Total Cache &mdash; WordPress Plugins")  
  Select "**Disk: Basic**" and enable "**Late initialization**" for page cache. "**Disk: Enhanced**" (where "**Late initialization**" is not available) in W3TC 0.9.5.1 seems to work good without any imcompatibility with this plugin.

- [Vendi Cache](https://wordpress.org/plugins/vendi-cache/ "Vendi Cache &mdash; WordPress Plugins")  
  This plugin was formerly built in Wordfence. Select "**basic caching**" for Vendi Cache and **"mu-plugin" (ip-geo-block-mu.php)** for IP Geo Block.

Other plugins adopting `mod_rewrite` (e.g. WP Fastest Cache) or `advanced-cache.php` [drop-in](https://make.wordpress.org/core/2016/08/13/global-overloading-in-advanced-cache-php/ "Global overloading in advanced-cache.php &#8211; Make WordPress Core") (e.g. Comet Cache) or other caching method at server side might serve a normal page.

Thus your site would have less risk against the exploiting via vulnerable plugins and themes.

For more details, please refer to some documents at "[Blocking on front-end](http://www.ipgeoblock.com/codex/#blocking-on-front-end 'Codex | IP Geo Block')".

= I still have access from blacklisted country. Does it work correctly? =

Absolutely, YES.

Sometimes, a Wordfence Security user would report this type of claim when he/she found some accesses in its Live traffic view. But please don't worry. Before WordPress runs, Wordfence cleverly filters out malicious requests to your site using <a href="http://php.net/manual/en/ini.core.php#ini.auto-prepend-file" title="PHP: Description of core php.ini directives - Manual">auto_prepend_file</a> directive to include PHP based Web Application Firewall. Then this plugin validates the rest of the requests that pass over Wordfence because those were not in WAF rules, especially you enables "**Prevent Zero-day Exploit**".

It would also possibly be caused by the accuracy of country code in the geolocation databases. Actually, there is a case that a same IP address has different country code.

For more detail, please refer to "[I still have access from blacklisted country.](http://www.ipgeoblock.com/codex/access-from-blacklisted-country.html 'I still have access from blacklisted country. | IP Geo Block')".

= How can I test this plugin works? =

The easiest way is to use [free proxy browser addon](https://www.google.com/search?q=free+proxy+browser+addon "free proxy browser addon - Google Search").

Another one is to use [http header browser addon](https://www.google.com/search?q=browser+add+on+modify+http+header "browser add on modify http header - Google Search").

You can add an IP address to the `X-Forwarded-For` header to emulate the access behind the proxy. In this case, you should add `HTTP_X_FORWARDED_FOR` into the "**$_SERVER keys for extra IPs**" on "**Settings**" tab.

See more details at "[How to test prevention of attacks](http://www.ipgeoblock.com/codex/#how-to-test-prevention-of-attacks 'Codex | IP Geo Block')".

= I was locked down. What shall I do? =

You can find the "**Emergent Functionality**" code section near the bottom of `ip-geo-block.php`. This code block can be activated by replacing `/*` (opening multi-line comment) at the top of the line to `//` (single line comment), or `*` at the end of the line to `*/` (closing multi-line comment).

`/**
 * Invalidate blocking behavior in case yourself is locked out.
 *
 * How to use: Activate the following code and upload this file via FTP.
 */
/* -- ADD '/' TO THE TOP OR END OF THIS LINE TO ACTIVATE THE FOLLOWINGS -- */
function ip_geo_block_emergency( $validate ) {
    $validate['result'] = 'passed';
    return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */`

Please not that you have to use an [appropriate editor](https://codex.wordpress.org/Editing_Files#Using_Text_Editors "Editing Files « WordPress Codex").

After saving and uploading it to `/wp-content/plugins/ip-geo-block/` on your server via FTP, you become to be able to login again as an admin.

Remember that you should upload the original one after re-configuration to deactivate this feature.

[This document](http://www.ipgeoblock.com/codex/what-should-i-do-when-i-m-locked-out.html "What should I do when I'm locked out? | IP Geo Block") can also help you.

= Do I have to turn on all the selection to enhance security? =

Yes. Roughly speaking, the strategy of this plugin has been constructed as follows:

- **Block by country**  
  It blocks malicious requests from outside your country.

- **Prevent Zero-day Exploit**  
  It blocks malicious requests from your country.

- **Force to load WP core**  
  It blocks the request which has not been covered in the above two.

- **Bad signatures in query**  
  It blocks the request which has not been covered in the above three.

Please try "**Best for Back-end**" button at the bottom of this plugin's setting page for easy setup. And also see more details in "[The best practice of target settings](http://www.ipgeoblock.com/codex/the-best-practice-for-target-settings.html 'The best practice of target settings | IP Geo Block')".

= Does this plugin validate all the requests? =

Unfortunately, no. This plugin can't handle the requests that are not parsed by WordPress. In other words, a standalone file (PHP, CGI or something excutable) that is unrelated to WordPress can't be validated by this plugin even if it is in the WordPress install directory.

But there're exceptions: When you enable "**Force to load WP core**" for **Plugins area** or **Themes area**, a standalone PHP file becomes to be able to be blocked. Sometimes this kind of file has some vulnerabilities. This function protects your site against such a case.

= How to resolve "Sorry, your request cannot be accepted."? =

If you encounter this message, please refer to [this document](http://www.ipgeoblock.com/codex/you-are-not-allowed-to-access.html "Why &ldquo;Sorry, your request cannot be accepted&rdquo; ? | IP Geo Block") to resolve your blocking issue.

If you can't solve your issue, please let me know about it on the [support forum](https://wordpress.org/support/plugin/ip-geo-block/ "View: Plugin Support &laquo;  WordPress.org Forums"). Your logs in this plugin and "**Installation information**" at "**Plugin settings**" will be a great help to resolve the issue.

= How can I fix "Unable to write" error? =

When you enable "**Force to load WP core**" options, this plugin will try to configure `.htaccess` in your `/wp-content/plugins/` and `/wp-content/themes/` directory in order to protect your site against the malicous attacks to the [OMG plugins and themes](http://www.ipgeoblock.com/article/exposure-of-wp-config-php.html "Prevent exposure of wp-config.php | IP Geo Block").

But some servers doesn't give read / write permission against `.htaccess` to WordPress. In this case, you can configure `.htaccess` files by your own hand instead of enabling "**Force to load WP core**" options.

Please refer to "[How can I fix permission troubles?](http://www.ipgeoblock.com/codex/how-can-i-fix-permission-troubles.html 'How can I fix permission troubles? | IP Geo Block')" in order to fix this error.

== Other Notes ==

= Known issues =

* No image is shown after drag & drop a image in grid view at "Media Library". For more details, please refer to [this ticket at Github](https://github.com/tokkonopapa/WordPress-IP-Geo-Block/issues/2 "No image is shown after drag & drop a image in grid view at "Media Library". - Issue #2 - tokkonopapa/WordPress-IP-Geo-Block - GitHub").
* From [WordPress 4.5](https://make.wordpress.org/core/2016/03/09/comment-changes-in-wordpress-4-5/ "Comment Changes in WordPress 4.5 &#8211; Make WordPress Core"), `rel=nofollow` had no longer be attached to the links in `comment_content`. This change prevents to block "[Server Side Request Forgeries](https://www.owasp.org/index.php/Server_Side_Request_Forgery 'Server Side Request Forgery - OWASP')" (not Cross Site but a malicious internal link in the comment field).

== Screenshots ==

1. **IP Geo Plugin** - Settings tab
2. **IP Geo Plugin** - Validation rule settings
3. **IP Geo Plugin** - Back-end target settings
4. **IP Geo Plugin** - Front-end target settings
5. **IP Geo Plugin** - Geolocation API settings
6. **IP Geo Plugin** - IP address cache settings
7. **IP Geo Plugin** - Statistics tab
8. **IP Geo Plugin** - Logs tab
9. **IP Geo Plugin** - Search tab
10. **IP Geo Plugin** - Attribution tab

== Changelog ==

= 3.0.3.1 =
This is a maintenance release addressing various internal improvement.

* **Bug fix:** Some issues caused by IE10/11 on admin pages had been fixed.
* **Bug fix:** Turning off check boxes in "**API selection and key settings**" section now becomes to work.
* **Improvement:** Better validation performance for logged in user authentication.
* **Improvement:** Better rendering by CSS and JS for sections.
* **Improvement:** Better handling of cookie for sections.
* **Improvement:** Better handling of server and private IP address.
* **Improvement:** Better compatibility with file operations using Filesystem API. FTP or SSH based operations are now supported only when [some symbols are defined in `wp-config.php`](https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants "Editing wp-config.php &laquo; WordPress Codex").
* **Improvement:** Better timing of upgrade check at activation phase instead of `init` action hook.

= 3.0.3 =
* **New feature:** New option "Prevent malicious upload" to restrict MIME types.
* **New feature:** New option "Response code" and "Response message" for front-end. This is useful not to violate your affiliate program.
* **Improvement:** New Option "DNS reverse lookup" to enable/disable.
* **Improvement:** Stop rendering by javascript on setting pages to reduce flash of unstyled content.
* **Improvement:** Better compatibility of WP-ZEP with some plugins (Wordfence, Imagify) that request ajax from server side.
* **Improvement:** Better handling of server and private IP address.
* **Bug fix:** Fix the bug of "Export/Import settings". **Please export json file again if you hold it as backup purpose** because some of settings data might be incompatible.
* **Bug fix:** Fix the bug of "Password Reset" caused by miss-spelling "resetpasss".
* See some details at [release 3.0.3](http://www.ipgeoblock.com/changelog/release-3.0.3.html "3.0.3 Release Note | IP Geo Block").

= 3.0.2.2 =
* **Improvement:** Change the behavior of "Referrer Suppressor" not to open a new window on public facing pages.
* **Improvement:** Improve some of the descriptions of help text.
* **Bug fix:** Fix the bug of undefined symbol in admin class related to the Google Map API.
* **Bug fix:** Fix the bug of incompatible function arguments when the number of login fails reaches the limit.
* **Bug fix:** Fix the issue of not working blocking by country on specific pages correctly as the validation target.

= 3.0.2.1 =
This is a maintenance release addressing some issues.

* **Update:** Net_DNS2, Net_IPv6, Net_IPv4 to the newest.
* **Update:** Geolocation database API for Maxmind and IP2Location to 1.1.8.
* **Update:** Bring back the priority of validation for wp-zep and badsig as same as 3.0.2 and before.
* **Improvement:** Handle some of loop back and private IP addresses for localhost and host inside load balancer.
* **Improvement:** Update instructions when the geolocation API libraries fails to install.
* **Bug fix:** Fix the blocking issue of admin ajax/post on front-end.
* **Bug fix:** Fix the issue of improper IPv6 handling on setting page.

= 3.0.2 =
* **New feature:** Add "Exceptions" for "Admin ajax/post" to specify the name of action which causes undesired blocking (typically on the public facing pages).
* **Improvement:** Add "Disable" to "Max number of failed login attempts per IP address" to avoid conflict with other similar plugin.
* **Improvement:** Update geolocation database libraries to 1.1.7 for better compatibility on some platform.
* **Trial feature:** Add custom action hook `ip-geo-block-send-response`. This is useful to control firewall via [fail2ban](http://www.fail2ban.org/ "Fail2ban") like [WP fail2ban](https://wordpress.org/plugins/wp-fail2ban/ "WP fail2ban - WordPress Plugins").
* See some details at [release 3.0.2](http://www.ipgeoblock.com/changelog/release-3.0.2.html "3.0.2 Release Note | IP Geo Block").

= 3.0.1.2 =
* **Bug fix:** Fix the blocking issue in some environments when upgrading from 2.2.9.1 to 3.0.0.
* **Bug fix:** Fix the blocking issue at opening a new window via context menu on dashboard.
* **Bug fix:** Fix the potential issue of 500 Internal error in cron job.
* **Improvement:** Revive 410 Gone for response code.
* **Improvement:** Prevent the issue of resetting matching rule and country code at upgrading.

= 3.0.1.1 =
* **Bug fix:** Fix the issue where **Login form** could not be disabled on **Back-end target settings**.
* **Bug fix:** Fix the issue where trackback and pingback could not be blocked since 2.2.4.
* **Improved:** Apply the action hook 'pre_trackback_post' that was introduced in WP 4.7.0.
* **Improved:** Use 'safe_redirect()' instead of 'redirect()' for secured internal redirection. If you set an external url for **Redirect URL**, please use the filter hook 'allowed_redirect_hosts'.
* **Improved:** Better compatibility with the plugin "Anti-Malware Security and Brute-Force Firewall".

= 3.0.1 =
* **Bug fix:** Add lock mechanism for local geolocation DBs to avoid potential fatal error.
* **Improvement:** Add self blocking prevention potentially caused by login attempts with the same IP address of logged in user.
* **New feature:** Add "**Installation information**" button to make it easy to submit an issue at support forum.

= 3.0.0 =
* **New feature:** Add the function of blocking on front-end.
* **New filter hook:** Add `ip-geo-block-public` to extend validation on front-end.
* **Improvement:** Avoid conflict with "Open external links in a new window" plugin and some other reason to prevent duplicated window open. For more detail, see [this discussion at support forum](https://wordpress.org/support/topic/ip-geoblock-opens-2-windows-on-link-clicks-when-user-is-logged-in/ "Topic: IP Geoblock opens 2 windows on link clicks when user is logged in &laquo; WordPress.org Forums").
* **Improvement:** Better compatibility with some plugins, themes and widgets.
* **Improvement:** Deferred execution of SQL command to improve the response.
* **Improvement:** Make the response compatible with WP original when it is requested by GET method.
* See some details at [release 3.0.0](http://www.ipgeoblock.com/changelog/release-3.0.0.html "3.0.0 Release Note | IP Geo Block").

= 2.2.9.1 =
* **Bug fix:** Blocking Wordfence scanning. ([@](https://wordpress.org/support/topic/wordfence-conflict-2/ "WordFence Conflict"))
* **Bug fix:** Illegal elimination of colon in text field for IP address. ([@](https://wordpress.org/support/topic/adding-ipv6-to-white-list/ "Adding IPv6 to white list"))
* **Improved:** Compatibility with PHP 7 that cause to feel relaxed. ([@](https://wordpress.org/support/topic/plans-for-php-7-compatiblity/ "Plans for PHP 7 compatiblity?"))
* **Improved:** Avoid resetting whitelist on update by InfiniteWP. ([@](https://wordpress.org/support/topic/whitelist-resets-on-update/ "[Resolved] Whitelist resets on update"))
* **Trial feature:** `X-Robots-Tag` HTTP header with `noindex, nofollow` for login page. ([@](https://wordpress.org/support/topic/ip-geo-block-and-searchmachines/ "IP GEo-block and searchmachines"))

= 2.2.9 =
* **New feature:** A new option that makes this plugin configured as a "Must-use plugin". It can massively reduce the server load especially against brute-force attacks because it initiates this plugin prior to other typical plugins.
* **Improvement:** Validation of a certain signature against XSS is internally added to "Bad signature in query" by default.
* **Improvement:** Improved compatibility with PHP 7 (Thanks to [FireMyst](https://wordpress.org/support/topic/plans-for-php-7-compatiblity/ "Topic: Plans for PHP 7 compatiblity? &laquo; WordPress.org Forums")).
* Find details in [2.2.9 Release Note](http://www.ipgeoblock.com/changelog/release-2.2.9.html "2.2.9 Release Note").

= 2.2.8.2 =
* **Bug fix:** Fixed the mismatched internal version number.

= 2.2.8.1 =
* **Bug fix:** Fixed the issue of undefined function `wp_get_raw_referer()` error that happened under certain condition. See [the issue](https://wordpress.org/support/topic/since-php-update-fatal-error-everytime-i-want-to-edit-a-post/ "Since PHP update Fatal error everytime I want to edit a post") at forum.
* **Improved:** Avoid resetting country code on update. See [the issue](https://wordpress.org/support/topic/whitelist-resets-on-update/ "Whitelist resets on update") at forum.

= 2.2.8 =
* **Bug fix:** Fixed the issue of stripping some required characters for Google maps API key.
* **New feature:** Whois database Lookup for IP address on search tab.
* **Update:** Updated geolocation API libraries and services.
* Find more details in [2.2.8 Release Note](http://www.ipgeoblock.com/changelog/release-2.2.8.html "2.2.8 Release Note").

= 2.2.7 =
* **Bug fix:** Fix inadequate validation of "**Bad signatures in query**".
* **Improvement:** Add fallback for Google Maps API key ([@](https://wordpress.org/support/topic/226-problem-with-search-resp-google-maps "WordPress &#8250; Support &raquo; [2.2.6] Problem with SEARCH resp. Google Maps")) and corruption of "Bad signatures" ([@](https://wordpress.org/support/topic/226-problem-with-bad-signatures-in-query "WordPress &#8250; Support &raquo; [2.2.6] Problem with &quot;Bad signatures in query&quot;")).
* **Update:** Update geolocation service api.
* Find details about Google Maps API in [2.2.7 Release Note](http://www.ipgeoblock.com/changelog/release-2.2.7.html "2.2.7 Release Note").

= 2.2.6 =
* **New feature:** Add saving csv file of logs in "Logs" tab.
* **New feature:** Add filter hook `ip-geo-block-record-log` to control over the conditions of recording in more detail.
* **Bug fix:** Fixed the issue that "Exceptions" for Plugins/Themes area does not work properly. Please confirm your settings again.
* See details at [release 2.2.6](http://www.ipgeoblock.com/changelog/release-2.2.6.html "2.2.6 Release Note").

= 2.2.5 =
* **New feature:** On the settings page, you can specify the pliugin or theme which would cause undesired blocking in order to exclude it from the validation target without embedding any codes into `functions.php`.
* **Improvement:** Optimize resource loading on admin dashboard.
* **Improvement:** Support clean uninstall for network / multisite.
* **Improvement:** Improve the compatibility of downloading IP address databases for Microsoft IIS.
* **Bug fix:** Support `FORCE_SSL_ADMIN`.
* **Bug fix:** Fix the issue of [@](https://wordpress.org/support/topic/compatibility-with-ag-custom-admin "WordPress › Support » Compatibility with AG Custom Admin") and change the option name "**Important files**" to "**Bad signatures in query**" to avoid misuse.
* **Bug fix:** Fix the issue of [@](https://wordpress.org/support/topic/gb-added-to-whitelist "WordPress › Support » GB added to whitelist") which might be caused by some race condition.
* **Bug fix:** Fix the issue of restoring post revisions which was blocked.

= 2.2.4.1 =
Sorry for frequent updating.

* **Bug fix:** Fixed the issue of `Warning: strpos(): Empty needle in...` that was reported in [@](https://wordpress.org/support/topic/version-224-produces-warning-message "WordPress › Support » Version 2.2.4 Produces Warning Message") and [@](https://wordpress.org/support/topic/error-after-update-to-newest-version "WordPress › Support » Error after Update to newest version").

= 2.2.4 =
* **Bug fix:** Fixed the issue that some links on network admin of multisite were blocked when WP-ZEP for `admin area` or `admin ajax/post` was enabled.
* **New feature:** Added configure of `.htaccess` for the plugins/themes area.
* **Enhancement:** Added `wp-signup.php` to the list of validation target.
* **Enhancement:** Added exporting and importing the setting parameters.
* **Improvement:** Made the logout url compatible with [Rename wp-login.php](https://wordpress.org/plugins/rename-wp-login/).
* **Improvement:** Made condition of validation more strictly at admin diagnosis to prevent unnecessary notice of self blocking. ([@](https://wordpress.org/support/topic/youll-be-blocked-after-you-log-out-notice-doesnt-disappear "[resolved] &quot;You'll be blocked after you log out&quot; notice doesn't disappear"))
* **Improvement:** Improved some of UI. ([@](https://wordpress.org/support/topic/possible-to-select-which-countries-are-blocked "[resolved] Possible to select which countries are blocked?"), [@](https://wordpress.org/support/topic/ip-geo-block-black-list "IP Geo Block Black List"))
* See some details at [release 2.2.4](http://www.ipgeoblock.com/changelog/release-2.2.4.html "2.2.4 Release Note").

= 2.2.3.1 =
* **Bug fix:** Fixed the issue that disabled validation target was still blocked by country. ([@](https://wordpress.org/support/topic/logs-whitelist-comments-still-blocked "[resolved] logs whitelist comments still blocked?"))
* **Improvement:** Better handling of charset and errors for MySQL. ([@](https://wordpress.org/support/topic/whitelist-log "[resolved] Whitelist + Log"))

= 2.2.3 =
* **Improvement:** Since WordPress 4.4, XML-RPC system.multicall is disabled when the authentication fails, but still processed all the methods to the end. Now this plugin immediately blocks the request when the authentication fails without processing the rest of the methods.
* **Improvement:** Add UI to change the maximum number of login attempts.
* **Improvement:** Add a fallback process of setting up the directory where the geo location database APIs should be installed. It will be set as `wp-content/uploads/` instead of `wp-content/plugins/ip-geo-block/` or `wp-content/` in case of being unable to obtain proper permission. ([@](https://wordpress.org/support/topic/deactivated-after-updte-why "[resolved] Deactivated after update - why?"), [@](https://wordpress.org/support/topic/the-plugin-caused-an-error-message "[resolved] The plugin caused an error message"))
* **Improvement:** Moderate the conditions of redirection after logout. ([@](https://wordpress.org/support/topic/logout-redirect-doesnt-work-when-plugin-is-active "[resolved] Logout redirect doesn't work when plugin is active"))
* **Improvement:** Prevent self blocking caused by irrelevant signature. ([@](https://wordpress.org/support/topic/works-too-well-blocked-my-wp-admin-myself "[resolved] Works too well - Blocked my wp-admin myself"))
* **Bug fix:** Fixed the issue of conflicting with certain plugins due to the irrelevant handling of js event. ([@](https://wordpress.org/support/topic/cannot-edit-pages-when-ip-geo-block-is-enabled "[resolved] Cannot edit pages when ip-geo-block is enabled."))
* **New feature:** Add "Blocked per day" graph for the daily statistics.
* See some details at [2.2.3 release note](http://www.ipgeoblock.com/changelog/release-2.2.3.html "2.2.3 Release Note").

= 2.2.2.3 =
Sorry for frequent update again but the following obvious bugs should be fixed.

* **Bug fix:** Fixed the issue of not initializing country code at activation.
* **Bug fix:** Fixed the issue that scheme less notation like '//example.com' could not be handled correctly.

= 2.2.2.2 =
Sorry for frequent update.

* **Bug fix:** Fixed the issue of race condition at activation. This fix is related to the urgent security update at **2.2.2.1 which was not actually the security issue but a bug**. See [this thread](https://wordpress.org/support/topic/white-list-hack "white list hack") about little more details.
* **Improvement:** Improved the compatibility with Jetpack.

= 2.2.2.1 =
* **Urgent security update:** Killed the possibility of the options being altered.

= 2.2.2 =
* **Enhancement:** Refactored some codes and components. The number of attacks that can be proccessed per second has been improved by 25% at the maximum.
* **Improvement:** In the previous version, the statistical data was recorded into `wp_options`. It caused the uncertainty of recording especially in case of burst attacks. Now the data will be recorded in an independent table to improve this issue.
* **Bug fix:** Fixed conflict with NextGEN Gallary Pro. Thanks to [bodowewer](https://wordpress.org/support/profile/bodowewer).
* **Bug fix:** Fixed some filter hooks that did not work as intended.
* See more details at [2.2.2 release note](http://www.ipgeoblock.com/changelog/release-2.2.2.html "2.2.2 Release Note").

= 2.2.1.1 =
* **Bug fix:** Fixed "open_basedir restriction" issue caused by `file_exists()`.

= 2.2.1 =
* **Enhancement:** In previous version, local geolocation databases will always be removed and downloaded again at every upgrading. Now, the class library for Maxmind and IP2Location have become independent of this plugin and you can put them outside this plugin in order to cut the above useless process. The library can be available from [WordPress-IP-Geo-API](https://github.com/tokkonopapa/WordPress-IP-Geo-API).
* **Deprecated:** Cooperation with IP2Location plugins such as [IP2Location Tags](http://wordpress.org/plugins/ip2location-tags/ "WordPress - IP2Location Tags - WordPress Plugins"), [IP2Location Variables](http://wordpress.org/plugins/ip2location-variables/ "WordPress - IP2Location Variables - WordPress Plugins"), [IP2Location Country Blocker](http://wordpress.org/plugins/ip2location-country-blocker/ "WordPress - IP2Location Country Blocker - WordPress Plugins") is out of use. Instead of it, free [IP2Location LITE databases for IPv4 and IPv6](http://lite.ip2location.com/ "Free IP Geolocation Database") will be downloaded.
* **Improvement:** Improved connectivity with Jetpack.
* **Improvement:** Improved immediacy of downloading databases at upgrading.
* **Improvement:** Replaced a terminated RESTful API service with a new stuff.
* **Bug fix:** Fixed issue that clicking a link tag without href always refreshed the page. Thanks to [wyclef](https://wordpress.org/support/topic/conflict-with-menu-editor-plugin "WordPress › Support » Conflict with Menu Editor plugin?").
* **Bug fix:** Fixed issue that deactivating and activating repeatedly caused to show the welcome message.
* **Bug fix:** Fixed issue that a misaligned argument in the function caused 500 internal server error when a request to the php files in plugins/themes area was rewrited to `rewrite.php`.

= 2.2.0.1 =
Sorry for frequent update.

* **Fix:** Fixed the issue that some actions of other plugins were blocked.

= 2.2.0 =
* **Important:** Now **Block by country** and **Prevent Zero-day Exploit** become to work independently on **Admin area**, **Admin ajax/post** at **Validation target settings**. Please reconfirm them.
* **Important:** Previously, a request whose country code can't be available was always blocked. But from this release, such a request is considered as comming from the country whose code is `ZZ`. It means that you can put `ZZ` into the white list and black list.
* **New feature:** White list and Black list of extra IP addresses prior to the validation of country code. Thanks to Fabiano for good suggestions at [support forum](https://wordpress.org/support/topic/white-list-of-ip-addresses-or-ranges "WordPress › Support » White list of IP addresses or ranges?")
* **New feature:** Malicious signatures to prevent disclosing the important files via vulnerable plugins or themes. A malicious request to try to expose `wp-config.php` or `passwd` can be blocked.
* **New feature:** Add privacy considerations related to IP address. Add **Anonymize IP address** at **Record settings**.
* **Bug fix:** Fix the issue that spaces in **Text message on comment form** are deleted.
* See details at [2.2.0 release note](http://www.ipgeoblock.com/changelog/release-2.2.0.html "2.2.0 Release Note").

= 2.1.5.1 =
* **Bug fix:** Fixed the issue that the Blacklist did not work properly. Thanks to TJayYay for reporting this issue at [support forum](https://wordpress.org/support/topic/hackers-from-country-in-blocked-list-of-countries-trying-to-login "WordPress › Support » Hackers from country in Blocked List of Countries trying to login").

= 2.1.5 =
* **Enhancement:** Enforce preventing self blocking at the first installation. And add the scan button to get all the country code using selected API. Thanks to **Nils** for a nice idea at [support forum](https://wordpress.org/support/topic/locked-out-due-to-eu-vs-country "WordPress › Support » Locked out due to EU vs. Country").
* **New feature:** Add pie chart to display statistics of "Blocked by country".
* **Enhancement:** WP-ZEP is reinforced against CSRF.
* **Bug fix:** Fix illegal handling of the fragment in a link.
* See details at [2.1.5 release note](http://www.ipgeoblock.com/changelog/release-2.1.5.html "2.1.5 Release Note").

= 2.1.4 =
* **Bug fix:** Fix the issue that this plugin broke functionality of a certain plugin. Thanks to **opsec** for reporting this issue at [support forum](https://wordpress.org/support/topic/blocks-saves-in-types-or-any-plugins-from-wp-typescom "WordPress › Support » Blocks saves in Types or any plugins from wp-types.com").
* **Improvement:** Add checking process for validation rule to prevent being blocked itself. Thanks to **internationals** for proposing at [support forum](https://wordpress.org/support/topic/locked-out-due-to-eu-vs-country "WordPress › Support » Locked out due to EU vs. Country")
* **Improvement:** Arrage the order of setting sections to focus the goal of this plugin.
* See details at [2.1.4 release note](http://www.ipgeoblock.com/changelog/release-2.1.4.html "2.1.4 Release Note").

= 2.1.3 =
* **New feature:** Add "show" / "hide" at each section on the "Settings" tab.
* **New feature:** Add an emergency function that invalidate blocking behavior in case yourself is locked out. This feature is commented out by default at the bottom of `ip-geo-block.php`.
* **Improvement:** Prevent adding query strings to the static resources when users logged in.
* **Improvement:** Improved the compatibility with Autoptimize.
* **Bug fix:** Fix the issue related to showing featured themes on dashboard.
* **Bug fix:** Fix minor bug in `rewrite.php` for the advanced use case.
* See details at [2.1.3 release note](http://www.ipgeoblock.com/changelog/release-2.1.3.html "2.1.3 Release Note").

= 2.1.2 =
This is a maintenance release.

* **Bug fix:** Fix the issue that the login-fail-counter didn't work when the validation at `Login form` was `block by country (register, lost password)`. In this release, the login-fail-counter works correctly.
* **Bug fix:** Fix the issue that the validation settings of `Admin area` and `Admin ajax/post` were influential with each other. Now each of those works individually.
* **Bug fix:** "Site Stats" of Jetpack is now shown on the admin bar which issue was reported on [support forum](https://wordpress.org/support/topic/admin-area-prevent-zero-day-exploit-incompatible-with-jetpack-site-stats-in-a "WordPress › Support » Admin area - Prevent zero-day exploit: Incompatible with Jetpack Site Stats in A").
* **Improvement:** Hide checking the existence of log db behind the symbol `IP_GEO_BLOCK_DEBUG` to reduce 1 query on admin screen.
* **Improvement:** Add alternative functions of BCMath extension to avoid `PHP Fatal error: Call to undefined function` in `IP2Location.php` when IPv6 is specified.
* **Improvement:** Use MaxMind database at the activating process not to be locked out by means of inconsistency of database at the activation and after.
* See more details at [2.1.2 release note](http://www.ipgeoblock.com/changelog/release-2.1.2.html "2.1.2 Release Note").

= 2.1.1 =
* **New feature:** Added `Block by country (register, lost password)` at `Login form` on `Settings` tab in order to accept the registered users as membership from anywhere but block the request of new user ragistration and lost password by the country code. Is't suitable for BuddyPress and bbPress.
* **Improvement:** Added showing the custom error page for http response code 4xx and 5xx. For example the `403.php` in the theme template directory or in the child theme directory is used if it exists. And new filter hooks `ip-geo-block-(comment|xmlrpc|login|admin)-(status|reason)` are available to customize the response code and reason for human.
* **Obsoleted:** Obsoleted the filter hooks `ip-geo-block-(admin-actions|admin-pages|wp-content)`. Alternatively new filter hooks `ip-geo-block-bypass-(admins|plugins|themes)` are added to bypass WP-ZEP.
* Find out more details in the [2.1.1 release note](http://www.ipgeoblock.com/changelog/release-2.1.1.html "2.1.1 Release Note").

= 2.1.0 =
* **New feature:** Expanded the operating range of ZP-ZEP, that includes admin area, plugins area, themes area. Now it can prevent a direct malicios attack to the file in plugins and themes area. Please go to the "Validation Settings" on "Settings" tab and check it. Also check my article in "[Analysis of Attack Vector against WP Plugins](http://www.ipgeoblock.com/article/analysis-attack-vector.html)".
* **Bug fix:** Fixed the issue that action hook `ip-geo-block-backup-dir` did not work correctly because the order of argument was mismatched.
* **Bug fix:** Fixed the issue that a record including utf8 4 bytes character in its columns was not logged into DB in WordPress 4.2.
* **Improvement:** Fixed the issue that Referrer Suppressor do nothing with a new element which is added into DOM after DOM ready. The event handler is now delegated at the `body`.

= 2.0.8 =
* Fixed an issue that a certain type of attack vector to the admin area ([example](https://blog.sucuri.net/2014/08/database-takeover-in-custom-contact-forms.html "Critical Vulnerability Disclosed on WordPress Custom Contact Forms Plugin")) could not be blocked by the reason that some plugins accept it on earlier hook (ie `init`) than this plugin (previously `admin_init`).
* Added re-creating DB table for validation logs in case of accidentally failed at activation process.
* The time of day is shown with local time by adding GMT offset based on the time zone setting.
* Optimized resource loading and settings to avoid redundancy.
* See details at [this plugin's blog](http://www.ipgeoblock.com/changelog/release-2.0.8.html "2.0.8 Release Note").

= 2.0.7 =
* Avoid JavaScript error which occurs if an anchor link has no `href`.
* Improved UI on admin screen.
* Added a diagnosis for creation of database table.

= 2.0.6 =
* Sorry for urgent update but avoid an javascript error.

= 2.0.4 =
* Sorry for frequent update but added a function of showing admin notice when none of the IP geolocation providers is selected. Because the user will be locked out from admin screen when the cache expires.
* **Bug fix:** Fixed an issue of `get_geolocation()` method at a time of when the cache of IP address is cleared.
* Referrer suppressor now supports [meta referrer](https://wiki.whatwg.org/wiki/Meta_referrer "Meta referrer - WHATWG Wiki")

= 2.0.3 =
* **Bug fix:** Fixed an issue that empty black list doesn't work correctly when matching rule is black list.
* **New feature:** Added 'Zero-day Exploit Prevention for wp-admin'. Because it is an experimental feature, please open a new issue at [support forum](https://wordpress.org/support/plugin/ip-geo-block "WordPress &#8250; Support &raquo; IP Geo Block") if you have any troubles with it.
* **New feature:** Referrer suppressor for external link. When you click an external hyperlink on admin screen, http referrer will be suppressed to hide a footprint of your site.
* Also added the filter hook `ip-geo-block-admin-actions` for safe actions on back-end.

= 2.0.2 =
* **New feature:** Include `wp-admin/admin-post.php` as a validation target in the `Admin area`. This feature is to protect against a vulnerability such as [Analysis of the Fancybox-For-WordPress Vulnerability](http://blog.sucuri.net/2015/02/analysis-of-the-fancybox-for-wordpress-vulnerability.html) on Sucuri Blog.
* Added a sample code snippet as a use case for 'Give ajax permission in case of safe actions on front facing page'. See Example 10 in `sample.php`.

= 2.0.1 =
* Fixed the issue of improper scheme from the HTTPS site when loading js for google map.
* In order to prevent accidental disclosure of the length of password, changed the length of `*` (masked password) which is logged into the database.

= 2.0.0 =
* **New feature:** Protection against brute-force and reverse-brute-force attacks to `wp-login.php`, `xmlrpc.php` and admin area. This is an experimental function and can be enabled on `Settings` tab. Malicious access can try to login only 5 times per IP address. This retry counter can be reset to zero by `Clear statistics` on `Statistics` tab.

= 1.0.0 =
* Ready to release.

== Upgrade Notice ==
