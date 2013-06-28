/**
 * A toolbar for an application.
 *
 * This file is meant to be included in the xatjax.ui.application file
 * using the XataJax compiler.  It should not be referenced on its
 * own.
 * @depends xatajax.ui.application/ToolGroup.js
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created Feb. 5, 2011
 */
(function(){
	var $ = jQuery;
	
	XataJax.ui.application.ToolBar = ToolBar;
	
	function ToolBar(o){
		
		/**
		 * Publish the public api for this object.
		 */
		$.extend(this, {
			getToolGroup: getToolGroup,
			setToolGroup: setToolGroup,
			getElement: getElement
		});
	
		/**
		 * private member variables.
		 */
		var members = {
			toolGroup: null,
			el: null
		};
		
		setToolGroup(new XataJax.ui.application.ToolGroup());
		
		
		/**
		 * @returns XataJax.ui.application.ToolGroup
		 */
		function getToolGroup(){
			return members.toolGroup;
		}
		
		/**
		 * @param XataJax.ui.application.ToolGroup group
		 */
		function setToolGroup(group){
			if ( members.toolGroup != null ){
				$(members.toolGroup.getElement()).remove();
			}
			members.toolGroup = group;
			getToolGroupWrapper().appendChild(group.getElement());
		}
		
		/**
		 * Private convenience function to retrieve the DOM element wrapper for the
		 * toolgroup.
		 * @returns DOMElement
		 */
		function getToolGroupWrapper(){
			return $('.xatajax-application-toolbar-toolgroupwrapper', getElement()).get(0);
		}
		
		/**
		 * @returns DOMElement
		 */
		function getElement(){
			if ( members.el == null ){
				members.el = document.createElement('div');
				$(members.el).addClass('xatajax-application-toolbar');
				
				var toolGroupWrapper = document.createElement('div');
				$(toolGroupWrapper).addClass('xatajax-application-toolbar-toolgroupwrapper');
				members.el.appendChild(toolGroupWrapper);
				
			}
			return members.el;
		}
		
		
		
	}
	
})();