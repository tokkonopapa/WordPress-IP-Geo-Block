/**
 * WP-ZEP - Zero-day exploit Prevention for wp-admin
 *
 */
/* utility object */
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

(function ($, document) {
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

	// `/wp-admin/`, `/wp-admin/something.php` or `something.php` for is_admin()
	var regexp = new RegExp(
		'(?:\/wp-admin\/.*(?:\.php|\/)+' +
		(IP_GEO_BLOCK_AUTH.plugins ? '|' + IP_GEO_BLOCK_AUTH.plugins + '.*(?:\.php|\/)+' : '') +
		(IP_GEO_BLOCK_AUTH.themes  ? '|' + IP_GEO_BLOCK_AUTH.thems   + '.*(?:\.php|\/)+' : '') +
		'|^[^\/]+\.php)$'
	);

	function is_admin(uri) {
		// directory traversal should be checked more strictly ?
		uri = parse_uri(uri ? uri.toString().toLowerCase() : '');
		var path = (uri.path.replace('/\./g', '').charAt(0) === '/' ? uri.path : location.pathname);

		// external domain (`http://example` or `www.example`)
		if ((/https?/.test(uri.scheme) || (! uri.scheme && uri.authority)) && uri.authority !== location.host.toLowerCase()) {
			return -1; // -1: external
		}

		// possibly scheme is `javascript` or path is `;`
		return (uri.scheme || uri.path || uri.query) && regexp.test(path) ? 1 : 0;
	}

	function query_args(uri, args) {
		return (uri.scheme ? uri.scheme + '://' : '') +
		       (uri.authority + uri.path + '?' + args.join('&'));
	}

	function add_query_nonce(src, nonce) {
		var uri = parse_uri(src), data;
		data = uri.query ? uri.query.split('&') : [];
		data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponentRFC3986(nonce));
		return query_args(uri, data);
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
		if (nonce && is_admin(settings.url) === 1) {
			// multipart/form-data (XMLHttpRequest Level 2)
			// IE10+, Firefox 4+, Safari 5+, Android 3+
			if (typeof window.FormData !== 'undefined' &&
			    settings.data instanceof FormData) {
				settings.data.append(IP_GEO_BLOCK_ZEP.auth, nonce);
			}

			// application/x-www-form-urlencoded
			else {
				// Behavior of jQuery Ajax
				// method  url  url+data data
				// GET    query  query   data
				// POST   query  query   data
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

	// Set the event priority to the first (jQuery 1.8+, WP 3.5+)
	// http://stackoverflow.com/questions/2360655/jquery-event-handlers-always-execute-in-order-they-were-bound-any-way-around-t
	$.fn.bindFirst = function (event, selector, fn) {
		this.on(event, selector, fn).each(function () {
			var handlers = $._data(this, 'events')[event.split('.')[0]],
			    handler = handlers.pop();
			handlers.splice(0, 0, handler);
		});
	};

	$(function () {
		var nonce = IP_GEO_BLOCK_ZEP.nonce;
		if (nonce) {
			var $body = $('body');

			$body.find('img').each(function (index) {
				var src = $(this).attr('src');

				// if admin area
				if (is_admin(src) === 1) {
					$(this).attr('src', add_query_nonce(src, nonce));
				}
			});

			$body.bindFirst('click', 'a', function (event) {
				var href = $(this).attr('href'), // 'string' or 'undefined'
				    admin = is_admin(href);

				// if admin area
				if (admin === 1) {
					$(this).attr('href', add_query_nonce(href, nonce));
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

			$body.bindFirst('submit', 'form', function (event) {
				var $this = $(this),
				    action = $this.attr('action');

				// if admin area
				if (is_admin(action) === 1) {
					$this.attr('action', add_query_nonce(action, nonce));
				}
			});

			$('form').each(function (index) {
				var $this = $(this),
				    action = $this.attr('action');

				// if admin area
				if (is_admin(action) === 1 && 'multipart/form-data' === $this.attr('enctype')) {
					$this.append(
						'<input type="hidden" name="' + IP_GEO_BLOCK_ZEP.auth + '" value="'
						+ sanitize(nonce) + '" />'
					);
				}
			});
		}
	});
}(jQuery, document));