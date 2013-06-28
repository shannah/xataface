//require <xatacard/layout/__init__.js>
//require <xatacard/layout/Schema.js>

(function(){
	xatacard.layout.SchemaSubSchemaEvent = SchemaSubSchemaEvent;
	function SchemaSubSchemaEvent(o){
		
		/**
		 * The source RecordSet object that is originating this event.  This is always present.
		 * @type {RecordSet}
		 */
		var schema;
		
		/**
		 * The name of the action that was performed.  This may be different for different events.
		 * @type {String}
		 */
		var action;
		
		/**
		 * The schema that is being acted upon.  The child schema.
		 * @type {Schema}
		 */
		var subSchema;
		
		
		
		XataJax.publicAPI(this, {
		
			schema: null,
			action: null,
			subSchema: null
		});
	
	}
})();