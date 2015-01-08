/**
 * Avoid `console` errors in browsers that lack a console.
 *
 */
!function(){for(var e,n=function(){},o=["assert","clear","count","debug","dir","dirxml","error","exception","group","groupCollapsed","groupEnd","info","log","markTimeline","profile","profileEnd","table","time","timeEnd","timeline","timelineEnd","timeStamp","trace","warn"],i=o.length,r=window.console=window.console||{};i--;)e=o[i],r[e]||(r[e]=n)}();

/**
 * Appends a trailing slash if it does not exist at the end
 *
 */
function trailingslashit(url) {
	if (typeof(url) !== 'undefined' && url.substr(-1) === '/')
		url = url.substr(0, url.length - 1);
	return url + '/';
}

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

/**
 * Strip tags
 *
 */
function strip_tags(html) {
	if ('string' === typeof html) {
		// keep p tags
		var match = html.match(/<p.*?\/p>/gi);
		if (match) {
			for (var text, i = 0; i < match.length; i++) {
				// remove tags
				// http://qiita.com/miiitaka/items/793555b4ccb0259a4cb8
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
 * Parse url (scheme://authority/path?query#fragment)
 *
 */
function parse_uri(uri) {
	var reg = /^(?:([^:\/?#]+):)?(?:\/\/([^\/?#]*))?([^?#]*)(?:\?([^#]*))?(?:#(.*))?/;
	var m = uri.match(reg);
	if (m) {
		return {
			"scheme":m[1], "authority":m[2], "path":m[3], "query":m[4], "fragment":m[5]
		};
	} else {
		return null;
	}
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
	$ip =
		get_random_int(11, 230) + '.' +
		get_random_int( 0, 255) + '.' +
		get_random_int( 0, 255) + '.' +
		get_random_int( 0, 255);
	return $ip;
}

function combine_ip(ip, country) {
	return ip + ' ' + country;
}

function retrieve_ip(ip) {
	ip = ip.split(' ', 2);
	ip = ip.shift();
	return ip;
}