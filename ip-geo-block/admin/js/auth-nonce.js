/**
 * WP-ZEP - Zero-day exploit Prevention for wp-admin
 *
 */
var IP_GEO_BLOCK_ZEP = {
	auth: 'ip-geo-block-auth-nonce',
	nonce: IP_GEO_BLOCK_AUTH.nonce || '',
	redirect: function (url) {
		if (-1 !== location.href.indexOf(url)) {
			if (this.nonce) {
				url += (url.indexOf('?') >= 0 ? '&' : '?') + this.auth + '=' + this.nonce;
			} 
			window.location.href = url;
		}
	}
};

(function ($) {
	function parse_uri(uri) {
		var m = uri ? uri.toString().match(
			// https://tools.ietf.org/html/rfc3986#appendix-B
			/^(?:([^:\/?#]+):)?(?:\/\/([^\/?#]*))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?/
		) : [];

		// scheme :// authority path ? query # fragment
		return {
			scheme    : m[1] || '',
			authority : m[2] || '',
			path      : m[3] || '',
			query     : m[4] || '',
			fragment  : m[5] || ''
		};
	}

	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
	function encodeURIComponentRFC3986(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, function (c) {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}

	function is_admin(url, query) {
		var uri = parse_uri(url ? url.toString().toLowerCase() : ''),
		    http = /https?/.test(uri.scheme),
		    path = uri.path.charAt(0) === '/' ? uri.path : location.pathname;

		// explicit scheme and external domain
		if (http && uri.authority !== location.host.toLowerCase()) {
			return -1; // -1: external
		}

		// check scheme, path, query
		return (
			path.indexOf('admin.php'     ) >= 0 ||
			path.indexOf('admin-ajax.php') >= 0 ||
			path.indexOf('admin-post.php') >= 0
 		) && (
			// currently, request via jQuery ajax is always true
 			typeof query === 'string' ? /(?:action)=/.test(query) : true
		) ? 1 : 0; // 1: target, 0: other
	}

	function query_args(uri, args) {
		return (uri.scheme ? uri.scheme + '://' : '') +
		       (uri.authority + uri.path + '?' + args.join('&'));
	}

	function sanitize(str) {
		return str ? str.toString().replace(/[&<>"']/g, function (match) {
			return {
				'&' : '&amp;',
				'<' : '&lt;',
				'>' : '&gt;',
				'"' : '&quot;',
				"'" : '&#39;'
			}[match];
		}) : '';
	}

	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = IP_GEO_BLOCK_ZEP.nonce;
		if (nonce && is_admin(settings.url, null/*settings.data*/) === 1) {
			// multipart/form-data (XMLHttpRequest Level 2)
			// IE10+, Firefox 4+, Safari 5+, Android 3+
			if (typeof window.FormData !== 'undefined' &&
			    settings.data instanceof FormData) {
				settings.data.append(IP_GEO_BLOCK_ZEP.auth, nonce);
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
					data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponentRFC3986(nonce));
					settings.url = query_args(uri, data);
				} else {
					data = settings.data ? settings.data.split('&') : [];
					data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponentRFC3986(nonce));
					settings.data = data.join('&');
				}
			}
		}
	});

	$(function () {
		var nonce = IP_GEO_BLOCK_ZEP.nonce;
		if (nonce) {
			$('a').on('click', function (event) {
				var href = $(this).attr('href'), // String or undefined
				    admin = is_admin(href, href);

				// if admin area
				if (admin === 1) {
					var uri = parse_uri(href), data;
					data = uri.query ? uri.query.split('&') : [];
					data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponentRFC3986(nonce));
					$(this).attr('href', query_args(uri, data));
				}

				// if external
				else if (admin === -1) {
					// redirect with no referrer not to leak out the nonce
					var w = window.open();
					w.document.write(
						'<meta name="referrer" content="never" />' +
						'<meta name="referrer" content="no-referrer" />' +
						'<meta http-equiv="refresh" content="0; url=' + sanitize(this.href) + '" />'
					);
					w.document.close();
					return false;
				}
			});

			$('form').on('submit', function (event) {
				var $this = $(this);

				// if admin area
				if (is_admin($this.attr('action'), $this.serialize()) === 1) {
					$this.append(
						'<input type="hidden" name="' + IP_GEO_BLOCK_ZEP.auth + '" value="'
						+ sanitize(nonce) + '" />'
					);
				}
			});
		}
	});
}(jQuery));