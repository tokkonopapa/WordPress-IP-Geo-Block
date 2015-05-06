---
layout: post
title:  "Release 2.1.0"
date:   2015-05-02 00:00:00
categories: changelog
published: true
---

From this release, the ability of WP-ZEP becomes more widely.

In this release, the ability of WP-ZEP have been greatly improved. In the 
previous version, the successful probability of preventing zero-day attack 
was estimated about 26%. But now it's 60%.

In this release note, I'll explain about it.

<!--more-->

In the version 2.0.8 or less, the prevention target of WP-ZEP was as follows:

* `wp-admin/admin-ajax.php` and `wp-admin/admin-post.php` with `action`
* `wp-admin/admin.php` with `action`

In 2.1.0, the followings are added:

* `wp-admin/*.php` with `page`
* `wp-content/plugins/name-of-plugin/…/*.php`
* `wp-content/themes/name-of-theme/…/*.php`

Along with above expansion, two filter hooks `ip-geo-block-admin-pages` and 
`ip-geo-block-wp-content` can be available to drop from the target.To use 
those filter hooks, please add appropriate code into your `functions.php` as 
follows:

{% highlight php startinline linenos %}
add_filter( 'ip-geo-block-admin-pages', 'my_admin_pages' );
add_filter( 'ip-geo-block-wp-content', 'my_wp_content' );

function my_admin_pages( $names ) {
    // ex) wp-admin/upload.php?page=name-of-page
    return $names + array( 'name-of-page' );
}

function my_wp_content( $names ) {
    // ex) wp-content/plugins/name-of-plugin/
    // ex) wp-content/themes/name-of-theme/
    return $names + array( 'name-of-plugin', 'name-of-theme' );
}
{% endhighlight %}

<!-- http://www.emoji-cheat-sheet.com/ -->
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
