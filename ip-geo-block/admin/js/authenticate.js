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
	// Parse a URL and return its components
	function parse_uri(uri) {
		uri = decodeURIComponent(uri ? uri.toString() : '');

		var m = uri.match(
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

	/**
	 * Returns canonicalized absolute pathname
	 *
	 * This code is based on http://phpjs.org/functions/realpath/
	 */
	function realpath(uri) {
		var path, real = [];

		// extract pathname (avoid `undefined`)
		if (typeof uri !== 'object') {
			uri = parse_uri(uri);
		}

		// focusing only at pathname
		path = uri.path;

		// if it's not absolute, add root path
		if ('/' !== path.charAt(0)) {
			var p = this.window.location.pathname;
			path = p.substring(0, p.lastIndexOf('/') + 1) + path;
		}

		// explode the given path into it's parts
		path = path.split('/');

		// if path ends with `/`, adds it to the last part
		if ('' === path[path.length-1]) {
			path.pop();
			path[path.length-1] += '/';
		}

		for (var i in path) {
			// this is'nt really interesting
			if ('.' === path[i]) {
				continue;
			}

			// this reduces the realpath
			if ('..' === path[i]) {
				if (real.length > 0) {
					real.pop();
				}
			}

			// this adds parts to the realpath
			else {
				if ((real.length < 1) || (path[i] !== '')) {
					real.push(path[i]);
				}
			}
		}

		// returns the absloute path as a string
		return real.join('/').replace(/\/\//g, '/');
	}

	// `/wp-admin/`, `/wp-admin/something.php` for is_admin()
	var regexp = new RegExp(
		'(?:' + IP_GEO_BLOCK_AUTH.root + IP_GEO_BLOCK_AUTH.admin
		+ '|' + IP_GEO_BLOCK_AUTH.root + IP_GEO_BLOCK_AUTH.plugins
		+ '|' + IP_GEO_BLOCK_AUTH.root + IP_GEO_BLOCK_AUTH.themes
		+ ')(?:.*\.php|.*\/)?$'
	);

	function is_admin(uri) {
		// parse uri and get real path
		uri = parse_uri(uri ? uri.toString().toLowerCase() : location.pathname);

		// get absolute path with flattening `./`, `../`, `//`
		var path = realpath(uri);

		// possibly scheme is `javascript` and path is `void(0);`
		if (/https?/.test(uri.scheme) || ! uri.scheme) {
			// external domain (`http://example` or `www.example`)
			if (uri.authority && uri.authority !== location.host.toLowerCase()) {
				return -1; // external
			}

			// possibly only fragment is `#...`
			else if ((uri.scheme || uri.path || uri.query) && regexp.test(path)) {
				return 1; // internal admin
			}
		}

		return 0; // internal not admin
	}

	function query_args(uri, args) {
		return (uri.scheme ? uri.scheme + '://' : '') +
		       (uri.authority + uri.path + '?' + args.join('&')) +
		       (uri.fragment);
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

	/**
	 * Set the event priority to the first (jQuery 1.8+, WP 3.5+)
	 *
	 * http://stackoverflow.com/questions/2360655/jquery-event-handlers-always-execute-in-order-they-were-bound-any-way-around-t
	 */
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
				// 'string' or 'undefined'
				var href = $(this).attr('href'), admin = is_admin(href);

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
				var $this = $(this), action = $this.attr('action');

				// if admin area
				if (is_admin(action) === 1) {
					$this.attr('action', add_query_nonce(action, nonce));
				}
			});

			$('form').each(function (index) {
				var $this = $(this), action = $this.attr('action');

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