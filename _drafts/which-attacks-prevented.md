---
layout: post
title:  "Which attacks can WP-ZEP prevent?"
date:   2015-05-01 00:00:00
categories: article
published: true
script: [/js/jquery-1.11.2.min.js, /js/tablesort.min.js]
inline: <script>
  var table = document.getElementById('my-table');
  var sort = new Tablesort(table);
  var sortby = function (id) {
    var cols = [
      'attack-vec',
      'geolocation',
      'wp-zep',
    ];
    for (var i = 0; i < cols.length; i++) {
      $(table).find('#' + cols[i]).removeClass('sort-default');
    }
    $(table).find('#' + id).addClass('sort-default').trigger('click');
  }
  </script>
---

On [WPScan Vulnerability Database][wpvulndb] maintained by [Sucuri][Sucuri],
we can find many new plugins and themes every month. Of course [WP-ZEP][WP-ZEP]
is not God Almighty against these. Then you may wonder about:

- Which attack can WP-ZEP prevent?
- How many attacks can WP-ZEP prevent?

I'm with you!!

So I picked up latest 50 vulnerabilities from [WPScan DB][wpvulndb], and dig 
into the each attack vector one by one to investigate which can be prevented 
or not by WP-ZEP.

<!--more-->

### Investigation ###

To find out the prevention ability of WP-ZEP, I only picked up the vulnerable 
plugins which I can download by free. Then I got code differences between 
before and after fixing issues to start reading the code deeply. In many cases,
exploitation reports in something like [Packet Storm][Packet-Storm] gave me 
a lot of help, but in some cases I had to install these plugins into my PC to 
confirm the attack vectors.

Each vulnerability has its own attack vectors. Some of them are classified in 
a direct attack onto the plugin files, and some of them are classified in an 
indirect attack via WordPress core files. So at first I must make clarify the 
definition of "Attack Vector" itself. My definition is:

```text
Attack Vector = Type x Path
```

where:

- **Type**: The type of vulnerability that an attacker can abuse. For example, 
  XSS, SQLI, LFI and so on. Also it includes some certain parameters which 
  are generally called "signature".
- **Path**: The path to the entrance into WordPress where an attacker can 
  deliver a payload or malicious code.

Father more, I categorized the "**Path**" into severals patterns. Here are the 
short descriptions of abbreviation in the later part of this article.

| Abbreviation | Description           |
|:-------------|:----------------------|
| PD           | **P**lugin **D**irect |
| FE           | **F**ront **E**nd     |
| AX           | **A**ja**x** / Post   |
| WA           | **w**p-**a**dmin      |

Then I examined the prevention ability of 
  [IP Geo Block in 2.1.0][IP-Geo-Block] 
based on the type of validation which are:

1. validate by **Geo**location
2. validate by WP-**ZEP**

For example, 2. is available on "Validation Settings" as follows:

![Validation Settings in IP Geo Block in 2.1.0](
  {{ "/img/2015-05/validation-settings.png" | prepend: site.baseurl }}
  "Validation Settings in IP Geo Block in 2.1.0"
)

Well then, let's take a look at the results:

<div class="table-responsive">
  <a href="https://wpvulndb.com/plugins" title="WordPress Plugin Vulnerabilities"><small>Source: &copy; The WPScan Team</small></a>
  <table id="my-table" class="table">
    <thead>
      <tr>
        <th>Vulnerability</th>
        <th class="no-sort">Version</th>
        <th><abbr title="Type of vulnerability">Type</abbr></th>
        <th id="attack-vec"><abbr title="Attack Vector">Path</abbr></th>
        <th id="geolocation"><abbr title="validate by Geolocation">Geo</abbr></th>
        <th id="wp-zep"><abbr title="validate by WP-ZEP">ZEP</abbr></th>
      </tr>
    </thead>
    <tbody>
      <tr><!-- 1. /wp-content/plugins/wp-business-intelligence-lite/view.php?t=... | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7879" title="WP Business Intelligence Lite &lt;= 1.6.1 - SQL Injection">WP Business Intelligence Lite</a></td>
        <td>&lt;= 1.6.1</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="http://packetstormsecurity.com/files/131228/">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 2. /?account=1&pwd=1&uid=1&setFirst=0... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7873" title="Ptengine &lt;= 1.0.1 - Reflected Cross-Site Scripting (XSS)">Ptengine</a></td>
        <td>&lt;= 1.0.1</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Front End"><a href="https://wpvulndb.com/vulnerabilities/7873">FE</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 3. ajax/post -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7821" title="EZ Portfolio &lt;= 1.0.1 - Multiple Cross-Site Scripting (XSS) ">EZ Portfolio</a></td>
        <td>&lt;= 1.0.1</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post">AX</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 4. /wp-admin/admin.php?page=wonderplugin_audio_show_items... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7798" title="WonderPlugin Audio Player 2.0 Blind SQL Injection and XSS">WonderPlugin Audio Player</a></td>
        <td>&lt;= 2.0</td>
        <td><abbr title="SQL Injection">SQLI</abbr>, <abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36086/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 5. /wp-content/plugins/aspose-cloud-ebook-generator/aspose_posts_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7866" title="Aspose Cloud eBook Generator - File Download">Aspose Cloud eBook Generator</a></td>
        <td>&lt;= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131040/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 6. /wp-content/plugins/wpshop/includes/ajax.php?elementCode=ajaxUpload... | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7830" title="Wpshop - eCommerce &lt;= 1.3.9.5 - Arbitrary File Upload">WPshop - eCommerce</a></td>
        <td>&lt;= 1.3.9.5</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="https://research.g0blin.co.uk/g0blin-00036/">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 7. lack of check_admin_referer() -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7813" title="WPBook &lt;= 2.7 - Cross-Site Request Forgery (CSRF)">WPBook</a></td>
        <td>&lt;= 2.7</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin">WA</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 8. /wp-admin/options-general.php?page=wp-vipergb... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7817" title="WP-ViperGB 1.3.10 - XSS Weakness and CSRF">WP-ViperGB</a></td>
        <td>&lt;= 1.3.10</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr>, <abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/129501">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 9. /wp-admin/admin-ajax.php?action=ajax_survey -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7794" title="WordPress Survey & Poll &lt;= 1.1.7 - Blind SQL Injection">WordPress Survey & Poll</a></td>
        <td>&lt;= 1.1.7</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/130381/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 10. /wp-admin/upload.php?s=test&page=wp-media-cleaner... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7814" title="WP Media Cleaner &lt;= 2.2.6 - Cross-Site Scripting (XSS)">WP Media Cleaner</a></td>
        <td>&lt;= 2.2.6</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130576/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 11. /wp-admin/admin.php?page=wss-images... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7888" title="WP Easy Slideshow &lt;= 1.0.3 - Multiple Cross-Site Request Forgery (CSRF)">WP Easy Slideshow</a></td>
        <td>&lt;= 1.0.3</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin"><a href="https://www.exploit-db.com/exploits/36612/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 12. /wp-admin/admin-ajax.php?nm_webcontact_upload_file... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7896" title="N-Media Website Contact Form with File Upload &lt;= 1.3.4 - Arbitrary File Upload">N-Media Website Contact Form</a></td>
        <td>&lt;= 1.3.4</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/131413/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 13. /?page_id=2&artistletter=G' UNION ALL SELECT... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7901" title="Tune Library &lt;= 1.5.4 - SQL Injection">Tune Library</a></td>
        <td>&lt;= 1.5.4</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Front End"><a href="http://packetstormsecurity.com/files/131558/">FE</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 14. /wp-admin/options-general.php?page=redirection-page... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7791" title="Redirection Page &lt;= 1.2 - CSRF/XSS">Redirection Page</a></td>
        <td>&lt;= 1.2</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130314/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 15. /wp-content/plugins/php-event-calendar/server/classes/uploadify.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7884" title="PHP Event Calendar &lt;= 1.5 - Arbitrary File Upload">PHP Event Calendar</a></td>
        <td>&lt;= 1.5</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131277/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 16. /?wishdonorname=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7937" title="My Wish List - Multiple Parameter XSS">My Wish List</a></td>
        <td>&lt;= 1.4.1</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Front End">FE</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 17. /wp-admin/options-general.php?page=mobile-domain... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7792" title="Mobile Domain &lt;= 1.5.2 - CSRF/XSS">Mobile Domain</a></td>
        <td>&lt;= 1.5.2</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130316/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 18. /wp-content/plugins/mailchimp-subscribe-sm/data.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7935" title="MailChimp Subscribe Form &lt;= 1.1 - Email Field Remote PHP Code Execution">MailChimp Subscribe Form</a></td>
        <td>&lt;= 1.1</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://plugins.svn.wordpress.org/mailchimp-subscribe-sm/tags/1.1/data.php">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 19. /wp-admin/admin.php?page=wp-IPBLC... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7816" title="IP Blacklist Cloud &lt;= 3.4 - SQL Injection">IP Blacklist Cloud</a></td>
        <td>&lt;= 3.4</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="wp-admin">WA</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 20. /?action=importCSVIPCloud... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7844" title="IP Blacklist Cloud &lt;= 3.42 - Arbitrary File Disclosure">IP Blacklist Cloud</a></td>
        <td>&lt;= 3.42</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Front End"><a href="https://research.g0blin.co.uk/g0blin-00037/">FE</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 21. /wp-content/plugins/inboundio-marketing/admin/partials/csv_uploader.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7864" title="InBoundio Marketing Plugin &lt;= 2.0.3 - Shell Upload">InBoundio Marketing</a></td>
        <td>&lt;= 2.0.3</td>
        <td><abbr title="Remote File Upload">RFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/130957/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 22. /wp-admin/plugins.php?page=image_metadata_cruncher-options... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7796" title="Image Metadata Cruncher - Multiple XSS">Image Metadata Cruncher</a></td>
        <td>&lt;= 1.8</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://www.securityfocus.com/archive/1/archive/1/534718/100/0/threaded">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 23. /wp-admin/options-general.php?page=thisismyurl_csj.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7812" title="CrossSlide jQuery Plugin &lt;= 2.0.5 - Stored XSS &amp; CSRF">CrossSlide jQuery</a></td>
        <td>&lt;= 2.0.5</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130313/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 24. /wp-content/plugins/Wordpress/Aaspose-pdf-exporter/aspose_pdf_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7876" title="Aspose PDF Exporter - Arbitrary File Download">Aspose PDF Exporter</a></td>
        <td>&lt; 2.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131161/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 25. /wp-content/plugins/aspose-importer-exporter/aspose_import_export_download?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7877" title="Aspose Importer and Exporter 1.0 - Arbitrary File Download">Aspose Importer &amp; Exporter</a></td>
        <td>&lt;= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131162/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 26. /wp-content/plugins/aspose-doc-exporter/aspose_doc_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7869" title="Aspose DOC Exporter 1.0 - Arbitrary File Download">Aspose DOC Exporter</a></td>
        <td>&lt;= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131167/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 27. /wp-content/plugins/wp-ultimate-csv-importer/modules/export/templates/export.php | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7778" title="WP Ultimate CSV Importer &lt;= 3.6.74 - Database Table Export">WP Ultimate CSV Importer</a></td>
        <td>&lt;= 3.6.74</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="https://research.g0blin.co.uk/g0blin-00025/">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 28. /wp-content/plugins/wp-ultimate-csv-importer/templates/readfile.php?file_name=... | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7949" title="WP Ultimate CSV Importer &lt;= 3.7.1 - Directory Traversal">WP Ultimate CSV Importer</a></td>
        <td>&lt;= 3.7.1</td>
        <td><abbr title="Directory Traversal">DT</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="http://www.pritect.net/blog/wp-ultimate-csv-importer-3-7-1-critical-vulnerability">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 29. /wp-content/themes/mTheme-Unus/css/css.php?files=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7898" title="WP Mobile Edition &lt;= 2.7 - Remote File Disclosure">WP Mobile Edition</a></td>
        <td>&lt;= 2.2.7</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
       <td><abbr title="Plugin Direct"><a href="https://www.exploit-db.com/exploits/36733/">PD</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 30. /wp-admin/admin-ajax.php?page=pmxi-admin-settings&action=upload... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7809" title="WP All Import &lt;= 3.2.3 - RCE">WP All Import</a></td>
        <td>&lt;= 3.2.3</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/130596/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 31. /wp-admin/admin-apax.php?action=auto_detect_cf&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7852" title="WP All Import &lt;= 3.2.4 - Multiple Vulnerabilities">WP All Import</a></td>
        <td>&lt;= 3.2.4</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post">AX</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 32. /wp-admin.php/admin.php?action=upgrade-plugin&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7781" title="UpdraftPlus &lt;= 1.9.50 - Privilege Escalation">UpdraftPlus</a></td>
        <td>&lt;= 1.9.50</td>
        <td><abbr title="Privilege Escalation">PE</abbr></td>
        <td><abbr title="Ajax/Post">AX</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 33. /wp-content/plugins/ultimate-member/core/lib/upload/um-file-upload.php... | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7850" title="Ultimate Member &lt;= 1.0.78 - Multiple Vulnerabilities">Ultimate Member</a></td>
        <td>&lt;= 1.0.78</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="http://www.pritect.net/blog/ultimate-member-plugin-1-0-78-critical-security-vulnerability">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 34. /wp-admin/admin-ajax.php?action=widgets_init&Action=UPCP_AddProductSpreadsheet -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7939" title="Ultimate Product Catalogue Plugin &lt;= 3.1.1 - Unauthenticated File Upload">Ultimate Product Catalogue</a></td>
        <td>&lt;= 3.1.1</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://wpvulndb.com/vulnerabilities/7939">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 35. /wp-admin/admin-ajax.php?action=record_view&Item_ID=2&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7946" title="Ultimate Product Catalogue Plugin &lt;= 3.1.2 - Unauthenticated SQL Injection">Ultimate Product Catalogue</a></td>
        <td>&lt;= 3.1.2</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36823/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 36. /?SingleProduct=2'+and+'a'='a -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7948" title="Ultimate Product Catalogue Plugin &lt;= 3.1.2 - Unauthenticated SQL Injection">Ultimate Product Catalogue</a></td>
        <td>&lt;= 3.1.2</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Front End"><a href="https://www.exploit-db.com/exploits/36824/">FE</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 37. /wp-admin/options-general.php?page=tinymce-advanced&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7775" title="TinyMCE Advanced 4.1 - Setting Reset CSRF">TinyMCE Advanced</a></td>
        <td>&lt;= 4.1</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://vexatioustendencies.com/wordpress-plugin-vulnerability-dump-part-2/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 38. /wp-admin/admin.php?page=sliders_huge_it_slider&task=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7811" title="Huge-IT Slider - SQL Injection ">Huge-IT Slider</a></td>
        <td>&lt;= 2.6.8</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.htbridge.com/advisory/HTB23250">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 39. /wp-content/plugins/simple-ads-manager/sam-ajax-admin.php... | wp-load.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7882" title="Simple Ads Manager &lt;= 2.5.94 - Arbitrary File Upload & SQL Injection">Simple Ads Manager</a></td>
        <td>&lt;= 2.5.94</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr>, <abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct including wp-load.php"><a href="http://packetstormsecurity.com/files/131282/">PD*</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 40. should escape just before output to public page -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7922" title="Related Posts for WordPress &lt;= 1.8.1 - Cross-Site Scripting (XSS)">Related Posts for WordPress</a></td>
        <td>&lt;= 1.8.1</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Front End">FE</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 41. /wp-admin/admin-ajax.php?page=ajax-search-lite/backend/settings.php&action=wpdreams-ajaxinput... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7858" title="Ajax Search Lite &lt;= 3.1 - Authenticated RCE">Ajax Search Lite</a></td>
        <td>&lt;= 3.1</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://research.evex.pw/?vuln=9">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 42. /wp-admin/admin.php?page=powerpress/powerpressadmin_categoryfeeds.php&action=powerpress-editcategoryfeed... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7773" title="Blubrry PowerPress &lt;= 6.0 - Cross-Site Scripting (XSS)">Blubrry PowerPress</a></td>
        <td>&lt;= 6.0</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="https://www.netsparker.com/cve-2015-1385-xss-vulnerability-in-blubrry-powerpress/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 43. lack of nonce /wp-admin/admin.php?page=PlusCaptcha&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7870" title="PlusCaptcha Plugin - CSRF">PlusCaptcha</a></td>
        <td>&lt;= 2.0.14</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin">WA</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 44. lack of esc_url() -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7924" title="P3 (Plugin Performance Profiler) &lt;= 1.5.3.8 - Cross-Site Scripting (XSS)">Plugin Performance Profiler</a></td>
        <td>&lt;= 1.5.3.8</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin">WA</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 45. /wp-admin/admin-ajax.php?action=submit_nex_form&nex_forms_Id=10 AND (SELECT * FROM (SELECT(SLEEP(10)))NdbE) -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7928" title="NEX-Forms - Ultimate Form builder &lt;= 3.0 - SQL Injection">NEX-Forms</a></td>
        <td>&lt;= 3.0</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36800/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 46. /?action=download&option=com_miwoftp&item=wp-config.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7848" title="MiwoFTP - File & Folder Manager &lt;= 1.0.4 - Arbitrary File Disclosure">MiwoFTP</a></td>
        <td>&lt;= 1.0.4</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Front End"><a href="https://research.g0blin.co.uk/g0blin-00038/">FE</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 47. /wp-admin/admin.php?page=miwoftp&action=edit... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7905" title="MiwoFTP - File & Folder Manager &lt;= 1.0.5 - Multiple Vulnerabilities">MiwoFTP</a></td>
        <td>&lt;= 1.0.5</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/131436/">WA</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 48. /?login_required=1&user=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7839" title="MainWP Child &lt;= 2.0.9.1 - Authentication Bypass">MainWP Child</a></td>
        <td>&lt;= 2.0.9.1</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Front End">FE</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 49. /wp-admin/admin-ajax.php?action=-&mashsb-action=tools_tab_system_info -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7936" title="Mashshare &lt;= 2.3.0 - Information Disclosure">Mashshare</a></td>
        <td>&lt;= 2.3.0</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://research.g0blin.co.uk/g0blin-00045/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 50. allow ajax to anonymous users with `wp_ajax_nopriv_` -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7871" title="WordPress Leads 1.6.1-1.6.2 - Persistent XSS">WordPress Leads</a></td>
        <td>&lt;= 1.6.2</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://research.g0blin.co.uk/g0blin-00042/">AX</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr class="no-sort"><!-- Summary -->
        <th class="text-right" colspan="4">The total amount of <span class="label label-success">OK</span></tt>
        <td class="text-center">33</td>
        <td class="text-center">30</td>
      </tr>
    </tbody>
  </table>
</div>

### Analysis of Attack Vectors ###

The results gave me something of great interest when I 
<a href="javascript:void(0);" onclick="sortby('attack-vec');" 
   title="execute to sort by Path">sort by "<strong>Path</strong>"</a>.
"PD" (Plugin Direct) and "FE" (Front End) are all in red.
So I'd like to dive into these attack vectors.

#### Plugin Direct ####

Some plugin or theme authors tend to call the files directly under their plugin 
or theme folder. Some of these files include `wp-load.php` to bootstrap 
WordPress, which cases are indicated as "PD*". This is not generally 
recommended from the WordPress security point of view. While such authors have 
their own reasons (for example they prefer to reuse their own great resources), 
that does not mean to excuse of ignoring the WordPress programming paradigm, 
that is [event-driven architecture][Tom-McFarlin].

> [In almost every case there is no reason to allow code to be called directly]
  (http://www.pritect.net/blog/wp-ultimate-csv-importer-3-7-1-critical-vulnerability
  "by James Golovich").

It's also a remarkable fact that a variety of vulnerabilities are there in 
this type of attack vector. So, a direct assess to the `/wp-content/plugins/` 
and `/wp-content/themes/` from outside the site should be blocked to prevent 
various vulnerability.

#### Front End ####

There was [an vulnerability][MainWP-Child] that allowed anyone to login as an 
administrator without any fences by following access on the top:

```html
http://example.com/?login_required=1&user=admin&...
```

Typical case in this type of vulnerability is that the `init` action is hooked 
to some functions to make significant jobs for administrators, which can be 
triggered by anyone when visiting at public facing pages.

Generally speaking, all we should do is to filter out any malicious queries 
from the requests using whitelist or blacklist to prevent vulnerability such 
as LFI, XSS, SQLI.

### Conclusion ###

For WP-ZEP, the estimated amount of ratio to prevent zero-day exploitation is 
about 60%. Is it too low? Yes it is. But please consider that none of plugins 
but WP-ZEP have the ability of preventing unveiled attacks.

After this investigation, I found two things. The first One is that denying 
an attacker accessing to the plugins/themes area by restricting IP addresses 
via `.htaccess` can be a bullet proof for zero-day attack. But I think most 
people do not do this because it's hard to know the range of IP addresses 
which depend on the server components.

The second is that it's better to implement the blocking functionarity on the 
public facing pages based on the geolocation according to my bacic concept of 
this plugin, that is:

> the protection based on the IP address is not a perfect solution for everyone.
> But for some site owners or some certain cases such as 'zero-day attack', 
> it can still reduce the risk of infection against the specific attacks.

The another way to prevent malicious access to the front end is detecting 
signature base on the type of vulnerability like 
<abbr title="Web Applicatio Firewall">WAF</abbr>. But I think this 
functionarity is out of scope of this plugin. <span class="emoji">
![emoji](https://assets-cdn.github.com/images/icons/emoji/unicode/2693.png "anchor")
</span>.

[wpvulndb]:     https://wpvulndb.com/plugins "WordPress Plugin Vulnerabilities"
[Sucuri]:       https://sucuri.net/ "Sucuri Security — Website Protection, Malware Removal, and Blacklist Prevention"
[WP-ZEP]:       {{ "/article/how-wpzep-works.html" | prepend: site.baseurl }} "How does WP-ZEP prevent zero-day attack?"
[Packet-Storm]: http://packetstormsecurity.com/ "Packet Storm"
[Tom-McFarlin]: https://tommcfarlin.com/wordpress-and-mvc/ "WordPress and MVC (Gloves Aren't Made for Feet) | Tom McFarlin"
[MainWP-Child]: https://blog.sucuri.net/2015/03/security-advisory-mainwp-child-wordpress-plugin.html "Security Advisory: MainWP-Child WordPress Plugin | Sucuri Blog"
[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
