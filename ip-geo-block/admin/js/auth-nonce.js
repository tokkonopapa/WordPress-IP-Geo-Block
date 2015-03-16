/**
 * ZEP - Zero-day exploit Prevention for admin area
 *
 */
(function ($) {
	// http://tools.ietf.org/html/rfc2396#appendix-B
	function parse_uri(uri) {
		var m = uri.match(
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

	function is_admin(url) {
		var admin = (
//			url.indexOf('wp-admin'      ) >= 0 ||
			url.indexOf('admin.php'     ) >= 0 ||
			url.indexOf('admin-ajax.php') >= 0 ||
			url.indexOf('admin-post.php') >= 0
		) ? true : false;
		return admin;
	}

	function query_args(uri, args) {
		var url = uri.scheme ? uri.scheme + '://' : '';
		return url + uri.authority + uri.path + '?' + args.join('&');
	}

	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = IP_GEO_AUTH.nonce || null;
		if (nonce && is_admin(settings.url)) {
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
				var data, uri = parse_uri(settings.url);
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
			$('a').on('click', function(event) {
				var href = $(this).attr('href');
				if (is_admin(href)) {
					var data, uri = uri = parse_uri(href);
					data = uri.query ? uri.query.split('&') : [];
					data.push('ip-geo-block-auth-nonce=' + encodeURIComponent(nonce));
					href = query_args(uri, data);
					$(this).attr('href', href);
				}
			});

			$('form').on('submit', function(event) {
				$(this).append('<input type="hidden" name="ip-geo-block-auth-nonce" value="' + nonce + '" />');
			});
		}
	});
}(jQuery));