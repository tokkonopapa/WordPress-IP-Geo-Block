---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-bypass-admins
file: [class-ip-geo-block.php]
---

The list of query strings in the admin request which WP-ZEP should bypass.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-bypass-admins**" assigns the array of query 
strings in the request to the `/wp-admin/…/*.php` which WP-ZEP should bypass.

In some cases, WP-ZEP (Zero-day Exploit Prevention for WordPress) blocks the 
valid request to the `/wp-admin/…/*.php`. This filter hook is used to prevent 
such an unexpected blocking.

### Default value ###

array( `…` )

### Use case ###

The following code snippet in your theme's `functions.php` can bypass WP-ZEP 
validation against some admin requests with query strings `action=do-my-action`
and `page=my-plugin-page`.

{% highlight ruby startinline %}
function my_bypass_admins( $queries ) {
    $whitelist = array(
        'do-my-action',
        'my-plugin-page',
    );
    return array_merge( $queries, $whitelist );
}
add_filter( 'ip-geo-block-bypass-admins', 'my_bypass_admins' );
{% endhighlight %}

<div class="alert alert-info">
	<strong>NOTE:</strong>
	When you select <code>"mu-plugins" (ip-geo-block-mu.php)</code> as 
	<a href='/codex/validation-timing.html' title='Validation timing | IP Geo Block'><strong>Validation timing</strong></a>,
	you should put your code snippet into <code>drop-in.php</code> in your 
	geolocation API directory instead of <code>functions.php</code>.
</div>

### Since ###

2.1.1

### See also ###

- [The best practice of target settings][BestPractice]
- [Record settings and logs][RecordingLogs]
- [ip-geo-block-bypass-plugins][BypassPlugins]
- [ip-geo-block-bypass-themes][BypassThemes]

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]:  {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[RecordingLogs]: {{ '/codex/record-settings-and-logs.html'              | prepend: site.baseurl }} 'Record settings and logs | IP Geo Block'
[BypassPlugins]: {{ '/codex/ip-geo-block-bypass-plugins.html'           | prepend: site.baseurl }} 'ip-geo-block-bypass-plugins | IP Geo Block'
[BypassThemes]:  {{ '/codex/ip-geo-block-bypass-themes.html'            | prepend: site.baseurl }} 'ip-geo-block-bypass-themes | IP Geo Block'
