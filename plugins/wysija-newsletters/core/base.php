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

    /**
     * return a plugin version safely anywhere in any hook and stock it staticaly
     * @staticvar array $versions
     * @param string $pluginName
     * @return array|string
     */
    public static function get_version($pluginName=false) {
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

    /**
     * get the current_user data in a safe manner making sure a field exists before returning it's value
     * @global type $current_user
     * @param string $field
     * @return mixed
     */
    public static function wp_get_userdata($field=false){
        //WordPress globals be careful there
        global $current_user;
        if($field){
            if(function_exists('get_currentuserinfo')) get_currentuserinfo();
            if(isset($current_user->$field))
                return $current_user->$field;
            elseif(isset($current_user->data->$field))
               return $current_user->data->$field;
            else{
                return $current_user;
            }
        }
        return $current_user;
    }

    /**
     * set a global notice message
     * @global array $wysija_wpmsg
     * @param type $msg
     */
    function wp_notice($msg){
        global $wysija_wpmsg;

        //add the hook only once
        if(!$wysija_wpmsg) add_action('admin_notices', array($this,'wp_msgs'));

        //record msgs
        $wysija_wpmsg['updated'][]=$msg;
    }

    /**
     * set a global error message
     * @global array $wysija_wpmsg
     * @param type $msg
     */
    function wp_error($msg){
        global $wysija_wpmsg;

        //add the hook only once
        if(!$wysija_wpmsg) add_action('admin_notices', array($this,'wp_msgs'));

        //record msgs
        $wysija_wpmsg['error'][]=$msg;
    }

    /**
     * prints a global message in the WordPress' backend identified as belonging to wysija
     * we tend to avoid as much as possible printing messages globally, since this is ugly
     * and make the administrators immune to beige-yellowish messages :/
     * @global array $wysija_wpmsg
     */
    function wp_msgs() {
        global $wysija_wpmsg;
        foreach($wysija_wpmsg as $keymsg => $wp2){
            $msgs= '<div class="'.$keymsg.' fade">';
            foreach($wp2 as $mymsg)
                $msgs.= '<p><strong>Wysija</strong> : '.$mymsg.'</p>';
            $msgs.= '</div>';
        }

        echo $msgs;
    }

    /**
     * returns an error message, it will appear as a red pinkish message in our interfaces
     * @param string $msg
     * @param boolean $public if set to true it will appear as a full message, otherwise it will appear behind a "Show more details." link
     * @param boolean $global if set to true it will appear on all of the backend interfaces, not only wysija's own
     */
    function error($msg,$public=false,$global=false){
        $status='error';
        if($global) $status='g-'.$status;
        $this->setInfo($status,$msg,$public);
    }

    /**
     * returns a success message, it will appear as a beige yellowish message in our interfaces
     * @param string $msg
     * @param boolean $public if set to true it will appear as a full message, otherwise it will appear behind a "Show more details." link
     * @param boolean $global if set to true it will appear on all of the backend interfaces, not only wysija's own
     */
    function notice($msg,$public=true,$global=false){
        $status='updated';
        if($global) $status='g-'.$status;
        $this->setInfo($status,$msg,$public);
    }

    /**
     * store all of the error and success messages in a global variable
     * @global type $wysija_msg
     * @param type $status whether this is a success message or an error message
     * @param type $msg
     * @param type $public if set to true it will appear as a full message, otherwise it will appear behind a "Show more details." link
     */
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

    /**
     * read the global function containing all of the error messages and print them
     * @global type $wysija_msg
     * @return type
     */
    function getMsgs(){
        global $wysija_msg;

        if(isset($wysija_msg['private']['error'])){
            $wysija_msg['error'][]=str_replace(array('[link]','[/link]'),array('<a class="showerrors" href="javascript:;">','</a>'),__('An error occured. [link]Show more details.[/link]',WYSIJA));
        }

        if(isset($wysija_msg['private']['updated'])){
            $wysija_msg['updated'][]=str_replace(array('[link]','[/link]'),array('<a class="shownotices" href="javascript:;">','</a>'),__('[link]Show more details.[/link]',WYSIJA));
        }
        if(isset($wysija_msg['private'])){
            $prv=$wysija_msg['private'];
            unset($wysija_msg['private']);
            if(isset($prv['error']))    $wysija_msg['xdetailed-errors']=$prv['error'];
            if(isset($prv['updated']))    $wysija_msg['xdetailed-updated']=$prv['updated'];
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
     * when doing an ajax request in admin this is the first place we come to
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
                $resultArray['result']=$this->controller->$_REQUEST['task']();
            }else{
                $this->error('Method "'.$_REQUEST['task'].'" doesn\'t exist for controller : "'.$_REQUEST['controller']);
            }
        }

        $resultArray['msgs'] = $this->getMsgs();

        //this header will allow ajax request from the home domain, this can be a lifesaver when domain mapping is on
        if(function_exists('home_url')) header('Access-Control-Allow-Origin: '.home_url());

        header('Content-type: application/json');
        $jsonData = json_encode($resultArray);

        //in some case scenario our client will have jquery forcing the jsonp so we need to adapt ourselves
        if(isset($_REQUEST['callback'])) {
            $hJSONP =& WYSIJA::get('jsonp', 'helper');
            if($hJSONP->isValidCallback($_REQUEST['callback'])) {
                print $_REQUEST['callback'] . '('.$jsonData.');';
            }
        } else {
            print $jsonData;
        }
        die();
    }
}


class WYSIJA extends WYSIJA_object{

    function WYSIJA(){

    }

    /**
     * function created at the beginning to handle particular cases with WP get_permalink it got much smaller recently
     * @param int $pageid
     * @param array $params
     * @param boolean $simple
     * @return type
     */
    public static function get_permalink($pageid,$params=array(),$simple=false){
        $hWPtools=&WYSIJA::get('wp_tools','helper');
        return $hWPtools->get_permalink($pageid,$params,$simple);
    }

    /**
     * translate the plugin
     * @staticvar boolean $extensionloaded
     * @param type $extendedplugin
     * @return boolean
     */
    public static function load_lang($extendedplugin=false){
        static $extensionloaded = false;

        //we return the entire array of extensions loaded if non is specified
        if(!$extendedplugin) return $extensionloaded;

        //we only need to load this translation loader once on init
        if(!$extensionloaded){
            add_action('init', array('WYSIJA','load_lang_init'));
        }
        //each plugin has a different name
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

            //store all the required translations to be loaded in the static variable
            if($transstring !== null) {
                $extensionloaded[$extendedplugin] = $transstring;
            }
        }
    }

    /**
     * check if the user is tech support as this can be used to switch the language back to english when helping our customers
     * @global type $current_user
     * @param type $debugmode
     * @return type
     */
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
                $filename=WYSIJA_PLG_DIR.$extendedplugin.DS.'languages'.DS.$transstring.'-'.get_locale().'.mo';
                if(file_exists($filename))  load_textdomain($transstring, $filename);
            }
        }
    }

    /**
     * function to generate objects of different types, managing file requiring in order to be the most efficient
     * @staticvar array $arrayOfObjects
     * @param string $name
     * @param string $type : in which folder do we go and pick the class
     * @param boolean $force_side : this parameter is almost never set to true,
     *                              it will be useful for instance if you want to get a back controller
     *                              from the frontend, it was used maybe in the shop but it can be ignored for wysija-newsletters
     * @param type $extended_plugin : used only when calling the url from a different plugin it is used watch those files :
     *                              -core/controller.php line 21, 23 ,24
     * @param type $load_lang : the load lang is in the get  to be sure we don't forget to load the language file for each plugin at least once
     *                          the way I see it it could be moved to the index.php of each plugin. for now only wysija-newsletters is translated anyway
     * @return boolean
     */
    public static function get($name,$type,$force_side=false,$extended_plugin='wysija-newsletters',$load_lang=true){
        static $array_of_objects;

        if($load_lang)  WYSIJA::load_lang($extended_plugin);

        // store all the objects made so that we can reuse them accross the application if the object is already set we return it immediately
        if(isset($array_of_objects[$extended_plugin][$type.$name])) {
            return $array_of_objects[$extended_plugin][$type.$name];
        }

        // which folder do we pick for controllersand views ? back or front ?
        if($force_side) {
            $side=$force_side;
        } else {
            $side=WYSIJA_SIDE;
        }

        // for each plugin we will define the $extended_constant variable if it's not defined already
        // also we will defined the $extended_plugin_name which corresponds to the folder name and also will be used to build the class to be called
        switch($extended_plugin){
            case 'wysija-newsletters-premium':
                $extended_constant='WYSIJANLP';
                if(!defined($extended_constant)) define($extended_constant,$extended_constant);
                $extended_plugin_name='wysijanlp';
                break;
            case 'wysija-newsletters':
                $extended_constant='WYSIJA';
                if(!defined($extended_constant)) define($extended_constant,$extended_constant);
                $extended_plugin_name='wysija';
                break;
            default :
                $extended_constant=strtoupper($extended_plugin);
                if(!defined($extended_constant)) define($extended_constant,$extended_constant);
                $extended_plugin_name=$extended_plugin;
        }

        // security to protect against dangerous ./../ includes
        $name = preg_replace('#[^a-z0-9_]#i','',$name);

        // this switch will require_once the file needed and build a the class name depending on the parameters passed to the function
        switch($type){
            case 'controller':
                // require the parent class necessary
                require_once(WYSIJA_CORE.'controller.php');

                $ctrdir=WYSIJA_PLG_DIR.$extended_plugin.DS.'controllers'.DS;

                // if we are doing ajax we don't go to one side, ajax is for frontend or backend in the same folder
                if(defined('DOING_AJAX')) {
                    $class_path=$ctrdir.'ajax'.DS.$name.'.php';
                }else {
                    // the other controllers are called in a side folder back or front
                    $class_path=$ctrdir.$side.DS.$name.'.php';
                    // require the side specific controller file
                    require_once(WYSIJA_CTRL.$side.'.php');
                }
                $class_name = strtoupper($extended_plugin_name).'_control_'.$side.'_'.$name;
                break;
            case 'view':
                $viewdir=WYSIJA_PLG_DIR.$extended_plugin.DS.'views'.DS;
                // let's get the right path for the view front or back and the right class_name
                $class_path=$viewdir.$side.DS.$name.'.php';
                $class_name = strtoupper($extended_plugin_name).'_view_'.$side.'_'.$name;

                // require the common view file and the side view file
                require_once(WYSIJA_CORE.'view.php');
                require_once(WYSIJA_VIEWS.$side.'.php');
                break;
            case 'helper':
                $helpdir=WYSIJA_PLG_DIR.$extended_plugin.DS.'helpers'.DS;
                $class_path=$helpdir.$name.'.php';
                $class_name = strtoupper($extended_plugin_name).'_help_'.$name;

                break;
            case 'model':
                $modeldir=WYSIJA_PLG_DIR.$extended_plugin.DS.'models'.DS;
                $class_path=$modeldir.$name.'.php';
                $class_name = strtoupper($extended_plugin_name).'_model_'.$name;
                // require the parent class necessary
                require_once(WYSIJA_CORE.'model.php');
                break;
            case 'widget':
                $modeldir=WYSIJA_PLG_DIR.$extended_plugin.DS.'widgets'.DS;
                $class_path=$modeldir.$name.'.php';
                if($name=='wysija_nl') $class_name='WYSIJA_NL_Widget';
                else $class_name = strtoupper($extended_plugin_name).'_widget_'.$name;
                break;
            default:
                WYSIJA::setInfo('error','WYSIJA::get does not accept this type of file "'.$type.'" .');
                return false;
        }

        if(!file_exists($class_path)) {
            WYSIJA::setInfo('error','file has not been recognised '.$class_path);
            return;
        }

        // require the file needed once and store & return the object needed
        require_once($class_path);
        return $array_of_objects[$extended_plugin][$type.$name]=new $class_name($extended_plugin_name);

    }

    /**
     * log function to spot some strange issues when sending emails for instance
     * @param type $key
     * @param type $data
     * @param type $category
     * @return type
     */
    public static function log($key='default',$data='empty',$category='default'){
        $config=&WYSIJA::get('config','model');

        if(defined('WYSIJA_DBG') && WYSIJA_DBG>1 && $category && (int)$config->getValue('debug_log_'.$category)>0){

            $optionlog=get_option('wysija_log');
            if ( false === $optionlog ){
                add_option( 'wysija_log', array() ,'','no');
                $optionlog=array();
            }

            $optionlog[$category][(string)microtime(true)][$key]=$data;
            update_option('wysija_log', $optionlog);
        }
        return false;
    }

    /**
     * the filter to add option to the cron frequency instead of being stuck with hourly, daily and twicedaily...
     * we can add filters but we cannot delete other values such as the default ones, as this might break other plugins crons
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
                'interval' => 604800,
                'display' => __( 'Once a week',WYSIJA)
                ),
            'each28days' => array(
                'interval' => 2419200,
                'display' => __( 'Once every 28 days',WYSIJA)
                ),
            );

        return array_merge($param, $frequencies);
    }

    /**
     * scheduled task for sending the emails in the queue, the frequency is set in the settings
     */
    public static function croned_queue() {
        //create the automatic post notifications email if there is any
        $autoNL=&WYSIJA::get('autonews','helper');
        $autoNL->checkPostNotif();

        //queue the scheduled newsletter also if there are any
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
     * everyday we make sure not to leave any trash files
     * remove temporary files
     */
    public static function croned_daily() {

        @ini_set('max_execution_time',0);

        /*user refresh count total*/
        $helper_user =& WYSIJA::get('user','helper');
        $helper_user->refreshUsers();

        /*clear temporary folders*/
        $helper_file =& WYSIJA::get('file','helper');
        $helper_file->clear();

        /*clear queue from unsubscribed*/
        $helper_queue =& WYSIJA::get('queue','helper');
        $helper_queue->clear();

        $model_config =& WYSIJA::get('config','model');

        /* send daily report about emails sent */
        if($model_config->getValue('emails_notified_when_dailysummary')){
            $helper_stats =& WYSIJA::get('stats','helper');
            $helper_stats->sendDailyReport();
        }

    }

    // Weekly cron
    public static function croned_weekly() {

        @ini_set('max_execution_time',0);

        $model_config =& WYSIJA::get('config','model');

        // If enabled, flag MixPanel sending on next page load.
        if ($model_config->getValue('analytics') == 1) {
            $model_config->save(array('send_analytics_now' => 1));
        }

    }

    // Monthly cron
    public static function croned_monthly() {

        @ini_set('max_execution_time',0);

        $model_config =& WYSIJA::get('config','model');

        /* send daily report about emails sent */
        if ($model_config->getValue('sharedata')) {
            $helper_stats =& WYSIJA::get('stats','helper');
            $helper_stats->share();
        }

    }

    /**
     * when we deactivate the plugin we clear the WP install from those cron records
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('wysija_cron_queue');
        wp_clear_scheduled_hook('wysija_cron_bounce');
        wp_clear_scheduled_hook('wysija_cron_daily');
        wp_clear_scheduled_hook('wysija_cron_weekly');
        wp_clear_scheduled_hook('wysija_cron_monthly');
    }


    /**
     * wysija's redirect allows to save some variables for the next page load such as notices etc..
     * @global type $wysija_msg
     * @global type $wysija_queries
     * @global type $wysija_queries_errors
     * @param type $redirectTo
     */
    public static function redirect($redirectTo){
        //save the messages
        global $wysija_msg,$wysija_queries,$wysija_queries_errors;
        WYSIJA::update_option('wysija_msg',$wysija_msg);
        WYSIJA::update_option('wysija_queries',$wysija_queries);
        WYSIJA::update_option('wysija_queries_errors',$wysija_queries_errors);
        wp_redirect($redirectTo);
        exit;
    }

    /**
     * custom post type for wysija is call wysijap as in wysija's post
     */
    public static function create_post_type() {

        //by default there is url rewriteing on wysijap custom post, though in one client case I had to deactivate it.
        //as this is rare we just need to set this setting to activate it
        //by default let's deactivate the url rewriting of the wysijap confirmation page because it is breaking in some case.
        $show_interface=false;
        if(defined('WYSIJA_DBG') && WYSIJA_DBG>1) $show_interface=true;
        register_post_type( 'wysijap',
            array(
                    'labels' => array(
                            'name' => __('Wysija page'),
                            'singular_name' => __('Wysija page')
                    ),
            'public' => true,
            'has_archive' => false,
            'show_ui' =>$show_interface,
            'show_in_menu' =>$show_interface,
            'rewrite' => false,
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

    /**
     * wysija update_option function is very similar to WordPress' one but it
     * can also manage new options not automatically loaded each time
     * @param type $option_name
     * @param type $newvalue
     * @param type $defaultload this parameter is the advantage other Wp's update_option here
     */
    public static function update_option($option_name,$newvalue,$defaultload='no'){
        if ( get_option( $option_name ) != $newvalue ) {
            update_option( $option_name, $newvalue );
        } else {
            add_option( $option_name, $newvalue, '', $defaultload );
        }
    }

    /**
     * When a WordPress user is added we also need to add it to the subscribers list
     * @param type $user_id
     * @return type
     */
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

    /**
     * when a WordPress user is updated we also need to update the corresponding subscriber
     * @param type $user_id
     * @return type
     */
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
            //chck that we didnt update the email
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

    /**
     * when a wp user is deleted we also delete the subscriber corresponding to it
     * @param type $user_id
     */
    public static function hook_del_WP_subscriber($user_id) {
        $modelConf=&WYSIJA::get('config','model');
        $modelUser=&WYSIJA::get('user','model');
        $data=$modelUser->getOne(array('email','user_id'),array('wpuser_id'=>$user_id));
        $modelUser->delete(array('email'=>$data['email']));
        $modelUser=&WYSIJA::get('user_list','model');
        $modelUser->delete(array('user_id'=>$data['user_id'],'list_id'=>$modelConf->getValue('importwp_list_id')));
    }

    /**
     * post notification transition hook, know when a post really gets published
     * @param type $new_status
     * @param type $old_status
     * @param type $post
     * @return type
     */
    public static function hook_postNotification_transition($new_status, $old_status, $post) {
        //we run some process only if the status of the post changes from something to publish
        if( $new_status=='publish' && $old_status!=$new_status){
            $modelEmail =& WYSIJA::get('email', 'model');
            $emails = $modelEmail->get(false, array('type' => 2, 'status' => array(1, 3, 99)));
            if(!empty($emails)){
                //we loop through all of the automatic emails
                foreach($emails as $key => $email) {
                    //we will try to give birth to a child email only if the automatic newsletter is a post notification email and in immediate mode
                    if(is_array($email) && $email['params']['autonl']['event'] === 'new-articles' && $email['params']['autonl']['when-article'] === 'immediate') {
                        WYSIJA::log('post_transition_hook_give_birth',array('postID'=>$post->ID,'postID'=>$post->post_title,'old_status'=>$old_status,'new_status'=>$new_status),'post_notif');
                        $modelEmail->reset();
                        $modelEmail->give_birth($email, $post->ID);
                    }
                }
            }
        }

        return true;
    }

    /**
     * uninstall process not used
     */
    public static function uninstall(){
        $helperUS=&WYSIJA::get('uninstall','helper');
        $helperUS->uninstall();
    }

    /**
     * this function is run when wysija gets activated
     * there is no installation process here, all is about checking the global status of the app
     */
    public static function activate(){
        $encoded_option=get_option('wysija');
        $installApp=false;
        if($encoded_option){
            $values=unserialize(base64_decode($encoded_option));
            if(isset($values['installed'])) $installApp=true;
        }

        //test again for plugins on reactivation
        if($installApp){
            $helper_import=&WYSIJA::get('import','helper');
            $helper_import->testPlugins();

            //resynch wordpress list
            $helper_user=&WYSIJA::get('user','helper');
            $helper_user->synchList($values['importwp_list_id']);
        }
    }

    /**
     * the is_plugin_active functions from WordPress sometimes are not loaded so here is one that works for single and multisites anywhere in the code
     * @param type $pluginName
     * @return type
     */
    public static function is_plugin_active($pluginName){
        $arrayactiveplugins=get_option('active_plugins');
        //we check in the list of the site options if the plugin is activated
        if(in_array($pluginName, $arrayactiveplugins)) {
            //plugin is activated for that site
            return true;
        }

        //if this is a multisite it might not be activated in the site option but network activated though
        if(is_multisite()){
            $plugins = get_site_option('active_sitewide_plugins');
            //plugin is activated for that multisite
            if(isset($plugins[$pluginName])){
                return true;
            }
        }
        return false;
    }

    /**
     * make sure that the current user has the good access rights corresponding to its role
     * @global type $current_user
     * @return type
     */
    public static function update_user_caps(){
        global $current_user;

        if(empty($current_user)) get_currentuserinfo();
        if(empty($current_user)) return false;
        $current_user->get_role_caps();

        return true;
    }

    /**
     * depending where it's used the base function from WordPress doesn't work, so this one will work anywhere
     * @param type $capability
     * @return type
     */
    public static function current_user_can($capability){
        if(!$capability) return false;
        WYSIJA::update_user_caps();
        if(!current_user_can($capability)) return false;
        return true;
    }

    /**
     * this function get and sets the cron schedules when Wysija's own cron system is active
     * @staticvar type $cron_schedules
     * @param type $schedule
     * @return type
     */
    public static function get_cron_schedule($schedule='queue'){
        static $cron_schedules;

        //if the cron schedules are already loaded statically then we just have to return the right schedule value
        if(!empty($cron_schedules)){
            if($schedule=='all') return $cron_schedules;
            if(isset($cron_schedules[$schedule])) {
                return $cron_schedules[$schedule];
            }else{
                WYSIJA::set_cron_schedule($schedule);
                return false;
            }
        }else{
            //this is the first time this function is executed so let's get them from the db and store them statically
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

    /**
     * return the frequency for each cron task needed by wysija
     * @return type an array of frequencies
     */
    public static function get_cron_frequencies(){
        $mConfig=&WYSIJA::get('config','model');
        $fHelper=&WYSIJA::get('forms','helper');

        $is_multisite=is_multisite();

        //$is_multisite=true;//PROD comment that line
        if($is_multisite && $mConfig->getValue('sending_method')=='network'){
           $sending_emails_each=$mConfig->getValue('ms_sending_emails_each');
        }else{
           $sending_emails_each=$mConfig->getValue('sending_emails_each');
        }

        $queue_frequency=$fHelper->eachValuesSec[$sending_emails_each];
        $bounce_frequency=99999999999999;
        if(isset($fHelper->eachValuesSec[$mConfig->getValue('bouncing_emails_each')]))  $bounce_frequency=$fHelper->eachValuesSec[$mConfig->getValue('bouncing_emails_each')];
        return array('queue'=>$queue_frequency,'bounce'=>$bounce_frequency,'daily'=>86400,'weekly'=>604800,'monthly'=>2419200);
    }

    /**
     * set the next cron schedule
     * TODO : needs probably to make the difference of running process for the next schedule, so that there is no delay(this is only problematic on some slow servers)
     * @param string $schedule
     * @param int $lastsaved
     * @param boolean $set_running
     * @return boolean
     */
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

    /**
     * check that there is no passed schedules that need to be executed now
     * @return void
     */
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

        $model_config=&WYSIJA::get('config','model');
        if(!empty($processesToRun) && $model_config->getValue('cron_page_hit_trigger')){
            //call the cron url

            $cron_url=site_url( 'wp-cron.php').'?'.WYSIJA_CRON.'&action=wysija_cron&process='.implode(',',$processesToRun).'&silent=1';

            //TODO we should use the http class there
            $hHTTP=&WYSIJA::get('http','helper');
            $hHTTP->request_timeout($cron_url);

        }
    }

    /**
     * Function somehow necessary to avoid some conflicts in windows server and WordPress autoload of plugins language file
     * @param type $boolean
     * @param type $domain
     * @param type $mofile
     * @return boolean
     */
    public static function override_load_textdomain($boolean, $domain, $mofile){
            $extensionloaded=WYSIJA::load_lang('get_all');

            if(isset($extensionloaded[$domain]) && !@file_exists($mofile)){
                return true;
            }

            return false;
    }

    /**
     * function to rewrite the path of the file if the file doesn't exist
     * @param type $mofile
     * @param type $domain
     * @return type
     */
    public static function load_textdomain_mofile($mofile, $domain){
        $extensionloaded=WYSIJA::load_lang('get_all');

        if(isset($extensionloaded[$domain]) && !file_exists($mofile)){
            return WYSIJA_PLG_DIR.$domain.DS.'languages'.DS.$extensionloaded[$domain].'-'.get_locale().'.mo';
        }
        return $mofile;
    }
}

//if we're entering the wysija's cron part, it should go and end there
if(isset($_REQUEST['action']) && $_REQUEST['action']=='wysija_cron'){
    add_action('init', 'init_wysija_cron',1);
    function init_wysija_cron(){
        $hCron=WYSIJA::get('cron','helper');
        $hCron->run();
    }
}

//subscribers/wp-user synch hooks
add_action('user_register', array('WYSIJA', 'hook_add_WP_subscriber'), 1);
add_action('added_existing_user', array('WYSIJA', 'hook_add_WP_subscriber'), 1);
add_action('profile_update', array('WYSIJA', 'hook_edit_WP_subscriber'), 1);
add_action('delete_user', array('WYSIJA', 'hook_del_WP_subscriber'), 1);

//post notif trigger
add_action('transition_post_status', array('WYSIJA', 'hook_postNotification_transition'), 1, 3);

//add image size for emails
add_image_size( 'wysija-newsletters-max', 600, 99999 );

$modelConf=&WYSIJA::get('config','model');
if($modelConf->getValue('installed_time')){
    if($modelConf->getValue('cron_manual')){
        //if cron queue is still set then unset it
        if(wp_get_schedule('wysija_cron_queue'))    WYSIJA::deactivate();

        //set the crons schedule for each process
        WYSIJA::get_cron_schedule();

    }else{
        //filter fixing a bug with automatic load_text_domain_from WP didn't understand yet why this was necessary...
        //somehow wp_register_script(which is irrelevant) was triggerring this kind of notice
        //Warning: is_readable() [function.is-readable]: open_basedir restriction in effect. File(C:\Domains\website.com\wwwroot\web/wp-content/plugins/C:\Domains\website.com\wwwroot\web\wp-content\plugins\wysija-newsletters/languages/wysija-newsletters-en_US.mo) is not within the allowed path(s): (.;C:\Domains\;C:\PHP\;C:\Sites\;C:\SitesData\;/) in C:\Domains\website.com\wwwroot\web\wp-includes\l10n.php on line 339
        //the only solution is to make sure on our end that the file exists and rewrite it if necessary
        add_filter( 'override_load_textdomain', array( 'WYSIJA', 'override_load_textdomain' ), 10, 3);
        add_filter('load_textdomain_mofile',  array( 'WYSIJA', 'load_textdomain_mofile' ), 10, 2);

        //filter to add new possible frequencies to the cron
        add_filter( 'cron_schedules', array( 'WYSIJA', 'filter_cron_schedules' ) );

        //action to handle the scheduled tasks in wysija
        add_action( 'wysija_cron_queue', array( 'WYSIJA', 'croned_queue' ) );
        add_action( 'wysija_cron_daily', array( 'WYSIJA', 'croned_daily' ) );
        add_action( 'wysija_cron_weekly', array( 'WYSIJA', 'croned_weekly' ) );
        add_action( 'wysija_cron_monthly', array( 'WYSIJA', 'croned_monthly' ) );

        //same with the weekly task
        if(!wp_next_scheduled('wysija_cron_weekly')){
            wp_schedule_event( $modelConf->getValue('last_save') , 'eachweek', 'wysija_cron_weekly' );
        }
        //the monthly task...
        if(!wp_next_scheduled('wysija_cron_monthly')){
            wp_schedule_event( $modelConf->getValue('last_save') , 'each28days', 'wysija_cron_monthly' );
        }

        //the daily task...
        if(!wp_next_scheduled('wysija_cron_daily')){
            wp_schedule_event( $modelConf->getValue('last_save') , 'daily', 'wysija_cron_daily' );
        }

        //if the bounce task is not scheduled then we initialize it
        if(!wp_next_scheduled('wysija_cron_bounce')){
            wp_schedule_event( $modelConf->getValue('last_save') , $modelConf->getValue('bouncing_emails_each'), 'wysija_cron_bounce' );
        }

        //and  the queue processing task ...
        //if we are in a multisite case we make sure that the ms frequency hasn't been changed, if it has we reset it
         $is_multisite=is_multisite();
        if($is_multisite && $modelConf->getValue('sending_method')=='network'){
            //in the case of multisite and the network's method we schedule with a different frequency
            //this option contains the list of sites already scheduled
            $ms_wysija_sending_cron=get_site_option('ms_wysija_sending_cron');
            global $blog_id;

            //if this blog is not recorded in our wysija_sending_cron option then we clear its scheduled so that we can reinitialize it
            if(!$ms_wysija_sending_cron || !isset($ms_wysija_sending_cron[$blog_id])){
                wp_clear_scheduled_hook('wysija_cron_queue');
                WYSIJA::set_cron_schedule('queue');
                $ms_wysija_sending_cron[$blog_id]=1;
                update_site_option('ms_wysija_sending_cron',$ms_wysija_sending_cron);
            }

        }


        //simply schedule the queue
        if(!wp_next_scheduled('wysija_cron_queue')){

            //in the case of multisite and the network's method we schedule with a different frequency
            if($is_multisite && $modelConf->getValue('sending_method')=='network'){
                $sending_emails_each=$modelConf->getValue('ms_sending_emails_each');
            }else{
               $sending_emails_each=$modelConf->getValue('sending_emails_each');
            }
            wp_schedule_event( $modelConf->getValue('last_save') , $sending_emails_each, 'wysija_cron_queue' );
        }

    }
}

//not yet used but the purpose is to override any notification sent through wp_mail
if($modelConf->getValue('wp_notifications')){
    $hWPnotif=&WYSIJA::get('wp_notifications','helper');
}

//check that there is no late cron schedules if we are using wysija's cron option and that the cron option is triggerred by any page view
if($modelConf->getValue('cron_manual') && !isset($_REQUEST['process'])){
    WYSIJA::cron_check();
}

register_deactivation_hook(WYSIJA_FILE, array( 'WYSIJA', 'deactivate' ));
register_activation_hook(WYSIJA_FILE, array( 'WYSIJA', 'activate' ));
add_action( 'init', array('WYSIJA','create_post_type') );

//launch application
$helper=&WYSIJA::get(WYSIJA_SIDE,'helper');
