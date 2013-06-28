if ( typeof(Dataface) == 'undefined' ) Dataface = {};
if ( typeof(Dataface.RelatedList) == 'undefined' ) Dataface.RelatedList = {};
Dataface.RelatedList.searchElement = null;
Dataface.RelatedList.advancedSearchElement = null;
Dataface.RelatedList.searchHTML = null;
Dataface.RelatedList.advancedSearchHTML = null;
require(DATAFACE_URL+'/js/ajaxgold.js');

/**
 * Shows the standard search form for a related list.
 */
Dataface.RelatedList.showSearch = function(relationship, e){
	if ( this.searchElement && this.searchElement != e ){
		this.searchElement.innerHTML = '';
		this.searchElement.style.display = 'none';
		this.searchElement = null;
	}
	this.searchElement = e;
	
	
	if ( this.searchHTML ){
		this.searchElement.innerHTML = this.searchHTML;
		// In case there are <script> tags embedded in the HTML,
		// let's load them.
		loadScripts(this.searchElement);
		this.searchElement.style.display = '';
	} else {
		this.searchElement.innerHTML = '<img src="'+DATAFACE_URL+'/images/progress.gif" alt="Loading search form" />';
		this.searchElement.style.display='';
		getDataReturnText(DATAFACE_SITE_HREF+'?-action=ajax_related_find_form&-relationship='+relationship+'&-related:search='+escape(this.getSearchTerm()), function(text){
			var me = Dataface.RelatedList;
			if ( text ){
				me.searchHTML = text;
			} else {
				me.searchHTML = "[Error loading find form.  No content returned]";
			}
			
			// Now that we have our innerHTML set we can call the showSearch method 
			//  again.
			me.showSearch(null, me.searchElement);
		});
	}


};

Dataface.RelatedList.showAdvancedSearch = function(relationship, e){
	if ( this.advancedSearchElement && this.advancedSearchElement != e ){
		this.advancedSearchElement.innerHTML = '';
		this.advancedSearchElement.style.display = 'none';
		this.advancedSearchElement = null;
	}
	
	this.advancedSearchElement = e;
	
	if ( this.advancedSearchHTML ){
		this.advancedSearchElement.innerHTML = this.advancedSearchHTML;
		// In case there are script tags embedded in the HTML, let's load them
		
		loadScripts(this.advancedSearchElement);
		this.advancedSearchElement.style.display = '';
	} else {
		this.advancedSearchElement.innerHTML = '<img src="'+DATAFACE_URL+'/images/progress.gif" alt="Loading search form" />';
		this.searchElement.style.display='';
		getDataReturnText(DATAFACE_SITE_HREF+'?-action=ajax_related_advanced_find_form&-relationship='+relationship, function(text){
			var me = Dataface.RelatedList;
			if (text){
				me.advancedSearchHTML = text;
			} else {
				me.advancedSearchHTML = "[Error loading advanced find form.  No content returned]";
			}
			
			me.showAdvancedSearch(null, me.advancedSearchElement);
		});
	}
};

Dataface.RelatedList.hideSearch = function(e){
	e.style.display = 'none';
};

Dataface.RelatedList.getSearchTerm = function(){
	var qstr = window.location.search;
	var params = qstr.split('&');
	for ( var i=0; i<params.length; i++){
		var parts = params[i].split('=');
		if ( parts[0] == '-related:search' ){
			return unescape(parts[1]);
		}
	}
	return '';
}


