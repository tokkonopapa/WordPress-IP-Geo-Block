(function () {
	'use strict';
	function insertText(id, text) {
		var code = document.createElement('code');
		text = document.createTextNode(text);
		code.appendChild(text, code);
		id = document.getElementById(id);
		id.appendChild(code, id);
	}

	if (navigator.userAgent) {
		insertText("user-agent", navigator.userAgent);
	}

	if (document.referrer) {
		insertText("referer", document.referrer);
	}
}());