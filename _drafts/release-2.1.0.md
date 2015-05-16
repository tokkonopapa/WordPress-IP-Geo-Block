---
layout: post
title:  "2.1.0 Release Note"
date:   2015-05-01 00:00:00
categories: changelog
published: true
---

In this release, the ability of WP-ZEP have been greatly improved. Previously, 
the probability of successful prevention against the zero-day attack (true 
positive) was estimated about 26%. But now it's 60%. Please refer to 
[this article][investigation] about the background of these percentage.

In this note, I'll mention what's new in 2.1.0.

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
`ip-geo-block-wp-content` can be available to specify some pages or plugins 
to drop them from the target. To use those filter hooks, you should add 
appropriate code into your `functions.php` as follows:

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

I hope there's no need this kind of bypass.
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

### Bug fix ###
There's a bug that the order of the arguments for the action handler 
`ip-geo-block-backup-dir` was incorrect. Now it works correctly as shown in 
the [samples.php][samples.php].

### Improvement ###
In the previous version, the "[Referer Suppressor][Referer-Suppressor]", that 
eliminate the browser's referer, do nothing with an element which is added into 
the DOM after DOM ready. This issue could be seen at the "WordPress News" on 
the dashboard, where the contents were added after firing the browser's 
document ready.

It doesn't mean that this plugin was vulnerable but should be fixed.
The `click` event handler is now delegated at the `body`.

[IP-Geo-Block]:       https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[samples.php]:        https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/samples.php "WordPress-IP-Geo-Block/samples.php at master - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
[investigation]:      {{ "/article/which-attacks-prevented.html" | prepend: site.baseurl }} "Whick attacks prevented?"
[Referer-Suppressor]: {{ "/article/referer-suppressor.html" | prepend: site.baseurl }} "Referer Suppressor for external link"
