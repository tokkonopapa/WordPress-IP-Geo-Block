(function ($) {
	function sanitize(str) {
		return str ? str.toString().replace(/[&<>"']/g, function (match) {
			return {
				'&' : '&amp;',
				'<' : '&lt;',
				'>' : '&gt;',
				'"' : '&quot;',
				"'" : '&#39;'
			}[match];
		}) : '';
	}

	$(function () {
		$('a').on('click', function (event) {
			var meta = '';
			var ref = $(this).data('meta-referrer');
			if (typeof ref !== 'undefined') {
				if (ref) {
					meta += '<meta name="referrer" content="never" />' +
					        '<meta name="referrer" content="no-referrer" />';
				}
				meta += '<meta http-equiv="refresh" content="0; url=' +
				        sanitize(this.href) + '" />'

				var w = window.open();
				w.document.write(meta);
				w.document.close();
				return false;
			}
		});
	});
}(jQuery));