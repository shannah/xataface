//require <xatajax.ui.tk/Component.js>
(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var Exception = XataJax.Exception;
	
	XataJax.ui.tk.TextComponent = TextComponent;
	
	
	/**
	 * @constructor
	 */
	function TextComponent(o){
	
		/**
		 * Define the Public API
		 */
		 
		/**
		 * Extend Component.
		 */
		XataJax.extend(this, new Component(o));
		
		XataJax.publicAPI(this, {
			getText: getText,
			setText: setText,
			isEditable: isEditable,
			setEditable: setEditable,
			isDisabled: isDisabled,
			setDisabled: setDisabled
		});
		
		/**
		 * Private member variables.
		 */
		var members = {
			/**
			 * @type string
			 */
			text: null,
			
			/**
			 * @type boolean
			 */
			editable: true,
			
			/**
			 * @type boolean
			 */
			disabled: false
		};
		
		/**
		 * Gets the text in this text component.
		 * @returns string
		 */
		function getText(){
			return members.text;
		}
		
		
		/**
		 * Sets the text in this text component.
		 * @param string t
		 */
		function setText(t){
			if ( t != members.text ){
				var old = members.text;
				members.text = t;
				$(this.getElement()).val(t);
				this.firePropertyChange('text', old, t);
			}
		}
		
		/**
		 * Tests whether this text component is currently editable.
		 * @returns boolean
		 */
		function isEditable(){
			return members.editable;
		}
		
		
		/**
		 * Sets whether this text component is currently editable.
		 *
		 * @param boolean e
		 */
		function setEditable(e){
			if ( members.editable != e ){
				var old = members.editable;
				members.editable = e;
				$(this.getElement()).attr('editable', e);
				this.firePropertyChange('editable', old, e);
			}
		}
		
		
		/**
		 * Tests whether this text component is currently disabled.
		 */
		function isDisabled(){
			return members.disabled;
		}
		
		
		/**
		 * Sets whether this text component is currently disabled.
		 * @param boolean d
		 */
		function setDisabled(d){
			if ( members.disabled != d ){
				var old = members.disabled;
				members.disabled = d;
				$(this.getElement()).attr('disabled', d);
				this.firePropertyChange('disabled', old, d);
			}
		}
		
		
		
	}
})();