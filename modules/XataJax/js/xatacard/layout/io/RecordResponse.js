//require <xatacard/layout/io/__init__.js>
//require <xatacard/layout/Record.js>
(function(){
	var $,
		Record;
	XataJax.ready(function(){
		$ = jQuery;
		Record = xatacard.layout.Record;
	});
	xatacard.layout.io.RecordResponse = RecordResponse;
	
	RecordResponse.SUCCESS=200;
	RecordResponse.ERROR=500;
	RecordResponse.SOME_ERRORS=501;
	RecordResponse.ALL_ERRORS=503;
	RecordResponse.ALL_SUCCESS=201;
	
	
	/**
	 * Encapsulates a response for a set of records.
	 * @constructor
	 */
	function RecordResponse(o){
	
		/**
		 * @type {array Record}
		 */
		var records=[];
		
		/**
		 * @type {array int}
		 */
		var codes=[];
		
		/**
		 * @type {array String}
		 */
		var messages=[];
		
		/**
		 * @type {int}
		 */
		var code=null;
		
		/**
		 * @type {String}
		 */
		var message=null;
		
		XataJax.publicAPI(this, {
			setRecords: setRecords,
			setCodes: setCodes,
			setMessages: setMessages,
			getResponseForRecord: getResponseForRecord,
			setMessage: setMessage,
			setCode: setCode,
			getMessage: getMessage,
			getCode: getCode,
			getRecords: getRecords
		});
		
		/**
		 * Sets the records in this response.
		 * @param {array Record} recs
		 * @returns {RecordResponse} Self for chaining.
		 */
		function setRecords(recs){
			records = recs;
			return this;
		}
		
		/**
		 * Sets the codes in this response.
		 * @param {array int}
		 * @returns {RecordResponse} Self for chaining.
		 */
		function setCodes(cds){
			codes = cds;
			return this;
		}
		
		/**
		 * Sets the messages in this response.
		 * @param {array String}
		 * @returns {RecordResponse} Self for chaining.
		 */
		function setMessages(msgs){
			messages = msgs;
			return this;
		}
		
		/**
		 * Returns another response object but only for a single record that was part of this
		 * response.  This is handy if you want to get the status of only a single record
		 * when the original request was for multiple records.
		 *
		 * @param {Record} record The record for which we want to get the response.
		 * @returns {RecordResponse} A new response object for one record only.
		 */
		function getResponseForRecord(record){
			var idx = records.indexOf(record);
			if ( idx == -1 ){
				throw new Exception({
					message: 'Attempt to get response for record that is not part of response.'
				});
			}
			var out = new RecordResponse();
			out.setCodes([codes[idx]]);
			out.setMessages([messages[idx]]);
			out.setRecords([records[idx]]);
			out.setMessage(messages[idx]);
			out.setCode(codes[idx]);
			return out;
		}
		
		/**
		 * Sets the summary message for this response.
		 * @param {String} str The string to set for the message.
		 * @returns {RecordResponse} Self for chaining.
		 */
		function setMessage(str){
			message = str;
		}
		
		/**
		 * Sets the summary code.  This will be one of 
		 * <li>RecordResponse.SOME_ERRORS</li>
		 * <li>RecordResponse.ALL_ERRORS</li>
		 * <li>RecordResponse.ALL_SUCCESS</li>
		 *
		 * @param {int} c
		 * @returns {RecordResponse} Self for chaining.
		 */
		function setCode(c){
			code = c;
			return this;
		}
		
		/**
		 * Gets the summary message for this response.  IF there were multiple records in this 
		 * response you may want to first get a response for the particular record you are interested
		 * in using the getResponseForRecord() method, then call getMessage() on that object.
		 *
		 * @returns {String} The summary message for this response.
		 */
		function getMessage(){
			
			return message;
		}
		
		/**
		 * Gets the summary code for this response.  If this response is for a single record
		 * only then this might be one of :
		 * <li>RecordResponse.ERROR</li>
		 * <li>RecordResponse.SUCCESS</li>
		 *
		 * Otherwise it will be one of:
		 * <li>RecordResponse.SOME_ERRORS</li>
		 * <li>RecordResponse.ALL_ERRORS</li>
		 * <li>RecordResponse.ALL_SUCCESS</li>
		 *
		 * @returns {int} The response code.
		 */
		function getCode(){
			if ( code == null ){
				var all_success = true;
				var all_errors = true;
				$.each(codes, function(){
					if ( this == RecordResponse.SUCCESS ){
						all_errors = false;
					}
					else if ( this == RecordResponse.ERROR ){
						all_success = false;
					}
				});
				if ( all_success ) return RecordResponse.ALL_SUCCESS;
				else if ( all_errors ) return RecordResponse.ALL_ERRORS;
				else return RecordResponse.SOME_ERRORS;
			}
			return code;
		}
		
		
		/**
		 * Gets the records that were part of this response.
		 * @returns {array Record} The records that were part of this response.
		 */
		function getRecords(){
			return records;
		}
		
		
		
		
		
	}
})();