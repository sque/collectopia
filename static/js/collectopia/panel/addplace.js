collectopia.namespace('collectopia');

/**
 * Specialized version of Panel for Add Place form.
 * @returns
 */
collectopia.AddPlacePanel = function(map, geocoder) {
	var pthis = this,
		marker_moved_action;
	collectopia.Panel.call(this, 'place-new', 'Add Place', 
		[{'title': 'send', 'callback': function(){				
				pthis.editor.form.submit();
			}
		}]);
	
	// Save data
	this.map = map;
	this.geocoder = geocoder;

	// Move a bit on the right to see th marker
	var view = collectopia.panels.size();
	this.move(view.width - this.dom.width() - 240, 15);	
	
	this.editor = new collectopia.ui.PlaceEditor(undefined, map, geocoder);
	this.editor.getEvents().bind('ajax.start', function(){ pthis.disable('<div class="loading" />'); });
	this.editor.getEvents().bind('ajax.end', function(){ pthis.enable(); });
	this.editor.getEvents().bind('success', function(e, args) {
		var initial_size = {h: pthis.dom_body.height(), w: pthis.dom_body.width() };
		// Reshape form
		pthis.dom_body.html('<div class="submitted">' +
			'<h1>Your place has been successfully submitted.</h1></div>');
		pthis.dom.children('ul').remove();
		pthis.dom_body.height(initial_size.h);
		pthis.dom_body.width(initial_size.w);
		var place =this.form.api_form.getObject();
		map.places[place.id] = place;
		map.drawPlaces();
		
		// Fade out
		pthis.editor.marker.setMap(null);
		pthis.dom.delay(1500).fadeOut('slow', function(){	pthis.close(); });
	});
	this.editor.attachTo(pthis.dom_body);
	
	this.events.bind('onclose', function(){
		pthis.editor.marker.setMap(null);
	});	
	
	

};
collectopia.AddPlacePanel.prototype = jQuery.extend({}, collectopia.Panel.prototype);
collectopia.AddPlacePanel.prototype.constructor = collectopia.AddPlacePanel;

