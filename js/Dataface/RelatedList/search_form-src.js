if ( typeof(Dataface) == 'undefined' ) Dataface = {};
if ( typeof(Dataface.RelatedList) == 'undefined' ) Dataface.RelatedList = {};

Dataface.RelatedList.processSearchForm = function(f){
	var query = f.related_find_query;
	var qstr = window.location.search;
	if ( qstr.search(/[&?]-related:search=[^&]+/) >= 0 ){
		qstr = qstr.replace(/([&?])-related:search=[^&]+/, '$1-related:search='+escape(query.value));
	} else {
		qstr += '&-related:search='+escape(query.value);
	}
	window.location.search = qstr;
	return false;
}