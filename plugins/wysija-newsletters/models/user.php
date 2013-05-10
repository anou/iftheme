<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_user extends WYSIJA_model{

    var $pk="user_id";
    var $table_name="user";
    var $columns=array(
        'user_id'=>array("auto"=>true),
        'wpuser_id' => array("req"=>true,"type"=>"integer"),
        'email' => array("req"=>true,"type"=>"email"),
        'firstname' => array(),
        'lastname' => array(),
        'ip' => array("req"=>true,"type"=>"ip"),
        'keyuser' => array(),
        'status' => array("req"=>true,"type"=>"boolean"),
        'created_at' => array("auto"=>true,"type"=>"date"),
    );

    function WYSIJA_model_user(){
        $this->columns['status']['label']=__('Status',WYSIJA);
        $this->columns['created_at']['label']=__('Created on',WYSIJA);
        $this->WYSIJA_model();
    }

    function beforeInsert(){
        /* set the activation key */
        $modelUser=WYSIJA::get("user","model");

        $this->values['keyuser']=md5($this->values['email'].$this->values['created_at']);
        while($modelUser->exists(array("keyuser"=>$this->values['keyuser']))){
            $this->values['keyuser']=$this->generateKeyuser($this->values['email']);
            $modelUser->reset();
        }

        if(!isset($this->values['status'])) $this->values['status']=0;

        return true;
    }

    function getSubscriptionStatus($uid){
        $this->getFormat=OBJECT;
        $result=$this->getOne(array('status'),array('user_id'=>$uid));
        return $result->status;
    }

    function getObject($uid){
        $this->getFormat=OBJECT;
        return $this->getOne(false,array('user_id'=>$uid));
    }


    function getDetails($conditions,$stats=false,$subscribedListOnly=false){
        $data=array();
        $this->getFormat=ARRAY_A;
        $array=$this->getOne(false,$conditions);
        if(!$array) return false;

        $data['details']=$array;

        //get the list  that the user subscribed to
        $modelRECYCLE=&WYSIJA::get('user_list','model');
        $conditions=array('user_id'=>$data['details']['user_id']);
        if($subscribedListOnly){
            $conditions['unsub_date']=0;
        }

        $data['lists']=$modelRECYCLE->get(false,$conditions);

        //get the user stats if requested
        if($stats){
            $modelRECYCLE=&WYSIJA::get('email_user_stat','model');
            $modelRECYCLE->setConditions(array('equal'=>array('user_id'=>$data['details']['user_id'])));
            $data['emails']=$modelRECYCLE->count(false);
        }

        return $data;
    }

    function getCurrentSubscriber(){
        $this->getFormat=OBJECT;
        $objUser=$this->getOne(false,array('wpuser_id'=>WYSIJA::wp_get_userdata('ID')));

        if(!$objUser){
            $this->getFormat=OBJECT;
            $objUser=$this->getOne(false,array('email'=>WYSIJA::wp_get_userdata('user_email')));
            $this->update(array('wpuser_id'=>WYSIJA::wp_get_userdata('ID')),array('email'=>WYSIJA::wp_get_userdata('user_email')));
        }


        //the subscriber doesn't exist
        if(!$objUser){
            $data=get_userdata(WYSIJA::wp_get_userdata('ID'));
            $firstname=$data->first_name;
            $lastname=$data->last_name;
            if(!$data->first_name && !$data->last_name) $firstname=$data->display_name;
            $this->noCheck=true;
            $this->insert(array(
                'wpuser_id'=>$data->ID,
                'email'=>$data->user_email,
                'firstname'=>$firstname,
                'lastname'=>$lastname));
            $this->getFormat=OBJECT;
            $objUser=$this->getOne(false,array('wpuser_id'=>WYSIJA::wp_get_userdata('ID')));
        }

        return $objUser;
    }

    function getConfirmLink($userObj=false,$action='subscribe',$text=false,$urlOnly=false){
        if(!$text) $text=__('Click here to subscribe',WYSIJA);
        $userspreview=false;
        //if($action=='subscriptions')dbg($userObj);
        if(!$userObj){
            //preview mode
            $userObj=$this->getCurrentSubscriber();
            $userspreview=true;
        }
        $params=array(
        'wysija-page'=>1,
        'controller'=>'confirm',
        );
        if($userObj && isset($userObj->keyuser)){
            //if the user key doesn exists let's generate it
            if(!$userObj->keyuser){
                $this->getKeyUser($userObj);
            }

            $this->reset();
            $params['wysija-key']=$userObj->keyuser;
        }
        $params['action']=$action;
        $modelConf=&WYSIJA::get('config','model');
        if($userspreview) $params['demo']=1;
        $fullurl=WYSIJA::get_permalink($modelConf->getValue('confirm_email_link'),$params);
        if($urlOnly) return $fullurl;
        return '<a href="'.$fullurl.'" target="_blank">'.$text.'</a>';
    }

    function getEditsubLink($userObj=false,$urlOnly=false){
        return $this->getConfirmLink($userObj,'subscriptions',__('Edit your subscriptions',WYSIJA),$urlOnly);
    }

    function getUnsubLink($userObj=false,$urlOnly=false){
        $modelConf=&WYSIJA::get('config','model');
        return $this->getConfirmLink($userObj,'unsubscribe',$modelConf->getValue('unsubscribe_linkname'),$urlOnly);
    }

    function getResendLink($userid,$email_id){
        $params=array(
            'wysija-page'=>1,
            'controller'=>'confirm',
            'action'=>'resend',
            'user_id'=>$userid,
            'email_id'=>$email_id
        );

        $modelConf=&WYSIJA::get('config','model');
        return WYSIJA::get_permalink($modelConf->getValue('confirm_email_link'),$params);
    }

    function getKeyUser($user){
        //generate a user key
        $user->keyuser=$this->generateKeyuser($user->email);
         while($this->exists(array('keyuser'=>$user->keyuser))){
             $user->keyuser=$this->generateKeyuser($user->email);
         }
        $this->update(array('keyuser'=>$user->keyuser),array('user_id'=>$user->user_id));
    }

    function generateKeyuser($email){
        return md5($email.time());
    }

    function user_id($email){
        $this->getFormat=ARRAY_A;
        if(is_numeric($email)){
            $obj=$this->getOne(array("user_id"),array("wpuser_id"=>$email));
            //$cond = ' wpuser_id = '.$email;
        }else{
            $obj=$this->getOne(array("user_id"),array("email"=>$email));
            //$cond = 'email = '.$this->database->Quote(trim($email));
        }

            //$this->database->setQuery('SELECT subid FROM '.acymailing_table('subscriber').' WHERE '.$cond);
        return $obj['user_id'];
    }

    function beforeDelete(){
        $newum=new WYSIJA_model_user();
        $users=$newum->get(array('user_id'),$this->conditions);
        $userids=array();
        foreach($users as $usr) $userids[]=$usr['user_id'];

        //delete all the user stats
        $eusM=&WYSIJA::get('email_user_stat','model');
        $conditions=array('user_id'=>$userids);
        $eusM->delete($conditions);
        //delete all the queued emails
        $qM=&WYSIJA::get('queue','model');
        $qM->delete($conditions);
        return true;
    }

    function afterDelete(){
        $helper_user=&WYSIJA::get('user','helper');
        $helper_user->refreshUsers();
        return true;
    }

    function afterInsert($id){
        $helper_user=&WYSIJA::get('user','helper');
        $helper_user->refreshUsers();

        do_action('wysija_subscriber_added', $id);
        return true;
    }

    function afterUpdate($id){
        $helper_user=&WYSIJA::get('user','helper');
        $helper_user->refreshUsers();
        
        do_action('wysija_subscriber_modified', $id);
        return true;
    }
}
