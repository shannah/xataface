//require <xatajax.beans.core.js>
(function(){
	XataJax.beans.PropertyChangeListener = PropertyChangeListener;
	
	/**
	 * @override-params any
	 * @constructor
	 *
	 """
	 PropertyChangeListener.__methods__ = publicAPI;
	 """
	*/
	function PropertyChangeListener(o){
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
	}
	
	var publicAPI = {
		propertyChange: function(event){}
	};
	
	PropertyChangeListener.prototype = publicAPI;
	PropertyChangeListener.constructor = PropertyChangeListener;
	
	
	/**
	 * Handles event when a property is changed on a bean.
	 *
	 * @param {XataJax.beans.PropertyChangeEvent} event
	 */
	function propertyChange(event){}
})();