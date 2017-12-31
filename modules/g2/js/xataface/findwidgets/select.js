//require <xatajax.core.js>
//require <xatajax.util.js>
//require <jquery.packed.js>
(function(){
	var $ = jQuery;
	var findwidgets = XataJax.load('xataface.findwidgets');
	findwidgets.select = select;

	
	function select(/**Object*/ o){
		this.el = null;
		this.btn = null;
		this.name = null;
		this.hiddenField = null;
		
	}
	
	$.extend(select.prototype, {
	
		install: install,
		toggleAdvanced: toggleAdvanced,
		showAdvanced: showAdvanced,
		hideAdvanced: hideAdvanced
	});
	
	function install(/**HTMLElement*/ el){
		var self = this;
		this.el = el;
		this.name = $(this.el).attr('name');
		
		
		
		$(this.el).removeAttr('name');
		this.hiddenField = $('<input type="hidden" name="'+this.name+'"/>');
		this.hiddenField.insertAfter(this.el);
		$(this.el).change(function(){
			if ( $('option:selected', self.el).size() <= 1 ){
				$(self.hiddenField).val($(self.el).val());
			} else {
				$(self.hiddenField).val($(self.el).val().join(' OR '));
			}
		});
		
		this.btn = $('<button>')
			.addClass('advanced-button')
			.text('...')
			.click(function(){
				self.toggleAdvanced();
				return false;
			})
			;
		this.btn.insertAfter(el);
		
		
		var params = XataJax.util.getRequestParams();
		
		if ( params[this.name] ){
			
			var val = decodeURIComponent(params[this.name]);
			if ( val.match(/ OR /) ){
				val = val.split(/ OR /);
				self.showAdvanced();
				$(self.el).val(val);
			} else {
				$(self.el).val(val);
			}
			$(self.hiddenField).val(val);
		}
		
		
		
	}
	
	function toggleAdvanced(){
	
		if ( $(this.el).hasClass('xf-findfields-select-advanced') ) this.hideAdvanced();
		else this.showAdvanced();
	}
	
	
	function showAdvanced(){
		$(this.el)
			.attr('size', 6)
			.attr('multiple', 1)
			.addClass('xf-findfields-select-advanced')
			;
	}
	
	function hideAdvanced(){
		$(this.el).removeAttr('multiple').attr('size',1)
			.removeClass('xf-findfields-select-advanced');
	}
	
	
	
})();