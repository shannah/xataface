//require <xatajax.core.js>
//require <jquery.packed.js>
(function(){
	var $ = jQuery;
	
	/**
	 * @class 
	 * @name util
	 * @memberOf XataJax
	 * @description A class that includes utility methods that are needed for 
	 * loading scripts and manipulating the environment.
	 * 
	 */
	XataJax.util = {};
	XataJax.util.getSiteURL = getSiteURL;
	XataJax.util.getSiteHref = getSiteHref;
	XataJax.util.getRequestParams = getRequestParams;
	XataJax.util.url = url;
	XataJax.util.loadScript = loadScript;
	XataJax.util.extractCallback = extractCallback;
	
	
	/**
	 * @function
   	 * @memberOf XataJax.util
   	 * 
	 * @description Returns the URL to the site (not including the actual file).  This is a wrapper
	 * for the DATAFACE_SITE_URL variable that should be set in the template using
	 * PHP/Smarty.
	 *
	 * @returns {String} The base URL to the site.
	 */
	function getSiteURL(){
		return DATAFACE_SITE_URL;
	}
	
	
	/**
	 * @function
	 * @memberOf XataJax.util
	 * 
	 *  @description Returns the full path to the script (including index.php).  This 
	 * is a wrapper for the DATAFACE_SITE_HREF variable that should be 
	 * set in the template via PHP/Smarty.
	 * @returns {String}
	 */
	function getSiteHref(){
		return DATAFACE_SITE_HREF;
	}
	
	/**
	 * @function
	 * @memberOf
	 * 
	 * @description Returns the URL to the xataface directory.  This essentially returns
	 * the value of the DATAFACE_URL variable which should be set in PHP
	 * in the template.
	 *
	 * @returns {String} The URL to the Xataface directory.
	 */ 
	function getXatafaceURL(){
		return DATAFACE_URL;
	}
	
	
	/**
	 * @function
	 * @memberOf XataJax.util
	 * @description Gets the query parameters for the current request.
	 * @returns {Object} The key-value pairs of GET variables.
	 *
	 */
	function getRequestParams(url){
		
		var search = window.location.search;
		if ( typeof(url) != 'undefined' ){
			var pos = url.indexOf('?');
			if ( pos >= 0 ){
				search = url.substr(pos);
			} else {
				search = '';
			}
		}
		
		if ( search.indexOf('?') == 0 ){
			search = search.substr(1);
		}
		var params = search.split('&');

		var out = {};

		$.each(params, function(){
			var parts = this.split('=');
			out[decodeURIComponent(parts[0]).replace(/\+/g,' ')] = decodeURIComponent(parts[1]).replace(/\+/g,' ');
		});
		
		return out;
		
	
	}
	
	/**
	 * @function
	 * @memberOf XataJax.util
	 * @description Converts the specified query parameters into a URL with proper 
	 * url encoding.
	 * @param {Object} params Key-value pairs of GET parameters to include.
	 * @returns {String} A URL
	 */
	function url(params){
		var out = [];
		$.each(params, function(key,val){
			out.push(encodeURIComponent(key)+'='+encodeURIComponent(val));
		});
		return getSiteHref()+'?'+out.join('&')+window.location.hash;
	}
	
	/**
	 * @function
	 * @memberOf XataJax.util
	 * @description Dynamically loads a script by the script's path.
	 *
	 * Note that only scripts located in the Xataface Javascript include paths
	 * can be loaded with this method.  
	 
	 * @see <a href="http://xataface.com/dox/core/latest/class_dataface___javascript_tool.html">Dataface_JavascriptTool</a> for information
	 * about javascript include paths and how to set them up.
	 * @see <a href="http://xataface.com/dox/core/latest/classdataface__actions__load__script.html">The Xataface load_script action</a> for information on how to manipulate
	 * the include path on a per request basis. In particular you may find it useful to implement a handler for the beforeLoadScript event (@see The registerEventListener() method in <a href="http://xataface.com/dox/core/latest/class_dataface___application.html">Dataface_Application</a>
	 * @param {String} path The path to the script to load.  This should be the 
	 *  same format as the require directive takes.
	 * @param {Function} callback A callback function to be called after the script
	 * 	has been loaded. 
	 * @example
	 * //require &lt;xatajax.util.js&gt;
	 * var loadScript = XataJax.load('XataJax.util.loadScript');
	 * loadScript('xataface/IO.js', function(){
	 *     // Now IO.js should be loaded.
	 *     var IO = XataJax.load('xataface.IO');
	 *     IO.load('people?person_id=1', function(res){
	 *         alert('First Name: '+res.first_name);
	 *     });
	 * });
	 */
	function loadScript(path, callback){
		$.getScript(DATAFACE_SITE_HREF+'?-action=load_script&--script='+encodeURIComponent(path),
			callback
		);
		
	}
	
	
	/** 
	 * @function
	 * @name extractCallback
	 * @description Utility function to convert an callback into full callback object.
	 * This is used by most methods in the Document class to parse the callbacks they
	 * receive.  Callback objects include 3 methods:
	 *	onSuccess()
	 *	onCancel()
	 *  onFail()
	 *
	 * If a callback is passed just a function, then the resulting callback function
	 * will use this single function as the onSuccess(), onCancel(), and onFail()
	 * handlers.
	 * If, instead, an object with specific handlers is passed to this function
	 * then the resulting callback object will have empty functions set for
	 * the remaining handlers.
	 *
	 * @param {Object} callback
	 * @param {Function} callback.onSuccess
	 * @param {Function} callback.onCancel
	 * @param {Function} callback.onFail
	 *
	 * @return {xataface.store.Document.CallbackObject} A proper callback object.
	 */
	function extractCallback(callback){
		var out = {
			onSuccess : function(){},
			onCancel : function(){},
			onFail : function(){}
		};
		if ( typeof(callback) == 'undefined' ){
			return out;
		}
		
		if ( typeof(callback) == 'function' ){
			out.onSuccess = callback;
			out.onCancel = callback;
			out.onFail = callback;
		} else {
			if ( typeof(callback.onSuccess) == 'function' ){
				out.onSuccess = callback.onSuccess;
			}
			if ( typeof(callback.onCancel) == 'function' ){
				out.onCancel = callback.onCancel;
			}
			if ( typeof(callback.onFail)  == 'function' ){
				out.onFail = callback.onFail;
			}
		}
		return out;
	}
	
	
})();