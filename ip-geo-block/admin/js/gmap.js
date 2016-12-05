/*!
 * Project: GmapRS - google map for WordPress IP Geo Block
 * Description: A really simple google map plugin based on jQuery-boilerplate.
 * Version: 0.2.4
 * Copyright (c) 2013-2016 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
// https://developers.google.com/maps/documentation/javascript/events?hl=en#auth-errors
function gm_authFailure() {
	'use strict';
	jQuery(window).trigger('ip-geo-block-gmap-error');
}

(function ($) {
	'use strict';
	$(function ($) {
		var e = "GmapRS",
			d = "plugin_" + e,
			b = {
				zoom: 2,
				latitude: 0,
				longitude: 0
			},
			i = google.maps,
			h = function (j) {
				this.o = $.extend({}, b);
				this.q = [];
			};
		h.prototype = {
			init: function (j) {
				$.extend(this.o, j);
				this.c = new i.LatLng(this.o.latitude, this.o.longitude);
				this.m = new i.Map(this.e.get(0), {
					zoom: this.o.zoom,
					center: this.c,
					mapTypeId: i.MapTypeId.ROADMAP
				});
			},
			destroy: function () {
				this.deleteMarkers();
				this.e.data(d, null);
			},
			setCenter: function () {
				if (arguments.length >= 2) {
					var j = new i.LatLng((this.o.latitude = arguments[0]), (this.o.longitude = arguments[1]));
					delete this.c;
					this.c = j;
				}
				this.m.setCenter(this.c);
				return this.e;
			},
			setZoom: function (j) {
				this.m.setZoom(j || this.o.zoom);
				return this.e;
			},
			showMarker: function (l, k) {
				var j = this.q[l];
				if (j && j.w) {
					(false === k) ? j.w.close() : j.w.open(this.m, j.m);
				}
			},
			addMarker: function (l) {
				var m, j, k;
				m = new i.LatLng(l.latitude || this.o.latitude, l.longitude || this.o.longitude);
				j = new i.Marker({
					position: m,
					map: this.m,
					title: l.title || ""
				});
				if (l.content) {
					k = new i.InfoWindow({
						content: l.content
					});
					i.event.addListener(j, "click", function () {
						k.open(j.getMap(), j);
					});
				}
				this.q.push({
					p: m,
					w: k,
					m: j
				});
				this.m.setCenter(m);
				this.m.setZoom(l.zoom);
				if (l.show) {
					this.showMarker(this.q.length - 1);
				}
				return this.e;
			},
			deleteMarkers: function () {
				var j, k;
				for (j in this.q) {
					if (this.q.hasOwnProperty(j)) {
						k = this.q[j];
						k.m.setMap(null);
					}
				}
				this.q.length = 0;
				return this.e;
			}
		};
		$.fn[e] = function (k) {
			var l, j;
			if (!(this.data(d) instanceof h)) {
				this.data(d, new h(this));
			}
			j = this.data(d);
			j.e = this;
			if (typeof k === "undefined" || typeof k === "object") {
				if (typeof j.init === "function") {
					j.init(k);
				}
			} else {
				if (typeof k === "string" && typeof j[k] === "function") {
					l = Array.prototype.slice.call(arguments, 1);
					return j[k].apply(j, l);
				} else {
					$.error("Method " + k + " does not exist." + e);
				}
			}
		};
	});
}(jQuery));