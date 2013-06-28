//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	model.Model = Model;
	Model.wrap = wrap;
	Model.addProperty = addProperty;
	Model.addProperties = addProperties;
	
	
	
	
	
	
	/**
	 * @function 
	 * @memberOf xataface.model.Model
	 * @name wrap
	 * @description Wraps an object in a Model object.   If the provided object
	 * is a Model already, it just returns that model.
	 *
	 * @param {mixed} o The object to be wrapped.
	 * @returns {Model} The model object that wrapped the input object.
	 */
	function wrap(o){
		if ( o instanceof Model ) return o;
		return new Model({
			data : o
		});
	}
	
	/**
	 * @function
	 * @memberOf xataface.model.Model
	 * @name addProperty
	 * @description Adds a property to a class.
	 * @param {Object} o The object that the property is being added to.
	 * @param {String} name The name of the property.
	 * @param {mixed} defaultValue The default value of the property.
	 * @returns {void}
	 */
	function addProperty(o, name){
		Object.defineProperty(o, name, {
			enumerable : true,
			configurable : true,
			get : function(){
				return this.get(name);
			},
			set : function(value){
				return this.set(name, value);
			}
		
		});
	}
	
	/**
	 * @function
	 * @memberOf xataface.model.Model
	 * @name addProperties
	 * @description Adds a bunch of properties to an object.
	 *
	 * @param {Object} o The object to which properties are being added.
	 * @param {Object} properties  An object whose keys are the properties to be added
	 * 	and whose values are the corresponding default values.
	 * @returns {void}
	 */
	function addProperties(o, properties){
		$.each(properties, function(k, name){
			Model.addProperty(o, name);
		});
	}
	
	/**
	 * @class
	 * @name Model
	 * @memberOf xataface.model
	 * @description A model object that can sends changed events to notify listeners
	 *  when data in the model has changed.
	 *
	 */
	function Model(/*Object*/ o){
		if ( typeof(o) == 'undefined' ){
			o = {};
		}
		if ( typeof(o.data) != 'object' ){
			o.data = {};
		}
		this.data = o.data;
		delete o.data;
		$.extend(this, o);
		
		
		
		$(this).bind('changed', function(evt, data){
			if ( typeof(data) != 'undefined' ){
				if ( data.ignoreDirtyBit ){
					// If this change was a result of setting
					// the dirty bit, then we'll ignore this
					// event.
					return;
				}
			}
			this.dirty = true;
		});
	}
	
	(function(){
		$.extend(Model.prototype, {
			_inTransaction: false,
			_changedInUpdate : false,
			get: get,
			set: set,
			startUpdate: startUpdate,
			endUpdate: endUpdate,
			
			dirty : false,
			setDirty : setDirty
		});
		
		/**
		 * @function 
		 * @name setDirty
		 * @memberOf xataface.model.Model#
		 * @description Sets the dirty flag to indicate that changes have been made
		 * 	to the model's data since it was last "saved".
		 *
		 * <h3>Events</h3>
		 * <p>This will fire the "changed" event.</p>
		 *
		 * @param {boolean} dirty Whether it is dirty or not.
		 * @returns {xataface.model.Model} Self for chaining.
		 */
		function setDirty(dirty){
			if ( this.dirty != dirty ){
				this.dirty = dirty;
				$(this).trigger('changed', {
					ignoreDirtyBit : true
				});
			}
			return this;
		}
		
		
		
		
		
		
		/**
		 * @function
		 * @name get
		 * @memberOf xataface.model.Model#
		 * @description Gets a data value by name. (key value coding).
		 * @param {String} key The key of the value to return.  If this is omitted, then
		 * 	all values will be returned in an object.
		 *
		 * @returns {mixed} Either the value corresponding value for the provided key or
		 *	an Object of the values if the key is omitted.
		 */
		function get(key){
			if ( typeof(this.data) != 'object' ) return null;
			var self = this;
			

			if ( typeof(key) == 'undefined' ){
				var out = {};
				$.each(this.data, function(k,v){
					out[k] = v;
				});
				return out;
			} else if ( typeof(key) == 'object' && !key.substring ){
				// Key is an object and not a string
				$.each(key, function(k,v){
					key[k] = self.get(k);
				});
				
			} else {
				if ( this.data == null ) return null;
				
				// Handle kvc dot notation.
				if ( key.indexOf('.') != -1 ){
					var keyparts = key.split(/\./);
					var k,o=this.data;
					while ( keyparts.length > 0 ){
						k = keyparts.shift();
						if ( typeof(o) == 'object' ){
							o = o[k];
						} else {
							return null;
						}
						
					}
					
					return o;
				} else {
					return this.data[key];
				}
			}
		}
		
		/**
		 * @function
		 * @name set
		 * @memberOf xataface.model.Model#
		 * @description Sets a key-value pair, or sets multiple key-value pairs depending
		 * 	on the types of the parameters. 
		 *	<p>If <em>key</em> is a String, then this sets the value of that key.  If it
		 *	is an Object, then all of the key-value pairs in the object will be set.</p>
		 *  <h3>Events</h3>
		 *  <p>This fires a "changed" event if the value is different than the previous
		 *	 value for the provided key.  Only one "changed" event will be fired even
		 *   if a set of key-value pairs is provided here.</p>
		 * @see startUpdate() For starting a transaction so that you can make multiple 
		 *	set() calls without firing a "changed" event until the end the subsequent 
		 *	call to endUpdate().
		 * @param {String} key The key to set.  If this is an Object, then it will set
		 *	all key-value pairs in the object.
		 * @param {mixed} val The value to set for the key.
		 * @returns {xataface.model.Model} Self for chaining.
		 */
		function set(key, val){
			var self = this;
			if ( typeof(key) == 'object' && !key.substring ){
				// The key is not a string.
				var changed = false;
				$.each(key, function(k,v){
					if ( self.data[k] != v ){
						changed = true;
						var old = self.data[k];
						self.data[k] = v;
						$(self).trigger('propertyChanged', {
							oldValue : old,
							newValue : v,
							propertyName : k,
							undo : function(){
								self.set(k, old);
							}
						});
					}
				});
				if ( changed ){
					if ( !this._inTransaction ){
						$(this).trigger('changed');
					} else {
						this._changedInUpdate = true;
					}
				}
			} else {
				// The key is a string
				if ( this.data == null ){
					this.data = {};
				}
				if ( val != this.data[key] ){
					var old = self.data[key];
					this.data[key] = val;
					$(self).trigger('propertyChanged', {
						oldValue : old,
						newValue : val,
						propertyName : key,
						undo : function(){
							self.set(key, old);
						}
					});
					if ( !this._inTransaction ){
						
						$(this).trigger('changed');
					} else {
						this._changedInUpdate = true;
					}
				}
			}
			return this;
		}
		
		
		/**
		 * @name startUpdate
		 * @function
		 * @memberOf xataface.model.Model#
		 * @description Starts a transaction so that you can set multiple values
		 *  in this model without a changed event being fired until the next call
		 * 	to endUpdate();
		 * @see endUpdate() To end a transaction
		 *
		 * @returns {xataface.model.Model} Self for chaining.
		 */
		function startUpdate(){
			if ( this._inTransaction ){
				if ( this._changedInUpdate ){
					$(this).trigger('changed');
				} 
			} else {
				this._inTransaction = true;
			}
			this._changedInUpdate = false;
			return this;
		}
		
		
		/**
		 * @name endUpdate
		 * @function
		 * @memberOf xataface.model.Model#
		 * @description Ends a transaction and fires a "changed" event if any changes
		 * have been made since the last call to startUpdate().
		 * @see startUpdate()
		 * @returns {xataface.model.Model} Self for chaining.
		 */
		function endUpdate(){
			this._inTransaction = false;
			if ( this._changedInUpdate ){
				this._changedInUpdate = false;
				$(this).trigger('changed');
			}
			return this;
		}
		
		
		
	})();
})();