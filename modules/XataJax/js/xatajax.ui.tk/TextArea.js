//require <xatajax.ui.tk/TextComponent.js>
(function(){
	var TextComponent = XataJax.ui.tk.TextComponent;
	var $ = jQuery;
	
	XataJax.ui.tk.TextArea = TextArea;
	
	/**
	 * @constructor
	 */
	function TextArea(o){
		XataJax.extend(this, new TextComponent(o));
		XataJax.publicAPI(this, {
			createElement: createElement,
			decorateElement: decorateElement
		});
		
	}
	
	/**
	 * @override
	 */
	function createElement(){
		return document.createElement('textarea');
	}
	
	/**
	 * @override
	 */
	function decorateElement(e){
		this.getSuper(TextComponent).decorateElement(e);
		$(e).addClass('xatajax-ui-textarea');
	}
	
	
	
	
})();