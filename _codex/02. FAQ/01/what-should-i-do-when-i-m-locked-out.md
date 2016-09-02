---
layout: page
category: codex
section: FAQ
title: What should I do when I'm locked out?
excerpt: How to recover when I'm locked out?
---

### Emergent Functionality ###

When you are locked out by misfortune, this feature inhibits the blocking 
behavior of this plugin.

Download [IP Geo Block][IP-Geo-Block], unzip and open the `ip-geo-block.php` 
using [appropriate editor][editors]. Then activate the code block in 
"**Emergent Functionality**" section near the bottom of the file as follows:

{% highlight php %}
<?php
/*----------------------------------------------------------------------------*
 * Emergent Functionality
 *----------------------------------------------------------------------------*/

/**
 * Invalidate blocking behavior in case yourself is locked out.
 * @note: activate the following code and upload this file via FTP.
 */ /* -- EDIT THIS LINE AND ACTIVATE THE FOLLOWING FUNCTION -- *
function ip_geo_block_emergency( $validate ) {
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */
?>
{% endhighlight %}

This code block can be activated by replacing `/*` (opening multi-line comment)
to `//` (single line comment). Then save it and upload it to this plugin's 
directory on your server via FTP.

{% highlight php %}
<?php
/*----------------------------------------------------------------------------*
 * Emergent Functionality
 *----------------------------------------------------------------------------*/

/**
 * Invalidate blocking behavior in case yourself is locked out.
 * @note: activate the following code and upload this file via FTP.
 */ // -- EDIT THIS LINE AND ACTIVATE THE FOLLOWING FUNCTION -- *
function ip_geo_block_emergency( $validate ) {
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency' );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency' );
// */
?>
{% endhighlight %}

After reconfiguring "**Maching rule**" and "**Country code for matching rule**"
at "**Validation rule settings**" properly, do not forget to restore the 
`ip-geo-block.php` on your server to the original one.

### Another solution at emergency ###

Although the above process is strongly recommended at your emergency, some 
users are not familiar with editing PHP file.

In that case, you can just forcibly remove `ip-geo-block` in your plugin's 
directory (typically `wp-content/plugins`) by using FTP or 
[cPanel File Manager][cPanel-FM]. Then you'll see the following message on 
your plugin's dashboard.

![Force to delete]({{ '/img/2015-08/ForceDelete.png' | prepend: site.baseurl }}
 "Force to delete"
)

After that, you can reinstall and reactivate this plugin. But you'll find soon 
you're blocked again because your settings still remains in your database.

But don't worry about that. A background process kicked by the activation will 
rescue you. After pausing for breath, you can visit your admin dashboard again!

### For power users ###

If you're familiar with the use of phpMyAdmin, you can change the value of 
`matching_rule` to `-1` which means `Disable`. Please do it at your own risk.

![Change matching_rule via phpMyAdmin]({{ '/img/2016-01/MatchingRule.png' | prepend: site.baseurl }}
 "Change matching_rule via phpMyAdmin"
)

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[editors]:      https://codex.wordpress.org/Editing_Files#Using_Text_Editors "Editing Files « WordPress Codex"
[cPanel-FM]:    https://documentation.cpanel.net/display/ALD/File+Manager "File Manager - Documentation - cPanel Documentation"
