jQuery(document).ready(function($) { 
	
  $(".ifdate").datepicker({
    dateFormat: 'D, M d, yy',
    showOn: 'button',
    buttonImage: 'http://'+window.location.hostname+'/wp-content/themes/iftheme/inc/events/images/icon-datepicker.png',
    buttonImageOnly: true,
    numberOfMonths: 3

  });
  
  $('#post').validate({
		
		rules: {
		
		  'if_events_disciplines[]': {
  		  required: true
  	  },
		  
			if_events_startdate: {
				required: true,
				date: true
			},

			if_events_enddate: {
				required: true,
				date: true
			},

			if_events_lieu: {
				required: true,
				minlength: 3
			},
			
			if_events_city: {
  			required: true,
  			minlength: 2
			},
			
			if_events_pays: {
				required: true
			},
			
			longlat: {
  			required: true
			},
			
			if_events_long: {
  			required: true,
  			number: true
			},

			if_events_lat: {
  			required: true,
  			number: true
			},

			if_events_link1: {
  			url: true
			},

			if_events_link2: {
  			url: true
			},

			if_events_link3: {
  			url: true
			},

			if_book_mail: {
  			email: true
			},
			
			if_events_mmail: {
  			email: true
			}
			
		},
		
		messages: {
			'if_events_disciplines[]': objectL10n.disciplines,
			if_events_lieu: objectL10n.place,
			if_events_lieu: objectL10n.place,
			if_events_city: objectL10n.mandatory,
			if_events_pays: objectL10n.mandatory,
			longlat: objectL10n.mandatory,
			if_events_long: objectL10n.long,
			if_events_lat: objectL10n.lat,
			if_events_link1: objectL10n.httpurl,
			if_events_link2: objectL10n.httpurl,
			if_events_link3: objectL10n.httpurl
		}
	});


});
