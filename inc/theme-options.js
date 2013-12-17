(function ($) {

$(document).ready(function() {
 
	$('.upload-button').click(function() {
         targetfield = $(this).prev('.background_img');
         tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
         return false;
    });
    window.send_to_editor = function(html) {
         imgurl = $('img',html).attr('src');
         $(targetfield).val(imgurl);
         tb_remove();
    }	
	
	$('.reset-bg-img').click(function(){
		$(this).siblings('label').find('.background_img').val('');
		$(this).siblings('.bg-img-preview').hide();
		$(this).siblings('.actual-img').html('You must submit to validate your changes...').css({'background-color':'red','border':'2px solid gray'});
		$(this).hide();
	});
	
 
});//end document.ready

})(jQuery);