collectopia.namespace('collectopia');

/**
 * Specialized version of Panel for Add Place form.
 * @returns
 */
collectopia.HelpPanel = function() {
	var pthis = this;
	collectopia.Panel.call(this, 'help', 'Help');
	this.addAction('close', 'Close', function(){ this.close(); });
	this.dom_body.createEl('iframe', { src : 'help/help.php'});	

};
collectopia.HelpPanel.prototype = jQuery.extend({}, collectopia.Panel.prototype);
collectopia.HelpPanel.prototype.constructor = collectopia.HelpPanel;

