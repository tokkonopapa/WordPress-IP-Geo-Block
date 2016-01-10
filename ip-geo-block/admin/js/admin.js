/*jslint white: true */
/*
 * Project: WordPress IP Geo Block
 * Copyright (c) 2015 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
var ip_geo_block_time = new Date();

(function ($) {
	'use strict';

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
			$('#ip-geo-block-' + id).addClass('ip-geo-block-loading');
		} else {
			$('#ip-geo-block-' + id).removeClass('ip-geo-block-loading');
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
		var data, desc = '.ip_geo_block_settings_desc';
		select = $(select);
		select.next(desc).empty();
		if (data = select.children('option:selected').data('desc')) {
			select.next(desc).html($.parseHTML(data)); // jQuery 1.8+
		}
	}

	// google chart
	var chart = {
		self: this,
		drawChart: function () {
			this.drawPie();
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
				$('#ip-geo-block-countries li').each(function () {
					value = $(this).text().split(':');
					self.dataPie.addRow([value[0] || '', Number(value[1])]);
				});
			}
			if (!self.viewPie) {
				self.viewPie = new google.visualization.PieChart(
					document.getElementById('ip-geo-block-chart-countries')
				);
			}
			if ($('#ip-geo-block-chart-countries').width()) {
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
		}
	};

	$(function () {
		// processing time for the browser's performance
		ip_geo_block_time = new Date() - ip_geo_block_time;

		// Get tab number and check wpCookies in wp-includes/js/utils.js
		var cookie = ('undefined' !== typeof wpCookies && wpCookies.getHash('ip-geo-block-admin')) || {},
		    tabNum = /&tab=(\d)/.exec(window.location.href);
		tabNum = Number(tabNum && tabNum[1]);

		// Make form style with fieldset and legend
		var fieldset = $('<fieldset class="ip-geo-block-field"></fieldset>'),
		    legend = $('<legend></legend>');

		$('.form-table').each(function (index) {
			var $this = $(this),
			    title = $this.prevAll('h2,h3:first'),
			    notes = title.nextUntil($this);

			// Move title into the fieldset and wrap with legend
			$this.wrap(fieldset).parent() // fieldset itself
			     .attr('id', 'ip-geo-block-settings-' + index)
			     .data('ip-geo-block', index)
			     .prepend(title.wrap(legend).parent());
			notes.insertBefore($this);

			// Initialize show/hide form-table on tab 0, 1
			if (tabNum <= 1) {
				index += (tabNum ? 8 : 0);
				if ('undefined' === typeof cookie[index] || cookie[index]) { // 'undefined' or 'o'
					title.addClass('ip-geo-block-dropdown').parent().nextAll().show();
				} else {
					title.addClass('ip-geo-block-dropup').parent().nextAll().hide();
				}
			}
		});

		// Click event handler to show/hide form-table
		if (tabNum <= 1) {
			$('form').on('click', 'h2,h3', function (event) {
				var title = $(this),
				    index = title.closest('fieldset').data('ip-geo-block');

				// Show/Hide
				title.parent().nextAll().toggle();
				title.toggleClass('ip-geo-block-dropup').toggleClass('ip-geo-block-dropdown');

				// Save cookie
				cookie[index + (tabNum ? 8: 0)] = title.hasClass('ip-geo-block-dropdown') ? 'o' : '';
				wpCookies.setHash('ip-geo-block-admin', cookie);

				// redraw google chart
				if ($('#ip-geo-block-chart-countries').length) {
					chart.drawChart();
				}

				return false;
			});
		}

		// Inhibit to submit by return key
		$('#ip-geo-block-inhibit').on('submit', function () {
			return false;
		});

		// Register event handler at specific tab
		switch (tabNum) {
		  case 0:
			// Scan your country code
			$('#ip-geo-block-scan-code').on('click', function (event) {
				var parent = $(this).parent();
				ajax_post('scanning', {
					cmd: 'scan-code'
				}, function (data) {
					if (!parent.children('ul').length) {
						parent.append('<ul id="ip-geo-block-code-list"></ul>');
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
							parent.append('<li>' + key + ' : <span class="ip-geo-block-notice">' + val + '</span></li>');
						}
					}
					parent.show('slow');
				});

				return false;
			});

			// Matching rule
			$('#ip_geo_block_settings_matching_rule').on('change', function () {
				$('#ip_geo_block_settings_white_list').closest('tr').toggle(this.value !== '1');
				$('#ip_geo_block_settings_black_list').closest('tr').toggle(this.value !== '0');
				return false;
			}).trigger('change');

			// Update local database
			$('#update').on('click', function (event) {
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
										$('#ip_geo_block_settings_' + api + '_' + key + '_path').val(sanitize(data[key].filename));
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

			// Show/Hide description of WP-ZEP
			$('select[name^="ip_geo_block_settings[validation]"]').on('change', function (event) {
				show_description(this);
				return false;
			}).trigger('change');

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
			break;

		  case 1:
			// https://developers.google.com/loader/#Dynamic
			if ($('#ip-geo-block-chart-countries').length && 'object' === typeof google) {
				google.load('visualization', '1', {
					packages: ['corechart'],
					callback: function () {
						chart.drawChart();
					}
				});
			}

			// Show/Hide the details of Block by country
			$('#show-hide-details').on('click', function (event) {
				$('#ip-geo-block-countries').toggle();
				return false;
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
			break;

		  case 2:
			// Initialize map if exists
			$('#ip-geo-block-map').each(function () {
				$(this).GmapRS();
			});

			// Search Geolocation
			$('#get_location').on('click', function (event) {
				var ip = $('#ip_geo_block_settings_ip_address').val();
				if (ip) {
					ajax_post('loading', {
						cmd: 'search',
						ip: ip,
						which: $('#ip_geo_block_settings_service').val()
					}, function (data) {
						var key, info = '';
						for (key in data) {
							if (data.hasOwnProperty(key)) {
								key = sanitize(key);
								info +=
									'<li>' +
										'<span class="ip-geo-block-title">' + key + ' : </span>' +
										'<span class="ip-geo-block-result">' + sanitize(data[key]) + '</span>' +
									'</li>';
							}
						}

						$('#ip-geo-block-map').GmapRS('addMarker', {
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

		  case 4:
			// Kick-off footable
			if ($('.ip-geo-block-log').hide().length) {
				ajax_post('logs', {
					cmd: 'restore',
					which: null,
					time: ip_geo_block_time
				}, function (data) {
					var key;
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							key = sanitize(key); // data has been already sanitized
//							$('#ip-geo-block-log-' + key).html($.parseHTML(data[key])); // jQuery 1.8+
							$('#ip-geo-block-log-' + key).html(data[key]);
						}
					}

					if (typeof $.fn.footable === 'function') {
						$('.ip-geo-block-log').fadeIn('slow').footable();
					}
				});
			}

			// Validation logs
			$('#clear_logs').on('click', function (event) {
				confirm('Clear logs ?', function () {
					ajax_clear('logs', null);
				});
				return false;
			});
			break;
		}
	});
}(jQuery));