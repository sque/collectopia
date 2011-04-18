collectopia.namespace('collectopia');

/**
 * @class Create the complete map for collectopia
 */
collectopia.Map = function(dom, categories) {
	var pthis = this;
	this.google = {};
	this.places = {};	
	this.categories = categories;
	this.dom = dom;
	
	/* Describe buttons functionality */
	this.buttons = {
		create : {
			dom : null,
			title : 'create',
			click : function() {
				var btn = $(this);
				if (btn.hasClass('enabled'))
					return;
				btn.addClass('enabled');
				
				var add_panel = new collectopia.AddPlacePanel(pthis, pthis.google.geocoder);
				add_panel.events.bind('closed', function() {
					btn.stop(true, true).animate({left: -65}).removeClass('enabled');
					pthis.drawPlaces();
				}); 
				return false;
			},
			category : 'general'
		},
		expose : {
			dom : null,
			title : 'Full view',
			click : function() {
				collectopia.panels.toggleExpose();
				return false;
			},
			initialize : function(btn) {
				collectopia.panels.events.bind('expose-changed', function(event, params){
					if (params['enabled'])
						btn.addClass('enabled');
					else
						btn.removeClass('enabled');
				});
			},
			category : 'map-control'
		},
		help : {
			dom : null,
			title : 'Help',
			click : function() {
				var btn = $(this);
				if (btn.hasClass('enabled'))
					return;
				btn.addClass('enabled');
				
				var panel = new collectopia.HelpPanel();
				panel.events.bind('closed', function() {
					btn.stop(true, true).animate({left: -65}).removeClass('enabled');
				}); 
				return false;
			},			
			category : 'help'
		}
	};
	
	
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
			console.log('idle');
			pthis.drawPlaces();
		});
		this.google.geocoder = new google.maps.Geocoder();
	};
	
	/* Draw ui search */
	var draw_ui_search = function()
	{
		var search_div = this.dom.createEl('div', { class : 'widget search'}),
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
				if (ui.item)
					pthis.showPlace(ui.item.id);					
			}
		});
		/*search_div.createEl('ul', { class : 'results' });		
		var show_results = function(data) {
			var ul = search_div.find('ul').html('');
			for(var i in data) {
				var li = ul.createEl('li', { class : data[i].type });
				if (data[i].type == 'place') {
					var p = data[i];
					li.data('id', p.id);
					li.text(p.name + ', ' + p.city + ' ' + p.country);
					li.click(function(){
						pthis.showPlace($(this).data('id'));
					});
				}
			}
		};
		
		search.bind('keyup', function(){
			$.get('api/search', { query : search.val() }, function(data){
				show_results(data);
			});
		});*/
	};
	
	
	/* Add button actions on the ui of collectopia */
	var draw_ui_buttons = function ()
	{	
		var ul = this.dom.createEl('ul', { class: 'buttons' });
		var mapcontrolul = this.dom.createEl('ul', { class: 'buttons map-control' });
		for(var class in this.buttons) {
			this.buttons[class].dom = ((this.buttons[class].category == 'map-control')?mapcontrolul:ul).createEl('li', { class: class });
			this.buttons[class].dom.createEl('span', { class: 'description'}).text(this.buttons[class].title);
			this.buttons[class].dom.click(this.buttons[class].click);
			
			if (collectopia.isDefined(this.buttons[class].initialize))
					this.buttons[class].initialize(this.buttons[class].dom);
		}
		
		// Add hover effect on buttons
		$('ul.buttons li').hover(
			function(){
				var pthis = $(this); if (pthis.hasClass('enabled')) {
					pthis.removeAttr('style');
					return;
				}
				$(this).stop(true, true).animate({left: 0}); },
			function(){
				var pthis = $(this); if (pthis.hasClass('enabled')) {
					pthis.removeAttr('style');
					return;
				}
				$(this).stop(true, true).animate({left: -65});
			}
		);
	};

	init_google_map.call(this);
	draw_ui_search.call(this);
	draw_ui_buttons.call(this);

	$.get('api/place/+all', function(data){
		pthis.places = {};
		for(var id in data)
			pthis.places[id] = new collectopia.api.Place(data[id]);
		pthis.drawPlaces();
	});
};


collectopia.Map.prototype.drawPlaces = function() {
	console.log('drawing all places');
	for(var i in this.places){		
		this.places[i].showMarker(this);
	};
};
collectopia.Map.prototype.showPlace = function(place_id) {
	
	if ((!collectopia.isDefined(place_id))
		|| (!collectopia.isDefined(this.places[place_id])))
		return;
	var place = this.places[place_id];
	this.google.map.panTo(place.getGoogleLatLng());
	this.google.map.setZoom(10);
};

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

/**
 * Extend collectopia.api.Place to support map operations
 */
collectopia.api.Place.prototype.getGoogleLatLng = function() {
	return new google.maps.LatLng(this.loc_lat, this.loc_lng);
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
		this._marker.setMap(null);
		delete this._marker;		
		return this.showMarker(map);
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
				null,
				null,
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
	}
};

/**
 * Switch to focused marker for this place
 */
collectopia.api.Place.prototype.useFocusedMarker = function() {
	if (!collectopia.isDefined(this._marker))
		return;
	
	this._marker.setIcon(this.getFocusedMarkerImage().toGoogleMarkerImage());
	this._marker.setShadow(new google.maps.MarkerImage(
			'static/images/marker_focus_shadow.png',
			null,
			null,
			new google.maps.Point(0, 56)
		));
};


/**
 * Switch to normal marker for this place
 */
collectopia.api.Place.prototype.useNormalMarker = function() {
	if (!collectopia.isDefined(this._marker))
		return;
	
	this._marker.setIcon(this.getFocusedMarkerImage().toGoogleMarkerImage());
	this._marker.setShadow(new google.maps.MarkerImage(
			'static/images/marker_shadow.png',
			null,
			null,
			new google.maps.Point(0, 28)
		));
};

/**
 * Hide the marker of a place from the map
 */
collectopia.api.Place.prototype.hideMarker = function() {
	if (collectopia.isDefined(this._marker))
		this._marker.setMap(null);
};