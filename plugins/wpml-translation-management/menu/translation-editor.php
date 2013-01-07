<?php
$job = $iclTranslationManagement->get_translation_job((int)$_GET['job_id'], false, true, 1); // don't include not-translatable and auto-assign

if(empty($job)){
    $job_checked = true;
    include WPML_TM_PATH . '/menu/translations-queue.php';
    return;
}
$rtl_original = $sitepress->is_rtl($job->source_language_code);
$rtl_translation = $sitepress->is_rtl($job->language_code);
$rtl_original_attribute = $rtl_original ? ' dir="rtl"' : ' dir="ltr"';
$rtl_translation_attribute = $rtl_translation ? ' dir="rtl"' : ' dir="ltr"';

?>
<div class="wrap icl-translation-editor">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php echo __('Translation editor', 'wpml-translation-management') ?></h2>    
    
    <?php do_action('icl_tm_messages'); ?>
    <?php 
    $opost = get_post($job->original_doc_id);
    if(!empty($opost) && ($opost->post_status == 'draft' || $opost->post_status == 'private') && $opost->post_author != $current_user->data->ID){
        $elink1 = '<i>';
        $elink2 = '</i>';
    }else{
        $elink1 = sprintf('<a href="%s">', get_permalink($job->original_doc_id));
        $elink2 = '</a>';
    }
    
    ?>
    <p class="updated fade"><?php printf(__('You are translating %s from %s to %s.', 'wpml-translation-management'), 
        TranslationManagement::tm_post_link($job->original_doc_id), $job->from_language, $job->to_language); ?></p>
    
    <?php if($translators_note = get_post_meta($job->original_doc_id, '_icl_translator_note', true)): ?>
    <i><?php _e('Note for translator', 'wpml-translation-management'); ?></i><br />
    <div class="icl_cyan_box">        
        <?php echo $translators_note ?>
    </div>
    <?php endif; ?>
    
    <form id="icl_tm_editor" method="post" action="">
    <input type="hidden" name="icl_tm_action" value="save_translation" />
    <input type="hidden" name="job_id" value="<?php echo esc_attr($_GET['job_id']) ?>" />
    <div id="dashboard-widgets-wrap">
        <?php $icl_tm_all_finished = true; ?>
        <?php foreach($job->elements as $element): ?>    
        
        <?php 
            if(empty($element->field_data)) continue;
            $_iter = !isset($_iter) ? 1 : $_iter + 1; 
            if(!$element->field_finished){
                $icl_tm_all_finished = false;
            }
        ?>        
        <div class="metabox-holder" id="icl-tranlstion-job-elements-<?php echo $_iter ?>">
            <div class="postbox-container icl-tj-postbox-container-<?php echo $element->field_type ?>">
                <div class="meta-box-sortables ui-sortable" id="icl-tranlstion-job-sortables-<?php echo $_iter ?>">
                    <div class="postbox" id="icl-tranlstion-job-element-<?php echo $_iter ?>">
                        <div title="<?php _e('Click to toggle', 'wpml-translation-management')?>" class="handlediv">
                            <br />
                        </div>
                        <?php 
                            // allow custom field names to be filtered
                            if(0 === strpos($element->field_type, 'field-')){                                
                                $element_field_type  = apply_filters('icl_editor_cf_name', $element->field_type);                                
                                $element_field_style = 1;
                                $element_field_style = apply_filters('icl_editor_cf_style', $element_field_style, $element->field_type);                                
                            }else{
                                $element_field_type = $element->field_type;
                                $element_field_style = false;
                            }                            
                        ?>
                        <h3 class="hndle"><?php echo $element_field_type  ?></h3>
                        <div class="inside">
                            <?php 
                                // allow custom field descriptions to be set/filtered
                                if(0 === strpos($element->field_type, 'field-')){
                                    $icl_editor_cf_description = apply_filters('icl_editor_cf_description', '', $element->field_type);    
                                    if($icl_editor_cf_description !== null){
                                        echo '<p class="icl_tm_field_description">' . $icl_editor_cf_description . '</p>';
                                    }
                                }
                            ?>
                            <?php /* TRANSLATED CONTENT */ ?>
                            <?php 
                                $icl_tm_original_content = TranslationManagement::decode_field_data($element->field_data, $element->field_format);
                                $icl_tm_translated_content = TranslationManagement::decode_field_data($element->field_data_translated, $element->field_format);
                                if($element->field_type=='tags' || $element->field_type=='categories'){
                                    $taxonomy = $element->field_type == 'tags' ? 'post_tag' : 'category';
                                    $icl_tm_translated_taxs[$element->field_type] = 
                                        TranslationManagement::determine_translated_taxonomies($icl_tm_original_content, $taxonomy, $job->language_code);
                                }   
                                                             
                                $translatable_taxonomies = $sitepress->get_translatable_taxonomies(false, $job->original_post_type);
                                if(in_array($element->field_type, $translatable_taxonomies)){
                                    $taxonomy = $element->field_type;
                                    $icl_tm_translated_taxs[$element->field_type] = 
                                        TranslationManagement::determine_translated_taxonomies($icl_tm_original_content, $taxonomy, $job->language_code);
                                };
                            ?>
                            <p>
                                <?php _e('Translated content', 'wpml-translation-management'); echo ' - ' . $job->to_language; ?>
                                <?php if(empty($icl_tm_translated_content)):?>
                                <span>| &nbsp;<a class="icl_tm_copy_link" id="icl_tm_copy_link_<?php echo $element->field_type 
                                    ?>" href="#"><?php printf(__('Copy from %s', 'wpml-translation-management'), $job->from_language)?></a></span>
                                <?php endif; ?>
                            </p>
                            
                            <?php // CASE 1 - body *********************** ?>
                            <?php if($element->field_type=='body'): ?>
                            <div id="poststuff">
                            <?php 
                                global $post;
                                if(is_null($post)) $post = clone $opost;
                                if(version_compare($wp_version, '3.3', '>=')){
                                    $settings = array(
                                        'media_buttons'     => false,
                                        'textarea_name'     => 'fields['.$element->field_type.'][data]',
                                        'textarea_rows'     => 20,
                                        'editor_css'        => $rtl_translation ? ' <style type="text/css">.wp-editor-container textarea.wp-editor-area{direction:rtl;}</style>' : ''
                                    );
                                    wp_editor($icl_tm_translated_content, 'fields['.$element->field_type.'][data]', $settings);                               
                                }else{
                                    the_editor($icl_tm_translated_content, 'fields['.$element->field_type.'][data]', false, false); 
                                }
                            ?>
                            </div>    
                                                                          
                            <?php // CASE 2 - csv_base64 *********************** ?>         
                            <?php elseif($element->field_format == 'csv_base64'): ?>
                            <?php foreach($icl_tm_original_content as $k=>$c): ?>
                            <?php 
                                // if have we added/removed/replaced attached taxonomies check for existing translations!
                                $__is_translated = isset($icl_tm_translated_taxs[$element->field_type]) && !empty($icl_tm_translated_taxs[$element->field_type][$k]);
                                if((empty($icl_tm_translated_content[$k]) && $__is_translated) || ($__is_translated && ($icl_tm_translated_content[$k] != $icl_tm_translated_taxs[$element->field_type][$k]))){                                
                                    $icl_tm_translated_content[$k] = $icl_tm_translated_taxs[$element->field_type][$k];    
                                    $icl_tm_f_translated = true;
                                }else{
                                    $icl_tm_f_translated = false;
                                }
                            ?>
                            <label><input class="icl_multiple" type="text" name="fields[<?php echo htmlspecialchars($element->field_type) 
                                ?>][data][<?php echo $k ?>]" value="<?php if(isset($icl_tm_translated_content[$k])) 
                                    echo esc_attr($icl_tm_translated_content[$k]); ?>"<?php echo $rtl_translation_attribute; ?> /></label>
                            <?php if($icl_tm_f_translated): ?>
                            <div class="icl_tm_tf"><?php _e('Translated field', 'wpml-translation-management'); ?></div>
                            <?php endif; ?>
                            <?php endforeach;?>
                            
                            <?php // CASE 3 - multiple lines *********************** ?>         
                            <?php elseif(0 === strpos($element->field_type, 'field-') && $element_field_style == 1): ?>
                                <textarea style="width:100%;" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][data]"<?php 
                                    echo $rtl_translation_attribute; ?>><?php echo esc_html($icl_tm_translated_content); ?></textarea>

                            <?php // CASE 4 - wysiwyg *********************** ?>         
                            <?php elseif(0 === strpos($element->field_type, 'field-') && $element_field_style == 2): 
                                    if(version_compare($wp_version, '3.3', '>=')){
                                        $settings = array(
                                            'media_buttons'     => false,
                                            'textarea_name'     => 'fields['.$element->field_type.'][data]',
                                            'textarea_rows'     => 4
                                        );
                                        wp_editor($icl_tm_translated_content, 'fields['.$element->field_type.'][data]', $settings);
                                    }else{                                        
                                        ?>
                                        <textarea style="width:100%;" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][data]"<?php 
                                    echo $rtl_translation_attribute; ?>><?php echo esc_html($icl_tm_translated_content); ?></textarea>
                                        <?php 
                                    }
                            ?>
                            <?php // CASE 5 - one-liner *********************** ?>         
                            <?php else: ?>
                            <label><input type="text" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][data]" value="<?php 
                                echo esc_attr($icl_tm_translated_content); ?>"<?php echo $rtl_translation_attribute; ?> /></label>
                            <?php endif; ?> 
                            
                            <p><label><input class="icl_tm_finished<?php if($element->field_format == 'csv_base64'): ?> icl_tmf_multiple<?php endif;
                                ?>" type="checkbox" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][finished]" value="1" <?php 
                                if($element->field_finished): ?>checked="checked"<?php endif;?> />&nbsp;<?php 
                                _e('This translation is finished.', 'wpml-translation-management')?></label>                                
                                <span class="icl_tm_error" style="display: none;"><?php _e('This field cannot be empty', 'wpml-translation-management') ?></span>
                                </p>                            
                                
                            <br />                                                            
                            <?php /* TRANSLATED CONTENT */ ?>
                            
                            <?php /* ORIGINAL CONTENT */ ?>
                            <p><?php _e('Original content', 'wpml-translation-management'); echo ' - ' . $job->from_language; ?></p>
                            <?php           
                                
                                // get terms descriptions
                                if($element->field_type=='tags' || $element->field_type=='categories' || in_array($element->field_type, $translatable_taxonomies)){
                                    
                                    if($element->field_type=='tags'){
                                        $term_taxonomy = 'post_tag';    
                                    }elseif($element->field_type=='categories'){
                                        $term_taxonomy = 'category';    
                                    }else{
                                        $term_taxonomy = $element->field_type;
                                    }
                                    if(!empty($icl_tm_original_content)){
                                        $res = $wpdb->get_results($wpdb->prepare(
                                            "SELECT t.name, x.description FROM {$wpdb->terms} t 
                                                JOIN {$wpdb->term_taxonomy} x ON  x.term_id = t.term_id
                                            WHERE description<>'' && x.taxonomy=%s 
                                                AND t.name IN ('".join("','", $wpdb->escape($icl_tm_original_content))."')",
                                            $term_taxonomy
                                        ));
                                        $term_descriptions = array();
                                        foreach($res as $row){
                                            $term_descriptions[$row->name] = $row->description;
                                        }
                                    }
                                }
                                                      
                                if($element->field_type=='body' || $element_field_style == 2){
                                    $icl_tm_original_content_html = esc_html($icl_tm_original_content);
                                    $icl_tm_original_content = apply_filters('the_content', $icl_tm_original_content);
                                    $icl_wysiwyg_height = $element->field_type == 'body' ? get_option('default_post_edit_rows', 20)*20 : 100;
                                    ?>
                                    <div class="icl_tm_orig_toggle">
                                        <a class="icl_tm_toggle_html" href="#"><?php _e('HTML', 'wpml-translation-management') ?></a>
                                        <a class="icl_tm_toggle_visual active" href="#"><?php _e('Visual', 'wpml-translation-management') ?></a>
                                        <br clear="all">
                                    </div>
                                    <?php
                                }
                            ?>
                            <div class="icl-tj-original<?php if(0 === strpos($element->field_type, 'field-')) :?> icl-tj-original-cf<?php endif; ?>" >                                
                                <?php if($element->field_type=='body' || $element_field_style == 2): ?>
                                <div class="icl_single visual"<?php echo $rtl_original_attribute; ?>>

                                <iframe src="<?php echo admin_url('admin-ajax.php?action=show_post_content&field_type='.
                                    $element->field_type.'&post_id='.$job->original_doc_id) . '&rtl=' . intval($rtl_original); 
                                    ?>" width="100%" height="<?php echo $icl_wysiwyg_height ?>" frameborder="0"></iframe>
                                
                                <br clear="all"/></div>
                                <div class="html"><textarea id="icl_tm_original_<?php echo $element->field_type ?>" readonly="readonly"><?php 
                                    echo $icl_tm_original_content_html ?></textarea></div>
                                <?php elseif($element->field_format == 'csv_base64'): ?>
                                <?php foreach($icl_tm_original_content as $c): ?>
                                <div class="icl_multiple"<?php echo $rtl_original_attribute; ?>>
                                    <div style="float: left;margin-right:4px;"><?php echo $c ?></div>
                                    <?php if(isset($term_descriptions[$c])) icl_pop_info($term_descriptions[$c], 'info', array('icon_size'=>10)); ?>
                                    <br clear="all"/>
                                </div>
                                <?php endforeach;?>
                                <?php else: ?>
                                <div class="icl_single"<?php if ($rtl_original) echo ' dir="rtl" style="text-align:right;"'; else echo ' dir="ltr" style="text-align:left;"'; ?>><span style="white-space:pre-wrap;" id="icl_tm_original_<?php echo str_replace(' ', '__20__', $element->field_type) ?>"><?php echo esc_html($icl_tm_original_content) ?></span><br clear="all"/></div>
                                <?php endif; ?>
                            </div>
                            <?php /* ORIGINAL CONTENT */ ?>
                            
                            <input type="hidden" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][format]" value="<?php echo $element->field_format ?>" />
                            <input type="hidden" name="fields[<?php echo htmlspecialchars($element->field_type) ?>][tid]" value="<?php echo $element->tid ?>" />
                            
                            <?php if(!$element->field_finished && !empty($job->prev_version)): ?>                            
                                <?php 
                                    $prev_value = '';
                                    foreach($job->prev_version->elements as $pel){
                                        if($element->field_type == $pel->field_type){
                                            $prev_value = TranslationManagement::decode_field_data($pel->field_data, $pel->field_format);
                                        }    
                                    }
                                    if($element->field_format != 'csv_base64'){
                                        $diff = wp_text_diff( $prev_value, TranslationManagement::decode_field_data($element->field_data, $element->field_format) );  
                                    }
                                    if(!empty($diff)){
                                        ?>
                                        <p><a href="#" onclick="jQuery(this).parent().next().slideToggle();return false;"><?php 
                                            _e('Show Changes', 'sitepress'); ?></a></p>
                                        <div class="icl_tm_diff">
                                            <?php echo $diff ?>
                                        </div>
                                        <?php 
                                    }
                                ?>
                            <?php endif;?>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <br clear="all" />
    <label><input type="checkbox" name="complete" <?php if(!$icl_tm_all_finished): ?>disabled="disabled"<?php endif; ?> <?php 
    if($job->translated):?> checked="checked"<?php endif; ?> value="1" />&nbsp;<?php 
        _e('Translation of this document is complete', 'wpml-translation-management')?></label>
    
    <div id="icl_tm_validation_error" class="icl_error_text"><?php _e('Please review the document translation and fill in all the required fields.', 'wpml-translation-management') ?></div>
    <p class="submit-buttons">
        <input type="submit" class="button-primary" value="<?php _e('Save translation', 'wpml-translation-management')?>" />&nbsp;
        <?php
        if (isset($_POST['complete']) && $_POST['complete']) {
            $cancel_txt = __('Jobs queue', 'wpml-translation-management');
        } else {
            $cancel_txt = __('Cancel', 'wpml-translation-management');
        }
        ?>
        <a class="button-secondary" href="<?php echo admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php') ?>"><?php echo $cancel_txt; ?></a>
        <input type="submit" id="icl_tm_resign" class="button-secondary" value="<?php _e('Resign', 'wpml-translation-management')?>" onclick="if(confirm('<?php echo esc_js(__('Are you sure you want to resign from this job?', 'wpml-translation-management')) ?>')) jQuery(this).next().val(1); else return false;" /><input type="hidden" name="resign" value="0" />
    </p>
    <?php do_action('edit_form_advanced'); ?>
    </form>
        
</div>    
