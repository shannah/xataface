//require <xatacard/layout/SchemaEvent.js>
//require <xatacard/layout/Field.js>
(function(){
	var $, SchemaEvent, Field, Schema;
	XataJax.ready(function(){
		$ = jQuery;
		SchemaEvent = xatacard.layout.SchemaEvent;
		Field = xatacard.layout.Field;
		Schema = xatacard.layout.Schema;
	});
	
	xatacard.layout.SchemaFieldEvent = SchemaFieldEvent;
	
	function SchemaFieldEvent(o){
		
		/**
		 * The field that this event is related to.
		 * @type {Field}
		 */
		var field;
		
		/**
		 * The schema originating this event.
		 * @type {Schema}
		 */
		var schema;
		
		
		/**
		 * The name of the action that is being performed. e.g. remove, or add
		 * @type {String}
		 */
		var action;
		
		XataJax.extend(this, new SchemaEvent(o));
		XataJax.publicAPI(this, {
			field: null,
			schema: null,
			action: null
		});
		
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
		
		
	}
	
})();