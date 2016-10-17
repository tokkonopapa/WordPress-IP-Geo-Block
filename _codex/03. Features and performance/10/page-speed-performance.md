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

- **Plugins:**  
  Here's the list of activated plugins.  
  
  ![Activated Plugins]({{ '/img/2016-10/P3-plugins.png' | prepend: site.baseurl }}
   "Activated Plugins"
  )

- **IP Geo Block:**  
  Version: 3.0.0 and later.  
  Settings:
  [ip-geo-block-settings.json](https://gist.github.com/tokkonopapa/a6805c53b32e0fb1dc49c19434e81591 "IP Geo Block settings for performance measure.")  

<div class="alert alert-info">
    The <strong>Validation timing</strong> shoule be set as 
    <strong>"init" action hook</strong>. If you set it as 
    <strong>"mu-plugins" (ip-geo-block-mu.php)</strong>, P3 would fail to 
    measure the performance of this plugin because 
    <a href="https://codex.wordpress.org/Must_Use_Plugins" title="Must Use Plugins &laquo; WordPress Codex">Must-use plugins</a>
    would be into the race condition.  
    <img src="/img/2016-10/P3-mu-plugins.png" title="Must-use Plugins" />
</div>

### Results ###

[P3 (Plugin Performance Profiler)](https://wordpress.org/plugins/p3-profiler/ "P3 (Plugin Performance Profiler) &mdash; WordPress Plugins")
can investigate WordPress plugins' performance by measuring their impact on 
your site's load time.

This awesome tool has two mode to measure the performance. One is "**Auto**" 
which will access to both admin area and public facing pages under the 
confition of logged in as an admin. On the other hand, "**Manual**" mode can 
make it possible to freely access.

In this report, the point of interest should be the impact on accessing public 
facing pages as an anonymous visitor. So the following results were measured 
with a [private window](https://support.mozilla.org/en-US/kb/private-browsing-use-firefox-without-history "Private Browsing - Use Firefox without saving history | Firefox Help").

- **Use case 1:** - enables only "**Back-end target settings**"  
  ![The result of P3 (back-end)]({{ '/img/2016-10/P3-manual-backend.png' | prepend: site.baseurl }}
   "The result of P3 (back-end)"
  )
  
- **Use case 2:** - enables both "**Back-end target settings**" and "**Front-end target settings**"  
  ![The result of P3 (front-end)]({{ '/img/2016-10/P3-manual-frontend.png' | prepend: site.baseurl }}
   "The result of P3 (front-end)"
  )

### Conclusion ###

If you prefer to serve your contents to all visitors from the world, it's good 
to leave "**Front-end target settings**" disable for the performance reason.

Once you enable it, please consider to reduce the number of "**UA string and 
qualification**". Especially, if it includes `HOST`, reverse DNS lookup is 
fetched when it isn't in the internal cache.

![Front-end target settings]({{ '/img/2016-10/FrontendTarget.png' | prepend: site.baseurl }}
 "Front-end target settings"
)

Here's the result in case that "**UA string and qualification**" is empty.

![Empty UA string and aualification]({{ '/img/2016-10/P3-manual-front-empty.png' | prepend: site.baseurl }}
 "Empty UA string and aualification"
)

