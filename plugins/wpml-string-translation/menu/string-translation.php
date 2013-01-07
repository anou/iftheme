<?php 
icl_st_reset_current_trasnslator_notifications();

if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) /*|| 2 > count($sitepress->get_active_languages())*/){
    return;
}

if(isset($_GET['trop']) && $_GET['trop'] > 0){
    include dirname(__FILE__) . '/string-translation-translate-options.php';
    return;
}elseif(isset($_GET['download_mo']) && $_GET['download_mo']){
    include dirname(__FILE__) . '/auto-download-mo.php';
    return;    
}

if(isset($_GET['status']) && preg_match("#".ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR."-(.+)#", $_GET['status'], $matches)){
    $status_filter = ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR;
    $status_filter_lang = $matches[1];
}else{
    $status_filter = isset($_GET['status']) ? intval($_GET['status']) : false;    
}
$context_filter = isset($_GET['context']) ? $_GET['context'] : false;
$search_filter = isset($_GET['search']) ? esc_html($_GET['search']) : false;
$exact_match = isset($_GET['em']) ? $_GET['em'] == 1 : false;

$icl_string_translations = icl_get_string_translations();

if(!empty($icl_string_translations)){
    $icl_strings_in_page = icl_get_strigs_tracked_in_pages($icl_string_translations);
}
$active_languages = $sitepress->get_active_languages();            
$icl_contexts = icl_st_get_contexts($status_filter);

/*
if($status_filter != ICL_STRING_TRANSLATION_COMPLETE){
    $icl_contexts_translated = icl_st_get_contexts(ICL_STRING_TRANSLATION_COMPLETE);
}else{
    $icl_contexts_translated = $icl_contexts;
}
*/
$icl_st_translation_enabled = $sitepress->icl_account_configured() && $sitepress->get_icl_translation_enabled();

$available_contexts = array();
if(!empty($icl_contexts)){
    foreach($icl_contexts as $c){
        if($c) $available_contexts[] = $c->context;
    }                                                
}
if(!empty($sitepress_settings['st']['theme_localization_domains']) && is_array($sitepress_settings['st']['theme_localization_domains'])){
    foreach($sitepress_settings['st']['theme_localization_domains'] as $c){
        if($c) $available_contexts[] = 'theme ' . $c;
    }
}
    
$available_contexts = array_unique($available_contexts);

function _icl_string_translation_rtl_div($language) {
    if (in_array($language, array('ar','he','fa'))) {
        echo ' dir="rtl" style="text-align:right;direction:rtl;"';
    } else {
        echo ' dir="ltr" style="text-align:left;direction:ltr;"';
    }
}
function _icl_string_translation_rtl_textarea($language) {
    if (in_array($language, array('ar','he','fa'))) {
        echo ' dir="rtl" style="text-align:right;direction:rtl;width:100%"';
    } else {
        echo ' dir="ltr" style="text-align:left;direction:ltr;width:100%"';
    }
}

?>
<div class="wrap">

    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php echo __('String translation', 'wpml-string-translation') ?></h2>    
    
    <?php $WPML_String_Translation->show_messages(); ?>
    
    <?php if(isset($icl_st_po_strings) && !empty($icl_st_po_strings)): ?>
    
        <p><?php printf(__("These are the strings that we found in your .po file. Please carefully review them. Then, click on the 'add' or 'cancel' buttons at the %sbottom of this screen%s. You can exclude individual strings by clearing the check boxes next to them.", 'wpml-string-translation'), ',<a href="#add_po_strings_confirm">', '</a>'); ?></p>  
        
        <form method="post" action="<?php echo admin_url("admin.php?page=" . WPML_ST_FOLDER . "/menu/string-translation.php");?>">
        <?php wp_nonce_field('add_po_strings') ?>
        <?php if(isset($_POST['icl_st_po_translations'])): ?>
        <input type="hidden" name="action" value="icl_st_save_strings" />
        <input type="hidden" name="icl_st_po_language" value="<?php echo $_POST['icl_st_po_language'] ?>" />
        <?php endif; ?>
        <input type="hidden" name="icl_st_domain_name" value="<?php echo $_POST['icl_st_i_context_new']?$_POST['icl_st_i_context_new']:$_POST['icl_st_i_context'] ?>" />
        
        <table id="icl_po_strings" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
                    <th><?php echo __('String', 'wpml-string-translation') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" checked="checked" name="" /></th>
                    <th><?php echo __('String', 'wpml-string-translation') ?></th>
                </tr>
            </tfoot>        
            <tbody>
                <?php $k = -1; foreach($icl_st_po_strings as $str): $k++; ?>
                    <tr>
                        <td><input class="icl_st_row_cb" type="checkbox" name="icl_strings_selected[]" 
                            <?php if($str['exists'] || !isset($_POST['icl_st_po_translations'])): ?>checked="checked"<?php endif;?> value="<?php echo $k ?>" /></td>
                        <td>
                            <input type="text" name="icl_strings[]" value="<?php echo htmlspecialchars($str['string']) ?>" readonly="readonly" style="width:100%;" size="100" />
                            <?php if(isset($_POST['icl_st_po_translations'])):?>
                            <input type="text" name="icl_translations[]" value="<?php echo htmlspecialchars($str['translation']) ?>" readonly="readonly" style="width:100%;<?php if($str['fuzzy']):?>;background-color:#ffecec<?php endif; ?>" size="100" />
                            <input type="hidden" name="icl_fuzzy[]" value="<?php echo $str['fuzzy'] ?>" />
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>            
        <a name="add_po_strings_confirm"></a>        
        <p><input class="button" type="button" value="<?php echo __('Cancel', 'wpml-string-translation'); ?>" onclick="location.href='admin.php?page=<?php echo $_GET['page'] ?>'" />
        &nbsp; <input class="button-primary" type="submit" value="<?php echo __('Add selected strings', 'wpml-string-translation'); ?>" />
        </p>
        </form>

    <?php else: ?>
    
        <p style="line-height:220%;">
        <?php echo __('Select which strings to display:', 'wpml-string-translation')?>
        <select name="icl_st_filter_status">
            <option value="" <?php if($status_filter === false ):?>selected="selected"<?php endif;?>><?php echo __('All strings', 'wpml-string-translation') ?></option>        
            <option value="<?php echo ICL_STRING_TRANSLATION_COMPLETE ?>" <?php if($status_filter === ICL_STRING_TRANSLATION_COMPLETE):?>selected="selected"<?php endif;?>><?php echo $icl_st_string_translation_statuses[ICL_STRING_TRANSLATION_COMPLETE] ?></option>            
            <?php if(icl_st_is_translator()) :?>
                <?php if($icl_st_pending = icl_st_get_pending_string_translations_stats()): ?>
                <?php foreach($icl_st_pending as $lang=>$count): $lang_details = $sitepress->get_language_details($lang); ?>
                <option value="<?php echo ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR .'-' . $lang ?>" <?php if(isset($status_filter_lang) && $status_filter_lang == $lang):?>selected="selected"<?php endif;?>><?php printf(__('Pending %s translation (%d)', 'wpml-string-translation'), $lang_details['display_name'], $count) ?></option>            
                <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
            <option value="<?php echo ICL_STRING_TRANSLATION_NOT_TRANSLATED ?>" <?php if($status_filter === ICL_STRING_TRANSLATION_NOT_TRANSLATED):?>selected="selected"<?php endif;?>><?php echo __('Translation needed', 'wpml-string-translation') ?></option>
            <option value="<?php echo ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR ?>" <?php if($status_filter === ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif;?>><?php echo __('Waiting for translator', 'wpml-string-translation') ?></option>                        
            <?php endif; ?>
            
        </select>
        
        <?php if(!empty($icl_contexts)): ?>
        &nbsp;&nbsp;
        <span style="white-space:nowrap">
        <?php echo __('Select strings within context:', 'wpml-string-translation')?>
        <select name="icl_st_filter_context">
            <option value="" <?php if($context_filter === false ):?>selected="selected"<?php endif;?>><?php echo __('All contexts', 'wpml-string-translation') ?></option>
            <?php foreach($icl_contexts as $v):?>
            <option value="<?php echo htmlspecialchars($v->context)?>" <?php if($context_filter == $v->context ):?>selected="selected"<?php endif;?>><?php echo $v->context . ' ('.$v->c.')'; ?></option>
            <?php endforeach; ?>
        </select>    
        </span>
        <?php endif; ?>
        
        &nbsp;&nbsp;
        <span style="white-space:nowrap">
        <label>
        <?php echo __('Search for:', 'wpml-string-translation')?>
        <input type="text" id="icl_st_filter_search" value="<?php echo $search_filter ?>" />
        </label>
        
        <label>
        <input type="checkbox" id="icl_st_filter_search_em" value="1" <?php if($exact_match):?>checked="checked"<?php endif;?> />
        <?php echo __('Exact match', 'wpml-string-translation')?>
        </label>
        
        <input class="button" type="button" value="<?php _e('Search', 'wpml-string-translation')?>" id="icl_st_filter_search_sb" />
        </span>
        
        <?php if($search_filter): ?>
        <span style="white-space:nowrap">
        <?php printf(__('Showing only strings that contain %s', 'wpml-string-translation'), '<i>' . htmlspecialchars($search_filter). '</i>') ; ?>
        <input class="button" type="button" value="<?php _e('Exit search', 'wpml-string-translation')?>" id="icl_st_filter_search_remove" />
        </span>
        <?php endif; ?>
        
        </p>
    
        <table id="icl_string_translations" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col"><?php echo __('Context', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('Name', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('View', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('String', 'wpml-string-translation') ?></th>        
                    <th scope="col"><?php echo __('Status', 'wpml-string-translation') ?></th>
                </tr>        
            </thead>        
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
                    <th scope="col"><?php echo __('Context', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('Name', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('View', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('String', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('Status', 'wpml-string-translation') ?></th>        
                </tr>        
            </tfoot>                
            <tbody>
                <?php if(empty($icl_string_translations)):?> 
                <tr>
                    <td colspan="6" align="center"><?php echo __('No strings found', 'wpml-string-translation')?></td>
                </tr>
                <?php else: ?>
                <?php foreach($icl_string_translations as $string_id=>$icl_string): ?> 
                <tr valign="top">
                    <td><input class="icl_st_row_cb" type="checkbox" value="<?php echo $string_id ?>" /></td>
                    <td><?php echo htmlspecialchars($icl_string['context']); ?></td>
                    <td><?php echo htmlspecialchars(_icl_st_hide_random($icl_string['name'])); ?></td>
                    <td nowrap="nowrap">
                        <?php if(isset($icl_strings_in_page[ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE][$string_id])): ?>
                            <a class="thickbox" title="<?php _e('view in source', 'wpml-string-translation') ?>"
                                href="admin.php?page=<?php echo WPML_ST_FOLDER ?>%2Fmenu%2Fstring-translation.php&amp;icl_action=view_string_in_source&amp;string_id=<?php 
                                echo $string_id ?>&amp;width=810&amp;height=600"><img src="<?php echo WPML_ST_URL ?>/res/img/view-in-source.png" width="16" height="16"
                                alt="<?php _e('view in page', 'wpml-string-translation') ?>" /></a>
                        <?php endif; ?>
                        <?php if(isset($icl_strings_in_page[ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE][$string_id])): ?>
                            <a class="thickbox" title="<?php _e('view in page', 'wpml-string-translation') ?>"
                            href="admin.php?page=<?php echo WPML_ST_FOLDER ?>%2Fmenu%2Fstring-translation.php&icl_action=view_string_in_page&string_id=<?php 
                            echo $string_id ?>&width=810&height=600"><img src="<?php echo WPML_ST_URL ?>/res/img/view-in-page.png" width="16" height="16" 
                            alt="<?php _e('view in page', 'wpml-string-translation') ?>" /></a>                        
                        <?php endif; ?>
                    </td> 
                    <td width="70%">                                        
                        <div class="icl-st-original"<?php _icl_string_translation_rtl_div($sitepress_settings['st']['strings_language']); ?>>
                        <?php echo htmlspecialchars($icl_string['value']); ?>                    
                        </div>                    
                        <div style="float:right;">
                            <a href="#icl-st-toggle-translations"><?php echo __('translations','wpml-string-translation') ?></a>
                        </div>
                        <br clear="all" />
                        <div class="icl-st-inline">          
                            <?php foreach($active_languages as $lang): if($lang['code'] == $sitepress_settings['st']['strings_language']) continue;  ?>
                            
                            <?php                                    
                                if(isset($icl_string['translations'][$lang['code']]) && $icl_string['translations'][$lang['code']]['status'] == ICL_STRING_TRANSLATION_COMPLETE){
                                    $tr_complete_checked = 'checked="checked"';
                                }else{
                                    if(icl_st_is_translator()){
                                        global $current_user;
                                        get_currentuserinfo();
                                        $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);    
                                        if(empty($user_lang_pairs[$sitepress_settings['st']['strings_language']][$lang['code']])){
                                            continue;
                                        }
                                    }
                                    $tr_complete_checked = '';    
                                }
                                
                                if(icl_st_is_translator()){
                                    
                                    $icl_pro_status = $wpdb->get_var($wpdb->prepare("
                                            SELECT c.status FROM {$wpdb->prefix}icl_core_status c 
                                                JOIN {$wpdb->prefix}icl_string_status s ON s.rid = c.rid
                                                WHERE s.string_translation_id = %d AND target=%s AND status = %d
                                                ORDER BY s.id DESC LIMIT 1
                                        ", $icl_string['string_id'], $lang['code'], CMS_TARGET_LANGUAGE_ASSIGNED));                                    
                                                                        
                                    if(
                                        isset($icl_string['translations'][$lang['code']]) && 
                                        (   
                                            $icl_string['translations'][$lang['code']]['translator_id'] == get_current_user_id() || 
                                            ( 
                                                is_null($icl_string['translations'][$lang['code']]['translator_id']) &&
                                                $icl_string['translations'][$lang['code']]['status'] == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR && 
                                                !$icl_pro_status
                                            )
                                        )
                                        
                                    ){
                                        $form_disabled = '';
                                    }else{
                                        $form_disabled = ' disabled="disabled" ';                                    
                                    }
                                    
                                }else{
                                    $form_disabled = '';
                                }
                            ?>
                            
                            <form class="icl_st_form" name="icl_st_form_<?php echo $lang['code'] . '_' . $string_id ?>" action="">
                            <?php wp_nonce_field('icl_st_save_translation_nonce', '_icl_nonce') ?>
                            <input type="hidden" name="icl_st_language" value="<?php echo $lang['code'] ?>" />                        
                            <input type="hidden" name="icl_st_string_id" value="<?php echo $string_id ?>" />                        
                            
                            <table class="icl-st-table">
                                <tr>
                                    <td style="border:none">
                                        <?php echo $lang['display_name'] ?>                                        
                                        <br />
                                        <img class="icl_ajx_loader" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="float:left;display:none;position:absolute;margin:5px" alt="" />
                                        <?php
                                        $rows = ceil(strlen($icl_string['value'])/80);
                                        $temp_line_array = preg_split( '/\n|\r/', $icl_string['value'] );
                                        $temp_num_lines = count($temp_line_array);
                                        $rows = $rows + $temp_num_lines;
                                        ?>
                                        <textarea<?php echo $form_disabled ?><?php _icl_string_translation_rtl_textarea($lang['code']); ?>rows="<?php echo $rows ?>" cols="40" name="icl_st_translation" <?php if(isset($icl_string['translations'][$lang['code']])): ?>id="icl_st_ta_<?php echo $icl_string['translations'][$lang['code']]['id'] ?>"<?php endif;?>><?php                                            
                                            if(isset($icl_string['translations'][$lang['code']]) && !is_null($icl_string['translations'][$lang['code']]['value'])) echo $icl_string['translations'][$lang['code']]['value']; else echo $icl_string['value']; 
                                            ?></textarea>                                        
                                    </td>
                                </tr>
                                <tr>
                                    <td align="right" style="border:none">                                    
                                        <?php 
                                        if(current_user_can('manage_options') && isset($icl_string['translations'][$lang['code']]['status']) &&
                                            $icl_string['translations'][$lang['code']]['status'] == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR
                                            ){
                                                echo '<div style="float: left;">';
                                                echo '<i>' . __('Waiting for a local translator.', 'wpml-string-translation') . '</i>&nbsp;';
                                                echo '<a href="#cancel-local-'.$icl_string['translations'][$lang['code']]['id'].'" onclick="icl_st_cancel_local_translation(jQuery(this))">'
                                                    .__('Cancel', 'wpml-string-translation').'</a>';
                                                echo '</div>';
                                        }else{
                                            if(isset($icl_string['translations'][$lang['code']]) && $icl_string['translations'][$lang['code']]['translator_id'] > 0){
                                                $_user = get_userdata($icl_string['translations'][$lang['code']]['translator_id']);
                                                if(!empty($_user)){
                                                    echo '<div style="float:left;margin-right:4px;"><small>';                                                                                                
                                                    printf(__('Translated by %s on %s', 'wpml-string-translation'), $_user->display_name , $icl_string['translations'][$lang['code']]['translation_date']);
                                                    echo '</small></div>';
                                                }
                                            }else{
                                                $icl_pro_status = $wpdb->get_var($wpdb->prepare("
                                                    SELECT c.status FROM {$wpdb->prefix}icl_core_status c 
                                                        JOIN {$wpdb->prefix}icl_string_status s ON s.rid = c.rid
                                                        WHERE s.string_translation_id = %d  AND target=%s AND status = %d
                                                        ORDER BY s.id DESC LIMIT 1
                                                ", $icl_string['string_id'], $lang['code'], CMS_TARGET_LANGUAGE_ASSIGNED));
                                                if(!empty($icl_pro_status)){
                                                    echo '<div style="float: left;"><small>';
                                                    echo "ICanLocalize: " . icl_decode_translation_status_id($icl_pro_status);    
                                                    echo '</small></div>';
                                                }
                                            }
                                            
                                        }
                                        ?>
                                        
                                        <?php if(isset($icl_string['translations'][$lang['code']]['value']) && preg_match('#<([^>]*)>#im',$icl_string['translations'][$lang['code']]['value'])):?>
                                        <br clear="all" /><div style="text-align:left;display:none" class="icl_html_preview"></div>
                                        <a href="#" class="alignleft icl_htmlpreview_link">HTML preview</a>
                                        <?php endif; ?>                                    
                                        <label><input<?php echo $form_disabled ?> type="checkbox" name="icl_st_translation_complete" value="1" <?php echo $tr_complete_checked ?> <?php if(isset($icl_string['translations'][$lang['code']])): ?>id="icl_st_cb_<?php echo $icl_string['translations'][$lang['code']]['id'] ?>"<?php endif;?> /> <?php echo __('Translation is complete','wpml-string-translation')?></label>&nbsp;
                                        <input<?php echo $form_disabled ?> type="submit" class="button-secondary action" value="<?php echo __('Save', 'wpml-string-translation')?>" />
                                    </td>
                                </tr>
                                </table>
                                </form>
                                                                
                            <?php endforeach;?>

                        </div>
                    </td>
                    <td nowrap="nowrap" id="icl_st_string_status_<?php echo $string_id ?>">
                    <span>
                    <?php
                        $icl_status = icl_translation_get_string_translation_status($string_id);
                        if ($icl_status != "") {
                            $icl_status = '<br /><span class="meta_comment">' . __('ICanLocalize ', 'wpml-string-translation').$icl_status . '</span>';
                        }
                        echo $icl_st_string_translation_statuses[$icl_string['status']].$icl_status;
                    ?> 
                    </span>
                    <input type="hidden" id="icl_st_wc_<?php echo $string_id ?>" value="<?php 
                        echo $WPML_String_Translation->estimate_word_count($icl_string['value'], $sitepress_settings['st']['strings_language']) ?>" />   
                    </td>
                </tr>            
                <?php endforeach;?>
                <?php endif; ?>
            </tbody>
        </table>      
                    
        <?php if($wp_query->found_posts > 10): ?>
            <div class="tablenav" style="width:70%;float:right;">            
            <?php    
            $page_links = paginate_links( array(
                'base' => add_query_arg('paged', '%#%' ),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $wp_query->max_num_pages,
                'current' => isset($_GET['paged']) ? $_GET['paged'] : 1,
                'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
            ));         
            ?>
            <?php if(isset($_GET['show_results']) && $_GET['show_results']=='all'): ?>
            <div class="tablenav-pages">                
            <a href="admin.php?page=<?php echo $_GET['page'] ?><?php if(isset($_GET['context'])) echo '&amp;context='.$_GET['context'];?><?php if(isset($_GET['status'])) echo '&status='.$_GET['status'];?>"><?php printf(__('Display %d results per page', 'wpml-string-translation'), $sitepress_settings['st']['strings_per_page']); ?></a>
            </div>
            <?php endif; ?>            

            <div class="tablenav-pages"> 
                <?php if ( $page_links ): ?>               
                <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'wpml-string-translation' ) . '</span>%s',   
                    number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
                    number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
                    number_format_i18n( $wp_query->found_posts ),
                    $page_links
                    ); echo $page_links_text; 
                ?>                                         
                <?php endif; ?>
                <?php if(!isset($_GET['show_results'])): ?>
                <?php echo __('Strings per page:', 'wpml-string-translation')?>
                <?php 
                $params = array('context', 'status', 'search', 'em');
                $spp_qsa = '';
                foreach($params as $p){
                    if(isset($_GET[$p])){
                        $spp_qsa .= '&amp;' . urlencode($p) . '=' . urlencode($_GET[$p]);                        
                    }                    
                }    
                ?>
                <select name="icl_st_per_page" onchange="location.href='admin.php?page=<?php echo $_GET['page']?><?php echo $spp_qsa ?>&amp;strings_per_page='+this.value">
                    <option value="10"<?php if($sitepress_settings['st']['strings_per_page']==10) echo ' selected="selected"'; ?>>10</option>
                    <option value="20"<?php if($sitepress_settings['st']['strings_per_page']==20) echo ' selected="selected"'; ?>>20</option>
                    <option value="50"<?php if($sitepress_settings['st']['strings_per_page']==50) echo ' selected="selected"'; ?>>50</option>
                    <option value="100"<?php if($sitepress_settings['st']['strings_per_page']==100) echo ' selected="selected"'; ?>>100</option>
                </select>
                &nbsp;
                <a href="admin.php?page=<?php echo $_GET['page'] ?>&amp;show_results=all<?php if(isset($_GET['context'])) echo '&amp;context='.$_GET['context'];?><?php if(isset($_GET['status'])) echo '&amp;status='.$_GET['status'];?>"><?php echo __('Display all results', 'wpml-string-translation'); ?></a>
                <?php endif; ?>
            </div>
            
            </div>
        <?php endif; ?>    

        <?php if(current_user_can('manage_options')):  // the rest is only for admins. not for editors  ?>
        
        <span class="subsubsub">
            <input type="hidden" id="_icl_nonce_dstr" value="<?php echo wp_create_nonce('icl_st_delete_strings_nonce') ?>" />
            <input type="button" class="button-secondary" id="icl_st_delete_selected" value="<?php echo __('Delete selected strings', 'wpml-string-translation') ?>" disabled="disabled" />
            <span style="display:none"><?php echo __("Are you sure you want to delete these strings?\nTheir translations will be deleted too.",'wpml-string-translation') ?></span>
        </span>
        
        <br clear="all" />    
        
        <br />
        
        <form method="post" id="icl_st_send_strings" name="icl_st_send_strings" action="">
        <input type="hidden" name="icl_st_action" value="send_strings" />
        <input type="hidden" name="strings" value="" />
        <input type="hidden" name="icl-tr-from" value="<?php echo $sitepress_settings['st']['strings_language']; ?>" />
        
        <?php
        if (!empty($sitepress_settings['icl_lang_status'])){
            foreach($sitepress_settings['icl_lang_status'] as $lang){
                if($lang['from'] == $sitepress->get_current_language()) {
                    $target_status[$lang['to']] = $lang['have_translators'];
                    $target_rate[$lang['to']] = $lang['max_rate'];
                }
            }
        }
        ?>
        
        <?php if(isset($WPML_Translation_Management)): ?>
            <table id="icl-tr-opt" class="widefat fixed" cellspacing="0" style="width:100%">
                <thead>
                    <tr>
                        <th><?php _e('Translation options', 'wpml-string-translation')?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <ul id="icl_tm_languages">
                            <?php foreach($sitepress->get_active_languages()as $lang):?>
                            <?php 
                                if($lang['code'] == $sitepress_settings['st']['strings_language']) continue;
                            ?>
                            <li>
                                <label><input type="checkbox" name="translate_to[<?php echo $lang['code'] ?>]" value="1" id="icl_st_translate_to_<?php echo $lang['code'] ?>" />
                                    &nbsp;<?php printf(__('Translate to %s', 'wpml-string-translation'),$lang['display_name'])?></label>                            
                                <select name="service[<?php echo $lang['code'] ?>]" id="icl_st_service_<?php echo $lang['code']; ?>">
                                    <?php if(isset($target_status[$lang['code']]) && $target_status[$lang['code']]):?>
                                    <option value="icanlocalize"><?php _e('Use translators from ICanLocalize', 'wpml-string-translation') ?></option>
                                    <?php endif; ?>
                                    <option value="local"><?php _e('Use local translators', 'wpml-string-translation') ?></option>
                                </select>                       
                                </label>
                                &nbsp;<a href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&sm=translators"><?php _e('Manage translators', 'wpml-string-translation'); ?></a>
                                
                                
                                <?php if(isset($target_status[$lang['code']]) && $target_status[$lang['code']]):?>
                                <span style="display: none;" id="icl_st_max_rate_<?php  echo $lang['code'] ?>"><?php echo $target_rate[$lang['code']] ?></span>
                                <span style="display: none;" id="icl_st_estimate_<?php  echo $lang['code'] ?>_wrap" class="icl_st_estimate_wrap">
                                    &nbsp;(<?php printf(__('Estimated cost: %s USD', 'wpml-translation-management'), 
                                    '<span id="icl_st_estimate_' . $lang['code'] . '">0</span>') ?>)</span>
                                <?php endif; ?>    
                            </li>
                            <?php endforeach; ?>
                            </ul>
                            <input name="iclnonce" type="hidden" value="<?php echo wp_create_nonce('icl-string-translation') ?>" />
                            <input id="icl_send_strings" class="button-primary" type="submit" value="<?php _e('Translate strings', 'wpml-translation-management') ?>" disabled="disabled" />
                        </td>
                    </tr>
                </tbody>
            </table>        

            <?php if(isset($sitepress_settings['icl_balance'])): ?>
            <br clear="all" />
            <p>
                <?php echo sprintf(__('Your balance with ICanLocalize is %s. Visit your %sICanLocalize finance%s page to deposit additional funds.', 'wpml-string-translation'),
                                      '$'.$sitepress_settings['icl_balance'],
                                      $sitepress->create_icl_popup_link(ICL_API_ENDPOINT.'/finance/?wid=' . $sitepress_settings['site_id'], array('title'=>'ICanLocalize')),
                                      '</a>',
                                      'wpml-string-translation')?>
            </p>
            <br />
            <?php endif; ?>
        <?php endif; ?>
                    
        </form>    
    
        <br style="clear:both;" />
        <div id="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder">
            
                <div class="postbox-container" style="width: 49%;">
                    <div id="normal-sortables-stsel" class="meta-box-sortables ui-sortable">
                        <div id="dashboard_wpml_stsel_1" class="postbox">
                            <div class="handlediv" title="<?php echo __('Click to toggle', 'wpml-string-translation'); ?>">
                                <br/>
                            </div>
                            <h3 class="hndle">
                                <span><?php echo __('Track where string appear on the site', 'wpml-string-translation')?></span>
                            </h3>         
                            <div class="inside">
                                <p class="sub"><?php echo __("WPML can keep track of where strings are used on the public pages. Activating this feature will enable the 'view in page' functionality and make translation easier.", 'wpml-string-translation')?></p>
                                <form id="icl_st_track_strings" name="icl_st_track_strings" action="">
                                    <?php wp_nonce_field('icl_st_track_strings_nonce', '_icl_nonce'); ?>
                                    <p class="icl_form_errors" style="display:none"></p>
                                    <ul>
                                        <li>
                                           	<input type="hidden" name="icl_st[track_strings]" value="0" />
                                            <label><input type="checkbox" id="icl_st_track_strings" name="icl_st[track_strings]" value="1" <?php 
                                            if(!empty($sitepress_settings['st']['track_strings'])): ?>checked="checked"<?php endif ?> /> 
                                        <?php _e('Track where strings appear on the site', 'wpml-string-translation'); ?></label>
                                        <p><a href="http://wpml.org/?p=9073"><?php _e('Performance considerations', 'wpml-string-translation') ?>&nbsp;&raquo;</a></p>
                                        </li>
                                        <li>
                                            <label>
                                                <?php _e('Highlight color for strings', 'wpml-string-translation'); ?>
                                                <?php $hl_color = !empty($sitepress_settings['st']['hl_color'])?$sitepress_settings['st']['hl_color']:'#FFFF00'; ?>
                                                <input type="text" size="7" id="icl_st_hl_color" name="icl_st[hl_color]" value="<?php echo $hl_color ?>" 
                                                    style="background-color:<?php echo $hl_color ?>" />
                                            </label>
                                            <img src="<?php echo WPML_ST_URL; ?>/res/img/icon_color_picker.png" id="icl_st_hl_picker" 
                                                alt="" border="0" style="vertical-align:bottom;cursor:pointer;" class="pick-show" 
                                                onclick="cp.show('icl_st_hl_color');return false;" />
                                        </li>
                                    </ul>
                                    <p>
                                    <input class="button-secondary" type="submit" name="iclt_st_save" value="<?php _e('Apply', 'wpml-string-translation')?>" />
                                    <span class="icl_ajx_response" id="icl_ajx_response2" style="display:inline"></span>
                                    </p>
                                </form>
                                                               
                            </div>           
                        </div>
                        

                        <div id="dashboard_wpml_stsel_1.5" class="postbox">
                            <div class="handlediv" title="<?php echo __('Click to toggle', 'wpml-string-translation'); ?>">
                                <br/>
                            </div>
                            <h3 class="hndle">
                                <span><?php echo __('Auto register strings for translation', 'wpml-string-translation')?></span>
                            </h3>         
                            <div class="inside">
                                <p class="sub"><?php echo __('WPML can automatically register strings for translation. This allows you to translate user-generated content with minimal PHP code.', 'wpml-string-translation')?></p>
                                <form id="icl_st_ar_form" name="icl_st_ar_form" method="post" action="">
                                <?php wp_nonce_field('icl_st_ar_form_nonce', '_icl_nonce') ?>
                                    <p class="icl_form_errors" style="display:none"></p>
                                    <ul>
                                        <li>
                                        <label>
                                            <input type="radio" name="icl_auto_reg_type" value="disable" <?php if($sitepress_settings['st']['icl_st_auto_reg'] == 'disable'):?>checked="checked"<?php endif?> />
                                            <?php echo __('Disable auto-register strings', 'sitepress') ?>
                                        </label>                    
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="icl_auto_reg_type" value="auto-admin" <?php if(!$sitepress_settings['st']['icl_st_auto_reg'] || $sitepress_settings['st']['icl_st_auto_reg'] == 'auto-admin'):?>checked="checked"<?php endif?> />
                                                <?php echo __('Auto-register strings only when logged in as an administrator', 'sitepress') ?>
                                            </label>
                                        </li>
                                        <li>
                                            <label>
                                                <input type="radio" name="icl_auto_reg_type" value="auto-always" <?php if($sitepress_settings['st']['icl_st_auto_reg'] == 'auto-always'):?>checked="checked"<?php endif?> />
                                                <?php echo __('Auto-register strings always', 'sitepress') ?>
                                            </label>
                                            <p><a href="http://wpml.org/?p=9073"><?php _e('Performance considerations', 'wpml-string-translation') ?>&nbsp;&raquo;</a></p>
                                        </li>
                                    </ul>
                                    <p>
                                    <input class="button-secondary" type="submit" name="iclt_auto_reg_apply" value="<?php echo __('Apply', 'wpml-string-translation')?>" />
                                    <span class="icl_ajx_response" id="icl_ajx_response3" style="display:inline"></span>
                                    </p>
                                </form> 
                                                               
                            </div>           
                        </div>                        
                        
                        <div id="dashboard_wpml_stsel_2" class="postbox">
                            <div class="handlediv" title="<?php echo __('Click to toggle', 'wpml-string-translation'); ?>">
                                <br/>
                            </div>
                            <h3 class="hndle">
                                <span><?php echo __('Original language of strings', 'wpml-string-translation')?></span>
                            </h3>         
                            <div class="inside">
                                <p class="sub"><?php echo __('Choose the language in which the strings are written in.', 'wpml-string-translation')?></p>
                                <form id="icl_st_sw_form" name="icl_st_sw_form" method="post" action="">
                                    <?php wp_nonce_field('icl_sw_form') ?> 
                                    <p class="icl_form_errors" style="display:none"></p>
                                    <ul>
                                        <li>
                                            <label>
                                                <?php echo __('Strings Language', 'wpml-string-translation'); ?>                                                
                                                <select name="icl_st_sw[strings_language]"> 
                                                <?php foreach($sitepress->get_languages($sitepress->get_admin_language()) as $l): ?>
                                                <option value="<?php echo $l['code'] ?>" <?php 
                                                    if($l['code'] == $sitepress_settings['st']['strings_language']): ?>selected="selected"<?php endif; ?>><?php echo $l['display_name'] ?></option>
>                                                <?php endforeach; ?>
                                                </select>
                                            </label>
                                        </li>
                                    </ul>
                                    <p>
                                    <input class="button-secondary" type="submit" name="iclt_st_sw_save" value="<?php echo __('Save options and rescan strings', 'wpml-string-translation')?>" />
                                    <span class="icl_ajx_response" style="display:inline">&nbsp;<?php if(isset($_GET['updated']) && $_GET['updated']=='true') echo __('Settings saved', 'wpml-string-translation') ?></span>
                                    </p>
                                </form> 
                                                               
                            </div>           
                        </div>                        
                        
                    </div>
                </div>
                
                <div class="postbox-container" style="width: 49%;">
                    <div id="normal-sortables-poie" class="meta-box-sortables ui-sortable">
                        <div id="dashboard_wpml_st_poie" class="postbox">
                            <div class="handlediv" title="<?php echo __('Click to toggle', 'wpml-string-translation'); ?>">
                                <br/>
                            </div>
                            <h3 class="hndle">
                                <span><?php echo __('Import / export .po', 'wpml-string-translation')?></span>
                            </h3>         
                            <div class="inside">
                                <h5><?php echo __('Import', 'wpml-string-translation')?></h5>                         
                                <form id="icl_st_po_form" action="" name="icl_st_po_form" method="post" enctype="multipart/form-data">
                                    <?php wp_nonce_field('icl_po_form') ?>
                                    <p class="sub">
                                         <label for="icl_po_file"><?php echo __('.po file:', 'wpml-string-translation')?></label>
                                        <input id="icl_po_file" class="button primary" type="file" name="icl_po_file" />  
                                    </p>
                                    <p class="sub" style="line-height:2.3em">
                                        <input type="checkbox" name="icl_st_po_translations" id="icl_st_po_translations" />
                                        <label for="icl_st_po_translations"><?php echo __('Also create translations according to the .po file', 'wpml-string-translation')?></label>
                                        <select name="icl_st_po_language" id="icl_st_po_language" style="display:none">
                                        <?php foreach($active_languages as $al): if($al['code']==$sitepress_settings['st']['strings_language']) continue; ?>
                                        <option value="<?php echo $al['code'] ?>"><?php echo $al['display_name'] ?></option>
                                        <?php endforeach; ?>
                                        </select>
                                    </p>           
                                    <p class="sub" style="line-height:2.3em"    >
                                        <?php echo __('Select what the strings are for:', 'wpml-string-translation');?>
                                        <?php if(!empty($available_contexts)): ?>
                                        
                                        &nbsp;&nbsp;
                                        <span>                                        
                                        <select name="icl_st_i_context">
                                            <option value="">-------</option>
                                            <?php foreach($available_contexts as $v):?>
                                            <option value="<?php echo htmlspecialchars($v)?>" <?php if($context_filter == $v ):?>selected="selected"<?php endif;?>><?php echo $v; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <a href="#" onclick="var __nxt = jQuery(this).parent().next(); jQuery(this).prev().val(''); jQuery(this).parent().fadeOut('fast',function(){__nxt.fadeIn('fast')});return false;"><?php echo __('new','wpml-string-translation')?></a>
                                        </span>
                                        <?php endif; ?>
                                        <span <?php if(!empty($available_contexts)):?>style="display:none"<?php endif ?>>                                        
                                        <input type="text" name="icl_st_i_context_new" />
                                        <?php if(!empty($available_contexts)):?>
                                        <a href="#" onclick="var __prv = jQuery(this).parent().prev(); jQuery(this).prev().val(''); jQuery(this).parent().fadeOut('fast',function(){__prv.fadeIn('fast')});return false;"><?php echo __('select from existing','wpml-string-translation')?></a>
                                        <?php endif ?>
                                        </span>                                        
                                    </p>  
                                    
                                    <p>
                                    <input class="button" name="icl_po_upload" id="icl_po_upload" type="submit" value="<?php echo __('Submit', 'wpml-string-translation')?>" />        
                                    <span id="icl_st_err_domain" class="icl_error_text" style="display:none"><?php echo __('Please enter a context!', 'wpml-string-translation')?></span>
                                    <span id="icl_st_err_po" class="icl_error_text" style="display:none"><?php echo __('Please select the .po file to upload!', 'wpml-string-translation')?></span>
                                    </p>
                                    
                                </form>       
                                <?php if(!empty($icl_contexts)):?>
                                <h5><?php echo __('Export strings into .po/.pot file', 'wpml-string-translation')?></h5>                         
                                <form method="post" action="">
                                <?php wp_nonce_field('icl_po_export') ?>
                                <p>
                                    <?php echo __('Select context:', 'wpml-string-translation')?>
                                    <select name="icl_st_e_context" id="icl_st_e_context">
                                        <option value="" <?php if($context_filter === false ):?>selected="selected"<?php endif;?>><?php echo __('All contexts', 'wpml-string-translation') ?></option>
                                        <?php foreach($icl_contexts as $v):?>
                                        <option value="<?php echo htmlspecialchars($v->context)?>" <?php if($context_filter == $v->context ):?>selected="selected"<?php endif;?>><?php echo $v->context . ' ('.$v->c.')'; ?></option>
                                        <?php endforeach; ?>
                                    </select>   
                               </p>
                               <p style="line-height:2.3em">     
                                    <input type="checkbox" name="icl_st_pe_translations" id="icl_st_pe_translations" checked="checked" value="1" onchange="if(jQuery(this).attr('checked'))jQuery('#icl_st_e_language').fadeIn('fast'); else jQuery('#icl_st_e_language').fadeOut('fast')" />
                                    <label for="icl_st_pe_translations"><?php echo __('Also include translations', 'wpml-string-translation')?></label>                                
                                    <select name="icl_st_e_language" id="icl_st_e_language">
                                    <?php foreach($active_languages as $al): if($al['code']==$sitepress_settings['st']['strings_language']) continue; ?>
                                    <option value="<?php echo $al['code'] ?>"><?php echo $al['display_name'] ?></option>
                                    <?php endforeach; ?>
                                    </select>                                     
                                </p>  
                                <p><input type="submit" class="button-secondary" name="icl_st_pie_e" value="<?php echo __('Submit', 'wpml-string-translation')?>" /></p>                                                                      
                                <?php endif ?>
                                </form>
                            </div>           
                        </div>
                    </div>
                </div>
                
                <div class="postbox-container" style="width: 49%;">
                    <div id="normal-sortables-moreoptions" class="meta-box-sortables ui-sortable">
                        <div id="dashboard_wpml_st_poie" class="postbox">
                            <div class="handlediv" title="<?php echo __('Click to toggle', 'wpml-string-translation'); ?>">
                                <br/>
                            </div>
                            <h3 class="hndle">
                                <span><?php echo __('More options', 'wpml-string-translation')?></span>
                            </h3>         
                            <div class="inside">
                                <form id="icl_st_more_options" name="icl_st_more_options" method="post">
                                <?php wp_nonce_field('icl_st_more_options_nonce', '_icl_nonce') ?>
                                <div>
                                    <?php 
                                    $editable_roles = get_editable_roles(); 
                                    if(!isset($sitepress_settings['st']['translated-users'])) $sitepress_settings['st']['translated-users'] = array();
                                    
                                    $tnames = array();
                                    foreach($editable_roles as $role => $details){
                                        if(in_array($role, $sitepress_settings['st']['translated-users'])){
                                            $tnames[] = translate_user_role($details['name'] );    
                                        }    
                                    }
                                    
                                    $tustr = '<span id="icl_st_tusers_list">';
                                    if(!empty($tnames)){
                                        $tustr .= join(', ' , array_map('translate_user_role', $tnames));
                                    }else{
                                        $tustr = __('none', 'wpml-string-translation');
                                    }
                                    $tustr .= '</span>';
                                    $tustr .= '&nbsp;&nbsp;<a href="#" onclick="jQuery(\'#icl_st_tusers\').slideToggle();return false;">' . __('edit', 'wpml-string-translation') . '</a>';
                                    
                                    ?>
                                    <?php printf(__('Translating users of types: %s', 'wpml-string-translation'), $tustr); ?>
                                    
                                    
                                    <div id="icl_st_tusers" style="padding:6px;display: none;">
                                    <?php 
                                    foreach ( $editable_roles as $role => $details ) {
                                        $name = translate_user_role($details['name'] );
                                        $checked = in_array($role, (array)$sitepress_settings['st']['translated-users']) ? ' checked="checked"' : '';
                                        ?>
                                        <label><input type="checkbox" name="users[<?php echo $role ?>]" value="1"<?php echo $checked ?>/>&nbsp;<span><?php echo $name ?></span></label>&nbsp;
                                        <?php
                                    }
                                    ?>
                                    </div>
                                    
                                </div>
                            
                                <p class="submit">
                                    <input class="button-secondary" type="submit" value="<?php esc_attr_e('Apply', 'wpml-string-translation') ?>" />
                                    <span class="icl_ajx_response" id="icl_ajx_response4" style="display:inline"></span>
                                </p>
                                
                                </form>
                                
                                
                                
                            </div>                    
                    </div>    
                </div>
                
            </div>
        </div>
        
        <br clear="all" /><br />
    
        <a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;trop=1"><?php _e('Translate texts in admin screens &raquo;', 'wpml-string-translation'); ?></a>         
    
    <?php endif; //if(current_user_can('manage_options') ?>
    
    <?php endif; ?>
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>
