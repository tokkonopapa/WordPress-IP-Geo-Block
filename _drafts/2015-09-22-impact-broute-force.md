---
layout: post
title:  "Measuring load of broute-force attack"
date:   2015-09-22 09:00:00
categories: article
published: true
script: []
inline:
---

I have examined the load reduction performance against brute-force attacks by 
using [IP Geo Block][IP-Geo-Block]. I report the result in this article.

<!--more-->

### <span id="sec1">A shell program</span> ###

The [`attack.sh`][attack-sh] is a shell program which mesures load of malicious
burst accesses to WordPress back-end such as `wp-comments-post.php`, 
`xmlrpc.php`, `wp-login.php`, `wp-admin/admin-ajax.php` using 
[apache bench][ApacheBench].

[![attack.sh]({{ "/img/2015-09/attack-sh.png" | prepend: site.baseurl }}
  "attack.sh"
)][repository]

It gives an emulation of spam comment, pingback spam, login attempt and 
malicious access to the admin ajax with 5 multiple requests at a time 
throughout 60 seconds.

{% highlight php startinline linenos %}
code
{% endhighlight %}

<!-- html+php, css+php, js+php -->
```html
code
```

<!-- success, info, warning, danger -->
<div class="alert alert-info" role="alert">
	Information
</div>

[![title]({{ "/img/2015-xx/sample.png" | prepend: site.baseurl }}
  "title"
)][link]

<!-- http://www.emoji-cheat-sheet.com/ -->
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

| Left-Aligned  | Center Aligned  | Right Aligned |
|:--------------|:---------------:|--------------:|
| col 3 is      | some wordy text |         $1600 |
| col 2 is      | centered        |           $12 |
| zebra stripes | are neat        |            $1 |

<div class="table-responsive">
	<cite>cite</cite>
	<table class="table">
		<thead>
			<tr>
				<th>title1</th>
				<th>title2</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>content1</td>
				<td>content2</td>
			</tr>
		</tbody>
		<caption>caption</caption>
	</table>
</div>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[attack-sh]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/test/bin/attack.sh "WordPress-IP-Geo-Block/attack.sh at master"
[repository]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/master/test/bin "WordPress-IP-Geo-Block/test/bin at master"
[ApacheBench]:  http://httpd.apache.org/docs/current/programs/ab.html "ab - Apache HTTP server benchmarking tool"
