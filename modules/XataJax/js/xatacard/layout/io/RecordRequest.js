//require <xatacard/layout/io/__init__.js>
//require <xatacard/layout/Record.js>
//require <xatacard/layout/RecordSet.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
(function(){
	var $,
		RecordResponse,
		Record,
		RecordSet,
		PropertyChangeSupport,
		Subscribable;
		
		
	XataJax.ready(function(){
		$ = jQuery;
		RecordResponse = xatacard.layout.io.RecordResponse;
		Record = xatacard.layout.Record;
		RecordSet = xatacard.layout.RecordSet;
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		Subscribable = XataJax.beans.Subscribable;
	});
	
	xatacard.layout.io.RecordRequest = RecordRequest;
	
	/**
	 * Encapsulates a request to a datasource for a record.
	 *
	 * @constructor
	 */
	function RecordRequest(o){
	
		var parameters = {};
	
		/**
		 * List of records involved in this request.
		 * @type {array Record}
		 */
		var records=[];
		
		/**
		 * The response to this request.
		 * @type {RecordResponse}
		 */
		var response=null;
		
		/**
		 * The action that this request is asking to be taken.
		 * @type {String}
		 */
		var action=null;
		
		var recordSet = null;
		
		var complete = false;
		
		
		XataJax.extend(this, new Subscribable(o));
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.publicAPI(this, {
			addRecord: addRecord,
			removeRecord: removeRecord,
			setResponse: setResponse,
			getResponse: getResponse,
			setAction: setAction,
			getAction: getAction,
			fireComplete: fireComplete,
			getRecordSet: getRecordSet,
			setRecordSet: setRecordSet,
			setParameter: setParameter,
			getParemeter: getParameter,
			getParameters: getParameters,
			isComplete: isComplete
		});
		
		function isComplete(){
			return complete;
		}
		
		
		
		/**
		 * Adds a record to this request.
		 * @param {Record} r The record to add.
		 * @returns {RecordRequest} Self for chaining.
		 */
		function addRecord(r){
			var idx = records.indexOf(r);
			if ( idx == -1 ){
				records.push(r);
			}
			return this;
		}
		
		/**
		 * Removes a record from this request.
		 * @param {Record} r The record to remove.
		 * @returns {RecordRequest} Self for chaining.
		 */
		function removeRecord(r){
			var idx = records.indexOf(r);
			if ( records.indexOf(r) != -1 ){
				records.splice(idx,1);
			}
			return this;
		}
		
		/**
		 * Sets the response of this request.
		 *
		 * @param {RecordResponse} r The response for this request.
		 * @returns {RecordRequest} Self for chaining.
		 */
		function setResponse(r){
			if ( r != response ){
				var old = response;
				response = r;
				this.firePropertyChange('response', old, r);
			}
			return this;
		}
		
		/**
		 * Gets the response for this request.
		 * @returns {RecordResponse}
		 */
		function getResponse(){
			return response;
		}
		
		/**
		 * Sets the name of the action to take for this request.  e.g. 'commit'
		 * @param {String} a The name of the action.
		 * @returns {RecordRequest} Self for chaining.
		 */
		function setAction(a){
			if ( a != action ){
				var old = action;
				action = a;
				this.firePropertyChange('action', old, a);
			}
			return this;
		}
		
		/**
		 * Returns the name of the action to perform as a result of this request.
		 * @returns {String} The name of the action to perform.
		 */
		function getAction(){
			return action;
		}
		
		/**
		 * Fires the 'complete' event, passing it the response object from this record.
		 *
		 * @returns {RecordRequest} Self for chaining.
		 */
		function fireComplete(){
			if ( !isComplete() ){
				this.trigger('complete', response);
				complete = true;
			}
			return this;
		}
		

		
		/**
		 * @param {RecordSet} rs 
		 * @returns {RecordRequest} Self for chaining.
		 */
		function setRecordSet(rs){
			if ( rs != recordSet ){
				var old = recordSet;
				recordSet = rs;
				this.firePropertyChange('recordSet', old, rs);
			}
			return this;
		}
		
		/**
		 * @returns {RecordSet}
		 */
		function getRecordSet(){
			return recordSet;
		}
		
		/**
		 * Sets a parameter.
		 * @param {String} key The parameter key.
		 * @param {mixed} val The value of the parameter.
		 */
		function setParameter(key,val){
			parameters[key] = val;
		}
		
		/**
		 * Gets a parameter.
		 * @param {String} key
		 * @returns {mixed}
		 */
		function getParameter(key){
			return parameters[key];
		}
		
		/**
		 * Gets the paramters that have been added to this request.
		 * @returns {Object}
		 */
		function getParameters(){
			return $.extend({}, parameters);
		}
		
	}
})();