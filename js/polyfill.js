// Polyfill

if (!Object.create) {
	Object.create = function (o) {
		if (arguments.length > 1) {
			throw new Error('Object.create implementation only accepts the first parameter.');
		}
		function F() {}
		F.prototype = o;
		return new F();
	};
}

if (typeof String.prototype.trim !== 'function') {
	String.prototype.trim = function() {
		return this.replace(/^\s+|\s+$/g, ''); 
	};
}
