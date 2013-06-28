(function(){
	var $ = jQuery;
	XataJax.ui.application.Tool = Tool;
	XataJax.ui.application.ToolListener = ToolListener;
	
	/**
	 * Error code used to cancel a selection or deselection action
	 * silently.  This code can be used with exceptions thrown
	 * from the beforeSelect or beforeDeselect methods of a 
	 * ToolListener to quietly cancel the selection or deselection.
	 */
	XataJax.errorcodes.CANCEL_ACTION = XataJax.nextErrorCode();
	
	function Tool(o){
		$.extend(this, {
			setName: setName,
			getName: getName,
			setLabel: setLabel,
			getLabel: getLabel,
			setTooltip: setTooltip,
			getTooltip: getTooltip,
			setIcon: setIcon,
			getIcon: getIcon,
			setCssClass: setCssClass,
			getCssClass: getCssClass,
			getElement: getElement,
			select: select,
			deselect: deselect,
			isSelected: isSelected,
			addListener: addListener,
			removeListener: removeListener
			
		});
		var self = this;
	
		var members = {
			name: null,
			label: null,
			tooltip: null,
			icon: null,
			listeners: [],
			cssClass: null,
			el: null
		};
		$.extend(members, o);
		
		
		function setName(name){
			members.name = name;
		}
		
		function getName(){
			return members.name;
		}
		
		function setLabel(label){
			return members.label = label;
		}
		
		function getLabel(){
			if ( members.label != null ) return members.label;
			else return members.name;
		}
		
		function setTooltip(tt){
			members.tooltip = tt;
		}
		
		function getTooltip(){
		
			return members.tooltip;
		}
		
		/**
		 * Sets the icon to use on this tool's button.
		 * @param string icon
		 */
		function setIcon(icon){
			members.icon = icon;
		}
		
		
		/**
		 * Returns the icon to use on this tool's button.
		 *  @return string
		 */
		function getIcon(){
			return members.icon;
		}
		
		
		/**
		 * @return string
		 */
		function getCssClass(){
			return members.cssClass;
		}
		
		
		/**
		 * Sets the CSS class to be used on the DOMElement button for this tool.
		 * @param string css
		 */
		function setCssClass(css){
			members.cssClass = css;
		}
		
		/**
		 * Returns the DOM element that serves as this tool's ui button.
		 * @returns DOMElement
		 */
		function getElement(){
			if ( members.el == null ){
				members.el = document.createElement('a');
				$(members.el).addClass('xatajax-application-tool');
				if ( members.cssClass != null ) $(members.el).addClass(members.cssClass);
				if ( members.icon != null ) $(members.el).css('background-image', 'url('+members.icon+')');
				
				$(members.el).click(fireElementClicked);
			}
			return members.el;
		}
		
		/**
		 * Listener method that is called whenever the tool's button is clicked.
		 */
		 
		function fireElementClicked(event){
			select(event);
		}
		
		
		/**
		 * Selects the tool.  This method will first call the beforeSelect
		 * method of all listeners to give them an opportunity to override
		 * the selection.  Any exception thrown from the beforeSelect
		 * method with the code XataJax.ui.application.errorcodes.CANCEL_ACTION
		 * will cause the selection to not occur.  After the select has successfully
		 * completed this will call the onSelect method of the listeners.
		 */
		function select(event){
			if ( $(members.el).hasClass('selected') ) 
			try {
				$.each(members.listeners, function(){
					($.proxy(this.beforeSelect,self))(event);
				});
			} catch (e){
				e = new XataJax.Exception(e);
				switch (e.getCode()){
					case XataJax.errorcodes.CANCEL_ACTION:
						return;
					default:
						alert(e.getMessage());
				}
				
			}
			
			$(members.el).addClass('selected');
			$.each(members.listeners, function(){
				($.proxy(this.onSelect, self))(event);
			});
		
		}
		
		
		/**
		 * Deselects the tool.  This method will first call the listeners' 
		 * beforeDeselect method to give them an opportunity to override the
		 *	event.  Any exception thrown from the beforeDeselect method
		 * 	with the code XataJax.ui.application.errorcodes.CANCEL_ACTION will
		 * result in this method not completing the deselect.
		 *
		 * @param eventdata event The jquery event data.  May be undefined.  This is optional.
		 * @return void
		 */
		function deselect(event){
			if ( !$(members.el).hasClass('selected') ) return;
			try {
				$.each(members.listeners, function(){
					($.proxy(this.beforeDeselect, self))(event);
				});
			} catch (e){
				e = new XataJax.Exception(e);
				switch (e.getCode()){
					case XataJax.errorcodes.CANCEL_ACTION:
						return;
					default:
						alert(e.getMessage());
				}
				
			}
			
			$(members.el).removeClass('selected');
			$.each(members.listeners, function(){
				($.proxy(this.onDeselect, self)(event));
			});
		}
		
		function isSelected(){
			return $(members.el).hasClass('selected');
		}
		
		/**
		 * Adds a listener to the tool.
		 * @param ToolListener l
		 */
		function addListener(l){
			members.listeners.push(l);
		}
		
		
		/**
		 * Removes a listener.
		 * @param ToolListener l
		 */
		function removeListener(l){
			var idx = members.listeners.indexOf(l);
			if ( idx != -1 ) members.listeners.splice(idx,1);
		}
		
		
		
	}
	
	
	
	function ToolListener(o){
		$.extend(this, o);
	}
	
	ToolListener.prototype = {
		beforeSelect: function(){},
		beforeDeselect: function(){},
		onSelect: function(){},
		onDeselect: function(){}
	};
	
	
	
})();