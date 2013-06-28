//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	
	var Model = xataface.model.Model;
	var form = XataJax.load('xataface.form');
	form.FormElement = FormElement;
	XataJax.subclass(FormElement, Model);
	
	/**
	 * @class
	 * @memberOf xataface.form
	 * @extends xataface.model.Model
	 * @description A wrapper class for a form element that is responsible
	 *  for getting and setting values in the form element.
	 * This is very thin.
	 * @param {Object} o The input parameters.
	 * @param {xataface.model.Model} model The model that is being bound to 
	 * 	this form element.  The model itself won't *actually* be bound (i.e.
	 *	no automatic updates, but it will push and pull values from the correct
	 *  property of the model.
	 * 
	 */
	function FormElement(/*Object*/ o){
		var self = this;
		Model.call(this, o);
		
		if ( this.key == null && this.el != null ){
			var key = $(this.el).attr('data-key');
			if ( !key ) key = $(this.el).attr('name');
			this.key = key;
		}
		
	}
	
	(function(){
		Model.addProperties(FormElement.prototype, [
			/**
			 * @property {String} name The name of the element.
			 */
			'el',
			
			/**
			 * @property {boolean} error Whether or not there is an error on this
			 *	form element.
			 */
			'error',
			
			/**
			 * @property {String} errorMessage The error message that is present
			 *	in this element from the last time it was validated.
			 */
			'errorMessage',
			
			/**
			 * @property {xataface.model.Model} model The model to which this form
			 * 	element is bound.
			 */
			'model',
			
			/**
			 * @property {String} key The key on the model that is being bound to this
			 * element.
			 */
			'key'
			
		]);
		
		$.extend(FormElement.prototype, {
			el : null,
			error : null,
			errorMessage : null,
			model : null,
			key : null,
			setValue : setValue,
			getValue : getValue,
			push : push,
			_push : _push,
			pull : pull,
			_pull : _pull,
			validate : validate,
			formToModel : formToModel,
			modelToForm : modelToForm,
			decorate : decorate,
			undecorate : undecorate
			
		});
		
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Sets the value in the form element.
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function setValue(val){
			$(this.el).val(val);
			return this;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Gets the value in the form element.
		 * @returns {String}
		 */
		function getValue(){
			return $(this.el).val();
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Pushes the value from the form element into the model.
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function push(){
			var self = this;
			$(this).trigger('beforePush');
				
			this._push();
			this._pull(); // Re-pull the changes from the model in case it 
							// changes the input.
			$(this).trigger('afterPush');
			return this;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Pushes the value from the form element into the model.  This is
		 * meant to be overridden, but not called.  This method is actually called
		 * by the push() method, which fires events before and after.
		 *
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function _push(){
			this.model.set(this.key, this.formToModel(this.getValue()));
			return this;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Pulls the value from the model to the form element.
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function pull(){
			$(this).trigger('beforePull');
			this._pull();
			$(this).trigger('afterPull');
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Pulls the value from the model to the form element.  This is
		 * meant to be overridden, but not called.  This method is actually called
		 * by the pull() method, which fires events before and after.
		 *
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function _pull(){
			var modelVal = this.model.get(this.key);
			this.setValue(this.modelToForm(modelVal));
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Validates the form input value asynchronously and calls the 
		 * appropriate callback when it is done.
		 * @param {Callback} callback Either a function that will be called in every 
		 * 	case (pass or fail), or an object with onSuccess, onFail, and onCancel methods.
		 *
		 * @see XataJax.util.extractCallback() for more details.
		 * @returns {xataface.form.FormElement} Self for chaining.
		 */
		function validate(callback, timeout){
			var self = this;
			if ( typeof(timeout) == 'undefined' ){
				timeout = 10000;
			}
			// A semaphore to keep track if any validation is still going on.  When 
			// validators start, they call the start() method provided in the
			// event data parameter.  This increments the semaphore.  When they
			// finish, they either call pass() or fail().  Both decrement the
			// semaphore.
			var semaphore = 0;
			
			var validated = false;
			
			// Counter to store the number of failures that occur.
			var failures = 0;
			
			// A start() function that is passed to the validators, and which they should
			// call when they start validating.  This increments the semaphore so that
			// the join() function knows if there is a validator that hasn't yet completed
			// its operation.
			function startValidation(){
				semaphore++;
			}
			
			// A fail() method that is called by the validator if validation fails.
			function fail(msg){
				semaphore--;
				failures++;
				self.error = true;
				self.errorMessage = msg;
				join();
			}
			
			// A pass() method that is called by the validator if validation succeeds.
			function pass(){
				semaphore--;
				join();
			}
			
			// Attempts to finish the validation.  This is called whenever a validation
			// completes.  It checks to see if there are any other pending validations
			// and calls the onFail or onSuccess callback (depending on failure), if
			// all validations were completed.
			function join(){
				if ( validated ) return;

				if ( semaphore == 0 ){
					validated = true;
					var cb = XataJax.util.extractCallback(callback);
					if ( failures > 0 ){
						cb.onFail.call(this, {
							validated : false,
							message : self.errorMessage
						});
					} else {
						self.error = false;
						self.errorMessage = null;
						cb.onSuccess.call(this, {
							validated : true
						});
					}
				}
			}
			
			
			// The parameter that is passed to the validators.  This provides them
			// with all the tools that they need for communicating back to 
			// this object.
			var param = {
			
				fail : fail,
				pass : pass,
				start : startValidation,
				el : this
			};
			
			$(this).trigger('validators', param);
			
			// Call join just in case there were no validators.
			join();
			
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Transforms a value from its representation inside the form
		 * element (widget) to a representation that can be set in the model.
		 * 
		 *
		 * @param {mixed} val The value that is being converted from the form
		 * input to a model-friendly format.
		 *
		 * @returns {mixed} The sanitized value.
		 */
		function formToModel(val){
			var evt = {
				value : val
			};
			$(this).trigger('toModel', evt);
			return evt.value;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 * @description Transforms a value from its representation inside the model
		 * to a value that can be set in the form element.
		 * @param {mixed} val The value that is to be convered.
		 * @returns {mixed} The converted value.
		 */
		function modelToForm(val){
			var evt = {
				value : val
			};
			$(this).trigger('toForm', evt);
			return evt.value;
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 */
		function decorate(){
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.form.FormElement#
		 */
		function undecorate(){
			return this;
		}
	})();
})();