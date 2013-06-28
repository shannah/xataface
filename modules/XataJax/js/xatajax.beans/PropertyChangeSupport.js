//require <xatajax.beans.core.js>
//require <xatajax.beans/PropertyChangeEvent.js>
//require <xatajax.beans/PropertyChangeListener.js>
//require <jquery.packed.js>
(function(){
	var $ = jQuery;
	var Exception,
		PropertyChangeEvent,
		PropertyChangeListener;
	XataJax.ready(function(){
		Exception = XataJax.Exception;
		PropertyChangeEvent = XataJax.beans.PropertyChangeEvent;
		PropertyChangeListener = XataJax.beans.PropertyChangeListener;
	});
	
	XataJax.beans.PropertyChangeSupport = PropertyChangeSupport;
	
	
	/**
	 * A utility class that can be extended by any class to turn it into
	 * a bean.  It provides a mechanism for firing property change events
	 * when properties change.
	 *
	 * @override-params any
	 * @constructor
	 *
	 """
	 new PropertyChangeSupport();
	 """
	 */
	function PropertyChangeSupport(o){
		/**
		 """
		 PropertyChangeSupport.__methods__ = publicAPI;
		 """
		*/
	
		XataJax.extend(this, new Object());
		
		var publicAPI = {
			addPropertyChangeListener: addPropertyChangeListener,
			removePropertyChangeListener: removePropertyChangeListener,
			getPropertyChangeListeners: getPropertyChangeListeners,
			firePropertyChange: firePropertyChange
		};
		
		XataJax.publicAPI(this, publicAPI);
		
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
		
		
		var members = {
			listeners: [],
			keyListeners: {}
		};
		
		/**
		 * @variant 2 Registers a PropertyChangeListener to listen to changes to any
		 *  property.
		 * @param {PropertyChangeListener} p1 The listener to be registered to listen to
		 *	all property changes.
		 * @variant 1  Registers a PropertyChangeListener to listen to changes to a single property.
		 * @param 1 {String} p1 The name of the property for the listener to track.
		 * @param 2 {PropertyChangeListener} p2 The listener to be retistered to listen to changes
		 *	on the given event.
		 * 
		 *
		 */
		function addPropertyChangeListener(p1, p2){
			var l = null;
			var prop = null;
			if ( typeof(p1) == 'string' && typeof(p2) == 'object' ){
				prop = p1;
				l = p2;
			} else if ( typeof(p1) == 'object' ){
				l = p1;
			} else {
			
				throw new Exception("Illegal arguments for addPropertyChangeListener");
			}
			if ( prop == null ){
				var idx = members.listeners.indexOf(l);
				if ( idx == -1 ){
					members.listeners.push(l);
				}
			} else {
				if ( typeof(members.keyListeners[prop]) == 'undefined' ){
					members.keyListeners[prop] = [];
				}
				members.keyListeners[prop].push(l);
			}
		}
		
		
		/**
		 * Unsubscribes a PropertyChangeListener from receiving property changes.
		 *
		 * @variant 1 Unsubscribes listener from updates for specific property.
		 * @param 1 {String} p1 The property name that we are unsubscribing from.
		 * @param 2 {PropertyChangeListener} p2 The listener that we are removing.
		 *
		 * @variant 2 Unsubscribes listener from updates for all properties.
		 * @param 1 {PropertyChangeListener} p1 The listener that we are removing
		 * 	from receiving notifications for all changes.
		 */
		function removePropertyChangeListener(p1, p2){
			var l = null;
			var prop = null;
			if ( typeof(p1) == 'string' && typeof(p2) == 'object' ){
				prop = p1;
				l = p2;
			} else if ( typeof(p1) == 'object' ){
				l = p1;
			} else {
			
				throw new Exception("Illegal arguments for removePropertyChangeListener");
			}
			
			if ( prop == null ){
				var idx = members.listeners.indexOf(l);
				if ( idx != -1 ){
					members.listeners.splice(idx, 1);
				}
				for ( var key in members.keyListeners ){
					idx = members.keyListeners[key].indexOf(l);
					if ( idx != -1 ){
						members.keyListeners[key].splice(idx, 1);
					}
				}
			} else {
				if ( typeof(members.keyListeners[prop]) != 'undefined' ){
					idx = members.keyListeners[prop].indexOf(l);
					if ( idx != -1 ){
						members.keyListeners[prop].splice(idx, 1);
					}
				}
			}
		}
		
		/**
		 * @variant 1 Gets array of listeners registered to receive updates
		 *		for all properties.  It doesn't return listeners registered to receive
		 * 		updates for specific properties.
		 * @returns {array PropertyChangeListener}
		 *
		 * @variant 2 Gets array of listeners registered to receive updates for a specific 
		 *	property.
		 * @param 1 {String} prop The name of the property that is being listened to.
		 * @returns {array PropertyChangeListener}
		 */
		function getPropertyChangeListeners(prop){
			if ( typeof(prop) == 'undefined' ){
				return $.merge([], members.listeners);
			} else {
				if ( typeof(members.keyListeners[prop]) != 'undefined' ){
					return $.merge([], members.keyListeners[prop]);
				} else {
					return [];
				}
			}
		}
		
		
		/**
		 * Fires a {PropertyChangeEvent} to all listeners registered to 
		 * receive an event.
		 *
		 * @param 1 {String} propertyName The name of the property that was changed.
		 * @param 2 {mixed} oldValue The old value of the property.
		 * @param 3 {mixed} newValue The new value of the property.
		 * @param 4 optional {int} index For indexed properties, the index that was changed.
		 */
		function firePropertyChange(propertyName, oldValue, newValue, index){
			var event = new PropertyChangeEvent({
				source: this,
				propertyName: propertyName,
				oldValue: oldValue,
				newValue: newValue,
				index: index
			});
			
			if ( typeof(members.keyListeners[propertyName]) != 'undefined' ){
				$.each(members.keyListeners[propertyName], function(){
					if ( typeof(this.propertyChange) == 'function' ){
						this.propertyChange(event);
					}
				});
			}
			
			$.each(members.listeners, function(){
				if ( typeof(this.propertyChange) == 'function' ){
					this.propertyChange(event);
				}
			});
		}
		
		
	}
	
	
})();