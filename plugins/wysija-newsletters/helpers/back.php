<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_help_back extends WYSIJA_help{
    function WYSIJA_help_back(){
        parent::WYSIJA_help();

        $config=&WYSIJA::get('config','model');
        define('WYSIJA_DBG',(int)$config->getValue('debug_new'));

        if(!defined('WP_DEBUG') || !WP_DEBUG){
            error_reporting(0);
            ini_set('display_errors', '0');
        }



        if(isset($_GET['page']) && substr($_GET['page'],0,7)=='wysija_'){
            define('WYSIJA_ITF',TRUE);
            $this->controller=&WYSIJA::get(str_replace('wysija_','',$_GET['page']),'controller');
        }else{//check if we are pluging in wordpress interface
            define('WYSIJA_ITF',FALSE);
        }
        if(WYSIJA_DBG>0) include_once(WYSIJA_INC.'debug.php');
        if(!function_exists('dbg')) {
            function dbg($mixed,$exit=true){}
        }


        if(defined('DOING_AJAX')){

            add_action( 'after_setup_theme', array($this, 'ajax_setup') );
        }else{
            if(WYSIJA_ITF)  {
                add_action('admin_init', array($this->controller, 'main'));
                if(!isset($_REQUEST['action']) || (isset($_REQUEST['action']) && $_REQUEST['action'] !== 'editTemplate')) {
                    add_action('admin_footer',array($this,'version'),9);
                }
                add_action('after_setup_theme',array($this,'resolveConflicts'));
            }



            add_action('after_setup_theme', array('WYSIJA', 'update_user_caps'),11);
            add_action('admin_menu', array($this, 'define_translated_strings'),98);
            add_action('admin_menu', array($this, 'add_menus'),99);
            add_action('admin_enqueue_scripts',array($this, 'add_js'),10,1);


            add_action('admin_head-post-new.php',array($this,'addCodeToPagePost'));
            add_action('admin_head-post.php',array($this,'addCodeToPagePost'));

             $wptools =& WYSIJA::get('wp_tools', 'helper');
             $wptools->set_default_rolecaps();

            if($config->getValue('premium_key') && !WYSIJA::is_plugin_active('wysija-newsletters-premium/index.php')){
                add_filter( 'pre_set_site_transient_update_plugins', array($this,'prevent_update_wysija'));
                add_filter( 'http_request_args', array($this,'disable_wysija_version_requests'), 5, 2 );
                if(file_exists(WYSIJA_PLG_DIR.'wysija-newsletters-premium'.DS.'index.php')){

                    $this->notice('<p>'.__('You need to activate the Wysija Premium plugin.',WYSIJA).' <a id="install-wjp" class="button-primary"  href="admin.php?page=wysija_campaigns&action=install_wjp">'.__('Activate now',WYSIJA).'</a></p>');
                }else{

                    $this->notice('<p>'.__('Congrats, your Premium license is active. One last step...',WYSIJA).' <a id="install-wjp" class="button-primary"  href="admin.php?page=wysija_campaigns&action=install_wjp">'.__('Install the Premium plugin.',WYSIJA).'</a></p>');
                }
                $this->controller->jsTrans['instalwjp']='Installing Wysija Newsletter Premium plugin';
            }
        }

        if($config->getValue('commentform')){
            add_action('wp_set_comment_status',  array($this,'comment_approved'), 60,2);
        }
    }
    function comment_approved($cid,$comment_status){

        $metaresult=get_comment_meta($cid, 'wysija_comment_subscribe', true);
        if($comment_status=='approve' && get_comment_meta($cid, 'wysija_comment_subscribe', true)){
            $mConfig=&WYSIJA::get('config','model');
            $comment = get_comment($cid);
            $userHelper=&WYSIJA::get('user','helper');
            $data=array('user'=>array('email'=>$comment->comment_author_email,'firstname'=>$comment->comment_author),'user_list'=>array('list_ids'=>$mConfig->getValue('commentform_lists')));
            $userHelper->addSubscriber($data);
        }
    }
    function ajax_setup(){
        if(!isset($_REQUEST['adminurl']) && !is_user_logged_in())    add_action('wp_ajax_nopriv_wysija_ajax', array($this, 'ajax'));
        else    add_action('wp_ajax_wysija_ajax', array($this, 'ajax'));
    }
    function disable_wysija_version_requests( $r, $url ) {
        if ( 0 !== strpos( $url, 'http://api.wordpress.org/plugins/update-check' ) )
            return $r; // Not a plugin update request. Bail immediately.
        $plugins = unserialize( $r['body']['plugins'] );
        unset( $plugins->plugins['wysija-newsletters/index.php'] );
        unset( $plugins->active[ array_search('wysija-newsletters/index.php', $plugins->active ) ] );
        $r['body']['plugins'] = serialize( $plugins );
        return $r;
    }

    function prevent_update_wysija($value){
        if(isset($value->response['wysija-newsletters/index.php'])) unset($value->response['wysija-newsletters/index.php']);
        return $value;
    }
    
    function resolveConflicts(){

        $possibleConflictiveThemes = $this->controller->get_conflictive_plugins(true);
        $conflictingTheme = null;
        $currentTheme = strtolower(function_exists( 'wp_get_theme' ) ? wp_get_theme() : get_current_theme());
        foreach($possibleConflictiveThemes as $keyTheme => $conflictTheme) {
            if($keyTheme === $currentTheme) {
                $conflictingTheme = $keyTheme;
            }
        }

        if($conflictingTheme !== null) {
            $helperConflicts =& WYSIJA::get('conflicts', 'helper');
            $helperConflicts->resolve(array($possibleConflictiveThemes[$conflictingTheme]));
        }

        $possibleConflictivePlugins=$this->controller->get_conflictive_plugins();
        $conflictingPlugins=array();
        foreach($possibleConflictivePlugins as $keyPlg => $conflictPlug){
            if(WYSIJA::is_plugin_active($conflictPlug['file'])) {

                $conflictingPlugins[$keyPlg]=$conflictPlug;
            }
        }
        if($conflictingPlugins){
            $helperConflicts=&WYSIJA::get('conflicts','helper');
            $helperConflicts->resolve($conflictingPlugins);
        }
    }
    
    function define_translated_strings(){
        $config=&WYSIJA::get('config','model');
        $linkcontent=__("It doesn't always work the way we want it to, doesn't it? We have a [link]dedicated support website[/link] with documentation and a ticketing system.",WYSIJA);
        $finds=array('[link]','[/link]');
        $replace=array('<a target="_blank" href="http://support.wysija.com" title="support.wysija.com">','</a>');
        $truelinkhelp='<p>'.str_replace($finds,$replace,$linkcontent).'</p>';
        $extra=__('[link]Request a feature for Wysija[/link] in User Voice.',WYSIJA);
        $finds=array('[link]','[/link]');
        $replace=array('<a target="_blank" href="http://wysija.uservoice.com/forums/150107-feature-request" title="Wysija User Voice">','</a>');
        $truelinkhelp.='<p>'.str_replace($finds,$replace,$extra).'</p>';

        $truelinkhelp.='<p>'.__('Wysija Version: ',WYSIJA).'<strong>'.WYSIJA::get_version().'</strong></p>';
        $this->menus=array(
            'campaigns'=>array('title'=>'Wysija'),
            'subscribers'=>array('title'=>__('Subscribers',WYSIJA)),
            'config'=>array('title'=>__('Settings',WYSIJA)),

        );
        $this->menuHelp=$truelinkhelp;
        if($config->getValue('queue_sends_slow')){
            $msg=$config->getValue('ignore_msgs');
            if(!isset($msg['queuesendsslow'])){
                $this->notice(
                        __('Tired of waiting more than 48h to send your emails?',WYSIJA).' '. str_replace(array('[link]','[/link]'), array('<a href="http://support.wysija.com/knowledgebase/how-fast-can-i-send-emails-optimal-sending-configurations-explained/?utm_source=wpadmin&utm_campaign=slowqueue" target="_blank">','</a>'), __('[link]Find out[/link] how you can improve this.',WYSIJA)).
                        ' <a class="linkignore queuesendsslow" href="javascript:;">'.__('Hide!',WYSIJA).'</a>');
            }
        }

        if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON && !WYSIJA::is_plugin_active('wp-cron-control/wp-cron-control.php')) {
            $msg=$config->getValue('ignore_msgs');
            if(!isset($msg['crondisabled'])){
                $this->notice(
                        __("Oops! Looks like your site's event scheduler (wp-cron) is turned off.",WYSIJA).' '.
                        str_replace(
                            array('[link]','[/link]'),
                            array('<a href="http://support.wysija.com/knowledgebase/your-cron-is-disabled/" target="_blank">','</a>'), __('[link]Read more.[/link]',WYSIJA)
                        ).' <a class="linkignore crondisabled" href="javascript:;">'.__('Hide!',WYSIJA).'</a>');
            }
        }
        if(WYSIJA_ITF){
            global $wysija_installing;
            if( !$config->getValue('sending_emails_ok')){
                $msg=$config->getValue('ignore_msgs');
                $urlsendingmethod='admin.php?page=wysija_config#tab-sendingmethod';
                if($_REQUEST['page'] === 'wysija_config') {
                    $urlsendingmethod='#tab-sendingmethod';
                }
            }
        }
    }

    function add_menus(){
        $modelC=&WYSIJA::get('config','model');
        $count=0;

        global $menu,$submenu;


        $position=50;
        $positionplus1=$position+1;
        while(isset($menu[$position]) || isset($menu[$positionplus1])){
            $position++;
            $positionplus1=$position+1;

            if(!isset($menu[$position]) && isset($menu[$positionplus1])){
                $position=$position+2;
            }
        }
        global $wysija_installing;
        foreach($this->menus as $action=> $menutemp){
            $actionFull='wysija_'.$action;
            if(!isset($menutemp['subtitle'])) $menutemp['subtitle']=$menutemp['title'];
            if($action=='campaigns')    $roleformenu='wysija_newsletters';
            elseif($action=='subscribers')    $roleformenu='wysija_subscribers';
            else $roleformenu='wysija_config';
            if($wysija_installing===true){
                if($count==0){
                    $parentmenu=$actionFull;
                    $hookname=add_menu_page($menutemp['title'], $menutemp['subtitle'], $roleformenu, $actionFull , array($this->controller, 'errorInstall'), WYSIJA_EDITOR_IMG.'mail.png', $position);
                }
            }else{
                if($count==0){
                    $parentmenu=$actionFull;
                    $hookname=add_menu_page($menutemp['title'], $menutemp['subtitle'], $roleformenu, $actionFull , array($this->controller, 'render'), WYSIJA_EDITOR_IMG.'mail.png', $position);
                }else{
                    $hookname=add_submenu_page($parentmenu,$menutemp['title'], $menutemp['subtitle'], $roleformenu, $actionFull , array($this->controller, 'render'));
                }

                if(WYSIJA_ITF){

                    if(version_compare(get_bloginfo('version'), '3.3.0')>= 0){
                        add_action('load-'.$hookname, array($this,'add_help_tab'));
                    }else{

                        add_contextual_help($hookname, $this->menuHelp);
                    }
                }
            }
            $count++;
        }
        if(isset($submenu[$parentmenu])){
            if($submenu[$parentmenu][0][2]=="wysija_subscribers") $textmenu=__('Subscribers',WYSIJA);
            else $textmenu=__('Newsletters',WYSIJA);
            $submenu[$parentmenu][0][0]=$submenu[$parentmenu][0][3]=$textmenu;
        }
    }
    function add_help_tab($params){
        $screen = get_current_screen();
        if(method_exists($screen, "add_help_tab")){
            $screen->add_help_tab(array(
            'id'	=> 'wysija_help_tab',
            'title'	=> __('Get Help!',WYSIJA),
            'content'=> $this->menuHelp));
            $tabfunc=true;
        }
    }
    function add_js($hook) {

        $jstrans=array();
        wp_register_script('wysija-charts', 'https://www.google.com/jsapi', array( 'jquery' ), true);
        wp_register_script('wysija-admin-list', WYSIJA_URL.'js/admin-listing.js', array( 'jquery' ), true, WYSIJA::get_version());
        wp_register_script('wysija-base-script-64', WYSIJA_URL.'js/base-script-64.js', array( 'jquery' ), true, WYSIJA::get_version());

        wp_enqueue_style('wysija-admin-css-widget', WYSIJA_URL.'css/admin-widget.css',array(),WYSIJA::get_version());

        $model_config =& WYSIJA::get('config', 'model');
        if ($model_config->getValue('send_analytics_now') == 1) {
            require_once WYSIJA_CLASSES . 'autoloader.php';
            $analytics = new WJ_Analytics();
            $analytics->generate_data();
            $analytics->send();

            $model_config->save(array('send_analytics_now' => 0));
        }


        if(WYSIJA_ITF){
            wp_enqueue_style('wysija-admin-css-global', WYSIJA_URL.'css/admin-global.css',array(),WYSIJA::get_version());
            wp_enqueue_script('wysija-admin-js-global', WYSIJA_URL.'js/admin-wysija-global.js',array(),WYSIJA::get_version());
            $pagename=str_replace('wysija_','',$_REQUEST['page']);
            $backloader=&WYSIJA::get('backloader','helper');
            $backloader->initLoad($this->controller);

            $jstrans=$this->controller->jsTrans;

            $jstrans['gopremium']=__('Go Premium!',WYSIJA);

            $backloader->jsParse($this->controller,$pagename,WYSIJA_URL);

            $backloader->loadScriptsStyles($pagename,WYSIJA_DIR,WYSIJA_URL,$this->controller);

            $backloader->localize($pagename,WYSIJA_DIR,WYSIJA_URL,$this->controller);
        }
            $jstrans['newsletters']=__('Newsletters',WYSIJA);
            $jstrans['urlpremium']='admin.php?page=wysija_config#tab-premium';
            if(isset($_REQUEST['page']) && $_REQUEST['page']=='wysija_config'){
                $jstrans['urlpremium']='#tab-premium';
            }
            wp_localize_script('wysija-admin', 'wysijatrans', $jstrans);
    }
    
    function addCodeToPagePost(){

        if(current_user_can('wysija_subscriwidget') &&  get_user_option('rich_editing') == 'true') {
         add_filter("mce_external_plugins", array($this,"addRichPlugin"));
         add_filter('mce_buttons', array($this,'addRichButton1'),999);
         $myStyleUrl = "../../plugins/wysija-newsletters/css/tmce/style.css";
         add_editor_style($myStyleUrl);

         wp_enqueue_style('custom_TMCE_admin_css', WYSIJA_URL.'css/tmce/panelbtns.css');
         wp_print_styles('custom_TMCE_admin_css');
       }
    }
    function addRichPlugin($plugin_array) {
       $plugin_array['wysija_register'] = WYSIJA_URL.'mce/wysija_register/editor_plugin.js';

       return $plugin_array;
    }
    function addRichButton1($buttons) {
       $newButtons=array();
       foreach($buttons as $value) $newButtons[]=$value;

       array_push($newButtons, '|', 'wysija_register');

       return $newButtons;
    }
    function version(){
        $wysijaversion= '<div class="wysija-version">';
        $wysijaversion.='<div class="social-foot">';
        $wysijaversion.= '<div id="upperfoot"><div class="support"><a target="_blank" href="http://support.wysija.com/?utm_source=wpadmin&utm_campaign=footer" >'.__('Support & documentation',WYSIJA).'</a> | <a target="_blank" href="http://wysija.uservoice.com/forums/150107-feature-request" >'.__('Request feature',WYSIJA).'</a> | ';
        $wysijaversion.=str_replace(
                array('[stars]','[link]','[/link]'),
                array('<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/wysija-newsletters" >★★★★★</a>','<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/wysija-newsletters" >','</a>'),
                __('Add your [stars] on [link]wordpress.org[/link] and keep this plugin essentially free.',WYSIJA)
                );
        $wysijaversion.= '<div class="version">'.__('Wysija Version: ',WYSIJA).'<a href="admin.php?page=wysija_campaigns&action=whats_new">'.WYSIJA::get_version().'</a></div></div>';
        
        $wysijaversion.= '</div></div>';
        echo $wysijaversion;
    }

}

