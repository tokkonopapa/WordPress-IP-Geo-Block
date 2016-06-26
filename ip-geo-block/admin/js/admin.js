/*jslint white: true */
/*!
 * Project: WordPress IP Geo Block
 * Copyright (c) 2015-2016 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
var ip_geo_block_time = new Date();

(function ($) {
	'use strict';

	function ID(selector, id) {
		var keys = {
			'.': 'ip-geo-block-',
			'#': 'ip-geo-block-',
			'@': 'ip_geo_block_settings_',
			'%': 'ip_geo_block_statistics_'
		};
		return id ? ('.' === selector ? '.' : '#') + keys[selector] + id : keys['#'] + selector;
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
		window.alert(sanitize(status + ' ' + msg));
	}

	function notice_html5() {
		warning('Notice:', 'This feature is available with HTML5 compliant browsers.');
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

	function ajax_post(id, request, callback) {
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
				loading(id, false);
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

	// Encode to prevent blocking before post ajax
	function base64_encode(str) {
		return window.btoa(str);
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
			warning('Error: ', event.target.error.code);
		};
		reader.readAsText(file);
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

			// Additional edge case
			var i = 'ip_geo_block_settings[providers][IPInfoDB]';
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
		// processing time for the browser's performance
		ip_geo_block_time = new Date() - ip_geo_block_time;

		// Get tab number and check wpCookies in wp-includes/js/utils.js
		var cookie = ('undefined' !== typeof wpCookies && wpCookies.getHash(ID('admin'))) || {},
		    maxTabs = 8, tabNo = /&tab=(\d)/.exec(window.location.href);
		tabNo = Number(tabNo && tabNo[1]);

		// Make form style with fieldset and legend
		var fieldset = $('<fieldset class="' + ID('field') + '"></fieldset>'),
		    legend = $('<legend></legend>');

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
				index += (tabNo ? maxTabs : 0);
				if ('undefined' === typeof cookie[index] || cookie[index]) { // 'undefined' or 'o'
					title.addClass(ID('dropdown')).parent().nextAll().show();
				} else {
					title.addClass(ID('dropup')).parent().nextAll().hide();
				}
			}
		});

		// Click event handler to show/hide form-table
		if (tabNo <= 1) {
			$('form').on('click', 'h2,h3', function (event) {
				var title = $(this),
				    index = title.closest('fieldset').data('ip-geo-block');

				// Show/Hide
				title.parent().nextAll().toggle();
				title.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));

				// Save cookie
				if ('undefined' !== typeof wpCookies) {
					cookie[index + (tabNo ? maxTabs : 0)] = title.hasClass(ID('dropdown')) ? 'o' : '';
					wpCookies.setHash(ID('admin'), cookie, new Date(Date.now() + 2592000000));
				}

				// redraw google chart
				if ($(ID('#', 'chart-countries')).length) {
					chart.drawChart();
				}

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
				$(ID('@', 'white_list')).closest('tr').toggle(this.value !== '1');
				$(ID('@', 'black_list')).closest('tr').toggle(this.value !== '0');
				return false;
			}).trigger('change');

			$(ID('@', 'public_matching_rule')).on('change', function () {
				$(ID('@', 'public_white_list')).closest('tr').toggle(this.value !== '1');
				$(ID('@', 'public_black_list')).closest('tr').toggle(this.value !== '0');
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
										$('#ip_geo_block_' + api + '_' + key).text(sanitize(data[key].message));
									}
								}
							}
						}
					}
				});

				return false;
			});

			// Name of base class
			var name = 'ip_geo_block_settings';

			// Show/Hide description
			$('select[name^="' + name + '"]').on('change', function (event) {
				var $this = $(this);
				show_description($this);

				// List of exceptions
				$this.nextAll('.' + name + '_exception').each(function (i, obj) {
					obj = $(obj);
					if ('0' !== $this.val()) {
						obj.removeClass('exceptions-disable');
					} else {
						obj.children('li').hide();
						obj.addClass('exceptions-disable');
						obj.removeClass(ID('dropdown')).addClass(ID('dropup'));
					}
				});

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

				json[id += '[signature]'] = base64_encode(json[id]);
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

				var file = event.target.files[0];
				if (file) {
					readfile(file, function (data) {
						var id = name + '[signature]';
						data = JSON.parse(data);
						data[id] = base64_encode(data[id]);
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
				confirm('Import settings ?', function () {
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
				confirm('Import settings ?', function () {
					ajax_post('pre-defined', {
						cmd: 'import-preferred'
					}, deserialize_json);
				});
				return false;
			});

			// Manipulate DB table for validation logs
			$(ID('@', 'create_table')).on('click', function (event) {
				confirm('Create table ?', function () {
					ajax_table('create-table');
				});
				return false;
			});

			$(ID('@', 'delete_table')).on('click', function (event) {
				confirm('Delete table ?', function () {
					ajax_table('delete-table');
				});
				return false;
			});

			// Submit
			$('#submit').on('click', function (event) {
				var elm = $(ID('@', 'signature'));
				elm.val(base64_encode(elm.val()));
				return true;
			});

			// Exceptions
			$('ul.' + name + '_exception dfn').on('click', function (event) {
				var $this = $(this).parent();
				$this.children('li').toggle();
				$this.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));
				return false;
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
			$(ID('%', 'clear_statistics')).on('click', function (event) {
				confirm('Clear statistics ?', function () {
					ajax_clear('statistics', null);
				});
				return false;
			});

			// Statistics
			$(ID('%', 'clear_cache')).on('click', function (event) {
				confirm('Clear cache ?', function () {
					ajax_clear('cache', null);
				});
				return false;
			});
			break;

		  /*----------------------------------------
		   * Search
		   *----------------------------------------*/
		  case 2:
			// Initialize map if exists
			$(ID('#', 'map')).each(function () {
				$(this).GmapRS();
			});

			// Search Geolocation
			$(ID('@', 'get_location')).on('click', function (event) {
				var ip = $(ID('@', 'ip_address')).val();
				if (ip) {
					ajax_post('loading', {
						cmd: 'search',
						ip: ip,
						which: $(ID('@', 'service')).val()
					}, function (data) {
						var key, info = '';
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

						$(ID('#', 'map')).GmapRS('addMarker', {
							latitude: data.latitude || 0,
							longitude: data.longitude || 0,
							title: ip,
							content: '<ul>' + info + '</ul>',
							show: true,
							zoom: 8
						});
					});
				}

				return false;
			});
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
					time: ip_geo_block_time
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
				});
			}

			// Validation logs
			$(ID('@', 'clear_logs')).on('click', function (event) {
				confirm('Clear logs ?', function () {
					ajax_clear('logs', null);
				});
				return false;
			});

			// Export / Import settings
			add_hidden_form('export-logs');

			// Export settings
			$(ID('#', 'export-logs')).on('click', function (event) {
				$(ID('#', 'export-form')).trigger('submit');
				return false;
			});
			break;
		}
	});
}(jQuery));