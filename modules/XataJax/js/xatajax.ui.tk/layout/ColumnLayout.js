//require <xatajax.ui.tk/LayoutManager.js>
//require <xatajax.ui.tk/ComponentEvent.js>
//require <xatajax.ui.tk/ComponentListener.js>
//require-css <xatajax.ui.tk/layout/ColumnLayout.css>
(function(){
	var $ = jQuery;
	var LayoutManager = XataJax.ui.tk.LayoutManager;
	var ComponentListener = XataJax.ui.tk.ComponentListener;
	var ComponentEvent = XataJax.ui.tk.ComponentEvent;
	var Component = XataJax.ui.tk.Component;
	
	XataJax.ui.tk.layout.ColumnLayout = ColumnLayout;
	
	
	/**
	 * @constructor
	 */
	function ColumnLayout(o){
		
		
		XataJax.extend(this, new LayoutManager(o));
		
		var publicAPI = {
			install: install,
			uninstall: uninstall,
			update: update,
			childAdded: childAdded,
			childRemoved: childRemoved
			
		};
		
		XataJax.publicAPI(this, publicAPI );
		
		
		/**
		 * @param {Component} c
		 */
		function install(c){
			this.getSuper(LayoutManager).install(c);
			
			$(c.getElement()).addClass('xatajax-ui-layout-columnlayout');
			this.getComponent().update();
		}
		
		/**
		 * @param {Component} c
		 */
		function uninstall(c){
			this.getSuper(LayoutManager).uninstall(c);
			$(c.getElement()).removeClass('xatajax-ui-layout-columnlayout');
		}
		
		/**
		 * Refreshes the layout.
		 */
		function update(){
			
			if ( this.getComponent() == null ) return;
			
			this.getSuper(LayoutManager).update();
			
			$(this.getComponent().getElement()).children().detach();
			
			var table = document.createElement('table');
			
			var tr = document.createElement('tr');
			table.appendChild(tr);
			$(table).addClass('xatajax-ui-layout-columnlayout-table');
			var i=1;
			$.each(this.getComponent().getChildComponents(), function(){
				
				var td = document.createElement('td');
				$(td)
					.addClass('xatajax-ui-component-slot')
					.addClass('xatajax-ui-layout-columnlayout-table-column-'+(i++))
					.data('xatajax-component-slot', i-1)
					;
				tr.appendChild(td);
				td.appendChild(this.getElement());
			});
			this.getComponent().getElement().appendChild(table);
			
		}
		
		
		/**
		 * @interface ComponentListener
		 * @param {ComponentEvent} event
		 */
		function childAdded(event){
			
			this.getComponent().update();
		}
		
		
		/**
		 * @interface ComponentListener
		 * @param {ComponentEvent} event
		 */
		function childRemoved(event){
			this.getComponent().update();
		}
		
	
	}
})();