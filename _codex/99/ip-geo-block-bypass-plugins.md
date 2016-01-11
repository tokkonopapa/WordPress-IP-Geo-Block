---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-bypass-plugins
file: [class-ip-geo-block.php]
---

The list of plugins which WP-ZEP should bypass.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-bypass-plugins**" assigns the array of query 
strings in the request to the `/wp-content/plugins/my-plugin/…/*.php` which 
WP-ZEP should bypass.

In some cases, WP-ZEP (Zero-day Exploit Prevention for WordPress) blocks the 
valid request. This filter hook is used to prevent such an unexpected blocking.

### Default value ###

array( `…` )

### Use case ###

The following code snippet in your theme's `functions.php` can bypass WP-ZEP 
validation against the direct request to 
`/wp-content/plugins/my-plugin/…/*.php`.

{% highlight php startinline %}
function my_bypass_plugins( $plugins ) {
    $whitelist = array(
        'my-plugin',
    );
    return $plugins + $whitelist;
}
add_filter( 'ip-geo-block-bypass-plugins', 'my_bypass_plugins' );
{% endhighlight %}

### Since ###

2.1.1

### See also ###

- [The best practice of "Validation target settings"][BestPractice]
- [Record settings and logs][RecordingLogs]
- [Prevent exposure of wp-config.php][PreventExpose]
- [ip-geo-block-bypass-admins][BypassAdmins]
- [ip-geo-block-bypass-themes][BypassThemes]

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]:  {{ '/codex/the-best-practice-of-validation-target-settings.html' | prepend: site.baseurl }} 'The best practice of "Validation target settings" | IP Geo Block'
[RecordingLogs]: {{ '/codex/record-settings-and-logs.html'    | prepend: site.baseurl }} 'Record settings and logs | IP Geo Block'
[PreventExpose]: {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} 'Prevent exposure of wp-config.php | IP Geo Block'
[BypassAdmins]:  {{ '/codex/ip-geo-block-bypass-admins.html'  | prepend: site.baseurl }} 'ip-geo-block-bypass-admins | IP Geo Block'
[BypassThemes]:  {{ '/codex/ip-geo-block-bypass-themes.html'  | prepend: site.baseurl }} 'ip-geo-block-bypass-themes | IP Geo Block'
