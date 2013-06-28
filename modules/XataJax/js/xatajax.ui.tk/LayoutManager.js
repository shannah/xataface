//require <xatajax.ui.tk.js>
//require <xatajax.ui.tk/Component.js>
//require <xatajax.ui.tk/ComponentEvent.js>
/**
 * The base class for layout managers of Components.  A Layout Manager is responsible
 * for the rendering and formatting of child components of a component.  This default
 * implementation simply lays out child components sequentially, but subclasses, can
 * override the behavior to support more complex layouts.
 *
 * @depends XataJax.ui.tk.Component
 * @created Feb. 7, 2011
 * @author Steve Hannah <steve@weblite.ca>
 */
(function(){
	
	var Component = XataJax.ui.tk.Component;
	var ComponentEvent = XataJax.ui.tk.ComponentEvent;
	
	/**
	 * Define the public API
	 */
	XataJax.ui.tk.LayoutManager = LayoutManager;
	
	/**
	 * @package
	 */
	XataJax.ui.tk.layout = {};
	
	
	/**
	 * Implementation details.
	 */

	var $ = jQuery;
	

	/**
	 * Base class for layout managers which provide a layout mechanism for components.
	 * Components don't have to use layout managers, but they can help to make certain
	 * complex layouts reusable.
	 *
	 * @constructor
	 """
	 new LayoutManager();
	 """
	*/
	function LayoutManager(o){
		/**
		
		@constructor
		 """
		 LayoutManager.__methods__ = publicAPI;
		 """
		*/
		var publicAPI = {
			install: install,
			uninstall: uninstall,
			update: update,
			childAdded: childAdded,
			childRemoved: childRemoved,
			getComponent: getComponent
		};
	
		XataJax.publicAPI(this, publicAPI);
		
	
		/**
		 * Private Member Variables
		 */
		var members = {
			/**
			 * @type Component
			 */
			component: null
		};
		
		/**
		 * @returns {Component} The component that this object is managing.
		 */
		function getComponent(){
			return members.component;
		}
		
		
		
		/**
		 * Installs this layout manager into the specified component.  This 
		 * uninstalls any existing component, and registers tha layout manager
		 * as a component listener.  Finally it updates the component.
		 *
		 * @param {Component} c
		 */
		function install(c){
			if ( members.component != c ){
				if ( members.component != null ) this.uninstall(members.component);
				members.component = c;
				c.addComponentListener(this);
				c.update();
			}
			
		}
		
		/**
		 * Uninstalls the component from this layout manager.
		 *
		 * @param {Component} c
		 */
		function uninstall(c){
			if ( members.component == c ){
				members.component = null;
				c.removeComponentListener(this);
			}
		}
		
		/**
		 * Clears and re-lays out the component's children.  The default implementation
		 * just lays them all out sequentially, but other implementations might
		 * override this method to do more advanced layouts.
		 *
		 * Note: It is better to call getComponent().update() which in turn calls
		 * this method.  That way the ComponentListeners' beforeUpdate and afterUpdate
		 * events will be properly handled.
		 *
		 */
		function update(){
			if ( members.component == null ){
				return;
			}
			var el = members.component.getElement();
			$(el).children().detach();
			$.each(this.getComponent().getChildComponents(), function(){
				$(el).append(this.getElement());
			});
		}
		
		
		/**
		 * Method called when a child is added to the component.  This 
		 * prompts the layout manager to add the child's element
		 * to the layout.  This can be overridden by subclasses
		 * to handle things differently.
		 * This is an implementation of ComponentListener.childAdded
		 *
		 * @see ComponentListener#childAdded
		 * @param {ComponentEvent} event
		 */
		function childAdded(event){
			members.component.getElement().appendChild(event.component.getElement());
		}
		
		
		/**
		 * Method called when a child is removed from the component. This prompts
		 * the layout manager to remove the child's element from the component's 
		 * element.  This can be overridden b subclasses to handle things
		 * differently.
		 *
		 * This method is an implementation of the ComponentListener.childRemoved
		 *
		 * @see ComponentListener#childRemoved
		 * @param {ComponentEvent} event
		 */
		function childRemoved(event){
			$(event.component.getElement()).remove();
		}
		
		
		
		
		
	}
})();