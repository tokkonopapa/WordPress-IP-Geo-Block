---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-record-logs
file: [class-ip-geo-block.php]
---

Specify the condition of recording logs.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-record-logs**" can change the condition of 
recording logs depending on the situation.

### Default value ###

The value of "**Record validation logs**" at "**Record settings**".

### Use case ###

The following code snippet in your theme's `functions.php` can bypass WP-ZEP 
validation against the direct request to 
`/wp-content/plugins/my-plugin/…/*.php`.

{% highlight ruby startinline %}
/**
 * Example : Usage of 'ip-geo-block-record-logs'
 * Use case: Prevent recording logs when it requested from own country
 *
 * @param  int    $record   0:none 1:blocked 2:passed 3:unauth 4:auth 5:all
 * @param  string $hook     'comment', 'xmlrpc', 'login', 'admin' or 'public'
 * @param  array  $validate the result of validation which contains:
 *  'ip'       => string    ip address
 *  'auth'     => int       authenticated (>= 1) or not (0)
 *  'code'     => string    country code
 *  'time'     => unsinged  processing time for examining the country code
 *  'provider' => string    IP geolocation service provider
 *  'result'   => string    'passed' or the reason of blocking
 * @return int    $record   modified condition
 */
function my_record_logs( $record, $hook, $validate ) {
    /* if request is from my country and passed, then no record */
    if ( 'JP' === $validate['code'] && 'passed' === $validate['result'] )
        $record = 0;

    return $record;
}

add_filter( 'ip-geo-block-record-logs', 'my_record_logs', 10, 3 );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

2.1.1

### See also ###

- [The best practice of target settings][BestPractice]
- [Record settings and logs][RecordingLogs]
- [Prevent exposure of wp-config.php][PreventExpose]
- [ip-geo-block-bypass-admins][BypassAdmins]
- [ip-geo-block-bypass-themes][BypassThemes]

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]:  {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[RecordingLogs]: {{ '/codex/record-settings-and-logs.html'              | prepend: site.baseurl }} 'Record settings and logs | IP Geo Block'
[PreventExpose]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} 'Prevent exposure of wp-config.php | IP Geo Block'
[BypassAdmins]:  {{ '/codex/ip-geo-block-bypass-admins.html'            | prepend: site.baseurl }} 'ip-geo-block-bypass-admins | IP Geo Block'
[BypassThemes]:  {{ '/codex/ip-geo-block-bypass-themes.html'            | prepend: site.baseurl }} 'ip-geo-block-bypass-themes | IP Geo Block'
