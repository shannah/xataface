//require <xataface/model/Model.js>
//require <xatajax.util.js>
//require <xataface/form/FormElement.js>
(function(){
	var $ = jQuery;
	var Model = xataface.model.Model;
	
	Form.STATUS_IDLE = 0;
	Form.STATUS_BUILDING = 1;
	Form.STATUS_READY = 2;
	Form.STATUS_VALIDATING = 3;
	Form.STATUS_SAVING = 4;
	
	var form = XataJax.load('xataface.form');
	form.Form = Form;
	var FormElement = xataface.form.FormElement;
	
	
	XataJax.subclass(Form, Model);
	
	
	/**
	 * @class
	 * @memberOf xataface.form
	 * @extends xataface.model.Model
	 *
	 * @description A class to wrap an HTML form and manage the workflow of the form
	 * including binding it to a Model object.
	 *
	 * <p>A form goes through a life-cycle and will be in one of 5 states at any 
	 *	given time:</p>
	 * <ol>
	 *	<li>Form.STATUS_IDLE : The form has not been decorated yet and isn't ready
	 *		to use.
	 *	<li>
	 *	<li>Form.STATUS_BUILDING : The form is in the process of being built.  It
	 *		isn't ready to use yet.
	 *	</li>
	 *	<li>Form.STATUS_READY : The form has been built and is ready to be used.
	 *	</li>
	 *	<li>Form.STATUS_VALIDATING : The form is being validated and can't be 
	 *		used until validation is complete.  If attempts are made to 
	 *		validate the form while it is already validating, then this validation
	 *		will be queued and called when the current validation is complete.
	 *	</li>
	 *	<li>Form.STATUS_SAVING : The form is currently being saved.</li>
	 *	</ol>
	 */
	function Form(/*Object*/ o){
		var self = this;
		
		/**
		 * @property {Object} elements Set of form elements in this form.
		 */
		this.elements = {};
		
		/**
		 * @property {Array} pendingElements List of HTMLElement objects that
		 *	are currently being built.
		 */
		this.pendingElements = [];
		
		// THis needs to be defined here so that it is 
		this.onSubmitHandler = function(){
			$(self).trigger('beforeSubmit');
			try {
				self.validate({
					onSuccess : function(res){
						self.push();
						$(self).trigger('afterSubmit');
					}
				});
			} catch (e){
				console.log(e);
				console.trace(e);
			}
			
			return false;
		};
		
		// A change handler to deal with changes of the element
		// If the element is changed, we need to set ready to false
		// and unbind all sub elements from the model.
		function elChangeHandler(evt, data){
			if ( data.propertyName == 'el' ){
				if ( data.oldValue != null ){
					self.undecorate(data.oldValue);
				}
				self.formStatus = Form.STATUS_IDLE;
				
			}		
		}
		
		function modelChangeHandler(evt, data){
			if ( data.propertyName == 'model' ){
				if ( self.elements ){
					$.each(self.elements, function(k,v){
						v.model = data.newValue;
					});
				}
			}
		}	
		$(this).bind('propertyChanged', elChangeHandler);
		$(this).bind('propertyChanged', modelChangeHandler);
		Model.call(this, o);
	}
	
	
	(function(){
		Model.addProperties(Form.prototype, [
			
			/**
			 * @property {int} formStatus A flag to set the status of the form.  This
			 * should be one of Form.STATUS_CANCELLED, Form.STATUS_VALIDATION_FAILED, and 
			 * Form.STATUS_SUBMITTED.
			 */
			'formStatus',
			
			/**
			 * @property {HTMLElement} el The form element for this form.
			 */
			'el',
			
			/**
			 * @property {xataface.model.Model} model The model that this form
			 * is bound to.
			 */
			'model',
			
			/**
			 * @property {boolean} error Flag to indicate if an error has occurred
			 * in validation.
			 */
			'error',
			
			/**
			 * @property {String} errorMessage The error message to display if an 
			 * error has occurred in validation
			 */
			'errorMessage'
			
			
		]);
		
		$.extend(Form.prototype, {
			model : null,
			el : null,
			formStatus : Form.STATUS_IDLE,
			tryFireReady : tryFireReady,
			createElement : createElement,
			decorate : decorate,
			undecorate : undecorate,
			pull : pull,
			push : push,
			validate : validate
		});
		
		
		/**
		 * @memberOf xataface.form.Form#
		 * @function
		 * @description Decorates the form, wrapping all of its sub-elements
		 * in FormElement objects.
		 *
		 * @param {HTMLElement} el The <form> tag to wrap.
		 * @param {Function} elementLoader A class-loader function that can be 
		 *  used to load the HTMLelement classes.
		 * @returns {xataface.form.Form} Self for chaining.
		 */
		function decorate(/*HTMLElement*/ el, /*Function*/elementLoader){
			if ( typeof(el) == 'undefined' ){
				el = this.el;
			}
			var self = this;
			if ( this.formStatus == Form.STATUS_IDLE ){
			
				// Set the building flag so that we don't build the form twice
				this.formStatus = Form.STATUS_BUILDING;
				
				// Create a handler to listen for the form being ready at which
				// point we'll unset the building flag.
				function endBuild(evt, data){
					if ( data.propertyName == 'formStatus' ){
						
						// Makesure we detach this listener so that it doesn't 
						// fire again.
						$(self).unbind('propertyChanged', endBuild);
						self.formStatus = Form.STATUS_READY;
					}
				}
				
				// Now bind out handler to be called with the form is ready
				$(self).bind('propertyChanged', endBuild);
				
				var self = this;
				$(':input', el).each(function(){
					self.pendingElements.push(this);
				});
				$(':input', el).each(function(){
				
					
					self.createElement(this, function(){}, elementLoader);
				});
				
				// Add a validation handler on form submit.  Since validate() 
				// is asynchronous, we will use a callback to validate and
				// then try to submit again after the validation succeeds.
				$(el).bind('submit', this.onSubmitHandler);
				
				this.tryFireReady();
			}
			return this;
			
		}
		
		/**
		 * @memberOf xataface.form.Form#
		 * @function
		 * @description Undecorates the form, wrapping all of its sub-elements
		 * in FormElement objects.
		 *
		 * @param {HTMLElement} el The <form> tag to wrap.
		 * @param {Function} elementLoader A class-loader function that can be 
		 *  used to load the HTMLelement classes.
		 * @returns {xataface.form.Form} Self for chaining.
		 */
		function undecorate(/*HTMLElement*/ el){
			if ( typeof(el) == 'undefined' ){
				el = this.el;
			}
			var self = this;
			$(el).unbind('submit', this.onSubmitHandler);
			$.each(self.elements, function(name, el){
				if ( self.model ){
					el.unbind(self.model);
				}
				if ( typeof(el.undecorate) == 'function' ){
					el.undecorate();
				}
				delete self.elements[name];
			});
			return this;
		}
		
		function createElement(/*HTMLElement*/ el, 
			/*Function*/ callback, 
			/*Function*/ loaderFunc
		)
		{
			var self = this;
			
			
			var cb = XataJax.util.extractCallback(callback);
			var widgetType = $(el).attr('data-xf-widget-type');
			
			var name = $(el).attr('name');
			if ( !name ){
				name = $(el).attr('data-key');
			}
			if ( !widgetType ){
				widgetType = 'xataface.form.FormElement';
			}
			if ( !name || !widgetType ){
				
				cb.onCancel.call(this, null);
				
				var idx = this.pendingElements.indexOf(el);
				if ( idx != -1 ){
					this.pendingElements.splice(idx,1);
					
				}
				
				this.tryFireReady();
				
				return this;
			}
			
			// Now that we know the type of widget, we can build it.
			var Widget = XataJax.load(widgetType, false);
			var fo = new Widget();
			if ( Widget == null || !(fo instanceof FormElement) ){
				// We could not find the widget type to build it
				// What should we do?
				if ( typeof(loaderFunc) == 'function' ){
					loaderFunc(widgetType, function(){
						self.createElement(el, callback);
					});
				} else {
					$(this).trigger('error', {
						'message' : 'Failed to create form element of type '+widgetType
					});
					cb.onFail.call(this);
					var idx = this.pendingElements.indexOf(el);
					if ( idx != -1 ){
						this.pendingElements.splice(idx,1);
						
					}
					
				}
			} else {
				var widget = new Widget({
					el : el,
					model : self.model
				});
				this.elements[widget.key] = widget;
				cb.onSuccess.call(this, widget);
				var idx = this.pendingElements.indexOf(el);
				if ( idx != -1 ){
					this.pendingElements.splice(idx,1);
					
				}
				this.tryFireReady();
			} 
			
			return this;
		}
		
		
		/** 
		 * @function
		 * @memberOf xataface.form.Form#
		 * @description Changes the form status to ready after it is decorated.  If
		 *	there are still pending elements to build, it will do nothing.
		 * 
		 * @returns {xataface.form.Form} Self for chaining.
		 */
		function tryFireReady(){
			if ( this.formStatus == Form.STATUS_BUILDING && this.pendingElements.length == 0 ){
				this.formStatus = Form.STATUS_READY;
			}
			return this;
		}
		
		
		/** 
		 * @function
		 * @memberOf xataface.form.Form#
		 * @description Pushes data from the form fields into the model.  If this is
		 *	called when the form is not in ready state, then it will be queued until 
		 * the form is in ready state and will be called at that time.
		 * 
		 * @returns {xataface.form.Form} Self for chaining.
		 */
		function push(){
			var self = this;
			if ( self.model ){
				function doLater(evt, data){
					if ( data.propertyName == 'formStatus' && 
						data.newValue == Form.STATUS_READY )
					{
						$(self).unbind('propertyChanged', doLater);
						self.push();
					}
				}
				
				if ( this.formStatus != Form.STATUS_READY ){
					$(self).bind('propertyChanged', doLater);
					return this;
				}
				
				
				
				self.model.startUpdate();
				$.each(this.elements, function(name, el){
					el.push();
				});
				self.model.endUpdate();
			}
			
			return this;
		}
		
		
		/** 
		 * @function
		 * @memberOf xataface.form.Form#
		 * @description Pulls data from the model into the form fields.  If this is
		 *	called when the form is not in ready state, then it will be queued until 
		 * the form is in ready state and will be called at that time.
		 * 
		 * @returns {xataface.form.Form} Self for chaining.
		 */
		function pull(){
			var self = this;
			function doLater(evt, data){
				if ( data.propertyName == 'formStatus' && 
					data.newValue == Form.STATUS_READY )
				{
					$(self).unbind('propertyChanged', doLater);
					self.pull();
				}
			}
			
			if ( this.formStatus != Form.STATUS_READY ){
				$(self).bind('propertyChanged', doLater);
				return this;
			}
			
			$.each(this.elements, function(name, el){
				el.pull();
			});
			
			return this;
		}
		
		
		
		
		/**
		 * @memberOf xataface.form.Form#
		 * @function
		 * @description Asynchronously validates the form.  As the validation is in 
		 * progress the formStatus is changed to Form.STATUS_VALIDATING.  When 
		 * validation is complete, the form status will be changed to Form.STATUS_READY.
		 *
		 * <p>Validation can only occur if the formStatus is Form.STATUS_READY.  If it is
		 *	not in this state, then a it will attach a listener to apply the validation
		 *  when the status is returned to ready state.</p>
		 * 
		 * @param {Callback} callback The callback methods that will be called on 
		 *  success/fail/cancel.
		 * @see XataJax.util.extractCallback()
		 *
		 */
		function validate(callback){
			var performLater;
			var self = this;
			var cb = XataJax.util.extractCallback(callback);
			var pending = [];
			var failures = [];
			var successes = [];
			var cancellations = [];
			
			if ( this.formStatus != Form.STATUS_READY ){
				// We can only start validation when the form is in ready state
				
				performLater = function(evt, data){
					if ( data.propertyName == 'formStatus' && 
						data.newValue == Form.STATUS_READY 
					)
					{
						$(self).unbind('propertyChanged', performLater);
						self.validate(callback);
					}	
				};
				
				$(self).bind('propertyChanged', performLater);
				
				return this;
			}
			
			this.formStatus = Form.STATUS_VALIDATING;
			
			// Let's create a one-off event handler to listen for when the formStatus changes
			// back to ready from validating.
			function validatingChanged(evt, data){
				if ( data.propertyName == 'formStatus' && data.newValue == Form.STATUS_READY ){
					// Unbind out listener since we only needed it this once.
					$(self).unbind('propertyChanged', validatingChanged);
					var param = {
						cancellations : cancellations,
						successes : successes,
						failures : failures
					};
					if ( cancellations.length > 0 ){
						cb.onCancel.call(self, param);
					} else if ( failures.length > 0 ){
						self.error = true;
						self.errorMessage = 'Some errors occurred validating some fields';
						cb.onFail.call(self, param);
					} else {
						// No need to set error or error Message since this 
						// would have already been set in the form validation
						// step
						cb.onSuccess.call(self, param);
					}
				}
			}
			
			// Bind the listener so that we know when the formStatus has been changed
			// to ready (which means that validation is complete
			$(self).bind('propertyChanged', validatingChanged);
			
			
			FormElement.prototype.validate.call(this, {
				onSuccess : function(){
					self.error = false;
					self.errorMessage = null;
					validateElements();
				},
				onFail : function(data){
					self.error = true;
					self.errorMessage = data.message;
					failures.push('*');	// Need to add at least one to failures
										// or the status change handler won't
										// know that WE FAILED
					self.formStatus = Form.STATUS_READY;
				},
				onCancel : function(data){
					cancellations.push('*');
					self.formStatus = Form.STATUS_READY;
				}
			
			});
			
			function validateElements(){
				// Go through each of the elements on this form, and try to validate it
				$.each(self.elements, function(name, el){
					// Add this element to the list of pending inputs to be validated.å
					pending.push(name);
					el.validate({
						onSuccess : function(){
							// The element was successfully 
							// validated so we remove it from the pending list
							// add add it to the successes list.
							var idx = pending.indexOf(name);
							if ( idx > -1 ){
								pending.splice(idx,1);
							}
							successes.push(name);
							if ( pending.length == 0 ){
								// If there are none still pending, 
								// we'll return the status of the form 
								// to READY.  THis will, in turn, fire our
								// validatingChanged() handler that we defined
								// above.
								self.formStatus = Form.STATUS_READY;
							}
						},
						onFail : function(){
						
							// The element failed to validate.
							var idx = pending.indexOf(name);
							if ( idx > -1 ){
								pending.splice(idx,1);
							}
							failures.push(name);
							if ( pending.length == 0 ){
								self.formStatus = Form.STATUS_READY;
							}
						},
						onCancel : function(){
						
							// Validation was cancelled for some reason.
							var idx = pending.indexOf(name);
							if ( idx > -1 ){
								pending.splice(idx,1);
							}
							cancellations.push(name);
							if ( pending.length == 0 ){
								self.formStatus = Form.STATUS_READY;
							}
						},
					});
				});
			}
			
			
		}
		
		
		
		
		
	})();
})();