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
corresponded to the filtered logs to make analysys of logs easy.

<script src="https://gist.github.com/tokkonopapa/494949213f3d086cf4a28613f759314c.js"></script>

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
