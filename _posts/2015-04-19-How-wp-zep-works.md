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
<!--more-->

### What's the best practice of plugin actions? ###

We can find [the answer on Stack Exchange][Stack-Exchange].

#### Showing plugin page ####

* `wp-admin/admin.php?page=my-plugin`
* `wp-admin/tools.php?page=my-plugin`
* `wp-admin/option-general.php?page=my-plugin`
* &hellip;

#### Requesting to <samp>wp-admin/admin.php</samp> ####

{% highlight ruby %}
<?php
add_action( 'admin_action_' . 'do-my-action', 'my_action' );
?>
<form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">
    <?php wp_nonce_field( 'do-my-action' ); ?>
    <input type="hidden" name="action" value="do-my-action">
    <input type="submit" value="Do my action" class="button">
</form>
{% endhighlight %}

{% highlight ruby %}
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

{% highlight ruby %}
add_action( 'wp_ajax_' . 'do-my-action', 'my_action' );
{% endhighlight %}

#### Requesting to <samp>wp-admin/admin-post.php</samp> ####

{% highlight ruby %}
add_action( 'admin_post_' . 'do-my-action', 'my_action' );
{% endhighlight %}

#### Handling request ####

{% highlight ruby %}
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

### The limitation of WP-ZEP ###

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress &#8250; IP Geo Block &laquo; WordPress Plugins"
[Stack-Exchange]: http://wordpress.stackexchange.com/questions/10500/how-do-i-best-handle-custom-plugin-page-actions "wp admin - How do i best handle custom plugin page actions? - WordPress Development Stack Exchange"
