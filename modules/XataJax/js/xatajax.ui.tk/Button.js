//require <xatajax.ui.tk.js>
//require <xatajax.ui.tk/Component.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <xatajax.ui.tk/Button.css>

(function(){
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	
	
	XataJax.ui.tk.Button = Button;
	
	/**
	 * A button UI element.  Buttons can be added to toolbars or other 
	 * parts of the interface.
	 *
	 * Much of this implementation is copied from The Filament Group's
	 * Demonstration at <a href="http://www.filamentgroup.com/lab/styling_buttons_and_toolbars_with_the_jquery_ui_css_framework/>Here</a>.
	 *
	 *
	 * @constructor
	 *
	 * @event {void} click Fired when the button is clicked.
	 */
	function Button(o){
	
	
		/**
		 * @type {String}
		 */
		var label = 'Button';
		
		
		/**
		 * The priority of this button (either primary or secondary)
		 * @type {String}
		 */
		var priority = 'primary';
		
		
		/**
		 * Sets whether the button is disabled.
		 * @type {boolean}
		 */
		var disabled = false;
		
		/**
		 * The icon to use for this button.  This is the name of an icon.
		 * Generally you can choose from the icons in the jQueryUI theme.
		 * This string only holds the name of the icon (not the ui-icon- prefix).
		 *
		 * @type {String}
		 */
		var icon = null;
		
		
		/**
		 * The icon style.  This is one of 'solo', 'left', 'right'.  This 
		 * determines where the icon is displayed in the button.
		 *
		 * @type {String}
		 */
		var iconStyle = 'left';
		
		
		
		/**
		 * A setting to set whether this button is a toggle button.  By 
		 * default this is false.
		 *
		 * @type {boolean}
		 */
		var toggleable = false;
	
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			setLabel: setLabel,
			getLabel: getLabel,
			setIcon: setIcon,
			getIcon: getIcon,
			setIconStyle: setIconStyle,
			setIconStyleLeft: setIconStyleLeft,
			setIconStyleRight: setIconStyleRight,
			setIconStyleSolo: setIconStyleSolo,
			getIconStyle: getIconStyle,
			setPriority: setPriority,
			getPriority: getPriority,
			setDisabled: setDisabled,
			isDisabled: isDisabled,
			createElement: createElement,
			decorateElement: decorateElement
		});
		
		function setToggleable(t){
			if ( t != toggleable ){
				var old = toggleable;
				toggleable = t;
				
				if ( t ){
					$(this.getElement()).addClass('xataface-button-toggleable');
				} else {
					$(this.getElement()).removeClass('xataface-button-toggleable');
				}
				
				this.firePropertyChange('toggleable', old, t);
			}
		
		
		}
		
		/**
		 * Sets the icon for this button.
		 *
		 * See <a href="http://www.themeroller.com/">ThemeRoller</a> for a list of available
		 * 	buttons.
		 *
		 * @param {String} i The jQueryUI icon name.
		 * @returns {Button} Self for chaining.
		 */
		function setIcon(i){
			if ( i != icon ){
				var old = icon;
				icon = i;
				var iconEl = $('span.ui-icon', this.getElement());
				if ( iconEl.size() == 0 ){
					iconEl = document.createElement('span');
					iconEl = $(iconEl);
					iconEl.addClass('ui-icon');
					$(this.getElement()).prepend(iconEl);
				}
				if ( old ){
					iconEl.removeClass('ui-icon-'+old);
				}
				if ( i ){
					iconEl.addClass('ui-icon-'+i);
				} else {
					iconEl.remove();
				}
						
				this.firePropertyChange('icon', old, i);
			}
			return this;
		}
		
		
		/**
		 * Gets the icon for this button.
		 *
		 * See <a href="http://www.themeroller.com/">Themeroller</a> for a list of available
		 * icons.
		 *
		 * @returns {String} The jQueryUI icon name.
		 */
		function getIcon(){
			return icon;
		}
		
		/**
		 * Sets button's icon style.  Either left, right, or solo.
		 *
		 * @param {String} style Either 'left', 'right', or 'solo'.
		 * @returns {Button} Self for chaining.
		 */
		function setIconStyle(style){
			if ( style != iconStyle ){
				var old = iconStyle;
				iconStyle = style;
				
				$(this.getElement()).removeClass('xatajax-button-icon-'+old);
				$(this.getElement()).addClass('xatajax-button-icon-'+style);
				
				this.firePropertyChange('iconStyle', old, style);
			}
			return this;
		}
		
		/**
		 * Returns the icon style (left, right, or solo).
		 * @returns {String}
		 */
		function getIconStyle(){
			return iconStyle;
		}
		
		
		/**
		 * Sets the icon style to solo so that the label is hidden and only the icon
		 * is displayed.
		 * @returns {Button} Self for chaining
		 */
		function setIconStyleSolo(){
			return this.setIconStyle('solo');
		}
		
		/**
		 * Sets the icon style to 'left' so that the icon appears to the left of the 
		 * button label.
		 *
		 * @returns {Button} Self for chaining.
		 *
		 */
		function setIconStyleLeft(){
			return this.setIconStyle('left');
		}
		
		
		/**
		 * Sets the icon style to 'right' so that the icon is to the right of the 
		 * label.
		 *
		 * @returns {Button} Self for chaining.
		 */
		function setIconStyleRight(){
			return this.setIconStyle('right');
		}
		
		
		/**
		 * Sets whether this button is disabled.
		 * @param {boolean} d The disabled setting.  True for disabled.
		 * @returns {Button} Self for chaining.
		 */
		function setDisabled(d){
			if ( d != disabled ){
				var old = disabled;
				disabled = d;
				if ( d ){
					$(this.getElement()).addClass('ui-state-disabled');
				} else {
					$(this.getElement()).removeClass('ui-state-disabled');
				}
				this.firePropertyChange('disabled', old, d);
			}
			return this;
		}
		
		
		/**
		 * Checks whether button is disabled.
		 * @returns {boolean}
		 */
		function isDisabled(){
			return disabled;
		}
		
		/**
		 * Sets the label of this button.
		 * @param {String} l The string label.
		 * @returns {Button} Self for chaining.
		 */
		function setLabel(l){
			if ( label != l ){
				var old = label;
				label = l;
				var iconEl = $('span.ui-icon', this.getElement());
				$(this.getElement()).text(l);
				if ( iconEl.size() > 0 ){
					$(this.getElement()).prepend(iconEl);
				}
				this.firePropertyChange('label', old, l);
			}
			return this;
		}
		
		
		/**
		 * Gets the label of this button.
		 * @returns {String}
		 */
		function getLabel(){
			return label;	
		}
		
		/**
		 * Sets the priority of the button (either "primary" or "secondary").
		 * @param {String} p
		 * @returns {Button} Self for chaining.
		 */
		function setPriority(p){
			if ( ['primary','secondary'].indexOf(p) == -1 ){
				throw new Exception({
					message: 'Illegal parameters for setPriority.  Must be one of "primary" or "secondary"'
				});
			}
			if ( p != priority ){
				var old = priority;
				priority = p;
				$(this.getElement())
					.removeClass('ui-priority-'+old)
					.addClass('ui-priority-'+p);
				this.firePropertyChange('priority', old, p);
			}
			return this;
		}
		
		/**
		 * Gets the priority of the button.  This is either 'primary' or 'secondary'.
		 * This can help indicate to the user which button's have a higher priority.  For
		 * example in a dialog you may want to emphasize the "save" button over the "cancel"
		 * button.  In this case you would set the priority of the "save" button to "primary"
		 * and the priority of the "cancel" button to "secondary".
		 *
		 * @returns {String}
		 */
		function getPriority(){
			return priority;
		}
		
		
		
		/**
		 * Creates the button element.
		 * @override
		 * @returns {HTMLElement}
		 */
		function createElement(){
			return document.createElement('button');
		}
		
		/**
		 * This function is a private function that is meant
		 * to be attached to the button's HTMLElement as a
		 * listener to the hover event.
		 *
		 * The "this" context will be the HTMLElement and not the 
		 * component.
		 *
		 */
		function setButtonElementHover(){
			if ( !$(this).hasClass('ui-state-disabled') ){
				$(this).addClass("ui-state-hover");
			}
		}
		
		
		/**
		 * This function is a private function that is meant
		 * to be attached to the button's HTMLElement as a
		 * listener to the hover event.
		 *
		 * The "this" context will be the HTMLElement and not the 
		 * component.
		 *
		 */
		function clearButtonElementHover(){
			if ( !$(this).hasClass('ui-state-disabled') ){
				$(this).removeClass("ui-state-hover");
			}
		}
		
		
		function buttonMouseDown(){
			if ( !$(this).hasClass('ui-state-disabled') ){
			
				if( $(this).is('.ui-state-active.xatajax-button-toggleable') ){ 
					$(this).removeClass("ui-state-active"); 
				}
				else { 
					$(this).addClass("ui-state-active"); 
				}	
			}
			
		}
		
		function buttonMouseUp(){
			if ( !$(this).hasClass('ui-state-disabled') ){
				if(! $(this).is('.xatajax-button-toggleable') ){
					$(this).removeClass("ui-state-active");
				}
			}
	
			
		}
		
		
		/**
		 * Decorates the button element.  This assigns all the CSS classes
		 * and event handling.
		 * @param {HTMLElement} el The HTML element that we are styling.
		 * @returns {Button} Self for chaining.
		 */
		function decorateElement(el){
			this.getSuper(Component).decorateElement(el);
			var self = this;

			$(el)
				
				.click(function(){
					//alert('here');
					self.trigger('click');
				})
				.hover(setButtonElementHover,clearButtonElementHover)
				.mousedown(buttonMouseDown)
				.mouseup(buttonMouseUp)
				.attr('type', 'submit')
				.addClass('ui-state-default')
				.addClass('ui-corner-all')
				.addClass('xatajax-button')
				.addClass('xatajax-button-icon-'+iconStyle)
				;
				
				
			return this;
				
		}
		
	}
})();