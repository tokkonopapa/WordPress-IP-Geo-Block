---
layout: page
category: codex
section: FAQ
title: Why &ldquo;Sorry, your request cannot be accepted&rdquo; ?
excerpt: Why &ldquo;Sorry, your request cannot be accepted&rdquo; ?
---

Even if you encounter blocking, please feel relax. There're some ways to 
resolve it.

### When and why am I blocked? ###

Well, actually there're several rules to validate your requests in this plugin.
Each of them is very simple but combination of them is very powerful to protect
your sites. But sometimes, those are too strong for some plugins and themes to 
pass their requests.

One thing you should know is that **all activities by an administrator are not 
always permitted** in this plugin in terms of preventing [CSRF][CSRF] and 
[SSRF][SSRF] that are usually combined with other vulnerability and attack like
[XSS][XSS], [SQLi][SQLi], [LFI][LFI] and so on.

### When you encounter blocking&hellip; ###

You will see the following window by default unless you setup a [human friendly
error page][FriendlyPage]:

![Blocking message]({{ '/img/2016-12/forbidden-message.png' | prepend: site.baseurl }}
 "Blocking message"
)

The "**Dashboard**" is a kind of **safety zone** protected by WordPress 
authentication system. None of important jobs would be executed there but just 
showing something useful about your site. So when you encounter the above 
message, following the link is always recommended unless you have something to 
keep before you leave the last page.

### How to resolve it? ###

#### Step 1: Check JavaScript errors ####

A JavaScript file named `authenticate.min.js` has a very important role for 
this plugin. For example, "[Referrer Suppressor for external link][RefSup]" 
is done by this script. But once a js error occurs, you might end in seeing 
"Sorry, your request cannot be accepted". So please check js errors in your 
browser at first.

[This codex document][JSErrors] is very helpful to examine this step.

#### Step 2: Try "Prevent Zero-day Exploit" ####

"Prevent Zero-day Exploit" which I named WP-ZEP is the most powerful feature 
in this plugin to protect your site against undisclosed vulnerability. It can 
also distinguish the origin of request by a logged in user from an attacker 
using a scecret key called [nonce][WPnonce] that should be known only by a 
logged in user.

![Prevent Zero-day Exploit]({{ '/img/2016-12/PreventZeroDayExploit.png' | prepend: site.baseurl }}
 "Prevent Zero-day Exploit"
)

The priority of this rule is the highest in this plugin. So please try 
to enable / disable this feature in order to tell this plugin "**The request 
is not from an attacker but from me!**".

#### Step 3: Find a blocking reason in logs ####

If the Step 2 can't resolve the issue, please find the blocked request and look
at the "**Result**". The following is an example of `/wp-admin/admin-ajax.php` 
blocked by "**Prevent Zero-day Exploit**" that is described as "**wp-zep**":

![Blocking reason in logs]({{ '/img/2016-12/LogsAdminAjax.png' | prepend: site.baseurl }}
 "Blocking reason in logs"
)

You can find the full list of "**Result**" at [this document][BlockReason] in 
codex. Then please go to the next step.

#### Step 4: Give a permission as exception ####

If you can't resolve the blocking issue up to the step 2, please try to give 
a permission to the concerned request as an exception.

##### - **Admin area** / **Admin ajax/post** - #####

In the case when a request related to `wp-admin` is blocked, you can give it 
permission via the custom filter hook [ip-geo-block-bypass-admins][BypassAdmin].

For example, if the request has a query `action=do-my-action` or 
`page=my-plugin-page`, then you can add a code snippet into your 
theme's `functions.php` or `/path/to/your/ip-geo-api/drop-in.php` 
(typically `/wp-content/ip-geo-api/drop-in.php`) as below:

{% highlight ruby %}
function my_bypass_admins( $queries ) {
    $whitelist = array(
        'do-my-action',
        'my-plugin-page',
    );
    return array_merge( $queries, $whitelist );
}
add_filter( 'ip-geo-block-bypass-admins', 'my_bypass_admins' );
{% endhighlight %}

<div class="alert alert-info">
	<strong>Note:</strong> You can add the above code into the
	<code>functions.php</code> in your theme when you set
	<code>"init" action hook</code> as <a href="/codex/validation-timing.html" title="Validation timing | IP Geo Block"><strong>Validation timing</strong></a>.
	But when you select <code>"mu-plugins" (ip-geo-block-mu.php)</code>,
	you should use <code>drop-in.php</code> because it's prior to
	<a href="https://codex.wordpress.org/Plugin_API/Action_Reference/after_setup_theme" title="Plugin API/Action Reference/after setup theme &laquo; WordPress Codex">after_setup_theme</a>.
</div>

##### - **Plugins area** / **Themes area** - #####

If the requested URL is directly pointed to the particular plugin or theme, 
you can resolve its blocking issue by making an exception of that plugin or 
theme.

![Exceptions]({{ '/img/2016-12/exceptions.png' | prepend: site.baseurl }}
 "Exceptions"
)

It's also performed by [ip-geo-block-bypass-plugins][BypassPlugin] and 
[ip-geo-block-bypass-themes][BypassTheme].

#### Step 5: Installation information ####

In case you can't resove your blocking issue up to this step, I should help 
you to find a solution at [support forum][SupportForum]. Before submitting your
issue to the forum, I expect you to get your "**Installation information**" at 
"**Plugin settings**" section.

![Installation information]({{ '/img/2016-12/install-info.png' | prepend: site.baseurl }}
 "Installation information"
)

Please copy and submit them. Those are very helpful to know what happens to 
your site.

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[CSRF]:         https://en.wikipedia.org/wiki/Cross-site_request_forgery "Cross-site request forgery - Wikipedia"
[SSRF]:         http://cwe.mitre.org/data/definitions/918.html "CWE - CWE-918: Server-Side Request Forgery (SSRF) (2.9)"
[XSS]:          https://www.owasp.org/index.php/Cross-site_Scripting_(XSS) "Cross-site Scripting (XSS) - OWASP"
[SQLi]:         https://www.owasp.org/index.php/SQL_Injection "SQL Injection - OWASP"
[LFI]:          https://en.wikipedia.org/wiki/File_inclusion_vulnerability "File inclusion vulnerability - Wikipedia"
[RefSup]:       {{ '/article/referer-suppressor.html' | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[JSErrors]:     https://codex.wordpress.org/Using_Your_Browser_to_Diagnose_JavaScript_Errors "Using Your Browser to Diagnose JavaScript Errors &laquo; WordPress Codex"
[WPnonce]:      https://codex.wordpress.org/WordPress_Nonces "WordPress Nonces &laquo; WordPress Codex"
[BypassAdmin]:  http://www.ipgeoblock.com/codex/ip-geo-block-bypass-admins.html "ip-geo-block-bypass-admins | IP Geo Block"
[BypassPlugin]: {{ '/codex/ip-geo-block-bypass-plugins.html' | prepend: site.baseurl }} "ip-geo-block-bypass-plugins | IP Geo Block"
[BypassTheme]:  {{ '/codex/ip-geo-block-bypass-themes.html'  | prepend: site.baseurl }} "ip-geo-block-bypass-themes | IP Geo Block"
[Timing]:       {{ '/codex/validation-timing.html' | prepend: site.baseurl }} "Validation timing | IP Geo Block"
[FriendlyPage]: {{ '/codex/customizing-the-response.html#human-friendly-error-page' | prepend: site.baseurl }} "Customizing the response | IP Geo Block"
[BlockReason]:  {{ '/codex/record-settings-and-logs.html#description-of-result'     | prepend: site.baseurl }} "Record settings and logs | IP Geo Block"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block/ "View: Plugin Support &laquo; WordPress.org Forums"
