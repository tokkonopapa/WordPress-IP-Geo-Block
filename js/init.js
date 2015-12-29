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

  }); // end of document ready
})(jQuery); // end of jQuery name space