---
layout: page
category: codex
section: FAQ
title: Quick recovery from blocking on login page
excerpt: Quick recovery from blocking on login page
---

If you see the message &ldquo;**Sorry, your request can not be accepted**&rdquo;
on your login page like the picture bellow, please follow the steps:

![Blocking message]({{ '/img/2018-12/BlockingMessage.png' | prepend: site.baseurl }}
 "Blocking message"
)

1. Rename `ip-geo-block` to `ip-geo-block-` in the plugin directory 
   (`/wp-content/plugins/`) on your server using FTP or the file manager like 
   cPanel. This makes the plugin deactivated.

2. Login to your site as an admin. You'll see the following message on your **plugins** page.  
  
    > The plugin `ip-geo-block/ip-geo-block.php` has been deactivated due to an error: Plugin file does not exist.  
    
    ![Message on plugins page]({{ '/img/2018-12/MessageOnPluginsPage.png' | prepend: site.baseurl }}
     "Message on plugins page"
    )  
    <div class="alert alert-info">
        <strong>Note:</strong> When you configure
        <code>"mu-plugins" (ip-geo-block-mu.php)</code>
        as <a href="/codex/validation-timing.html" title="Validation timing | IP Geo Block">
        Validation timing</a>, then you'll also find the message
        "<strong>Can't find IP Geo Block in your plugins directory</strong>"
        like the above picture that can be ignored for now.
    </div>

3. Revert the renamed `ip-geo-block-` to the original name `ip-geo-block` using FTP or your file manager.

4. Refresh your plugins page, then activate **IP Geo Block** again.

5. Resolve the cause of blocking according to the error message as follows.

### Error message ###

- ##### Validation rules and behavior #####  
  When you find the following message:
  ![Once you logout...Validation rules and behavior]({{ '/img/2018-12/WarningBlocking.png' | prepend: site.baseurl }}
   "Once you logout...Validation rules and behavior"
  )
  then check your country code and configure properly on **Settings** tab:
  ![Configure your settings]({{ '/img/2018-12/ConsistencyCountryCode.png' | prepend: site.baseurl }}
   "Configure your settings"
  )
  Or in case your IP address is blacklisted:
  ![Your IP address is blacklisted]({{ '/img/2018-12/BlacklistedIP.png' | prepend: site.baseurl }}
   "Your IP address is blacklisted"
  )

- ##### Statistics in IP address cache #####  
  When you find the following message:
  ![Once you logout...Statistics in IP address cache]({{ '/img/2018-12/WarningLoginAttempts.png' | prepend: site.baseurl }}
   "Once you logout...Statistics in IP address cache"
  )
  then remove your IP address from the cache on **Statistics** tab:
  ![Login attempts exceeds the limit]({{ '/img/2018-12/StatisticsIPAddress.png' | prepend: site.baseurl }}
   "Login attempts exceeds the limit"
  )

- ##### Geolocation API libraries #####  
  When you find the following message:
  ![Can not load Geolocation API libraries]({{ '/img/2018-12/WarningGeoLibraries.png' | prepend: site.baseurl }}
   "Can not load Geolocation API libraries"
  )
  then please try to deactivate **IP Geo Block** once and re-activate again
  to re-install Geolocation API libraries on **Plugins** page.

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block &#124; WordPress.org"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block/ "View: Plugin Support &laquo; WordPress.org Forums"
[MU-PLUGINS]:   {{ '/codex/validation-timing.html' | prepend: site.baseurl }} "Validation timing | IP Geo Block"
