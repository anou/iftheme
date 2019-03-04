(function ($) {

  $(document).ready(function() {
    var targetfield;
    // check if image-field input is empty
    $('.image-field').each(function(){
      if( !$(this).val() ){
        $(this).closest('.layout').find('.actual-img').hide();
      }
    });

    $('.upload-button').click(function() {
      targetfield = $(this).prev('.image-field');
      tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
      return false;
    });
    window.send_to_editor = function(html) {
      // we must check that we have only the img tag
      var myRegex = /<img[^>]+src="(http:\/\/[^">]+)"/g;
      var img = myRegex.exec(html);
      var imgurl = img[1];
      $(targetfield).val(imgurl).closest('.layout').append('<div class="warning">'+ifOptions.delete_img_txt+'</div>');
      tb_remove();
    }

    $('.reset-bg-img').each(function(){
      $(this).click(function(){
        var $layout = $(this).closest('.layout');
        $layout.find('.image-field').val('');
        $layout.find('.actual-img').show().html(ifOptions.delete_img_txt).addClass('warning');
      });
    });
  });//end document.ready

})(jQuery);
