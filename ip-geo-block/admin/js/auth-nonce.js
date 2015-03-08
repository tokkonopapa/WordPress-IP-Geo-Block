/**
 * DZAP - Defence of Zero-day Attack for Admin Ajax/Post
 *
 * <meta name="ip-geo-block-auth-nonce" content="..." />
 */
(function ($) {
	$(function () {
		var nonce = $('meta[name=ip-geo-block-auth-nonce]').attr('content');
		if (nonce) {
			$('form').append('<input type="hidden" name="ip-geo-block-auth-nonce" value="' + nonce + '" />');
		}
	});
	$(document).ajaxSend(function (event, jqxhr, settings) {
		var nonce = $('meta[name=ip-geo-block-auth-nonce]').attr('content');
		if (nonce && settings.url.indexOf('wp-admin/admin-ajax.php') >= 0) {
			var data = settings.data ? settings.data.split('&') : [];
			data.push('ip-geo-block-auth-nonce=' + nonce);
			settings.data = data.join('&');
		}
	});
}(jQuery));