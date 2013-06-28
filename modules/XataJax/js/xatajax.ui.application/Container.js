(function(){


	var $ = jQuery;
	
	XataJax.ui.application.Container = Container;
	
	function Container(o){
	
		$.extend(this, {
			setToolBar: setToolBar,
			getToolBar: getToolBar,
			getElement: getElement,
			
		});
		
		var members = {
			toolBar: null,
			rootPane: null,
			el: null
		};
		
		setToolBar(new XataJax.ui.application.ToolBar());
		
		
		
		function setToolBar(tb){
			if ( members.toolBar != null ) $(members.toolBar.getElement()).remove();
			members.toolBar = tb;
			getToolBarWrapper().appendChild(tb.getElement());
		}
		
		function getToolBar(){
			return members.toolBar;
		}
		
		function getElement(){
			if ( members.el == null ){
				members.el = document.createElement('div');
				$(members.el).addClass('xatajax-application-container');
				
				var toolbarWrapper = document.createElement('div');
				$(toolbarWrapper).addClass('xatajax-application-container-toolbarwrapper');
				members.el.appendChild(toolbarWrapper);
				
				var rootPaneWrapper = document.createElement('div');
				$(rootPaneWrapper).addClass('xatajax-application-container-rootpanewrapper');
				members.el.appendChild(rootPaneWrapper);
			}
			
			return members.el;
		}
		
		function getToolBarWrapper(){
			return $('.xatajax-application-container-toolbarwrapper', getElement()).get(0);
		}
		
		function getRootPaneWrapper(){
			return $('.xatajax-application-container-rootpanewrapper', getElement()).get(0);
		}
	}
	
})();