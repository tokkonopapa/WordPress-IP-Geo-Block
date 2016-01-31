---
layout: page
category: codex
title: Record settings and logs
---

"**$_POST keys to be recorded with their values in logs**" at 
"**Record settings**" is useful when you want to investigate the content 
blocked by this plugin.

<!--more-->

### HTTP Method ###

[RFC2616][RFC2616-SEC9] defines 8 method, i.e. GET, POST, PUT, DELETE, HEAD, 
OPTIONS, TRACE, CONNECT. The definitions says :

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

### $_POST keys ###

If a request submitted by POST method is blocked, keys in `$_POST` environment 
variable are recorded into the log. The corresponded keys in "**$_POST keys to 
be recorded with their values in logs**" are deployed to their values in order 
to take a look at them.

![Record settings]({{ '/img/2016-01/RecordSettings.png' | prepend: site.baseurl }}
 "Record settings"
)

The recommended keys are as follows :

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

### Description of "Result" ###

The following picutre shows the reason of blocking at "**Result**" column on 
"**Logs**" tab.

![Validation Logs]({{ '/img/2015-11/validation-logs.png' | prepend: site.baseurl }}
 "Validation Logs"
)

Here are the descriptions :

| Result        | Description                     |
|:--------------|:--------------------------------|
| passed        | validation succeeded            |
| blocked       | blocked by country              |
| wp-zep        | blocked by WP-ZEP               |
| badsig        | blocked by Bad Signature        |
| extra         | blocked by Extra IP addresses   |
| failed        | blocked by failed login attempt |

### See also ###

- [ip-geo-block-backup-dir][BackupDir]
- [ip-geo-block-xxxxx][Validation]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[RFC2616-SEC9]: http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html "HTTP/1.1: Method Definitions"
[BackupDir]:    {{ '/codex/ip-geo-block-backup-dir.html' | prepend: site.baseurl }} 'ip-geo-block-backup-dir | IP Geo Block'
[Validation]:   {{ '/codex/ip-geo-block-xxxxx.html'      | prepend: site.baseurl }} 'ip-geo-block-xxxxx | IP Geo Block'
