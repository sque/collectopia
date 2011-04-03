mapness.namespace('mapness');

/**
 * Specialized version of Panel for Info.
 * @returns
 */
mapness.InfoPanel = function(map, place) {
	var pthis = this;
	mapness.Panel.call(this, 'info', place.name, 
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
	
	
	// Capture events
	this.events.bind('closed',function(){
		delete place.infopanel;	// Remove info panel
	});
};
mapness.InfoPanel.prototype = new mapness.Panel();
mapness.InfoPanel.prototype.constructor = mapness.InfoPanel;
