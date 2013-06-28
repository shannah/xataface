//require <xatajax.beans.core.js>
//require <jquery.packed.js>
(function(){
	var $ = jQuery;
	XataJax.beans.PropertyChangeEvent = PropertyChangeEvent;
	
	
	/**
	 * @constructor
	 * @override-params any
	 * @see {XataJax.beans.PropertyChangeListener}
	 *
	 """
	 new PropertyChangeSupport();
	 """
	*/
	function PropertyChangeEvent(o){
		/**
		"""
		PropertyChangeEvent.__properties__ = XataJax.doc.getProperties(publicAPI);
		"""
		*/
		if (typeof(o) == 'object' ){
			var constru = this.constructor;
			$.extend(this, o);
			this.constructor = constru;
			
		}
	}
	
	
	var publicAPI = {
		source: source,
		propertyName: propertyName,
		oldValue: oldValue,
		newValue: newValue,
		index: index
	};
	
	PropertyChangeEvent.prototype = publicAPI;
	PropertyChangeEvent.prototype.constructor = PropertyChangeEvent;
	
	/**
	 * @type {Object}
	 */
	var source = null;
	
	/**
	 * @type {String}
	 */
	var propertyName = null;
	
	/**
	 * @type {String}
	 */
	var oldValue = null;
	
	/**
	 * @type {String}
	 */
	var newValue = null;
	
	/**
	 * @type {int}
	 */
	var index = null;
})();