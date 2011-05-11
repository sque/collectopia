
collectopia.namespace('collectopia');

/**
 * Specialized version of Panel for Add Place form.
 * @returns
 */
collectopia.AdminPanel = function(map) {
	var pthis = this;
	collectopia.Panel.call(this, 'admin', 'Admin');
	this.addAction('logout', 'Logout', function(){ window.location = '+logout'; });
	this.addAction('close', 'Close', function(){ this.close(); });
	this.dom_body.createEl('iframe', { src : 'admin'});	
};
collectopia.AdminPanel.prototype = jQuery.extend({}, collectopia.Panel.prototype);
collectopia.AdminPanel.prototype.constructor = collectopia.HelpPanel;


var draw_ui = function() {
	// Load css
	$('head').append(
		$('<link rel="stylesheet" type="text/css" href="static/css/admin.css" />')
	);
	
	// Random button
	new collectopia.Map.ButtonWidget(map.widget_areas['right'], 'admin', 'Admin', function(event){
		if (this.isLocked())
			return;
		
		var pbutton = this;			
		this.switchOn().lock();
		
		var admin_panel = new collectopia.AdminPanel(map);
		admin_panel.events.bind('closed', function() {	pbutton.switchOff().unlock();	}); 
		return false;
	});
	
	// Info panel has new action
	var orig_switchToView = collectopia.InfoPanel.prototype.switchToView;
	collectopia.InfoPanel.prototype.switchToView = function()
	{
		var panel = this;
		orig_switchToView.call(this);
		this.addAction('delete', 'Delete', function(){ 
			if (confirm("Are you sure you want to delete this place!?")) {
				panel.place.reqDelete(function(){
					panel.close();
					panel.place.removeMarker();
				});
				
			}
		});
	};
	
	console.log('admin ui loaded.');
};


draw_ui();