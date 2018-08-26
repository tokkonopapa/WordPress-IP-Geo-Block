---
layout: default
title: Codex
style: ".other-things { display: none }"
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="{{ '/' | prepend: site.baseurl }}" title="Top">Top</a> &raquo; {{ page.title }}
    </div>

{% assign section = false %}
    <ul id="codex" class="icon icon-fore">
{% for post in site.codex %}
  {% if post.language == page.language or post.language == 'en' %}
    {% if post.section and post.section != section %}
      {% assign section = post.section %}
      <li class="icon-folder-open"><span class="list-title" id="{{ post.section | slugify }}">{{ post.section | capitalize }}</span><ul class="icon">
    {% endif %}
      <li class="icon-description">
        <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
      </li>
    {% if section and section != site.codex[forloop.index].section %}
      </ul></li>
      {% assign section = false %}
    {% endif %}
  {% endif %}
{% endfor %}
    </ul>

<script>var GOOG_FIXURL_LANG = 'en', GOOG_FIXURL_SITE = location.host;</script>
<script src="https://linkhelp.clients.google.com/tbproxy/lh/wm/fixurl.js"></script>

  </div>
</div>