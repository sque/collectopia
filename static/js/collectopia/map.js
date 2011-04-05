
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
				
				var create_panel = new collectopia.CreatePlacePanel(pthis, pthis.google.geocoder);
				create_panel.events.bind('closed', function() {
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
			zoom : 2,
			center : myLatlng,
			mapTypeId : google.maps.MapTypeId.ROADMAP,
			disableDefaultUI : true,
			streetViewControl : false
		};
	
		this.google.map = new google.maps.Map(
			document.getElementById("map-canvas"), this.google.mapOptions);
		google.maps.event.addListener(this.google.map, 'idle', function(bounds) {
			pthis.drawPlaces();
		});
		this.google.geocoder = new google.maps.Geocoder();
	};
	
	/* Fetch asynchronous form templates */
	var fetch_templates = function() {

		// Fetch needed panels
		$.get('api/place/new', function(data){
			$('#panels').html($('#panels').html() + data);
		});
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
			
			if (collectopia.is_defined(this.buttons[class].initialize))
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
	fetch_templates.call(this);
	draw_ui_search.call(this);
	draw_ui_buttons.call(this);

	$.get('api/place', function(data){
		pthis.places = data;
		pthis.drawPlaces();
	});
};
collectopia.Map.prototype.drawPlaces = function() {
	for(var i in this.places){
		var place = this.places[i];
		
		// Skip the rendered ones.
		if (collectopia.is_defined(place.marker))
			continue;
		
		place.marker = new google.maps.Marker( {
			map : this.google.map,
			position : new google.maps.LatLng(place.loc_lat, place.loc_lng),
			title : place.name,
			icon : new google.maps.MarkerImage(
				'api/place/' + place.id + '/image/marker',
				null,
				null,
				new google.maps.Point(0, 28)
			),
			shadow : new google.maps.MarkerImage(
				'static/images/marker_dark_shadow.png',
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
