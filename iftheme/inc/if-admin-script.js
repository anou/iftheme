(function ($) {

$(document).ready(function() {

	if($('.widgets-holder-wrap').length){
		var widgetBlock = $('#widgets-right .widgets-holder-wrap');
		widgetBlock.each(function(){
			var thisID = $(this).find('.widgets-sortables').attr('id');
			$(this).addClass(thisID);
		});
	}
/*
	var path = templateDir+'/inc/';
	var tr = $('.edit-tags-php table.widefat tbody tr');
	tr.each(function(){ $(this).last().css('background','url('+path+'images/draggable.png) no-repeat right center'); });
*/
 
});//end document.ready

})(jQuery);