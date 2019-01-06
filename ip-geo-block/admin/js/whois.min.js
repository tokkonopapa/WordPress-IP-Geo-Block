/*!
 * Project: whois.js - get whois infomation from RIPE Network Coordination Center
 * Description: A jQuery plugin to get whois infomation from RIPE NCC database.
 * Version: 0.2
 * Copyright (c) 2019 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
!function(e){e.extend({whois:function(t,n){var a=[];function u(e){return e?e.toString().replace(/[&<>"']/g,function(e){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[e]}):""}return e.ajax({url:"https://stat.ripe.net/data/whois/data.json?resource="+t,method:"GET",dataType:"json"}).done(function(e,t,n){!function e(t,n){for(var a in t)n.apply(this,[a,t[a]]),null!==t[a]&&"object"==typeof t[a]&&e(t[a],n)}(e.data,function(e,t){t&&"object"==typeof t&&t.key&&(t.key=u(t.key),t.value=u(t.value),t.details_link&&(t.value='<a href="'+u(t.details_link)+'">'+t.value+"</a>"),a.push({name:t.key,value:t.value}))})}).fail(function(e,t,n){a.push({name:u(t),value:u(n)})}).always(function(){n&&n(a)})}})}(jQuery);