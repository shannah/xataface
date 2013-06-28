//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	var load = XataJax.load;
	var model = load('xataface.model');
	var Model = model.Model;
	model.MasterDetailModel = MasterDetailModel;
	/**
	 * @class
	 * @name MasterDetailModel
	 * @memberOf xataface.model
	 * @description Serves as glue between a ListModel and a DetailModel to allow
	 * users to select rows from the list, and have the detail model updated accordingly.
	 */
	function MasterDetailModel(/*Object*/ o){
		$.extend(this, o);
		var self = this;
		
		
		/**
		 * @function
		 * @name selectionChangedHandler
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description The event handler for the "selectionChanged" event
		 * of the list model.
		 *
		 * <h3>Events</h3>
		 * <p>
		 *   This fires a "selectionChanged" event with a SelectionChangedEvent
		 *	 object as a parameter.
		 * </p>
		 */
		this.selectionChangedHandler = function(evt, data){
			
			var selectedRecord = self.listModel.getSelectedRecord();
			var detailModel = self.getDetailModelFor(selectedRecord);
			if ( detailModel != self.detailModel ){
				var oldModel = self.detailModel;
				var newModel = detailModel;
				
				self.setDetailModel(detailModel);
				$(self).trigger('selectionChanged', {
					newDetailModel : newModel,
					oldDetailModel : oldModel,
					oldSelectedRecords : data.oldValue,
					newSelectedRecords : data.newValue,
					undo : function(){
						//self.setDetailModel(oldModel);
						if ( typeof(data.undo) == 'function' ){
							data.undo.call();
						}
					}
				});
			
			}
		}
		
		if ( this.listModel != null ){
			var m = this.listModel;
			this.listModel = null;
			this.setListModel(m);
		}
	}
	
	/**
	 * @class
	 * @name SelectionChangedEvent
	 * @memberOf xataface.model.MasterDetailModel
	 * @description An event object that is passed as data to "selectionChanged"
	 * 	events in the MasterDetailModel class.
	 *
	 * @property {xataface.model.Model} newDetailModel The new detail model after the
	 *	change.
	 * @property {xataface.model.Model} oldDetailModel The old detail model from before
	 *	the change.
	 * @property {Array} oldSelectedRecords The array of selected records in the list
	 *	before the change was made.
	 * @property {Array} newSelectedRecords The array of selected records in the list
	 *	after the change was made.
	 *
	 * @see xataface.model.MasterDetailModel#selectionChangedHandler
	 */
	 
	/**
	 * @function
	 * @name undo
	 * @memberOf xataface.model.MasterDetailModel.SelectionChangedEvent#
	 * @description Reverses all of the changes in the "selectionChanged" event.
	 * @returns {void}
	 */
	 
	 
	
	(function(){
		$.extend(MasterDetailModel.prototype, {
			detailModel : null,
			listModel : null,
			setListModel : setListModel,
			setDetailModel : setDetailModel,
			decorateListModel : decorateListModel,
			undecorateListModel : undecorateListModel,
			decorateDetailModel : decorateDetailModel,
			undecorateDetailModel : undecorateDetailModel,
			getDetailModelFor : getDetailModelFor
			
		});
		
		/**
		 * @function
		 * @name setListModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Sets the list model that is used as the master.
		 * @param {xataface.model.ListModel} model The list model to set.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function setListModel(/*ListModel*/ model){
			if ( this.listModel != model ){
				if ( this.listModel != null ){
					this.undecorateListModel(this.listModel);
				}
				var oldValue = this.listModel;
				this.listModel = model;
				if ( this.listModel != null ){
					this.decorateListModel(this.listModel);
				}
				$(this).trigger('listModelChanged', {
					oldValue : oldValue,
					newValue : this.listModel,
					undo : function(){
						self.setListModel(oldValue);
					}
				});
			}
			return this;
		}
		
		/**
		 * @function
		 * @name setDetailModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Sets the model used as the detail model.
		 * @param {xataface.model.Model} model The model.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function setDetailModel(/*Model*/ model){
			var self = this;
			if ( this.detailModel != model ){
				if ( this.detailModel != null ){
					this.undecorateDetailModel(this.detailModel);
				}
				var oldValue = this.detailModel;
				this.detailModel = model;
				if ( this.detailModel != null ){
					this.decorateDetailModel(this.detailModel);
				}
				$(this).trigger('detailModelChanged', {
					oldValue : oldValue,
					newValue : this.detailModel,
					undo : function(){
						self.setDetailModel(oldValue);
					}
				});
			}
			return this;
		}
		
		/**
		 * @function
		 * @name decorateListModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Adds listeners to a list model.
		 * @param {xataface.model.ListModel} model The list model to decorate.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function decorateListModel(/*ListModel*/ model){
			$(model).bind('selectionChanged', this.selectionChangedHandler);
			return this;
		}
		
		/**
		 * @function
		 * @name undecorateListModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Removes listeners from a list model.
		 * @param {xataface.model.ListModel} model The list model to undecorate.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function undecorateListModel(/*ListModel*/ model){
			$(model).unbind('selectionChanged', this.selectionChangedHandler);
			return this;
		}
		
		/**
		 * @function
		 * @name decorateDetailModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Adds listeners to a detail model.
		 * @param {xataface.model.Model} model The model to decorate.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function decorateDetailModel(/*Model*/ model){
			return this;
		}
		
		/**
		 * @function
		 * @name undecorateDetailModel
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Removes listeners from a detail model.
		 * @param {xataface.model.Model} model The model to undecorate.
		 * @returns {xataface.model.MasterDetailModel} Self for chaining.
		 */
		function undecorateDetailModel(/*Model*/ model){
			return this;
		}
		
		/**
		 * @function
		 * @name getDetailModelFor
		 * @memberOf xataface.model.MasterDetailModel#
		 * @description Builds a detail model to wrap model from the list model.  This
		 * 	allows you to use a more advanced model for the detail than for the rows
		 *	of the list view.  Whenever the selection of the list changes, this method
		 *	is used to build a new detail model.  The default implementation just returns
		 * 	the same model.
		 *
		 * @param {Object} model The row from the list to convert into a detail model.
		 * @returns {xataface.model.Model} The detail model.
		 */
		function getDetailModelFor(/*Object*/ object){
			return object;
		}
		
	})();
})();