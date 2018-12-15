---
layout: page
category: codex
section: FAQ
title: Quick recovery from blocking on your login page
excerpt: Quick recovery from blocking on your login page
---

If you see the message &ldquo;**Sorry, your request can not be accepted**&rdquo;
on your login page like the picture bellow, please follow the steps:

![Blocking message]({{ '/img/2018-12/BlockingMessage.png' | prepend: site.baseurl }}
 "Blocking message"
)

1. Rename `ip-geo-block` to `ip-geo-block-` in the plugin directory 
   (`/wp-content/plugins/`) on your server using FTP or the file manager like 
   cPanel. This makes the plugin deactivated.

2. Login to your site as an admin. You'll see the following message on your plugins page.  
  
    > The plugin `ip-geo-block/ip-geo-block.php` has been deactivated due to an error: Plugin file does not exist.  
    
    ![Message on plugins page]({{ '/img/2018-12/MessageOnPluginsPage.png' | prepend: site.baseurl }}
     "Message on plugins page"
    )  

3. Revert the renamed `ip-geo-block-` to the original name `ip-geo-block` using FTP or your file manager like cPanel.

4. Refresh your plugins page, then activate the plugin **IP Geo Block** again.

5. Go to the settings page of **IP Geo Block** when you find the warning message
   &ldquo;**Once you logout, you will be unable to login again...**&rdquo; like
   the following picutre.  
  
    ![Warning message in case of blocking]({{ '/img/2018-12/WarningBlocking.png' | prepend: site.baseurl }}
     "Warning message in case of blocking"
    )

6. Check your country code and configure the settings properly in "**Validation rules and behavior**" section.
  
    ![Configure your settings]({{ '/img/2018-12/ConsistencyCountryCode.png' | prepend: site.baseurl }}
     "Configure your settings"
    )
  Or in case your IP address is blacklisted:  
    ![Your IP address is blacklisted]({{ '/img/2018-12/BlacklistedIP.png' | prepend: site.baseurl }}
     "Your IP address is blacklisted"
    )

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block &#124; WordPress.org"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block/ "View: Plugin Support &laquo; WordPress.org Forums"
