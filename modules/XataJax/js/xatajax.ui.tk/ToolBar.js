//require <xatajax.ui.tk/Component.js>
//require <xatajax.ui.tk/layout/FlowLayout.js>
//require-css <xatajax.ui.tk/ToolBar.css>
(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var FlowLayout = XataJax.ui.tk.layout.FlowLayout;
	XataJax.ui.tk.ToolBar = ToolBar;
	
	/**
	 * @constructor
	 */
	function ToolBar(o){
		
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			decorateElement: decorateElement
		});
		
		this.setLayout(new FlowLayout());
		
		/**
		 * Decorates the HTMLElement for this component.
		 *
		 * @param {HTMLElement} el
		 * @returns {ToolBar} Self for chaining.
		 */
		function decorateElement(el){
			this.getSuper(Component).decorateElement(el);
			$(el)
				.addClass('xatajax-toolbar')
				.addClass('ui-widget-header')
				.addClass('ui-helper-clearfix')
				
				;
			return this;
		}
		
		
		
	}
})();