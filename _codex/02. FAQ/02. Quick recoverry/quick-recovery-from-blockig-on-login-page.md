---
layout: page
category: codex
section: FAQ
title: Quick recovery from blocking on your login page
excerpt: Quick recovery from blocking on your login page
---

When you have &ldquo;**Sorry, your request cannot be accepted**&rdquo; message on 
your login page, please follow the steps bllow.

![Blocking message]({{ '/img/2018-12/BlockingMessage.png' | prepend: site.baseurl }}
 "Blocking message"
)

1. Rename `ip-geo-block` in your `/wp-content/plugins/` to `ip-geo-block-` to deactivate the plugin using FTP or your file manager like cPanel.

2. Login to your site as an admin. You'll see the following message on your plugins page.  
  
    > The plugin `ip-geo-block/ip-geo-block.php` has been deactivated due to an error: Plugin file does not exist.  
    
    ![Message on plugins page]({{ '/img/2018-12/MessageOnPluginsPage.png' | prepend: site.baseurl }}
     "Message on plugins page"
    )  
  
3. Revert the renamed `ip-geo-block-` to the original name `ip-geo-block` using FTP or your file manager like cPanel.

4. Refresh your plugins page and activate the plugin again.

5. Go to the settings page of this plugin when you find the warning message "**Once you logout, you will be unable to login again...**" like the following picutre.  
  
    ![Warning message in case of blocking]({{ '/img/2018-12/WarningBlocking.png' | prepend: site.baseurl }}
     "Warning message in case of blocking"
    )

6. Check your country code and configure the settings properly in "**Validation rules and behavior**" section.

![Configure your settings]({{ '/img/2018-12/ConsistencyCountryCode.png' | prepend: site.baseurl }}
 "Configure your settings"
)

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block &#124; WordPress.org"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block/ "View: Plugin Support &laquo; WordPress.org Forums"
