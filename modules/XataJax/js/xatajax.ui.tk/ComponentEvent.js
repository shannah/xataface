//require <xatajax.ui.tk.js>
//require <jquery.packed.js>

/**
 * The ComponentEvent class.  This encapsulates an event that is thrown
 * by a Component when adding or removing child components.
 *
 * @see ComponentListener
 * @see Component
 *
 * @created Feb. 7, 2011
 * @author Steve Hannah <steve@weblite.ca>
 */
(function(){
	
	var $ = jQuery;
	
	/**
	 * Define the Public API
	 */
	XataJax.ui.tk.ComponentEvent = ComponentEvent;
	
	
	/**
	 * Implementation Details
	 */
	
	
	/**
	 * @override-params any
	 * @constructor
	 """
	 new ComponentEvent();
	 ComponentEvent.__properties__ = XataJax.doc.getProperties(publicAPI);
	 """
	 */
	function ComponentEvent(o){
		/**
		"""
		ComponentEvent.__methods__ = {};
		
		"""
		*/
		$.extend(this,o);
	}
	
	var publicAPI = {
		name: name,
		source: source,
		component: component,
		index: index
	};

	ComponentEvent.prototype = publicAPI;
	ComponentEvent.constructor = ComponentEvent;
	

		
	/**
	 * The name of the child if a name was chosen.
	 * @type string
	 */
	var name = null;
	
	/**
	 * The source component (i.e. parent)
	 * @type Component
	 */
	var source = null;
	
	/**
	 * The child component (being added or removed)
	 * @type Component
	 */
	var component = null;
	
	/**
	 * The index where the component was inserted.
	 * @type int
	 */
	var index = null;
	
	
	
	
	
})();