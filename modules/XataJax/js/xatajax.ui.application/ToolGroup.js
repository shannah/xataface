/**
 * The ToolGroup class manages a number of tools and allows them to work together.
 * Selecting one tool should cause the other tools to become deselected.
 * This class is meant to be included by the XataJax compiler in the
 * xatajax.ui.application file and not referenced on its own.
 * 
 * @depends xatajax.ui.application/Tool.js
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created Feb. 5, 2011
 */
(function(){
	var $ = jQuery;
	// Register the ToolGroup class to it can be accessed publicly.
	XataJax.ui.application.ToolGroup = ToolGroup;
	
	function ToolGroup(o){
	
		/*
		 * Register the public methods for this object.
		 */
		$.extend(this, {
			addTool: addTool,
			removeTool: removeTool,
			getTools: getTools,
			getSelectedTool: getSelectedTool,
			getElement: getElement
			
		});
	
	
		/**
		 * Stores the private member variables for this object.
		 */
		var members = {
			tools:[],
			el: null
			
		};
		if ( typeof(o) == 'object' ){
			if ( typeof(o.tools) != 'undefined' ){
				for ( var i=0;i<o.tools.length; i++){
					addTool(tools[i]);
				}
			}
		}
		
		
		/**
		 * Adds a tool to the tool group.
		 * @param XataJax.ui.application.Tool tool
		 */
		function addTool(tool){
			var idx = members.tools.indexOf(tool);
			if ( idx == -1 ){
				tool.addListener(selectionListener);
				members.tools.push(tool);
				getElement().appendChild(tool.getElement());
			}
		}
		
		/**
		 * Removes a tool from this tool group.
		 * @param XataJax.ui.application.Tool tool
		 */
		function removeTool(tool){
			var idx = members.tools.indexOf(tool);
			
			if ( idx != -1 ){
				members.tools.splice(idx, 1);
				tool.removeListener(selectionListener);
				$(tool.getElement()).remove();
			}
		}
		
		/**
		 * Gets the tools that are part of this group.
		 * @returns array(XataJax.ui.application.Tool)
		 */
		function getTools(){
			return members.tools;
		}
		
		
		/**
		 * Gets the currently selected tool
		 * returns XataJax.ui.application.Tool
		 */
		function getSelectedTool(){
			for ( var i=0; i<members.tools.length; i++){
				if ( members.tools[i].isSelected() ) return members.tools[i];
			}
			return null;
		}
		
		function getElement(){
			if ( members.el == null ){
				members.el = document.createElement('div');
				$(members.el).addClass('xatajax-application-toolgroup');
				
			}
			
			return members.el;
		}
		
		/**
		 * Listener responsible to deslecting the current tool when a new tool
		 * is selected.
		 */
		var selectionListener = new XataJax.ui.application.ToolListener({
			
			beforeSelect: function(event){
				var currSelection = getSelectedTool();
				if ( currSelection != this ){
					currSelection.deselect(event);
				}
			}
		});
		
		
	}
})();