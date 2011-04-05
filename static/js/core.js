/*
 * Core utils and namespace for collectopia
 */
collectopia = {
	
	/**
	 * Check if this var is defined
	 * @param t The type to be checked
	 * @return boolean
	 */
	is_defined : function(t) {
		return typeof(t) != 'undefined';
	},
	
	/**
	 * Check if this var is null
	 * @param t The variable to check
	 * @return boolean
	 */
	is_null : function(t) {
		return (is_defined(t) && (t == null));
	},
	
	/**
	 * Check if this var is string
	 * @param t The variable to check
	 * @return boolean
	 */
	is_string : function(t) {
		return typeof(t) == 'string';
	},
	
	/**
	 * Check if this variable is object
	 * @param t The variable to check
	 * @return boolean
	 */
	is_object : function(t) {
		return typeof(t) == 'object';
	},
	
	/**
	 * Assure that a namespace is declared
	 * @param ns The namespace as a string if it is only for root level
	 * 	or array of all parts of namespace.
	 */
	namespace : function(ns) {
		if (typeof(ns) == 'string')
			ns = ns.split('.');
		
		var parent = window;
		for(var i in ns) {
			if (! collectopia.is_defined(parent[ns[i]]))
				parent[ns[i]] = {};
			parent = parent[ns[i]];
		}
		return parent;
	},
	/**
	 * Generate UUID v4
	 */
	uuid_v4 : function () {
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
	uuid : this.uuid_v4
};