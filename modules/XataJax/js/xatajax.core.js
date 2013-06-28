//require <jquery.packed.js>

/**
 * @class 
 * @name XataJax
 * @description A class that includes utility methods that are needed for 
 * loading scripts and manipulating the environment.
 */
XataJax = {};

(function(){
	XataJax.Exception = Exception;
	XataJax.proxy = proxy;
	XataJax.extend = extend;
	XataJax.instanceOf = instanceOf;
	XataJax.publicAPI = publicAPI;
	XataJax.findConstructor = findConstructor;
	XataJax.ready = ready;
	XataJax.main = main;
	XataJax.load = load;
	XataJax.subclass = subclass;
	XataJax.namespace = load;
	
	var $ = jQuery;
	
	
	function subclass(constructor, superConstructor){
		function temp(){}
		temp.prototype = superConstructor.prototype;
		var prototypeObject = new temp();
		prototypeObject.constructor = constructor;
		constructor.prototype = prototypeObject;
	}
		
	
	function Exception(o){
		/**
		"""
		Exception.__properties__ = XataJax.doc.getProperties(publicProperties);
		"""
		*/
		
		/**
		 * @type {String}
		 */
		var message = '';
		
		/**
		 * @type {int}
		 */
		var code = 0;
		
		var publicProperties = {
			message: message,
			code: code,
			toString: function(){
				return this.getMessage();
			}
		};
		
		$.extend(this, publicProperties);
		
		if ( typeof(o) == 'string' ){
			this.message = o;
		} else if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
	}
	
	var Exception_publicAPI = {
		getMessage: Exception_getMessage,
		getCode: Exception_getCode
	};
	
	Exception.prototype = Exception_publicAPI;
	Exception.prototype.constructor = Exception;
	
	/**
	 * Gets the exception's message.
	 * @returns {String} The message
	 */
	function Exception_getMessage(){ return this.message;}
	
	/**
	 * Gets the exception's code.
	 * @returns {int} The code
	 */
	function Exception_getCode(){ return this.code;}
	
	/**
	 * Keeps track of error codes.
	 */
	XataJax.errorcodes = {};
	
	/**
	 * Pointer to next error code.
	 */
	XataJax.nextErrorCode = nextErrorCode;
	
	var nextCode = 1;
	function nextErrorCode(){
		return nextCode++;
	}
	
	/**
	 * Proxies all of the methods of a class to that "this"
	 * refers to obj.
	 *
	 * @param 1 {Object} The class that we want to proxy.
	 * @param 2 {Object} The object that we want as "this" context.
	 * @returns {Object} Copy of the class that uses obj as this context.
	 */
	function proxy(cls, obj){	
		var out = {};
		for ( var i in obj){
			if ( typeof(obj[i]) == 'function' ){
				out[i] = $.proxy(obj[i], cls);
			} else {
				out[i] = obj[i];
			}
		}
		return out;
		
	}
	
	/**
	 * Checks of obj is an instance of cls
	 * @param 1 {Object} The object that we are checking.
	 * @param 2 {Object} The class that we are checking the object against.
	 */
	function instanceOf(obj, cls){
		if ( obj == null || typeof(obj) != 'object' ) return false;
		if ( cls == obj.constructor ){
			return true;
		}
		if ( typeof(obj._super) != 'undefined' ){
			for ( var i=0; i<obj._super.length; i++){
				var curr = obj._super[i];
				if ( typeof(curr.instanceOf) == 'function' && curr.instanceOf(cls) ){
					return true;
				} else if ( curr.constructor == cls ){
					return true;
				}
			}
		}
		return false;
	}
	
	
	/**
	 * Causes the target object to extend from the source object.  This is similar to 
	 * jQuery.extend but goes further by proxying all of the methods so that the 
	 * context of each method of the super classes correctly point to the target class.
	 * This builds a _super array of parent objects so that we can keep track of which
	 * objects inherit from which parent objects so we can build a class heirarchy.
	 * @deprecated
	 *
	 * @param 1 {Object} target The child object.
	 * @param 2 {Object} the parent object.
	 */
	function extend(target, source){
		var _super = proxy(target, source);
		_super.constructor = source.constructor;
		var oldConstructor = target.constructor;
		$.extend(target, _super);
		target.constructor = oldConstructor;
		if ( typeof(target._super) == 'undefined' ){
			target._super = [];
		}
		
		
		
		
		target._super.push(_super);
		target.getSuper = function(cls){
			for ( var i=0; i<target._super.length; i++ ){
				//alert('Checking if  is '+target._super[i].constructor.prototype);
				//alert(target._super[i].constructor );
				if ( (typeof(cls) == 'undefined') || (target._super[i].constructor == cls) ){
					return target._super[i];
				}
			}
			return null;
		};
		
		target.instanceOf = function(obj){
			return instanceOf(this, obj);
		};
		
		if ( typeof(target.init) == 'function' ){
			target.init();
		}
		
	}
	
	/**
	 * Defines the public API for a class.
	 * @param 1 {Object} The object instance to which we are adding
	 *		the properties.
	 * @param 2 {Object} The properties for the object.  Can be methods or functions.
	 *
	 * @deprecated
	 */
	function publicAPI(obj, properties){
		$.extend(obj, properties);
	}
	
	
	
	/**
	 * Finds a constructor based on an absolute dot notation path.
	 * e.g. XataJax.ui.tk.Component
	 * @deprecated
	 * @param {String} path
	 */
	function findConstructor(path){
		var parts = path.split('.');
		var pkg = window;
		while ( parts.length > 0 ){
			
			pkg = pkg[parts.shift()];
			if ( !pkg ){
				return null;
			}
		}
		return pkg;
	}
	
	
	var readyFuncs = [];
	var mainLoaded = false;
	function ready(func){
		if ( func == undefined ) return main(function(){});
		if ( mainLoaded ){
			func();
		} else {
			readyFuncs.push(func);
		}
	}
	
	function main(func){
		mainLoaded=true;
		$.each(readyFuncs, function(){
			this();
		});
		readyFuncs = [];
		func();
		
	}
	
	/**
	 * @function
	 * @name load
	 * @memberOf XataJax
	 * @description Loads a namespace, object, or class using its fully-qualified
	 * name.  If the namespace hasn't been created yet, this will create it.  If it
	 * has already been created, it just returns the existing namespace.
	 *
	 * @param {String} The name of a namespace.
	 * @returns {Object} The namespace.
	 *
	 * @example
	 * // require &lt;xataface/IO.js&gt;
	 * // Load the IO class so we can use it
	 * var IO = XataJax.load('xataface.IO');
	 *
	 */
	function load(/*String*/ ns, createIfUndefined){
		if ( typeof(createIfUndefined) == 'undefined' ){
			createIfUndefined = true;
		}
		var parts = ns.split('.');
		var context = window;
		while ( parts.length > 0 ){
			var part = parts.shift();
			if ( typeof(context[part]) == 'undefined' ){
				if ( createIfUndefined == false ){
					return null;
				}
				context[part] = {};
			}
			context = context[part];
		}
		return context;
	}
	
	
	
	
})();