---
layout: page
category: codex
section: customizing
title: ip-geo-block-extra-ips
file: [class-ip-geo-block.php]
---

White list and Black list of extra IP addresses prior to country code.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-extra-ips**" can assign the white and black 
list of extra IP addresses with [CIDR notation][CIDR] which should be 
validated prior to other validations.

### Default value ###

array( `'white_list' => '', 'black_list' => ''` )

### Use case ###

The following code snippet in your theme's `functions.php` can automatically 
fetch the IP addresses from [Tor exit nodes][TorExitNodes] on background and 
add them to the black list when login attempt is captured.

{% highlight php startinline %}
define( 'MY_EXTRA_IPS_LIST', 'my_extra_ips_list' );
define( 'MY_EXTRA_IPS_CRON', 'my_extra_ips_cron' );

function my_extra_ips_get() {
    // get tor address list
    $list = @file( 'https://check.torproject.org/exit-addresses' );

    if ( FALSE !== $list ) {
        // retrieve IP addresses from lines like :
        // 'ExitAddress 123.456.789.123 YYYY-MM-DD hh:mm:ss'
        $list = preg_filter(
            '/^ExitAddress (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) .*$/m',
            '$1',
            $list
        );

        // keep the list in the cache
        if ( ! empty( $list ) ) {
            $list = implode( ',', array_map( 'trim', $list ) );
            set_transient( MY_EXTRA_IPS_LIST, $list, DAY_IN_SECONDS );
        }
    }

    if ( ! wp_next_scheduled( MY_EXTRA_IPS_CRON ) ) {
        wp_schedule_single_event( time() + HOUR_IN_SECONDS, MY_EXTRA_IPS_CRON );
    }

    return $list;
}

function my_extra_ips_hook( $extra_ips, $hook ) {
    // if the list does not exist, then update
    $list = get_transient( MY_EXTRA_IPS_LIST );

    if ( ! $list ) {
        wp_schedule_single_event( time(), MY_EXTRA_IPS_CRON );
    }

    // restrict the target hook
    if ( in_array( $hook, array( 'xmlrpc', 'login' ) ) ) {
        $extra_ips['black_list'] .= ( $extra_ips['black_list'] ? ',' : '' ) . $list;
    }

    return $extra_ips;
}

add_action( MY_EXTRA_IPS_CRON, 'my_extra_ips_get' );
add_filter( 'ip-geo-block-extra-ips', 'my_extra_ips_hook', 10, 2 );
{% endhighlight %}

### Since ###

2.2.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[CIDR]:         https://en.wikipedia.org/wiki/Classless_Inter-Domain_Routing "Classless Inter-Domain Routing - Wikipedia, the free encyclopedia"
[TorExitNodes]: https://www.torproject.org/ "Tor Project: Anonymity Online"
