/**
 * PZAP - Prevention of Zero-day exploit via admin Ajax and Post
 *
 * <meta name="ip-geo-block-auth-nonce" content="..." />
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

	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = IP_GEO_AUTH.nonce || null;
		if (nonce && (
			settings.url.indexOf('wp-admin/admin-ajax.php') >= 0 ||
			settings.url.indexOf('wp-admin/admin-post.php') >= 0
		)) {
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
					settings.url  = uri.scheme ? uri.scheme + '://' : '';
					settings.url += uri.authority + uri.path;
					settings.url += '?' + data.join('&');
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
			$('form').append('<input type="hidden" name="ip-geo-block-auth-nonce" value="' + nonce + '" />');
		}
	});
}(jQuery));