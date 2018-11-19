/*jslint white: true */
/**
 * Creating Application Module
 *
 */
var app = angular.module('WPApp', [
	'ngSanitize', // ng-bind-html
	'ngCookies',  // $cookies
	'ngAnimate',  // animate-show-hide
	'language',
	'geolocation',
	'validate-wp',
	'http-proxy'
]);

/**
 * Configuration for runtime objects
 *
 */
app.config(['$httpProvider', function ($httpProvider) {
	'use strict';
	$httpProvider.defaults.timeout = 10000;
}])
.run(function () {
	'use strict';
	console.log('run');
});

/**
 * Exception Handler
 *
 */
app.factory('$exceptionHandler', ['$window', function ($window) {
	'use strict';
	return function (exception, cause) {
		console.log(exception ? exception.message : '');
	};
}]);

/**
 * Controller (too heavy!!)
 *
 * https://www.airpair.com/angularjs/posts/top-10-mistakes-angularjs-developers-make
 * https://stackoverflow.com/questions/23382109/how-to-avoid-a-large-number-of-dependencies-in-angularjs
 */
app.controller('WPAppCtrl', [
	'$scope',
	'$cookies',
	'LanguageSvc',
	'GeolocationSvc',
	'WPValidateSvc',
	'HttpProxySvc',
	function (
		$scope,
		$cookies,
		svcLang,
		svcGeoloc,
		svcWP,
		svcProxy
	) {
	'use strict';

	// Language
	$scope.lang = svcLang();

	// Message
	var messageOut = function (title, msg) {
		$scope.message += title + ': ' + msg + "\n";
	},
	messageClear = function () {
		$scope.message = '';
	};
	messageClear();

	// Post Comment
	$scope.form = {
		comment: {
			author: 'spam-master',
			email: 'spam@example.com',
			url: 'http://example.com/',
			comment: '<script>alert("XSS")</script>',
			comment_post_ID: 1,
			comment_parent: 0
		},
		trackback: {
			title: "Hi, I'm a spam.",
			excerpt: 'This is a trackback spam.',
			url: 'http://example.com/',
			blog_name: 'Spammer\'s'
		},
		login: {
			log: 'admin',
			pwd: '0123abcd'
		},
		admin: {
			cookie: 'wordpress_test_cookie=WP+Cookie+check'
		},
		ajax: {
			key: ['action', 'data', 'file'],
			val: ['wpgdprc_process_action', '{"type":"save_setting","append":false,"option":"users_can_register","value":"1″}', '../wp-config.php']
		},
		wp_content: {
			path:   'wp-content/plugins/ip-geo-block/samples.php',
			query:  'wp-load=0'
		},
		BuddyPress: {
			path: 'register/',
			signup_username: 'anonymous',
			signup_email: 'anonymous@example.com',
			signup_password: '0123abcd',
			signup_password_confirm: '',
			signup_profile_field_ids: 1,
			signup_submit: 'Complete Sign Up',
			field_1: '',
			_wpnonce: 'abcde01234',
			_wp_http_referer: ''
		},
		bbPress: {
			path: 'forums/',
			bbp_anonymous_name: 'anonymous',
			bbp_anonymous_email: 'anonymous@example.com',
			bbp_anonymous_website: 'http://example.com',
			bbp_topic_title: 'Anonymous Topic',
			bbp_topic_content: 'Hi there, I\'m anonymous.',
			bbp_forum_id: '100',
			action: 'bbp-new-topic',
			_wpnonce: 'abcde01234',
			_wp_http_referer: ''
		},
		pingback: {
			xml:
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodCall>\n" +
"    <methodName>\n" +
"        pingback.ping\n" +
"    </methodName>\n" +
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
"</methodCall>"
		},
		xmlrpc: {
			xml:
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodCall>\n" +
"    <methodName>\n" +
"        wp.getUsers\n" +
"    </methodName>\n" +
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
"</methodCall>"
		},
		xmlrpc_demo: {
			xml:
"<?xml version='1.0' encoding='utf-8'?>\n" +
"<methodCall>\n" +
"    <methodName>\n" +
"        demo.sayHello\n" +
"    </methodName>\n" +
"</methodCall>"
		},
		xmlrpc_multi: {
			xml:
"<?xml version='1.0' encoding='UTF-8'?>\n" +
"<methodCall>\n" +
"    <methodName>system.multicall</methodName>\n" +
"    <params>\n" +
"        <param>\n" +
"            <value>\n" +
"                <array>\n" +
"                    <data>\n" +
"                        <value>\n" +
"                            %METHODS%\n" +
"                        </value>\n" +
"                    </data>\n" +
"                </array>\n" +
"            </value>\n" +
"        </param>\n" +
"    </params>\n" +
"</methodCall>",
			repeat: 10,
			method:
"<struct>\n" +
"    <member>\n" +
"        <name>methodName</name>\n" +
"        <value>\n" +
"            <string>wp.getAuthors</string>\n" +
"        </value>\n" +
"    </member>\n" +
"    <member>\n" +
"        <name>params</name>\n" +
"        <value>\n" +
"            <array>\n" +
"                <data>\n" +
"                    <value>\n" +
"                        <string>1</string>\n" +
"                    </value>\n" +
"                    <value>\n" +
"                        <string>%USER_NAME%</string>\n" +
"                    </value>\n" +
"                    <value>\n" +
"                        <string>%PASSWORD%</string>\n" +
"                    </value>\n" +
"                </data>\n" +
"            </array>\n" +
"        </value>\n" +
"    </member>\n" +
"</struct>"
		},
		upload: {
			content: '',
			filename: 'test.gif\\0.php',
			disabled: false
		}
	};

	// Checkbox and Toggle
	$scope.checkbox = {
		post_items: true,
		comment: true,
		trackback: true,
		login: true,
		admin_area: true,
		admin_ajax: true,
		admin_ajax_get: true,
		admin_ajax_post: true,
		admin_post: true,
		wp_content: true,
		BuddyPress: true,
		bbPress: true,
		pingback: true,
		xmlrpc: true,
		xmlrpc_demo: true,
		xmlrpc_multi: true,
		upload: true
	};
	$scope.selectAll = function () {
		var item;
		for (item in $scope.checkbox) {
			if ($scope.checkbox.hasOwnProperty(item)) {
				$scope.checkbox[item] = $scope.checkbox.post_items;
			}
		}
	};
	$scope.show = {};
	$scope.toggle = function (item) {
		$scope.show[item] = !$scope.show[item];
	};

	// Home URL
	var url = $cookies.get('home-url');
	if (!url) {
		url = parse_uri(location.href);
		url = url.scheme + '://' + url.authority + '/';
	}
	$scope.home_url = url;

	// Single Page
	url = $cookies.get('single-page');
	if (!url) {
		url = trailingslashit($scope.home_url) +
			'?p=' + $scope.form.comment.comment_post_ID;
	}
	$scope.single_page = url;

	/**
	 * Check WordPress pages
	 *
	 */
	$scope.validate_home = function () {
		return svcWP.validate_home($scope.home_url).then(function (res) {
			$cookies.put('home-url', $scope.home_url);

			// Update Single Page
			var home = parse_uri($scope.home_url),
			    page = parse_uri($scope.single_page);
			home.path = untrailingslashit(home.path);
			page.path = page.path.replace(home.path, '');
			$scope.single_page =
				(home.scheme + '://' + home.authority + home.path) +
				(page.path     ?       page.path     : '') +
				(page.query    ? '?' + page.query    : '') +
				(page.fragment ? '#' + page.fragment : '');

			// Update BuddyPress
			$scope.form.BuddyPress._wp_http_referer = $scope.home_url + $scope.form.BuddyPress.path;

			messageOut('WordPress Home', res.stat);
		});
	};

	$scope.validate_page = function (echo) {
		return svcWP.validate_page($scope.single_page).then(function (res) {
			$scope.single_page = res.url;
			$scope.form.comment.comment_post_ID = res.id;
			$cookies.put('single-page', res.url);
			if (echo) {
				messageOut('Single Page', res.stat);
			}

			// Delete cookie of cache
			$cookies.put('ip_geo_block_cache', '');
		});
	};

	/**
	 * Generate random IP address and check the country
	 *
	 */
	$scope.generate_ip = function () {
		$scope.ip_address = get_random_ip();
//		svcGeoloc.get_geolocation($scope.ip_address).then(function (ip) {
		svcGeoloc.get_geolocation($scope.ip_address, function (ip) {
			$scope.ip_address = ip;
		});
	};
	$scope.generate_ip();

	/**
	 * Post a comment to the target page
	 *
	 */
	var post_comment = function (url, proxy) {
		$scope.form.comment.comment =
			$scope.form.comment.comment.replace(
				/(XSS)(?:#?\d*)/, "$1#" + get_random_int(1000, 9999)
			);
		var form = serialize_plain($scope.form.comment);
		svcProxy.post_form(url, proxy, 'POST', form).then(function (res) {
			messageOut('Comment', res.stat);
		});
	},

	/**
	 * Post a trackback message
	 *
	 */
	post_trackback = function (url, proxy) {
		var uri, form, excerpt;

		// Normalize trackback url, randomize post contents
		uri = parse_uri($scope.form.trackback.url);
		excerpt = $scope.form.trackback.excerpt;
		$scope.form.trackback.url = uri.scheme + '://' + uri.authority + '/';
		$scope.form.trackback.excerpt += ' #' + get_random_int(1000, 9999);

		form = serialize_plain($scope.form.trackback);
		$scope.form.trackback.excerpt = excerpt;

		svcProxy.post_form(url, proxy, 'POST', form).then(function (res) {
			messageOut('Trackback', res.stat.replace(
				/<("[^"]*"|'[^']*'|[^'">])*>/g, ''
			));
		});
	},

	/**
	 * Post form
	 *
	 */
	post_form = function (url, proxy, method, form, message) {
		svcProxy.post_form(url, proxy, method, form).then(function (res) {
			messageOut(message, res.stat);
		});
	},

	/**
	 * Post a pingback to XML-RPC server
	 *
	 */
	post_pingback = function (url, page, proxy) {
		var xml = $scope.form.pingback.xml;
		xml = xml.replace(/%WP_HOME%/, page);
		svcProxy.post_xml(url, proxy, xml).then(function (res) {
			messageOut('Pingback', res.stat);
		});
	},

	/**
	 * Post a remote command to XML-RPC server
	 *
	 */
	post_xmlrpc = function (url, proxy) {
		var xml = $scope.form.xmlrpc.xml;
		xml = xml.replace(/%USER_NAME%/, $scope.form.login.log);
		xml = xml.replace(/%PASSWORD%/, $scope.form.login.pwd);
		svcProxy.post_xml(url, proxy, xml).then(function (res) {
			messageOut('XML-RPC', res.stat); 
		});
	},

	/**
	 * Post a remote command to XML-RPC server
	 *
	 */
	post_xmlrpc_demo = function (url, proxy) {
		var xml = $scope.form.xmlrpc_demo.xml;
		svcProxy.post_xml(url, proxy, xml).then(function (res) {
			messageOut('XML-RPC Demo', res.stat);
		});
	},

	/**
	 * Post a remote multiple command to XML-RPC server
	 *
	 */
	post_xmlrpc_multi = function (url, proxy) {
		var i, j, r = '',
		    n = $scope.form.xmlrpc_multi.repeat,
		    xml = $scope.form.xmlrpc_multi.xml;
		for (i = 0; i < n; ++i) {
			j = $scope.form.xmlrpc_multi.method;
			j = j.replace(/%USER_NAME%/, $scope.form.login.log);
			j = j.replace(/%PASSWORD%/, $scope.form.login.pwd);
			r += j + "\n";
		}
		xml = xml.replace(/%METHODS%/, r);
		svcProxy.post_xml(url, proxy, xml).then(function (res) {
			messageOut('XML-RPC Multi', res.stat);
		});
	},

	post_upload = function (url, proxy, content, filename) {
		svcProxy.post_upload(url, proxy, content, filename, function (res) {
			messageOut('File upload', res);
			$scope.$apply(); // data binding to update view
		});
	},

	post_upload2 = function (url, proxy, file, filename) {
		svcProxy.post_upload2(url, proxy, file, filename).then(function (res) {
			messageOut('File upload', res.stat);
		});
	};

	/**
	 * Submit
	 *
	 */
	$scope.submit = function () {
		var url, form,
		    home = trailingslashit($scope.home_url),
		    page = trailingslashit($scope.single_page),
		    proxy = retrieve_ip($scope.ip_address);

		// Post Comment
		if ($scope.checkbox.comment) {
			$scope.validate_page(false).then(function () {
				post_comment(home + 'wp-comments-post.php', proxy);
			});
		}

		// Trackback
		if ($scope.checkbox.trackback) {
			$scope.validate_page(false).then(function () {
//				url = home + 'wp-trackback.php?p=' + $scope.form.comment.comment_post_ID;
//				url = home + 'wp-trackback.php/' + $scope.form.comment.comment_post_ID;
				url = page + 'trackback/'; // doesn't work in WordPress 4.4, works in WordPress 4.7
				post_trackback(url, proxy);
			});
		}

		// Pingback
		if ($scope.checkbox.pingback) {
			post_pingback(home + 'xmlrpc.php', page, proxy);
		}

		// XML-RPC
		if ($scope.checkbox.xmlrpc) {
			post_xmlrpc(home + 'xmlrpc.php', proxy);
		}

		// XML-RPC Demo
		if ($scope.checkbox.xmlrpc_demo) {
			post_xmlrpc_demo(home + 'xmlrpc.php', proxy);
		}

		// XML-RPC Multi
		if ($scope.checkbox.xmlrpc_multi) {
			post_xmlrpc_multi(home + 'xmlrpc.php', proxy);
		}

		// Login Form
		if ($scope.checkbox.login) {
			form = serialize_plain($scope.form.login);
			post_form(home + 'wp-login.php', proxy, 'POST', form, 'Login Form');
		}

		// Admin Area
		if ($scope.checkbox.admin_area) {
			form = serialize_plain($scope.form.admin);
			post_form(home + 'wp-admin/', proxy, 'POST', form, 'Admin Area');
		}

		// Admin Ajax and post
		if ($scope.checkbox.admin_ajax) {
			url = home + 'wp-admin/admin-';
			form = serialize_array($scope.form.ajax);

			if ($scope.checkbox.admin_ajax_get) {
				post_form(url + 'ajax.php', proxy, 'GET', form,  'Admin Ajax (GET)');
			}

			if ($scope.checkbox.admin_ajax_post) {
				post_form(url + 'ajax.php', proxy, 'POST', form, 'Admin Ajax (POST)');
			}
	
			if ($scope.checkbox.admin_post) {
				post_form(url + 'post.php', proxy, 'POST', form, 'Admin Post');
			}
		}

		// Plugins / Themes
		if ($scope.checkbox.wp_content) {
			url = home + $scope.form.wp_content.path;
			form = $scope.form.wp_content.query;
			post_form(url, proxy, 'GET', form, 'Plugins / Themes (GET)');
		}

		// BuddyPress
		if ($scope.checkbox.BuddyPress) {
			// hidden parameters
			$scope.form.BuddyPress._wp_http_referer = home + $scope.form.BuddyPress.path;
			$scope.form.BuddyPress.signup_password_confirm = $scope.form.BuddyPress.signup_password;
			$scope.form.BuddyPress.field_1 = $scope.form.BuddyPress.signup_username;

			url = home + $scope.form.BuddyPress.path;
			form = serialize_plain($scope.form.BuddyPress);
			post_form(url, proxy, 'POST', form, 'BuddyPress');
//			var form = new FormData(document.getElementById('BuddyPress'));
//			post_form(url, proxy, 'MULTI', form, 'BuddyPress');
		}

		// bbPress
		if ($scope.checkbox.bbPress) {
			// Get url to the forum
			svcWP.get_forum(home + trailingslashit(dirtop($scope.form.bbPress.path))).then(function (res) {
				$scope.form.bbPress.path = res.url.replace(home, '');

				// hidden parameters
				$scope.form.bbPress._wp_http_referer = home + $scope.form.bbPress.path;

				url = home + $scope.form.bbPress.path;
				form = serialize_plain($scope.form.bbPress);
				post_form(url, proxy, 'POST', form, 'bbPress');
			});
		}

		// File upload
		if ($scope.checkbox.upload) {
			url = home;
			$scope.form.upload.content = document.getElementById('upload-content').value;
			if ($scope.form.upload.content) {
				post_upload2(url, proxy, $scope.file, $scope.form.upload.filename);
			} else {
				/*var form = new FormData();
				var file = new File([], $scope.form.upload.filename);
				form.append('file', file);
				post_form(url, proxy, 'MULTI', form, 'File upload');*/
				post_upload(url, proxy, 'GIF89a<?php phpinfo(); ?>', $scope.form.upload.filename);
			}
		}
	};

	$scope.reset = function () {
		messageClear();
	};

	$scope.reset_content = function () {
		document.getElementById('upload-content').value = '';
		$scope.form.upload.content = '';
		$scope.form.upload.disabled = false;
		$scope.$apply(); // data binding to update view
	};
}]);

app.directive('fileModel', function ($parse) {
	'use strict';
	return {
		restrict: 'A',
		link: function (scope, element, attrs) {
			var model = $parse(attrs.fileModel);
			element.bind('change', function () {
				scope.form.upload.disabled = true;
				scope.$apply(function () {
					model.assign(scope, element[0].files[0]);
				});
			});
		}
	};
});