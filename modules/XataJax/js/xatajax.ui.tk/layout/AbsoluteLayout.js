//require <xatajax.ui.tk/LayoutManager.js>
//require <xatajax.ui.tk/Component.js>
//require-css <xatajax.ui.tk/layout/AbsoluteLayout.css>
(function(){
	var LayoutManager = XataJax.ui.tk.LayoutManager;
	var Component = XataJax.ui.tk.Component;
	XataJax.ui.tk.layout.AbsoluteLayout = AbsoluteLayout;
	
	/**
	 * A layout that positions elements absolutely on the canvas (e.g. x,y)
	 *
	 * @constructor
	 """
	 new AbsoluteLayout();
	 """
	 */
	function AbsoluteLayout(o){
		
		XataJax.extend(this, new LayoutManager(o));
		
		var publicAPI = {
			install: install,
			uninstall: uninstall
		};
		
		XataJax.publicAPI(this, publicAPI);
		
		/**
		 * Installs the layout onto a component.
		 * @param {Component} c
		 */
		function install(c){
			this.getSuper(LayoutManager).install(c);
			$(c.getElement()).addClass('xatajax-ui-layout-absolutelayout');
			$(c.getElement()).addClass('xatajax-ui-component-slot');
		}
		
		
		/**
		 * Uninstalls the layout from a component.
		 * @param {Component} c
		 */
		function uninstall(c){
			this.getSuper(LayoutManager).uninstall(c);
			$(c.getElement()).removeClass('xatajax-ui-layout-absolutelayout');
			$(c.getElement()).removeClass('xatajax-ui-component-slot');
		}
	
	}
})();