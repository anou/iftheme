<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'constants.php');
defined('WYSIJA') or die('Restricted access');
global $wysija_msg;
global $wysija_wpmsg;
if(!$wysija_msg) $wysija_msg=array();
$wysija_wpmsg=array();
class WYSIJA_object{

    function WYSIJA_object(){

    }

    function get_version($pluginName=false) {
        static $versions=array();
        if(isset($versions[$pluginName])) return $versions[$pluginName];
        if ( ! function_exists( 'get_plugins' ) )   {
            if(file_exists(ABSPATH . 'wp-admin'.DS.'includes'.DS.'plugin.php')){
                require_once( ABSPATH . 'wp-admin'.DS.'includes'.DS.'plugin.php' );
            }
        }
        if (function_exists( 'get_plugins' ) )  {
            if(!$pluginName)    $pluginName='wysija-newsletters/index.php';
            $pluginFile=WYSIJA_PLG_DIR.str_replace('/',DS,$pluginName);
            $plugin_data = get_plugin_data( $pluginFile );
            $versions[$pluginName] = $plugin_data['Version'];
        }else{
            $versions[$pluginName]='undefined';
        }
        return $versions[$pluginName];
    }

    function wp_get_userdata($field=false){
        /*WordPress globals be careful there*/
        global $current_user;
        if($field){
            if(isset($current_user->$field))
                return $current_user->$field;
            elseif(isset($current_user->data->$field))
               return $current_user->data->$field;
            else return $current_user;
        }
        return $current_user;
    }

    function wp_notice($msg){
        global $wysija_wpmsg;

        /* add the hook only once */
        if(!$wysija_wpmsg) add_action('admin_notices', array($this,'wp_msgs'));

        /* record msgs */
        $wysija_wpmsg['updated'][]=$msg;
    }

    function wp_error($msg){
        global $wysija_wpmsg;

        /* add the hook only once */
        if(!$wysija_wpmsg) add_action('admin_notices', array($this,'wp_msgs'));

        /* record msgs */
        $wysija_wpmsg['error'][]=$msg;
    }

    function wp_msgs() {
        global $wysija_wpmsg;
        foreach($wysija_wpmsg as $keymsg => $wp2){
            $msgs= "<div class='".$keymsg." fade'>";
            foreach($wp2 as $mymsg)
                $msgs.= "<p><strong>Wysija</strong> : ".$mymsg."</p>";
            $msgs.= "</div>";
        }

        echo $msgs;
    }

    function error($msg,$public=false,$global=false){
        $status="error";
        if($global) $status="g-".$status;
        $this->setInfo($status,$msg,$public);
    }

    function notice($msg,$public=true,$global=false){
        $status="updated";
        if($global) $status="g-".$status;
        $this->setInfo($status,$msg,$public);
    }

    function setInfo($status,$msg,$public=false){
        global $wysija_msg;
        if(!$public) {

            if(!isset($wysija_msg['private'][$status])){
                $wysija_msg['private']=array();
                $wysija_msg['private'][$status]=array();
            }
            array_push($wysija_msg['private'][$status], $msg);
        }else{
            if(!isset($wysija_msg[$status]))  $wysija_msg[$status]=array();
            array_push($wysija_msg[$status], $msg);
        }

    }

    function getMsgs(){
        global $wysija_msg;

        if(isset($wysija_msg["private"]["error"])){
            $wysija_msg["error"][]=str_replace(array("[link]","[/link]"),array('<a class="showerrors" href="javascript:;">',"</a>"),__("An error occured. [link]Show more details.[/link]",WYSIJA));
        }

        if(isset($wysija_msg["private"]["updated"])){
            $wysija_msg["updated"][]=str_replace(array("[link]","[/link]"),array('<a class="shownotices" href="javascript:;">',"</a>"),__("[link]Show more details.[/link]",WYSIJA));
        }
        if(isset($wysija_msg["private"])){
            $prv=$wysija_msg["private"];
            unset($wysija_msg["private"]);
            if(isset($prv['error']))    $wysija_msg["xdetailed-errors"]=$prv['error'];
            if(isset($prv['updated']))    $wysija_msg["xdetailed-updated"]=$prv['updated'];
        }
        return $wysija_msg;
    }
}


class WYSIJA_help extends WYSIJA_object{
    var $controller=null;
    function WYSIJA_help(){

        if(!defined('DOING_AJAX')){
            add_action('init', array($this, 'register_scripts'), 1);
        }
        add_action('widgets_init', array($this, 'widgets_init'), 1);
    }

    function widgets_init() {
        //load the widget file
        require_once(WYSIJA_WIDGETS.'wysija_nl.php');
        register_widget('WYSIJA_NL_Widget');
    }

    function register_scripts(){
        if(defined('WPLANG') && WPLANG!=''){
            $locale=explode('_',WPLANG);
            $wplang=$locale[0];
        }else{
            $wplang='en';
        }

        if(file_exists(WYSIJA_DIR.'js'.DS.'validate'.DS.'languages'.DS.'jquery.validationEngine-'.$wplang.'.js')){
            wp_register_script('wysija-validator-lang',WYSIJA_URL.'js/validate/languages/jquery.validationEngine-'.$wplang.'.js', array( 'jquery' ),WYSIJA::get_version(),true );
        }else{
            wp_register_script('wysija-validator-lang',WYSIJA_URL.'js/validate/languages/jquery.validationEngine-en.js', array( 'jquery' ),WYSIJA::get_version(),true );
        }
        wp_register_script('wysija-validator',WYSIJA_URL.'js/validate/jquery.validationEngine.js', array( 'jquery' ),WYSIJA::get_version(),true );
        wp_register_script('wysija-front-subscribers', WYSIJA_URL.'js/front-subscribers.js', array( 'jquery' ),WYSIJA::get_version(),true);


        wp_register_script('wysija-form', WYSIJA_URL.'js/forms.js', array( 'jquery' ),WYSIJA::get_version());
        wp_register_style('validate-engine-css',WYSIJA_URL.'css/validationEngine.jquery.css',array(),WYSIJA::get_version());
        wp_register_script('wysija-admin-ajax', WYSIJA_URL.'js/admin-ajax.js',array(),WYSIJA::get_version());
        wp_register_script('wysija-admin-ajax-proto', WYSIJA_URL.'js/admin-ajax-proto.js',array(),WYSIJA::get_version());

        if(defined('WYSIJA_SIDE') && WYSIJA_SIDE=='front')  wp_enqueue_style('validate-engine-css');

    }


    /**
     * when doing an ajax request in admin this is the first place where we come
     */
    function ajax() {

        $resultArray=array();
        if(!$_REQUEST || !isset($_REQUEST['controller']) || !isset($_REQUEST['task'])){
            $resultArray=array('result'=>false);
        }else{
            $wysijapp='wysija-newsletters';
            if(isset($_REQUEST['wysijaplugin'])) $wysijapp=$_REQUEST['wysijaplugin'];

            $this->controller=&WYSIJA::get($_REQUEST['controller'],'controller', false, $wysijapp);

            if(method_exists($this->controller, $_REQUEST['task'])){
                $resultArray["result"]=$this->controller->$_REQUEST['task']();
            }else{
                $this->error("Method '".$_REQUEST['task']."' doesn't exist for controller:'".$_REQUEST['controller']."'.");
            }
        }

        $resultArray['msgs']=$this->getMsgs();

        //this header will allow ajax request from the home domain, this can be a lifesaver when domain mapping is on
        if(function_exists('home_url'))     header('Access-Control-Allow-Origin: '.home_url());

        header('Content-type: application/json');
        $response=json_encode($resultArray);

        //in some case scenario our client will have jquery forcing the jsonp so we need to adapt ourselves
        if(isset($_REQUEST['callback'] ))   $response=$_REQUEST['callback'].'('.$response.')';
        echo $response;
        die();
    }

}


class WYSIJA extends WYSIJA_object{

    function WYSIJA(){

    }
    public static function get_permalink($pageid,$params=array(),$simple=false){
        $hWPtools=&WYSIJA::get('wp_tools','helper');
        return $hWPtools->get_permalink($pageid,$params,$simple);
    }

    public static function load_lang($extendedplugin=false){
        static $extensionloaded = false;

        if(!$extendedplugin) return $extensionloaded;

        if(!$extensionloaded){
            add_action('init', array('WYSIJA','load_lang_init'));
        }
        /*load the language file*/
        if ( !$extensionloaded || !isset($extensionloaded[$extendedplugin])) {
            $transstring = null;
            switch($extendedplugin){
                case 'wysija-newsletters':
                    $transstring=WYSIJA;
                    break;
                case 'wysijashop':
                    $transstring=WYSIJASHOP;
                    break;
                case 'wysijacrons':
                    $transstring=WYSIJACRONS;
                    break;
                case 'wysija-newsletters-premium':
                    $transstring=WYSIJANLP;
                    break;
                case 'get_all':
                    return $extensionloaded;
            }
            /*
             * Necessary somehow
             */

            if($transstring !== null) {
                if(!isset($extensionloaded[$extendedplugin])){
                    //we need to call the config this way otherwise it will loop
                    $config=&WYSIJA::get('config','model',false,'wysija-newsletters',false);
                    $debugmode=0;
                    if(is_object($config) && method_exists($config, 'getValue'))  $debugmode=(int)$config->getValue('debug_new');
                    if($debugmode==0 || ($debugmode>0 && !WYSIJA::is_wysija_admin($debugmode))){
                         load_plugin_textdomain( $transstring, false, $extendedplugin . DS.'languages' );
                    }
                }


                $extensionloaded[$extendedplugin] = $transstring;
            }

            //TODO I don't remember why do we load_lang_init twice I think it has to do with qTranslate compatibility ....
            WYSIJA::load_lang_init();
        }
    }

    public static function is_wysija_admin($debugmode=false){
        //to allow wysija team members to work in english mode if debug is activated
        global $current_user;

        if((int)$debugmode>0 && empty($current_user)) return true;

        if(isset($current_user->data->user_email) &&
                (strpos($current_user->data->user_email, '@wysija.com') !== false
                || strpos($current_user->data->user_email, '@bencaubere.com') !== false)) {
            return true;
        }
        return false;
    }

    /**
     * this function exists just to fix the issue with qtranslate :/ (it only fix it partially)
     * @param type $extendedplugin
     */
    public static function load_lang_init($extendedplugin=false){
        $config=&WYSIJA::get('config','model');
        $debugmode=(int)$config->getValue('debug_new');
        if($debugmode==0 || ($debugmode>0 && !WYSIJA::is_wysija_admin($debugmode))){
            $extensionloaded=WYSIJA::load_lang('get_all');
            foreach($extensionloaded as $extendedplugin => $transstring){
                load_plugin_textdomain( $transstring, false, $extendedplugin . DS.'languages' );
            }
        }
    }

    /**
     * function to generate objects of different types, managing file requiring in order to be the most efficient
     * @staticvar array $arrayOfObjects
     * @param type $name
     * @param type $type
     * @return type
     */
    public static function get($name,$type,$forceside=false,$extendedplugin='wysija-newsletters',$loadlang=true){
        static $arrayOfObjects;

        if($loadlang)  WYSIJA::load_lang($extendedplugin);

        /*store all the objects made so that we can reuse them accross the application*/
        if(isset($arrayOfObjects[$extendedplugin][$type.$name])) {
            return $arrayOfObjects[$extendedplugin][$type.$name];
        }

        if($forceside) {
            $side=$forceside;
        } else {
            $side=WYSIJA_SIDE;
        }

        switch($extendedplugin){
            case 'wysija-newsletters-premium':
                $extendeconstant='WYSIJANLP';
                if(!defined($extendeconstant)) define($extendeconstant,$extendeconstant);
                $extendedpluginname='wysijanlp';
                break;
            case 'wysija-newsletters':
                $extendeconstant='WYSIJA';
                if(!defined($extendeconstant)) define($extendeconstant,$extendeconstant);
                $extendedpluginname='wysija';
                break;
            default :
                $extendeconstant=strtoupper($extendedplugin);
                if(!defined($extendeconstant)) define($extendeconstant,$extendeconstant);
                $extendedpluginname=$extendedplugin;
        }

        //security to protect against ./../ includes
        $name = preg_replace('#[^a-z0-9_]#i','',$name);
        switch($type){
            case 'controller':
                $ctrdir=WYSIJA_PLG_DIR.$extendedplugin.DS.'controllers'.DS;
                /*require the parent class necessary*/
                require_once(WYSIJA_CORE.'controller.php');/*require the common controller file*/
                if(defined('DOING_AJAX')) {
                    $classpath=$ctrdir.'ajax'.DS.$name.'.php';
                }else {
                    $classpath=$ctrdir.$side.DS.$name.'.php';
                    require_once(WYSIJA_CTRL.$side.'.php');/*require the side specific controller file*/
                }
                $classname = strtoupper($extendedpluginname).'_control_'.$side.'_'.$name;
                break;
            case 'view':
                $viewdir=WYSIJA_PLG_DIR.$extendedplugin.DS.'views'.DS;
                $classpath=$viewdir.$side.DS.$name.'.php';
                $classname = strtoupper($extendedpluginname).'_view_'.$side.'_'.$name;
                require_once(WYSIJA_CORE.'view.php');/*require the common view file*/
                require_once(WYSIJA_VIEWS.$side.'.php');/*require the side specific view file*/
                break;
            case 'helper':
                $helpdir=WYSIJA_PLG_DIR.$extendedplugin.DS.'helpers'.DS;

                $classpath=$helpdir.$name.'.php';
                $classname = strtoupper($extendedpluginname).'_help_'.$name;

                break;
            case 'model':
                $modeldir=WYSIJA_PLG_DIR.$extendedplugin.DS.'models'.DS;
                $classpath=$modeldir.$name.'.php';
                $classname = strtoupper($extendedpluginname).'_model_'.$name;
                /*require the parent class necessary*/
                require_once(WYSIJA_CORE.'model.php');
                break;
            case 'widget':
                $modeldir=WYSIJA_PLG_DIR.$extendedplugin.DS.'widgets'.DS;
                $classpath=$modeldir.$name.'.php';
                if($name=='wysija_nl') $classname='WYSIJA_NL_Widget';
                else $classname = strtoupper($extendedpluginname).'_widget_'.$name;
                /*require the parent class necessary*/
                //require_once(WYSIJA_WIDGETS.'model.php');
                break;
            default:
                WYSIJA::setInfo('error','WYSIJA::get does not accept this type of file "'.$type.'" .');
                return false;
        }

        if(!file_exists($classpath)) {
            WYSIJA::setInfo('error','file has not been recognised '.$classpath);
            return;
        }

        require_once($classpath);
        return $arrayOfObjects[$extendedplugin][$type.$name]=new $classname($extendedpluginname);

    }

    public static function log($key='default',$data='empty',$category='default'){
        $config=&WYSIJA::get('config','model');
        if((int)$config->getValue('debug_new')>1){

            $optionlog=get_option('wysija_log');
            if ( false === $optionlog ){
                add_option( 'wysija_log', array() ,'','no');
                $optionlog=array();
            }

            $optionlog[$category][microtime()][$key]=$data;
            update_option('wysija_log', $optionlog);
        }
        return false;
    }

    /**
     * the filter to add option to the cron frequency instead of being stuck with hourly, daily and twicedaily...
     * @param type $param
     * @return type
     */
    public static function filter_cron_schedules( $param ) {
        $frequencies=array(
            'one_min' => array(
                'interval' => 60,
                'display' => __( 'Once every minutes',WYSIJA)
                ),
            'two_min' => array(
                'interval' => 120,
                'display' => __( 'Once every two minutes',WYSIJA)
                ),
            'five_min' => array(
                'interval' => 300,
                'display' => __( 'Once every five minutes',WYSIJA)
                ),
            'ten_min' => array(
                'interval' => 600,
                'display' => __( 'Once every ten minutes',WYSIJA)
                ),
            'fifteen_min' => array(
                'interval' => 900,
                'display' => __( 'Once every fifteen minutes',WYSIJA)
                ),
            'thirty_min' => array(
                'interval' => 1800,
                'display' => __( 'Once every thirty minutes',WYSIJA)
                ),
            'two_hours' => array(
                'interval' => 7200,
                'display' => __( 'Once every two hours',WYSIJA)
                ),
            'eachweek' => array(
                'interval' => 2419200,
                'display' => __( 'Once a week',WYSIJA)
                ),
            'each28days' => array(
                'interval' => 604800,
                'display' => __( 'Once every 28 days',WYSIJA)
                ),
            );

        return array_merge($param, $frequencies);
    }

    /**
     * cron where the frequency is decided by the administrator
     */
    public static function croned_queue() {
        /* create the automatic post notifications email if there is any*/
        $autoNL=&WYSIJA::get('autonews','helper');
        $autoNL->checkPostNotif();

        /* queue the scheduled newsletter also if there are any*/
        $autoNL->checkScheduled();
        $config=&WYSIJA::get('config','model');
        if((int)$config->getValue('total_subscribers')<2000 ){
            $helperQ=&WYSIJA::get('queue','helper');
            $helperQ->report=false;
            WYSIJA::log('croned_queue process',true,'cron');
            $helperQ->process();
        }
    }


    /**
     * remove temporary files
     */
    public static function croned_daily() {
        @ini_set('max_execution_time',0);
        /*user refresh count total*/
        $helperU=&WYSIJA::get('user','helper');
        $helperU->refreshUsers();

        /*clear temporary folders*/
        $helperF=&WYSIJA::get('file','helper');
        $helperF->clear();

        /*clear queue from unsubscribed*/
        $helperQ=&WYSIJA::get('queue','helper');
        $helperQ->clear();

        /* send daily report about emails sent */
        $modelC=&WYSIJA::get('config','model');
        if($modelC->getValue('emails_notified_when_dailysummary')){
            $helperS=&WYSIJA::get('stats','helper');
            $helperS->sendDailyReport();
        }
    }

    public static function croned_monthly() {
        @ini_set('max_execution_time',0);

        /* send daily report about emails sent */
        $modelC=&WYSIJA::get('config','model');
        if($modelC->getValue('sharedata')){
            $helperS=&WYSIJA::get('stats','helper');
            $helperS->share();
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('wysija_cron_queue');
        wp_clear_scheduled_hook('wysija_cron_bounce');
        wp_clear_scheduled_hook('wysija_cron_daily');
        wp_clear_scheduled_hook('wysija_cron_weekly');
        wp_clear_scheduled_hook('wysija_cron_monthly');
    }



    public static function redirect($redirectTo){
         /* save the messages */
        global $wysija_msg,$wysija_queries,$wysija_queries_errors;
        WYSIJA::update_option('wysija_msg',$wysija_msg);
        WYSIJA::update_option('wysija_queries',$wysija_queries);
        WYSIJA::update_option('wysija_queries_errors',$wysija_queries_errors);
        wp_redirect($redirectTo);
        exit;
    }


    public static function create_post_type() {

        //by default there is url rewriteing on wysijap custom post, though in one client case I had to deactivate it.
        //as this is rare we just need to set this setting to activate it
        //by default let's deactivate the url rewriting of the wysijap confirmation page because it is breaking in some case.
        $rewritewysijap=false;

        register_post_type( 'wysijap',
            array(
                    'labels' => array(
                            'name' => __('Wysija page'),
                            'singular_name' => __('Wysija page')
                    ),
            'public' => true,
            'has_archive' => false,
            'show_ui' =>false,
            'show_in_menu' =>false,
            'rewrite' => $rewritewysijap,
            'show_in_nav_menus'=>false,
            'can_export'=>false,
            'publicly_queryable'=>true,
            'exclude_from_search'=>true,
            )
        );

        if(!get_option('wysija_post_type_updated')) {
            $modelPosts=new WYSIJA_model();
            $modelPosts->tableWP=true;
            $modelPosts->table_prefix='';
            $modelPosts->table_name='posts';
            $modelPosts->noCheck=true;
            $modelPosts->pk='ID';
            if($modelPosts->exists(array('post_type'=>'wysijapage'))){
                $modelPosts->update(array('post_type'=>'wysijap'),array('post_type'=>'wysijapage'));
                flush_rewrite_rules( false );
            }
            WYSIJA::update_option('wysija_post_type_updated',time());
        }

        if(!get_option('wysija_post_type_created')) {
            flush_rewrite_rules( false );
            WYSIJA::update_option('wysija_post_type_created',time());
        }

    }


    public static function update_option($option_name,$newvalue,$defaultload='no'){
        if ( get_option( $option_name ) != $newvalue ) {
            update_option( $option_name, $newvalue );
        } else {
            add_option( $option_name, $newvalue, '', $defaultload );
        }
    }

    public static function hook_add_WP_subscriber($user_id) {
        $data=get_userdata($user_id);


        //check first if a subscribers exists if it doesn't then let's insert it
        $modelC=&WYSIJA::get('config','model');
        $modelUser=&WYSIJA::get('user','model');
        $subscriber_exists=$modelUser->getOne(array('user_id'),array('email'=>$data->user_email));
        $modelUser->reset();
        if($subscriber_exists){
            $uid=$subscriber_exists['user_id'];

        }else{
            $modelUser->noCheck=true;

            $firstname=$data->first_name;
            $lastname=$data->last_name;
            if(!$data->first_name && !$data->last_name) $firstname=$data->display_name;

            $uid=$modelUser->insert(array('email'=>$data->user_email,'wpuser_id'=>$data->ID,'firstname'=>$firstname,'lastname'=>$lastname,'status'=>$modelC->getValue('confirm_dbleoptin')));

        }

        $modelUL=&WYSIJA::get('user_list','model');
        $modelUL->insert(array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id'),'sub_date'=>time()),true);

        $helperUser=&WYSIJA::get('user','helper');
        $helperUser->sendAutoNl($uid,$data,'new-user');
        return true;
    }

    public static function hook_edit_WP_subscriber($user_id) {
        $data=get_userdata($user_id);

        //check first if a subscribers exists if it doesn't then let's insert it
        $modelUser=&WYSIJA::get('user','model');
        $modelC=&WYSIJA::get('config','model');
        $modelUL=&WYSIJA::get('user_list','model');

        $subscriber_exists=$modelUser->getOne(array('user_id'),array('email'=>$data->user_email));

        $modelUser->reset();

        $firstname=$data->first_name;
        $lastname=$data->last_name;
        if(!$data->first_name && !$data->last_name) $firstname=$data->display_name;

        if($subscriber_exists){
            $uid=$subscriber_exists['user_id'];

            $modelUser->update(array('email'=>$data->user_email,'firstname'=>$firstname,'lastname'=>$lastname),array('wpuser_id'=>$data->ID));

            $result=$modelUL->getOne(false,array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id')));
            $modelUL->reset();
            if(!$result)
                $modelUL->insert(array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id'),'sub_date'=>time()));
        }else{
            /*chck that we didnt update the email*/
            $subscriber_exists=$modelUser->getOne(false,array('wpuser_id'=>$data->ID));

            if($subscriber_exists){
                $uid=$subscriber_exists['user_id'];

                $modelUser->update(array('email'=>$data->user_email,'firstname'=>$firstname,'lastname'=>$lastname),array('wpuser_id'=>$data->ID));

                $result=$modelUL->getOne(false,array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id')));
                $modelUL->reset();
                if(!$result)
                    $modelUL->insert(array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id'),'sub_date'=>time()));
            }else{
                $modelUser->noCheck=true;
                $uid=$modelUser->insert(array('email'=>$data->user_email,'wpuser_id'=>$data->ID,'firstname'=>$firstname,'lastname'=>$lastname,'status'=>$modelC->getValue('confirm_dbleoptin')));
                $modelUL->insert(array('user_id'=>$uid,'list_id'=>$modelC->getValue('importwp_list_id'),'sub_date'=>time()));
            }
        }
        return true;
    }

    public static function hook_del_WP_subscriber($user_id) {
        $modelConf=&WYSIJA::get('config','model');
        $modelUser=&WYSIJA::get('user','model');
        $data=$modelUser->getOne(array('email','user_id'),array('wpuser_id'=>$user_id));
        $modelUser->delete(array('email'=>$data['email']));
        $modelUser=&WYSIJA::get('user_list','model');
        $modelUser->delete(array('user_id'=>$data['user_id'],'list_id'=>$modelConf->getValue('importwp_list_id')));
    }

    public static function hook_postNotification_transition($new_status, $old_status, $post) {
        WYSIJA::log('pn_transition_post',array('postID'=>$post->ID,'postID'=>$post->post_title,'old_status'=>$old_status,'new_status'=>$new_status),'post_notif');
        if( $new_status=='publish' && $old_status!=$new_status){
            $modelEmail =& WYSIJA::get('email', 'model');
            $emails = $modelEmail->get(false, array('type' => 2, 'status' => array(1, 3, 99)));

            if(!empty($emails)){
                foreach($emails as $key => $email) {
                    if(is_array($email) && $email['params']['autonl']['event'] === 'new-articles' && $email['params']['autonl']['when-article'] === 'immediate') {
                        $modelEmail->reset();
                        $modelEmail->giveBirth($email, $post->ID);
                    }
                }
            }
        }

        return true;
    }

    public static function uninstall(){
        $helperUS=&WYSIJA::get('uninstall','helper');
        $helperUS->uninstall();
    }

    public static function activate(){
        $encoded_option=get_option('wysija');
        $installApp=false;
        if($encoded_option){
            $values=unserialize(base64_decode($encoded_option));
            if(isset($values['installed'])) $installApp=true;
        }

        /*test again for plugins on reactivation*/
        if($installApp){
            $importHelp=&WYSIJA::get('import','helper');
            $importHelp->testPlugins();

            /*resynch wordpress list*/
            $helperU=&WYSIJA::get('user','helper');
            $helperU->synchList($values['importwp_list_id']);
        }
    }

    public static function is_plugin_active($pluginName){
        $arrayactiveplugins=get_option('active_plugins');
        if(in_array($pluginName, $arrayactiveplugins)/*is_plugin_active($conflictPlug['file'])*/) {
            //plugin is activated
            return true;
        }
        return false;
    }

    /*make sure that the current user has the good access rights corresponding to its role*/
    public static function update_user_caps(){
        global $current_user;

        if(empty($current_user)) get_currentuserinfo();
        if(empty($current_user)) return false;
        $current_user->get_role_caps();

        return true;
    }

    public static function current_user_can($capability){
        if(!$capability) return false;
        WYSIJA::update_user_caps();
        if(!current_user_can($capability)) return false;
        return true;
    }

    public static function get_cron_schedule($schedule='queue'){
        static $cron_schedules;

        if(!empty($cron_schedules)){
            if($schedule=='all') return $cron_schedules;
            if(isset($cron_schedules[$schedule])) {
                return $cron_schedules[$schedule];
            }else{
                WYSIJA::set_cron_schedule($schedule);
                return false;
            }
        }else{
            $cron_schedules=get_option('wysija_schedules',array());
            if(!empty($cron_schedules)){
                if(isset($cron_schedules[$schedule]))   return $cron_schedules[$schedule];
                else    return false;
            }else{
                WYSIJA::set_cron_schedule();
                return false;
            }
        }
        return false;
    }
    public static function get_cron_frequencies(){
        $mConfig=&WYSIJA::get('config','model');
        $fHelper=&WYSIJA::get('forms','helper');
        $queue_frequency=$fHelper->eachValuesSec[$mConfig->getValue('sending_emails_each')];
        $bounce_frequency=99999999999999;
        if(isset($fHelper->eachValuesSec[$mConfig->getValue('bouncing_emails_each')]))  $bounce_frequency=$fHelper->eachValuesSec[$mConfig->getValue('bouncing_emails_each')];
        return array('queue'=>$queue_frequency,'bounce'=>$bounce_frequency,'daily'=>86400,'weekly'=>604800,'monthly'=>2419200);
    }
    public static function set_cron_schedule($schedule=false,$lastsaved=0,$set_running=false){
        $cron_schedules=array();

        $start_time=$lastsaved;
        if(!$start_time)    $start_time=time();
        $processes=WYSIJA::get_cron_frequencies();
        if(!$schedule){
            foreach($processes as $process => $frequency){
                $next_schedule=$start_time+$frequency;
                $prev_schedule=0;
                if(isset($cron_schedules[$process]['running']) && $cron_schedules[$process]['running']) $prev_schedule=$cron_schedules[$process]['running'];
                $cron_schedules[$process]=array(
                    'next_schedule'=>$next_schedule,
                    'prev_schedule'=>$prev_schedule,
                    'running'=>false);
            }
        }else{
            $cron_schedules=WYSIJA::get_cron_schedule('all');
            if($set_running){
                 $cron_schedules[$schedule]['running']=$set_running;
            }else{
                 $running=0;
                if(isset($cron_schedules[$schedule]['running'])) $running=$cron_schedules[$schedule]['running'];
                //if the process is not running or has been running for more than 15 minutes then we set the next_schedule date
                if(!$running || time()>$running+900){
                    $next_schedule=$start_time+$processes[$schedule];
                    $cron_schedules[$schedule]=array(
                            'next_schedule'=>$next_schedule,
                            'prev_schedule'=>$running,
                            'running'=>false);
                }
            }
        }
        WYSIJA::update_option('wysija_schedules',$cron_schedules,'yes');
        return true;
    }

    /*check that there is no due cron*/
    public static function cron_check() {

        $cron_schedules=WYSIJA::get_cron_schedule('all');
        if(empty($cron_schedules)) return;
        else{
            $processes=WYSIJA::get_cron_frequencies();
            $updatedsched=false;
            foreach($cron_schedules as $proc => &$params){
                    $running=0;
                    if(isset($params['running'])) $running=$params['running'];
                    //if the process has timedout we reschedule the next execution
                    if($running && time()>$running+900){
                        //WYSIJA::setInfo('error','modifying next schedule for '.$proc);
                        $next_schedule=time()+$processes[$proc];
                        $params=array(
                                'next_schedule'=>$next_schedule,
                                'prev_schedule'=>$running,
                                'running'=>false);
                        $updatedsched=true;
                    }
            }
            if($updatedsched){
                //WYSIJA::setInfo('error','updating scheds');
                WYSIJA::update_option('wysija_schedules',$cron_schedules,'yes');
            }

        }

        $timenow=time();
        $processesToRun=array();
        foreach($cron_schedules as $process =>$scheduled_times){
            if((!$scheduled_times['running'] || (int)$scheduled_times['running']+900<$timenow) && $scheduled_times['next_schedule']<$timenow){
                $processesToRun[]=$process;
            }
        }

        if(!empty($processesToRun)){
            //call the cron url

            $cron_url=site_url( 'wp-cron.php').'?'.WYSIJA_CRON.'&action=wysija_cron&process='.implode(',',$processesToRun).'&silent=1';

            //TODO we should use the http class there
            $hHTTP=&WYSIJA::get('http','helper');
            $hHTTP->request_timeout($cron_url);

        }
    }
}


if(isset($_REQUEST['action']) && $_REQUEST['action']=='wysija_cron'){
    add_action('init', 'init_wysija_cron',1);
    function init_wysija_cron(){
        $hCron=WYSIJA::get('cron','helper');
        $hCron->run();
        exit;
    }
}

/*user synch moved*/
add_action('user_register', array('WYSIJA', 'hook_add_WP_subscriber'), 1);
add_action('added_existing_user', array('WYSIJA', 'hook_add_WP_subscriber'), 1);
add_action('profile_update', array('WYSIJA', 'hook_edit_WP_subscriber'), 1);
add_action('delete_user', array('WYSIJA', 'hook_del_WP_subscriber'), 1);

/*post notif trigger*/
add_action('transition_post_status', array('WYSIJA', 'hook_postNotification_transition'), 1, 3);

/*add image size for emails*/
add_image_size( 'wysija-newsletters-max', 600, 99999 );

$modelConf=&WYSIJA::get('config','model');
if($modelConf->getValue('installed_time')){
    if($modelConf->getValue('cron_manual')){
        //if cron queue is still set then unset it
        if(wp_get_schedule('wysija_cron_queue'))    WYSIJA::deactivate();

        //set the crons schedule for each process
        WYSIJA::get_cron_schedule();

    }else{
        /* some processing for cron management */
        add_filter( 'cron_schedules', array( 'WYSIJA', 'filter_cron_schedules' ) );
        add_action( 'wysija_cron_queue', array( 'WYSIJA', 'croned_queue' ) );
        add_action( 'wysija_cron_daily', array( 'WYSIJA', 'croned_daily' ) );
        add_action( 'wysija_cron_monthly', array( 'WYSIJA', 'croned_monthly' ) );

        if(!wp_next_scheduled('wysija_cron_bounce')){
            wp_schedule_event( $modelConf->getValue('last_save') , $modelConf->getValue('bouncing_emails_each'), 'wysija_cron_bounce' );
        }
        if(!wp_next_scheduled('wysija_cron_weekly')){
            wp_schedule_event( $modelConf->getValue('last_save') , 'eachweek', 'wysija_cron_weekly' );
        }
        if(!wp_next_scheduled('wysija_cron_queue')){
            wp_schedule_event( $modelConf->getValue('last_save') , $modelConf->getValue('sending_emails_each'), 'wysija_cron_queue' );
        }
        if(!wp_next_scheduled('wysija_cron_monthly')){
            wp_schedule_event( $modelConf->getValue('last_save') , 'each28days', 'wysija_cron_monthly' );
        }
        if(!wp_next_scheduled('wysija_cron_daily')){
            wp_schedule_event( time() , 'daily', 'wysija_cron_daily' );
        }
    }
}


if($modelConf->getValue('wp_notifications')){
    $hWPnotif=&WYSIJA::get('wp_notifications','helper');
}
//check that there is no late cron schedules
if($modelConf->getValue('cron_manual') && !isset($_REQUEST['process'])){
    WYSIJA::cron_check();
}

register_deactivation_hook(WYSIJA_FILE, array( 'WYSIJA', 'deactivate' ));
register_activation_hook(WYSIJA_FILE, array( 'WYSIJA', 'activate' ));
add_action( 'init', array('WYSIJA','create_post_type') );
$helper=&WYSIJA::get(WYSIJA_SIDE,'helper');