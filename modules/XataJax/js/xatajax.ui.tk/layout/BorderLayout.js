//require <xatajax.ui.tk/LayoutManager.js>
//require-css <xatajax.ui.tk/layout/BorderLayout.css>
(function(){
	var $ = jQuery;
	var LayoutManager = XataJax.ui.tk.LayoutManager;
	XataJax.ui.tk.layout.BorderLayout = BorderLayout;
	
	
	/**
	 * @constructor
	 */
	function BorderLayout(o){
		
		XataJax.extend(this, new LayoutManager(o));
		
		var publicAPI =  {
			install: install,
			uninstall: uninstall,
			update: update,
			childAdded: childAdded,
			childRemoved: childRemoved
			
		};
		
		XataJax.publicAPI(this, publicAPI);
		
		
		/**
		 * @param {XataJax.ui.tk.Component} c
		 */
		function install(c){
			this.getSuper(LayoutManager).install(c);
			
			$(c.getElement()).addClass('xatajax-ui-layout-borderlayout');
			this.getComponent().update();
		}
		
		/**
		 * @param {XataJax.ui.tk.Component} c
		 */
		function uninstall(c){
			this.getSuper(LayoutManager).uninstall(c);
			$(c.getElement()).removeClass('xatajax-ui-layout-borderlayout');
		}
		
		function update(){
			
			if ( this.getComponent() == null ) return;
			
			this.getSuper(LayoutManager).update();
			
			$(this.getComponent().getElement()).children().detach();
			
			var top = document.createElement('div');
			
			$(top)
				.addClass('xatajax-ui-component-slot')
				.addClass('xatajax-ui-layout-borderlayout-top')
				.addClass('xatajax-ui-layout-borderlayout-section')
				.data('xatajax-component-slot', 'top')
				;
			var topC = this.getComponent().get('top');
			if ( topC  ){
				top.appendChild(topC.getElement());
				
			}
			
			
			
			
			var left = document.createElement('div');
			$(left)
				.addClass('xatajax-ui-component-slot')
				.addClass('xatajax-ui-layout-borderlayout-left')
				.addClass('xatajax-ui-layout-borderlayout-section')
				.data('xatajax-component-slot', 'left')
				;
			var leftC = this.getComponent().get('left');
			if ( leftC ){
				left.appendChild(leftC.getElement());
	

			}
			
			
			
			var center = document.createElement('div');
			$(center)
				.addClass('xatajax-ui-component-slot')
				.addClass('xatajax-ui-layout-borderlayout-center')
				.addClass('xatajax-ui-layout-borderlayout-section')
				.data('xatajax-component-slot', 'center')
				;
			var centerC = this.getComponent().get('center');
			if ( centerC  ){
				center.appendChild(centerC.getElement());
			}
			
			var right = document.createElement('div');
			$(right)
				.addClass('xatajax-ui-component-slot')
				.addClass('xatajax-ui-layout-borderlayout-right')
				.addClass('xatajax-ui-layout-borderlayout-section')
				.data('xatajax-component-slot', 'right')
				;
			var rightC = this.getComponent().get('right');
			if ( rightC ){
				right.appendChild(rightC.getElement());
				
			}
			
			
			
			var bottom = document.createElement('div');
			$(bottom)
				.addClass('xatajax-ui-component-slot')
				.addClass('xatajax-ui-layout-borderlayout-bottom')
				.addClass('xatajax-ui-layout-borderlayout-section')
				.data('xatajax-component-slot', 'bottom')
				;
			var bottomC = this.getComponent().get('bottom');
			if ( bottomC ){
				bottom.appendChild(bottomC.getElement());

			}
			
			$(this.getComponent().getElement())
				.append(top)
				.append(left)
				.append(center)
				.append(right)
				.append(bottom);

			var y1 = 0;
			if ( topC ){
				y1 = $(topC.getElement()).height();
				//alert(topC.getContent());
			}
			$(top).css('height', y1);
			
			var x1 = 0;
			if ( leftC ){
				x1 = $(leftC.getElement()).width();
			}
			
			$(left).css({
				width: x1,
				top: y1
			});
			
			$(center).css({
				left: x1,
				top: y1
			});
			
			var y2 = 0;
			
			
			
			
			
			if ( rightC ){
			
				y2 = $(rightC.getElement()).width();
			}
			$(right).css({
				width: y2,
				top: x1
			});
			$(center).css('right', y2);
			
			var x2 = 0;
			
			
			
			
			
			if ( bottomC ){
			
				x2 = $(bottomC.getElement()).height();
			}
			$(bottom).css('height', x2);
			$(left).css('bottom', x2);
			$(center).css('bottom', x2);
			$(right).css('bottom', x2);
		
			
			$(this.getComponent().getElement()).css('height', $(window).height());
			$('body').css({
				margin: 0,
				padding: 0
			});
			
		}
		
		/**
		 * @param {XataJax.ui.tk.ComponentEvent} event
		 */
		function childAdded(event){
			
			this.getComponent().update();
		}
		
		
		/**
		 * @param {XataJax.ui.tk.ComponentEvent} event
		 */
		function childRemoved(event){
			this.getComponent().update();
		}
		
	
	}
})();