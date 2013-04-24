<?php $this->noscript_notice() ?>

<?php if(get_post_meta($post->ID, '_icl_lang_duplicate_of', true)): ?>
<div class="icl_cyan_box"><?php 
    printf(__('This document is a duplicate of %s and it is maintained by WPML.', 'sitepress'), 
        '<a href="'.get_edit_post_link($translations[$this->get_default_language()]->element_id).'">' . 
        $translations[$this->get_default_language()]->post_title . '</a>');
?>    
    <p><input id="icl_translate_independent" class="button-secondary" type="button" value="<?php _e('Translate independently', 'sitepress') ?>" /></p>
    <?php wp_nonce_field('reset_duplication_nonce', '_icl_nonce_rd') ?>
    <i><?php printf(__('WPML will no longer synchronize this %s with the original content.', 'sitepress'), $post->post_type); ?></i>
</div>

<span style="display:none"> <?php /* Hide everything else; */ ?>
<?php endif; ?>

<div id="icl_document_language_dropdown" class="icl_box_paragraph"> 
    <?php printf(__('Language of this %s', 'sitepress'), strtolower($wp_post_types[$post->post_type]->labels->singular_name != "" ? $wp_post_types[$post->post_type]->labels->singular_name : $wp_post_types[$post->post_type]->labels->name)); ?>&nbsp;
    
    <select name="icl_post_language" id="icl_post_language">
    <?php foreach($active_languages as $lang):?>
    <?php if(isset($translations[$lang['code']]->element_id) && $translations[$lang['code']]->element_id != $post->ID) continue ?>
    <option value="<?php echo $lang['code'] ?>" <?php if($selected_language==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?>&nbsp;</option>
    <?php endforeach; ?>
    </select> 
    <input type="hidden" name="icl_trid" value="<?php echo $trid ?>" />
</div>

<div id="translation_of_wrap">
    <?php if( ($selected_language != $default_language || (isset($_GET['lang']) && $_GET['lang']!=$default_language)) && 'all' != $this->get_current_language() ): ?>
        
        <div id="icl_translation_of_panel" class="icl_box_paragraph">
        <?php echo __('This is a translation of', 'sitepress') ?>&nbsp;
        <select name="icl_translation_of" id="icl_translation_of"<?php if((empty($_GET['action']) || $_GET['action'] != 'edit') && $trid) echo ' disabled="disabled"';?>>
            <?php if($source_language == null || $source_language == $default_language): ?>
                <?php if($trid): ?>
                    <option value="none"><?php echo __('--None--', 'sitepress') ?></option>                    
                    <?php
                        //get source
                        $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$default_language}'");                        
                        if(!$src_language_id) {
                            // select the first id found for this trid
                            $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid}");
                        }                                                      
                        if($src_language_id && $src_language_id != $post->ID) {
                            $src_language_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = {$src_language_id}");                            
                        }
                    ?>
                    <?php if(isset($src_language_title) && !isset($_GET['icl_ajx'])): ?>
                        <option value="<?php echo $src_language_id ?>" selected="selected"><?php echo $src_language_title; ?>&nbsp;</option>
                    <?php endif; ?>
                <?php else: ?>
                    <option value="none" selected="selected"><?php echo __('--None--', 'sitepress') ?></option>
                <?php endif; ?>
                
                <?php foreach($untranslated as $translation_of_id => $translation_of_title):?>
                    <?php //if (!empty($src_language_id) && $translation_of_id != $src_language_id): ?>
                        <option value="<?php echo $translation_of_id ?>"><?php echo $translation_of_title ?>&nbsp;</option>
                    <?php //endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if($trid): ?>
                    <?php
                        // add the source language
                        $src_language_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$source_language}'");
                        if($src_language_id) {
                            $src_language_title = $wpdb->get_var("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = {$src_language_id}");
                        }
                    ?>
                    <?php if(isset($src_language_title)): ?>
                        <option value="<?php echo $src_language_id ?>" selected="selected"><?php echo $src_language_title ?></option>
                    <?php endif; ?>
                <?php else: ?>   
                    <option value="none" selected="selected"><?php echo __('--None--', 'sitepress') ?></option>
                <?php endif; ?>
            <?php endif; ?>
        </select>

        </div>
    <?php endif; ?>
</div><!--//translation_of_wrap--><?php // don't delete this html comment ?>

<br clear="all" />

<?php if(isset($_GET['action']) && $_GET['action'] == 'edit' && $trid): ?>       
    
    <?php do_action('icl_post_languages_options_before', $post->ID);?>

    <div id="icl_translate_options">
    <?php
        // count number of translated and un-translated pages.
        $translations_found = 0;
        $untranslated_found = 0;
        foreach($active_languages as $lang) {
            if($selected_language==$lang['code']) continue;
            if(isset($translations[$lang['code']]->element_id)) {
                $translations_found += 1;
            } else {
                $untranslated_found += 1;
            }
        }
    ?>
    
    <?php if($untranslated_found > 0 && (empty($iclTranslationManagement->settings['doc_translation_method']) || $iclTranslationManagement->settings['doc_translation_method'] != ICL_TM_TMETHOD_PRO)): ?>    
        <?php if($this->get_icl_translation_enabled()):?>
            <p style="clear:both;"><b><?php _e('or, translate manually:', 'sitepress'); ?> </b>
        <?php else: ?>
            <p style="clear:both;"><b><?php _e('Translate yourself', 'sitepress'); ?></b>
        <?php endif; ?>
        <table width="100%" class="icl_translations_table">
        <tr>
            <th>&nbsp;</th>
            <th align="right"><?php _e('Translate', 'sitepress') ?></th>
            <th align="right" width="10" style="padding-left:8px;"><?php _e('Duplicate', 'sitepress') ?></th>
        </tr>            
        <?php $oddev = 1; ?>
        <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>        
        <tr <?php if($oddev < 0): ?>class="icl_odd_row"<?php endif; ?>>            
            <?php if(!isset($translations[$lang['code']]->element_id)):?>                
                <?php $oddev = $oddev*-1; ?>
                <td style="padding-left: 4px;">
                    <?php echo $lang['display_name'] ?>
                </td>
                <?php
                    $add_anchor =  __('add translation','sitepress');
                    $img = 'add_translation.png';
                    if(!empty($iclTranslationManagement->settings['doc_translation_method']) && $iclTranslationManagement->settings['doc_translation_method'] == ICL_TM_TMETHOD_EDITOR){
                            $job_id = $iclTranslationManagement->get_translation_job_id($trid, $lang['code']);
                            
                            $args = array('lang_from'=>$selected_language, 'lang_to'=>$lang['code'], 'job_id'=>@intval($job_id));
                            $current_user_is_translator = $iclTranslationManagement->is_translator(get_current_user_id(), $args);
                            
                            if($job_id){
                                $job_details = $iclTranslationManagement->get_translation_job($job_id);
                                
                                if($current_user_is_translator){
                                    if($job_details->status == ICL_TM_IN_PROGRESS){
                                        $add_anchor =  __('in progress','sitepress');
                                        $img = 'in-progress.png';
                                    }
                                }else{
                                    $tres = $wpdb->get_row($wpdb->prepare("
                                        SELECT s.* FROM {$wpdb->prefix}icl_translation_status s 
                                            JOIN {$wpdb->prefix}icl_translate_job j ON j.rid = s.rid
                                            WHERE job_id=%d", $job_id));
                                    if($tres->status == ICL_TM_IN_PROGRESS){
                                        $img = 'edit_translation_disabled.png';
                                        $add_anchor =  sprintf(__('In progress (by a different translator). <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');    
                                    }elseif($tres->status == ICL_TM_NOT_TRANSLATED || $tres->status == ICL_TM_WAITING_FOR_TRANSLATOR){
                                        $img = 'add_translation_disabled.png';
                                        $add_anchor = sprintf(__('You are not the translator of this document. <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');
                                    }elseif($tres->status == ICL_TM_NEEDS_UPDATE || $tres->status == ICL_TM_COMPLETE){
                                        $img = 'edit_translation_disabled.png';
                                        $add_anchor = sprintf(__('You are not the translator of this document. <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');
                                    }
                                    
                                }
                                if($current_user_is_translator){
                                    $add_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&job_id='.$job_id);        
                                }else{
                                    $add_link = '#';
                                    $add_anchor =  sprintf(__('In progress (by a different translator). <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');    
                                }
                                
                            }else{                                
                                if($current_user_is_translator){
                                    $add_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&icl_tm_action=create_job&iclpost[]='.
                                    $post->ID.'&translate_to['.$lang['code'].']=1&iclnonce=' . wp_create_nonce('pro-translation-icl'));
                                }else{
                                    $add_link = '#';
                                    $img = 'add_translation_disabled.png';
                                    $add_anchor = sprintf(__('You are not the translator of this document. <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');
                                }
                            }                                                    
                    }else{     
                        $add_link = admin_url("post-new.php?post_type={$post->post_type}&trid=" . 
                            $trid . "&lang=" . $lang['code'] . "&source_lang=" . $selected_language);    
                    }                                        
                ?>
                <td align="right">
                <?php if($add_link == '#'): 
                    icl_pop_info($add_anchor, ICL_PLUGIN_URL . '/res/img/' .$img, array('icon_size' => 16, 'but_style'=>array('icl_pop_info_but_noabs')));                    
                 else: ?>
                <a href="<?php echo $add_link?>" title="<?php echo esc_attr($add_anchor) ?>"><img  border="0" src="<?php 
                    echo ICL_PLUGIN_URL . '/res/img/' . $img ?>" alt="<?php echo esc_attr($add_anchor) ?>" width="16" height="16"  /></a>
                <?php endif; ?>
                    
                </td>
                <td align="right">
                    <?php 
                        // do not allow creating duplicates for posts that are being translated
                        $ddisabled = '';
                        $dtitle = esc_attr__('create duplicate', 'sitepress');
                        if(defined('WPML_TM_VERSION')){
                            $translation_id = $wpdb->get_var($wpdb->prepare("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code='%s'"
                                , $trid, $lang['code']));                    
                            if($translation_id){
                                $translation_status = $wpdb->get_var($wpdb->prepare("
                                    SELECT status FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d"
                                , $translation_id));
                                if(!is_null($translation_status) && $translation_status < ICL_TM_COMPLETE){
                                    $ddisabled = ' disabled="disabled"';
                                    $dtitle    = esc_attr__("Can't create a duplicate. A translation is in progress.", 'sitepress');
                                }
                            }
                        }
                        // do not allow creating duplicates for posts for which parents are not translated
                        if($post->post_parent){
                            $parent_tr = icl_object_id($post->post_parent, $post->post_type, false, $lang['code']);
                            if(is_null($parent_tr)){
                                $ddisabled = ' disabled="disabled"';
                                $dtitle    = esc_attr__("Can't create a duplicate. The parent of this post is not translated.", 'sitepress');
                            }
                        }
                    ?>                
                    <input<?php echo $ddisabled?> type="checkbox" name="icl_dupes[]" value="<?php echo $lang['code'] ?>" title="<?php echo $dtitle ?>" />
                </td>
                
            <?php endif; ?>        
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" align="right">
                <input id="icl_make_duplicates" type="button" class="button-secondary" value="<?php echo esc_attr_e('Duplicate', 'sitepress') ?>" disabled="disabled" style="display:none;" />
                <?php wp_nonce_field('make_duplicates_nonce', '_icl_nonce_mdup'); ?>
            </td>
        </tr>
        </table>
        
        </p>
    <?php endif; ?>
    <?php if($translations_found > 0): ?>    
    <?php if(!empty($iclTranslationManagement)){ $dupes = $iclTranslationManagement->get_duplicates($post->ID); } ?>
     <div class="icl_box_paragraph">
        
            <b><?php _e('Translations', 'sitepress') ?></b>
            (<a class="icl_toggle_show_translations" href="#" <?php if(empty($this->settings['show_translations_flag'])):?>style="display:none;"<?php endif;?>><?php _e('hide','sitepress')?></a><a class="icl_toggle_show_translations" href="#" <?php if(!empty($this->settings['show_translations_flag'])):?>style="display:none;"<?php endif;?>><?php _e('show','sitepress')?></a>)                
            <?php wp_nonce_field('toggle_show_translations_nonce', '_icl_nonce_tst') ?>  
        <table width="100%" class="icl_translations_table" id="icl_translations_table" <?php if(empty($this->settings['show_translations_flag'])):?>style="display:none;"<?php endif;?>>        
        <?php $oddev = 1; ?>
        <?php foreach($active_languages as $lang): if($selected_language==$lang['code']) continue; ?>
        <tr <?php if($oddev < 0): ?>class="icl_odd_row"<?php endif; ?>>
            <?php if(isset($translations[$lang['code']]->element_id)):?>
                <?php 
                    $oddev = $oddev*-1;
                    $img = 'edit_translation.png';
                    $edit_anchor = __('edit','sitepress');
                    list($needs_update, $in_progress) = $wpdb->get_row($wpdb->prepare("
                        SELECT needs_update, status = ".ICL_TM_IN_PROGRESS." FROM {$wpdb->prefix}icl_translation_status s JOIN {$wpdb->prefix}icl_translations t ON t.translation_id = s.translation_id
                        WHERE t.trid = %d AND t.language_code = '%s'
                    ", $trid, $lang['code']), ARRAY_N);           
                    $source_language_code  = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $trid));
                    switch($iclTranslationManagement->settings['doc_translation_method']){
                        case ICL_TM_TMETHOD_EDITOR:
                            $job_id = $iclTranslationManagement->get_translation_job_id($trid, $lang['code']);
                            
                            $args = array('lang_from'=>$selected_language, 'lang_to'=>$lang['code'], 'job_id'=>@intval($job_id));
                            $current_user_is_translator = $iclTranslationManagement->is_translator(get_current_user_id(), $args);
                            
                            if($needs_update){
                                $img = 'needs-update.png';
                                $edit_anchor = __('Update translation','sitepress');
                                if($current_user_is_translator){
                                    $edit_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&job_id='.$job_id);    
                                    //$edit_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&icl_tm_action=create_job&iclpost[]='.
                                    //    $post->ID.'&translate_to['.$lang['code'].']=1&iclnonce=' . wp_create_nonce('pro-translation-icl'));
                                }else{
                                    $edit_link = '#';
                                    $img = 'edit_translation_disabled.png';
                                    $edit_anchor = sprintf(__('You are not the translator of this document. <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');
                                }
                            }else{
                                if($lang['code'] == $source_language_code){
                                    $edit_link = '#';
                                    $img = 'edit_translation_disabled.png';
                                    $edit_anchor = __("You can't edit the original document using the translation editor",'sitepress');
                                }elseif($current_user_is_translator){
                                    $edit_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&job_id='.$job_id);    
                                }else{
                                    $edit_link = '#';
                                    $img = 'edit_translation_disabled.png';
                                    $edit_anchor = sprintf(__('You are not the translator of this document. <a%s>Learn more</a>.','sitepress'), ' href="http://wpml.org/?page_id=52218"');
                                }
                            }
                            break;                        
                        case ICL_TM_TMETHOD_PRO:
                            $job_id = $iclTranslationManagement->get_translation_job_id($trid, $lang['code']);
                            if($in_progress){
                                $img = 'in-progress.png';
                                $edit_link = '#';
                                $edit_anchor = __('Translation is in progress','sitepress');
                            }elseif($needs_update){
                                $img = 'needs-update.png';
                                $edit_anchor = __('Update translation','sitepress');
                                $qs = array();
                                if(!empty($_SERVER['QUERY_STRING']))
                                foreach($_exp = explode('&', $_SERVER['QUERY_STRING']) as $q=>$qv){
                                    $__exp = explode('=', $qv);
                                    $__exp[0] = preg_replace('#\[(.*)\]#', '', $__exp[0]);
                                    if(!in_array($__exp[0], array('icl_tm_action', 'translate_from', 'translate_to', 'iclpost', 'service', 'iclnonce'))){
                                        $qs[$q] = $qv;
                                    }
                                }                                
                                $edit_link = admin_url('post.php?'.join('&', $qs).'&icl_tm_action=send_jobs&translate_from='.$source_language
                                    .'&translate_to['.$lang['code'].']=1&iclpost[]='.$post->ID
                                    .'&service=icanlocalize&iclnonce=' . wp_create_nonce('pro-translation-icl')); 
                            }else{
                                $edit_link = admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&job_id='.$job_id);    
                            }
                            break;
                        default:
                            $edit_link = get_edit_post_link($translations[$lang['code']]->element_id);    
                    }
                ?>
                <td style="padding-left: 4px;">
                    <?php echo $lang['display_name'] ?>
                    <?php if(isset($dupes[$lang['code']])) echo ' (' . __('duplicate', 'sitepress') . ')'; ?>
                </td>
                <td align="right" >
                
                <?php if($edit_link == '#'): 
                    icl_pop_info($edit_anchor, ICL_PLUGIN_URL . '/res/img/' .$img, array('icon_size' => 16, 'but_style'=>array('icl_pop_info_but_noabs')));                    
                else: ?>
                <a href="<?php echo $edit_link ?>" title="<?php echo esc_attr($edit_anchor) ?>"><img border="0" src="<?php 
                    echo ICL_PLUGIN_URL . '/res/img/' . $img ?>" alt="<?php echo esc_attr($edit_anchor) ?>" width="16" height="16" /></a>                    
                <?php endif; ?>    
                    
                </td>
                
            <?php endif; ?>        
        </tr>
        <?php endforeach; ?>
        </table>
       
       </div>         
        
    <?php endif; ?>
    
    
    
    </div>
<?php endif; ?>

<?php do_action('icl_post_languages_options_after') ?>

<?php if(get_post_meta($post->ID, '_icl_lang_duplicate_of', true)): ?>
</span> <?php /* Hide everything else; */ ?>
<?php else: ?>


<?php 
$show_dup_button = false;
$tr_original_id = 0;
if(!empty($translations)) foreach($translations as $lang=>$tr){
    if($tr->original){
        $lang_details = $this->get_language_details($lang);
        $original_language = $lang_details['display_name'];
        $tr_original_id = $tr->element_id;
    } 
    if($tr->element_id == $post->ID){
        $show_dup_button = true;
    }
}        

?>
<?php if($tr_original_id != $post->ID && $show_dup_button): ?>
    <?php wp_nonce_field('set_duplication_nonce', '_icl_nonce_sd') ?>
    <input id="icl_set_duplicate" type="button" class="button-secondary" value="<?php printf(__('Overwrite with %s content.', 'sitepress'), $original_language) ?>" style="float: left;" />
    <span style="display: none;"><?php echo esc_js(sprintf(__('The current content of this %s will be permanently lost. WPML will copy the %s content and replace the current content.', 'sitepress'), $post->post_type, $original_language)); ?></span>
    <?php icl_pop_info(__("This operation will synchronize this translation with the original language. When you edit the original, this translation will update immediately. It's meant when you want the content in this language to always be the same as the content in the original language.", 'sitepress'), 'question'); ?>
    <br clear="all" />
    
    
<?php endif; ?>
    
<?php endif; ?>

