//require <xataface/view/ListView.js>
(function(){
	var $ = jQuery;
	var ListView = xataface.view.ListView;
	var View = xataface.view.View;
	xataface.view.SelectListView = SelectListView;
	
	XataJax.subclass(SelectListView, ListView);
	
	/**
	 * @class
	 * @memberOf xataface.view
	 * @extends xataface.view.ListView
	 * @description A list view for a <select> element.
	 */
	function SelectListView(/*Object*/ o){
		var self = this;
		this._selectChangeListener = function(){
			var sel = [];
			$(self.getRowElements()).filter('[selected]').each(function(){
				sel.push(this);
			});
			var selModels = [];
			$.each(sel, function(k,v){
				selModels.push(self.getRowModel(v));
			});
			self.model.select(selModels);
		}
		ListView.call(this, o);
		
		
		
	}
	
	(function(){
		
		$.extend(SelectListView.prototype, {
			getRowElements : getRowElements,
			getRowTemplateElement : getRowTemplateElement,
			getRowsWrapperElement : getRowsWrapperElement,
			getTemplateRowsWrapperElement : getTemplateRowsWrapperElement,
			_decorate : _decorate,
			_undecorate : _undecorate,
			_update : _update,
			updateSelections : updateSelections,
			_currentSelectedValue : null
			
		});
		
		function getRowElements(){
			return $('option', this.el);
		}
		
		
		
		function getRowTemplateElement(){
			return $(this.template).children('option');
		}
		
		function getRowsWrapperElement(){
			return $(this.el);
		}
		
		function getTemplateRowsWrapperElement(){
			return $(this.template);
		}
		
		function _decorate(){
			ListView.prototype._decorate.call(this);
			$(this.el).prepend('<option value="">Select...</option>');
			
			
			$(this.el).bind('change', this._selectChangeListener);
			this.updateSelections();
			return this;
		}
		
		function _undecorate(){
			ListView.prototype._undecorate.call(this);
			$(this.el).unbind('change', this._selectChangeListener);
			
			return this;
		}
		
		function _update(){
			ListView.prototype._update.call(this);
			
			return this;
		}
		
		function updateSelections(){
			ListView.prototype.updateSelections(this);
			$(this.getRowElements()).each(function(){
				if ( $(this).hasClass('selected') ){
					$(this).attr('selected', true);
				} else {
					$(this).removeAttr('selected');
				}
			});
			
			return this;
		}
		
		
		
	})();
})();