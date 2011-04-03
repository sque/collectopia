(function($){
	jQuery.fn.createEl = function(tag, attributes){
		var el;
		this.each(function(){			
			this.appendChild(el = document.createElement(tag));
			
			if (typeof(attributes) == 'object'){
				for(var name in attributes) {
					el.setAttribute(name, attributes[name]);
				}
			}
		})
		return $(el);
	};
}(jQuery))