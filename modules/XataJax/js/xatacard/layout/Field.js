//require <xatacard/layout/Schema.js>
//require <xatajax.beans/PropertyChangeListener.js>
//require <xatajax.beans/Subscribable.js>


(function(){
	
	var $=jQuery;
	
	var PropertyChangeSupport,
		PropertyChangeListener,
		Schema,
		Subscribable;
		
	XataJax.ready(function(){
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		PropertyChangeListener = XataJax.beans.PropertyChangeListener;
		Schema = xatacard.layout.Schema;
		Subscribable = XataJax.beans.Subscribable;
	});
	
	
	xatacard.layout.Field = Field;
	
	function Field(o){
	
		
		var path;
		
		if ( typeof(o.path) == 'string' ){
			path = o.path;
		}
	
	
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		
		XataJax.publicAPI(this, {
			getPath: getPath,
			setPath: setPath
		});
		
		
		function getPath(){
			return path;
		}
		
		
		function setPath(p){
			if ( p != path ){
				var old = path;
				path = p;
				this.firePropertyChange('path', old, p);
			}
		}
		
		
	
	}

})();