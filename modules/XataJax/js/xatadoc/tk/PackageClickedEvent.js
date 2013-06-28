//require <xatajax.core.js>
//require <xatadoc/tk/__init__.js>
//require <xatajax.doc.js>
(function(){
	
	var $ = jQuery;
	
	xatadoc.tk.PackageClickedEvent = PackageClickedEvent;
	PackageInfo = XataJax.doc.PackageInfo;
	
	
	/**
	 * Event that is fired by the ClassDetails class when a Package name is clicked by
	 * the user.
	 * @constructor
	 */
	function PackageClickedEvent(o){
	
		/**
		 * The packageInfo object for the package that was clicked.
		 * @type {PackageInfo}
		 */
		var packageInfo;
	
		XataJax.publicAPI(this, {
			packageInfo: null
		});
		
		if ( typeof(o) == 'object' ){
			$.extend(this,o);
		
		}
	
		
	}
})();