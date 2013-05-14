(function ($) {

$(document).ready(function() {

	if($('.widgets-holder-wrap').length){
		var widgetBlock = $('#widgets-right .widgets-holder-wrap');
		widgetBlock.each(function(){
			var thisID = $(this).find('.widgets-sortables').attr('id');
			$(this).addClass(thisID);
		});
	}
	
  //special for configuration: user must have an antenna assigned
  if(typeof(ifAdmin) != "undefined" && ifAdmin !== null)
    $('input#'+ifAdmin.id).attr('disabled','disabled').closest('form').css({'background-color':'#ffebe8', 'opacity': '0.5 '});
/*
	var path = templateDir+'/inc/';
	var tr = $('.edit-tags-php table.widefat tbody tr');
	tr.each(function(){ $(this).last().css('background','url('+path+'images/draggable.png) no-repeat right center'); });
*/
 
});//end document.ready

})(jQuery);
