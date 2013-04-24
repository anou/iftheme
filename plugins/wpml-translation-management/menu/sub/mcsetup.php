<?php //included from menu translation-management.php ?>
<?php
    
    $cf_keys_limit = 1000; // jic
    $cf_keys = $wpdb->get_col( "
        SELECT meta_key
        FROM $wpdb->postmeta
        GROUP BY meta_key
        ORDER BY meta_key
        LIMIT $cf_keys_limit" );
    
    $cf_keys_exceptions = array('_edit_last', '_edit_lock', '_wp_page_template', '_wp_attachment_metadata', '_icl_translator_note', '_alp_processed');
    // '_wp_attached_file'
    
    $cf_keys = array_diff($cf_keys, $cf_keys_exceptions);
    
    $cf_keys = array_unique(@array_merge($cf_keys, (array)$iclTranslationManagement->settings['custom_fields_readonly_config']));
    
    if ( $cf_keys )
        natcasesort($cf_keys);
    
    $cf_settings = $iclTranslationManagement->settings['custom_fields_translation'];  
    $cf_settings_ro = (array)$iclTranslationManagement->settings['custom_fields_readonly_config'];  
    $doc_translation_method = intval($iclTranslationManagement->settings['doc_translation_method']);
    
    //show custom fields defiend in types and not used yet
    if(function_exists('types_get_fields')){
        $types_cf = types_get_fields(array(), 'wpml' );
        foreach($types_cf as $key => $option){
            if(!in_array($key, $cf_keys)){
                $cf_keys[] = $key;        
                $cf_settings[$key] = $option;
            }
        }
    }
    
?>
        
    <div style="width:50%;float:left;">
    
        <form id="icl_doc_translation_method" name="icl_doc_translation_method" action="">        
        <?php wp_nonce_field('icl_doc_translation_method_nonce', '_icl_nonce') ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th colspan="2"><?php _e('How to translate posts and pages', 'wpml-translation-management');?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none;">
                        <ul>
                            <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_MANUAL ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_MANUAL): ?>checked="checked"<?php endif; ?> /> 
                                <?php _e('Create translations manually', 'wpml-translation-management')?></label></li>
                            <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_EDITOR ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_EDITOR): ?>checked="checked"<?php endif; ?> /> 
                                <?php _e('Use the translation editor', 'wpml-translation-management')?></label></li>
                            <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_PRO ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_PRO): ?>checked="checked"<?php endif; ?> /> 
                                <?php _e('Send to professional translation', 'wpml-translation-management')?></label></li>
                        </ul>
                        
                        <p>
                        <label><input name="how_to_translate" value="1" <?php checked(empty($sitepress_settings['hide_how_to_translate']), true) ?> type="checkbox" />&nbsp;
                            <?php _e('Show translation instructions in the list of pages', 'wpml-translation-management') ?></label>
                        </p>
                        
                        <p>
                            <input type="submit" class="button-secondary" value="<?php _e('Save', 'wpml-translation-management')?>" />
                            <span class="icl_ajx_response" id="icl_ajx_response_dtm"></span>
                        </p>
                        
                        <p><a href="http://wpml.org/?page_id=3416" target="_blank"><?php _e('Learn more about the different translation options') ?></a></p>
                    </td>    
                </tr>
            </tbody>
        </table>
        </form>    
        
        <br />
        <?php include ICL_PLUGIN_PATH . '/menu/_posts_sync_options.php'; ?>
    
    </div>    
    <div style="width:49%;float:left;margin-left:10px;">
    
        <form name="icl_tdo_options" id="icl_tdo_options" action="">
        <?php wp_nonce_field('icl_tdo_options_nonce', '_icl_nonce'); ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th colspan="2"><?php _e('Translated documents options', 'wpml-translation-management') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none;" nowrap="nowrap"><?php _e('Document status', 'wpml-translation-management')?></td>
                    <td style="border: none;">
                        <ul>
                            <li>
                                <p>
                                <label><input type="radio" name="icl_translated_document_status" value="0" 
                                    <?php if(!$sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> 
                                    <?php echo __('Draft', 'wpml-translation-management') ?>
                                </label>&nbsp;
                                <label><input type="radio" name="icl_translated_document_status" value="1" 
                                    <?php if($sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> 
                                    <?php echo __('Same as the original document', 'wpml-translation-management') ?>
                                </label>     
                                </p>
                                <i><?php echo __("Choose if translations should be published when received. Note: If Publish is selected, the translation will only be published if the original node is published when the translation is received.", 'wpml-translation-management') ?></i>
                            </li>
                        </ul>
                    </td>
                </tr>

                <tr>
                    <td style="border: none;"><?php _e('Page url', 'wpml-translation-management')?></td>
                    <td style="border: none;">
                        <ul>
                            <li>                            
                                <label><input type="radio" name="icl_translated_document_page_url" value="auto-generate" 
                                    <?php if(empty($sitepress_settings['translated_document_page_url']) || 
                                        $sitepress_settings['translated_document_page_url'] == 'auto-generate'): ?>checked="checked"<?php endif;?> /> 
                                    <?php echo __('Auto-generate from title (default)', 'wpml-translation-management') ?>
                                </label>
                            </li>
                            <li>
                                <label><input type="radio" name="icl_translated_document_page_url" value="translate" 
                                    <?php if($sitepress_settings['translated_document_page_url'] == 'translate'): ?>checked="checked"<?php endif;?> /> 
                                    <?php echo __('Translate (this will include the slug in the translation and not create it automatically from the title)', 'wpml-translation-management') ?>
                                </label>
                            </li>
                            <li>
                                <label><input type="radio" name="icl_translated_document_page_url" value="copy-encoded" 
                                    <?php if($sitepress_settings['translated_document_page_url'] == 'copy-encoded'): ?>checked="checked"<?php endif;?> /> 
                                    <?php echo __('Copy from original language if translation language uses encoded URLs', 'wpml-translation-management') ?>
                                </label>                                                        
                            </li>
                        </ul>
                    </td>
                </tr>
                
                <tr>
                    <td colspan="2" style="border: none;">
                        <p>
                            <input type="submit" class="button-secondary" value="<?php _e('Save', 'wpml-translation-management')?>" />
                            <span class="icl_ajx_response" id="icl_ajx_response_tdo"></span>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        </form>
            
        <br />
        
        <?php if(defined('WPML_ST_VERSION')) include WPML_ST_PATH . '/menu/_slug-translation-options.php'; ?>
            
        <form id="icl_translation_pickup_mode" name="icl_translation_pickup_mode" action="">        
        <?php wp_nonce_field('set_pickup_mode_nonce', '_icl_nonce') ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Translation pickup mode', 'wpml-translation-management');?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none;" id="icl_tm_pickup_wrap">
                        <p><?php _e('How should the site receive completed translations from ICanLocalize?', 'wpml-translation-management'); ?></p>
                        <p><label>
                            <input type="radio" name="icl_translation_pickup_method" value="<?php echo ICL_PRO_TRANSLATION_PICKUP_XMLRPC ?>"<?php
                                if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_XMLRPC) echo ' checked="checked"';
                            ?>/>&nbsp;
                            <?php _e('ICanLocalize will deliver translations automatically using XML-RPC', 'wpml-translation-management'); ?>
                        </label></p>
                        <?php if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_XMLRPC): ?>
                            <p style="padding-left: 20px;">
                            <label><input type="checkbox" name="icl_disable_reminders" value="1" <?php if(!empty($sitepress_settings['icl_disable_reminders'])): ?>checked="checked"<?php endif;?> />
                                &nbsp;<?php _e('Hide reminders', 'wpml-translation-management'); ?></label>
                            </p>
                        <?php endif; ?>
                        <p><label>
                            <input type="radio" name="icl_translation_pickup_method" value="<?php echo ICL_PRO_TRANSLATION_PICKUP_POLLING ?>"<?php
                                if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_POLLING) echo ' checked="checked"';
                            ?>/>&nbsp;
                            <?php _e('The site will fetch translations manually', 'wpml-translation-management'); ?>
                        </label></p></br>
                        <p><label>
                            <input name="icl_notify_complete" type="checkbox" value="1" 
                            <?php if(!empty($sitepress_settings['icl_notify_complete'])): ?>checked="checked"
                            <?php endif;?> /> 
                            <?php echo __('Send an email notification when translations complete', 'sitepress'); ?>
                        </label></p>  
                        <p>
                            <input class="button" name="save" value="<?php echo __('Save','wpml-translation-management') ?>" type="submit" />
                            <span class="icl_ajx_response" id="icl_ajx_response_tpm"></span>
                        </p>    
                        
                        <?php $ICL_Pro_Translation->get_icl_manually_tranlations_box(''); // shows only when translation polling is on and there are translations in progress ?>
                                                                                                   
                    </td>
                </tr>
            </tbody>
        </table>   
        </form>         
            
    </div>        
    <br clear="all" /><br />
    
    <div class="updated below-h2">
        <p style="line-height: 14px"><?php _e("WPML can read a configuration file that tells it what needs translation in themes and plugins. The file is named wpml-config.xml and it's placed in the root folder of the plugin or theme.", 'wpml-translation-management'); ?></p>
        <p><a href="http://wpml.org/?page_id=5526"><?php _e('Learn more', 'wpml-translation-management') ?></a></p>
    </div>
    
    <form id="icl_cf_translation" name="icl_cf_translation" action="">        
    <?php wp_nonce_field('icl_cf_translation_nonce', '_icl_nonce'); ?>
    <table class="widefat">
        <thead>
            <tr>
                <th colspan="2"><?php _e('Custom fields translation', 'wpml-translation-management');?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($cf_keys)): ?>
            <tr>
                <td colspan="2" style="border: none;">
                    <?php _e('No custom fields found. It is possible that they will only show up here after you add more posts after installing a new plugin.', 'wpml-translation-management'); ?>
                </td>
            </tr>
            <?php else: foreach($cf_keys as $cf_key): ?>
            <?php 
                $rdisabled = in_array($cf_key, $cf_settings_ro) ? 'disabled="disabled"' : '';
                if($rdisabled && $cf_settings[$cf_key]==0) continue;
                
                if (!empty($cf_settings[$cf_key]) && $cf_settings[$cf_key] == 3) {
                    continue;
                }
                
            ?>
            <tr>
                <td><?php echo $cf_key ?></td>
                <td align="right">
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="0" <?php echo $rdisabled ?>
                        <?php if(isset($cf_settings[$cf_key]) && $cf_settings[$cf_key]==0):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Don't translate", 'wpml-translation-management')?></label>&nbsp;
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="1" <?php echo $rdisabled ?>
                        <?php if(isset($cf_settings[$cf_key]) && $cf_settings[$cf_key]==1):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Copy from original to translation", 'wpml-translation-management')?></label>&nbsp;
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="2" <?php echo $rdisabled ?>
                        <?php if(isset($cf_settings[$cf_key]) && $cf_settings[$cf_key]==2):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Translate", 'wpml-translation-management')?></label>&nbsp;
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" style="border: none;">
                    <p>
                        <input type="submit" class="button" value="<?php _e('Save', 'wpml-translation-management') ?>" />
                        <span class="icl_ajx_response" id="icl_ajx_response_cf"></span>
                    </p>    
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </form>                    
    <br />
     
    <?php include ICL_PLUGIN_PATH . '/menu/_custom_types_translation.php'; ?>
        
    <?php if(!empty($iclTranslationManagement->admin_texts_to_translate) && function_exists('icl_register_string')): //available only with the String Translation plugin ?>
    <br clear="all" />
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Admin Strings to Translate', 'wpml-translation-management');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php foreach($iclTranslationManagement->admin_texts_to_translate as $option_name=>$option_value): ?>
                    <?php $iclTranslationManagement->render_option_writes($option_name, $option_value); ?>
                    <?php endforeach ?>
                    <br />
                    <p><a class="button-secondary" href="<?php echo admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php') ?>"><?php _e('Edit translatable strings', 'wpml-translation-management') ?></a></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
    
