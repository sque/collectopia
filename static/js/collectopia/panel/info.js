collectopia.namespace('collectopia');

/**
 * Specialized version of Panel for Info.
 * @returns
 */
collectopia.InfoPanel = function(map, place) {
	var pthis = this;
	collectopia.Panel.call(this, 'info', place.short_name, []);
		
	 
	// Store variables
	this.place = place;	
	this.map = map;
	
	// Construct components
	this.viewer = new collectopia.ui.PlaceViewer(place);	
	this.switchToView();
	
	// Capture events	
	var highlight_marker = function(){
		pthis.place._marker.setIcon(pthis.place.getFocusedMarkerImage().toGoogleMarkerImage());		
	};	
	var downlight_marker = function(){
		pthis.place._marker.setIcon(pthis.place.getMarkerImage().toGoogleMarkerImage());
	};	
	this.events.bind('closed',function(){
		downlight_marker();
		if (collectopia.isDefined(pthis.editor))
			pthis.editor.detach();
		pthis.viewer.detach();
	});
	this.events.bind('focus', highlight_marker);
	this.events.bind('blur', downlight_marker);
	highlight_marker();
	
	// Move window where the marker is
	marker_pos = this.place._marker.getPoint();
	this.moveNear(marker_pos.x - 10, marker_pos.y - 56, 140, 56);
};
collectopia.InfoPanel.prototype = jQuery.extend({}, collectopia.Panel.prototype);
collectopia.InfoPanel.prototype.constructor = collectopia.InfoPanel;

/**
 * Build the editor part
 */
collectopia.InfoPanel.prototype.buildEditor = function() {
	this.editor = new collectopia.ui.PlaceEditor(this.place, map, map.google.geocoder);
	
	var pthis = this;
	this.editor.getEvents().bind('ajax.start', function(){ pthis.disable('<div class="loading" />'); });
	this.editor.getEvents().bind('ajax.end', function(){ pthis.enable(); });
	this.editor.getEvents().bind('success', function(e, args) {
		pthis.clearActions();
		var initial_size = {h: pthis.dom_body.height(), w: pthis.dom_body.width() };
		// Reshape form
		pthis.dom_body.html('<div class="submitted">' +
			'<h1>Place has been successfully updated.</h1></div>');
		pthis.dom_body.height(initial_size.h);
		pthis.dom_body.width(initial_size.w);
		var place = this.form.api_form.getObject();
		place.showMarker(map, true);
		
		// Fade out
		pthis.editor.marker.setMap(null);
		pthis.dom.delay(1500).queue(function(){
			pthis.dom_body.css({width : '', height : ''});
			pthis.dom.css({width : '', height : ''});
			pthis.dom_body.html('');  
			pthis.switchToView(); 
		});
	});
	
	this.events.bind('onclose', function(){
		pthis.editor.detach();
	});	
	
	this.events.bind('detached', function(){
		pthis.place.hideMarker();
	});
};

/**
 * Switch to view mode
 */
collectopia.InfoPanel.prototype.switchToView = function() {
	this.clearActions();	
	
	if (collectopia.isDefined(this.editor))
		this.editor.detach();
	this.viewer.attachTo(this.dom_body);
	
	this.addAction('report', 'Report', function(){ console.log('Report fake'); });
	this.addAction('edit', 'Edit', function(){ this.switchToEdit(); });
};

/**
 * Switch to edit mode
 */
collectopia.InfoPanel.prototype.switchToEdit = function() {
	this.clearActions();
	
	if (!collectopia.isDefined(this.editor))
		this.buildEditor();
	
	this.viewer.detach();
	this.editor.attachTo(this.dom_body);
	
	this.addAction('cancel', 'Cancel', function(){ this.switchToView(); });
	this.addAction('save', 'Save', function(){ this.editor.form.submit(); });
};