---
layout: post
title:  "Impact on server load caused by brute-force attacks"
date:   2015-10-04 10:00:00
categories: article
published: true
script: []
inline:
---

I have examined the load reduction performance against brute-force attacks by 
using [IP Geo Block][IP-Geo-Block]. I report the result in this article.

<!--more-->

### A shell program ###

The [`attack.sh`][attack-sh] is a shell program which mesures load of malicious
burst accesses to WordPress back-end such as `wp-comments-post.php`, 
`xmlrpc.php`, `wp-login.php`, `wp-admin/admin-ajax.php` using 
[ApacheBench][ApacheBench].

[![attack.sh](/img/2015-10/attack-sh.png
  "attack.sh"
)][repository]

It gives an emulation of spam comment, pingback spam, login attempt and 
malicious access to the admin ajax with 5 multiple requests at a time 
throughout 60 seconds.

### Test environment ###

I setup the testbed in my local PC which specifications are followings:

| Category      | Description                                       |
|:--------------|:--------------------------------------------------|
| Hardware      | MacBook Pro / 2.8GHz Core i7 / Memory 16GB        |
| Software      | OS X 10.9.5 / MAMP 2.0 (Apache 2.2.22, PHP 5.4.4) |
| WordPress     | 4.3-ja / Site Language: English                   |

And here are the plugins which installed in the above environments:

![Plugins](/img/2015-10/Plugins.png
 "Plugins"
)

Speaking generally, it is better to separate the hardware on each side of 
requesting and responding because those have an influence on each other.
But unfortunately I don't have any such rich environments. So please take it 
into consideration when you see the results.

### Plugins configuration ###

[Wordfence][Wordfence] has a lot of options. So I leave them as just after 
installation.

On the other hand, options for [IP Geo Block 2.1.5][IP-Geo-Block] are changed 
as follows:

![Validation target settings](/img/2015-10/ValidationTarget.png
 "Validation target settings"
)

### The results ###

At the begining of each test, the DB was optimized using 
[Optimize Database after Deleting Revisions][OptimizeDB].

I picked up only "**Requests per second**", "**Time per request** (across all 
concurrent requests)" from the results of [ApacheBench][ApacheBench] which 
indicate the performace of load reduction. The higher in "**Requests/sec**" 
and the lower in "**Time/req [ms]**" are better.

The "**IBG**" means "IP Geo Block", "**WFS**" means "Wordfence Security", "ON" 
indicates "Activate" and "OFF" indicates "Deactivate".

#### `wp-comments-post.php` ####

| IGB | WFS | Requests/sec | Time/req [ms] |
|:----|:----|-------------:|--------------:|
| OFF | OFF |         4.54 |       220.073 |
| OFF | ON  |         4.02 |       248.843 |
| ON  | ON  |         5.98 |       167.262 |
| ON  | OFF |         6.33 |       157.940 |


#### `xmlrpc.php` ####

| IGB | WFS | Requests/sec | Time/req [ms] |
|:----|:----|-------------:|--------------:|
| OFF | OFF |         6.13 |       163.065 |
| OFF | ON  |         5.49 |       182.308 |
| ON  | ON  |         5.33 |       187.570 |
| ON  | OFF |         5.81 |       172.120 |

#### `wp-login.php` ####

| IGB | WFS | Requests/sec | Time/req [ms] |
|:----|:----|-------------:|--------------:|
| OFF | OFF |         6.43 |       155.479 |
| OFF | ON  |         4.97 |       201.078 |
| ON  | ON  |         5.98 |       167.145 |
| ON  | OFF |         6.23 |       160.487 |

When both "**IGB**" and "**WFS**" are "ON", I got the following email:

> A user with IP address WWW.XXX.YYY.ZZZ has been locked out from the signing 
> in or using the password recovery form for the following reason: Exceeded 
> the maximum number of login failures which is: 20. The last username they 
> tried to sign in with was: 'admin'

where "WWW.XXX.YYY.ZZZ" is IP address which is set by `attack.sh`. And there's 
no validation logs in IP Geo Block. It means that the excution priority of 
Wordfence is higher than IP Geo Block.

#### `wp-admin/admin-ajax.php` ####

| IGB | WFS | Requests/sec | Time/req [ms] |
|:----|:----|-------------:|--------------:|
| OFF | OFF |         5.51 |       181.351 |
| OFF | ON  |         5.00 |       200.071 |
| ON  | ON  |         5.53 |       180.822 |
| ON  | OFF |         6.03 |       165.840 |

### Conclusion ###

Speaking about the site performance, more plugins leads to less speed. The 
results shows that the performance of load reduction by IP Geo Block against 
brute-force attacks is not so outstanding, but I think it minimize a rise of 
the load. <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/2728.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[repository]:   https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/master/test/bin "WordPress-IP-Geo-Block/test/bin at master"
[attack-sh]:    https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/test/bin/attack.sh "WordPress-IP-Geo-Block/attack.sh at master"
[ApacheBench]:  http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool"
[Testbed]:      https://en.wikipedia.org/wiki/Testbed "Testbed - Wikipedia, the free encyclopedia"
[Wordfence]:    https://www.wordfence.com/ "WordPress Security Plugin | Wordfence"
[OptimizeDB]:   https://wordpress.org/plugins/rvg-optimize-database/ "WordPress › Optimize Database after Deleting Revisions « WordPress Plugins"
[NinjaFire]:    http://blog.nintechnet.com/wordpress-brute-force-attack-detection-plugins-comparison/ "WordPress: Brute-force attack detection plugins comparison"
