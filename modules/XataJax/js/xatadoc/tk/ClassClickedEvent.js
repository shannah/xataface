//require <xatajax.core.js>
//require <xatadoc/tk/__init__.js>
(function(){
	var $ = jQuery;
	var ClassInfo = XataJax.doc.ClassInfo;
	
	xatadoc.tk.ClassClickedEvent = ClassClickedEvent;
	
	
	
	/**
	 * Event that is fired by the ClassDetails class when a Class name is clicked by the
	 * user.
	 * @constructor
	 */
	function ClassClickedEvent(o){
		/**
		 * The ClassInfo object for the package that was clicked.
		 * @type {ClassInfo}
		 */
		var classInfo;
	
		XataJax.publicAPI(this, {
			classInfo: null
		});
		
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
		
		
	}
	
})();