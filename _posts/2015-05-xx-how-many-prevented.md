---
layout: post
title:  "How many vulnerabilities can WP-ZEP prevent?"
date:   2015-05-xx 00:00:00
categories: article
published: false
---
http://aristeides.com/blog/why-i-like-jetpack/
<!--more-->
https://wpvulndb.com/plugins
https://wpvulndb.com/vulnerabilities/7879 sqli
https://wpvulndb.com/vulnerabilities/7873 xss
https://wpvulndb.com/vulnerabilities/7821 xss
https://wpvulndb.com/vulnerabilities/7798 sqli xss / x
https://wpvulndb.com/vulnerabilities/7866 fi
https://wpvulndb.com/vulnerabilities/7830 afu
https://wpvulndb.com/vulnerabilities/7813 csrf
https://wpvulndb.com/vulnerabilities/7817 xss csrf
https://wpvulndb.com/vulnerabilities/7794 sqli
https://wpvulndb.com/vulnerabilities/7911 afd
https://wpvulndb.com/vulnerabilities/7814 xss
https://wpvulndb.com/vulnerabilities/7888 csrf
https://wpvulndb.com/vulnerabilities/7896 afu
https://wpvulndb.com/vulnerabilities/7901 sqli
https://wpvulndb.com/vulnerabilities/7791 csrf xss
https://wpvulndb.com/vulnerabilities/7884 afu
https://wpvulndb.com/vulnerabilities/7937 xss
https://wpvulndb.com/vulnerabilities/7792 csrf xss
https://wpvulndb.com/vulnerabilities/7935 rce
https://wpvulndb.com/vulnerabilities/7816 sqli
https://wpvulndb.com/vulnerabilities/7844 afd
https://wpvulndb.com/vulnerabilities/7864 su
https://wpvulndb.com/vulnerabilities/7796 xss
https://wpvulndb.com/vulnerabilities/7893 fu
https://wpvulndb.com/vulnerabilities/7812 xss csrf

プラグインフォルダを直接呼び出しているパターン
複数の SQL コマンドが組み合わされている場合
wp-admin/{upload|plugins|options-general}.php?page=...
page 以外にパラメータがセットされている場合
https://core.trac.wordpress.org/browser/tags/4.2.1/src/wp-includes/pluggable.php#L1165
wp_redirect にはフィルタ・フックがあるので、nonce を注ぎ足すことは可能

{% highlight php startinline linenos %}
{% endhighlight %}

<!-- html+php, css+php, js+php -->
```html
```

<!-- success, info, warning, danger -->
<div class="alert alert-info" role="alert">
</div>

[![title]({{ "/img/2015-xx/sample.png" | prepend: site.baseurl }}
  "title"
)][link]

<!-- http://www.emoji-cheat-sheet.com/ -->
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
