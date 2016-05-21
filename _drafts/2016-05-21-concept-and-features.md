---
layout: post
title: "Concept and features of IP Geo Block"
date: 2016-05-21 00:00:00
categories: article
published: true
script: []
inline:
---

### TL;DR ###

To be honest, I think that the features of IP Geo Block are lettle incomprehensible.
For example, what's the difference between "Block by country" and "Prevent Zero-day Exploit",
what's the purpose of "Force to load WP core", which switch should be turned on and 
so on.

In this article, I'll try to tell you the basic concept and corresponded features 
from the users's point view. I hope this document might help you to better site 
management.

<!--more-->

### What's the most secure state for WordPress ###

Well, IMO, the most secure state are:

1. Just after installation of WordPress
   This state consists with default theme and no plugin.

2. Secure configurations and settings
   Basic configurations for server settings, strong password are the main conserns 
   which are covered in [Hardening WordPress][Hardening].

In other words, more plugins and themes we add on the basic state of 
WordPress,less security we get (even if we install some of the security 
plugins!).

The starting concept of IP Geo Block stands on the above overview. It means 
that this plugin strongly focuses the patterns of vulnerability that is 
potentially in plugins and themes.

### Categorizatoin of security firewall ###

I do not intend to cover all the outline about security, but quickly focus 
to my plugin. So application firewall by WordPress plugin is the main topic 
here. If you are intersted in more wide reange of security firewall, 
"[Differentiate Between Security Firewalls][SUCURI-SF]" is good for you.

There is typical point of view for categorization, that is "Everything in one 
box" and "Dedicated security feature". But this categorization has no meaning 
for this topic.

Roughly speaking about another view of categorization, there are 2 approaches 
to harden WordPress security. One is whitelisting and another is blacklisting.
As a typical example of the former, WordFence is a famous one. It has a 
learning mode which would make up the whitelist of legitimate requests to the 
site. I think "hiding the path" like plugins are categorized in this area.
The apporach of blacklisting would be roughly divided into two types. The first
one focuses on the patterns of attacks or malicous accesses. The representative
example of this type, [Block Bad Queries][BBQ] is famous, but not only that one.

[Hardening]: http://codex.wordpress.org/Hardening_WordPress "Hardening WordPress « WordPress Codex"
[SUCURI-SF]: https://blog.sucuri.net/2016/04/ask-sucuri-differentiate-security-firewalls.html "What Are Security Firewalls? What is a WAF?"
[BBQ]: https://ja.wordpress.org/plugins/block-bad-queries/ "BBQ: Block Bad Queries - WordPress Plugins"
