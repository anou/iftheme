<?php
  
class WPML_Translation_Management{
    
    function __construct(){
        add_action('init', array($this,'init'));           
    }
    
    function __destruct(){
        
    }
    
    function init(){
        
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
        
        if(is_admin()){        
            add_filter('icl_menu_main_page', array($this, '_icl_menu_main_page'));
            add_action('icl_wpml_top_menu_added', array($this, '_icl_hook_top_menu'));        
            add_action('admin_menu', array($this,'menu'));               
            add_action('admin_menu', array($this,'menu_fix_order'), 999); // force 'Translations' at the end
            
            add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2); 
            
            if(!defined('DOING_AJAX')){
                wp_enqueue_script('wpml-tm-scripts', WPML_TM_URL . '/res/js/scripts.js', array('jquery'), WPML_TM_VERSION);
                wp_enqueue_style('wpml-tm-styles', WPML_TM_URL . '/res/css/style.css', array(), WPML_TM_VERSION); 
                wp_enqueue_style('wpml-tm-queue', WPML_TM_URL . '/res/css/translations-queue.css', array(), WPML_TM_VERSION); 
            }
            
            add_action( 'admin_print_footer_scripts', array($this, 'wp_tiny_mce_preload_dialogs'), 30 );
            
        
            add_action('icl_dashboard_widget_content_top', array($this, 'icl_dashboard_widget_content'));    
            
            add_action('icl_post_languages_options_before', array($this, 'icl_post_languages_options_before'));
            
            // Add a nice warning message if the user tries to edit a post manually and it's actually in the process of being translated
            global $pagenow, $sitepress;
            if(($pagenow == 'post-new.php' || $pagenow == 'post.php') && (isset($_GET['trid']) || isset($_GET['post']) ) && isset($_GET['lang'])){
                add_action('admin_notices', array($this, '_warn_editing_icl_translation'));    
            }
            
            add_action('wp_ajax_dismiss_icl_side_by_site', array($this, 'dismiss_icl_side_by_site'));
            add_action('wp_ajax_icl_tm_parent_filter', array($this, '_icl_tm_parent_filter'));
            add_action('wp_ajax_icl_tm_toggle_promo', array($this, '_icl_tm_toggle_promo'));
            
            add_action('admin_footer', array($this, '_icl_nonce_for_ajx'));
        }        
        
    }
    
    function _no_wpml_warning(){
        ?>
        <div class="message error"><p><?php printf(__('WPML Translation Management is enabled but not effective. It requires <a href="%s">WPML</a> in order to work.', 'wpml-translation-management'), 
            'http://wpml.org/'); ?></p></div>
        <?php
    }
    
    function _old_wpml_warning(){
        ?>
        <div class="message error"><p><?php printf(__('WPML Translation Management is enabled but not effective. It is not compatible with  <a href="%s">WPML</a> versions prior 2.0.5.', 'wpml-translation-management'), 
            'http://wpml.org/'); ?></p></div>
        <?php
    }    
    
    function _icl_menu_main_page($page){
        return basename(WPML_TM_PATH) . '/menu/main.php';
    }
    
    function _icl_hook_top_menu(){
        add_submenu_page(basename(WPML_TM_PATH) . '/menu/main.php', 
            __('Translation Management','wpml-translation-management'), 
            __('Translation Management','wpml-translation-management'),
            'manage_options', basename(WPML_TM_PATH) . '/menu/main.php');
    }
    
    function menu(){
        global $sitepress, $iclTranslationManagement;
        
        if (method_exists($sitepress, 'setup') && $sitepress->setup() && 1 < count($sitepress->get_active_languages())) {
            
            $current_translator = $iclTranslationManagement->get_current_translator();
            if(!empty($current_translator->language_pairs) || current_user_can('manage_options')){
                if(current_user_can('manage_options')){
                    $top_page = apply_filters('icl_menu_main_page', basename(ICL_PLUGIN_PATH).'/menu/languages.php');
                    add_submenu_page(basename(WPML_TM_PATH) . '/menu/main.php', 
                    __('Translations','wpml-translation-management'), __('Translations','wpml-translation-management'),
                    'manage_options', WPML_TM_FOLDER.'/menu/translations-queue.php');
                } else {
                    add_menu_page(__('Translation interface','wpml-translation-management'), 
                        __('Translation interface','wpml-translation-management'), 'translate', 
                        WPML_TM_FOLDER.'/menu/translations-queue.php',null, ICL_PLUGIN_URL . '/res/img/icon16.png');
                }
            }
        }
                    
    }
    
    function menu_fix_order(){
        global $submenu;
        
        if(!isset($submenu[WPML_TM_FOLDER . '/menu/main.php'])) return;
        
        // Make sure 'Translations' stays at the end        
        $found = false;
        foreach($submenu[WPML_TM_FOLDER . '/menu/main.php'] as $id => $sm){            
            if($sm[2] == WPML_TM_FOLDER . '/menu/translations-queue.php'){
                $found = $sm;
                unset($submenu[WPML_TM_FOLDER . '/menu/main.php'][$id]);
                break;
            }                
        }
        if($found){
            $submenu[WPML_TM_FOLDER . '/menu/main.php'][] = $found;
        }
    }
  
    function _warn_editing_icl_translation(){
        global $wpdb, $iclTranslationManagement;
        
        if(isset($_GET['trid'])){
            $translation_id = $wpdb->get_var($wpdb->prepare("
                    SELECT t.translation_id 
                        FROM {$wpdb->prefix}icl_translations t
                        JOIN {$wpdb->prefix}icl_translation_status s ON t.translation_id = s.translation_id
                        WHERE t.trid=%d AND t.language_code=%s"
                , $_GET['trid'], $_GET['lang']));            
        }else{
            $post_type = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $_GET['post']));
            $translation_id = $wpdb->get_var($wpdb->prepare("
                    SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s AND language_code='%s'"
                , $_GET['post'], 'post_' . $post_type, $_GET['lang']));            
        }
        
        if($translation_id){
            $translation_status = $wpdb->get_var($wpdb->prepare("
                SELECT status FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d"
            , $translation_id));  
            if(!is_null($translation_status) && $translation_status != ICL_TM_DUPLICATE && $translation_status < ICL_TM_COMPLETE){
                echo '<div class="error fade"><p id="icl_side_by_site">'. 
                    sprintf(__('<strong>Warning:</strong> You are trying to edit a translation that is currently in the process of being added using WPML.' , 'wpml-translation-management')) . '<br /><br />'.
                    sprintf(__('Please refer to the <a href="%s">Translation management dashboard</a> for the exact status of this translation.' , 'wpml-translation-management'),
                    admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&')) . '</p></div>';    
            }else{
                if($iclTranslationManagement->settings['doc_translation_method'] == ICL_TM_TMETHOD_EDITOR){
                ?>
                <div class="error">
                    <p><?php _e('<strong>Warning:</strong> You are trying to edit a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.' , 'wpml-translation-management')?></p>
                </div>
                <?php
                }
            }
        }else{
            if($iclTranslationManagement->settings['doc_translation_method'] == ICL_TM_TMETHOD_EDITOR){
            ?>
            <div class="error">
                <p><?php _e('<strong>Warning:</strong> You are trying to add a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.' , 'wpml-translation-management')?></p>
                <p><?php printf(__('You should use <a href="%s">Translation management dashboard</a> to send the original document to translation.' , 'wpml-translation-management'), admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php')); ?>
                </p>
            </div>
            <?php
            }
        }
        
    }
        
    function dismiss_icl_side_by_site(){
        global $iclTranslationManagement;
        $iclTranslationManagement->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
        $iclTranslationManagement->save_settings();
        exit;        
    }
        
    function icl_dashboard_widget_content(){
        global $wpdb, $ICL_Pro_Translation, $sitepress_settings, $sitepress, $current_user;
        get_currentuserinfo();
        $docs_sent = 0;
        $docs_completed = 0;
        $docs_waiting = 0;
        $docs_statuses = $wpdb->get_results($wpdb->prepare("SELECT status FROM {$wpdb->prefix}icl_translation_status WHERE status > %d", ICL_TM_NOT_TRANSLATED));
        foreach ($docs_statuses as $doc_status) {
            $docs_sent += 1;
            if ($doc_status->status == ICL_TM_COMPLETE) {
                $docs_completed += 1;
            } elseif ($doc_status->status == ICL_TM_WAITING_FOR_TRANSLATOR
                    || $doc_status->status == ICL_TM_IN_PROGRESS) {
                $docs_waiting += 1;
            }
        }        
        include WPML_TM_PATH . '/menu/_icl_dashboard_widget.php';
    }
    
    function icl_post_languages_options_before(){
        global $sitepress_settings, $icl_meta_box_globals, $iclTranslationManagement, $sitepress, $post;
        
        // Get them from Sitepress::meta_box
        extract($icl_meta_box_globals);
        
        $translations_count = count($translations) - 1;
        $language_count = count($active_languages) - 1;        
        
        // get languages with translators
        $languages_translated = $languages_not_translated = array();
        
        if(!empty($sitepress_settings['icl_lang_status']))
        foreach($sitepress_settings['icl_lang_status'] as $k=>$language_pair){
            if(!is_numeric($k)) continue;
            if($language_pair['from'] == $selected_language && !empty($language_pair['translators'])){
                $languages_translated[] = $language_pair['to'];
                $lang_rates[$language_pair['to']] = $language_pair['max_rate'];
            }
        }
        $languages_not_translated = array_diff(array_keys($active_languages), array_merge(array($selected_language), $languages_translated));
        
        // get pro translations        
        $pro_translations = $iclTranslationManagement->get_element_translations($post->ID, 'post_'.$post->post_type);            
        
        include WPML_TM_PATH . '/menu/_icl_post_menu.php';
    }
    
    function plugin_action_links($links, $file){
        $this_plugin = basename(WPML_TM_PATH) . '/plugin.php';
        if($file == $this_plugin) {
            $links[] = '<a href="admin.php?page='.basename(WPML_TM_PATH) . '/menu/main.php">' . 
                __('Configure', 'wpml-translation-management') . '</a>';
        }
        return $links;
    }

    // Localization
    function plugin_localization(){
        load_plugin_textdomain( 'wpml-translation-management', false, WPML_TM_FOLDER . '/locale');
    }
 
    function wp_tiny_mce_preload_dialogs() {
        // It's not clear why we need this function
        // It was there to fix a javascript error with a plugin
        // Can't remember which plugin.
        // wp_tiny_mce_preload_dialogs is no longer available in WP 3.2 so we need to check for it.
 
        if (isset($_GET['page']) && $_GET['page'] == WPML_TM_FOLDER.'/menu/translations-queue.php' && function_exists('wp_tiny_mce_preload_dialogs')) {
            wp_tiny_mce_preload_dialogs();
        }
    }
    
    //
    function _icl_tm_parent_filter(){
        global $sitepress;
        $sitepress->switch_lang($_POST['lang']);
        if($_POST['type'] == 'page'){                        
            $html = wp_dropdown_pages(array('echo'=>0, 'name'=>'filter[parent_id]', 'selected'=>$_POST['parent_id']));            
        }elseif($_POST['type'] == 'category'){
            $html = wp_dropdown_categories(array('echo'=>0, 'orderby'=>'name', 'name'=>'filter[parent_id]', 'selected'=>$_POST['parent_id']));
        }else{
            $html = '';
        }
        $sitepress->switch_lang();
        
        $html .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        if(is_null($_POST['parent_all']) || $_POST['parent_all']) $checked = ' checked="checked"'; else $checked="";
        $html .= "<label><input type=\"radio\" name=\"filter[parent_all]\" value=\"1\" {$checked} />&nbsp;" . __('Show all items under this parent.', 'wpml-translation-management') . '</label>';
        $html .= "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        if(empty($_POST['parent_all'])) $checked = ' checked="checked"'; else $checked="";
        $html .= "<label><input type=\"radio\" name=\"filter[parent_all]\" value=\"0\" {$checked} />&nbsp;" . __('Show only items that are immediately under this parent.', 'wpml-translation-management') . '</label>';
        
        echo json_encode(array('html'=>$html));
        exit;
        
    }
    
    function _icl_tm_toggle_promo(){
        global $sitepress;
        $iclsettings['dashboard']['hide_icl_promo'] = @intval($_POST['value']);
        $sitepress->save_settings($iclsettings);
        exit;
    }
    
    function _icl_nonce_for_ajx(){
        wp_nonce_field('get_translator_status_nonce', '_icl_nonce_gts');
    }
   
}
