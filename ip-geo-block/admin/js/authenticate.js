/*jslint white: true */
/*!
 * Project: WP-ZEP - Zero-day exploit Prevention for wp-admin
 * Copyright (c) 2013-2019 tokkonopapa (tokkonopapa@yahoo.com)
 * This software is released under the MIT License.
 */
(function ($, window, document) {
	// https://developer.mozilla.org/docs/Web/JavaScript/Reference/Global_Objects/RegExp#Description
	var auth = IP_GEO_BLOCK_AUTH, wpzep = {
		init: false,
		regexp: new RegExp(auth.key + '(?:=|%3D)\\w+')
	},

	// regular expression to find target for is_admin()
	regexp = new RegExp(
		'^(?:' + (auth.home || '') + auth.admin
		+ '|'  + (auth.home || '') + auth.plugins
		+ '|'  + (auth.home || '') + auth.themes
		+ '|'  + (auth.admin     ) // when site url is different from home url
		+ ')(?:.*\\.php|.*\\/)?$'
	),

	// `theme-install.php` eats the query and set it to `request[browse]` as a parameter
	theme_featured = function (data) {
		var i = data.length, q = 'request%5Bbrowse%5D=' + auth.key;
		while (i-- > 0) {
			if (data[i].indexOf(q) !== -1) {
				data[i] = 'request%5Bbrowse%5D=featured'; // correct the parameter
				break;
			}
		}
		return data;
	},

	// `upload.php` eats the query and set it to `query[ip-geo-block-auth-nonce]` as a parameter
	media_library = function (data) {
		var i = data.length, q = 'query%5B' + auth.key + '%5D=';
		while (i-- > 0) {
			if (data[i].indexOf(q) !== -1) {
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

	// Check path that should be excluded
	function check_ajax(path) {
		path = path.replace(auth.home + auth.admin, '');
		return ajax_links.hasOwnProperty(path) ? ajax_links[path] : null;
	}

	// Escape string for use in HTML.
	function escapeHTML(html) {
		var elem = document.createElement('div');
		elem.appendChild(document.createTextNode(html));
		html = elem.innerHTML.replace(/["']/g, function (match) {
			return {
				'"': '&quot;',
				"'": '&#39;' //"
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
	 * Convert relative url to absolute url using browser feature
	 *
	 * @param string target url
	 * @param string base of absolute url (default window.locatoin.href)
	 * @return component of url
	 */
	var absolute_uri = (function () {
		var doc = null;

		try {
			new URL('/', 'http://example.com/'); // test if URL object is abailable
		} catch (e) {
			try {
				doc = (new DOMParser()).parseFromString('<html><head></head><body></body></html>', 'text/html'); // IE11
			} catch (f) {
				doc = document.implementation.createHTMLDocument(''); // IE10
			}
		}

		return function (url, base) {
			var d = document, baseElm, aElm, result;
			url = typeof url !== 'undefined' ? url : window.location.href;
			if (null === doc) {
				if (typeof base === 'undefined') {
					base = window.location.href; // based on current url
				}
				try {
					result = new URL(url, base); // base must be valid
				} catch (e) {
					result = new URL(url, window.location.href);
				}
			} else {
				// use anchor element to resolve url
				if (typeof base !== 'undefined') {
					// assign base element to anchor to be independent of the current document
					d = doc;
					while (d.head.firstChild) {
						d.head.removeChild(d.head.firstChild);
					}
					baseElm = d.createElement('base');
					baseElm.setAttribute('href', base);
					d.head.appendChild(baseElm);
				}
				aElm = d.createElement('a');
				aElm.setAttribute('href', url);
				aElm.setAttribute('href', aElm.href);
				//d.appendChild(aElm);

				result = {
					protocol: aElm.protocol,
					host:     aElm.host,
					hostname: aElm.hostname,
					port:     aElm.port,
					pathname: aElm.pathname,
					search:   aElm.search,
					hash:     aElm.hash,
					href:     aElm.href,
					username: '',
					password: '',
					origin :  aElm.origin || null
				};
				if ('http:' === result.protocol && '80' === result.port) {
					// remove port number `80` in case of `http` and defalut port
					result.port = '';
					result.host = result.host.replace(/:80$/, '');
				} else if ('https:' === result.protocol && '443' === result.port) {
					// remove port number `443` in case of `https` and defalut port
					result.port = '';
					result.host = result.host.replace(/:443$/, '');
				}
				if ('http:' === result.protocol || 'https:' === result.protocol) {
					if (result.pathname && result.pathname.charAt(0) !== '/') {
						// in case no `/` at the top
						result.pathname = '/' + result.pathname;
					}
					if (!result.origin) {
						result.origin = result.protocol + '//' + result.hostname + (result.port ? ':' + result.port : '');
					}
				}
			}
			if (result.username || result.password) {
				// throw an error if basic basic authentication is targeted
				throw new URIError(result.username + ':' + result.password);
			}
			return result;
		};
	}());
/*
	// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
	function encodeURIComponentRFC3986(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, function (c) {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}
*/
	// Append the nonce as query strings to the uri
	function add_query_nonce(uri, nonce) {
		if (typeof uri !== 'object') { // `string` or `undefined`
			uri = parse_uri(uri || window.location.href);
		}

		var data = uri.query ? uri.query.split('&') : [],
		    i = data.length,
		    q = auth.key + '=';

		// remove an old nonce
		while (i-- > 0) {
			if (data[i].indexOf(q) === 0) { // or `wpzep.regexp.test(data[i])`
				data.splice(i, 1);
				break;
			}
		}

		data.push(auth.key + '=' + encodeURIComponent(nonce)); //RFC3986
		uri.query = data.join('&');

		return compose_uri(uri);
	}

	// Check uri component if it is not empty or only fragment (`#...`)
	function check_uri(uri) {
		return (!uri.scheme || /^https?$/.test(uri.scheme)) && (uri.path || uri.query);
	}

	// Check uri where the nonce is needed
	// Note: in case of url in the admin area of different site, it returns 0
	function is_admin(url) {
		// parse uri and get real path
		try {
			url = url || window.location.pathname || ''; // in case of empty `action` on the form tag
		} catch (e) {
			url = '';
		}

		var uri = parse_uri(url.toLowerCase());

		// possibly scheme is `javascript` and path is `void(0);`
		if (check_uri(uri)) {
			// get absolute path with flattening `./`, `../`, `//`
			uri = absolute_uri(url);

			// external domain (`http://example` or `www.example`)
			// https://tools.ietf.org/html/rfc6454#section-4
			if (uri.origin !== window.location.origin) {
				return -1; // external
			}

			// check if uri includes the target path of zep
			url = regexp.exec(uri.pathname);
			if (url) {
				if ((0 <= url[0].indexOf(auth.admin + 'admin-')) ||
				    (0 <= url[0].indexOf(auth.admin           )) ||
				    (0 <= url[0].indexOf(auth.plugins         )) ||
				    (0 <= url[0].indexOf(auth.themes          ))) {
					return 1; // internal for admin
				}
			}
		}

		return 0; // internal not admin
	}

	// Check if current page is admin area and the target of wp-zep
	function is_backend() {
		return (is_admin(window.location.pathname) === 1 || wpzep.regexp(window.location.search));
	}

	// Check if url belongs to multisite
	function is_multisite(url) {
		var i, j, n = auth.sites.length;

		for (i = 0; i < n; ++i) {
			j = url.indexOf(auth.sites[i] + '/');
			if (0 <= j && j <= 6) { // from `//` to `https://`
				return true;
			}
		}

		return false;
	}

	// check if the uri does not need a nonce
	function is_neutral(uri) {
//		return !uri.query; // without queries
//		return !uri.query || !$('body').hasClass('wp-core-ui'); // without queries or outside dashboard
		return /\/$/.test(uri.path); // `/wp-admin/`
	}

	// check if the link has nofollow
	function has_nofollow($elem) {
		return -1 !== ($elem.attr('rel') || '').indexOf('nofollow');
	}
/*
	$.ajaxSetup({
		beforeSend: function (xhr, settings) {
			// settings: {
			//    url:         '/wp-admin/admin-ajax.php'
			//    method:      'POST' or 'GET'
			//    contentType: 'application/x-www-form-urlencoded; charset=UTF-8'
			//    data:        'action=...'
			// }
		}
	});

	// plupload
	$(window).on('BeforeUpload', function (uploader, file) {
		console.log(uploader);
	});
*/
	// Embed a nonce before an Ajax request is sent
	// $(document).ajaxSend(function (event, jqxhr, settings) {
	$.ajaxPrefilter(function (settings /*, original, jqxhr*/) {
		// POST to async-upload.php causes an error in https://wordpress.org/plugins/mammoth-docx-converter/
		if (is_admin(settings.url) === 1 && !settings.url.match(/async-upload\.php$/)) {
			// multipart/form-data (XMLHttpRequest Level 2)
			// IE10+, Firefox 4+, Safari 5+, Android 3+
			if (typeof window.FormData !== 'undefined' && settings.data instanceof FormData) {
				settings.data.append(auth.key, auth.nonce);
			}

			// application/x-www-form-urlencoded
			else {
				// Behavior of jQuery Ajax
				// method  url  url+data data
				// GET    query  query   data
				// POST   query  query   data
				var data, callback, uri = parse_uri(settings.url);

				if (typeof settings.data === 'undefined' || uri.query) {
					settings.url = add_query_nonce(uri, auth.nonce);
				} else {
					data = settings.data ? settings.data.split('&') : [];
					callback = check_ajax(window.location.pathname);
					if (callback) {
						data = callback(data);
					}
					data.push(auth.key + '=' + encodeURIComponent(auth.nonce)); //RFC3986
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
			var type, $el = $(this), isDelegated = (typeof selector === 'string');

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

	/*--------------------------------
	 * Attach event to the document
	 *--------------------------------*/
	function attach_event() {
		// https://www.sitepoint.com/jquery-body-on-document-on/
		var elem = $(document); // `html` or `body` doesn't work with some browsers

		elem.onFirst('click contextmenu', 'a', function (event) {
			var admin = 0, // default: do nothing if href is empty
			    $this = $(this),
			    href  = $this.attr('href') || '', // returns 'string' or 'undefined'
			    uri   = parse_uri(href);

			// check href has right scheme and path
			if (check_uri(uri)) {
				admin = is_admin(href);
			}

//			console.log('href:' + href, uri, 'admin:' + admin, 'is_backend:' + is_backend(), 'is_multisite:' + is_multisite(href));

			// if context menu then continue and should be checked in check_nonce()
			if ('click' !== event.type) {
				return;
			}

			// if admin area (except a link with nofollow in the comment thread) then add a nonce
			else if (admin === 1) {
				$this.attr('href', is_neutral(uri) ? href :
					add_query_nonce( href, has_nofollow($this) ? 'nofollow' : auth.nonce )
				);
			}

			// if external then redirect with no referrer not to leak out the nonce
			else if (admin === -1 && is_backend()) {
				// open within the same window
				if ('_self' === $this.attr('target') || is_multisite(href)) {
					$this.attr('href', is_neutral(uri) ? href :
						add_query_nonce( href, has_nofollow($this) ? 'nofollow' : auth.nonce )
					);
				}

				// open a new window
				else if (!this.hasAttribute('onClick')) {
					// avoid `url=...;url=javascript:...`
					href = href.split(';', 2).shift();
					href = escapeHTML(decodeURIComponent(this.href)); // & => &amp;

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

					// stop event propagation and location transition
					event.stopImmediatePropagation();
					return false; // same as event.stopPropagation() and event.preventDefault()
				}
			}
		});

		elem.onFirst('submit', 'form', function (/*event*/) {
			var $this = $(this), action = $this.attr('action'); // possibly 'undefined'

			// if admin area then add the nonce
			if (is_admin(action) === 1) {
				if ('post' === ($this.attr('method') || '').toLowerCase()) {
					$this.attr('action', add_query_nonce(action, auth.nonce));
				} else {
					// https://www.w3.org/TR/1999/REC-html401-19991224/types.html#type-name
					$this.append('<input type="hidden" name="' + auth.key + '" value="' + auth.nonce + '">');
				}
			}
		});
	}

	/*--------------------------------
	 * Something after document ready
	 *--------------------------------*/
	function attach_ready(/*stat*/) {
		if (!wpzep.init) {
			wpzep.init = true;

			$('img').each(function (/*index*/) {
				var src = $(this).attr('src');

				// if admin area
				if (is_admin(src) === 1) {
					$(this).attr('src', add_query_nonce(src, auth.nonce));
				}
			});

			// Restore post revisions (wp-admin/revisions.php @since 2.6.0)
			if ('undefined' !== typeof window._wpRevisionsSettings) {
				var i, data = window._wpRevisionsSettings.revisionData, n = data.length;

				for (i = 0; i < n; ++i) {
					if (!wpzep.regexp.test(data[i].restoreUrl)) {
						window._wpRevisionsSettings.revisionData[i].restoreUrl = add_query_nonce(data[i].restoreUrl, auth.nonce);
					}
				}
			}

			// Hide the title of sub-menu.
			$('#toplevel_page_ip-geo-block li.wp-first-item').each(function (/*i, obj*/) {
				var $this = $(this);
				$this.css('display', 'IP Geo Block' === $this.children('a').text() ? 'none' : 'block');
			});
		}
	}

	$(window).on('error', function (/*event*/) { // event.originalEvent.message
		attach_ready(); // fallback on error
	});

	$(function () {
		attach_ready();
	});

	// Attach event to add nonce
	attach_event();
}(jQuery, window, document));