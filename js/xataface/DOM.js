//require <xataface/ClassLoader.js>
//require <xataface/model/Model.js>
//require <xatajax.util.js>
(function(){
	
	var $ = jQuery;
	var ClassLoader = xataface.ClassLoader;
	var Model = xataface.model.Model;
	xataface.DOM = DOM;
	
	XataJax.subclass(DOM, Model);
	
	DOM.STATUS_IDLE=0;
	DOM.STATUS_DECORATING=1;
	DOM.STATUS_READY=2;
	DOM.STATUS_ERROR=3;
	
	/**
	 * @class
	 * @memberOf xataface
	 */
	function DOM(/*HTMLElement*/ el){
		var obj = $(el).data('xf-dom-object');
		//console.log("Checking xf-dom-object key");
		//console.log(obj);
		if ( obj instanceof DOM ) return obj;
		//console.log(this);
		if ( !(this instanceof DOM)){
			//console.log("Not an instance of DOM");
			//console.log(this);
			return new DOM(el);
		} 
		Model.call(this, {
			el : el
		});
		//console.log("Instance of DOM");
		//console.log(this);
		$(el).data('xf-dom-object', this);
		this.el = el;
		this.controller = null;
		this.controllerClass = $(el).attr('data-xf-controller');
	
		this.status = DOM.STATUS_IDLE;
		this.controller = null;
		$(this).bind('propertyChanged', function(evt, data){
			console.log("Property change in DOM");
			console.log(data);
			console.log("Source");
			console.log(this);
		});
		
	}
	
	
	(function(){
		Model.addProperties(DOM.prototype, [
			
			/**
			 * @property {int} status The status of the DOM element.  When it is 
			 * 	first loaded it will be status DOM.STATUS_IDLE.  When decorate()
			 *	is called, it will try to load the controller class and 
			 *  assign it to the DOM element's controller property.  Then it will
			 * 	be set to DOM.STATUS_READY.  If there is a failure, it will be 
			 *	set to DOM.STATUS_ERROR.
			 * @memberOf xataface.DOM#
			 */
			'status',
		
			/**
			 * @property {Object} controller The controller object for this
			 *	DOM element.  A controller can be any Javascript class that takes
			 * 	a single HTMLElement as a parameter in its constructor.  The controller
			 *	is created lazily, so the controller class itself doesn't actually
			 *  need to worry about being lazy.  It can build itself straight away.
			 * @memberOf xataface.DOM#
			 */
			'controller'
			
		]);
	
		$.extend(DOM.prototype, {
			//status : DOM.STATUS_IDLE,
			el : null,
			//controller : null,
			controllerClass : null,
			
			ready : ready,
			decorate : decorate,
			undecorate : undecorate
		});
		
		/**
		 * @function
		 * @memberOf xataface.DOM#
		 */
		function decorate(){
			var self = this;
			if ( this.status == DOM.STATUS_ERROR ){
				this.status = DOM.STATUS_IDLE;
			}
			
			if ( this.status == DOM.STATUS_IDLE ){
				this.status = DOM.STATUS_DECORATING;
				//console.log("Decorating DOM");
				if ( this.controllerClass ){
					ClassLoader.loadClass(this.controllerClass, function(o){
						if ( o.Class != null ){
							self.controller = new o.Class(self.el);
							//console.log("Setting status ready");
							//console.log("Old status was "+self.status);
							//console.log(self.controller);
							self.status = DOM.STATUS_READY;
						} else {
							// Class could not be loaded
							this.status = DOM.STATUS_ERROR;
							$(self.el).trigger('error', {
								code : 500,
								message : o.message
							});
						}
					});
				} else {
					self.status = DOM.STATUS_READY;
				}
			}
			
			return this;
			
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.DOM#
		 */
		function undecorate(){
			this.controller = null;
			this.status = DOM.STATUS_IDLE;
			return this;	
		}
		
		/**
		 * @function
		 * @memberOf xataface.DOM#
		 */
		function ready(/*Callback*/ callback){
			var self = this;
			var cb = XataJax.util.extractCallback(callback);
			if ( this.status != DOM.STATUS_READY ){
				function onReady(evt, data){
					if ( data.propertyName == 'status' && 
						data.newValue == DOM.STATUS_READY 
					)
					{
						$(self).unbind('propertyChanged', onReady);
						cb.onSuccess.call(self);
					}
				}
				function onError(evt, data){
					//console.log('Error');
					//console.log(data);
				}
				$(self).bind('error', onError);
				$(self).bind('propertyChanged', onReady);
				self.decorate();
				
			} else {
				cb.onSuccess.call(self);
			}
		}
		
	})();
	
})();