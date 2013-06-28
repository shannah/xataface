//require <xatajax.ui.core.js>
//require-css <xatajax.ui.actions.css>

(function(){
	XataJax.ui.actions = {};
	XataJax.ui.actions.Action = Action;
	XataJax.ui.actions.getActions = getActions;
	XataJax.ui.actions.addAction = addAction;
	XataJax.ui.actions.removeAction = removeAction;
	XataJax.ui.actions.activateElement = activateElement;
	XataJax.ui.actions.deactivateElement = deactivateElement;
	

	var $ = jQuery;
	var hoverPanel = document.createElement('div');
	$(hoverPanel).addClass('xatajax-ui-actions-hoverPanel');
	
	var panelLocation = 'left';

	
	
	
	/**
	 * A base class for all actions that can be added to elements.
	 * @param string icon The url to the icon to use for this action.
	 * @param string cssClass The css class for this action.
	 * @param function func The function to perform for this action.  The function 
	 *		will be called in the context of the element (via jQuery... so you can
	 *		use the this keyword to refer to the activated element.
	 * @param Element el The element that this action acts upon.
	 * @param Element buttonEl The button element.
	 */
	function Action(o){
		this.icon = '';
		this.cssClass = 'generic-action';
		this.func = function(){};
		this.label = 'Untitled Action';
		this.name = 'new_action';
		this.description = '';
		$.extend(this, o);
		
		
	}
	
		
		
		
		
		
		
	
	
	
	
	function getActions(el){
	
		var actions = $(el).data('xatajax_actions');
		if ( typeof(actions) != 'object' ){
			actions = {};
		}
		return actions;
	}
	
	
	function setActions(el, actions){
		$(el).data('xatajax_actions', actions);
	}
	
	
	
	
	function addAction(el, action){
		var actions = getActions(el);
		actions[action.name] = action;
		setActions(el, actions);
		
	}
	
	function removeAction(el, action){
		var actions = getActions(el);
		delete actions[action.name];
		setActions(el, actions);
	}
	
	
	
	function activateElement(el){
		$(el).mouseover(showActionsCallback);
		//$(el).mouseout(hideActionsCallback);
		
	}
	
	function deactivateElement(el){
		$(el).unbind('mouseover', showActions);
		$(el).unbind('mouseout', hideActions);
	}
	
	function createActionButton(el, action){
		var button = document.createElement('a');
		$(button)
			.addClass('xatajax-ui-actions-actionBtn')
			.css('background-image', 'url('+(action.icon)+')')
			.click(function(){
				$(el).each(action.func);
			})
			.attr({
				title: action.label
			});
		
		return button;
		
	}
	
	function showActionsCallback(event){
		showActions(this);
	}
	
	function showActions(el){

		
		$(hoverPanel).html('');
		$('body').append(hoverPanel);
		
		var actions = getActions(el);
		for ( var i in actions){
			$(hoverPanel).append(createActionButton(el, actions[i]));
		}
		
		var o = $(el).offset();
		
		$(hoverPanel).css({
			top: o.top,
			left: o.left-50
		});
		
		$(hoverPanel).show();
		
		
		
	
	}
	
	// Wrapper for hideActions meant to be used
	// as a jquery onmouseout event listener.
	function hideActionsCallback(event){
		hideActions(this);
	}
	function hideActions(el){
		
		$(hoverPanel).hide();
		$(hoverPanel).remove();
	}
})();