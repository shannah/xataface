//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
(function(){
	
	var $ = jQuery;
	
	
	$(document).ready(function(){
		var form = document.getElementById('copy_replace_form');
		var fields= form.elements['-copy_replace:fields'];
		var fieldSelector = $('#xf-copy-replace-fields-select');
		
		
		var helpButton = $('#df-copy-replace-help-button');
		
		helpButton.click(function(){
		
			var dlg = document.createElement('div');
			$(dlg).load(DATAFACE_URL+'/help/update_records.html', function(){
				$('body').append(dlg);
				$(dlg).dialog({
					title: 'Update Form Help',
					width: $(window).width()-10,
					height: $(window).height()-10
				});
			});
			return false;
		});
		
			
			
		$('tr.xf-copy-replace-field-row').each(function(){
			var removeFieldBtn = $('button.xf-remove-field', this);
			var fld = $(this).attr('data-xf-update-field');
			var label = $('option[value="'+fld+'"]', fieldSelector).text();
			var input = $('[data-xf-field]', this);

			var tr = this;
			$(removeFieldBtn).click(function(){

				$(tr).hide();
				fieldSelector.append($('<option/>').attr('value',fld).text(label));
				
				var vals = fields.value.split('-');
				var newvals = [];
				$.each(vals, function(){
					if ( this != fld ) newvals.push(this);
				});
				fields.value = newvals.join('-');
				return false;
				
			});
			
			
			
			
		});
			
		$('tr.xf-update-default').each(function(){
		
			
			
			$(this)
				.css('display','');
				
			
			var fld = $(this).attr('data-xf-update-field');
			var label = $('option[value="'+fld+'"]', fieldSelector).text();
			
			if ( fields.value ) fields.value += '-'+fld;
			else fields.value = fld;
			
			$('option[value="'+fld+'"]', fieldSelector).remove();
			
			
			
			
			
			

			
		});
		
		
		
		
		
	});
	
})();