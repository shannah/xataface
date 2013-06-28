//require <xatacard/layout/__init__.js>
//require <xatacard/layout/Record.js>
//require <xatacard/layout/DataSource.js>
//require <xatacard/layout/RecordSetEvent.js>
//require <xatajax.beans/PropertyChangeSupport.js>
//require <xatajax.beans/Subscribable.js>


(function(){

	var $, 
		Record, 
		DataSource, 
		RecordSetEvent, 
		PropertyChangeSupport, 
		PropertyChangeListener, 
		Subscribable;
		
	XataJax.ready(function(){
		$ = jQuery;
		Record = xatacard.layout.Record;
		DataSource = xatacard.layout.DataSource;
		RecordSetEvent = xatacard.layout.RecordSetEvent;
		PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
		PropertyChangeListener = XataJax.beans.PropertyChangeListener;
		Subscribable = XataJax.beans.Subscribable;
	});
	xatacard.layout.RecordSet = RecordSet;
	
	
	/**
	 * Base class for sets of records.  This class should be extended by more specific
	 * classes for different types of schemas.  The load() method can be overridden
	 * to load records from the datasource.
	 *
	 * @constructor
	 *
	 * @event {RecordSetEvent} beforeLoad  Fired before records are loaded into set.
	 * @event {RecordSetEvent} afterLoad Fired after records are loaded into set.
	 * @event {RecordSetEvent} beforeUpdate Fired before update.
	 * @event {RecordSetEvent} afterUpdate Fired after update.
	 * @event {RecordSetEvent} beforeRemoveRecord Fired before a record is removed.
	 * @event {RecordSetEvent} afterRemoveRecord Fired after a record is removed.
	 */
	function RecordSet(o){
	
		var self = this;
	
		/**
		 * @type {DataSource}
		 */
		var dataSource = null;
		
		
		/**
		 * @type {dict Record} Currently loaded records.
		 */
		var records = [];
		
		/**
		 * @type {int}
		 */
		var cardinality = null;
		
		
		XataJax.extend(this, new PropertyChangeSupport(o));
		XataJax.extend(this, new Subscribable(o));
		XataJax.publicAPI(this, {
		
			setCardinality: setCardinality,
			getCardinality: getCardinality,
			load: load,
			decorateLoadRequest: decorateLoadRequest,
			handleLoad: handleLoad,
			setRecordAt:setRecordAt,
			getRecordAt:getRecordAt,
			removeRecord: removeRecord,
			decorateRecord: decorateRecord,
			undecorateRecord: undecorateRecord,
			clear: clear,
			indexIsLoaded: indexIsLoaded
			
		});
		
		
		
		/**
		 * @returns {int} The total number of records in this result set (that could be loaded
		 * from the server but may not necessarily be loaded yet).
		 */
		function getCardinality(){
			return cardinality;
		}
		
		
		/**
		* @param {int} c The cardinality of the result set.
		* @returns {RecordSet} Self for chaining.
		*/
		function setCardinality(c){
			if ( c != cardinality ){
				var old = cardinality;
				cardinality = c;
				this.firePropertyChange('cardinality', old, c);
			}
			return this;
		}
		
		
		
		/**
		 * Loads a segment of this record set.  This method should be overridden by 
		 * subclasses to actually perform the load.  The default implementation
		 * just sets the cardinality to 0 and all start/end values to -1.
		 *
		 * @param {int} start The index of the first record that should be loaded. (0-based).
		 * @param {int} end The index of the last record to be loaded.  (0-based).
		 * @returns {RecordSet} Self for chaining.
		 */
		function load(start, end, callback){
			var self = this;
			var req = new RecordRequest();
			req.setAction('load');
			req.setRecordSet(this);
			req.setParameter('start', start);
			req.setParameter('end', end);
			req.bind('complete', function(response){
				if ( response.getCode() == RecordResponse.SUCCESS || response.getCode() == RecordResponse.ALL_SUCCESS){
					self.handleLoad(response.getRecords(), start);
				}
				if ( typeof(callback) == 'function' ){
					callback(response);
				}
			});
			this.decorateLoadRequest(start, end, req);
			
			if ( dataSource == null ){
				throw new Exception({
					message: 'Null datasource.  Cannot load record set.'
				});
			}
			
			dataSource.sendRequest(req);
			
			
		}
		
		
		function decorateLoadRequest(start, end, req){
		
		}
		
		
		
		
		
		
		
		
		
		
		
		/**
		 * Handles the loading of a set of records.
		 * This should be called by the load() method after the loading is 
		 * completed - usually inside a callback.
		 *
		 * @param {array Record} recs The array of records to load.
		 * @param {int} index The start index where they should be inserted.
		 *
		 * @returns {RecordSet} Self for chaining.
		 */
		function handleLoad(recs, index){
			if ( recs.length == 0 ) return this;
			
			var lStart = index;
			var lEnd = index+records.length;
			
			var event = new RecordSetEvent({
				recordSet: this,
				action: 'load',
				records: recs,
				startIndex: lStart,
				endIndex: lEnd-1
				
			});
			
			this.trigger('beforeLoad', event);
			
			
			for ( var i=lStart; i<lEnd; i++){
				this.setRecordAt(i, recs[i-lStart]);
				
			}
			
			this.trigger('afterLoad', event);
			
			
			return this;
			
		}
		
		
		
		/**
		 * Sets the record at a specific position in this result set.  This does not imply
		 * that the record was added to the result set in the underlying data source - this is just a housekeeping method
		 * to be able to set the record at a particular index of the result set during the 
		 * loading of it.
		 *
		 * This fires an indexd PropertyChangeEvent for the 'records' property.
		 *
		 * @param {int} index The index within the result set that this record should be added.
		 * @param {Record} record The record that is being inserted.
		 */
		function setRecordAt(index, record){
			var self = this;
			if ( typeof(records[index]) == 'undefined' ){
				records[index] = null;
			}
			var old = records[index];
			if ( old != record){
				
				if ( XataJax.instanceOf(old, Record) ){
					this.undecorateRecord(old);
				}
				
				records[index] = record;
				this.decorateRecord(record);
				
				
				this.firePropertyChange('records', old, record, index);
			}
			
		}
		
		/**
		 * Decorates a record that has just been added to this record set.  This 
		 * adds the necessary listeners to be able to respond when the record is
		 * deleted (so that it can be removed from the record set) etc..
		 *
		 * @param {Record} record The record that is to be decorated.
		 * @returns {RecordSet} Self for chaining.
		 */
		function decorateRecord(record){
			var self = this;
			record.bind('afterDelete.updateRecordSet', function(event){
				self.removeRecord(record);
			});
			return this;
	
		}
		
		
		/**
		 * Strips listeners that were added to records by the decorateRecord method.
		 * This is generally called on records as they are being removed from the record set
		 * so that anything that was added to the record when it was added to the record
		 * set will now be removed.  This is the inverse of decorateRecord()
		 *
		 * @param {Record} record The record to undecorate.
		 * @returns {RecordSet} Self for chaining.
		 */
		function undecorateRecord(record){
			record.unbind('afterDelete.updateRecordSet');
		}
		
		/**
		 * Removes a record from the result set.  This will splice the record so that there is no blank
		 * hole remaining.. it just collapses all of the records up one row.  This is the correct 
		 * functionality to mirror a deletion of the record from the underlying datasource.  It is 
		 * important to understand that this action is generally taken in response to a record being
		 * deleted rather than to initiate a delete.  If you wish to delete a record, it is 
		 * most correct to call delete() on that particular record.  This method will be called
		 * by the afterDelete event handler for that record to update the state of the 
		 * result set.
		 *
		 * This will fire the beforeRemoveRecord and afterRemoveRecord events.
		 *
		 * @param {Record} record The record to remove.
		 * @returns {RecordSet} Self for chaining.
		 */
		function removeRecord(record){
			var idx = records.indexOf(record);
			if ( idx != -1 ){
				var event = new RecordSetEvent({
					recordSet: this,
					action: 'removeRecord',
					record: record,
					index: idx
				});
				this.trigger('beforeRemoveRecord', event);
				records.splice(idx,1);
				this.undecorateRecord(record);
				this.trigger('afterRemoveRecord', event); 
			}
			return this;
		}
		
		/**
		 * Gets the record at the specified index of the record set.  Note that this is 
		 * the absolute index, not the index within what's been loaded.
		 * @param {int} index The index to load.
		 *
		 * @returns {Record} The record at the specified index.
		 */
		function getRecordAt(index){
			if ( index < 0 || index > cardinality ){
				throw new Exception({
					message: 'Cannot get record at index '+index+' because that index has not been loaded yet - nor has the load been requested.'
				});
			}
			
			
			if ( this.indexIsLoaded(index) ){
				throw new Exception({
					message: 'Cannot get record at index '+index+' because that index has not been loaded yet.'
				});
			}
			
			return records[index];
			
		}
		
		
		
		
		/**
		 * Clears the result set.  Resets the start and end to default values and empties
		 * out the records array.
		 */
		function clear(){
			
			
			$.each(records, function(){
				self.undecorateRecord(this);
				
			});
			records = [];
		}
		
		/**
		 * Checks whether a particular index has been loaded yet.
		 * @param {int} index The index within the result set that we are checking 
		 * 	to see if it has been loaded yet.
		 *
		 */
		function indexIsLoaded(index){
			return (typeof(records[index]) == 'object'  && records[index] != null);
		}
		
		
		
		
		
		
		
		
	}
	
})();


