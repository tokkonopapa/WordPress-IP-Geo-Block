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
with an [appropriate editor][editors]. You can find the "**Emergent 
Functionality**" code section near the bottom of the file as follows:

{% highlight php %}
<?php
/*----------------------------------------------------------------------------*
 * Emergent Functionality
 *----------------------------------------------------------------------------*/

/**
 * Invalidate blocking behavior in case yourself is locked out.
 *
 * How to use: Activate the following code and upload this file via FTP.
 */
/* -- ADD `/` TO THE TOP OR END OF THIS LINE TO ACTIVATE THE FOLLOWINGS -- *
function ip_geo_block_emergency( $validate ) {
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency', 1, 2 );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency', 1, 2 );
// */
{% endhighlight %}

This code block can be activated by replacing `/*` (opening multi-line comment)
at the top of the line to `//` (single line comment), or `*` at the end of the 
line to `*/` (closing multi-line comment).

{% highlight php %}
<?php
/*----------------------------------------------------------------------------*
 * Emergent Functionality
 *----------------------------------------------------------------------------*/

/**
 * Invalidate blocking behavior in case yourself is locked out.
 *
 * How to use: Activate the following code and upload this file via FTP.
 */
//* -- ADD `/` TO THE TOP OR END OF THIS LINE TO ACTIVATE THE FOLLOWINGS -- *
function ip_geo_block_emergency( $validate ) {
	$validate['result'] = 'passed';
	return $validate;
}
add_filter( 'ip-geo-block-login', 'ip_geo_block_emergency', 1, 2 );
add_filter( 'ip-geo-block-admin', 'ip_geo_block_emergency', 1, 2 );
// */
{% endhighlight %}

After saving and uploading it into `ip-geo-block` in your plugins folder 
(typically `/wp-content/plugins/`) via FTP or [cPanel File Manager][cPanel-FM],
you become to be able to login again as an admin.

Then you can re-configure "**Maching rule**" and "**Country code for matching 
rule**" at "**Validation rule settings**" properly. After that, do not forget 
to restore the `ip-geo-block.php` on your server to the original one.

If you have no confidence in editing PHP file, please download ZIP from 
[here][GIST] and use it that "Emergent Functionality" is already activated.

### Deactivate by force ###

Although the above processes is strongly recommended at your emergency, some 
users are not familiar with this type of jobs.

In that case, you can rename `ip-geo-block` folder to `ip-geo-block.bak` using
FTP or something. Then you cal login and see the following message on your 
plugin's dashboard.

![Force to deactivate]({{ '/img/2015-08/ForceDelete.png' | prepend: site.baseurl }}
 "Force to deactivate"
)

After renaming `ip-geo-block.bak` to the original, you can activate this plugin
again.

### Another solution at emergency ###

You can also just forcibly remove `ip-geo-block` in your plugin's folder. Then 
you'll see the same message as the above picture on your plugin's dashboard.

After that, you can reinstall through "**Add New**" button and reactivate again.
But you'll find soon you're blocked again because your settings still remains 
in your database.

![Blocking message]({{ '/img/2017-03/AdminBlocking.png' | prepend: site.baseurl }}
 "Blocking message"
)

But don't worry about that. A background process kicked by the activation will 
rescue you. After pausing for breath, you can visit your admin dashboard again!

<div class="alert alert-warning">
	<strong>Warning:</strong>
	Do not delete <code>ip-geo-api</code> directory.
	If you do that, this solution becomes never to work.
</div>

### For power users ###

If you're familiar with the use of phpMyAdmin and know where the plugin's 
options are saved, you can change the value of `matching_rule` to `-1` which 
means `Disable`. Please do it at your own risk.

![Change matching_rule via phpMyAdmin]({{ '/img/2016-01/MatchingRule.png' | prepend: site.baseurl }}
 "Change matching_rule via phpMyAdmin"
)

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[editors]:      https://codex.wordpress.org/Editing_Files#Using_Text_Editors "Editing Files « WordPress Codex"
[cPanel-FM]:    https://documentation.cpanel.net/display/ALD/File+Manager "File Manager - Documentation - cPanel Documentation"
[GIST]:         https://gist.github.com/tokkonopapa/90921317325a3fc50791869cfcf81d04 "Emergent Functionality"
