//require <jquery.packed.js>
(function() {
	var $ = jQuery;
	
	
	
	
	registerXatafaceDecorator(function(root) {
		$('.xf-grouped-list-group').each(function() {
			var group = this;
			var contentUrl = $(group).attr('data-group-by-group-content-url');
			if (contentUrl) {
				console.log("Loading "+contentUrl);
				$('.xf-grouped-list-group-content', group).load(contentUrl);
			}
		});
	});
})();