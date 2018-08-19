---
layout: page
category: codex
title: Get Started
excerpt: Get started with IP Geo Block
---

### Be relax at first contact! ###

Just after the installation and activation of [IP Geo Block][IP-Geo-Block], 
you'll see the "Welcome" message and will be prompt to confirm the 
"**Matching rule**" at "**Validation rule settings**".

But it's better to keep yourself in a relaxed state for a several seconds, 
becase a background process of downloading Geo-IP databases may be running.

![After activation of IP Geo Block]({{ '/img/2016-01/AfterActivation.png' | prepend: site.baseurl }}
 "After activation of IP Geo Block"
)

### Configure the settings ###

The following picuture shows all the sections to configure this plugin to fit 
your demmand.

![All sections on Settings tab]({{ '/img/2017-03/TabSettings.png' | prepend: site.baseurl }}
 "All sections on Settings tab"
)

The most important sections are placed at the top 3:

- **Validation rule settings**  
  You can configure the common rules of validation of country code for back-end
  and front-end. Also you can setup the behavior of this plugin when blocking 
  happens.  
  ![Validation rule settings]({{ '/img/2017-03/ValidationRuleSettings.png' | prepend: site.baseurl }}
   "Validation rule settings"
  )

- **Back-end target settings**  
  Initial settings in this section are configured as "minimum" from a security 
  point of view. The easiest way to "maximize" the protection ability of this 
  plugin against various attacks is to push "**Best settings**" button in 
  "**Plugin settings**" section. Please try it!  
  ![Back-end target settings]({{ '/img/2017-03/BackendTargetSettings.png' | prepend: site.baseurl }}
   "Back-end target settings"
  )

- **Front-end target settings**  
  In this section, you can configure "**Matching rule**" as different from the 
  "**Validation rule settings**". For some details, please refre to 
  "[Blocking on front-end][BlockFront]".
  ![Front-end target settings]({{ '/img/2017-03/FrontendTargetSettings.png' | prepend: site.baseurl }}
   "Front-end target settings"
  )

### If initial setup fails... ###

You'll find the error message and the "**Matching rule**" is still `Disable`.

![Download fail]({{ '/img/2016-01/DownloadFail.png' | prepend: site.baseurl }}
 "Download fail"
)

In this case, please go to "**Local database settings**" and execute to 
download databases manually. And then, select either "**white list**" or 
"**black list**" and put a proper country code into the "**Country code for 
matching rule**".

![Download DBs]({{ '/img/2016-01/DownloadDBs.png' | prepend: site.baseurl }}
 "Download DBs"
)

### In case of the "Lock out" warning ###

Even if you save a wrong count code, you'll never be locked out while you are 
logged in as an administrator.

Please click the "**Scan your country code**" button to find your proper 
country code.

![Lock out warning message]({{ '/img/2016-01/LockoutWarning.png' | prepend: site.baseurl }}
 "Lock out warning message"
)

There're cases that each geolocation dabase has a 
[different country code][DifferentCC] especially in 
[Euro region][Euroregion]. This issue was reported at [support forum][Forum].
In this case, you had better to prioritize the country code by Maxmind.

![Different country code]({{ '/img/2015-09/ScanCountry.png' | prepend: site.baseurl }}
 "Different country code"
)

### See also ###

- [What should I do when I'm locked out?][HowToRecover]
- [The best practice of target settings][BestPractice]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BlockFront]:   {{ '/codex/#blocking-on-front-end'                     | prepend: site.baseurl }} "Blocking on front-end | IP Geo Block"
[HowToRecover]: {{ '/codex/what-should-i-do-when-i-m-locked-out.html'  | prepend: site.baseurl }} "What should I do when I'm locked out? | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[DifferentCC]:  {{ '/changelog/release-2.1.5.html'                     | prepend: site.baseurl }} "2.1.5 Release Note | IP Geo Block"
[Euroregion]:   https://en.wikipedia.org/wiki/Euroregion "Euroregion - Wikipedia, the free encyclopedia"
[Forum]:        https://wordpress.org/support/topic/locked-out-due-to-eu-vs-country "WordPress &#8250; Support &raquo; [resolved] Locked out due to EU vs. Country"
