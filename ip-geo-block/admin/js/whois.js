/*jslint white: true */
/*!
 * Project: whois.js - get whois infomation from RIPE Network Coordination Center
 * Description: A jQuery plugin to get whois infomation from RIPE NCC database.
 * Version: 0.2
 * Copyright (c) 2019 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 *
 * RIPE NCC
 * @link https://stat.ripe.net/docs/data_api#Whois
 */
(function ($) {
	$.extend({
		whois: function (query, callback) {
			var results = [],
				url = 'https://stat.ripe.net/data/whois/data.json?resource=';

			function escapeHTML(str) {
				return str ? str.toString().replace(/[&<>"']/g, function (match) { //'"
					return {
						'&': '&amp;',
						'<': '&lt;',
						'>': '&gt;',
						'"': '&quot;',
						"'": '&#39;'
					}[match];
				}) : '';
			}

			return $.ajax({
				url: url + query,
				method: 'GET',
				dataType: 'json'
			})

			.done(function (data, textStatus, jqXHR) {
				// https://stackoverflow.com/questions/722668/traverse-all-the-nodes-of-a-json-object-tree-with-javascript#answer-722676
				function process(key, value) {
					if (value && typeof value === 'object') {
						if (value.key) {
							value.key   = escapeHTML(value.key);
							value.value = escapeHTML(value.value);
							if (value.details_link) {
								value.value = '<a href="' + escapeHTML(value.details_link) + '">' + value.value + '</a>';
							}
							results.push({
								name : value.key,
								value: value.value
							});
						}
					}
				}

				function traverse(obj, func) {
					for (var i in obj) {
						func.apply(this, [i, obj[i]]);
						if (obj[i] !== null && typeof(obj[i]) === 'object') {
							traverse(obj[i], func); //going one step down in the object tree!!
						}
					}
				}
				traverse(data.data, process);
			})

			.fail(function (jqXHR, textStatus, errorThrown) {
				results.push({
					name : escapeHTML(textStatus),
					value: escapeHTML(errorThrown)
				});
			})

			.always(function () {
				if (callback) {
					callback(results);
				}
			});
		}
	});

}(jQuery));