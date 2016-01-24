---
layout: default
title: Codex
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="{{ '/' | prepend: site.baseurl }}" title="Top">Top</a> &raquo; {{ page.title }}
    </div>
{% assign section = false %}
    <ul class="icon">
{% for post in site.codex %}
  {% if post.section and post.section != section %}
    {% assign section = post.section %}
      <li class="icon-folder-open"><span class="list-title">{{ post.section | capitalize }}</span><ul class="icon">
  {% endif %}
      <li class="icon-circle-right">
        <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
      </li>
  {% if section and section != site.codex[forloop.index].section %}
      </ul></li>
    {% assign section = false %}
  {% endif %}
{% endfor %}
    </ul>

  </div>
</div>