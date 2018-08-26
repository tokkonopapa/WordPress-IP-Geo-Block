---
layout: page
language: en
category: codex
section: Dashboard
title: Validation rule settings
---

In addition to blocking based on IP address geolocation information, this 
plugin blocks malicious requests by validating based on some additional rules.
In this section, such validation rules and behavior at blocking are described.

<!--more-->

#### Your IP address / Country ####

The information shown here is your IP address and country code recognized by 
this plugin. The "**Scan country code**" button searches and displays the 
country code of your IP address from multiple geolocation databases. In rare 
cases, databases may indicate different country codes. If an incorrect country 
code is showen, it is a good idea to uncheck the database at "**Geolocation 
API settings**".

Also, if your country code is shown as `XX (Private)`, it means that your 
server is placed behind some reverse proxy server or load balancer. In such 
a case, please put some appropriate keys acquired by PHP and corresponding 
to the HTTP header field, such as [`HTTP_X_FOWARDED_FOR`][X-Forwarded], into 
"**$_SERVER keys to retrieve extra IP addresses**" described later so that a 
public IP address can be retrieved.

#### Matching rule ####

Select either `Whitelist` or `Blacklist`. With this selection, the display 
titles and text boxe in the next section change.

#### [Whitelist|Blacklist] of country code ####

Specify the country code according to the selection of "**Matching rule**" 
with two letters of the alphabet. Please use the code defined by [ISO 3166-1 
alpha-2][ISO-3166-1].

#### Use Autonomous System Number ####

[ASN][AS-Number] is the number assigned to the group of IP addresses. For 
example, Facebook has many IP addresses, and [AS32934][AS32934] is assigned.
Activating this setting will allow you to specify a group of IP addresses for 
a specific organization all in one piece.

#### [Whitelist|Blacklist] of extra IP addresses prior to country code ####

Specify the IP address group or AS number to block or pass, prior to validating
the country code. "**CIDR calculator forIPv4 / IPv6**" can help you to get the 
range of IP addresses that can be expressed simply as [CIDR][CIDR] notation.

![CIDR calculator for IPv4/IPv6]({{ '/img/2018-03/CIDR-Calculator.png' | prepend: site.baseurl }}
 "CIDR calculator for IPv4/IPv6"
)

#### $_SERVER keys to retrieve extra IP addresses ####

In the case of a request via a proxy server, the IP addresses of multiple 
servers may be passed through in a specific HTTP field. In order to validate 
all such IP addresses, you can set up some keys acquired by PHP such as 
`HTTP_X_FORWARDED_FOR`, `HTTP_CLIENT_IP` and so on.

#### Bad signatures in query ####

Specify a malicious string (excluding comments and article postings) to be 
scanned from the requested query in order to block a malicious request.

#### Prevent malicious file uploading ####

Set up rules to prevent uploading of malicious files targeted at plugins and 
theme vulnerabilities.

- **Verify file extension and MIME type**  
  Select the white list of MIME type to be permitted.

- **Verify file extension only**  
  Put the black list of prohibited file extension.

- **Capabilities to be verified**  
  Put up the necessary capabilities for uploading.
  See [Roles and Capabilities][Roles] for details.

#### Max number of failed login attempts per IP address ####

Select the maximum number of possible login attempts. It is necessary to enable
"**Record “IP address cache”**" in "**Privacy and record settings**" section.

#### Response code ####

Specify the [HTTP status code][HTTP-Status] for response on blocking. Set the 
followings according to your selection.

- **Redirect URL**  
  For 2XX and 3XX, specify the redirect destination URL (default is 
  [blackhole.webpagetest.org][Blackhole]).

- **Response message**  
  For 4XX and 5XX, specify a message displayed by a simple interface of 
  [`wp_die()`][WP-DIE]. A [human-friendly error page][Error-Page] can be 
  configured separately based on the theme template such as `404.php`.

#### Validation timing ####

Specify when this plugin will perform the validation.

Normally, the timing when the plugin can safely be initialized is 
[`init` action hook][Action-Hook]. But since it is after loading the theme 
and all the activated plugins, it takes unnecessary server load in case of 
blocking. In order to avoid this waste, you can select it as `muplugins_loaded`.

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[ISO-3166-1]:   https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements "Officially assigned code elements - Wikipedia"
[AS-Number]:    https://en.wikipedia.org/wiki/Autonomous_system_(Internet) "Autonomous system (Internet) - Wikipedia"
[AS32934]:      https://ipinfo.io/AS32934 "AS32934 Facebook, Inc. - ipinfo.io"
[CIDR]:         https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing "Classless Inter-Domain Routing - Wikipedia"
[X-Forwarded]:  https://en.wikipedia.org/wiki/X-Forwarded-For "X-Forwarded-For - Wikipedia"
[Roles]:        https://codex.wordpress.org/Roles_and_Capabilities "Roles and Capabilities &laquo; WordPress Codex"
[HTTP-Status]:  https://en.wikipedia.org/wiki/List_of_HTTP_status_codes "List of HTTP status codes - Wikipedia"
[WP-DIE]:       https://codex.wordpress.org/Function_Reference/wp_die "Function Reference/wp die &laquo; WordPress Codex"
[Error-Page]:   {{ '/codex/customizing-the-response.html#human-friendly-error-page' | prepend: site.baseurl }} "Response code and message | IP Geo Block"
[Action-Hook]:  https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference &laquo; WordPress Codex"
[Blackhole]:    http://blog.patrickmeenan.com/2011/10/testing-for-frontend-spof.html "Performance Matters: Testing for Frontend SPOF"
