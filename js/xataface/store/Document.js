//require <xataface/model/Model.js>
//require <xatajax.util.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	var $m = model.Model.wrap;
	var store = XataJax.load('xataface.store');
	var extractCallback = XataJax.util.extractCallback;
	store.Document = Document;
	
	Document.CLOSED = 0x1;
	Document.OPEN = 0x2;
	Document.SAVING = 0x4;
	Document.LOADING = 0x8;
	Document.DEFAULT_STATUS = 0x10;
	Document.DIRTY = 0x20;
	
	/**
	 * @class
	 * @name Document
	 * @memberOf xataface.store
	 * @description A document wrapper for a Model object that handles the loading,
	 * saving, and closing of the model.  This can be hooked into by any UI to implement
	 * open and close dialogs.
	 */
	function Document(/*Object*/ o){
		$.extend(this, o);
	}

	
	(function(){
		$.extend(Document.prototype, {
			model : null,
			query : null,
			saveQuery : null,
			setModel : setModel,
			open : open,
			openPrompt : openPrompt,
			close : close,
			closePrompt : closePrompt,
			savePrompt: savePrompt,
			getStatus : getStatus,
			load : load,
			_load : _load,
			handleLoadResponse : handleLoadResponse,
			save : save,
			_save : _save,
			_saveRequest : _saveRequest,
			saveRequest : null,
			handleSaveResponse : handleSaveResponse,
			
			_openCloseStatus : Document.CLOSED,
			getSaveQuery : getSaveQuery,
			getLoadQuery : getLoadQuery,
			setOpenCloseStatus : setOpenCloseStatus,
			
			getStatus : getStatus
			
		});
		
		/**
		 * @function
		 * @memberOf xataface.store.Document#
		 * @name setModel
		 * @description Sets the model object that is wrapped by this document.
		 * @param {xataface.model.Model} model The model object to be wrapped.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function setModel(/*Model*/ model){
			if ( this.model != model ){
				if ( this.model != null ){
					//Do some cleanup
				}
				this.model = model;
				if ( this.model != null ){
					// Do some attaching
				}
			}
			return this;
		}
		
		
		/**
		 * @function
		 * @memberOf xataface.store.Document#
		 * @name load
		 * @description Loads the document using the query that is currently set.
		 * It is usually better to use the open() method as this ties into the 
		 * status of the document to call close the current model first and save
		 * it if necessary.
		 */
		function load(callback){
			$(this).trigger('beforeLoad');
			$(this).trigger('loading', {
				loading: true
			});
			this._loading = true;
			this._load(callback);
			
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.store.Document#
		 * @name _load
		 * @description Method intended to be extended by subclasses
		 * performing the load() function on this document.  This method
		 * is called by the load() method so that the load method itself can call events
		 * before and after _load() is called.
		 */
		function _load(callback){
			var cb = extractCallback(callback);
			//console.log(cb);
			var self = this;
			var q = this.getLoadQuery();
			if ( q != null && q['-action'] == 'export_json' ){
				q['--var'] = 'data';
				q['--single'] = 1;
				q['-mode'] = 'view';
			}
			$.get(DATAFACE_SITE_HREF, q, function(res){
				self.handleLoadResponse(res);
				self._loading = false;
				$(self).trigger('loading', {
					loading: false
				});
				if ( res.code == 200 ){
					cb.onSuccess.call(self, res);
				} else {
					cb.onFail.call(self, res);
				}
				
				$(self).trigger('afterLoad', res);
			});
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.store.Document#
		 * @name handleLoadResponse
		 * @description Handles the server response from a request to load the
		 * current model's data.
		 * @param res The response object that was returned by the server.
		 *
		 */
		function handleLoadResponse(res){
			
			var self = this;
			var model = this.model;
			if ( res.code == 200 ){
				$m(model)
					.set(res.data)
					.setDirty(false);
				this.setOpenCloseStatus(Document.OPEN);
				
			} else {
				$(self).trigger('error', {
					message : res.message
				});
			}
			return this;
		}
		
		/**
		 * @function
		 * @memberOf xataface.store.Document#
		 * @name setOpenCloseStatus
		 * @description Sets the open/closed status of this document.
		 * @param {int} status The status to set.  This should be one of
		 *	 - Document.OPEN
		 *	 - Document.CLOSED
		 */
		function setOpenCloseStatus(status){
			var self = this;
			if ( this._openCloseStatus != status ){
				var oldValue = this._openCloseStatus;
				var newValue = status;
				this._openCloseStatus = status;
				
				$(this).trigger('openCloseStatusChanged', {
					oldValue : oldValue,
					newValue : newValue,
					undo : function(){
						self.setOpenCloseStatus(oldValue);
					}
				});
			}
			return this;
				
				
		}
		
		/**
		 * @function
		 * @name save
		 * @memberOf xataface.store.Document#
		 * @description Saves the current document.  This will check the status
		 * of the document and try to save it if necessary.  If the saveQuery is
		 * null, this will prompt the user with a savePrompt to get the query
		 * then save it.
		 * @param {xataface.store.Document.SaveCallback} callback function or object.
		 * @param {xataface.store.Document.SaveCallback.onSuccess} callback.onSuccess 
		 *		The function to be called on a successful save.
		 * @param {xataface.store.Document.SaveCallback.onCancel} callback.onCancel
		 *		The function to be called if the save is cancelled by the user.
		 * @param {xataface.store.Document.SaveCallback.onFail} callback.onFail
		 * 		The function to be called if the save operation fails.
		 *
		 * @returns {xataface.store.Document} Self for chaining.
		 *
		 */
		function save(callback){
			var self = this;
			var cb = extractCallback(callback);
			var q = this.getSaveQuery();
			if ( q == null ){
				this.savePrompt({
					save : function(query){
						if ( query == null ){
							
							cb.onCancel.call(self);
						} else {
							self.saveQuery = query;
							self.save(callback);
						}
					},
					cancel : function(){
						cb.onCancel.call(self);
					}
				});
			} else {
		
				$(this).trigger('beforeSave');
				$(this).trigger('saving', {
					saving : true
				});
				this._saving = true;
				this._save(callback);
			}
			return this;
		}
		
		/**
		 * @function
		 * @name savePrompt
		 * @memberOf xataface.store.Document#
		 * @description Opens a save dialog to allow the user to select some
		 * save parameters.
		 * @param {xataface.store.Document.SavePromptCallback} o The object with parameters.
		 * @param {xataface.store.Document.SavePromptCallback.save} o.save
		 * 		The function to be called if the user selects "Save" in the dialog.
		 * @param {xataface.store.Document.SavePromptCallback.cancel} o.cancel
		 * 		The function to be called if the user selects "Cancel" in the dialog.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function savePrompt(/*Object*/ o){
			o.save.call(this, this.saveQuery||{});
			return this;
		}
		
		/**
		 * @function
		 * @name _save
		 * @memberOf xataface.store.Document#
		 * @description saves the current model.  This is called by the save() method
		 * 	so that the save() method can handle all of the pre and post save event handling
		 *  making it easier to override just the save functionality in subclasses. 
		 *  Subclasses wishing to override how the save process works, should override
		 * this method instead of the save() method.
		 *
		* @param {xataface.store.Document.SaveCallback} callback function or object.
		 * @param {xataface.store.Document.SaveCallback.onSuccess} callback.onSuccess 
		 *		The function to be called on a successful save.
		 * @param {xataface.store.Document.SaveCallback.onCancel} callback.onCancel
		 *		The function to be called if the save is cancelled by the user.
		 * @param {xataface.store.Document.SaveCallback.onFail} callback.onFail
		 * 		The function to be called if the save operation fails.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function _save(callback){
			var cb = extractCallback(callback);
			var self = this;
			var model = this.model;
			function handleError(res){
				$(self).trigger('error', {
					message : 'Failed to save due to a server error.'
				});
				cb.onFail.call(self, res);
			}
			
			function handleComplete(res){
				self._saving = false;
				$(self).trigger('saving', {
					saving : false
				});
				$(self).trigger('afterSave', res);
			}
			
			function handleSuccess(res){
				self.handleSaveResponse(res);
				if ( res.code == 200 ){
					cb.onSuccess.call(self, res);
				} else {
					cb.onFail.call(self, res);
				}
			}
			
			this._saveRequest(handleSuccess, handleError, handleComplete);
			
			return this;
		}
		
		
		/**
		 * @function
		 * @name _saveRequest
		 * @memberOf xataface.store.Document#
		 * @description Sends a save request to the datasource.  The default 
		 * 	implementation makes an ajax request, but this method is designed
		 *  to be overridden if you don't want to use AJAX.
		 *
		 * @param {Function} success Callback function called on success.
		 * @param {Function} error Callback function called on error.
		 * @param {Function} complete Callback function called on complete.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function _saveRequest(success, error, complete){
			if ( this.saveRequest != null ){
				
				return this.saveRequest(success, error, complete);
			}
			var self = this;
			$.post(DATAFACE_SITE_HREF, this.getSaveQuery(), function(res){
				success.call(self, res);
			})
			.error(function(res){
				error.call(res);
			})
			.complete(function(res){
				complete.call(res);
			})
			
			;
			return this;
		}
		
		/**
		 * @function
		 * @name handleSaveResponse
		 * @memberOf xataface.store.Document#
		 * @description Handles the response to a save request.
		 * @param {xataface.store.Document.SaveResponse}  res The save response
		 */
		function handleSaveResponse(res){
			var model = this.model;
			var self = this;
			if ( res.code == 200 ){
				$m(model)
					.set(res.data)
					.setDirty(false);
				
			} else {
				$(self).trigger('error', {
					message: res.message
				});
			}
		}
		
		/**
		 * @function
		 * @name getSaveQuery
		 * @memberOf xataface.store.Document#
		 * @description Returns the query used to save this document.
		 * @returns {Object} The query used to save this document.
		 */
		function getSaveQuery(){
			return this.saveQuery;
		}
		
		/**
		 * @function
		 * @name getLoadQuery
		 * @memberOf xataface.store.Document#
		 * @description Returns the query used to load this document.
		 * @returns {Object} The query used to load this document.
		 */
		function getLoadQuery(){
			return this.query;
		}
		
		
		/**
		 * @function
		 * @name getStatus
		 * @memberOf xataface.store.Document#
		 * description Returns a status mask that can be used to determine
		 * all of the status flags relating to this document.
		 * The status flags include:
		 *  - Document.OPEN : Indicates the document is currently opened.
		 *  - Document.CLOSED : Indicates the document is currently closed.
		 *  - Document.LOADING : Indicates the document is currently loading.
		 *  - Document.SAVING : Indicates that a save is currently in progress.
		 *  - Document.DIRTY : Indicates that the model of this document has changed
		 *		since load or last save.
		 *
		 * @example
		 *  if ( doc.getStaus() & Document.LOADING ){
		 *      // The document is currently loading.
		 *  }
		 * 
		 * @returns {int} Mask that can be checked for all of the status flags.
		 */
		function getStatus(){
			var status = 0x0;
			
			if ( this._loading ){
				status = status | Document.LOADING;
			}
			if ( this._saving ){
				status = status | Document.SAVING;
			}
			
			if ( $m(this.model).dirty ){
				status = status | Document.DIRTY;
			}
			
			status = status | this._openCloseStatus;
			
			
			
			
			return status;
		}
		
		/**
		 * @function
		 * @name closePrompt
		 * @memberOf xataface.store.Document#
		 * @description Displays a prompt to the user to see if they want to 
		 * close the document.
		 * @param {xataface.store.Document.ClosePromptCallback} o The callback object
		 * 		that specifies what to do in response to the dialog.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function closePrompt(o){
			if ( o.yes && o.yes.call ){
				o.yes.call(this);
			}
			return this;
		}
		
		/**
		 * @function
		 * @name close
		 * @memberOf xataface.store.Document#
		 * @description Closes the document, but (as long as force is not true) goes 
		 *	through the necessary and familiar steps to ensure that unsaved changes
		 *  have been saved etc.. before completing.
		 * @param {xataface.store.Document.CloseCallback} callback The callback object
		 *	that contains function that are to be called after successful, cancelled, or
		 *	failed close.
		 * @param {boolean} force Optional flag to indicate whether the close should 
		 *	occur despite unsaved changes.  The default value is false.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function close(callback, force){
			if ( typeof(force) == 'undefined' ) force = false;
			if ( typeof(callback) == 'undefined' ){
				callback = function(){};
			}
			var self = this;
			var cb = extractCallback(callback);
			if ( this.model == null ){
				cb.onSuccess.call(this);
				return this;
			}
			var status = this.getStatus();
			
			if ( status & Document.CLOSED ){
				cb.onSuccess.call(this);
				return this;
			}
			if ( !force ){
				
				
				if ( status & Document.SAVING ){

					// The document is currently saving
					function afterSave(res){
						$(self).unbind('afterSave', afterSave);
						self.close(callback);
					}
					$(this).bind('afterSave', afterSave);
				} else if ( status & Document.LOADING ){
					function afterLoad(res){
						$(self).unbind('afterLoad', afterLoad);
						self.close(callback);
					}
					$(this).bind('afterLoad', afterLoad);
				} else if ( status & Document.DIRTY ){
					
					this.closePrompt({
						yes : function(){
							self.save(function(res){
								self.close(callback);
							});
						},
						cancel : function(){
							cb.onCancel.call(self);
						},
						no : function(){
							self.close(callback, true);
						}
						
					});
					
				} else {
					self.close(callback, true);
				}
			} else {
				this.setOpenCloseStatus(Document.CLOSED);
				$(this).trigger('statusChanged');
				cb.onSuccess.call(this);
				$(this).trigger('afterClose');
			}
			return this;
		}
		
		/**
		 * @function
		 * @name open
		 * @memberOf xataface.store.Document#
		 * @description Opens the document with the current load query.  If the load 
		 * 	query is null (or force is true), then it will first prompt the user
		 *  with an "open" dialog to select the entity that the wish to open.
		 * @param {xataface.store.Document.OpenCallback} callback The callback object 
		 *	containing functions to be called on success, failure, or user cancelling.
		 * @param {boolean} force Optional flag to "force" the open dialog to show up.
		 *  If this is true then the open dialog will be shown to allow the user to
		 * 	select an entity even if the query is already set.  Otherwise, it will try
		 *  to use the existing query to silently load the file if the query is not-null.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function open(callback, force){
			var cb = extractCallback(callback);
			if ( typeof(force) == 'undefined' ){
				force = false;
			}
				
			var self = this;
			if ( this.query == null || force ){
				this.openPrompt({
					open : function(query){
						this.query = query;
						self.open(callback);
					},
					cancel : function(){
						cb.onCancel.call(self);
					}
				}); 
			} else {
				var status = this.getStatus();
				if ( status & Document.OPEN ){
					this.close(function(){
						self.open(callback);
					});
				} else {
					this.load({
						onSuccess : function(res){
							cb.onSuccess.call(this, res);
							$(self).trigger('afterOpen');
						},
						onFail : cb.onFail,
						onCancel : cb.onCancel
						
					});
				}
			}
		}
		
		
		/**
		 * @function
		 * @name openPrompt 
		 * @memberOf xataface.store.Document#
		 * @description Prompts the user to select the model that they wish to open
		 * in this document.  This is meant to be overridden by instances or subclasses
		 * to provide a concrete implementation.
		 * 
		 * @param {xataface.store.Document.OpenPromptCallback} callback The callback object
		 * 	that includes functions to be called to handle the user's action with the prompt.
		 * @returns {xataface.store.Document} Self for chaining.
		 */
		function openPrompt(callback){
			
			if ( callback && callback.open && callback.open.call ){
				callback.open.call(this, {});
			}
			
			return this;
		}
		
		
		
		
	})();
})();