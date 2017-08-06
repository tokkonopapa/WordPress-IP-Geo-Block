---
layout: post
title:  "Why so many WordPress plugins vulnerable?"
date:   2015-04-18 00:00:00
categories: article
---

[![WordPress Vulnerability Statistics]({{ '/img/2015-04/vurlerability-statistics.png' | prepend: site.baseurl }}
  "WordPress Vulnerability Statistics by WPScan Vulnerability Database"
) <small>Source: &copy; The WPScan Team</small>][WPScan]

The above graph shows recent statistics of WordPress vulnerability from 
[WPScan Vulnerability Database][WPScan] summarized by [Sucuri][Sucuri] which 
is a world wide security company especially famous for analyzing vulnerability 
in WordPress.

Why so many vulnerabilities are there in WP plugins?

After reading the [Sucuri Blog][Sucuri-Blog] deeply and widely, I came to 
the conclusion that there are some kind of disuse and misuse of WordPress 
core functions.

I'd like to verify each vulnerability in this point of view.

<!--more-->

### XSS ###

Unfortunately, <abbr title="cross site scripting">XSS</abbr> is very popular 
in WordPress plugins. In many cases, this occur with insufficient validation 
of untrusted data which comes from the outside (including from the DB) and 
lack of an escape just before responding to the user agent. The later is a 
fundamental countermeasure.

I made a corresponding table between "[XSS Prevention Cheat Sheet][OWASP-XSS]" 
in OWASP and "[Data Validation][Data-Validation]" in Codex.

<div class="table-responsive">
  <table class="table">
    <thead>
      <tr>
        <th>Context</th>
        <th>Code Sample</th>
        <th>Defense</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>HTML body</td>
        <td><samp>&lt;div&gt;<code>DATA</code>&lt;/div&gt;</samp></td>
        <td><code>esc_html()</code></td>
      </tr>
      <tr>
        <td>HTML attributes</td>
        <td><samp>&lt;input&hellip;value="<code>DATA</code>"&gt;</samp></td>
        <td><code>esc_attr()</code>, <code>sanitize_text_field()</code></td>
      </tr>
      <tr>
        <td>GET parameter</td>
        <td><samp>&lt;a href="&hellip;?value=<code>DATA</code>"&gt;&hellip;&lt;/a&gt;</samp></td>
        <td><code>esc_url()</code></td>
      </tr>
      <tr>
        <td>SRC/HREF attribute</td>
        <td><samp>&lt;iframe src="<code>DATA</code>" /&gt;</samp></td>
        <td><code>esc_url()</code></td>
      </tr>
      <tr>
        <td>JavaScript Variable</td>
        <td><samp>&lt;script&gt;foo('<code>DATA</code>');&lt;/script&gt;</samp></td>
        <td><code>esc_js()</code></td>
      </tr>
      <tr>
        <td>CSS Value</td>
        <td><samp>&lt;div style="width:<code>DATA</code>;"&gt;&hellip;&lt;/div&gt;</samp></td>
        <td>N/A</td>
      </tr>
    </tbody>
  </table>
</div>

You already know about this very well?

Sure, in case of [Blubrry PowerPress <= 6.0][XSS-PowerPress], XSS had already 
taken into account. The following statistics shows before and after fixing XSS.

<div class="table-responsive text-center">
  <table class="table">
    <thead>
      <tr>
        <th>&nbsp;</th>
        <th>Files</th>
        <th>Lines</th>
        <th><code>htmlspecialchars()</code></th>
        <th><code>esc_html()</code></th>
        <th><code>esc_attr()</code></th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>before</td>
        <td>42</td>
        <td>36280</td>
        <td>274</td>
        <td>82</td>
        <td>153</td>
      </tr>
      <tr>
        <td>after</td>
        <td>33</td>
        <td>22807</td>
        <td>160</td>
        <td>53</td>
        <td>131</td>
      </tr>
    </tbody>
  </table>
</div>

In fact, those paches could be seen every where in their codes. Those were 
hard to track before and after. More over, `htmlspecialchars()` and 
`esc_html()`were mixed and used.

Speaking about `esc_html()`, the third parameter to `htmlspecialchars()` is 
specified more strictly than their codes. I could hardly say that's OK or not, 
but using core functions is always OK.

I think this issue is caused by disuse and misuse of WordPress core functions.

Also it's important to design codes to separate "Validating Input" in "Model" 
and "Escaping Output" in "View". I'm not saying about MVC here. But codex says 
to use 
"[Validating Sanitizing and Escaping User Data][Sanitizing-Escaping]" 
along the context. So every developer should design the context at first.

### SQL Injection ###

On August 2014, Sucuri reported about 
[SQLi in Custom Contact Forms][Custom-Contact-Forms].

The following snippet from CCF (<= 5.1.0.3) dumps the set of SQL queries and 
downloads the previous one.

{% highlight php %}
<?php
if (!is_admin()) { /* is front */
    ...
    $custom_contact_front = new CustomContactFormsFront();
    ...
} else { /* is admin */
    ...
    $custom_contact_admin = new CustomContactFormsAdmin();
    ...
    add_action('init', array($custom_contact_admin, 'adminInit'), 1);
}

function adminInit() {
    $this->downloadExportFile();
    $this->downloadCSVExportFile();
    $this->runImport();
}
?>
{% endhighlight %}

The function `adminInit()` was no doubt for the administrators. But this 
snippet had at least next five issues:

1. Validate user role by `is_admin()`.
2. Lack of consideration of unexpected access route.
3. Export and import without validating user privilege.
4. Export raw SQL to the client.
5. Import raw SQL from the client.

As a result, an attacker could easyly know the DB prefix defined in the 
`wp-config.php` with a certain attack vector and could inject some malicious 
SQL to exploit the site.

[![Vulnerability of Custom Contact Form]({{ '/img/2015-04/custom-contact-form.png' | prepend: site.baseurl }}
  "Vulnerability of Custom Contact Form"
)][Custom-Contact-Forms]

This was caused by misuse of `is_admin()` which is always `true` when someone 
access the admin area even without authentication.

To avoid this vulnerability, the developer must follow the 
[SQL Injection Prevention Cheat Sheet][OWASP-SQL] like below:

1. Validate user privilege by `current_user_can()` with `manage_options`.
2. Validate nonce to limit the access route.
3. Export and import validated and parameterized queries.
4. Use stored procedures.
5. Escape all user supplied input.

The action hook `admin_init` may cause similar misuse. For example, Sucuri 
reported [RFI in MailPoet Plugin][MailPoet] on July 1 2014, and
[RCE in Platform theme][Platform-theme] on Jan 21 2015.

### Privilege Escalation ###

On February 3 2015, Sucuri disclosed 
[PE in UpdraftPlus plugin <= 1.9.50][UpdraftPlus].

Suppose a registered user hit a button on the dashboard to do something.

{% highlight php %}
<form action="<?php echo admin_url( 'admin.php' ); ?>">
    <?php wp_nonce_field( 'foo-secret-nonce' ); ?>
    <input type="hidden" name="action" value="foo" />
    <input type="submit" value="Something to do" />
</form>
{% endhighlight %}

When `wp-admin/?action=foo` is requested, the function `foo_handler` will be 
triggered via the action hook `admin_action_foo` by following code:

{% highlight php %}
<?php add_action( 'admin_action_foo', 'foo_handler' ); ?>
{% endhighlight %}

If the `foo-secret-nonce` is also used in a function for an administrator to 
do an important job and only `check_admin_referer('foo-secret-nonce')` is 
examined without validating user privilege, a PE will happen.

Exactly the same vulnerability in WPtouch (<= 3.4.2) was also 
[disclosed on July 14, 2014][WPtouch].

This is caused by disuse of WordPress nonce.

### CSRF ###

A cause of <abbr title="Cross Site Request Forgeries">CSRF</abbr> is simple.

For example, if you click "Save Changes" on the admin screen, the page 
transition happens after saving the setting data into the DB.
In a sequence of these process, if no nonce is on the page before transition 
or no validation of nonce before saving data, CSRF immediately occurs.

In this case, only validation of user authentication or privilege at saving 
process is not enough to prevent this vulnerability because an administrator 
potencially click a malicious link with own authorized cookie.

Such disuse of nonce 
[leads many plugins to CSRF](https://wpvulndb.com/search?text=&vuln_type=3).

### File Inclusion ###

<abbr title="File Inclusion">FI</abbr> (or Arbitrary File Download) is a 
vulnerability that occurs by giving the user supplied input to some file 
system functions such as `file_get_contents()`without proper validation.

On September 2014, 
[a vulnerability of Slider Revolution][Slider-Revolution]
became a big topic. By requesting a certain functionality for an administrator, 
an attacker could download any files via a request to `admin-ajax.php` 
like this:

{% highlight html %}
http://example.com/wp-admin/admin-ajax.php?action=show-me&file=../wp-config.php
{% endhighlight %}

This request was handled with:

1. No validation of user privilege.
2. No validation of nonce.
3. No validation of given input.

As a result, the attacker could easily download `wp-config.php` without knowing
the user name and password.

[![Vulnerability of Slider Revolution]({{ '/img/2015-04/revslider.png' | prepend: site.baseurl }}
  "Vulnerability of Slider Revolution"
)][Slider-Revolution]

We can find this kind of vulnerability in the old version of 
[HD FLV Player][HD-FLV-Player]. In this plugin, `download.php` was called 
directly regardless of WordPress context where we should follow its event 
driven programming style to use appropriate functions for validating a nonce 
and user privilege.

### Conclusion ###

I've found a lot of disuse and misuse of WordPress core functions in 
[Sucuri Blog][Sucuri-Blog]
and conclude the followings for developing WordPress plugins and themes 
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f31d.png)
</span>.

* Input has been contaminated.
* The DB has also been contaminated.
* Secret information may leak.
* Administrator privileges also may leak and be stolen.
* Attacks may come from unexpected route.
* Attacker actually doesn't access to the page.

And I quite agree with [James Golovich][James-Golovich]:

> If you have the WordPress tools available to you then you should use them.
> Something like current_user_can() and a nonce should always be used.

[WPScan]:               https://wpvulndb.com/ "WPScan Vulnerability Database"
[Sucuri]:               https://sucuri.net/ "Sucuri Security - Website Protection, Malware Removal, and Blacklist Prevention"
[Sucuri-Blog]:          https://blog.sucuri.net/ "Sucuri Blog"
[Custom-Contact-Forms]: https://blog.sucuri.net/2014/08/database-takeover-in-custom-contact-forms.html "Critical Vulnerability Disclosed on WordPress Custom Contact Forms Plugin | Sucuri Blog"
[MailPoet]:             https://blog.sucuri.net/2014/07/remote-file-upload-vulnerability-on-mailpoet-wysija-newsletters.html "WordPress Security Vuln in MailPoet Plugin | Sucuri Blog"
[Platform-theme]:       https://blog.sucuri.net/2015/01/security-advisory-vulnerabilities-in-pagelinesplatform-theme-for-wordpress.html "Security Advisory - Vulnerabilities in Pagelines/Platform theme for WordPress - Public Preview | Sucuri Blog"
[UpdraftPlus]:          https://blog.sucuri.net/2015/02/advisory-dangerous-nonce-leak-in-updraftplus.html "Advisory - Dangerous &quot;nonce&quot; leak in UpdraftPlus | Sucuri Blog"
[WPtouch]:              https://blog.sucuri.net/2014/07/disclosure-insecure-nonce-generation-in-wptouch.html "Disclosure: Insecure Nonce Generation in WPtouch | Sucuri Blog"
[Slider-Revolution]:    https://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html "Slider Revolution Plugin Critical Vulnerability Being Exploited | Sucuri Blog"
[HD-FLV-Player]:        https://blog.sucuri.net/2014/12/critical-vulnerability-in-joomla-hd-flv-player-plugin.html "Critical vulnerability affecting HD FLV Player | Sucuri Blog"
[OWASP-SQL]:            https://www.owasp.org/index.php/SQL_Injection_Prevention_Cheat_Sheet#Introduction "SQL Injection Prevention Cheat Sheet"
[OWASP-XSS]:            https://www.owasp.org/index.php/XSS_%28Cross_Site_Scripting%29_Prevention_Cheat_Sheet#XSS_Prevention_Rules_Summary "XSS (Cross Site Scripting) Prevention Cheat Sheet - OWASP"
[XSS-PowerPress]:       https://wpvulndb.com/vulnerabilities/7773 "Blubrry PowerPress &lt;= 6.0 - Cross-Site Scripting (XSS)"
[Data-Validation]:      http://codex.wordpress.org/Data_Validation "Data Validation « WordPress Codex"
[Sanitizing-Escaping]:  http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data "Validating Sanitizing and Escaping User Data « WordPress Codex"
[James-Golovich]:       http://www.pritect.net/blog/wp-ultimate-csv-importer-3-7-1-critical-vulnerability "wp ultimate csv importer"
[IP-Geo-Block]:         https://wordpress.org/plugins/ip-geo-block/ "WordPress &#8250; IP Geo Block « WordPress Plugins"
