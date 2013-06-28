//require <xatajax.ui.tk/TextComponent.js>
(function(){
	var TextComponent = XataJax.ui.tk.TextComponent;
	var $ = jQuery;
	
	XataJax.ui.tk.TextField = TextField;
	
	/**
	 * @constructor
	 */
	function TextField(o){
		XataJax.extend(this, new TextComponent(o));
		XataJax.publicAPI(this, {
			createElement: createElement,
			decorateElement: decorateElement
		});
		
	}
	
	function createElement(){
		return document.createElement('input');
	}
	
	function decorateElement(e){
		this.getSuper(TextComponent).decorateElement(e);
		$(e).attr('type', 'text');
		$(e).addClass('xatajax-ui-textfield');
	}
	
	
})();