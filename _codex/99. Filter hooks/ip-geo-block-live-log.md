---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-live-log
file: [class-ip-geo-block-logs.php]
---

Database source name (DSN) of SQLite for Live update.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-live-log**" assigns the database source name 
of SQLite for Live update.

### Parameters ###

- $dsn  
  (string) [`get_temp_dir()`][WP-GET-TMP] . "`ip-geo-block-live-log-%d.sqlite`"
  (`%d` is blog ID)

### Use case ###

The following code in your `functions.php` can override the default value.

{% highlight ruby startinline %}
function my_sqlite_dsn( $path ) {
    return '/tmp/' . basename( $path );
}
add_filter( 'ip-geo-block-live-log', 'my_sqlite_dsn' );
{% endhighlight %}

#### Note ####

This plugin uses [PDO_SQLITE][PDO_SQLITE] driver. If you specify `:memory:`
as an DSN, then "Live update" would not work properly because 
`PDO::ATTR_PERSISTENT` is set as `false` in a constructor of PDO.

{% include alert-drop-in.html %}

### Since ###

3.0.5

### See also ###

- [PDO_SQLITE DSN][SQLITE_DSN]

[WP-GET-TMP]:   https://developer.wordpress.org/reference/functions/get_temp_dir/ "get_temp_dir() | Function | WordPress Developer Resources"
[PDO_SQLITE]:   http://php.net/manual/en/ref.pdo-sqlite.php "PHP: SQLite (PDO) - Manual"
[SQLITE_DSN]:   http://php.net/manual/en/ref.pdo-sqlite.connection.php "PHP: PDO_SQLITE DSN - Manual"
