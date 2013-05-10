<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_config extends WYSIJA_control{

    function WYSIJA_control_back_config(){
        if(!WYSIJA::current_user_can('wysija_config'))  die("Action is forbidden.");
        parent::WYSIJA_control();
    }

    function _displayErrors(){
       if(version_compare(phpversion(), '5.4')>= 0){
            error_reporting(E_ALL ^ E_STRICT);

        }else{
            error_reporting(E_ALL);
        }
       @ini_set("display_errors", 1);
    }

    function _hideErrors(){
       error_reporting(0);
       @ini_set('display_errors', '0');
    }

    function send_test_mail(){
        $this->_displayErrors();
        /*switch the send method*/
        $configVal=$this->_convertPostedInarray();

        /*send a test mail*/
        $hEmail=&WYSIJA::get('email','helper');
        $res['result']=$hEmail->send_test_mail($configVal);

        if($res['result']){
            $modelConf=&WYSIJA::get('config','model');
            $modelConf->save(array('sending_emails_ok'=>$res['result']));
        }
        $this->_hideErrors();
        return $res;
    }

    function send_test_mail_ms(){
        $this->_displayErrors();
        /*switch the send method*/
        $configVal=$this->_convertPostedInarray();

        /*send a test mail*/
        $hEmail=&WYSIJA::get('email','helper');
        $res['result']=$hEmail->send_test_mail($configVal,true);
        if($res['result']){
            $modelConf=&WYSIJA::get('config','model');
            $modelConf->save(array('ms_sending_emails_ok'=>$res['result']));
        }
        //$this->_hideErrors();
        return $res;
    }

    function bounce_connect(){
        $configVal=$this->_convertPostedInarray();
        /*try to connect to thebounce server*/
        $bounceClass=&WYSIJA::get('bounce','helper');
        $bounceClass->report = true;
        $res['result']=false;
        if($bounceClass->init($configVal)){
            if($bounceClass->connect()){
                $nbMessages = $bounceClass->getNBMessages();
                $this->notice(sprintf(__('Successfully connected to %1$s',WYSIJA),$bounceClass->config->getValue('bounce_login')));
                $this->notice(sprintf(__('There are %1$s messages in your mailbox',WYSIJA),$nbMessages));
                $bounceClass->close();
                if((int)$nbMessages >0) $res['result']=true;
                else $this->notice(sprintf(__('There are no bounced messages to process right now!',WYSIJA),$nbMessages));
                if(!empty($nbMessages)){
                        //$app->enqueueMessage('<a class="modal" style="text-decoration:blink" rel="{handler: \'iframe\', size: {x: 640, y: 480}}" href="'.acymailing_completeLink("bounces&task=process",true ).'">'.__("CLICK HERE to handle the messages",WYSIJA).'</a>');
                }
            }else{
                $errors = $bounceClass->getErrors();
                if(!empty($errors)){
                    $this->error($errors,true);
                    $errorString = implode(' ',$errors);
                    $port = $bounceClass->config->getValue('bounce_port','');
                    if(preg_match('#certificate#i',$errorString) && !$bounceClass->config->getValue('bounce_selfsigned',false)){
                            $this->notice(__('You may need to turn ON the option <i>Self-signed certificates</i>', WYSIJA));
                    }elseif(!empty($port) AND !in_array($port,array('993','143','110'))){
                            $this->notice(__('Are you sure you selected the right port? You can leave it empty if you do not know what to specify',WYSIJA));
                    }
                }
            }
        }


        return $res;
    }


    function bounce_process(){

        @ini_set('max_execution_time',0);

        $config = &WYSIJA::get('config','model');
        $bounceClass = &WYSIJA::get('bounce','helper');
        $bounceClass->report = true;
        if(!$bounceClass->init()){
                $res['result']=false;
                return $res;
        }
        if(!$bounceClass->connect()){
                $this->error($bounceClass->getErrors());
                $res['result']=false;
                return $res;
        }
        $this->notice(sprintf(__('Successfully connected to %1$s'),$config->getValue('bounce_login')));
        $nbMessages = $bounceClass->getNBMessages();


        if(empty($nbMessages)){
            $this->error(__('There are no messages'),true);
            $res['result']=false;
            return $res;
        }else{
            $this->notice(sprintf(__('There are %1$s messages in your mailbox'),$nbMessages));
        }


        $bounceClass->handleMessages();
        $bounceClass->close();

        $res['result']=true;

        return $res;
    }

    function linkignore(){
        $this->_displayErrors();

        $modelConf=&WYSIJA::get('config','model');

        $ignore_msgs=$modelConf->getValue('ignore_msgs');
        if(!$ignore_msgs) $ignore_msgs=array();

        $ignore_msgs[$_REQUEST['ignorewhat']]=1;
        $modelConf->save(array('ignore_msgs'=>$ignore_msgs));

        $res['result']=true;
        $this->_hideErrors();
        return $res;
    }

    // Ajax called function to enable analytics sharing from welcome page.
    function share_analytics() {
        $this->_displayErrors();

        $model_config =& WYSIJA::get('config','model');
        $model_config->save(array('analytics' => 1));

        $res['result'] = true;
        $this->_hideErrors();
        return $res;
    }

    function validate(){
        $helpLic=&WYSIJA::get('licence','helper');
        $res=$helpLic->check();

        if(!isset($res['result']))  $res['result']=false;
        return $res;
    }

    function devalidate(){

        $modelCOnfig=&WYSIJA::get('config','model');
        $res=$modelCOnfig->save(array('premium_key'=>false));

        if(!isset($res['result']))  $res['result']=false;
        return $res;
    }



    function _convertPostedInarray(){
        $_POST   = stripslashes_deep($_POST);
        $dataTemp=$_POST['data'];
        $_POST['data']=array();
        foreach($dataTemp as $val) $_POST['data'][$val['name']]=$val['value'];
        $dataTemp=null;
        foreach($_POST['data'] as $k =>$v){
            $newkey=str_replace(array('wysija[config][',']'),'',$k);
            $configVal[$newkey]=$v;
        }
        return $configVal;
    }

    // WYSIJA Form Editor
    function wysija_form_generate_template() {
        $field = array();

        if(isset($_POST['wysijaData'])) {
            // decode the data string
            $decoded_data = base64_decode($_POST['wysijaData']);

            // avoid using stripslashes as it's not reliable depending on the magic quotes settings
            $json_data = str_replace('\"', '"', $decoded_data);
            $field = json_decode($json_data, true);

            $helper_form_engine =& WYSIJA::get('form_engine', 'helper');
            return base64_encode($helper_form_engine->render_editor_template($field));
        }
    }

    function form_name_save() {
        // get name from post and stripslashes it
        $form_name = trim(stripslashes($_POST['name']));
        // get form_id from post
        $form_id = (int)$_POST['form_id'];

        if(strlen($form_name) > 0 && $form_id > 0) {
            // update the form name within the database
            $model_forms =& WYSIJA::get('forms', 'model');
            $model_forms->update(array('name' => $form_name), array('form_id' => $form_id));
        }
        return array('name' => $form_name);
    }

    function form_save() {
        // get form id
        $form_id = null;
        if(isset($_POST['form_id']) && (int)$_POST['form_id'] > 0) {
            $form_id = (int)$_POST['form_id'];
        }

        // decode json data and convert to array
        $raw_data = null;
        if(isset($_POST['wysijaData'])) {
            // decode the data string
            $decoded_data = base64_decode($_POST['wysijaData']);

            // avoid using stripslashes as it's not reliable depending on the magic quotes settings
            $json_data = str_replace('\"', '"', $decoded_data);
            // decode JSON data
            $raw_data = json_decode($json_data, true);
        }

        if($form_id === null or $raw_data === null) {
            $this->error('Error saving', false);
            return array('result' => false);
        } else {

            // flag to see if the user can select his own lists
            $has_list_selection = false;
            $raw_data['settings']['lists_selected_by'] = 'admin';

            // special case for block params, as we base64_encode the values and serialize arrays, so let's decode it before saving it
            foreach($raw_data['body'] as $block_id => $block) {
                if(isset($block['params']) && !empty($block['params'])) {
                    $params = array();

                    foreach($block['params'] as $key => $value) {
                        $value = base64_decode($value);
                        if(is_serialized($value) === true) {
                            $value = unserialize($value);
                        }
                        $params[$key] = $value;
                    }

                    if(!empty($params)) {
                        $raw_data['body'][$block_id]['params'] = $params;
                    }
                }
                // special case when the list selection widget is present
                if($block['type'] === 'list') {
                    $has_list_selection = true;

                    $lists = array();
                    foreach($params['values'] as $list) {
                        $lists[] = (int)$list['list_id'];
                    }

                    // override lists in form settings
                    $raw_data['settings']['lists'] = $lists;
                    $raw_data['settings']['lists_selected_by'] = 'user';
                }
            }

            // make sure the lists parameter is an array, otherwise it's not gonna work for a single list
            if($has_list_selection === false) {
                if(!is_array($raw_data['settings']['lists'])) {
                    $raw_data['settings']['lists'] = array((int)$raw_data['settings']['lists']);
                }
            }

            // set form id into data so we can track who subscribed through it
            $raw_data['form_id'] = $form_id;

            // set data in form engine so we can generate the render the web version
            $helper_form_engine =& WYSIJA::get('form_engine', 'helper');
            $helper_form_engine->set_data($raw_data);

            // check if the form has already been inserted in a widget and therefore display different success message
            $widgets = get_option('widget_wysija');
            $is_form_added_as_widget = false;
            if($widgets !== false) {
                foreach($widgets as $widget) {
                    if(is_array($widget) && isset($widget['form']) && (int)$widget['form'] === $form_id) {
                        $is_form_added_as_widget = true;
                    }

                }
            }
            if($is_form_added_as_widget === true) {
                $save_message = __('Saved! The changes are already active in your widget.', WYSIJA);
            } else {
                $save_message = str_replace(array(
                       '[link_widget]',
                       '[/link_widget]'
                    ), array(
                        '<a href="'.admin_url('widgets.php').'" target="_blank">',
                        '</a>'
                    ),
                    __('Saved! Add this form to [link_widget]a widget[/link_widget]', WYSIJA)
                );
            }

            // update form data in DB
            $model_forms =& WYSIJA::get('forms', 'model');
            $model_forms->reset();
            $result = $model_forms->update(array(
                // get encoded data to store it in the database
                'data' => $helper_form_engine->get_encoded('data')
            ), array('form_id' => $form_id));

            // return response depending on db save result
            if(!$result) {
                // throw error
                $this->error(__('Your form could not be saved', WYSIJA));
            } else {
                // save successful
                $this->notice(__('Your form has been saved', WYSIJA));
            }
        }

        return array('result' => $result, 'save_message' => base64_encode($save_message), 'exports' => base64_encode($helper_form_engine->render_editor_export($form_id)));
    }
}