    // head.html
    (function(win, doc) {
        var link = doc.getElementById('lang'),
            page = doc.getElementById('canonical').href.indexOf('-ja.') !== -1 ? 'ja' : 'en',
            lang = doc.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") || ((win.navigator.userLanguage || win.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');
        if (link && win.location.href.indexOf(link.href) < 0 && page !== lang) {
            doc.cookie = 'lang=' + lang + '; path=/';
            win.location.href = link.href + win.location.hash;
        }
    })(window, document);

// Closure Compiler
(function(a,b){var c=b.getElementById("lang"),e=-1!==b.getElementById("canonical").href.indexOf("-ja.")?"ja":"en",d=b.cookie.replace(/(?:(?:^|.*;\s*)lang\s*=\s*([^;]*).*$)|^.*$/,"$1")||(-1!==(a.navigator.userLanguage||a.navigator.language).indexOf("ja")?"ja":"en");c&&0>a.location.href.indexOf(c.href)&&e!==d&&(b.cookie="lang="+d+"; path=/",a.location.href=c.href+a.location.hash)})(window,document);

// UglifyJS 3.0
!function(a,e){var n=e.getElementById("lang"),o=-1!==e.getElementById("canonical").href.indexOf("-ja.")?"ja":"en",i=e.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/,"$1")||(-1!==(a.navigator.userLanguage||a.navigator.language).indexOf("ja")?"ja":"en");n&&a.location.href.indexOf(n.href)<0&&o!==i&&(e.cookie="lang="+i+"; path=/",a.location.href=n.href+a.location.hash)}(window,document);

    // navi.html
    (function(win, doc) {
        var lang = doc.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") || ((win.navigator.userLanguage || win.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');
        if ('ja' === lang) {
            (doc.getElementById('switch-container').classList || {
                add: function() {} // dummy function that does not support `classList` (IE9-)
            }).add('switch-on');
        }
    })(window, document);

// Closure Compiler
!function(a,n){"ja"===(n.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/,"$1")||(-1!==(a.navigator.userLanguage||a.navigator.language).indexOf("ja")?"ja":"en"))&&(n.getElementById("switch-container").classList||{add:function(){}}).add("switch-on")}(window,document);

// UglifyJS 3.0
(function(a,b){"ja"===(b.cookie.replace(/(?:(?:^|.*;\s*)lang\s*=\s*([^;]*).*$)|^.*$/,"$1")||(-1!==(a.navigator.userLanguage||a.navigator.language).indexOf("ja")?"ja":"en"))&&(b.getElementById("switch-container").classList||{add:function(){}}).add("switch-on")})(window,document);
