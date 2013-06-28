//require <xataface/DOM.js>
jQuery(document).ready(function($){
	$('.xf-application').each(function(){
		xataface.DOM(this).decorate();
	});
});