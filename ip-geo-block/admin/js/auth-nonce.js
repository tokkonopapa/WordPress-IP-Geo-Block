/**
 * WP-ZEP - Zero-day exploit Prevention for wp-admin
 *
 */
(function ($) {
	function parse_uri(uri) {
		var m = uri.match(
			// http://tools.ietf.org/html/rfc2396#appendix-B
			/^(?:([^:\/?#]+):)?(?:\/\/([^\/?#]*))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?/
		);

		// scheme :// authority path ? query # fragment
		return {
			scheme    : m[1] || '',
			authority : m[2] || '',
			path      : m[3] || '',
			query     : m[4] || '',
			fragment  : m[5] || ''
		};
	}

	function is_admin(url, method) {
		var uri = parse_uri(url.toLowerCase());
		if (uri.scheme.indexOf('http') === 0 &&
		    uri.authority !== location.host.toLowerCase()) {
			return -1; // -1: external
		}
		return ( // 1: target, 0: other internal
			url.indexOf('admin.php'     ) >= 0 ||
			url.indexOf('admin-ajax.php') >= 0 ||
			url.indexOf('admin-post.php') >= 0 ) && (
			uri.query.indexOf('action=' ) >= 0 ||
			'post' === method.toLowerCase()
		) ? 1 : 0;
	}

	function query_args(uri, args) {
		var url = uri.scheme ? uri.scheme + '://' : '';
		return url + uri.authority + uri.path + '?' + args.join('&');
	}

	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = IP_GEO_AUTH.nonce || null;
		if (nonce && is_admin(settings.url, settings.type) === 1) {
			// multipart/form-data
			if (settings.data instanceof FormData) {
				settings.data.append('ip-geo-block-auth-nonce', nonce);
			}

			// application/x-www-form-urlencoded
			else {
				// Behavior of jQuery Ajax
				// method query query+data data
				// GET    query query      data
				// POST   query query      data
				var uri = parse_uri(settings.url), data;
				if (typeof settings.data === 'undefined' || uri.query) {
					data = uri.query ? uri.query.split('&') : [];
					data.push('ip-geo-block-auth-nonce=' + encodeURIComponent(nonce));
					settings.url = query_args(uri, data);
				} else {
					data = settings.data ? settings.data.split('&') : [];
					data.push('ip-geo-block-auth-nonce=' + encodeURIComponent(nonce));
					settings.data = data.join('&');
				}
			}
		}
	});

	$(function () {
		var nonce = IP_GEO_AUTH.nonce || null;
		if (nonce) {
			$('a').on('click', function (event) {
				var href = $(this).attr('href'),
				    admin = is_admin(href, 'GET');

				// if target
				if (admin === 1) {
					var uri = parse_uri(href),
					data = uri.query ? uri.query.split('&') : [];
					data.push('ip-geo-block-auth-nonce=' + encodeURIComponent(nonce));
					$(this).attr('href', query_args(uri, data));
				}

				// if external
				else if (admin === -1) {
					// redirect with no referrer not to leak out the nonce
					var w = window.open();
					w.document.write(
						'<meta http-equiv="refresh" content="0; url=' + href + '">'
					);
					w.document.close();
					return false;
				}
			});

			$('form').on('submit', function (event) {
				var url = $(this).attr('action');

				// if not external
				if (is_admin(url, $(this).attr('method')) !== -1) {
					$(this).append(
						'<input type="hidden" name="ip-geo-block-auth-nonce" value="'
						+ nonce + '" />'
					);
				}
			});
		}
	});
}(jQuery));