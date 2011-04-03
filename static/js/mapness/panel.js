mapness.namespace('mapness');

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
 * - 'submit'	Called when form has been submitted succesfully
 * - 'submit_error'	Called on error submit a form.
 * - 'onclose' When close has been requested.
 * - 'closed' When the close operation and effects has finished. 
 */
mapness.Panel = function(type, title, actions, parent) {
	var pthis = this,
		template = $('#panels').find('.' + type),
		inner_div;
	

	// Create window DOM	
	this.dom_parent = (parent == undefined)?$('#content'):parent;
	this.dom = $('<div class="panel"/>').append(
			inner_div = $('<div><span class="title">' + title + '</span><span class="close">close</span>' +
				'</div>')
		)
		.append($('<ul class="actions"></ul>'))
		.addClass(type)
		.hide();
	if (template.length > 0)
		inner_div.append(template.clone());
	this.dom_parent.append(this.dom);
	
	// Add support for events
	this.events = $(this);
	
	// Add actions
	if (typeof(actions) != 'undefined') {
		$.each(actions, function(){
			pthis.addAction(this.title, this.callback);
		});
	}
	
	// Input functionality to panel
	this.dom.draggable({ handle : 'span.title', cursor: 'crosshair'});
	this.dom.find('.close').click(function(){
		pthis.close();
	});
	
	// Panel is ready, lets show it.
	this.dom.fadeIn('slow');
};
/**
 * Add an action to the panel
 */
mapness.Panel.prototype.addAction = function(title, callback) {
	var ul = this.dom.find('ul.actions');
	ul.append($('<li/>').append($('<a href="#"/>').text(title).click(callback)));
};
/**
 * Request to close this panel.
 */
mapness.Panel.prototype.close = function() {
	var pthis = this;

	// On close event
	this.events.triggerHandler('onclose', { panel: pthis});
	
	// Start close effect
	this.dom.fadeOut('300', function(){
		pthis.dom.remove();
		
		// Everything is finished
		pthis.events.triggerHandler('closed', { panel: pthis});
	});
	
};
/*
 * Submit the embeded form
 */
mapness.Panel.prototype.submitForm = function(selector) {
	var form, pthis = this;
	if (selector == undefined)
		form = this.dom.find('form');
	else
		form = this.dom.find(selector);

	if (form.length == 0)
		return;	// No form in panel
	
	var form_data = form.serialize();
	$.post(form.attr('action'), form_data, function(data){
		if ((data != null) && (typeof(data['error']) != 'undefined')) {
			pthis.events.triggerHandler('submit_error', data);
			return;
		}
		pthis.events.triggerHandler('submit', data);
	});	
};