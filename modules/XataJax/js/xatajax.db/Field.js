//require <xatajax.db.core.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
(function(){
	var $ = jQuery;
	var PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
	var Subscribable = XataJax.beans.Subscribable;
	
	
	/**
	 * Model representing a single field of a layout.
	 *
	 * @event {ValidationEvent} validate Fired when an attempt is made to change the value of this 
	 *	field in the datasource.
	 *
	 * @constructor
	 */
	function Field(o){
	
	
		/**
		 * The path to this field.  When bound to a datasource, this 
		 * dictates where the data for the field comes from.
		 *
		 * @type {String}
		 */
		var path;
		
		var includeInTableMode;
		var includeInFindMode;
		var includeInEditMode;
		var includeInViewMode;
		
		
		
		
		
		
	
		XataJax.extend(this, new PropertyChangeSupport(o));
		Xatajax.extend(this, new Subscribable(o));'
		XataJax.publicAPI(this, {
			getPath: getPath,
			setPath: setPath
		
		});
	
		/**
		 * Gets the path to the data contained in this field.  This is used when the layout
		 * is bound to a datasource.
		 * @returns {String}
		 */
		function getPath(){
			return path;
		}
		
		
		/**
		 * Sets the path to the data contained in the field.  This is used when the layout
		 * is bound to a datasource to decide the value that appears in the field.
		 *
		 * @param {String} p The path to the field's value relative to the datasource.
		 * @returns {Field} Self for chaining.
		 */
		function setPath(p){
			if ( p != path ){
				var old = path;
				path = p;
				this.firePropertyChange('path', old, p);
			}
			return this;
		}
		
		
		
	}

})();