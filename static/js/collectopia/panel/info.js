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
		pthis.place.marker.setIcon(pthis.place.getFocusedMarkerImage().toGoogleMarkerImage());		
	};	
	var downlight_marker = function(){
		pthis.place.marker.setIcon(pthis.place.getMarkerImage().toGoogleMarkerImage());
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
	marker_pos = this.place.marker.getPoint();
	this.moveNear(marker_pos.x - 10, marker_pos.y - 56, 140, 56);
};
collectopia.InfoPanel.prototype = jQuery.extend({}, collectopia.Panel.prototype);
collectopia.InfoPanel.prototype.constructor = collectopia.InfoPanel;

collectopia.InfoPanel.prototype.switchToView = function() {
	this.clearActions();	
	
	if (collectopia.isDefined(this.editor))
		this.editor.detach();	
	this.viewer.attachTo(this.dom_body);
	
	this.addAction('report', 'Report', function(){ console.log('Report fake'); });
	this.addAction('edit', 'Edit', function(){ this.switchToEdit(); });
};

collectopia.InfoPanel.prototype.switchToEdit = function() {
	this.clearActions();
	
	if (!collectopia.isDefined(this.editor))
		this.editor = new collectopia.ui.PlaceEditor(this.place, map, map.google.geocoder);
	this.viewer.detach();
	this.editor.attachTo(this.dom_body);
	
	this.addAction('cancel', 'Cancel', function(){ this.switchToView(); });
	this.addAction('save', 'Save', function(){ this.editor.form.submit(); });
};