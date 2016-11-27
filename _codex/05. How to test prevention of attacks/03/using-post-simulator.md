---
layout: page
category: codex
section: how to test prevention of attacks
title: Using WordPress post simulator
---

You may want to test the blocking behavior of this plugin. This document 
shows you how to use [WordPress Post Simulator][Simulator] which simulate 
various attacks to the WordPress site through the comment spam, trackback, 
pingback, ajax and so on.

<!--more-->

### Preparation ###

The simulator is composed by JavaScript. So it should be uploaded to the same 
domain with the target WordPress site because of the limitation of 
[Same-origin policy][SameOrigin].

Please download the [ZIP file of IP Geo Block master][MASTER-ZIP], unzip it, 
then upload `css`, `js` and `index.html` in the `test` folder to the 
appropriate directory on your server.

![Files required to be uploaded]({{ '/img/2016-02/FilesOnGitHub.png' | prepend: site.baseurl }}
 "Files required to be uploaded"
)

To prevent abuse by someone, the name of the uploaded directory should be 
secret which can be made up by referring [WordPress Secret Key API]
[SecretKeyAPI] for example, but only choosing [unreserved characters in 
RFC3986][RFC3986-2.3].

### WordPress Post Simulator ###

When you access to the uploaded `index.html`, you can see the following page.

![WordPress post simulator]({{ '/img/2016-02/PostSimulatorHead.png' | prepend: site.baseurl }}
 "WordPress post simulator"
)

#### Page Settings ####

The first step is to set up the WordPress related URL and proxy IP address.

1. **WordPress Home**  
   Home URL of your WordPress site. Push `Validate` to check the page.
2. **Single Page**  
   URL of a single page which has a comment form. Push `Validate` to check the 
   page.
3. **Proxy IP address**  
   When you push `Generate`, a random IP address is generated. It will be set 
   as the `HTTP_X_FORWARDED_FOR` header in order to simulate the attacks from 
   outside of your country. If empty, no header will be sent.

#### Submission Settings ####

Currently, you can test 13 methods for submission. In each method, you can set 
the attack vectors as follows:

![Request parameter settings]({{ '/img/2016-02/PostSimulatorRequests.png' | prepend: site.baseurl }}
 "Request parameter settings"
)

#### Submission ####

The last thing you should do is to submit the requests. Then you can get the 
responses against each request in the text area.

![Submitting and results]({{ '/img/2016-02/PostSimulatorSubmit.png' | prepend: site.baseurl }}
 "Submitting and results"
)

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Simulator]:    https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/master/test "WordPress Post Simulator"
[SameOrigin]:   https://en.wikipedia.org/wiki/Same-origin_policy "Same-origin policy - Wikipedia, the free encyclopedia"
[MASTER-ZIP]:   https://github.com/tokkonopapa/WordPress-IP-Geo-Block/archive/master.zip "WordPress-IP-Geo-Block-master.zip"
[SecretKeyAPI]: https://codex.wordpress.org/WordPress.org_API#Secret_Key "WordPress.org API « WordPress Codex"
[RFC3986-2.3]:  https://tools.ietf.org/html/rfc3986#section-2.3 "2.3. Unreserved Characters / RFC 3986 - Uniform Resource Identifier (URI): Generic Syntax"
[BestPractice]: {{ '/codex/the-best-practice-of-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html' | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
