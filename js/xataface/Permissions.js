//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){

	var $ = jQuery;
	var xataface = XataJax.load('xataface');
	xataface.Permissions = Permissions;
	
	
	
	/**
	 * @class
	 * @name Permissions
	 * @memberOf xataface
	 *
	 * @description Class to help obtain permissions information about a specific record.
	 * @param {Object} o The config parameters.
	 * @param {Object} o.query The query parameters following Xataface URL conventions.
	 * @example
	 * //require &lt;xataface/Permissions.js&gt;
	 * var Permissions = XataJax.load('xataface.Permissions');
	 * var myperms = new Permissions({
	 *		query: {
	 *			'-table': 'users',
	 *			'user_id': 10
	 *		}
	 * });
	 * myperms.ready(function(){
	 *     // We can't call any thing until the permissions are ready
	 *     if ( myperms.checkPermission('view') ){
	 *         alert('We have view permission');
	 *     }
	 *
	 * });
	 */
	function Permissions(/**Object*/ o){
		this.query = null;
		this.permissions = null;
		if ( typeof(o) == 'undefined' ) o = {};
		$.extend(this, o);
		
		
		
		
	
	}
	
	
	$.extend(Permissions.prototype, {
	
		load: load,
		ready: ready,
		checkPermission: checkPermission,
		getPermissions: getPermissions,
		setQuery: setQuery,
		getQuery: getQuery
	
	});
	
	
	/**
	 * @function
	 * @name load
	 * @memberOf xataface.Permissions#
	 *
	 * @description Loads the permissions for the current query from the server.
	 * @param {Function} callback Callback function called when loading is complete.
	 *		This function is run in the context of this Permissions object.
	 * @see xataface.Permissions#ready
	 */
	function load(callback){
		if ( !this.query ){
			throw "No query provided for permissions";
			
		}
		
		this.query['-action'] = 'ajax_get_permissions';
		
		if ( !callback ) callback = function(){};
		var self = this;
		$.get(DATAFACE_SITE_HREF, this.query, function(res){
			self.permissions = res;
			
			callback.call(self);
		});
		
	}
	
	
	/**
	 * @function
	 * @name ready
	 * @memberOf xataface.Permissions#
	 * 
	 * @description Runs a callback function after the permissions object is ready.  All methods
	 * for checking permissions should be run inside this object.
	 *
	 */
	function ready(callback){
	
		if ( typeof(callback) == 'undefined' ) callback = function(){};
		
		if ( this.permissions != null ){
			callback.call(this);
		
		} else {
			this.load(callback);
		}
	}
	
	
	/**
	 * @function 
	 * @name checkPermission
	 * @memberOf xataface.Permissions#
	 * @description Checks to see if the given permission is granted.  This method
	 *   should be run after the Permissions object is ready.
	 * @see xataface.Permissions#ready
	 *
	 * @param {String} perm The permission to check.
	 * @returns {Boolean} True if the permission is granted.  False otherwise.
	 *
	 */
	function checkPermission(perm){
		if ( this.permissions != null ){
			return this.permissions[perm] ? true:false;
		}
		return false;
	}
	
	/**
	 * @function
	 * @name getPermissions
	 * @memberOf xataface.Permissions#
	 * @description Returns an associative array of granted permissions.
	 * This should only be run after the Permissions object is ready.
	 * @see xataface.Permissions#ready
	 * @returns {Object} 
	 */
	function getPermissions(){
		return this.permissions;
	}
	
	
	/**
	 * @function
	 * @name setQuery
	 * @memberOf xataface.Permissions#
	 * @description Sets the query to be used.  This will clear out the current
	 * permissions cache and cause the object to need to reload the permissions.
	 * @returns {void}
	 * @param {Object} q The query.
	 * @see xataface.Permissions#getQuery
	 */
	function setQuery(q){
		this.query = q;
		this.permissions = null;
	
	}
	
	/**
	 * @function
	 * @name getQuery
	 * @memberOf xataface.Permissions#
	 * @description Gets the query that was used to get these permissions.
	 * @returns {Object}
	 * @see xataface.Permissions#setQuery
	 */
	function getQuery(){
		return this.query;
	}
	
	

})();