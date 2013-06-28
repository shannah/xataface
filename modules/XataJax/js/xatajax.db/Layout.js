//require <xatajax.db.core.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
(function(){
	
	var $ = jQuery;
	
	function Layout(o){
	
		
		var fields = {};
		var portals = {};
		
		
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		
		XataJax.publicAPI(this, {
		
		});
		
		
		
	
	}
})();