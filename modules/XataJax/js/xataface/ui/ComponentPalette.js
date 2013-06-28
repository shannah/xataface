//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <xataface/ui/ComponentPalette.css>
(function(){

	if ( window.xataface == 'undefined' ) window.xataface = {};
	if ( window.xataface.ui == 'undefined' ) window.xataface.ui = {};

	window.xataface.ui.ComponentPalette = ComponentPalette;
	ComponentPalette.Component = Component;

	var $ = jQuery;
	
	
	/**
	 * Constructor for a component palette that contains components that can be 
	 * dragged onto a UI designer.
	 *
	 * @param {String} title The title of the component palette.
	 * 
	 * @event componentDropped Fired when a component is dropped somewhere in the UI.
	 *		Event Data Format:
	 *			{
	 *				event: <jQuery Event Object>//
	 *				ui: <jQuery ui object> //
	 *				component: <Component> //The component that was dropped
	 *			}
	 */
	function ComponentPalette(/*Object*/ o){
		if( typeof(o) == 'undefined') o = {};
		
		this.components = [];
		this.el = $('<div></div>').get(0);
		this.title = 'Components';
		this.titleEl = $('<h3></h3>').get(0);
		this.componentsEl = $('<div></div>').get(0);
			
		
		$.extend(this,o);
		$(this.el)
			.addClass('xf-ComponentPalette')
			
		$(this.titleEl)
			.addClass('xf-ComponentPalette-title')
			.text(this.title);
			
		$(this.el).append(this.titleEl);
		
		
		$(this.componentsEl)
			.addClass('xf-ComponentPalette-components')
			;
		$(this.el).append(this.componentsEl);
		
		this.updateComponents = true;
			
	
	}
	
	
	ComponentPalette.prototype.update = ComponentPalette_update;
	ComponentPalette.prototype.newComponent = ComponentPalette_newComponent;
	ComponentPalette.prototype.addComponent = ComponentPalette_addComponent;
	ComponentPalette.prototype.removeComponent = ComponentPalette_removeComponent;
	
	
	
	/**
	 * Updates the component palette.  This re-lays out the component palette in the UI.
	 */
	function ComponentPalette_update(){
		var self = this;
		$(this.titleEl).text(this.title);
		if ( this.updateComponents ){
			$(this.componentsEl).children().detach();
			$.each(this.components, function(){
				$(self.componentsEl).append(this.el);
			});
			this.updateComponents = false;
		}
		
		$.each(this.components, function(){
			this.update();
		});
	}
	
	
	/**
	 * Adds a component to the component palette.
	 * @param {Component} c The component to add.
	 * @return {boolean} True if the component was added.  False otherwise (or it was already registered)
	 */
	function ComponentPalette_addComponent(/*Component*/ c){
		var idx = this.components.indexOf(c);
		if ( idx < 0 ){
			c.palette = this;
			this.components.push(c);
			this.updateComponents = true;
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Removes a component from the palette
	 * @param {Component} c The component to remove.
	 * @return {boolean} True if the component was removed.  False otherwise.
	 */
	function ComponentPalette_removeComponent(/*Component*/ c){
		var idx = this.components.indexOf(c);
		if ( idx >= 0 ){
			c.palette = null;
			this.components.splice(idx,1);
			this.updateComponents = true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Creates a new component that can be added to this palette.  This is not added to the palette
	 * yet.  To add the component to the Palette, see the addComponent() method.
	 *
	 * @param {Object} o Object with properties to pass to the Component.  
	 *		@see Component()
	 * @return {Component} The component that was created.
	 */
	function ComponentPalette_newComponent(/*Object*/o){
		if ( typeof(o) == 'undefined' ){
			o = {};
		}	
		var newO = $.extend({}, o);
		return new Component(newO);
	
	}
	
	
	/**
	 * Constructor for a component that can be added to the component palette.
	 *
	 * @param {String} icon The URL of the icon for this component.
	 * @param {String} title The help text or title for this component.
	 *
	 * @event componentDropped
	 *		Event Data:
	 *			{
	 *				event: <jQuery Event>
	 *				ui: <jQuery UI Object>
	 *				component: <Component>
	 *			}
	 */
	function Component(/*Object*/ o){
		var self = this;
		this.palette = null;
		this.icon = null;
		this.title = 'Add this component to the canvas';
		if ( typeof(o) == 'undefined' ) o = {};
		this.el = $('<img/>').get(0);
			
			
		$.extend(this,o);
		
		
		
		$(this.el)
			.addClass('xf-ComponentPalette-Component')
		
			.draggable({
				opacity: 0.5,
				revert: true,
				stop: function(event,ui){
					$(self).trigger('componentDropped', {event:event, ui:ui, component:self});
					$(self.palette).trigger('componentDropped', {event:event,ui:ui,component:self});
				},
				helper: 'clone',
				appendTo: 'body'
				
				
			})
			;
		
	}
	
	Component.prototype.update = Component_update;
	
	/**
	 * Updates the UI of the component based on the details of it.
	 */
	function Component_update(){
		
		$(this.el)
			.attr('title', this.title)
			.attr('src', this.icon);
	}
	
	
	
	
	

})();