//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	var load = XataJax.load;
	var store = load('xataface.store');
	var model = load('xataface.model');
	store.ResultSet = ResultSet;
	
	/**
	 * @class
	 * @name ResultSet
	 * @memberOf xataface.store
	 * @description A loader for a list model.
	 */
	function ResultSet(/*Object*/ o){
		$.extend(this, o);
	}
	
	(function(){
		$.extend(ResultSet.prototype, {
			model : null,
			query : null,
			defaultSkip : 0,
			defaultLimit : 30,
			handleLoadResponse : handleLoadResponse,
			load : load,
			_load : _load,
			wrap : wrap,
			setModel : setModel
		});
		
		/**
		 * @function
		 * @memberOf xataface.store.ResultSet#
		 * @name setModel
		 * @description Sets the model object that is wrapped by this document.
		 * @param {xataface.model.ListModel} model The model object to be wrapped.
		 * @returns {xataface.store.ResultSet} Self for chaining.
		 */
		function setModel(/*ListModel*/ model){
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
		 * @name handleLoadResponse
		 * @memberOf xataface.store.ResultSet#
		 * @description Handles the server response to a load request.
		 * @param {Object} res The server response.
		 * @param {int} res.code The status code. 200 means success.  The actual
		 * 	possible codes (other than 200) will vary with server implementations.
		 * @returns {xataface.store.ResultSet} Self for chaining.
		 */
		function handleLoadResponse(res){
			var self = this;
			var model = this.model;
			if ( res.code == 200 ){
				var selectedHashes = [];
				if ( model.rows.selected ){
					$.each(model.rows.selected, function(k,v){
						selectedHashes.push(model.getRowHash(v));
					});
				}
				var rows = [];
				if ( res.metaData ){
					$.extend(this, res.metaData);
				}
				model.startRowUpdate();
				var rowsToRemove = model.rows.slice();
				$.each(rowsToRemove, function(k,v){
					model.remove(v);
				});
				
				$.each(res.rows, function(k,v){
					rows.push(self.wrap(v));
				});
				model.add(rows);
				model.selectByHash(selectedHashes);
				model.endRowUpdate();
				$(model).trigger('changed');
			}
			return this;
		}
		
		
		/**
		 * @function
		 * @name load
		 * @memberOf xataface.store.ResultSet#
		 * @description Loads or refreshes the result set by making a request
		 * 	to the server with the current query.  Upon completion (success or fail)
		 *  the provided callback function will be called.
		 *
		 * <h3>Events</h3>
		 * <p>This method will fire the following events.</p>
		 * <ol>
		 *  <li><em>loading</em> : Before the request is made, the <em>loading</em> 
		 * event will be fired with a {loading : true} parameter.</li>
		 *  <li><em>beforeLoad</em> : Fired before the request is made.</li>
		 *  <li><em>loading</em> : After the request is complete, the <em>loading</em>
		 *		event is triggered again with {loading : false} as a parameter.</li>
		 *  <li><em>afterLoad</em> : Fired after the request is complete (success or fail)
		 *		.</li>
		 * </ol>
		 *
		 * @param {xataface.store.ResultSet.LoadCallback} callback The function to be 
		 *	called upon completion (success or fail).
		 * @returns {xataface.store.ResultSet} Self for chaining.
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
		 * @name _load
		 * @memberOf xataface.store.ResultSet#
		 * @description The load funciton that is meant to be overridden by subclasses.
		 * This performs the actual request for loading content into the model.
		 * 
		 * @param {xataface.store.ResultSet.LoadCallback} callback The callback function
		 * 	to call on completion.
		 * @returns {xataface.store.ResultSet} Self for chaining.
		 */
		function _load(callback){
			
			var self = this;
			if ( typeof(self.getQuery) != 'undefined' ){
				this.query = self.getQuery();
			}
			var q = this.query;
			if ( q['-action'] == 'export_json' ){
				q['--var'] = 'rows';
			}
			$.get(DATAFACE_SITE_HREF, q, function(res){
				self.handleLoadResponse(res);
				this._loading = false;
				$(self).trigger('loading', {
					loading: false
				});
				$(self).trigger('afterLoad', res);
				callback.call(self, res);
				
				
			});
			return this;
		}
		
		
		/**
		 * @function 
		 * @name wrap
		 * @memberOf xataface.store.ResultSet#
		 * @description Wraps an object that is received from the server during the load 
		 * 	step.  Outputs the object as a model.  This method should usually be overriden
		 *  to return the appropriate model object.  The default implementation just 
		 *  returns the same object that it receives as a parameter.
		 * @param {Object} o The object to wrap.
		 * @returns {Object} The wrapper object.  (Default implementation just returns
		 * the same object.
		 */
		function wrap(/*Object*/ o){
			return o;
		}
		
	})();
})();