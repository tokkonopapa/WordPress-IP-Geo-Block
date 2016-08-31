---
layout: page
category: codex
section: FAQ
title: How to fix permission troubles?
excerpt: How to fix permission troubles?
---

This plugin must have read/write permission at two places outside of the plugin
directory which is typically `/wp-content/plugins/`. But in some cases, you 
might find the error message related to the permission because of your server's
security setting.

In this case, you have to configure these places by your own hand.

### Geolocation database ###

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

This plugin needs the IP address geolocation databases of [Maxmind][Maxmind] 
and [IP2Location][IP2Location]. Those databases would be downloaded using class
libraries for each. When you install this plugin at the first time, one of the 
following directories will be selected as the place where the class libraries 
and databases are stored.

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

The 3rd one is not recommended because at every time this plugin updates, 
files in its directory will be removed. So when you meet "Unable to write" 
message or find your dabase directory is the 3rd of the above, you should 
download `ip-geo-api` from [Github][GitGeoAPI] and upload it to 1. and 2.

### .htaccess ###

![Error of .htaccess]({{ '/img/2016-09/ErrorHtaccess.png' | prepend: site.baseurl }}
 "Error of .htaccess"
)

When you enable "**Force to load WP core**" options, this plugin will try to 
configure `.htaccess` in your `/wp-content/plugins/` and `/wp-content/themes/` 
directory in order to protect your site against the malicous attacks to the 
[OMG plugins and shemes][PreventExp].

If you encounter an "Unable to write" message for plugins, you should put the 
following directives into your `/wp-content/plugins/.htaccess` :

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
And here's the example directives in `/wp-content/themes/.htaccess` :

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
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[PreventExp]:   {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
