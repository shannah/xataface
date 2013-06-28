//require <xatacard/layout/SchemaFieldEvent.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
//require <xatacard/layout/Record.js>
//require <xatacard/layout/RecordEvent.js>
//require <xatacard/layout/SchemaSubSchemaEvent.js>

(function(){
	
	var $, 
		SchemaFieldEvent, 
		PropertyChangeSupport, 
		PropertyChangeListener, 
		Subscribable, 
		SchemaSubSchemaEvent,
		Record,
		RecordEvent;
		
		
	XataJax.ready(function(){
		$ = jQuery;
		SchemaFieldEvent = xatacard.layout.SchemaFieldEvent;
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		PropertyChangeListener = XataJax.beans.PropertyChangeListener;
		Subscribable = XataJax.beans.Subscribable;
		SchemaSubSchemaEvent = xatacard.layout.SchemaSubSchemaEvent;
		Record = xatacard.layout.Record;
		RecordEvent = xatacard.layout.RecordEvent;
	});
	
	xatacard.layout.Schema = Schema;
	
	
	
	/**
	 * Base class for a schema.  A schema is basically anything that contains 
	 * fields or subschemas.
	 *
	 * @constructor
	 *
	 * @event {SchemaFieldEvent} fieldAdded Fired when a field is added to the schema.
	 * @event {SchemaSubSchemaEvent} subSchemaAdded Fired when a subshcema was added.
	 * @event {SchemaFieldEvent} fieldRemoved Fired when a field is removed from the schema.
	 * @event {SchemaSubSchemaEvent} subSchemaRemoved Fired when a subschema is removed.
	 * @event {ValidationEvent} validate Fired when input is placed into a field.  Allows listeners
	 *		to validate the input.
	 * @event {RecordEvent} beforeSave Fired before a record is saved.
	 * @event {RecordEvent} afterSave Fired after a record is saved.
	 * @event {RecordEvent} beforeInsert Fired before a record is inserted.
	 * @event {RecordEvent} afterInsert Fired after a record is inserted.
	 * @event {RecordEvent} beforeUpdate Fired before a record is updated.
	 * @event {RecordEvent} afterUpdate Fired after a record is updated.
	 * @event {RecordEvent} beforeDelete Fired before a record is deleted.
	 * @event {RecordEvent} afterDelete Fired after a record is deleted.
	 * @event {RecordEvent} decorateRecord Fired when decorating a record.  Allows listeners to add 
	 *		their own custom decoration.
	 * @event {RecordEvent} undecorateRecord Fired when undecorating a record.  Allows listeners to 
	 *		remove any custom decoration they added in the decorateRecord hook.
	 *
	 */
	function Schema(o){
	
		
		

		var fields = {};
		var subSchemas = {};
		
		
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		XataJax.publicAPI(this, {
			decorateRecord: decorateRecord,
			undecorateRecord: undecorateRecord,
			createRecord: createRecord,
			newRecord: newRecord,
			addField: addField,
			removeField: removeField,
			getField: getField,
			addSubSchema: addSubSchema,
			removeSubSchema: removeSubSchema,
			getSubSchema: getSubSchema,
			validate: validate,
			beforeSave: beforeSave,
			afterSave: afterSave,
			beforeInsert: beforeInsert,
			afterInsert: afterInsert,
			beforeUpdate: beforeUpdate,
			afterUpdate: afterUpdate,
			beforeDelete: beforeDelete,
			afterDelete: afterDelete,
			beforeSetValue: beforeSetValue,
			afterSetValue: afterSetValue,
			containsField: containsField,
			getFields: getFields,
			getSubSchemas: getSubSchemas
		
		});
		
		
		
		
		var recordListener = new PropertyChangeListener({
			propertyChange: function(evt){
				// Perform stuff in here to handle when records of this
				// table are changed.
			
			}
		});
		
		var fieldListener = new PropertyChangeListener({
			propertyChange: function(evt){
				// Perform stuff in here to handle when fields are 
				// changed.  In particular we are interested to know
				// when the field's path is changed so we can update
				// our index.
				
				if ( evt.propertyName == 'path' ){
					//alert("Property change: "+evt.oldValue+' -> '+evt.newValue);
					var field = evt.source;
					delete fields[evt.oldValue];
					fields[evt.newValue] = field;
				}
			}
		});
		
		var subSchemaListener = new PropertyChangeListener({
		
			propertyChange: function(evt){
				//perform stuff here to handle when subschemas of this 
				// schema are changed.
				
				if ( evt.propertyName == 'path' ){
					var schema = evt.source;
					delete subSchemas[evt.oldValue];
					subSchemas[evt.newValue] = schema;
				}
			}
		});
		
		
		/**
		 * Creates a record based on this schema.  This is a bare bones method that essiantially
		 * just creates a new blank record (factory style).  This method is mainly there to 
		 * be overridden by subclasses.  Generally you should use newRecord() to create new
		 * records as this will also decorate the record before returning it.
		 *
		 * @param {mixed} o The parameter that is passed to the record's constructor.
		 * @returns {Record} The new record.
		 */
		function createRecord(o){
			//alert(Record);
			return new Record(o);
		}
		
		/**
		 * Creates a new record based on this schema, and decorates it.  I.e. this calls createRecord()
		 * then calls decorateRecord() on the resulting record before returning it.
		 * 
		 * @param {mixed} o The parameter that is passed to the record's constructor.
		 * @returns {Record} The new record.
		 */
		function newRecord(o){
			var record = this.createRecord(o);
			record.setSchema(this);
			//this.decorateRecord(record);
			return record;
		}
		
		/**
		 * Handles validation for a ValidationEvent.
		 *
		 * @param {ValidationEvent} evt The validation event that has occurred.
		 * @throws {Exception} Throws a validation exception if the validation fails.  Otherwise it is 
		 * assumed that validation has succeeded.
		 */
		function validate(evt){
		
			this.trigger('validate', evt);
			
		}
		
		/**
		 * Hook called before a record is saved.  This in turn fires off the beforeSave event to
		 * all listeners.
		 *
		 * @param {RecordEvent} evt The save event that is about to occur.
		 * @throws {Exception} Any of the listeners can thrown an exception to cancel the save.
		 */
		function beforeSave(evt){
			this.trigger('beforeSave', evt);
		}
		
		
		/**
		 * Hook called after a record is saved.  This in turn fires off the afterSave event to all 
		 * listeners.
		 * @param {RecordEvent} evt The save event that has occurred.
		 * @param {Exception} If there is a problem.  
		 */
		function afterSave(evt){
			this.trigger('afterSave', evt);
		}
		
		/**
		 * Hook called before a record is inserted. This in turn fires off the beforeInsert event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function beforeInsert(evt){
			this.trigger('beforeInsert', evt);
		}
		
		
		/**
		 * Hook called after a record is inserted. This in turn fires off the afterInsert event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function afterInsert(evt){
			this.trigger('afterInsert', evt);
		}
		
		
		/**
		 * Hook called before a record is updated. This in turn fires off the beforeUpdate event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function beforeUpdate(evt){
			this.trigger('beforeUpdate', evt);
		}
		
		/**
		 * Hook called after a record is updated. This in turn fires off the afterUpdate event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function afterUpdate(evt){
			this.trigger('afterUpdate', evt);
		}
		
		
		/**
		 * Hook called before a record is deleted. This in turn fires off the beforeDelete event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function beforeDelete(evt){
			this.trigger('beforeDelete', evt);
		}
		
		
		/**
		 * Hook called after a record is deleted. This in turn fires off the afterDelete event
		 * to all listeners.
		 *
		 * @param {RecordEvent} evt
		 */
		function afterDelete(evt){
			this.trigger('afterDelete', evt);
		}
		
		function beforeSetValue(evt){
			this.trigger('beforeSetValue', evt);
		}
		
		function afterSetValue(evt){
			this.trigger('afterSetValue', evt);
		}
		
		
		/**
		 * Decorates a record of this schema.  This is generally called on to decorate
		 * records created with createRecord, but you could use this on any record to
		 * essentially cast the record into this schema.
		 *
		 * @param {Record} record The record that is being decorated.
		 * @returns {Record} A reference to the same record that was input.  Alternative output method.
		 */
		function decorateRecord(record){
			var self = this;
			//record.setSchema(this);
			record.addPropertyChangeListener(recordListener);
			//alert('here');
			record.bind('validate.Schema', function(e){self.validate(e);});
			record.bind('beforeSave.Schema', function(e){self.beforeSave(e);});
			record.bind('afterSave.Schema', function(e){self.afterSave(e);});
			record.bind('beforeInsert.Schema', function(e){self.beforeInsert(e);});
			record.bind('afterInsert.Schema', function(e){self.afterInsert(e);});
			record.bind('beforeUpdate.Schema', function(e){self.beforeUpdate(e);});
			record.bind('afterUpdate.Schema', function(e){self.afterUpdate(e);});
			
			record.bind('beforeDelete.Schema', function(e){self.beforeDelete(e);});
			record.bind('afterDelete.Schema', function(e){self.afterDelete(e);});
			record.bind('beforeSetValue.Schema', function(e){self.beforeSetValue(e);});
			record.bind('afterSetValue.Schema', function(e){self.afterSetValue(e);});
			this.trigger('decorateRecord', new RecordEvent({
				record: record,
				action: 'decorate'
			}));
			//alert('there');
			return record;
			
		}
		
		
		function undecorateRecord(record){
			record.removePropertyChangeListener(recordListener);
			record.unbind('validate.Schema');
			record.unbind('beforeSave.Schema');
			record.unbind('afterSave.Schema');
			record.unbind('beforeInsert.Schema');
			record.unbind('afterInsert.Schema');
			record.unbind('beforeUpdate.Schema');
			record.unbind('afterUpdate.Schema');
			
			record.unbind('beforeDelete.Schema');
			record.unbind('afterDelete.Schema');
			record.unbind('afterSetValue.Schema');
			record.unbind('beforeSetValue.Schema');
			
			this.trigger('undecorateRecord', new RecordEvent({
				record: record,
				action: 'undecorate'
			}));
			
			return record;
			
		}
		
		/**
		 * Adds a field to this schema.
		 * @param {Field} fld The field to be added.
		 * @returns {Schema} Self for chaining.
		 */
		function addField(fld){
			if ( typeof(fields[fld.getPath()]) != 'undefined' ){
				var old = fields[fld.getPath()];
				if ( old == fld ) return this;
				else {
					this.removeField(old);
				}
			}
			fields[fld.getPath()] = fld;
			
			fld.addPropertyChangeListener(fieldListener);
			this.trigger('fieldAdded', new SchemaFieldEvent({
				schema: this,
				field: fld,
				action: 'add'
			}));
			return this;
		}
		
		/**
		 * Removes a field from this schema.
		 * @param {Field} fld The field to be removed.
		 * @returns {Schema} Self for chaining.
		 */
		function removeField(fld){
			delete fields[fld.getPath()];
			fld.removePropertyChangeListener(fieldListener);
			this.trigger('fieldRemoved', new SchemaFieldEvent({
				schema: this,
				field: fld,
				action: 'remove'
			}));
			return this;
		}
		
		/**
		 * Gets the field of this schema located at the specified path.
		 * @param {String} path The path to the field.
		 * @param {boolean} Whether to search subschemas.
		 */
		 function getField(path, searchSubschemas){
			if ( typeof(searchSubschemas) == 'undefined' ){
				searchSubschemas = false;
			}
			if ( typeof(fields[path]) != 'undefined' ){
				return fields[path];
			}
			if ( searchSubschemas ){
				for ( var p in subSchemas ){
					if ( path.indexOf(p) == 0 ){
						var field = subSchemas[p].getField(path.substr(p.length));
						if ( field != null ){
							return field;
						}
					}
				}
			}
			return null;
		}
		
		/**
		 * Checks whether this schema contains a field at this path.
		 *
		 * @param {String} path
		 * @returns {boolean}
		 */
		function containsField(path){
			return ( this.getField(path) != null );
		}
		
		/**
		 * Adds a subschema to this schema.
		 * @param {Schema} schema The subschema to add.
		 * @return {Schema} Self for chaining.
		 *
		 */
		function addSubSchema(schema){
			if ( typeof(subSchemas[schema.getPath()]) != 'undefined' ){
				var old = subSchemas[schema.getPath()];
				if ( old == schema ) return this;
				else this.removeSubSchema(old);
			}
			subSchemas[schema.getPath()] = schema;
			schema.addPropertyChangeListener(subSchemaListener);
			this.trigger('subSchemaAdded', new SchemaSubSchemaEvent({
				schema: this,
				subSchema: schema,
				action: 'add'
			}));
			return this;
		}
		
		
		/**
		 * Removes a subschema.
		 * @param {Schema} schema The schema to remove.
		 * @returns {Schema} Self for chaining.
		 */
		function removeSubSchema(schema){
			delete subSchemas[schema.getPath()];
			schema.removePropertyChangeListener(subSchemaListener);
			this.trigger('subSchemaAdded', new SchemaSubSchemaEvent({
				schema: this,
				subSchema: schema,
				action: 'remove'
			}));
			return this;
		}
		
		
		/**
		 * Returns the subschema at the specified path.
		 * @param {String} path The path to the subschema.
		 */
		function getSubSchema(path, searchSubschemas){
			if ( typeof(searchSubschemas) == 'undefined' ){
				searchSubschemas = false;
			}
			if ( typeof(subSchemas[path]) != 'undefined' ){
				return subSchemas[path];
			}
			if ( searchSubschemas ){
				for ( var p in subSchemas ){
					if ( path.indexOf(p) == 0 ){
						var out = subSchemas[p].getSubSchema(path.subtr(p.length));
						if ( out != null ){
							return out;
						}
					}
				}
			}
			return null;
		}
		
		/**
		 * Returns the fields of this schema as a keyed object.
		 * @returns {dict Field} The fields keyed on path.
		 */
		function getFields(){
			return $.extend({}, fields);
		}
		
		
		/**
		 * Returns the subschemas of this schema as a keyed object.
		 * @returns {dict Schema} The subschemas keyed on path.
		 */
		function getSubSchemas(){
			return $.extend({}, subSchemas);
		}
		
		
		
		
		
		
	}
	
})();