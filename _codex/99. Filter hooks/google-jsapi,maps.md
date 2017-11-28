---
layout: page
category: codex
section: Filter Hooks
title: google-jsapi, google-maps
file: [class-ip-geo-block-admin.php]
---

URL to Google API services.

### Description ###

The filter hook "**google-jsapi**" and "**google-maps**" assign the URL to 
[Google API Client Libraries][Google-APIs] for [Google Charts][Google-Charts] 
and [Google Maps API][Google-Maps].

### Parameters ###

#### `google-apis` ####

- $url  
  (string) `https://www.google.com/jsapi` for Google Charts


#### `google-maps` ####

- $url  
  (string) `//maps.googleapis.com/maps/api/js` for Google Maps

### Use case ###

The following code in your `functions.php` can override the default value.

{% highlight ruby %}
function my_google_jsapi( $url ) {
    return 'https://www.google.cn/jsapi';
}
function my_google_maps( $url ) {
    return 'http://maps.google.cn/maps/api/js';
}
add_filter( 'google-jsapi', 'my_google_jsapi' );
add_filter( 'google-maps',  'my_google_maps'  );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.5

### See also ###

- [Why can't I access Google Maps APIs from China?][Google-Dev]

[Google-APIs]:   https://developers.google.com/api-client-library/
[Google-Charts]: https://developers.google.com/chart/ 'Charts | Google Developers'
[Google-Maps]:   https://developers.google.com/maps/ 'Google Maps API | Google Developers'
[Google-Dev]:    https://developers.google.com/maps/faq#china_ws_access 'FAQ | Google Maps APIs | Google Developers'
