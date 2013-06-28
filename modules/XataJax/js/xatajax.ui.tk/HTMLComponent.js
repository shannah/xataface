//require <xatajax.ui.tk/Component.js>
(function(){
	XataJax.ui.tk.HTMLComponent = HTMLComponent;
	var Component = XataJax.ui.tk.Component;
	
	/**
	 * @constructor
	 """
	 new HTMLComponent();
	 """
	*/
	function HTMLComponent(o){
		
		/**
		"""
		HTMLComponent.__methods__ = publicAPI;
		HTMLComponent.__super__ = Component;
		"""
		*/
		/**
		 * Inherit from the Component class
		 */
		XataJax.extend(this, new Component(o));
		
		var publicAPI = {
			setContent: setContent,
			getContent: getContent
		};
		
		/**
		 * Define the public API methods for this class.
		 */
		XataJax.publicAPI(this, publicAPI);
		
		
		/**
		 * Private member variables.  None to speak of.
		 */
		var members = {
			
		};
		
		/**
		 * Sets the HTML content of this component.
		 *
		 * @param string content
		 */
		function setContent(content){
			$(this.getElement()).html(content);
		}
		
		
		/**
		 * Gets the HTML Content of this component.
		 */
		function getContent(){
			return $(this.getElement()).html();
		}
	}
})();