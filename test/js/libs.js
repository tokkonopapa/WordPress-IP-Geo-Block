/*jslint white: true */
/**
 * Avoid `console` errors in browsers that lack a console.
 *
 */
!function(){for(var e,n=function(){},o=["assert","clear","count","debug","dir","dirxml","error","exception","group","groupCollapsed","groupEnd","info","log","markTimeline","profile","profileEnd","table","time","timeEnd","timeline","timelineEnd","timeStamp","trace","warn"],i=o.length,r=window.console=window.console||{};i--;)e=o[i],r[e]||(r[e]=n)}();

/**
 * Sanitize strings
 *
 */
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

/**
 * Strip tags
 *
 */
function strip_tags(html) {
	if ('string' === typeof html) {
		// keep p tags
		var match = html.match(/<p.*?\/p>/gi);
		if (match) {
			var text, i;
			for (i = 0; i < match.length; i++) {
				// remove tags
				// https://qiita.com/miiitaka/items/793555b4ccb0259a4cb8
				if (text = match[i].replace(/<("[^"]*"|'[^']*'|[^'">])*>/g, '')) {
					html = text;
					break;
				}
			}
		}

		// trim spaces
		html = html.replace(/[\s]+/g, ' ');
		html = html.replace(/^[\s]+|[\s]+$/g, '');

		return html;
	} else {
		return null;
	}
}

/**
 * Parse url
 *
 */
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

/**
 * Returns trailing name component of path.
 * @link http://phpjs.org/functions/basename/
 */
function basename(path, suffix) {
	var b = path,
	    lastChar = b.charAt(b.length - 1);

	if (lastChar === '/' || lastChar === '\\') {
		b = b.slice(0, -1);
	}

	b = b.replace(/^.*[\/\\]/g, '');

	if (typeof suffix === 'string' && b.substr(b.length - suffix.length) == suffix) {
		b = b.substr(0, b.length - suffix.length);
	}

	return b;
}

/**
 * Returns parent directory's path.
 * @link http://phpjs.org/functions/dirname/
 */
function dirname(path) {
	return path.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '');
}

/**
 * Returns top directory's path.
 * @link http://phpjs.org/functions/dirname/
 */
function dirtop(path) {
	return (path.split('/', 2))[0];
}

/**
 * Removes whitespace from both ends of a string. 
 * @link https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/String/trim
 */
if (!String.prototype.trim) {
	String.prototype.trim = function () {
		return this.replace(/^\s+|\s+$/g,'');
	};
}

/**
 * Strip whitespace (or other characters) from the beginning and end of a string.
 * @link http://phpjs.org/functions/trim/
 */
function php_trim(str, charlist) {
	var whitespace, l = 0, i = 0;
	str += '';

	if (!charlist) {
		// default list
		whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
	} else {
		// preg_quote custom list
		charlist += '';
		whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
	}

	l = str.length;
	for (i = 0; i < l; i++) {
		if (whitespace.indexOf(str.charAt(i)) === -1) {
			str = str.substring(i);
			break;
		}
	}

	l = str.length;
	for (i = l - 1; i >= 0; i--) {
		if (whitespace.indexOf(str.charAt(i)) === -1) {
			str = str.substring(0, i + 1);
			break;
		}
	}

	return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
}

/**
 * Strip whitespace (or other characters) from the end of a string.
 * @link http://phpjs.org/functions/rtrim/
 */
function php_rtrim(str, charlist) {
	charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
	var re = new RegExp('[' + charlist + ']+$', 'g');
	return (str + '').replace(re, '');
}

/**
 * Appends a trailing slash if it does not exist at the end.
 *
 */
function trailingslashit(str) {
//	if (typeof(url) !== 'undefined' && url.substr(-1) === '/')
//		url = url.substr(0, url.length - 1);
//	return url + '/';
	return untrailingslashit(str) + '/';
}

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 */
function untrailingslashit(str) {
	return php_rtrim(str, '/');
}

/**
 * Get a random integer of from min to max
 *
 */
function get_random_int(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * Make a possibly valid IP address (IPv4)
 *
 */
function get_random_ip() {
	var ip =
		get_random_int(11, 230) + '.' +
		get_random_int( 0, 255) + '.' +
		get_random_int( 0, 255) + '.' +
		get_random_int( 0, 255);
	return ip;
}

function combine_ip(ip, country) {
	return ip + ' ' + country;
}

function retrieve_ip(ip) {
	ip = ip.split(' ', 2);
	ip = ip.shift();
	return ip;
}

/**
 * Serialize plain object and array to the type of form
 *
 */
function serialize_plain(obj) {
	var data = [];
	for (var key in obj) {
		if (key && obj.hasOwnProperty(key)) {
			data.push(key + '=' + encodeURIComponent(obj[key]));
		}
	}
	return data.length ? data.join('&') : '';
}

function serialize_array(obj) {
	var data = [];
	for (var i = 0; i < obj.key.length; i++) {
		if (obj.key[i]) {
			data.push(obj.key[i] + '=' + encodeURIComponent(obj.val[i]));
		}
	}
	return data.length ? data.join('&') : '';
}