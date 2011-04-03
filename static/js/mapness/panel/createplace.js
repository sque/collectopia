mapness.namespace('mapness');

/**
 * Specialized version of Panel for Create form.
 * @returns
 */
mapness.CreatePlacePanel = function(map, geocoder) {
	var pthis = this,
		marker_moved_action;
	mapness.Panel.call(this, 'place-new', 'create', 
		[{'title': 'send', 'callback': function(){
				pthis.submitForm();
			}
		}]);
	this.map = map;

	// Create Marker
	this.marker = new google.maps.Marker( {
		map : map.google.map,
		position : map.google.map.getCenter(),
		draggable : true
	});
	// Add extra stuff on the form	
	(function(){
		this.dom.find('form').attr('action', 'api/place/new');		
		var ul = this.dom.find('ul.fields')
			.createEl('li', { class : 'categories', name : 'categories'})
			.createEl('ul', { class : 'categories'});
		
		var update_cat_form_field = function() {
			var enabled_cats = [];
			pthis.dom.find('ul.categories li.enabled').each(function(){
				enabled_cats.push(this.getAttribute('tag'));
			});
			pthis.dom.find('input[name=categories]').val(enabled_cats.join(','));
		};
		for(var i in this.map.categories){
			var cat = this.map.categories[i];
			ul.createEl('li', { class : cat['tag'], tag : cat['tag']}).text(cat['title'])
				.prepend($('<span class="checkbox"/>'))
				.click(function(event){
					var li = $(this);
					li.toggleClass('enabled');
					update_cat_form_field();
				});
		}
		
		this.dom.find('ul.fields').append($('<li class="address">none</li>'));		
	}).call(this);
	
	
	this.events.bind('onclose', function(){
		pthis.marker.setMap(null);
	});	
	this.events.bind('submit', function(event, place){
		var indiv = pthis.dom.children('div');
		var initial_size = {h: indiv.height(), w: indiv.width() };
		// Reshape form
		pthis.dom.children('div').text('<h1>PLACE HOLDER[THANK YOU]</h1>');
		pthis.dom.children('ul').remove();
		indiv.height(initial_size.h);
		indiv.width(initial_size.w);
		map.places.push(place);
		map.drawPlaces();
		
		// Fade out
		pthis.marker.setMap(null);
		pthis.dom.delay(1500).fadeOut('slow', function(){	pthis.close(); });		
	});
	this.events.bind('submit_error', function(event, data){
		var indiv = pthis.dom.children('div');
		indiv.find('.ui-form-error').remove();
		
		for(var name in data.fields)
			indiv.find('li[name=' + name + ']')
				.createEl('span' , {class : 'ui-form-error'}).text(data.fields[name]);
	});

	/* Listen on marker "position_changed" and update location on creation form */
	google.maps.event.addListener(this.marker, 'dragend', marker_moved_action = function(){
		geocoder.geocode({'latLng': pthis.marker.getPosition(), 'language' : 'en'}, function(results, status) {
			pthis.setLocation(results[0].geometry.location);
			if (status == google.maps.GeocoderStatus.OK) {				
				pthis.setGeocode(results[0]);
			}
		});
	});
	marker_moved_action();
	
};
mapness.CreatePlacePanel.prototype = new mapness.Panel();
mapness.CreatePlacePanel.prototype.constructor = mapness.CreatePlacePanel;


/**
 * Set a field on the form
 * @param name The "name" attribute of the input element in form.
 * @param value The value to set in the input element.
 */
mapness.CreatePlacePanel.prototype.setField = function(name, value) {
	this.dom.find('input[name=' + name + ']').val(value);
};

/**
 * Helper function to set the location elements of the form.
 */
mapness.CreatePlacePanel.prototype.setLocation = function(loc) {
	this.setField('loc_lat', loc.lat());
	this.setField('loc_lng', loc.lng());
};

/**
 * Helper function to set the geocode elements of the form
 */
mapness.CreatePlacePanel.prototype.setGeocode = function(geo) {
	// Find in geocode data an entry by type
	var get_by_type = function(type) {
		var ac = geo['address_components'];
		for(var i in ac) {
			if ($.inArray(type, ac[i].types) >= 0){
				return ac[i];
			}
		};
	};
	
	// Clean up previous values
    var fields = 
	[
	 	'pos_country',
	 	'pos_city',
	 	'pos_locality',
	 	'pos_street',
	 	'pos_street_number',
	 	'pos_administrative_area_level_1',
	 	'pos_administrative_area_level_2'	 	
	];
	for(var i in fields)
		this.dom.find('form input[name=' + fields[i] + ']').val('');
	
	// Add new values
	var country, city, street, street, street_number, address, ad_level_1, ad_level_2;
	if (country = get_by_type('country'))
		this.setField('pos_country', country.long_name);
	if (city = get_by_type('locality'))
		this.setField('pos_city', city.long_name);
	if (street = get_by_type('route'))
		this.setField('pos_street', street.long_name);
	if (street_number = get_by_type('street_number'))
		this.setField('pos_street_number', street_number.long_name);
	if (ad_level_1 = get_by_type('administrative_area_level_1'))
		this.setField('pos_administrative_area_level_1', ad_level_1.long_name);
	if (ad_level_2 = get_by_type('administrative_area_level_2'))
		this.setField('pos_administrative_area_level_2', ad_level_2.long_name);
	
	this.setField('pos_address', geo.formatted_address);
	this.dom.find('.address').text(geo.formatted_address);
};