//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	var Model = model.Model;
	model.ListModel = ListModel;
	
	/**
	 * @const
	 * @name ROW_ADD_CHANGE
	 * @memberOf xataface.model.ListModel
	 * @description Constant used for change events (row adds and removal).
	 */
	ListModel.ROW_ADD_CHANGE = 1;
	
	/**
	 * @const
	 * @name ROW_REMOVE_CHANGE
	 * @memberOf xataface.model.ListModel
	 * @description Constant used for change events (row adds and removal).
	 */
	ListModel.ROW_REMOVE_CHANGE = 2;
	
	XataJax.subclass(ListModel, Model);
	
	/**
	 * @class
	 * @extends xataface.model.Model
	 * @name ListModel
	 * @memberOf xataface.model
	 * @description A list model.  Fires events to notify listeners when rows are
	 *	added or removed.
	 */
	function ListModel(/*Object*/ o){
		Model.call(this, o);
		if ( this.rows == null ) {
			this.rows = [];
		}
		this.selected = [];
		this.pendingChanges = [];
	}
	
	
	
	(function(){
		$.extend(ListModel.prototype, {
			selected : null,
			_inRowTransaction : false,
			_changedInRowTransaction : false,
			rows : null,
			pendingChanges : null,
			add : add,
			remove : remove,
			startRowUpdate : startRowUpdate,
			endRowUpdate : endRowUpdate,
			
			select : select,
			deselect : deselect,
			isSelected : isSelected,
			getSelectedRecord : getSelectedRecord,
			_inSelectionTransaction : false,
			_changedInSelectionTransaction : false,
			reverseChange : reverseChange,
			reverseChanges : reverseChanges,
			fireRowsChanged : fireRowsChanged,
			getRowHash : getRowHash,
			selectByHash : selectByHash
			
			
		});
		
		/**
		 * @function 
		 * @name reverseChange
		 * @memberOf xataface.model.ListModel#
		 * @description Reverses a change that was described in a change event.
		 * @param {xataface.model.ListModel.ChangeEvent} changeEvent Reverses a change 
		 *  specified by a change event. If the change event was to add a row, then this 
		 *  will remove it.  If the change was to remove a row, then this will add it.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function reverseChange(changeEvent){
			if ( changeEvent.changeType == ListModel.ROW_ADD_CHANGE ){
				this.remove(changeEvent.row);
			} else if ( changeEvent.changeType == ListModel.ROW_REMOVE_CHANGE ) {
				this.add(changeEvent.row, changeEvent.index);
			} else {
				throw "Unknown change event type";
			}
			return this;
		}
		
		/**
		 * @function
		 * @name reverseChanges
		 * @memberOf xataface.model.ListModel#
		 * @description Reverses a set of changes.
		 *
		 * @param {Array} events An array of change events to reverse.
		 *
		 * @see reverseChange()
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function reverseChanges(events){
			var inTransaction = this._inRowTransaction;
			if ( !inTransaction ){
				this.startRowUpdate();
			}
			events = events.slice();
			while ( events.length > 0 ){
				var event = events.pop();
				this.reverseChange(event);
			}
			if ( !inTransaction ){
				this.endRowUpdate();
			}
			
			return this;
			
		}
		
		/**
		 * @function
		 * @name add
		 * @memberOf xataface.model.ListModel#
		 * @description Adds an object to the list (optionally) at a specified index.
		 *
		 * <h3>Events</h3>
		 *
		 * <p>This will fire a "rowAdded" event with an xataface.model.ListModel.ChangeEvent
		 *	as a parameter.
		 *	</p>
		 *
		 * @param {Object} object The object to add to the list.
		 * @param {int} atIndex Optional index where the object should be added.  If this
		 *	is omitted, the object is just added to the end of the list. If it is 
		 *	specified, then it will be added at the specified position (0-based index), and
		 * 	bump all objects after it to be one later in the list (i.e. it doesn't over-
		 *	write the object that is currently at that index.  It just creates a new space
		 *	in the list.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function add(/*Object*/ object, atIndex){
			var self = this;
			
			if ( object instanceof Array ){
				var inTransaction = this._inRowTransaction;
				if ( !inTransaction ){
					this.startRowUpdate();
				}
				$.each(object, function(k,v){
					self.add(v);
				});
				if ( !inTransaction ){
					this.endRowUpdate();
				}
				return this;
			}
			
			var index = this.rows.indexOf(object);
			if ( index == -1 ){
				if ( typeof(atIndex) == 'undefined' ){
					this.rows.push(object);
					atIndex = this.rows.length-1;
				} else {
					this.rows.splice(atIndex, 0, object);
				}
				$(this).trigger('rowAdded', object);
				if ( this._inRowTransaction ){
					this.pendingChanges.push({
						row : object,
						changeType : ListModel.ROW_ADD_CHANGE,
						index : atIndex
					});
					this._changedInRowTransaction = true;
				} else {
					
					this.pendingChanges.push({
					
						row : object,
						changeType : ListModel.ROW_ADD_CHANGE,
						index : atIndex
					});
					this.fireRowsChanged();
				}
			} else {
				// do nothing.. already added
			}
			return this;
		}
		
		
		/**
		 * @function
		 * @name remove
		 * @memberOf xataface.model.ListModel#
		 * @description Removes an object from the list..
		 *
		 * <h3>Events</h3>
		 *
		 * <p>This will fire a "rowRemoved" event with an xataface.model.ListModel.ChangeEvent
		 *	as a parameter.
		 *	</p>
		 *
		 * @param {Object} object The object to remove from the list.
		 * 
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function remove(/*Object*/ object){
			var self = this;
			
			if ( object instanceof Array ){
				var inTransaction = this._inRowTransaction;
				if ( !inTransaction ){
					this.startRowUpdate();
				}
				$.each(object, function(k,v){
					self.remove(v);
				});
				if ( !inTransaction ){
					this.endRowUpdate();
				}
				return this;
			}
		
			var index = this.rows.indexOf(object);
			if ( index >= 0 ){
				this.rows.splice(index, 1);
				$(this).trigger('rowRemoved', object);
				
				if ( this.isSelected(object) ){
					this.deselect(object);
				}
				
				if ( this._inRowTransaction ){
					this.pendingChanges.push({
						row : object,
						changeType : ListModel.ROW_REMOVE_CHANGE,
						index : index
					});
					this._changedInRowTransaction = true;
				} else {
					
					this.pendingChanges.push({
					
						row : object,
						changeType : ListModel.ROW_ADD_CHANGE,
						index : atIndex
					});
					this.fireRowsChanged();
				}
			} else {
				// do nothing.. this row isn't in the list
				
			}
			return this;
		}
		
		/**
		 * @function
		 * @name fireRowsChanged
		 * @memberOf xataface.model.ListModel#
		 * @description Triggers a "rowsChanged" event including a list of 
		 *	all of the changeevents in the parameters.  The event parameter
		 * 	also includes an undo() function that can be called to undo the changes.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function fireRowsChanged(){
			var self = this;
			var changes = this.pendingChanges;
			this.pendingChanges =[];
			$(this).trigger('rowsChanged', {
				changes : changes,
				undo : function(){
					self.reverseChanges(changes);
				}
			});
			return this;
			
		}
		
		/**
		 * @function
		 * @name startRowUpdate
		 * @memberOf xataface.model.ListModel#
		 * @description Starts a transaction so that "rowsChanged" events won't be
		 * 	triggered until the subsequent endRowUpdate() call.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function startRowUpdate(){
			if ( this._inRowTransaction ){
				if ( this._changedInRowTransaction ){
					this.fireRowsChanged();
				}
			} else {
				this._inRowTransaction = true;
			}
			this._changedInRowTransaction = false;
			return this;
		}
		
		
		/**
		 * @function
		 * @name endRowUpdate
		 * @memberOf xataface.model.ListModel#
		 * @description Ends a transaction and triggers a "rowsChanged" event if 
		 * 	any rows have been added or removed since the last call to startRowUpdate()
		 *
		 * @returns {xataface.model.ListModel} Self for chaining.
		 */
		function endRowUpdate(){
			if ( this._inRowTransaction ){
				if ( this._changedInRowTransaction ){
					this._inRowTransaction = false;
					this._changedInRowTransaction = false;
					this.fireRowsChanged();
				} else {
					this._inRowTransaction = false;
				}
			}	
			return this;
		}
		
		/**
		 * @function
		 * @name isSelected
		 * @memberOf xataface.model.ListModel#
		 * @description Checks if an object/row is selected.
		 * @param {Object} o A row from the list that is being checked to see it is
		 * 	is currently selected.
		 * @returns {boolean} True if the object is selected. False otherwise.
		 */
		function isSelected(/*Object*/ o){
			return ( this.selected.indexOf(o) != -1 );
		}
		
		/**
		 * @function
		 * @name getSelectedRecord
		 * @memberOf xataface.model.ListModel#
		 * @description Returns either the first selected row in the list, or null
		 *	if there are no selections.
		 * 
		 * @returns {Object} The first selected row, or null if no selections.
		 *
		 */
		function getSelectedRecord(){
			if ( this.selected.length > 0 ) return this.selected[0];
			else return null;
		}
		
		/**
		 * @function
		 * @name deselect
		 * @memberOf xataface.model.ListModel#
		 * @description Deselects a row of the list.
		 * @param {Object} o The row to deselect.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 *
		 */
		function deselect(/*Object*/ o){
			var self = this;
			var index = this.selected.indexOf(o);
			if ( index >= 0 ){
				var oldSelected = this.selected.slice();
				this.selected.splice(index,1);
				$(this).trigger('selectionChanged', {
					oldValue : oldSelected,
					newValue : this.selected,
					undo : function(){
						self.select(o, false);
					}
				});
			}
			return this;
		}
		
		/**
		 * @function
		 * @name select
		 * @memberOf xataface.model.ListModel#
		 * @description Selects a row of the list.
		 * @param {Object} o The row to select.
		 * @returns {xataface.model.ListModel} Self for chaining.
		 *
		 */
		function select(/*Object*/ o, replace){
			var self = this;
			if ( typeof(replace) == 'undefined' ) replace = true;
			if ( o instanceof Array ){
				var isSuperSet = ($(o).not(this.selected).length == 0);
				var isSubSet = ($(this.selected).not(o).length == 0);
				if ( !(isSuperSet && isSubSet) ){
					
					// This selection is not the same as the old one
					var oldSelected = this.selected.slice();
					this.selected = o.slice();
					//console.log("About to trigger another selection changed in array");
					$(this).trigger('selectionChanged', {
						oldValue : oldSelected,
						newValue : this.selected,
						undo : function(){
							//console.log("Triggering array select section changed");
							self.select(oldSelected);
						}
					});
				}
				return this;
			}
			var index = this.selected.indexOf(o);
			if ( index == -1 ){
				var oldSelected = this.selected.slice();
				if ( replace ){
					this.selected = [];
				}
				this.selected.push(o);
				$(this).trigger('selectionChanged', {
					oldValue : oldSelected,
					newValue : this.selected,
					undo : function(){
						if ( !replace ){
							self.deselect(o);
						} else {
							self.select(oldSelected);
						}
					}
				});
			}
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.model.ListModel#
		 * @description Selects a row or list of rows by their hash value.
		 */
		function selectByHash(vals){
			var rows = [];
			var self = this;
			if ( !(vals instanceof Array) ){
				vals = [vals];
			}
			$.each(this.rows, function(k,row){
				if ( vals.indexOf(self.getRowHash(row)) != -1 ){
					rows.push(row);
				}
			});
			
			this.select(rows);
			return this;
			
		}
		
		/**
		 * @function
		 * @memberOf xataface.model.ListModel#
		 * @description Returns a unique hash for the row.  This is used
		 * for maintaining selected values between loads.
		 */
		function getRowHash(/*Object*/ rowModel){
			return rowModel;
		}
		
		
	})();
})();