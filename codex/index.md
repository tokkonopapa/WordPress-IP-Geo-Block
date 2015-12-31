---
layout: default
title: Codex
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="/" title="Top">Top</a> &raquo; {{ page.title }}
    </div>

    <ul>
{% for post in site.codex %}
      <li>
        <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
      </li>
{% endfor %}
    </ul>

  </div>
</div>
