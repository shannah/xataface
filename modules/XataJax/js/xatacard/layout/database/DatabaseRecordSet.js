(function(){

	function DatabaseRecordSet(o){
	
		var sort = [];
		var filters = {};
		
		
	
		XataJax.extend(this, new RecordSet(o));
		XataJax.publicAPI(this, {
			addSort: addSort,
			removeSort: removeSort,
			clearSort: clearSort,
			addFilter: addFilter,
			removeFilter: removeFilter,
			getFilters: getFilters,
			getSort: getSort
		});
		
		
		function addSort(s){
			sort.push(s);
		}
		
		function removeSort(s){
			var idx = sort.indexOf(s);
			if ( idx != -1 ){
			
				sort.splice(idx,1);
			}
		}
		
		function clearSort(){
			sort = [];
		}
		
		function addFilter(key, value){
			filters[key] = value;
		}
		
		function removeFilter(key){
			delete filters[key];
		}
		
		function getFilters(){
			return $.extend({}, filters);
		}
		
		function getSort(){
			return $.merge([], sort);
		}
		
	}
})();