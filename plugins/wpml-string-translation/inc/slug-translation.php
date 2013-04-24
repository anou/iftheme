<?php

class WPML_Slug_Translation{
    
    
    static function setup(){
        global $sitepress_settings;
        if(!empty($sitepress_settings['posts_slug_translation']['on'])){
            add_filter('gettext_with_context', array('WPML_Slug_Translation', 'filters_gettext_with_context'), 0, 4); // high priority
        }
    }
    
    static function init(){
        global $sitepress_settings;
        if(!empty($sitepress_settings['posts_slug_translation']['on'])){            
            add_filter('option_rewrite_rules', array('WPML_Slug_Translation', 'rewrite_rules_filter'), 1, 1); // high priority
            add_filter('post_type_link', array('WPML_Slug_Translation', 'post_type_link_filter'), 1, 4); // high priority
            add_action('_icl_before_archive_url', array('WPML_Slug_Translation', '_icl_before_archive_url'), 1, 2);
            add_action('_icl_after_archive_url', array('WPML_Slug_Translation', '_icl_after_archive_url'), 1, 2);
        }
        
        add_action('icl_ajx_custom_call', array('WPML_Slug_Translation', 'gui_save_options'), 10, 2);
        
    }
    
    static function filters_gettext_with_context($translation, $text, $_gettext_context, $domain){
        global $sitepress;
        if($_gettext_context == 'URL slug'){
            global $wpdb;
            $string_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE name=%s AND value = %s", 'URL slug: ' . $text, $text));
            if(empty($string_id)){            
                icl_register_string('URL slugs - ' . $domain, 'URL slug: ' . $text, $text, false);
            }else{
                $tr = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d AND language = %s", $string_id, $sitepress->get_current_language()));
                if(!empty($tr)){
                    $translation = $tr;
                }
            }
        }
        return $translation;
    }
    
    static function rewrite_rules_filter($value){
        global $sitepress, $sitepress_settings, $wpdb;
        
        $strings_language = $sitepress_settings['st']['strings_language'];
        
        if($sitepress->get_current_language() != $sitepress->get_default_language()){
            $buff_value = array();
            
            $queryable_post_types = get_post_types( array('publicly_queryable' => true) );
            
            foreach((array)$value as $k=>$v){            
                
                foreach($queryable_post_types as $type){
                    
                    if(!$sitepress->is_translated_post_type($type)) continue;
                    
                    // see if this slug string is registered. case of when it's not wrapped in a gettext call
                    //$string_id = 
                    
                    $post_type_obj = get_post_type_object($type);
                    $slug_translation = isset($post_type_obj->rewrite['slug']) ? $post_type_obj->rewrite['slug'] : false;
                    
                    // get slug in default language
                    if($strings_language == $sitepress->get_default_language()){
                        $slug = $wpdb->get_var("
                                    SELECT s.value 
                                    FROM {$wpdb->prefix}icl_strings s    
                                    JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                                    WHERE t.value='". $wpdb->escape($slug_translation)."' 
                                        AND t.language = '" . $wpdb->escape($sitepress->get_current_language()) . "' 
                                        AND s.name LIKE 'URL slug:%' 
                                        AND s.language = '" . $wpdb->escape($strings_language) . "'
                        ");
                    }else{
                        if($strings_language == $sitepress->get_current_language()){
                            $slug = $wpdb->get_var($wpdb->prepare("
                                        SELECT t.value 
                                        FROM {$wpdb->prefix}icl_string_translations t
                                            JOIN {$wpdb->prefix}icl_strings s ON t.string_id = s.id
                                        WHERE t.language = %s AND s.name LIKE %s AND s.value = %s
                                    ", $sitepress->get_default_language(), 'URL slug: %', $slug_translation));
                        }else{
                            
                            $slug_base_id = $wpdb->get_var($wpdb->prepare("
                                SELECT s.id FROM {$wpdb->prefix}icl_strings s
                                    JOIN {$wpdb->prefix}icl_string_translations st
                                    ON st.string_id = s.id
                                    WHERE st.language=%s AND st.value=%s AND s.name LIKE %s                        
                            ", $sitepress->get_current_language(), $slug_translation, 'URL slug: %'));
                            
                            $slug = $wpdb->get_var($wpdb->prepare("
                                        SELECT value
                                        FROM {$wpdb->prefix}icl_string_translations 
                                        WHERE string_id = %d
                                            AND language = %s 
                                        ", $slug_base_id, $sitepress->get_default_language()));
                            
                        }

                    }
                    
                    if($slug && $slug != $slug_translation){                        
                        if(preg_match('#^[^/]*/?' . $slug . '/#', $k) && $slug != $slug_translation){
                            $k = preg_replace('#^([^/]*)(/?)' . $slug . '/#',  '$1$2' . $slug_translation . '/' , $k);    
                        }
                    }
                    
                }
                
                $buff_value[$k] = $v;
            }
            $value = $buff_value;
            unset($buff_value);
        }            
        
        return $value;
    }
    
    static function get_translated_slug($slug, $language){
        global $wpdb, $sitepress_settings, $sitepress;
        
        static $cache = array();
        
        if(empty($cache[$slug][$language.$sitepress->get_current_language()])){
        
            if($sitepress_settings['st']['strings_language'] != $sitepress->get_current_language()){
                $slug = $wpdb->get_var("
                            SELECT s.value 
                            FROM {$wpdb->prefix}icl_strings s    
                            JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                            WHERE t.value='". $wpdb->escape($slug)."' 
                                AND t.language = '" . $wpdb->escape($sitepress->get_current_language()) . "' 
                                AND s.name LIKE 'URL slug:%' 
                                AND s.language = '" . $wpdb->escape($sitepress_settings['st']['strings_language']) . "'
                ");
            }

            if($language != $sitepress_settings['st']['strings_language']){
                $slug = $wpdb->get_var("
                                    SELECT t.value 
                                    FROM {$wpdb->prefix}icl_strings s    
                                    JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                                    WHERE s.value='". $wpdb->escape($slug)."' 
                                        AND t.language = '" . $wpdb->escape($language) . "' 
                                        AND s.name LIKE 'URL slug:%' 
                                        AND s.language = '" . $wpdb->escape($sitepress_settings['st']['strings_language']) . "'
                ");
            }
            
            $cache[$slug][$language.$sitepress->get_current_language()] = $slug;
            
        }
        
        return $slug;
        
    }
    
    static function _icl_before_archive_url($post_type, $language){
        global $sitepress_settings, $sitepress;
        if(!empty($sitepress_settings['posts_slug_translation']['types'][$post_type])){
            global $wp_post_types;
            if($translated_slug = self::get_translated_slug($wp_post_types[$post_type]->rewrite['slug'], $language)){
                $wp_post_types[$post_type]->rewrite['slug_original'] = $wp_post_types[$post_type]->rewrite['slug'];
                $wp_post_types[$post_type]->rewrite['slug'] = $translated_slug;    
            }
        }
    }

    static function _icl_after_archive_url($post_type, $language){
        // restore default lang slug
        global $sitepress_settings, $sitepress;
        if(!empty($sitepress_settings['posts_slug_translation']['types'][$post_type])){
            global $wp_post_types;
            if(!empty($wp_post_types[$post_type]->rewrite['slug_original'])){
                $wp_post_types[$post_type]->rewrite['slug'] = $wp_post_types[$post_type]->rewrite['slug_original'];    
                unset($wp_post_types[$post_type]->rewrite['slug_original']);
            }
        }
    }
    
    static function post_type_link_filter($post_link, $post, $leavename, $sample){
        global $wpdb, $sitepress, $sitepress_settings;
        
        static $no_recursion_flag;
        
        if(!empty($no_recursion_flag)) return $post_link;
        
        if(!$sitepress->is_translated_post_type($post->post_type)){
            return $post_link;
        } 
        
        // get element language
        $ld = $sitepress->get_element_language_details($post->ID, 'post_' . $post->post_type);
        
        if(empty($ld)){
            return $post_link;
        } 

        static $cache;        
        
        if(!isset($cache[$post->ID])){
            
            $strings_language = $sitepress_settings['st']['strings_language'];
            
            
            // fix permalink when object is not in the current language
            if($ld->language_code != $sitepress->get_current_language()){

                $post_type = get_post_type_object($post->post_type);
                $slug_this = $post_type->rewrite['slug'];
                
                if($ld->language_code == $strings_language){
                    
                    $slug_real = $wpdb->get_var("
                                SELECT s.value 
                                FROM {$wpdb->prefix}icl_strings s    
                                JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                                WHERE t.value='". $wpdb->escape($slug_this)."' 
                                    AND t.language = '" . $wpdb->escape($sitepress->get_current_language()) . "' 
                                    AND s.name LIKE 'URL slug:%' 
                                    AND s.language = '" . $wpdb->escape($ld->language_code) . "'
                    ");
                    
                }else{
                    
                    if($sitepress->get_current_language() == $strings_language){
                        
                        $slug_real = $wpdb->get_var("
                                    SELECT t.value 
                                    FROM {$wpdb->prefix}icl_strings s    
                                    JOIN {$wpdb->prefix}icl_string_translations t ON t.string_id = s.id
                                    WHERE s.value='". $wpdb->escape($slug_this)."' 
                                        AND t.language = '" . $wpdb->escape($ld->language_code) . "' 
                                        AND s.name LIKE 'URL slug:%' 
                                        AND s.language = '" . $wpdb->escape($sitepress->get_current_language()) . "'
                        ");
                        
                    }else{
                        $slug_base_id = $wpdb->get_var($wpdb->prepare("
                            SELECT s.id FROM {$wpdb->prefix}icl_strings s
                                JOIN {$wpdb->prefix}icl_string_translations st
                                ON st.string_id = s.id
                                WHERE st.language=%s AND st.value=%s AND s.name LIKE %s                        
                        ", $sitepress->get_current_language(), $slug_this, 'URL slug: %'));
                        
                        $slug_real = $wpdb->get_var($wpdb->prepare("
                                    SELECT value
                                    FROM {$wpdb->prefix}icl_string_translations 
                                    WHERE string_id = %d
                                        AND language = %s 
                                    ", $slug_base_id, $ld->language_code));
                    }
                    
                }
                
                global $wp_rewrite;
                                
                $struct_original = $wp_rewrite->extra_permastructs[$post->post_type]['struct'];
                
                $lslash = false !== strpos($struct_original, '/' . $slug_this) ? '/' : '';
                //$wp_rewrite->extra_permastructs[$post->post_type]['struct'] = str_replace('/' . $slug_this, '/' . $slug_real, $struct_original);
                $wp_rewrite->extra_permastructs[$post->post_type]['struct'] = preg_replace('@'. $lslash . $slug_this . '/@', $lslash.$slug_real.'/' , $struct_original);
                $no_recursion_flag = true;
                $post_link = get_post_permalink($post->ID);
                $no_recursion_flag = false;
                $wp_rewrite->extra_permastructs[$post->post_type]['struct'] = $struct_original;
                
                $cache[$post->ID] = $post_link;
            }
            
        }else{
            
            $post_link = $cache[$post->ID];
            
        }
                
        return $post_link;        
    }    
    
    static function gui_save_options($action , $data){
        
        switch($action){        
            case 'icl_slug_translation':        
                global $sitepress;
                $iclsettings['posts_slug_translation']['on'] = intval(!empty($_POST['icl_slug_translation_on']));
                $sitepress->save_settings($iclsettings);
                echo '1|' . $iclsettings['posts_slug_translation']['on'];
                break;
        }
        
    }
    
}

  
  
  
?>
