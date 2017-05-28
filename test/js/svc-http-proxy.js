/*jslint white: true */
/**
 * Service: Post data with X-Forwarded-For
 *
 */
angular.module('http-proxy', []);
angular.module('http-proxy').service('HttpProxySvc', ['$http', function ($http) {
	'use strict';

	/**
	 * Post form data
	 *
	 */
	this.post_form = function (url, proxy, method, form) {
		var type;
		switch (method.toLowerCase()) {
		  case 'get':
			method = 'GET';
			type = 'text/html; charset=UTF-8';
			url += '?' + decodeURIComponent(form);
			break;
		  case 'post':
			method = 'POST';
			type = 'application/x-www-form-urlencoded; charset=UTF-8';
			break;
		  case 'multi':
		  default:
			method = 'POST';
			type = undefined; // 'multipart/form-data' will fail in the invalid boundary.
		}

		// Post the comment with `X-Forwarded-For` header
		return $http({
			url: url,
			method: method,
			data: form,
			headers: {
				'Content-Type': type,
				'X-Forwarded-For': proxy
			}
		})

		// data       – {string|Object} The response body.
		// status     – {number} HTTP status code of the response.
		// headers    – {function([headerName])} Header getter function.
		// config     – {Object} The configuration object used for the request.
		// statusText – {string} HTTP status text of the response.
		.then(
			// In case of the comment being accepted
			function (res) {
				return {stat: res.status + ' ' + res.statusText};
			},

			// In case of the comment being denied
			function (res) {
				var msg = ''; // res.data ? ' ' + strip_tags(res.data) : '';
				return {stat: res.status + ' ' + res.statusText + msg};
			}
		);
	};

	/**
	 * Post XML data
	 *
	 */
	this.post_xml = function (url, proxy, xml) {
		xml = xml.replace(/\s*([<>])\s*/g, '$1');

		return $http({
			url: url,
			method: 'POST',
			data: xml,
			headers: {
				'Content-Type': 'application/xml',
				'X-Forwarded-For': proxy
			}
		})

		.then(
			// In case of the comment being accepted
			function (res) {
				return {stat: res.status + ' ' + res.statusText};
			},

			// In case of the comment being denied
			function (res) {
				var msg = ''; // res.data ? ' ' + strip_tags(res.data) : '';
				return {stat: res.status + ' ' + res.statusText + msg};
			}
		);
	};

	/**
	 * Post XMLHttpRequest
	 *
	 */
	this.post_upload = function (url, proxy, content, filename, callback) {
		var xhr = window.XDomainRequest ? new XDomainRequest() : new XMLHttpRequest(),
			boundary = '----boundary',
			request =
			'--' + boundary + "\r\n"
			+ 'Content-Disposition: form-data; name="file"; '
			+ 'filename="' + filename + '"' + "\r\n"
			+ 'Content-Type: application/octet-stream' + "\r\n\r\n"
			+ content + "\r\n"
			+ '--' + boundary + '--';

		xhr.open('POST', url, 'true');
		xhr.withCredentials = true; // send Cookie
		xhr.setRequestHeader(
			'Content-Type', 'multipart/form-data; boundary=' + boundary
		);
		xhr.setRequestHeader('X-Forwarded-For', proxy);
		xhr.onreadystatechange = function () {
			if (xhr.readyState == 4) {
				if (callback) {
					callback(
						xhr.status + ' ' + strip_tags(xhr.responseText || xhr.statusText)
					);
				}
			}
		};
		xhr.send(request);
	};

	// http://qiita.com/zaburo/items/f03433caa710902d599f
	this.post_upload2 = function (url, proxy, file, filename) {
		// formdata
		var fd = new FormData();
		fd.append('file', file);

		// post
		return $http({
			url: url,
			method: 'POST',
			data: fd,
			transformRequest: null,
			headers: {
				'Content-Type': undefined,
				'X-Forwarded-For': proxy
			}
		})

		.then(
			// In case of the comment being accepted
			function (res) {
				return {
					stat: res.status + ' ' + strip_tags(res.statusText)
				};
			},

			// In case of the comment being denied
			function (res) {
				return {
					stat: res.status + ' ' + strip_tags(res.statusText)
				};
			}
		)
	};
}]);