---
layout: default
title: Archives
categories: archives
---
<div class="container">
  <div class="section">

    <div class="breadcrumb">
      <a href="{{ '/' | prepend: site.baseurl }}" title="Top">Top</a> &raquo; 
{% for category in site.categories %}
      <a href="#{{ category | first | remove:' ' }}">{{ category | first | capitalize }}</a>{% if forloop.last %}{% else %}, {% endif %}
{% endfor %}
    </div>
{% for category in site.categories %}
    <div class="catbloc" id="{{ category | first | remove:' ' }}">
      <h2 class="blog-post-title">{{ category | first | capitalize }}</h2>
      <ul class="icon">{% for posts in category %}{% for post in posts %}{% if post.url %}
        <li class="icon-circle-right">
          <time class="blog-post-meta">{{ post.date | date: "%b %-d, %Y" }}</time>
          <a class="blog-post-link" href="{{ post.url | prepend: site.baseurl }}">{{ post.title }}</a>
        </li>{% endif %}{% endfor %}{% endfor %}
      </ul>
    </div>
{% endfor %}
  </div>
</div>