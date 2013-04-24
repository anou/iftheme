<?php

function icl_get_home_url() {
    global $sitepress;
    return $sitepress->language_url($sitepress->get_current_language());
}

// args:
// skip_missing (0|1|true|false)
// orderby (id|code|name)
// order (asc|desc)
function icl_get_languages($a='') {
    if ($a) {
        parse_str($a, $args);
    } else {
        $args = '';
    }
    global $sitepress;
    $langs = $sitepress->get_ls_languages($args);
    return $langs;
}

function icl_disp_language($native_name, $translated_name,
        $lang_native_hidden = false, $lang_translated_hidden = false) {
    if (!$native_name && !$translated_name) {
        $ret = '';
    } elseif ($native_name && $translated_name) {
        $hidden1 = $hidden2 = $hidden3 = '';
        if ($lang_native_hidden) {
            $hidden1 = 'style="display:none;"';
        }
        if ($lang_translated_hidden) {
            $hidden2 = 'style="display:none;"';
        }
        if ($lang_native_hidden && $lang_translated_hidden) {
            $hidden3 = 'style="display:none;"';
        }

        if ($native_name != $translated_name) {
            $ret = '<span ' . $hidden1 . ' class="icl_lang_sel_native">' . $native_name .
                    '</span> <span ' . $hidden2 . ' class="icl_lang_sel_translated"><span ' . $hidden1 . ' class="icl_lang_sel_native">(</span>' . $translated_name .
                    '<span ' . $hidden1 . ' class="icl_lang_sel_native">)</span></span>';
        } else {
            $ret = '<span ' . $hidden3 . ' class="icl_lang_sel_current">' . $native_name . '</span>';
        }
    } elseif ($native_name) {
        $ret = $native_name;
    } elseif ($translated_name) {
        $ret = $translated_name;
    }

    return $ret;
}

function icl_link_to_element($element_id, $element_type='post', $link_text='',
        $optional_parameters=array(), $anchor='', $echoit = true,
        $return_original_if_missing = true) {
    global $sitepress, $wpdb, $wp_post_types, $wp_taxonomies;

    if ($element_type == 'tag')
        $element_type = 'post_tag';
    if ($element_type == 'page')
        $element_type = 'post';

    $post_types = array_keys((array) $wp_post_types);
    $taxonomies = array_keys((array) $wp_taxonomies);

    if (in_array($element_type, $taxonomies)) {
        $element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='{$element_type}'",
                        $element_id));
    } elseif (in_array($element_type, $post_types)) {
        $element_type = 'post';
    }

    
    if (!$element_id)
        return '';

    if (in_array($element_type, $taxonomies)) {
        $icl_element_type = 'tax_' . $element_type;
    } elseif (in_array($element_type, $post_types)) {
        $icl_element_type = 'post_' . $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID='{$element_id}'");
    }


    $trid = $sitepress->get_element_trid($element_id, $icl_element_type);
    $translations = $sitepress->get_element_translations($trid,
            $icl_element_type);


    // current language is ICL_LANGUAGE_CODE    
    if (isset($translations[ICL_LANGUAGE_CODE])) {
        if ($element_type == 'post') {
            $url = get_permalink($translations[ICL_LANGUAGE_CODE]->element_id);
            $title = $translations[ICL_LANGUAGE_CODE]->post_title;
        } elseif ($element_type == 'post_tag') {
            list($term_id, $title) = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='post_tag'",
                            $translations[ICL_LANGUAGE_CODE]->element_id),
                    ARRAY_N);
            $url = get_tag_link($term_id);
            $title = apply_filters('single_cat_title', $title);
        } elseif ($element_type == 'category') {
            list($term_id, $title) = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='category'",
                            $translations[ICL_LANGUAGE_CODE]->element_id),
                    ARRAY_N);
            $url = get_category_link($term_id);
            $title = apply_filters('single_cat_title', $title);
        } else {
            list($term_id, $title) = $wpdb->get_row($wpdb->prepare("SELECT t.term_id, t.name FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='{$element_type}'",
                            $translations[ICL_LANGUAGE_CODE]->element_id),
                    ARRAY_N);
            $url = get_term_link($term_id, $element_type);
            $title = apply_filters('single_cat_title', $title);
        }
    } else {
        if (!$return_original_if_missing) {
            if ($echoit) {
                echo '';
            }
            return '';
        }

        if ($element_type == 'post') {
            $url = get_permalink($element_id);
            $title = get_the_title($element_id);
        } elseif ($element_type == 'post_tag') {
            $url = get_tag_link($element_id);
            $my_tag = &get_term($element_id, 'post_tag', OBJECT, 'display');
            $title = apply_filters('single_tag_title', $my_tag->name);
        } elseif ($element_type == 'category') {
            $url = get_category_link($element_id);
            $my_cat = &get_term($element_id, 'category', OBJECT, 'display');
            $title = apply_filters('single_cat_title', $my_cat->name);
        } else {
            $url = get_term_link((int) $element_id, $element_type);
            $my_cat = &get_term($element_id, $element_type, OBJECT, 'display');
            $title = apply_filters('single_cat_title', $my_cat->name);
        }
    }

    if (!$url || is_wp_error($url))
        return '';

    if (!empty($optional_parameters)) {
        $url_glue = false === strpos($url, '?') ? '?' : '&';
        $url .= $url_glue . http_build_query($optional_parameters);
    }

    if (isset($anchor) && $anchor) {
        $url .= '#' . $anchor;
    }

    $link = '<a href="' . $url . '">';
    if (isset($link_text) && $link_text) {
        $link .= $link_text;
    } else {
        $link .= $title;
    }
    $link .= '</a>';

    if ($echoit) {
        echo $link;
    } else {
        return $link;
    }
}

function icl_object_id($element_id, $element_type='post',
        $return_original_if_missing=false, $ulanguage_code=null) {
    global $sitepress, $wpdb, $wp_post_types, $wp_taxonomies;

    // special case of any - we assume it's a post type
    if($element_type == 'any' && $_dtype = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $element_id))){
        $element_type = $_dtype;    
    }
    // 
    
    static $fcache = array();
    $fcache_key = $element_id . '#' . $element_type . '#' . intval($return_original_if_missing) . '#' . $ulanguage_code;
    if (isset($fcache[$fcache_key])) {
        return $fcache[$fcache_key];
    }

    if ($element_id <= 0) {
        return $element_id;
    }

    $post_types = array_keys((array) $wp_post_types);
    $taxonomies = array_keys((array) $wp_taxonomies);
    $element_types = array_merge($post_types, $taxonomies);
    $element_types[] = 'comment';
    
    if (!in_array($element_type, $element_types)) {
        trigger_error(__('Invalid object kind', 'sitepress'), E_USER_NOTICE);
        return null;
    } elseif (!$element_id) {
        trigger_error(__('Invalid object id', 'sitepress'), E_USER_NOTICE);
        return null;
    }

    if (in_array($element_type, $taxonomies)) {
        $icl_element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='{$element_type}'",
                        $element_id));
    } else {
        $icl_element_id = $element_id;
    }

    if (in_array($element_type, $taxonomies)) {
        $icl_element_type = 'tax_' . $element_type;
    } elseif (in_array($element_type, $post_types)) {
        $icl_element_type = 'post_' . $element_type;
    } else {
        $icl_element_type = $element_type;
    }

    $trid = $sitepress->get_element_trid($icl_element_id, $icl_element_type);
    $translations = $sitepress->get_element_translations($trid,
            $icl_element_type);    
    if (is_null($ulanguage_code)) {
        $ulanguage_code = $sitepress->get_current_language();
    }
    if (isset($translations[$ulanguage_code]->element_id)) {
        $ret_element_id = $translations[$ulanguage_code]->element_id;
        if (in_array($element_type, $taxonomies)) {
            $ret_element_id = $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='{$element_type}'",
                            $ret_element_id));
        }
    } else {
        $ret_element_id = $return_original_if_missing ? $element_id : null;
    }

    $fcache[$fcache_key] = $ret_element_id;

    return $ret_element_id;
}

function icl_get_default_language() {
    global $sitepress;
    return $sitepress->get_default_language();
}

function icl_tf_determine_mo_folder($folder, $rec = 0) {
    global $sitepress;

    $dh = @opendir($folder);
    $lfn = $sitepress->get_locale_file_names();

    while ($file = readdir($dh)) {
        if (0 === strpos($file, '.'))
            continue;
        if (is_file($folder . '/' . $file) && preg_match('#\.mo$#i', $file) && in_array(preg_replace('#\.mo$#i',
                                '', $file), $lfn)) {
            return $folder;
        } elseif (is_dir($folder . '/' . $file) && $rec < 5) {
            if ($f = icl_tf_determine_mo_folder($folder . '/' . $file, $rec + 1)) {
                return $f;
            };
        }
    }

    return false;
}

function wpml_cf_translation_preferences($id, $custom_field = false,
        $class = 'wpml', $ajax = false, $default_value = 'ignore',
        $fieldset = false, $suppress_error = false) {
    $output = '';
    if ($custom_field) {
        $custom_field = @strval($custom_field);
    }
    $class = @strval($class);
    if ($fieldset) {
        $output .= '
<fieldset id="wpml_cf_translation_preferences_fieldset_' . $id
            . '" class="wpml_cf_translation_preferences_fieldset '
            . $class . '-form-fieldset form-fieldset fieldset">'
            . '<legend>' . __('Translation preferences', 'wpml') . '</legend>';
    }
    $actions = array('ignore' => 0, 'copy' => 1, 'translate' => 2);
    $action = isset($actions[@strval($default_value)]) ? $actions[@strval($default_value)] : 0;
    global $iclTranslationManagement;
    if ($custom_field) {
        if (defined('WPML_TM_VERSION') && !empty($iclTranslationManagement)) {
            if (isset($iclTranslationManagement->settings['custom_fields_translation'][$custom_field])) {
                $action = intval($iclTranslationManagement->settings['custom_fields_translation'][$custom_field]);
            }
            $disabled = $xml_override = in_array($custom_field, (array)$iclTranslationManagement->settings['custom_fields_readonly_config']);
            if ($disabled) {
                $output .= '<div style="color:Red;font-style:italic;margin: 10px 0 0 0;">' . __('The translation preference for this field are being controlled by a language configuration XML file. If you want to control it manually, remove the entry from the configuration file.', 'wpml') . '</div>';
            }
        } else if (!$suppress_error) {
            $output .= '<span style="color:#FF0000;">'
                    . __("To synchronize values for translations, you need to enable WPML's Translation Management module.",
                            'wpml')
                    . '</span>';
            $disabled = true;
        }
    } else if (!$suppress_error) {
        $output .= '<span style="color:#FF0000;">'
                    . __('Error: Something is wrong with field value. Translation preferences can not be set.',
                            'wpml')
                    . '</span>';
        $disabled = true;
    }
    $disabled = !empty($disabled) ? ' readonly="readonly" disabled="disabled"' : '';
    $output .= '<div class="description ' . $class . '-form-description '
            . $class . '-form-description-fieldset description-fieldset">'
            . __('Choose what to do when translating content with this field:',
                    'wpml')
            . '</div>
<input';
    $output .= $action == 0 ? ' checked="checked"' : '';
    $output .= ' id="wpml_cf_translation_preferences_option_ignore_'
            . $id . '" name="wpml_cf_translation_preferences['
            . $id . ']" value="0" class="' . $class
            . '-form-radio form-radio radio" type="radio"' . $disabled . '>&nbsp;<label class="'
            . $class . '-form-label ' . $class
            . '-form-radio-label" for="wpml_cf_translation_preferences_option_ignore_'
            . $id . '">' . __('Do nothing', 'wpml') . '</label>
<br />
<input';
    $output .= $action == 1 ? ' checked="checked"' : '';
    $output .= ' id="wpml_cf_translation_preferences_option_copy_'
            . $id . '" name="wpml_cf_translation_preferences['
            . $id . ']" value="1" class="' . $class
            . '-form-radio form-radio radio" type="radio"' . $disabled . '>&nbsp;<label class="'
            . $class . '-form-label ' . $class
            . '-form-radio-label" for="wpml_cf_translation_preferences_option_copy_'
            . $id. '">' . __('Copy from original', 'wpml') . '</label>
<br />
<input';
    $output .= $action == 2 ? ' checked="checked"' : '';
    $output .= ' id="wpml_cf_translation_preferences_option_translate_'
            . $id . '" name="wpml_cf_translation_preferences['
            . $id . ']" value="2" class="' . $class
            . '-form-radio form-radio radio" type="radio"' . $disabled . '>&nbsp;<label class="'
            . $class . '-form-label ' . $class
            . '-form-radio-label" for="wpml_cf_translation_preferences_option_translate_'
            . $id . '">' . __('Translate', 'wpml') . '</label>
<br />';
    if ($custom_field && $ajax) {
        $output .= '
<div style=";margin: 5px 0 5px 0;" id="wpml_cf_translation_preferences_ajax_response_'
            . $id . '"></div>
<input type="button" onclick="icl_cf_translation_preferences_submit(\''
            . $id . '\', jQuery(this));" style="margin-top:5px;" class="button-secondary" value="'
            . __('Apply') . '" name="wpml_cf_translation_preferences_submit_'
            . $id . '" />
<input type="hidden" name="wpml_cf_translation_preferences_data_'
            . $id . '" value="custom_field=' . $custom_field
            . '&amp;_icl_nonce='
            . wp_create_nonce('wpml_cf_translation_preferences_nonce') . '" />';
    }
    if ($fieldset) {
        $output .= '
</fieldset>
';
    }
    return $output;
}

function wpml_cf_translation_preferences_store($id, $custom_field) {
    if (defined('WPML_TM_VERSION')) {
        if (empty($id) || empty($custom_field)
                || !isset($_POST['wpml_cf_translation_preferences'][$id])) {
            return false;
        }
        $custom_field = @strval($custom_field);
        $action = @intval($_POST['wpml_cf_translation_preferences'][$id]);
        global $iclTranslationManagement;
        if (!empty($iclTranslationManagement)) {
            $iclTranslationManagement->settings['custom_fields_translation'][$custom_field] = $action;
            $iclTranslationManagement->save_settings();
            return true;
        } else {
            return false;
        }
    }
    return false;
}

/**
 * wpml_get_copied_fields_for_post_edit
 *
 * return a list of fields that are marked for copying and the
 * original post id that the fields should be copied from
 *
 * This should be used to popupate any custom field controls when
 * a new translation is selected and the field is marked as "copy" (sync)
 */

function wpml_get_copied_fields_for_post_edit() {
    global $sitepress, $wpdb, $sitepress_settings, $pagenow;
    
    $copied_cf = array('fields' => array());
    $translations = null;
    
    if (defined('WPML_TM_VERSION')) {
        
        if(($pagenow == 'post-new.php' || $pagenow == 'post.php')) {
            if (isset($_GET['trid'])){
                $post_type = isset($_GET['post_type'])?$_GET['post_type']:'post';
                
                $translations = $sitepress->get_element_translations($_GET['trid'], 'post_' . $post_type);
            
                $source_lang = isset($_GET['source_lang'])?$_GET['source_lang']:$sitepress->get_default_language();
                $lang_details = $sitepress->get_language_details($source_lang);
            } else if (isset($_GET['post'])) {
                $post_id = @intval($_GET['post']);
                $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID='{$post_id}'");
                $trid = $sitepress->get_element_trid($post_id, 'post_' . $post_type);    
                $original_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE source_language_code IS NULL AND trid=%d", $trid));
                if ($original_id != $post_id) {
                    // Only return information if this is not the source language post.
                    $translations = $sitepress->get_element_translations($trid, 'post_' . $post_type);
                    $source_lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE source_language_code IS NULL AND trid=%d", $trid));
                    $lang_details = $sitepress->get_language_details($source_lang);
                }
            }
            
            if ($translations) {
                $original_custom = get_post_custom($translations[$source_lang]->element_id);
                
                $copied_cf['original_post_id'] = $translations[$source_lang]->element_id;
                $ccf_note = '<img src="' . ICL_PLUGIN_URL . '/res/img/alert.png" alt="Notice" width="16" height="16" style="margin-right:8px" />';
                $copied_cf['copy_message'] = $ccf_note . sprintf(__('WPML will copy this field from %s when you save this post.', 'sitepress'), $lang_details['display_name']);
                
                foreach((array)$sitepress_settings['translation-management']['custom_fields_translation'] as $key=>$sync_opt){
                    if($sync_opt == 1 && isset($original_custom[$key])){
                        $copied_cf['fields'][] = $key;    
                    }
                }
            }
        }
    }
    
    return $copied_cf;
    
}

function wpml_get_language_information($post_id = null){
    global $sitepress;
    
    if(is_null($post_id)){
        $post_id = get_the_ID();
    }
    if(empty($post_id)) return new WP_Error('missing_id', __('Missing post ID', 'sitepress'));
    
    $post = get_post($post_id);
    if(empty($post)) return new WP_Error('missing_post', sprintf(__('No such post for ID = %d', 'sitepress'), $post_id));
    
    
    $language = $sitepress->get_language_for_element($post_id, 'post_' . $post->post_type);
    $language_information = $sitepress->get_language_details($language);
    
    $info = array(
        'locale'                => $sitepress->get_locale($language),
        'text_direction'        => $sitepress->is_rtl($language),
        'display_name'          => $sitepress->get_display_language_name($language, $sitepress->get_current_language()),
        'native_name'           => $language_information['display_name'],
        'different_language'    => $language != $sitepress->get_current_language()    
        
    );
    
    return $info;    
    
}


function wpml_custom_post_translation_options($type_id){
    global $sitepress, $sitepress_settings;
    
    $out = '<table id="wpcf-types-form-visibility-table" class="wpcf-types-form-table widefat"><thead><tr><th>' . __('Translation',
                'sitepress') . '</th></tr></thead><tbody><tr><td>';
    
    $type = get_post_type_object($type_id);
    
    $translated = $sitepress->is_translated_post_type($type_id);
    if(defined('WPML_TM_VERSION')){
        $link = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#icl_custom_posts_sync_options');
        $link2 = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup#icl_slug_translation');
        
    }else{
        $link = admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-options.php#icl_custom_posts_sync_options');    
        $link2 = admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-options.php#icl_slug_translation');    
    }
    
    if($translated){
        
        $out .= sprintf(__('%s is translated via WPML. %sClick here to change translation options.%s', 'sitepress'), 
            '<strong>' . $type->labels->singular_name . '</strong>', '<a href="'.$link.'">', '</a>');

        if($type->rewrite['enabled']){
            
            if($sitepress_settings['posts_slug_translation']['on']){                                         
                if(empty($sitepress_settings['posts_slug_translation']['types'][$type_id])){
                    $out .= '<ul><li>' . __('Slugs are currently not translated.', 'sitepress') . '<li></ul>';
                }else{
                    $out .= '<ul><li>' . __('Slugs are currently translated. Click the link above to edit the translations.', 'sitepress') . '<li></ul>';
                }
            }else{
                $out .= '<ul><li>' . sprintf(__('Slug translation is currently disabled in WPML. %sClick here to enable.%s', 'sitepress'), 
                    '<a href="'.$link2.'">', '</a>') . '</li></ul>';
            }
            
        }
        
        
    }else{
        
        $out .= sprintf(__('%s is not translated. %sClick here to make this post type translatable.%s', 'sitepress'), 
            '<strong>' . $type->labels->singular_name . '</strong>', '<a href="'.$link.'">', '</a>');
        
    }
    
    $out .= '</tbody></table>';
    
    return $out;
    
}