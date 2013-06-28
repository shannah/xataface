//require <xatajax.core.js>
(function(){

	var $ = jQuery;
	var Exception = XataJax.Exception;

	XataJax.doc = {};
	XataJax.doc.PackageInfo = PackageInfo;
	XataJax.doc.ClassInfo = ClassInfo;
	XataJax.doc.MethodInfo = MethodInfo;
	XataJax.doc.ParameterInfo = ParameterInfo;
	XataJax.doc.ReturnInfo = ReturnInfo;
	XataJax.doc.PropertyInfo = PropertyInfo;
	XataJax.doc.ExceptionInfo = ExceptionInfo;
	XataJax.doc.TypeInfo = TypeInfo;
	XataJax.doc.EventInfo = EventInfo;
	
	XataJax.doc.getProperties = getProperties;
	XataJax.errorcodes.OBJECT_NOT_VALID_CLASS = XataJax.nextErrorCode();
	XataJax.errorcodes.OBJECT_NOT_VALID_PACKAGE = XataJax.nextErrorCode();
	
	
	
	function getProperties(o){
		var doc = getProperties.caller.__doc__;
		var out = {};
		if ( typeof(doc) == 'object' ){
			if ( !doc.__properties__ ){
				return {};
			}
			for ( var i in o ){
				out[i] = doc.__properties__[i];
			}
		}
		return out;
		
	}
	
	function isPackage(obj){
		return ((typeof(obj) == 'object') && (typeof(obj.__doc__) == 'object') && (obj.__doc__.isPackage));
	
	}
	
	function getPackageByName(name){
		if ( typeof(XataJax.__packages__[name]) == 'object' ){
			return new PackageInfo(XataJax.__packages__[name]);
		}
		return null;
	}
	
	
	/**
	 * Returns all loaded packages.
	 * @returns {dict PackageInfo} Map of packages currently loaded in memory.
	 *
	 */
	function getLoadedPackages(){
		var out = {};
		if ( typeof(XataJax.__packages__) == 'object' ){
			$.each(XataJax.__packages__, function(key,value){
				try {
					out[key] = new PackageInfo(value);
				} catch (ex){
				
				}
			});
			
		}
		return out;
	}
	
	PackageInfo.getPackageByName = getPackageByName;
	PackageInfo.isPackage = isPackage;
	PackageInfo.getLoadedPackages = getLoadedPackages;
	
	function buildPackageIndex(){
		if ( typeof(XataJax.__packages__) == 'object' ){
			for ( var i in XataJax.__packages__){
				try {
					var info = new PackageInfo(XataJax.__packages__[i]);
					info.updateClasses();
				} catch (ex){}
			}
		}
	}
	PackageInfo.buildPackageIndex = buildPackageIndex;
	
	
	function PackageInfo(obj){
		if ( !isPackage(obj) ){
			throw new Exception({
				message: 'PackageInfo object cannot be created for input parameter because it is not an object.',
				code: XataJax.errorcodes.OBJECT_NOT_VALID_PACKAGE
			});
		}
		
		XataJax.publicAPI(this, {
			getSubPackages: getSubPackages,
			getClasses: getClasses,
			getFunctions: getFunctions,
			getName: getName,
			getDescription: getDescription,
			updateClasses: updateClasses,
			getParentPackage: getParentPackage,
			getClassInfos: getClassInfos,
			getFunctionInfos: getFunctionInfos,
			getFile: getFile
		});
		
		function getFile(){
			return '';
		}
		
		function updateClasses(){
			var self = this;
			$.each(this.getClasses(), function(){
				var c = this;
				try {
					var info = new ClassInfo(c);
					if ( typeof(c.__doc__) == 'undefined' ){
						c.__doc__ = {};
					}
					c.__doc__.package_name = self.getName();
				} catch (ex){}
			});
			
		}
		
		function getParentPackage(){
			var parts = this.getName().split('.');
			parts.pop();
			var name = parts.join('.');
			return PackageInfo.getPackageByName(name);
		}
		
		function getSubPackages(){
			var out = {};
			for ( var i in obj){
				if ( isPackage(obj[i]) ){
					out[i] = obj[i];
				}
			}
			return out;
		}
		
		
		
		function getClasses(){
			var out = {};
			for ( var i in obj ){
				if ( isClass(obj[i]) ){
					out[i] = obj[i];
				}
			}
			return out;
		}
		
		function getClassInfos(){
			var out = {};
			var classes = this.getClasses();
			for ( var i in classes ){
				try {
					out[i] = new ClassInfo(classes[i]);
				} catch (ex){}
			}
			return out;
		
		}
		
		function getFunctions(){
			var out = {};
			for ( var i in obj ){
				if ( typeof(obj[i]) == 'function' && !isClass(obj[i]) ){
					out[i] = obj[i];
				}
			}
			return out;
		}
		
		function getFunctionInfos(){
			var out = {};
			var functions = this.getFunctions();
			for ( var i in functions ){
				try {
					out[i] = new MethodInfo(null, functions[i]);
				} catch (ex){}
			}
			return out;
		}
		
		function getName(){
			return obj.__doc__.name;
		}
		
		function getDescription(){
			return obj.__doc__.description;
		}
		
		
		
	}
	
	function isClass(obj){
		if ( typeof(obj) != 'function' ){
			//alert('not func');
			return false;
		}
		if ( typeof(obj.__doc__) != 'object' ){
			//alert('Doc not obj');
			return false;
		}
		//alert('Constructor '+obj.__doc__);
		return obj.__doc__.isConstructor;
	}
	
	
	function ClassInfo(obj){
		
		if ( !isClass(obj) ){
			throw new Exception({
				message: 'ClassInfo object cannot be created for non class ['+obj+']',
				code: XataJax.errorcodes.OBJECT_NOT_VALID_CLASS
			});
		}
	
		XataJax.publicAPI(this, {
		
			getSuperClasses: getSuperClasses,
			getSubClasses: getSubClasses,
			getMethods: getMethods,
			getProperties: getProperties,
			getDescription: getDescription,
			getConstructor: getConstructor,
			getName: getName,
			getPackage: getPackage,
			getFile: getFile,
			getConstructorInfo: getConstructorInfo,
			getFullName: getFullName,
			getEvents: getEvents
		});
	
		var clazz = obj;
		
		
		function getPackage(){
			if ( typeof(obj.__doc__) == 'undefined' ) return null;
			if ( typeof(obj.__doc__.package_name) != 'undefined'){
				return getPackageByName(obj.__doc__.package_name);
			}
			return null;
		
		}
		
		function getFullName(){
			var pkg = this.getPackage();
			var name = this.getName();
			if ( pkg ) name = pkg.getName()+'.'+name;
			return name;
		}
		
		
		function getSuperClasses(){
		
			if ( typeof(clazz.__super__) == 'undefined' ){
				return [];
			} else {
				var out = [];
				$.each(clazz.__super__, function(){
					try {
						out.push(new ClassInfo(this));
					} catch (ex){
						//alert('HERE1: '+ex);
					}
				});
				return out;
			}
		}
		
		function getSubClasses(){
			if (typeof(clazz.__sub__) == 'undefined' ){
				return [];
			} else {
				var out = [];
				$.each(clazz.__sub__, function(){
					try {
						out.push(new ClassInfo(this));
					} catch (ex){
						//alert('HERE2: '+ex);
					}
				});
				return out;
			}
		}
		
		function getEvents(includeInherited){
			if ( typeof(includeInherited) == 'undefined' ){
				includeInherited = false;
			}
			
			var out = {};
			
			if ( includeInherited ){
				
				$.each(this.getSuperClasses(), function(){
					$.extend(out, this.getEvents(true));
				});
			}
			
			if ( typeof(clazz.__events__) == 'undefined' ){
				//
			} else {
				//var out = {};
				//alert(JSON.stringify(clazz.__events__));
				for ( var i in clazz.__events__ ){
					out[i] = new PropertyInfo(this, clazz.__events__[i]);
					
				}
				
			}
			
			
			
			return out;
			
		}
		
		function getMethods(includeInherited){
			if ( typeof(includeInherited) == 'undefined' ){
				includeInherited = false;
			}
			
			var out = {};
			
			if ( includeInherited ){
				
				$.each(this.getSuperClasses(), function(){
					$.extend(out, this.getMethods(true));
				});
			}
			
			if ( typeof(clazz.__methods__) == 'undefined' ){
				//
			} else {
				//var out = {};
				for ( var i in clazz.__methods__ ){
					out[i] = new MethodInfo(this, clazz.__methods__[i]);
					
				}
				
			}
			
			
			
			return out;
		}
		
		function getProperties(){
			if ( typeof(clazz.__properties__) == 'undefined' ){
				return {};
			} else {
				var out = {};
				for ( var i in clazz.__properties__){ 
					out[i] = new PropertyInfo(clazz, clazz.__properties__[i]);
				}
				return out;
			}
		
		}
		
		function getConstructor(){
			return obj;
		}
		
		function getConstructorInfo(){
			return new MethodInfo(this, this.getConstructor());
		}
		
		function getDescription(){
			if ( obj.__doc__ ){
				return obj.__doc__.description;
			} else {
				return '';
			}
		}
		
		
		function getName(){
			if ( obj.__doc__ ){
				return obj.__doc__.name;
			} else {
				return '';
			}
		}
		
		function getFile(){
			if ( obj.__doc__ ){
				return obj.__doc__.file;
			}
			return '';
		}
		
		
	
	}
	
	
	function MethodInfo(classInfo, m, _docs, isVariant){
		if ( typeof(isVariant) == 'undefined' ) isVariant = false;
		XataJax.publicAPI(this, {
			getClassInfo: getClassInfo,
			getParameters: getParameters,
			getVariants: getVariants,
			getReturns: getReturns,
			getThrows: getThrows,
			getEvents: getEvents,
			getName: getName,
			getDescription: getDescription,
			getMethod: getMethod,
			toString: toString
			
		});
		var method = m;
		var docs = $.extend({},m.__doc__);
		if ( typeof(_docs) != 'undefined' ){
			//alert('EXTENDING: '+JSON.stringify(_docs));
			$.extend(docs, _docs);
			//docs.description = m.__doc__.description+"\n"+docs.description;
			//alert('EXTENDED: '+JSON.stringify(docs));
		}
		
		function toString(){
			var params = [];
			$.each(this.getParameters(), function(){
				var typename = 'mixed';
				if ( this.getType() ){
					typename = this.getType().getName();
				}
				params.push(typename+' '+this.getName());
			});
			
			var returns = 'void';
			if ( this.getReturns() ){
				returns = this.getReturns()[0].getType();
				if ( typeof(returns) == 'object' ){
					returns = returns.getName();
				} else {
					returns = 'void';
				}
				
			}
			
			return this.getName()+'('+params.join(', ')+') : '+returns;
		}
		
		function getMethod(){
			return m;
		}
		
		function getDescription(){
			return docs.description;
		}
		
		function getName(){
			
			return docs.name;
		}
		
		
		function getClassInfo(){
			return classInfo;
		}
		
		function getParameters(){
			var params = [];
			if ( isVariant){
				//alert(JSON.stringify(docs));
			}
			if ( typeof(docs.__parameters__) != 'undefined' ){
				
				params = docs.__parameters__;
			} else if ( typeof(method.__parameters__ ) != 'undefined' ){
				params = method.__parameters__;
			}
			
			
			var out = [];
			var self = this;
			$.each(params, function(){
				out.push(new ParameterInfo(self, this));
			});
			return out;
			
		}
		
		function getVariants(){
			if ( typeof(method.__variants__) == 'undefined' ){
				return [];
			} else {
				var out = [];
				$.each(method.__variants__, function(){
					//alert(method.__doc__.name+':'+this.__parameters__[0].typename);
					//alert('VARIANT: '+JSON.stringify(this));
					out.push(new MethodInfo(classInfo, method, this, true));
				});
				return out;
			
			}
		}
		
		function getReturns(){
			var returns = [];
			if ( typeof(docs.__returns__) != 'undefined' ){
				returns = docs.__returns__;
			} else if ( typeof(method.__returns__) != 'undefined'){
				returns = method.__returns__;
			}
			var out = [];
			var self = this;
			$.each(returns, function(){
				out.push(new ReturnInfo(self, this));
			});
			//alert(out);
			return out;
			
		}
		
		
		
		function getThrows(){
			if ( typeof(method.__throws__) == 'undefined' ){
				return [];
			} else {
				var out = [];
				$.each(method.__throws__, function(){
					out.push(new ExceptionInfo(this));
				});
				return out;
			}
		}
		
		function getEvents(){
			if ( typeof(method.__events__) == 'undefined' ){
				return [];
			} else {
				var out = [];
				
				$.each(method.__events__, function(){
					out.push(new EventInfo(this));
				});
			}
		}
		
		
	
	}
	
	function ParameterInfo(methodInfo, docs){
	
		XataJax.publicAPI(this, {
			getType: getType,
			getIndex: getIndex,
			getDescription: getDescription,
			isOptional: isOptional,
			getMethodInfo: getMethodInfo,
			getName: getName,
			getOptions: getOptions
			
		});
		
		function getType(){
			if ( typeof(docs.type) == 'undefined' ){
				return new TypeInfo(null);
			} else {
				return new TypeInfo(docs.type);
			}
		}
		
		function getIndex(){
			return docs.index;
		}
		
		function getDescription(){
			return docs.description;
		}
		
		function isOptional(){
			if ( docs.optional ){
				return true;
			} else {
				return false;
			}
		}
		
		function getMethodInfo(){
			return methodInfo;
		}
		
		function getName(){
			return docs.name;
		}
		
		function getOptions(){
			if ( typeof(docs.options) != 'undefined' ){
				var out = [];
				$.each(docs.options, function(){
					out.push(new ParameterInfo(methodInfo, this));
				});
				return out;
			}
			return [];
		}
		
		
	}
	
	function PropertyInfo(classInfo, docs){
		
		XataJax.publicAPI(this, {
			getType: getType,
			getName: getName,
			getDescription: getDescription,
			getClassInfo: getClassInfo
		});
		
		function getType(){
			if ( typeof(docs.type) == 'undefined' ){
				return null;
			} else {
				return new TypeInfo(docs.type);
			}
		}
		
		
		function getName(){
			return docs.name;
		}
		
		function getDescription(){
			if ( typeof(docs.description) == 'undefined' ){
				docs.description = '';
			}
			//alert(JSON.stringify(docs));
			return docs.description;
		}
		
		function getClassInfo(){
			return classInfo;
		}
	}
	
	/**
	 * @constructor
	 */
	function ReturnInfo(methodInfo, docs){
	
		XataJax.publicAPI(this, {
			getType: getType,
			getName: getName,
			getDescription: getDescription,
			getMethodInfo: getMethodInfo
		});
		
		function getType(){
			return new TypeInfo(docs.type);
		}
		
		function getName(){
			return docs.name;
		}
		
		function getDescription(){
			return docs.description;
		}
		
		function getMethodInfo(){
			return methodInfo;
		}
	}
	
	
	
	function ExceptionInfo(methodInfo, docs){
	
		XataJax.publicAPI(this, {
			getCode: getCode,
			getType: getType,
			getReason: getReason,
			getMethodInfo: getMethodInfo
		});
		function getCode(){
			return docs.code;
		}
		
		function getType(){
			return new TypeInfo(docs.type);
		}
		
		function getReason(){
			return docs.reason;
		}
		
		function getMethodInfo(){
			return methodInfo;
		}
	}
	
	
	
	function TypeInfo(o){
	
		XataJax.publicAPI(this, {
			getName: getName,
			getConstructor: getConstructor,
			getClassInfo: getClassInfo,
			isArray: isArray,
			isDictionary: isDictionary
		});
	
		var _constructor = null;
		var _name = null;
		var arr = false;
		if ( typeof(o._constructor) == 'function' ){
			_constructor = o._constructor;
			_name = /(\w+)\(/.exec(_constructor.toString())[1];
		}
		if ( typeof(o.name) == 'string' ){
			_name = o.name;
		}
		if ( o.isArray){
			arr = true;
		}
		
		
		function getName(){
			return _name;
		}
		
		function getConstructor(){
			return _constructor;
		}
		
		function getClassInfo(){
			if ( _constructor){
				try {
					return new ClassInfo(_constructor);
				} catch (ex){}
			}
			return null;
		}
		
		
		function isArray(){
			return arr;
		}
		
		function isDictionary(){
			return o.isDictionary ? true:false;
		}
		
	}
	
	function EventInfo(o){
	
		XataJax.publicAPI(this, {
			getEventMethodInfo: getEventMethodInfo,
			getEventClassInfo: getEventClassInfo,
			getPropertyName: getPropertyName,
			getDescription: getDescription
		});
		
		function getEventMethodInfo(){
			var listenerClass = new ClassInfo(o.listenerClass);
			
			return new MethodInfo(listenerClass, new MethodInfo(listenerClass, o.listenerMethod));
		}
		
		function getEventClassInfo(){
			return new ClassInfo(o.eventClass);
			
		}
		
		function getPropertyName(){
			return o.propertyName;
		}
		
		function getDescription(){
			return o.description;
		}
	}
	
	
	
	
	//--------------------------------------------------------------------------
	// START XataJax.extend ADDON
	// We override XataJax.extend to start recording metadata information.
	//
	var defaultExtend = XataJax.extend;
	function extend(target, source){
		defaultExtend(target, source);
		// Let's add metadata to the super and sub classes 
		// so that they know about each other.  This step is
		// more for documentation than anything.
		if ( typeof(target.constructor) == 'function') {
			if ( typeof(target.constructor.__super__) == 'undefined' ){
				target.constructor.__super__ = [];
				
			}
			var superIndex = target.constructor.__super__.indexOf(source.constructor);
			if ( superIndex == -1 ){
				target.constructor.__super__.push(source.constructor);
				if ( typeof(source.constructor.__sub__) == 'undefined' ){
					source.constructor.__sub__ = [];
				}
				var subIndex = source.constructor.__sub__.indexOf(target.constructor);
				if ( subIndex == -1 ){
					source.constructor.__sub__.push(target.constructor);
				}
			}
			
		}
	}
	
	XataJax.extend = extend;
	
	// END XataJax.extend ADDON
	//----------------------------------------------------------------------------
	
	
	// START XataJax.publicAPI ADDON
	// We override the XataJax.publicAPI method to store metadata for documentation
	// purposes.
	
	var defaultPublicAPI = XataJax.publicAPI;
	/**
	 * An extension of the default XataJax.publicAPI() method that 
	 * builds the documentation for the public API.
	 */
	function publicAPI(obj, properties){
		defaultPublicAPI(obj, properties);
		var c = obj.constructor;
		if ( typeof(c.__doc__) == 'undefined' ){
			c.__doc__ = {};
		}
		var doc = c.__doc__;
		if ( typeof(doc._public) == 'undefined' ){
			doc._public = {};
		}
		for ( var i in properties ){
			if ( typeof(properties[i]) == 'function' ){
				if ( typeof(properties[i].__doc__) == 'undefined' ){
					var fname = properties[i].toString();
					fname = fname.substr('function '.length);
					fname = fname.substr(0, fname.indexOf('('));
					properties[i].__doc__ = {
						name: fname,
						description: ''
					
					};
				
				}
				doc._public[i] = properties[i].__doc__;
				if ( typeof(c.__methods__) == 'undefined' ){
					c.__methods__ = {};
				}
				c.__methods__[i] = properties[i];
			} else if ( typeof(doc._private) == 'object' && typeof(doc._private[i]) != 'undefined' ){
				doc._public[i] = doc._private[i];
				if ( typeof(c.__properties__) == 'undefined' ){
					c.__properties__ = {};
				}
				c.__properties__[i] = doc._public[i];
			}
		}
		
		
	}
	XataJax.publicAPI = publicAPI;
	
	// END XataJax.publicAPI ADDON
	//-----------------------------------------------------------------------------
	
	
})();