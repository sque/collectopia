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
			//pthis.drawPlaces();
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
	console.log('drawing places');
	for(var i in this.places){
		var place = this.places[i];
		
		// Skip the rendered ones.
		if (collectopia.isDefined(place.marker))
			continue;
		
		place.marker = new google.maps.Marker( {
			map : this.google.map,
			position : new google.maps.LatLng(place.loc_lat, place.loc_lng),
			title : place.name,
			icon : place.getMarkerImage().toGoogleMarkerImage(),
			shadow : new google.maps.MarkerImage(
				'static/images/marker_shadow.png',
				null,
				null,
				new google.maps.Point(0, 28)
			),
			place: place
		});
		var pthis = this;
		google.maps.event.addListener(place.marker, 'click', function() {
			if (this.place.infopanel == undefined)
				this.place.infopanel = new collectopia.InfoPanel(pthis, this.place);
			else
				this.place.infopanel.bringToFront();
		});
	};
};
collectopia.Map.prototype.showPlace = function(place_id) {
	var place = this.places[place_id];
	if ((place_id == undefined)
		|| ((place = this.places[place_id]) == undefined))
		return;
	this.google.map.panTo(new google.maps.LatLng(place.loc_lat, place.loc_lng));
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