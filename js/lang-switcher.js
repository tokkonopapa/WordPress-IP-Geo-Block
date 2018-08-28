    (function(win, doc) {
        var link = doc.getElementById('lang'), lang = (doc.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1"));// || ((win.navigator.userLanguage || win.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en'));
        if (link && lang === link.hreflang && lang !== (-1 !== win.location.href.indexOf('-' + lang + '.') ? 'ja' : 'en')) {
            doc.cookie = 'lang=' + lang + '; path=/';
            win.location.href = link.href + win.location.hash;
        }
    })(window, document);
