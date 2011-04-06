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
	var inner_div = this.dom.children('div');
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
	
	// Capture events
	this.events.bind('closed',function(){
		
		delete place.infopanel;	// Remove info panel
		downlight_marker();
	});
	this.events.bind('focus', highlight_marker);
	this.events.bind('blur', downlight_marker);
	
	highlight_marker();
	
	// Move window where the marker is
	marker_pos = this.getMarkerPoint();
	this.move_near(marker_pos.x - 10, marker_pos.y - 56, 140, 56);
};
collectopia.InfoPanel.prototype = new collectopia.Panel();
collectopia.InfoPanel.prototype.constructor = collectopia.InfoPanel;

collectopia.InfoPanel.prototype.getMarkerPoint = function(){
	
	var
		map = this.map.google.map,
		topRight = map.getProjection().fromLatLngToPoint(map.getBounds().getNorthEast()),
	    bottomLeft = map.getProjection().fromLatLngToPoint(map.getBounds().getSouthWest()),
	    scale = Math.pow(2,map.getZoom()),
	    worldPoint = map.getProjection().fromLatLngToPoint(this.place.marker.getPosition());
	 return new google.maps.Point((worldPoint.x - bottomLeft.x)*scale,(worldPoint.y-topRight.y)*scale); 
};