---
layout: default
title: Article
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="{{ '/' | prepend: site.baseurl }}" title="Top">Top</a> &raquo; {{ page.title }}
    </div>

    <ul>
{% for post in site.categories.article %}{% if post.url %}
      <li>
        <time class="blog-post-meta">{{ post.date | date: "%b %-d, %Y" }}</time>
        <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
      </li>
{% endif %}{% endfor %}
    </ul>

  </div>
</div>