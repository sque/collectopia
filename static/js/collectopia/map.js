collectopia.namespace('collectopia');

/**
 * @class Create the complete map for collectopia
 */
collectopia.Map = function(dom, categories) {
	var pthis = this;
	this.google = {};
	this.places = {};	
	this.categories = categories;
	this.show_filters = [];
	this.dom = dom;
	
	
	/* Initialize google map */
	var init_google_map = function() {
		var myLatlng = new google.maps.LatLng(0,0);
		this.google.mapOptions = {
			zoom : 3,
			center : myLatlng,
			mapTypeId : google.maps.MapTypeId.ROADMAP,
			disableDefaultUI : true,
			streetViewControl : false,
			keyboardShortcuts: true
		};
	
		this.google.map = new google.maps.Map(
			document.getElementById("map-canvas"), this.google.mapOptions);
		google.maps.event.addListener(this.google.map, 'idle', function(bounds) {
			//console.log('idle');
			//pthis.drawPlaces();
		});
		this.google.geocoder = new google.maps.Geocoder();
	};
	
	
	/* Add all widgets of the map */
	var draw_ui_widgets = function ()
	{	
		var pthismap = this;
	
		// Create areas
		this.widget_areas = {
			'top' : new collectopia.Map.WidgetArea(this, 'top', {left : 0, top: 0}),
			'left' : new collectopia.Map.WidgetArea(this, 'left', {left : 0, top : 80}),
			'right' : new collectopia.Map.WidgetArea(this, 'right', {right : 0, bottom : 100}),
			'left-bottom' : new collectopia.Map.WidgetArea(this, 'left-bottom', {left : 0, bottom : 30})
		};
		
		// Search
		new collectopia.Map.SearchWidget(this.widget_areas['top'], 'search');
		
		// Create button
		new collectopia.Map.ButtonWidget(this.widget_areas['right'], 'create', 'Add Place', function(event){
			if (this.isLocked())
				return;
			
			var pbutton = this;			
			this.switchOn().lock();
			
			var add_panel = new collectopia.AddPlacePanel(pthismap, pthismap.google.geocoder);
			add_panel.events.bind('closed', function() {	pbutton.switchOff().unlock();	}); 
			return false;
		});

		// Random button
		new collectopia.Map.ButtonWidget(this.widget_areas['right'], 'random', 'Random Place', function(event){
			var place_ids = [];
			for(var i in map.places)
				place_ids.push(i);
			var p = map.places[place_ids[Math.floor((place_ids.length-1) * Math.random())]];
			p.panTo(pthismap);
			p.showInfo(pthismap);
			 
			return false;
		});
		
		// Help button
		new collectopia.Map.ButtonWidget(this.widget_areas['right'], 'help', 'Help', function(event){
			if (this.isLocked())
				return;
			var pbutton = this;
			this.switchOn().lock();
			
			var panel = new collectopia.HelpPanel();
			panel.events.bind('closed', function() {	pbutton.switchOff().unlock();	}); 
			return false;
		});
		
		// Expose button		
		var btn_expose = new collectopia.Map.ButtonWidget(this.widget_areas['left-bottom'],
				'expose', 'Expose', function(event){
			var pbutton = this;
			this.switchOn();			
			collectopia.panels.toggleExpose();			 
			return false;
		}, true);
		collectopia.panels.events.bind('expose-changed', function(event, enabled){
			if (enabled)
				btn_expose.switchOn().lock();
			else
				btn_expose.switchOff().unlock();
		});
		
		// Categories filter
		new collectopia.Map.FilterWidget(this.widget_areas['left']);
	};

	init_google_map.call(this);
	draw_ui_widgets.call(this);

	$.get('api/place/+all', function(data){
		pthis.places = {};
		for(var id in data)
			pthis.places[id] = new collectopia.api.Place(data[id]);
		pthis.drawPlaces();
	});
};

collectopia.Map.prototype.getDom = function () {
	return this.dom;
};

/**
 * Get a list of places divide in show and hide category
 */
collectopia.Map.prototype.getFilteredPlaces = function(){
	if (this.show_filters.length == 0)
		return this.places;
	
	var filtered = { 'show' : {}, 'hide' : {}};
	for(var p in this.places) {
		var skip = false;
		for(var i in this.show_filters)	{
			if (this.show_filters[i](this.places[p]))
				continue;
			skip = true;
		}
		filtered[skip?'hide':'show'][p] = this.places[p];
	}
	return filtered;
};

/**
 * Draw all places of the map, using the filters
 */
collectopia.Map.prototype.drawPlaces = function() {
	
	if (this.show_filters.length == 0) {
		//console.log('drawing all places');
		for(var i in this.places)
			this.places[i].showMarker(this);
	} else {
		//console.log('drawing using filters');
		var filtered = this.getFilteredPlaces();
		for(var i in filtered['show'])
			filtered['show'][i].showMarker(this);
		for(var i in filtered['hide'])
			filtered['hide'][i].hideMarker(this);
	}	
};


/* --------------------------------------------------------------------*/

/**
 * A widget area on the map for widgets (WOW!)
 * @param map
 */
collectopia.Map.WidgetArea = function (map, name, position) {
	this.map = map;
	this.widgets = [];
	this.position = position;
	this.dom = $('<ul class="widget-area" />').appendTo(map.getDom())
		.css(position).attr('name', name).addClass(name);
};

collectopia.Map.WidgetArea.prototype.getDom = function() {
	return this.dom;
};

collectopia.Map.WidgetArea.prototype.getMap = function() {
	return this.map;
};

collectopia.Map.WidgetArea.prototype.addWidget = function(w) {
	this.widgets.push(w);
};

/* --------------------------------------------------------------------*/

/**
 * Base class for creating widgets
 */
collectopia.Map.Widget = function(area, classes) {
	this.area = area;
	this.dom = $('<li class="widget" />').appendTo(area.getDom())
		.addClass('widget').addClass(classes);
	this.area.addWidget(this);
};

collectopia.Map.Widget.prototype.getDom = function() {
	return this.dom;
};

collectopia.Map.Widget.prototype.getArea = function() {
	return this.area;
};

/* --------------------------------------------------------------------*/

/**
 * Base class for creating widgets
 */
collectopia.Map.FilterWidget = function(area, map) {
	collectopia.Map.Widget.call(this, area, 'filter');
	
	var pwidget = this;
	var ul = $('<ul class="categories">').appendTo(this.getDom());
	var cats = this.getArea().getMap().categories;
	var li;
	for(var i in cats) {
		ul.append(li = $('<li />')
			.attr('title', cats[i].title)
			.addClass('sprite').addClass('sprite cat small ' + cats[i].tag).append('<span> </span>'));
		li.data('color', cats[i].color).data('category', cats[i]);

		li.click(function(){
			pwidget.toggleCategory($(this).data('category').tag);
			pwidget.getArea().getMap().drawPlaces();
		});
		pwidget.enableCategory(cats[i].tag);
	}
	
	// Add our filter on the map
	this.getArea().getMap().show_filters.push(function(p) {
		for(c in pwidget.enabled_categories)
			if (p.categories.indexOf(pwidget.enabled_categories[c]) >= 0)
				return true;
		return false;
	});
};
collectopia.Map.FilterWidget.prototype = jQuery.extend({}, collectopia.Map.Widget.prototype);
collectopia.Map.FilterWidget.prototype.constructor = collectopia.Map.FilterWidget;

/**
 * Enable a category
 * @param cat The tag of the category
 */
collectopia.Map.FilterWidget.prototype.enableCategory = function(cat) {
	var li = this.getDom().find('li.' + cat);
	li.css({
		'box-shadow' : '0px 0px 4px 2px #' + li.data('color'),
		'background-color' : '#' + li.data('color')
		}).addClass('enabled');
	delete this.enabled_categories;
	this.getEnabledCategories();	
};

/**
 * Disable a category
 * @param cat The tag of the category
 */
collectopia.Map.FilterWidget.prototype.disableCategory = function(cat) {
	var li = this.getDom().find('li.' + cat);
	li.css({'box-shadow' : '', 'background-color' : 'transparent'}).removeClass('enabled');
	delete this.enabled_categories;
	this.getEnabledCategories();	
};

/**
 * Toggle a category
 * @param cat The tag of the category
 */
collectopia.Map.FilterWidget.prototype.toggleCategory = function(cat) {
	var li = this.getDom().find('li.' + cat);
	if (li.hasClass('enabled'))
		this.disableCategory(cat);
	else
		this.enableCategory(cat);
};

/**
 * Get all enabled categories
 */
collectopia.Map.FilterWidget.prototype.getEnabledCategories = function(){
	if (collectopia.isDefined(this.enabled_categories))
		return this.enabled_categories;
	var ec = this.enabled_categories = [];
	this.getDom().find('li.enabled').each(function(e){
		ec.push($(this).data('category').tag);
	});
	return ec;
};


/* --------------------------------------------------------------------*/

/**
 * Button widget implementor
 */
collectopia.Map.ButtonWidget = function(area, classes, title, callback, ltr) {
	collectopia.Map.Widget.call(this, area, classes);
	
	this.locked = false;
	this.ltr = Boolean(ltr);
	var pbutton = this;
	
	$('<span class="description" />').attr('title', title)
		.appendTo(this.dom.addClass('button').click(function(event){
			callback.call(pbutton, event);
		}));

	this.dom.hover(
		function(){
			if (pbutton.locked)
				return;
			pbutton.animateSwitchOn();			
		},
		function(){
			if (pbutton.locked)
				return;
			pbutton.animateSwitchOff();
		}
	);
	
};
collectopia.Map.ButtonWidget.prototype = jQuery.extend({}, collectopia.Map.Widget.prototype);
collectopia.Map.ButtonWidget.prototype.constructor = collectopia.Map.ButtonWidget;

/**
 * Animate to switching on this button 
 */
collectopia.Map.ButtonWidget.prototype.animateSwitchOn = function(callback) {
	this.getDom().stop(true, true).animate(this.ltr?{left: -34}:{right: -34}, callback);	
	return this;
};

/**
 * Animate to switch off this button 
 */
collectopia.Map.ButtonWidget.prototype.animateSwitchOff = function(callback) {
	this.getDom().stop(true, true).animate(this.ltr?{left: -54}:{right: -54}, callback);	
	return this;
};

/**
 * Switch on this button 
 */
collectopia.Map.ButtonWidget.prototype.switchOn = function() {
	var pthis = this;
	this.animateSwitchOn(function(){ pthis.getDom().removeAttr('style').addClass('enabled'); });
	return this;
};

/**
 * Switch off this button
 */
collectopia.Map.ButtonWidget.prototype.switchOff = function() {
	var pthis = this;
	this.animateSwitchOff(function(){ pthis.getDom().removeAttr('style').removeClass('enabled'); });
	return this;
};

/**
 * Lock button state
 */
collectopia.Map.ButtonWidget.prototype.lock = function() {
	this.locked = true;
	return this;
};

/**
 * UnLock button state
 */
collectopia.Map.ButtonWidget.prototype.unlock = function() {
	this.locked = false;
	return this;
};

/**
 * Check the lock state
 * @returns {Boolean}
 */
collectopia.Map.ButtonWidget.prototype.isLocked = function() {
	return this.locked;
};

/* --------------------------------------------------------------------*/

/**
 * Search widget implementor
 */
collectopia.Map.SearchWidget = function(area, classes) {
	collectopia.Map.Widget.call(this, area, classes);
	
	// Build search widget
	var map = this.getArea().getMap();
	var search_div = this.dom,
		search = search_div.createEl('input', { name : 'search' });

	search.autocomplete({
	source: function( request, response ) {
		$.ajax({
			url: "api/search",
			data: {
				query: request.term
			},
			success: function( data ) {
				response( $.map( data, function( p) {
					return {
						label: p.name + ', ' + p.city + ' ' + p.country,
						value: p.name + ', ' + p.city + ' ' + p.country,
						id: p.id
					};
				}));
			}
		});
	},
	minLength: 2,
	select: function( event, ui ) {
		if (ui.item){
			if (collectopia.isDefined(map.places[ui.item.id])){
				map.places[ui.item.id].panTo(map);
				map.places[ui.item.id].showInfo(map);
			}
		}
	}
	});

	google.maps.event.addListener(map.google.map, 'click', function() {
		search.blur();
	});
};
collectopia.Map.SearchWidget.prototype = jQuery.extend({}, collectopia.Map.Widget.prototype);
collectopia.Map.SearchWidget.prototype.constructor = collectopia.Map.SearchWidget;


/* --------------------------------------------------------------------*/

/**
 * Extend google.Marker to suport querying for xy point on the projection (window).
 * @returns {google.maps.Point}
 */
google.maps.Marker.prototype.getPoint = function(){
	var
		topRight = this.map.getProjection().fromLatLngToPoint(this.map.getBounds().getNorthEast()),
	    bottomLeft = this.map.getProjection().fromLatLngToPoint(this.map.getBounds().getSouthWest()),
	    scale = Math.pow(2, this.map.getZoom()),
	    worldPoint = this.map.getProjection().fromLatLngToPoint(this.getPosition());
	 return new google.maps.Point((worldPoint.x - bottomLeft.x)*scale,(worldPoint.y-topRight.y)*scale); 
};

/* -------------------------------------------------------------------- */

/**
 * Extend collectopia.api.Place to support map operations
 */
collectopia.api.Place.prototype.getGoogleLatLng = function() {
	return new google.maps.LatLng(this.loc_lat, this.loc_lng);
};

/**
 * Center map and zoom on it.
 */
collectopia.api.Place.prototype.panTo = function(map) {
	map.google.map.panTo(this.getGoogleLatLng());
	map.google.map.setZoom(10);
};

/**
 * Show places info panel
 */
collectopia.api.Place.prototype.showInfo = function(map) {
	if (!collectopia.isDefined(this._infopanel)) {
		this._infopanel = new collectopia.InfoPanel(map, this);
		this._infopanel.events.bind('closed', function(event){
			delete this.place._infopanel;
		});
	}else
		this._infopanel.bringToFront();
};

/**
 * Show the marker on map for this place
 */
collectopia.api.Place.prototype.showMarker = function(map, redraw) {
	
	if (Boolean(redraw) && collectopia.isDefined(this._marker)) {
		// To redraw, erase everything and recall
		var focused = Boolean(this._marker._focused);
		this._marker.setMap(null);
		delete this._marker;		
		this.showMarker(map);	// Create nwe
		this._marker._focused = focused;
		if (focused)
			this.useFocusedMarker();
	}
	
	// Skip the rendered ones.
	if (!collectopia.isDefined(this._marker)) {
		this._marker = new google.maps.Marker( {
			map : map.google.map,
			position : this.getGoogleLatLng(),
			title : this.name,
			icon : this.getMarkerImage().toGoogleMarkerImage(),
			shadow : new google.maps.MarkerImage(
					'static/images/marker_shadow.png',
					new google.maps.Size(79, 28),
					new google.maps.Point(0, 0),
					new google.maps.Point(0, 28)
				),
			place: this
		});
		
		google.maps.event.addListener(this._marker, 'click', function() {
			this.place.showInfo(map);			
		});
	} else if (!this._marker.getMap()) {
		
		// Show marker if hidden
		this._marker.setMap(map.google.map);
	} else if (!this._marker.getVisible()) {
		
		// Show marker if hidden
		this._marker.setVisible(true);
	}
};

/**
 * Switch to focused marker for this place
 */
collectopia.api.Place.prototype.useFocusedMarker = function() {
	if (!collectopia.isDefined(this._marker))
		return;
	
	this._marker._focused = true;
	this._marker.setIcon(this.getFocusedMarkerImage().toGoogleMarkerImage());
	this._marker.setShadow(new google.maps.MarkerImage(
			'static/images/marker_shadow.png',
			new google.maps.Size(152, 56),
			new google.maps.Point(79, 0),
			new google.maps.Point(0, 56)
		));
};


/**
 * Switch to normal marker for this place
 */
collectopia.api.Place.prototype.useNormalMarker = function() {
	if (!collectopia.isDefined(this._marker))
		return;
	
	this._marker._focused = false;
	this._marker.setIcon(this.getMarkerImage().toGoogleMarkerImage());
	this._marker.setShadow(new google.maps.MarkerImage(
			'static/images/marker_shadow.png',
			new google.maps.Size(79, 28),
			new google.maps.Point(0, 0),
			new google.maps.Point(0, 28)
		));
};

/**
 * Hide the marker of a place from the map
 */
collectopia.api.Place.prototype.hideMarker = function() {
	if (collectopia.isDefined(this._marker))
		this._marker.setVisible(false);
};


/**
 * Remove the marker of a place from the map
 */
collectopia.api.Place.prototype.removeMarker = function() {
	if (collectopia.isDefined(this._marker))
		this._marker.setMap(null);
};
