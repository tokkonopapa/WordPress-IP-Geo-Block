---
layout: default
title: Codex
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="{{ '/' | prepend: site.baseurl }}" title="Top">Top</a> &raquo; {{ page.title }}
    </div>

    <ul class="icon">{% for post in site.codex %}
      <li class="icon-circle-right">
        <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
      </li>{% endfor %}
    </ul>

  </div>
</div>
