(function ($) {

function setWings() {
	if ($('.container').length) {
		if(!$('.sides').length) {
			$('body').append('<div class="sides" id="side-left" /><div class="sides" id="side-right" />');
		}
	
		var w = ($(window).width()) - ($('.container').outerWidth());
		//var w = ($(window).width()) - 905;
		w = (w/2);
		$('.sides').width(w-15); //15 = margin between .container and sides
		
		$('.sides').height($(window).height());
	}
}

$(window).load(function() {
	setWings();
});//end window.load

$(window).resize(setWings);
	
$(document).ready(function() {
	function realWidth(obj){
	    var clone = obj.clone();
	    clone.css("visibility","hidden");
	    $('body').append(clone);
	    var width = clone.outerWidth();
	    clone.remove();
	    return width;
	}

    //SLIDER
    if($('#slides').length){
	    //cf. http://slidesjs.com/ for all options
	    $('#slides').slides({
			preload: true,
			preloadImage: 'http://'+window.location.hostname+'/wp-content/themes/iftheme/images/slide/loading.gif',
			play: 7000,
			pause: 2500,
			hoverPause: true,
			slideSpeed: 700,
			animationStart: function(current){
				$('.caption').animate({left:-1200},100);
			},
			animationComplete: function(current){
				$('.caption').animate({ left:0 },200);
				//$('.pagination').css('right', realWidth($('.caption').eq(current-1)));
				$('.pagination').animate({ left: $('.caption').eq(current-1).outerWidth()},200);
			},
			slidesLoaded: function() {
				$('.caption').animate({left:0},200);
				$('.pagination').css('left', $('.caption').outerWidth());
			}
		});
    
    }



	if($('blockquote').length) {
		$('blockquote').addClass('clearfix');
		$('blockquote').prepend('<span class="open" />');
		$('blockquote').append('<span class="close" />');
	}
	
	if($('.featured-thumbnail').length){
		
		var thumbs = $('.featured-thumbnail');
		thumbs.each(function(){
			$(this).next('.top-block').css({'float':'left','width':'390px','margin-bottom':'15px'});
			var newH = $(this).closest('article').height();
			if($(this).height() < newH ) {
				$(this).height(newH+2);
				
			} else if($(this).height() >= newH ) {
				$(this).closest('article').height($(this).height());
				$(this).siblings('.post-excerpt').find('.read-more').css({'top':'-4px'})
			}
		});
	}
	
	//main menu
	$('nav#antennes').css('height','30px');
    if($('nav#antennes ul li.current-cat ul.children').length || $('nav#antennes ul li.current-cat-parent ul.children').length) { //console.log('bih');
    	//$('ul.children').removeClass('active').hide();
	    $('nav#antennes').css('height','60px');
	    $('nav#antennes ul li.current-cat ul.children').show();
	    $('nav#antennes ul li.current-cat-parent ul.children').show();
    }
    if( $('.children li').hasClass('current-cat-parent')) { //console.log('bah');
    	$('nav#antennes').css('height','60px');
	    $('.children li.current-cat-parent').closest('ul').closest('.cat-item').addClass('current-cat-parent');
	    $('.children li.current-cat-parent').closest('ul.children').show();
    } 
    
    //display category children
    if($('.display-children').length){
	    var items = $('.display-children li.cat-item');
	    
	    items.each(function(){
	    	var i = $(items).index($(this));
		    if(i%2 == 1) 
		    	var h = $(this).prev().height();
		    	var thisH = $(this).height();
		    	h = h+1;
		    	if(thisH < h) $(this).height(h);
		    	else if(thisH > h) $(this).prev().height(thisH-1);
	    })
    }
    
    if($('.breadcrumbs').length){
	   $('.breadcrumbs').find('> a:first-child').html('<img src="http://'+window.location.hostname+'/wp-content/themes/iftheme/images/pict-home.png" alt="'+$('.breadcrumbs').find('> a:first-child').text()+'" />'); 
	   
	   if($('.archive.date').length){ 
		   $('.breadcrumbs').html( $('.breadcrumbs').find('> a:first-child').html());
	   }
	   if($('.home').length){ 
		   $('.breadcrumbs').html('<img src="http://'+window.location.hostname+'/wp-content/themes/iftheme/images/pict-home-on.png" alt="" />');
	   }
    }
    
    //NEWSLETTER widget
    if($('#sidebar-newsletter').length) {
    	var title = $('#sidebar-newsletter h3');
    	$(title).css('cursor','pointer').append($('.wysija-instruct').css('top',0)).siblings().hide();

    	title.click(function(){
	    	$(this).siblings().slideToggle('fast');
    	});
	    //.wysija-msg.ajax
    }
    if($(".wysija_lists .checkbox").length){
	    $(".wysija_lists .checkbox").unbind('click');
		//styling checkboxes
		$(".wysija_lists .checkbox").each(function() {
			var lClass = 'unchecked';
			if($(this).is(':checked')) {lClass = 'checked';}
			
			$(this).closest('label').addClass(lClass).prepend('<span class="checkbox-span" />');
			$(this).unbind('click');
			$(this).attr('disabled', true);
			$(this).css('visibility','hidden');
	    });
	    
	    $(".wysija_lists .wysija_list_check label").click(function(){
	
			if($(this).children("input").is(':checked')){
				// uncheck
				$(this).children("input").attr('checked',false);
				$(this).children("span.checkbox-span").css('background-position','left top');
				$(this).removeClass("checked");
				$(this).addClass("unchecked");
			}else{
				// check
				$(this).children("input").attr('checked',true);
				$(this).children("span.checkbox-span").css('background-position','left bottom');
				$(this).removeClass("unchecked");
				$(this).addClass("checked");
			}
		});
		
		$('form.widget_wysija').submit(function(){
			//console.log($(".wysija_lists input.checkbox").attr('checked'));
		});
	} else { $('input.wysija-email').css({ width: '233px'}); }
    
    //homepages
    if($('.block-home').length) { //for all possible options see http://masonry.desandro.com/docs/options.html
	    $('#home-list').masonry({
		    // options
		    isRTL : true, //for Right-To-Left language
		    itemSelector : '.block-home',
		    columnWidth : 320
		});
    }
    
});//end document.ready
	

})(jQuery);