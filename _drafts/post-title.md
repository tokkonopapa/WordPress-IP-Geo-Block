---
layout: post
title:  "Post Title"
date:   2015-01-01 00:00:00
categories: article changelog
published: false
script: []
inline:
---

<!--more-->

### Title ###

{% highlight php starinline linenos %}
code
{% endhighlight %}

[![title]({{ '/img/2015-xx/sample.png' | prepend: site.baseurl }}
  "title"
)][link]

{% comment %} http://www.emoji-cheat-sheet.com/ {% endcomment %}
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

{% comment %} alert-{success|info|warning|danger} {% endcomment %}
<div class="alert alert-info">
	Information
</div>

| Left-Aligned  | Center Aligned  | Right Aligned |
|:--------------|:---------------:|--------------:|
| col 3 is      | some wordy text |         $1600 |
| col 2 is      | centered        |           $12 |
| zebra stripes | are neat        |            $1 |

<div class="table-responsive">
	<cite></cite>
	<table class="table">
		<thead>
			<tr>
				<th class="text-right">title1</th>
				<th class="text-right">title2</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="text-right">content1</td>
				<td class="text-right">content2</td>
			</tr>
		</tbody>
		<caption>caption</caption>
	</table>
</div>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[WP-ZEP]: {{ '/article/how-wpzep-works.html' | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack?"
