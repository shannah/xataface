//require <xataface/view/ListView.js>
(function(){
	var $ = jQuery;
	var ListView = xataface.view.ListView;
	xataface.view.TableView = TableView;
	
	XataJax.subclass(TableView, ListView);
	
	function TableView(/*Object*/ o){
		ListView.call(this, o);
	}
	
	(function(){
		
		$.extend(TableView.prototype, {
			getRowElements : getRowElements,
			getRowTemplateElement : getRowTemplateElement,
			getRowsWrapperElement : getRowsWrapperElement,
			getTemplateRowsWrapperElement : getTemplateRowsWrapperElement
			
		});
		
		function getRowElements(){
			return $(this.el).children('tbody').children('tr');
		}
		
		function getRowTemplateElement(){
			return $(this.template).children('tbody').children('tr');
		}
		
		function getRowsWrapperElement(){
			return $(this.el).children('tbody')
		}
		
		function getTemplateRowsWrapperElement(){
			return $('tbody', this.template);
		}
		
	})();
})();