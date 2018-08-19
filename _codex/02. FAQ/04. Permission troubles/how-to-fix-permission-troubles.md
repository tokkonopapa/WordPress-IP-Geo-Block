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

When you meet those cases, you have to configure something related to the 
WordPress file system by your own hand.

### Geolocation API library ###

#### Configuring file system ####

If your host is running under a special installation setup involving symlinks,
or certain installations with a PHP FTP extension, you'll see the following
error message when you install and activate this plugin for the first time:

![Error of Filesystem]({{ '/img/2017-06/FilesystemError.png' | prepend: site.baseurl }}
 "Error of Filesystem"
)

In this case, as of the instruction in [this document at codex][WP_FILESYS], 
you have to configure some symbols in your `wp-config.php` something like this:

{% highlight ruby %}
define( 'FTP_HOST', 'http://example.com/' );
define( 'FTP_USER', 'username' );
define( 'FTP_PASS', 'password' );
{% endhighlight %}

If you have some reasons you can't do this, please follow the next instruction.

#### Installing Geolocation API library ####

When you'll see the following when you jump to the option page of this plugin:

![Error of IP Geo API]({{ '/img/2016-09/ErrorGeoAPI.png' | prepend: site.baseurl }}
 "Error of IP Geo API"
)

In this case, you should install `ip-geo-api` that includes geolocation 
API library named [IP-Geo-API][GitGeoAPI] for [Maxmind][Maxmind] and 
[IP2Location][IP2Location] under one of the following folders:

1. `/wp-content/`
2. `/wp-content/uploads/`
3. `/wp-content/plugins/ip-geo-block/`

You can download the [ZIP file][GitGeoAPIZIP] and upload `ip-geo-api` in the 
unzipped folder onto the above 1. or 2 **with a proper permission** using FTP.

[![IP-Geo-API]({{ '/img/2016-09/GeoLocationAPI.png' | prepend: site.baseurl }}
)][GitGeoAPI]

<div class="alert alert-info">
	<strong>Note: </strong>
	Installing <code>ip-geo-api</code> into 3. is not recommended, because 
	it will be removed at every time this plugin is updated.
</div>


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
	(<code>ip2location</code> and <code>maxmind</code>) a proper permission.
	It may be <code>755</code> but should be confirmed by consulting your
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
[WP_FILESYS]:   https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants "Editing wp-config.php &laquo; WordPress Codex"
[Maxmind]:      https://www.maxmind.com/ "IP Geolocation and Online Fraud Prevention | MaxMind"
[IP2Location]:  https://www.ip2location.com/ "IP Address Geolocation to Identify Website Visitor's Geographical Location"
[GitGeoAPI]:    https://github.com/tokkonopapa/WordPress-IP-Geo-API "GitHub - tokkonopapa/WordPress-IP-Geo-API: A class library for WordPress plugin IP Geo Block to handle geolocation database of Maxmind and IP2Location."
[GitGeoAPIZIP]: https://github.com/tokkonopapa/WordPress-IP-Geo-API/archive/master.zip
[PreventExp]:   {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
