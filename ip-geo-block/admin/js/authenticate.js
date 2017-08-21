/*jslint white: true */
/*!
 * Project: WP-ZEP - Zero-day exploit Prevention for wp-admin
 * Copyright (c) 2015-2017 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
// utility object
var IP_GEO_BLOCK_ZEP = {
	init: false,
	auth: 'ip-geo-block-auth-nonce',
	nonce: IP_GEO_BLOCK_AUTH.nonce || '',
	sites: IP_GEO_BLOCK_AUTH.sites || [],
	redirect: function (url) {
		'use strict';
		var i, n = this.sites.length;
		for (i = 0; i < n; ++i) {
			if (url && -1 !== url.indexOf(this.sites[i]) && this.nonce) {
				window.location = add_query_nonce(url, this.nonce);
			}
		}
	}
};

(function ($, document) {
	'use strict';

	// produce safe text for HTML
	function escapeHTML(html) {
		var elem = document.createElement('div');
		elem.appendChild(document.createTextNode(html));
		html = elem.innerHTML.replace(/["']/g, function (match) {
			return {
				'"': '&quot;',
				"'": '&#39;'
			}[match];
		});
		elem = '';
		return html;
	}

	// Parse a URL and return its components
	function parse_uri(uri) {
		// avoid malformed URI error when uri includes '%'
		uri = /*decodeURIComponent*/(uri ? uri.toString() : '');

		var m = uri.match(
			// https://tools.ietf.org/html/rfc3986#appendix-B
			/^(?:([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?/
		);

		// scheme :// authority path ? query # fragment
		return {
			scheme:    m[1] || '',
			relative:  m[2] || '',
			authority: m[3] || '',
			path:      m[4] || '',
			query:     m[5] || '',
			fragment:  m[6] || ''
		};
	}

	// Compose a URL from components
	function compose_uri(uri) {
		return (uri.scheme   ? uri.scheme + ':'   : '') +
		       (uri.relative + uri.path)  +
		       (uri.query    ? '?' + uri.query    : '') +
		       (uri.fragment ? '#' + uri.fragment : '');
	}

	/**
	 * Returns canonicalized absolute pathname
	 *
	 * This code is based on http://phpjs.org/functions/realpath/
	 */
	function realpath(uri) {
		var i, path, real = [];

		// extract pathname (avoid `undefined`)
		if (typeof uri !== 'object') {
			uri = parse_uri(uri);
		}

		// focusing only at pathname
		path = uri.path;

		// if it's not absolute, add root path
		if ('/' !== path.charAt(0)) {
			i = window.location.pathname;
			path = i.substring(0, i.lastIndexOf('/') + 1) + path;
		}

		// explode the given path into it's parts
		path = path.split('/');

		// if path ends with `/`, adds it to the last part
		if ('' === path[path.length - 1]) {
			path.pop();
			path[path.length - 1] += '/';
		}

		for (i in path) {
			if (path.hasOwnProperty(i)) {
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
		}

		// returns the absloute path as a string
		return real.join('/').replace(/\/\//g, '/');
	}
/*
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
	function encodeURIComponentRFC3986(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, function (c) {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}
*/
	// append the nonce as query strings to the uri
	function add_query_nonce(uri, nonce) {
		if (typeof uri !== 'object') { // `string` or `undefined`
			uri = parse_uri(uri || location.href);
		}

		var data = uri.query ? uri.query.split('&') : [],
		    i = data.length;

		// remove an old nonce
		while (i-- > 0) {
			if (data[i].indexOf(IP_GEO_BLOCK_ZEP.auth) === 0) {
				data.splice(i, 1);
				break;
			}
		}

		data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponent(nonce));//RFC3986
		uri.query = data.join('&');

		return compose_uri(uri);
	}

	// check if uri is valid
	function is_valid_uri(uri) {
		return uri.scheme || uri.path || uri.query;
	}

	// check if current page in admin and the target of wp-zep
	function is_back_end() {
		return (is_admin(location.pathname) === 1 || location.search.indexOf(IP_GEO_BLOCK_ZEP.auth) >= 0);
	}

	// regular expression to find target for is_admin()
	var regexp = new RegExp(
		'^(?:' + (IP_GEO_BLOCK_AUTH.home || '') + IP_GEO_BLOCK_AUTH.admin
		+ '|'  + (IP_GEO_BLOCK_AUTH.home || '') + IP_GEO_BLOCK_AUTH.plugins
		+ '|'  + (IP_GEO_BLOCK_AUTH.home || '') + IP_GEO_BLOCK_AUTH.themes
		+ ')(?:.*\.php|.*\/)?$'
	);

	// check the URI where the nonce is needed
	function is_admin(uri) {
		// parse uri and get real path
		uri = uri || location.pathname; // in case of empty `action` on the form tag
		uri = parse_uri(uri.toLowerCase());

		// get absolute path with flattening `./`, `../`, `//`
		var match, path = realpath(uri);

		// possibly scheme is `javascript` and path is `void(0);`
		if (!uri.scheme || /^https?$/.test(uri.scheme)) {
			// external domain (`http://example` or `www.example`)
			// https://tools.ietf.org/html/rfc6454#section-4
			if (uri.authority && uri.authority !== location.host.toLowerCase()) {
				return -1; // external
			}

			// exclude the case which component is only fragment (`#...`)
			if (is_valid_uri(uri) && (match = regexp.exec(path))) {
				if ((IP_GEO_BLOCK_AUTH.zep.ajax    && -1 !== match[0].indexOf(IP_GEO_BLOCK_AUTH.admin + 'admin-')) ||
				    (IP_GEO_BLOCK_AUTH.zep.admin   && -1 !== match[0].indexOf(IP_GEO_BLOCK_AUTH.admin           )) ||
				    (IP_GEO_BLOCK_AUTH.zep.plugins && -1 !== match[0].indexOf(IP_GEO_BLOCK_AUTH.plugins         )) ||
				    (IP_GEO_BLOCK_AUTH.zep.themes  && -1 !== match[0].indexOf(IP_GEO_BLOCK_AUTH.themes          ))) {
					return 1; // internal for admin
				}
			}
		}

		return 0; // internal not admin
	}

	var nonce = IP_GEO_BLOCK_ZEP.nonce,

	// `theme-install.php` eats the query and set it to `request[browse]` as a parameter
	theme_featured = function (data) {
		var i = data.length;
		while (i-- > 0) {
			if (data[i].indexOf('request%5Bbrowse%5D=ip-geo-block-auth') !== -1) {
				data[i] = 'request%5Bbrowse%5D=featured'; // correct the parameter
				break;
			}
		}
		return data;
	},

	// `upload.php` eats the query and set it to `query[ip-geo-block-auth-nonce]` as a parameter
	media_library = function (data) {
		var i = data.length;
		while (i-- > 0) {
			if (data[i].indexOf('query%5Bip-geo-block-auth-nonce%5D=') !== -1) {
				delete data[i];
				break;
			}
		}
		return data;
	},

	// list of excluded links
	ajax_links = {
		'upload.php': media_library,
		'theme-install.php': theme_featured,
		'network/theme-install.php': theme_featured
	};

	// check excluded path
	function check_ajax(path) {
		path = path.replace(IP_GEO_BLOCK_AUTH.home + IP_GEO_BLOCK_AUTH.admin, '');
		return ajax_links.hasOwnProperty(path) ? ajax_links[path] : null;
	}

	// embed a nonce before an Ajax request is sent
	$.ajaxPrefilter(function (settings, original, jqxhr) {
		// POST to async-upload.php causes an error in https://wordpress.org/plugins/mammoth-docx-converter/
		if (is_admin(settings.url) === 1 && !settings.url.match(/async-upload\.php$/)) {
			// multipart/form-data (XMLHttpRequest Level 2)
			// IE10+, Firefox 4+, Safari 5+, Android 3+
			if (typeof window.FormData !== 'undefined' && settings.data instanceof FormData) {
				settings.data.append(IP_GEO_BLOCK_ZEP.auth, nonce);
			}

			// application/x-www-form-urlencoded
			else {
				// Behavior of jQuery Ajax
				// method  url  url+data data
				// GET    query  query   data
				// POST   query  query   data
				var uri = parse_uri(settings.url);

				if (typeof settings.data === 'undefined' || uri.query) {
					settings.url = add_query_nonce(uri, nonce);
				} else {
					var data = settings.data ? settings.data.split('&') : [],
					    callback = check_ajax(location.pathname);
					if (callback) {
						data = callback(data);
					}
					data.push(IP_GEO_BLOCK_ZEP.auth + '=' + encodeURIComponent(nonce));//RFC3986
					settings.data = data.join('&');
				}
			}
		}
	});

	/*
	 * jQuery.bind-first library v0.2.3 (jquery >= 1.7)
	 * Copyright (c) 2013 Vladimir Zhuravlev
	 *
	 * Released under MIT License
	 * @license https://github.com/private-face/jquery.bind-first
	 *
	 * Date: Thu Feb  6 10:13:59 ICT 2014
	 */
	function moveHandlerToTop($el, eventName, isDelegated) {
		var data = $._data($el[0]).events,
		    events = data[eventName],
		    handler = isDelegated ? events.splice(events.delegateCount - 1, 1)[0] : events.pop();

		events.splice(isDelegated ? 0 : (events.delegateCount || 0), 0, handler);
	}

	function moveEventHandlers($elems, eventsString, isDelegate) {
		var events = eventsString.split(/\s+/);
		$elems.each(function(i) {
			for (i = 0; i < events.length; ++i) {
				var pureEventName = $.trim(events[i]).match(/[^\.]+/i)[0];
				moveHandlerToTop($(this), pureEventName, isDelegate);
			}
		});
	}

	if (typeof $.fn.onFirst === 'undefined') {
		$.fn.onFirst = function(types, selector) {
			var type, $el = $(this),
			    isDelegated = typeof selector === 'string';

			$.fn.on.apply($el, arguments);

			// events map
			if (typeof types === 'object') {
				for (type in types) {
					if (types.hasOwnProperty(type)) {
						moveEventHandlers($el, type, isDelegated);
					}
				}
			} else if (typeof types === 'string') {
				moveEventHandlers($el, types, isDelegated);
			}

			return $el;
		};
	}

	function attach_event() {
		// https://www.sitepoint.com/jquery-body-on-document-on/
		var elem = $(document); // `html` or `body` doesn't work with some browsers

		elem.onFirst('click contextmenu', 'a', function (event) {
			// attr() returns 'string' or 'undefined'
			var $this = $(this),
			    href  = $this.attr('href'),
			    rel   = $this.attr('rel' ),
			    admin = is_valid_uri(parse_uri(href)) ? is_admin(href) : -2; // do nothing if uri is not valid

			// if context menu then continue and should be checked in check_nonce()
			if ('contextmenu' === event.type) {
				return true;
			}

			// if admin area (except in comment with nofollow) then add a nonce
			else if (admin === 1) {
				$this.attr('href', add_query_nonce(
					href, (!rel || rel.indexOf('nofollow') < 0) ? nonce : 'nofollow'
				));
				return true;
			}

			// if internal then check network admin url
			else if (admin === 0) {
				IP_GEO_BLOCK_ZEP.redirect(href);
				return true;
			}

			// if external then redirect with no referrer not to leak out the nonce
			else if (admin === -1 && is_back_end()) {
				if ('_self' === $this.attr('target')) {
					IP_GEO_BLOCK_ZEP.redirect(href);
					return true;
				}

				href = escapeHTML(decodeURIComponent(this.href));
				href = href.split(';', 2).shift(); // avoid `url=...;url=javascript:...`

				admin = window.open();
				admin.document.write(
					'<!DOCTYPE html><html><head>' +
					'<meta name="referrer" content="never" />' +
					'<meta name="referrer" content="no-referrer" />' +
					'<meta http-equiv="refresh" content="0; url=' + href + '" />' +
					($('body').hasClass('webview') ? '<script>window.location.replace("' + href + '")</script>' : '') +
					'</head></html>'
				);
				admin.document.close();

				// stop event propagation
				event.stopImmediatePropagation();

				// automatically call event.stopPropagation() and event.preventDefault()
				return false;
			}
		});

		elem.onFirst('submit', 'form', function (event) {
			var $this = $(this),
			    action = $this.attr('action'); // possibly 'undefined'

			// if admin area then add the nonce
			if (is_admin(action) === 1) {
				$this.attr('action', add_query_nonce(action, nonce));
			}
		});
	}

	function attach_ready() {
		// avoid conflict with "Open external links in a new window"
		if (is_back_end()) {
			$('a').each(function () {
				if(!this.hasAttribute('onClick') && is_admin(this.getAttribute('href')) === -1) {
					this.setAttribute('onClick', 'javascript:void(0);return false;');
				}
			});
		}

		$('img').each(function (index) {
			var src = $(this).attr('src');

			// if admin area
			if (is_admin(src) === 1) {
				$(this).attr('src', add_query_nonce(src, nonce));
			}
		});

		// Restore post revisions (wp-admin/revisions.php @since 2.6.0)
		if ('undefined' !== typeof window._wpRevisionsSettings) {
			var i, data = window._wpRevisionsSettings.revisionData,
			    n = data.length;

			for (i = 0; i < n; ++i) {
				if (-1 === data[i].restoreUrl.indexOf(IP_GEO_BLOCK_ZEP.auth)) {
					window._wpRevisionsSettings.revisionData[i].restoreUrl = add_query_nonce(data[i].restoreUrl, nonce);
				}
			}
		}
	}

	// fallback on error
	$(window).on('error', function () {
		if (!IP_GEO_BLOCK_ZEP.init) {
			attach_ready();
		}
	});

	$(function () {
		attach_ready();
		IP_GEO_BLOCK_ZEP.init = true; // finish to attach event
	});

	// attach event to add nonce
	attach_event();
}(jQuery, document));