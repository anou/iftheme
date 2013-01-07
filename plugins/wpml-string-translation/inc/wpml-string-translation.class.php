<?php
  
class WPML_String_Translation{
    
    private $messages = array();
    
    function __construct(){
        global $icl_st_string_translation_statuses;
        require WPML_ST_PATH . '/inc/functions.php';
        require WPML_ST_PATH . '/inc/wpml-string-shortcode.php';
        include WPML_ST_PATH . '/inc/slug-translation.php';    
                                                           
        add_action('plugins_loaded', array('WPML_Slug_Translation','setup'));                                                                      
                                                           
        add_action('init', array($this,'init'));           
        add_action('init', array('WPML_Slug_Translation','init'));           
        
        add_action('icl_ajx_custom_call', array($this, 'ajax_calls'), 10, 2);
    }
    
    function __destruct(){
        
    }
    
    function init(){        
        global $sitepress_settings;

        if(is_admin()){
            wp_enqueue_style('thickbox');
            wp_enqueue_script('jquery');
            wp_enqueue_script('thickbox');
        }
        
        
        if(is_admin()){            
            require_once WPML_ST_PATH . '/inc/auto-download-locales.php';
            global $WPML_ST_MO_Downloader;
            $WPML_ST_MO_Downloader = new WPML_ST_MO_Downloader;
        }
        
        $this->plugin_localization();
        
        // Check if WPML is active. If not display warning message and not load Sticky links
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE){
            if ( !function_exists('is_multisite') || !is_multisite() ) {
                add_action('admin_notices', array($this, '_no_wpml_warning'));
            }
            return false;            
        }elseif(version_compare(ICL_SITEPRESS_VERSION, '2.0.5', '<')){
            add_action('admin_notices', array($this, '_old_wpml_warning'));
            return false;            
        }        
        
        
        
        add_action('admin_menu', array($this,'menu'));           
        
        add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2); 
        
        
        if(is_admin() && isset($_GET['page']) && ($_GET['page'] == WPML_ST_FOLDER . '/menu/string-translation.php' || $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/theme-localization.php')){
            wp_enqueue_script('colorpicker');
            wp_enqueue_script('wpml-st-scripts', WPML_ST_URL . '/res/js/scripts.js', array(), WPML_ST_VERSION);
            wp_enqueue_style('wpml-st-styles', WPML_ST_URL . '/res/css/style.css', array(), WPML_ST_VERSION); 
        } 
        
        if(isset($sitepress_settings['theme_localization_type']) && $sitepress_settings['theme_localization_type'] == 1){
            add_action('icl_custom_localization_type', array($this, 'localization_type_ui'));    
        }
        
        add_action('wp_ajax_icl_tl_rescan', array($this, 'tl_rescan'));
        add_action('wp_ajax_icl_tl_rescan_p', array($this, 'tl_rescan_p'));
        add_action('wp_ajax_icl_st_pop_download', array($this, 'plugin_po_file_download'));
        add_action('wp_ajax_icl_st_cancel_local_translation', array($this, 'cancel_local_translation'));
        add_action('wp_ajax_icl_st_string_status', array($this, 'icl_st_string_status'));
        
        // add message to WPML dashboard widget
        add_action('icl_dashboard_widget_content', array($this, 'icl_dashboard_widget_content'));        
        
    }
    
    function _no_wpml_warning(){
        ?>
        <div class="message error"><p><?php printf(__('WPML String Translation is enabled but not effective. It requires <a href="%s">WPML</a> in order to work.', 'wpml-string-translation'), 
            'http://wpml.org/'); ?></p></div>
        <?php
    }
    
    function _old_wpml_warning(){
        ?>
        <div class="message error"><p><?php printf(__('WPML String Translation is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'wpml-string-translation'), 
            'http://wpml.org/'); ?></p></div>
        <?php
    }

    function add_message($text, $type='updated'){
        $this->messages[] = array('type'=>$type, 'text'=>$text);        
    }
    
    function show_messages(){
        if(!empty($this->messages)){            
            foreach($this->messages as $m){
                printf('<div class="%s fade"><p>%s</p></div>', $m['type'], $m['text']);
            }
        }
    }
    
    function ajax_calls($call, $data){
        global $sitepress;
        switch($call){        
            
            case 'icl_st_save_translation':
                    $icl_st_complete = isset($data['icl_st_translation_complete'])?$data['icl_st_translation_complete']:ICL_STRING_TRANSLATION_NOT_TRANSLATED;
                    if ( get_magic_quotes_gpc() ){
                        $data = stripslashes_deep( $data );         
                    }
                    if(icl_st_is_translator()){
                        $translator_id = get_current_user_id() > 0 ? get_current_user_id() : null;
                    }else{
                        $translator_id = null;    
                    }
                    echo icl_add_string_translation($data['icl_st_string_id'], $data['icl_st_language'], stripslashes($data['icl_st_translation']), $icl_st_complete, $translator_id);
                    echo '|';
                    global $icl_st_string_translation_statuses;
                    
                    $ts = icl_update_string_status($data['icl_st_string_id']);
                    
                    if(icl_st_is_translator()){
                        $ts = icl_get_relative_translation_status($data['icl_st_string_id'], $translator_id);                        
                    }
                    
                    echo $icl_st_string_translation_statuses[$ts];    
                break;
            case 'icl_st_delete_strings':
                $arr = explode(',',$data['value']);
                __icl_unregister_string_multi($arr);
                break;
            /*
            case 'icl_st_send_strings':
                $arr = explode(',',$data['strings']);
                icl_translation_send_strings($arr, explode('#',$data['languages']));
                echo '1';
                break;    
            case 'icl_st_send_strings_all':
                icl_translation_send_untranslated_strings(explode(',',$data['languages']));
                echo '1';
                break;    
            */        
            // OBSOLETE?
            case 'icl_st_option_writes_form':
                if(!empty($data['icl_admin_options'])){
                    icl_register_admin_options($data['icl_admin_options']);
                    echo '1|';        
                }else{
                    echo '0' . __('No strings selected', 'wpml-string-translation');
                }
                break;
            
            // OBSOLETE?
            case 'icl_st_ow_export':
                // filter empty options out
                do{
                    list($data['icl_admin_options'], $empty_found) = _icl_st_filter_empty_options_out($data['icl_admin_options']);
                }while($empty_found);
                
                if(!empty($data['icl_admin_options'])){
                    
                    foreach($data['icl_admin_options'] as $k => $opt){
                        if(!$opt){
                            unset($data['icl_admin_options'][$k]);
                        }
                    }
                    
                    $message = __('Include the following PHP in your code. <em>functions.php</em> would be a good place.', 'wpml-string-translation')
                    . "<textarea wrap=\"soft\">&lt;?php
        if (function_exists('wpml_register_admin_strings')) {
            wpml_register_admin_strings('".serialize($data['icl_admin_options'])."');
        }
        ?&gt;</textarea>";
                }else{
                    $error = 1;
                    $message = __('Error: no strings selected', 'wpml-string-translation');
                }
                echo json_encode(array('error'=>0, 'message'=>$message));
                break; 
                
                       
        }
    }
    
    function menu(){
        global $sitepress_settings, $wpdb;
        
        if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified'])){
            return;
        }
        
        if(current_user_can('manage_options')){
            $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH).'/menu/languages.php');
            if(current_user_can('translate')){
                $_cap = 'translate';
            }else{
                $_cap = 'edit_others_pages';
            }
            
            add_submenu_page($top_page, 
                __('String Translation','wpml-string-translation'), __('String Translation','wpml-string-translation'),
                $_cap, WPML_ST_FOLDER.'/menu/string-translation.php');                        
        }else{
            $user_lang_pairs = get_user_meta(get_current_user_id(), $wpdb->prefix.'language_pairs', true);    
            if(isset($sitepress_settings['st']['strings_language']) && !empty($user_lang_pairs[$sitepress_settings['st']['strings_language']])){
                add_menu_page(__('String Translation','wpml-string-translation'), 
                    __('String Translation','wpml-string-translation'), 'translate', 
                    WPML_ST_FOLDER.'/menu/string-translation.php',null, ICL_PLUGIN_URL . '/res/img/icon16.png');
            }
            
        }
    }
    
    function plugin_action_links($links, $file){
        $this_plugin = basename(WPML_ST_PATH) . '/plugin.php';
        if($file == $this_plugin) {
            $links[] = '<a href="admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php">' . 
                __('Configure', 'wpml-string-translation') . '</a>';
        }
        return $links;
    }
    
    function localization_type_ui(){
        global $sitepress_settings;
        
        $theme_localization_stats = get_theme_localization_stats();
        $plugin_localization_stats = get_plugin_localization_stats();
        
        include WPML_ST_PATH . '/menu/theme-localization-ui.php';

    }

    function tl_rescan(){
        global $wpdb, $sitepress_settings;
        
        $scan_stats = icl_st_scan_theme_files();                
        
        if(isset($_POST['icl_load_mo']) && $_POST['icl_load_mo']){
            $mo_files = icl_st_get_mo_files(TEMPLATEPATH);
            foreach((array)$mo_files as $m){
                $i = preg_match('#[-]?([a-z_]+)\.mo$#i', $m, $matches);
                if($i && $lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='".$matches[1]."'")){
                    $tr_pairs = icl_st_load_translations_from_mo($m);
                    foreach($tr_pairs as $original=>$translation){
                        foreach($sitepress_settings['st']['theme_localization_domains'] as $tld){
                            $string_id = icl_get_string_id($original, 'theme ' . $tld);                            
                            if($string_id){
                                break;
                            }
                        }                        
                        if(!$wpdb->get_var{"SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string_id} AND language='{$lang}'"}){
                            icl_add_string_translation($string_id, $lang, $translation, ICL_STRING_TRANSLATION_COMPLETE);
                        }
                    }
                }
            }
        }
        
        echo '1|'.$scan_stats;
        exit;
        
    }
    
    function tl_rescan_p(){
        global $wpdb, $sitepress_settings;
        
        set_time_limit(0);
        if(preg_replace('#M$#', '', ini_get('memory_limit')) < 128) ini_set('memory_limit', '128M');        
        $plugins = array();
        if(!empty($_POST['plugin']))
        foreach($_POST['plugin'] as $plugin){
            $plugins[] = array('file'=>$plugin, 'mu'=>0); // regular plugins
        }
        if(!empty($_POST['mu-plugin']))
        foreach($_POST['mu-plugin'] as $plugin){
            $plugins[] = array('file'=>$plugin, 'mu'=>1); //mu plugins
        }    
        $scan_stats = '';
        foreach($plugins as $p){
            $plugin = $p['file'];
            
            if(false !== strpos($plugin, '/') && !$p['mu']){
                $plugin = dirname($plugin);
            }
            if($p['mu']){
                $plugin_path = WPMU_PLUGIN_DIR . '/' . $plugin;    
            }else{
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;    
            }
            
            $scan_stats .= icl_st_scan_plugin_files($plugin_path);                
            
            if($_POST['icl_load_mo'] && !$p['mu']){
                $mo_files = icl_st_get_mo_files($plugin_path);
                foreach($mo_files as $m){
                    $i = preg_match('#[-]([a-z_]+)\.mo$#i', $m, $matches);
                    if($i && $lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='".$matches[1]."'")){
                        $tr_pairs = icl_st_load_translations_from_mo($m);
                        foreach($tr_pairs as $original=>$translation){
                            $string_id = icl_get_string_id($original, 'plugin ' . basename($plugin_path));                            
                            if(!$wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string_id} AND language='{$lang}'")){
                                icl_add_string_translation($string_id, $lang, $translation, ICL_STRING_TRANSLATION_COMPLETE);
                            }
                        }
                    }
                }
            }
            
        }
        echo '1|' . $scan_stats;        
        exit;
    }
    
    function icl_dashboard_widget_content(){
        global $wpdb;
        ?>
        
        <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('String translation', 'wpml-string-translation') ?></a></div>
        <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;">
            <p><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'wpml-string-translation') ?></p>
            <?php
                $strings_need_update = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}icl_strings WHERE status <> 1");
            ?>
            <?php if ($strings_need_update == 1): ?>
                <p><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'wpml-string-translation'), 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;status=0') ?></b></p>
            <?php elseif ($strings_need_update): ?>
                <p><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'wpml-string-translation'), 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;status=0', $strings_need_update) ?></b></p>
            <?php else: ?>
                <p><?php echo __('All strings are up to date.', 'wpml-string-translation'); ?></p>
            <?php endif; ?>
            
            <p>
            <a class="button secondary" href="<?php echo 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'wpml-string-translation') ?></a>
            </p>
        </div>
        <?php
    }
    
    // Localization
    function plugin_localization(){
        load_plugin_textdomain( 'wpml-string-translation', false, WPML_ST_FOLDER . '/locale');
    }
    
    function plugin_po_file_download($file = false, $recursion = 0){
        global $__wpml_st_po_file_content;
        
        if(empty($file) && !empty($_GET['file'])){
            $file = WP_PLUGIN_DIR . '/' . $_GET['file'];    
        }        
        if(empty($file)) return;
        
        if(is_null($__wpml_st_po_file_content)){
            $__wpml_st_po_file_content = '';    
        }
        
        require_once ICL_PLUGIN_PATH . '/inc/potx.php';        
                        
        if(is_file($file) && WP_PLUGIN_DIR == dirname($file)){               

            _potx_process_file($file, 0, '__pos_scan_store_results','_potx_save_version', POTX_API_7);                                
        }else{
            
            if(!$recursion){
                $file = dirname($file);
            }
            
            if(is_dir($file)){    
                $dh = opendir($file);    
                while(false !== ($f = readdir($dh))){
                    if(0 === strpos($f, '.')) continue;
                    $this->plugin_po_file_download($file . '/' . $f, $recursion+1);
                }
            }elseif(preg_match('#(\.php|\.inc)$#i', $file)){     
                _potx_process_file($file , 0, '__pos_scan_store_results','_potx_save_version', POTX_API_7);                    
            }
        }

        if(!$recursion){
            $po = "";
            $po .= '# This file was generated by WPML' . PHP_EOL;
            $po .= '# WPML is a WordPress plugin that can turn any WordPress site into a full featured multilingual content management system.' . PHP_EOL;    
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
            
            $po .= $__wpml_st_po_file_content; 
            
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Transfer-Encoding: binary');
            header("Content-Disposition: attachment; filename=\"".basename($file).".po\"");
            header("Content-Length: ". strlen($po));
            echo $po;
            exit(0);
        }
        
    }
    
    function estimate_word_count($string, $lang_code){
        $__asian_languages = array('ja', 'ko', 'zh-hans', 'zh-hant', 'mn', 'ne', 'hi', 'pa', 'ta', 'th');
        $words = 0;
        if(in_array($lang_code, $__asian_languages)){
            $words += strlen(strip_tags($string)) / 6;
        } else {
            $words += count(explode(' ',strip_tags($string)));
        }
        return (int)$words;
    }
    
    function cancel_local_translation(){
        global $wpdb;
        $id = $_POST['id'];
        $string_id = $wpdb->get_var($wpdb->prepare("SELECT string_id FROM {$wpdb->prefix}icl_string_translations WHERE id=%d", $id));
        $wpdb->update($wpdb->prefix . 'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NOT_TRANSLATED), array('id'=>$id));
        icl_update_string_status($string_id);
        echo json_encode(array('string_id'=>$string_id));
        exit;
    }
    
    function icl_st_string_status(){
        global $wpdb, $icl_st_string_translation_statuses;
        $string_id = $_POST['string_id'];
        echo $icl_st_string_translation_statuses[($wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}icl_strings WHERE id=%d", $string_id)))];
        exit;
    }
    
}

function __pos_scan_store_results($string, $domain, $file, $line){
    global $__wpml_st_po_file_content;
    static $strings = array();
    
    //avoid duplicates     
    if(isset($strings[$domain][$string])){
       return false; 
    }
    $strings[$domain][$string] = true;
    
    $file = @file($file);
    if(!empty($file)){                        
        $__wpml_st_po_file_content .= PHP_EOL;
        $__wpml_st_po_file_content .= '# ' . @trim($file[$line-2])  . PHP_EOL;
        $__wpml_st_po_file_content .= '# ' . @trim($file[$line-1])  . PHP_EOL;
        $__wpml_st_po_file_content .= '# ' . @trim($file[$line])  . PHP_EOL;
    }
    
    //$__wpml_st_po_file_content .= 'msgid "'.str_replace('"', '\"', $string).'"' . PHP_EOL;
    $__wpml_st_po_file_content .= PHP_EOL;
    $__wpml_st_po_file_content .= 'msgid "' . $string . '"' . PHP_EOL;
    $__wpml_st_po_file_content .= 'msgstr ""' . PHP_EOL;
    
}