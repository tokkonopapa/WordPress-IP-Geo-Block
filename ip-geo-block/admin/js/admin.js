/*
* jQuery GoogleMaps Plugin
*
* Copyright (c) 2011 TORU KOKUBUN (http://d-s-b.jp/)
* Licensed under MIT Lisence:
* http://www.opensource.org/licenses/mit-license.php
* http://sourceforge.jp/projects/opensource/wiki/licenses%2FMIT_license
*
* Last Modified: 2011-03-02
* version: 1.01
*
* This program checked the oparation on jQuery 1.4.2.
*
*/
(function(c){c.fn.GoogleMaps=function(d){var a=c.extend({},c.fn.GoogleMaps.defaults,d);return this.each(function(){function m(){f=new google.maps.LatLng(a.lat,a.lng);n={center:f,zoom:a.zoom,mapTypeControl:a.map_type_control,mapTypeId:a.map_type_id};g=new google.maps.Map(r,n);e&&o(g,f,s,t)}function o(a,f,b,g,e,i,h){var b=u[b?b:"redDot"],i=i?i:"#",j=new google.maps.MarkerImage(b.markerImg,b.markerSize,b.markerOrigin,b.markerAnchor,b.markerScaleSize),b=new google.maps.MarkerImage(b.shadowImg,b.shadowSize,b.shadowOrigin,b.shadowAnchor,b.shadowScaleSize),d=new google.maps.Marker({position:f,map:a,icon:j,shadow:b});k.push(d);v&&google.maps.event.addListener(d,"click",function(){a.setCenter(f);l.close();l.setContent(g);l.open(a,d)});w&&c(p+":eq("+h+"),"+p+":eq("+h+").children()").click(function(){google.maps.event.trigger(k[h],"click");return false});x&&(c(q).append('<li><a href="'+i+'">'+e+"</a></li>"),c(q+" li:eq("+h+")").click(function(){google.maps.event.trigger(k[h],"click");return false}))}function d(){c.ajax({url:y,dataType:"xml",cache:false,success:function(d){m();var e=new google.maps.LatLngBounds;c("Placemark",d).each(function(b){f=new google.maps.LatLng(parseFloat(c(this).find("latitude").text()),parseFloat(c(this).find("longitude").text()));var d=c(this).find("name").text(),k=c(this).find("description").text(),i=c(this).find("url").text(),h=c(this).find("icon").text(),j='<div class="'+a.info_window_class+'">';j+="<h"+a.info_window_heading_level+">"+d+"</h"+a.info_window_heading_level+">";j+=k;j+="</div>";o(g,f,h,j,d,i,b);e.extend(f);g.fitBounds(e);g.setCenter(e.getCenter())})}})}var r=this,f,n,g,k=[],e=!a.file?true:false,y=a.file,v=a.info_window==1||!e?true:false,t=e?a.info_content:"",w=a.link_target?true:false,p=a.link_target,x=a.list_target?true:false,q=a.list_target,s=a.icon_type?a.icon_type:"",u=a.icons;e?m():d()})};var l=new google.maps.InfoWindow;c.fn.GoogleMaps.defaults={lat:0,lng:0,zoom:1,map_type_control:false,map_type_id:google.maps.MapTypeId.ROADMAP,file:null,info_window:0,info_content:null,info_window_class:"info-data",info_window_heading_level:1,link_target:null,list_target:null,icon_type:null,icons:{redDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/red-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},blueDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/blue-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},greenDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/green-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},ltblueDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/ltblue-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},yellowDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/yellow-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},purpleDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/purple-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},pinkDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/pink-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)},orangeDot:{markerImg:"http://maps.google.co.jp/mapfiles/ms/icons/orange-dot.png",markerSize:new google.maps.Size(32,32),markerOrigin:new google.maps.Point(0,0),markerAnchor:new google.maps.Point(16,32),markerScaleSize:new google.maps.Size(32,32),shadowImg:"http://maps.google.co.jp/mapfiles/ms/icons/msmarker.shadow.png",shadowSize:new google.maps.Size(59,32),shadowOrigin:new google.maps.Point(-14,0),shadowAnchor:new google.maps.Point(29,32),shadowScaleSize:new google.maps.Size(59,32)}}}})(jQuery);

(function ($) {

	function ajax_get_location(service, ip) {
		$('#post-geo-block-info').empty().addClass('post-geo-block-loading');

		// `POST_GEO_BLOCK` is enqueued by wp_localize_script()
		$.post(POST_GEO_BLOCK.url, {
			action: POST_GEO_BLOCK.action,
			nonce: POST_GEO_BLOCK.nonce,
			provider: service,
			ip: ip
		})

		.done(function (data, textStatus, jqXHR) {
			var info = '<ul>';
			for (var key in data) {
				info +=
					'<li>' +
						'<span class="post-geo-block-title">' + key + ' : </span>' +
						'<span class="post-geo-block-result">' + data[key] + '</span>' +
					'</li>';
			}
			info += '</ul>';

			$('#post-geo-block-info').append(info);
			$("#post-geo-block-map").GoogleMaps({
				lat: data.latitude,
				lng: data.longitude,
				info_window: 1,
				info_content: data.cityName + ", " + data.countryName,
				zoom: 7
			});
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			alert(jqXHR.responseText);
		})

		.complete(function () {
			$('#post-geo-block-info').removeClass('post-geo-block-loading');
		});
	}

	function ajax_clear_statistics() {
		$('#wpbody-content').addClass('post-geo-block-loading');

		$.post(POST_GEO_BLOCK.url, {
			action: POST_GEO_BLOCK.action,
			nonce: POST_GEO_BLOCK.nonce,
			clear: 'statistics'
		})

		.done(function (data, textStatus, jqXHR) {
			window.location = data.refresh;
		})

		.fail(function (jqXHR, textStatus, errorThrown) {
			alert(jqXHR.responseText);
		})

		.complete(function () {
			$('#wpbody-content').removeClass('post-geo-block-loading');
		});
	}

	$(function () {
		// Settings
		$('#post_geo_block_settings_provider').bind('change', function () {
			var key = $(this).find('option:selected').attr('data-api-key');
			var set = 'undefined' === typeof key;
			$('#post_geo_block_settings_api_key').prop('disabled', set).val(key);
		});

		// Statistics
		$('#clear_statistics').click(function () {
			if (window.confirm('Clear statistics ?')) {
				ajax_clear_statistics();
			}
		});

		// Search Geolocation
		$("#post-geo-block-map").GoogleMaps();
		$('#get_location').click(function () {
			var ip = $('#post_geo_block_settings_ip_address').val();
			var service = $('#post_geo_block_settings_service').val();
			if (ip) {
				ajax_get_location(service, ip);
			}
		});

	});

}(jQuery));