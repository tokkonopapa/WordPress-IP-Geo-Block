---
layout: page
category: codex
section: Features and performance
title: Page speed performance
excerpt: The latest result of P3 (Plugins Performance Profiler) on back-end and front-end.
script:
inline:
---

### Conditions ###
- **Server:**  
  Apache on a [shared hosting server](http://homepage.nifty.com/ "LaCoocan").  
  WordPress 4.6.1-ja / Theme: Twenty Twelve  
  PHP: PHP-5.2.14 (it's too old!!) / MySQL-5.5.21

- **IP Geo Block:**  
  3.0.0 and later

- **IP Geo Block settings:**  
  [ip-geo-block-settings.json](https://gist.github.com/tokkonopapa/a6805c53b32e0fb1dc49c19434e81591 "IP Geo Block settings for performance measure.")

- **Plugins:**  
  Here's the list of activated plugins and [Must-use plugins](https://codex.wordpress.org/Must_Use_Plugins "Must Use Plugins &laquo; WordPress Codex").

![Activated Plugins]({{ '/img/2016-10/P3-plugins.png' | prepend: site.baseurl }}
 "Activated Plugins"
)

![Must-use Plugins]({{ '/img/2016-10/P3-mu-plugins.png' | prepend: site.baseurl }}
 "Must-use Plugins"
)

### Results ###

- **Auto:**  
  This includes accesses on both admin area and public facing pages under the 
  confition of logged in as an admin.

![The result of P3 (auto)]({{ '/img/2016-10/P3-auto.png' | prepend: site.baseurl }}
 "The result of P3 (auto)"
)

- **Manual:**  
  This includes accesses only on public facing pages under the condition of 
  logged off as an anonymous visitor with another browser.

![The result of P3 (manual)]({{ '/img/2016-10/P3-manual.png' | prepend: site.baseurl }}
 "The result of P3 (manual)"
)

IP Geo Block spends only 0.5 [msec] !?

It may be incorrect because of **"mu-plugins" (ip-geo-block-mu.php)** mode.
Here is the result with **"init" action hook** mode.

![The result of P3 (init action hook)]({{ '/img/2016-10/P3-manual-init.png' | prepend: site.baseurl }}
 "The result of P3 (init action hook)"
)