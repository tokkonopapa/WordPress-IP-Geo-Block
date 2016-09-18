/*jslint white: true */
/*!
 * Project: whois.js - get whois infomation
 * Description: A jQuery plugin to get whois infomation from RIPE NCC database.
 * Version: 0.1
 * Copyright (c) 2016 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 *
 * RIPE NCC
 * @link https://apps.db.ripe.net/search/query.html
 * @link https://labs.ripe.net/ripe-database/database-api/api-documentation
 * @link https://www.ripe.net/manage-ips-and-asns/db/support/documentation/ripe-database-documentation
 * @link https://www.ripe.net/manage-ips-and-asns/db/support/documentation/ripe-database-documentation/how-to-query-the-ripe-database/14-3-restful-api-queries/14-3-2-api-search
 * @link https://github.com/RIPE-NCC/whois/wiki/WHOIS-REST-API-search
 */
(function ($) {
	'use strict';

	$.extend({
		whois: function (query, callback) {
			/**
			 * APIs that doesn't support CORS.
			 * It is accessed through https://developer.yahoo.com/yql/
			 */
			var results = [],
				yql = 'https://query.yahooapis.com/v1/public/yql?q=select * from xml where url="%URL%"&format=json&jsonCompat=new',
				url = 'https://rest.db.ripe.net/search%3fflags=no-filtering%26flags=resource%26query-string=';
//				app = 'https://apps.db.ripe.net/search/lookup.html?source=%SRC%&key=%KEY%&type=%TYPE%';

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

			return $.ajax({
				url: yql.replace(/%URL%/, url + query),
				method: 'GET',
				dataType: 'json'
			})

			.done(function (data, textStatus, jqXHR) {
				// http://stackoverflow.com/questions/722668/traverse-all-the-nodes-of-a-json-object-tree-with-javascript#answer-722676
				function traverse(key, value) {
					if (value && typeof value === 'object') {
						if (value.errormessage) {
							var err = value.errormessage,
								msg = err.text.split(/\n+/);

							results.push({
								name : sanitize(err.severity),
								value: sanitize(msg[1].replace(/%s/, err.args.value))
							});
						}

						else if (value.href) {
							value.href = sanitize(value.href);
							results.push({
								name : sanitize(key),
								value: '<a href="' + value.href + '.json" target=_blank>' + value.href + '</a>'
							});
						}

						else if (value.name && value.value) {
							/*if (value.link) {
								var src = value.link.href.match(/\w+-grs/);
								value.value = '<a href="' + 
									app.replace('%SRC%', src[0])
									   .replace('%KEY%', encodeURI(value['value']))
									   .replace('%TYPE%', value['referenced-type']) +
									'" target=_blank>' + value.value + '</a>';
							}*/

							if (value.link) {
								value.value = '<a href="' + sanitize(value.link.href) + '.json" target=_blank>' + sanitize(value.value) + '</a>';
							}

							else if ('remarks' === value.name) {
								value.value = sanitize(value.value);
								value.value = value.value.replace(/(https?:\/\/[^\s]+)/gi, '<a href="$1" target=_blank>$1</a>');
							}

							results.push({
								name : sanitize(value.name),
								value: value.value
							});
						}

						else if ('primary-key' !== key) {
							$.each(value, function(k, v) {
								// k is either an array index or object key
								traverse(k, v);
							});
						}
					}
				}

				var i, attr = data.query.results, objs = [];

				for (i in attr) {
					if (attr.hasOwnProperty(i)) {
						objs = attr[i]; // whois-resouces
						break;
					}
				}

				traverse(null, objs);
			})

			.fail(function (jqXHR, textStatus, errorThrown) {
				results.push({
					name : sanitize(textStatus),
					value: sanitize(errorThrown)
				});
			})

			.always(function () {
				results.push({
					name : 'copyright',
					value: '<a href="https://apps.db.ripe.net/search/query.html" title="Database Query - RIPE Network Coordination Centre">RIPE NCC</a>'
				});

				if (callback) {
					callback(results);
				}
			});
		}
	});

})(jQuery);