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
 /* Get the window's width, and check whether it is narrower than 480 pixels */
  var windowWidth = $(window).width();



	function realWidth(obj){
	    var clone = obj.clone();
	    clone.css("visibility","hidden");
	    $('body').append(clone);
	    var width = clone.outerWidth();
	    clone.remove();
	    return width;
	}

	//OL numbers in blue IF
	$('ol li').wrapInner('<span style="color:#231F20;"> </span>').css('color','#008AC9');

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
				$('.caption').animate({right:-1200},100);
			},
			animationComplete: function(current){
				$('.caption').animate({ right:0 },200);
				//$('.pagination').css('right', realWidth($('.caption').eq(current-1)));
				var animRight = $('.caption').eq(current-1).outerWidth();
				
				if(windowWidth <= 767) { animRight = animRight+210; }
				if(windowWidth <= 479) { animRight = animRight+130; }
				
				$('.pagination').animate({ right: animRight},200);
			},
			slidesLoaded: function() {
				$('.caption').animate({right:0},200);console.log($('.caption').outerWidth());
				var animRightStart = $('.caption').outerWidth();

				if(windowWidth <= 767) { animRightStart = animRightStart+210; }
				if(windowWidth <= 479) { animRightStart = animRightStart+130; }
				
				$('.pagination').css('right', animRightStart);
			}
		});
    
    }
    
    //PARTNERS
    if($('.partners')){
	    $(function(){
	      $(".partners").slides({
	        container: 'partners_container',
	        generatePagination: false,
	        effect: 'fade',
	        pagination: false,
			preload: true,
			preloadImage: 'http://'+window.location.hostname+'/wp-content/themes/iftheme/images/slide/loading.gif',
			play: 7000,
			pause: 2500,
			hoverPause: true,
			slideSpeed: 700,
	      });
	    });
	}
	
	


	if($('blockquote').length) {
		$('blockquote').addClass('clearfix');
		$('blockquote').prepend('<span class="open" />');
		$('blockquote').append('<span class="close" />');
	}
 if( windowWidth > 767){
	if($('.featured-thumbnail').length){
		
		var thumbs = $('.featured-thumbnail');
		thumbs.each(function(){
			$(this).next('.top-block').css({'float':'left','width':'400px','margin-bottom':'15px'});
			var newH = $(this).closest('article').height();
			if($(this).height() < newH ) {
				$(this).height(newH+2);
				
			} else if($(this).height() >= newH ) {
				$(this).closest('article').height($(this).height());
				$(this).siblings('.post-excerpt').find('.read-more').css({'top':'-4px'})
			}
		});
	}
 }
	
	//main menu
	//$('nav#antennes').css('height','30px');
	  
 
 if (windowWidth > 767){
  if($('nav#antennes ul li.current-cat ul.children').length || $('nav#antennes ul li.current-cat-parent ul.children').length) { 
    $('.container.for-angle ul.children').appendTo('nav#antennes').show();
    $('ul li.current-cat-parent ul.children').show();
  }
  if( $('.children li').hasClass('current-cat-parent')) {
    $('.container.for-angle ul.children').appendTo('nav#antennes').show();
    $('.children li.current-cat-parent').closest('ul').closest('.cat-item').addClass('current-cat-parent');
    $('.children li.current-cat-parent').closest('ul.children').show();
  }
}
//responsive select

if (windowWidth <= 767) {
  /* Clone our navigation */
  var mainNavigation = $('nav#antennes').clone();

  /* Replace unordered list with a "select" element to be populated with options, and create a variable to select our new empty option menu */
  $('nav#antennes').html('<select class="menu"></select>');
  
  var selectMenu = $('select.menu');
 
  /* Navigate our nav clone for information needed to populate options */
  $(mainNavigation).children('ul').children('li').each(function() {
    /* Get top-level link and text */
    var href = $(this).children('a').attr('href');
    var text = $(this).children('a').text();

    /* Append this option to our "select" */
    $(selectMenu).append('<option value="'+href+'">'+text+'</option>');

    /* Check for "children" and navigate for more options if they exist */
    if ($(this).children('ul').length > 0) {
       $(this).children('ul').children('li').each(function() {
          /* Get child-level link and text */
          var href2 = $(this).children('a').attr('href');
          var text2 = $(this).children('a').text();

          /* Append this option to our "select" */
          $(selectMenu).append('<option value="'+href2+'">--- '+text2+'</option>');
       });
    }
 });

}

/* When our select menu is changed, change the window location to match the value of the selected option. */
$(selectMenu).change(function() {
   location = this.options[this.selectedIndex].value;
});



    
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
	   $('.breadcrumbs').find('> a:first-child').html('<img src="'+ bInfo.bTheme + '/images/pict-home.png" alt="'+$('.breadcrumbs').find('> a:first-child').text()+'" />'); 
	   
	   if($('.archive.date').length){ 
		   $('.breadcrumbs').html( $('.breadcrumbs').find('> a:first-child').html());
	   }
	   if($('.home').length){ 
		   $('.breadcrumbs').html('<img src="'+ bInfo.bTheme + '/images/pict-home-on.png" alt="" />');
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
		
	} else { $('input.wysija-email').css({ width: '233px'}); }
    
    //homepages
  if(windowWidth > 767){
    if($('.block-home').length) { //for all possible options see http://masonry.desandro.com/docs/options.html
	    $('#home-list').masonry({
		    // options
		    itemSelector : '.block-home',
		    columnWidth : 320
		  });
    }
  }
    //more then 3 blocks in footer widget area
    if($('.footer-all-block .widget-area').length > 3) { //for all possible options see http://masonry.desandro.com/docs/options.html
	    $('.footer-all-block').masonry({
		    // options
		    itemSelector : '.widget-footer',
		});
    }


	//header page menu
	$('#header-pages-menu ul').each(function(){
	 
	  var list = $(this),
	  currentCountry = bInfo['bDesc'],//cf. header.php for the bInfo array
	  select = $(document.createElement('select')).insertBefore($(this).hide());
	  
	  $('<option>'+currentCountry+'</option>').appendTo(select);
	  
	  $('>li a', this).each(function(){
	   // var target = $(this).attr('target'),
	    option = $(document.createElement('option'))
	     .appendTo(select)
	     .val(this.href)
	     .html($(this).html());
	     
	     });
	     
	   $(select).chosen().change(
	     function(){
	     var target = $(this).attr('target');
	       if (target==='_blank'){
	         window.open($(this).val());
	       }
	       else{
	         window.location.href=$(this).val();
	       }
	   });



	  list.remove();
	  
	});

    
});//end document.ready
	

})(jQuery);