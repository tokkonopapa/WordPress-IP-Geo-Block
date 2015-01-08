/**
 * Service: Generate random IP and get geolocation
 *
 */
angular.module('geolocation', []);
angular.module('geolocation').service('GeolocationSvc', ['$http', function ($http) {
	/**
	 * Geolocation API
	 * These APIs need to respond `Access-Control-Allow-Origin` in header.
	 */
	this.apis = [
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
			}
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
			}
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
			}
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
			}
		}
	];

	this.generate_ip = function (ip) {
		var api = this.apis[get_random_int(0, this.apis.length-1)];
		var url = api.url
			.replace('%API_IP%', ip)
			.replace(/%API_FMT%/g, api['fmt']);

		return $http({
			url: url,
			method: 'GET'
		})

		// data       – {string|Object} The response body.
		// status     – {number} HTTP status code of the response.
		// headers    – {function([headerName])} Header getter function.
		// config     – {Object} The configuration object used for the request.
		// statusText – {string} HTTP status text of the response.
		.then(
			// success
			function (data) {
				var geo = api.get(data.data, 'name');
				if (geo)
					geo += ' (' + api.get(data.data, 'code') + ')';
				else
					geo = api.get(data.data, 'error') + ' (' + api.api + ')';

				return combine_ip(ip, geo);
			},

			// error
			function (data) {
				var msg = data.data ? ' ' + strip_tags(data.data) : '';
				return data.status + ' ' + data.statusText + msg;
			}
		);
	};
}]);