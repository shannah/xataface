//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require <RecordBrowser/RecordBrowser.js>
//require <RecordDialog/RecordDialog.js>

(function(){

	var $ = jQuery;

	registerXatafaceDecorator(function(node){
	
		$('.xf-lookup', node).each(function(){
	
	
			var options = {};
			if ( $(this).attr('data-xf-lookup-options') ){
				eval('options='+$(this).attr('data-xf-lookup-options')+';');
			}
			
			if ( !options.filters ) options.filters = {};
			options.dynFilters = {};
			$.each(options.filters, function(key,val){
				if ( val.indexOf("$")==0 ){
					options.dynFilters[key] = val.substr(1);
					delete options.filters[key];
				}
			});
			//options.callback = '.$properties['callback'].';
			if ( options.callback ){
				eval('options.callback='+options.callback+';');
			}
			options.click = function(){
				$.each(options.dynFilters, function(key,val){
					delete options.filters[key];
					$("form *[name="+val+"]").each(function(){
						options.filters[key] = $(this).val();
					});
				});
				
			};
			$(this).RecordBrowserWidget(options);
		});
	});

})();