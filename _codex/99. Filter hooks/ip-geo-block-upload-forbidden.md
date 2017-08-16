---
layout: page
category: codex
section: Filter Hooks
title: ip-geo-block-upload-forbidden
file: [class-ip-geo-block.php]
---

This filter hook will be applied when forbidden uploading is detected.

<div class="alert alert-danger">
	<strong>Warning:</strong>
	The filter hook <code>ip-geo-block-forbidden-upload</code> was abolished 
	in <a href="/changelog/release-3.0.4.html" title="3.0.4 Release note">
		release 3.0.4
	</a>, but is replaced to <code>ip-geo-block-upload-forbidden</code>.
</div>

<!--more-->

### Description ###

The filter hook "**ip-geo-block-upload-forbidden**" will be applied via 
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
function my_upload_forbidden( $validate ) {
    // something to handle in $_FILES
    ;

    return $validate;
}
add_filter( "ip-geo-block-upload-forbidden", "my_upload_forbidden" );
{% endhighlight %}

{% include alert-drop-in.html %}

### Since ###

3.0.4

### See also ###

- [3.0.3 Release Note][ReleaseNote303]
- [3.0.4 Release Note][ReleaseNote304]
- [Roles and Capabilities][RoleCapability]
- [`get_allowed_mime_types()`][AllowedMIME]

[IP-Geo-Block]:   https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[Apply-Filters]:  https://developer.wordpress.org/reference/functions/apply_filters/ "apply_filters() | Function | WordPress Developer Resources"
[RoleCapability]: https://codex.wordpress.org/Roles_and_Capabilities "Roles and Capabilities &laquo; WordPress Codex"
[AllowedMIME]:    https://developer.wordpress.org/reference/functions/get_allowed_mime_types/ "get_allowed_mime_types() | Function | WordPress Developer Resources"
[ReleaseNote303]: {{ '/changelog/release-3.0.3.html' | prepend: site.baseurl }} "3.0.3 Release Note | IP Geo Block"
[ReleaseNote304]: {{ '/changelog/release-3.0.4.html' | prepend: site.baseurl }} "3.0.4 Release Note | IP Geo Block"
