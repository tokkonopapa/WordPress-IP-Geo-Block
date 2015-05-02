---
layout: post
title:  "Which attack can WP-ZEP prevent?"
date:   2015-05-01 00:00:00
categories: article
published: false
---

On [WPScan Vulnerability Database][wpvulndb] maintained by [Sucuri][Sucuri],
we can find 5 or 6 new vulnerable plugins every month. Of course WP-ZEP is not 
God Almighty against these. Then you may wonder about:

- Which attack can WP-ZEP prevent?
- How many attacks can WP-ZEP prevent?

I'm with you!!

So I pick up each vulnerability in [WPScan DB][wpvulndb] one by one, and dig 
into the attack vectors to investigate the cause of the infection.

<!--more-->

Each vulnerability has its own attack vectors. Some of them are classified in 
a direct attack onto the plugin files, and some of them are classified in an 
indirect attack via WordPress core files.

[IP Geo Block][IP-Geo-Block] 

<div class="table-responsive">
  <a href="https://wpvulndb.com/plugins" title="WordPress Plugin Vulnerabilities"><small>Source: &copy; The WPScan Team</small></a>
  <table class="table">
    <thead>
      <tr>
        <th>Vulnerability</th>
        <th>Version</th>
        <th>Type</th>
        <th><abbr title="Attack Vector">A.V.</abbr></th>
        <th><abbr title="Country Code">C.C.</abbr></th>
        <th><abbr title="WP-ZEP Lv1">Lv1</abbr></th>
        <th><abbr title="WP-ZEP Lv2">Lv2</abbr></th>
      </tr>
    </thead>
    <tbody>
      <tr><!-- 1. /wp-content/plugins/wp-business-intelligence/view.php?t=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7879" title="WP Business Intelligence Lite <= 1.6.1 - SQL Injection">WP Business Intelligence Lite</a></td>
        <td><= 1.6.1</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131228/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 2. plugin direct -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7873" title="Ptengine <= 1.0.1 - Reflected Cross-Site Scripting (XSS)">Ptengine</a></td>
        <td><= 1.0.1</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Plugin Direct">P.D.</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 3. ajax/post -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7821" title="EZ Portfolio <= 1.0.1 - Multiple Cross-Site Scripting (XSS) ">EZ Portfolio</a></td>
        <td><= 1.0.1</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post">A/P</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 4. /wp-admin/admin.php?page=wonderplugin_audio_show_items... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7798" title="WonderPlugin Audio Player 2.0 Blind SQL Injection and XSS">WonderPlugin Audio Player</a></td>
        <td><= 2.0</td>
        <td><abbr title="SQL Injection">SQLI</abbr>, <abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36086/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 5. /wp-content/plugins/aspose-cloud-ebook-generator/aspose_posts_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7866" title="Aspose Cloud eBook Generator - File Download">Aspose Cloud eBook Generator</a></td>
        <td><= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131040/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 6. /wp-content/plugins/wpshop/includes/ajax.php?elementCode=ajaxUpload... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7830" title="Wpshop - eCommerce <= 1.3.9.5 - Arbitrary File Upload">WPshop - eCommerce</a></td>
        <td><= 1.3.9.5</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="https://research.g0blin.co.uk/g0blin-00036/">P.D.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 7. lack of check_admin_referer() -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7813" title="WPBook <= 2.7 - Cross-Site Request Forgery (CSRF)">WPBook</a></td>
        <td><= 2.7</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin">W.A.</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 8. /wp-admin/options-general.php?page=wp-vipergb... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7817" title="WP-ViperGB 1.3.10 - XSS Weakness and CSRF">WP-ViperGB</a></td>
        <td><= 1.3.10</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr>, <abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/129501">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 9. /wp-admin/admin-ajax.php?action=ajax_survey -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7794" title="WordPress Survey & Poll <= 1.1.7 - Blind SQL Injection">WordPress Survey & Poll</a></td>
        <td><= 1.1.7</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/130381/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 10. /wp-admin/upload.php?s=test&page=wp-media-cleaner... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7814" title="WP Media Cleaner <= 2.2.6 - Cross-Site Scripting (XSS)">WP Media Cleaner</a></td>
        <td><= 2.2.6</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130576/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 11. /wp-admin/admin.php?page=wss-images... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7888" title="WP Easy Slideshow <= 1.0.3 - Multiple Cross-Site Request Forgery (CSRF)">WP Easy Slideshow</a></td>
        <td><= 1.0.3</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin"><a href="https://www.exploit-db.com/exploits/36612/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 12. /wp-admin/admin-ajax.php?nm_webcontact_upload_file... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7896" title="N-Media Website Contact Form with File Upload <= 1.3.4 - Arbitrary File Upload">N-Media Website Contact Form</a></td>
        <td><= 1.3.4</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/131413/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 13. /?page_id=2&artistletter=G' UNION ALL SELECT... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7901" title="Tune Library <= 1.5.4 - SQL Injection">Tune Library</a></td>
        <td><= 1.5.4</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131558/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 14. /wp-admin/options-general.php?page=redirection-page... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7791" title="Redirection Page <= 1.2 - CSRF/XSS">Redirection Page</a></td>
        <td><= 1.2</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130314/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 15. /wp-content/plugins/php-event-calendar/server/classes/uploadify.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7884" title="PHP Event Calendar <= 1.5 - Arbitrary File Upload">PHP Event Calendar</a></td>
        <td><= 1.5</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131277/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 16. -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7937" title="My Wish List - Multiple Parameter XSS">My Wish List</a></td>
        <td><= 1.4.1</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Plugin Direct">P.D.</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 17. /wp-admin/options-general.php?page=mobile-domain... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7792" title="Mobile Domain <= 1.5.2 - CSRF/XSS">Mobile Domain</a></td>
        <td><= 1.5.2</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130316/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 18. /wp-content/plugins/mailchimp-subscribe-sm/data.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7935" title="MailChimp Subscribe Form <= 1.1 - Email Field Remote PHP Code Execution">MailChimp Subscribe Form</a></td>
        <td><= 1.1</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://plugins.svn.wordpress.org/mailchimp-subscribe-sm/tags/1.1/data.php">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 19. /admin.php?page=wp-IPBLC... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7816" title="IP Blacklist Cloud <= 3.4 - SQL Injection">IP Blacklist Cloud</a></td>
        <td><= 3.4</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="wp-admin">W.A.</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 20. /?action=importCSVIPCloud... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7844" title="IP Blacklist Cloud <= 3.42 - Arbitrary File Disclosure">IP Blacklist Cloud</a></td>
        <td><= 3.42</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="https://research.g0blin.co.uk/g0blin-00037/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 21. /wp-content/plugins/inboundio-marketing/admin/partials/csv_uploader.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7864" title="InBoundio Marketing Plugin <= 2.0.3 - Shell Upload">InBoundio Marketing</a></td>
        <td><= 2.0.3</td>
        <td><abbr title="Remote File Upload">RFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/130957/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 22. /wp-admin/plugins.php?page=image_metadata_cruncher-options... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7796" title="Image Metadata Cruncher - Multiple XSS">Image Metadata Cruncher</a></td>
        <td><= 1.8</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://www.securityfocus.com/archive/1/archive/1/534718/100/0/threaded">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 23. /wp-admin/options-general.php?page=thisismyurl_csj.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7812" title="CrossSlide jQuery Plugin <= 2.0.5 - Stored XSS &amp; CSRF">CrossSlide jQuery</a></td>
        <td><= 2.0.5</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/130313/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 24. /wp-content/plugins/Wordpress/Aaspose-pdf-exporter/aspose_pdf_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7876" title="Aspose PDF Exporter - Arbitrary File Download">Aspose PDF Exporter</a></td>
        <td>< 2.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131161/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 25. /wp-content/plugins/aspose-importer-exporter/aspose_import_export_download?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7877" title="Aspose Importer and Exporter 1.0 - Arbitrary File Download">Aspose Importer &amp; Exporter</a></td>
        <td><= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131162/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 26. /wp-content/plugins/aspose-doc-exporter/aspose_doc_exporter_download.php?file=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7869" title="Aspose DOC Exporter 1.0 - Arbitrary File Download">Aspose DOC Exporter</a></td>
        <td><= 1.0</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131167/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 27. /wp-content/plugins/wp-ultimate-csv-importer/modules/export/templates/export.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7778" title="WP Ultimate CSV Importer <= 3.6.74 - Database Table Export">WP Ultimate CSV Importer</a></td>
        <td><= 3.6.74</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Plugin Direct"><a href="https://research.g0blin.co.uk/g0blin-00025/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 28. /templates/readfile.php?file_name=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7949" title="WP Ultimate CSV Importer <= 3.7.1 - Directory Traversal">WP Ultimate CSV Importer</a></td>
        <td><= 3.7.1</td>
        <td><abbr title="Directory Traversal">DT</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://www.pritect.net/blog/wp-ultimate-csv-importer-3-7-1-critical-vulnerability">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 29. /wp-content/themes/mTheme-Unus/css/css.php?files=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7898" title="WP Mobile Edition <= 2.7 - Remote File Disclosure">WP Mobile Edition</a></td>
        <td><= 2.2.7</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
       <td><abbr title="Plugin Direct"><a href="https://www.exploit-db.com/exploits/36733/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 30. /wp-admin/admin-ajax.php?page=pmxi-admin-settings&action=upload... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7809" title="WP All Import <= 3.2.3 - RCE">WP All Import</a></td>
        <td><= 3.2.3</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://packetstormsecurity.com/files/130596/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 31. /wp-admin/admin-apax.php?action=auto_detect_cf&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7852" title="WP All Import <= 3.2.4 - Multiple Vulnerabilities">WP All Import</a></td>
        <td><= 3.2.4</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post">A/P</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 32. /wp-admin.php/admin.php?action=upgrade-plugin&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7781" title="UpdraftPlus <= 1.9.50 - Privilege Escalation">UpdraftPlus</a></td>
        <td><= 1.9.50</td>
        <td><abbr title="Privilege Escalation">PE</abbr></td>
        <td><abbr title="Ajax/Post">A/P</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 33. wp-content/plugins/ultimate-member/core/lib/upload/um-file-upload.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7850" title="Ultimate Member <= 1.0.78 - Multiple Vulnerabilities">Ultimate Member</a></td>
        <td><= 1.0.78</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://www.pritect.net/blog/ultimate-member-plugin-1-0-78-critical-security-vulnerability">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 34. /wp-admin/admin-ajax.php?action=widgets_init&Action=UPCP_AddProductSpreadsheet -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7939" title="Ultimate Product Catalogue Plugin <= 3.1.1 - Unauthenticated File Upload">Ultimate Product Catalogue</a></td>
        <td><= 3.1.1</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://wpvulndb.com/vulnerabilities/7939">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 35. wp-admin/admin-ajax.php?action=record_view&Item_ID=2&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7946" title="Ultimate Product Catalogue Plugin <= 3.1.2 - Unauthenticated SQL Injection">Ultimate Product Catalogue</a></td>
        <td><= 3.1.2</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36823/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 36. /?SingleProduct=2'+and+'a'='a -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7948" title="Ultimate Product Catalogue Plugin <= 3.1.2 - Unauthenticated SQL Injection">Ultimate Product Catalogue</a></td>
        <td><= 3.1.2</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="https://www.exploit-db.com/exploits/36824/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 37. wp-admin/options-general.php?page=tinymce-advanced&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7775" title="TinyMCE Advanced 4.1 - Setting Reset CSRF">TinyMCE Advanced</a></td>
        <td><= 4.1</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://vexatioustendencies.com/wordpress-plugin-vulnerability-dump-part-2/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 38. /wp-admin/admin.php?page=sliders_huge_it_slider&task=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7811" title="Huge-IT Slider - SQL Injection ">Huge-IT Slider</a></td>
        <td><= 2.6.8</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.htbridge.com/advisory/HTB23250">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 39. /wp-content/plugins/simple-ads-manager/sam-ajax-admin.php... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7882" title="Simple Ads Manager <= 2.5.94 - Arbitrary File Upload & SQL Injection">Simple Ads Manager</a></td>
        <td><= 2.5.94</td>
        <td><abbr title="Arbitrary File Upload">AFU</abbr>, <abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="http://packetstormsecurity.com/files/131282/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 40. should escape just before output to public page -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7922" title="Related Posts for WordPress <= 1.8.1 - Cross-Site Scripting (XSS)">Related Posts for WordPress</a></td>
        <td><= 1.8.1</td>
        <td><abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post">A/P</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 41. /wp-admin/admin-ajax.php?page=ajax-search-lite/backend/settings.php&action=wpdreams-ajaxinput... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7858" title="Ajax Search Lite <= 3.1 - Authenticated RCE">Ajax Search Lite</a></td>
        <td><= 3.1</td>
        <td><abbr title="Remote Code Execution">RCE</abbr></td>
        <td><abbr title="Ajax/Post"><a href="http://research.evex.pw/?vuln=9">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 42. /wp-admin/admin.php?page=powerpress/powerpressadmin_categoryfeeds.php&action=powerpress-editcategoryfeed... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7773" title="Blubrry PowerPress <= 6.0 - Cross-Site Scripting (XSS)">Blubrry PowerPress</a></td>
        <td><= 6.0</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="https://www.netsparker.com/cve-2015-1385-xss-vulnerability-in-blubrry-powerpress/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 43. lack of nonce wp-admin/admin.php?page=PlusCaptcha&... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7870" title="PlusCaptcha Plugin - CSRF">PlusCaptcha</a></td>
        <td><= 2.0.14</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr></td>
        <td><abbr title="wp-admin">W.A.</abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 44. can't esc_url() or something -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7924" title="P3 (Plugin Performance Profiler) <= 1.5.3.8 - Cross-Site Scripting (XSS)">Plugin Performance Profiler</a></td>
        <td><= 1.5.3.8</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin">W.A.</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 45. /wp-admin/admin-ajax.php?action=submit_nex_form&nex_forms_Id=10 AND (SELECT * FROM (SELECT(SLEEP(10)))NdbE) -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7928" title="NEX-Forms - Ultimate Form builder <= 3.0 - SQL Injection">NEX-Forms</a></td>
        <td><= 3.0</td>
        <td><abbr title="SQL Injection">SQLI</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://www.exploit-db.com/exploits/36800/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 46. /?action=download&option=com_miwoftp&item=wp-config.php -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7848" title="MiwoFTP - File & Folder Manager <= 1.0.4 - Arbitrary File Disclosure">MiwoFTP</a></td>
        <td><= 1.0.4</td>
        <td><abbr title="Local File Inclusion">LFI</abbr></td>
        <td><abbr title="Plugin Direct"><a href="https://research.g0blin.co.uk/g0blin-00038/">P.D.</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 47. /wp-admin/admin.php?page=miwoftp&action=edit... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7905" title="MiwoFTP - File & Folder Manager <= 1.0.5 - Multiple Vulnerabilities">MiwoFTP</a></td>
        <td><= 1.0.5</td>
        <td><abbr title="Cross-Site Request Forgery">CSRF</abbr>, <abbr title="Cross-Site Scripting">XSS</abbr></td>
        <td><abbr title="wp-admin"><a href="http://packetstormsecurity.com/files/131436/">W.A.</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 48. /?login_required=1&user=... -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7839" title="MainWP Child <= 2.0.9.1 - Authentication Bypass">MainWP Child</a></td>
        <td><= 2.0.9.1</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Plugin Direct">P.D.</abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
      <tr><!-- 49. /wp-admin/admin-ajax.php?action=-&mashsb-action=tools_tab_system_info -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7936" title="Mashshare <= 2.3.0 - Information Disclosure">Mashshare</a></td>
        <td><= 2.3.0</td>
        <td><abbr title="Authentication Bypass">AB</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://research.g0blin.co.uk/g0blin-00045/">A/P</a></abbr></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
        <td><span class="label label-success">OK</span></td>
      </tr>
      <tr><!-- 50. allow ajax to anonymous users with `wp_ajax_nopriv_` -->
        <td><a href="https://wpvulndb.com/vulnerabilities/7871" title="WordPress Leads 1.6.1-1.6.2 - Persistent XSS">WordPress Leads</a></td>
        <td><= 1.6.2</td>
        <td><abbr title="Cross Site Scripting">XSS</abbr></td>
        <td><abbr title="Ajax/Post"><a href="https://research.g0blin.co.uk/g0blin-00042/">A/P</a></abbr></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
        <td><span class="label label-danger">NG</span></td>
      </tr>
    </tbody>
  </table>
</div>

First off, the um-file-upload.php can be called directly and it bootstraps WordPress. I’d rather send every request through WordPress and not allow direct access to anything.
Which does what is essentially the commonly recommended WordPress security practice of not allowing a script to be called directly.
In almost every case there is no reason to allow code to be called directly. If you have the WordPress tools available to you then you should use them. Something like current_user_can() and a nonce should always be used.
This function is called upon the ‘admin_init’ action being fired, which can be triggered by anyone when visiting the admin AJAX handler.
The second type is assuming that the ‘admin_init’ action only happens for admins. Which isn’t the case, it is called for any action that starts up the admin interface. Which could be the user profile editor or the AJAX interface.
Coupled with the fact that there is no checking of user privilege on this function means that anonymous users are able to trigger certain functions intended for Administrative use only.

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

[wpvulndb]:     https://wpvulndb.com/plugins "WordPress Plugin Vulnerabilities"
[Sucuri]:       https://sucuri.net/ "Sucuri Security — Website Protection, Malware Removal, and Blacklist Prevention"
[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
