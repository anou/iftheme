<?php //included from menu translation-management.php ?>
<?php


if(isset($_SESSION['translation_dashboard_filter'])){
    $icl_translation_filter = $_SESSION['translation_dashboard_filter'];
}

if(!isset($icl_translation_filter['from_lang'])){
    $icl_translation_filter['from_lang'] = isset($_GET['lang'])?$_GET['lang']:$sitepress->get_current_language();
}

if(!isset($icl_translation_filter['to_lang'])){
    $icl_translation_filter['to_lang'] = isset($_GET['to_lang'])?$_GET['to_lang']:'';
}

if($icl_translation_filter['to_lang'] == $icl_translation_filter['from_lang']){
   $icl_translation_filter['to_lang'] = false;
}

if(!isset($icl_translation_filter['tstatus'])){
    $icl_translation_filter['tstatus'] = isset($_GET['tstatus'])?$_GET['tstatus']:'not';
}

if(!isset($icl_translation_filter['sort_by']) || !$icl_translation_filter['sort_by']){ $icl_translation_filter['sort_by'] = 'p.post_date';}
if(!isset($icl_translation_filter['sort_order']) || !$icl_translation_filter['sort_order']){ $icl_translation_filter['sort_order'] = 'DESC';}
$sort_order_next = $icl_translation_filter['sort_order'] == 'ASC' ? 'DESC' : 'ASC';
$title_sort_link = 'admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=dashboard&icl_tm_action=sort&sort_by=p.post_title&sort_order='.$sort_order_next;
$date_sort_link = 'admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=dashboard&icl_tm_action=sort&sort_by=p.post_date&sort_order='.$sort_order_next;

$icl_post_statuses = array(
    'publish'   =>__('Published', 'wpml-translation-management'),
    'draft'     =>__('Draft', 'wpml-translation-management'),
    'pending'   =>__('Pending Review', 'wpml-translation-management'),
    'future'    =>__('Scheduled', 'wpml-translation-management'),
    'private'   =>__('Private', 'wpml-translation-management')
);

// Get the document types that we can translate
$icl_post_types = $sitepress->get_translatable_documents();
$icl_post_types = apply_filters('WPML_get_translatable_types', $icl_post_types);
foreach ($icl_post_types as $id => $type_info) {
    if (is_string($type_info)) {

        // this is an external type returned by WPML_get_translatable_types
        $new_type = new stdClass();
        $new_type->labels->singular_name = $type_info;
        $new_type->labels->name = $type_info;

        $new_type->external_type = 1;

        $icl_post_types[$id] = $new_type;
    }
}

$icl_dashboard_settings = isset($sitepress_settings['dashboard']) ? $sitepress_settings['dashboard'] : array();

$icl_translation_filter['limit_no'] = isset($_GET['show_all']) && $_GET['show_all'] ? 10000 : ICL_TM_DOCS_PER_PAGE;
if(!isset($icl_translation_filter['parent_type'])) $icl_translation_filter['parent_type'] = 'any';


// Get all the documents
$icl_documents = $iclTranslationManagement->get_documents($icl_translation_filter);

// Get any documents from external sources.
foreach ($icl_post_types as $id => $type_info) {
    if (isset($icl_translation_filter['type']) && $id == $icl_translation_filter['type']
            && isset($type_info->external_type) && $type_info->external_type) {
        $icl_documents = apply_filters('WPML_get_translatable_items', $icl_documents, $id, $icl_translation_filter);
    }
}

$icl_translators = $iclTranslationManagement->get_blog_translators();

$icl_selected_posts         = array();
$icl_selected_languages     = array();
$icl_selected_translators   = array();
if(!empty($iclTranslationManagement->dashboard_select)){
    $icl_selected_posts = $iclTranslationManagement->dashboard_select['post'];
    $icl_selected_languages = $iclTranslationManagement->dashboard_select['translate_to'];
    $icl_selected_translators = $iclTranslationManagement->dashboard_select['translator'];
}

if(!empty($sitepress_settings['default_translators'][$icl_translation_filter['from_lang']])){
    foreach($sitepress_settings['default_translators'][$icl_translation_filter['from_lang']] as $_tolang => $tr){
        if($iclTranslationManagement->translator_exists($tr['id'], $icl_translation_filter['from_lang'], $_tolang, $tr['type'])){
            $icl_selected_translators[$_tolang] = $tr['type'] == 'local' ? $tr['id'] : $tr['id'] . '-' . $tr['type'];
        }
    }
}
foreach($sitepress->get_active_languages()as $lang){
    if(empty($icl_selected_translators[$lang['code']]) && !empty($sitepress_settings['icl_lang_status']) && is_array($sitepress_settings['icl_lang_status'])){
        foreach($sitepress_settings['icl_lang_status'] as $lpair){
            if($lpair['from']==$icl_translation_filter['from_lang'] && $lpair['to']==$lang['code'] && !empty($lpair['translators'])){
                $icl_selected_translators[$lang['code']] = $lpair['translators']['0']['id'] . '-icanlocalize';
            }
        }
    }
}

if(!defined('ICL_DONT_PROMOTE') || !ICL_DONT_PROMOTE){

    $icl_translation_services = apply_filters('icl_translation_services', array());
    $icl_translation_services = array_merge($icl_translation_services, TranslationManagement::icanlocalize_service_info());
    if (!empty($icl_translation_services)) {
        $icls_output = '';
        if(empty($icl_dashboard_settings['hide_icl_promo'])){
            $nt_visible = ' ="hidden"';
            $nt_show = '';
        }else{
            $nt_visible =  '';
            $nt_show = 'hidden';
        }

        $icls_output .= '<div class="icl-translation-services '.$nt_show.'">';
        foreach ($icl_translation_services as $key => $service) {
			$icls_output .= '<div class="icl-translation-services-inner">';
				$icls_output .= '<p class="icl-translation-services-logo"><span><img src="' . $service['logo'] . '" alt="' . $service['name'] . '" /></span></p>';
				$icls_output .= '<h3 class="icl-translation-services-header">  ' . $service['header']. '</h3>';
				$icls_output .= '<div class="icl-translation-desc"> '. $service['description'] . '</div>';
			$icls_output .= '</div>';
			$icls_output .= '<p class="icl-translation-buttons">';
				$icls_output .= '<a href="' . admin_url( 'index.php?icl_ajx_action=quote-get&_icl_nonce=' . wp_create_nonce( 'quote-get_nonce' ) ) . '" class="button-primary thickbox">' . __( 'Get quote', 'wpml-translation-management' ) . '</a>';
				$icls_output .= '<a href="admin.php?page=wpml-translation-management/menu/main.php&sm=translators&icl_lng=' . $sitepress->get_current_language() . '&service=icanlocalize" class="button-secondary"><span>' . __( 'Add translators from ICanLocalize', 'wpml-translation-management' ) . '</span></a>';
			$icls_output .= '</p>';
			$icls_output .= '<p class="icl-translation-links">';
				$icls_output .= '<a class="icl-mail-ico" href="http://www.icanlocalize.com/site/about-us/contact-us/?utm_source=WPML&utm_medium=dashboard&utm_term=contact-icanlocalize&utm_content=dashboard-message&utm_campaign=WPML" target="_blank">' . __('Contact ICanLocalize', 'wpml-translation-management') . '</a>';
				$icls_output .= '<a id="icl_hide_promo" href="#">' . __('Hide this', 'wpml-translation-management') . '</a>';
			$icls_output .= '</p>';
        }
        $icls_output .= '</div>';
    }

}else{
    $icls_output = "";
}
?>

    <form method="post" name="translation-dashboard-filter" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=dashboard">
    <input type="hidden" name="icl_tm_action" value="dashboard_filter" />

    <table class="form-table widefat fixed">
        <thead>
        <tr>
            <th scope="col" colspan="2"><strong><?php _e('Select which documents to display','wpml-translation-management')?></strong></th>
        </tr>
        </thead>
        <tr valign="top">
            <td colspan="2">
                <img id="icl_dashboard_ajax_working" align="right" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display: none;" width="16" height="16" alt="loading..." />
                <label>
                    <strong><?php echo __('Show documents in:', 'wpml-translation-management') ?></strong>
                    <select name="filter[from_lang]">
                    <!--<option value=""><?php _e('All languages', 'wpml-translation-management') ?></option>-->
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>
                        <option value="<?php echo $lang['code'] ?>" <?php if($icl_translation_filter['from_lang']==$lang['code']): ?>selected="selected"<?php endif;?>>
                            <?php echo $lang['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </label>
                &nbsp;
                <label>
                    <strong><?php _e('Translated to:', 'wpml-translation-management');?></strong>
                    <select name="filter[to_lang]">
                    <option value=""><?php _e('All languages', 'wpml-translation-management') ?></option>
                    <?php foreach($sitepress->get_active_languages() as $lang): ?>
                        <option value="<?php echo $lang['code'] ?>" <?php if($icl_translation_filter['to_lang']==$lang['code']): ?>selected="selected"<?php endif;?>><?php echo $lang['display_name'] ?></option>
                    <?php endforeach; ?>
                    </select>
                </label>
                &nbsp;
                <label>
                    <strong><?php echo __('Translation status:', 'wpml-translation-management') ?></strong>
                    <select name="filter[tstatus]">
                        <?php
                            $option_status = array(
                                                   'all' => __('All documents', 'wpml-translation-management'),
                                                   'not' => __('Not translated or needs updating', 'wpml-translation-management'),
                                                   'need-update' => __('Needs updating', 'wpml-translation-management'),
                                                   'in_progress' => __('Translation in progress', 'wpml-translation-management'),
                                                   'complete' => __('Translation complete', 'wpml-translation-management'));
                        ?>
                        <?php foreach($option_status as $k=>$v):?>
                        <option value="<?php echo $k ?>" <?php if($icl_translation_filter['tstatus']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <br />
            </td>
        </tr>
        <tr id="icl_dashboard_advanced_filters" valign="top">
            <td>
                <strong><?php echo __('Filters:', 'wpml-translation-management') ?></strong><br />

                <?php // filters start */ ?>
                <table>
                    <tr>
                        <td><?php _e('Status:', 'wpml-translation-management')?></td>
                        <td align="right">
                            <select name="filter[status]">
                                <option value=""><?php _e('Any', 'wpml-translation-management') ?></option>
                                <?php foreach($icl_post_statuses as $k=>$v):?>
                                <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['status']) && $icl_translation_filter['status']==$k):?>selected="selected"<?php endif?>><?php echo $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>&nbsp;</td>
                    </tr>

                    <tr>
                        <td><?php _e('Type:', 'wpml-translation-management')?></td>
                        <td align="right">
                            <select name="filter[type]">
                                <option value=""><?php _e('Any', 'wpml-translation-management') ?></option>
                                <?php foreach($icl_post_types as $k=>$v):?>
                                <option value="<?php echo $k ?>" <?php if(isset($icl_translation_filter['type']) && $icl_translation_filter['type']==$k):?>selected="selected"<?php endif?>><?php echo $v->labels->singular_name != "" ? $v->labels->singular_name : $v->labels->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>&nbsp;</td>
                    </tr>

                    <tr>
                        <td><?php _e('Title:', 'wpml-translation-management')?></td>
                        <td align="right">
                            <input type="text" name="filter[title]" value="<?php echo isset($icl_translation_filter['title']) ? $icl_translation_filter['title'] : '' ?>" />
                        </td>
                        <td>
                            <?php if(!empty($icl_translation_filter['title'])): ?>
                                <input class="button-secondary" type="button" value="<?php esc_attr_e('clear', 'wpml-translation-management') ?>" onclick="jQuery(this).parent().parent().find('input[type=text]').val('');jQuery(this).fadeOut();" />
                            <?php else: ?>
                                &nbsp;
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <td><?php echo __('Parent:', 'wpml-translation-management') ?></td>
                        <td align="right">
                            <label>
                                <select id="icl_parent_filter_control" name="filter[parent_type]">
                                    <option value=""><?php _e('Any', 'wpml-translation-management') ?></option>
                                    <option value="page" <?php if($icl_translation_filter['parent_type']=='page'):?>selected="selected"<?php endif?>><?php _e('Page', 'wpml-translation-management') ?></option>
                                    <option value="category" <?php if($icl_translation_filter['parent_type']=='category'):?>selected="selected"<?php endif?>><?php _e('Category', 'wpml-translation-management') ?></option>
                                </select>
                            </label>
                            <input type="hidden" id="icl_tm_parent_id" value="<?php if(isset($icl_translation_filter['parent_id'])) echo $icl_translation_filter['parent_id']; ?>" />
                            <input type="hidden" id="icl_tm_parent_all" value="<?php if(isset($icl_translation_filter['parent_all']))  echo $icl_translation_filter['parent_all']; ?>" />
                        </td>
                        <td><label id="icl_parent_filter_drop"></label></td>
                    </tr>
                    <tr>
                        <td colspan="3" >
                            <p><input name="translation_dashboard_filter" class="button-primary" type="submit" value="<?php echo __('Display','wpml-translation-management')?>" /></p>
                        </td>
                    </tr>
                </table>
                <?php // filters end */ ?>

            </td>
            <td align="right">
                <?php echo $icls_output; ?>
            </td>
        </tr>
    </table>
    </form>
    <br />

    <?php
    // #############################################
    // Display the items for translation in a table.
    // #############################################
    ?>

    <form method="post">
    <input type="hidden" name="icl_tm_action" value="send_jobs" />
    <input type="hidden" name="translate_from" value="<?php echo $icl_translation_filter['from_lang'] ?>" />
    <table class="widefat fixed" id="icl-tm-translation-dashboard" cellspacing="0">
        <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?>/></th>
            <th scope="col"><a href="<?php echo $title_sort_link ?>"><?php echo __('Title', 'wpml-translation-management') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_title') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date"><a href="<?php echo $date_sort_link ?>"><?php echo __('Date', 'wpml-translation-management') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_date') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'wpml-translation-management') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'wpml-translation-management') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'wpml-translation-management') ?></th>
            <?php if($icl_translation_filter['to_lang']): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($icl_translation_filter['to_lang']) ?>" width="16" height="12" alt="<?php echo $icl_translation_filter['to_lang'] ?>" />
                </th>
            <?php else: ?>
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
                <th scope="col" class="manage-column column-cb check-column">
                    <img src="<?php echo $sitepress->get_flag_url($lang['code']) ?>" width="16" height="12" alt="<?php echo $lang['code'] ?>" />
                </th>
                <?php endforeach; ?>
            <?php endif; ?>

        </tr>
        </thead>
        <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" <?php if(isset($_GET['post_id'])) echo 'checked="checked"'?>/></th>
            <th scope="col"><a href="<?php echo $title_sort_link ?>"><?php echo __('Title', 'wpml-translation-management') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_title') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date"><a href="<?php echo $date_sort_link ?>"><?php echo __('Date', 'wpml-translation-management') ?>&nbsp;
                <?php if($icl_translation_filter['sort_by']=='p.post_date') echo $icl_translation_filter['sort_order']=='ASC' ? '&uarr;' : '&darr;' ?></a></th>
            <th scope="col" class="manage-column column-date">
                <img title="<?php _e('Note for translators', 'wpml-translation-management') ?>" src="<?php echo ICL_PLUGIN_URL ?>/res/img/notes.png" alt="note" width="16" height="16" /></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Type', 'wpml-translation-management') ?></th>
            <th scope="col" class="manage-column column-date"><?php echo __('Status', 'wpml-translation-management') ?></th>
            <?php if($icl_translation_filter['to_lang']): ?>
            <th scope="col" class="manage-column column-cb check-column">
                <img src="<?php echo $sitepress->get_flag_url($icl_translation_filter['to_lang']) ?>" width="16" height="12" alt="<?php echo $icl_translation_filter['to_lang'] ?>" />
                </th>
            <?php else: ?>
                <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
                <th scope="col" class="manage-column column-cb check-column">
                    <img src="<?php echo $sitepress->get_flag_url($lang['code']) ?>" width="16" height="12" alt="<?php echo $lang['code'] ?>" />
                </th>
                <?php endforeach; ?>
            <?php endif; ?>
        </tr>
        </tfoot>
        <tbody>
            <?php $wctotal = 0; ?>
            <?php if(!$icl_documents): ?>
            <tr>
                <td scope="col" colspan="<?php
                    echo 6 + ($icl_translation_filter['to_lang'] ? 1 : count($sitepress->get_active_languages())-1); ?>" align="center"><?php _e('No documents found', 'wpml-translation-management') ?></td>
            </tr>
            <?php else: $oddcolumn = false; ?>

            <?php
            // #############################################
            //          Display each document
            // #############################################
            ?>

            <?php foreach($icl_documents as $doc): $oddcolumn=!$oddcolumn; ?>
            <tr<?php if($oddcolumn): ?> class="alternate"<?php endif;?>>
                <td scope="row">
                    <input type="checkbox" value="<?php echo $doc->post_id ?>" name="iclpost[]" <?php
                        if(isset($_GET['post_id']) || (is_array($icl_selected_posts) && in_array($doc->post_id, $icl_selected_posts))) echo 'checked="checked"'?> />
                </td>
                <td scope="row" class="post-title column-title">
                    <?php echo TranslationManagement::tm_post_link($doc->post_id); ?>
                    <?php
                        $wc = $iclTranslationManagement->estimate_word_count($doc, $icl_translation_filter['from_lang']);
                        $wc += $iclTranslationManagement->estimate_custom_field_word_count($doc->post_id, $icl_translation_filter['from_lang']);
                    ?>
                    <span id="icl-cw-<?php echo $doc->post_id ?>" style="display:none"><?php echo $wc; $wctotal+=$wc; ?></span>
                    <span class="icl-tr-details">&nbsp;</span>
                    <div class="icl_post_note" id="icl_post_note_<?php echo $doc->post_id ?>">
                        <?php
                            $note = '';
                            if(!$doc->is_translation){
                                $note = get_post_meta($doc->post_id, '_icl_translator_note', true);
                                if($note){
                                    $note_text = __('Edit note for the translators', 'wpml-translation-management');
                                    $note_icon = 'edit_translation.png';
                                }else{
                                    $note_text = __('Add note for the translators', 'wpml-translation-management');
                                    $note_icon = 'add_translation.png';
                                }
                            }
                        ?>
                        <?php _e('Note for the translators', 'wpml-translation-management')?>
                        <textarea rows="5"><?php echo $note ?></textarea>
                        <table width="100%"><tr>
                        <td style="border-bottom:none">
                            <input type="button" class="icl_tn_clear button"
                                value="<?php _e('Clear', 'wpml-translation-management')?>" <?php if(!$note): ?>disabled="disabled"<?php endif; ?> />
                            <input class="icl_tn_post_id" type="hidden" value="<?php echo $doc->post_id ?>" />
                        </td>
                        <td align="right" style="border-bottom:none">
                            <input type="button" class="icl_tn_save button-primary" value="<?php _e('Save', 'wpml-translation-management')?>" />
                            <?php wp_nonce_field('save_translator_note_nonce', '_icl_nonce_stn_' . $doc->post_id) ?>
                        </td>
                        </tr></table>
                    </div>
                </td>
                <td scope="row" class="post-date column-date">
                    <?php if($doc->post_date) echo date('Y-m-d', strtotime($doc->post_date)); ?>
                </td>
                <td scope="row" class="icl_tn_link" id="icl_tn_link_<?php echo $doc->post_id ?>">
                    <?php if($doc->is_translation):?>
                    &nbsp;
                    <?php else: ?>
                    <a title="<?php echo $note_text ?>" href="#"><img src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $note_icon ?>" width="16" height="16" /></a>
                    <?php endif; ?>
                </td>
                <td scope="row">
                    <?php echo $icl_post_types[$doc->post_type]->labels->singular_name != "" ? $icl_post_types[$doc->post_type]->labels->singular_name : $icl_post_types[$doc->post_type]->labels->name; ?>
                    <input class="icl_td_post_type" name="icl_post_type[<?php echo $doc->post_id ?>]" type="hidden" value="<?php echo $doc->post_type ?>" />
                </td>
                <td scope="row"><?php if (isset($icl_post_statuses[$doc->post_status])) echo $icl_post_statuses[$doc->post_status]; else echo $doc->post_status?></td>
                <?php if($icl_translation_filter['to_lang']): ?>
                <?php $docst = $doc->needs_update ? ICL_TM_NEEDS_UPDATE : intval($doc->status); ?>
                <td scope="row" class="manage-column column-cb check-column" style="padding-top:4px;">
                    <img
                        src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = $iclTranslationManagement->status2img_filename($docst)?>"
                        width="16" height="16" alt="<?php echo $_st ?>" />
                    </td>
                <?php else: ?>
                    <?php foreach($sitepress->get_active_languages() as $lang): if($lang['code']==$icl_translation_filter['from_lang']) continue;?>
                    <?php

                        $_suffix = str_replace('-','_',$lang['code']);
                        $_prop_up = 'needs_update_'.$_suffix;
                        $_prop_st = 'status_'.$_suffix;

                        if(!isset($doc->$_prop_up)) $doc->$_prop_up = false;
                        if(!isset($doc->$_prop_st)) $doc->$_prop_st = ICL_TM_NOT_TRANSLATED;

                        switch(intval($doc->$_prop_st)){
                            case ICL_TM_NOT_TRANSLATED : $tst_title = esc_attr(__('Not translated','wpml-translation-management')); break;
                            case ICL_TM_WAITING_FOR_TRANSLATOR : $tst_title = esc_attr(__('Waiting for translator','wpml-translation-management')); break;
                            case ICL_TM_IN_PROGRESS : $tst_title = esc_attr(__('In progress','wpml-translation-management')); break;
                            case ICL_TM_DUPLICATE : $tst_title = esc_attr(__('Duplicate','wpml-translation-management')); break;
                            case ICL_TM_COMPLETE : $tst_title = esc_attr(__('Complete','wpml-translation-management')); break;
                            default: $tst_title = '';
                        }
                        $docst = ($doc->$_prop_up && $icl_translation_filter['tstatus']=='not') ? ICL_TM_NEEDS_UPDATE : intval($doc->$_prop_st);
                        if($doc->$_prop_up){
                            $tst_title .= ' - ' . esc_attr(__('needs update','wpml-translation-management'));
                        }

                    ?>
                    <td scope="row" class="manage-column column-cb check-column" style="padding-top:4px;">
                        <img title="<?php echo $tst_title ?>"
                            src="<?php echo ICL_PLUGIN_URL ?>/res/img/<?php echo $_st = $iclTranslationManagement->status2img_filename($docst, $doc->$_prop_up)?>"
                            width="16" height="16" alt="<?php echo $tst_title ?>" />
                    </td>
                    <?php endforeach; ?>
                <?php endif; ?>


            </tr>
            <?php endforeach;?>
            <?php endif;?>
        </tbody>
    </table>


    <?php

    if(isset($_GET['show_all']) && $_GET['show_all'] && count($icl_documents)>ICL_TM_DOCS_PER_PAGE){
        echo '<a style="float:right" href="'.admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=dashboard').'">' . sprintf(__('Show %d documents per page', 'wpml-translation-management'),
             ICL_TM_DOCS_PER_PAGE) . '</a>';
    }
    // pagination
    $page_links = paginate_links( array(
        'base' => add_query_arg('paged', '%#%' ),
        'format' => '',
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'total' => $wp_query->max_num_pages,
        'current' => $_GET['paged'],
        'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array()
    ));

    ?>
    <span id="icl-cw-total" style="display:none"><?php echo $wctotal; ?></span>
    <div class="tablenav">
        <div style="float:left;margin-top:4px;">
            <strong><?php echo __('Word count estimate:', 'wpml-translation-management') ?></strong> <?php printf(__('%s words', 'wpml-translation-management'), '<span id="icl-tm-estimated-words-count">0</span>')?>
            <span id="icl-tm-doc-wrap" style="display: none"><?php printf(__('in %s document(s)', 'wpml-translation-management'), '<span id="icl-tm-sel-doc-count">0</span>'); ?></span>
        </div>
        <?php if ( $page_links ) { ?>
        <div class="tablenav-pages">
        <?php
        if(!isset($_GET['show_all']) && $wp_query->found_posts > ICL_TM_DOCS_PER_PAGE){
            echo '<a style="font-weight:normal" href="'.admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=dashboard&show_all=1').'">' . __('show all', 'wpml-translation-management') . '</a>';
        }
        ?>
        <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'wpml-translation-management' ) . '</span>%s',
            number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
            number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
            number_format_i18n( $wp_query->found_posts ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
        <?php } ?>
    </div>
    <?php // pagination - end ?>


    <table class="widefat fixed" cellspacing="0" style="width:100%">
        <thead>
            <tr>
                <th><?php _e('Translation options', 'wpml-translation-management')?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table id="icl_tm_languages" class="widefat" style="width:auto;border: none;">
                        <?php foreach($sitepress->get_active_languages()as $lang):?>
                        <?php
                            if($lang['code'] == $icl_translation_filter['from_lang']) continue;
                            $tr_checked = isset($icl_selected_languages[$lang['code']]) ? 'checked="checked"' : '';
                            $du_checked = '';
                            $no_checked = empty($tr_checked) && empty($du_checked) ? 'checked="checked"' : '';
                        ?>
                        <tr>
                            <td><strong><?php echo $lang['display_name'] ?></strong></td>
                            <td>
                                <label>
                                    <input type="radio" name="tr_action[<?php echo $lang['code']; ?>]" value="1" <?php echo $tr_checked ?>/>
                                    <?php _e('Translate by', 'wpml-translation-management'); ?>
                                 </label>
                                <?php $iclTranslationManagement->translators_dropdown(array(
                                                'from'          => $icl_translation_filter['from_lang'],
                                                'to'            => $lang['code'],
                                                'name'          => 'translator['.$lang['code'].']',
                                                'selected'      =>  isset($icl_selected_translators[$lang['code']]) ? $icl_selected_translators[$lang['code']] : 0,
                                                'services'      => array('local', 'icanlocalize')
                                                ));
                                ?>
                                &nbsp;<a href="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&sm=translators"><?php _e('Manage translators', 'wpml-translation-management'); ?></a>&nbsp;&nbsp;|
                            </td>
                            <td>
                                <label>
                                    <input type="radio" name="tr_action[<?php echo $lang['code']; ?>]" value="0" <?php echo $no_checked ?>/>
                                    <?php _e('Do not update', 'wpml-translation-management'); ?>&nbsp;&nbsp;|
                                </label>
                            </td>
                            <td>
                                <label>
                                    <input type="radio" name="tr_action[<?php echo $lang['code']; ?>]" value="2" <?php echo $du_checked ?>/>
                                    <?php _e('Duplicate content', 'wpml-translation-management'); ?>
                                </label>
                            </td>
                        </tr>

                        <?php endforeach; ?>
                    </table>
                    <br />

                    <input name="iclnonce" type="hidden" value="<?php echo wp_create_nonce('pro-translation-icl') ?>" />
                    <input id="icl_tm_jobs_submit" class="button-primary" type="submit" value="<?php _e('Send documents', 'wpml-translation-management') ?>"
                        <?php if(empty($icl_selected_languages) && empty($icl_selected_posts)):?>disabled="disabled" <?php endif; ?> /><br /><br />
                    <div class="icl_tm_error" id="icl_dup_ovr_warn" style="display: none">
                        <?php esc_html_e('Any existing content (translations) will be overwritten when creating duplicates.', 'wpml-translation-management'); ?><br />
                        <?php esc_html_e("When duplicating content, please first duplicate parent pages to maintain the site's hierarchy.", 'wpml-translation-management'); ?>

                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    </form>

    <br />
    <?php $ICL_Pro_Translation->get_icl_manually_tranlations_box('icl_cyan_box'); // shows only when translation polling is on and there are translations in progress ?>

<?php if ($sitepress->icl_account_configured() && $sitepress_settings['icl_html_status']): ?>
    <div class="icl_cyan_box">
        <h3><?php _e('ICanLocalize account status', 'wpml-translation-management') ?></h3>
    <?php echo $sitepress_settings['icl_html_status']; ?>
    </div>
<?php endif; ?>
