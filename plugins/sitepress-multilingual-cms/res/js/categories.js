jQuery(document).ready(function(){  
    if(jQuery('#addtag').html()){
        jQuery('#addtag p[class="submit"]').before(jQuery('#icl_category_menu').html());    
    }else{
        jQuery('#edittag table[class="form-table"]').append(jQuery('form[name="editcat"] table[class="form-table"] tr:last').clone());    
        jQuery('#edittag table[class="form-table"] tr:last th:first').html('&nbsp;');
        jQuery('#edittag table[class="form-table"] tr:last td:last').html(jQuery('#icl_category_menu').html());
    }    
    
    jQuery('#icl_tax_menu').remove();
   
   jQuery('select[name="icl_category_language"]').change(function(){
    
        var lang = jQuery(this).val();
        var ajx = location.href.replace(/#(.*)$/,'');
        ajx = ajx.replace(/pagenum=([0-9]+)/,'');
        if(-1 == location.href.indexOf('?')){
            url_glue='?';
        }else{
            url_glue='&';
        }
        
        if(icl_this_lang != lang){
            jQuery('#icl_translate_options').fadeOut();
        }else{
            jQuery('#icl_translate_options').fadeIn();
        }
        
        jQuery('#posts-filter').parent().load(ajx+url_glue+'lang='+lang + ' #posts-filter', {}, function(resp){
            strt = resp.indexOf('<span id="icl_subsubsub">');
            endd = resp.indexOf('</span>\'', strt);
            lsubsub = resp.substr(strt,endd-strt+7);
            jQuery('table.widefat').before(lsubsub);            
                         
            start_sel = resp.indexOf('<select name=\'category_parent\' id=\'category_parent\' class=\'postform\' >');
            end_sel = resp.indexOf('</select>', start_sel);
            sel_sel = resp.substr(start_sel+70, end_sel-start_sel-70);            
            jQuery('#category_parent').html(sel_sel)
            
        });        
   })
   
});

/*
 jQuery(function($) {
	var addAfter3 = function( r, settings ) {
            jQuery('#icon-edit').remove();
	}
        
	$('#the-list').wpList( { addAfter: addAfter3} );
        

});
*/
