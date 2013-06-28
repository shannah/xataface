//require <xatacard/layout/__init__.js>
//require <xatacard/layout/Schema.js>
(function(){

	var $, Schema;
	
	XataJax.ready(function(){
		$ = jQuery;
		Schema = xatacard.layout.Schema;
	});
	
	xatacard.layout.SchemaEvent = SchemaEvent;

	function SchemaEvent(o){
	
	
		/**
		 * The schema from which the event originated.
		 * @type {Schema}
		 */
		var schema;
		
		/**
		 * The name of the action that was performed.
		 * @type {String}
		 */
		var action;
		
		XataJax.publicAPI(this, {
			schema: null,
			action: null
		});
		
		if ( typeof(o) == 'object' ){
			$.extend(this, o);
		}
		
	
	}
})();