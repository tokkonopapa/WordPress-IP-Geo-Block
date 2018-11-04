---
layout: page
language: en
category: codex
section: Dashboard
title: Front-end target settings
---

In this section you can set up rules to block access to the public facing pages
(aka front-end) from undesired countries.

For spammers, this plugin can reduce both the load on the server and the amount
of comment spams by preventing comment form acquisition on the front-end.
Against attacks targeted at vulnerabilities in themes and plugins, this plugin 
can also reduce the risk of hacking sites such as malware installation.

In general, it is difficult to filter only malicious requests from all requests
unless you [restrict content by region][GeoBlock], but with the combination of 
rules in "[**Validation rule settings**][RuleSettings]", unnecessary traffic 
for your site and risks can be reduced considerably.

![Front-end target settings]({{ '/img/2016-08/FrontEndSettings.png' | prepend: site.baseurl }}
 "Front-end target settings"
)

### Public facing pages ###

Turn on "**Block by country**" when you do not want traffic from the specific 
countries. Even when you enable this option, "**Whitelist/Blacklist of extra 
IP addresses prior to country code**", "**Bad signatures in query**" and 
"**Prevent malicious file uploading**" in "[**Validation rule settings**]
[RuleSettings]" section are effective.

### Matching rule ###

You can select one of these:

- **Follow "Validation rule settings"**
- **Whitelist**
- **Blacklist**

When you select **Whitelist** or **Blacklist**, you can configure a different 
set of country code and response code from "[**Validation rule settings**]
[RuleSettings]" section.

If blocking by country is inappropriate for your site or if you want to block 
only specific bots and crawlers, you can leave "**Whitelist of country code**"
empty to apply only a set of rules under "**UA string and qualification**".

![Additional 3 options]({{ '/img/2016-08/FrontEndMatchingRule.png' | prepend: site.baseurl }}
 "Additional 3 options"
)

### Validation target ###

You can select one of the followings:

- **All requests**  
Every request to the front-end will be validated as a blocking target. This can
be compatible with [some caching plugins under certain conditions][LivingCache].

- **Specify the targets**  
You can specify the requests for the __page__, __post type__, __category__ 
and __tag__ on a single page or archive page as a blocking target. This ignores
the setting of "[**Validation timing**][TimingRule]" to get those information 
from the requested URL. That means the validation is always deferred util [`wp`
action hook][ActionHookWP] fires, and also lose the compatibility with page 
caching.
  
  ![Validation target]({{ '/img/2016-11/ValidationTarget.png' | prepend: site.baseurl }}
   "Validation target"
  )  
  
  <div class="alert alert-info">
    <strong>Note:</strong>
    Even if you specify all the targets here, attacker can still access the TOP
    page because it belongs to neither single page nor archive page. Therefore,
    when you intend to validate all requests, you should select "<strong>All
    requests</strong>".
  </div>

### Block badly-behaved bots and crawlers ###

Block badly-behaved bots and crawlers that repeat many requests in a short time.
Make sure to specify the observation period and the number of page requests to 
the extent that impatient visitors do not feel uncomfortable.

![Block badly-behaved bots and crawlers]({{ '/img/2016-08/FrontEndBadBehave.png' | prepend: site.baseurl }}
 "Block badly-behaved bots and crawlers"
)

### UA string and qualification ###
You can configure the rules to qualify valuable bots and crawlers such as 
google, yahoo and being OR the rules to block unwanted requests that can 
not be blocked by country code, giving a pair of "**UA string**" and
"**qualification**" separated by an applicable behavior which can be "`:`"
(pass) or "`#`" (block).

![UA string and qualification]({{ '/img/2016-08/UA-Qualify.png' | prepend: site.baseurl }}
 "UA string and qualification"
)

See "[UA string and qualification][UA-Qualify]" for more details.

- **Reverse DNS lookup**  
In order to make use of `HOST` in "**qualification**", you should specify this 
option to get the host name corresponding the IP address. If it is disabled, 
`HOST` and <code>HOST=&hellip;</code> shall always be deemed as TRUE.

### See also ###

- [The best practice of target settings][BestPractice]
- [Living with caching plugin][LivingCache]
- [UA string and qualification][UA-Qualify]

[IP-Geo-Block]: https://wordpress.org/plugins/ip-geo-block/ "WordPress › IP Geo Block « WordPress Plugins"
[BestPractice]: {{ '/codex/the-best-practice-for-target-settings.html'        | prepend: site.baseurl }} "The best practice of target settings | IP Geo Block"
[LivingCache]:  {{ '/codex/living-with-caching-plugin.html'                   | prepend: site.baseurl }} "Living with caching plugin | IP Geo Block"
[UA-Qualify]:   {{ '/codex/ua-string-and-qualification.html'                  | prepend: site.baseurl }} "UA string and qualification | IP Geo Block"
[RuleSettings]: {{ '/codex/validation-rule-settings.html'                     | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[TimingRule]:   {{ '/codex/validation-rule-settings.html#validation-timing'   | prepend: site.baseurl }} "Validation rule settings | IP Geo Block"
[ActionHookWP]: https://codex.wordpress.org/Plugin_API/Action_Reference/wp "Plugin API/Action Reference/wp &laquo; WordPress Codex"
[GeoBlock]:     https://en.wikipedia.org/wiki/Geo-blocking "Geo-blocking - Wikipedia"
[GeoBlockEU]:   https://ec.europa.eu/digital-single-market/en/faq/geo-blocking "Geo-blocking | Digital Single Market"
