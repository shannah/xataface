require(DATAFACE_URL+'/js/ajaxgold.js');

if ( typeof(Xataface) == 'undefined' ) Xataface = {};
if ( typeof(Xataface.deleteFile) == 'undefined' ){
	Xataface.deleteFile = function(record_url,field,preview){
		var query = record_url;
		query = query.replace(/&?-action=[^&]*/, '')+'&-action=delete_file&--field='+encodeURIComponent(field)+'&--format=json';
		query = query.substring(query.indexOf('?')+1);
		if ( confirm('Are you sure you want to delete this file?', 'Yes','No') ){
			postDataReturnText(DATAFACE_SITE_HREF, query, function(text){
				//alert(text);
				//return;
				var struct;
				try {
					eval('struct = '+text+';');
					var previewEl = document.getElementById(preview);
					previewEl.parentNode.removeChild(previewEl);
					alert(struct['--msg']);
				} catch (error){
					alert("Failed to delete file.");
				}
				
			});
		}
	};

}