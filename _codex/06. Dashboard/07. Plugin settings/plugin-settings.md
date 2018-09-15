---
layout: page
language: en
category: codex
section: Dashboard
title: Plugin settings
---

This section collects the operation setting of this plugin and the function to 
acquire diagnostic information.

<!--more-->

### Emergency login link ###

In case you can not access to the login form, you can generate a special link 
with a secret key in advance to reestablish your authority. The secret key 
remains in effect until it is deleted.

![Emergency login link]({{ '/img/2018-09/EmergencyLoginLink.png' | prepend: site.baseurl }}
 "Emergency login link"
)

Since this plugin doesn't hold the key itself, please keep it in the "favorite"
or "bookmark" of your browser, and take careful not to disclose it to  others.

While the emergency function described in "**[What should I do when I&apos;m 
locked out?][EmergentFunc]**" force to invalidates the specific validation,
this link can provide the reason of your login fail as follows:

- **In case your country code or IP address is not included in whitelist or 
included in blacklist**  
  
  ![Blocking reason 1]({{ '/img/2018-09/LoginValidation1.png' | prepend: site.baseurl }}
   "Blocking reason 1"
  )
  
  When you find the above message on dashboard, please visit **[Validation rule
  settings][ValidateRule]** section and check your rules.

- **In case the number of login attempts with your IP address exceeds 
the limit**  
  
  ![Blocking reason 2]({{ '/img/2018-09/LoginValidation2.png' | prepend: site.baseurl }}
   "Blocking reason 2"
  )
  
  When you find the above message on dashboard, please visit "**Statistics**" 
  tab and go to "**Statistics in IP address cache**", then remove your IP 
  address from the cache entries.

### Export / Import settings ###

You can export the setting of this plugin to a text file in [JSON][JSON] 
format, and import from the file.

When "**Import**" is done, items different from the current setting are marked 
with <code style="color:red">*</code>{% comment %}*{% endcomment %}.
Since these are only changing the UI in each section, don't forget to execute 
"**Save Changes**" to reflect the setting.

### Import pre-defined settings ###

It imports predefined setting values of this plugin. In particular, "**Best for
Back-end**" makes the ability and performance of this plugin the best.

### Diagnostic information ###

It shows useful information for diagnosis and debugging when some issue occurs.

In particular, if there is a conflict with other plugin, please push 
"**Show information**". It extracts the blocked request by your IP address from
the log and add it at the end of the information like follows:

{% highlight text %}
- 2018-09-15 09:14:51 wp-zep  GET:/wp-admin/admin-ajax.php?action=ipgb-tester-admin-ajax(HTTP_REFERER=http://localhost/,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:11:12 limited GET:/wp-login.php(HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:13 limited GET:/(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:09 limited POST:/wp-login.php(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:10:02 failed  POST:/wp-login.php(HTTP_REFERER=http://localhost/wp-login.php,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 07:09:19 blocked GET:/wp-login.php(HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
- 2018-09-15 06:22:52 wp-zep  GET:/wp-content/plugins/ip-geo-block/samples.php?file=../../../wp-config.php(HTTP_REFERER=http://localhost/blog/2018/07/29/hello-world/,HTTP_DNT=1,HTTP_UPGRADE_INSECURE_REQUESTS=1,HTTP_X_FORWARDED_FOR=192.168.0.***)
{% endhighlight %}

Those information can help greatly to solve the issues. Please provide them by 
copy and paste when reporting something at [support forum][SupportForum]
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f4cc.png)
</span>.

### See also ###

- [The best practice of target settings][BestPractice]
- [Prevent exposure of wp-config.php][ExposeWPConf]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[SupportForum]: https://wordpress.org/support/plugin/ip-geo-block "View: [IP Geo Block] Support &#124; WordPress.org"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html' | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[EmergentFunc]: {{ '/codex/what-should-i-do-when-i-m-locked-out.html'  | prepend: site.baseurl }} "What should I do when I&apos;m locked out? | IP Geo Block"
[ValidateRule]: {{ '/codex/validation-rule-settings.html'              | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[ExposeWPConf]: {{ '/article/exposure-of-wp-config-php.html'           | prepend: site.baseurl }} "Prevent exposure of wp-config.php | IP Geo Block"
[JSON]:         https://en.wikipedia.org/wiki/JSON "JSON - Wikipedia"
