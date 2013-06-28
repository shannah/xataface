(function(){
	
	function DatabaseRecord(o){
		
		
		XataJax.extend(this, new Record(o));
		XataJax.publicAPI(this, {
			createSubRecordSet: createSubRecordSet
		});
		
		/**
		 * Creates a related record set for the given relationship.
		 *
		 * @param {String} path
		 * @returns {DatabaseRelatedRecordSet} A record set for the given relationship.
		 */
		function createSubRecordSet(path){
			
			var set = new DatabaseRelatedRecordSet();
			set.setSchema(this.getSchema().getSubSchema(path));
			set.setSourceRecord(this);
			return set;
			
			
		}
		
		
		
	}
})();