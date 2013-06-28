//require <xatacard/layout/Schema.js>
//require <xatacard/layout/io/RecordRequest.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>

(function(){
	
	var $,
		Schema,
		PropertyChangeSupport,
		Subscribable,
		RecordRequest,
		RecordResponse,
		Exception;

	XataJax.ready(function(){
		$ = jQuery;
		Schema = xatacard.layout.Schema;
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		Subscribable = XataJax.beans.Subscribable;
		RecordRequest = xatacard.layout.io.RecordRequest;
		RecordResponse = xatacard.layout.io.RecordResponse;
		Exception = XataJax.Exception;
	});
	
	
	xatacard.layout.DataSource = DataSource;
	
	/**
	 * Represents a datasource from which records can be loaded and saved.  A datasource
	 * could be an XML document, a database, or something else.  Generally there is a one-to-one
	 * relationship between a DataSource class and a Schema class.  Therefore if you want to 
	 * create a new type of Schema, you're generally creating at least 4 classes:
	 *
	 * <li>A Schema</li>
	 * <li>A DataSource</li>
	 * <li>A Record</li>
	 * <li>A RecordSet</li>
	 *
	 * @constructor
	 * @event delete {RecordRequest} Fired when a delete request comes in.
	 * @event update {RecordRequest} Fired when an update request comes in.
	 * @event insert {RecordRequest} Fired when an insert request comes in.
	 * @event load {RecordRequest} Fired when a load request comes in.
	 *
	 */
	function DataSource(o){
	
		var schema;
		
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		XataJax.publicAPI(this, {
			sendRequest: sendRequest,
			updateRequest: updateRequest,
			deleteRequest: deleteRequest,
			loadRequest: loadRequest,
			insertRequest: insertRequest,
			setSchema: setSchema,
			getSchema: getSchema
		});
		
		
		/**
		 * Sends a request to the datasource.  This should ultimately result in the 
		 * request having it's response set and its fireComplete() method called.
		 *
		 * This method will defer to one of:
		 *
		 * <li>deleteRequest</li>
		 * <li>commitRequest</li>
		 * <li>loadRequest</li>
		 *
		 * depending on the value of the request's action.
		 *
		 * @param {RecordRequest} req The request that is being sent.
		 * @returns {DataSource} Self for chaining.
		 */
		function sendRequest(req){
			
			req.setResponse(new RecordResponse());
			var a = req.getAction();
			if ( a == 'insert' ){
				this.insertRequest(req);
			} else if ( a == 'load' ){
				this.loadRequest(req);
			} else if ( a == 'delete' ){
				this.deleteRequest(req);
			} else if ( a == 'update' ){
				this.updateRequest(req);
			} else {
				throw new Exception({
					message: 'Unrecognized action in request: '+a
				});
			}
			
			return this;
				
		}
		
		/**
		 * Handles a delete request.
		 * @param {RecordRequest} The request object.
		 * @returns {DataSource} Self for chaining.
		 *
		 */
		function deleteRequest(req){
			var self = this;
			var lastHandler = function(req){
				self.unbind('delete', lastHandler);
				req.fireComplete();
			}
			this.bind('delete', lastHandler);
			this.trigger('delete', req);
			//req.fireComplete();
			return this;
		}
		
		/**
		 * Handles an update request.
		 * @param {RecordRequest} req The request object.
		 * @returns {DataSource} Self for chaining.
		 */
		function updateRequest(req){
			var self = this;
			var lastHandler = function(req){
				self.unbind('update', lastHandler);
				req.fireComplete();
			}
			this.bind('update', lastHandler);
			this.trigger('update', req);
			
			return this;
		}
		
		/**
		 * Handles an insert request.
		 * @param {RecordRequest} req The request object.
		 * @returns {DataSource} Self for chaining.
		 */
		function insertRequest(req){
			var self = this;
			var lastHandler = function(req){
				self.unbind('insert', lastHandler);
				req.fireComplete();
			}
			this.bind('insert', lastHandler);
			this.trigger('insert', req);
			
			return this;
		}
		
		/**
		 * Handles a load request.
		 * @param {RecordRequest} req The request object.
		 * @returns {DataSource} Self for chaining.
		 */
		function loadRequest(req){
			var self = this;
			var lastHandler = function(req){
				self.unbind('load', lastHandler);
				req.fireComplete();
			}
			this.bind('load', lastHandler);
		
			this.trigger('load', req);
			
			return this;
		}
		
		
		
		
		
		function setSchema(s){
			if ( s != schema ){
				var old = schema;
				schema = s;
				this.firePropertyChange('schema', old, s);
			}
			return this;
		}
		
		function getSchema(){
			return schema;
		}
		
		
		
	
	}
})();