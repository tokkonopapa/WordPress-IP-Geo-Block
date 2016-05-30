---
layout: post
title: "Call for testing 2.2.5 beta1"
date: 2016-05-15 00:00:00
categories: changelog
published: true
script: []
inline:
---

I've almost done with devoloping the next release 2.2.5 of [IP Geo Block]
[IP-Geo-Block]. Before releasing this version in public, I'd ask you to test 
and see if the [beta1][2.2.5Beta1] works for you. You can download it from 
[here][2.2.5Beta1-ZIP].

<!--more-->

### How to test ###

1. Download [zip archive][2.2.5Beta1-ZIP] and unzip it.
2. Deactivate your IP Geo Block.
3. Upload whole of `ip-geo-block` in the unzipped archive into your plugin's 
   directory on your server. It can be overwritten.
4. Activate IP Geo Block again.

Although I've made sure that it would not break anything in an obvious way, 
please keep in mind that this is a developing version. It might still contain 
undiscovered issues. When you find or notice something, please let me know 
about them in the comment of this post.

### What's new in 2.2.5 beta1 ###

Comparing changes between the last release 2.2.4.1 and 2.2.5 Beta1 is available
[here][2.2.5Beta1Diff].

#### Improvement: Clean uninstall for multisite ####

Before this release, clean uninstallation of this plugin would remove settings,
statistics and logs only for main site in multisite environment. Now you can 
remove those for all sites by just enabling "**Remove all settings at 
uninstallation**" at "**Plugin settings**" of main site, and execute 
"**Delete**" from the plugin dashboard.

#### Improvement: Analyzing requested URI ####

It's very important for IP Geo Block to apply the proper filter to validate 
the malicious requests. So analyzing a requested URI depending on its type is 
the core process of this plugin.

In the previous version 2.2.4, I tried to improve to perform this analysis 
well which result caused the [fatal error][FatalError] and broke the site.
The main reason of this trouble was the lack of testing patterns of server 
configurations.

In this release, I've tested the following patterns :

1. `http://example.com/` ... Top directory type of single and multisite
2. `http://example.com/sub/` ... Sub directory type of single and multisite
3. `http://domain.example.com/` ... Sub domain type of multisite
4. [`FORCE_SSL_ADMIN`][SSL_ADMIN] ... Admin dashboard over SSL

In the last version 2.2.4.1, `FORCE_SSL_ADMIN` did not work properly. But in 
this release, that issue might be fixed.

Additionally, [`parse_url()`][PARSE_URL] was discarded in analyzing requested 
URI to avoid unexpected behavior because the document says :

> This function is not meant to validate the given URL, it only breaks it up 
> into the above listed parts.
> ... 
> On seriously malformed URLs, `parse_url()` may return `FALSE`.

#### Buf fix: Avoid race condition on activation/upgrade ####

On activation or upgrade, IP Geo Block provides self recovery process that 
would add the valid country code into the whitelist when self lockout happens.
This would be done in background by WordPress cron system.

During the execution of this process, there was slight possibility that the 
cron would be kicked by the visitor. In this case, the country code derived 
from that vistor's ip address would be added with comma like `JP,US`.

Thanks to [BartTheMan][BartTheMan], this issue was completely fixed in this 
release <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span> .

#### Bug fix: Compatibility with other plugins ####

In the last version, the option "**Important files**" was defined as 
"`wp-config.php,passwd`". But actually, these words are used to detect 
malicious signatures in the requested query that works independently of 
"**Block by country**" and "[**Prevent Zero-day Exploit**][WP-ZEP]".

For example, the following [Local File Inclusion][LFI-OWASP] attack can be 
detected :

{% highlight text %}
http://example.com/wp-content/plugins/vulnerable/download.php?file=../../../etc/passwd
{% endhighlight %}

But this feature was not so clever to distinguish `WordfencePasswdAudit` 
from `passwd`. Thanks to [ac1643][AC1643], the compatibility issue with 
[Wordfence][WORDFENCE] + [AG Custom Admin][AGCustomAdmin] and [IP Geo Block]
[IP-Geo-Block] was reported at [support forum][ISSUE-AGC].

In this release, `wp-config.php` and `passwd` will be replaced to 
`/wp-config.php` and `/passwd` if those have no `/` at their top.

#### Trial new feature: Bad signatures in query ####

Now "**Important files**" has been renamed "**Bad signatures in query**".

Well then, how should this plugin validate malicious signatures against such 
as SQLi, XSS.

The bast way to achieve this is to equip a kind of parser to analyze command 
and syntax of MySQL and JavaScript. But implementation in PHP is not suitable 
because of its cost. So I've implemented "**Sum of weighted scores**" with 
only a few lines of additional code.

Let's think about SQLi. Usually, several sql commands are combined as below :

{% highlight sql %}
CREATE USER 'user'@'attacker.com' IDENTIFIED BY 'password';
{% endhighlight %}

or

{% highlight sql %}
SELECT LOAD_FILE(0x2F6574632F706173737764); # /etc/passwd
{% endhighlight %}

If `SELECT` and `LOAD_FILE` are weighted by 0.5 as their score, the total 
score becomes 1.0. As a result, it reaches at the threshold of "malicious" 
(actually its value is 0.99). Now you can define a signature as 
"***signature*** : ***weight***" like following :

![Bad signature in query]({{ '/img/2016-05/BadSignatures.png' | prepend: site.baseurl }}
 "Bad signature in query"
)

My recommended definition can be available from "**Import best practice**" 
button. But I can't decide its best settings. For example, the following valid 
request would be blocked because it includes `select` and `where` in its query.

{% highlight text %}
http://example.com/wp-admin/admin-ajax.php?action=my_selection&where=my_page
{% endhighlight %}

So I should continue to improve the parser and words from now. Note that the 
current limitation is that you can't include ***space*** inside each signature.

#### New feature: Exceptions in plugins/themes area ####

Some of plugins and themes need to access their own php files directly on the 
public facing pages. For example, [WP-SpamShield Anti-Spam][WpSpamShield] 
requires `wp-content/plugins/wp-spamshield/js/jscripts.php` on the pages.

Previously, you need to add some snippets into your `functions.php` to specify
the [plugin][BypassPlugin] or [theme][BypassTheme] that you want to exclude 
from the validation target.

Now you can specify them at "**Plugins area**" and "**Themes area**".

![Exceptions]({{ '/img/2016-05/Exceptions.png' | prepend: site.baseurl }}
 "Exceptions"
)

Note that you should still use [`ip-geo-block-bypass-admin`][BypassAdmin] to 
bypass a certain action for `admin-ajax.php` and `admin-post.php`.

### Hope for your cooperation ###

It's nothing special but I'm hoping for your ordinary job.

For me and [IP Geo Block][IP-Geo-Block], the next formal release 2.2.5 is a 
very important milestone, because I'd like to give it the potential ability 
for a new protection functionality in the future release which may be 2.3.0 
and later.

So I would deeply appreciate for your kind cooperation <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f604.png)
</span> .

[IP-Geo-Block]:   https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[2.2.5Beta1]:     https://github.com/tokkonopapa/WordPress-IP-Geo-Block/tree/2.2.5 "GitHub - tokkonopapa/WordPress-IP-Geo-Block at 2.2.5"
[2.2.5Beta1-ZIP]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/releases/tag/v2.2.5 "Release v2.2.5 - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
[2.2.5Beta1Diff]: https://github.com/tokkonopapa/WordPress-IP-Geo-Block/compare/2.2.4.1...2.2.5 "Comparing 2.2.4.1...2.2.5 - tokkonopapa/WordPress-IP-Geo-Block - GitHub"
[FatalError]:     {{ '/article/confession-on-224.html' | prepend: site.baseurl }} "Confession of the problem in 2.2.4"
[SSL_ADMIN]:      https://codex.wordpress.org/Administration_Over_SSL "Administration Over SSL « WordPress Codex"
[BartTheMan]:     https://wordpress.org/support/topic/gb-added-to-whitelist "WordPress › Support » GB added to whitelist"
[LFI-OWASP]:      https://www.owasp.org/index.php/Testing_for_Local_File_Inclusion "Testing for Local File Inclusion - OWASP"
[PARSE_URL]:      http://php.net/manual/en/function.parse-url.php "PHP: parse_url - Manual"
[AC1643]:         https://wordpress.org/support/profile/ac1643 "WordPress › Support » ac1643"
[WORDFENCE]:      https://wordpress.org/plugins/wordfence/ "Wordfence Security - WordPress Plugins"
[ISSUE-AGC]:      https://wordpress.org/support/topic/compatibility-with-ag-custom-admin "WordPress › Support » Compatibility with AG Custom Admin"
[AGCustomAdmin]:  https://wordpress.org/plugins/ag-custom-admin/ "AG Custom Admin - WordPress Plugins"
[WpSpamShield]:   https://wordpress.org/plugins/wp-spamshield/ "WP-SpamShield Anti-Spam - WordPress Plugins"
[BypassPlugin]:   http://www.ipgeoblock.com/codex/ip-geo-block-bypass-plugins.html "ip-geo-block-bypass-plugins | IP Geo Block"
[BypassTheme]:    http://www.ipgeoblock.com/codex/ip-geo-block-bypass-themes.html "ip-geo-block-bypass-themes | IP Geo Block"
[BypassAdmin]:    http://www.ipgeoblock.com/codex/ip-geo-block-bypass-admins.html "ip-geo-block-bypass-admins | IP Geo Block"
[WP-ZEP]:         {{ '/article/how-wpzep-works.html' | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack?"
