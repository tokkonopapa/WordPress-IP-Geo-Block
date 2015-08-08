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
			var url = sanitize(page) + (tab ? '&' + sanitize(tab) : '');
			if (typeof IP_GEO_BLOCK_ZEP === 'undefined') {
				window.location.href = url;
			} else {
				IP_GEO_BLOCK_ZEP.redirect(url);
			}
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

	// Manipulate DB table for validation logs
	function ajax_table(cmd) {
		loading('loading', true);

		$.post(IP_GEO_BLOCK.url, {
			action: IP_GEO_BLOCK.action,
			nonce: IP_GEO_BLOCK.nonce,
			cmd: cmd
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

	// Show/Hide description of WP-ZEP
	function show_description(select) {
		var desc = '.ip_geo_block_settings_validation_desc';
		if (2 <= (select = $(select)).val()) {
			select.next(desc).show();
		} else {
			select.next(desc).hide();
		}
	}

	$(function () {
		// Get tab number and check wpCookies in wp-includes/js/utils.js
		var cookie, tabNum = /&tab=(\d)/.exec(window.location.href);
		tabNum = Number(tabNum && tabNum[1]);
		if (typeof wpCookies && 0 === tabNum) {
			cookie = wpCookies.getHash('ip-geo-block-admin') || [];

			// Click event handler to show/hide form-table
			$('form').on('click', 'h3', function (event) {
				var title = $(this);
				title.parent().next().toggle();
				title.toggleClass('ip-geo-block-dropup').toggleClass('ip-geo-block-dropdown');
				cookie[title.closest('fieldset').data('ip-geo-block')] = title.hasClass('ip-geo-block-dropdown') ? 1 : 0;
				wpCookies.setHash('ip-geo-block-admin', cookie);
			});
		}

		// Make form style with fieldset and legend
		$('.form-table').each(function (index) {
			var $this = $(this),
			    title = $this.prev();
			if (title.prop('tagName').toLowerCase() === 'h3') {
				// Move title into the fieldset and wrap with legend
				$this.wrap('<fieldset data-ip-geo-block=' + index + ' class="ip-geo-block-field"></fieldset>')
				     .parent().prepend(title.wrap('<legend></legend>').parent());

				// Show/Hide form-table on tab 0
				if (typeof wpCookies && 0 === tabNum) {
					if ('undefined' === typeof cookie[index] || 'undefined' === cookie[index] || 1 == cookie[index]) {
						title.addClass('ip-geo-block-dropdown').parent().next().show();
					} else {
						title.addClass('ip-geo-block-dropup').parent().next().hide();
					}
				}
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

		// Validation logs
		$('#clear_logs').on('click', function (event) {
			confirm('Clear logs ?', function () {
				ajax_clear('logs', null);
			});
			return false;
		});

		// Manipulate DB table for validation logs
		$('#create_table').on('click', function (event) {
			confirm('Create table ?', function () {
				ajax_table('create_table');
			});
			return false;
		});
		$('#delete_table').on('click', function (event) {
			confirm('Delete table ?', function () {
				ajax_table('delete_table');
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
		$('select[name^="ip_geo_block_settings[validation]"]').on('change', function (event) {
			show_description(this);
		}).trigger('change');
	});
}(jQuery));