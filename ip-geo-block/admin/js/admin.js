/*jslint white: true */
/*!
 * Project: WordPress IP Geo Block
 * Copyright (c) 2015-2017 tokkonopapa (tokkonopapa@yahoo.com)
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
			'%': 'ip_geo_block_',
			'!': 'ip_geo_block_settings_'
		};
		return 'undefined' !== typeof id ? keys[selector] + id : keys.$ + selector;
	}

	function escapeHTML(str) {
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
		if (window.confirm(escapeHTML(msg))) {
			callback();
		}
	}

	function warning(status, msg) {
		window.alert(status ? escapeHTML(status + ': ' + msg) : escapeHTML(msg));
	}

	function notice_html5() {
		warning(null, IP_GEO_BLOCK.msg[6]);
	}

	function redirect(page, tab) {
		if (-1 !== location.href.indexOf(page)) {
			window.location = escapeHTML(page) + (tab ? '&' + escapeHTML(tab) : '');
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

	// Prevent event propergation
	function stopPropergation(event) {
		event.stopImmediatePropagation();
		return false;
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

	// Fold the contents
	function fold_elements(obj, stat) { // obj: ul object
		if (stat) {
			obj.removeClass('folding-disable');
		} else {
			obj.children('li,a').hide();
			obj.addClass('folding-disable');
			obj.removeClass(ID('dropdown')).addClass(ID('dropup'));
		}
	}

	// Show/Hide folding list
	function show_folding_list($this, element, mask) {
		var stat = (0 === $this.prop('type').indexOf('checkbox') && $this.is(':checked')) ||
		           (0 === $this.prop('type').indexOf('select'  ) && '0' !== $this.val());

		element.nextAll(ID('.', 'settings-folding')).each(function (i, obj) {
			fold_elements($(obj), stat && mask);
		});
	}

	// Show / Hide Exceptions
	function show_folding_ajax(elem) {
		var id = ID('@', 'validation_ajax_');
		fold_elements(
			elem.closest('ul').next(),
			$(id + '1').is(':checked') || $(id + '2').is(':checked')
		);
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
		var checked = $this.is(':checked'),
		    select  = $(ID('@', 'public_target_rule')),
		    parent  = $this.closest('tr').nextAll('tr');

		// Enable / Disable descendent items
		parent.find('[name^="' + ID('%', 'settings') + '"]').prop('disabled', !checked);

		// Enable / Disable description
		parent.find(ID('.', 'desc')).css('opacity', checked ? 1.0 : 0.5);

		// Show / Hide validation target
		show_folding_list($this, select, '1' === select.val() ? true : false);
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
					try {
						name = decodeURIComponent(key); // URIError: malformed URI sequence
						value = decodeURIComponent(json[key]);

						if (!data.hasOwnProperty(name)) { // !(name in data)
							data[name] = [];
						}

						data[name].push(value);
					} catch (e) {
					}
				}
			}

			$.each(data, function (name, val) {
				$('[name="' + name + '"]:input', self).val(val);
			});
		});
	};

	function deserialize_json(json, clear) {
		if (json) {
			// Set fields on form
			if ('string' === typeof json) {
				json = JSON.parse(json);
			}

			// reset all checkboxes
			if (clear) {
				$('input[type="checkbox"]').prop('checked', false).change();
			}

			// deserialize to the form
			$(ID('#', 'import')).closest('form').deserialize(json);

			// update textfield, checkbox (Exceptions, Mimetype)
			$(ID('@', 'exception_admin') + ',' + ID('@', 'validation_mimetype')).change();

			// update selection
			$('select[name*="' + ID('%', 'settings') + '"]').change();

			// folding list at Login form
			$(ID('@', 'validation_login')).change();

			// Public facing pages
			set_front_end($(ID('@', 'validation_public')));

			// Admin ajax/post
			show_folding_ajax($(ID('@', 'validation_ajax_1')));

			// Additional edge case
			if (clear) {
				clear = ID('%', 'settings[providers][IPInfoDB]');
				$(ID('@', 'providers_IPInfoDB')).prop('checked', json[clear] ? true : false);
			}
		}
	}

	// google chart
	var chart = {
		// Pie Chart
		dataPie: [],
		viewPie: [],
		drawPie: function (id) {
			var i, data;
			if ('undefined' === typeof chart.dataPie[id]) {
				i = chart.dataPie[id] = new google.visualization.DataTable();
				i.addColumn('string', 'Country');
				i.addColumn('number', 'Requests');
				data = $.parseJSON($('#' + id).attr('data-' + id));
				chart.dataPie[id].addRows(data);
			}
			if ('undefined' === typeof chart.viewPie[id]) {
				chart.viewPie[id] = new google.visualization.PieChart(
					document.getElementById(id)
				);
			}
			if ($('#' + id).width()) {
				chart.viewPie[id].draw(chart.dataPie[id], {
					backgroundColor: { fill: 'transparent' }, // '#f1f1f1',
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
		dataLine: [],
		viewLine: [],
		drawLine: function (id, datetype) {
			var i, n, t, data;
			if ('undefined' === typeof chart.dataLine[id]) {
				i = chart.dataLine[id] = new google.visualization.DataTable();
				i.addColumn(datetype, 'Date'   );
				i.addColumn('number', 'comment');
				i.addColumn('number', 'xmlrpc' );
				i.addColumn('number', 'login'  );
				i.addColumn('number', 'admin'  );
				i.addColumn('number', 'public' );
				data = $.parseJSON($('#' + id).attr('data-' + id));
				n = data.length;
				for (i = 0; i < n; ++i) {
					data[i][0] = new Date(data[i][0] * 1000); // [sec] to [msec]
				}
				chart.dataLine[id].addRows(data);
			}

			if ('undefined' === typeof chart.viewLine[id]) {
				chart.viewLine[id] = new google.visualization.LineChart(
					document.getElementById(id)
				);
			}

			i = $('#' + id).width();
			if (i) {
				i = i > 320 ? true : false;
				chart.viewLine[id].draw(chart.dataLine[id], {
					backgroundColor: { fill: 'transparent' }, // '#f1f1f1',
					legend: { position: 'bottom' },
					hAxis: { format: 'MM/dd' + ('datetime' === datetype ? ' HH:mm' : '') },
					vAxis: { textPosition: (i ? 'out' : 'in') },
					chartArea: {
						left: (i ? '10%' : 0),
						top: '5%',
						width: '100%',
						height: '75%'
					}
				});
			}
		}
	};

	// google chart
	function initChart(tabNo) {
		if ('object' === typeof google) {
			google.load('visualization', '1', {
				packages: ['corechart'],
				callback: function () {
					drawChart(tabNo);
				}
			});
		}
	}

	function drawChart(tabNo) {
		if (1 === tabNo) {
			chart.drawPie(ID('chart-countries'));
			chart.drawLine(ID('chart-daily'), 'date');
		} else if (5 === tabNo) {
			$(ID('.', 'multisite')).each(function (i, obj) {
				chart.drawLine($(obj).attr('id'), 'datetime');
			});
		}
	}

	// Load / Save cookie using wpCookies in wp-includes/js/utils.js
	function loadCookie(tabNo) {
		var i, cookie = ('undefined' !== typeof wpCookies && wpCookies.getHash('ip-geo-block')) || [];

		for (i in cookie) {
			if(cookie.hasOwnProperty(i)) {
				cookie[i] = cookie[i].replace(/[^ox\d]/g, '').split(''); // string (ooo...) to array (n)
			}
		}

		if ('undefined' === typeof cookie[tabNo]) {
			cookie[tabNo] = [];
		}

		return cookie;
	}

	// setHash( name, value, expires, path, domain, secure )
	function saveCookie(cookie) {
		var j, n, c = [];

		$.each(cookie, function(i, obj) {
			c[i] = '';
			if ('undefined' !== typeof obj) {
				n = obj.length;
				if (n) {
					c[i] = (obj[0] || 'o');
					for (j = 1; j < n; ++j) {
						c[i] += (obj[j] || 'o');
					}
				}
			}
		});

		if ('undefined' !== typeof wpCookies) {
			j = 'undefined' !== typeof IP_GEO_BLOCK_AUTH ? IP_GEO_BLOCK_AUTH.home + IP_GEO_BLOCK_AUTH.admin : '';
			wpCookies.setHash('ip-geo-block', c, new Date(Date.now() + 2592000000), j);
		}
	}

	// Click event handler to show/hide form-table
	function toggleSection(title, tabNo, cookie) {
		var index = title.closest('fieldset').data('section'),
		    body  = title.parent().nextAll('.panel-body').toggle(), border;

		// Show/Hide
		title.toggleClass(ID('dropup')).toggleClass(ID('dropdown'));

		border = title.hasClass(ID('dropdown'));
		if (border) {
			body.addClass(ID('border'));
		} else {
			body.removeClass(ID('border'));
		}

		cookie[tabNo][index] =  border ? 'o' : 'x';
		saveCookie(cookie); // Save cookie

		// redraw google chart
		drawChart(tabNo);
	}

	function manageSection(tabNo) {
		var cookie = loadCookie(tabNo);

		// Click event handler to show/hide form-table
		$('form').on('click', 'h2,h3', function (event) {
			toggleSection($(this), tabNo, cookie);
			return false;
		});

		// Toggle all
		$(ID('#', 'toggle-sections')).on('click', function (event) {
			var $this,
			    title = $(ID('.', 'field')).find('h2,h3'),
			    m = [ID('dropdown'), ID('dropup')],
			    n = title.filter('.' + m[0]).length;

			// update cookie
			title.each(function (i) {
				$this = $(this);
				$this.removeClass(m.join(' ')).addClass(n ? m[1] : m[0]);
				$this = $this.parent().nextAll('.panel-body').toggle(n ? false : true);
				if (n) {
					$this.removeClass(ID('border'));
				} else {
					$this.addClass(ID('border'));
				}
				cookie[tabNo][i] = n ? 'x' : 'o';
			});

			// Save cookie
			saveCookie(cookie);

			// redraw google chart
			drawChart(tabNo);

			return false;
		});

		return cookie;
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
		// Get tab number
		var tabNo = Number(IP_GEO_BLOCK.tab) || 0,

		// Attach event handler and manage cookie
		cookie = manageSection(tabNo);

		// Inhibit to submit by return key
		$(ID('.', 'inhibit')).on('submit', function () {
			return false;
		});

		// Register event handler at specific tab
		switch (tabNo) {
		  /*----------------------------------------
		   * Settings
		   *----------------------------------------*/
		  case 0:
			// Name of base class
			var name = ID('%', 'settings');

			/*---------------------------
			 * Validation rule settings
			 *---------------------------*/
			// Scan your country code
			$('[id^="' + ID('scan-') + '"]').on('click', function (event) {
				var $this = $(this),
				    id = $this.attr('id'),
				    parent = $this.parent();

				ajax_post(id.replace(/^.*(?:scan)/, 'scanning'), {
					cmd: 'scan-code',
					which: id.replace(ID('scan-'), '')
				}, function (data) {
					if (!parent.children('ul').length) {
						parent.append('<ul id="' + ID('code-list') + '"></ul>');
					}
					parent = parent.children('ul').empty();

					var key, val;
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							key = escapeHTML(key);
							if ('string' === typeof data[key]) {
								val = escapeHTML(data[key]);
							} else {
								val = escapeHTML(data[key].code);
								key = '<abbr title="' + escapeHTML(data[key].type) + '">' + key + '</abbr>';
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
				var value = this.value;
				$(ID('@', 'white_list')).closest('tr').toggle(value === '0');
				$(ID('@', 'black_list')).closest('tr').toggle(value === '1');
				return false;
			}).change();

			// Show/Hide folding list at prevent malicious upload
			$(ID('@', 'validation_mimetype')).on('change', function (event) {
				var $this = $(this),
				    stat = parseInt($this.val(), 10);
				$this.nextAll(ID('.', 'settings-folding')).each(function (i, obj) {
					fold_elements($(obj), stat === i + 1);
				});
				return stopPropergation(event);
			}).change();

			// Response message and Redirect URL
			$('select[name*="response_code"]').on('change', function (event) {
				var $this = $(this),
				    res = parseInt($this.val() / 100, 10),
				    elm = $this.closest('tr').nextAll('tr');

				// only for Front-end target settings
				if (0 <= $this.attr('name').indexOf('public')) {
					if (-1 == $(ID('@', 'public_matching_rule')).val()) {
						elm.each(function (index) {
							if (1 >= index) {
								$(this).hide();
							}
						});
						return stopPropergation(event);
					}
				}

				if (res <= 3) { // 2xx, 3xx
					elm.each(function (index) {
						if      (0 === index) { $(this).show(); } // redirect_uri
						else if (1 === index) { $(this).hide(); } // response_msg
					});
				} else { // 4xx, 5xx
					elm.each(function (index) {
						if      (0 === index) { $(this).hide(); } // redirect_uri
						else if (1 === index) { $(this).show(); } // response_msg
					});
				}
				return stopPropergation(event);
			}).change();

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

			/*---------------------------
			 * Back-end target settings
			 *---------------------------*/
			// Show/Hide folding list at Login form
			$(ID('@', 'validation_login')).on('change', function (event) {
				var $this = $(this);
				show_folding_list($this, $this, name, true);
				return stopPropergation(event);
			}).change();

			// Exceptions for Admin ajax/post
			$(ID('@', 'exception_admin')).on('change', function (event) {
				var actions = $.grep($(this).val().split(','), function (e){
					return '' !== e.replace(/^\s+|\s+$/g, ''); // remove empty element
				});

				$(ID('#', 'actions')).find('input').each(function (i, e) {
					var $this = $(this),
					    action = $this.attr('id').replace(ID('%', ''), '');
					$this.prop('checked', -1 !== $.inArray(action, actions));
				});
				return stopPropergation(event);
			}).change();

			// Candidate actions
			$(ID('#', 'actions')).on('click', 'input', function (event) {
				var i, $this = $(this),
				action = $this.attr('id').replace(ID('%', ''), ''),
				$admin = $(ID('@', 'exception_admin')),
				actions = $.grep($admin.val().split(','), function (e){
					return '' !== e.replace(/^\s+|\s+$/g, ''); // remove empty element
				});

				// find the action
				i = $.inArray(action, actions);

				if (-1 === i) {
					actions.push(action);
				} else {
					actions.splice(i, 1);
				}

				$admin.val(actions.join(',')).change();
			});

			// Enable / Disable Exceptions
			$('input[id^="' + ID('!', 'validation_ajax_') + '"]').on('change', function (event) {
				show_folding_ajax($(this));
			}).change();

			/*---------------------------
			 * Front-end target settings
			 *---------------------------*/
			// Enable / Disable for Public facing pages
			$(ID('@', 'validation_public')).on('change', function (event) {
				set_front_end($(this));
				return stopPropergation(event);
			}).change();

			// Matching rule on front-end
			$(ID('@', 'public_matching_rule')).on('change', function (event) {
				var value = this.value;
				$(ID('@', 'public_white_list'   )).closest('tr').toggle(value ===  '0');
				$(ID('@', 'public_black_list'   )).closest('tr').toggle(value ===  '1');
				$(ID('@', 'public_response_code')).change().closest('tr').toggle(value !== '-1');
				return stopPropergation(event);
			}).change();

			/*---------------------------
			 * Local database settings
			 *---------------------------*/
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
									key = escapeHTML(key);
									if (data[key].filename) {
										$(ID('@', api + '_' + key + '_path')).val(escapeHTML(data[key].filename));
									}
									if (data[key].message) {
										$(ID('#', api + '-' + key)).text(escapeHTML(data[key].message));
									}
								}
							}
						}
					}
				});

				return false;
			});

			/*---------------------------
			 * Plugin settings
			 *---------------------------*/
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
				$(ID('#', 'export-form')).submit();

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
						}, function (data) {
							deserialize_json(data, true);
						});
					});
				}

				return false;
			});

			$(ID('#', 'import')).on('click', function (event) {
				$(ID('#', 'file-dialog')).click();
				return false;
			});

			// Import pre-defined settings
			$(ID('#', 'default')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[0], function () {
					ajax_post('pre-defined', {
						cmd: 'import-default'
					}, function (data) {
						deserialize_json(data, true);
					});
				});
				return false;
			});

			$(ID('#', 'preferred')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[0], function () {
					ajax_post('pre-defined', {
						cmd: 'import-preferred'
					}, function (data) {
						deserialize_json(data, false);
					});
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

			// Show WordPress installation info
			$(ID('#', 'show-info')).on('click', function (event) {
				$(ID('#', 'wp-info')).empty();
				ajax_post('wp-info', {
					cmd: 'show-info'
				}, function (data) {
					var key, res = [];
					for (key in data) {
						if (data.hasOwnProperty(key)) {
							res.push('- ' + key + ' ' + data[key]);
						}
					}

					// response should be escaped at server side
					$(ID('#', 'wp-info')).html('<textarea rows="' + res.length + '">' + /*escapeHTML*/(res.join("\n")) + '</textarea>').find('textarea').select();
					return false;
				});
			});

			/*---------------------------
			 * Common event handler
			 *---------------------------*/
			// Show/Hide description (this change event hander should be at the last)
			$('select[name^="' + name + '"]').on('change', function (event) {
				var $this = $(this);
				show_description($this);
				show_folding_list($this, $this, name, true);
				return false;
			}).change();

			// Toggle checkbox
			$(ID('.', 'cycle')).on('click', function (event) {
				var $that = $(this).next('li'),
				    text = $that.find(ID('@', 'exception_admin')),
				    cbox = $that.find('input:checkbox'),
				    stat = cbox.filter(':checked').length;

				if (text.length) {
					cbox.filter(stat ? ':checked' : ':not(:checked)').click();
				} else {
					cbox.prop('checked', !stat);
				}

				return false;
			});

			// Folding list
			$(ID('.', 'settings-folding>dfn')).on('click', function (event) {
				var drop = ID('drop'),
				$this = $(this).parent();
				$this.children('li').toggle();
				$this.toggleClass(drop + 'up').toggleClass(drop + 'down');

				if ($this.hasClass(drop + 'down')) {
					$this.children('a').show();
				} else {
					$this.children('a').hide();
				}

				return false;
			});

			// Submit
			$('#submit').on('click', function (event) {
				var elm = $(ID('@', 'signature')),
				    str = elm.val();
				if (str.indexOf(',') !== -1) {
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
			initChart(tabNo);

			// Statistics
			$(ID('@', 'clear_statistics')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[3], function () {
					ajax_clear('statistics', null);
				});
				return false;
			});

			// Validation logs
			$(ID('@', 'clear_logs')).on('click', function (event) {
				confirm(IP_GEO_BLOCK.msg[5], function () {
					ajax_clear('logs', null);
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

			// Set selected provider to cookie
			$('select[id^="' + ID('!', 'service') + '"]').on('change', function (event) {
				cookie[tabNo][3] = $(this).prop('selectedIndex');
				saveCookie(cookie); // Save cookie
			}).change();

			// Search Geolocation
			$(ID('@', 'get_location')).on('click', function (event) {
				var whois = $(ID('#', 'whois')), obj,
				    ip = $(ID('@', 'ip_address')).val();

				if (ip) {
					whois.hide().empty();

					// Get whois data
					obj = $.whois(ip, function (data) {
						var i, str = '';
						for (i = 0; i < data.length; ++i) {
							str +=
							'<tr>' +
								'<td>' + data[i].name  + '</td>' +
								'<td>' + data[i].value + '</td>' +
							'</tr>';
						}

						whois.html(
							'<fieldset id="' + ID('section-1') + '" class="' + ID('field') + ' panel panel-default" data-section="1">' +
							'<legend class="panel-heading"><h3 id="' + ID('whois-title') + '" class="' + ID('dropdown') + '">Whois</h3></legend>' +
							'<div class="panel-body ' + ID('border') + '"><table class="' + ID('table') + '">' + str + '</table></div>' +
							'</fieldset>'
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
						    latitude  = escapeHTML(data.latitude  || '0'),
						    longitude = escapeHTML(data.longitude || '0'),
						    zoom = (data.latitude || data.longitude) ? 8 : 2;

						for (key in data) {
							if (data.hasOwnProperty(key)) {
								key = escapeHTML(key);
								info +=
									'<li>' +
										'<span class="' + ID('title' ) + '">' + key + ' : </span>' +
										'<span class="' + ID('result') + '">' + escapeHTML(data[key]) + '</span>' +
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
										'<span class="' + ID('result') + '">' + escapeHTML(ip) + '</span>' +
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
				$(ID('@', 'get_location')).click();
			}
			break;

		  /*----------------------------------------
		   * Logs
		   *----------------------------------------*/
		  case 4:
			// Kick-off footable
			ajax_post('logs', {
				cmd: 'restore',
				which: null,
				time: new Date() - ip_geo_block_time
			}, function (data) {
				var key;
				for (key in data) {
					if (data.hasOwnProperty(key)) {
						key = escapeHTML(key); // data has been already sanitized
//						$(ID('#', 'log-' + key)).html($.parseHTML(data[key])); // jQuery 1.8+
						$(ID('#', 'log-' + key)).html(data[key]);
					}
				}

				if (typeof $.fn.footable === 'function') {
					var logs = $(ID('.', 'log')),
					    title = logs.parent().prevAll('legend').find('h2,h3');

					// Once open section
					title.removeClass(ID('dropup')).addClass(ID('dropdown'));

					// Then make footable
					logs.fadeIn('slow').footable();

					// Finaly close section
					title.each(function (i, obj) {
						if ('x' === (cookie[tabNo][i+1] || 'o')) {
							cookie[tabNo][i+1] = 'o';
							$(obj).click();
						}
					});
				}

				// Jump to search tab with opening new window
				$('tbody[id^="' + ID('log-') + '"]').on('click', 'a', function (event) {
					key = window.location.pathname + window.location.search;
					window.open(key.replace(/tab=\d/, 'tab=2') + '&ip=' + $(this).text().replace(/[^\w\.\:\*]/, ''), '_blank');
					return false;
				});
			});

			// Clear filter
			$(ID('#', 'reset-filter')).on('click', function (event) {
				$('.footable').trigger('footable_clear_filter');
				return false;
			});

			// Preset filter
			$(ID('.', 'field')).on('footable_initialized', function (event) {
				$('.footable').trigger(
					'footable_filter', {
						'filter': $(ID('@', 'filter_logs')).val()
					}
				);
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
				$(ID('#', 'export-form')).submit();
				return false;
			});
			break;

		  /*----------------------------------------
		   * Sites
		   *----------------------------------------*/
		  case 5:
			// https://developers.google.com/loader/#Dynamic
			initChart(tabNo);
			break;
		}
	}); // document.ready()

}(jQuery, window, document));