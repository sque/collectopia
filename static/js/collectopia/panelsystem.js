
collectopia.namespace('collectopia');


/**
 * A complete system to manage panels and give
 * functionality found on desktop window systems. 
 * events exposed by PanelSystem
 * 
 * - expose-changed { enabled: bool }
 */
collectopia.PanelSystem = function() {
	var pthis = this;
	
	this.all_panels = {};
	this.zorder = [];
	this.events = $(this);
	
	this.expose_mode = {
		enabled: false,
		stored_state : {
			positions: {}
		}
	};
	
	this._private_ = {		
		panel_id_next : 0,
		update_css_zindex : function(){
			// Add proper z index
			for(var i in pthis.zorder) {
				var panel = pthis.all_panels[pthis.zorder[i]]; 
				panel.dom.css('z-index', 10 + i);
				if (i == pthis.zorder.length - 1) {
					panel.dom.css({'box-shadow' : '0px 0px 10px 0px #880000'});
				} else {
					panel.dom.css({'box-shadow' : ''});
				}
			}
		},
		status : {
			enabledKeyboardEvents : true
		}
	};
	
	// Hot keys system
	$(document).bind('keydown', function(e){
		if (e.keyCode == 27 && pthis._private_.status.enabledKeyboardEvents) {
			var f = pthis.focused();
			if (f)
				f.close();
		}
	});
};

/**
 * Keyboard input events
 */
collectopia.PanelSystem.prototype.enableKeyboardEvents = function (flag) {
	this._private_.status.enabledKeyboardEvents = Boolean(flag);
};

/**
 * Get the size of the panel system
 */
collectopia.PanelSystem.prototype.size = function() {
	var container = $('#wrapper');
	return { width: container.width(), height: container.height()};
};

/**
 * Register a new panel on system
 */
collectopia.PanelSystem.prototype.registerPanel = function(p) {
	p.id = this._private_.panel_id_next++;
	this.all_panels[p.id] = p;
	
	this.switchExposeOff();
	
	// Blur last
	var f = this.focused();
	if (collectopia.is_defined(f))
		f.events.triggerHandler('blur', { panel: f});

	// Focus new
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
	
	// Switch off expose mode
	this.switchExposeOff();
	
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

/**
 * Switch on expose mode
 */
collectopia.PanelSystem.prototype.switchExposeOn = function() {
	if (this.expose_mode.enabled)
		return;	// Already enabled
	
	// Save state
	this.expose_mode.enabled = true;
	this.expose_mode.stored_state.positions = {};
	
	var system_size = this.size();
	for(var id in this.all_panels) {
		var p = this.all_panels[id].dom;
		
		// Save state
		this.expose_mode.stored_state.positions[id] = {
			left : p.offset().left
		};
		
		// Animate to the borders
		if ((p.width() / 2 + p.offset().left ) > system_size.width /2)
			p.animate({ left : system_size.width});
		else
			p.animate({ left : - (p.width() + 30)});
	}
	
	// Send blur event on current focus
	if (this.zorder.length)
		this.focused().events.triggerHandler('blur', this.focused());
	
	// Expose related change event
	this.events.triggerHandler('expose-changed', { enabled : true });
};

/**
 * Switch off expose mode
 */
collectopia.PanelSystem.prototype.switchExposeOff = function() {
	if (! this.expose_mode.enabled)
		return;	// Already enabled
	
	for(var id in this.expose_mode.stored_state.positions) {
		var p = this.all_panels[id].dom;
		p.animate({ left : this.expose_mode.stored_state.positions[id].left});		
	}
	
	this.expose_mode.enabled = false;
	
	// Send focus event on last focused
	if (this.zorder.length)
		this.focused().events.triggerHandler('focus', this.focused());

	// Expose related change event
	this.events.triggerHandler('expose-changed', { enabled : false });
};

collectopia.PanelSystem.prototype.toggleExpose = function() {
	if (this.expose_mode.enabled)
		this.switchExposeOff();
	else
		this.switchExposeOn();
};

/* Initialize panel system */
collectopia.panels = new collectopia.PanelSystem();