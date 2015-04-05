/*
 * Project: GmapRS - google map for WordPress IP Geo Block
 * Description: A really simple google map plugin based on jQuery-boilerplate.
 * Version: 0.2.3
 * Copyright (c) 2013 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
if(typeof google==="object")(function(c,f,g,a){var e="GmapRS",d="plugin_"+e,b={zoom:2,latitude:0,longitude:0},i=google.maps,h=function(j){this.o=c.extend({},b);this.q=[]};h.prototype={init:function(j){c.extend(this.o,j);this.c=new i.LatLng(this.o.latitude,this.o.longitude);this.m=new i.Map(this.e.get(0),{zoom:this.o.zoom,center:this.c,mapTypeId:i.MapTypeId.ROADMAP})},destroy:function(){this.deleteMarkers();this.e.data(d,null)},setCenter:function(){if(arguments.length>=2){var j=new i.LatLng((this.o.latitude=arguments[0]),(this.o.longitude=arguments[1]));delete this.c;this.c=j}this.m.setCenter(this.c);return this.e},setZoom:function(j){this.m.setZoom(j||this.o.zoom);return this.e},showMarker:function(l,k){var j=this.q[l];if(j&&j.w){false===k?j.w.close():j.w.open(this.m,j.m)}},addMarker:function(l){var m,j,k;m=new i.LatLng(l.latitude||this.o.latitude,l.longitude||this.o.longitude);j=new i.Marker({position:m,map:this.m,title:l.title||""});if(l.content){k=new i.InfoWindow({content:l.content});i.event.addListener(j,"click",function(){k.open(j.getMap(),j)})}this.q.push({p:m,w:k,m:j});this.m.setCenter(m);this.m.setZoom(l.zoom);if(l.show){this.showMarker(this.q.length-1)}return this.e},deleteMarkers:function(){var j,k;for(j in this.q){k=this.q[j];k.m.setMap(null)}this.q.length=0;return this.e}};c.fn[e]=function(k){var l,j;if(!(this.data(d) instanceof h)){this.data(d,new h(this))}j=this.data(d);j.e=this;if(typeof k==="undefined"||typeof k==="object"){if(typeof j.init==="function"){j.init(k)}}else{if(typeof k==="string"&&typeof j[k]==="function"){l=Array.prototype.slice.call(arguments,1);return j[k].apply(j,l)}else{c.error("Method "+k+" does not exist."+e)}}}}(jQuery,window,document));

var ip_geo_block_start = new Date();

(function ($) {
	function sanitize(str) {
		return str ? str.toString().replace(/[&<>"']/g, function (match) {
			return {
				'&' : '&amp;',
				'<' : '&lt;',
				'>' : '&gt;',
				'"' : '&quot;',
				"'" : '&#39;'
			}[match];
		}) : '';
	}

	function loading(id, flag) {
		if (flag) {
			$('#ip-geo-block-' + id).addClass('ip-geo-block-loading');
		} else {
			$('#ip-geo-block-' + id).removeClass('ip-geo-block-loading');
		}
	}

	function confirm(msg, callback) {
		if (window.confirm(msg)) {
			callback();
		}
	}

	function warning(status, msg) {
		alert(status + ' ' + msg);
	}

	function redirect(page, tab) {
		if (-1 !== location.href.indexOf(page)) {
			window.location.href = sanitize(page) + '&' + sanitize(tab);
		}
	}

	// Download from Maxmind server
	function ajax_update_database() {
		loading('download', true);

		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			cmd: 'download',
			which: 'maxmind'
		})

		.done(function (data, textStatus, jqXHR) {
			var key;
			for (key in data) { // key: ipv4, ipv6
				if (data.hasOwnProperty(key)) {
					key = sanitize(key);
					if (data[key].filename) {
						$('#ip_geo_block_settings_maxmind_'
						+ key + '_path').val(sanitize(data[key].filename));
					}
					if (data[key].message) {
						$('#ip_geo_block_' + key).text(sanitize(data[key].message));
					}
				}
			}
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			warning(textStatus, jqXHR.responseText);
		})

		.always(function () {
			loading('download', false);
		});
	}

	// Search Geolocation
	function ajax_get_location(service, ip) {
		loading('loading', true);

		// `IP_GEO_BLOCK` is enqueued by wp_localize_script()
		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			cmd: 'search',
			which: service,
			ip: ip
		})

		.done(function (data, textStatus, jqXHR) {
			var key, info = '<ul>';
			for (key in data) {
				if (data.hasOwnProperty(key)) {
					key = sanitize(key);
					info +=
						'<li>'
							+ '<span class="ip-geo-block-title">' + key
							+ ' : </span>' + '<span class="ip-geo-block-result">'
							+ sanitize(data[key]) + '</span>' +
						'</li>';
				}
			}
			info += '</ul>';

			$('#ip-geo-block-map').GmapRS('addMarker', {
				latitude: data.latitude || 0,
				longitude: data.longitude || 0,
				title: ip,
				content: info,
				show: true,
				zoom: 8
			});
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			warning(textStatus, jqXHR.responseText);
		})

		.always(function () {
			loading('loading', false);
		});
	}

	// Clear statistics, cache, logs
	function ajax_clear(cmd, type) {
		loading('loading', true);

		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			cmd: 'clear-' + cmd,
			which: type
		})

		.done(function (data, textStatus, jqXHR) {
			redirect(data.page, data.tab);
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			warning(textStatus, jqXHR.responseText);
		})

		.always(function () {
			loading('loading', false);
		});
	}

	// Load logs
	function ajax_load_logs(type) {
		loading('loading', true);

		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			cmd: 'restore',
			which: type,
			time: new Date() - ip_geo_block_start
		})

		.done(function (data, textStatus, jqXHR) {
			var key;
			for (key in data) {
				if (data.hasOwnProperty(key)) {
					key = sanitize(key); // data has been already sanitized
//					html = $.parseHTML(data[key]); // @since 1.8
//					$('#ip-geo-block-log-' + key).empty().append(html);
					$('#ip-geo-block-log-' + key).html(data[key]);
				}
			}
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			warning(textStatus, jqXHR.responseText);
		})

		.always(function () {
			if (typeof $.fn.footable === 'function') {
//				console.time('timer');
				$('.ip-geo-block-log').fadeIn('slow').footable();
//				console.timeEnd('timer');
			}
			loading('loading', false);
		});
	}

	// Show/Hide description of WP-ZEP
	function show_description(select, id) {
		if (2 == $(select).val()) {
			$(id).show();
		} else {
			$(id).hide();
		}
	}

	$(function () {
		// Make form style with fieldset and legend
		$('.form-table').each(function () {
			$this = $(this);
			var title = $this.prev();
			if (title.prop('tagName').toLowerCase() === 'h3') {
				// Move title into the fieldset and wrap with legend
				$this.wrap('<fieldset class="ip-geo-block-field"></fieldset>')
				     .parent().prepend(title.wrap('<legend></legend>').parent());
			}
		});

		// Kick-off footable
		if ($('.ip-geo-block-log').hide().length) {
			ajax_load_logs(null);
		}

		// Inhibit to submit by return key
		$('#ip-geo-block-inhibit').on('submit', function () {
			return false;
		});

		// Update database
		$('#update').on('click', function (event) {
			ajax_update_database();
		});

		// Statistics
		$('#clear_statistics').on('click', function (event) {
			confirm('Clear statistics ?', function () {
				ajax_clear('statistics', null);
			});
			return false;
		});

		// Statistics
		$('#clear_cache').on('click', function (event) {
			confirm('Clear cache ?', function () {
				ajax_clear('cache', null);
			});
			return false;
		});

		// Validation Logs
		$('#clear_logs').on('click', function (event) {
			confirm('Clear logs ?', function () {
				ajax_clear('logs', null);
			});
			return false;
		});

		// Initialize map if exists
		$('#ip-geo-block-map').each(function () {
			$(this).GmapRS();
		});

		// Search Geolocation
		$('#get_location').on('click', function (event) {
			var ip = $('#ip_geo_block_settings_ip_address').val(),
			    service = $('#ip_geo_block_settings_service').val();
			if (ip) {
				ajax_get_location(service, ip);
			}
			return false;
		});

		// Show/Hide description of WP-ZEP
		$('#ip_geo_block_settings_validation_admin').on('change', function (event) {
			show_description(this, '#ip-geo-block-admin-desc');
		}).trigger('change');

		$('#ip_geo_block_settings_validation_ajax').on('change', function (event) {
			show_description(this, '#ip-geo-block-ajax-desc');
		}).trigger('change');
	});
}(jQuery));