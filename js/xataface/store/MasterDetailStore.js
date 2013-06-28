//require <xataface/model/MasterDetailModel.js>
(function(){
	var $ = jQuery;
	var load = XataJax.load;
	var model = load('xataface.model');
	var store = load('xataface.store');
	var MasterDetailModel = model.MasterDetailModel;
	store.MasterDetailStore = MasterDetailStore;
	 
	 
	/**
	 * @class
	 * @name MasterDetailStore
	 * @memberOf xataface.store
	 * @description Serves as the glue between a MasterDetailModel and a data store.  This
	 *	class has a Document that manages the life-cycle of the detail model, and a 
	 *	resultSet to manage the loading/refreshing of the list model.  This helps to 
	 * 	manage some usability problems like:
	 *	- If a different record is selected while there are unsaved changes to the
	 * 	  current detail model.  
	 *	- Loading the detail model from the data store
	 */ 
	function MasterDetailStore(/*Object*/ o){
		$.extend(this, o);
		var self = this;
		
		
		this.selectionChangeHandler = function(evt, data){
			self.handleSelectionChanged(data);
		};
		
		if ( this.model != null ){
			var m = this.model;
			this.model = null;
			this.setModel(m);
		}
		
		
		
	}
	
	(function(){
		$.extend(MasterDetailStore.prototype, {
			model : null,
			document : null,
			resultSet : null,
			setModel : setModel,
			decorateModel : decorateModel,
			undecorateModel : undecorateModel,
			handleSelectionChanged : handleSelectionChanged,
			getQueryFor : getQueryFor,
			selectRow : selectRow
			
		});
		
		function setModel(/*MasterDetailModel*/ model){
			if ( model != this.model ){
				if ( this.model != null ){
					this.undecorateModel(this.model);
				}
				this.model = model;
				if ( this.model != null ){
					this.decorateModel(this.model);
				}
			}
		}
		
		function getQueryFor(/*Object*/ o){
			return null;
		}
		
		/**
		 * @function
		 * @name decorateMasterDetailModel
		 * @memberOf xataface.store.MasterDetailStore#
		 * @description Adds the selectionChanged listener to the master detail model
		 *  so that the store can respond to selection changes and possibly override
		 *	them.
		 * @param {xataface.model.MasterDetailModel} model The model to decorate.
		 * @returns {xataface.store.MasterDetailStore} Self for chaining.
		 */
		function decorateModel(/*MasterDetailModel*/ model){
			$(model).bind('selectionChanged', this.selectionChangeHandler);
			return this;
		}
		
		
		/**
		 * @function
		 * @name undecorateMasterDetailModel
		 * @memberOf xataface.store.MasterDetailStore#
		 * @description Removes the selectionChanged listener from the master detail model.
		 * @param {xataface.model.MasterDetailModel} model The model to undecorate.
		 * @returns {xataface.store.MasterDetailStore} Self for chaining.
		 */
		function undecorateModel(/*MasterDetailModel*/ model){
			$(model).unbind('selectionChanged', this.selectionChangeHandler);
		}
		
		
		/**
		 * @function
		 * @name handleSelectionChanged
		 * @memberOf xataface.store.MasterDetailStore#
		 * @description Handles the selection changed event from the MasterDetailModel.
		 * <p>
		 *	This method will try to close the current document's model and, if successful,
		 *	to set the new model to the document and open it.  If it either fails to 
		 *	close the old document or fails to open the new one, it will set the old
		 *	model back to the document, and call undo the change in the MasterDetailModel
		 *
		 * @param {xataface.model.MasterDetailModel} model The model to undecorate.
		 * @returns {xataface.store.MasterDetailStore} Self for chaining.
		 */
		function handleSelectionChanged(data){
			var self = this;
			
			function cancel(){
				self.document.setModel(data.oldDetailModel);
				data.undo.call(self);
			}
			
			self.document.close({
				onSuccess : function(){
					self.document.setModel(data.newDetailModel);
					self.document.query = self.getQueryFor(data.newDetailModel);
					if ( self.document.query != null ){
						self.document.open({
							onFail : cancel,
							onCancel : cancel
						});
					} else {
						// WE don't do anything
					}
				},
				onCancel : cancel	 
			});
			
		}
		
		function selectRow(/*Object*/ o, callback){
			var self = this;
			function afterLoad(res){
				$(self.document).unbind('afterLoad', afterLoad);
				callback.call(self, res);
			}
			$(this.document).bind('afterLoad', afterLoad);
			$(this.model.listModel).bind('selectionChanged', function(){
				
			});
			this.model.listModel.select(o);
			return this;
		}
		
	})();
})();