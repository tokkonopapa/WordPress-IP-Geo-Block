/*
 * Project: GmapRS - google map for WordPress IP Geo Block
 * Description: A really simple google map plugin based on jQuery-boilerplate.
 * Version: 0.2.3
 * Copyright (c) 2013 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
;(function(c,f,g,a){var e="GmapRS",d="plugin_"+e,b={zoom:2,latitude:0,longitude:0},i=google.maps,h=function(j){this.o=c.extend({},b);this.q=[]};h.prototype={init:function(j){c.extend(this.o,j);this.c=new i.LatLng(this.o.latitude,this.o.longitude);this.m=new i.Map(this.e.get(0),{zoom:this.o.zoom,center:this.c,mapTypeId:i.MapTypeId.ROADMAP})},destroy:function(){this.deleteMarkers();this.e.data(d,null)},setCenter:function(){if(arguments.length>=2){var j=new i.LatLng((this.o.latitude=arguments[0]),(this.o.longitude=arguments[1]));delete this.c;this.c=j}this.m.setCenter(this.c);return this.e},setZoom:function(j){this.m.setZoom(j||this.o.zoom);return this.e},showMarker:function(l,k){var j=this.q[l];if(j&&j.w){false===k?j.w.close():j.w.open(this.m,j.m)}},addMarker:function(l){var m,j,k;m=new i.LatLng(l.latitude||this.o.latitude,l.longitude||this.o.longitude);j=new i.Marker({position:m,map:this.m,title:l.title||""});if(l.content){k=new i.InfoWindow({content:l.content});i.event.addListener(j,"click",function(){k.open(j.getMap(),j)})}this.q.push({p:m,w:k,m:j});this.m.setCenter(m);this.m.setZoom(l.zoom);if(l.show){this.showMarker(this.q.length-1)}return this.e},deleteMarkers:function(){var j,k;for(j in this.q){k=this.q[j];k.m.setMap(null)}this.q.length=0;return this.e}};c.fn[e]=function(k){var l,j;if(!(this.data(d) instanceof h)){this.data(d,new h(this))}j=this.data(d);j.e=this;if(typeof k==="undefined"||typeof k==="object"){if(typeof j.init==="function"){j.init(k)}}else{if(typeof k==="string"&&typeof j[k]==="function"){l=Array.prototype.slice.call(arguments,1);return j[k].apply(j,l)}else{c.error("Method "+k+" does not exist."+e)}}}}(jQuery,window,document));

(function ($) {

	function sanitize(str) {
		return (str + '').replace(/[&<>"']/g, function (match) {
			return {
				'&' : '&amp;',
				'<' : '&lt;',
				'>' : '&gt;',
				'"' : '&quot;',
				"'" : '&#39;'
			}[match];
		});
	}

	function ajax_get_location(service, ip) {
		$('#ip-geo-block-loading').addClass('ip-geo-block-loading');

		// `IP_GEO_BLOCK` is enqueued by wp_localize_script()
		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			provider: service,
			ip: ip
		})

		.done(function (data, textStatus, jqXHR) {
			var info = '<ul>';
			for (var key in data) {
				info +=
					'<li>' +
						'<span class="ip-geo-block-title">' + sanitize(key) + ' : </span>' +
						'<span class="ip-geo-block-result">' + sanitize(data[key]) + '</span>' +
					'</li>';
			}
			info += '</ul>';

			$("#ip-geo-block-map").GmapRS('addMarker', {
				latitude: data.latitude || 0,
				longitude: data.longitude || 0,
				title: ip,
				content: info,
				show: true,
				zoom: 8
			});
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			alert(jqXHR.responseText);
		})

		.complete(function () {
			$('#ip-geo-block-loading').removeClass('ip-geo-block-loading');
		});
	}

	function ajax_clear_statistics() {
		$('#ip-geo-block-loading').addClass('ip-geo-block-loading');

		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			clear: 'statistics'
		})

		.done(function (data, textStatus, jqXHR) {
			window.location = data.refresh;
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			alert(jqXHR.responseText);
		})

		.complete(function () {
			$('#ip-geo-block-loading').removeClass('ip-geo-block-loading');
		});
	}

	$(function () {

		// Statistics
		$('#clear_statistics').on('click', function (event) {
			if (window.confirm('Clear statistics ?')) {
				ajax_clear_statistics();
			}
			return false;
		});

		// Initialize map if exists
		$("#ip-geo-block-map").each(function () {
			$(this).GmapRS();
		});

		// Search Geolocation
		$('#get_location').on('click', function (event) {
			var ip = $('#ip_geo_block_settings_ip_address').val();
			var service = $('#ip_geo_block_settings_service').val();
			if (ip) {
				ajax_get_location(service, ip);
			}
			return false;
		});

		// Attribution link (redirect without referer)
		$('a.ip-geo-block-api').on('click', function (event) {
			var url = this.href;
			var w = window.open();
			w.document.write(
				'<meta http-equiv="refresh" content="1; url=' + url +'">' +
				'<body>Redirecting to ' + sanitize(url) + ' ...</body>'
			);
			w.document.close();
			return false;
		});

	});

}(jQuery));