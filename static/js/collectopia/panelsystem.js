
collectopia.namespace('collectopia');


/**
 * A complete system to manage panels and give
 * functionality found on desktop window systems. 
 */
collectopia.PanelSystem = function() {
	var pthis = this;
	
	this.all_panels = {};
	this.zorder = [];
	
	this._private_ = {		
		panel_id_next : 0,
		update_css_zindex : function(){
			for(var i in pthis.zorder) {
				pthis.all_panels[pthis.zorder[i]].dom.css('z-index', 10 + i);
			}
		}
	};
};

/**
 * Register a new panel on system
 */
collectopia.PanelSystem.prototype.registerPanel = function(p) {
	p.id = this._private_.panel_id_next++;
	this.all_panels[p.id] = p;
	this.zorder.push(p.id);
	p.events.triggerHandler('focus', { panel: p });
	this._private_.update_css_zindex();
};

/**
 * Unregister a panel from system
 */
collectopia.PanelSystem.prototype.unregisterPanel = function(p) {
	delete this.all_panels[p.id];	// Remove from panels
		
	var zpos = this.zorder.indexOf(p.id);
	if (zpos == (this.zorder.length - 1)) {
		// Removed the focused		
		this.zorder.splice(zpos, 1);
		this._private_.update_css_zindex();
		this.all_panels[this.zorder[this.zorder.length - 1]].events.triggerHandler('focus', { panel : this});
	} else if (zpos != -1) {
		this.zorder.splice(zpos, 1);
		this._private_.update_css_zindex();
	}	
};

/**
 * Get the focused window
 */
collectopia.PanelSystem.prototype.focused = function() {
	return this.all_panels[this.zorder[this.zorder.length - 1]];
};

/**
 * Bring a panel to front
 */
collectopia.PanelSystem.prototype.bringToFront= function(panel_id) {
	var infront,
		zpos = this.zorder.indexOf(panel_id);
	if (zpos == -1)
		return; // WTF Data!?
	
	if (zpos == this.zorder.length - 1)
		return; // Already panel to foreground
	
	// Blur actual infront panel
	infront = this.focused();
	infront.events.triggerHandler('blur', { panel: infront});
	
	// Reorder things
	this.zorder.splice(zpos, 1);
	this.zorder.push(panel_id);	
	this._private_.update_css_zindex();
	
	// Focus event
	infront = this.focused();
	infront.events.triggerHandler('focus', { panel: infront});
};


/* Initialize panel system */
collectopia.panels = new collectopia.PanelSystem();