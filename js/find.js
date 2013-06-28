//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require-css <find.css>
(function(){

	jQuery(document).ready(function($){
	
	
		var instructions = $('#search-instructions');
		var instructionsLink = $('.search-instructions-link');
		
		instructionsLink.click(function(){
			var div = document.createElement('div');
			$(div)
				.addClass('search-instructions')
				.html(instructions.html())
				.dialog({
					title: 'Search Instructions',
					position: 'right',
					height: $(window).height()*0.9,
					width: $(window).width()*0.4
				
				});
				
			$('.accordion', div).accordion({
				header: 'h6'
				
			});
		});
		/*
		var firstHeading = $('.Dataface_collapsible_sidebar').get(0);
		if ( firstHeading ){
			$(firstHeading).remove();
		}*/
		
		
	});
})();