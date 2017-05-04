---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-forbidden-upload
file: [class-ip-geo-block.php]
---

This will be applied when forbidden uploading is detected.

<!--more-->

### Description ###

The filter hook "**ip-geo-block-forbidden-upload**" will be applied via 
[`apply_filters()`][Apply-Filters] when the request has improper [capability]
[RoleCapability] or forbidden MIME type is detected in the uploaded files.

### Parameters ###

- $validation  
  (array) An associative array of validation results.

| Type    | Name     | Description                              |
|:--------|:--------:|:-----------------------------------------|
| string  | 'ip'     | validated ip address                     |
| bool    | 'auth'   | authenticated or not                     |
| string  | 'code'   | country code                             |
| string  | 'result' | reason of blocking ('passed', 'blocked') |

### Use case ###

The following code snippet can handle the uploaded files.

{% highlight ruby startinline %}
/**
 * Handle uploaded files when fobidden MIME type is detected.
 *
 * @param  array $validate  validation result
 * @return array $validate  validation result
 */
function my_forbidden_upload( $validate ) {
    // something to handle in $_FILES
    ;

    return $validate;
}
add_filter( "ip-geo-block-forbidden-upload", "my_forbidden_upload" );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.3

### See also ###

- [3.0.3 Release Note][ReleaseNote303]
- [Roles and Capabilities][RoleCapability]
- [`get_allowed_mime_types()`][AllowedMIME]

[IP-Geo-Block]:   https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Apply-Filters]:  https://developer.wordpress.org/reference/functions/apply_filters/ "apply_filters() | Function | WordPress Developer Resources"
[RoleCapability]: https://codex.wordpress.org/Roles_and_Capabilities "Roles and Capabilities &laquo; WordPress Codex"
[AllowedMIME]:    https://developer.wordpress.org/reference/functions/get_allowed_mime_types/ "get_allowed_mime_types() | Function | WordPress Developer Resources"
[ReleaseNote303]: {{ '/changelog/release-3.0.3.html' | prepend: site.baseurl }} "3.0.3 Release Note | IP Geo Block"
