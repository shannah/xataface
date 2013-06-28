//require <xatajax.ui.core.js>
//require <xatajax.ui.actions.js>
//require <xatajax.delete.js>
(function(){
	
	function deletePrompt(params, msg){
		if ( typeof(msg) == 'undefined' ) msg = 'Are you sure you want to delete this record?';
		
		if ( !confirm(msg) ) return;
		
		var q = jQuery.extend({
			
		
		}, params);
		
		q.onSuccess = function(data){
			window.status = 'Record was successfully deleted';
			if ( typeof(params.onSuccess) == 'function' ){
				params.onSuccess(data);
			}
			
		};
		
		q.onFail = function(data){
			alert(data.message);
			if ( typeof(params.onFail) == 'function' ){
				params.onFail(data);
			}
		};
		
		XataJax.submitDelete(q);
			
	}
	
	XataJax.ui.deletePrompt = deletePrompt;
	
})();