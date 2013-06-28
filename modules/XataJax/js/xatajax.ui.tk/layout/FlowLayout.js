//require <xatajax.ui.tk/LayoutManager.js>
(function(){
	var $ = jQuery;
	var LayoutManager = XataJax.ui.tk.LayoutManager;
	XataJax.ui.tk.layout.FlowLayout = FlowLayout;
	
	
	/**
	 * @constructor
	 */
	function FlowLayout(o){
		
		XataJax.extend(this, new LayoutManager(o));
		
		var publicAPI =  {
			install: install,
			uninstall: uninstall,
			childAdded: childAdded,
			childRemoved: childRemoved
			
		};
		
		XataJax.publicAPI(this, publicAPI);
		
		
		/**
		 * @param {XataJax.ui.tk.Component} c
		 */
		function install(c){
			this.getSuper(LayoutManager).install(c);
			
			$(c.getElement()).addClass('xatajax-ui-layout-flowlayout');
			this.getComponent().update();
		}
		
		/**
		 * @param {XataJax.ui.tk.Component} c
		 */
		function uninstall(c){
			this.getSuper(LayoutManager).uninstall(c);
			$(c.getElement()).removeClass('xatajax-ui-layout-flowlayout');
		}
		
		
		
		/**
		 * @param {XataJax.ui.tk.ComponentEvent} event
		 */
		function childAdded(event){
			
			this.getComponent().update();
		}
		
		
		/**
		 * @param {XataJax.ui.tk.ComponentEvent} event
		 */
		function childRemoved(event){
			this.getComponent().update();
		}
		
	
	}
})();