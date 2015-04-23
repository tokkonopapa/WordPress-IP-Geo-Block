---
layout: post
title:  "How does WP-ZEP prevent zero-day attack?"
date:   2015-04-19 00:00:00
categories: article
---

["IP Geo Block"][IP-Geo-Block] is the only plugin which has an ability to 
prevent zero-day attack even if some of plugins in a WordPress site have 
unveiled vulnerability. I call it "**Z**ero-day **E**xploit **P**revention 
for wp-admin" (WP-ZEP).

In this article, I'll explain about its mechanism and also its limitations.
But at first, I'll mention about the best practice of plugin actions.

<!--more-->

### What's the best practice of plugin actions? ###

While we can find [the answer at Stack Exchange][Stack-Exchange], I'd like to 
describe a little in detail.

#### Showing plugin page ####

The plugin page can be displayed according to its category like this:

* `wp-admin/admin.php?page=my-plugin`
* `wp-admin/tools.php?page=my-plugin`
* `wp-admin/option-general.php?page=my-plugin`
* &hellip;

#### Requesting to <samp>wp-admin/admin.php</samp> ####

On the plugin's dashboard, we can provide an action `do-my-action` via 
`admin.php` with a form like this:

{% highlight php startinline=true %}
<?php
add_action( 'admin_action_' . 'do-my-action', 'my_action' );
?>
<form action="<?php echo admin_url( 'admin.php' ); ?>">
    <?php wp_nonce_field( 'do-my-action' ); ?>
    <input type="hidden" name="action" value="do-my-action" />
    <input type="submit" value="Do my action" class="button" />
</form>
{% endhighlight %}

Or a link:

{% highlight php startinline=true %}
<?php
$link = add_query_arg(
    array(
        'action' => 'do-my-action',
        '_wpnonce' => wp_create_nonce( 'do-my-action' ),
    ),
    admin_url( 'admin.php' )
);
?>
<a href="<?php echo esc_url( $link ); ?>">Do my action</a>
{% endhighlight %}

#### Requesting to <samp>wp-admin/admin-ajax.php</samp> ####

We can also do the same thing via `admin-ajax.php` by `GET` or `POST` method 
using jQuery.

{% highlight php startinline=true %}
add_action( 'wp_ajax_' . 'do-my-action', 'my_action' );
{% endhighlight %}

#### Requesting to <samp>wp-admin/admin-post.php</samp> ####

WordPress also give us a chance to handle `POST` request via `admin-post.php`.

{% highlight php startinline=true %}
add_action( 'admin_post_' . 'do-my-action', 'my_action' );
{% endhighlight %}

#### Handling request ####

All above-mentioned can be handled by the function `my_action`.

{% highlight php startinline=true %}
function my_action() {
    // validate privilege and nonce
    if ( ! current_user_can( 'manage_options' ) ||
         ! check_admin_referer( 'do-my-action' ) ) {
        return; // force to redirect to login page
    }
 
    // do my action
    ...
 
    // show result in case of Ajax
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        wp_send_json( $result );
    }
 
    // show result after page transition
    else {
        if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
            // redirect to the referer by wp_nonce_field()
            $redirect_to = $_REQUEST['_wp_http_referer'];
        }
        else if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
            // redirect to the referer by browser
            $redirect_to = $_SERVER['HTTP_REFERER'];
        }
        else {
            // redirect to the plugin page
            $redirect_to = admin_url( 'admin.php?page=my-plugin' );
        }
 
        wp_safe_redirect( $redirect_to );
        exit;
    }
}
{% endhighlight %}

### The mechanism of WP-ZEP ###

In the above code, the most important things before **doing my action** are:

1. validate user privilege with `current_user_can()`.
2. validate the nonce with `check_admin_referer()`.
3. validate the given input.

When either lacks, the result becomes serious.

So WP-ZEP will make up 1. and 2. by embedding a nonce into the 
request.

### The limitation of WP-ZEP ###
<!--
One big challenge for WP-ZEP is to decide the request hander is vulnerable or 
not if the same function `my_action()` is registered for both authorized and 
unauthorized users like this:

{% highlight php startinline=true %}
add_action( 'wp_ajax_'        . 'do-my-action', 'my_action' );
add_action( 'wp_ajax_nopriv_' . 'do-my-action', 'my_action' );
{% endhighlight %}

If WP-ZEP blocks the action `do-my-action`, users on the public facing pages 
can not take any benefit via the ajax call. So in this case, WP-ZEP currently 
do nothing but validate IP address by country code.

This bypass causes a serious problem: can't block 
[vulnerability in Slider Revolution][Slider-Rev] 
if the malicous access comes from the permitted country.
-->

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress &#8250; IP Geo Block &laquo; WordPress Plugins"
[Stack-Exchange]: http://wordpress.stackexchange.com/questions/10500/how-do-i-best-handle-custom-plugin-page-actions "wp admin - How do i best handle custom plugin page actions? - WordPress Development Stack Exchange"
[Slider-Rev]: https://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html "Slider Revolution Plugin Critical Vulnerability Being Exploited | Sucuri Blog"
