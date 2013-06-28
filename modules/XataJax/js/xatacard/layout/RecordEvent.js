//require <xatacard/layout/__init__.js>
//require <xatacard/layout/Record.js>
(function(){
	
	var $=jQuery,
		Record;
		
	XataJax.ready(function(){
		Record = xatacard.layout.Record;
	});

	xatacard.layout.RecordEvent = RecordEvent;
	xatacard.layout.RecordEvent.RecordEventResult = RecordEventResult;
	
	

	/**
	 * An event to wrap a record.  Can optionally contain results of the 
	 * action.
	 */
	function RecordEvent(o){
	
		/**
		 * The record that is subject of this event.
		 * @type {Record}
		 */
		var record;
		
		/**
		 * The action that has taken place on this record.
		 * @type {String}
		 */
		var action;
		
		/**
		 * The result of this event.
		 * @type {RecordEventResult}
		 */
		var result;
		
		XataJax.publicAPI(this, {
			record: record,
			action: action,
			setResult: setResult,
			getResult: getResult
		});
		
		if ( typeof(o) == 'object' ){
			
			$.extend(this, o);
		
		}
		
		/**
		 * Sets the result of this action.
		 * @param {RecordEventResult} r The result of this event.
		 *
		 */
		function setResult(r){
			result = r;
		}
		
		/**
		 * Gets the result of this action.
		 * @returns {RecordEventResult} The result of this event.
		 */
		function getResult(){
			return result;
		}
	}
	
	
	function RecordEventResult(o){
		
		var success;
		var message;
		var exception;
		
	}
})();