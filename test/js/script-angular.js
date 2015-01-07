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
/*	// remove inside the specified tags
	var tags = ['title', 'style', 'h\\d', 'a'];

	// /<tag[^>]+?\/>|<tag(.|\s)*?\/tag>/gi
	for (var regexp, i = 0; i < tags.length; i++) {
		regexp = new RegExp('<(' + tags[i] + ')(.|\\s)*?\\/\\1>', 'gi');
		html = html.replace(regexp, '');
	}

	// strip tags
	html = $('<div>').html(html).text();
*/
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

/**
 * Show a message
 *
 */
function message(msg) {
	var elm = document.getElementById('message');
	if (elm) {
		elm.value = msg ? elm.value + msg : '';
	} else {
		console.log(msg);
	}
}

/**
 * Geolocation API
 * These APIs need to respond `Access-Control-Allow-Origin` in header.
 */
var geolocation_apis = [
	{
		api: 'Telize',
		url: 'http://www.telize.com/geoip/%API_IP%',
		fmt: 'jsonp',
		type: 'IPv4, IPv6',
		get: function (data, type) {
			switch (type) {
				case 'name' : return data.country || null;
				case 'code' : return data.country_code || null;
				case 'error': return 'not found';
			}
			return null;
		},
	}
	,{
		api: 'ip-api',
		url: 'http://ip-api.com/%API_FMT%/%API_IP%',
		fmt: 'json',
		type: 'IPv4',
		get: function (data, type) {
			switch (type) {
				case 'name' : return data.country || null;
				case 'code' : return data.countryCode || null;
				case 'error': return data.message || null;
			}
			return null;
		},
	}
	/**
	 * APIs that doesn't support CORS.
	 * These are accessed through https://developer.yahoo.com/yql/
	 */
	,{
		api: 'Pycox',
		url: 'https://query.yahooapis.com/v1/public/yql?q=select * from %API_FMT% where url="http://ip.pycox.com/%API_FMT%/%API_IP%"&format=%API_FMT%&jsonCompat=new',
		fmt: 'json',
		type: 'IPv4',
		get: function (data, type) {
			switch (type) {
				case 'name' :
					if (typeof data.query.results.json !== 'undefined')
						return data.query.results.json.country_name;
					break;
				case 'code' :
					if (typeof data.query.results.json !== 'undefined')
						return data.query.results.json.country_code
					break;
				case 'error':
					return data.query.results.error;
			}
			return null;
		},
	}
	,{
		api: 'Nekudo',
		url: 'https://query.yahooapis.com/v1/public/yql?q=select * from %API_FMT% where url="http://geoip.nekudo.com/api/%API_IP%"&format=%API_FMT%&jsonCompat=new',
		fmt: 'json',
		type: 'IPv4, IPv6',
		get: function (data, type) {
			switch (type) {
				case 'name' :
					if (typeof data.query.results.json.country !== 'undefined')
						return data.query.results.json.country.name;
					break;
				case 'code' :
					if (typeof data.query.results.json.country !== 'undefined')
						return data.query.results.json.country.code;
					break;
				case 'error':
					return data.query.results.json.msg;
			}
			return null;
		},
	}
];

var language = {
	'en': {
		main_title: 'WordPress POST Access Emulator',
		page_settings: 'Page Settings',
		page_readme: 'After setting the followings, click <span class="highlight">Validate</span> to validate the pages.',
		home_url: 'WordPress Home',
		single_page: 'Single Page',
		ip_address: 'Proxy IP Address',
		note_ip_address: 'You should add <span class="highlight"><code>HTTP_X_FORWARDED_FOR</code></span> into <span class="highlight"><code>$_SERVER</code> keys for extra IPs</span> on <span class="highlight">Settings</span> tab of IP Geo Block in order to emulate the post from outside your nation.',
		submit_post: 'POST Access',
		post_settings: 'POST Settings',
		required: 'Required',
		cb_post_items: 'Select All',
		post_comment: 'Post Comment',
		author: 'Name',
		email: 'Email',
		site_url: 'Site URL',
		comment: 'Comment',
		trackback: 'Trackback',
		title: 'Title',
		excerpt: 'Excerpt',
		trackback_url: 'Trackback URL',
		blog_name: 'Blog Name',
		login: 'Login Form',
		user_name: 'User Name',
		password: 'Password',
		admin_area: 'Admin Area',
		admin_cookie: 'Admin Cookie',
		admin_ajax: 'Admin Ajax',
		action: 'Action',
		pingback: 'Pingback',
		pingback_readme: '<span class="highlight"><code>%WP_HOME%</code></span> will be replaced with <span class="highlight">WordPress HOME</span>.',
		xmlrpc: 'XML-RPC',
		xmlrpc_readme: '<span class="highlight"><code>%USER_NAME%</code></span> and <span class="highlight"><code>%PASSWORD%</code></span> will be replaced with settings in <span class="highlight">Login Form</span>.',
		xmlrpc_demo: 'XML-RPC Demo',
		end: ''
	},

	'ja': {
		main_title: 'WordPressへのPOSTアクセス',
		page_settings: 'ページ設定',
		page_readme: '以下を適切に設定した後、<span class="highlight">Validate</span> を実行し、正当性を確認して下さい。',
		home_url: 'WordPressホーム',
		single_page: 'シングルページ',
		ip_address: 'プロキシアドレス',
		note_ip_address: '海外からの投稿を模擬するために、IP Geo Block の <span class="highlight">設定</span> タブで、<span class="highlight">追加検証する<code>$_SERVER</code>のキー</span> に <span class="highlight"><code>HTTP_X_FORWARDED_FOR</code></span> を設定しておいて下さい。',
		submit_post: 'POSTアクセス',
		post_settings: 'POST設定',
		required: '必須',
		cb_post_items: '全選択',
		post_comment: 'コメント投稿',
		author: '名前',
		email: 'Eメール',
		site_url: 'サイトURL',
		comment: 'コメント',
		trackback: 'トラックバック',
		title: 'タイトル',
		excerpt: '抜粋',
		trackback_url: 'トラックバックURL',
		blog_name: 'ブログ名',
		login: 'ログインフォーム',
		user_name: 'ユーザー名',
		password: 'パスワード',
		admin_area: '管理領域',
		admin_cookie: '管理者クッキー',
		admin_ajax: '管理領域のAjax',
		action: 'アクション',
		pingback: 'ピンバック',
		pingback_readme: '<span class="highlight"><code>%WP_HOME%</code></span> は <span class="highlight">WordPressホーム</span> の設定値に置き換えられます。',
		xmlrpc: 'XML-RPC',
		xmlrpc_readme: '<span class="highlight"><code>%USER_NAME%</code></span>、<span class="highlight"><code>%PASSWORD%</code></span> は <span class="highlight">ログインフォーム</span> の設定値に置き換えられます。',
		xmlrpc_demo: 'XML-RPC デモ',
		end: ''
	}
};

/**
 * Initialize Application Module
 *
 */
var postEmulatorApp = angular.module('WPApp', ['ngSanitize', 'ngCookies', 'ngAnimate'])
.config(['$httpProvider', function($httpProvider) {
	$httpProvider.defaults.timeout = 10000;
}])
.run(function() {
	console.log('run');
});

/**
 * Exception Handler
 *
 */
postEmulatorApp.factory('$exceptionHandler', ['$window', function ($window) {
	return function (exception, cause) {
		message(exception.message + "\n");
	};
}]);

/**
 * Controller (too heavy!!)
 *
 */
postEmulatorApp.controller('appCtrl', ['$scope', '$cookies', '$http',
function($scope, $cookies, $http) {
	// Language
	var lang = window.navigator.userLanguage || window.navigator.language;
	$scope.lang = language[lang.indexOf('ja') !== -1 ? 'ja' : 'en']

	// Post Comment
	$scope.formComment = {
		author: 'spam-master',
		email: 'spam@example.com',
		url: 'http://example.com/',
		comment: 'This is a spam comment.'
	};

	// Trackback
	$scope.formTrackback = {
		title: 'About spam',
		excerpt: 'This is a trackback spam.',
		url: 'http://example.com/',
		blog_name: 'Spammer\'s'
	};

	// Login
	$scope.formLogin = {
		log: 'admin',
		pwd: '0123abcd'
	};

	// Admin Area
	$scope.formAdminArea = {
		cookie: 'wordpress_test_cookie=WP+Cookie+check'
	};

	// Admin Ajax
	$scope.formAdminAjax = {
		action: 'myAction'
	};

	// Pingback
	$scope.formPingback = {
		xml: 
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodcall>\n" +
"    <methodname>\n" +
"        pingback.ping\n" +
"    </methodname>\n" +
"    <params>\n" +
"        <param>\n" +
"            <value>\n" +
"                <string>\n" +
"                    http://example.com/\n" +
"                </string>\n" +
"            </value>\n" +
"        </param>\n" +
"        <param>\n" +
"            <value>\n" +
"                <string>\n" +
"                    %WP_HOME%\n" +
"                </string>\n" +
"            </value>\n" +
"        </param>\n" +
"    </params>\n" +
"</methodcall>"
	};

	// XML-RPC
	$scope.formXmlrpc = {
		xml:
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodcall>\n" +
"    <methodname>\n" +
"        wp.getUsers\n" +
"    </methodname>\n" +
"    <params>\n" +
"        <param>\n" +
"            <value>\n" +
"                <string>\n" +
"                    %USER_NAME%\n" +
"                </string>\n" +
"            </value>\n" +
"        </param>\n" +
"        <param>\n" +
"            <value>\n" +
"                <string>\n" +
"                    %PASSWORD%\n" +
"                </string>\n" +
"            </value>\n" +
"        </param>\n" +
"        <param>\n" +
"            <value>\n" +
"                <string>\n" +
"                    \n" +
"                </string>\n" +
"            </value>\n" +
"        </param>\n" +
"    </params>\n" +
"</methodcall>"
	};

	// XML-RPC Demo
	$scope.formXmlrpcDemo = {
		xml:
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodcall>\n" +
"    <methodname>\n" +
"        demo.sayHello\n" +
"    </methodname>\n" +
"</methodcall>"
	};

	// Checkbox and Toggle
	$scope.checkbox = {};
	$scope.selectAll = function(state) {
		if (typeof state !== 'undefined')
			state = true;
		else if ($scope.checkbox.post_items)
			state = true;
		else
			state = false;
		var checkbox = [
			'post_items',
			'comment',
			'trackback',
			'login',
			'admin_area',
			'admin_ajax',
			'pingback',
			'xmlrpc',
			'xmlrpc_demo'
		];
		for (var i = 0; i < checkbox.length; i++) {
			$scope.checkbox[checkbox[i]] = state;
		}
	};
	$scope.selectAll(true);
	$scope.show = {};
	$scope.toggle = function(item) {
		$scope.show[item] = !$scope.show[item];
	};

	// Message
	var messageOut = function(title, msg) {
		message(title + ': ' + msg + "\n");
	};
	var messageClear = function() {
		message(null);
	};

	// Home URL
	var url = $cookies['home-url'];
	if (url) {
		$scope.home_url = url;
	} else {
		url = parse_uri(location.href);
		$scope.home_url = url['scheme'] + '://' + url['authority'] + '/';
	}

	// Single Page
	url = $cookies['single-page'];
	if (url) {
		$scope.single_page = url;
	} else {
		$scope.single_page = trailingslashit($scope.home_url) + '?p=0';
	}

	/**
	 * Validate if the page is WordPress
	 *
	 */
	$scope.validate_home = function() {
		var title = 'WordPress Home';

		var deferred = $http({
			method: 'GET',
			url: $scope.home_url
		})

		.success(function(data, status, headers, config) {
			if (-1 !== data.indexOf('wp-content')) {
				$cookies['home-url'] = $scope.home_url;
				messageOut(title, 'Site is OK.');
			} else {
				messageOut(title, 'Can\'t find "wp-content".');
			}
		})

		.error(function(data, status, headers, config) {
			if (status) {
				messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + status);
			}
		});
		
		return deferred;
	};

	/**
	 * Validate if the comment form in the page
	 *
	 */
	$scope.validate_page = function() {
		var title = 'Single Page';

		var deferred = $http({
			method: 'GET',
			url: $scope.single_page
		})

		.success(function(data, status, headers, config) {
			// Extract canonical URL
			var regexp = /<link[^>]+?rel=(['"]?)canonical\1.+/i;
			var match = data.match(regexp);
			if (match && match.length) {
				var canonical = match[0].replace(/.*href=(['"]?)(.+?)\1.+/, '$2');
				if ($scope.single_page !== canonical) {
					$scope.single_page = canonical;
				}
			}

			// Extract ID of the post
			regexp = /<input[^>]+?comment_post_ID.+?>/i;
			match = data.match(regexp);
			if (match && match.length) {
				// if found then set ID into form and cookie
				$cookies['single-page'] = $scope.single_page;

				$scope.comment_post_id = match[0].replace(/[\D]/g, '');
				messageOut(title, 'Comment form is OK.');
			} else {
				// if not found then set ID as zero
				$scope.comment_post_id = 0;
				messageOut(title, 'Can\'t find comment form.');
			}
		})

		.error(function(data, status, headers, config) {
			if (status) {
				messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + status);
			}
		});
		
		return deferred;
	};

	/**
	 * Generate random IP address and check the country
	 *
	 */
	$scope.generate_ip = function() {
		var ip = $scope.ip_address = get_random_ip();
		var api = geolocation_apis[get_random_int(0, geolocation_apis.length-1)];
		var url = api.url
			.replace('%API_IP%', ip)
			.replace(/%API_FMT%/g, api['fmt']);

		$http({
			url: url,
			method: 'GET'
		})

		.success(function(data, status, headers, config) {
			var msg = api.get(data, 'name');
			if (msg)
				msg += ' (' + api.get(data, 'code') + ')';
			else
				msg = api.get(data, 'error') + ' (' + api.api + ')';

			$scope.ip_address = combine_ip(ip, msg);
		})

		.error(function(data, status, headers, config) {
			if (data) {
				var msg = strip_tags(data);
				messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + msg);
			}
		});
	};
	$scope.generate_ip();

	/**
	 * Post form data
	 *
	 */
	var post_form = function(title, url, form) {
		var adrs = retrieve_ip($scope.ip_address);

		// Post the comment with `X-Forwarded-For` header
		$http({
			url: url,
			method: 'POST',
			data: form,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-Forwarded-For': adrs
			}
		})

		// In case of the comment being accepted
		.success(function(data, status, headers, config) {
			messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + status);
		})

		// In case of the comment being denied
		.error(function(data, status, headers, config) {
			if (data) {
				var msg = strip_tags(data);
				messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + msg);
			}
		});
	};

	/**
	 * Post XML data
	 *
	 */
	var post_xml = function(title, url, xml) {
		var adrs = retrieve_ip($scope.ip_address);
		xml = xml.replace(/\s*([<>])\s*/g, '$1');

		$http({
			url: url,
			method: 'POST',
			data: xml,
			headers: {
				'Content-Type': 'application/xml',
				'X-Forwarded-For': adrs
			}
		})

		.success(function(data, status, headers, config) {
			messageOut(title, data.status + ' ' + data.statusText + ' ' + status);
		})

		.error(function(data, status, headers, config) {
			if (data) {
				var msg = strip_tags(data);
				messageOut(title, data.statusCode + ' ' + data.statusText + ' ' + msg);
			}
		});
	};

	/**
	 * Post a comment to the target page
	 *
	 */
	var post_comment = function(url) {
		post_form('Comment', url, $scope.formComment);
	};

	/**
	 * Post a trackback message
	 *
	 */
	var post_trackback = function(url) {
		// Normalize trackback url
		var trackback = parse_uri($scope.formTrackback.url);
		trackback = trackback['scheme'] + '://' + trackback['authority'] + '/'

		// Every time trackback url should be changed
		$scope.formTrackback.url = trackback + '#' + get_random_int(1000, 9999);

		post_form('Trackback', url, $scope.formTrackback);
	}

	/**
	 * Access to login form
	 *
	 */
	var post_login = function(url) {
		post_form('Login Form', url, $scope.formLogin);
	};

	/**
	 * Access to admin area
	 *
	 */
	var post_admin = function(url) {
		post_form('Admin Area', url, $scope.formAdminArea);
	};

	/**
	 * Access to admin ajax
	 *
	 */
	var post_admin_ajax = function(url) {
		post_form('Admin Ajax', url, $scope.formAdminAjax);
	};

	/**
	 * Post a pingback to XML-RPC server
	 *
	 */
	var post_pingback = function(url, page) {
		var xml = $scope.formPingback.xml;
		xml = xml.replace(/%WP_HOME%/, page);
		post_xml('Pingback', url, xml);
	};

	/**
	 * Post a remote command to XML-RPC server
	 *
	 */
	var post_xmlrpc = function(url) {
		var xml = $scope.formXmlrpc.xml;
		xml = xml.replace(/%USER_NAME%/, $scope.formAdminArea.log);
		xml = xml.replace(/%PASSWORD%/, $scope.formAdminArea.pwd);
		post_xml('XML-RPC', url, xml);
	};

	/**
	 * Post a remote command to XML-RPC server
	 *
	 */
	var post_xmlrpc_demo = function(url) {
		var xml = $scope.formXmlrpcDemo.xml;
		post_xml('XML-RPC demo', url, xml);
	};

	/**
	 * Submit
	 *
	 */
	$scope.submit = function() {
		var home = trailingslashit($scope.home_url);
		var page = trailingslashit($scope.single_page);

		// Post Comment
		if ($scope.checkbox.comment) {
			$scope.validate_page().then(function() {
				post_comment(home + 'wp-comments-post.php');
			});
		}

		// Trackback
		if ($scope.checkbox.trackback) {
			$scope.validate_page().then(function() {
				post_trackback(page + 'trackback/');
			});
		}

		// Login Form
		if ($scope.checkbox.login)
			post_login(home + 'wp-login.php');

		// Admin Area
		if ($scope.checkbox.admin_area)
			post_admin(home + 'wp-admin/');

		// Admin Ajax
		if ($scope.checkbox.admin_ajax)
			post_admin_ajax(home + 'wp-admin/admin-ajax.php');

		// Pingback
		if ($scope.checkbox.pingback)
			post_pingback(home + 'xmlrpc.php', page);

		// XML-RPC
		if ($scope.checkbox.xmlrpc)
			post_xmlrpc(home + 'xmlrpc.php');

		// XML-RPC Demo
		if ($scope.checkbox.xmlrpc_demo)
			post_xmlrpc_demo(home + 'xmlrpc.php');
	};

	$scope.reset = function() {
		messageClear();
	};
}]);