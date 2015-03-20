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

	function is_admin(url, query) {
		var uri = parse_uri(url.toLowerCase()),
		    http = uri.scheme.indexOf('http') === 0,
		    path = uri.path;

		if (http && uri.authority !== location.host.toLowerCase()) {
			return -1; // -1: external
		}

		// check scheme, path, query
		return (http || ! uri.scheme) && (
			path.indexOf('admin.php'     ) >= 0 ||
			path.indexOf('admin-ajax.php') >= 0 ||
			path.indexOf('admin-post.php') >= 0 ) && (
			typeof query === 'string' ?
				/(?:action)=/.test(query) :
				typeof query.action !== 'undefined'
		) ? 1 : 0; // 1: target, 0: other
	}

	function query_args(uri, args) {
		return (uri.scheme ? uri.scheme + '://' : '') +
		       (uri.authority + uri.path + '?' + args.join('&'));
	}

	function sanitize(str) {
		return (str + '').replace(/[&<>"']/g, function (match) {
			return {
				'&' : '&amp;',
				'<' : '&lt;',
				'>' : '&gt;',
				'"' : '&quot;',
				"'" : '&#39;'
			}[match];
		});
	}

	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = IP_GEO_AUTH.nonce || null;
		if (nonce && is_admin(settings.url, settings.data) === 1) {
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
				    admin = is_admin(href, href);

				// if target
				if (admin === 1) {
					var uri = parse_uri(href), data;
					data = uri.query ? uri.query.split('&') : [];
					data.push('ip-geo-block-auth-nonce=' + encodeURIComponent(nonce));
					$(this).attr('href', query_args(uri, data));
				}

				// if external
				else if (admin === -1) {
					// redirect with no referrer not to leak out the nonce
					var w = window.open();
					w.document.write(
						'<meta http-equiv="refresh" content="0; url=' + 
						sanitize(this.href) + '">'
					);
					w.document.close();
					return false;
				}
			});

			$('form').on('submit', function (event) {
				var $this = $(this);

				// if target
				if (is_admin($this.attr('action'), $this.serialize()) === 1) {
					$this.append(
						'<input type="hidden" name="ip-geo-block-auth-nonce" value="'
						+ nonce + '" />'
					);
				}
			});
		}
	});
}(jQuery));