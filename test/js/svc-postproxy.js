/**
 * Service: Language
 *
 */
angular.module('postproxy', []);
angular.module('postproxy').service('PostProxySvc', ['$http', function ($http) {
	/**
	 * Post form data
	 *
	 */
	this.post_form = function (url, form, proxy) {
		// Post the comment with `X-Forwarded-For` header
		return $http({
			url: url,
			method: 'POST',
			data: form,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
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
			function (data) {
				console.log(data);
				return {stat: data.status + ' ' + data.statusText};
			},

			// In case of the comment being denied
			function (data) {
				var msg = data.data ? ' ' + strip_tags(data.data) : '';
				return {stat: data.status + ' ' + data.statusText + msg};
			}
		);
	};

	/**
	 * Post XML data
	 *
	 */
	this.post_xml = function (url, xml, proxy) {
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
			function (data) {
				return {stat: data.status + ' ' + data.statusText};
			},

			// In case of the comment being denied
			function (data) {
				var msg = data.data ? ' ' + strip_tags(data.data) : '';
				return {stat: data.status + ' ' + data.statusText + msg};
			}
		);
	};
}]);