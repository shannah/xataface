//require <xatajax.core.js>
(function(){
	var $ = jQuery;
	var xataface = XataJax.load('xataface');
	xataface.ClassLoader = ClassLoader;
	/**
	 * @class
	 * @memberOf xataface
	 * @description A class for loading Javascript classes from the server.  
	 */
	function ClassLoader(){
		this.classLoaders = [];
	}
	
	
	
	
	
	(function(){
		$.extend(ClassLoader.prototype, {
			classLoaders : null,
			loadClass : loadClass,
			loadClasses : loadClasses,
			require : require,
			handlesClass : handlesClass,
			addClassLoader : addClassLoader,
			removeClassLoader : removeClassLoader
		});
		
		ClassLoader.instance = null;
		ClassLoader.getInstance = function(){
			if ( ClassLoader.instance == null ){
				ClassLoader.instance = new ClassLoader();
			}
			return ClassLoader.instance;
		}
		
		ClassLoader.require = function(classNames, callback){
			return ClassLoader.getInstance().require(classNames, callback);
		}
		
		ClassLoader.loadClass = function(className, callback){
			return ClassLoader.getInstance().loadClass(className, callback);
		}
		
		ClassLoader.loadClasses = function(classNames, callback){
			console.log(ClassLoader.getInstance());
			return ClassLoader.getInstance().loadClasses(classNames, callback);
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Checks to see if this classloader will handle loading
		 * 	a particular class.
		 * @param {String} className The name of the class to check (fully qualified).
		 * @returns {boolean} True if the className can be loaded with this classloader.
		 */
		function handlesClass(className){
			return true;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Adds a classloader as a child loader of this loader.
		 * All child classloaders are checked for loading a class before falling
		 * back to the local class loading.
		 * @param {xataface.ClassLoader} loader The ClassLoader to add.
		 * @returns {xataface.ClassLoader} Self for chaining.
		 */
		function addClassLoader(/*ClassLoader*/ loader){
			this.classLoaders.push(loader);
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Removes a child classloader from this parent.
		 *
		 * @param {xataface.ClassLoader} loader The ClassLoader to remove.
		 * @returns {xataface.ClassLoader} Self for chaining.
		 */
		function removeClassLoader(/*ClassLoader*/ loader){
			var idx = this.classLoaders.indexOf(loader);
			if ( idx > -1 ){
				this.classLoaders.splice(idx,1);
			}
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Loads a list of classnames and executes the provided 
		 * callback function after loading is complete.  The callback function
		 * 	will accept a "classes" argument that is a hashtable of class names
		 *	and their associated class.  It also accepts a "missing" argument
		 *	that is an array of class names that could not be loaded for some 
		 * reason or another.
		 *
		 * @param {Array} classNames List of class names to load.
		 * @param {Callback} callback
		 * @return {xataface.ClassLoader} Self for chaining.
		 */
		function require(classNames, callback){
			return this.loadClasses(classNames, callback);
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Loads a list of classnames and executes the provided 
		 * callback function after loading is complete.  The callback function
		 * 	will accept a "classes" argument that is a hashtable of class names
		 *	and their associated class.  It also accepts a "missing" argument
		 *	that is an array of class names that could not be loaded for some 
		 * reason or another.
		 *
		 * @param {Array} classNames List of class names to load.
		 * @param {Callback} callback
		 * @return {xataface.ClassLoader} Self for chaining.
		 */
		function loadClasses(classNames, callback){
			var allClassNames = classNames;
			var self = this;
			var classes = {};
			var missing = [];
			$.each(classNames, function(k,v){
				var cls = self.loadClass(v);
				classes[v] = cls;
				if ( cls == null ){
					missing.push(v);
				}
			});
			
			if (callback == undefined ){
				// we are working in a synchronous manner.
				// If the classes are in memory we return them.
				return classes;
			} else {
				if ( missing.length > 0 ){
					var pending = missing.slice();
					
					var classNames = [];
					$.each(missing, function(k,className){
						classNames.push(className.replace(/\./g, '/')+'.js');
					});
					var classNamesStr = classNames.join(',');
					XataJax.util.loadScript(classNamesStr, function(){
						var cb = XataJax.util.extractCallback(callback);
						var classes = self.loadClasses(allClassNames);
						console.log(classes);
						var missing = [];
						$.each(classes, function(className, Class){
							if ( Class == null ){
								missing.push(className);
							}
						});
						
						if ( missing.length == 0 ){
							cb.onSuccess.call(this, {
								classes : classes,
								missing : []
							});
						} else {
							cb.onFail.call(this, {
								classes : classes,
								missing : missing
							});
						}
						
					});
					
					
				} else {
					var cb = XataJax.util.extractCallback(callback);
					cb.onSuccess.call(this, {
						classes : classes,
						missing : []
					});
				}
				
				return this;
			}
			
			
			
		}
		
		/**
		 * @function
		 * @memberOf xataface.ClassLoader#
		 * @description Loads a class if it hasn't already been loaded yet.  This method
		 * 	can be called both synchronously or asynchronously by simply providing
		 *	(or omitting) a callback function.  If no callback function is provided
		 * 	then this will operate synchronously.  However, if the class is not already
		 *	loaded, then this will just return null and not attempt to load it (if 
		 *	in synchronous mode.
		 *
		 * <p>In asynchronous mode, this will first check if the class is already
		 *	loaded.  If it hasn't been loaded, it pass the request to the child
		 *	class loaders until it finds one that handles this class.  If none is found
		 *	it will simply try loading the class from the server.</p>
		 *
		 * <p>After the class is loaded (or fails to load), the provided callback
		 * 	will be called, and the loaded class will be provided as the "Class" 
		 *	parameter.</p>
		 *
		 * @param {String} className The name of the class to load (fully qualified).
		 * @param {Callback} callback The callback function to call on completion.  This
		 *	supports Xataface's callback object conventions (e.g. onSuccess, onFail, 
		 *	onCancel - optionally).
		 */
		function loadClass(className, callback){
			var Clazz = XataJax.load(className, false);
			var self = this;
			if ( Clazz == null && callback == undefined ){
				return null;
			} 
			if ( Clazz == null && typeof(callback) == 'function' ){
				
				var handled = false;
				$.each(this.classLoaders, function(k,loader){
					if ( handled ) return;
					if ( loader.handlesClass(className) ){
						handled = true;
						loader.loadClass(className, callback);
					}
				});
				
				if ( !handled ){
					var scriptPath = className.replace(/\./g, '/')+'.js';
					XataJax.util.loadScript(scriptPath, function(){
						Clazz = XataJax.load(className, false);
						var cb = XataJax.util.extractCallback(callback);
						if ( Clazz == null ){
							cb.onFail.call(self, {
								Class : null,
								message : 'Failed to load class '+className
							});
						} else {
							cb.onSuccess.call(self, {
								Class : Clazz
							});
						}
					});
				}
				
				return self;
			}
			
			if ( Clazz != null && typeof(callback) == 'function' ){
				var cb = XataJax.util.extractCallback(callback);
				cb.onSuccess.call(self, {
					Class : Clazz
				});
				return self;
			}
			
			if ( Clazz != null && callback == undefined ){
				return Clazz;
			}
			
			if ( callback == undefined ){
			
				return null;
			} else {
				return this;
			}
		}
		
	})();
})();