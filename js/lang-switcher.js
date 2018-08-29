    // head.html
    (function(win, doc) {
        var link = doc.getElementById('lang'),
            page = doc.getElementById('canonical').href.indexOf('-ja.') !== -1 ? 'ja' : 'en',
            lang = doc.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") || ((win.navigator.userLanguage || win.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');
        if (link && page !== lang) {
            doc.cookie = 'lang=' + lang + '; path=/';
            win.location.href = link.href + win.location.hash;
        }
    })(window, document);

    // navi.html
    (function(win, doc) {
        var lang = doc.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") || ((win.navigator.userLanguage || win.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');
        if ('ja' === lang) {
            doc.getElementById('switch-container').classList.add('switch-on');
        }
    })(window, document);
