---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-backup-dir
file: [class-ip-geo-block-logs.php]
---

The directory where the backup files of validation logs are saved.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-backup-dir**" assigns the absolute path to the 
directory where the backup files of validation logs are saved.

The maximum entries of each validation log for the target (e.g. `comment`, 
`xmlrpc`, `login` and `adnin`) is limited to 100. So if you want to save 
backups, use this hook.

### Default value ###

( `null` )

### Use case ###

The following code snippet in your theme's `functions.php` selectively saves 
the CSV formatted text files which will be named as `ip-geo-block-YYYY-MM.log`.

{% highlight ruby startinline %}
function my_backup_dir( $dir, $hook ) {
    if ( 'login' === $hook )
        return '/absolute/path/to/';
    else
        return null; // do not keep backups
}
add_filter( 'ip-geo-block-backup-dir', 'my_backup_dir', 10, 2 );
{% endhighlight %}

<div class="alert alert-warning">
  WARNING: The '<code>/absolute/path/to/</code>' should be outside of your 
  public area.
</div>

The CSV format is as follows:

| Entry      | Description                                                    |
|:-----------|:---------------------------------------------------------------|
| time       | requested time in seconds                                      |
| ip address | ip address of requester                                        |
| target     | requested target e.g. `comment`, `xmlrpc`, `login`, `admin`    |
| authority  | logged in user or not                                          |
| country    | country code                                                   |
| result     | validation result e.g. `blocked`, `wp-zep`, `failed`, `badsig` |
| method     | HTTP method and port e.g. GET, POST                            |
| UA         | user agent strings                                             |
| headers    | HTTP headers                                                   |
| queries    | requested queries                                              |

### Since ###
1.4.0

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
