//require <xatajax.beans.core.js>
(function(){
	var $ = jQuery;

	XataJax.beans.Subscribable = Subscribable;
	
	/**
	 * A mixin class that adds support for events in an object.
	 *
	 * @constructor
	 */
	function Subscribable(o){
	
		var listeners = {};
		var taggedListeners = {};
		
		XataJax.publicAPI(this, {
			unbind: unbind,
			bind: bind,
			trigger: trigger
		});
		
		
		function getTag(name){
			return name;
		}
		
		function getName(name){
			if ( name.indexOf('.') != -1 ){
				var parts = name.split('.');
				return parts[0];
			} else {
				return name;
			}
		}
		
		function getListenersForTag(tag){
			if ( typeof(taggedListeners[tag]) == 'undefined' ){
				return [];
			} else {
				return taggedListeners[tag];
			}
		}
		
		function addListenerForTag(tag, listener){
			if ( typeof(taggedListeners[tag]) == 'undefined' ){
				taggedListeners[tag] = [];
			}
			
			taggedListeners[tag].push(listener);
		}
	
	
		/**
		 * Unbinds a function from the given event.
		 * @param {String} name The name of the event to unbind from.
		 * @param {Function} func The function to bind to the event.
		 * @returns {Subscribable} Self for chaining.
		 */
		function unbind(name, func){
			var self = this;
			var tag = getTag(name);
			var name = getName(name);
			if ( typeof(func) == 'undefined' ){
				$.each(getListenersForTag(tag), function(){
					self.unbind(name, this);
				});
			}
			if ( typeof(listeners[name]) == 'undefined' ){
				listeners[name] = [];
			}
			var i = listeners[name].indexOf(func);
			if ( i != -1 ){
				listeners[name].splice(i,1);
			}
			return this;
		}
		
		/**
		 * Binds a function for a given event type.  Functions will be
		 * called in the context of the Subscribable object.
		 *
		 * @param {String} name The name of the event to bind.
		 * @param {Function} func The function to bind to the event.
		 * @returns {Subscribable} Self for chaining.
		 */
		function bind(name, func){
			var tag = getTag(name);
			var name = getName(name);
			
			addListenerForTag(tag, func);
			if ( typeof(listeners[name]) == 'undefined' ){
				listeners[name] = [];
			}
			var i = listeners[name].indexOf(func);
			if ( i == -1 ){
				listeners[name].push(func);
			}
			return this;
		}
		
		
		/**
		 * Triggers an event so that all functions listening to the event will be called.
		 *
		 * @param {String} name The name of the event that is being fired.
		 * @param {Object} event The event object.  This event can be any type of 
		 *		object that the Subscribable chooses to call.
		 * @returns {Subscribable} Self for chaining.
		 */
		function trigger(name, event){
			var self = this;
			if ( typeof(listeners[name]) == 'undefined' ){
				return this;
			}
			$.each(listeners[name], function(){
				($.proxy(this, self))(event);
			});
			
			return this;
		}
	}
})();