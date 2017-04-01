---
layout: page
category: codex
section: FAQ
title: My custom functions in “functions.php” doesn’t work.
excerpt: How to make your custom functions work in “functions.php”.
---

Normally, you can add code snippets for your custom functions into
[`functions.php`][FunctionFile] which is placed in your theme or child 
theme folder.

But in case you select **“mu-plugins” (ip-geo-block-mu.php)** as "[**Validation
timing**][ValidateTime]" in "**Validation rule settings**" section, your code 
for this plugin in `functions.php` would be failed to work as you expected.

![Validation timing]({{ '/img/2016-09/ValidationTiming.png' | prepend: site.baseurl }}
 "Validation timing"
)

[This restriction][Restrictions] is originated from the excution order described
in [Action Reference][ActionHook] where you can find `muplugins_loaded` action 
hook is triggered far before `after_setup_theme` which is the timing of your 
`functions.php` to be parsed.

![Action Reference]({{ '/img/2017-03/ActionReference.png' | prepend: site.baseurl }}
 "Action Reference"
)

Then what't the solution?

### Installing “drop-in.php” ###

If there is a special file named `drop-in.php` in your [Geolocation API folder]
[GeoAPIFolder] which might be one the of following locations:

1. `/wp-content/ip-geo-api/`
2. `/wp-content/uploads/ip-geo-api/`
3. `/wp-content/plugins/ip-geo-block/ip-geo-api/`

You can find a sample for `drop-in.php` in 
`/wp-content/plugins/ip-geo-block/drop-in-sample.php`. So you can copy it into 
your Geolocation API folder and rename it as `drop-in.php`.

![drop-in.php]({{ '/img/2017-03/drop-in.png' | prepend: site.baseurl }}
 "drop-in.php"
)

Note that even in case of multisite, `drop-in.php` is the only one file for 
all sites and will be called on each site. So if you want each site to behave 
differently, you should add some code like follows:

{% highlight php %}
<?php
/**
 * Drop-in for IP Geo Block custom filters
 *
 * @package   IP_Geo_Block
 * @link      http://www.ipgeoblock.com/codex/#filter-hooks
 * @example   Use `IP_Geo_Block::add_filter()` instead of `add_filter()`.
 */
if ( ! class_exists( 'IP_Geo_Block' ) ) {
    die;
}

$components = parse_url( site_url() );

switch ( $components['host'] ) {
    case 'example.com':
      if ( 0 === strpos( $components['path'], '/subdir1' ) ) {
          // here is code snippet for sub directory 1
      }

      elseif ( 0 === strpos( $components['path'], '/subdir2' ) ) {
          // here is code snippet for sub directory 2
      }
      break;

    case 'subdomain1.example.com':
      // here is code snippet for sub domain 1
      break;

    default:
      // here is code snippet for default
      break;
}
{% endhighlight %}


<div class="alert alert-info">
	<strong>NOTE:</strong> All your custom functions in 
	<code>functions.php</code> doesn't need to be put together into
	<code>drop-in.php</code> but functions related to only this plugin 
	such as <a href="/codex/#filter-hooks">Filter hooks for this plugin</a>.
</div>

### See also ###

- [Validation timing][ValidateTime]
- [Geolocation API library][GeoAPIFolder]
- [Customizing the response][CustomRes]
- [Filter hooks][FilterHooks]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[FunctionFile]: https://codex.wordpress.org/Functions_File_Explained "Functions File Explained &laquo; WordPress Codex"
[ActionHook]:   https://codex.wordpress.org/Plugin_API/Action_Reference "Plugin API/Action Reference &laquo; WordPress Codex"
[ValidateTime]: {{ '/codex/validation-timing.html'              | prepend: site.baseurl }} "Validation timing | IP Geo Block"
[Restrictions]: {{ '/codex/validation-timing.html#restrictions' | prepend: site.baseurl }} "Validation timing | IP Geo Block"
[GeoAPIFolder]: {{ '/codex/geolocation-api-library.html'        | prepend: site.baseurl }} "Geolocation API library | IP Geo Block"
[FilterHooks]:  {{ '/codex/#filter-hooks'                       | prepend: site.baseurl }} "Filter hooks | IP Geo Block"
[CustomRes]:    {{ '/codex/customizing-the-response.html'       | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
