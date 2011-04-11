collectopia.namespace('collectopia');

/**
 * Specialized version of Panel for Info.
 * @returns
 */
collectopia.InfoPanel = function(map, place) {
	var pthis = this;
	collectopia.Panel.call(this, 'info', place.name, 
		[{'title': 'report fake', 'callback': function(){
				console.log('Report fake');
			}
	}]);
	
	// Store variables
	this.place = place;	
	this.map = map;
	
	// Draw panel
	var inner_div = this.dom_body;
	var info = inner_div.createEl('ul', {class: 'info'});
	var show_place_info_field = function(field, title){
		if ((place[field] != null) && (place[field] != ''))
			info.createEl('li')
				.createEl('label').text(title + ': ')
				.parent().createEl('span').text(place[field]);
	};
	show_place_info_field('name', 'Name');
	show_place_info_field('country', 'Country');
	show_place_info_field('city', 'City');
	show_place_info_field('address', 'Address');
	show_place_info_field('email', 'eMail');
	show_place_info_field('web', 'www');
	
	// Show categories
	var cats = inner_div.createEl('ul', { class: 'categories'});
	for(var i in place.categories) {
		var cat = place.categories[i];
		cats.createEl('li').createEl('img', 
			{ src : 'static/images/cat_' + cat + '_24.png',
				title : map.categories[cat].title }
		);
	}
	
	// Current rating
	var rating_span = inner_div.createEl('div', { class : 'rating'});
	var rating = rating_span.createEl('ul', { class : 'rating current-' + Math.round(place.rate_current)});
	for(var i = 0; i< 5; i++) rating.createEl('li', { class: 'rate-' + (i+1), rate: (i+1)}).text(' ');
	rating.find('li').hover(
		function(event){
			$(this).addClass('hover').prevAll().addClass('hover');
		}, function() {
			$(this).parent().find('li').removeClass('hover');
		})
		.click(function(){
			$.post('api/place/' + place.id + '/rate', { rate : $(this).attr('rate') }, function(data){
				place.rate_current = parseFloat(data.rate_current);
				place.rate_total = parseInt(data.rate_total);
				
				// Posted
				rating.attr('class', 'rating selected current-' + Math.round(place.rate_current))
					.find('li').removeClass('hover').unbind('mouseover').unbind('mouseout').unbind('click');
				rating_span.find('.textual .total .number').text(place.rate_total);
				rating_span.find('.textual .score').text(place.rate_current.toFixed(1));
			});
		});
	rating_span.createEl('span', { class : 'textual'})
		.createEl('span', {class : 'score'}).text(place.rate_current.toFixed(1)).parent()
		.createEl('span', {class : 'total'})
			.append('(')
			.append($('<span class="number"/>').text(place.rate_total))
			.append(' votes)');
		
	inner_div.createEl('hr');
	var media_div = inner_div.createEl('div', { class : 'media'});
	var video_present = ((typeof place.video_url != 'undefined') && ( place.video_url != ''));
	var photos_present = (typeof place.photos != 'undefined');
	var media_present = video_present || photos_present;
	
	// Photos
	if (photos_present) {
		var cont = media_div.createEl('div', { class : 'photos'});
		var gal = cont.createEl('div', {class : 'gallery'});
		for(var i in place.photos)
			gal.createEl('a', { rel : 'photos_place_' + this.place.id, 'href' : place.photos[i]['url'], target: '_blank'})
				.createEl('img', { src : place.photos[i]['thumb_url']});
		gal.after('<div class="pager" id="photos_nav_' + escape(String(this.place.id)) + '" >')
			.cycle({ 
		    fx:     'scrollLeft', 
		    timeout: 5000,
		    pager:  '#photos_nav_' + String(this.place.id),
		    pagerAnchorBuilder: function(index, dom) {
		    	return '<a href="#"> </a>';
		    }
		 });
		
		gal.find('a').fancybox({
			'speedIn'		:	600, 
			'speedOut'		:	200, 
			'overlayShow'	:	false,
			'onStart' : function(){
				collectopia.panels.enableKeyboardEvents(false);
				gal.cycle('pause');
			},
			'onClosed' : function(){
				collectopia.panels.enableKeyboardEvents(true);
				gal.cycle('resume');
			}
		});
	} else if (media_present) {
		media_div.createEl('div', { class : 'photos empty' })
			.createEl('img', { src : 'static/css/images/no_photos.jpg'});
	}
	
	// Video
	if (video_present){
		var video = place.video_url.split(':'),
			img_url;
		if (video[0] == 'youtube') {
			img_url = 'http://img.youtube.com/vi/' + video[1] + '/1.jpg';
			swf_url = 'http://www.youtube-nocookie.com/v/' + video[1] + '?autoplay=1&fs=1';
		}
		
		// Create elements
		var v = media_div.createEl('div', { class : 'video ' + video[0] })
			.createEl('a', { href : swf_url });		
		v.createEl('img', { src : img_url });
		v.createEl('div', { class: 'watermark'});
		
		media_div.find(".video a").fancybox({
	        'titleShow'     : false,
	        'transitionIn'  : 'elastic',
	        'transitionOut' : 'elastic',
	        'type' : 'swf',
	        'swf' : {'wmode':'transparent','allowfullscreen':'true'},
			'onStart' : function(){
				collectopia.panels.enableKeyboardEvents(false);
			},
			'onClosed' : function(){
				collectopia.panels.enableKeyboardEvents(true);
			}
		});
	} else if (media_present){
		media_div.createEl('div', { class : 'video empty' })
			.createEl('img', { src : 'static/css/images/no_video.jpg'});
	}
	
	media_div.createEl('div', { class: 'spacer', style : 'clear: both;' });

	
	// Description
	inner_div.createEl('div', { class : 'description'})
		.text(place.description);
	
	var highlight_marker = function(){
		var marker = pthis.place.marker;
		marker.anchor = new google.maps.Point(0,56);
		marker.scaledSize = marker.size = new google.maps.Size(120, 56);
		pthis.place.marker.setIcon();
		pthis.place.marker.setIcon(marker);		
	};
	
	var downlight_marker = function(){
		var marker = pthis.place.marker;
		marker.anchor = new google.maps.Point(0,28);
		marker.scaledSize = marker.size = new google.maps.Size(60, 28);
		pthis.place.marker.setIcon();
		pthis.place.marker.setIcon(marker);
	};
	inner_div.createEl('div', { class: 'spacer', style : 'clear: both;' });
	
	// Capture events
	this.events.bind('closed',function(){
		delete place.infopanel;	// Remove info panel
		downlight_marker();
	});
	this.events.bind('focus', highlight_marker);
	this.events.bind('blur', downlight_marker);
	
	highlight_marker();
	
	// Move window where the marker is
	marker_pos = this.place.marker.getPoint();
	this.moveNear(marker_pos.x - 10, marker_pos.y - 56, 140, 56);
};
collectopia.InfoPanel.prototype = new collectopia.Panel();
collectopia.InfoPanel.prototype.constructor = collectopia.InfoPanel;

