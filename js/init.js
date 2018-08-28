(function($){
  $(function(){

    $('.button-collapse').sideNav();

    $('#menu-close').on('click', function () {
      $('#sidenav-overlay').trigger('click');
    });

    // Horizontal staggered list
    Materialize.showScrolled = function(selector) {
      var time = 0;
      $(selector).find('p').velocity(
          { translateX: "-100px"},
          { duration: 0 }
      );

      $(selector).find('p').each(function() {
        $(this).velocity(
          { opacity: "1", translateX: "0"},
          { duration: 800, delay: time, easing: [60, 10] });
        time += 120;
      });
    };

    Materialize.scrollFire([
      { selector: '#scroll-fire1', offset: 50, callback: 'Materialize.showScrolled("#scroll-fire1")' },
      { selector: '#scroll-fire2', offset: 50, callback: 'Materialize.showScrolled("#scroll-fire2")' },
      { selector: '#scroll-fire3', offset: 50, callback: 'Materialize.showScrolled("#scroll-fire3")' }
    ]);

    // language switcher
    var lang = document.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") ||
        ((window.navigator.userLanguage || window.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');

    $('#lang-switch').prop('checked', (lang === 'ja')).on('change', function () {
      var page = $('link[rel=canonical]').attr('href').indexOf('-ja.html') !== -1 ? 'ja' : 'en',
          prop = $(this).prop("checked") ? 'ja' : 'en'; // false: En(left), true: Ja(right)
      if (page !== lang || prop !== lang) {
        document.cookie = 'lang=' + prop + '; path=/';
        lang = $('#' + ('ja' === prop ? 'lang' : 'lang-x')).attr('href');
        if (lang) {
          window.location.href = lang;
        }
      }
    }).trigger('change');

    $(window).on('load', function() {
        var hash = window.location.hash || null;
        if (hash) {
            window.location.hash = '';
            window.location.hash = hash;
        }
    });
  }); // end of document ready
})(jQuery); // end of jQuery name space