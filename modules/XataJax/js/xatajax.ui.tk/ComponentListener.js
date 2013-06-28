//require <xatajax.ui.tk.js>
/**
 * The ComponentListener interface that should be implemented by any
 * object that wishes to register to receive notifications from 
 * Components when child components are added or removed.
 *
 * @created Feb. 7, 2011
 * @author Steve Hannah <steve@weblite.ca>
 */
(function(){

	var ComponentEvent = XataJax.ui.tk.ComponentEvent;

	/**
	 * Register the Public API
	 */
	XataJax.ui.tk.ComponentListener = ComponentListener;
	
	
	/**
	 * Implementation Details Below this line =========
	 */
	var $ = jQuery;
	
	/**
	 * An interface for objects that wish to be notified of changes to a component.
	 * @override-params any
	 *
	 * @constructor
	 */
	function ComponentListener(o){
		
		XataJax.publicAPI(this, publicAPI);
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
	}
	
	var publicAPI = {
		beforeChildAdded: beforeChildAdded,
		beforeChildRemoved: beforeChildRemoved,
		childAdded: childAdded,
		childRemoved: childRemoved,
		
		beforeUpdate: beforeUpdate,
		
		afterUpdate: afterUpdate
		
		
		 
	};
	
	ComponentListener.prototype = publicAPI;
	ComponentListener.constructor = ComponentListener;
	
	
	/**
	 * Method called before a child is added to a component.  This gives an
	 * opportunity for the listener to veto the add by throwing an exception.
	 *
	 * @param ComponentEvent event
	 * @throws XataJax.Exception
	 */
	function beforeChildAdded(event){}
	
	/**
	 * Method called before a child is removed from the compoennt. This
	 * gives an opportunity for the listener to veto the removal by 
	 * throwing an exception.
	 *
	 * @param {ComponentEvent} event
	 * @throws XataJax.Exception
	 */
	function beforeChildRemoved(event){}
	
	/**
	 * Method called after a child component has successfully been added to
	 * the component.
	 *
	 * @param ComponentEvent event
	 */
	function childAdded(event){}
	
	/**
	 * Method called after a child component has successfully been removed from
	 * the component.
	 *
	 * @param ComponentEvent event
	 */
	function childRemoved(event){}
	
	function beforeUpdate(event){}
	
	function afterUpdate(event){}
		
		
		 

})();