jQuery(document).ready(function(){
   
   jQuery("#icl_msync_cancel").click(function(){
       location.href = location.href.replace(/#(.)$/, '');
   }); 
   
   jQuery('#icl_msync_confirm thead :checkbox').change(function(){
       var on = jQuery(this).attr('checked');
       if(on){
           jQuery('#icl_msync_confirm :checkbox').attr('checked', 'checked');           
           if(jQuery('#icl_msync_confirm tbody .check-column :checkbox').length){
                jQuery('#icl_msync_submit').removeAttr('disabled');
           }
       }else{
           jQuery('#icl_msync_confirm :checkbox').removeAttr('checked');
           if(!jQuery('input[name^="sync"]').length){
                jQuery('#icl_msync_submit').attr('disabled', 'disabled');
           }
       }
   })
   
   jQuery('#icl_msync_confirm tbody :checkbox').change(function(){
       
       if(jQuery(this).attr('readonly') == 'readonly'){
           if(jQuery(this).attr('checked')){
               jQuery(this).removeAttr('checked');
           }else{
               jQuery(this).attr('checked', 'checked');
           }
       };
       
       var checked = jQuery('#icl_msync_confirm tbody :checkbox:checked').length;

       if(checked){
           jQuery('#icl_msync_submit').removeAttr('disabled');                
       }else{
           jQuery('#icl_msync_submit').attr('disabled', 'disabled');
       }
       
       if(checked && jQuery('#icl_msync_confirm tbody :checkbox:checked').length == jQuery('#icl_msync_confirm tbody :checkbox').length){
           jQuery('#icl_msync_confirm thead :checkbox').attr('checked', 'checked');           
       }else{
           jQuery('#icl_msync_confirm thead :checkbox').removeAttr('checked');
       }
       
       icl_msync_validation();
       
   });
    
});


function icl_msync_validation(){
    
    jQuery('#icl_msync_confirm tbody :checkbox').each(function(){
        var mnthis = jQuery(this);
        
        mnthis.removeAttr('readonly', 'readonly');
        
        if(jQuery(this).attr('name')=='menu_translation[]'){
            var spl = jQuery(this).val().split('#');
            var menu_id = spl[0];   
            
            jQuery('#icl_msync_confirm tbody :checkbox').each(function(){
                
                if(jQuery(this).val().search('newfrom-'+menu_id+'-') == 0 && jQuery(this).attr('checked')){
                    mnthis.attr('checked', 'checked');
                    mnthis.attr('readonly', 'readonly');
                }
                    
            });
            
        }    
    });
    
    
    return;
}