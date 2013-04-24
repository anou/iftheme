<?php 

    $cposts = array();
    $icl_post_types = $sitepress->get_translatable_documents(true);    
    
    foreach($icl_post_types as $k=>$v){
        if(!in_array($k, array('post','page'))){
            $cposts[$k] = $v;        
        }
    }
    
    foreach($cposts as $k=>$cpost){
        if(!isset($sitepress_settings['custom_posts_sync_option'][$k])){
            $cposts_sync_not_set[] = $cpost->labels->name;
        }    
    }  
    
    $notice = '';  
    if(!empty($cposts_sync_not_set)){
        $notice .= '<div class="updated below-h2"><p>';
        $notice .= sprintf(__("You haven't set your synchronization preferences for these custom posts: %s. Default value was selected.", 'sitepress'), 
            '<i>'.join('</i>, <i>', $cposts_sync_not_set) . '</i>');
        $notice .= '</p></div>';
    }
    
    global $wp_taxonomies;
    $ctaxonomies = array_diff(array_keys((array)$wp_taxonomies), array('post_tag','category', 'nav_menu', 'link_category', 'post_format'));    
    
    foreach($ctaxonomies as $ctax){
        if(!isset($sitepress_settings['taxonomies_sync_option'][$ctax])){
            $tax_sync_not_set[] = $wp_taxonomies[$ctax]->label;
        }    
    }
    if(!empty($tax_sync_not_set)){
        $notice .= '<div class="updated below-h2"><p>';
        $notice .= sprintf(__("You haven't set your synchronization preferences for these taxonomies: %s. Default value was selected.", 'sitepress'), 
            '<i>'.join('</i>, <i>', $tax_sync_not_set) . '</i>');
        $notice .= '</p></div>';
    }
    

?>  
  
    <?php if(isset($notice)) echo $notice ?>
    
    <?php if(!empty($cposts)): ?>    
    <form id="icl_custom_posts_sync_options" name="icl_custom_posts_sync_options" action="">        
    <?php wp_nonce_field('icl_custom_posts_sync_options_nonce', '_icl_nonce') ?>
    <table class="widefat">
        <thead>
            <tr>
                <th width="60%"><?php _e('Custom posts', 'sitepress');?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cposts as $k=>$cpost): ?>
            <?php 
                $rdisabled = isset($iclTranslationManagement->settings['custom_types_readonly_config'][$k]) ? 'disabled="disabled"':'';
            ?>
            <tr>
                <td><?php echo $cpost->labels->name; ?></td>
                <td>
                    <label><input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="1" <?php echo $rdisabled; 
                        if(@intval($sitepress_settings['custom_posts_sync_option'][$k])==1) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Translate', 'sitepress') ?></label>&nbsp;
                    <label><input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="0" <?php echo $rdisabled;
                        if(@intval($sitepress_settings['custom_posts_sync_option'][$k])==0) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Do nothing', 'sitepress') ?></label>
                    <?php if($rdisabled): ?>
                    <input type="hidden" name="icl_sync_custom_posts[<?php echo $k ?>]" value="<?php echo @intval($sitepress_settings['custom_posts_sync_option'][$k]) ?>" />
                    <?php endif; ?>
                </td>
            </tr>
            
            <?php if(defined('WPML_ST_VERSION')): ?>
            <?php             
            $_has_slug = isset($cpost->rewrite['slug']) && $cpost->rewrite['slug'];
            $_on = $sitepress_settings['posts_slug_translation']['on'] &&  
                   $_has_slug &&
                   isset($sitepress_settings['custom_posts_sync_option'][$k]) && 
                   $sitepress_settings['custom_posts_sync_option'][$k] == 1;                        
            $_display = $_on ? '' : 'display:none;';  
            $_translate = !empty($sitepress_settings['posts_slug_translation']['types'][$k]);            
            if($_has_slug){
                if($sitepress->get_default_language() != $sitepress_settings['st']['strings_language']){
                    $string_id = $wpdb->get_var($wpdb->prepare("
                        SELECT s.id FROM {$wpdb->prefix}icl_strings s
                            JOIN {$wpdb->prefix}icl_string_translations st
                            ON st.string_id = s.id
                            WHERE st.language=%s AND st.value=%s AND s.name LIKE %s                        
                    ", $sitepress->get_default_language(), $cpost->rewrite['slug'], 'URL slug: %'));
                    
                }else{
                    $string_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", 'Url slug: ' . $cpost->rewrite['slug'], $cpost->rewrite['slug']));    
                }
                $_slug_translations = icl_get_string_translations_by_id($string_id);
            }else{
                $_slug_translations = false;
            }
            ?>
            <?php if($_has_slug): ?>
            <tr class="icl_slug_translation_choice" style="background-color: #efefef;<?php echo $_display; ?>">
                <td colspan="2">
                    <label><input name="translate_slugs[<?php echo $k ?>][on]" type="checkbox" value="1" <?php checked(1, $_translate, true) ?> />&nbsp;<?php 
                        printf(__('Use different slugs in different languages for %s.', 'sitepress'), $cpost->labels->name); ?></label>
                    <table <?php if(empty($_translate)):?>style="display:none"<?php endif; ?>>
                    <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code'] == $sitepress_settings['st']['strings_language']) continue; ?>                    
                        <tr>
                            <td style="border: none;"><?php echo $lang['display_name']?></td>
                            <td style="border: none;"><input name="translate_slugs[<?php echo $k ?>][langs][<?php echo $lang['code'] ?>]" type="text" value="<?php 
                                echo !empty($_slug_translations[$lang['code']]['value']) ? $_slug_translations[$lang['code']]['value'] : '';    
                                ?>" />
                                <?php if(isset($_slug_translations[$lang['code']]) && $_slug_translations[$lang['code']]['status'] != ICL_STRING_TRANSLATION_COMPLETE): ?>
                                <i class="icl_st_slug_tr_warn"><?php _e("Not marked as 'complete'. Press 'Save' to enable.", 'sitepress') ?></i>
                                <?php endif; ?>
                                </td>                                        
                        </tr>
                    <?php endforeach; ?>
                    </table>
                    
                </td>
            </tr>
            <?php endif; ?>
            
            <?php endif; ?>
            
            
            <?php endforeach; ?>
            <tr>
                <td colspan="2">
                <p>
                    <input type="submit" class="button" value="<?php _e('Save', 'sitepress') ?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_cp"></span>
                </p>
                </td>
            </tr>
        </tbody>
    </table>
    </form>        
    <?php endif; ?>     
    
    <?php if(!empty($ctaxonomies)): ?>
    <form id="icl_custom_tax_sync_options" name="icl_custom_tax_sync_options" action="">        
    <?php wp_nonce_field('icl_custom_tax_sync_options_nonce', '_icl_nonce') ?>
    <table class="widefat">
        <thead>
            <tr>
                <th width="60%"><?php _e('Custom taxonomies', 'sitepress');?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($ctaxonomies as $ctax): ?>
            <?php 
                $rdisabled = isset($iclTranslationManagement->settings['taxonomies_readonly_config'][$ctax]) ? 'disabled="disabled"':'';
            ?>            
            <tr>
                <td><?php echo $wp_taxonomies[$ctax]->label; ?></td>
                <td>
                    <label><input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="1" <?php echo $rdisabled; 
                        if(@$sitepress_settings['taxonomies_sync_option'][$ctax]==1) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Translate', 'sitepress') ?></label>&nbsp;
                    <label><input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="0" <?php echo $rdisabled; 
                        if(@$sitepress_settings['taxonomies_sync_option'][$ctax]==0) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Do nothing', 'sitepress') ?></label>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2">
                <p>
                    <input type="submit" class="button" value="<?php _e('Save', 'sitepress') ?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_ct"></span>
                </p>
                </td>
            </tr>
        </tbody>
    </table>
    </form>        
    <?php endif; ?>     
    <br clear="all" />    