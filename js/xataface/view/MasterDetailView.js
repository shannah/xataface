//require <xataface/view/TableView.js>
//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	var view = XataJax.load('xataface.view');
	
	var ListModel = model.ListModel;
	var TableView = view.TableView;
	var View = view.View;
	
	XataJax.subclass(MasterDetailView, View);
	
	view.MasterDetailView = MasterDetailView;
	
	function MasterDetailView(/*Object*/ o){
		var self = this;
		View.call(this, o);
		
		// A handler to handle selection changes in the master list
		this.selectionChangedHandler = function(evt, data){
			
			self.updateSelections();
		}
		
		// We want to apply listeners to the model when we add it
		if ( this.model != null ){
			var model = this.model;
			this.model = null;
			this.setModel(model);
		}
	}
	
	(function(){
		$.extend(MasterDetailView.prototype, {
			masterView : null,
			detailView : null,
			model : null,
			_update : _update,
			updateSelections : updateSelections,
			setModel : setModel
		});
		
		
		
		function _update(){
			View.prototype._update.call(this);
			masterView.update();
			detailView.update();
			return this;
			
		}
		
		function setModel(/*ListModel*/ model){
			if ( model != this.model ){
				if ( this.model != null ){
					$(this.model).unbind('selectionChanged', this.selectionChangedHandler);
				}
				View.prototype.setModel.call(this, model);
				alert(this.model);
				if ( this.model != null ){
					alert('binding');
					$(this.model).bind('selectionChanged', this.selectionChangedHandler);
				}
			}
			return this;
			
		}
		
		function updateSelections(){
			var self = this;
			this.detailView.setModel(this.model.detailModel);
			this.detailView.update();
			return this;
		}
		
		
		
		
	
	})();
	
})();