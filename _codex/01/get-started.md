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

### Still see the "Welcome" message? ###

When you still see the "Welcome" message on the IP Geo Block dashboard, please 
select either "**white list**" or "**black list**" at "**Matching rule**" and 
put a proper country code into the "**Country code for matching rule**".

![Still see the welcome message]({{ '/img/2016-01/WelcomeMessage.png' | prepend: site.baseurl }}
 "Still see the welcome message"
)

### In case of the "Lock out" warning ###

Even if you save a wrong count code, you'll never be locked out while you are 
logged in as an administrator.

Please click the "**Scan your country code**" button to find your proper 
country code.

![Lock out warning message]({{ '/img/2016-01/LockoutWarning.png' | prepend: site.baseurl }}
 "Lock out warning message"
)

### See also ###

- [What should I do when I'm locked out?][HowToRecover]
- [The best practice of target settings][BestPractice]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[HowToRecover]: {{ '/codex/what-should-i-do-when-i-m-locked-out.html' | prepend: site.baseurl }} "What should I do when I'm locked out? | IP Geo Block"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
