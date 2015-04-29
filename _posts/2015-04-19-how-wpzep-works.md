---
layout: post
title:  "How does WP-ZEP prevent zero-day attack?"
date:   2015-04-19 00:00:00
categories: article
---

[IP Geo Block][IP-Geo-Block] is the only plugin which has an ability to 
prevent zero-day attack even if some of plugins in a WordPress site have 
unveiled vulnerability. I call it "**Z**ero-day **E**xploit **P**revention 
for wp-admin" (WP-ZEP).

In this article, I'll explain about its mechanism and also its limitations.
Before that, I'll mention about the best practice of plugin actions.

<!--more-->

### What's the best practice of plugin actions? ###

While we can find [the answer at Stack Exchange][Stack-Exchange], I'd like to 
describe a little in detail.

#### Showing plugin page ####

A url to the plugin dashboard depending on 
[its parent category][Sub-Level-Menu] can be specified  as follows:

* `wp-admin/admin.php?page=my-plugin`
* `wp-admin/tools.php?page=my-plugin`
* `wp-admin/option-general.php?page=my-plugin`
* &hellip;

#### Requesting to <samp>wp-admin/admin.php</samp> ####

On the plugin dashboard, we can provide an action `do-my-action` via 
`admin.php` with a form like this:

```html+php
<?php add_action( 'admin_action_' . 'do-my-action', 'my_action' ); ?>
<form action="<?php echo admin_url( 'admin.php' ); ?>">
    <?php wp_nonce_field( 'do-my-action' ); ?>
    <input type="hidden" name="action" value="do-my-action" />
    <input type="submit" class="button" value="Do my action" />
</form>
```

Or a link:

```html+php
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
```

#### Requesting to <samp>wp-admin/admin-ajax.php</samp> ####

We can also do the same thing via `admin-ajax.php` by `GET` or `POST` method 
using jQuery. This request can be handled via `wp_ajax_xxxx` action hook.

```php
<?php add_action( 'wp_ajax_' . 'do-my-action', 'my_action' ); ?>
```

#### Requesting to <samp>wp-admin/admin-post.php</samp> ####

WordPress also gives us a chance to handle `POST` request via `admin-post.php`.

```php
<?php add_action( 'admin_post_' . 'do-my-action', 'my_action' ); ?>
```

#### Processing the requests ####

All above-mentioned can be processed by the function `my_action()`.

{% highlight php startinline %}
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

In `my_action()`, the most important processes before **doing my action** are:

1. validate user privilege with `current_user_can()`.
2. validate the nonce with `check_admin_referer()`.
3. validate the given input.

When a plugin developer loses one of those, the result becomes serious.

So WP-ZEP will make up 1. and 2. by embedding a nonce into the request.

### The limitations of WP-ZEP ###

One big challenge for WP-ZEP is to embed a nonce. You already notice that 
there're countlessly many ways to do their own job besides the best practice.
For example, it is possible to distribute the request into their jobs in a 
plugin side.

```html
wp-admin/?page=my-plugin&job=do-my-job
```

WP-ZEP can do nothing about those cases.

Another big challenge is to decide whether the request hander is vulnerable or 
not if `my_action()` is registered for both authorized and unauthorized users 
like this:

{% highlight php startinline %}
add_action( 'wp_ajax_'        . 'do-my-action', 'my_action' );
add_action( 'wp_ajax_nopriv_' . 'do-my-action', 'my_action' );
{% endhighlight %}

If WP-ZEP blocks the action `do-my-action`, users on the public facing pages 
can not get any services via the ajax call. So in this case, all this plugin 
has to do is validating IP address by county code.

This causes a serious problem: 
[vulnerability in Slider Revolution][Slider-Revolution] 
cannot be blocked when the attack comes from the permitted country. (Because 
it had added the above two actions <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f620.png)
</span> !!) To protect against this kind of attack, you should add following 
snippet into your `functions.php`.
(Should I implement this kind of WAF functionality in this plugin?)

{% highlight php startinline %}
add_filter( 'ip-geo-block-admin', 'my_protectives' );
function my_protectives( $validate ) {
    $signatures = array(
        'wp-config.php',
        'passwd',
    );

    $req = strtolower( urldecode( serialize( $_GET + $_POST ) ) );

    foreach ( $signatures as $item ) {
        if ( strpos( $req, $item ) !== FALSE ) {
            $validate['result'] = 'blocked';
            break;
        }
    }

    return $validate;
}
{% endhighlight %}

The last limitation is related to validation of user privilege. WP-ZEP can not 
know which privilege is needed to `do-my-action`. For example, some plugins 
need `manage_options`, while `moderate_comments` is sufficient for others.
So all WP-ZEP can do is to validate if a user is logged-in or not as a minimum 
privilege.

This limitation will tolerate the vulnerability of 
[Privilege Escalation][PrivilegeEscalation]. You should prohibit guest users 
from registrating to pretend it.

### Conclusion ###

So many security plugins are there on WordPress.org, but nothing is perfect 
against the "Zero-day Attack".

So I'd like to keep this plugin simple and light enough to collaborate with 
other plugins while playing a certain degree of role by itself <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f47b.png)
</span>.

[IP-Geo-Block]:        https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Stack-Exchange]:      http://wordpress.stackexchange.com/questions/10500/how-do-i-best-handle-custom-plugin-page-actions "wp admin - How do i best handle custom plugin page actions? - WordPress Development Stack Exchange"
[Sub-Level-Menu]:      https://codex.wordpress.org/Administration_Menus#Sub-Level_Menus "Administration Menus « WordPress Codex"
[Slider-Revolution]:   https://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html "Slider Revolution Plugin Critical Vulnerability Being Exploited | Sucuri Blog"
[PrivilegeEscalation]: http://en.wikipedia.org/wiki/Privilege_escalation "Privilege escalation - Wikipedia, the free encyclopedia"
