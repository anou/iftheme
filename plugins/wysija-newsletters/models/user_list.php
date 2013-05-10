<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_user_list extends WYSIJA_model{

    var $pk=array("list_id","user_id");
    var $table_name="user_list";
    var $columns=array(
        'list_id'=>array("req"=>true,"type"=>"integer"),
        'user_id'=>array("req"=>true,"type"=>"integer"),
        'sub_date' => array("type"=>"integer"),
        'unsub_date' => array("type"=>"integer")
    );



    function WYSIJA_model_user_list(){
        $this->WYSIJA_model();
    }


    function hook_subscriber_to_list( $details ) {

        $config=&WYSIJA::get('config','model');
        $modelUser=&WYSIJA::get('user','model');
        $userdata=$modelUser->getOne(false,array('user_id'=>$details['user_id']));
        $confirmed=true;

        /* do not send email if user is not confirmed*/
        /*if($config->getValue('confirm_dbleoptin') && (int)$userdata['status']!=1)   $confirmed=false;

        if($confirmed){
            $helperU=&WYSIJA::get('user','helper');
            $helperU->sendAutoNl($details['user_id'],array(0=>$details));
        }*/
        $dbloptin=$config->getValue('confirm_dbleoptin');
        /*only if dbleoptin has been deactivated we send immediately the post notification*/

        if(!$dbloptin || ($dbloptin && (int)$userdata['status']>0)){
            /*check for auto nl and send if needed*/
            $helperU=&WYSIJA::get('user','helper');
            if(isset($this->backSave) && $this->backSave){
                $helperU->sendAutoNl($details['user_id'],array(0=>$details),'subs-2-nl',true);
            }else{
                $helperU->sendAutoNl($details['user_id'],array(0=>$details));
            }
        }

        return true;
    }

    function afterInsert($resultSaveID) {
        if(!isset($this->nohook)){
            add_action('wysija_subscribed_to', array($this, 'hook_subscriber_to_list'), 1);
        }

        do_action('wysija_subscribed_to',$this->values);
    }

}
