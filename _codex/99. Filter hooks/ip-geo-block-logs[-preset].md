---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-logs[-preset]
file: [drop-in-admin.php]
---

Filter each entry in "**Validation logs**" and register "**Preset filters**" 
for "**Search in logs**".

<!--more-->

### Description ###

The filter hook "**ip-geo-block-logs**" makes each entry in logs to be filtered.
Also "**ip-geo-block-logs-preset**" allows to resiger preset filters for search
text box.

### Parameters ###

##### ip-geo-block-logs #####

- $logs  
  An array of validation logs that consist as follows:
{% highlight javascript startinline %}
  Array (
      [0 /* DB row number */] => '154',
      [1 /* Target        */] => 'comment',
      [2 /* Time          */] => '1534580897',
      [3 /* IP address    */] => '102.177.147.***',
      [4 /* Country code  */] => 'ZA',
      [5 /* Result        */] => 'blocked',
      [6 /* AS number     */] => 'AS328239',
      [7 /* Request       */] => 'POST[80]:/wp-comments-post.php',
      [8 /* User agent    */] => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) ...',
      [9 /* HTTP headers  */] => 'HTTP_X_FORWARDED_FOR=102.177.147.***...',
     [10 /* $_POST data   */] => 'comment=Hello.,author,email,url,comment_post_ID...',
  )
{% endhighlight %}

##### ip-geo-block-logs-preset #####

- $filters  
  An array of preset filters that consists of `title` and `value`.

### Use case ###

The following code snippet in `drop-in-admin.php` placed at the directory of 
[Geolocation API library][GeoAPI-Folder] can add an UI to "**Search in logs**"
corresponded to the filtered logs.

{% highlight javascript startinline %}
/**
 * Analyze entries in "Validation logs"
 *
 */
function ip_geo_block_logs( $logs = array() ) {
    // Get settings of IP Geo Block
    $settings = IP_Geo_Block::get_option();

    // White/Black list for back-end
    $white_backend = $settings['white_list'];
    $black_backend = $settings['black_list'];

    // White/Black list for front-end
    if ( $settings['public']['matching_rule'] < 0 ) {
        // Follow "Validation rule settings"
        $white_frontend = $white_backend;
        $black_frontend = $black_backend;
    } else {
        // Whitelist or Blacklist for "Public facing pages"
        $white_frontend = $settings['public']['white_list'];
        $black_frontend = $settings['public']['black_list'];
    }

    foreach ( $logs as $key => $log ) {
        // Passed or Blocked
        $mark = IP_Geo_Block::is_passed( $log[5] ) ? '&sup1;' : '&sup2;';

        // Whitelisted, Blacklisted or N/A
        if ( 'public' === $log[1] ) {
            $mark .= IP_Geo_Block::is_listed( $log[4], $white_frontend ) ? '&sup1;' : (
                     IP_Geo_Block::is_listed( $log[4], $black_frontend ) ? '&sup2;' : '&sup3;' );
        } else {
            $mark .= IP_Geo_Block::is_listed( $log[4], $white_backend  ) ? '&sup1;' : (
                     IP_Geo_Block::is_listed( $log[4], $black_backend  ) ? '&sup2;' : '&sup3;' );
        }

        // Put a mark at "Target"
        // ¹¹: Passed  in Whitelist
        // ¹²: Passed  in Blacklist
        // ¹³: Passed  not in list
        // ²¹: Blocked in Whitelist
        // ²²: Blocked in Blacklist
        // ²³: Blocked not in list
        $logs[ $key ][1] .= $mark;
    }

    return $logs;
}

IP_Geo_Block::add_filter( 'ip-geo-block-logs', 'ip_geo_block_logs' );

/**
 * Register "Preset filters"
 *
 * @param  array  An empty array by default.
 * @return array  The pare of 'title' and 'value'.
 */
function ip_geo_block_logs_preset( $filters = array() ) {
    return $filters + [
        [ 'title' => 'Passed  in Whitelist', 'value' => '&sup1;&sup1;' ],
        [ 'title' => 'Passed  in Blacklist', 'value' => '&sup1;&sup2;' ],
        [ 'title' => 'Passed  not in list',  'value' => '&sup1;&sup3;' ],
        [ 'title' => 'Blocked in Whitelist', 'value' => '&sup2;&sup1;' ],
        [ 'title' => 'Blocked in Blacklist', 'value' => '&sup2;&sup2;' ],
        [ 'title' => 'Blocked not in list',  'value' => '&sup2;&sup3;' ],
    ];
}

IP_Geo_Block::add_filter( 'ip-geo-block-logs-preset', 'ip_geo_block_logs_preset' );
{% endhighlight %}

And here's a sample of new UI "**Preset filters**".

![Preset filters at Search in logs]({{ '/img/2018-09/PresetFilters.png' | prepend: site.baseurl }}
 "Preset filters at Search in logs"
)

<div class="alert alert-info">
  <strong>Note:</strong> In the above code snippet, some html entities such as
  <code>&amp;sup1;</code> are used. Not all the entities are available but some
  of those which are defined in
  <a href="https://developer.wordpress.org/reference/functions/ent2ncr/"
  title="ent2ncr() | Function | WordPress Developer Resources">ent2ncr()</a>
  because all the text will be escaped by 
  <a href="https://developer.wordpress.org/reference/functions/esc_html/"
  title="esc_html() | Function | WordPress Developer Resources">esc_html()</a>
  before rendering.
</div>

### Since ###

3.0.15

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[GeoAPI-Folder]: https://www.ipgeoblock.com/codex/geolocation-api-library.html#geolocation-api-library "Local database settings | IP Geo Block"
