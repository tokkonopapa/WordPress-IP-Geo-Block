---
layout: page
category: codex
section: FAQ
title: How can I fix permission troubles?
excerpt: How can I fix permission troubles?
---

This plugin must have read/write permission at the certain places outside of 
the plugin directory which is typically `/wp-content/`. But in some cases, 
you might find the error message related to the permission because of your 
server's security configurations.

In this case, you have to configure these places by your own hand.

### Geolocation API libraries and databases ###

This plugin handles the IP address geolocation databases of [Maxmind][Maxmind] 
and [IP2Location][IP2Location] on your server. But it is also designed to work 
properly without of these because of the requirement for GNU license.

So when you install this plugin for the first time, the geolocation API library
[IP-Geo-API][GitGeoAPI] for these databases would be installed into one of the 
following directories will be selected as the place where the class libraries 
and databases are stored.

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

But installing these libraries seldomly fails because of the security policy of
your server.

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

In this case, you had better to download [the ZIP file][GitGeoAPIZIP] and 
upload `ip-geo-api` manually in the unzipped folder as the above 1. or 2 with 
proper permission.

The 3rd one is also not recommended because at every time this plugin updates, 
files in its directory will be removed. So if you find your dabase directory 
as the 3rd one, you should move it to 1. or 2.

<div class="alert alert-info">
	<strong>NOTE:</strong> Please refer to 
	"<a href='https://codex.wordpress.org/Hardening_WordPress#Core_Directories_.2F_Files' title='Hardening WordPress &laquo; WordPress Codex'>Hardening WordPress</a>"
	to give the proper permission to <code>ip-geo-api</code> folder. It may be 
	<code>755</code> but should be confirmed by consulting your host administrator.
</div>

### Force to load WP core ###

![Error of .htaccess]({{ '/img/2016-09/ErrorHtaccess.png' | prepend: site.baseurl }}
 "Error of .htaccess"
)

When you enable "**Force to load WP core**" options, this plugin will try to 
configure `.htaccess` in your `/wp-content/plugins/` and `/wp-content/themes/` 
directory in order to protect your site against the malicous attacks targeted 
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
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library combined with WordPress plugin IP Geo Block to handle geo-location database of Maxmind and IP2Location."
[GitGeoAPIZIP]: https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip
[PreventExp]:   {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
