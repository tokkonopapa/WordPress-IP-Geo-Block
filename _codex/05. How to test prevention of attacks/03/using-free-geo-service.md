---
layout: page
category: codex
section: how to test prevention of attacks
title: Using free geolocation service
---

Here, I will introduce some of free services that preview how your webpage 
looks in multiple locations.

<!--more-->

### WebPageTest ###

[WebPageTest][WebPageTest] is a tool for measuring and analyzing the 
performance of web pages. There are many options that may seem complicated 
at first, but you can use it just putting the URL of your site into the text 
box and simply hitting the "START TEST" button. You can choose the server's 
location among several regions such as North/South America, Europe, Africa, 
Asia and Oceania.

[![WebPageTest]({{ '/img/2016-08/WebPageTest.png' | prepend: site.baseurl }}
  "WebPageTest"
)](https://www.webpagetest.org/ "WebPagetest - Website Performance and Optimization Test")

Unfortunately, you can't get the screen shots that would show how your site 
were rendered when [your site returns 4xx-5xx][ResultCodes] as a response code.
But you can know that IP Geo Block works fine.

![Test Result]({{ '/img/2016-08/WebPageTestResult.png' | prepend: site.baseurl }}
 "Test Result"
)

### GeoScreenshot ###

[GeoScreenshot][GeoScreenshot] is a very straightforward tool. You can pick up 
three countries and get the screen shots in those.

[![GeoScreenshot]({{ '/img/2016-08/GeoScreenshot.png' | prepend: site.baseurl }}
  "GeoScreenshot"
)](https://www.geoscreenshot.com/ "GeoScreenshot - Easy GeoIP, SEO, Local Ad and CDN Testing from Multiple Locations")

For example, my site looks as follows in Japan :

![My Site]({{ '/img/2016-08/MySite.png' | prepend: site.baseurl }}
 "My Site"
)

And here is a result from GeoScreenshot :

![Captured results]({{ '/img/2016-08/GeoScreenCaptured.png' | prepend: site.baseurl }}
 "Captured results"
)

You can also check whether IP Geo Block and your caching plugin does conflict 
or not.

[IP-Geo-Block]:  https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[WebPageTest]:   https://www.webpagetest.org/ "WebPagetest - Website Performance and Optimization Test"
[ResultCodes]:   https://sites.google.com/a/webpagetest.org/docs/using-webpagetest/result-codes "Result Codes - WebPagetest Documentation"
[GeoScreenshot]: https://www.geoscreenshot.com/ "GeoScreenshot - Easy GeoIP, SEO, Local Ad and CDN Testing from Multiple Locations"
