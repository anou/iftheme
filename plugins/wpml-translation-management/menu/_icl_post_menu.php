    <?php if(!defined('ICL_DONT_PROMOTE') || !ICL_DONT_PROMOTE):?>
    <div class="icl_cyan_box">
    <div class="clear" style="font-size: 0px">&nbsp;</div>    
    <a id="icl_pt_hide" href="#" style="float:right;<?php if(!empty($sitepress_settings['hide_professional_translation_controls'])):?>display:none;<?php endif; ?>"><?php _e('hide', 'wpml-translation-management') ?></a>
    <a id="icl_pt_show" href="#" style="float:right;<?php if(empty($sitepress_settings['hide_professional_translation_controls'])):?>display:none;<?php endif; ?>"><?php _e('show', 'wpml-translation-management') ?></a>        
    <?php wp_nonce_field('toggle_pt_controls_nonce', '_icl_nonce_ptc'); ?>
    <strong><?php _e('Professional translation', 'wpml-translation-management'); ?></strong>    
    <div id="icl_pt_controls" <?php if(!empty($sitepress_settings['hide_professional_translation_controls'])):?>style="display:none;"<?php endif; ?>>
    <?php 
        if(!empty($languages_translated)){ 
            
            if(!empty($sitepress_settings['default_translators'][$language_pair['from']])){
                foreach($sitepress_settings['default_translators'][$language_pair['from']] as $_tolang => $tr){
                    $status = $iclTranslationManagement->get_element_translation($post->ID, $_tolang , 'post_' . $post->post_type);
                    if(isset($status->translator_id)) $tr['id']  = $status->translator_id;                    
                    if($iclTranslationManagement->translator_exists($tr['id'], $language_pair['from'], $_tolang, $tr['type'])){            
                        $icl_selected_translators[$_tolang] = $tr['type'] == 'local' ? $tr['id'] : $tr['id'] . '-' . $tr['type'];        
                    }        
                }
            }
            
            echo '<ul>';
            foreach($languages_translated as $lang){
                if(isset($pro_translations[$lang]) 
                    && ($pro_translations[$lang]->status == ICL_TM_IN_PROGRESS || 
                        ($pro_translations[$lang]->status == ICL_TM_COMPLETE && !$pro_translations[$lang]->needs_update))){
                    $disabled = ' disabled="disabled"';
                }else{
                    $disabled = '';
                }
                echo '<li><label>';
                echo '<input type="hidden" id="icl_pt_rate_'.$lang.'" value="'.$lang_rates[$lang].'" />';
                echo '<input type="checkbox" id="icl_pt_to_'.$lang.'" value="'.$lang.'"'.$disabled.'/>&nbsp;';
                if(isset($pro_translations[$lang]) && $pro_translations[$lang]->status == ICL_TM_COMPLETE){
                    printf(__('Update %s translation', 'wpml-translation-management'), $active_languages[$lang]['display_name']);
                }else{
                    printf(__('Translate to %s', 'wpml-translation-management'), $active_languages[$lang]['display_name']);
                }
                
                if(isset($pro_translations[$lang]) && $pro_translations[$lang]->status == ICL_TM_IN_PROGRESS){
                    echo '&nbsp;<small>('.__('in progress', 'wpml-translation-management').')</small>';
                }elseif(isset($pro_translations[$lang]) && $pro_translations[$lang]->status == ICL_TM_COMPLETE && !$pro_translations[$lang]->needs_update){
                    echo '&nbsp;<small>('.__('up to date', 'wpml-translation-management').')</small>';
                }
                                
                echo '</label>';
                global $source_language;
                $iclTranslationManagement->translators_dropdown(array(
                        'from'          => $source_language,
                        'to'            => $lang,
                        'name'          => 'translator['.$lang.']',
                        'selected'      =>  isset($icl_selected_translators[$lang]) ? $icl_selected_translators[$lang] : 0,
                        'services'      => array('icanlocalize'),
                        'show_service'  => false,
                        'disabled'      => $disabled
                )); 

                echo '</li>';
            }    
            echo '</ul>';
        }
        if(!empty($languages_not_translated)){ 
            echo '<ul>';
            foreach($languages_not_translated as $lang){
                echo '<li>'.$sitepress->create_icl_popup_link("@select-translators;{$selected_language};{$lang}@", 
                    array(
                        'ar'=>1, 
                        'title'=>__('Select translators', 'wpml-translation-management'),
                        'unload_cb' => 'icl_pt_reload_translation_box'
                    )
                ); // <a> included
                printf(__('Get %s translators', 'wpml-translation-management'), $active_languages[$lang]['display_name']);
                echo '</a></li>';
            }    
            echo '</ul>';            
        }
        if(!empty($languages_translated)){
            $note = trim(get_post_meta($post->ID, '_icl_translator_note', true));
        }
    ?>
    <?php if(!empty($languages_translated)): ?>
    <div id="icl_post_add_notes">
        <h4><a href="#"><?php _e('Note for the translators', 'wpml-translation-management')?></a></h4>
        <div id="icl_post_note">
            <textarea id="icl_pt_tn_note" name="icl_tn_note" rows="5"><?php echo $note ?></textarea> 
            <table width="100%"><tr>
            <td><input id="icl_tn_clear" type="button" class="button" value="<?php _e('Clear', 'wpml-translation-management')?>" <?php if(!$note): ?>disabled="disabled"<?php endif; ?> /></td>            <td align="right"><input id="icl_tn_save"  type="button" class="button-primary" value="<?php _e('Close', 'wpml-translation-management')?>" /></td>
            </tr></table>
            <input id="icl_tn_cancel_confirm" type="hidden" value="<?php _e('Your changes to the note for the translators are not saved.', 'wpml-translation-management') ?>" />
        </div>
        <div id="icl_tn_not_saved"><?php _e('Note not saved yet', 'wpml-translation-management'); ?></div>
    </div>    
    
    <div style="text-align: right;margin:0 5px 5px 0;"><?php printf(__('Cost: %s USD', 'wpml-translation-management'), '<span id="icl_pt_cost_estimate">0.00</span>');?></div>
    <input type="hidden" id="icl_pt_wc" value="<?php echo ICL_Pro_Translation::estimate_word_count($post, $selected_language) + ICL_Pro_Translation::estimate_custom_field_word_count($post->ID, $selected_language) ?>" />
    <input type="hidden" id="icl_pt_post_id" value="<?php echo $post->ID ?>" />
    <input type="hidden" id="icl_pt_post_type" value="<?php echo $post->post_type ?>" />
    <?php wp_nonce_field('send_translation_request_nonce', '_icl_nonce_pt_' . $post->ID ) ?>
    <input type="button" disabled="disabled" id="icl_pt_send" class="button-primary alignright" value="<?php echo esc_html(__('Send to translation', 'wpml-translation-management')) ?>" style="clear: right;"/>
    <br clear="all" />
    <?php else:?>
    <?php 
        $estimated_cost = sprintf("%.2f", (ICL_Pro_Translation::estimate_word_count($post, $selected_language) + ICL_Pro_Translation::estimate_custom_field_word_count($post->ID, $selected_language)) * 0.09);
    ?>
    <div style="text-align: right;margin:0 5px 5px 0;white-space:nowrap;">
    <?php printf( __('Estimated cost: %s USD', 'wpml-translation-management'), $estimated_cost);?><br />
    (<?php echo $sitepress->create_icl_popup_link('http://www.icanlocalize.com/destinations/go?name=cms-cost-estimate&iso='.
        $sitepress->get_locale($sitepress->get_admin_language()).'&src='.get_option('home'), 
        array(
            'ar'=>1, 
            'title'=>__('Cost estimate', 'wpml-translation-management'),
        )
    ) 
        . __('why estimated?', 'wpml-translation-management');?></a>)
    </div>
        
    <br />
    <p><b><?php echo $sitepress->create_icl_popup_link('http://www.icanlocalize.com/destinations/go?name=moreinfo-wp&iso='.
        $sitepress->get_locale($sitepress->get_admin_language()).'&src='.get_option('home'), 
        array('title' => __('About Our Translators', 'wpml-translation-management'), 'ar' => 1)) ?><?php _e('About Our Translators', 'wpml-translation-management'); ?></a></b></p>
    <p><?php _e('ICanLocalize offers expert translators at competitive rates.', 'wpml-translation-management'); ?></p>
    <p><?php echo $sitepress->create_icl_popup_link('http://www.icanlocalize.com/destinations/go?name=wp-about-translators&iso='.
        $sitepress->get_locale($sitepress->get_admin_language()).'&src='.get_option('home'), 
        array('title' => __('About Our Translators', 'wpml-translation-management'), 'ar' => 1)) ?><?php _e('Learn more', 'wpml-translation-management'); ?></a></p>    
        
    <?php endif; ?>
    </div>
    
    <div id="icl_pt_error" class="icl_error_text" style="display: none;margin-top: 4px;"><?php _e('Failed sending to translation.', 'wpml-translation-management') ?></div>    
    <?php if(isset($_GET['icl_message']) && $_GET['icl_message']=='success'):?>
    <div id="icl_pt_success" class="icl_valid_text" style="margin-top: 8px;"><?php _e('Sent to translation.', 'wpml-translation-management') ?></div>    
    <?php endif; ?>
    </div>
<?php endif; ?>
