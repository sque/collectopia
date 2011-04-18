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
					panel.dom.addClass('active');
				} else {
					panel.dom.removeClass('active');
				}
			}
		},
		status : {
			enabledKeyboardEvents : true
		}
	};
	
	// Hot keys system
	$(document).bind('keydown', function(e){
		if (pthis._private_.status.enabledKeyboardEvents) {
			switch (e.keyCode){
			case 27:
				var f = pthis.getFocused();
				if (f)
					f.close();
				break;
			case 32:
				if ($(':focus').length == 0)
					pthis.toggleExpose();
				break;
			}
			
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
	var f = this.getFocused();
	if (collectopia.isDefined(f))
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
		if (this.zorder.length != 0)
			this.all_panels[this.zorder[this.zorder.length - 1]].events.triggerHandler('focus', { panel : this});
	} else if (zpos != -1) {
		this.zorder.splice(zpos, 1);
		this._private_.update_css_zindex();
	}	
};

/**
 * Get the focused window
 */
collectopia.PanelSystem.prototype.getFocused = function() {
	if (this.zorder.length == 0)
		return;
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
	
	// Blur actual focused panel
	infront = this.getFocused();
	infront.events.triggerHandler('blur', { panel: infront});
	
	// Reorder things
	this.zorder.splice(zpos, 1);
	this.zorder.push(panel_id);	
	this._private_.update_css_zindex();
	
	// Focus event
	infront = this.getFocused();
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
	
	var system_size = this.size();
	for(var id in this.all_panels) {
		var p = this.all_panels[id].dom;
		
		// Save state
		if (! collectopia.isDefined(this.expose_mode.stored_state.positions[id])) {
			this.expose_mode.stored_state.positions[id] = {
				left : p.offset().left
			};
		}
		
		// Animate to the borders
		if ((p.width() / 2 + p.offset().left ) > system_size.width /2)
			p.stop().animate({ left : system_size.width});
		else
			p.stop().animate({ left : - (p.width() + 30)});
	}
	
	// Send blur event on current focus
	if (this.zorder.length)
		this.getFocused().events.triggerHandler('blur', this.getFocused());
	
	// Expose related change event
	this.events.triggerHandler('expose-changed', { enabled : true });
};

/**
 * Switch off expose mode
 */
collectopia.PanelSystem.prototype.switchExposeOff = function() {
	if (! this.expose_mode.enabled)
		return;	// Already enabled
	
	// Loop around all stored states
	for(var id in this.expose_mode.stored_state.positions) {
		if (! collectopia.isDefined(this.all_panels[id])) {
			// This panel is lost
			delete this.expose_mode.stored_state.positions[id];
			continue;
		}
		var p = this.all_panels[id].dom,
			pthis = this;
		
		p.stop().animate({ left : this.expose_mode.stored_state.positions[id].left}, function(){
			var p = $(this).data('panel');
			delete pthis.expose_mode.stored_state.positions[p.id];
		});		
	}
	
	this.expose_mode.enabled = false;
	
	// Send focus event on last focused
	if (this.zorder.length)
		this.getFocused().events.triggerHandler('focus', this.getFocused());

	// Expose related change event
	this.events.triggerHandler('expose-changed', { enabled : false });
};

/**
 * Toggle the state of expose mode by switching On and Of
 */
collectopia.PanelSystem.prototype.toggleExpose = function() {
	if (this.expose_mode.enabled)
		this.switchExposeOff();
	else
		this.switchExposeOn();
};

/* Initialize panel system */
collectopia.panels = new collectopia.PanelSystem();

/**
 * Create a new panel, based on templates from #panels div.
 * @param type The css class of the panel. This will be used
 * 	to search for templates on #panels container.
 * @param title	The small title on the left top corner of the panel.
 * @param actions Array of objects { title: string, callback: callable }
 * @param parent The parent element that this panel will be created in.
 * 	If it is undefined, it will be rendered in #content.
 * 
 * @par Events
 * 
 * - 'onclose' When close has been requested.
 * - 'closed' When the close operation and effects has finished. 
 * - 'focus' when this panel is brought to the foreground.
 * - 'blur' when this panel looses foreground.
 * - 'disabled' when this panel is disabled.
 * - 'enabled' when this panel is enabled.
 */
collectopia.Panel = function(type, title, actions, parent) {
	var pthis = this,
		template = $('#panels').find('.' + type),
		inner_div,
		id = null;
	
	// Create window DOM	
	this.dom_parent = (parent == undefined)?$('#content'):parent;
	this.dom = $('<div class="panel"/>').append(
		'<div><span class="title" />' +
		'<span class="close">close</span>' +
		'<div class="container" /></div>'
	);
	this.dom.find('.title').text(title);
	this.dom.append($('<ul class="actions"></ul>'))
		.addClass(type)
		.hide();
	this.dom_body = this.dom.find('.container');
	this.dom.data('panel', this);	
	if (template.length > 0)
		this.dom_body.append(template.clone());
	this.dom_parent.append(this.dom);
	this.title = title;
	this.enabled = true;
	
	// Add support for events
	this.events = $(this);
	
	// Add actions
	this.actions = {};
	if (collectopia.isDefined(actions)) {
		$.each(actions, function(name){
			pthis.addAction(name, this.title, this.callback);
		});
	}
	
	// Input functionality to panel
	this.dom.mousedown(function(event){
		pthis.bringToFront();
	});
	this.dom.draggable({
		cancel : '.container, .close, .actions',
		cursor: 'crosshair'		
	});
	
	this.dom.find('.close').click(function(){
		pthis.close();
	});
	
	// Register at panel system
	collectopia.panels.registerPanel(this);
	
	// Panel is ready, lets show it.
	this.dom.fadeIn('slow');
};

/**
 * Add an action to the panel
 * @param title The title of the action or an element to add.
 * @param callback A function to callback when finished.
 */
collectopia.Panel.prototype.addAction = function(name, title, callback) {
	var ul = this.dom.find('ul.actions'), pthis = this;
	var title_el = collectopia.isString(title)?$('<a href="#"/>').text(title):title;
	ul.append($('<li/>').attr('name', name).append(title_el.click(function(event){
		callback.call(pthis, event);
	})));
};

/**
 * Remove an action from the panel
 */
collectopia.Panel.prototype.removeAction = function(name) {
	this.dom.find('ul.actions li[name='+ name + ']').remove();
	delete this.actions['name'];
};

/**
 * Get all actions of this panel
 */
collectopia.Panel.prototype.getActions = function() {
	return this.actions;
};

/**
 * Remove all actions from this panel
 */
collectopia.Panel.prototype.clearActions = function() {
	this.actions = {};
	this.dom.find('ul.actions').html('');
};

/**
 * Request to close this panel.
 */
collectopia.Panel.prototype.close = function() {
	var pthis = this;

	// On close event
	this.events.triggerHandler('onclose', { panel: pthis});
	
	// Start close effect
	this.dom.fadeOut('300', function() {
		collectopia.panels.unregisterPanel(pthis);
		
		pthis.dom.remove();
		
		// Everything is finished
		pthis.events.triggerHandler('closed', { panel: pthis });
	});
};

/**
 * Bring to front z-index
 */
collectopia.Panel.prototype.bringToFront = function() {
	collectopia.panels.bringToFront(this.id);
};

/**
 * Get the size of the panel
 */
collectopia.Panel.prototype.size = function(){
	return { width: this.dom.outerWidth(), height: this.dom.outerHeight() };
};

/**
 * Move the panel at an offset
 */
collectopia.Panel.prototype.move = function(left, top) {
	this.dom.offset({left: left, top: top});
};

/**
 * Move the panel near something?
 */
collectopia.Panel.prototype.moveNear = function(left, top, width, height) {
	var view_size = collectopia.panels.size();
	var panel_size = this.size();
	var dest_point = { left: left + width, top: top };
	
	// If there is no space on right and the space on left is enough
	if (((left + width + panel_size.width) > view_size.width) && (left > panel_size.width))
		dest_point.left = left - panel_size.width;
	
	if ((top + panel_size.height) > view_size.height)
		dest_point.top = view_size.height - panel_size.height; 
	
	this.move(dest_point.left, dest_point.top);
};

/**
 * Disable this panel
 */
collectopia.Panel.prototype.disable = function(html) {
	if (!this.enabled)
		return;
	this.dom.addClass('disabled');
	this.dom_body.createEl('div', { class : 'disable_frame'}).html(html);	
	this.enabled = false;
	this.events.triggerHandler('disabled', { panel : this});
};

/**
 * Enable this panel
 */
collectopia.Panel.prototype.enable = function(html) {
	if (this.enabled)
		return;
	this.dom.removeClass('disabled');
	this.dom_body.find('.disable_frame').remove();
	this.enabled = true;
	this.events.triggerHandler('enabled', { panel : this});
};

/**
 * Set the title of the panel
 */
collectopia.Panel.prototype.setTitle = function(title) {
	this.dom.find('> div > .title').text(title);	
};