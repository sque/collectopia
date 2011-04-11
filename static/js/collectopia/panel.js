collectopia.namespace('collectopia');

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
 * - 'submit' Event related with submit status update.
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
	if (typeof(actions) != 'undefined') {
		$.each(actions, function(){
			pthis.addAction(this.title, this.callback);
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
 */
collectopia.Panel.prototype.addAction = function(title, callback) {
	var ul = this.dom.find('ul.actions');
	ul.append($('<li/>').append($('<a href="#"/>').text(title).click(callback)));
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
		pthis.events.triggerHandler('closed', { panel: pthis});
	});
};

/**
 * Submit the embeded form
 */
collectopia.Panel.prototype.submitForm = function(selector) {
	var form, pthis = this;
	if (selector == undefined)
		form = this.dom.find('form');
	else
		form = this.dom.find(selector);

	if (form.length == 0)
		return;	// No form in panel
	
	var form_data = form.serialize();
	pthis.events.triggerHandler('submit', { status: 'start', data : form_data });
	$.post(form.attr('action'), form_data, function(data){
		if ((data != null) && (typeof(data['error']) != 'undefined')) {
			pthis.events.triggerHandler('submit', { status: 'error', response : data});
			return;
		}
		pthis.events.triggerHandler('submit', { status: 'finish', response : data });
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