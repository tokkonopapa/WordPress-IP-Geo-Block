---
layout: post
title: "Shortcode for geo filtering by country"
date: 2017-01-15 00:00:00
categories: article
published: true
script: []
inline:
---

I had developed this plugin as an security purpose plugin so as to protect the 
back-end of the site. And since version 3.0.0, I started to provide front-end 
protection feature based on [this suggestion at support forum][Suggestion].

This helped to greatly improved [the protection performance][Protection] of 
this plugin against the attacks via front-end of the site.

<!--more-->

### Content filtering by country ###

Meanwhile, this also helped to erode boundaries between country blocking and 


{% highlight PHP %}
<?php
if ( class_exists( 'IP_Geo_Block' ) ) {
    function my_filter_content( $args, $content = null ) {
        // set alternative content for not whilelisted country
        extract( shortcode_atts( array(
            'alt' => "<p>Sorry, but you can't access this content.</p>",
        ), $args ) );

        // get the geolocation of visitor
        $geo = IP_Geo_Block::get_geolocation();

        // return content if the contry code matches the white list
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

{% highlight YAML %}
[ip-geo-block alt="<img src='/image/alternative.png' alt='This content is not allowed in your country.'>"]

<p>This is a content for whitelisted countries.</p>

[/ip-geo-block]
{% endhighlight %}

![Front-end target settings for geo filtering]({{ '/img/2017-01/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings for geo filtering"
)

<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Suggestion]:   https://wordpress.org/support/topic/feature-suggestion-redirection-for-certain-pagesposts-etc/ "Topic: Feature suggestion: Redirection for certain pages/posts etc. « WordPress.org Forums"
[Protection]:   {{ '/codex/analysis-of-attack-vectors.html' | prepend: site.baseurl }} "Analysis of Attack Vectors | IP Geo Block"
