---
layout: post
title:  "2.1.0 Release Note"
date:   2015-05-02 00:00:00
categories: changelog
published: true
---

In this release, the ability of WP-ZEP have been greatly improved. Previously, 
the successful probability of preventing zero-day attack was estimated about 
26%. But now it's 60%.

About the background of these numbers, please refer to 
[this article][investigation].

In this note, I'll explain about the functionarity of 2.1.0.

<!--more-->

### New feature ###

In 2.0.8 or less, the prevention target of WP-ZEP was as follows:

* `wp-admin/admin-ajax.php` and `wp-admin/admin-post.php` with `action`
* `wp-admin/admin.php` with `action`

In 2.1.0, the followings are added:

* `wp-admin/*.php` with `page`
* `wp-content/plugins/name-of-plugin/…/*.php`
* `wp-content/themes/name-of-theme/…/*.php`

Along with these expansions, two filter hooks `ip-geo-block-admin-pages` and 
`ip-geo-block-wp-content` can be available to drop from the target. To use 
those filter hooks, you should add appropriate code into your `functions.php` 
as follows:

{% highlight php startinline linenos %}
add_filter( 'ip-geo-block-admin-pages', 'my_admin_pages' );
add_filter( 'ip-geo-block-wp-content', 'my_wp_content' );

function my_admin_pages( $names ) {
    // ex) wp-admin/tools.php?page=name-of-page
    return $names + array( 'name-of-page' );
}

function my_wp_content( $names ) {
    // ex) wp-content/plugins/name-of-plugin/
    // ex) wp-content/themes/name-of-theme/
    return $names + array( 'name-of-plugin', 'name-of-theme' );
}
{% endhighlight %}

I expect that there's no need this kind of bypass.
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

### A remaining issue ###

"[Referer Suppressor][Referer-Suppressor]" which eliminate the browser's 
referer does'nt work correctly when the ajax request from "WordPress News" 
on the dashboard have not finished before firing the browser's document ready.

I'll fix this issue in the next release.

[IP-Geo-Block]:       https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[investigation]:      {{ "/article/which-attacks-prevented.html" | prepend: site.baseurl }} "Whick attacks prevented?"
[Referer-Suppressor]: {{ "/article/referer-suppressor.html" | prepend: site.baseurl }} "Referer Suppressor for external link"
