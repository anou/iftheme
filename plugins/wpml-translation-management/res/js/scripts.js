jQuery(document).ready(function(){
    
    jQuery('#icl_tm_selected_user').change(function(){
        if(jQuery(this).val()){
            jQuery('.icl_tm_lang_pairs').slideDown();
        }else{
            jQuery('.icl_tm_lang_pairs').slideUp();
            jQuery('#icl_tm_adduser .icl_tm_lang_pairs_to').hide();
            jQuery('#icl_tm_add_user_errors span').hide();
        }
        
    });
    
    jQuery('#icl_tm_adduser .icl_tm_from_lang').change(function(){
        if(jQuery(this).attr('checked')){
           jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideDown();
        }else{
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').find(':checkbox').removeAttr('checked'); 
            jQuery(this).parent().parent().find('.icl_tm_lang_pairs_to').slideUp();
        }
    });
    
    jQuery('a[href="#hide-advanced-filters"]').click(function(){        
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',0,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideUp()
            athis.hide();
            jQuery('a[href="#show-advanced-filters"]').show();
        });
    })
    
    jQuery('a[href="#show-advanced-filters"]').click(function(){
        athis = jQuery(this);        
        icl_save_dashboard_setting('advanced_filters',1,function(f){
            jQuery('#icl_dashboard_advanced_filters').slideDown()
            athis.hide();
            jQuery('a[href="#hide-advanced-filters"]').show();
        });
    })
    
    /* word count estimate */
    
    jQuery('#icl-tm-translation-dashboard td :checkbox').click(icl_tm_update_word_count_estimate);
    jQuery('#icl-tm-translation-dashboard th :checkbox').click(icl_tm_select_all_documents);
    jQuery('#icl_tm_languages :radio').change(icl_tm_enable_submit);
    jQuery('#icl_tm_languages :radio').change(icl_tm_dup_warn);
    
    jQuery('.icl_tj_select_translator select').live('change', icl_tm_assign_translator);
    
    jQuery('#icl_tm_editor .handlediv').click(function(){
        if(jQuery(this).parent().hasClass('closed')){
            jQuery(this).parent().removeClass('closed');
        }else{
            jQuery(this).parent().addClass('closed');
        }
    })
    
    jQuery('.icl_tm_toggle_visual').click(function(){
        var inside = jQuery(this).closest('.inside');
        jQuery('.icl-tj-original .html', inside).hide();
        jQuery('.icl-tj-original .visual', inside).show();
        jQuery('.icl_tm_orig_toggle a', inside).removeClass('active');
        jQuery(this).addClass('active');        
        return false;
    });
    
    jQuery('.icl_tm_toggle_html').click(function(){
        var inside = jQuery(this).closest('.inside');
        jQuery('.icl-tj-original .html', inside).show();
        jQuery('.icl-tj-original .visual', inside).hide();
        jQuery('.icl_tm_orig_toggle a', inside).removeClass('active');
        jQuery(this).addClass('active');
        return false;
    })
    
    jQuery('.icl_tm_finished').change(function(){        
        jQuery(this).parent().parent().find('.icl_tm_error').hide();
        var field = jQuery(this).attr('name').replace(/finished/,'data');
        
        if(field == 'fields[body][data]'){
            var datatemp = '';
            try{
                datatemp = tinyMCE.get('fields[body][data]').getContent();
            }catch(err){;}
            var data = jQuery('*[name="'+field+'"]').val() + datatemp;
        }
        else if(jQuery(this).hasClass('icl_tmf_multiple')){
            var data = 1;
            jQuery('[name*="'+field+'"]').each(function(){
                data = data * jQuery(this).val().length;
            });
        }else{
            
            var datatemp = '';
            try{
                datatemp = tinyMCE.get(field).getContent();
            }catch(err){;}
                    
            var data = jQuery('[name="'+field+'"]*').val() + datatemp;    
        }
        
        
        
        
        if(jQuery(this).attr('checked') && !data){
            jQuery(this).parent().parent().find('.icl_tm_error').show();
            jQuery(this).removeAttr('checked');    
        }
    });
    
    jQuery('#icl_tm_editor .icl_tm_finished').change(icl_tm_update_complete_cb_status);
    
    jQuery('#icl_tm_editor').submit(function(){
        formnoerr = true;
        jQuery('#icl_tm_validation_error').hide();
        jQuery('.icl_tm_finished:checked').each(function(){
            var field = jQuery(this).attr('name').replace(/finished/,'data');
            
            if(field == 'fields[body][data]'){
                var data = jQuery('*[name="'+field+'"]').val() + tinyMCE.get('fields[body][data]').getContent();
            }
            else if(jQuery(this).hasClass('icl_tmf_multiple')){
                var data = 1;
                jQuery('[name*="'+field+'"]').each(function(){
                    data = data * jQuery(this).val().length;
                });
            }else{
                var data = jQuery('[name="'+field+'"]*').val();    
            }
            if(!data){                
                jQuery('#icl_tm_validation_error').fadeIn();
                jQuery(this).removeAttr('checked');    
                icl_tm_update_complete_cb_status();                
                formnoerr = false;
            }
        });  
        
        return formnoerr;
    });
    
    if (jQuery('#radio-local').is(':checked')) {
      jQuery('#local_translations_add_translator_toggle').slideDown();
    }
    
    var icl_tm_users_quick_search = {
        
        attach_listener : function (){
            var searchTimer;        
            
            jQuery('#icl_quick_src_users').keydown( function(e){
                
                jQuery('#icl_tm_selected_user').val('');
                jQuery('#icl_quick_src_users').css('border-color', '#ff0000');
                icl_add_translators_form_check_submit();
                
                
                var t = jQuery(this);
                
                if( 13 == e.which ) {            
                    icl_tm_users_quick_search.update( t );
                    return false;
                }

                if( e.keyCode == 40 && jQuery('.icl_tm_auto_suggest_dd').length){
                    
                    jQuery('.icl_tm_auto_suggest_dd').focus();
                    
                    
                }else if( e.which >= 32 && e.which <=127 || e.which == 8) {            
                
                    jQuery('#icl_user_src_nf').remove();
                    
                    if( searchTimer ) clearTimeout(searchTimer);
                    
                    searchTimer = setTimeout(function(){
                        icl_tm_users_quick_search.update( t );
                    }, 400);
                
                }
                
                
                
            } ).attr('autocomplete','off');
            
            icl_tm_users_quick_search.select_listener();
            
            jQuery('#icl_quick_src_users').focus(function(){
                if(jQuery('.icl_tm_auto_suggest_dd').length){
                    jQuery('.icl_tm_auto_suggest_dd').css('visibility', 'visible');    
                }
            })
            
            
            jQuery('#icl_quick_src_users').blur(function(){
                setTimeout(function(){
                    if(jQuery('.icl_tm_auto_suggest_dd').length && !jQuery('select.icl_tm_auto_suggest_dd').is(':focus') ){
                        jQuery('.icl_tm_auto_suggest_dd').css('visibility', 'hidden');
                    }
                }, 500);
            })
            
            
            
        },
        
        update : function(input){
            
            var panel, params,
            minSearchLength = 2,
            q = input.val();

            panel = input.parent();
            
            if( q.length < minSearchLength ){
                jQuery('select.icl_tm_auto_suggest_dd', panel).remove();
                return;  
            } 

            params = {
                'action': 'icl_tm_user_search',
                'q': q
            };
            
            
            jQuery('img.waiting', panel).show();
            jQuery('select.icl_tm_auto_suggest_dd', panel).remove();
            
            jQuery.post( ajaxurl, params, function(response) {
                icl_tm_users_quick_search.ajax_response(response, params, panel, input);
            });
            
        },
        
        ajax_response : function (response, params, panel, input){
            
            jQuery('#icl_user_src_nf').remove();
            input.after(response);
            jQuery('img.waiting', panel).hide();
            
        },
        
        select_listener : function(){
            
            /*
            jQuery('.icl_tm_auto_suggest_dd option').live('click', function(){
                icl_tm_users_quick_search.select(jQuery(this).val());
            });
            */
            
            jQuery('.icl_tm_auto_suggest_dd').live('change', function(){
                icl_tm_users_quick_search.select(jQuery(this).val());
            });
            
            
            jQuery('.icl_tm_auto_suggest_dd').live('keydown', function(e){
                if(e.which == 13){
                    icl_tm_users_quick_search.select(jQuery(this).val());
                    e.preventDefault();
                }
            });
            
            return;
            /*
            jQuery('.icl_tm_auto_suggest_dd').live('change', function(){
                
                var spl = jQuery(this).val().split('|');
                jQuery('#icl_tm_selected_user').val(spl[0]);
                spl.shift();
                jQuery('#icl_quick_src_users').val(spl.join('|'));
                jQuery(this).remove();
            })  
            */  
        },
        
        select : function(val){
            var spl = val.split('|');
            jQuery('#icl_tm_selected_user').val(spl[0]);
            spl.shift();
            jQuery('#icl_quick_src_users').val(spl.join('|')).css('border-color', 'inherit');
            jQuery('.icl_tm_auto_suggest_dd').remove();
            icl_add_translators_form_check_submit();
        }
        
    }
    
    icl_tm_users_quick_search.attach_listener();
    
    
    icl_add_translators_form_check_submit();
    var icl_active_service = jQuery("input[name='services']:checked").val();
    
    
    jQuery('input[name=services]').change(function() {
      if (jQuery('#radio-local').is(':checked')) {
        jQuery('#local_translations_add_translator_toggle').slideDown();
      } else {
        jQuery('#local_translations_add_translator_toggle').slideUp();
      }
      icl_active_service = jQuery(this).val();
      icl_add_translators_form_check_submit();
    });

    jQuery('#edit-from').change(function() {
      icl_add_translators_form_check_submit();
    });

    jQuery('#edit-to').change(function() {
      icl_add_translators_form_check_submit();
    });

    jQuery('#icl_add_translator_submit').click(function() {
      var url = jQuery('#'+icl_active_service+'_setup_url').val();
      if (url !== undefined) {
        url = url.replace(/from_replace/, jQuery('#edit-from').val());
        url = url.replace(/to_replace/, jQuery('#edit-to').val());
        icl_thickbox_reopen(url);
        return false;
      }
      jQuery('#icl_tm_add_user_errors span').hide();
      if (jQuery('input[name=services]').val() == 'local' && jQuery('#icl_tm_selected_user').val() == 0){
          jQuery('#icl_tm_add_user_errors .icl_tm_no_to').show();
          return false;
      }
    });

    jQuery('#icl_add_translator_form_toggle').click(function() {
      jQuery('#icl_add_translator_form_wrapper').slideToggle(function(){
        if (jQuery('#icl_add_translator_form_wrapper').is(':hidden')) {
          var caption = jQuery('#icl_add_translator_form_toggle').val().replace(/<</, '>>');
        } else {
          var caption = jQuery('#icl_add_translator_form_toggle').val().replace(/>>/, '<<');
        }
        jQuery('#icl_add_translator_form_toggle').val(caption);
      });
      
      return false;
    });
    
    jQuery('#icl_side_by_site a[href=#cancel]').click(function(){
        var thisa = jQuery(this);
        jQuery.ajax({
            type: "POST", url: ajaxurl, data: 'action=dismiss_icl_side_by_site',
            success: function(msg){
                    thisa.parent().parent().fadeOut();
                }
            });
        return false;
    });

    
    if (typeof(icl_tb_init) != 'undefined') {
        icl_tb_init('a.icl_thickbox');
        icl_tb_set_size('a.icl_thickbox');
    }
    
    var cache = '&cache=1';
    if (location.href.indexOf("main.php&sm=translators") != -1 || location.href.indexOf('/post.php') != -1 || location.href.indexOf('/edit.php') != -1) {
        cache = '';    
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=get_translator_status" + cache + '&_icl_nonce=' + jQuery('#_icl_nonce_gts').val(),
        success: function(msg){
            if (cache == '') {
            }
        }
    });

    if(jQuery('#icl_tdo_options').length)
    jQuery('#icl_tdo_options').submit(iclSaveForm);     

    jQuery('.icl_tm_copy_link').click(function(){
        var type = jQuery(this).attr('id').replace(/^icl_tm_copy_link_/,'');                
        
        field = 'fields['+type+'][data]';
        var original = '';        

        
        if(0 == type.indexOf('field-')){
            type = type.replace(/ /g, '__20__');                
        }
        
        if(type=='body' || (0 == type.indexOf('field-') && jQuery('#icl_tm_original_'+type)[0].tagName != 'SPAN')){      
            
            original = jQuery('#icl_tm_original_'+type).val()            
            
            try{
                tinyMCE.get(field); // activate
                
            }catch(err){;} //backward compatibility
            
            if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {                
                
                if(tinyMCE.activeEditor.id != field){
                    for(i in tinyMCE.editors){
                        if(field == tinyMCE.editors[i].id){
                            ed = tinyMCE.editors[i];
                        }    
                    }
                }
                
                ed.focus();
                if (tinymce.isIE)
                    ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
                original = original.replace(/\n\n/g, '<br />');
                ed.execCommand('mceInsertContent', false, original);
            } else {                
                wpActiveEditor = field;
                edInsertContent(edCanvas, original);
            }
        }else{
            type = type.replace(/ /g, '__20__');            
            original = jQuery('#icl_tm_original_'+type).html();
            
            if(jQuery('#icl_tm_editor input[name="'+field+'"]').length){
                jQuery('#icl_tm_editor input[name="'+field+'"]').val(original);    
            }else if(jQuery('#icl_tm_editor textarea[name="'+field+'"]').length){
                jQuery('#icl_tm_editor textarea[name="'+field+'"]').val(original);    
            }            
            /*jQuery('#icl_tm_editor *[name="'+field+'"]').val(original);    */
            
        }
        jQuery(this).parent().fadeOut();
        return false;
    });
    
    // Translator notes - translation dashboard - start 
    jQuery('.icl_tn_link').click(function(){
        jQuery('.icl_post_note:visible').slideUp();
        thisl = jQuery(this);
        spl = thisl.attr('id').split('_');
        doc_id = spl[3];
        if(jQuery('#icl_post_note_'+doc_id).css('display') != 'none'){
            jQuery('#icl_post_note_'+doc_id).slideUp();
        }else{
            jQuery('#icl_post_note_'+doc_id).slideDown();
            jQuery('#icl_post_note_'+doc_id+' textarea').focus();
        }
        return false;
    });
    
    jQuery('.icl_post_note textarea').keyup(function(){
        if(jQuery.trim(jQuery(this).val())){
            jQuery('.icl_tn_clear').removeAttr('disabled');
        }else{
            jQuery('.icl_tn_clear').attr('disabled', 'disabled');
        }  
    });
    jQuery('.icl_tn_clear').click(function(){
        jQuery(this).closest('table').prev().val('');
        jQuery(this).attr('disabled','disabled');
    })
    jQuery('.icl_tn_save').click(function(){
        thisa = jQuery(this);
        thisa.closest('table').find('input').attr('disabled','disabled');
        tn_post_id = thisa.closest('table').find('.icl_tn_post_id').val();
        jQuery.ajax({
                type: "POST",
                url: icl_ajx_url,        
                data: "icl_ajx_action=save_translator_note&note="+thisa.closest('table').prev().val()+'&post_id='+tn_post_id + '&_icl_nonce=' + jQuery('#_icl_nonce_stn_' + tn_post_id).val(),
                success: function(msg){
                    thisa.closest('table').find('input').removeAttr('disabled');
                    thisa.closest('table').parent().slideUp();
                    icon_url = jQuery('#icl_tn_link_'+tn_post_id+' img').attr('src');
                    if(thisa.closest('table').prev().val()){
                        jQuery('#icl_tn_link_'+tn_post_id+' img').attr('src', icon_url.replace(/add_translation\.png$/, 'edit_translation.png'));
                    }else{
                        jQuery('#icl_tn_link_'+tn_post_id+' img').attr('src', icon_url.replace(/edit_translation\.png$/, 'add_translation.png'));
                    }
                }
        });    
        
    });
    // Translator notes - translation dashboard - end
    
    // MC Setup 
    jQuery('#icl_doc_translation_method').submit(iclSaveForm);    
    jQuery('#icl_page_sync_options').submit(iclSaveForm);    
    jQuery('form[name="icl_custom_tax_sync_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_custom_posts_sync_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_cf_translation"]').submit(iclSaveForm);
    
    if(jQuery.browser.msie){
        jQuery('#icl_translation_pickup_mode').submit(icl_tm_set_pickup_method);         
    }else{
        jQuery('#icl_translation_pickup_mode').live('submit', icl_tm_set_pickup_method);     
    }
    
    jQuery('#icl_tm_get_translations').live('click', icl_tm_pickup_translations);
    if(jQuery('#icl_sec_tic').length){
        icl_sec_tic_to = window.setTimeout(icl_sec_tic_decrement, 60000);
    }
    
    jQuery('#icl-translation-jobs th :checkbox').change(iclTmSelectAllJobs);
    jQuery('#icl-tm-jobs-cancel-but').click(iclTmCancelJobs);
    jQuery('#icl-translation-jobs td :checkbox').change(iclTmUpdateJobsSelection);
    
    iclTmPopulateParentFilter();
    jQuery('#icl_parent_filter_control').change(iclTmPopulateParentFilter);
    jQuery('form[name="translation-dashboard-filter"]').find('select[name="filter[from_lang]"]').change(iclTmPopulateParentFilter);
    
    jQuery('#icl_tm_jobs_dup_submit').click(function(){return confirm(jQuery(this).next().html());})
    
    jQuery('#icl_show_promo').click(function(){
        jQuery.ajax({type:"POST", url:ajaxurl, data: 'action=icl_tm_toggle_promo&value=0', success: function(){
            jQuery('#icl_show_promo').fadeOut(function(){jQuery('.icl-translation-services').    slideDown()});return false;    
        }})
    });
    jQuery('#icl_hide_promo').click(function(){
        jQuery.ajax({type:"POST", url:ajaxurl, data: 'action=icl_tm_toggle_promo&value=1', success: function(){
            jQuery('.icl-translation-services').slideUp(function(){jQuery('#icl_show_promo').fadeIn()});return false;
        }});
    })
    
    
    
});

function icl_save_dashboard_setting(setting, value, callback){
        jQuery('#icl_dashboard_ajax_working').fadeIn();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: 'icl_ajx_action=save_dashboard_setting&setting='+setting+'&value='+value+'_icl_nonce=' + jQuery('#_icl_nonce_sds').val(),
            success: function(msg){
                jQuery('#icl_dashboard_ajax_working').fadeOut();
                callback(msg);                
            }
        });         
}

function icl_add_translators_form_check_submit() {       
  jQuery('#icl_add_translator_submit').attr('disabled', 'disabled');
  
  if(jQuery('#edit-from').val() != 0 && jQuery('#edit-to').val() != 0 && jQuery('#edit-from').val() != jQuery('#edit-to').val()){
      if (jQuery('#radio-icanlocalize').is(':checked') || jQuery('#radio-local').is(':checked') && jQuery('#icl_tm_selected_user').val()) {
        jQuery('#icl_add_translator_submit').removeAttr('disabled');
      }
  }
  
}

function icl_tm_update_word_count_estimate(){
    icl_tm_enable_submit();
    var id = jQuery(this).val();
    var val = parseInt(jQuery('#icl-cw-'+id).html());
    var curval = parseInt(jQuery('#icl-tm-estimated-words-count').html());
    if(jQuery(this).attr('checked')){
        var newval = curval + val;        
    }else{
        var newval = curval - val;        
    }    
    jQuery('#icl-tm-estimated-words-count').html(newval);
    icl_tm_update_doc_count();    
}

function icl_tm_select_all_documents(){    
    if(jQuery(this).attr('checked')){
        jQuery('#icl-tm-translation-dashboard :checkbox').attr('checked','checked');    
        jQuery('#icl-tm-estimated-words-count').html(parseInt(jQuery('#icl-cw-total').html()));
    }else{
        jQuery('#icl-tm-translation-dashboard :checkbox').removeAttr('checked');    
        jQuery('#icl-tm-estimated-words-count').html('0');
    }
    icl_tm_update_doc_count();
    icl_tm_enable_submit();    
}

function icl_tm_update_doc_count(){
    dox = jQuery('#icl-tm-translation-dashboard td :checkbox:checked').length;
    jQuery('#icl-tm-sel-doc-count').html(dox);
    if(dox){
        jQuery('#icl-tm-doc-wrap').fadeIn();
    }else{
        jQuery('#icl-tm-doc-wrap').fadeOut();
    }    
}

function icl_tm_enable_submit(){
    var anyaction = false;
    jQuery('#icl_tm_languages :radio:checked').each(function(){
        if(jQuery(this).val() > 0){
            anyaction = true;
            return;
        }
    });
    
    if( jQuery('#icl-tm-translation-dashboard td :checkbox:checked').length > 0 && anyaction){
        jQuery('#icl_tm_jobs_submit').removeAttr('disabled');
    }else{
        jQuery('#icl_tm_jobs_submit').attr('disabled','disabled');
    }
}

function icl_tm_dup_warn(){
    dupsel = false;
    jQuery('#icl_tm_languages :radio:checked').each(function(){
        if(jQuery(this).val() == 2){
            dupsel = true;
            return;
        }
    });    
    if(dupsel) jQuery('#icl_dup_ovr_warn').fadeIn();
    else jQuery('#icl_dup_ovr_warn').fadeOut();
}


function icl_tm_assign_translator(){
    var thiss = jQuery(this);
    var translator_id = thiss.val();
    var translation_controls = thiss.parent().parent().find('.icl_tj_select_translator_controls');
    var job_id = translation_controls.attr('id').replace(/^icl_tj_tc_/,'');
    translation_controls.show();    
    translation_controls.find('.icl_tj_cancel').click(function(){
            thiss.val(jQuery('#icl_tj_ov_'+job_id).val());
            translation_controls.hide()
    });
    translation_controls.find('.icl_tj_ok').unbind('click').click(function(){icl_tm_assign_translator_request(job_id, translator_id, thiss)});
    
}

function icl_tm_assign_translator_request(job_id, translator_id, select){
    var translation_controls = select.parent().parent().find('.icl_tj_select_translator_controls');
    select.attr('disabled', 'disabled');
    translation_controls.find('.icl_tj_cancel, .icl_tj_ok').attr('disabled', 'disabled');
    var tdwrp = select.parent().parent();
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: 'icl_ajx_action=assign_translator&job_id='+job_id+'&translator_id='+translator_id+'&_icl_nonce=' + jQuery('#_icl_nonce_at').val(),
        success: function(msg){
            if(!msg.error){
                translation_controls.hide();    
                if(msg.service == 'icanlocalize'){
                    tdwrp.html(msg.message);
                }else{                    
                    jQuery('#icl_tj_ov_'+job_id).val(translator_id);
                }
            }else{
                //                
            }
            select.removeAttr('disabled');
            translation_controls.find('.icl_tj_cancel, .icl_tj_ok').removeAttr('disabled');
        }
    }); 
    
    return false;            
}

function icl_tm_update_complete_cb_status(){
    if(jQuery('#icl_tm_editor .icl_tm_finished:checked').length == jQuery('#icl_tm_editor .icl_tm_finished').length){
        jQuery('#icl_tm_editor :checkbox[name=complete]').removeAttr('disabled');
    }else{
        jQuery('#icl_tm_editor :checkbox[name=complete]').attr('disabled', 'disabled');        
    }    
}

function icl_tm_set_pickup_method(){  
    var thisf = jQuery(this);
    var thiss = thisf.find(':submit');
    thiss.attr('disabled', 'disabled').after(icl_ajxloaderimg);
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: 'icl_ajx_action=set_pickup_mode&'+thisf.serialize(),
        success: function(msg){
            if(!msg.error){
                jQuery('#icl_tm_pickup_wrap').load(location.href+' #icl_tm_pickup_wrap', 
                    function(resp){
                        jQuery(this).html(jQuery(resp).find('#icl_tm_pickup_wrap').html());                    
                        thiss.removeAttr('disabled').next().remove();
                    }
                )
            }else{
                alert(msg.error);
                thiss.removeAttr('disabled').next().remove();
            }
        }
    }); 
    return false;    
}

function icl_tm_pickup_translations(){
    var thisb = jQuery(this);
    thisb.attr('disabled', 'disabled').after(icl_ajxloaderimg);
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: 'icl_ajx_action=pickup_translations&_icl_nonce='+jQuery('#_icl_nonce_pickt').val(),
        success: function(msg){
            if(!msg.error){
                url_glue = (-1 == location.href.indexOf('?')) ? '?' : '&'; 
                jQuery('#icl_tm_pickup_wrap').load(location.href+url_glue+'icl_pick_message='+msg.fetched+' #icl_tm_pickup_wrap', function(resp){
                    jQuery(this).html(jQuery(resp).find('#icl_tm_pickup_wrap').html());                    
                    thisb.removeAttr('disabled').next().remove();
                })
            }else{
                alert(msg.error);
                thisb.removeAttr('disabled').next().remove();
            }
            
        }
    }); 
}


function icl_sec_tic_decrement(){
    var curval = parseInt(jQuery('#icl_sec_tic').html());
    if(curval > 0){
        jQuery('#icl_sec_tic').html(curval - 1);
        window.setTimeout(icl_sec_tic_decrement, 60000);
    }else{        
        jQuery('#icl_tm_get_translations').removeAttr('disabled');  
        jQuery('#icl_tm_get_translations').next().fadeOut();
    }    
}

/* MC Setup */

function iclTmSelectAllJobs(){
    if(jQuery(this).attr('checked')){
        jQuery('#icl-translation-jobs :checkbox').attr('checked', 'checked');
        jQuery('#icl-tm-jobs-cancel-but').removeAttr('disabled');
    }else{
        jQuery('#icl-translation-jobs :checkbox').removeAttr('checked');
        jQuery('#icl-tm-jobs-cancel-but').attr('disabled', 'disabled');        
    }
}

function iclTmCancelJobs(){
    
    var tm_prompt = jQuery('#icl-tm-jobs-cancel-msg').html();
    var in_progress = jQuery('tr.icl_tm_status_2 input:checkbox:checked').length;
    
    if(in_progress > 0){
        tm_prompt += "\n" + jQuery('#icl-tm-jobs-cancel-msg-2').html().replace(/%s/g, in_progress);    
        jQuery('tr.icl_tm_status_2 :checkbox:checked').parent().parent().addClass('icl_tm_row_highlight');
    }
    
    if(!confirm(tm_prompt)){
        jQuery('#icl-tm-jobs-form input[name=icl_tm_action]').val('jobs_filter');
        jQuery('tr.icl_tm_row_highlight').removeClass('icl_tm_row_highlight');
        return false;    
    }
    jQuery('#icl-tm-jobs-form input[name=icl_tm_action]').val('cancel_jobs');
    
    return true;
}

function iclTmUpdateJobsSelection(){
    if(jQuery('#icl-translation-jobs :checkbox:checked').length > 0){
        jQuery('#icl-tm-jobs-cancel-but').removeAttr('disabled');
        
        if(jQuery('#icl-translation-jobs td :checkbox:checked').length == jQuery('#icl-translation-jobs td :checkbox').length){
            jQuery('#icl-translation-jobs th :checkbox').attr('checked', 'checked');
        }else{
            jQuery('#icl-translation-jobs th :checkbox').removeAttr('checked');
        }
        
    }else{
        jQuery('#icl-tm-jobs-cancel-but').attr('disabled', 'disabled');        
    }
}

function iclTmPopulateParentFilter(){
    var val = jQuery('#icl_parent_filter_control').val();
    
    jQuery('#icl_parent_filter_drop').html(icl_ajxloaderimg);
    
    if(val){
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: 'action=icl_tm_parent_filter&type='+val+'&lang=' + jQuery('form[name="translation-dashboard-filter"]').find('select[name="filter[from_lang]"]').val()+'&parent_id='+jQuery('#icl_tm_parent_id').val()+'&parent_all='+jQuery('#icl_tm_parent_all').val(),
            success: function(msg){
                jQuery('#icl_parent_filter_drop').html(msg.html);
                
                //select page
                $('#filter[parent_id]').val(jQuery('#icl_tm_parent_id').val());
            }
        });  
    }else{
        jQuery('#icl_parent_filter_drop').html('');
    }
}
