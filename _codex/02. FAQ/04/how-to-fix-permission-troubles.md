---
layout: page
category: codex
section: FAQ
title: How can I fix permission troubles?
excerpt: How can I fix permission troubles?
---

This plugin must have read/write permission at the certain places outside of 
the plugin folder. But in some cases, you might find the error message related 
to the permission because of your server's security configurations.

When you meet those cases, you have to configure these places by your own hand.

### Geolocation API library ###

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

Although this plugin is designed to work properly without geolocation databases
for [Maxmind][Maxmind] and [IP2Location][IP2Location] on your server by using 
3rd party's free REST API services, it is highly recommended to install 
geolocation API library named [IP-Geo-API][GitGeoAPI] to handle IP address and 
its country code.

So when you install this plugin for the first time, this plugin would try to 
install `ip-geo-api` into one of the following folders:

1. `/wp-content/`
2. `/wp-content/uploads/`
3. `/wp-content/plugins/ip-geo-block/`

But installing `ip-geo-api` could be failed because of the security policy of 
your server.

In such a case, you can download the [ZIP file][GitGeoAPIZIP] manually and 
upload `ip-geo-api` in the unzipped folder onto the above 1. or 2. **with 
proper permission**.

[![IP-Geo-API]({{ '/img/2016-09/GeoLocationAPI.png' | prepend: site.baseurl }}
)][GitGeoAPI]

The 3. is not recommended. Because at every time this plugin is updated, the 
geolocation database files will be removed. So in this case, you should move 
your `ip-geo-api` to 1. or 2.

Here's a final tree view after uploading `ip-geo-api` to 1.

{% highlight text %}
/wp-content/ip-geo-api/
  ├── index.php
  ├── ip2location
  │   ├── IP2Location.php
  │   ├── bcmath.php
  │   └── class-ip2location.php
  └── maxmind
      ├── LICENSE
      ├── class-maxmind.php
      ├── geoip.inc
      └── geoipcity.inc
{% endhighlight %}

<div class="alert alert-info">
	<strong>NOTE:</strong> Please refer to 
	"<a href='https://codex.wordpress.org/Hardening_WordPress#Core_Directories_.2F_Files' title='Hardening WordPress &laquo; WordPress Codex'>Hardening WordPress</a>"
	to give <code>ip-geo-api</code> and the following folders 
	(<code>ip2location</code> and <code>maxmind</code>) the proper permission
	that may be <code>755</code> but should be confirmed by consulting your
	hosting administrator.
</div>

### Force to load WP core ###

![Error of .htaccess]({{ '/img/2016-09/ErrorHtaccess.png' | prepend: site.baseurl }}
 "Error of .htaccess"
)

When you enable "**Force to load WP core**" options, this plugin will try to 
configure `.htaccess` in your `/wp-content/plugins/` and `/wp-content/themes/` 
folder in order to protect your site against the malicous attacks targeted 
at the [OMG plugins and themes][PreventExp].

If you encounter an "Unable to write" message for plugins, you should put the 
following directives into your `/wp-content/plugins/.htaccess` manually 
instead of enabling this option:

{% highlight text %}
# BEGIN IP Geo Block
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /wp-content/plugins/ip-geo-block/
RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$
RewriteRule ^.*\.php$ rewrite.php [L]
</IfModule>
# END IP Geo Block
{% endhighlight %}

The absolute path `/wp-content/plugins/` should be changed according to your 
site configuration.
And here's an example directives in `/wp-content/themes/.htaccess`:

{% highlight text %}
# BEGIN IP Geo Block
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /wp-content/plugins/ip-geo-block/
RewriteRule ^.*\.php$ rewrite.php [L]
</IfModule>
# END IP Geo Block
{% endhighlight %}

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  http://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library for WordPress plugin IP Geo Block to handle geolocation database of Maxmind and IP2Location."
[GitGeoAPIZIP]: https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip
[PreventExp]:   {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
