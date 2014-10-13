//require <jquery.packed.js>
//require <xatajax.core.js>
//require-css <xataface/actions/show_hide_columns.css>
//require <jquery.noty.js>

(function(){
	var $ = jQuery;
	
	var controllers = XataJax.load('xataface.controllers');
	controllers.ShowHideColumnsController = {saveCallbacks : []};
	
	
	
	function buildRequestData(el){
		var data = {fields:{}};
		$('input[data-field-name]', el).each(function(){
			var fieldName = $(this).attr('data-field-name');
			if ( fieldName.indexOf('.') < 0 ){
				// This is not a related field
				var visibilityType = $(this).attr('data-visibility-type');
				var visibility = $(this).is(':checked') ? 'visible' : 'hidden';
				
				data.fields[fieldName] = data.fields[fieldName] || {};
				var fieldConfig = data.fields[fieldName];
				
				//fieldConfig.visibility = fieldConfig.visibility || {};
				fieldConfig[visibilityType] = visibility;
			} else {
				var parts = fieldName.split('.');
				var relationshipName = parts[0];
				var sfieldName = parts[1];
				if ( typeof(data['relationships']) === 'undefined'  ){
					data.relationships = {};
				}
				if ( typeof(data.relationships[relationshipName]) === 'undefined' ){
					data.relationships[relationshipName] = {
						name : relationshipName,
						fields : {}
					};
					
				}
				var relationshipConfig = data.relationships[relationshipName];
				relationshipConfig.fields[fieldName] = relationshipConfig.fields[fieldName] || {};
				var fieldConfig = relationshipConfig.fields[fieldName];
				var visibilityType = $(this).attr('data-visibility-type');
				var visibility = $(this).is(':checked') ? 'visible' : 'hidden';
				fieldConfig[visibilityType] = visibility;
				
			}
		});
		
		return data;
	}
	
	function showDialog(layout,type,message, callback) {
		layout = layout || 'top';
		type = type || 'alert';
        var n = noty({
            text        : '<b>'+message+'</b>',
            type        : type || 'alert',
            dismissQueue: true,
            layout      : layout || 'top',
            theme       : 'defaultTheme',
            buttons     : [
                {addClass: 'btn btn-primary', text: 'Ok', onClick: callback}
            ]
        });
        
    }

	registerXatafaceDecorator(function(el){
	
		
	
		
		var saveBtn = $('button.save', el);
		saveBtn.click(function(){
			var query = {
				'-action' : 'show_hide_columns',
				'-table' : $('table.show-hide-columns-grid', el).attr('data-table-name'),
				'-format' : 'json',
				'--data' : JSON.stringify(buildRequestData(el))
			};
			$.post(DATAFACE_SITE_HREF, query, function(res){
				console.log(res);
				if ( res && res.code >= 200 && res.code < 300 ){
					$(controllers.ShowHideColumnsController.saveCallbacks).each(function(k,v){
						if ( res.preventDefault ) return;
						v.call(window, res);
					});
					if ( !res.preventDefault ){
						var msg = 'Successfully saved column orderings.';
						if ( res.errors && res.errors.length > 0 ){
							msg = '<p>Saved column orders but some errors were reported:</p><ul>';
							$(res.errors).each(function(k,v){
								msg += '<li>'+v+'</li>';
							});
							msg += '</ul>';
						}	
						showDialog(null, null, msg, function(){
							window.location.href=DATAFACE_SITE_HREF+'?-table='+$('table.show-hide-columns-grid', el).attr('data-table-name');
						});
					
					}
				} else {
					showDialog(null, null, msg, function(){});
				}
			});
		});
		
		$('input.select-all', el).click(function(){
			$('input[data-visibility-type="'+$(this).attr('data-visibility-type')+'"]', $(this).parents('table').first())
				.prop('checked', $(this).prop('checked'));
		});
		
		
	});
})();