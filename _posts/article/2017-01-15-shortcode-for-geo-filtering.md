---
layout: post
title: "Shortcode for Geo Filtering"
date: 2017-01-22 00:00:00
categories: article
published: true
script: []
inline:
---

I had developed this plugin as an security purpose plugin so as to protect the 
back-end of the site. And since version 3.0.0, I've provided functionality of 
front-end protection based on [this suggestion at support forum][Suggestion].

This helped to greatly improved [the protection ability][Protection] of this 
plugin against the attacks via front-end of the site.

<!--more-->

### Need for content filtering by country ###

Meanwhile, this also helped to make the purpose of this plugin unclear. Because
the function of this plugin to specifing the validation target is not enough 
for a user who wants to manage contents by [Geo-blocking][GeoBlocking].

![Front-end validation target settings]({{ '/img/2016-11/ValidationTarget.png' | prepend: site.baseurl }}
 "Front-end validation target settings"
)

For example, [this discussion][NeedHelp] indicated that not only categories or
tags but also [custom taxonomies][Taxonomies] are needed to specify the targets.

[![WP-Property]({{ '/img/2017-01/WP-Property.jpg' | prepend: site.baseurl }}
  "WP-Property"
)][WP-Property]

But I don't like to extend the functionality toward this direction because I 
prefer to keep this plugin simple. Then what't the solution?


### Shortcode for Geo Filtering ###

Well, this plugin has much extendability enough to satisfy the demmand. You can
embed the following snippet for [shortcode][Shortcodes] into your `functions.php`
to manage the content:

{% highlight PHP %}
<?php
if ( class_exists( 'IP_Geo_Block' ) ) {
    function my_filter_content( $args, $content = null ) {
        // set alternative content for not whilelisted countries
        extract( shortcode_atts( array(
            'alt' => "<p>Sorry, but you can't access this content.</p>",
        ), $args ) );

        // get the geolocation of visitor
        $geo = IP_Geo_Block::get_geolocation();

        // return content if the contry code matches one in the whitelist
        $settings = IP_Geo_Block::get_option();
        if ( FALSE !== strpos( $settings['public']['white_list'], $geo['code'] ) ) {
            return $content;
        }

        // return alternative content for not whilelisted country
        return wp_kses_post( $alt );
    }

    add_shortcode( 'ip-geo-block', 'my_filter_content' );
}
{% endhighlight %}

The usage of the shortcode `[ip-geo-block]` is as follows:

{% highlight YAML %}
[ip-geo-block alt="<img src='/image/alternative.png' alt='This content is not allowed in your country.'>"]

<p>This is a content for whitelisted countries.</p>

[/ip-geo-block]
{% endhighlight %}

And there's a tweak to keep the whitelist or blacklist of coutnry code in
"**Front-end target settings**". You need 2 steps to do this: 

1. Check "**Block by country**" and specify the others. Then "**Save Changes**".
2. Uncheck "**Block by country**" and "**Save Changes**" once again.

![Front-end target settings for geo filtering]({{ '/img/2017-01/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings for geo filtering"
)

Have fun <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Suggestion]:   https://wordpress.org/support/topic/feature-suggestion-redirection-for-certain-pagesposts-etc/ "Topic: Feature suggestion: Redirection for certain pages/posts etc. « WordPress.org Forums"
[NeedHelp]:     https://wordpress.org/support/topic/i-need-help-because-of-the-settings/ "Topic: I need help because of the settings &laquo; WordPress.org Forums"
[Taxonomies]:   https://codex.wordpress.org/Taxonomies "Taxonomies &laquo; WordPress Codex"
[Shortcodes]:   https://codex.wordpress.org/Shortcode "Shortcode &laquo; WordPress Codex"
[WP-Property]:  https://wordpress.org/plugins/wp-property/ "WP-Property - WordPress Powered Real Estate and Property Management &mdash; WordPress Plugins"
[GeoBlocking]:  https://en.wikipedia.org/wiki/Geo-blocking "Geo-blocking - Wikipedia"
[Protection]:   {{ '/codex/analysis-of-attack-vectors.html' | prepend: site.baseurl }} "Analysis of Attack Vectors | IP Geo Block"
