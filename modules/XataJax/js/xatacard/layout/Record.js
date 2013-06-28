//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>
//require <xatajax.beans/PropertyChangeEvent.js>
//require <xatacard/layout/RecordSet.js>
//require <xatacard/layout/Schema.js>
//require <xatacard/layout/DataSource.js>
//require <xatacard/layout/io/RecordRequest.js>

(function(){
	var $,
		PropertyChangeSupport,
		PropertyChangeEvent,
		Subscribable,
		RecordSet,
		Schema,
		Exception,
		DataSource,
		RecordEvent,
		RecordRequest;
		
	XataJax.ready(function(){
		$ = jQuery;
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		PropertyChangeEvent = XataJax.beans.PropertyChangeEvent;
		Subscribable = XataJax.beans.Subscribable;
		RecordSet = xatacard.layout.RecordSet;
		Schema = xatacard.layout.Schema;
		Exception = XataJax.Exception;
		DataSource = xatacard.layout.DataSource;
		RecordEvent = xatacard.layout.RecordEvent;
		RecordRequest = xatacard.layout.io.RecordRequest;
	});
	
	xatacard.layout.Record = Record;
	Record.STATUS_NEW=0;
	Record.STATUS_EXISTING=1;
	Record.STATUS_CONDEMNED=2;
	Record.STATUS_DELETED=3;
	
	XataJax.errorcodes.PATH_NOT_IN_SCHEMA= XataJax.nextErrorCode();
	XataJax.errorcodes.NOT_BOUND_TO_DATASOURCE = XataJax.nextErrorCode();
	XataJax.errorcodes.INVALID_RECORD_STATUS = XataJax.nextErrorCode();
	//XataJax.errorcodes.COMPONENT_NOT_FOUND=XataJax.nextErrorCode();
	//XataJax.errorcodes.CHILD_COMPONENT_NOT_ALLOWED = XataJax.nextErrorCode();
	

	/**
	 * Represents a single record of a schema.  Generally a record constitutes a single row in a 
	 * table, but it is more abstract than that, as it could represent a tag in an XML file also.
	 *
	 * Records may contain fields and subrecords.  Subrecords would refer to related records 
	 * in terms of a relational database, or child tags in terms of an xml file.
	 *
	 * @constructor
	 *
	 * @event {PropertyChangeEvent} beforeSetValue Event fired before a value is set.
	 * @event {PropertyChangeEvent} afterSetValue Event fired after a value is set.
	 * @event {RecordResponse} afterCommit Fired after commit is successfully completed.
	 * @event {RecordResponse} failedCommit Fired after a commit attempt has failed.
	 * @event {RecordEvent} beforeCommit Fired just before a commit is attempted.
	 * @event {RecordEvent} beforeRollback Fired just before a rollback is performed.
	 * @event {RecordEvent} afterRollback Fired just after a rollback is performed.
	 *
	 */
	function Record(o){
	
		/**
		 * @type {DataSource}
		 */
		var dataSource =null;
	
		/**
		 * The schema that this record belongs to.
		 */
		var schema;
		
		/**
		 * Private object that stores all of the record values.
		 * @type {Object}
		 */
		var values = {};
		
		/**
		 * Flag to indicate whether the record has unsaved changes or not.
		 * @type {boolean}
		 */
		var dirty = false;
		
		/**
		 * Contains snapshot of last saved version of the record.
		 * @type {Object}
		 */
		var snapshot = null;
		
		/**
		 * Map of sub record sets that correspond with the schema's sub-schemas.  The keys
		 * are the paths to the subschema, and the values are RecordSets.
		 *
		 * @type {dict RecordSet}
		 */
		var subRecords = {};
		
		
		/**
		 * Flag to indicate that this is a new record.  When committed, it will
		 * be treated as a new record by the datasource if this flag is set.
		 * @type {int}
		 */
		var status = Record.STATUS_NEW;
		
		
		
		
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		XataJax.publicAPI(this, {
			getSchema: getSchema,
			setSchema: setSchema,
			setValue: setValue,
			getValue: getValue,
			prepareValue: prepareValue,
			getSubRecordSet: getSubRecordSet,
			createSubRecordSet: createSubRecordSet,
			setDirty: setDirty,
			isDirty: isDirty,
			valueChanged: valueChanged,
			commit: commit,
			handleCommitResponse: handleCommitResponse,
			makeSnapshot: makeSnapshot,
			getSnapshot: getSnapshot,
			rollback: rollback,
			setDataSource: setDataSource,
			getDataSource: getDataSource,
			getValues: getValues,
			setValues: setValues,
			isNew: isNew,
			isDeleted: isDeleted,
			isCondemned: isCondemned
		});
		
		
		/**
		 * Returns the schema of this record.
		 * @returns {Schema} The schema of this record.
		 */
		function getSchema(){
			return schema;
			
		}
		
		/**
		 * Sets the schema for this record.
		 *
		 * @param {Schema} s The schema.
		 * @returns {Record} Self for chaining.
		 */
		function setSchema(s){
			if ( s != schema ){
				var old = schema;
				if ( XataJax.instanceOf(old, Schema) ){
					old.undecorateRecord(this);
				}
				schema = s;
				if ( XataJax.instanceOf(schema, Schema) ){
					schema.decorateRecord(this);
				}
				this.firePropertyChange('schema', old, s);
			}
			return this;
		}
		
		
		/**
		 * Returns the 'new' flag, indicating whether this record is to be 
		 * treated as a new record upon commit.
		 *
		 * @returns {boolean} True if this record should be treated as a new
		 * 	record.
		 */
		function isNew(){
			return (status == Record.STATUS_NEW);
		}
		
		/**
		 * Returns the 'deleted' flag, indicating whether this record has
		 * been deleted.
		 *
		 * @returns {boolean} True if this record has already been deleted.
		 */
		function isDeleted(){
			return (status == Record.STATUS_DELETED);
		}
		
		
		
		
		
		/**
		 * The 'condemned' flag indicating that this record is marked for 
		 * deletion on the next commit.
		 *
		 * @returns {boolean} True if this record should be deleted by the datasource
		 *	when the record is committed.
		 */
		function isCondemned(){
			return (status == Record.STATUS_CONDEMNED);
		}
		
		/**
		 * Gets the current status of this record.  Possible values include:
		 *  Record.STATUS_NEW
		 *  Record.STATUS_EXISTING
		 *  Record.STATUS_CONDEMNED
		 *  Record.STATUS_DELETED
		 *
		 * @returns {int} The status code of the record.
		 * @see #isNew()
		 * @see #isDeleted()
		 * @see #isCondemned()
		 *
		 */
		function getStatus(){
			return status;
		}
		
		/**
		 * Sets the status of the record.  Possible values include:
		 *  Record.STATUS_NEW
		 *  Record.STATUS_EXISTING
		 *  Record.STATUS_CONDEMNED
		 *  Record.STATUS_DELETED
		 *
		 * @param {int} The status code of the record.
		 *
		 * @returns {Record} Self for chaining.
		 *
		 * @see #isNew()
		 * @see #isDeleted()
		 * @see #isCondemned()
		 */
		function setStatus(s){
			if ( s != status ){
				var old = status;
				status = s;
				this.firePropertyChange('status', old, s);
			}
			return this;
		}
		
		
		
		/**
		 * Returns the value of this record in the specified field (i.e. the field
		 * of this record such that its path = path
		 *
		 * @param {String} path The path to the field whose value we wish to retrieve.
		 * @returns {mixed} The value of the field in this record.
		 * @throws {Exception(code: XataJax.errorcodes.PATH_NOT_IN_SCHEMA)} If the schema does not 
		 *		contain a field at this path.
		 */
		function getValue(path){
		
			
			if ( !schema.containsField(path) ){
				throw new Exception({
					message: 'Attempt to set value in field at path '+path+' but the schema contains no such field.',
					code: XataJax.errorcodes.PATH_NOT_IN_SCHEMA
				});
			}
			return values[path];
		}
		
		
		/**
		 * Sets the value of a field in this record.
		 *
		 * @param {String} path The path to the field whose value we are setting.
		 * @param {mixed} value The value to set for this field.
		 * @throws {Exception(code: XataJax.errorcodes.PATH_NOT_IN_SCHEMA)} If the schema doesn't 
		 *		contain a field at this path.
		 *
		 * @returns {Record} Self for chaining.
		 */
		function setValue(path, value){
			if ( snapshot == null ){
				makeSnapshot();
			}
			if ( !schema.containsField(path) ){
				throw new Exception({
					message: 'Attempt to set value in field at path '+path+' but the schema contains no such field.',
					code: XataJax.errorcodes.PATH_NOT_IN_SCHEMA
				});
			}
			var old = this.getValue(path);
			value = this.prepareValue(path, value);
			if ( old != value){
				var event = new PropertyChangeEvent({
					source: this,
					propertyName: path,
					oldValue: old,
					newValue: value
				});
				this.trigger('beforeSetValue',event);
				
				
				
				values[path] = value;
				dirty = true;
				
				this.trigger('afterSetValue', event);
				
				
				
				
			}
			
			return this;
		}
		
		/**
		 * Sets multiple values at once.  This takes an object whose keys are the field paths, and whose corresponding
		 * values are the values to set for the fields.
		 *
		 * @param {Object} values The values to set.
		 * @returns {Record} Self for chaining.
		 */
		function setValues(values){
			var self = this;
			$.each(values, function(key,value){
				self.setValue(key, value);
			});
			return this;
		}
		
		/**
		 * Gets multiple values at once.
		 *
		 * @variant 1 Returns object with the values.  Object keys are the field paths, and their corresponding
		 *		values are the values.
		 * @returns {Object} The output values.  Contains all fields in schema.
		 * 
		 * @variant 2 Populates an input object with values for the keys provided in the object.
		 * @param {Object} outValues The object with keys to be populated.  This same object's values
		 * 	will be populated by this method.
		 * @returns {Object} Another reference to outValues so that this method can be called the same
		 * 	way as for the no-argument version.
		 */
		function getValues(outValues){
			var self = this;
			if ( typeof(outValues) == 'object' ){
				$.each(outValues, function(key,value){
					outValues[key] = self.getValue(key);
				});
				return values;
			} else {
				outValues = {};
				$.each(schema.getFields(), function(key, value){
					outValues[key] = self.getValue(key);
				});
				return outValues;
			}
		}
		
		
		/**
		 * Prepares the value for input into the given field.
		 *
		 * @param {String} path The path to the field that the value is being prepared for.
		 * @param {mixed} value The value that is being input.
		 * @returns {mixed} The transformed value that has been sanitized to be saved in the record
		 */
		function prepareValue(path, value){
			
			return value;
		}
		
		
		/**
		 * <p>Gets the subrecord set (corresponding to a subschema of the record's schema
		 * at the specified path.  This does not recursively search the subschemas.  We'll 
		 * want to make recursive access a distinct action because it is possible
		 * that we would need to load extra information from the server in order to 
		 * perform such a request.  This is because the sub-sub schemas will depend
		 * on which record of the sub-schema it branches from.</p>
		 *
		 * <p>Note that the record set that is returned won't contain any records until 
		 * they are loaded explicitly.</p>
		 *
		 * <p>Note 2: The if this is the first time that record set has been loaded,
		 *  the recordset will be created by the createSubRecordSet(path) method.  Subclasses
		 *  would probably override that method to be able to correctly produce the subrecord
		 * 	sets according to the data type.
		 * </p>
		 *
		 * @param {String} path The path to the subschema whose record set we are retrieving.
		 * @returns {RecordSet} The record set corresponding with that schema.
		 * @throws {Exception} If there is no subschema at that path.
		 */
		function getSubRecordSet(path){
			if ( !schema.containsSubSchema(path) ){
				throw new Exception({
					message: 'The schema does not contain a subschema at path '+path+' so you can not retrieve subrecords at that path.'
				});
			}
			
			if ( typeof(subRecords[path]) == 'undefined' ){
				 subRecords[path] = this.createSubRecordSet(path);
			}
			
			return subRecords[path];
		}
		
		/**
		 * Stub implementation that simply returns an empty record set.  This should 
		 * be overridden by subclasses to produce the correct type of record set.
		 *
		 * @param {String} path The path to the subSchema that this record set should
		 *  represent.
		 *
		 * @returns {RecordSet} The record set that is created for this subschema.
		 */
		function createSubRecordSet(path){
			return new RecordSet();
		}
		
		
		
		/**
		 * Returns the dirty status of this record.
		 * @returns {boolean} True if the record is dirty.
		 */
		function isDirty(){
			return dirty;
		}
		
		/**
		 * Sets the dirty status of this record.  If the record has been changed and the changes
		 * haven't been committed to the database, then the record is considered to be dirty.
		 *
		 * @param {boolean} d The dirty status.
		 * @returns {Record} Self for chaining.
		 */
		function setDirty(d){
			if ( d != dirty ){
				var old = dirty;
				dirty = d;
				this.firePropertyChange('dirty', old, d);
			}
			return this;
		}
		
		/**
		 * Makes a snapshot that can be rolled back to.
		 * @returns {Record} self for chaining.
		 */
		function makeSnapshot(){
			snapshot = $.extend({}, values);
		}
		
		/**
		 * Returns the snapshot that can be rolled back to.
		 * @returns {Object} Key value pairs of values in this record.
		 */
		function getSnapshot(){
			if ( !snapshot ) makeSnapshot();
			return snapshot;
		}
		
		/**
		 * Rolls changes back to the way they were when the last snapshot was taken.
		 * @returns {Record} Self for chaining.
		 */
		function rollback(){
			var event = new RecordEvent({
				record: this,
				action: 'rollback'
			});
			trigger('beforeRollback', event);
			values = $.extend({}, snapshot);
			dirty = false;
			trigger('afterRollback', event);
			return this;
		}
		
		/**
		 * Commits any changes to the datasource.
		 *
		 * @param {Function} callback A function to be called after the commit is complete.
		 * 	it should be passed a RecordCommitResponseEvent so that it knows what happened
		 *  with the commit.
		 * @param {boolean} deep If true then this will try to commit any changes to subrecord sets
		 *  also.
		 * @returns {Record} Self for chaining.
		 */
		function commit(callback, deep){
			var self = this;
			if ( typeof(deep) == 'undefined' ) deep = false;
			
			if ( dataSource == null ){
				throw new Exception({
					message: 'Cannot commit record because the data source is currently null.',
					code: XataJax.errorcodes.NOT_BOUND_TO_DATASOURCE
				});
			}
			
			if ( this.isDirty() ){
				var event = new RecordEvent({
					record: this,
					action: 'commit'
				});
				
				if ( this.isNew() || this.getStatus() == Record.STATUS_EXISTING ){
					this.trigger('beforeSave', new RecordEvent({
						record: this,
						action: 'save'
					}));
				}
				
				if ( this.isNew() ){
					this.trigger('beforeInsert', new RecordEvent({
						record: this,
						action: 'insert'
					}));
					event.action = 'insert';
				} else if ( this.isCondemned() ){
					this.trigger('beforeDelete', new RecordEvent({
						record: this,
						action: 'delete'
					}));
					event.action = 'delete';
				} else if ( this.getStatus() == Record.STATUS_EXISTING) {
					this.trigger('beforeUpdate', new RecordEvent({
						record: this,
						action: 'update'
					}));
					event.action = 'update';
				} else {
					throw new Exception({
						message: 'Cannot commit record because it has an invalid status: '+this.getStatus()+'.  Status must be one of Condemned, Existing, or New.',
						code: XataJax.errorcodes.INVALID_RECORD_STATUS
					});
				}
				this.trigger('beforeCommit', event);
				
				var request = new RecordRequest();
				request.bind('complete', function(e){self.handleCommitResponse(e);});
				request.bind('complete', callback);
				request.addRecord(this);
				request.setAction('commit');
				
				dataSource.sendRequest(request);
			}
			
			//trigger('afterCommit', event);
			return this;
		}
		
		/**
		 * @param {RecordResponse}
		 */
		function handleCommitResponse(response){
			var singleResponse = response.getResponseForRecord(this);
			if ( singleResponse.getCode() == RecordResponse.SUCCESS ){
				this.setDirty(false);
				makeSnapshot();
				
				
				
				this.trigger('afterCommit', response);
				var afterSave = false;
				if ( this.isNew() ){
					afterSave = true;
					this.setStatus(Record.STATUS_EXISTING);
					this.trigger('afterInsert', new RecordEvent({
						record: this,
						action: 'insert'
					}));
					
				} else if ( this.isCondemned() ){
					this.setStatus(Record.STATUS_DELETED);
					this.trigger('afterDelete', new RecordEvent({
						record: this,
						action: 'delete'
					
					}));
					
				} else {
					afterSave = true;
					this.trigger('afterUpdate', new RecordEvent({
						record: this,
						action: 'update'
					}));
				
				}
				
				if ( afterSave ){
					this.trigger('afterSave', new RecordEvent({
						record: this,
						action: 'save'
					}));
				}
				
			} else {
				this.trigger('failedCommit', response);
			}
		}
		
		
		
		/**
		 * Checks if the value in a field is changed.
		 * @param {String} path The path to the field that we are checking.
		 * @returns {boolean}
		 */
		function valueChanged(path){
			if ( snapshot == null ){
				makeSnapshot();
			}
			if ( !schema.containsField(path) ){
				throw new Exception({
					message: 'Attempt to check status of value in field at path '+path+' but the schema contains no such field.',
					code: XataJax.errorcodes.PATH_NOT_IN_SCHEMA
				});
			}
			
			return (snapshot[path] != values[path]);
		}
		
		
		/**
		 * Sets the data source from which this record was loaded.
		 *
		 * @param {DataSource} ds
		 *
		 * @returns {Record} Self for chaining.
		 */
		function setDataSource(ds){
			if ( ds != dataSource ){
				var old = dataSource;
				dataSource = ds;
				this.firePropertyChange('dataSource', old, ds);
			}
			return this;
		}
		
		/**
		 * Returns the datasource that this record is bound to.
		 * @returns {DataSource} The datasource.
		 */
		function getDataSource(){
			return dataSource;
		}
		
		
		
		
		
		
		
		

		
		
		
	}


})();