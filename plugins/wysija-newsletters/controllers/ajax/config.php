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

    function form_delete(){
        /*delete an element from wysija_forms option*/
        $wysija_forms=json_decode(get_option('wysija_forms'),true);
        unset($wysija_forms[$_POST['formid']]);
        WYSIJA::update_option('wysija_forms', json_encode($wysija_forms));
        $this->notice('Form deleted');
        return true;
    }

    function form_save(){
        /*add modify an element in the wysija_forms option*/
        $arrayData=json_decode(stripslashes($_POST['data']),true);
        $wysija_forms=json_decode(get_option('wysija_forms'),true);
        if(isset($wysija_forms[$_POST['formid']]))    unset($wysija_forms[$_POST['formid']]);
        $wysija_forms[$arrayData['id']]=$arrayData;
        WYSIJA::update_option('wysija_forms', json_encode($wysija_forms));
        $this->notice('Form added/modified');

        return json_decode(stripslashes($_POST['data']),true);
    }

    function send_test_mail(){
        $this->_displayErrors();
        /*switch the send method*/
        $configVal=$this->_convertPostedInarray();

        /*send a test mail*/
        $toolbox=&WYSIJA::get('email','helper');
        $res['result']=$toolbox->send_test_mail($configVal);

        if($res['result']){
            $modelConf=&WYSIJA::get("config","model");
            $modelConf->save(array('sending_emails_ok'=>$res['result']));
        }
        $this->_hideErrors();
        return $res;
    }

    function bounce_connect(){


        $configVal=$this->_convertPostedInarray();



        /*try to connect to thebounce server*/
        $bounceClass=&WYSIJA::get("bounce","helper");
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

    function linkshareme(){
        $this->_displayErrors();

        $modelConf=&WYSIJA::get('config','model');
        $modelConf->save(array('sharedata'=>true));

        $res['result']=true;
        $this->_hideErrors();
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

}