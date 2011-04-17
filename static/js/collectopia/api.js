/*
 * Core utils and namespace for collectopia
 */
collectopia = {
	
	/**
	 * Check if this var is defined
	 * 
	 * @param t The type to be checked
	 * @return boolean
	 */
	isDefined : function(t) {
		return typeof(t) != 'undefined';
	},
	
	/**
	 * Check if this var is null
	 * 
	 * @param t The variable to check
	 * @return boolean
	 */
	isNull : function(t) {
		return (isDefined(t) && (t == null));
	},
	
	/**
	 * Check if this var is string
	 * 
	 * @param t The variable to check
	 * @return boolean
	 */
	isString : function(t) {
		return typeof(t) == 'string';
	},
	
	/**
	 * Check if this variable is object
	 * 
	 * @param t The variable to check
	 * @return boolean
	 */
	isObject : function(t) {
		return typeof(t) == 'object';
	},	
	
	/**
	 * Check if this variable is a function
	 * 
	 * @param t The variable to check
	 * @return boolean
	 */
	isFunction : function(t) {
		return jQuery.isFunction(t);
	},
	
	/**
	 * Assure that a namespace is declared
	 * 
	 * @param ns
	 *            The namespace as a string if it is only for root level or
	 *            array of all parts of namespace.
	 */
	namespace : function(ns) {
		if (typeof(ns) == 'string')
			ns = ns.split('.');
		
		var parent = window;
		for(var i in ns) {
			if (! collectopia.isDefined(parent[ns[i]]))
				parent[ns[i]] = {};
			parent = parent[ns[i]];
		}
		return parent;
	},
	/**
	 * Generate UUID v4
	 */
	uuidV4 : function () {
		  var s = [], itoh = '0123456789ABCDEF';

		  // Make array of random hex digits. The UUID only has 32 digits in
			// it, but we
		  // allocate an extra items to make room for the '-'s we'll be
			// inserting.
		  for (var i = 0; i <36; i++) s[i] = Math.floor(Math.random()*0x10);

		  // Conform to RFC-4122, section 4.4
		  s[14] = 4;  // Set 4 high bits of time_high field to version
		  s[19] = (s[19] & 0x3) | 0x8;  // Specify 2 high bits of clock sequence

		  // Convert to hex chars
		  for (var i = 0; i <36; i++) s[i] = itoh[s[i]];

		  // Insert '-'s
		  s[8] = s[13] = s[18] = s[23] = '-';

		  return s.join('');
	},
	
	/**
	 * Default uuid is v4
	 */
	uuid : this.uuidV4,
	
	/**
	 * Collectopia API
	 */
	api : {
		init : function(server_url) {
			collectopia.api._server_url = server_url;
			if (server_url[server_url.length - 1] != '/')
				collectopia.api._server_url += '/';
		},
		
		/**
		 * Get full absolute url for an api element 
		 */
		furl : function(url) {
			if (url[0] == '/')
				url = url.slice(1);
			return collectopia.api._server_url + url;
		}
	}
};

/**
 * Class for managing errors
 */
collectopia.api.Error = function(message, fields) {
	this.error = message;
	this.fields = fields;
};

/**
 * Forms are used to submit data
 */
collectopia.api.Form = function(data, object, submit_action) {
	jQuery.extend(this, data, { method : 'get' });	
	this.object = object;
	this.submit_action = submit_action;
};

/**
 * Get the binded object with the form.
 */
collectopia.api.Form.prototype.getObject = function(){
	return this.object;
};

/**
 * Trigger action to submit this form
 * @param success Function to be executed on success.
 * @param error Function to be executed on fail.
 */
collectopia.api.Form.prototype.submit = function(data, on_success, on_error) {
	
	var form = this;
	this.object.clone().update(data)[this.submit_action](
		function(object){
			form.object.update(object.data());
			if (collectopia.isFunction(on_success))
				on_success.call(form, form);
		}, 
		function(error){
			// Form_status contains error object
			if (collectopia.isFunction(on_error))
				on_error.call(form, error);
			
		});
};

/**
 * This class reflects the availabe API for places.
 */
collectopia.api.Place = function(data) {
	jQuery.extend(this, data, {});	// Save data and default values
	
	// map photo data to object
	if (collectopia.isObject(this.photos))
		this.photos = this.photos.map(
			function(data){ return new collectopia.api.Photo(data); });
};

/**
 * Create a URL encoded serialized version of this object.
 * @returns {Object} All data of place serialized in url encoded format
 */
collectopia.api.Place.prototype.serialize = function() {
	return jQuery.param(this.data());
};

/**
 * Get all data of this place.
 * @returns {Object}
 */
collectopia.api.Place.prototype.data = function() {
	data = {};
	for(var i in this) {
		if (collectopia.isFunction(this[i]))
			continue;
		data[i] = this[i];
	}	   
	return data;
};

/**
 * Return a full clone of this place.
 * @returns {collectopia.api.Place}
 */
collectopia.api.Place.prototype.clone = function() {
	return new collectopia.api.Place(this.data());
};

/**
 * Update this object with new data
 */
collectopia.api.Place.prototype.update = function(data) {
	jQuery.extend(this, data);
	return this;
};

/**
 * Internal function to process request reply that returns place
 */
collectopia.api.Place.prototype._process_reply_place = function(reply, on_success, on_error) {
	  if (collectopia.isDefined(reply.error)) {
		  if (collectopia.isFunction(on_error))
			  on_error.call(this, new collectopia.api.Error(reply.error, reply.fields));
	  } else {
		  this.update(reply);
		  if (collectopia.isFunction(on_success))
			  on_success.call(this, this);
	  }
};

/**
 * Internal function to process request fail
 */
collectopia.api.Place.prototype._process_fail = function(textStatus, on_error) {
	if (collectopia.isFunction(on_error))
		  on_error.call(this, new collectopia.api.Error(textStatus));
};

/**
 * Request to create this place on the server
 * @param on_success (place) A callback to be called if it is successfully created.
 * @param on_error A callback to be called on error.
 * @returns {collectopia.api.Place} Chainable action
 */
collectopia.api.Place.prototype.reqCreate = function(on_success, on_error) {
	if (collectopia.isDefined(this.id))
		return false;
	
	var place = this;
	jQuery.ajax({
	  type: 'POST',
	  url: collectopia.api.furl('/api/place/+new'),
	  data: this.data(),
	  success: function(reply){
		  place._process_reply_place(reply, on_success, on_error);		  
	  },
	  error: function(jqXHR, textStatus){
		  place._process_fail(textStatus, on_error);		  
	  }
	});
	return this;
};

/**
 * Request the Form from the server for the create action.
 * @param on_success (form) A callback to be called if the form was successfully retrievd.
 * @param on_error A callback to be called on error.
 * @returns {collectopia.api.Place} Chainable action
 * @note This is a cached action.
 */
collectopia.api.Place.prototype.reqCreateForm = function(on_success, on_error) {
	// Check cache
	if (collectopia.isDefined(collectopia.api.Place._cache_reqCreateForm_data)) {
		on_success.call(place, new collectopia.api.Form(collectopia.api.Place._cache_reqCreateForm_data, place));
		return;
	}
	
	// Otherwise request
	var place = this;
	jQuery.ajax({
		  type: 'GET',
		  url: collectopia.api.furl('/api/place/+new'),
		  success: function(data){
			  if (collectopia.isFunction(on_success)) {
				  // Cache response
				  collectopia.api.Place._cache_reqCreateForm_data = data;
				  on_success.call(place, new collectopia.api.Form(data, place, 'reqCreate'));
			  }
		  },
		  error: on_error
		});
	return this;
};

/**
 * Request to update this place on the server
 * @param on_success (place) A callback to be called if it is successfully updated
 * @param on_error A callback to be called on error.
 * @returns {collectopia.api.Place} Chainable action
 */
collectopia.api.Place.prototype.reqUpdate = function(on_success, on_error) {
	if (!collectopia.isDefined(this.id))
		return false;
	
	jQuery.ajax({
	  type: 'POST',
	  url: collectopia.api.furl('/api/place/@' + escape(this.id) + '/+edit'),
	  data: this.data(),
	  success: function(reply){
		  place._process_reply_place(reply, on_success, on_error);		  
	  },
	  error: function(jqXHR, textStatus){
		  place._process_fail(textStatus, on_error);		  
	  }
	});
	return this;
};

/**
 * Request to delete this place on the server
 * @param on_success (place) A callback to be called if it is successfully deleted
 * @param on_error A callback to be called on error.
 * @returns {collectopia.api.Place} Chainable action
 */
collectopia.api.Place.prototype.reqDelete = function(on_success, on_error) {
	if (!collectopia.isDefined(this.id))
		return false;
	
	jQuery.ajax({
	  type: 'POST',
	  url: collectopia.api.furl('/api/place/@' + escape(this.id) + '/+delete'),
	  data: { id : this.id },
	  success: function(data){		  
		  if (typeof on_success == 'function')
			  on_success.call(place);
	  },
	  error: on_error
	});
	return this;
};

/**
 * Request to rate this place
 * @param on_success (place) A callback to be called if it is successfully rated
 * @param on_error A callback to be called on error.
 * @returns {collectopia.api.Place} Chainable action
 */
collectopia.api.Place.prototype.reqRate = function(score, on_success, on_error) {
	if (typeof this.id != undefined)
		return false;
	
	jQuery.ajax({
	  type: 'POST',
	  url: collectopia.api.furl('/api/place/@' + escape(this.id) + '/+rate'),
	  data: { id : this.id, rate: score },
	  success: function(reply){
		  place._process_reply_place(reply, on_success, on_error);		  
	  },
	  error: function(jqXHR, textStatus){
		  place._process_fail(textStatus, on_error);		  
	  }
	});
	return this;
};

/**
 * Return an object fully describing the normal marker image.
 * @returns {collectopia.api.MarkerImage} Get the normal marker image
 */
collectopia.api.Place.prototype.getMarkerImage = function() {
	return new collectopia.api.MarkerImage(this.markerpack_hash, this.markerpack_index);		
};

/**
 * Return an object fully describing the focused marker image.
 * @returns {collectopia.api.MarkerImage} Get the focused marker image
 */
collectopia.api.Place.prototype.getFocusedMarkerImage = function() {
	return new collectopia.api.MarkerImage(this.markerpack_hash, this.markerpack_index, true);		
};

/**
 * @returns Get an object with all video service urls
 */
collectopia.api.Place.prototype.getVideoUrls = function() {
	if (typeof this.video_url != undefined) {
		var u = this.video_url.split(':'), v = {};
		if (v[0] == 'youtube') {
			v['swf'] = 'http://www.youtube-nocookie.com/v/' + video[1] + '?autoplay=1&fs=1';
			v['thumb'] = 'http://img.youtube.com/vi/' + video[1] + '/1.jpg';
			return v;
		}
	}
};

/**
 * Class to handle photos
 */
collectopia.api.Photo = function(data) {
	jQuery.extend(this, data, {
		name : 'noname',
		width : 0,
		height: 0,
		size: 0
	});
};

/**
 * Check if this photo is temporary or binded
 * @returns boolean Flag if it is temporary or binded to a place.
 */
collectopia.api.Photo.prototype.isTemporary = function() {
	return collectopia.isDefined(this.secret);
};

/**
 * Get the absolute url to access this photo
 * @returns string With the url.
 */
collectopia.api.Photo.prototype.getUrl = function() {
	return collectopia.api.furl('/data/photos/' + this.hash + '.jpg');
};

/**
 * Get the absolute url to access a thumbnail of this photo.
 * @param width The width of the thumbnail.
 * @param height The height of the thumbnail.
 * @returns string With the url.
 */
collectopia.api.Photo.prototype.getThumbUrl = function(width, height) {
	if (collectopia.isDefined(this.thumb_url))
		return this.thumb_url;
	width = collectopia.isDefined(width)?width:154;
	height = collectopia.isDefined(height)?height:115;
	return collectopia.api.furl('/data/photos/' + encodeURIComponent(this.hash) + '_thumb_'
			+ encodeURIComponent(width) + 'x' + encodeURIComponent(height) + '.jpg');
};

/**
 * Request to remove a photo from server.
 * @param on_success A callback to be called on success.
 * @param on_error A callback to be called on error.
 */
collectopia.api.Photo.prototype.reqDelete = function(on_success, on_error) {
	if (!this.isTemporary())
		return;
	
	var photo = this;
	var ajaxRequest = {
	  type: 'POST',
	  url: collectopia.api.furl('/api/photo/' + encodeURIComponent(this.secret) + '/+delete-temp' ),
	  data: { secret : this.secret },
	  success: function(data){		  
		  if (typeof on_success == 'function')
			  on_success.call(photo);
	  }
	};
	if (collectopia.isDefined(on_error))
		ajaxRequest['error'] = on_error;
	return jQuery.ajax(ajaxRequest);
};

/**
 * A marker image is a sprite from a Marker Pack
 */
collectopia.api.MarkerImage = function(pack_hash, index, focused) {
	var y_step = 56, x_step = 60;

	this.pack = pack_hash;
	this.size = (focused)?{ width: 120, height: 56}:{ width: 60, height: 28};	
	this.origin = { x : (focused) ? x_step: 0, y: index * y_step };
	this.anchor = { x : 0, y: (focused) ? 55: 27 };
};

/**
 * Create a google.maps.MarkerImage out of collectopia marker image.
 */
collectopia.api.MarkerImage.prototype.toGoogleMarkerImage = function() {
	return new google.maps.MarkerImage(
			this.getImageUrl(),
			new google.maps.Size(this.size.width, this.size.height),
			new google.maps.Point(this.origin.x, this.origin.y),
			new google.maps.Point(this.anchor.x, this.anchor.y)
	);
};

/**
 * Get the actual marker pack image url
 */
collectopia.api.MarkerImage.prototype.getImageUrl = function() {
	return collectopia.api.furl('/data/markers/' + this.pack + '.png');	
};