<?php

$icl_st_string_translation_statuses = array(
    ICL_STRING_TRANSLATION_COMPLETE => __('Translation complete','wpml-string-translation'),
    ICL_STRING_TRANSLATION_PARTIAL => __('Partial translation','wpml-string-translation'),
    ICL_STRING_TRANSLATION_NEEDS_UPDATE => __('Translation needs update','wpml-string-translation'),
    ICL_STRING_TRANSLATION_NOT_TRANSLATED => __('Not translated','wpml-string-translation'),
    ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR => __('Waiting for translator','wpml-string-translation')
);

add_action('plugins_loaded', 'icl_st_init');

if(!defined('XMLRPC')){
    // called separately for XMLRPC post saves
    add_action('save_post', 'icl_st_fix_links_in_strings', 19);
}

//add_filter('xmlrpc_methods','icl_add_custom_xmlrpc_methods');

function icl_st_init(){                       
    global $sitepress_settings, $sitepress, $wpdb, $icl_st_err_str;

    if ( $GLOBALS['pagenow'] == 'site-new.php' && isset($_REQUEST['action']) && 'add-site' == $_REQUEST['action'] ) return;
    
    add_action('icl_update_active_languages', 'icl_update_string_status_all');
    add_action('update_option_blogname', 'icl_st_update_blogname_actions',5,2);
    add_action('update_option_blogdescription', 'icl_st_update_blogdescription_actions',5,2);               
    
    if(isset($_GET['icl_action']) && $_GET['icl_action'] == 'view_string_in_page'){
        icl_st_string_in_page($_GET['string_id']);
        exit;
    }

    if(isset($_GET['icl_action']) && $_GET['icl_action'] == 'view_string_in_source'){
        icl_st_string_in_source($_GET['string_id']);
        exit;
    }
    
    if ( get_magic_quotes_gpc() && isset($_GET['page']) && $_GET['page'] == WPML_ST_FOLDER . '/menu/string-translation.php'){
        $_POST = stripslashes_deep( $_POST );         
    }
              
    if(!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']){
        return;
    }          
    
    if(!isset($sitepress_settings['st']['sw'])){
        $sitepress_settings['st']['sw'] = array();  //no settings for now
        $sitepress->save_settings($sitepress_settings); 
        $init_all = true;
    }
    
    if(!isset($sitepress_settings['st']['strings_per_page'])){
        $sitepress_settings['st']['strings_per_page'] = 10;
        $sitepress->save_settings($sitepress_settings); 
    }elseif(isset($_GET['strings_per_page']) && $_GET['strings_per_page'] > 0){
        $sitepress_settings['st']['strings_per_page'] = $_GET['strings_per_page'];
        $sitepress->save_settings($sitepress_settings); 
    }
    if(!isset($sitepress_settings['st']['icl_st_auto_reg'])){
        $sitepress_settings['st']['icl_st_auto_reg'] = 'disabled';
        $sitepress->save_settings($sitepress_settings); 
    }
    if(empty($sitepress_settings['st']['strings_language'])){
        $iclsettings['st']['strings_language'] = $sitepress_settings['st']['strings_language'] = $sitepress->get_default_language();
        $sitepress->save_settings($iclsettings);
    }
    
    if(!isset($sitepress_settings['st']['translated-users'])) $sitepress_settings['st']['translated-users'] = array();
    
    if((isset($_POST['iclt_st_sw_save']) && wp_verify_nonce($_POST['_wpnonce'], 'icl_sw_form')) || isset($init_all)){
            if(isset($init_all)){
                
                icl_register_string('WP',__('Blog Title','wpml-string-translation'), get_option('blogname'));
                icl_register_string('WP',__('Tagline', 'wpml-string-translation'), get_option('blogdescription'));

                __icl_st_init_register_widget_titles();
                
                // create a list of active widgets
                $active_text_widgets = array();
                $widgets = (array)get_option('sidebars_widgets');
                foreach($widgets as $k=>$w){             
                    if('wp_inactive_widgets' != $k && $k != 'array_version'){
                        if(is_array($widgets[$k])){
                            foreach($widgets[$k] as $v){
                                if(preg_match('#text-([0-9]+)#i',$v, $matches)){
                                    $active_text_widgets[] = $matches[1];
                                }                            
                            }
                        }
                    }
                }
                                                                
                $widget_text = get_option('widget_text');
                if(is_array($widget_text)){
                    foreach($widget_text as $k=>$w){
                        if(!empty($w) && isset($w['title']) && in_array($k, $active_text_widgets)){
                            icl_register_string('Widgets', 'widget body - ' . md5(apply_filters('widget_text',$w['text'])), apply_filters('widget_text',$w['text']));
                        }
                    }
                }
            }  
                    
            if(isset($_POST['iclt_st_sw_save'])){
                $updat_string_statuses = false;
                $sitepress_settings['st']['sw'] = $_POST['icl_st_sw'];
                if($sitepress_settings['st']['strings_language'] != $_POST['icl_st_sw']['strings_language']){
                    $updat_string_statuses = true;
                }
                $sitepress_settings['st']['strings_language'] = $_POST['icl_st_sw']['strings_language'];
                $sitepress->save_settings($sitepress_settings); 
                if($updat_string_statuses){
                    icl_update_string_status_all();
                }
                
                
                //register author strings
                
                if(!empty($sitepress_settings['st']['translated-users'])){
                    icl_st_register_user_strings_all();
                }
                
                wp_redirect($_SERVER['REQUEST_URI'].'&updated=true');
            }
            
    }
    
    // handle po file upload
    if(isset($_POST['icl_po_upload']) && wp_verify_nonce($_POST['_wpnonce'], 'icl_po_form')){                
        global $icl_st_po_strings;
        if($_FILES['icl_po_file']['size']==0){
            $icl_st_err_str = __('File upload error', 'wpml-string-translation');
        }else{
            $lines = file($_FILES['icl_po_file']['tmp_name']);
            $icl_st_po_strings = array();
            
            $fuzzy = 0;    
            for($k = 0; $k < count($lines); $k++){
                if(0 === strpos($lines[$k], '#, fuzzy')){
                    $fuzzy = 1;
                    $k++;
                }                                                        
                $int = preg_match('#msgid "(.+)"#im',trim($lines[$k]), $matches);
                if($int){
                    $string = str_replace('\"','"', $matches[1]);
                    $int = preg_match('#msgstr "(.+)"#im',trim($lines[$k+1]),$matches);
                    if($int){
                        $translation = str_replace('\"','"',$matches[1]);
                    }else{
                        $translation = "";
                    }
                    
                    $string_exists = $wpdb->get_var("
                        SELECT id FROM {$wpdb->prefix}icl_strings 
                        WHERE context='".$wpdb->escape($_POST['icl_st_i_context_new']?$_POST['icl_st_i_context_new']:$_POST['icl_st_i_context'])."' 
                        AND name='".md5($string)."'");
                    
                    $icl_st_po_strings[] = array(     
                        'string' => $string,
                        'translation' => $translation,
                        'fuzzy' => $fuzzy,
                        'exists' => $string_exists
                    );
                    $k++;                        
                    
                }
                if(!trim($lines[$k])){
                    $fuzzy = 0;    
                }
            }            
            if(empty($icl_st_po_strings)){
                $icl_st_err_str = __('No string found', 'wpml-string-translation');
            }
        }
    }
    elseif(isset($_POST['action']) && 'icl_st_save_strings' == $_POST['action']){
        $arr = array_intersect_key($_POST['icl_strings'], array_flip($_POST['icl_strings_selected']));
        //$arr = array_map('html_entity_decode', $arr);         
        if(isset($_POST['icl_st_po_language'])){
            $arr_t = array_intersect_key($_POST['icl_translations'], array_flip($_POST['icl_strings_selected']));
            $arr_f = array_intersect_key($_POST['icl_fuzzy'], array_flip($_POST['icl_strings_selected']));
            //$arr_t = array_map('html_entity_decode', $arr_t);         
        }   
        
        
        // see if the strings are already registered and have names
        // case of adding translation
        $res = $wpdb->get_results("                                                                                              
            SELECT value, name 
            FROM {$wpdb->prefix}icl_strings 
            WHERE context = '". $wpdb->escape($_POST['icl_st_domain_name']) ."' AND value IN ('".join("','", array_map('mysql_real_escape_string', $arr))."')  
        ");
        if(!empty($res)){
            foreach($res as $r){
                $map[$r->value] = $r->name;
            }
        }
         
        foreach($arr as $k=>$string){
            if(isset($map[$string])){
                $name = $map[$string];
            }else{
                $name = md5($string);
            }
            $string_id = icl_register_string($_POST['icl_st_domain_name'], $name, $string);
            if($string_id && isset($_POST['icl_st_po_language'])){
                if($arr_t[$k] != ""){
                    if($arr_f[$k]){
                        $_status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
                    }else{
                        $_status = ICL_STRING_TRANSLATION_COMPLETE;
                    }
                    icl_add_string_translation($string_id, $_POST['icl_st_po_language'], $arr_t[$k], $_status);
                    icl_update_string_status($string_id);
                }                
            }            
        }        
    }
    
    //handle po export
    if(isset($_POST['icl_st_pie_e']) && wp_verify_nonce($_POST['_wpnonce'], 'icl_po_export')){
        //force some filters
        if(isset($_GET['status'])) unset($_GET['status']);
        $_GET['show_results']='all';
        if($_POST['icl_st_e_context']){
            $_GET['context'] = $_POST['icl_st_e_context'];
        }
                                                    
        $_GET['translation_language'] = $_POST['icl_st_e_language'];
        $strings = icl_get_string_translations();
        if(!empty($strings)){
            $po = icl_st_generate_po_file($strings, !isset($_POST['icl_st_pe_translations']));
        }else{
            $po = "";  
        }
        if(!isset($_POST['icl_st_pe_translations'])){
            $popot = 'pot';
            $poname = $_POST['icl_st_e_context'] ? urlencode($_POST['icl_st_e_context']) : 'all_context'; 
        }else{
            $popot = 'po';
            $poname = $_GET['translation_language'];
        }
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$poname.'.'.$popot.";");
        header("Content-Length: ". strlen($po));
        echo $po;
        exit(0);
    }
    
    // handle string translation request
    elseif(isset($_POST['icl_st_action']) && $_POST['icl_st_action'] == 'send_strings'){        
        
        if($_POST['iclnonce'] == wp_create_nonce('icl-string-translation')){
            $_POST = stripslashes_deep($_POST);
            $services = $_POST['service'];
            $string_ids = explode(',', $_POST['strings']);
            $translate_to = array();
            foreach($_POST['translate_to'] as $lang_to=>$one){
                if(isset($services[$lang_to])){
                    $translate_to[$lang_to] = $services[$lang_to];
                }
            }
            if(!empty($translate_to)){
                icl_translation_send_strings($string_ids, $translate_to);
            }
        }        
    }
    
    
    // hook into blog title and tag line    
    add_filter('option_blogname', 'icl_sw_filters_blogname');
    add_filter('option_blogdescription', 'icl_sw_filters_blogdescription');        
    add_filter('widget_title', 'icl_sw_filters_widget_title');
    add_filter('widget_text', 'icl_sw_filters_widget_text');
                         
    if(isset($sitepress_settings['theme_localization_type']) && $sitepress_settings['theme_localization_type']==1){
        add_filter('gettext', 'icl_sw_filters_gettext', 9, 3);
        add_filter('gettext_with_context', 'icl_sw_filters_gettext_with_context', 9, 4);
        add_filter('ngettext', 'icl_sw_filters_ngettext', 9, 5);
        add_filter('ngettext_with_context', 'icl_sw_filters_nxgettext', 9, 6);
    }
    
    $widget_groups = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget\\_%'");
    foreach($widget_groups as $w){
        add_action('update_option_' . $w->option_name, 'icl_st_update_widget_title_actions', 5, 2);
    }
    
    add_action('update_option_widget_text', 'icl_st_update_text_widgets_actions', 5, 2);
    add_action('update_option_sidebars_widgets', '__icl_st_init_register_widget_titles');
    
    if($icl_st_err_str){
        add_action('admin_notices', 'icl_st_admin_notices');
    }
    
    add_filter('get_the_author_first_name', 'icl_st_author_first_name_filter', 10, 2);
    add_filter('get_the_author_last_name', 'icl_st_author_last_name_filter', 10, 2);
    add_filter('get_the_author_nickname', 'icl_st_author_nickname_filter', 10, 2);
    add_filter('get_the_author_description', 'icl_st_author_description_filter', 10, 2);
    add_filter('the_author', 'icl_st_author_displayname_filter', 10);
    
}


add_action('profile_update', 'icl_st_register_user_strings');
add_action('user_register', 'icl_st_register_user_strings');

function __icl_st_init_register_widget_titles(){
    global $wpdb;        
    
    // create a list of active widgets
    $active_widgets = array();
    $widgets = (array)get_option('sidebars_widgets');    
    
    foreach($widgets as $k=>$w){                     
        if('wp_inactive_widgets' != $k && $k != 'array_version'){
            if(is_array($widgets[$k]))
            foreach($widgets[$k] as $v){                
                $active_widgets[] = $v;
            }
        }
    }                      
    foreach($active_widgets as $aw){        
        $int = preg_match('#-([0-9]+)$#i',$aw, $matches);
        if($int){
            $suffix = $matches[1];
        }else{
            $suffix = 1;
        }
        $name = preg_replace('#-[0-9]+#','',$aw);                
        //if($name == 'rss-links') $name = 'rss';
        
        //$w = $wpdb->get_row("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name = 'widget_{$name}'");
        //$value = unserialize($w->option_value);
        $value = get_option("widget_".$name);
        if(isset($value[$suffix]['title']) && $value[$suffix]['title']){
            $w_title = $value[$suffix]['title'];     
        }else{
            $w_title = __icl_get_default_widget_title($aw);
            $value[$suffix]['title'] = $w_title;
            update_option("widget_".$name, $value);
        }
        
        if($w_title){            
            icl_register_string('Widgets', 'widget title - ' . md5(apply_filters('widget_title',$w_title)), apply_filters('widget_title',$w_title));                                    
            
        }
    }    
}

function __icl_get_default_widget_title($id){
    if(preg_match('#archives(-[0-9]+)?$#i',$id)){                        
        $w_title = 'Archives';
    }elseif(preg_match('#categories(-[0-9]+)?$#i',$id)){
        $w_title = 'Categories';
    }elseif(preg_match('#calendar(-[0-9]+)?$#i',$id)){
        $w_title = 'Calendar';
    }elseif(preg_match('#links(-[0-9]+)?$#i',$id)){
        $w_title = 'Links';
    }elseif(preg_match('#meta(-[0-9]+)?$#i',$id)){
        $w_title = 'Meta';
    }elseif(preg_match('#pages(-[0-9]+)?$#i',$id)){
        $w_title = 'Pages';
    }elseif(preg_match('#recent-posts(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Posts';
    }elseif(preg_match('#recent-comments(-[0-9]+)?$#i',$id)){
        $w_title = 'Recent Comments';
    }elseif(preg_match('#rss-links(-[0-9]+)?$#i',$id)){
        $w_title = 'RSS';
    }elseif(preg_match('#search(-[0-9]+)?$#i',$id)){
        $w_title = 'Search';
    }elseif(preg_match('#tag-cloud(-[0-9]+)?$#i',$id)){
        $w_title = 'Tag Cloud';
    }else{
        $w_title = false;
    }  
    return $w_title;  
}

function icl_register_string($context, $name, $value, $allow_empty_value = false){    
    global $wpdb, $sitepress, $sitepress_settings, $ICL_Pro_Translation;
    // if the default language is not set up return without doing anything
    if( 
        !isset($sitepress_settings['existing_content_language_verified']) || 
        !$sitepress_settings['existing_content_language_verified']
    ){
        return;
    }       
    
    // Check if cached (so exists)    
    $cached = icl_t_cache_lookup($context, $name);
    if ($cached && isset($cached['original']) && $cached['original'] == $value) {
        return;
    }
    
    $language = $sitepress->get_default_language();
    $res = $wpdb->get_row("SELECT id, value, status, language FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");
    if($res){
        $string_id = $res->id;
        $update_string = array();
        if($value != $res->value){
            $update_string['value'] = $value;
        }
        if($language != $res->language){
            $update_string['language'] = $language;
        }
        if(!empty($update_string)){
            $wpdb->update($wpdb->prefix.'icl_strings', $update_string, array('id'=>$string_id));
            $wpdb->update($wpdb->prefix.'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string_id));
            icl_update_string_status($string_id);
        }        
    }else{
        if(!empty($value) && is_scalar($value) && trim($value) || $allow_empty_value){
            $string = array(
                'language' => $language,
                'context' => $context,
                'name' => $name,
                'value' => $value,
                'status' => ICL_STRING_TRANSLATION_NOT_TRANSLATED,
            );
            $wpdb->insert($wpdb->prefix.'icl_strings', $string);
            $string_id = $wpdb->insert_id;
        }else{
            $string_id = 0;
        }
    } 
    global $WPML_Sticky_Links;
    if(!empty($WPML_Sticky_Links) && $WPML_Sticky_Links->settings['sticky_links_strings']){        
        require_once ICL_PLUGIN_PATH . '/inc/translation-management/pro-translation.class.php';
        ICL_Pro_Translation::_content_make_links_sticky($string_id, 'string', false);   
    }
    return $string_id; 
}

function icl_translate($context, $name, $original_value = false, $allow_empty_value = false, &$has_translation = null) {
    static $cache = null;
    if (isset($cache[$context][$name])) {
       return $cache[$context][$name];
    }
    icl_register_string($context, $name, $original_value, $allow_empty_value);
    $cache[$context][$name] = icl_t($context, $name, $original_value, $has_translation);
    return $cache[$context][$name];
}

function icl_st_is_registered_string($context, $name){
    global $wpdb;
    static $cache = array();
    if(isset($cache[$context][$name])){
        $string_id = $cache[$context][$name];
    }else{
        $string_id = $wpdb->get_var("
            SELECT id 
            FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");
        $cache[$context][$name] = $string_id;
    }
    return $string_id;
}

function icl_st_string_has_translations($context, $name){
    global $wpdb;
    $sql = "
        SELECT COUNT(st.id) 
        FROM {$wpdb->prefix}icl_string_translations st 
        JOIN {$wpdb->prefix}icl_strings s ON s.id=st.string_id
        WHERE s.name='".$wpdb->escape($name)."' AND s.context='".$wpdb->escape($context)."'
    ";
    return $wpdb->get_var($sql);
}

function icl_rename_string($context, $old_name, $new_name){
    global $wpdb;
    $wpdb->update($wpdb->prefix.'icl_strings', array('name'=>$new_name), array('context'=>$context, 'name'=>$old_name));
}

function icl_update_string_status($string_id){
    global $wpdb, $sitepress, $sitepress_settings;    
    $st = $wpdb->get_results($wpdb->prepare("SELECT language, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d", $string_id));    
    
    if($st){                
        foreach($st as $t){
            if($sitepress_settings['st']['strings_language'] != $t->language){
                $translations[$t->language] = $t->status;
            }
        }  
        
        $active_languages = $sitepress->get_active_languages();
        
        if(empty($translations) || max($translations) == ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        }elseif( in_array(ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR,$translations) ){            
            $status = ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR;
        }elseif(count($translations) < count($active_languages) - intval(in_array($sitepress_settings['st']['strings_language'], array_keys($active_languages)))){
            if(in_array(ICL_STRING_TRANSLATION_NEEDS_UPDATE,$translations)){
                $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
            }elseif(in_array(ICL_STRING_TRANSLATION_COMPLETE,$translations)){
                $status = ICL_STRING_TRANSLATION_PARTIAL;            
            }else{
                $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
            }            
        }elseif(ICL_STRING_TRANSLATION_NEEDS_UPDATE == array_unique($translations)){            
            $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
        }else{
            if(in_array(ICL_STRING_TRANSLATION_NEEDS_UPDATE,$translations)){
                $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
            }elseif(in_array(ICL_STRING_TRANSLATION_NOT_TRANSLATED,$translations)){
                $status = ICL_STRING_TRANSLATION_PARTIAL;            
            }else{
                $status = ICL_STRING_TRANSLATION_COMPLETE;            
            }
        }
    }else{
        $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;        
    }    
    $wpdb->update($wpdb->prefix.'icl_strings', array('status'=>$status), array('id'=>$string_id));
    return $status;    
}

function icl_update_string_status_all(){
    global $wpdb;
    $res = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}icl_strings");
    foreach($res as $id){
        icl_update_string_status($id);
    }
}

function icl_unregister_string($context, $name){
    global $wpdb; 
    $string_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");       
    if($string_id){
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_strings WHERE id=" . $string_id);
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_translations WHERE string_id=" . $string_id);
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id=" . $string_id);
    }
    do_action('icl_st_unregister_string', $string_id);
}  

function __icl_unregister_string_multi($arr){
    global $wpdb; 
    $str = join(',', array_map('intval', $arr));
    $wpdb->query("
        DELETE s.*, t.* FROM {$wpdb->prefix}icl_strings s LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
        WHERE s.id IN ({$str})");
    $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN ({$str})");
    do_action('icl_st_unregister_string_multi', $arr);
}  

function icl_t($context, $name, $original_value=false, &$has_translation=null, $dont_auto_register = false){
    global $wpdb, $sitepress, $sitepress_settings;
        
    // if the default language is not set up return
    if(!isset($sitepress_settings['existing_content_language_verified'])){        
        if(isset($has_translation)) $has_translation = false;
        return $original_value !== false ? $original_value : $name;
    }   
       
    if(defined('DOING_AJAX')){            
         $current_language = $sitepress->get_language_cookie();
    }elseif(is_admin()){            
        $current_language = $sitepress->get_admin_language();                 
    }else{
        $current_language = $sitepress->get_current_language();     
    }
    $default_language = !empty($sitepress_settings['st']['strings_language']) ? $sitepress_settings['st']['strings_language'] : $sitepress->get_default_language();
    
    if($current_language == $default_language && $original_value){
        
        $ret_val = $original_value;
        if(isset($has_translation)) $has_translation = false;
        
    }else{
        $result = icl_t_cache_lookup($context, $name);
        
        $is_string_change = 
            $result !== false && (
                $result['translated'] && $result['original'] != $original_value ||
                !$result['translated'] && $result['value'] != $original_value
            );
        
        if (($result === false || $is_string_change) && !is_admin() && !$dont_auto_register && $context != 'Widgets') {
            // See if we should auto register the strings.
            if (isset($sitepress_settings['st']['icl_st_auto_reg'])) {
                $auto_reg = $sitepress_settings['st']['icl_st_auto_reg'];
            } else {
                $auto_reg = 'disabled';
            }
            
            switch ($auto_reg) {
                case 'auto-admin':                    
                    if (current_user_can('manage_options')) {
                        icl_register_string($context, $name, $original_value);
                    }
                    break;
                
                case 'auto-always':
                    icl_register_string($context, $name, $original_value);
                    break;
            }
        }
        if($result === false || is_array($result) && !$result['translated'] && $original_value){        
            $ret_val = $original_value;    
            if(isset($has_translation)) $has_translation = false;
        }else{
            $ret_val = $result['value'];    
            if(isset($has_translation)) $has_translation = true;
        }
        
    }
    return $ret_val;
}

function icl_add_string_translation($string_id, $language, $value = null, $status = false, $translator_id = null){
    global $wpdb;
    
    $res = $wpdb->get_row("SELECT id, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id='".$wpdb->escape($string_id)."' AND language='".$wpdb->escape($language)."'");
    
    // the same string should not be sent two times to translation
    if(isset($res->status) && $res->status == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR && is_null($value)){
        return false;        
    }
    
    if($res){
        $st_id = $res->id;
        $st_update = array();
        if(!is_null($value) && $value != $res->value){  // null $value is for sending to translation. don't override existing.
            $st_update['value'] = $value;
        }
        if($status){
            $st_update['status'] = $status;
        }elseif($status === ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $st_update['status'] = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        } 
        
        if(!empty($st_update)){
            if(!is_null($translator_id)){
                $st_update['translator_id'] = get_current_user_id();
            }
            $st_update['translation_date'] = current_time("mysql");
            $wpdb->update($wpdb->prefix.'icl_string_translations', $st_update, array('id'=>$st_id));
        }        
    }else{
        if(!$status){
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        }
        $st = array(
            'string_id' => $string_id,
            'language'  => $language,
            'status'    => $status
        );
        if(!is_null($value)){
            $st['value'] = $value;
        }
        if(!is_null($translator_id)){
            $st_update['translator_id'] = get_current_user_id();
        }        
        $wpdb->insert($wpdb->prefix.'icl_string_translations', $st);
        $st_id = $wpdb->insert_id;
    }    

    $GLOBALS['ICL_Pro_Translation']->_content_fix_links_to_translated_content($st_id, $language, 'string');    
                                         
    icl_update_string_status($string_id);
    
    do_action('icl_st_add_string_translation', $st_id);
    
    return $st_id;
}

function icl_get_string_id($string, $context){
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE value=%s AND context=%s", $string, $context));    
    return $id;
}

function icl_get_string_translations($offset=0){
    global $wpdb, $sitepress, $sitepress_settings, $wp_query, $icl_st_string_translation_statuses; 
    $string_translations = array();
    
    $extra_cond = "";
    if(icl_st_is_translator() && isset($_GET['status']) && preg_match("#".ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR."-(.+)#", $_GET['status'], $matches)){
        $status_filter = ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR;
        $status_filter_lang = $matches[1];
        $lcode_alias = str_replace('-', '', $status_filter_lang);
        $extra_cond .= " AND str_{$lcode_alias}.language = '{$status_filter_lang}' ";        
    }else{
        $status_filter = isset($_GET['status']) ? intval($_GET['status']) : false;    
    }
    
    $search_filter = isset($_GET['search']) ? $_GET['search'] : false;
    $exact_match   = isset($_GET['em']) ? $_GET['em'] == 1 : false;
    
    if($status_filter !== false){
        if($status_filter == ICL_STRING_TRANSLATION_COMPLETE){
            $extra_cond .= " AND s.status = " . ICL_STRING_TRANSLATION_COMPLETE;
        }elseif($status_filter == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR){
            ; // do nothing
        }else{
            $extra_cond .= " AND status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "," . ICL_STRING_TRANSLATION_NOT_TRANSLATED . "," . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . ")";
        }        
    }
    
    if($search_filter != false){
        if($exact_match){
            $extra_cond .= " AND s.value = '". $wpdb->escape($search_filter)."' ";            
        }else{
            $extra_cond .= " AND s.value LIKE '%". $wpdb->escape($search_filter)."%' ";            
        }
    }
    
    $context_filter = isset($_GET['context']) ? $_GET['context'] : false;
    if($context_filter !== false){
        $extra_cond .= " AND s.context = '" . $wpdb->escape($context_filter) . "'";
    }
    
    if(isset($_GET['show_results']) && $_GET['show_results']=='all'){
        $limit = 9999;
        $offset = 0;
    }else{       
        $limit = $sitepress_settings['st']['strings_per_page']; 
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit;
    }
    
    /* TRANSLATOR - START */
    if(icl_st_is_translator()){
        
        $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);    
        
        if(!empty($status_filter_lang)){
            
            $_joins = $_sels = $_where = array();
            foreach($sitepress->get_active_languages() as $l){
                if($l['code'] == $sitepress_settings['st']['strings_language']) continue;
                $lcode_alias = str_replace('-', '', $l['code']);
                $_sels[]  = "str_{$lcode_alias}.id AS id_{$lcode_alias}, 
                             str_{$lcode_alias}.status AS status_{$lcode_alias}, 
                             str_{$lcode_alias}.value AS value_{$lcode_alias},
                             str_{$lcode_alias}.translator_id AS translator_{$lcode_alias}, 
                             str_{$lcode_alias}.translation_date AS date_{$lcode_alias}
                             ";
                $_joins[] = " LEFT JOIN {$wpdb->prefix}icl_string_translations str_{$lcode_alias} ON str_{$lcode_alias}.string_id = s.id AND str_{$lcode_alias}.language = '{$l['code']}'";    
            }
                        
            $sql = "
                SELECT SQL_CALC_FOUND_ROWS s.id AS string_id, s.language AS string_language, s.context, s.name, s.value, s.status,
                    " . join(", ", $_sels) . "               
                FROM  {$wpdb->prefix}icl_strings s 
                " . join("\n", $_joins) . "
                WHERE 
                    str_{$status_filter_lang}.status = ".ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR." AND
                    (str_{$status_filter_lang}.translator_id IS NULL OR str_{$status_filter_lang}.translator_id = ".get_current_user_id().")
                    {$extra_cond}
                ORDER BY string_id DESC
                LIMIT {$offset},{$limit}
            ";            
            $res = $wpdb->get_results($sql, ARRAY_A);
            
        }else{
            $_joins = $_sels = $_where = array();
            
            foreach($sitepress->get_active_languages() as $l){
                
                if($l['code'] == $sitepress_settings['st']['strings_language'] 
                    || empty($user_lang_pairs[$sitepress_settings['st']['strings_language']][$l['code']])) continue;
                $lcode_alias = str_replace('-', '', $l['code']);
                $lcode_alias = str_replace('-', '', $l['code']);
                
                $_sels[]  = "str_{$lcode_alias}.id AS id_{$lcode_alias}, 
                             str_{$lcode_alias}.status AS status_{$lcode_alias}, 
                             str_{$lcode_alias}.value AS value_{$lcode_alias},
                             str_{$lcode_alias}.translator_id AS translator_{$lcode_alias}, 
                             str_{$lcode_alias}.translation_date AS date_{$lcode_alias}
                             ";
                $_joins[] = "LEFT JOIN {$wpdb->prefix}icl_string_translations str_{$lcode_alias} ON str_{$lcode_alias}.string_id = s.id AND str_{$lcode_alias}.language='{$l['code']}'";
                
                if($status_filter == ICL_STRING_TRANSLATION_COMPLETE){
                    $_where[] .= " AND str_{$lcode_alias}.status = " . ICL_STRING_TRANSLATION_COMPLETE;
                }else{
                    if(empty($_lwhere)){     
                        $_lwhere = ' AND (';             
                        foreach($sitepress->get_active_languages() as $l2){
                            if($l2['code'] == $sitepress_settings['st']['strings_language'] || empty($user_lang_pairs[$sitepress_settings['st']['strings_language']][$l2['code']])) continue;
                            $l2code_alias = str_replace('-', '', $l2['code']);
                            $_lwheres[] = " str_{$l2code_alias}.status = " . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . " OR 
                                                str_{$l2code_alias}.translator_id = " . get_current_user_id() ;
                        }
                        $_lwhere .= join(' OR ', $_lwheres )  . ')';
                        $_where[] = $_lwhere;
                    }
                }        
                
            }
            
            
            $sql = "
                SELECT SQL_CALC_FOUND_ROWS s.id AS string_id, s.language AS string_language, s.context, s.name, s.value, s.status, ".
                join(', ', $_sels) . "
                FROM {$wpdb->prefix}icl_strings s " . join("\n", $_joins) . "
                WHERE s.language = '{$sitepress_settings['st']['strings_language']}' ".join(' ', $_where) . "
                    {$extra_cond}
                ORDER BY s.id DESC
                LIMIT {$offset},{$limit}
                ";

            $res = $wpdb->get_results($sql, ARRAY_A);
        }
        
        $wp_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
        $wp_query->query_vars['posts_per_page'] = $limit;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
        
        if($res){
            if(!empty($status_filter_lang)){
                foreach($res as $row){
                    
                    $_translations = array();
                    foreach($sitepress->get_active_languages() as $l){
                        if($l['code'] == $sitepress_settings['st']['strings_language']) continue;
                        $lcode_alias = str_replace('-', '', $l['code']);
                        if($row['id_'. $lcode_alias]){
                            $_translations[$l['code']] = array(
                                'id' => $row['id_'. $lcode_alias],
                                'status' => $row['status_'. $lcode_alias],
                                'language' => $l['code'],
                                'value' => $row['value_'. $lcode_alias],
                                'translator_id' => $row['translator_'. $lcode_alias],
                                'translation_date' => $row['date_'. $lcode_alias]
                            );
                        }
                    }
                    
                    
                    $string_translations[$row['string_id']] = array(
                        'string_id'             => $row['string_id'],
                        'string_language'       => $row['string_language'],
                        'context'               => $row['context'],
                        'name'                  => $row['name'],
                        'value'                 => $row['value'],
                        'status'                => ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR,
                        'translations'          => $_translations
                    );                    
                }
            }else{
                foreach($res as $row){

                    $_translations = array();
                    
                    $_status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
                    $_statuses = array();
                    foreach($sitepress->get_active_languages() as $l){
                        if($l['code'] == $sitepress_settings['st']['strings_language'] || empty($user_lang_pairs[$sitepress_settings['st']['strings_language']][$l['code']])) continue;
                        $lcode_alias = str_replace('-', '', $l['code']);
                        if($row['id_'. $lcode_alias]){
                            $_translations[$l['code']] = array(
                                'id' => $row['id_'. $lcode_alias],
                                'status' => $row['status_'. $lcode_alias],
                                'language' => $l['code'],
                                'value' => $row['value_'. $lcode_alias],
                                'translator_id' => $row['translator_'. $lcode_alias],
                                'translation_date' => $row['date_'. $lcode_alias]
                            );
                        }
                        
                        $_statuses[$l['code']] = @intval($row['status_'. $lcode_alias]);
                        
                        if($row['status_'. $lcode_alias] == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR){
                            $_status == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR; 
                        } 
                        
                    }
                    $_statuses = array_values($_statuses);
                    $_statuses = array_unique($_statuses);
                    
                    if($_statuses == array(ICL_STRING_TRANSLATION_NOT_TRANSLATED)){
                        $_status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;     
                    }elseif($_statuses == array(ICL_STRING_TRANSLATION_COMPLETE, ICL_STRING_TRANSLATION_NOT_TRANSLATED)){
                        $_status = ICL_STRING_TRANSLATION_PARTIAL;
                    }elseif($_statuses == array(ICL_STRING_TRANSLATION_COMPLETE)){
                        $_status = ICL_STRING_TRANSLATION_COMPLETE;     
                    }elseif(in_array(ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR, $_statuses) || in_array(ICL_STRING_TRANSLATION_NEEDS_UPDATE, $_statuses)){
                        $_status = ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR;
                    }    
                                          
                    
                    $string_translations[$row['string_id']] = array(
                        'string_id'             => $row['string_id'],
                        'string_language'       => $row['string_language'],
                        'context'               => $row['context'],
                        'name'                  => $row['name'],
                        'value'                 => $row['value'],
                        'status'                => $_status,
                        'translations'          => $_translations
                    );
                }
                
            }
            
        }        
    /* TRANSLATOR - END */    
    }else{
        
        // removed check for language = default lang        
        if($status_filter != ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR){
            $res = $wpdb->get_results("
                SELECT SQL_CALC_FOUND_ROWS id AS string_id, language AS string_language, context, name, value, status                
                FROM  {$wpdb->prefix}icl_strings s
                WHERE 
                    1
                    {$extra_cond}
                ORDER BY string_id DESC
                LIMIT {$offset},{$limit}
            ", ARRAY_A);
        }else{
            $res = $wpdb->get_results("
                SELECT SQL_CALC_FOUND_ROWS s.id AS string_id, s.language AS string_language, s.context, s.name, s.value, " . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . " AS status                
                FROM  {$wpdb->prefix}icl_strings s
                JOIN {$wpdb->prefix}icl_string_translations str ON str.string_id = s.id
                WHERE 
                    str.status = " . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . "
                    {$extra_cond}
                ORDER BY string_id DESC
                LIMIT {$offset},{$limit}
            ", ARRAY_A);
        }
        
        $wp_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
        $wp_query->query_vars['posts_per_page'] = $limit;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit);
        
        if($res){
            $extra_cond = '';
            if(isset($_GET['translation_language'])){
                $extra_cond .= " AND language='".$wpdb->escape($_GET['translation_language'])."'";    
            }
            
            foreach($res as $row){
                $string_translations[$row['string_id']] = $row;
                $tr = $wpdb->get_results("
                    SELECT id, language, status, value, translator_id, translation_date  
                    FROM {$wpdb->prefix}icl_string_translations 
                    WHERE string_id={$row['string_id']} {$extra_cond}
                ", ARRAY_A);
                if($tr){
                    foreach($tr as $t){
                        $string_translations[$row['string_id']]['translations'][$t['language']] = $t;
                    }                
                }
                
            }
        }
    }
    
    return $string_translations;
}

function icl_get_string_translations_by_id($string_id){
    global $wpdb;
    
    $translations = array();
    
    $results = $wpdb->get_results($wpdb->prepare("SELECT language, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d", $string_id));
    foreach($results as $row){
        $translations[$row->language] = array('value' => $row->value, 'status' => $row->status);
    }
    
    return $translations;
    
}

function icl_get_relative_translation_status($string_id, $translator_id){
    global $wpdb, $sitepress, $sitepress_settings;
    
    $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);    
    
    $src_langs = array_intersect(array_keys($sitepress->get_active_languages()), array_keys($user_lang_pairs[$sitepress_settings['st']['strings_language']]));
    
    if(empty($src_langs)) return ICL_STRING_TRANSLATION_NOT_TRANSLATED;
    
    $sql = "SELECT st.status
            FROM {$wpdb->prefix}icl_strings s 
            JOIN {$wpdb->prefix}icl_string_translations st ON s.id = st.string_id
            WHERE st.language IN ('" . join("','", $src_langs) . "') AND s.id = %d
    ";
    $statuses = $wpdb->get_col($wpdb->prepare($sql, $string_id));
    
    $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
    $one_incomplete = false;
    foreach($statuses as $s){
        if($s == ICL_STRING_TRANSLATION_COMPLETE){
            $status = ICL_STRING_TRANSLATION_COMPLETE;        
        }elseif($s == ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $one_incomplete = true;
        }
    }
    
    if($status == ICL_STRING_TRANSLATION_COMPLETE && $one_incomplete){
        $status = ICL_STRING_TRANSLATION_PARTIAL;        
    }
    
    return $status;
    
}

function icl_get_strigs_tracked_in_pages($string_translations){
    global $wpdb;
    // get string position in page - if found
    $found_strings = $strings_in_page = array();
    foreach(array_keys((array)$string_translations) as $string_id){
        $found_strings[] = $string_id;
    }
    if($found_strings){
        $res = $wpdb->get_results("
            SELECT kind, string_id  FROM {$wpdb->prefix}icl_string_positions 
            WHERE string_id IN (".implode(',', $found_strings).")");
        foreach($res as $row){
            $strings_in_page[$row->kind][$row->string_id] = true;
        }
    }
    return $strings_in_page;
}

function icl_sw_filters_blogname($val){
    return icl_t('WP', 'Blog Title', $val);
}

function icl_sw_filters_blogdescription($val){
    return icl_t('WP', 'Tagline', $val);
}

function icl_sw_filters_widget_title($val){
    return icl_t('Widgets', 'widget title - ' . md5($val) , $val);    
}

function icl_sw_filters_widget_text($val){    
    $val = icl_t('Widgets', 'widget body - ' . md5($val) , $val);
    return $val;
}

function icl_sw_filters_gettext($translation, $text, $domain, $name = false){
    global $sitepress_settings;
    $has_translation = 0;
    
    static $gettext_calls = array('__', '_e', '_ex', '_n', '_n_noop', '_nx', '_nx_noop', '_x', 'esc_attr__', 'esc_attr_e', 'esc_attr_x', 'esc_html__', 'esc_html_e', 'esc_html_x',);
    
    $dbt = debug_backtrace();    
    $dbt4 = isset($dbt[4]['file']) ? str_replace('\\','/',$dbt[4]['file']) : '';
    $dbt5 = isset($dbt[5]['file']) ? str_replace('\\','/',$dbt[5]['file']) : '';
    $wp_plugin_dir = str_replace('\\','/',WP_PLUGIN_DIR);
    $wpmu_plugin_dir = str_replace('\\','/',WPMU_PLUGIN_DIR); 
    
    if(0 === strpos($dbt4, $wp_plugin_dir)){        
        if(dirname($dbt4) == $wp_plugin_dir){
            $plugin_folder = basename(str_replace($wp_plugin_dir, '', $dbt4));    
        }else{
            $exp = explode('/', ltrim(str_replace($wp_plugin_dir, '', $dbt4),'/'));            
            $plugin_folder = $exp[0];    
        }
        $context = 'plugin ' . $plugin_folder;
    }elseif(0 === strpos($dbt5, $wp_plugin_dir) && in_array($dbt[5]['function'], $gettext_calls)){        
        if(dirname($dbt4) == $wp_plugin_dir){
            $plugin_folder = basename(str_replace($wp_plugin_dir, '', $dbt5));    
        }else{
            $exp = explode('/', ltrim(str_replace($wp_plugin_dir, '', $dbt5),'/'));            
            $plugin_folder = $exp[0];    
        }
        $context = 'plugin ' . $plugin_folder;
    }elseif(0 === strpos($dbt4, $wpmu_plugin_dir)){ 
        $context = ($domain != 'default') ? 'plugin ' . $domain : 'plugin';         
    }elseif(0 === strpos($dbt5, $wpmu_plugin_dir) && in_array($dbt[5]['function'], $gettext_calls)){ 
        $context = ($domain != 'default') ? 'plugin ' . $domain : 'plugin';                 
    }else{
        $context = ($domain != 'default') ? 'theme ' . $domain : 'WordPress';
    }
    
    // track strings if the user has enabled this and if it's and editor or admin
    if(isset($sitepress_settings['st']['track_strings']) && $sitepress_settings['st']['track_strings'] && current_user_can('edit_others_posts')){
        icl_st_track_string($text, $context, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE);
    }   

    if(empty($name)){
        $name = md5($text);
    }     
    
    $ret_translation = icl_t($context, $name, $text, $has_translation);
    if(false === $has_translation){
        $ret_translation = $translation;   
    }
    
    if(isset($_GET['icl_string_track_value']) && isset($_GET['icl_string_track_context']) 
        && stripslashes($_GET['icl_string_track_context']) == $context && stripslashes($_GET['icl_string_track_value']) == $text){
            $ret_translation = '<span style="background-color:'.$sitepress_settings['st']['hl_color'].'">' . $ret_translation . '</span>';
            
    }
    
    return $ret_translation;
}

function icl_sw_filters_gettext_with_context($translation, $text, $_gettext_context, $domain){
    return icl_sw_filters_gettext($translation, $text, $domain, $_gettext_context . ': ' . $text);
}

function icl_sw_filters_ngettext($translation, $single, $plural, $number, $domain, $_gettext_context = false){    
    if($number == 1){
        return icl_sw_filters_gettext($translation, $single, $domain, $_gettext_context);    
    }else{
        return icl_sw_filters_gettext($translation, $plural, $domain, $_gettext_context);            
    }
}

function icl_sw_filters_nxgettext($translation, $single, $plural, $number, $_gettext_context, $domain){        
    return icl_sw_filters_ngettext($translation, $single, $plural, $number, $domain, $_gettext_context);
}

function icl_st_author_first_name_filter($value, $user_id){
    global $sitepress_settings;
    
    if(false === $user_id){
        global $authordata;
        $user_id = $authordata->data->ID;
    }
    
    $user = new WP_User($user_id);        
    if ( is_array( $user->roles ) && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
        $value = icl_st_translate_author_fields('first_name', $value, $user_id);
    }
        
    return $value;
}

function icl_st_author_last_name_filter($value, $user_id){
    global $sitepress_settings;
    
    if(false === $user_id){
        global $authordata;
        $user_id = $authordata->data->ID;
    }
    
    $user = new WP_User($user_id);        
    if ( is_array( $user->roles ) && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
        $value = icl_st_translate_author_fields('last_name', $value, $user_id);
    }
        
    return $value;
}

function icl_st_author_nickname_filter($value, $user_id){
    global $sitepress_settings;

    if(false === $user_id){
        global $authordata;
        $user_id = $authordata->data->ID;
    }
    
    $user = new WP_User($user_id);        
    if ( is_array( $user->roles ) && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
        $value = icl_st_translate_author_fields('nickname', $value, $user_id);
    }
        
    return $value;
}    

function icl_st_author_description_filter($value, $user_id){
    global $sitepress_settings;
    
    if(false === $user_id){
        global $authordata;
        if(empty($authordata->data)) return $value;
        $user_id = $authordata->data->ID;
    }
    
    $user = new WP_User($user_id);        
    
    if ( is_array( $user->roles ) && is_array($sitepress_settings['st']['translated-users']) && array_intersect($user->roles, $sitepress_settings['st']['translated-users'])){
        $value = icl_st_translate_author_fields('description', $value, $user_id);
    }
    
    return $value;
}    

function icl_st_author_displayname_filter($value){
    global $authordata, $sitepress_settings;
    
    if(isset($authordata->ID)){    
        $user = new WP_User($authordata->ID);        
        if ( is_array( $user->roles ) && isset($sitepress_settings['st']['translated-users']) && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
            $value = icl_st_translate_author_fields('display_name', $value, isset($authordata->ID)?$authordata->ID:null);
        }
    }
    
    return $value;
}        

function icl_st_translate_author_fields($field, $value, $user_id){
    global $sitepress_settings;
    if(empty($user_id)) $user_id = get_current_user_id();
    
    $user = new WP_User($user_id);        
    if ( is_array( $user->roles ) && is_array($sitepress_settings['st']['translated-users'])  && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
        $value = icl_translate('Authors', $field . '_' . $user_id, $value, true);
    }

    return $value;
}

function icl_st_register_user_strings($user_id){
    global $sitepress_settings;
    
    $user = new WP_User($user_id);        

    if ( is_array( $user->roles ) && is_array($sitepress_settings['st']['translated-users'])  && array_intersect($user->roles, (array)$sitepress_settings['st']['translated-users'])){
        $fields = array('first_name', 'last_name', 'nickname', 'description');                  
        foreach($fields as $field){
            icl_register_string('Authors', $field . '_' . $user_id, get_the_author_meta($field, $user_id), true, true);
        }
        
        icl_register_string('Authors', 'display_name_' . $user_id, $user->display_name, true, true);    
    }
} 
    
function icl_st_register_user_strings_all(){
    global $wpdb;
    $users = get_users(array('blog_id'=>$wpdb->blogid, 'fields'=>'ID'));
    foreach($users as $uid){
        icl_st_register_user_strings($uid);
    }    
}

function icl_st_update_string_actions($context, $name, $old_value, $new_value){
    global $wpdb;  
    if($new_value != $old_value){        
        $string = $wpdb->get_row($wpdb->prepare("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", $context, $name));    
        if(!$string){
            icl_register_string($context, $name, $new_value);
            return;
        }
        $wpdb->update($wpdb->prefix . 'icl_strings', array('value'=>$new_value), array('id'=>$string->id));
        if($string->status == ICL_STRING_TRANSLATION_COMPLETE || $string->status == ICL_STRING_TRANSLATION_PARTIAL){
            $wpdb->update($wpdb->prefix . 'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string->id));
            $wpdb->update($wpdb->prefix . 'icl_strings', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('id'=>$string->id));
        }
        
        if($context == 'Widgets' && $new_value){
            if(0 === strpos($name, 'widget title - ')){
                icl_rename_string('Widgets', 'widget title - ' . md5($old_value), 'widget title - ' . md5($new_value));
            }elseif(0 === strpos($name, 'widget body - ')){
                icl_rename_string('Widgets', 'widget body - ' . md5($old_value), 'widget body - ' . md5($new_value));
            }
        }        
        
    }
    
}

function icl_st_update_blogname_actions($old, $new){
    icl_st_update_string_actions('WP', 'Blog Title', $old, $new);
}

function icl_st_update_blogdescription_actions($old, $new){
    icl_st_update_string_actions('WP', 'Tagline', $old, $new);
}

function icl_st_update_widget_title_actions($old_options, $new_options){        
    
    if(isset($new_options['title'])){ // case of 1 instance only widgets
        $buf = $new_options;
        unset($new_options);
        $new_options[0] = $buf;
        unset($buf);
        $buf = $old_options;
        unset($old_options);
        $old_options[0] = $buf;
        unset($buf);        
    }
    
    foreach($new_options as $k=>$o){
        if(isset($o['title'])){
            if(isset($old_options[$k]['title']) && $old_options[$k]['title']){
                icl_st_update_string_actions('Widgets', 'widget title - ' . md5(apply_filters('widget_title', $old_options[$k]['title'])), apply_filters('widget_title', $old_options[$k]['title']), apply_filters('widget_title', $o['title']));        
            }else{                
                if($new_options[$k]['title']){          
                    icl_register_string('Widgets', 'widget title - ' . md5(apply_filters('widget_title', $new_options[$k]['title'])), apply_filters('widget_title', $new_options[$k]['title']));
                }                
            }            
        }
    }    
}

function icl_st_update_text_widgets_actions($old_options, $new_options){
    global $sitepress_settings, $wpdb;
    
    // remove filter for showing permalinks instead of sticky links while saving
    $GLOBALS['__disable_absolute_links_permalink_filter'] = 1;
    
    $widget_text = get_option('widget_text');    
    if(is_array($widget_text)){
        foreach($widget_text as $k=>$w){
            if(isset($old_options[$k]['text']) && trim($old_options[$k]['text']) && $old_options[$k]['text'] != $w['text']){
                $old_md5 = md5(apply_filters('widget_text', $old_options[$k]['text']));
                $string = $wpdb->get_row($wpdb->prepare("SELECT id, value, status FROM {$wpdb->prefix}icl_strings WHERE context=%s AND name=%s", 'Widgets', 'widget body - ' . $old_md5));    
                if ($string) {
                    icl_st_update_string_actions('Widgets', 'widget body - ' . $old_md5, apply_filters('widget_text', $old_options[$k]['text']), apply_filters('widget_text', $w['text']));
                } else {
                    icl_register_string('Widgets', 'widget body - ' . md5(apply_filters('widget_text', $w['text'])), apply_filters('widget_text', $w['text']));
                }
            }elseif(isset($new_options[$k]['text']) && $old_options[$k]['text']!=$new_options[$k]['text']){
                icl_register_string('Widgets', 'widget body - ' . md5(apply_filters('widget_text', $new_options[$k]['text'])), apply_filters('widget_text', $new_options[$k]['text']));
            }
        }
    }

    // add back the filter for showing permalinks instead of sticky links after saving
    unset($GLOBALS['__disable_absolute_links_permalink_filter']);
    
}

function icl_t_cache_lookup($context, $name){
    global $sitepress_settings, $sitepress, $wpdb;
    
    static $icl_st_cache;
    $ret_value = false;
    
    if(!isset($icl_st_cache[$context])){  //CACHE MISS (context)    
        
        $icl_st_cache[$context] = array();
                        
        // determine the correct current language
        if(defined('DOING_AJAX') || defined('DOING_CRON') || isset($_GET['doing_wp_cron'])){            
             $current_language = $sitepress->get_language_cookie();
        }elseif(is_admin()){            
            $current_language = $sitepress->get_admin_language();                 
        }else{
            $current_language = $sitepress->get_current_language();     
        }
        $default_language = $sitepress->get_default_language();
        
        // workaround for multi-site setups - part i
        global $switched, $switched_stack;        
        if(isset($switched) && $switched){
            $prev_blog_id = $wpdb->blogid;
            $wpdb->set_blog_id($switched_stack[0]);
        }
        
        // THE QUERY        
        $res = $wpdb->get_results($wpdb->prepare("
            SELECT s.name, s.value, t.value AS translation_value, t.status
            FROM  {$wpdb->prefix}icl_strings s
            LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
            WHERE s.context = %s
                AND (t.language = %s OR t.language IS NULL)
            ", $context, $current_language), ARRAY_A);        
        // workaround for multi-site setups - part ii
        if(isset($switched) && $switched){
            $wpdb->set_blog_id($prev_blog_id);
        }   
        
        // SAVE QUERY RESULTS
        if($res){
            foreach($res as $row){                
                if($row['status'] != ICL_STRING_TRANSLATION_COMPLETE || empty($row['translation_value'])){
                    $icl_st_cache[$context][$row['name']]['translated'] = false;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['value'];
                }else{
                    $icl_st_cache[$context][$row['name']]['translated'] = true;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['translation_value'];
                    $icl_st_cache[$context][$row['name']]['original'] = $row['value'];
                }
            }
        }
        
    }
        
    if(isset($icl_st_cache[$context][$name])){   
        $ret_value = $icl_st_cache[$context][$name];                             
    }    
        
    return $ret_value;    
}

function icl_st_get_contexts($status){
    global $wpdb, $sitepress, $sitepress_settings;    
    $extra_cond = '';
    
    if($status !== false){
        if($status == ICL_STRING_TRANSLATION_COMPLETE){
            $extra_cond .= " AND s.status = " . ICL_STRING_TRANSLATION_COMPLETE;
        }else{
            $extra_cond .= " AND s.status IN (" . ICL_STRING_TRANSLATION_PARTIAL . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "," . ICL_STRING_TRANSLATION_NOT_TRANSLATED . ")";
        }        
    }
    
    if(icl_st_is_translator()){
        $user_langs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);
    
        $active_langs = $sitepress->get_active_languages();
        if(!empty($user_langs[$sitepress_settings['st']['strings_language']])){
            
            foreach($user_langs[$sitepress_settings['st']['strings_language']] as $lang=>$one){
                if(isset($active_langs[$lang])){
                    $lcode_alias = str_replace('-', '', $lang);
                    $joins[] = " JOIN {$wpdb->prefix}icl_string_translations {$lcode_alias}_str ON {$lcode_alias}_str.string_id = s.id AND {$lcode_alias}_str.language='{$lcode_alias}' AND
                    ( 
                        {$lcode_alias}_str.status = " . ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR . 
                        " OR {$lcode_alias}_str.translator_id = " . get_current_user_id() 
                    . ")" . "\n";
                        
                }            
                
            }            
            
            $sql = "
                SELECT s.context, COUNT(s.context) AS c FROM {$wpdb->prefix}icl_strings s
                ".join("\n", $joins)."
                WHERE 1 {$extra_cond} 
                GROUP BY context
                ORDER BY context ASC
            ";
            
            $results = $wpdb->get_results($sql);
        }
        
    }else{
        $results = $wpdb->get_results("
            SELECT context, COUNT(context) AS c 
            FROM {$wpdb->prefix}icl_strings s
            WHERE language='{$sitepress->get_current_language()}' {$extra_cond}
            GROUP BY context 
            ORDER BY context ASC");
        
    }
    
    return $results;
}

function icl_st_admin_notices(){
    global $icl_st_err_str;
    if($icl_st_err_str){
        echo '<div class="error"><p>' . $icl_st_err_str . '</p></div>';
    }    
}

function icl_st_scan_theme_files($dir = false, $recursion = 0){
    require_once ICL_PLUGIN_PATH . '/inc/potx.php';
    static $scan_stats = false;
    static $recursion, $scanned_files = array();
    global $icl_scan_theme_found_domains, $sitepress, $sitepress_settings;
    if($dir === false){  
        $dir = TEMPLATEPATH;
    }
    if(!$scan_stats){
        $scan_stats = sprintf(__('Scanning theme folder: %s', 'wpml-string-translation'),$dir) . PHP_EOL;
    }    
                            
    $dh = opendir($dir);    
    while(false !== ($file = readdir($dh))){
        if($file=="." || $file=="..") continue;
        
        if(is_dir($dir . "/" . $file)){
            $recursion++;
            $scan_stats .= str_repeat("\t",$recursion) . sprintf(__('Opening folder: %s', 'wpml-string-translation'), $dir . "/" . $file) . PHP_EOL;
            icl_st_scan_theme_files($dir . "/" . $file, $recursion);            
            $recursion--;
        }elseif(preg_match('#(\.php|\.inc)$#i', $file)){     
            // THE potx way
            $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Scanning file: %s', 'wpml-string-translation'), $dir . "/" . $file) . PHP_EOL;
            $scanned_files[] = $dir . "/" . $file;
            _potx_process_file($dir . "/" . $file, 0, '__icl_st_scan_theme_files_store_results','_potx_save_version', POTX_API_7);
        }else{
            $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Skipping file: %s', 'wpml-string-translation'), $dir . "/" . $file) . PHP_EOL;    
        }
    }
    
    if($dir == TEMPLATEPATH && TEMPLATEPATH != STYLESHEETPATH){
        static $double_scan = true;
        icl_st_scan_theme_files(STYLESHEETPATH);            
        $double_scan = false;
    }
    
    if(!$recursion && (empty($double_scan) || !$double_scan)){
        global $__icl_registered_strings;
        $scan_stats .= __('Done scanning files', 'wpml-string-translation') . PHP_EOL;
            $sitepress_settings['st']['theme_localization_domains'] = array_keys($icl_scan_theme_found_domains);
            $sitepress->save_settings($sitepress_settings);
            closedir($dh);
            $scan_stats_all = __('= Your theme was scanned for texts =', 'wpml-string-translation') . '<br />' . 
                          __('The following files were processed:', 'wpml-string-translation') . '<br />' .
                          '<ol style="font-size:10px;"><li>' . join('</li><li>', $scanned_files) . '</li></ol>' . 
                          sprintf(__('WPML found %s strings. They were added to the string translation table.','wpml-string-translation'),count($__icl_registered_strings)) . 
                          '<br /><a href="#" onclick="jQuery(this).next().toggle();return false;">' . __('More details', 'wpml-string-translation') . '</a>'.
                          '<textarea style="display:none;width:100%;height:150px;font-size:10px;">' . $scan_stats . '</textarea>'; 
            return $scan_stats_all;
    }
    
}
                                                
function __icl_st_scan_theme_files_store_results($string, $domain, $_gettext_context, $file, $line){
    
    global $icl_scan_theme_found_domains;
    
    $string = str_replace(array('\"',"\\'"), array('"',"'"), $string);
    //replace extra backslashes added by _potx_process_file
    $string = str_replace(array('\\\\'), array('\\'), $string);
    
    if(!isset($icl_scan_theme_found_domains[$domain])){
        $icl_scan_theme_found_domains[$domain] = true;
    }
    global $wpdb, $__icl_registered_strings;
    
    $context = false;
    
    if(!isset($__icl_registered_strings)){
        $__icl_registered_strings = array();
        
        // clear existing entries (both source and page type)
        $context  = $domain ? 'theme ' . $domain : 'WordPress';
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN 
            (SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = '{$context}')");        
    }
    
    if(!isset($__icl_registered_strings[$domain.'||'.$string.'||'.$_gettext_context])){
        if(!$domain){
            $context = 'WordPress';
        }else{
            $context = 'theme ' . $domain;            
        }        
        
        $name = $_gettext_context ? $_gettext_context . ': ' . $string  : md5($string);
        icl_register_string($context, $name, $string);
        
        $__icl_registered_strings[$domain.'||'.$string.'||'.$_gettext_context] = true;
    }                
    
    // store position in source
    icl_st_track_string($string, $context, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $file, $line);              
}

function icl_st_scan_plugin_files($plugin, $recursion = 0){
    require_once ICL_PLUGIN_PATH . '/inc/potx.php';
    static $recursion, $scanned_files = array();
    static $scan_stats = false;
    global $icl_scan_plugin_found_domains, $icl_st_p_scan_plugin_id,
           $sitepress, $sitepress_settings;
    
    if(!$recursion){
        $icl_st_p_scan_plugin_id = str_replace(WP_PLUGIN_DIR .'/', '', $plugin);
        $icl_st_p_scan_plugin_id = str_replace(WPMU_PLUGIN_DIR .'/', '', $icl_st_p_scan_plugin_id);
    }
    
    if(is_file($plugin) && !$recursion){ // case of one-file plugins
        $scan_stats = sprintf(__('Scanning file: %s', 'wpml-string-translation'), $plugin) . PHP_EOL;
        _potx_process_file($plugin, 0, '__icl_st_scan_plugin_files_store_results','_potx_save_version', POTX_API_7);                    
        $scanned_files[] = $plugin;
    }else{
        $dh = opendir($plugin);    
        while(false !== ($file = readdir($dh))){
            if(0 === strpos($file, '.')) continue;
            if(is_dir($plugin . "/" . $file)){
                $recursion++;
                $scan_stats .= str_repeat("\t",$recursion-1) . sprintf(__('Opening folder: %s', 'wpml-string-translation'), "/" . $file) . PHP_EOL;
                icl_st_scan_plugin_files($plugin . "/" . $file, $recursion);            
                $recursion--;
            }elseif(preg_match('#(\.php|\.inc)$#i', $file)){     
                $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Scanning file: %s', 'wpml-string-translation'), "/" . $file) . PHP_EOL;
                $scanned_files[] = "/" . $file;
                _potx_process_file($plugin . "/" . $file, 0, '__icl_st_scan_plugin_files_store_results','_potx_save_version', POTX_API_7);
            }else{
                $scan_stats .=  str_repeat("\t",$recursion) . sprintf(__('Skipping file: %s', 'wpml-string-translation'), "/" . $file) . PHP_EOL;    
            }
        }        
    }
    
    
    if(!$recursion){
        global $__icl_registered_strings;
        if(is_null($__icl_registered_strings)){
            $__icl_registered_strings = array();    
        }        
        $scan_stats .= __('Done scanning files', 'wpml-string-translation') . PHP_EOL;                    
        
        /*
        if(is_array($icl_scan_plugin_found_domains)){
            $existing_domains = $sitepress_settings['st']['plugins_localization_domains'];
            if(is_array($existing_domains)){
                $sitepress_settings['st']['plugins_localization_domains'] = array_unique(array_merge(array_keys($icl_scan_plugin_found_domains), $existing_domains));
            }else{
                $sitepress_settings['st']['plugins_localization_domains'] = array_keys($icl_scan_plugin_found_domains);
            }
            $sitepress->save_settings($sitepress_settings);
        }
        */
        
        unset($icl_st_p_scan_plugin_id);        
        $scan_stats = '<textarea style="width:100%;height:150px;font-size:10px;">' . $scan_stats . "\n" .
                       count($scanned_files) . ' scanned files' . "\n";    
        if(count($__icl_registered_strings)){
            $scan_stats .=  sprintf(__('WPML found %s strings. They were added to the string translation table.','wpml-string-translation'),count($__icl_registered_strings)) . "\n";
        }else{
            $scan_stats .=  __('No strings found.','wpml-string-translation') . "\n";
        }
        $scan_stats .= '</textarea>';
                        
        
        $scan_stats_ret = $scan_stats;               
        
        $scan_stats = false;
        
        return $scan_stats_ret;
    }    
    
}

function __icl_st_scan_plugin_files_store_results($string, $domain, $_gettext_context, $file, $line){
    global $icl_scan_plugin_found_domains, $icl_st_p_scan_plugin_id;
    
    $string = str_replace(array('\"',"\\'"), array('"',"'"), $string);
    //replace extra backslashes added by _potx_process_file
    $string = str_replace(array('\\\\'), array('\\'), $string);        
        
    //if(!isset($icl_scan_plugin_found_domains[$domain])){
    //    $icl_scan_plugin_found_domains[$domain] = true;
    //}    
    global $wpdb, $__icl_registered_strings;
    if(empty($__icl_registered_strings) ){
        $__icl_registered_strings = array();
        
        // clear existing entries (both source and page type)        
        $context  = $icl_st_p_scan_plugin_id ? 'plugin ' . $icl_st_p_scan_plugin_id : 'plugins';
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_string_positions WHERE string_id IN 
            (SELECT id FROM {$wpdb->prefix}icl_strings WHERE context = '{$context}')");
    }
    
    if(!isset($__icl_registered_strings[$icl_st_p_scan_plugin_id.'||'.$string])){
        
        $name = $_gettext_context ? $_gettext_context . ': ' . $string  : md5($string);
        
        if(!$domain){
            icl_register_string('plugins', $name, $string);
        }else{
            icl_register_string('plugin ' . $icl_st_p_scan_plugin_id, $name, $string);
        }        
        $__icl_registered_strings[$icl_st_p_scan_plugin_id.'||'.$string] = true;
    }  
    
    // store position in source
    icl_st_track_string($string, 'plugin ' . $icl_st_p_scan_plugin_id, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE, $file, $line);              
    
}

function get_theme_localization_stats(){
    global $sitepress_settings, $wpdb;
    $stats = false;
    if(isset($sitepress_settings['st']['theme_localization_domains'])){    
        foreach((array)$sitepress_settings['st']['theme_localization_domains'] as $domain){
            $domains[] = $domain ? 'theme ' . $domain : 'theme';
        }
        $results = $wpdb->get_results("
            SELECT context, status, COUNT(id) AS c 
            FROM {$wpdb->prefix}icl_strings
            WHERE context IN ('".join("','",$domains)."')
            GROUP BY context, status            
        ");
        foreach($results as $r){
            if(!isset($stats[$r->context]['complete'])){
                $stats[$r->context]['complete'] = 0;
            }
            if(!isset($stats[$r->context]['incomplete'])){
                $stats[$r->context]['incomplete'] = 0;
            }            
            if($r->status == ICL_STRING_TRANSLATION_COMPLETE){
                $stats[$r->context]['complete'] = $r->c; 
            }else{
                $stats[$r->context]['incomplete'] += $r->c; 
            }
            
        }
    }
   return $stats; 
}

function get_plugin_localization_stats(){
    global $sitepress_settings, $wpdb;
    $stats = false;

    $results = $wpdb->get_results("
        SELECT context, status, COUNT(id) AS c 
        FROM {$wpdb->prefix}icl_strings
        WHERE context LIKE ('plugin %')
        GROUP BY context, status            
    ");
    
    foreach($results as $r){
        if(!isset($stats[$r->context]['complete'])){
            $stats[$r->context]['complete'] = 0;
        }
        if(!isset($stats[$r->context]['incomplete'])){
            $stats[$r->context]['incomplete'] = 0;
        }            
        if($r->status == ICL_STRING_TRANSLATION_COMPLETE){
            $stats[$r->context]['complete'] = $r->c; 
        }else{
            $stats[$r->context]['incomplete'] += $r->c; 
        }
        
    }
    
    return $stats;     
}

function icl_st_generate_po_file($strings, $potonly = false){
    global $wpdb;
    
    $po = "";
    $po .= '# This file was generated by WPML' . PHP_EOL;
    $po .= '# WPML is a WordPress plugin that can turn any WordPress or WordPressMU site into a full featured multilingual content management system.' . PHP_EOL;    
    $po .= '# http://wpml.org' . PHP_EOL;
    $po .= 'msgid ""' . PHP_EOL;
    $po .= 'msgstr ""' . PHP_EOL;
    $po .= '"Content-Type: text/plain; charset=utf-8\n"' . PHP_EOL;
    $po .= '"Content-Transfer-Encoding: 8bit\n"' . PHP_EOL;
    $po .= '"Project-Id-Version: \n"' . PHP_EOL;
    $po .= '"POT-Creation-Date: \n"' . PHP_EOL;
    $po .= '"PO-Revision-Date: \n"' . PHP_EOL;
    $po .= '"Last-Translator: \n"' . PHP_EOL;
    $po .= '"Language-Team: \n"' . PHP_EOL;
    $po .= '"MIME-Version: 1.0\n"' . PHP_EOL;    
    
    foreach($strings as $s){
        $ids[] = $s['string_id'];
    }
    if(!empty($ids)){
        $res = $wpdb->get_results("
            SELECT string_id, position_in_page 
            FROM {$wpdb->prefix}icl_string_positions 
            WHERE kind = " . ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE . " AND string_id IN(".join(',',$ids).")");
        foreach($res as $row){
            $positions[$row->string_id] = $row->position_in_page;
        }
        
    }
    
    
    foreach($strings as $s){
        $po .= PHP_EOL;        
        if(!$potonly && isset($s['translations']) && isset($s['translations'][key($s['translations'])]['value'])){
            $translation = $s['translations'][key($s['translations'])]['value'];
            if($translation != '' && $s['translations'][key($s['translations'])]['status'] != ICL_STRING_TRANSLATION_COMPLETE){
                $po .= '#, fuzzy' . PHP_EOL;
            }
        }else{
            $translation = '';            
        }
        if(isset($positions[$s['string_id']])){           
            $exp = @explode('::',$positions[$s['string_id']]);
            $file = @file($exp[0]);
        }
        $po .= '# ' . @trim($file[$exp[1]-2])  . PHP_EOL;
        $po .= '# ' . @trim($file[$exp[1]-1])  . PHP_EOL;
        $po .= '# ' . @trim($file[$exp[1]])  . PHP_EOL;
        $po .= 'msgid "'.str_replace('"', '\"', $s['value']).'"' . PHP_EOL;
        $po .= 'msgstr "'.str_replace('"', '\"', $translation).'"' . PHP_EOL;
    }
    return $po;
}

function icl_st_track_string($text, $context, $kind = ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE, $file = null, $line = null){
    global $wpdb;
    // get string id
    $string_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND value='".$wpdb->escape($text)."'");    
    if($string_id){
        // get existing records
        $string_records_count = $wpdb->get_var("SELECT COUNT(id) 
                                        FROM {$wpdb->prefix}icl_string_positions 
                                        WHERE string_id = '{$string_id}' AND kind = " . $kind);
        if(ICL_STRING_TRANSLATION_STRING_TRACKING_THRESHOLD > $string_records_count){        
            if($kind == ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE){
                // get page url
                $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's':'';
                $position = 'http' . $https . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            }else{
                $position = $file . '::' . $line;
            }
            
            if(!$wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_string_positions 
                                WHERE string_id='{$string_id}' AND position_in_page='".$wpdb->escape($position)."' AND kind='".$kind."'")){
                $wpdb->insert($wpdb->prefix . 'icl_string_positions', array(
                    'string_id' => $string_id,
                    'kind' => $kind,
                    'position_in_page' => $position
                ));                    
            }
            
        }
    }
}

function icl_st_string_in_page($string_id){
    global $wpdb;
    // get urls   
    $urls = $wpdb->get_col($wpdb->prepare("SELECT position_in_page 
                            FROM {$wpdb->prefix}icl_string_positions 
                            WHERE string_id = %d AND kind = %d", $string_id, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_PAGE));
    if(!empty($urls)){
        $string = $wpdb->get_row($wpdb->prepare("SELECT context, value FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id));
        echo '<div id="icl_show_source_top">';
        for($i = 0; $i < count($urls); $i++){
            $c = $i+1;
            if(strpos($urls[$i], '?') !== false){
                $urls[$i] .= '&icl_string_track_value=' . urlencode($string->value);
            }else{
                $urls[$i] .= '?icl_string_track_value=' . urlencode($string->value);
            }            
            $urls[$i] .= '&icl_string_track_context=' . urlencode($string->context);            
            echo '<a href="#" onclick="jQuery(\'#icl_string_track_frame_wrap iframe\').attr(\'src\',\''.$urls[$i].'\');jQuery(\'#icl_string_track_url a\').html(\''.$urls[$i].'\').attr(\'href\',  \''.$urls[$i].'\'); return false;">'.$c.'</a><br />';
            
        }
        echo '</div>';
        echo '<div id="icl_string_track_frame_wrap">';        
        echo '<iframe onload="iclResizeIframe()" src="'.$urls[0].'" width="10" height="10" frameborder="0" marginheight="0" marginwidth="0"></iframe>';
        echo '<div id="icl_string_track_url" class="icl_string_track_url"><a href="'.$urls[0].'">' . htmlspecialchars($urls[0]) . "</a></div>\n";
        echo '</div>';        
    }else{
        _e('No records found', 'wpml-string-translation');
    }
}

function icl_st_string_in_source($string_id){
    global $wpdb, $sitepress_settings;
    // get positions    
    $files = $wpdb->get_col($wpdb->prepare("SELECT position_in_page 
                            FROM {$wpdb->prefix}icl_string_positions 
                            WHERE string_id = %d AND kind = %d", $string_id, ICL_STRING_TRANSLATION_STRING_TRACKING_TYPE_SOURCE));
    
    if(!empty($files)){
        $string = $wpdb->get_row($wpdb->prepare("SELECT context, value FROM {$wpdb->prefix}icl_strings WHERE id=%d",$string_id));        
        echo '<div id="icl_show_source_top">';
        for($i = 0; $i < count($files); $i++){            
            $c = $i+1;
            $exp = explode('::', $files[$i]);
            $line = $exp[1];
            echo '<a href="#" onclick="icl_show_in_source('.$i.','.$line.')">'.$c.'</a><br />';
        }
        echo '</div>';
        echo '<div id="icl_show_source_wrap">';
        for($i = 0; $i < count($files); $i++){            
            $exp = explode('::', $files[$i]);
            $file = $exp[0];
            if(!file_exists($file) || !is_readable($file)) continue;
            $line = $exp[1];
            echo '<div class="icl_string_track_source" id="icl_string_track_source_'.$i.'"';
            if($i > 0){
                echo 'style="display:none"';
            }else{
                $first_pos = $line;                
            }
            echo '>';
            if($i == 0){
                echo '<script type="text/javascript">icl_show_in_source_scroll_once = ' . $line . '</script>';
            }
            echo '<div class="icl_string_track_filename">' . $file . "</div>\n";
            echo '<pre>';        
            $content = file($file);
            echo '<ol>';
            $hl_color = !empty($sitepress_settings['st']['hl_color'])?$sitepress_settings['st']['hl_color']:'#FFFF00';
            foreach($content as $k=>$l){
                if($k == $line-1){
                    $hl =  ' style="background-color:'.$hl_color.';"';
                }else{
                    $hl = '';   
                }
                echo '<li id="icl_source_line_'.$i.'_'.$k.'"'.$hl.'">' . htmlspecialchars($l) . '&nbsp;</li>';
            }
            echo '</ol>';
            echo '</pre>';
            echo '</div>'; 
        }
        echo '</div>';
    }else{
        _e('No records found', 'wpml-string-translation');
    }    
}

function _icl_st_hide_random($str){
    $str = preg_replace('#^((.+)( - ))?([a-z0-9]{32})$#', '$2', $str);
    return $str;
}

function _icl_st_get_options_writes($path){
    static $found_writes = array();
    if(is_dir($path)){        
        $dh = opendir($path);
        while($file = readdir($dh)){
            if($file=="." || $file=="..") continue;
            if(is_dir($path . '/' . $file)){
                _icl_st_get_options_writes($path . '/' . $file);                
            }elseif(preg_match('#(\.php|\.inc)$#i', $file)){
                $content = file_get_contents($path . '/' . $file);
                $int = preg_match_all('#(add|update)_option\(([^,]+),([^)]+)\)#im', $content, $matches);
                if($int){
                    foreach($matches[2] as $m){
                        $option_name = trim($m);
                        if(0 === strpos($option_name, '"') || 0 === strpos($option_name, "'")){
                            $option_name = trim($option_name, "\"'");
                        }elseif(false === strpos($option_name, '$')){
                            if(false !== strpos($option_name, '::')){
                                $cexp = explode('::', $option_name);
                                if (class_exists($cexp[0])){
                                    if (defined($cexp[0].'::'. $cexp[1])){
                                        $option_name = constant($cexp[0].'::'. $cexp[1]);
                                    }
                                }
                            }else{
                                if (defined( $option_name )){
                                    $option_name = constant($option_name);
                                }
                            }                            
                        }else{
                            $option_name = false;
                        }
                        if($option_name){
                            $found_writes[] = $option_name;
                        }
                    }
                }
            }
        }
    } 
    return $found_writes;
}

function icl_st_scan_options_strings(){
    global $wpdb;
    
    $options = array();
    
    // scan theme php file for update_option(), add_option()
    $options_names = _icl_st_get_options_writes(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY);
    
    
    $options_names = array_merge($options_names, _icl_st_get_options_writes(ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY));
    $options_names = array_unique($options_names);
    $options_names = array_map('mysql_real_escape_string', $options_names);
            
    if(!empty($options_names)){   
        $res = $wpdb->get_results("SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ('".join("','", $options_names)."')");
        foreach($res as $row){
            $options[$row->option_name] = maybe_unserialize($row->option_value);
            // try unserializing twice - just in case (see Arras Theme)
            $options[$row->option_name] = maybe_unserialize($options[$row->option_name]);
        }
    }
    
    
    $_icl_admin_option_names = get_option('_icl_admin_option_names', array());
    
    $_icl_admin_option_names = @array_merge_recursive($_icl_admin_option_names, $options_names);
    $_icl_admin_option_names = __array_unique_recursive($_icl_admin_option_names);
    
    update_option('_icl_admin_option_names', $_icl_admin_option_names);
         
    return $options;
}

function icl_st_render_option_writes($option_name, $option_value, $option_key=''){    
    $has_translations = '';
    if(is_array($option_value) || is_object($option_value)){
        echo '<h4><a class="icl_stow_toggler" href="#">- ' . $option_name . '</a></h4>';
        echo '<ul class="icl_st_option_writes">';
        foreach($option_value as $key=>$value){
            echo '<li>';
            icl_st_render_option_writes($key, $value, $option_key . '[' . $option_name . ']');    
            echo '</li>';
        }        
        echo '</ul>';
    }elseif(is_string($option_value) || is_numeric($option_value)){
        if(icl_st_is_registered_string('admin_texts_theme_' . get_option('template'), $option_key . $option_name)){
            $checked = ' checked="checked"';
            if(icl_st_string_has_translations('admin_texts_theme_' . get_option('template'), $option_key . $option_name)){
                $has_translations = ' class="icl_st_has_translations"';
            }else{
                $has_translations = '';
            }            
        }else{
            $checked = '';
        }        
        if(is_numeric($option_value)){
            $class = 'icl_st_numeric';
        }else{
            $class = 'icl_st_string';
        }
        
        global $iclTranslationManagement;
        
        $int = preg_match_all('#\[([^\]]+)\]#', $option_key.'['.$option_name.']', $matches);
        $_v = $iclTranslationManagement->admin_texts_to_translate;
        if($int){
            foreach($matches[1] as $m){
                if(isset($_v[$m])){
                    $_v = $_v[$m];
                }else{
                    $_v = 0;
                    break;
                }
            }
        }
        
        if($_v){
            $disabled = ' disabled="disabled"';
        }else{
            $disabled = '';
        }
        echo '<div class="icl_st_admin_string '.$class.'">';
        echo '<input'.$disabled.' type="hidden" name="icl_admin_options'.$option_key.'['.$option_name.']" value=""  />';
        echo '<input'.$disabled.$has_translations.' type="checkbox" name="icl_admin_options'.$option_key.'['.$option_name.']" value="'.htmlspecialchars($option_value).'" 
            '.$checked.' />';
        echo '<input type="text" readonly="readonly" value="'.$option_name.'" size="32" />'; 
        echo '<input type="text" value="'.htmlspecialchars($option_value).'" readonly="readonly" size="48" />';        
        //echo '<br /><input type="text" size="100" value="icl_admin_options'.$option_key.'['.$option_name.']" />';
        echo '</div><br clear="all" />';
    }
}

function icl_register_admin_options($array, $key=""){    
    
    foreach($array as $k=>$v){
        if(is_array($v)){
            icl_register_admin_options($v, $key . '['.$k.']');
        }else{
            if($v === ''){
                icl_unregister_string('admin_texts_theme_' . get_option('template'), $key . $k);
            }else{
                icl_register_string('admin_texts_theme_' . get_option('template'), $key . $k, $v);
                
                $int = preg_match_all('#\[([^\]]+)\]#', $key, $matches);
                $vals = array();
                if(count($matches[1]) > 0){
                    
                    $vals[] = $k;
                    for($i = count($matches[1]) - 1; $i >= 0 ; $i--){
                        $tmp = $vals;
                        unset($vals);
                        $vals[$matches[1][$i]] = $tmp; 
                    }
                    
                }else{
                    
                    $vals[0] = $k;
                }
                
                
                $_icl_admin_option_names = get_option('_icl_admin_option_names');
                
                
                $_icl_admin_option_names['theme'][get_option('template')] =                     
                    array_merge_recursive((array)$_icl_admin_option_names['theme'][get_option('template')], $vals);
                
                $_icl_admin_option_names['theme'][get_option('template')] = __array_unique_recursive($_icl_admin_option_names['theme'][get_option('template')]);
                
                update_option('_icl_admin_option_names', $_icl_admin_option_names); 
                
            }            
        }
    }      
    
    
}

function __array_unique_recursive($array){
    $scalars = array();
    foreach($array as $key=>$value){
        if(is_scalar($value)){
            if(isset($scalars[$value])){
                unset($array[$key]);
            }else{
                $scalars[$value] = true;    
            }
        }elseif(is_array($value)){
            $array[$key] = __array_unique_recursive($value);
        }
    }   
    return $array; 
}

function _icl_st_filter_empty_options_out($array){
    $empty_found = false;
    foreach($array as $k=>$v){
        if(is_array($v) && !empty($v)){
            list($array[$k], $empty_found) = _icl_st_filter_empty_options_out($v);
        }else{
            if(empty($v)){
                unset($array[$k]);
                $empty_found = true;
            }
        }
    }
    return array($array, $empty_found);
}

function wpml_register_admin_strings($serialized_array){
    try{
        icl_register_admin_options(unserialize($serialized_array));    
    }catch(Exception $e){
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
}

add_action('plugins_loaded', 'icl_st_set_admin_options_filters', 10);
function icl_st_set_admin_options_filters(){
    static $option_names;
    if(empty($option_names)) $option_names = get_option('_icl_admin_option_names');

    if(!empty($option_names['theme']) && !empty($option_names['theme'][basename(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY)])){
        foreach($option_names['theme'][basename(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY)] as $option_key=>$option){
            if(is_array($option) || is_object($option)){
                add_filter('option_'.$option_key, 'icl_st_translate_admin_string');        
            }else{
                add_filter('option_'.$option, 'icl_st_translate_admin_string');        
            }                
        }
        if(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY != ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY && !empty($option_names['theme'][basename(ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY)])){                         
            foreach((array)$option_names['theme'][basename(ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY)] as $option_key=>$option){
                if(is_array($option) || is_object($option)){
                    add_filter('option_'.$option_key, 'icl_st_translate_admin_string');        
                }else{
                    add_filter('option_'.$option, 'icl_st_translate_admin_string');        
                }                
            }
        }
    }
    if(!empty($option_names['plugin'])){
        foreach($option_names['plugin'] as $plugin => $options){            
            foreach((array)$options as $option_key => $option){
                if(is_array($option) || is_object($option)){
                    add_filter('option_'.$option_key, 'icl_st_translate_admin_string');        
                }else{
                    add_filter('option_'.$option, 'icl_st_translate_admin_string');        
                }                
            }            
        }
    }
}

function icl_st_translate_admin_string($option_value, $key="", $name="", $rec_level = 0){
    
    // determine option name
    if(!$name){
        
        $ob = debug_backtrace();
        if(is_scalar($ob[2+$rec_level]['args'][0])){
            $name = preg_replace('@^option_@', '',$ob[2+$rec_level]['args'][0]);    
        }
        
    }
    
    // cache - phase 1 - check/get
    static $__icl_st_cache;
    if($rec_level == 0){
        if(isset($__icl_st_cache[$name])) {
            //echo "FROM CACHE $name<br />";
            return $__icl_st_cache[$name];    
        }
        
    }
    
    // case of double-serialized options (See Arras theme)   
    $serialized = false;
    if(is_serialized( $option_value )){
        $option_value = @unserialize($option_value);
        $serialized = true;
    }
    
    //if(is_object($option_value)){
        //$option_value = (array)$option_value;
    //}
    
    if(is_array($option_value) || is_object($option_value)){
        foreach($option_value as $k=>$value){            
            
            $val = icl_st_translate_admin_string($value, $key . '[' . $name . ']' , $k, $rec_level+1);
            
            if(is_object($option_value)){
                $option_value->$k = $val;
            }else{
                $option_value[$k] = $val;
            }   
        }            
        
    }else{   
        static $option_names;
        if(empty($option_names)) $option_names = get_option('_icl_admin_option_names');                
        // determine theme/plugin name
        
         if(!empty($option_names['theme']) && !empty($option_names['theme'][basename(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY)])){
            foreach((array)$option_names['theme'][basename(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY)] as $ops=>$val){                
                if(!empty($key)){
                    $int = preg_match_all('#\[([^\]]+)\]#', $key, $matches);
                    if($int) $opname = $matches[1][0];
                }else{
                    $opname = $name;
                }
                
                if($ops == $opname){
                    $key_suff = 'theme_' . basename(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY);
                    break;
                }
            }
            
            if(ICL_STRING_TRANSLATION_TEMPLATE_DIRECTORY != ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY){
                foreach((array)$option_names['theme'][basename(ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY)] as $ops=>$val){
                    
                    if(!empty($key)){
                        $int = preg_match_all('#\[([^\]]+)\]#', $key, $matches);
                        if($int) $opname = $matches[1][0];
                    }else{
                        $opname = $name;
                    }
                    
                    if($ops == $opname){
                        $key_suff = 'theme_' . ICL_STRING_TRANSLATION_STYLESHEET_DIRECTORY;
                        break;
                    }
                }
            }
        }
        
        if(!empty($option_names['plugin'])){
            foreach((array)$option_names['plugin'] as $plugin => $options){
                foreach($options as $kops=>$ops){                    
                    if(is_array($ops)){
                        $arrkey = explode('][', trim($key, '[]'));
                        $_val = $options;
                        for($i=0; $i<count($arrkey); $i++){
                            if(isset($_val[$arrkey[$i]])){
                                $_val = $_val[$arrkey[$i]];                                 
                            }else{
                                break;
                            }
                        }
                        if(in_array($name, $_val)){
                            $key_suff = 'plugin_' . $plugin;
                            break;
                        }
                    }elseif($ops == $name){
                        $key_suff = 'plugin_' . $plugin;
                        break;
                    }
                }            
            }
        }       
        
        if(!empty($key_suff)){
            $tr = icl_t('admin_texts_' . $key_suff, $key . $name, $option_value, $hast, true);                
        } 
                
        if(isset($tr)){
            $option_value = $tr;
        }
        
    }
    
    // case of double-serialized options (See Arras theme)   
    if($serialized){
        $option_value = serialize($option_value);
    }
    
    // cache - phase 2 - set
    if($rec_level == 0){
        $__icl_st_cache[$name] = $option_value;
    }
    
    return $option_value;
}

function icl_st_get_mo_files($path){
    static $mo_files = array();
    
    if ( function_exists('realpath') )
        $path = realpath($path);
    
    if(is_dir($path) && is_readable($path)){
        $dh = opendir($path);
        if($dh !== false){
            while($f = readdir($dh)){            
                if(0 !== strpos($f, '.')){    
                    if(is_dir($path . '/' . $f)){
                        icl_st_get_mo_files($path . '/' . $f);
                    }else{
                        if(preg_match('#\.mo$#', $f)){                    
                            $mo_files[] = $path . '/' . $f;
                        }
                    }
                }
            }    
        }
    }
    
    return $mo_files;
}

function icl_st_load_translations_from_mo($mo_file){
    $translations = array();
    $mo = new MO();     
    $mo->import_from_file( $mo_file );
    foreach($mo->entries as $str=>$v){
        $str = str_replace("\n",'\n', $str);
        $translations[$str] = $v->translations[0];
    }
    return $translations;
}

// fix links in existing strings according to the new translation added
function icl_st_fix_links_in_strings($post_id){
    static $runnonce = false;
    if($runnonce){
        return;
    }
    $runonce = true;
    
    if(isset($_POST['autosave']) && $_POST['autosave']) return;
    
    if(isset($_POST['post_ID'])){
        $post_id = $_POST['post_ID'];
    }
    
    global $wpdb, $sitepress;
    $language = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='post' AND element_id=%d", $post_id));    
    if($language){
        if($sitepress->get_default_language()==$language){
            $strings = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE language=%s",$language));
        }else{
            $strings = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE language=%s",$language));
        }
            
        foreach($strings as $string_id){
            _icl_content_fix_links_to_translated_content($string_id, $language, 'string');
        }
    
    }
}

function icl_translation_send_strings($string_ids, $target_languages) {
    global $WPML_String_Translation;
    
    $errors = 0;
    
    
    // send to each language
    foreach($target_languages as $target=>$service){
        if($service == 'icanlocalize'){
            if( !_icl_translation_send_strings_icanlocalize($string_ids, $target, $service) ) $errors++;
        }elseif($service == 'local'){
            if( ! _icl_translation_send_strings_local($string_ids, $target, $service) ) $errors++;
        }
    }
    
    if($errors == count($target_languages)){
       $WPML_String_Translation->add_message(__('No string was sent to translation.','wpml-string-translation'), 'error');
    }elseif($errors > 0){
        $WPML_String_Translation->add_message(__('Some strings were not sent to translation.','wpml-string-translation'), 'error');
    }else{
        if(count($string_ids) > 1){
            $WPML_String_Translation->add_message(__('All strings were sent to translation.','wpml-string-translation'));                
        }else{
            $WPML_String_Translation->add_message(__('The string was sent to translation.','wpml-string-translation'));    
        }       
    }
}

function _icl_translation_send_strings_local($string_ids, $target) {
    global $wpdb, $sitepress_settings;
    static $site_translators;
    $site_translators = TranslationManagement::get_blog_translators();
    
    $mkey = $wpdb->prefix . 'strings_notification'; 
    $lkey = $wpdb->prefix . 'language_pairs'; 
    $slang =& $sitepress_settings['st']['strings_language'];
    
    foreach($string_ids as $string_id) {    
        $added = icl_add_string_translation($string_id, $target, NULL , ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR);
        
        if($added)
        foreach($site_translators as $key=>$st){                        
            $ulangs = isset($st->$lkey) ? $st->$lkey : false;
            if(!empty($ulangs) && !empty($ulangs[$slang][$target])){
            
                $enot = isset($st->$mkey) ? $st->$mkey : false;
                if(empty($enot[$sitepress_settings['st']['strings_language']][$target])){
                    _icl_st_translator_notification($st, $sitepress_settings['st']['strings_language'], $target);    
                    $enot[$sitepress_settings['st']['strings_language']][$target] = 1;
                    $site_translators[$key]->$mkey = $enot;                
                    update_option($wpdb->prefix . 'icl_translators_cached', $site_translators);
                    //printf("Notify %d for %s -> %s <br />", $st->ID, $sitepress_settings['st']['strings_language'], $target); 
                }else{
                    //printf("Already notified %d for %s -> %s <br />", $st->ID, $sitepress_settings['st']['strings_language'], $target); 
                }
                
            }
            
        }
    }
    
    return 1;
}

function _icl_st_translator_notification($user, $source, $target){
    global $wpdb, $sitepress;
    
    $_ldetails = $sitepress->get_language_details($source);
    $source_en = $_ldetails['english_name'];
    $_ldetails = $sitepress->get_language_details($target);
    $target_en = $_ldetails['english_name'];
    
    $message = "You have been assigned to new translation job from %s to %s.

Start editing: %s

You can view your other translation jobs here: %s

 This message was automatically sent by Translation Management running on WPML. To stop receiving these notifications contact the system administrator at %s.

 This email is not monitored for replies.

 - The folks at ICanLocalize
 101 Convention Center Dr., Las Vegas, Nevada, 89109, USA
";
    
    
    $to = $user->user_email;
    $subject = sprintf("You have been assigned to new translation job on %s.", get_bloginfo('name'));
    $body = sprintf($message, 
        $source_en, $target_en, admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php'), 
            admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php'), home_url());

    wp_mail($to, $subject, $body);
    
    $meta = get_user_meta($user->ID, $wpdb->prefix . 'strings_notification', 1);
    $meta[$source][$target] = 1;        
    update_user_meta($user->ID, $wpdb->prefix . 'strings_notification', $meta);
}

function icl_st_reset_current_trasnslator_notifications(){
    global $current_user, $wpdb;
    $mkey = $wpdb->prefix . 'strings_notification'; 
    if(!empty($current_user->$mkey)){
        update_user_meta(get_current_user_id(), $mkey, array());
    }
}

function _icl_translation_send_strings_icanlocalize($string_ids, $target) {
    global $wpdb, $sitepress, $sitepress_settings;
                                                                        
    if(!$sitepress_settings['st']['strings_language']) 
        $sitepress_settings['st']['strings_language'] = $sitepress->get_default_language();
    
    $target_details = $sitepress->get_language_details($target);
    
    // get all the untranslated strings
    $untranslated = array();
    foreach($string_ids as $st_id) {        
        $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}icl_string_translations WHERE string_id=%d and language=%s", $st_id, $target));
        if ($status != ICL_STRING_TRANSLATION_COMPLETE) {
            $untranslated[] = $st_id;
        }
    }
    
    if (sizeof($untranslated) >  0) {
        // Something to translate.
        $target_for_server = array(ICL_Pro_Translation::server_languages_map($target_details['english_name'])); //filter some language names to match the names on the server
        $data = array(
            'url'=>'', 
            'target_languages' => $target_for_server,
        );
        $string_values = array();
        foreach($untranslated as $st_id) {
            
            $string = $wpdb->get_row($wpdb->prepare("SELECT context, name, value FROM {$wpdb->prefix}icl_strings WHERE id=%d", $st_id));
            $string_values[$st_id] = $string->value;
            $data['contents']['string-'.$st_id.'-context'] = array(
                    'translate'=>0,
                    'data'=>base64_encode(htmlspecialchars($string->context)),
                    'format'=>'base64',
            );
            $data['contents']['string-'.$st_id.'-name'] = array(
                    'translate'=>0,
                    'data'=>base64_encode(htmlspecialchars($string->name)),
                    'format'=>'base64',
            );
            $data['contents']['string-'.$st_id.'-value'] = array(
                    'translate'=>1,
                    'data'=>base64_encode(htmlspecialchars($string->value)),
                    'format'=>'base64',
            );
            
        }

        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
        
        $orig_lang = $sitepress->get_language_details($sitepress_settings['st']['strings_language']);
        $orig_lang_for_server = ICL_Pro_Translation::server_languages_map($orig_lang['english_name']);

        $timestamp = date('Y-m-d H:i:s');
        
        $xml = $iclq->build_cms_request_xml($data, $orig_lang_for_server);
        $args = array(
            'xml'           => $xml,
            'title'         => "String translations",
            'to_languages'  =>  $target_for_server,
            'orig_language' => $orig_lang_for_server
            
        );
        
        $res = $iclq->send_request($args);

        if($res > 0){
            foreach($string_values as $st_id => $value){
                $wpdb->insert($wpdb->prefix.'icl_string_status', 
                    array('rid'=>$res, 'string_translation_id'=>$st_id, 'timestamp'=>$timestamp, 'md5'=>md5($value))); //insert rid
            }
            $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid'=>$res,
                                                                     'origin'=>$orig_lang['code'],
                                                                     'target'=>$target,
                                                                     'status'=>CMS_REQUEST_WAITING_FOR_PROJECT_CREATION));
            return $res;
        }else{
            return 0;
            
        }
    }
}

function icl_decode_translation_status_id($status){
    switch($status){
        case CMS_TARGET_LANGUAGE_CREATED: $st = __('Waiting for translator','wpml-string-translation');break;
        case CMS_TARGET_LANGUAGE_ASSIGNED: $st = __('In progress','wpml-string-translation');break; 
        case CMS_TARGET_LANGUAGE_TRANSLATED: $st = __('Translation received','wpml-string-translation');break;
        case CMS_TARGET_LANGUAGE_DONE: $st = __('Translation complete','wpml-string-translation');break;
        case CMS_REQUEST_FAILED: $st = __('Request failed','wpml-string-translation');break;
        default: $st = __('Not translated','wpml-string-translation');
    }
    
    return $st;
}

function icl_translation_get_string_translation_status($string_id) {
    global $wpdb;
    $status = $wpdb->get_var($wpdb->prepare("
            SELECT
                MIN(cs.status) 
            FROM
                {$wpdb->prefix}icl_core_status cs
            JOIN 
               {$wpdb->prefix}icl_string_status ss
            ON
               ss.rid = cs.rid
            WHERE
                ss.string_translation_id=%d
            ", $string_id    
            ));
    
    if ($status === null){
        return "";
    }
    
    $status = icl_decode_translation_status_id($status);
    
    return $status;
        
}

function icl_translation_send_untranslated_strings($target_languages) {
    global $wpdb;
    $untranslated = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}icl_strings WHERE status <> %d", ICL_STRING_TRANSLATION_COMPLETE));
    
    icl_translation_send_strings($untranslated, $target_languages);
    
}

function icl_is_string_translation($translation) {
    // determine if the $translation data is for string translation.
    
    foreach($translation as $key => $value) {
        if($key == 'body' or $key == 'title') {
            return false;
        }
        if (preg_match("/string-.*?-value/", $key)){
            return true;
        }
    }
    
    // if we get here assume it's not a string.
    return false;
    
}

function icl_translation_add_string_translation($rid, $translation, $lang_code){
    global $wpdb, $sitepress_settings, $sitepress;
    foreach($translation as $key => $value) {
        if (preg_match("/string-(.*?)-value/", $key, $match)){
            $string_id = $match[1];
            
            $md5_when_sent = $wpdb->get_var($wpdb->prepare("SELECT md5 FROM {$wpdb->prefix}icl_string_status 
                WHERE string_translation_id=%d AND rid=%d", $string_id, $rid));
            $current_string_value = $wpdb->get_var($wpdb->prepare("SELECT value FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id));
            if ($md5_when_sent == md5($current_string_value)) {
                $status = ICL_STRING_TRANSLATION_COMPLETE;
            } else {
                $status = ICL_STRING_TRANSLATION_NEEDS_UPDATE;
            }
            $value = str_replace ( '&#0A;', "\n", $value );
            icl_add_string_translation($string_id, $lang_code, html_entity_decode($value), $status, 0);
        }
    }

    // update translation status
    $wpdb->update($wpdb->prefix.'icl_core_status', array('status'=>CMS_TARGET_LANGUAGE_DONE), array('rid'=>$rid, 'target'=>$lang_code));
    
    return true;
}

function icl_st_get_pending_string_translations_stats(){
    global $wpdb, $current_user, $sitepress_settings;
    get_currentuserinfo();
    
    $user_lang_pairs = get_user_meta($current_user->ID, $wpdb->prefix.'language_pairs', true);    
    
    $stats = array();
    
    if(!empty($user_lang_pairs[$sitepress_settings['st']['strings_language']])){
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT COUNT(id) AS c, language 
            FROM {$wpdb->prefix}icl_string_translations 
            WHERE status=%d AND language IN ('".join("','", array_keys($user_lang_pairs[$sitepress_settings['st']['strings_language']]))."')
                    AND (translator_id IS NULL or translator_id > 0)
            GROUP BY language
            ORDER BY c DESC
            ",
            ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR    
        ));
        
        foreach($results as $r){
            $_stats[$r->language] = $r->c;
        }
        
        foreach($user_lang_pairs[$sitepress_settings['st']['strings_language']] as $lang=>$one){
            $stats[$lang] = isset($_stats[$lang]) ? $_stats[$lang] : 0;
        }
        
    }
    
    
    return $stats;
}

function icl_st_is_translator(){
    return current_user_can('translate') && !current_user_can('manage_options');
}

function icl_st_debug($str){
    trigger_error($str, E_USER_WARNING);
}
