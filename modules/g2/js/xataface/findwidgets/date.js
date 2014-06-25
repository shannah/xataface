//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require <xatajax.core.js>
(function(){
	var $ = jQuery;
	
	var findwidgets = XataJax.load('xataface.findwidgets');
	findwidgets.date = date;
	
	
	function date(/**Object*/ o){
		var self = this;
		this.el = null;
		this.name = null;
		this.from = $('<input type="text">');
		this.to = $('<input type="text">');
		this.rangePanel = $('<div>').css('text-align','right');
		$(this.rangePanel).append('From ').append(this.from).append(' to ').append(this.to);
		this.btn = $('<button>').addClass('advanced-button').text('...').click(function(){
			self.toggleRange();
			return false;
		});
		
	}
	
	$.extend(date.prototype, {
	
		install: install,
		toggleRange: toggleRange,
		showRange: showRange,
		hideRange: hideRange
	});
	
	
	
	function install(/**HTMLElement*/ el){
		var self = this;
		this.el = el;
		this.rangePanel.insertAfter(this.el).hide();
		this.btn.insertAfter(this.el);
		
		$.each([this.from, this.to], function(){
			var dates = $(this).datepicker({
				defaultDate: "+1w",
				changeMonth: true,
				numberOfMonths: 3,
				onSelect: function( selectedDate ) {
					var option = this == self.from ? "minDate" : "maxDate",
						instance = $( this ).data( "datepicker" ),
						date = $.datepicker.parseDate(
							instance.settings.dateFormat ||
							$.datepicker._defaults.dateFormat,
							selectedDate, instance.settings );
					dates.not( this ).datepicker( "option", option, date );
					$(this).change();
					
				}
			});
		});
		
		$.each([this.from, this.to], function(){
		
			$(this).change(function(){
				var from = $(self.from).val();
				var to = $(self.to).val();
				if ( from && to ){
					$(self.el).val(from+'..'+to);
				} else if ( from ){
					$(self.el).val('>='+from);
				} else if ( to ){
					$(self.el).val('<='+to);
				} else {
					$(self.el).val();
				}
				
			});
		});
		
		this.name = $(this.el).attr('name');
		
		
		
		
	}
	
	
	function toggleRange(){
		if ( $(this.el).is(':visible') ) this.showRange();
		else this.hideRange();
	}
	
	function showRange(){
		
		$(this.el).hide();
		var val = $(this.el).val();
		if ( val.match(/\.\./) ){
			var parts = val.split('..');
			$(this.from).val(parts[0]);
			$(this.to).val(parts[1]);
		} else if ( val.match(/^</) ){
			$(this.from).val('');
			val = val.replace(/^[^0-9]+/, '');
			$(this.to).val(val);
			
		} else if ( val.match(/^>/) ){
			$(this.to).val('');
			val = val.replace(/^[^0-9]+/, '');
			$(this.from).val(val);
			
		} else {
			$(this.to).val(val);
			$(this.from).val(val);
		}
		
		$(this.rangePanel).show();
	}
	
	function hideRange(){
		$(this.el).show();
		$(this.rangePanel).hide();
	}
})();