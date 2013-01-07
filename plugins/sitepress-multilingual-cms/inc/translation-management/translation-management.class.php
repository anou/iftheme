<?php
define ( 'ICL_TM_NOT_TRANSLATED', 0);
define ( 'ICL_TM_WAITING_FOR_TRANSLATOR', 1);
define ( 'ICL_TM_IN_PROGRESS', 2);
define ( 'ICL_TM_NEEDS_UPDATE', 3);  //virt. status code (based on needs_update)
define ( 'ICL_TM_DUPLICATE', 9);
define ( 'ICL_TM_COMPLETE', 10);

define('ICL_TM_NOTIFICATION_NONE', 0);
define('ICL_TM_NOTIFICATION_IMMEDIATELY', 1);
define('ICL_TM_NOTIFICATION_DAILY', 2);    

define('ICL_TM_TMETHOD_MANUAL', 0);    
define('ICL_TM_TMETHOD_EDITOR', 1);    
define('ICL_TM_TMETHOD_PRO', 2);    

define('ICL_TM_DOCS_PER_PAGE', 20);    


$asian_languages = array('ja', 'ko', 'zh-hans', 'zh-hant', 'mn', 'ne', 'hi', 'pa', 'ta', 'th');
  
class TranslationManagement{
    
    private $selected_translator = array('ID'=>0);
    private $current_translator = array('ID'=>0);
    public $messages = array();    
    public $dashboard_select = array();
    public $settings;
    public $admin_texts_to_translate = array();
    
    function __construct(){
        add_action('init', array($this, 'init'), 15);
        
        if(isset($_GET['icl_tm_message'])){
            $this->messages[] = array(
                'type' => isset($_GET['icl_tm_message_type']) ? $_GET['icl_tm_message_type'] : 'updated',
                'text'  => $_GET['icl_tm_message']
            );        
        }
        
        add_action('save_post', array($this, 'save_post_actions'), 11, 2); // calling *after* the Sitepress actions
        
        add_action('delete_post', array($this, 'delete_post_actions'), 1, 1); // calling *before* the Sitepress actions
		
		add_action('edit_term',  array($this, 'edit_term'),11, 2); // calling *after* the Sitepress actions
		
        add_action('icl_ajx_custom_call', array($this, 'ajax_calls'), 10, 2);
        add_action('wp_ajax_show_post_content', array($this, '_show_post_content'));
                
        if(isset($_GET['sm']) && ($_GET['sm'] == 'dashboard' || $_GET['sm'] == 'jobs')){@session_start();}
        elseif(isset($_GET['page']) && preg_match('@/menu/translations-queue\.php$@', $_GET['page'])){@session_start();}
        add_filter('icl_additional_translators', array($this, 'icl_additional_translators'), 99, 3);
        
        add_filter('icl_translators_list', array($this, 'icanlocalize_translators_list'));
        
        add_action('user_register', array($this, 'clear_cache'));
        add_action('profile_update', array($this, 'clear_cache'));
        add_action('delete_user', array($this, 'clear_cache'));
        add_action('added_existing_user', array($this, 'clear_cache'));
        add_action('remove_user_from_blog', array($this, 'clear_cache'));
        
        add_action('admin_print_scripts', array($this, '_inline_js_scripts'));
        
        add_action('wp_ajax_icl_tm_user_search', array($this, '_user_search'));
        
    }
    
    function save_settings(){
        global $sitepress;
        $iclsettings['translation-management'] = $this->settings;
        $sitepress->save_settings($iclsettings);    
    }
    
    function init(){
        
        global $wpdb, $current_user, $sitepress_settings, $sitepress, $pagenow;

        $this->settings =& $sitepress_settings['translation-management'];
        
        //logic for syncing comments
        if($sitepress->get_option('sync_comments_on_duplicates')){
            add_action('delete_comment', array($this, 'duplication_delete_comment'));
            add_action('edit_comment', array($this, 'duplication_edit_comment'));
            add_action('wp_set_comment_status', array($this, 'duplication_status_comment'), 10, 2);
            add_action('wp_insert_comment', array($this, 'duplication_insert_comment'), 100);
        }
        
        $this->initial_custom_field_translate_states();
        
        // defaults
        if(!isset($this->settings['notification']['new-job'])) $this->settings['notification']['new-job'] = ICL_TM_NOTIFICATION_IMMEDIATELY;
        if(!isset($this->settings['notification']['completed'])) $this->settings['notification']['completed'] = ICL_TM_NOTIFICATION_IMMEDIATELY;
        if(!isset($this->settings['notification']['resigned'])) $this->settings['notification']['resigned'] = ICL_TM_NOTIFICATION_IMMEDIATELY;
        if(!isset($this->settings['notification']['dashboard'])) $this->settings['notification']['dashboard'] = true;
        if(!isset($this->settings['notification']['purge-old'])) $this->settings['notification']['purge-old'] = 7;
        
        if(!isset($this->settings['custom_fields_translation'])) $this->settings['custom_fields_translation'] = array();
        if(!isset($this->settings['doc_translation_method'])) $this->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
        
        get_currentuserinfo();
        if(isset($current_user->ID)){
            $user = new WP_User($current_user->ID);    
        }
        
        if(empty($user->data)) return;
        
        $ct['translator_id'] =  $current_user->ID;
        $ct['display_name'] =  isset($user->data->display_name) ? $user->data->display_name : $user->data->user_login;
        $ct['user_login'] =  $user->data->user_login;
        $ct['language_pairs'] = get_user_meta($current_user->ID, $wpdb->prefix.'language_pairs', true);    
        if(empty($ct['language_pairs'])) $ct['language_pairs'] = array();
        
        $this->current_translator = (object)$ct;

        $this->load_config_pre_process();            
        $this->load_plugins_wpml_config();
        $this->load_theme_wpml_config();
        $this->load_config_post_process();
                
        if(isset($_POST['icl_tm_action'])){
            $this->process_request($_POST['icl_tm_action'], $_POST);
        }elseif(isset($_GET['icl_tm_action'])){
            $this->process_request($_GET['icl_tm_action'], $_GET);
        }        
        
        //$this->load_plugins_wpml_config();
        //$this->load_theme_wpml_config();
        
        if($GLOBALS['pagenow']=='edit.php'){ // use standard WP admin notices
            add_action('admin_notices', array($this, 'show_messages'));    
        }else{                               // use custom WP admin notices
            add_action('icl_tm_messages', array($this, 'show_messages'));            
        }
        
        if(isset($_GET['page']) && basename($_GET['page']) == 'translations-queue.php' && isset($_GET['job_id'])){
            add_filter('admin_head',array($this, '_show_tinyMCE'));                
        }
                
        
        //if(!isset($this->settings['doc_translation_method'])){        
        if(isset($this->settings['doc_translation_method']) && $this->settings['doc_translation_method'] < 0 ){
            if(isset($_GET['sm']) && $_GET['sm']=='mcsetup' && isset($_GET['src']) && $_GET['src']=='notice'){
                        $this->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
                        $this->save_settings();
            }else{
                add_action('admin_notices', array($this, '_translation_method_notice'));    
            }                        
        }
                
        if(defined('WPML_TM_VERSION') && isset($_GET['page']) && $_GET['page'] == WPML_TM_FOLDER. '/menu/main.php' && isset($_GET['sm']) && $_GET['sm'] == 'translators'){
            $iclsettings =& $sitepress_settings;
            $sitepress->get_icl_translator_status($iclsettings);
            $sitepress->save_settings($iclsettings);
        }
        
        // default settings
        if(empty($this->settings['doc_translation_method']) || !defined('WPML_TM_VERSION')){
            $this->settings['doc_translation_method'] = ICL_TM_TMETHOD_MANUAL;
        }        
    }

    function initial_custom_field_translate_states() {
        global $wpdb;
        
        $cf_keys_limit = 1000; // jic
        $custom_keys = $wpdb->get_col( "
            SELECT meta_key
            FROM $wpdb->postmeta
            GROUP BY meta_key
            ORDER BY meta_key
            LIMIT $cf_keys_limit" );

        $changed = false;
        
        foreach($custom_keys as $cfield) {
            if(empty($this->settings['custom_fields_translation'][$cfield]) || $this->settings['custom_fields_translation'][$cfield] == 0) {
                // see if a plugin handles this field
                $override = apply_filters('icl_cf_translate_state', 'nothing', $cfield);
                switch($override) {
                    case 'nothing':
                        break;
                    
                    case 'ignore':
                        $changed = true;
                        $this->settings['custom_fields_translation'][$cfield] = 3;
                        break;
                    
                    case 'translate':
                        $changed = true;
                        $this->settings['custom_fields_translation'][$cfield] = 2;
                        break;
                    
                    case 'copy':
                        $changed = true;
                        $this->settings['custom_fields_translation'][$cfield] = 1;
                        break;
                }

            }
        }
        if ($changed) {
            $this->save_settings();
        }
    }
    
    function _translation_method_notice(){
        echo '<div class="error fade"><p id="icl_side_by_site">'.sprintf(__('New - side-by-site translation editor: <a href="%s">try it</a> | <a href="#cancel">no thanks</a>.', 'sitepress'),
                admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=mcsetup&src=notice')) . '</p></div>';    
    }
    
    function _show_tinyMCE() {
        wp_print_scripts('editor');
        //add_filter('the_editor', array($this, 'editor_directionality'), 9999);
        add_filter('tiny_mce_before_init', array($this, '_mce_set_direction'), 9999);        
        add_filter('mce_buttons', array($this, '_mce_remove_fullscreen'), 9999);                    
        
        if (version_compare($GLOBALS['wp_version'], '3.1.4', '<=') && function_exists('wp_tiny_mce')) 
        try{
            @wp_tiny_mce();
        } catch(Exception $e) {  /*don't do anything with this */ }
    }    
    
    function _mce_remove_fullscreen($options){
        foreach($options as $k=>$v) if($v == 'fullscreen') unset($options[$k]);
        return $options;
    }
    
    function _inline_js_scripts(){
        // remove fullscreen mode
        if(defined('WPML_TM_FOLDER') && isset($_GET['page']) && $_GET['page'] == WPML_TM_FOLDER . '/menu/translations-queue.php' && isset($_GET['job_id'])){
            ?>
            <script type="text/javascript">addLoadEvent(function(){jQuery('#ed_fullscreen').remove();});</script>
            <?php             
        }
    }
    

    function _mce_set_direction($settings) {
        $job = $this->get_translation_job((int)$_GET['job_id'], false, true);
        if (!empty($job)) {
            $rtl_translation = in_array($job->language_code, array('ar','he','fa'));
            if ($rtl_translation) {
                $settings['directionality'] = 'rtl';
            } else {
                $settings['directionality'] = 'ltr';
            }
        }
        return $settings;
    }

    /*
    function editor_directionality($tag) {
        $job = $this->get_translation_job((int)$_GET['job_id'], false, true);
        $rtl_translation = in_array($job->language_code, array('ar','he','fa'));
        if ($rtl_translation) {
            $dir = 'dir="rtl"';
        } else {
            $dir = 'dir="ltr"';
        }
        return str_replace('<textarea', '<textarea ' . $dir, $tag);
    }
    */
    
    function process_request($action, $data){        
        $data = stripslashes_deep($data);
        switch($action){
            case 'add_translator':
                if(wp_create_nonce('add_translator') == $data['add_translator_nonce']){
                    // Initial adding
                    if (isset($data['from_lang']) && isset($data['to_lang'])) {
                      $data['lang_pairs'] = array();
                      $data['lang_pairs'][$data['from_lang']] = array($data['to_lang'] => 1);
                    }
                    $this->add_translator($data['user_id'], $data['lang_pairs']);
                    $_user = new WP_User($data['user_id']);
                    wp_redirect('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=translators&icl_tm_message='.urlencode(sprintf(__('%s has been added as a translator for this site.','sitepress'),$_user->data->display_name)).'&icl_tm_message_type=updated');                    
                }
                break;
            case 'edit_translator':
                if(wp_create_nonce('edit_translator') == $data['edit_translator_nonce']){
                    $this->edit_translator($data['user_id'], $data['lang_pairs']);
                    $_user = new WP_User($data['user_id']);
                    wp_redirect('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=translators&icl_tm_message='.urlencode(sprintf(__('Language pairs for %s have been edited.','sitepress'),$_user->data->display_name)).'&icl_tm_message_type=updated');
                }
                break;
            case 'remove_translator':
                if(wp_create_nonce('remove_translator') == $data['remove_translator_nonce']){
                    $this->remove_translator($data['user_id']);
                    $_user = new WP_User($data['user_id']);
                    wp_redirect('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=translators&icl_tm_message='.urlencode(sprintf(__('%s has been removed as a translator for this site.','sitepress'),$_user->data->display_name)).'&icl_tm_message_type=updated');
                }                
                break;
            case 'edit':
                $this->selected_translator['ID'] = intval($data['user_id']);
                break;
            case 'dashboard_filter':
                $_SESSION['translation_dashboard_filter'] = $data['filter'];
                wp_redirect('admin.php?page='.WPML_TM_FOLDER . '/menu/main.php&sm=dashboard');
                break;  
           case 'sort':
                if(isset($data['sort_by'])) $_SESSION['translation_dashboard_filter']['sort_by'] = $data['sort_by'];
                if(isset($data['sort_order'])) $_SESSION['translation_dashboard_filter']['sort_order'] = $data['sort_order'];
                break;
           case 'reset_filters':
                unset($_SESSION['translation_dashboard_filter']);
                break;          
           case 'send_jobs':
                if(isset($data['iclnonce']) && wp_verify_nonce($data['iclnonce'], 'pro-translation-icl')){
                    $this->send_jobs($data);                        
                }   
                break; 
           case 'jobs_filter':
                $_SESSION['translation_jobs_filter'] = $data['filter'];                
                wp_redirect('admin.php?page='.WPML_TM_FOLDER . '/menu/main.php&sm=jobs');
                break;                               
           case 'ujobs_filter':
                $_SESSION['translation_ujobs_filter'] = $data['filter'];
                wp_redirect('admin.php?page='.WPML_TM_FOLDER . '/menu/translations-queue.php');
                break;                                               
           case 'save_translation':
                if(!empty($data['resign'])){
                    $this->resign_translator($data['job_id']);
                    wp_redirect(admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&resigned='.$data['job_id']));
                    exit;
                }else{
                    $this->save_translation($data);    
                }                
                break;
           case 'save_notification_settings':
                $this->settings['notification'] = $data['notification'];                                
                $this->save_settings();
                $this->messages[] = array(
                    'type'=>'updated',
                    'text' => __('Preferences saved.', 'sitepress')
                );
                break;
           case 'create_job':
                global $current_user;
                if(!isset($this->current_translator->ID) && isset($current_user->ID)){
                    $this->current_translator->ID  = $current_user->ID;   
                }
                $data['translator'] = $this->current_translator->ID;
                $job_ids = $this->send_jobs($data);
                wp_redirect('admin.php?page='.WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . array_pop($job_ids));
                break;
           case 'cancel_jobs':
                $ret = $this->cancel_translation_request($data['icl_translation_id']);
                $this->messages[] = array(
                    'type'=>'updated',
                    'text' => __('Translation requests cancelled.', 'sitepress')
                );
                break;
        }
    }
    
    function ajax_calls($call, $data){
        global $wpdb, $sitepress, $sitepress_settings;        
        switch($call){
            /*
            case 'save_dashboard_setting':
                $iclsettings['dashboard'] = $sitepress_settings['dashboard'];
                if(isset($data['setting']) && isset($data['value'])){
                    $iclsettings['dashboard'][$data['setting']] = $data['value'];
                    $sitepress->save_settings($iclsettings);    
                }
                break;
            */
            case 'assign_translator':
            
                $_exp = explode('-', $data['translator_id']);
                $service = isset($_exp[1]) ? $_exp[1] : 'local';
                $translator_id = $_exp[0];                
                if($this->assign_translation_job($data['job_id'], $translator_id, $service)){
                    if($service == 'icanlocalize'){
                        $job = $this->get_translation_job($data['job_id']);
                        global $ICL_Pro_Translation;
                        $ICL_Pro_Translation->send_post($job->original_doc_id, array($job->language_code), $translator_id);
                        foreach($sitepress_settings['icl_lang_status'] as $lp){
                            if($lp['from'] == $job->source_language_code && $lp['to'] == $job->language_code){
                                $contract_id = $lp['contract_id'];
                                $lang_tr_id =  $lp['id']; 
                                break;
                            }
                        }
                        $translator_edit_link = $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/websites/' . $sitepress_settings['site_id']
                        . '/website_translation_offers/' . $lang_tr_id . '/website_translation_contracts/'
                        . $contract_id, array('title' => __('Chat with translator', 'sitepress'), 'unload_cb' => 'icl_thickbox_refresh')) 
                        . esc_html(ICL_Pro_Translation::get_translator_name($translator_id))  . '</a> (ICanLocalize)';                    
                        
                    }else{
                        $translator_edit_link = '<a href="'.$this->get_translator_edit_url($data['translator_id']).'">' . 
                        esc_html($wpdb->get_var($wpdb->prepare("SELECT display_name FROM {$wpdb->users} WHERE ID=%d",$data['translator_id']))) . '</a>';
                    }
                    echo json_encode(array('error'=>0, 'message'=>$translator_edit_link, 'status'=>$this->status2text(ICL_TM_WAITING_FOR_TRANSLATOR), 'service'=>$service));
                }else{
                    echo json_encode(array('error'=>1));
                }
                break;
            case 'icl_cf_translation':
                if(!empty($data['cf'])){
                    foreach($data['cf'] as $k=>$v){
                        $cft[base64_decode($k)] = $v;
                    }
                    $this->settings['custom_fields_translation'] = $cft;
                    $this->save_settings();                    
                }            
                echo '1|';
                break;
            case 'icl_doc_translation_method':
                $this->settings['doc_translation_method'] = intval($data['t_method']);
                $sitepress->save_settings(array('hide_how_to_translate' =>  empty($data['how_to_translate'])));
                $this->save_settings();
                echo '1|';
                break;
            case 'reset_duplication':
                $this->reset_duplicate_flag($_POST['post_id']);
                break;
            case 'set_duplication':
                $this->set_duplicate($_POST['post_id']);
                break;
            case 'make_duplicates':
                $mdata['iclpost'] = array($data['post_id']);
                $langs = explode(',', $data['langs']);
                foreach($langs as $lang){
                    $mdata['duplicate_to'][$lang] = 1;       
                }
                $this->make_duplicates($mdata);
                break;
        }
    }
    
    function show_messages(){
        if(!empty($this->messages)){
            foreach($this->messages as $m){
                echo '<div class="'.$m['type'].' below-h2"><p>' . $m['text'] . '</p></div>';
            }
        }
    }
    
    /* TRANSLATORS */
    /* ******************************************************************************************** */
    function add_translator($user_id, $language_pairs){
        global $wpdb;
        
        $user = new WP_User($user_id);
        $user->add_cap('translate');
        
        $um = get_user_meta($user_id, $wpdb->prefix . 'language_pairs', true);
        if(!empty($um)){
            foreach($um as $fr=>$to){
                if(isset($language_pairs[$fr])){
                    $language_pairs[$fr] = array_merge($language_pairs[$fr], $to);        
                }
                
            }
        }
        
        update_user_meta($user_id, $wpdb->prefix . 'language_pairs',  $language_pairs);
        $this->clear_cache();
    }

    function edit_translator($user_id, $language_pairs){
        global $wpdb;
        $_user = new WP_User($user_id);
        if(empty($language_pairs)){
            $this->remove_translator($user_id);                
            wp_redirect('admin.php?page='.WPML_TM_FOLDER.'/menu/main.php&sm=translators&icl_tm_message='.
                urlencode(sprintf(__('%s has been removed as a translator for this site.','sitepress'),$_user->data->display_name)).'&icl_tm_message_type=updated'); exit; 
        }
        else{
            if(!$_user->has_cap('translate')) $_user->add_cap('translate');
            update_user_meta($user_id, $wpdb->prefix . 'language_pairs',  $language_pairs);    
        }
    }
    
    function remove_translator($user_id){
        global $wpdb;
        $user = new WP_User($user_id);
        $user->remove_cap('translate');
        delete_user_meta($user_id, $wpdb->prefix . 'language_pairs');
        $this->clear_cache();
    }
    
    function is_translator($user_id, $args = array()){
        // $lang_from
        // $lang_to
        // $job_id
        
        //$default_args = array(); 
        //extract($default_args);
        extract($args, EXTR_OVERWRITE);
        
        global $wpdb;
        $user = new WP_User($user_id);
        $is_translator = $user->has_cap('translate');
        
        if(isset($lang_from) && isset($lang_to)){
            $um = get_user_meta($user_id, $wpdb->prefix . 'language_pairs', true);            
            $is_translator = $is_translator && isset($um[$lang_from]) && isset($um[$lang_from][$lang_to]) && $um[$lang_from][$lang_to];
        }
        if(isset($job_id)){
            $translator_id = $wpdb->get_var($wpdb->prepare("
                    SELECT j.translator_id  
                        FROM {$wpdb->prefix}icl_translate_job j 
                        JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                    WHERE job_id = %d AND s.translation_service='local'
                ", $job_id));
                                                                
            //$is_translator = $is_translator && ($translator_id == $user_id || ($translator_id === '0'));
            $is_translator = $is_translator && (($translator_id == $user_id) || empty($translator_id));
                
        }
        
        return $is_translator;
    }
    
    function translator_exists($id, $from, $to, $type = 'local'){
        global $sitepress_settings;
        $exists = false;
        if($type == 'icanlocalize' && !empty($sitepress_settings['icl_lang_status'])){
            foreach($sitepress_settings['icl_lang_status'] as $lpair){
                if($lpair['from'] == $from && $lpair['to'] == $to){
                    if(!empty($lpair['translators'])){
                        foreach($lpair['translators'] as $t){
                            if($t['id'] == $id){
                                $exists = true;
                                break(2);
                            }
                        }
                    }
                }
            }
        }elseif($type == 'local'){
            $exists = $this->is_translator($id, array('lang_from'=>$from, 'lang_to'=>$to));    
        }
        return $exists;
    }
    
    function set_default_translator($id, $from, $to, $type = 'local'){
        global $sitepress, $sitepress_settings;
        $iclsettings['default_translators'] = isset($sitepress_settings['default_translators']) ? $sitepress_settings['default_translators'] : array();
        $iclsettings['default_translators'][$from][$to] = array('id'=>$id, 'type'=>$type);
        $sitepress->save_settings($iclsettings);
    } 
    
    function get_default_translator($from, $to){
        global $sitepress_settings;
        if(isset($sitepress_settings['default_translators'][$from][$to])){
            $dt = $sitepress_settings['default_translators'][$from][$to];    
        }else{
            $dt = array();
        }
        return $dt; 
    }   
    
    public function get_blog_not_translators(){
        global $wpdb;
        $cached_translators = get_option($wpdb->prefix . 'icl_non_translators_cached', array());
        if (!empty($cached_translators)) {
            return $cached_translators;
        }
        $sql = "SELECT u.ID, u.user_login, u.display_name, m.meta_value AS caps
            FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key = '{$wpdb->prefix}capabilities' ORDER BY u.display_name";
        $res = $wpdb->get_results($sql);
        
        $users = array();
        foreach($res as $row){            
            $caps = @unserialize($row->caps);
            if(!isset($caps['translate'])){
                $users[] = $row;    
            }  
        }                                     
        update_option($wpdb->prefix . 'icl_non_translators_cached', $users);
        return $users;
    }

    /**
     * Implementation of 'icl_translators_list' hook
     *
     * @global object $sitepress
     * @param array $array
     * @return array
     */
    public function icanlocalize_translators_list() {  
      global $sitepress_settings, $sitepress;
      
      $lang_status = isset($sitepress_settings['icl_lang_status']) ? $sitepress_settings['icl_lang_status'] : array();
      if (0 != key($lang_status)){
        $buf[] = $lang_status;  
        $lang_status = $buf;    
      }
      
      $translators = array();
      foreach($lang_status as $lpair){
          foreach((array)$lpair['translators'] as $translator){
            $translators[$translator['id']]['name'] = $translator['nickname'];
            $translators[$translator['id']]['langs'][$lpair['from']][] = $lpair['to'];
            $translators[$translator['id']]['type'] = 'ICanLocalize';
            $translators[$translator['id']]['action'] = $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/websites/' . $sitepress_settings['site_id']
                . '/website_translation_offers/' . $lpair['id'] . '/website_translation_contracts/'
                . $translator['contract_id'], array('title' => __('Chat with translator', 'sitepress'), 'unload_cb' => 'icl_thickbox_refresh', 'ar'=>1)) . __('Chat with translator', 'sitepress') . '</a>';        
          }
      }

      return $translators;
    }
    
    public function get_blog_translators($args = array()){
        global $wpdb;
        $args_default = array('from'=>false, 'to'=>false);
        extract($args_default);
        extract($args, EXTR_OVERWRITE);

//        $sql = "SELECT u.ID, u.user_login, u.display_name, u.user_email, m.meta_value AS caps
//                    FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key LIKE '{$wpdb->prefix}capabilities' ORDER BY u.display_name";
//        $res = $wpdb->get_results($sql);

        $cached_translators = get_option($wpdb->prefix . 'icl_translators_cached', array());

        if (empty($cached_translators)) {
            $sql = "SELECT u.ID FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON u.id=m.user_id AND m.meta_key = '{$wpdb->prefix}language_pairs' ORDER BY u.display_name";
            $res = $wpdb->get_results($sql);
            update_option($wpdb->prefix . 'icl_translators_cached', $res);
        } else {
            $res = $cached_translators;
        }

        $users = array();
        foreach($res as $row){
            $user = new WP_User($row->ID);
//            $caps = @unserialize($row->caps);
//            $row->language_pairs = (array)get_user_meta($row->ID, $wpdb->prefix.'language_pairs', true);
            $user->language_pairs = (array)get_user_meta($row->ID, $wpdb->prefix.'language_pairs', true);
//            if(!empty($from) && !empty($to) && (!isset($row->language_pairs[$from][$to]) || !$row->language_pairs[$from][$to])){
//                continue;
//            }
            if(!empty($from) && !empty($to) && (!isset($user->language_pairs[$from][$to]) || !$user->language_pairs[$from][$to])){
                continue;
            }
//            if(isset($caps['translate'])){
//                $users[] = $user;
//            }
            if($user->has_cap('translate')){
                $users[] = $user;
            }
        }
        return $users;
    }
    
    function get_selected_translator(){
        global $wpdb;
        if($this->selected_translator['ID']){
            $user = new WP_User($this->selected_translator['ID']);
            $this->selected_translator['display_name'] =  $user->data->display_name;
            $this->selected_translator['user_login'] =  $user->data->user_login;
            $this->selected_translator['language_pairs'] = get_user_meta($this->selected_translator['ID'], $wpdb->prefix.'language_pairs', true);
        }else{
            $this->selected_translator['ID'] = 0;
        }
        return (object)$this->selected_translator;    
    }
    
    function get_current_translator(){
        return $this->current_translator;
    }
    
    public function get_translator_edit_url($translator_id){
        $url = '';
        if(!empty($translator_id)){
            $url = 'admin.php?page='. WPML_TM_FOLDER .'/menu/main.php&amp;sm=translators&icl_tm_action=edit&amp;user_id='. $translator_id;
        }
        return $url;
    }
    
    public function translators_dropdown($args = array()){
        global $sitepress_settings;
        $args_default = array(
            'from'=>false, 'to'=>false,
            'name'          => 'translator_id',
            'selected'      => 0,
            'echo'          => true,
            'services'      => array('local'),
            'show_service'  => true,
            'disabled'      => false
        );
        extract($args_default);
        extract($args, EXTR_OVERWRITE);

        $translators = array();
        
        if(in_array('icanlocalize', $services)){
            if(empty($sitepress_settings['icl_lang_status'])) $sitepress_settings['icl_lang_status'] = array();
            foreach((array)$sitepress_settings['icl_lang_status'] as $langpair){
                if($from && $from != $langpair['from']) continue;
                if($to && $to != $langpair['to']) continue;
                
                if(!empty($langpair['translators'])){
                    if (1 < count($langpair['translators'])) {
                        $translators[] = (object) array(
                            'ID' => '0-icanlocalize',
                            'display_name' => __('First available', 'sitepress'),
                            'service'       => 'ICanLocalize'
                        );
                    }
                    foreach($langpair['translators'] as $tr){
                        if(!isset($_icl_translators[$tr['id']])){
                            $translators[] = $_icl_translators[$tr['id']] = (object) array(
                                'ID'=>$tr['id'].'-icanlocalize', 
                                'display_name'=>$tr['nickname'],
                                'service'       => 'ICanLocalize'
                            );            
                        }
                    }
                }    
            }
        }

        if(in_array('local', $services)){
            $translators[] = (object) array(
                'ID' => 0,
                'display_name' => __('First available', 'sitepress'),
            );
            $translators = array_merge($translators, $this->get_blog_translators(array('from'=>$from,'to'=>$to)));
        }
        
        ?>
        <select name="<?php echo $name ?>" <?php if($disabled): ?>disabled="disabled"<?php endif;?>>
            <?php foreach($translators as $t):?>
            <option value="<?php echo $t->ID ?>" <?php if($selected==$t->ID):?>selected="selected"<?php endif;?>><?php echo esc_html($t->display_name);
                 ?> <?php if($show_service){ echo '(' ; echo isset($t->service) ? $t->service:  _e('Local', 'sitepress'); echo ')'; } ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function get_number_of_docs_sent($service = 'icanlocalize'){
        global $wpdb;
        $n = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(rid) FROM {$wpdb->prefix}icl_translation_status WHERE translation_service=%s
        ", $service));
        return $n;
    }

    public function get_number_of_docs_pending($service = 'icanlocalize'){
        global $wpdb;
        $n = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(rid) FROM {$wpdb->prefix}icl_translation_status WHERE translation_service=%s AND status < " . ICL_TM_COMPLETE . "
        ", $service));
        return $n;        
    }
    
        
    /* HOOKS */
    /* ******************************************************************************************** */
    function save_post_actions($post_id, $post, $force_set_status = false){
        global $wpdb, $sitepress, $sitepress_settings, $current_user;
        // skip revisions
        if($post->post_type == 'revision'){
            return;
        }
        // skip auto-drafts
        if($post->post_status == 'auto-draft'){
            return;
        }
        // skip autosave
        if(isset($_POST['autosave'])){
            return;
        }

        // is this the original document? 
        if(!empty($_POST['icl_trid'])){
            $is_original = $wpdb->get_var($wpdb->prepare("SELECT source_language_code IS NULL FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND trid=%d", $post_id, $_POST['icl_trid']));
        }
        
        // when a manual translation is added/edited make sure to update translation tables
        if(!empty($_POST['icl_trid']) && !$is_original){        
        
            $trid = $_POST['icl_trid'];
            $lang = $_POST['icl_post_language'];
            
            $res = $wpdb->get_row($wpdb->prepare("
                SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL
            ", $trid));
            $original_post_id = $res->element_id;
            $from_lang = $res->language_code;
            $original_post = get_post($original_post_id);
            $translation_id = $wpdb->get_var($wpdb->prepare("
                SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code='%s'
            ", $trid, $lang));
            $md5 = $this->post_md5($original_post);
            
        
            get_currentuserinfo();
            $user_id = $current_user->ID;
            
            if(!$this->is_translator($user_id, array('lang_from'=>$from_lang, 'lang_to'=>$lang))){
                $this->add_translator($user_id, array($from_lang=>array($lang=>1)));
            }
            
            if($translation_id){
                $translation_package = $this->create_translation_package($original_post_id);                    
                
                list($rid, $update) = $this->update_translation_status(array(
                    'translation_id'        => $translation_id,
                    'status'                => isset($force_set_status) && $force_set_status > 0 ? $force_set_status : ICL_TM_COMPLETE,
                    'translator_id'         => $user_id,
                    'needs_update'          => 0,
                    'md5'                   => $md5,
                    'translation_service'   => 'local',
                    'translation_package'   => serialize($translation_package)
                ));
                if(!$update){
                    $job_id = $this->add_translation_job($rid, $user_id , $translation_package);
                }else{
                    $job_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d GROUP BY rid", $rid));
                }
                
                
                // saving the translation
                $this->save_job_fields_from_post($job_id, $post);
                
                /*    
                $data['complete'] = 1;
                $data['job_id'] = $job_id;
                
                $job = $this->get_translation_job($job_id,1);
                foreach($job->elements as $element){
                    $field_data = '';
                    switch($element->field_type){
                        case 'title':
                            $field_data = $this->encode_field_data($post->post_title, $element->field_format);
                            break;
                        case 'body':
                            $field_data = $this->encode_field_data($post->post_content, $element->field_format);
                            break;
                        case 'excerpt':
                            $field_data = $this->encode_field_data($post->post_excerpt, $element->field_format);
                            break;                           
                        default:
                            if(false !== strpos($element->field_type, 'field-') && !empty($this->settings['custom_fields_translation'])){                                
                                $cf_name = preg_replace('#^field-#', '', $element->field_type);
                                if(isset($this->settings['custom_fields_translation'][$cf_name])){
                                    if($this->settings['custom_fields_translation'][$cf_name] == 1){ //copy
                                        $field_data = get_post_meta($original_post->ID, $cf_name, 1);               
                                        $field_data = $this->encode_field_data($field_data, $element->field_format);                             
                                    }elseif($this->settings['custom_fields_translation'][$cf_name] == 2){ // translate
                                        $field_data = get_post_meta($post->ID, $cf_name, 1);             
                                        $field_data = $this->encode_field_data($field_data, $element->field_format);             
                                    }
                                }
                            }else{
                                // taxonomies                                                 
                                // TBD
                            }
                    }

                    $wpdb->update($wpdb->prefix.'icl_translate', 
                        array('field_data_translated'=>$field_data, 'field_finished'=>1), 
                        array('tid'=>$element->tid)
                    );
                    
                }
                $wpdb->update($wpdb->prefix . 'icl_translate_job', array('translated'=>1), array('job_id'=>$job_id));    
                */
                
            }
        }
                
        // if this is an original post - compute md5 hash and mark for update if neded
        if(!empty($_POST['icl_trid']) && empty($_POST['icl_minor_edit'])){
            
            $needs_update = false;
            $is_original  = false;
            $translations = $sitepress->get_element_translations($_POST['icl_trid'], 'post_' . $post->post_type);    

            foreach($translations as $lang=>$translation){                
                if($translation->original == 1 && $translation->element_id == $post_id){
                    $is_original = true;
                    break;
                }
            }
            if($is_original){
                $md5 = $this->post_md5($post_id);
                foreach($translations as $lang=>$translation){                
                    if(!$translation->original){
                        $emd5 = $wpdb->get_var($wpdb->prepare("SELECT md5 FROM {$wpdb->prefix}icl_translation_status WHERE translation_id = %d", $translation->translation_id));
                        if($md5 != $emd5){
                            
                            $translation_package = $this->create_translation_package($post_id);                    
                            
                            list($rid, $update) = $this->update_translation_status(
                                array(
                                    'translation_id'=>$translation->translation_id, 
                                    'needs_update'=>1, 
                                    'md5'=>$md5, 
                                    'translation_package' => serialize($translation_package)
                                    )                                
                            );
                            
                            $translator_id = $wpdb->get_var($wpdb->prepare("SELECT translator_id 
                                FROM {$wpdb->prefix}icl_translation_status WHERE translation_id = %d", $translation->translation_id));
                            $job_id = $this->add_translation_job($rid, $translator_id, $translation_package);
                            // update
                            
                        }
                    }
                }
            }
        }
        
        // sync copies/duplicates
        $duplicates = $this->get_duplicates($post_id);
        foreach($duplicates as $lang => $_pid){
            $this->make_duplicate($post_id, $lang);
        }
        
        
        // review?
        /*
        if($_POST['icl_trid']){
            //
            // get original document
            $translations = $sitepress->get_element_translations($_POST['icl_trid'], 'post_' . $post->post_type);
            foreach($translations as $t){
                if($t->original){
                    $origin = $t->language_code;
                }
            }
            
            // remove ?            
            $rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_content_status WHERE nid = %d"), $post_id);
            if(!$rid){                
                $wpdb->insert($wpdb->prefix.'icl_content_status', array('nid' => $post_id, 'md5'=>$this->post_md5($post), 'timestamp'=>date('Y-m-d H:i:s')));                
                $rid = $wpdb->insert_id;
            }else{
                $wpdb->update($wpdb->prefix.'icl_content_status', array('md5'=>$this->post_md5($post)), array('rid'=>$rid));                
            }
            
            // add update icl_core_status entry
            $id = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_core_status WHERE rid = %d AND target= = %s"), $rid, $_POST['icl_post_language']);
            if(!$id){
                $wpdb->insert($wpdb->prefix.'icl_core_status', array('rid' => $rid, 'origin' => $origin, 'target' => $_POST['icl_post_language'], 'status' => 1 ));  //!!!!!!!               
            }else{
                $wpdb->update($wpdb->prefix.'icl_content_status', array('md5'=>$this->post_md5($post)), array('rid'=>$rid));                
            }
            
        }
        */
        
    }
    
    function delete_post_actions($post_id){
        global $wpdb;
        $post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID={$post_id}");
        if(!empty($post_type)){
            $translation_id = $wpdb->get_var($wpdb->prepare("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post_type));                
            if($translation_id){
                $rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id));     
                if($rid){
                    $jobs = $wpdb->get_col($wpdb->prepare("SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid));
                    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid));                                 
                    if(!empty($jobs)){
                        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (".join(',', $jobs).")");                                 
                    }
                }
            }
        }
    }
    
    function edit_term($cat_id, $tt_id){
        global $wpdb, $sitepress;

        $el_type = $wpdb->get_var("SELECT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id={$tt_id}");

        if(!$sitepress->is_translated_taxonomy($el_type)){
            return;
        };

        $icl_el_type = 'tax_' . $el_type;

        // has trid only when it's a translation of another tag
        $trid = isset($_POST['icl_trid']) && (isset($_POST['icl_'.$icl_el_type.'_language'])) ? $_POST['icl_trid']:null;
        // see if we have a "translation of" setting.
        $src_language = false;
        if (isset($_POST['icl_translation_of']) && $_POST['icl_translation_of']) {
            $src_term_id = $_POST['icl_translation_of'];
            if ($src_term_id != 'none') {
                $res = $wpdb->get_row("SELECT trid, language_code
                    FROM {$wpdb->prefix}icl_translations WHERE element_id={$src_term_id} AND element_type='{$icl_el_type}'");
                $trid = $res->trid;
                $src_language = $res->language_code;
            } else {
                $trid = null;
            }
        }

        if(isset($_POST['action']) && $_POST['action'] == 'inline-save-tax'){
            $trid = $sitepress->get_element_trid($tt_id, $icl_el_type);
        }	
		
	// update icl_translate if necessary
		// get the terms translations
		$element_translations = $sitepress->get_element_translations($trid, $icl_el_type);
		$tr_ids = array();
		foreach($element_translations as $el_lang => $el_info){
			if($tt_id != $el_info->term_id){
				$tr_ids[] = $el_info->term_id;
			}
		}			
		
		// does the term taxonomy we are currently editing have a record in the icl_translate?
		$results = $wpdb->get_results( 
			"SELECT field_data, job_id FROM {$wpdb->prefix}icl_translate WHERE field_type = '{$el_type}_ids'"
		);
		
		if(!empty($results)){
			$job_ids = array(); // this will hold the job ids that contain our term under field_data
			$job_ids2 = array(); // this will hold the job ids that contain our term under field_data_translated
			foreach($results as $result){
				$r_ids = explode (',', $result->field_data);
				// is the term found?
				if(in_array ($tt_id, $r_ids)){
					$job_ids[] = $result->job_id;
					// this is used further down to identify the correct term name to update
					foreach($r_ids as $k => $v){
						if($v == $tt_id){
							$t_k[] = $k;
						}
					}
				}
			
				// the name of the current category being edited could also be under field_data_translated
				if(!empty($tr_ids)){
					foreach($tr_ids as $tr_id){
						// is the term found?
						if(in_array ($tr_id, $r_ids)){
							$job_ids2[] = $result->job_id;
							// this is used further down to identify the correct term name to update
							foreach($r_ids as $k => $v){
								if($v == $tr_id){
									$t_k2[] = $k;
								}
							}
						}	
					}
				}
			}

			// if we have job_ids to be updated proceed
			if(!empty($job_ids)){
				$in = implode (',', $job_ids);
				$field_type = ($el_type == 'category' ? 'categories' : $el_type);
				//grab the term names
				$results = $wpdb->get_results( 
					"SELECT tid, field_data, field_format FROM {$wpdb->prefix}icl_translate WHERE job_id IN ({$in}) AND field_type = '{$field_type}'"
				);
				
				$count = 0;				
				foreach($results as $result){
					// decode
					$decoded_data = $this->decode_field_data($result->field_data, $result->field_format);				
					// we may have multiple comma separated term names - pass the new term name to the correct one!
					$decoded_data[$t_k[$count]] = $_POST['name'];	
					// encode
					$encoded_data =$this->encode_field_data($decoded_data, $result->field_format);
					// update
					$wpdb->update($wpdb->prefix.'icl_translate', 
							array('field_data'=>$encoded_data), 
							array('tid'=>$result->tid)
						);
				$count++;
				}
				
				// update the translation status as "needs_update"
				foreach($job_ids as $job_id){
					list($translator_id, $rid) = $wpdb->get_row($wpdb->prepare("SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id), ARRAY_N);
					// update
					$wpdb->update($wpdb->prefix.'icl_translation_status', 
							array('needs_update'=>1), 
							array('rid'=>$rid, 'translator_id'=>$translator_id)
						);
				}
			}
			
			// if we have job_ids2 to be updated proceed
			if(!empty($job_ids2)){
				$in = implode (',', $job_ids2);
				$field_type = ($el_type == 'category' ? 'categories' : $el_type);
				//grab the term names
				$results = $wpdb->get_results( 
					"SELECT tid, field_data_translated, field_format FROM {$wpdb->prefix}icl_translate WHERE job_id IN ({$in}) AND field_type = '{$field_type}'"
				);
				
				$count = 0;				
				foreach($results as $result){
					if(!empty($result->field_data_translated)){
						// decode
						$decoded_data = $this->decode_field_data($result->field_data_translated, $result->field_format);					
						// we may have multiple comma separated term names - pass the new term name to the correct one!
						$decoded_data[$t_k2[$count]] = $_POST['name'];
						// encode
						$encoded_data =$this->encode_field_data($decoded_data, $result->field_format);
						// update
						$wpdb->update($wpdb->prefix.'icl_translate', 
								array('field_data_translated'=>$encoded_data), 
								array('tid'=>$result->tid)
							);
					}
				$count++;
				}
			}
		}
    }
	
	/* TRANSLATIONS */
    /* ******************************************************************************************** */
    /**
    * calculate post md5
    * 
    * @param object|int $post
    * @return string
    * 
    * @todo full support for custom posts and custom taxonomies
    */
    function post_md5($post){
        
        if (isset($post->external_type) && $post->external_type) {
            
            $md5str = '';
            
            foreach ($post->string_data as $key => $value) {
                $md5str .= $key . $value;
            }
            
            
        } else {
        
            $post_tags = $post_categories = $custom_fields_values = array();
            
            if(is_numeric($post)){
                $post = get_post($post);    
            }
            
            foreach(wp_get_object_terms($post->ID, 'post_tag') as $tag){
                $post_tags[] = $tag->name;
            }
            if(is_array($post_tags)){
                sort($post_tags, SORT_STRING);
            }        
            foreach(wp_get_object_terms($post->ID, 'category') as $cat){
                $post_categories[] = $cat->name;
            }    
            if(is_array($post_categories)){
                sort($post_categories, SORT_STRING);
            }
            
            global $wpdb, $sitepress_settings;
            // get custom taxonomies
            $taxonomies = $wpdb->get_col("
                SELECT DISTINCT tx.taxonomy 
                FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->term_relationships} tr ON tx.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tr.object_id = {$post->ID}
            ");
            
            sort($taxonomies, SORT_STRING);
            foreach($taxonomies as $t){
                if(@intval($sitepress_settings['taxonomies_sync_option'][$t]) == 1){
                    $taxs = array();
                    foreach(wp_get_object_terms($post->ID, $t) as $trm){
                        $taxs[] = $trm->name;
                    }
                    if($taxs){
                        sort($taxs,SORT_STRING);
                        $all_taxs[] = '['.$t.']:'.join(',',$taxs);
                    }
                }
            }
            
            $custom_fields_values = array();
            if(is_array($this->settings['custom_fields_translation'])){
                foreach($this->settings['custom_fields_translation'] as $cf => $op){
                    if ($op == 2) {
                        $custom_fields_values[] = get_post_meta($post->ID, $cf, true);
                    }
                }
            }
            
            $md5str =         
                $post->post_title . ';' .                 
                $post->post_content . ';' . 
                join(',',$post_tags).';' . 
                join(',',$post_categories) . ';' . 
                join(',', $custom_fields_values);
            
            if(!empty($all_taxs)){
                $md5str .= ';' . join(';', $all_taxs);
            }
            
            if($sitepress_settings['translated_document_page_url'] == 'translate'){
                $md5str .=  $post->post_name . ';';
            }
            
            
        }
        
        $md5 = md5($md5str);
                    
        return $md5;        
    }
    
    /**
    * get documents
    * 
    * @param array $args
    */
    function get_documents($args){
                
        extract($args);
        
        global $wpdb, $wp_query, $sitepress;
        
        $t_el_types = array_keys($sitepress->get_translatable_documents());
        
        // SELECT
        $select = " p.ID AS post_id, p.post_title, p.post_content, p.post_type, p.post_status, p.post_date, t.trid, t.source_language_code <> '' AS is_translation";
        if($to_lang){
            $select .= ", iclts.status, iclts.needs_update";
        }else{
            foreach($sitepress->get_active_languages() as $lang){
                if($lang['code'] == $from_lang) continue;
                $tbl_alias_suffix = str_replace('-','_',$lang['code']);                
                $select .= ", iclts_{$tbl_alias_suffix}.status AS status_{$tbl_alias_suffix}, iclts_{$tbl_alias_suffix}.needs_update AS needs_update_{$tbl_alias_suffix}";
            }
        }
        
        // FROM
        $from   = " {$wpdb->posts} p";
        
        // JOIN
        $join = "";        
        $join   .= " LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id=p.ID\n";    
        if($to_lang){
            $tbl_alias_suffix = str_replace('-','_',$to_lang);
            $join .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix} 
                        ON iclt_{$tbl_alias_suffix}.trid=t.trid AND iclt_{$tbl_alias_suffix}.language_code='{$to_lang}'\n";    
            $join   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts ON iclts.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";    
        }else{
            foreach($sitepress->get_active_languages() as $lang){
                if($lang['code'] == $from_lang) continue;
                $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                $join .= " LEFT JOIN {$wpdb->prefix}icl_translations iclt_{$tbl_alias_suffix} 
                        ON iclt_{$tbl_alias_suffix}.trid=t.trid AND iclt_{$tbl_alias_suffix}.language_code='{$lang['code']}'\n";    
                $join   .= " LEFT JOIN {$wpdb->prefix}icl_translation_status iclts_{$tbl_alias_suffix} 
                        ON iclts_{$tbl_alias_suffix}.translation_id=iclt_{$tbl_alias_suffix}.translation_id\n";    
            }
        }
        
        
        // WHERE
        $where = " t.language_code = '{$from_lang}' AND p.post_status NOT IN ('trash', 'auto-draft') \n";        
        if(!empty($type)){
            $where .= " AND p.post_type = '{$type}'";
            $where .= " AND t.element_type = 'post_{$type}'\n";
        }else{
            $where .= " AND p.post_type IN ('".join("','",$t_el_types)."')\n";
            foreach($t_el_types as $k=>$v){
                $t_el_types[$k] = 'post_' . $v;
            }
            $where .= " AND t.element_type IN ('".join("','",$t_el_types)."')\n";
        }  
        if(!empty($title)){
            $where .= " AND p.post_title LIKE '%".$wpdb->escape($title)."%'\n";
        }
        
        if(!empty($status)){
            $where .= " AND p.post_status = '{$status}'\n";
        }        
        
        if(isset($from_date)){
            $where .= " AND p.post_date > '{$from_date}'\n";
        }

        if(isset($to_date)){
            $where .= " AND p.post_date > '{$to_date}'\n";
        }
        
        if($tstatus){
            if($to_lang){
                if($tstatus == 'not'){
                    $where .= " AND (iclts.status IS NULL OR iclts.status = ".ICL_TM_WAITING_FOR_TRANSLATOR." OR iclts.needs_update = 1)\n";    
                }elseif($tstatus == 'need-update'){
                    $where .= " AND iclts.needs_update = 1\n";    
                }elseif($tstatus == 'in_progress'){
                    $where .= " AND iclts.status = ".ICL_TM_IN_PROGRESS." AND iclts.needs_update = 0\n";    
                }elseif($tstatus == 'complete'){
                    $where .= " AND iclts.status = ".ICL_TM_COMPLETE." AND iclts.needs_update = 0\n";    
                }
                
            }else{
                if($tstatus == 'not'){
                    $where .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status IS NULL OR iclts_{$tbl_alias_suffix}.status = ".ICL_TM_WAITING_FOR_TRANSLATOR." OR iclts_{$tbl_alias_suffix}.needs_update = 1\n";    
                    }
                    $where .= join(' OR ', $wheres) . ")";
                }elseif($tstatus == 'need-update'){
                    $where .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.needs_update = 1\n";    
                    }
                    $where .= join(' OR ', $wheres) . ")";
                }elseif($tstatus == 'in_progress'){
                    $where .= " AND (";
                    $wheres = array();
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $wheres[] = "iclts_{$tbl_alias_suffix}.status = ".ICL_TM_IN_PROGRESS."\n";    
                    }
                    $where .= join(' OR ', $wheres)  . ")";
                }elseif($tstatus == 'complete'){
                    foreach($sitepress->get_active_languages() as $lang){
                        if($lang['code'] == $from_lang) continue;
                        $tbl_alias_suffix = str_replace('-','_',$lang['code']);
                        $where .= " AND iclts_{$tbl_alias_suffix}.status = ".ICL_TM_COMPLETE." AND iclts_{$tbl_alias_suffix}.needs_update = 0\n";    
                    }
                }
            }
        }
        
        if(isset($parent_type) && $parent_type == 'page' && $parent_id > 0){            
            if($parent_all){
                $children = icl_get_post_children_recursive($parent_id);    
                $where .= ' AND p.ID IN (' . join(',', $children) . ')';
            }else{
                $where .= ' AND p.post_parent=' . intval($parent_id);
            }
        }
        
        if(isset($parent_type) && $parent_type == 'category' && $parent_id > 0){
            if($parent_all){
                $children = icl_get_tax_children_recursive($parent_id);  
                $children[] = $parent_id;
                $join .= "  JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
                            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND taxonomy = 'category'
                            JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id AND tm.term_id IN(" . join(',', $children) . ")"; 
            }else{
                $join .= "  JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
                            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND taxonomy = 'category'
                            JOIN {$wpdb->terms} tm ON tt.term_id = tm.term_id AND tm.term_id = " . intval($parent_id); 
            }
        }
        
        // ORDER
        if($sort_by){
            $order = " $sort_by ";    
        }else{
            $order = " p.post_date DESC";
        }
        if($sort_order){
            $order .= $sort_order;    
        }else{
            $order .= 'DESC';    
        }
        
        
        
        // LIMIT
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit_no;
        $limit = " " . $offset . ',' . $limit_no;
        
        
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS {$select} 
            FROM {$from}
            {$join}
            WHERE {$where}
            ORDER BY {$order}
            LIMIT {$limit}
        ";  
        
        $results = $wpdb->get_results($sql);    
        
        $count = $wpdb->get_var("SELECT FOUND_ROWS()");
        
        $wp_query->found_posts = $count;
        $wp_query->query_vars['posts_per_page'] = $limit_no;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit_no);
        
        // post process
        foreach($results as $k=>$v){
            if($v->is_translation){
                $source_language = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $v->trid));    
                $_tmp = 'status_' . $source_language;
                $v->$_tmp = ICL_TM_COMPLETE;
            }    
        }
          
        return $results;
        
    }   
    
    function get_element_translation($element_id, $language, $element_type='post_post'){
        global $wpdb, $sitepress;
        $trid = $sitepress->get_element_trid($element_id, $element_type);
        $translation = array();
        if($trid){
            $translation = $wpdb->get_row($wpdb->prepare("
                SELECT * 
                FROM {$wpdb->prefix}icl_translations tr 
                JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id 
                WHERE tr.trid=%s AND tr.language_code='%s'
            ", $trid, $language));        
        }
        return $translation;
    }
    
    function get_element_translations($element_id, $element_type='post_post', $service = false){
        global $wpdb, $sitepress;
        $trid = $sitepress->get_element_trid($element_id, $element_type);
        $translations = array();
        if($trid){
            $service =  $service ? " AND translation_service = '$service'" : '';
            $translations = $wpdb->get_results($wpdb->prepare("
                SELECT * 
                FROM {$wpdb->prefix}icl_translations tr 
                JOIN {$wpdb->prefix}icl_translation_status ts ON tr.translation_id = ts.translation_id 
                WHERE tr.trid=%s {$service} 
            ", $trid)); 
            foreach($translations as $k=>$v){
                $translations[$v->language_code] = $v;
                unset($translations[$k]);
            }      
        }
        return $translations;
    }
    
    /**
    * returns icon file name according to status code
    * 
    * @param int $status
    */
    public function status2img_filename($status, $needs_update = 0){
        if($needs_update){
            $img_file = 'needs-update.png'; 
        }else{
            switch($status){
                case ICL_TM_NOT_TRANSLATED: $img_file = 'not-translated.png'; break;
                case ICL_TM_WAITING_FOR_TRANSLATOR: $img_file = 'in-progress.png'; break;
                case ICL_TM_IN_PROGRESS: $img_file = 'in-progress.png'; break;
                case ICL_TM_NEEDS_UPDATE: $img_file = 'needs-update.png'; break;
                case ICL_TM_DUPLICATE: $img_file = 'copy.png'; break;
                case ICL_TM_COMPLETE: $img_file = 'complete.png'; break;
                default: $img_file = '';
            }
        }
        return $img_file;
    }
    
    public function status2text($status){
        switch($status){
            case ICL_TM_NOT_TRANSLATED: $text = __('Not translated', 'sitepress'); break;
            case ICL_TM_WAITING_FOR_TRANSLATOR: $text = __('Waiting for translator', 'sitepress'); break;
            case ICL_TM_IN_PROGRESS: $text = __('In progress', 'sitepress'); break;
            case ICL_TM_NEEDS_UPDATE: $text = __('Needs update', 'sitepress'); break;
            case ICL_TM_DUPLICATE: $text = __('Duplicate', 'sitepress'); break;
            case ICL_TM_COMPLETE: $text = __('Complete', 'sitepress'); break;
            default: $text = '';
        }
        return $text;
    } 
    
    public function estimate_word_count($data, $lang_code){
        global $asian_languages;
        
        $words = 0;
        if(isset($data->post_title)){
            if(in_array($lang_code, $asian_languages)){
                $words += strlen(strip_tags($data->post_title)) / 6;
            } else {
                $words += count(explode(' ',$data->post_title));
            }
        }
        if(isset($data->post_content)){
            if(in_array($lang_code, $asian_languages)){
                $words += strlen(strip_tags($data->post_content)) / 6;
            } else {
                $words += count(explode(' ',strip_tags($data->post_content)));
            }
        }
        
        return (int)$words;
        
    }
    
    public function estimate_custom_field_word_count($post_id, $lang_code) {
        global $asian_languages;

        $words = 0;
        
        if(!empty($this->settings['custom_fields_translation']) && is_array($this->settings['custom_fields_translation'])){
            $custom_fields = array();        
            foreach($this->settings['custom_fields_translation'] as $cf => $op){
                if ($op == 2) {
                    $custom_fields[] = $cf;
                }
            }
            foreach($custom_fields as $cf ){
                $custom_fields_value = get_post_meta($post_id, $cf, true);
                if ($custom_fields_value != "" && is_scalar($custom_fields_value)) {  // only support scalar values fo rnow
                    if(in_array($lang_code, $asian_languages)){ 
                        $words += strlen(strip_tags($custom_fields_value)) / 6;
                    } else {
                        $words += count(explode(' ',strip_tags($custom_fields_value)));
                    }
                }
            }
        }
                
        return (int)$words;
    }
    
    public function decode_field_data($data, $format){
        if($format == 'base64'){
            $data = base64_decode($data);
        }elseif($format == 'csv_base64'){
            $exp = explode(',', $data);
            foreach($exp as $k=>$e){
                $exp[$k] = base64_decode(trim($e,'"'));
            }
            $data = $exp;
        }
        return $data;
    }
    
    public function encode_field_data($data, $format){        
        if($format == 'base64'){
            $data = base64_encode($data);
        }elseif($format == 'csv_base64'){
            $exp = $data;
            foreach($exp as $k=>$e){
                $exp[$k] = '"' . base64_encode(trim($e)) . '"';
            }
            $data = join(',', $exp);
        }
        return $data;
    }
    
    /**
    * create translation package 
    * 
    * @param object|int $post
    */
    function create_translation_package($post){
        global $sitepress, $sitepress_settings;
        
        $package = array();
        
       
        if(is_numeric($post)){
            $post = get_post($post);    
        }

        if (isset($post->external_type) && $post->external_type) {

            foreach ($post->string_data as $key => $value) {
                $package['contents'][$key] = array(
                    'translate' => 1,
                    'data'      => $this->encode_field_data($value, 'base64'),
                    'format'    => 'base64'
                );
            }
            
            $package['contents']['original_id'] = array(
                'translate' => 0,
                'data'      => $post->post_id,
            );
            
        
        } else {
            if($post->post_type=='page'){
                $package['url'] = htmlentities(get_option('home') . '?page_id=' . ($post->ID));
            }else{
                $package['url'] = htmlentities(get_option('home') . '?p=' . ($post->ID));
            }
            
            $package['contents']['title'] = array(
                'translate' => 1,
                'data'      => $this->encode_field_data($post->post_title, 'base64'),
                'format'    => 'base64'
            );
            
            if($sitepress_settings['translated_document_page_url'] == 'translate'){
                $package['contents']['URL'] = array(
                    'translate' => 1,
                    'data'      => $this->encode_field_data($post->post_name, 'base64'),
                    'format'    => 'base64'
                );
            }
            
            $package['contents']['body'] = array(
                'translate' => 1,
                'data'      => $this->encode_field_data($post->post_content, 'base64'),
                'format'    => 'base64'
            );
    
            if(!empty($post->post_excerpt)){
                $package['contents']['excerpt'] = array(
                    'translate' => 1,
                    'data'      => base64_encode($post->post_excerpt),
                    'format'    => 'base64'
                );
            }
            
            $package['contents']['original_id'] = array(
                'translate' => 0,
                'data'      => $post->ID
            );
                    
            if(!empty($this->settings['custom_fields_translation']))
            foreach($this->settings['custom_fields_translation'] as $cf => $op){
                if ($op == 2) { // translate
                
                    /* */
                    $custom_fields_value = get_post_meta($post->ID, $cf, true);
                    if ($custom_fields_value != '' && is_scalar($custom_fields_value)) {
                        $package['contents']['field-'.$cf] = array(
                            'translate' => 1,
                            'data' => $this->encode_field_data($custom_fields_value, 'base64'),
                            'format' => 'base64'
                        );
                        $package['contents']['field-'.$cf.'-name'] = array(
                            'translate' => 0,
                            'data' => $cf
                        );
                        $package['contents']['field-'.$cf.'-type'] = array(
                            'translate' => 0,
                            'data' => 'custom_field'
                        );
                    }                    
                    /* */
                    /*    
                    $post_custom = get_post_custom($post->ID);
                    if(isset($post_custom[$cf])){
                        $package['contents']['field-'.$cf] = array(
                            'translate' => 1,
                            'data' => $this->encode_field_data(serialize($post_custom[$cf]), 'base64'),
                            'format' => 'base64'
                        );
                        $package['contents']['field-'.$cf.'-name'] = array(
                            'translate' => 0,
                            'data' => $cf
                        );
                        $package['contents']['field-'.$cf.'-type'] = array(
                            'translate' => 0,
                            'data' => 'custom_field'
                        );
                    }
                    */
                }
            } 
            
            foreach((array)$sitepress->get_translatable_taxonomies(true, $post->post_type) as $taxonomy){
                $terms = get_the_terms( $post->ID , $taxonomy );
                if(!empty($terms)){
                    $_taxs = $_tax_ids = array();
                    foreach($terms as $term){
                        $_taxs[] = $term->name;    
                        $_tax_ids[] = $term->term_taxonomy_id;
                    }
                    if($taxonomy == 'post_tag'){ 
                        $tax_package_key  = 'tags'; 
                        $tax_id_package_key  = 'tag_ids'; 
                    }
                    elseif($taxonomy == 'category'){
                        $tax_package_key  = 'categories'; 
                        $tax_id_package_key  = 'category_ids'; 
                    }
                    else{
                        $tax_package_key  = $taxonomy;
                        $tax_id_package_key  = $taxonomy . '_ids'; 
                    } 
                    
                    $package['contents'][$tax_package_key] = array(
                        'translate' => 1,
                        'data'      => $this->encode_field_data($_taxs,'csv_base64'),
                        'format'=>'csv_base64'
                    );
                    
                    $package['contents'][$tax_id_package_key] = array(
                        'translate' => 0,
                        'data'      => join(',', $_tax_ids)
                    );
                }            
            }
        }
        return $package;
    }    
    
    /**
    * add/update icl_translation_status record
    * 
    * @param array $data
    */
    function update_translation_status($data){
        global $wpdb;
        if(!isset($data['translation_id'])) return;
        if($rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $data['translation_id']))){
            
            $wpdb->update($wpdb->prefix.'icl_translation_status', $data, array('rid'=>$rid));
            
            $update = true;
        }else{
            $wpdb->insert($wpdb->prefix.'icl_translation_status',$data);
            $rid = $wpdb->insert_id;
            $update = false;
        }    
        
        return array($rid, $update);
    }

    function _get_post($post_id) {
        if (is_string($post_id) && strcmp(substr($post_id, 0, strlen('external_')), 'external_')===0) {
            $item = null;
            return apply_filters('WPML_get_translatable_item', $item, $post_id);
        } else {
            return get_post($post_id);
        }
    }
    /* TRANSLATION JOBS */
    /* ******************************************************************************************** */   
    
    function send_jobs($data){                                
        global $wpdb, $sitepress;
                      
        if(!isset($data['tr_action']) && isset($data['translate_to'])){ //adapt new format
            $data['tr_action'] = $data['translate_to'];
            unset($data['translate_to']);
        }
        
        // translate_from
        // tr_action (translate_to)
        // translator
        // post
        // service
        // defaults
        $data_default = array(
            'translate_from'    => $sitepress->get_default_language()
        );        
        extract($data_default);
        extract($data, EXTR_OVERWRITE);
        
        // no language selected ?
        if(!isset($tr_action) || empty($tr_action)){
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('Please select at least one language to translate into.', 'sitepress')
            );
            $this->dashboard_select = $data; // prepopulate dashboard
            return;
        }
        // no post selected ?
        if(!isset($iclpost) || empty($iclpost)){
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('Please select at least one document to translate.', 'sitepress')
            );
            $this->dashboard_select = $data; // prepopulate dashboard
            return;
        }
        
        $selected_posts = $iclpost;
        $selected_translators = isset($translator) ? $translator : array();
        $selected_languages = $tr_action;
        $job_ids = array();
        
        foreach($selected_posts as $post_id){
            $post = $this->_get_post($post_id); 
            $post_trid = $sitepress->get_element_trid($post->ID, 'post_' . $post->post_type);
            $post_translations = $sitepress->get_element_translations($post_trid, 'post_' . $post->post_type);            
            $md5 = $this->post_md5($post);
            
            $translation_package = $this->create_translation_package($post);
            
            foreach($selected_languages as $lang => $action){
                
                // making this a duplicate?
                if($action == 2){
                    // dont send documents that are in progress
                    $current_translation_status = $this->get_element_translation($post_id, $lang, 'post_' . $post->post_type);                
                    if($current_translation_status && $current_translation_status->status == ICL_TM_IN_PROGRESS) continue;
                    
                    $job_ids[] = $this->make_duplicate($post_id, $lang);                                         
                }elseif($action == 1){    

                    if(empty($post_translations[$lang])){
                        $translation_id = $sitepress->set_element_language_details(null , 'post_' . $post->post_type, $post_trid, $lang, $translate_from);
                    }else{
                        $translation_id = $post_translations[$lang]->translation_id;
                    }     
                    
                    $current_translation_status = $this->get_element_translation($post_id, $lang, 'post_' . $post->post_type);                
                    
                    // don't send documents that are in progress
                    // don't send documents that are already translated and don't need update
                    if(!empty($current_translation_status)){
                        if($current_translation_status->status == ICL_TM_IN_PROGRESS) continue;
                        if($current_translation_status->status == ICL_TM_COMPLETE && !$current_translation_status->needs_update) continue;
                    }
                    
                    $_status = ICL_TM_WAITING_FOR_TRANSLATOR;
                    
                    $_exp = isset($selected_translators[$lang]) ? explode('-', $selected_translators[$lang]) : false;
                    if(!isset($service)){
                        $translation_service = isset($_exp[1]) ? $_exp[1] : 'local';
                    }else{
                        $translation_service = $service;
                    }                
                    $translator_id = $_exp[0];
                                                    
                    // set as default translator                
                    if($translator_id > 0){
                        $this->set_default_translator($translator_id, $translate_from, $lang, $translation_service);
                    }
                    
                    // add translation_status record        
                    $data = array(
                        'translation_id'        => $translation_id,
                        'status'                => $_status,
                        'translator_id'         => $translator_id,
                        'needs_update'          => 0,
                        'md5'                   => $md5,
                        'translation_service'   => $translation_service,
                        'translation_package'   => serialize($translation_package)                    
                    );
                    
                    $_prevstate = $wpdb->get_row($wpdb->prepare("
                        SELECT status, translator_id, needs_update, md5, translation_service, translation_package, timestamp, links_fixed
                        FROM {$wpdb->prefix}icl_translation_status
                        WHERE translation_id = %d                    
                    ", $translation_id), ARRAY_A);
                    if(!empty($_prevstate)){
                        $data['_prevstate'] = serialize($_prevstate);
                    }                
                                    
                    list($rid, $update) = $this->update_translation_status($data);
                                    
                    $job_ids[] = $this->add_translation_job($rid, $translator_id, $translation_package);                                                
                    if( $translation_service == 'icanlocalize' ){
                        global $ICL_Pro_Translation;
                        $sent = $ICL_Pro_Translation->send_post($post, array($lang), $translator_id);
                        if(!$sent){
                            $job_id = array_pop($job_ids);
                            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id));
                            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE rid=%d ORDER BY job_id DESC LIMIT 1", $rid));
                            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id));
                        }
                    }
                } // if / else is making a duplicate
            }                
            
        }
        
        $job_ids = array_unique($job_ids);
        if(array(false) == $job_ids || empty($job_ids)){
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('No documents were sent to translation. Make sure that translations are not currently in progress or already translated for the selected language(s).', 'sitepress')
            );            
        }elseif(in_array(false, $job_ids)){
            $this->messages[] = array(
                'type'=>'updated',
                'text' => __('Some documents were sent to translation.', 'sitepress')
            );            
            $this->messages[] = array(
                'type'=>'error',
                'text' => __('Some documents were <i>not</i> sent to translation. Make sure that translations are not currently in progress for the selected language(s).', 'sitepress')
            );
        }else{
            $this->messages[] = array(
                'type'=>'updated',
                'text' => __('Selected document(s) sent to translation.', 'sitepress')
            );
        }

        return $job_ids;
    }    
    
    /**
    * Adds a translation job record in icl_translate_job
    * 
    * @param mixed $rid
    * @param mixed $translator_id
    */
    function add_translation_job($rid, $translator_id, $translation_package){
        global $wpdb, $current_user;        
        get_currentuserinfo();
        if(!$current_user->ID){
            $manager_id = $wpdb->get_var($wpdb->prepare("SELECT manager_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d ORDER BY job_id DESC LIMIT 1", $rid));
        }else{
            $manager_id = $current_user->ID;
        }
        
        $translation_status = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translation_status WHERE rid=%d", $rid));
        
        // if we have a previous job_id for this rid mark it as the top (last) revision
        list($prev_job_id, $prev_job_translated) = $wpdb->get_row($wpdb->prepare("
                    SELECT job_id, translated FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NULL 
        ", $rid), ARRAY_N);
        if(!is_null($prev_job_id)){
         
            // if previous job is not complete bail out
            if(!$prev_job_translated){
                //trigger_error(sprintf(__('Translation is in progress for job: %s.', 'sitepress'), $prev_job_id), E_USER_NOTICE);
                return false;
            }
            
            $last_rev = $wpdb->get_var($wpdb->prepare("
                SELECT MAX(revision) AS rev FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NOT NULL 
            ", $rid));        
            $wpdb->update($wpdb->prefix . 'icl_translate_job', array('revision'=>$last_rev+1), array('job_id'=>$prev_job_id));
            
            $prev_job = $this->get_translation_job($prev_job_id);
            
            $original_post = get_post($prev_job->original_doc_id);
            foreach($prev_job->elements as $element){
                $prev_translation[$element->field_type] = $element->field_data_translated;
                switch($element->field_type){
                    case 'title':
                        if($this->decode_field_data($element->field_data, $element->field_format) == $original_post->post_title){
                            //$unchanged[$element->field_type] = $element->field_data_translated;
                            $unchanged[$element->field_type] = true;
                        }
                        break;
                    case 'body':
                        if($this->decode_field_data($element->field_data, $element->field_format) == $original_post->post_content){
                            //$unchanged[$element->field_type] = $element->field_data_translated;
                            $unchanged[$element->field_type] = true;
                        }
                        break;
                    case 'excerpt':
                        if($this->decode_field_data($element->field_data, $element->field_format) == $original_post->post_excerpt){
                            //$unchanged[$element->field_type] = $element->field_data_translated;
                            $unchanged[$element->field_type] = true;
                        }
                        break;                    
                    case 'tags':
                        $terms = (array)get_the_terms( $prev_job->original_doc_id , 'post_tag' );
                        $_taxs = array();
                        foreach($terms as $term){
                            $_taxs[] = $term->name;    
                        }
                        if($element->field_data == $this->encode_field_data($_taxs, $element->field_format)){
                            //$unchanged['tags'] = $element->field_data_translated;
                            $unchanged['tags'] = true;
                        }                    
                        break;
                    case 'categories':
                        $terms = get_the_terms( $prev_job->original_doc_id , 'category' );
                        $_taxs = array();
                        foreach($terms as $term){
                            $_taxs[] = $term->name;    
                        }
                        if($element->field_data == $this->encode_field_data($_taxs, $element->field_format)){
                            //$unchanged['categories'] = $element->field_data_translated;
                            $unchanged['categories'] = true;
                        }                                    
                        break;
                    default:
                        if(false !== strpos($element->field_type, 'field-') && !empty($this->settings['custom_fields_translation'])){                                
                            $cf_name = preg_replace('#^field-#', '', $element->field_type);
                            if($this->decode_field_data($element->field_data, $element->field_format) == get_post_meta($prev_job->original_doc_id, $cf_name, 1)){
                                //$unchanged[$element->field_type] = $element->field_data_translated;
                                $unchanged[$element->field_type] = true;
                            }
                        }else{
                            // taxonomies                                                 
                            if(taxonomy_exists($element->field_type)){
                                $terms = get_the_terms( $prev_job->original_doc_id , $element->field_type );
                                $_taxs = array();
                                foreach($terms as $term){
                                    $_taxs[] = $term->name;    
                                }
                                if($element->field_data == $this->encode_field_data($_taxs, $element->field_format)){
                                    //$unchanged[$element->field_type] = $field['data_translated'];
                                    $unchanged[$element->field_type] = true;
                                }  
                            }                                  
                        }                
                }
            }                    
        }
        
        $wpdb->insert($wpdb->prefix . 'icl_translate_job', array(
            'rid' => $rid,
            'translator_id' => $translator_id, 
            'translated'    => 0,
            'manager_id'    => $manager_id
        ));        
        $job_id = $wpdb->insert_id;
        
        foreach($translation_package['contents'] as $field => $value){
            $job_translate = array(
                'job_id'            => $job_id,
                'content_id'        => 0,
                'field_type'        => $field,
                'field_format'      => isset($value['format'])?$value['format']:'',
                'field_translate'   => $value['translate'],
                'field_data'        => $value['data'],
                'field_data_translated' => isset($prev_translation[$field]) ? $prev_translation[$field] : '',
                'field_finished'    => 0      
            );
            if(isset($unchanged[$field])){                
                $job_translate['field_finished'] = 1;
            }
            //$job_translate['field_data_translated'] = $unchanged[$field];    
            
            $wpdb->insert($wpdb->prefix . 'icl_translate', $job_translate);    
        }
        
        if($this->settings['doc_translation_method'] == ICL_TM_TMETHOD_EDITOR){ // only send notifications if the translation editor method is on
            if(!defined('ICL_TM_DISABLE_ALL_NOTIFICATIONS') && $translation_status->translation_service=='local'){
                if($this->settings['notification']['new-job'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
                    require_once ICL_PLUGIN_PATH . '/inc/translation-management/tm-notification.class.php';
                    if($job_id){
                        $tn_notification = new TM_Notification();
                        if(empty($translator_id)){
                            $tn_notification->new_job_any($job_id);    
                        }else{
                            $tn_notification->new_job_translator($job_id, $translator_id);
                        }            
                    }
                }
            }
        }

        return $job_id;
        
    }
    
    function assign_translation_job($job_id, $translator_id, $service='local'){
        global $wpdb;
        list($prev_translator_id, $rid) = $wpdb->get_row($wpdb->prepare("SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id), ARRAY_N);
        
        require_once ICL_PLUGIN_PATH . '/inc/translation-management/tm-notification.class.php';
        $tn_notification = new TM_Notification();
        if(!empty($prev_translator_id) && $prev_translator_id != $translator_id){
            if($job_id){
                $tn_notification->translator_removed($prev_translator_id, $job_id);
            }
        }
        
        if($this->settings['notification']['new-job'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
            if(empty($translator_id)){
                $tn_notification->new_job_any($job_id);    
            }else{
                $tn_notification->new_job_translator($job_id, $translator_id);
            }            
        }
        
        $wpdb->update($wpdb->prefix.'icl_translation_status', 
            array('translator_id'=>$translator_id, 'status'=>ICL_TM_WAITING_FOR_TRANSLATOR, 'translation_service' => $service),
            array('rid'=>$rid));
        $wpdb->update($wpdb->prefix.'icl_translate_job', array('translator_id'=>$translator_id), array('job_id'=>$job_id));
        return true;
    }
    
    function get_translation_jobs($args = array()){        
        global $wpdb, $sitepress, $wp_query;
        
        // defaults
        $args_default = array(
            'translator_id' => 0,
            'status' => false,
            'include_unassigned' => false
        );
        
        extract($args_default);
        extract($args, EXTR_OVERWRITE);
        
        $_exp = explode('-', $translator_id);
        $service = isset($_exp[1]) ? $_exp[1] : 'local';
        $translator_id = $_exp[0];
        
        $where = " s.status > " . ICL_TM_NOT_TRANSLATED;
        if($status != ''){
            $where .= " AND s.status=" . intval($status);    
        }    
        if($status != ICL_TM_DUPLICATE){
            $where .= " AND s.status <> " . ICL_TM_DUPLICATE;    
        }    
        if(!empty($translator_id)){
            if($include_unassigned){
                $where .= " AND (j.translator_id=" . intval($translator_id) . " OR j.translator_id=0) ";    
            }else{
                $where .= " AND j.translator_id=" . intval($translator_id);        
            }
            if(!empty($service)){
                $where .= " AND s.translation_service='{$service}'";        
            }
            
            $language_pairs = get_user_meta($translator_id, $wpdb->prefix.'language_pairs', true);       
        }
        
        // HANDLE FROM
        if(!empty($from)){
            $where .= PHP_EOL . " AND t.source_language_code='".$wpdb->escape($from)."'";                
        }else{
            // only if we filter by translator, make sure to use just the 'from' languages that apply
            // in no translator_id, ommit condition and all will be pulled 
            if($translator_id){
                if(!empty($to)){
                    // get 'from' languages corresdonding to $to (to $translator_id)
                    $froms = array();
                    foreach($language_pairs as $fl => $tls){
                        if(isset($tls[$to])) $froms[] = $fl;
                    }
                    if($froms){
                        $where .= PHP_EOL . sprintf(" AND t.source_language_code IN(%s)", "'" . join("','", $froms) . "'");                    
                    }                
                }else{
                    // all to all case
                    // get all possible combinations for $translator_id
                    $from_languages = array_keys($language_pairs);
                    $wconds = array();
                    foreach($from_languages as $fl){
                        $to_languages = "'" . join("','", array_keys($language_pairs[$fl])) . "'";
                        $wconds[] = sprintf(" (t.source_language_code='%s' AND t.language_code IN (%s)) ", $fl, $to_languages);
                    }
                    if(!empty($wconds)){
                        $where .= PHP_EOL . ' AND ( ' . join (' OR ', $wconds) . ')';     
                    }
                }
            }
            
        }
        
        // HANDLE TO
        if(!empty($to)){
            $where .= PHP_EOL . " AND t.language_code='".$wpdb->escape($to)."'";    
        }else{
            // only if we filter by translator, make sure to use just the 'from' languages that apply
            // in no translator_id, ommit condition and all will be pulled 
            if($translator_id){
                if(!empty($from)){
                    // get languages the user can translate into from $from
                    $tos = isset($language_pairs[$from]) ? array_keys($language_pairs[$from]) : array();
                    if($tos){
                        $where .= PHP_EOL . sprintf(" AND t.language_code IN(%s)", "'" . join("','", $tos) . "'");                    
                    }
                }else{
                    // covered by 'all to all case' above
                }
            }            
        }
        
        // ORDER BY
        if($include_unassigned){
            $orderby[] = 'j.translator_id DESC'; 
        }
        $orderby[] = ' j.job_id DESC ';
        $orderby = join(', ', $orderby);
                
        // LIMIT
        if(!isset($_GET['paged'])) $_GET['paged'] = 1;
        $offset = ($_GET['paged']-1)*$limit_no;
        $limit = " " . $offset . ',' . $limit_no;
                
        $jobs = $wpdb->get_results(
            "SELECT SQL_CALC_FOUND_ROWS 
                j.job_id, t.trid, t.language_code, t.source_language_code, 
                s.translation_id, s.status, s.needs_update, s.translator_id, u.display_name AS translator_name, s.translation_service 
                FROM {$wpdb->prefix}icl_translate_job j
                    JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                    JOIN {$wpdb->prefix}icl_translations t ON s.translation_id = t.translation_id
                    LEFT JOIN {$wpdb->users} u ON s.translator_id = u.ID
                WHERE {$where} AND revision IS NULL
                ORDER BY {$orderby}
                LIMIT {$limit}
            "
        ); 
        
        
        //echo '<pre>';
        //print_r($wpdb->last_query);
        //echo '</pre>';
        
        $count = $wpdb->get_var("SELECT FOUND_ROWS()");
        
        $wp_query->found_posts = $count;
        $wp_query->query_vars['posts_per_page'] = $limit_no;
        $wp_query->max_num_pages = ceil($wp_query->found_posts/$limit_no);
        
        foreach($jobs as $k=>$row){
            //original
            $post_id = $wpdb->get_var($wpdb->prepare("
                                                     SELECT field_data
                                                     FROM {$wpdb->prefix}icl_translate
                                                     WHERE job_id=%d and field_type='original_id'", $row->job_id));

            $parts = explode('_', $post_id);
            if ($parts[0] == 'external') {
                $jobs[$k]->original_doc_id = $post_id;

                $jobs[$k]->post_title = base64_decode($wpdb->get_var($wpdb->prepare("
                                                         SELECT field_data
                                                         FROM {$wpdb->prefix}icl_translate
                                                         WHERE job_id=%d and field_type='name'", $row->job_id)));
                if ($jobs[$k]->post_title == "") {
                    // try the title field.
                    $jobs[$k]->post_title = base64_decode($wpdb->get_var($wpdb->prepare("
                                                         SELECT field_data
                                                         FROM {$wpdb->prefix}icl_translate
                                                         WHERE job_id=%d and field_type='title'", $row->job_id)));
                }

                $jobs[$k]->edit_link = $this->tm_post_link($post_id);
                $ldf = $sitepress->get_language_details($row->source_language_code);
            } else {       
                $doc = $wpdb->get_row($wpdb->prepare("SELECT ID, post_title, post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id));
                if ($doc) {
                    $jobs[$k]->post_title = $doc->post_title;
                    $jobs[$k]->original_doc_id = $doc->ID;
                    $jobs[$k]->edit_link = get_edit_post_link($doc->ID);                
                    $ldf = $sitepress->get_language_details($sitepress->get_element_language_details($post_id, 'post_' . $doc->post_type)->language_code);
                } else {
                    $jobs[$k]->post_title = __("The original has been deleted!", "sitepress");
                    $jobs[$k]->original_doc_id = 0;
                    $jobs[$k]->edit_link = "";
                    $ldf['display_name'] = __("Deleted", "sitepress");
                }
            }
            $ldt = $sitepress->get_language_details($row->language_code);            
            $jobs[$k]->lang_text = $ldf['display_name'] . ' &raquo; ' . $ldt['display_name'];
            if($row->translation_service=='icanlocalize'){
                $row->translator_name = ICL_Pro_Translation::get_translator_name($row->translator_id);
            }
        }
       return $jobs; 
        
    }
    
    function get_translation_job($job_id, $include_non_translatable_elements = false, $auto_assign = false, $revisions = 0){
        global $wpdb, $sitepress, $current_user;
        get_currentuserinfo();
        $job = $wpdb->get_row($wpdb->prepare("
            SELECT 
                j.rid, j.translator_id, j.translated, j.manager_id,
                s.status, s.needs_update, s.translation_service,
                t.trid, t.language_code, t.source_language_code             
            FROM {$wpdb->prefix}icl_translate_job j 
                JOIN {$wpdb->prefix}icl_translation_status s ON j.rid = s.rid
                JOIN {$wpdb->prefix}icl_translations t ON s.translation_id = t.translation_id                
            WHERE j.job_id = %d", $job_id));
        
        $post_id = $wpdb->get_var($wpdb->prepare("
                                                 SELECT field_data
                                                 FROM {$wpdb->prefix}icl_translate
                                                 WHERE job_id=%d and field_type='original_id'", $job_id));

        $parts = explode('_', $post_id);
        if ($parts[0] == 'external') {
            $job->original_doc_id = $post_id;
            $job->original_doc_title = base64_decode($wpdb->get_var($wpdb->prepare("
                                                     SELECT field_data
                                                     FROM {$wpdb->prefix}icl_translate
                                                     WHERE job_id=%d and field_type='name'", $job_id)));
            if ($job->original_doc_title == "") {
                // try the title field.
                $job->original_doc_title = base64_decode($wpdb->get_var($wpdb->prepare("
                                                         SELECT field_data
                                                         FROM {$wpdb->prefix}icl_translate
                                                         WHERE job_id=%d and field_type='title'", $job_id)));
            }
            $job->original_post_type = $wpdb->get_var($wpdb->prepare("
                                                                     SELECT element_type
                                                                     FROM {$wpdb->prefix}icl_translations
                                                                     WHERE trid=%d AND language_code='%s'",
                                                                     $job->trid, $job->source_language_code));
        } else {        
            
            $original = $wpdb->get_row($wpdb->prepare("
                SELECT t.element_id, p.post_title, p.post_type
                FROM {$wpdb->prefix}icl_translations t 
                JOIN {$wpdb->posts} p ON t.element_id = p.ID AND t.trid = %d 
                WHERE t.language_code = %s", $job->trid, $job->source_language_code));

            $job->original_doc_title = $original->post_title;
            $job->original_doc_id = $original->element_id;
            $job->original_post_type = $original->post_type;
        }
        
        $_ld = $sitepress->get_language_details($job->source_language_code);
        $job->from_language = $_ld['display_name'];
        $_ld = $sitepress->get_language_details($job->language_code);
        $job->to_language = $_ld['display_name'];
        
        if(!$include_non_translatable_elements){
            $jelq = ' AND field_translate = 1';
        }else{
            $jelq = '';
        }
        $job->elements = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translate WHERE job_id = %d {$jelq} ORDER BY tid ASC", $job_id));        
        if($job->translator_id == 0 || $job->status == ICL_TM_WAITING_FOR_TRANSLATOR){
            if($auto_assign){
                $wpdb->update($wpdb->prefix . 'icl_translate_job', array('translator_id' => $this->current_translator->translator_id), array('job_id'=>$job_id));
                $wpdb->update($wpdb->prefix . 'icl_translation_status', 
                    array('translator_id' => $this->current_translator->translator_id, 'status' => ICL_TM_IN_PROGRESS), 
                    array('rid'=>$job->rid)
                );
            }
        }elseif($job->translator_id != @intval($this->current_translator->translator_id) && !defined('XMLRPC_REQUEST') && $job->manager_id != $current_user->ID){            
            static $erroronce = array();
            if(empty($erroronce[$job_id])){
                $this->messages[] = array(
                    'type' => 'error', 'text' => sprintf(__("You can't translate this document. It's assigned to a different translator.<br /> Document: <strong>%s</strong> (ID = %d).", 'sitepress') , $job->original_doc_title, $job_id)
                );
                $erroronce[$job_id] = true;
            }
            return false;
        }
        
        //do we have a previous version
        if($revisions > 0){
            $prev_version_job_id = $wpdb->get_var($wpdb->prepare("
                SELECT MAX(job_id) 
                FROM {$wpdb->prefix}icl_translate_job 
                WHERE rid=%d AND job_id < %d", $job->rid, $job_id));            
            if($prev_version_job_id){
                $job->prev_version = $this->get_translation_job($prev_version_job_id, false, false, $revisions - 1);
            }   
            
        }
        
        // allow adding custom elements
        $job->elements = apply_filters('icl_job_elements', $job->elements, $post_id, $job_id);
        
        return $job;    
    }
    
    function get_translation_job_id($trid, $language_code){
        global $wpdb, $sitepress;
        
        $job_id = $wpdb->get_var($wpdb->prepare("
            SELECT tj.job_id FROM {$wpdb->prefix}icl_translate_job tj 
                JOIN {$wpdb->prefix}icl_translation_status ts ON tj.rid = ts.rid
                JOIN {$wpdb->prefix}icl_translations t ON ts.translation_id = t.translation_id
                WHERE t.trid = %d AND t.language_code='%s'
                ORDER BY tj.job_id DESC LIMIT 1                
        ", $trid, $language_code));
        
        return $job_id;
    }
        
    function _save_translation_field($tid, $field){
        global $wpdb;
        $update['field_data_translated'] = $this->encode_field_data($field['data'], $field['format']);
        if(isset($field['finished']) && $field['finished']){
            $update['field_finished'] = 1;
        }
        $wpdb->update($wpdb->prefix . 'icl_translate', $update, array('tid'=>$tid));    
    }

    
    function save_translation($data){
        global $wpdb, $sitepress, $sitepress_settings, $ICL_Pro_Translation;
                
        $is_incomplete = false;
        foreach($data['fields'] as $field){
            $this->_save_translation_field($field['tid'], $field);
            if(!isset($field['finished']) || !$field['finished']){
                $is_incomplete = true;        
            }
        }
        
        if(!empty($data['complete']) && !$is_incomplete){
            $wpdb->update($wpdb->prefix . 'icl_translate_job', array('translated'=>1), array('job_id'=>$data['job_id']));    
            $rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $data['job_id']));
            $translation_id = $wpdb->get_var($wpdb->prepare("SELECT translation_id FROM {$wpdb->prefix}icl_translation_status WHERE rid=%d", $rid));            
            $wpdb->update($wpdb->prefix . 'icl_translation_status', array('status'=>ICL_TM_COMPLETE, 'needs_update'=>0), array('rid'=>$rid));
            list($element_id, $trid) = $wpdb->get_row($wpdb->prepare("SELECT element_id, trid FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id), ARRAY_N);            
            $job = $this->get_translation_job($data['job_id'], true);
            
            $parts = explode('_', $job->original_doc_id);
            if ($parts[0] == 'external') {
                
                // Translations are saved in the string table for 'external' types
                
                $id = array_pop($parts);
                unset($parts[0]);
                $type = implode('_', $parts);

                foreach($job->elements as $field){
                    if ($field->field_translate) {
                        if (function_exists('icl_st_is_registered_string')) {
                            $string_id = icl_st_is_registered_string($type, $id . '_' . $field->field_type);
                            if (!$string_id) {
                                icl_register_string($type, $id . '_' . $field->field_type, $this->decode_field_data($field->field_data, $field->field_format));
                                $string_id = icl_st_is_registered_string($type, $id . '_' . $field->field_type);
                            }
                            if ($string_id) {
                                icl_add_string_translation($string_id, $job->language_code, $this->decode_field_data($field->field_data_translated, $field->field_format), ICL_STRING_TRANSLATION_COMPLETE);
                            }
                        }
                    }                    
                }
                
            } else {

                if(!is_null($element_id)){
                    $postarr['ID'] = $_POST['post_ID'] = $element_id;
                }
                            
                foreach($job->elements as $field){
                    switch($field->field_type){
                        case 'title': 
                            $postarr['post_title'] = $this->decode_field_data($field->field_data_translated, $field->field_format); 
                            break;                            
                        case 'body':
                            $postarr['post_content'] = $this->decode_field_data($field->field_data_translated, $field->field_format);
                            break;
                        case 'excerpt': 
                            $postarr['post_excerpt'] = $this->decode_field_data($field->field_data_translated, $field->field_format);
                            break;
                        case 'URL':
                            $postarr['post_name'] = $this->decode_field_data($field->field_data_translated, $field->field_format);
                            break;
                        case 'tags': 
                            $tags = $this->decode_field_data($field->field_data_translated, $field->field_format);
                            $original_tags = $this->decode_field_data($field->field_data, $field->field_format);
                            // create tags that don't exist
                            foreach($tags as $k=>$t){
                                $thetag = $sitepress->get_term_by_name_and_lang($t, 'post_tag', $job->language_code);
                                $tags[$k] = $t; // Save $t as we may have added @.lang to it
                                if(empty($thetag)){
                                    $the_original_tag = $sitepress->get_term_by_name_and_lang($original_tags[$k], 'post_tag', $job->source_language_code);
                                    $tmp = wp_insert_term($t, 'post_tag');
                                    if(!is_wp_error($tmp) && isset($tmp['term_taxonomy_id'])){
                                        $sitepress->set_term_translation($the_original_tag,
                                                                            $tmp['term_taxonomy_id'],
                                                                            'post_tag',
                                                                            $job->language_code,
                                                                            $job->source_language_code);
                                    }
                                }
                            }
                            $postarr['tags_input'] = join(',', $tags);
                            $postarr['tax_input']['post_tag'] = $tags;
                            
                            break;
                        case 'categories': 
                            $cats = $this->decode_field_data($field->field_data_translated, $field->field_format);
                            $original_cats = $this->decode_field_data($field->field_data, $field->field_format);
                            $missing_parents = array();
                                                        
                            foreach($cats as $k=>$c){
                                $parent_missing = false;                                                           
                                $thecat = $sitepress->get_term_by_name_and_lang($c, 'category', $job->language_code);
                                
                                if(empty($thecat)){
                                    $the_original_cat = $sitepress->get_term_by_name_and_lang($original_cats[$k], 'category', $job->source_language_code);
                                    if ($the_original_cat) {
                                        $the_original_cat_parent = $wpdb->get_var("SELECT parent FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=".$the_original_cat->term_taxonomy_id);
                                    } else {
                                        $the_original_cat_parent = false;
                                    }
                                    if($the_original_cat_parent){
                                        $op_tr = icl_object_id($the_original_cat_parent, 'category', false, $job->language_code);
                                        if (!$op_tr) {
                                            // The parent is missing. Possibly because we haven't created a translation of it yet
                                            $parent_missing = true;
                                        }
                                    }else{$op_tr = 0;}                                
                                    $tmp = wp_insert_term($c, 'category', array('parent'=>$op_tr));
                                    if(!is_wp_error($tmp) && isset($tmp['term_taxonomy_id'])){
                                        $sitepress->set_term_translation($the_original_cat,
                                                                            $tmp['term_taxonomy_id'],
                                                                            'category',
                                                                            $job->language_code,
                                                                            $job->source_language_code);
                                        
                                    }
                                    $cat_id = $tmp['term_id'];
                                }else{
                                    $cat_id = $thecat->term_id;
                                }
                                
                                $cat_ids[] = $cat_id;
                                
                                if ($parent_missing) {
                                    $missing_parents[$cat_id] = $the_original_cat_parent;
                                }
                            }

                            // Double check missing parents as they might be available now.
                            foreach ($missing_parents as $cat_id => $the_original_cat_parent) {
                                $op_tr = icl_object_id($the_original_cat_parent, 'category', false, $job->language_code);
                                $cat_trid = $sitepress->get_element_trid($cat_id,'tax_category');
                                wp_update_term($cat_id, 'category', array('parent' => $op_tr));
                                $wpdb->update($wpdb->prefix.'icl_translations', 
                                    array('language_code'=>$job->language_code, 'trid'=>$cat_trid, 'source_language_code'=>$job->source_language_code), 
                                    array('element_type'=>'tax_category','element_id'=>$cat_id));
                            }

                            $postarr['post_category'] = $cat_ids;
                            break;
                        default:
                            if(in_array($field->field_type, $sitepress->get_translatable_taxonomies(false, $job->original_post_type))){                            
                                $taxs = $this->decode_field_data($field->field_data_translated, $field->field_format);
                                
                                $original_taxs = $this->decode_field_data($field->field_data, $field->field_format);
                                $taxonomy = $field->field_type;
                                $alltaxs = $tax_ids = array();
                                foreach($taxs as $k=>$c){
                                    $thetax = $sitepress->get_term_by_name_and_lang($c, $taxonomy, $job->language_code);
                                    
                                    $taxs[$k] = $c; // Save $c as we may have added @.lang to it
                                    if(empty($thetax)){
                                        $the_original_tax = $sitepress->get_term_by_name_and_lang($original_taxs[$k], $taxonomy, $job->source_language_code);                                
                                        $the_original_tax_parent = $wpdb->get_var("SELECT parent FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=".$the_original_tax->term_taxonomy_id);
                                        if($the_original_tax_parent){
                                            $op_tr = icl_object_id($the_original_tax_parent, $taxonomy, false, $job->language_code);
                                        }else{$op_tr = 0;}                                
                                        
                                        $tmp = wp_insert_term($c, $taxonomy, array('parent'=>$op_tr));
                                        if(isset($tmp['term_taxonomy_id'])){                
                                            $sitepress->set_term_translation($the_original_tax,
                                                                            $tmp['term_taxonomy_id'],
                                                                            $taxonomy,
                                                                            $job->language_code,
                                                                            $job->source_language_code);
                                        }
                                        $tax_id = $tmp['term_id'];
                                         
                                    }else{
                                        $tax_id = $thetax->term_id;
                                    }                                
                                    $tax_ids[] = $tax_id;
                                    $alltaxs[] = $c;
                                }
                                
                                $taxonomy_obj = get_taxonomy($taxonomy);
                                // array = hierarchical, string = non-hierarchical.
                                if ($taxonomy_obj->hierarchical) {
                                    $postarr['tax_input'][$taxonomy] = $tax_ids;
                                } else {
                                    $postarr['tax_input'][$taxonomy] = $taxs;
                                }
                            }
                    }
                }
                            
                $original_post = get_post($job->original_doc_id);
                
                $postarr['post_author'] = $original_post->post_author;  
                $postarr['post_type'] = $original_post->post_type;
                
                if($sitepress_settings['sync_comment_status']){
                    $postarr['comment_status'] = $original_post->comment_status;
                }
                if($sitepress_settings['sync_ping_status']){
                    $postarr['ping_status'] = $original_post->ping_status;
                }
                if($sitepress_settings['sync_page_ordering']){
                    $postarr['menu_order'] = $original_post->menu_order;
                }
                if($sitepress_settings['sync_private_flag'] && $original_post->post_status=='private'){    
                    $postarr['post_status'] = 'private';
                }
                if($sitepress_settings['sync_post_date']){
                    $postarr['post_date'] = $original_post->post_date;
                }                
                
                
                if(is_null($element_id)){
                    $postarr['post_status'] = !$sitepress_settings['translated_document_status'] ? 'draft' : $original_post->post_status;
                } else {
                    // set post_status to the current post status.
                    $postarr['post_status'] = $wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts WHERE ID = ".$element_id);
                }
                
                if($original_post->post_parent){
                    $post_parent_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations 
                        WHERE element_type='post_{$original_post->post_type}' AND element_id='{$original_post->post_parent}'");
                    if($post_parent_trid){
                        $parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations 
                            WHERE element_type='post_{$original_post->post_type}' AND trid='{$post_parent_trid}' AND language_code='{$job->language_code}'");
                    }            
                }
                
                if(isset($parent_id) && $sitepress_settings['sync_page_parent']){
                    $_POST['post_parent'] = $postarr['post_parent'] = $parent_id;  
                    $_POST['parent_id'] = $postarr['parent_id'] = $parent_id;  
                }
                
                $_POST['trid'] = $trid;
                $_POST['lang'] = $job->language_code;
                $_POST['skip_sitepress_actions'] = true;
    
                $postarr = apply_filters('icl_pre_save_pro_translation', $postarr);

                if(isset($element_id)){ // it's an update so dont change the url
                    $postarr['post_name'] = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $element_id));
                }
                
                if(isset($element_id)){ // it's an update so dont change post date
                    $existing_post = get_post($element_id);
                    $postarr['post_date'] = $existing_post->post_date;
                    $postarr['post_date_gmt'] = $existing_post->post_date_gmt;
                }

                $new_post_id = wp_insert_post($postarr);
                
                // set taxonomies for users with limited caps
                if(!current_user_can('manage-categories') && !empty($postarr['tax_input'])){            
                    foreach($postarr['tax_input'] as $taxonomy => $terms){
                        wp_set_post_terms( $new_post_id, $terms, $taxonomy, FALSE ); // true to append to existing tags | false to replace existing tags
                    }
                }     
                                
                do_action('icl_pro_translation_saved', $new_post_id, $data['fields']);
                
                
                // Allow identical slugs
                $post_name = sanitize_title($postarr['post_title']);
                $post_name_rewritten = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $new_post_id));
                
                $post_name_base = $post_name;
                
                if($post_name != $post_name_rewritten){
                    $incr = 1;                    
                    do{
                        
                        $exists = $wpdb->get_var($wpdb->prepare("
                            SELECT p.ID FROM {$wpdb->posts} p
                                JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type=%s
                            WHERE p.ID <> %d AND t.language_code = %s AND p.post_name=%s
                        ", 'post_' . $postarr['post_type'], $new_post_id, $job->language_code, $post_name));
                        
                        if($exists){
                            $incr++;
                        }else{
                            break;
                        }
                        $post_name = $post_name_base . '-' . $incr;
                         
                    }while($exists);
                    
                    $wpdb->update($wpdb->posts, array('post_name' => $post_name), array('ID' => $new_post_id));
                }
                    
                    
                
                
    
                $ICL_Pro_Translation->_content_fix_links_to_translated_content($new_post_id, $job->language_code);
                
                // update body translation with the links fixed
                $new_post_content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID=%d", $new_post_id));
                foreach($job->elements as $jel){
                    if($jel->field_type=='body'){
                        $fields_data_translated = $this->encode_field_data($new_post_content, $jel->field_format);
                        break;
                    }
                }
                $wpdb->update($wpdb->prefix.'icl_translate', array('field_data_translated'=>$fields_data_translated), array('job_id'=>$data['job_id'], 'field_type'=>'body'));
                
                           
                // set stickiness
                //is the original post a sticky post?
                remove_filter('option_sticky_posts', array($sitepress,'option_sticky_posts')); // remove filter used to get language relevant stickies. get them all
                $sticky_posts = get_option('sticky_posts');
                $is_original_sticky = $original_post->post_type=='post' && in_array($original_post->ID, $sticky_posts);
                
                if($is_original_sticky && $sitepress_settings['sync_sticky_flag']){
                    stick_post($new_post_id);
                }else{
                    if($original_post->post_type=='post' && !is_null($element_id)){
                        unstick_post($new_post_id); //just in case - if this is an update and the original post stckiness has changed since the post was sent to translation
                    }
                }
                 
                //sync plugins texts
                foreach((array)$this->settings['custom_fields_translation'] as $cf => $op){
                    if ($op == 1) {
                        update_post_meta($new_post_id, $cf, get_post_meta($original_post->ID,$cf,true));
                    }
                }
                           
                // set specific custom fields
                $copied_custom_fields = array('_top_nav_excluded', '_cms_nav_minihome');    
                foreach($copied_custom_fields as $ccf){
                    $val = get_post_meta($original_post->ID, $ccf, true);
                    update_post_meta($new_post_id, $ccf, $val);
                }    
                
                // sync _wp_page_template
                if($sitepress_settings['sync_page_template']){
                    $_wp_page_template = get_post_meta($original_post->ID, '_wp_page_template', true);
                    if(!empty($_wp_page_template)){
                        update_post_meta($new_post_id, '_wp_page_template', $_wp_page_template);
                    }
                }
    
                   
                // set the translated custom fields if we have any.
                foreach((array)$this->settings['custom_fields_translation'] as $field_name => $val){
                    if ($val == 2) { // should be translated
                        // find it in the translation
                        foreach($job->elements as $name => $eldata) {
                            if ($eldata->field_data == $field_name) {
                                if (preg_match("/field-(.*?)-name/", $eldata->field_type, $match)) {
                                    $field_id = $match[1];                                
                                    foreach($job->elements as $k => $v){
                                        if($v->field_type=='field-'.$field_id){
                                            $field_translation = $this->decode_field_data($v->field_data_translated, $v->field_format) ;
                                        }
                                        if($v->field_type=='field-'.$field_id.'-type'){
                                            $field_type = $v->field_data;
                                        }
                                    }
                                    if ($field_type == 'custom_field') {
                                        $field_translation = str_replace ( '&#0A;', "\n", $field_translation );
                                        // always decode html entities  eg decode &amp; to &
                                        $field_translation = html_entity_decode($field_translation);
                                        update_post_meta($new_post_id, $field_name, $field_translation);
                                    }
                                }
                            }
                        }
                    }
                }
                $link = get_edit_post_link($new_post_id);
                if ($link == '') {
                    // the current user can't edit so just include permalink
                    $link = get_permalink($new_post_id);
                }
                if(is_null($element_id)){
                    $wpdb->update($wpdb->prefix.'icl_translations', array('element_id' => $new_post_id), array('translation_id' => $translation_id) );
                    $user_message = __('Translation added: ', 'sitepress') . '<a href="'.$link.'">' . $postarr['post_title'] . '</a>.';
                }else{
                    $user_message = __('Translation updated: ', 'sitepress') . '<a href="'.$link.'">' . $postarr['post_title'] . '</a>.';                
                }
            }                                                  
                        
            
            $this->messages[] = array(
                'type'=>'updated',
                'text' => $user_message
            );
            
            
            if($this->settings['notification']['completed'] != ICL_TM_NOTIFICATION_NONE){
                require_once ICL_PLUGIN_PATH . '/inc/translation-management/tm-notification.class.php';
                if($data['job_id']){
                    $tn_notification = new TM_Notification();
                    $tn_notification->work_complete($data['job_id'], !is_null($element_id));
                }
            }
            
            self::set_page_url($new_post_id);
            
            // redirect to jobs list
            wp_redirect(admin_url(sprintf('admin.php?page=%s&%s=%d', 
                WPML_TM_FOLDER . '/menu/translations-queue.php', is_null($element_id) ? 'added' : 'updated', is_null($element_id) ? $new_post_id : $element_id )));
            
        }else{
            $this->messages[] = array('type'=>'updated', 'text' => __('Translation (incomplete) saved.', 'sitepress'));
        }
        
    }
    
    // returns a front end link to a post according to the user access
    // hide_empty - if current user doesn't have access to the link don't show at all
    public function tm_post_link($post_id, $anchor = false, $hide_empty = false){
        global $current_user;
        get_currentuserinfo();
        
        $parts = explode('_', $post_id);
        if ($parts[0] == 'external') {
            
            $link = '';
            return apply_filters('WPML_get_link', $link, $post_id, $anchor, $hide_empty);
        }
        
        if(false === $anchor){
            $anchor = get_the_title($post_id);    
        }
        
        $opost = get_post($post_id);
        if(($opost->post_status == 'draft' || $opost->post_status == 'private') && $opost->post_author != $current_user->data->ID){
            if($hide_empty){
                $elink = '';                
            }else{
                $elink = sprintf('<i>%s</i>', $anchor);
            }
        }else{
            $elink = sprintf('<a href="%s">%s</a>', get_permalink($post_id), $anchor);    
        }
        
        return $elink;
        
    }
    
    public function tm_post_permalink($post_id){
        global $current_user;
        get_currentuserinfo();
        
        $parts = explode('_', $post_id);
        if ($parts[0] == 'external') {
            
            return '';
        }
        
        $opost = get_post($post_id);
        if(($opost->post_status == 'draft' || $opost->post_status == 'private') && $opost->post_author != $current_user->data->ID){
            $elink = '';                
        }else{
            $elink = get_permalink($post_id);    
        }
        
        return $elink;
        
    }
 
    //
    // when the translated post was created, we have the job_id and need to update the job
    function save_job_fields_from_post($job_id, $post){
        global $wpdb, $sitepress;
        $data['complete'] = 1;
        $data['job_id'] = $job_id;        
        $job = $this->get_translation_job($job_id,1);
        
        if(is_array($job->elements))
        foreach($job->elements as $element){
            $field_data = '';
            switch($element->field_type){
                case 'title':
                    $field_data = $this->encode_field_data($post->post_title, $element->field_format);
                    break;
                case 'body':
                    $field_data = $this->encode_field_data($post->post_content, $element->field_format);
                    break;
                case 'excerpt':
                    $field_data = $this->encode_field_data($post->post_excerpt, $element->field_format);
                    break;                           
                default:
                    if(false !== strpos($element->field_type, 'field-') && !empty($this->settings['custom_fields_translation'])){                                
                        $cf_name = preg_replace('#^field-#', '', $element->field_type);
                        if(isset($this->settings['custom_fields_translation'][$cf_name])){
                            if($this->settings['custom_fields_translation'][$cf_name] == 1){ //copy
                                $field_data = get_post_meta($original_post->ID, $cf_name, 1);               
                                if(is_scalar($field_data))
                                $field_data = $this->encode_field_data($field_data, $element->field_format);                             
                                else $field_data = '';
                            }elseif($this->settings['custom_fields_translation'][$cf_name] == 2){ // translate
                                $field_data = get_post_meta($post->ID, $cf_name, 1);             
                                if(is_scalar($field_data))
                                $field_data = $this->encode_field_data($field_data, $element->field_format);                             
                                else $field_data = '';
                            }
                        }
                    }else{
                        if(in_array($element->field_type, $sitepress->get_translatable_taxonomies(true, $post->post_type))){                            
                            $ids = array();
                            foreach($job->elements as $je){
                                if($je->field_type == $element->field_type .'_ids' ){
                                    $ids = explode(',', $je->field_data);
                                }
                            }
                            $translated_tax_names = array();
                            foreach($ids as $id){
                                $translated_tax_id = icl_object_id($id, $element->field_type,false,$job->language_code);
                                if($translated_tax_id){
                                    $translated_tax_names[] = $wpdb->get_var($wpdb->prepare("
                                        SELECT t.name FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON t.term_id = x.term_id
                                        WHERE x.term_taxonomy_id = %d
                                    ", $translated_tax_id));
                                }
                            }
                            $field_data = $this->encode_field_data($translated_tax_names, $element->field_format);
                            
                        }
                    }
            }            
            $wpdb->update($wpdb->prefix.'icl_translate', 
                array('field_data_translated'=>$field_data, 'field_finished'=>1), 
                array('tid'=>$element->tid)
            );
            
        }   
        
        $this->mark_job_done($job_id);                

    }
    
    public function determine_translated_taxonomies($elements, $taxonomy, $translated_language){
        global $sitepress, $wpdb;
        foreach($elements as $k=>$element){
            $term = get_term_by('name', $element, $taxonomy);
            if ($term) {
                $trid = $sitepress->get_element_trid($term->term_taxonomy_id, 'tax_' . $taxonomy);
                $translations = $sitepress->get_element_translations($trid, 'tax_' . $taxonomy);
                if(isset($translations[$translated_language])){
                    $translated_elements[$k] = $translations[$translated_language]->name;   
                }else{
                    $translated_elements[$k] = '';
                }
            } else {
                $translated_elements[$k] = '';
            }
        }
        
        return $translated_elements;
    }
    
    function mark_job_done($job_id){
        global $wpdb;
        $wpdb->update($wpdb->prefix.'icl_translate_job', array('translated'=>1), array('job_id'=>$job_id));
        $wpdb->update($wpdb->prefix.'icl_translate', array('field_finished'=>1), array('job_id'=>$job_id));
    }
    
    function resign_translator($job_id){
        global $wpdb;
        list($translator_id, $rid) = $wpdb->get_row($wpdb->prepare("SELECT translator_id, rid FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id), ARRAY_N);
        
        if(!empty($translator_id)){
            if($this->settings['notification']['resigned'] != ICL_TM_NOTIFICATION_NONE){
                require_once ICL_PLUGIN_PATH . '/inc/translation-management/tm-notification.class.php';
                if($job_id){
                    $tn_notification = new TM_Notification();
                    $tn_notification->translator_resigned($translator_id, $job_id);
                }
            }
        }
        
        $wpdb->update($wpdb->prefix.'icl_translate_job', array('translator_id'=>0), array('job_id'=>$job_id));
        $wpdb->update($wpdb->prefix.'icl_translation_status', array('translator_id'=>0, 'status'=>ICL_TM_WAITING_FOR_TRANSLATOR), array('rid'=>$rid));
    }
    
    // $translation_id - int or array
    function cancel_translation_request($translation_id){
        global $wpdb;
        
        if(is_array($translation_id)){
            foreach($translation_id as $id){
                $this->cancel_translation_request($id);
            }
        }else{

            $rid = $wpdb->get_var($wpdb->prepare("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id));
            $job_id = $wpdb->get_var($wpdb->prepare("SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NULL ", $rid));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id));
            
            $max_job_id = $wpdb->get_var($wpdb->prepare("SELECT MAX(job_id) FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d", $rid));
            if($max_job_id){
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE job_id=%d", $max_job_id));
                $_prevstate = $wpdb->get_var($wpdb->prepare("SELECT _prevstate FROM {$wpdb->prefix}icl_translation_status WHERE translation_id = %d", $translation_id));
                if(!empty($_prevstate)){                
                    $_prevstate = unserialize($_prevstate);
                    $wpdb->update($wpdb->prefix . 'icl_translation_status', 
                        array(
                            'status'                => $_prevstate['status'], 
                            'translator_id'         => $_prevstate['translator_id'], 
                            'status'                => $_prevstate['status'], 
                            'needs_update'          => $_prevstate['needs_update'], 
                            'md5'                   => $_prevstate['md5'], 
                            'translation_service'   => $_prevstate['translation_service'], 
                            'translation_package'   => $_prevstate['translation_package'], 
                            'timestamp'             => $_prevstate['timestamp'], 
                            'links_fixed'           => $_prevstate['links_fixed'] 
                        ), 
                        array('translation_id'=>$translation_id)
                    ); 
                }                
            }else{
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id));
            }
            
            // delete record from icl_translations if trid is null
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d AND element_id IS NULL", $translation_id));
        }
        
    }    
    
    /* WPML CONFIG */
    /* ******************************************************************************************** */           
    function _parse_wpml_config($file){
        global $sitepress, $sitepress_settings;
        
        $config = icl_xml2array(file_get_contents($file));    
        
        // custom fields
        if(!empty($config['wpml-config']['custom-fields'])){
            if(!is_numeric(key(current($config['wpml-config']['custom-fields'])))){
                $cf[0] = $config['wpml-config']['custom-fields']['custom-field'];
            }else{
                $cf = $config['wpml-config']['custom-fields']['custom-field'];
            }
            foreach($cf as $c){
                if($c['attr']['action'] == 'translate'){
                    $action = 2;
                }elseif($c['attr']['action'] == 'copy'){
                    $action = 1;
                }else{
                    $action = 0;
                }
                $this->settings['custom_fields_translation'][$c['value']] = $action;        
                if(is_array($this->settings['custom_fields_readonly_config']) && !in_array($c['value'], $this->settings['custom_fields_readonly_config'])){
                $this->settings['custom_fields_readonly_config'][] = $c['value'];
            }
                
        }  
        }  
        
        // custom types
        $cf = array();        
        if(!empty($config['wpml-config']['custom-types'])){            
            if(!is_numeric(key(current($config['wpml-config']['custom-types'])))){
                $cf[0] = $config['wpml-config']['custom-types']['custom-type'];
            }else{
                $cf = $config['wpml-config']['custom-types']['custom-type'];
            }
            foreach($cf as $c){                
                $translate = intval($c['attr']['translate']);
                if($translate){
                    $sitepress->verify_post_translations($c['value']);                
                }
                $this->settings['custom_types_readonly_config'][$c['value']] = $translate;
                $sitepress_settings['custom_posts_sync_option'][$c['value']] = $translate;
            }            
            add_filter('get_translatable_documents', array($this, '_override_get_translatable_documents'));  
        }
        
        // taxonomies        
        $cf = array();
        if(!empty($config['wpml-config']['taxonomies'])){
            if(!is_numeric(key(current($config['wpml-config']['taxonomies'])))){
                $cf[0] = $config['wpml-config']['taxonomies']['taxonomy'];
            }else{
                $cf = $config['wpml-config']['taxonomies']['taxonomy'];
            }
            
            
            
            
            foreach($cf as $c){                
                
                // did this exist before?
                $empty_before = empty($this->settings['taxonomies_readonly_config']);    
                
                $translate = intval($c['attr']['translate']);
                $this->settings['taxonomies_readonly_config'][$c['value']] = $translate;                
                $sitepress_settings['taxonomies_sync_option'][$c['value']] = $translate;
                
                // this has just change. save.
                if($translate == 1 && $empty_before){
                    $sitepress->verify_taxonomy_translations($c['value']);                
                    $this->save_settings();
                }
            }   
            add_filter('get_translatable_taxonomies', array($this, '_override_get_translatable_taxonomies'));  
        }  
        
        // admin texts
        if(function_exists('icl_register_string')){
            $admin_texts = array();
            if(!empty($config['wpml-config']['admin-texts'])){
                
                $type = (dirname($file) == get_template_directory() || dirname($file) == get_stylesheet_directory()) ? 'theme' : 'plugin';
                $atid = basename(dirname($file));                                    
                
                if( !is_numeric(key(@current($config['wpml-config']['admin-texts'])))){
                    $admin_texts[0] = $config['wpml-config']['admin-texts']['key'];
                }else{
                    $admin_texts = $config['wpml-config']['admin-texts']['key'];
                }
                
                foreach($admin_texts as $a){
                    $keys = array(); 
                    if(!isset($a['key'])){
                        $arr[$a['attr']['name']] = 1;                                
                        continue;
                    }elseif(!is_numeric(key($a['key']))){                    
                        $keys[0] = $a['key'];
                    }else{
                        $keys = $a['key'];
                    }            
                    
                    foreach($keys as $key){                
                        if(isset($key['key'])){
                            $arr[$a['attr']['name']][$key['attr']['name']] = $this->_read_admin_texts_recursive($key['key']);
                        }else{
                            $arr[$a['attr']['name']][$key['attr']['name']] = 1;                                
                        }
                    }
                }
                            
                foreach($arr as $key => $v){
                    // http://forum.wpml.org/topic.php?id=4888&replies=7#post-23038
                    // http://forum.wpml.org/topic.php?id=4615
                    // http://forum.wpml.org/topic.php?id=4761
                    remove_filter('option_'.$key, 'icl_st_translate_admin_string');   // dont try to translate this one below      
                    $value = get_option($key);
                    add_filter('option_'.$key, 'icl_st_translate_admin_string');       // put the filter back on     
                    
                    $value = maybe_unserialize($value);       
                    if(false === $value){                    
                        unset($arr[$key]);
                    }if(is_scalar($value)){                    
                        icl_register_string('admin_texts_' . $type . '_' . $atid, $key , $value);    
                    }else{                    
                        if(is_object($value)) $value = (array)$value;                    
                        if(!empty($value)){
                            $this->_register_string_recursive($key, $value, $arr[$key], '', $type . '_' . $atid);    
                        }
                    }
                }

                $this->admin_texts_to_translate = array_merge($this->admin_texts_to_translate, $arr);                        
                $_icl_admin_option_names = get_option('_icl_admin_option_names');
                
                //filter out obsolete entries
                /*
                $_as_changed = false;
                foreach($_icl_admin_option_names[$type][$atid] as $k=>$csf){
                    if(empty($arr[$csf])){
                        unset($_icl_admin_option_names[$type][$atid][$k]);    
                    }
                }
                */
                                
                $_icl_admin_option_names[$type][$atid] = @array_merge_recursive((array)$_icl_admin_option_names[$type][$atid], $this->_array_keys_recursive($arr));
                $_icl_admin_option_names[$type][$atid] = __array_unique_recursive($_icl_admin_option_names[$type][$atid]);
                // __array_unique_recursive is declared in the string translation plugin inc/functions.php
                
                update_option('_icl_admin_option_names', $_icl_admin_option_names);            
                
            }
        }  
        
        // language-switcher-settings
        if(empty($sitepress_settings['language_selector_initialized']) || (isset($_GET['restore_ls_settings']) && $_GET['restore_ls_settings'] == 1)){
            if(!empty($config['wpml-config']['language-switcher-settings'])){
                
                if(!is_numeric(key($config['wpml-config']['language-switcher-settings']['key']))){
                    $cfgsettings[0] = $config['wpml-config']['language-switcher-settings']['key'];
                }else{
                    $cfgsettings = $config['wpml-config']['language-switcher-settings']['key'];
                }
                $iclsettings = $this->_read_settings_recursive($cfgsettings);
                
                $iclsettings['language_selector_initialized'] = 1;
                
                $sitepress->save_settings($iclsettings);
                
                if(!empty($sitepress_settings['setup_complete']) && !empty($_GET['page'])){
                    wp_redirect(admin_url('admin.php?page='.$_GET['page'].'&icl_ls_reset=default#icl_save_language_switcher_options'));
                }
            }
        }
        
    }
    
    function _array_keys_recursive($arr){          
        $arr_rec_ret = array();
        foreach((array)$arr as $k=>$v){            
            if(is_array($v)){                
                $arr_rec_ret[$k] = $this->_array_keys_recursive($v);
            }else{
                $arr_rec_ret[] = $k;        
            }
        }
        return $arr_rec_ret;
    }
    
    function _read_admin_texts_recursive($keys){
        if(!is_numeric(key($keys))){
            $_keys = array($keys);
            $keys = $_keys;
            unset($_keys);
        }
        foreach($keys as $key){                
            if(isset($key['key'])){
                $arr[$key['attr']['name']] = $this->_read_admin_texts_recursive($key['key']);                    
            }else{
                $arr[$key['attr']['name']] = 1;                            
            }
        }
        return $arr;
    }
    
    function _register_string_recursive($key, $value, $arr, $prefix = '', $suffix){        
        if(is_scalar($value)){
            if(!empty($value) && $arr == 1){
                icl_register_string('admin_texts_' . $suffix, $prefix . $key , $value);
            }
        }else{
            if(!is_null($value)){
                foreach($value as $sub_key=>$sub_value){
                    if(isset($arr[$sub_key])){
                        $this->_register_string_recursive($sub_key, $sub_value, $arr[$sub_key], $prefix . '[' . $key .']', $suffix);    
                    }
                }
            }
        }
    }
    
    function _read_settings_recursive($cfgsettings){
        foreach($cfgsettings as $s){
            if(isset($s['key'])){
                if(!is_numeric(key($s['key']))){
                    $skey[0] = $s['key'];
                }else{
                    $skey = $s['key'];
                }
                $iclsettings[$s['attr']['name']] = $this->_read_settings_recursive($skey);
            }else{
                $iclsettings[$s['attr']['name']] = $s['value'];
            }    
        }        
        return $iclsettings;
    }
    
    function render_option_writes($option_name, $option_value, $option_key=''){
        if(!defined('WPML_ST_FOLDER')) return;
        static $option;
        if(!$option_key){
            $option = maybe_unserialize(get_option($option_name));
            if(is_object($option)){
                $option = (array)$option;
            }
        }
        
        $option_names = get_option('_icl_admin_option_names');        
        
        // determine theme/plugin name (string context)
        if(!empty($option_names['theme'])){
            foreach((array)$option_names['theme'][basename(get_template_directory())] as $ops=>$val){
                
                if(!empty($option_key)){
                    $int = preg_match_all('#\[([^\]]+)\]#', $option_key, $matches);
                    if($int) $opname = $matches[1][0];
                }else{
                    $opname = $option_name;
                }
                
                if($ops == $opname){
                    $es_context = 'admin_texts_theme_' . basename(get_template_directory());
                    break;
                }
            }
            
            if(get_template_directory() != get_stylesheet_directory()){
                foreach((array)$option_names['theme'][basename(get_stylesheet_directory())] as $ops=>$val){

                    if(!empty($option_key)){
                        $int = preg_match_all('#\[([^\]]+)\]#', $option_key, $matches);
                        if($int) $opname = $matches[1][0];
                    }else{
                        $opname = $option_name;
                    }
                    
                    if(is_scalar($ops) && $ops == $opname){
                        $es_context = 'admin_texts_theme_' . get_stylesheet_directory();
                        break;
                    }
                }
            }
        }
        
        if(!empty($option_names['plugin'])){
            foreach((array)$option_names['plugin'] as $plugin => $options){
                foreach($options as $okey => $ops){
                    if(is_scalar($ops) && $ops == $option_name){
                        $es_context = 'admin_texts_plugin_' . $plugin;
                        break;
                    }elseif(is_array($ops) && is_array($option_value) && (array_values($ops) == array_values(array_keys($option_value)))){
                        $es_context = 'admin_texts_plugin_' . $plugin;
                    }
                }            
            }
            
        }
        
        
        echo '<ul class="icl_tm_admin_options">';
        echo '<li>';
        
        
        if(is_scalar($option_value)){
            $int = preg_match_all('#\[([^\]]+)\]#', $option_key, $matches);
            
            if(count($matches[1]) > 1){
                $value = $option;
                for($i = 1; $i < count($matches[1]); $i++){
                    $value = $value[$matches[1][$i]];
                }
                $value = $value[$option_name];
                $edit_link = '';
            }else{
                if(is_scalar($option)){
                    $value = $option;
                }elseif(isset($option[$option_name])){
                    $value = $option[$option_name];    
                }else{
                    $value = '';
                }

                if(!$option_key){
                    $edit_link = '[<a href="'.admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&context='.$es_context) . '">' .
                     __('translate', 'sitepress') . '</a>]';
                }else{
                    $edit_link = '';
                }
            }
            
            echo '<li>' . $option_name . ': <i>' . $value . '</i> ' . $edit_link . '</li>';
        }else{  
            $edit_link = '[<a href="'.admin_url('admin.php?page='.WPML_ST_FOLDER.'/menu/string-translation.php&context='.$es_context) . '">' .
                     __('translate', 'sitepress') . '</a>]';          
            echo '<strong>' . $option_name . '</strong> ' . $edit_link;
            foreach((array)$option_value as $key=>$value){
                $this->render_option_writes($key, $value, $option_key . '[' . $option_name . ']');                
            }            
        }
        echo '</li>';
        echo '</ul>';        
    }
        
    function _override_get_translatable_documents($types){
        global $wp_post_types;
        foreach($types as $k=>$type){
            if(isset($this->settings['custom_types_readonly_config'][$k]) && !$this->settings['custom_types_readonly_config'][$k]){
                unset($types[$k]);
            }
        }
        foreach($this->settings['custom_types_readonly_config'] as $cp=>$translate){
            if($translate && !isset($types[$cp]) && isset($wp_post_types[$cp])){
                $types[$cp] = $wp_post_types[$cp];
            }
        }
        return $types;
    }

    function _override_get_translatable_taxonomies($taxs_obj_type){                
        global $wp_taxonomies, $sitepress;
        
        $taxs = $taxs_obj_type['taxs'];        
        
        $object_type = $taxs_obj_type['object_type'];
        foreach($taxs as $k=>$tax){
            if(!$sitepress->is_translated_taxonomy($tax)){
                unset($taxs[$k]);
            }
        }
        
        foreach($this->settings['taxonomies_readonly_config'] as $tx=>$translate){
            if($translate && !in_array($tx, $taxs) && isset($wp_taxonomies[$tx]) && in_array($object_type, $wp_taxonomies[$tx]->object_type)){
                $taxs[] = $tx;
            }
        }        
        
        $ret = array('taxs'=>$taxs, 'object_type'=>$taxs_obj_type['object_type']);
        
        return $ret;
    }
    
    function load_plugins_wpml_config(){
        $plugins = get_option('active_plugins');
        foreach($plugins as $p){
            $config_file = ABSPATH . '/' . PLUGINDIR . '/' . dirname($p) . '/wpml-config.xml';
            if(trim(dirname($p),'\/.') && file_exists($config_file)){
                $this->_parse_wpml_config($config_file);
            }
        }
        
        $mu_plugins = wp_get_mu_plugins();
        if(!empty($mu_plugins)){
            foreach($mu_plugins as $mup){
                if(rtrim(dirname($mup), '/') != WPMU_PLUGIN_DIR){
                    $config_file = dirname($mup) . '/wpml-config.xml';                         
                    $this->_parse_wpml_config($config_file);
                }
            }
        }        
    }    

    function load_theme_wpml_config(){
        if(get_template_directory() != get_stylesheet_directory()){
            $config_file = get_stylesheet_directory().'/wpml-config.xml';
            if(file_exists($config_file)){
                $this->_parse_wpml_config($config_file);
            }
        }

        $config_file = get_template_directory().'/wpml-config.xml';
        if(file_exists($config_file)){
            $this->_parse_wpml_config($config_file);
        }
        
        
    }

    function load_config_pre_process(){
        $this->settings['__custom_types_readonly_config_prev'] = (isset($this->settings['custom_types_readonly_config']) && is_array($this->settings['custom_types_readonly_config'])) ? $this->settings['custom_types_readonly_config'] : array();
        $this->settings['custom_types_readonly_config'] = array();
        
        $this->settings['__custom_fields_readonly_config_prev'] = (isset($this->settings['custom_fields_readonly_config']) && is_array($this->settings['custom_fields_readonly_config'])) ? $this->settings['custom_fields_readonly_config'] : array();
        $this->settings['custom_fields_readonly_config'] = array();
        
    }
    
    function load_config_post_process(){

        $changed = false;
        foreach($this->settings['__custom_types_readonly_config_prev'] as $pk=>$pv){
            if(!isset($this->settings['custom_types_readonly_config'][$pk]) || $this->settings['custom_types_readonly_config'][$pk] != $pv){
                $changed = true;
                break;
            }                        
        }
        foreach($this->settings['custom_types_readonly_config'] as $pk=>$pv){
            if(!isset($this->settings['__custom_types_readonly_config_prev'][$pk]) || $this->settings['__custom_types_readonly_config_prev'][$pk] != $pv){
                $changed = true;
                break;
            }                        
        }
        
        foreach($this->settings['__custom_fields_readonly_config_prev'] as $cf){
            if(!in_array($cf, $this->settings['custom_fields_readonly_config'])){
                $changed = true;
                break;
            }                        
        }
        foreach($this->settings['custom_fields_readonly_config'] as $cf){          
            if(!in_array($cf, $this->settings['__custom_fields_readonly_config_prev'])){
                $changed = true;
                break;
            }                        
        }
        
        if($changed){
            $this->save_settings();    
        }
        
        
    }
    
    public static function icanlocalize_service_info($info = array()) {
        global $sitepress;
        $return = array();
        $return['name'] = 'ICanLocalize';
        $return['logo'] = ICL_PLUGIN_URL . '/res/img/web_logo_small.png';
        $return['setup_url'] = $sitepress->create_icl_popup_link('@select-translators;from_replace;to_replace@', array('ar' => 1), true);
        $return['description'] = __('Looking for a quality translation service? ICanLocalize offers excellent<br /> human service done by expert translators, starts at only $0.09 per word.', 'sitepress');
        $info['icanlocalize'] = $return;
        return $info;
    }

    public function clear_cache() {
        global $wpdb;
        delete_option($wpdb->prefix . 'icl_translators_cached');
        delete_option($wpdb->prefix . 'icl_non_translators_cached');
    }
    
    // shows post content for visual mode (iframe) in translation editor
    function _show_post_content(){
        global $tinymce_version;
        if($id = @intval($_GET['post_id'])){
            $post = get_post($id);
            if($post){
                if(@intval($_GET['rtl'])){
                    $rtl = ' dir="rtl"';
                }else{
                    $rtl = '';
                }
                echo '<html'.$rtl.'>';
                echo '<head>';
                $csss = array(
                    '/' . WPINC . '/js/tinymce/themes/advanced/skins/wp_theme/content.css?ver='.$tinymce_version,
                    '/' . WPINC . '/js/tinymce/plugins/spellchecker/css/content.css?ver='.$tinymce_version,
                    '/' . WPINC . '/js/tinymce/plugins/wordpress/css/content.css?ver='.$tinymce_version    
                );
                foreach($csss as $css){
                    echo '<link rel="stylesheet" href="'.get_option('siteurl') . $css . '">' . "\n";
                }
                echo '</head>';
                echo '<body>';
                switch($_GET['field_type']){                    
                    default: 
                        if(0 === strpos($_GET['field_type'], 'field-')){
                            echo get_post_meta($id, preg_replace('#^field-#', '', $_GET['field_type']), true);
                        }else{
                            remove_filter('the_content', 'do_shortcode', 11);
                            echo apply_filters('the_content', $post->post_content);
                        }
                }
                echo '</body>';
                echo '</html>';
                exit;
            }else{
                wp_die(__('Post not found!', 'sitepress'));
            }
            exit;
        }else{
            wp_die(__('Post not found!', 'sitepress'));
        }
    }
    
    function _user_search(){
        $q = $_POST['q'];
        
        $non_translators = $this->get_blog_not_translators();
        
        $matched_users = array();
        foreach($non_translators as $t){
            if(false !== stripos($t->user_login, $q) || false !== stripos($t->display_name, $q)){
                $matched_users[] = $t;        
            }
            if(count($matched_users) == 100) break;
        }
        
        if(!empty($matched_users)){
            $cssheight  = count($matched_users) > 10 ? '200' : 20*count($matched_users) + 5;
            echo '<select size="10" class="icl_tm_auto_suggest_dd" style="height:'.$cssheight.'px">';
            foreach($matched_users as $u){
                echo '<option value="' . $u->ID . '|' . esc_attr($u->display_name).'">'.$u->display_name . ' ('.$u->user_login.')'.'</option>';
            
            }
            echo '</select>';
            
        }else{
            echo '&nbsp;<span id="icl_user_src_nf">';
            _e('No matches', 'sitepress');
            echo '</span>';
        }
        
        
        exit;
        
    }
    
    function make_duplicates($data){
        foreach($data['iclpost'] as $master_post_id){
            foreach($data['duplicate_to'] as $lang => $one){
                $this->make_duplicate($master_post_id, $lang);                        
            }
        }
    }
    
    function make_duplicate($master_post_id, $lang){
        global $sitepress, $wpdb;
        
        $master_post = get_post($master_post_id);
        
        $trid = $sitepress->get_element_trid($master_post_id, 'post_' . $master_post->post_type);
        if($trid){
            $translations = $sitepress->get_element_translations($trid, 'post_' . $master_post->post_type);    
            
            if(isset($translations[$lang])){
                $postarr['ID'] = $translations[$lang]->element_id;    
            }
            
        }
        
        $postarr['post_author']     = $master_post->post_author; 
        $postarr['post_date']       = $master_post->post_date; 
        $postarr['post_date_gmt']   = $master_post->post_date_gmt; 
        $postarr['post_content']    = $master_post->post_content; 
        $postarr['post_title']      = $master_post->post_title;
        $postarr['post_excerpt']    = $master_post->post_excerpt;
        $postarr['post_status']     = $master_post->post_status;
        $postarr['comment_status']  = $master_post->comment_status;
        $postarr['ping_status']     = $master_post->ping_status;
        $postarr['post_name']       = $master_post->post_name;
        
        if($master_post->post_parent){
            $parent = icl_object_id($master_post->post_parent, $master_post->post_type, false, $lang);
            $postarr['post_parent']     = $parent;
        }
        
        $postarr['menu_order']     = $master_post->menu_order;
        $postarr['post_type']      = $master_post->post_type;
        $postarr['post_mime_type'] = $master_post->post_mime_type;

        
        $trid = $sitepress->get_element_trid($master_post->ID, 'post_' . $master_post->post_type); 
                
        $_POST['icl_trid'] = $trid;
        $_POST['icl_post_language'] = $lang;
        $_POST['skip_sitepress_actions'] = true;
                             
        if(isset($postarr['ID'])){
            $id = wp_update_post($postarr);
        }else{
            $id = wp_insert_post($postarr);
        }
        
        global $ICL_Pro_Translation;
        $ICL_Pro_Translation->_content_fix_links_to_translated_content($id, $lang);
        
        if(!is_wp_error($id)){
            
            $sitepress->set_element_language_details($id, 'post_' . $master_post->post_type, $trid, $lang);
            
            $this->save_post_actions($id, get_post($id), $force_set_status = ICL_TM_DUPLICATE);

            $this->duplicate_fix_children($master_post_id, $lang);
            
            // dup comments
            if($sitepress->get_option('sync_comments_on_duplicates')){
                $this->duplicate_comments($master_post_id, $lang);    
            }
            
            // make sure post name is copied
            $wpdb->update($wpdb->posts, array('post_name'=>$master_post->post_name), array('ID'=>$id));
            
            update_post_meta($id, '_icl_lang_duplicate_of', $master_post->ID);
                        
            // copy custom fields
            $custom_fields = get_post_custom($master_post_id);
            foreach($custom_fields as $key => $values){
                
                // skip some
                if(in_array($key, array('_wp_old_slug', '_edit_last', '_edit_lock', '_icl_translator_note'))) continue;
                
                delete_post_meta($id, $key);        
                foreach($values as $value){
                    add_post_meta($id, $key, maybe_unserialize($value));        
                }
                
            }
            
            // sync taxonomies
            $taxonomies = array_values(get_taxonomies());
            //exclude_some
            $taxonomies = array_diff($taxonomies, array('nav_menu', 'link_category'));
            
            foreach($taxonomies as $taxonomy){
                $is_translated = $sitepress->is_translated_taxonomy($taxonomy);
                $terms = wp_get_post_terms($master_post_id, $taxonomy);            
                if(!empty($terms)){
                    $terms_array = array();
                    foreach($terms as $t){
                        if($is_translated){
                            $tr_id = icl_object_id($t->term_id, $taxonomy, false, $lang);
                            if($tr_id){                                
                                if(is_taxonomy_hierarchical($taxonomy)){
                                    $terms_array[] = $tr_id;
                                }else{
                                    $tr_term = get_term($tr_id, $taxonomy);
                                    $terms_array[] = $tr_term->name;                                    
                                }
                            }
                        }else{
                            if(is_taxonomy_hierarchical($taxonomy)){
                                $terms_array[] = $t->term_id;                                        
                            }else{
                                $terms_array[] = $t->name;        
                            }
                        }
                    } 
                    wp_set_post_terms($id, $terms_array, $taxonomy);
                }
            }
            $ret = $id;
            do_action('icl_make_duplicate', $master_post_id, $lang, $postarr, $id);
            
        }else{
            $ret = false;
        }
        
        
        
        return $ret;
        
    }
    
    function duplicate_fix_children($master_post_id, $lang){
        global $wpdb;
        
        $post_type = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $master_post_id));
        $master_children = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_parent=%d AND post_type != 'revision'", $master_post_id));
        $dup_parent = icl_object_id($master_post_id, $post_type, false, $lang);
        
        if($master_children){
            foreach($master_children as $master_child){
                $dup_child = icl_object_id($master_child, $post_type, false, $lang);
                if($dup_child){
                    $wpdb->update($wpdb->posts, array('post_parent' => $dup_parent), array('ID' => $dup_child));                        
                }
                $this->duplicate_fix_children($master_child, $lang);
            }
        }
    } 
    
    function make_duplicates_all($master_post_id){
        global $sitepress;
        
        $master_post = get_post($master_post_id);
        $language_details_original = $sitepress->get_element_language_details($master_post_id, 'post_' . $master_post->post_type);
        
        $data['iclpost'] = array($master_post_id);
        foreach($sitepress->get_active_languages() as $lang => $details){
            if($lang != $language_details_original->language_code){
                $data['duplicate_to'][$lang] = 1;       
            }
        }
        
        $this->make_duplicates($data);
    }
    
    function reset_duplicate_flag($post_id){
        global $sitepress;

        $post = get_post($post_id);
        
        $trid = $sitepress->get_element_trid($post_id, 'post_' . $post->post_type);
        $translations = $sitepress->get_element_translations($trid, 'post_' . $post->post_type);
        
        foreach($translations as $tr){
            if($tr->element_id == $post_id){
                $this->update_translation_status(array(
                    'translation_id' => $tr->translation_id,
                    'status'         => ICL_TM_COMPLETE
                ));
            }
        }
        
        delete_post_meta($post_id, '_icl_lang_duplicate_of');        
        
        
    }
    
    function set_duplicate($post_id){
        global $sitepress;
        // find original (source) and copy
        $post = get_post($post_id);
        $trid = $sitepress->get_element_trid($post_id, 'post_' . $post->post_type);
        $translations = $sitepress->get_element_translations($trid, 'post_' . $post->post_type);
        
        foreach($translations as $lang => $tr){
            if($tr->original){
                $master_post_id = $tr->element_id;
            }elseif($tr->element_id == $post_id){
                $this_language = $lang;
            }
        }
        
        $this->make_duplicate($master_post_id, $this_language);
        
    }
    
    function get_duplicates($master_post_id){
        global $wpdb, $sitepress;
        
        $duplicates = array();
        
        $res = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key='_icl_lang_duplicate_of' AND meta_value=%d", $master_post_id));
            
        foreach($res as $post_id){
            
            $post = get_post($post_id);
            $language_details = $sitepress->get_element_language_details($post_id, 'post_' . $post->post_type);
            
            $duplicates[$language_details->language_code] = $post_id;
        }
        
        return $duplicates;
        
    }
    
    function duplicate_comments($post_id, $master_post_id){
        global $wpdb, $sitepress;
        
        // delete existing comments
        $current_comments = $wpdb->get_results($wpdb->prepare("SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d", $post_id));
        foreach($current_comments as $id){
            wp_delete_comment($id);
        }
        
        
        $original_comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->comments} WHERE comment_post_id = %d", $master_post_id), ARRAY_A);
        
        $post_type = $wpdb->get_var($wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id));
        $language = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post_type));

        $wpdb->update($wpdb->posts, array('post_count'=>count($original_comments)), array('ID'=>$post_id));        
    
        foreach($original_comments as $comment){
            
            $original_comment_id = $comment['comment_ID'];
            unset($comment['comment_ID']);
            
            $comment['comment_post_ID'] = $post_id;
            $wpdb->insert($wpdb->comments, $comment);
            $comment_id = $wpdb->insert_id;
            
            update_comment_meta($comment_id, '_icl_duplicate_of', $original_comment_id);
            
            // comment meta
            $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d", $original_comment_id));
            foreach($meta as $key => $val){
                $wpdb->insert($wpdb->commentmeta, array(
                    'comment_id'    => $comment_id,
                    'meta_key'      => $key,  
                    'meta_value'    => $val 
                ));
            }
            
            $original_comment_tr = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", 
                $original_comment_id, 'comment'));
            
            $comment_translation = array(
                'element_type'          => 'comment',
                'element_id'            => $comment_id,
                'trid'                  => $original_comment_tr->trid,
                'language_code'         => $language,
                /*'source_language_code'  => $original_comment_tr->language_code */
            );
            
            
            $comments_map[$original_comment_id] = array('trid' => $original_comment_tr->trid, 'comment' => $comment_id);
            
            $wpdb->insert($wpdb->prefix . 'icl_translations', $comment_translation); 
            
        }
        
        // sync parents
        foreach($original_comments as $comment){
            if($comment['comment_parent']){
                
                $tr_comment_id = $comments_map[$comment['comment_ID']]['comment']; 
                $tr_parent = icl_object_id($comment['comment_parent'], 'comment', false, $language);
                if($tr_parent){
                    $wpdb->update($wpdb->comments, array('comment_parent' => $tr_parent), array('comment_ID' => $tr_comment_id));
                }
                                       
            }
        }
         
                
    }
    
    function duplication_delete_comment($comment_id){
        global $wpdb;
        static $_avoid_8_loop;
        
        if(isset($_avoid_8_loop)) return;
        $_avoid_8_loop = true;
        
        $original_comment = get_comment_meta($comment_id, '_icl_duplicate_of', true);        
        if($original_comment){
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment));
            $duplicates = array($original_comment) + array_diff($duplicates, array($comment_id));
            foreach($duplicates as $dup){
                wp_delete_comment($dup);
            }
        }else{
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id));
            if($duplicates){
                foreach($duplicates as $dup){
                    wp_delete_comment($dup);
                }
            }
        }
        
        unset($_avoid_8_loop);
    }
    
    function duplication_edit_comment($comment_id){
        global $wpdb;
        
        $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id), ARRAY_A);
        unset($comment['comment_ID'], $comment['comment_post_ID']);
        
        $comment_meta = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $comment_id));
        
        $original_comment = get_comment_meta($comment_id, '_icl_duplicate_of', true);        
        if($original_comment){
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment));
            $duplicates = array($original_comment) + array_diff($duplicates, array($comment_id));
        }else{
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id));
        }                
        
        if(!empty($duplicates)){
            foreach($duplicates as $dup){
                
                $wpdb->update($wpdb->comments, $comment, array('comment_ID' => $dup));

                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->commentmeta} WHERE comment_id=%d AND meta_key <> '_icl_duplicate_of'", $dup));
                
                if($comment_meta){
                    foreach($comment_meta as $key => $value){
                        update_comment_meta($dup, $meta_key, $meta_value);
                    }
                }
                
            }
        }
        
            
    }
    
    function duplication_status_comment($comment_id, $comment_status){
        global $wpdb;
        
        static $_avoid_8_loop;
        
        if(isset($_avoid_8_loop)) return;
        $_avoid_8_loop = true;
        
                
        $original_comment = get_comment_meta($comment_id, '_icl_duplicate_of', true);        
        if($original_comment){
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $original_comment));
            $duplicates = array($original_comment) + array_diff($duplicates, array($comment_id));
        }else{
            $duplicates = $wpdb->get_col($wpdb->prepare("SELECT comment_id FROM {$wpdb->commentmeta} WHERE meta_key='_icl_duplicate_of' AND meta_value=%d", $comment_id));
        }                
        
        if(!empty($duplicates)){
            foreach($duplicates as $dup){
                wp_set_comment_status($t->element_id, $status);
            }
        }
        
        unset($_avoid_8_loop);
        
        
    }
    
    function duplication_insert_comment($comment_id){
        global $wpdb, $sitepress;
        
        $comment = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->comments} WHERE comment_ID=%d", $comment_id), ARRAY_A);
        
        // loop duplicate posts, add new comment
        $post_id = $comment['comment_post_ID'];
        
        // if this is a duplicate post
        $duplicate_of = get_post_meta($post_id, '_icl_lang_duplicate_of', true);
        if($duplicate_of){
            $post_duplicates = $this->get_duplicates($duplicate_of);    
            $_lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_type='comment' AND element_id=%d", $comment_id));
            unset($post_duplicates[$_lang]);
            $_post = get_post($duplicate_of);
            $_orig_lang = $sitepress->get_language_for_element($duplicate_of, 'post_' . $_post->post_type);
            $post_duplicates[$_orig_lang] = $duplicate_of;
        }else{
            $post_duplicates = $this->get_duplicates($post_id);    
        }
        
        unset($comment['comment_ID'], $comment['comment_post_ID']);
        
        foreach($post_duplicates as $lang => $dup_id){
            $comment['comment_post_ID'] = $dup_id;
            
            if($comment['comment_parent']){
                $comment['comment_parent'] = icl_object_id($comment['comment_parent'], 'comment', false, $lang);
            }
            
            
            $wpdb->insert($wpdb->comments, $comment);
            
            $dup_comment_id = $wpdb->insert_id;
            
            update_comment_meta($dup_comment_id, '_icl_duplicate_of', $comment_id);
            
            // comment meta
            $meta = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id=%d", $comment_id));
            foreach($meta as $key => $val){
                $wpdb->insert($wpdb->commentmeta, array(
                    'comment_id'    => $dup_comment_id,
                    'meta_key'      => $key,  
                    'meta_value'    => $val 
                ));
            }
            
            $original_comment_tr = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", 
                $comment_id, 'comment'));
            
            $comment_translation = array(
                'element_type'          => 'comment',
                'element_id'            => $dup_comment_id,
                'trid'                  => $original_comment_tr->trid,
                'language_code'         => $lang,
                /*'source_language_code'  => $original_comment_tr->language_code */
            );
            
            $wpdb->insert($wpdb->prefix . 'icl_translations', $comment_translation);             
                        
        }
        
        
    }
    
    // set slug according to user preference
    static function set_page_url($post_id){
        
        global $sitepress, $sitepress_settings, $wpdb;
        
        if($sitepress_settings['translated_document_page_url'] == 'copy-encoded'){
        
            $post = $wpdb->get_row($wpdb->prepare("SELECT post_type FROM {$wpdb->posts} WHERE ID=%d", $post_id));                
            $translation_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $post_id, 'post_' . $post->post_type));
            
            $encode_url = $wpdb->get_var($wpdb->prepare("SELECT encode_url FROM {$wpdb->prefix}icl_languages WHERE code=%s", $translation_row->language_code));
            if($encode_url){
            
                $trid = $sitepress->get_element_trid($post_id, 'post_' . $post->post_type);
                $original_post_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $trid));
                $post_name_original = $wpdb->get_var($wpdb->prepare("SELECT post_name FROM {$wpdb->posts} WHERE ID = %d", $original_post_id));
            
                $post_name_to_be = $post_name_original;
                $taken = true;
                $incr = 1;        
                do{
                    $taken = $wpdb->get_var($wpdb->prepare("
                        SELECT ID FROM {$wpdb->posts} p  
                        JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
                        WHERE ID <> %d AND t.element_type = %s AND t.language_code = %s AND p.post_name = %s
                        ", $post_id, 'post_' . $post->post_type, $translation_row->language_code, $post_name_to_be ));
                    if($taken){
                        $incr++;
                        $post_name_to_be = $post_name_original . '-' . $incr;
                    }else{
                        $taken = false;
                    }
                }while($taken == true);
                $wpdb->update($wpdb->posts, array('post_name' => $post_name_to_be), array('ID' => $post_id));
                
            }
            
        }
        
    }
    
    
}
  
?>
