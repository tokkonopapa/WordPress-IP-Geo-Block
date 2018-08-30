(function(window, document, $) {
	/**
	 * Preferred language
	 *
	 */
	var preferred = document.cookie.replace(/(?:(?:^|.*;\s*)lang\s*\=\s*([^;]*).*$)|^.*$/, "$1") || ((window.navigator.userLanguage || window.navigator.language).indexOf('ja') !== -1 ? 'ja' : 'en');

	/**
	 * Disqus
	 *
	 */
	function addDisqus(script) {
		var dsq = document.createElement('script');
		dsq.type = 'text/javascript';
		dsq.async = true;
		dsq.src = 'https://ip-geo-block' + (preferred === 'ja' ? '-jp' : '') + '.disqus.com/' + script;
		dsq.setAttribute('data-timestamp', +new Date());
		(document.head || document.body).appendChild(dsq);
	}

	// navigation menu
	$('.button-collapse').sideNav();

	$('#menu-close').on('click', function() {
		$('#sidenav-overlay').trigger('click');
	});

	// language switcher
	$('#switch-container').removeClass('switch-on'); // remove class added in navi.html

	$('#lang-switch').prop('checked', 'ja' === preferred).on('change', function() {
		var prop = $(this).prop("checked") ? 'ja' : 'en', // false: En(left), true: Ja(right)
		    page = $('link[rel=canonical]').attr('href').indexOf('-ja.') !== -1 ? 'ja' : 'en';

		// save cookie
		document.cookie = 'lang=' + prop + '; path=/';

		// redirect if current page is not preferred
		if (page !== prop) {
			page = $('#' + ('ja' === prop ? 'lang' : 'lang-x')).attr('href');
			if (page) {
				window.location.href = page;
			}
		}

		// just kick off disqus
		else {
			var dsq = $('#disqus_thread');
			if (dsq.length && location.hostname.indexOf('ipgeoblock') >= 0) {
				dsq.empty();
				addDisqus('embed.js');
//				addDisqus('count.js');
			}
		}
	}).trigger('change');

	// consent
	if (!document.cookie.replace(/(?:(?:^|.*;\s*)consent\s*\=\s*([^;]*).*$)|^.*$/, "$1")) {
		$('.consent').addClass('consent-show');
	}

	$('.compliance').on('click', 'a', function() {
		document.cookie = 'consent=true; max-age=31536000; path=/';
		$('.consent').addClass('consent-hide');
	});

	// jump to the hash if it is specified
	$(window).on('load', function() {
		var hash = window.location.hash || null;
		if (hash) {
			window.location.hash = '';
			window.location.hash = hash;
		}
	});

	// Horizontal staggered list
	Materialize.showScrolled = function(selector) {
		var time = 0;
		$(selector).find('p').velocity({
			translateX: "-100px"
		}, {
			duration: 0
		});

		$(selector).find('p').each(function() {
			$(this).velocity({
				opacity: "1",
				translateX: "0"
			}, {
				duration: 800,
				delay: time,
				easing: [60, 10]
			});
			time += 120;
		});
	};

	Materialize.scrollFire([{
			selector: '#scroll-fire1',
			offset: 50,
			callback: 'Materialize.showScrolled("#scroll-fire1")'
		},
		{
			selector: '#scroll-fire2',
			offset: 50,
			callback: 'Materialize.showScrolled("#scroll-fire2")'
		},
		{
			selector: '#scroll-fire3',
			offset: 50,
			callback: 'Materialize.showScrolled("#scroll-fire3")'
		}
	]);

})(window, document, jQuery); // end of jQuery name space