---
layout: page
language: en
category: codex
section: Dashboard
title: Validation logs
---

This plugin stores validation logs when **Record "Logs"** is enabled in 
[**Privacy and record settings**][Privacy] section on **Settings** tab.

<!--more-->

### Contents in log ###

![Validation logs]({{ '/img/2018-12/ValidationLogs.png' | prepend: site.baseurl }}
 "Validation logs"
)

The followings are some of items that are stores in logs.

#### Request ####

Following the HTTP method and the port, the requested path is recorded.
[RFC2616][RFC2616-SEC9] (obsoleted by [RFC7231][RFC7231-SEC4]) defines 8 
method, i.e. GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACE, CONNECT. The 
definitions says:

> In particular, the convention has been established that the GET and HEAD 
> methods SHOULD NOT have the significance of taking an action other than 
> retrieval. These methods ought to be considered "safe". This allows user 
> agents to represent other methods, such as POST, PUT and DELETE, in a 
> special way, so that the user is made aware of the fact that a possibly 
> unsafe action is being requested.

But in the real world, we can find a simple hyperlink (i.e. GET method) which 
takes an action other than retrieval.

![Media Library]({{ '/img/2016-01/MediaLibrary.png' | prepend: site.baseurl }}
 "Media Library"
)

Anyway, we'd better take care about what's being done by a malicious request.

#### $_POST data ####

When a request submitted by POST method is blocked, keys in `$_POST` 
environment variable are recorded into the log. The corresponded keys in 
"**$_POST keys to be recorded with their values in logs**" in [**Privacy and 
record settings**][Privacy] section are deployed to their values in order to 
take a look at them.

![Record settings]({{ '/img/2016-01/RecordSettings.png' | prepend: site.baseurl }}
 "Record settings"
)

The recommended keys are as follows:

- `action`  
  This key is very popular in WordPress. It usually shows the process of doing 
  something.

- `comment`  
  It shows the contents of comment posted to `wp-comments-post.php`.

- `log`, `pwd`  
  The login name and password posted to `wp-login.php`. The `pwd` will be 
  masked with `***` when it comes from a logged in user.  
  
  ![Log of Login form]({{ '/img/2016-01/LogLoginForm.png' | prepend: site.baseurl }}
   "Log of Login form"
  )
{% comment %} *** {% endcomment %}

- `FILES`  
  It shows the contents of HTTP File Upload variables `$_FILES` if 
  [POST method uploads][PHP-UPLOADS] was requested. (since 3.0.3)  
  
  ![Malicious file upload]({{ '/img/2017-04/LogUploadFile.png' | prepend: site.baseurl }}
   "Malicious file upload"
  )

#### Result ####

The column "**Result**" shows the validation result as the following table 
describes:

| Result        | Description                                      |
|:--------------|:-------------------------------------------------|
| passed        | passed through the validation                    |
| passUA        | passed by menas of "UA string and qualification" |
| blocked       | blocked by country                               |
| blockUA       | blocked by menas of "UA string and qualification"|
| wp-zep        | blocked by WP-ZEP                                |
| multi         | blocked by XML-RPC multicall                     |
| badsig        | blocked by Bad signatures                        |
| badbot        | blocked by Badly-behaved bots and crawlers       |
| extra         | blocked by Extra IP addresses                    |
| failed        | blocked by failed login attempt                  |
| limited       | blocked by excess of limit login attempt         |
| upload        | blocked by forbidden MIME type                   |
| ^             | found unexpected attached files                  |

### Live update ###

Independent of [**Privacy and record settings**][Privacy] section, you can 
see all the requests validated by this plugin in almost real time.

![Live update]({{ '/img/2018-12/LiveUpdate.png' | prepend: site.baseurl }}
 "Live update"
)

### See also ###

- [ip-geo-block-record-logs][RecordLogs]
- [ip-geo-block-logs[-preset]][LogsPreset]
- [ip-geo-block-live-log][LiveLogs]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "IP Geo Block &#124; WordPress.org"
[RFC2616-SEC9]: https://tools.ietf.org/html/rfc2616#section-9 "Hypertext Transfer Protocol -- HTTP/1.1: 9 Method Definitions"
[RFC7231-SEC4]: https://tools.ietf.org/html/rfc7231#section-4 "Hypertext Transfer Protocol (HTTP/1.1): 4. Request Methods"
[Privacy]:      {{ '/codex/privacy-and-record-settings.html' | prepend: site.baseurl }} 'Privacy and record settings | IP Geo Block'
[PHP-UPLOADS]:  https://php.net/manual/features.file-upload.post-method.php 'PHP: POST method uploads - Manual'
[RecordLogs]:   {{ '/codex/ip-geo-block-record-logs.html'    | prepend: site.baseurl }} 'ip-geo-block-record-logs | IP Geo Block'
[LogsPreset]:   {{ '/codex/ip-geo-block-logs-preset.html'    | prepend: site.baseurl }} 'ip-geo-block-logs[-preset] | IP Geo Block'
[LiveLogs]:     {{ '/codex/ip-geo-block-live-log.html'       | prepend: site.baseurl }} 'ip-geo-block-live-log | IP Geo Block'
