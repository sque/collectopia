collectopia.namespace('collectopia.ui');

/**
 * Base class for an ui component
 * This object exposes the following events
 * 	- attached When this coponent is attached to an element
 *  - detached When this coponent is detached.
 */
collectopia.ui.Component = function() {
	this.events = $(this);
};

/**
 * Get the events subsystem, where you can bind handlers.
 * @returns {jQuery} jQuery compatible event system
 */
collectopia.ui.Component.prototype.getEvents = function() {
	return this.events;
};

/**
 * Get the DOM of this component
 * @returns {jQuery} jQuery enabled DOM element ready to be appended.
 */
collectopia.ui.Component.prototype.getDom = function() {
	if (collectopia.isDefined(this.dom))
		return this.dom;
	return this.dom = this.buildDom().data('uic', this);
};

/**
 * Append/attach this component on an element
 * @param {jQuery} el Jquery enabled DOM Element that will be append to.
 */
collectopia.ui.Component.prototype.attachTo = function(el) {	
	el.append(this.getDom());
	this.events.triggerHandler('attached');
};

/**
 * Detach this component from the ui.
 */
collectopia.ui.Component.prototype.detach = function() {
	this.getDom().detach();
	this.events.triggerHandler('detached');
};

/**
 * Check if this component is attached
 */
collectopia.ui.Component.prototype.isAttached = function() {
	if (!collectopia.isDefined(this.dom))
		return false;
	return (this.dom.parent().length != 0);
};

/* --------------------------------------------------------------------*/

/**
 * Form component to visualize the {collectopia.api.Form}
 * @param options
 * Events exposed by this object are
 *  - success: When this form is submitted successfully
 *  - start: When a submision starts
 *  - finish: When a submision finishes
 *  - error: When a submision failed
 */
collectopia.ui.Form = function(form, options) {
	collectopia.ui.Component.call(this);
	
	this.api_form = form;	
	
	// Create options
	this.options = $.extend({
	}, options);
};
collectopia.ui.Form.prototype = jQuery.extend({}, collectopia.ui.Component.prototype);
collectopia.ui.Form.prototype.constructor = collectopia.ui.Form;

/**
 * Build the form UI and attach hooks
 * @returns {jQuery} DOM Element
 */
collectopia.ui.Form.prototype.buildDom = function() {
	var ui = $('<div class="ui-form"><form ><ul class="fields"></ul></form></div>');
	this.dom_form = ui.find('form');
	
	var ui_form = this;
	
	// Add fields	
	var ul_fields = ui.find('ul');
	for(var name in this.api_form.fields) {
		var f = this.api_form.fields[name], el;		
		

		switch(f.type) {
		case 'text':
		case 'hidden':
		case 'password':
			el = $('<input />').attr('name', name).attr('type', f.type).val(f.value);
			break;
		case 'textarea':
			el = $('<textarea />').attr('name', name).attr('type', f.type).val(f.value);
			break;
		case 'dropbox':
			el = $('<select />').attr('name', name).attr('type', f.type).val(f.value);
			for(var opt in f.optionlist) {
				var ol = $('<option />').attr('value', f.optionlist[opt]).appendTo(el);
				if (f.value == opt)
					ol.attr('selected', 'selected');
			}
			break;
		}
		
		// Add extra html attributes
		for(var at in f.htmlattribs)
			el.attr(at, f.htmlattribs[at]);
		
		// Wrap with li and append to ul
		var li = $('<li />').attr('name', name).addClass('type-' + f.type).appendTo(ul_fields);
		li.append($('<label />').text(f.display)).append(el);
		
		if (collectopia.isDefined(f.hint))
			li.append($('<div class="ui-form-hint" />').text(f.hint));
	}
	
	// Function to update the form
	var update_error_fields = function(error) {
		this.dom_form.find('.ui-form-error').remove();
		
		for(var name in error.fields) {
			this.dom_form.find('li[name=' + name + ']:not(.type-hidden)')
				.append($('<div class="ui-form-error" />').text(error.fields[name]))
				.find('.ui-form-hint').remove();
		}
	};
	
	// Add hooks on UI
	this.dom_form.submit(function(){
			//Extract form data in an object format.
			var form_data_array = ui_form.dom_form.serializeArray();
			var form_data = {};
			
			ui_form.getEvents().triggerHandler('start', { form: ui_form } );
			for(var i = 0; i < form_data_array.length; i++)
				form_data[form_data_array[i].name] = form_data_array[i].value;
			
			// Request api_form to submit data
			ui_form.api_form.submit(
				form_data,
				function(){
					ui_form.getEvents().triggerHandler('success');
					ui_form.getEvents().triggerHandler('finish' );
				},
				function(error) {
					ui_form.getEvents().triggerHandler('error', error );
					update_error_fields.call(ui_form, error);					
					ui_form.getEvents().triggerHandler('finish');
				}
			);		
		return false;	// Break HTML submition
	});
	return ui;
};

/**
 * Set a value to a field of the form.
 * @param name The "name" attribute of the input element in form.
 * @param value The value to set in the input element.
 */
collectopia.ui.Form.prototype.setField = function(name, value) {
	this.getDom().find('input[name=' + name + ']').val(value);
};

/**
 * Get the value of a field int the form.
 * @param name The "name" attribute of the input element in form.
 */
collectopia.ui.Form.prototype.getField = function(name) {
	return this.getDom().find('input[name=' + name + ']').val();
};

/**
 * Request to submit this form
 */
collectopia.ui.Form.prototype.submit = function(){
	this.dom_form.submit();
};

/* --------------------------------------------------------------------*/

/**
 * A complete place editor UI.
 * @param place The place we are editing. (Give null for new one)
 * Events exposed by this component
 *  - success When the place editor finished successfully.
 *  - ajax.start
 *  - ajax.end
 */
collectopia.ui.PlaceEditor = function(place, map, geocoder) {
	collectopia.ui.Component.call(this);
	pd = this;
	
	this.mode_create = collectopia.isDefined(place)?false:true;
	this.place = this.mode_create?new collectopia.api.Place({photos: [], categories : []}):place;
	this.map = map;
	this.geocoder = geocoder;
	
	// On detached remove google marker
	this.events.bind('detached', function(event) {
		event.target.marker.setMap(null);
	});
};

collectopia.ui.PlaceEditor.prototype = jQuery.extend({}, collectopia.ui.Component.prototype);
collectopia.ui.PlaceEditor.prototype.constructor = collectopia.ui.PlaceEditor;

/**
 * Build the PlaceEditor UI and attach hooks
 * @returns {jQuery} DOM Element
 */
collectopia.ui.PlaceEditor.prototype.buildDom = function() {
	var ped = this;	
	
	// Create Component
	var dom = $('<div class="component place-editor">');	

	// Ask for the needed form
	ped.events.triggerHandler('ajax.start');
	this.place[this.mode_create?'reqCreateForm':'reqEditForm'](function(form_data){
		ped.form = new collectopia.ui.PlaceEditor.Form(form_data);
		ped.form.attachTo(dom);
		
		/* Hook on form */
		ped.form.getEvents().bind('start', function(){ ped.events.triggerHandler('ajax.start'); });
		ped.form.getEvents().bind('finish', function(){ ped.events.triggerHandler('ajax.end'); });
		ped.form.getEvents().bind('success', function(){ ped.events.triggerHandler('success'); });
		
		
		// Add extra stuff on the form	
		var ul_cats = ped.form.getDom().find('ul.fields')
			.append('<li class="categories" name="categories"><ul class="categories" /></li>' +
					'<li class="address" name="address"><div>none</div></li>')
			.find('ul.categories');
			
		var update_cat_form_field = function() {
			var enabled_cats = [];
			ped.form.getDom().find('ul.categories li.enabled').each(function(){
				enabled_cats.push(this.getAttribute('tag'));
			});
			ped.form.setField('categories', enabled_cats.join(','));
		};
		/* Show all categories */
		for(var i in map.categories){
			var cat = map.categories[i];
			var li = ul_cats.createEl('li', { class : cat['tag'], tag : cat['tag']}).text(cat['title'])
				.prepend($('<span class="checkbox"/>'))
				.click(function(event){
					var li = $(this);
					li.toggleClass('enabled');
					update_cat_form_field();
				});
			if (collectopia.isDefined(ped.place.categories) && (ped.place.categories.indexOf(cat) >= 0))
				li.addClass('enabled');
		}

		
		/* Add photo management support */
		var ui_show_uploads = function() {
			dom.addClass('show-uploads').parents('.panel').animate({ width : 542 });
			/*dom.find('td.right-row').resize(function(e) {
				td = $(this);
				td.find('> div').height(td.height());
				console.log('resize', e);
			});
			dom.find('td.right-row').triggerHandler('resize');*/			
		};
		
		var create_upload_row = function(thumb_url, photo, delete_callback) {
			var a = $('<tr><td class="image"><img></td><td class="fname"><div/></td>' +
            		'<td class="delete">' +
            		'<button class="ui-state-default ui-corner-all" title="Delete">' +
            		'<span class="ui-icon ui-icon-trash">Delete</span></button>' +
            		'</td></tr>');
            a.find('.fname div').text(photo.name);
            a.find('img').attr('src', thumb_url);
            return a;
		};
		
		/* Photos upload area */
		dom.find('form').wrap('<table><tr><td class="left-row" /><td class="right-row">' +
				'<div><table class="photos-grid"/></div></td></tr></table>');	
		dom.find('table td:first-child').append('<form method="post" class="photos-upload" action="api/photo/+new">' +
				'<input name="image" type="file" multiple>' +
				'<button>Upload</button><div>Upload photos<em>maximum size 2MB</em></div></form>');

		/* add existing photos */
		if (collectopia.isObject(ped.place.photos) && (ped.place.photos.length)) {
			ui_show_uploads();
			$.each(ped.place.photos, function() {
				console.log(dom.find('.photos-grid'));
				var row = create_upload_row(
						this.getUrl(),						
						this
				).appendTo(dom.find('.photos-grid'));
			});
		}
		
		dom.find('.photos-upload').fileUploadUI( {
	        uploadTable: dom.find('.photos-grid'),
	        downloadTable: dom.find('.photos-grid'),
	        buildUploadRow: function (files, index) {
	            var a = $('<tr class="upload"><td colspan="2" class="fname"><div />' + 
	                    '<div class="file_upload_progress"><div /></div></td>' +
	                    '<td class="file_upload_cancel delete">' +
	                    '<button class="ui-state-default ui-corner-all" title="Cancel">' +
	                    '<span class="ui-icon ui-icon-cancel">Cancel</span>' +
	                    '</button></td></tr>');
	            a.find('.fname > div:first-child').text(files[index].name);
	            return a;
	        },
	        buildDownloadRow: function (fdata) {		        	
	        	if (collectopia.isDefined(fdata.error)) {
	        		var a = $('<tr class="upload">' +
	        				'<td colspan="3" class="fname"><div/>' +
	        				'<div class="ui-form-error">' + fdata['error'] + '</div></td></tr>');
	        		a.find('.fname > div:first-child').text(fdata['name']);
	        		setTimeout(function(){ a.hide('fast', function(){ a.remove(); }); }, 8000);
	        		return a;
	        	}
	        	var photo = new collectopia.api.Photo(fdata);
	        	ped.form.attachPhoto(photo);
	        	
	            var a = $('<tr><td class="image"><img></td><td class="fname"><div/></td>' +
	            		'<td class="delete">' +
	            		'<button class="ui-state-default ui-corner-all" title="Delete">' +
	            		'<span class="ui-icon ui-icon-trash">Delete</span></button>' +
	            		'</td></tr>');
	            a.find('.fname div').text(photo.name);
	            a.find('img').attr('src', photo.getThumbUrl());
	            a.find('button').data('photo', photo).click(function(event){
	            	a.hide('fast', function(){ a.remove(); }); 
	            	ped.form.detachPhoto(photo.secret);
	            	photo.reqDelete();		            	
	            });
	            return a;
	        },
	        initUpload : function (event, files, index, xhr, handler, callBack) {
	        	ui_show_uploads();
	          
	            // Chain processing
	        	handler.initUploadRow(event, files, index, xhr, handler, function () {        		
	        		callBack();
	        	});
	        }
	    }); // fileUploadUi
		
		// On attached add google marker
		ped.events.bind('attached', function(event, args){
			event.target.showMarker();
		});
		if (ped.isAttached())
			ped.showMarker();
		
		ped.events.triggerHandler('ajax.end');
	}); // reqXXXFrom
	
	return dom;
};

/**
 * Show the google marker on the map for this editor
 */
collectopia.ui.PlaceEditor.prototype.showMarker = function() {
	if (collectopia.isDefined(this.marker)) {
		// In case that was just hidden
		this.marker.setMap(this.map);
		return;
	}
	
	this.marker = this.getGoogleMarker();
	/* Listen on marker "position_changed" and update location on creation form */
	var ped = this;
	google.maps.event.addListener(this.marker, 'dragend', marker_moved_action = function() {
		ped.geocoder.geocode({'latLng': ped.marker.getPosition(), 'language' : 'en'}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				ped.form.setLocation(results[0].geometry.location);
				ped.form.setGeocode(results[0]);
			} else {
				ped.form.setLocation();
				ped.getDom().find('.address div').text('none');
			}
			
			// On update remove error hints
			ped.getDom().find('.address .ui-form-error').remove(); 				
		});
	});
	marker_moved_action();
};

/**
 * Hide the google marker of this editor from the map.
 */
collectopia.ui.PlaceEditor.prototype.hideMarker = function() {
	if (collectopia.isDefined(this.marker))
		this.marker.setMap(null);
};

/**
 * Build the google marker that we need for pointing the new place.
 * @returns {google.maps.Marker}
 */
collectopia.ui.PlaceEditor.prototype.getGoogleMarker = function() {
	var image = new google.maps.MarkerImage('static/images/create_marker.png',
			new google.maps.Size(28, 35), new google.maps.Point(0, 0),
			new google.maps.Point(14, 35));

	var shadow = new google.maps.MarkerImage(
			'static/images/create_marker_shadow.png', new google.maps.Size(50,
					35), new google.maps.Point(0, 0), new google.maps.Point(14,
					35));

	var shape = {
		coord : [ 25, 0, 26, 1, 26, 2, 27, 3, 27, 4, 27, 5, 26, 6, 24, 7, 22,
				8, 23, 9, 23, 10, 23, 11, 23, 12, 23, 13, 23, 14, 22, 15, 22,
				16, 22, 17, 21, 18, 20, 19, 19, 20, 18, 21, 17, 22, 17, 23, 16,
				24, 16, 25, 16, 26, 16, 27, 15, 28, 15, 29, 15, 30, 15, 31, 14,
				32, 14, 33, 14, 34, 13, 34, 13, 33, 13, 32, 12, 31, 12, 30, 12,
				29, 12, 28, 11, 27, 11, 26, 11, 25, 10, 24, 10, 23, 9, 22, 8,
				21, 7, 20, 6, 19, 6, 18, 5, 17, 5, 16, 5, 15, 4, 14, 4, 13, 4,
				12, 3, 11, 3, 10, 4, 9, 4, 8, 5, 7, 5, 6, 6, 5, 7, 4, 8, 3, 10,
				2, 19, 1, 21, 0, 25, 0 ],
		type : 'poly'
	};

	return marker = new google.maps.Marker({
		draggable : true,
		raiseOnDrag : true,
		icon : image,
		shadow : shadow,
		shape : shape,
		map : map.google.map,
		position : map.google.map.getCenter()
	});
};

/**
 * Request to submit place editor
 */
collectopia.ui.PlaceEditor.prototype.submit = function() {
	this.form.submit();
};

/* --------------------------------------------------------------------*/

/**
 * Specialization of ui.From for PlaceEditor
 * @param data Form data
 * @param options Extra options for the form
 */
collectopia.ui.PlaceEditor.Form = function(data, options) {
	collectopia.ui.Form.call(this, data, options);
	
	// Hook on error to be more descriptive
	this.events.bind('error', function(event, error){
		if (collectopia.isDefined(error.fields.loc_lat) || collectopia.isDefined(error.fields.loc_lng)) {
			error.fields.address = error.fields.loc_lat;
		}
	});
};
collectopia.ui.PlaceEditor.Form.prototype = jQuery.extend({}, collectopia.ui.Form.prototype);
collectopia.ui.PlaceEditor.Form.prototype.constructor = collectopia.ui.PlaceEditor.Form; 


/**
 * Return a list of all photos on the form.
 * @returns {Array} An array of photos that are attached on the form
 */
collectopia.ui.PlaceEditor.Form.prototype.getAttachedPhotos = function() {
	var photos = this.getField('photos');
	return (photos.length == 0)?[]:photos.split(',');
};

/**
 * Attach a new photo on the form
 * @param photo The {collectopia.api.Photo} objet to be attached on the form  
 */
collectopia.ui.PlaceEditor.Form.prototype.attachPhoto = function(photo){
	var photos = this.getAttachedPhotos();
	photos.push(photo.isTemporary()?'s:' + photo.secret:photo.id);
	this.setField('photos', photos.join(','));
};

/**
 * Detach a photo from the form
 * @param id The distinguish id of this photo in the form.
 */
collectopia.ui.PlaceEditor.Form.prototype.detachPhoto = function(id){
	var photos = this.getAttachedPhotos();
	var idx = photos.indexOf(id);
	if (idx >= 0)
		photos.splice(idx, 1);
	this.setField('photos', photos.join(','));
};

/**
 * Helper function to set the location elements of the form.
 */
collectopia.ui.PlaceEditor.Form.prototype.setLocation = function(loc) {
	if (collectopia.isDefined(loc)) {
		this.setField('loc_lat', loc.lat());
		this.setField('loc_lng', loc.lng());
	} else {
		this.setField('loc_lat', '');
		this.setField('loc_lng', '');
	}
};

/**
 * Helper function to set the geocode elements of the form
 * @param results Geocode Results object.
 */
collectopia.ui.PlaceEditor.Form.prototype.setGeocode = function(results) {
	// Find in geocode data an entry by type
	var get_by_type = function(type) {
		var ac = results['address_components'];
		for(var i in ac) {
			if ($.inArray(type, ac[i].types) >= 0){
				return ac[i];
			}
		};
	};
	
	// Clean up previous values
    var fields = 
	[
	 	'pos_country',
	 	'pos_city',
	 	'pos_locality',
	 	'pos_street',
	 	'pos_street_number',
	 	'pos_administrative_area_level_1',
	 	'pos_administrative_area_level_2'	 	
	];
	for(var i in fields)
		this.setField(fields[i], '');
	
	// Add new values
	var country, city, street, street, street_number, address, ad_level_1, ad_level_2;
	if (country = get_by_type('country'))
		this.setField('pos_country', country.long_name);
	if (city = get_by_type('locality'))
		this.setField('pos_city', city.long_name);
	if (street = get_by_type('route'))
		this.setField('pos_street', street.long_name);
	if (street_number = get_by_type('street_number'))
		this.setField('pos_street_number', street_number.long_name);
	if (ad_level_1 = get_by_type('administrative_area_level_1'))
		this.setField('pos_administrative_area_level_1', ad_level_1.long_name);
	if (ad_level_2 = get_by_type('administrative_area_level_2'))
		this.setField('pos_administrative_area_level_2', ad_level_2.long_name);
	
	this.setField('pos_address', results.formatted_address);
	this.getDom().find('.address > div').text(results.formatted_address);
};