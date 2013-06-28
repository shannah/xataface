//require <xatacard/layout/__init__.js>
//require <xatacard/layout/RecordSet.js>
//require <xatacard/layout/Record.js>
(function(){
	xatacard.layout.RecordSetEvent = RecordSetEvent;
	
	function RecordSetEvent(o){
		
		/**
		 * The source RecordSet object that is originating this event.  This is always present.
		 * @type {RecordSet}
		 */
		var recordSet;
		
		/**
		 * The name of the action that was performed.  This may be different for different events.
		 * @type {String}
		 */
		var action;
		
		/**
		 * Optional array of records involved in this event.  If the event is notifying of records
		 * being added or removed from the record set then this array will be populated with the
		 * records that are either being added or removed.
		 * @type {Record}
		 */
		var records;
		
		/**
		 * Optional start index for records being added or removed.  This is the absolute index
		 * within the record set where records are being added or removed from.
		 * @type {int}
		 */
		var startIndex;
		
		/**
		 * Optional end index for records being added or removed.  This is the absolute index
		 * within the record set where records are being added or removed.
		 * @type {int}
		 */
		var endIndex;
		
		XataJax.publicAPI(this, {
		
			recordSet: null,
			action: null,
			records: null,
			startIndex: null,
			endIndex: null
		});
	
	}
})();