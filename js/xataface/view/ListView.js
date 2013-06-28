//require <xataface/view/View.js>
//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	var view = XataJax.load('xataface.view');
	var View = view.View;
	var model = XataJax.load('xataface.model');
	var ListModel = model.ListModel;
	var Model = model.Model;
	var $m = Model.wrap;
	
	view.ListView = ListView;
	XataJax.subclass(ListView, View);
	
	function ListView(/*Object*/ o){
		View.call(this, o);
		var self = this;
		
		this.selectionChangedHandler = function(evt, data){
			self.updateSelections();
		};
		
		this.rowsChangedHandler = function(evt, data){
			self.update();
		}
		
		if ( this.template == null ){
			this.template = $(this.el).clone();
		} else {
			this.template = $(this.template);
			this.el = this.template.clone();
		}
		this.getRowElements().remove();
		
		if ( this.model != null ){
			var model = this.model;
			this.model = null;
			this.setModel(model);
		}
		
		
		this.getTemplateRowsWrapperElement().addClass('subview');
		this.getRowsWrapperElement().addClass('subview');
		
		this.rowCell = new View({});
		
	}
	
	(function(){
		$.extend(ListView.prototype, {
			_update : _update,
			updateRow : updateRow,
			_updateRow : _updateRow,
			template : null,
			decorateRow : decorateRow,
			_decorateRow : _decorateRow,
			undecorateRow : undecorateRow,
			_undecorateRow : _undecorateRow,
			setModel : setModel,
			updateSelections : updateSelections,
			_decorate : _decorate,
			_undecorate : _undecorate,
			getRowModel : getRowModel,
			setRowModel : setRowModel,
			getRowChangeHandler : getRowChangeHandler,
			setRowChangeHandler : setRowChangeHandler,
			createElement : createElement,
			getRowElements : getRowElements,
			getRowTemplateElement : getRowTemplateElement,
			getRowsWrapperElement : getRowsWrapperElement,
			getTemplateRowsWrapperElement : getTemplateRowsWrapperElement
			
		});
		
		
		function createElement(){
			try {
				return $(this.template).clone();
			} catch (e){
				return View.prototype.createElement.call(this);
			}
		}
		
		function setModel(/*ListModel*/ model){
			if ( model != this.model ){
				if ( this.model != null ){
					$(this.model).unbind('selectionChanged', this.selectionChangedHandler);
					$(this.model).unbind('rowsChanged', this.rowsChangedHandler);
				}
				View.prototype.setModel.call(this, model);
				if ( this.model != null ){
					if ( !(model instanceof ListModel) ){
						throw "ListView model must be instance of ListModel class";
					}
					$(this.model).bind('selectionChanged', this.selectionChangedHandler);
					$(this.model).bind('rowsChanged', this.rowsChangedHandler);
				}
			}
			return this;
			
		}
		
		function getRowElements(){
			return $(this.el).children('li');
		}
		
		function getRowTemplateElement(){
			return $(this.template).children('li');
		}
		
		function getRowsWrapperElement(){
			return $(this.el);
		}
		
		function getTemplateRowsWrapperElement(){
			return $(this.template);
		}
		
		function _update(){
			View.prototype._update.call(this);
			var self = this;
			this.getRowElements().each(function(){
				var rowModel = self.getRowModel(this);
				if ( rowModel ){
					self.undecorateRow(this, rowModel);
				}
				$(this).remove();
			});
			
			if ( this.model && this.model.rows ){
				$.each(this.model.rows, function(k, rowModel){
					var $rowEl = self.getRowTemplateElement().clone();
					self.decorateRow($rowEl, rowModel);
					self.updateRow($rowEl, rowModel);
					self.getRowsWrapperElement().append($rowEl);
				});
			}
			
			this.updateSelections();
			
			
			return this;
		}
		
		function updateSelections(){
			var self = this;
			var $rows = this.getRowElements();
			$rows.each(function(){
				var rowModel = self.getRowModel(this);
				if ( rowModel && self.model.isSelected(rowModel) ){
					$(this).addClass('selected');
				} else {
					$(this).removeClass('selected');
				}
			});
			return this;
		}
		
		function _updateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			var self = this;
			var model = $m(rowModel);
			var view = this.rowCell;
			view.el = rowView;
			view.model = model;
			view.update();
			//$('[data-kvc]:not(.subview [data-kvc])', rowView).each(function(){
			//	$(this).text(model.get($(this).attr('data-kvc')));
			//});
			
			
			return this;
		}
		
		function updateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			$(this).trigger('beforeUpdateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			
			this._updateRow(rowView, rowModel);
			
			$(this).trigger('afterUpdateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			return this;
		}
		
		function _decorateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			var self = this;
			
			
			function rowChangeHandler(){
				self.updateRow(rowView, rowModel);
			}
			
			this.setRowModel(rowView, rowModel);
			this.setRowChangeHandler(rowView, rowChangeHandler);
			$(rowView).click(function(evt){
                                if ( evt.metaKey ){
                                    if ( self.model.isSelected(rowModel) ){
                                        self.model.deselect(rowModel);
                                    } else {
                                        self.model.select(rowModel, false);
                                    }
                                } else {
                                    self.model.select(rowModel);
                                }
				$(self).trigger('actionPerformed', {
				    view : rowView,
				    model : rowModel
				});
			});
				
			
			return this;
			
		}
		
		function decorateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			var self = this;
			
			$(this).trigger('beforeDecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			
			this._decorateRow(rowView, rowModel);
				
			$(this).trigger('afterDecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});	
			return this;
			
		}
		
		function _undecorateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			$(this).trigger('beforeUndecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			this.setRowChangeHandler(rowView, null);
			this.setRowModel(rowView, null);
			
			$(this).trigger('afterUndecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			return this;
		}
		
		function undecorateRow(/*HTMLElement*/ rowView, /*Object*/ rowModel){
			$(this).trigger('beforeUndecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			
			this._undecorateRow(rowView, rowModel);
			
			$(this).trigger('afterUndecorateRow', {
				rowView : rowView,
				rowModel : rowModel
			});
			return this;
		}
		
		function getRowModel(rowView){
			return $(rowView).data('xataface.view.ListView.rowModel');
		}
		
		function setRowModel(rowView, rowModel){
			if ( rowModel == null ){
				$(rowView).removeData('xataface.view.ListView.rowModel');
			} else {
				$(rowView).data('xataface.view.ListView.rowModel', rowModel);
			}
			return this;
		}
		
		function getRowChangeHandler(rowView){
			return $(rowView).data('xataface.view.ListView.rowChangeHandler');
		}
		
		function setRowChangeHandler(rowView, changeHandlerFunc){
			var currentHandler = this.getRowChangeHandler();
			var rowModel = this.getRowModel(rowView);
			if ( currentHandler ){
				$(rowModel).unbind('changed', currentHandler);
			}
			if (changeHandlerFunc == null ){
				
				$(rowView).removeData('xataface.view.ListView.rowChangeHandler');
			} else {
				$(rowView).data('xataface.view.ListView.rowChangeHandler', changeHandlerFunc);
				$(rowModel).bind('changed', changeHandlerFunc);
			}
			return this;
		}
		
		function _decorate(){
			View.prototype._decorate.call(this);
			
			return this;
		}
		
		function _undecorate(){
			View.prototype._undecorate.call(this);
			
			return this;
		}
		
	})();

})();