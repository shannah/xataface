//require <xatajax.ui.tk/Component.js>
//require <xatajax.ui.tk/Button.js>
//require <xatajax.ui.tk/layout/FlowLayout.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <xatajax.ui.tk/ButtonGroup.css>

(function(){
	
	
	var $ = jQuery;
	var Component = XataJax.ui.tk.Component;
	var Button = XataJax.ui.tk.Button;
	var FlowLayout = XataJax.ui.tk.layout.FlowLayout;
	
	
	XataJax.ui.tk.ButtonGroup = ButtonGroup;
	
	
	/**
	 * A ButtonGroup component that is handy building a toolbar.  The concept 
	 * for this button group is borrowed from Filament Group:
	 * <a href="http://www.filamentgroup.com/lab/styling_buttons_and_toolbars_with_the_jquery_ui_css_framework/">Here</a>.
	 *
	 * @constructor
	 */
	function ButtonGroup(o){
	
	
		/**
		 * A setting to decide whether this button group allows only a single select.
		 *
		 * @type {boolean}
		 */
		var singleSelect = true;
	
		XataJax.extend(this, new Component(o));
		XataJax.publicAPI(this, {
			setSingleSelect: setSingleSelect,
			isSingleSelect: isSingleSelect,
			decorateElement: decorateElement,
			add: add,
			remove: remove
		});
		
		this.setLayout(new FlowLayout());
		
		
		/**
		 * Sets whether this group allows only single selection.
		 *
		 * @param {boolean} s
		 * @returns {ButtonGroup} Self for chaining.
		 */
		function setSingleSelect(s){
		
			if ( s != singleSelect ){
				var old = singleSelect;
				singleSelect = s;
				if ( s ){
					$(e)
						.removeClass('xatajax-buttonset-multi')
						.addClass('xatajax-buttonset-single');
				} else {
					$(e)
						.removeClass('xatajax-buttonset-single')
						.addClass('xatajax-buttonset-multi');
				}
				this.firePropertyChange('singleSelect', old, s);
			}
			return this;
		}
		
		/**
		 * Checks if this group allows only single selection.
		 * @returns {boolean}
		 */
		function isSingleSelect(){
			return singleSelect;
		}
		
		/**
		 * Decorates the buttongroup's element.
		 * @override
		 * @param {HTMLElement} e The element for this component.
		 * @returns {ButtonGroup} Self for chaining.
		 */
		function decorateElement(e){
			this.getSuper(Component).decorateElement(e);
			$(e).addClass('xatajax-buttonset');
				
			if ( singleSelect ){
				$(e).addClass('xatajax-buttonset-single');
			} else {
				$(e).addClass('xatajax-buttonset-multi');
			}
			
			return this;
		}
		
		
		function buttonMouseDown(){
			if ( !$(this).hasClass('ui-state-disabled') ){
				$(this)
					.parents('.xatajax-buttonset-single').first()
						.find(".xatajax-button.ui-state-active")
							.removeClass("ui-state-active");
				if( $(this).is('.xatajax-buttonset-multi .ui-state-active') ){ 
					$(this).removeClass("ui-state-active"); 
				} else { 
					$(this).addClass("ui-state-active"); 
				}
			}
			return true;
		}
		
		function buttonMouseUp(){
			if ( !$(this).hasClass('ui-state-disabled') ){
			
				if(! $(this).is('.xatajax-buttonset-single .xatajax-button,  .xatajax-buttonset-multi .xatajax-button') ){
					$(this).removeClass("ui-state-active");
				}
			
			}
			return true;
		
		}
		
		/**
		 * Adds a button to this button group.  It overrides the 
		 * standard Component add method by adding some additional
		 * event handlers to the button.  Although this can be used
		 * to add other types of elements to the buttongroup, it
		 * isn't advisable and will yield undefined results.
		 * @override
		 * 
		 * @param {Button} c The button to add.
		 * @param {mixed} param
		 * @returns {ButtonGroup} Self for chaining.
		 *
		 */
		function add(c, param){
			this.getSuper(Component).add(c, param);
			if ( XataJax.instanceOf(c, Button) ){
				decorateButton(c);
			}
			return this;
		}
		
		/**
		 * Removes a button from this group.  It overrides the Component
		 * remove method by also removing event handlers that had been
		 * added on add.
		 * 
		 * @param {Button} c The button to add to the group.
		 * @param {boolean} repack Whether to repack the layout (not used in ButtonGroup).
		 * @returns {ButtonGroup} Self for chaining.
		 */
		function remove(c, repack){
			this.getSuper(Component).remove(c, repack);
			if ( XataJax.instanceOf(c, Button) ){
				undecorateButton(c);
			}
		}
		
		/**
		 * Decorates a button element.  This adds necessary event handlers
		 * to make the button work nicely in this button group.
		 *
		 * @private
		 * @param {Button} btn The button component to decorate.
		 *
		 */
		function decorateButton(btn){
			//return;
			$(btn.getElement())
				.bind('mousedown.ButtonGroup',buttonMouseDown)
				.bind('mouseup.ButtonGroup', buttonMouseUp);
			
		}
		
		/**
		 * Undecorates a button.  This removes the event handlers that were added
		 * by decorateButton.
		 *
		 * @private
		 * @param {Button} btn Teh button to undecorate.
		 */
		function undecorateButton(btn){
			//return;
			$(btn.getElement())
				.unbind('mousedown.ButtonGroup', buttonMouseDown)
				.unbind('mouseup.ButtonGroup', buttonMouseUp);
		}
	}
})();