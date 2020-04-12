//require <jquery.packed.js>
(function() {


jQuery(document).ready(function($) {
	$('#install-bindings').click(function() {
		this.disabled = true;
		var self = this;
		$.post(DATAFACE_SITE_HREF, {'-action':'sync_bindings'}).always(function(res){
			self.disabled = false;
			
			$('#result').html(res);
		});
	});
});

})();