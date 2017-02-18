/*jslint white: true */
/*!
 * Project: WordPress IP Geo Block
 * Copyright (c) 2015-2016 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
var ip_geo_block_time = new Date();

(function ($, window, document) {
	'use strict';

	function ID(selector, id) {
		var keys = {
			'.': '.ip-geo-block-',
			'#': '#ip-geo-block-',
			'@': '#ip_geo_block_settings_',
			'$': 'ip-geo-block-',
			'%': 'ip_geo_block_'
		};
		return 'undefined' !== typeof id ? keys[selector] + id : keys.$ + selector;
	}

	function sanitize(str) {
		return str ? str.toString().replace(/[&<>"']/g, function (match) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;'
			}[match];
		}) : '';
	}

	function loading(id, flag) {
		if (flag) {
			$(ID('#', id)).addClass(ID('loading'));
		} else {
			$(ID('#', id)).removeClass(ID('loading'));
		}
	}

	function confirm(msg, callback) {
		if (window.confirm(sanitize(msg))) {
			callback();
		}
	}

	function warning(status, msg) {
		window.alert(status ? sanitize(status + ': ' + msg) : sanitize(msg));
	}

	function notice_html5() {
		warning(null, IP_GEO_BLOCK.msg[6]);
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

	function ajax_post(id, request, callback, objs) {
		if (id) {
			loading(id, true);
		}

		request.action = IP_GEO_BLOCK.action;
		request.nonce = IP_GEO_BLOCK.nonce;

		$.post(IP_GEO_BLOCK.url, request)

		.done(function (data, textStatus, jqXHR) {
			callback(data);
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			warning(textStatus, jqXHR.responseText);
		})

		.always(function () {
			if (id) {
				if (objs) {
					$.when.apply($, objs).then(function () {
						loading(id, false);
					});
				} else {
					loading(id, false);
				}
			}
		});
	}

	// Clear statistics, cache, logs
	function ajax_clear(cmd, type) {
		ajax_post(cmd, {
			cmd: 'clear-' + cmd,
			which: type
		}, function (data) {
			redirect(data.page, data.tab);
		});
	}

	// Manipulate DB table for validation logs
	function ajax_table(cmd) {
		ajax_post(cmd, {
			cmd: cmd
		}, function (data) {
			redirect(data.page, data.tab);
		});
	}

	// Show/Hide description of WP-ZEP
	function show_description(select) {
		var data, desc = ID('.', 'desc');
		select.next(desc).empty();
		data = select.children('option:selected').data('desc');
		if (data) {
			select.next(desc).html($.parseHTML(data)); // jQuery 1.8+
		}
	}

	// Show/Hide folding list
	function show_folding_list($this, element, field, mask) {
		var stat = false;
		stat |= (0 === $this.prop('type').indexOf('checkbox') && $this.is(':checked'));
		stat |= (0 === $this.prop('type').indexOf('select'  ) && '0' !== $this.val());

		element.nextAll('.' + field + '_folding').each(function (i, obj) {
			obj = $(obj);

			// completely hide
			// obj.css('display', mask ? 'block' : 'none');

			// fold the contents
			if (stat && mask) {
				obj.removeClass('folding-disable');
			} else {
				obj.children('li').hide();
				obj.addClass('folding-disable');
				obj.removeClass(ID('dropdown')).addClass(ID('dropup'));
			}
		});
	}

	// Encode/Decode to prevent blocking before post ajax
	function base64_encode(str) {
		return window.btoa(str);
	}

	function base64_decode(str) {
		return window.atob(str);
	}

	// Equivalent for PHP's str_rot13
	// @link http://phpjs.org/functions/str_rot13/
	function str_rot13(str) {
		return String(str).replace(/[a-z]/gi, function (s) {
			return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13)); //'
		});
	}

	// Wrapper for encode/decode strings
	function encode_str(str) {
		return base64_encode(str_rot13(str));
	}

	function decode_str(str) {
		return str_rot13(base64_decode(str));
	}

	// File Reader
	function readfile(file, callback) {
		var reader = new FileReader();
		reader.onload = function (event) {
			if (callback) {
				callback(event.target.result);
			}
		};
		reader.onerror = function (event) {
			warning('Error', event.target.error.code);
		};
		reader.readAsText(file);
	}

	// Enable / Disable at front-end target settings
	function set_front_end($this) {
		var field   = ID('%', 'settings'),
		    checked = $this.is(':checked'),
		    select  = $(ID('@', 'public_target_rule')),
		    parent  = $this.closest('tr').nextAll('tr');

		// Enable / Disable descendent items
		parent.find('[name^="' + field + '"]').prop('disabled', !checked);

		// Enable / Disable description
		parent.find(ID('.', 'desc')).css('opacity', checked ? 1.0 : 0.5);

		// Show / Hide validation target
		show_folding_list($this, select, field, '1' === select.val() ? true : false);
	}

	/**
	 * jQuery deserialize plugin based on https://gist.github.com/nissuk/835256
	 *
	 * usage: $('form').deserialize({'name':'value', ...});
	 */
	$.fn.deserialize = function (json, options) {
		return this.each(function () {
			var key, name, value,
			    self = this,
			    data = {};

			for (key in json) {
				if(json.hasOwnProperty(key)) {
					name = decodeURIComponent(key);
					value = decodeURIComponent(json[key]);

					if (!(name in data)) { // !data.hasOwnProperty(name)
						data[name] = [];
					}

					data[name].push(value);
				}
			}

			$.each(data, function (name, val) {
				$('[name="' + name + '"]:input', self).val(val);
			});
		});
	};

	function deserialize_json(json) {
		if (json) {
			// Set fields on form
			if ('string' === typeof json) {
				json = JSON.parse(json);
			}

			// deserialize to the form
			$(ID('#', 'import')).closest('form').deserialize(json);

			// Help text
			$.each(['matching_rule', 'validation_login', 'validation_plugins', 'validation_themes'], function (i, key) {
				$(ID('@', key)).trigger('change');
			});

			// Public facing pages
			set_front_end($(ID('@', 'validation_public')));

			// Additional edge case
			var i = ID('%', 'settings[providers][IPInfoDB]');
			$(ID('@', 'providers_IPInfoDB')).prop('checked', json[i] ? true : false);
		}
	}

	// google chart
	var chart = {
		self: this,
		drawChart: function () {
			this.drawPie();
			this.drawLine();
		},

		// Pie Chart
		dataPie: null,
		viewPie: null,
		drawPie: function () {
			if (!self.dataPie) {
				self.dataPie = new google.visualization.DataTable();
				self.dataPie.addColumn('string', 'Country');
				self.dataPie.addColumn('number', 'Requests');
				var value;
				$(ID('#', 'countries li')).each(function () {
					value = $(this).text().split(':');
					self.dataPie.addRow([value[0] || '', Number(value[1])]);
				});
			}
			if (!self.viewPie) {
				self.viewPie = new google.visualization.PieChart(
					document.getElementById(ID('chart-countries'))
				);
			}
			if ($(ID('#', 'chart-countries')).width()) {
				self.viewPie.draw(self.dataPie, {
					backgroundColor: '#f1f1f1',
					chartArea: {
						left: 0,
						top: '5%',
						width: '100%',
						height: '90%'
					},
					sliceVisibilityThreshold: 0.015
				});
			}
		},

		// Line Chart
		dataLine: null,
		viewLine: null,
		drawLine: function () {
			if (!self.dataLine) {
				self.dataLine = new google.visualization.DataTable();
				self.dataLine.addColumn('date', 'Date');
				self.dataLine.addColumn('number', 'comment');
				self.dataLine.addColumn('number', 'xmlrpc');
				self.dataLine.addColumn('number', 'login');
				self.dataLine.addColumn('number', 'admin');
				self.dataLine.addColumn('number', 'public');
				var i, j, k, m, n, cells, arr = [],
				    tr = $(ID('#', 'targets tr'));
				for (m = tr.length, i = 0; i < m; i++) {
					arr[i] = [];
					cells = tr.eq(i).children();
					for (n = cells.length, j = 0; j < n; j++) {
						k = cells.eq(j).text();
						arr[i].push(j ? Number(k) : new Date(k));
					}
				}
				self.dataLine.addRows(arr);
			}
			if (!self.viewLine) {
				self.viewLine = new google.visualization.LineChart(
					document.getElementById(ID('chart-daily'))
				);
			}
			var w = $(ID('#', 'chart-daily')).width();
			if (w) {
				w = w > 320 ? true : false;
				self.viewLine.draw(self.dataLine, {
					backgroundColor: '#f1f1f1',
					legend: { position: 'bottom' },
					hAxis: { format: 'MM/dd' },
					vAxis: { textPosition: (w ? 'out' : 'in') },
					chartArea: {
						left: (w ? '10%' : 0),
						top: '5%',
						width: '100%',
						height: '75%'
					}
				});
			}
		}
	};

	// google chart
	function drawChart() {
		if ($(ID('#', 'chart-countries')).length) {
			chart.drawChart();
		}
	}

	// Load / Save cookie using wpCookies in wp-includes/js/utils.js
	function loadCookie(id) {
		return ('undefined' !== typeof wpCookies && wpCookies.getHash(ID('$', id))) || {};
	}

	// setHash( name, value, expires, path, domain, secure )
	function saveCookie(id, cookie) {
		if ('undefined' !== typeof wpCookies) {
			var path = 'undefined' !== typeof IP_GEO_BLOCK_AUTH ? IP_GEO_BLOCK_AUTH.home + IP_GEO_BLOCK_AUTH.admin : '';
			wpCookies.setHash(ID('$', id), cookie, new Date(Date.now() + 2592000000), path);
		}
	}

	// Click event handler to show/hide form-table
	function toggleSection(title, id, cookie) {
		var index = title.closest('fieldset').data('ip-geo-block');

		// Show/Hide
		title.parent().nextAll().toggle();
		title.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));

		cookie[index] = title.hasClass(ID('dropdown')) ? 'o' : 'x';
		saveCookie(id, cookie); // Save cookie

		// redraw google chart
		drawChart();
	}

	// form for export / import
	function add_hidden_form(cmd) {
		$('body').append(
			'<div style="display:none">' +
				'<form method="POST" id="' + ID('export-form') + '" action="' + IP_GEO_BLOCK.url.replace('ajax.php', 'post.php') + '">' +
					'<input type="hidden" name="action" value="' + IP_GEO_BLOCK.action + '" />' +
					'<input type="hidden" name="nonce" value="' + IP_GEO_BLOCK.nonce + '" />' +
					'<input type="hidden" name="cmd" value="' + cmd + '" />' +
					'<input type="hidden" name="data" value="" id="' + ID('export-data') + '"/>' +
					'<input type="submit" value="submit" />' +
				'</form>' +
				'<input type="file" name="settings" id="' + ID('file-dialog') + '" />' +
			'</div>'
		);
	}

	$(function () {
		// Make form style with fieldset and legend
		var fieldset = $('<fieldset class="' + ID('field') + '"></fieldset>'),
		    legend = $('<legend></legend>'),

		// Get tab number and cookie
		tabNo = Number(IP_GEO_BLOCK.tab) || 0,
		cookie = loadCookie(tabNo);

		$('.form-table').each(function (index) {
			var $this = $(this),
			    title = $this.prevAll('h2,h3:first'),
			    notes = title.nextUntil($this);

			// Move title into the fieldset and wrap with legend
			$this.wrap(fieldset).parent() // fieldset itself
			     .attr('id', ID('settings-' + index))
			     .data('ip-geo-block', index)
			     .prepend(title.wrap(legend).parent());
			notes.insertBefore($this);

			// Initialize show/hide form-table on tab 0, 1
			if (tabNo <= 1) {
				if ('undefined' === typeof cookie[index] || 'o' === cookie[index]) { // 'undefined', 'x' or 'o'
					title.addClass(ID('dropdown')).parent().nextAll().show();
				} else {
					title.addClass(ID('dropup')).parent().nextAll().hide();
				}
			}
		});

		// Click event handler to show/hide form-table
		if (tabNo <= 1) {
			$('form').on('click', 'h2,h3', function (event) {
				toggleSection($(this), tabNo, cookie);
				return false;
			});

			// Toggle all
			$(ID('#', 'toggle-sections')).on('click', function (event) {
				var $this, n = 0,
				    id = [ID('dropdown'), ID('dropup')],
				    title = $(ID('.', 'field')).find('h2,h3');

				title.each(function (i) {
					n += $(this).hasClass(id[0]);
				});

				// update cookie
				title.each(function (i) {
					$this = $(this);
					$this.parent().nextAll().toggle(n ? false : true);
					$this.removeClass(id.join(' '))
					     .addClass(n ? id[1] : id[0]);
					cookie[i] = n ? 'x' : 'o';
				});

				// Save cookie
				saveCookie(tabNo, cookie);

				// redraw google chart
				drawChart();

				return false;
			});
		}

		// Inhibit to submit by return key
		$(ID('#', 'inhibit')).on('submit', function () {
			return false;
		});

		// Register event handler at specific tab
		switch (tabNo) {
		  /*----------------------------------------
		   * Settings
		   *----------------------------------------*/
		  case 0:
			// Scan your country code
			$(ID('#', 'scan-code')).on('click', function (event) {
				var parent = $(this).parent();
				ajax_post('scanning', {
					cmd: 'scan-code'
				}, function (data) {
					if (!parent.children('ul').length) {
						parent.append('<ul id="' + ID('code-list') + '"></ul>');
					}
					parent = parent.children('ul').empty();

					var key, val;
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							key = sanitize(key);
							if ('string' === typeof data[key]) {
								val = sanitize(data[key]);
							} else {
								val = sanitize(data[key].code);
								key = '<abbr title="' + sanitize(data[key].type) + '">' + key + '</abbr>';
							}
							parent.append('<li>' + key + ' : <span class="' + ID('notice') + '">' + val + '</span></li>');
						}
					}
					parent.show('slow');
				});

				return false;
			});

			// Matching rule
			$(ID('@', 'matching_rule')).on('change', function () {
				$(ID('@', 'white_list')).closest('tr').toggle(this.value === '0');
				$(ID('@', 'black_list')).closest('tr').toggle(this.value === '1');
				return false;
			}).trigger('change');

			$(ID('@', 'public_matching_rule')).on('change', function () {
				$(ID('@', 'public_white_list')).closest('tr').toggle(this.value === '0');
				$(ID('@', 'public_black_list')).closest('tr').toggle(this.value === '1');
				return false;
			}).trigger('change');

			// Update local database
			$(ID('@', 'update')).on('click', function (event) {
				ajax_post('download', {
					cmd: 'download'
				}, function (res) {
					var api, key, data;
					for (api in res) {
						if (res.hasOwnProperty(api)) {
							data = res[api];
							for (key in data) { // key: ipv4, ipv6
								if (data.hasOwnProperty(key)) {
									key = sanitize(key);
									if (data[key].filename) {
										$(ID('@', api + '_' + key + '_path')).val(sanitize(data[key].filename));
									}
									if (data[key].message) {
										$(ID('#', api + '-' + key)).text(sanitize(data[key].message));
									}
								}
							}
						}
					}
				});

				return false;
			});

			// Name of base class
			var name = ID('%', 'settings');

			// Show/Hide folding list at Login form
			$(ID('@', 'validation_login')).on('change', function (event) {
				var $this = $(this);
				show_folding_list($this, $this, name, true);
				return false;
			}).trigger('change');

			// Show/Hide description
			$('select[name^="' + name + '"]').on('change', function (event) {
				var $this = $(this);
				show_description($this);
				show_folding_list($this, $this, name, true);
				return false;
			}).trigger('change');

			// Enable / Disable for Public facing pages
			$(ID('@', 'validation_public')).on('change', function (event) {
				set_front_end($(this));
				return false;
			}).trigger('change');

			// Export / Import settings
			add_hidden_form('validate');

			// Export settings
			$(ID('#', 'export')).on('click', function (event) {
				if ('undefined' === typeof JSON) {
					notice_html5();
					return false;
				}

				var id = name, json = {};
				$.each($(this).closest('form').serializeArray(), function (i, obj) {
					if (-1 !== obj.name.indexOf(id)) {
						json[obj.name] = obj.value;
					}
				});

				json[id += '[signature]'] = encode_str(json[id]);
				$(ID('#', 'export-data')).val(JSON.stringify(json));
				$(ID('#', 'export-form')).trigger('submit');

				return false;
			});

			// Import settings
			$(ID('#', 'file-dialog')).on('change', function (event) {
				if ('undefined' === typeof FileReader) {
					notice_html5();
					return false;
				}

				var id, file = event.target.files[0];
				if (file) {
					readfile(file, function (data) {
						data = JSON.parse(data);
						id = name + '[signature]';
						if ('undefined' !== typeof data[id]) {
							data[id] = encode_str(data[id]);
						}
						ajax_post('export-import', {
							cmd: 'validate',
							data: JSON.stringify(data)
						}, deserialize_json);
					});
				}

				return false;
			});

			$(ID('#', 'import')).on('click', function (event) {
				$(ID('#', 'file-dialog')).trigger('click');
				return false;
			});

			// Import pre-defined settings
			$(ID('#', 'default')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[0], function () {
					ajax_post('pre-defined', {
						cmd: 'import-default'
					}, deserialize_json);
					/*}, function (json) {
						deserialize_json(json);
						$('#submit').trigger('click');
					});*/
				});
				return false;
			});

			$(ID('#', 'preferred')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[0], function () {
					ajax_post('pre-defined', {
						cmd: 'import-preferred'
					}, deserialize_json);
				});
				return false;
			});

			// Manipulate DB table for validation logs
			$(ID('@', 'create_table')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[1], function () {
					ajax_table('create-table');
				});
				return false;
			});

			$(ID('@', 'delete_table')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[2], function () {
					ajax_table('delete-table');
				});
				return false;
			});

			// Folding list
			$('ul.' + name + '_folding dfn').on('click', function (event) {
				var $this = $(this).parent();
				$this.children('li').toggle();
				$this.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));
				return false;
			});

			// Decode
			$(ID('#', 'decode')).on('click', function (event) {
				var elm = $(ID('@', 'signature')),
				    str = elm.val();
				if (str.search(/,/) === -1) {
					elm.val(decode_str(str));
				} else {
					elm.val(encode_str(str));
				}
				return false;
			});

			// Response message and Redirect URL
			$(ID('@', 'response_code')).on('change', function (event) {
				var res = parseInt($(this).val() / 100, 10),
				    elm = $(this).closest('tr').nextAll('tr');
				if (res <= 3) { // 2xx, 3xx
					elm.each(function (index) {
						if      (0 === index) { $(this).show(); } // redirect_uri
						else if (1 === index) { $(this).hide(); } // response_msg
					});
				}
				else { // 4xx, 5xx
					elm.each(function (index) {
						if      (0 === index) { $(this).hide(); } // redirect_uri
						else if (1 === index) { $(this).show(); } // response_msg
					});
				}
			}).trigger('change');

			// Show WordPress installation info
			$(ID('#', 'show-info')).on('click', function (event) {
				$(ID('#', 'wp-info')).empty();
				ajax_post('wp-info', {
					cmd: 'show-info'
				}, function (data) {
					var key, val, res = [];
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							for (val in data[key]) {
								if (data[key].hasOwnProperty(val)) {
									res.push('- ' + val + ' ' + data[key][val]);
								}
							}
						}
					}

					// response should be escaped at server side
					$(ID('#', 'wp-info')).html('<textarea rows="' + res.length + '">' + /*sanitize*/(res.join("\n")) + '</textarea>').find('textarea').select();
					return false;
				});
			});

			// Submit
			$('#submit').on('click', function (event) {
				var elm = $(ID('@', 'signature')),
				    str = elm.val();
				if (str.search(/,/) !== -1) {
					elm.val(encode_str(str));
				}
				return true;
			});
			break;

		  /*----------------------------------------
		   * Statistics
		   *----------------------------------------*/
		  case 1:
			// https://developers.google.com/loader/#Dynamic
			if ($(ID('#', 'chart-countries')).length && 'object' === typeof google) {
				google.load('visualization', '1', {
					packages: ['corechart'],
					callback: function () {
						chart.drawChart();
					}
				});
			}

			// Statistics
			$(ID('@', 'clear_statistics')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[3], function () {
					ajax_clear('statistics', null);
				});
				return false;
			});

			// Statistics
			$(ID('@', 'clear_cache')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[4], function () {
					ajax_clear('cache', null);
				});
				return false;
			});
			break;

		  /*----------------------------------------
		   * Search
		   *----------------------------------------*/
		  case 2:
			// Google Maps API error
			$(window).on(ID('gmap-error'), function () {
				ajax_post(null, { cmd: 'gmap-error' }, function (data) {
					redirect(data.page, data.tab);
				});
			});

			// Initialize map if exists
			var map = $(ID('#', 'map'));
			if ('object' === typeof google) {
				// Initialize map if exists
				map.each(function () {
					$(this).GmapRS();
				});
			} else {
				map.each(function () {
					$(this).empty().html(
						'<iframe src="//maps.google.com/maps?output=embed" frameborder="0" style="width:100%; height:400px; border:0" allowfullscreen></iframe>'
					);
				});
			}

			// Search Geolocation
			$(ID('@', 'get_location')).on('click', function (event) {
				var whois = $(ID('#', 'whois')),
				    ip = $(ID('@', 'ip_address')).val();

				if (ip) {
					whois.hide().empty();

					// Get whois data
					var obj = $.whois(ip, function (data) {
						var i, str = '';
						for (i = 0; i < data.length; i++) {
							str +=
							'<tr>' +
								'<td>' + data[i].name  + '</td>' +
								'<td>' + data[i].value + '</td>' +
							'</tr>';
						}

						whois.html(
							'<fieldset class="' + ID('field') + '">' +
							'<legend><h2 id="' + ID('whois-title') + '" class="' + ID('dropdown') + '">Whois</h2></legend>' +
							'<table class="' + ID('table') + '">' + str + '</table>' +
							'<fieldset>'
						).fadeIn('slow');

						$(ID('#', 'whois-title')).on('click', function (event) {
							var $this = $(this);
							$this.parent().nextAll().toggle();
							$this.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));
							return false;
						});
					});

					// Show map
					ajax_post('loading', {
						cmd: 'search',
						ip: ip,
						which: $(ID('@', 'service')).val()
					}, function (data) {
						var key, info = '',
						    latitude = sanitize(data.latitude || '0'),
						    longitude = sanitize(data.longitude || '0'),
						    zoom = (data.latitude || data.longitude) ? 8 : 2;

						for (key in data) {
							if (data.hasOwnProperty(key)) {
								key = sanitize(key);
								info +=
									'<li>' +
										'<span class="' + ID('title' ) + '">' + key + ' : </span>' +
										'<span class="' + ID('result') + '">' + sanitize(data[key]) + '</span>' +
									'</li>';
							}
						}

						if ('object' === typeof google) {
							map.GmapRS('addMarker', {
								latitude: latitude,
								longitude: longitude,
								title: ip,
								content: '<ul>' + info + '</ul>',
								show: true,
								zoom: zoom
							});
						} else {
							map.css({
								height: '600px',
								backgroundColor: 'transparent'
							}).empty().html(
								'<ul style="margin-top:0; margin-left:1em;">' +
									'<li>' +
										'<span class="' + ID('title' ) + '">' + 'IP address' + ' : </span>' +
										'<span class="' + ID('result') + '">' + sanitize(ip) + '</span>' +
									'</li>' +
									info +
									/*'<li>' +
										'<span class="' + ID('title' ) + '">' + 'show map' + ' : </span>' +
										'<span class="' + ID('result') + '">' + '<a href="//maps.google.com/maps?q=' + latitude + ',' + longitude + '">Click here</a>' + '</span>' +
									'</li>' +*/
								'</ul>'
								+ '<iframe src="//maps.google.com/maps?q=' + latitude + ',' + longitude + '&z=' + zoom + '&output=embed" frameborder="0" style="width:100%; height:400px; border:0" allowfullscreen></iframe>'
								/*+ '<iframe src="//www.google.com/maps/embed/v1/place?key=...&q=%20&center=' + latitude + ',' + longitude + '&zoom=' + zoom + '" frameborder="0" style="width:100%; height:400px; border:0" allowfullscreen></iframe>'*/
							);
						}
					}, [obj]);
				}

				return false;
			});

			// Preset IP address
			if ($(ID('@', 'ip_address')).val()) {
				$(ID('@', 'get_location')).trigger('click');
			}
			break;

		  /*----------------------------------------
		   * Logs
		   *----------------------------------------*/
		  case 4:
			// Kick-off footable
			if ($(ID('.', 'log')).hide().length) {
				ajax_post('logs', {
					cmd: 'restore',
					which: null,
					time: new Date() - ip_geo_block_time
				}, function (data) {
					var key;
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							key = sanitize(key); // data has been already sanitized
//							$(ID('#', 'log-' + key)).html($.parseHTML(data[key])); // jQuery 1.8+
							$(ID('#', 'log-' + key)).html(data[key]);
						}
					}

					if (typeof $.fn.footable === 'function') {
						$(ID('.', 'log')).fadeIn('slow').footable();
					}

					// Jump to search tab with opening new window
					$('tbody[id^="' + ID('$', 'log-') + '"]').on('click', 'a', function (event) {
						window.open(window.location.href.replace(/tab=\d/, 'tab=2') + '&ip=' + $(this).text().replace(/[^\w\.\:\*]/, ''));
						return false;
					});
				});
			}

			// Clear filter logs
			$(ID('#', 'reset-filter')).on('click', function (event) {
				$('.footable').trigger('footable_clear_filter');
				return false;
			});

			// Validation logs
			$(ID('@', 'clear_logs')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[5], function () {
					ajax_clear('logs', null);
				});
				return false;
			});

			// Export / Import settings
			add_hidden_form('export-logs');

			// Export logs
			$(ID('#', 'export-logs')).on('click', function (event) {
				$(ID('#', 'export-form')).trigger('submit');
				return false;
			});
			break;
		}
	});
}(jQuery, window, document));