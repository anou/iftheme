<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_control_front_confirm extends WYSIJA_control_front{
    var $model='user';
    var $view='confirm';

    function WYSIJA_control_front_confirm(){
        parent::WYSIJA_control_front();
    }

    function _testKeyuser(){
        $this->helperUser=&WYSIJA::get('user','helper');

        $this->userData=$this->helperUser->checkUserKey();
        add_action('init',array($this,'testsession'));

        if(!$this->userData){
            $this->title=__('Page does not exist.',WYSIJA);
            $this->subtitle=__('Please verify your link to this page.',WYSIJA);
            return false;
        }
        return true;
    }


    function subscribe(){
        $listids=array();
        if(isset($_REQUEST['wysiconf'])) $listids= unserialize(base64_decode($_REQUEST['wysiconf']));
        $mList=&WYSIJA::get('list','model');
        $namesRes=$mList->get(array('name'),array('list_id'=>$listids));
        $names=array();
        foreach($namesRes as $nameob) $names[]=$nameob['name'];

        $modelConf=&WYSIJA::get('config','model');
        $this->title=$modelConf->getValue('subscribed_title');
        if(!isset($modelConf->values['subscribed_title'])) $this->title=__('You\'ve subscribed to: %1$s',WYSIJA);
        $this->title=sprintf($this->title,  implode(', ', $names));

        $this->subtitle=$modelConf->getValue('subscribed_subtitle');
        if(!isset($modelConf->values['subscribed_subtitle'])) $this->subtitle=__("Yup, we've added you to our list. You'll hear from us shortly.",WYSIJA);

        if(!isset($_REQUEST['demo'])){
            if($this->_testKeyuser()){
               //user is not confirmed yet
               if((int)$this->userData['details']['status']<1){
                    $this->helperUser->subscribe($this->userData['details']['user_id'],true, false,$listids);
                    $this->helperUser->uid=$this->userData['details']['user_id'];
                    if($modelConf->getValue('emails_notified') && $modelConf->getValue('emails_notified_when_sub'))    $this->helperUser->_notify($this->userData['details']['email']);
                    return true;
                }else{
                    if(isset($_REQUEST['wysiconf'])){
                        $modelUserList=&WYSIJA::get('user_list','model');
                        $needssubscription=false;
                        foreach($this->userData['lists'] as $list){
                            if(in_array($list['list_id'],$listids) && (int)$list['sub_date']<1){
                                $needssubscription=true;
                            }
                        }
                        if($needssubscription){
                            $this->helperUser->subscribe($this->userData['details']['user_id'],true,false,$listids);
                            $this->title=sprintf($modelConf->getValue('subscribed_title'),  implode(', ', $names));
                            $this->subtitle=$modelConf->getValue('subscribed_subtitle');
                        }else{
                            $this->title=sprintf(__('You are already subscribed to : %1$s',WYSIJA),  implode(', ', $names));
                        }
                    }else{
                        $this->title=__('You are already subscribed.',WYSIJA);
                    }
                    return false;
                }
            }
        }

        return true;
    }

    function unsubscribe(){
        $modelConf=&WYSIJA::get('config','model');
        $this->title=$modelConf->getValue('unsubscribed_title');
        if(!isset($modelConf->values['unsubscribed_title'])) $this->title=__("You've unsubscribed!",WYSIJA);

        $this->subtitle=$modelConf->getValue('unsubscribed_subtitle');
        if(!isset($modelConf->values['unsubscribed_subtitle'])) $this->subtitle=__("Great, you'll never hear from us again!",WYSIJA);

        if(!isset($_REQUEST['demo'])){
            if($this->_testKeyuser()){
                $statusint=(int)$this->userData['details']['status'];
                if( ($modelConf->getValue('confirm_dbleoptin') && $statusint>0) || (!$modelConf->getValue('confirm_dbleoptin') && $statusint>=0)){
                    $listids=$this->helperUser->unsubscribe($this->userData['details']['user_id']);
                    $this->helperUser->uid=$this->userData['details']['user_id'];
                    if($modelConf->getValue('emails_notified') && $modelConf->getValue('emails_notified_when_unsub'))    $this->helperUser->_notify($this->userData['details']['email'],false,$listids);
                }else{
                    $this->title=__('You are already unsubscribed.',WYSIJA);
                    return false;
                }
            }
        }
        return true;
    }

    function subscriptions(){
        $data=array();

        //get the user_id out of the params passed
        if($this->_testKeyuser()){

            $data['user']=$this->userData;
            //get the list of user
            $modelL=&WYSIJA::get('list','model');
            $modelL->orderBy('ordering','ASC');
            $data['list']=$modelL->get(array('list_id','name','description'),array('is_enabled'=>true,'is_public'=>true));

            $this->title=sprintf(__('Edit your subscriber profile: %1$s',WYSIJA),$data['user']['details']['email']);

            $this->subtitle=$this->viewObj->subscriptions($data);


            return true;
        }


    }



    function resend(){
        $this->title='The link you clicked has expired';

        $this->subtitle=$this->viewObj->resend();
    }


    function resendconfirm(){
        //make sure the user has the right to access this action
        if($this->requireSecurity()){
            //resend email
            $hMailer=&WYSIJA::get('mailer','helper');
            $hMailer->sendOne((int)$_REQUEST['email_id'],(int)$_REQUEST['user_id']);
            $this->title='Please check your inbox!';

            $this->subtitle='<h3>A new email with working links has been sent to you.<h3/>';
        }
    }


    function save(){

        //get the user_id out of the params passed */
        if($this->_testKeyuser()){
            //update the general details */
            $userid=$_REQUEST['wysija']['user']['user_id'];
            unset($_REQUEST['wysija']['user']['user_id']);
            $modelConf=&WYSIJA::get('config','model');
            $this->helperUser->uid=$userid;
            //if the status changed we might need to send notifications */
            if((int)$_REQUEST['wysija']['user']['status'] !=(int)$this->userData['details']['status']){
                if($_REQUEST['wysija']['user']['status']>0){
                    if($modelConf->getValue('emails_notified_when_sub'))    $this->helperUser->_notify($this->userData['details']['email']);
                }else{
                    if($modelConf->getValue('emails_notified_when_unsub'))    $this->helperUser->_notify($this->userData['details']['email'],false);
                }
            }

            //check whether the email address has changed if so then we should make sure that the new address doesnt exists already
            if(isset($_REQUEST['wysija']['user']['email'])){
                $_REQUEST['wysija']['user']['email']=trim($_REQUEST['wysija']['user']['email']);
                if($this->userData['details']['email']!=$_REQUEST['wysija']['user']['email']){
                    $this->modelObj->reset();
                    $result=$this->modelObj->getOne(false,array('email'=>$_REQUEST['wysija']['user']['email']));
                    if($result){
                        $this->error(sprintf(__('Email %1$s already exists.',WYSIJA),$_REQUEST['wysija']['user']['email']),1);
                        unset($_REQUEST['wysija']['user']['email']);
                    }
                }
            }

            $this->modelObj->update($_REQUEST['wysija']['user'],array('user_id'=>$userid));
            $id=$userid;

            $hUser=&WYSIJA::get('user','helper');
            //update the list subscriptions */
           //run the unsubscribe process if needed
            if((int)$_REQUEST['wysija']['user']['status']==-1){
                $hUser->unsubscribe($id);
            }

            //update subscriptions */
            $modelUL=&WYSIJA::get('user_list','model');
            $modelUL->backSave=true;
            /* list of core list */
            $modelLIST=&WYSIJA::get('list','model');
            $results=$modelLIST->get(array('list_id'),array('is_enabled'=>'0'));
            $core_listids=array();
            foreach($results as $res){
                $core_listids[]=$res['list_id'];
            }

            //0 - get current lists of the user
            $userlists=$modelUL->get(array('list_id','unsub_date'),array('user_id'=>$id));

            $oldlistids=$newlistids=array();
            foreach($userlists as $listdata)    $oldlistids[$listdata['list_id']]=$listdata['unsub_date'];

            $config=&WYSIJA::get('config','model');
            $dbloptin=$config->getValue('confirm_dbleoptin');
            //1 - insert new user_list
            if(isset($_POST['wysija']['user_list']) && $_POST['wysija']['user_list']){
                $modelUL->reset();
                $modelUL->update(array('sub_date'=>time()),array('user_id'=>$id));
                foreach($_POST['wysija']['user_list']['list_id'] as $list_id){
                    //if the list is not already recorded for the user then we will need to insert it
                    if(!isset($oldlistids[$list_id])){
                        $modelUL->reset();
                        $newlistids[]=$list_id;
                        $dataul=array('user_id'=>$id,'list_id'=>$list_id,'sub_date'=>time());
                        //if double optin is on then we want to send a confirmation email for newly added subscription
                        if($dbloptin){
                            unset($dataul['sub_date']);
                            $modelUL->nohook=true;
                        }
                        $modelUL->insert($dataul);
                    //if the list is recorded already then let's check the status, if it is an unsubed one then we update it
                    }else{
                        if($oldlistids[$list_id]>0){
                            $modelUL->reset();
                            $modelUL->update(array('unsub_date'=>0,'sub_date'=>time()),array('user_id'=>$id,'list_id'=>$list_id));
                        }
                        //$alreadysubscribelistids[]=$list_id;
                    }
                }
            }




            //if a confirmation email needs to be sent then we send it
            if($dbloptin && !empty($newlistids)){
                $hUser->sendConfirmationEmail($id,true,$newlistids);
            }

            // list ids
            $list_ids = $_POST['wysija']['user_list']['list_id'];
            if(is_array($list_ids) === false) $list_ids = array();

            $notEqual = array_merge($core_listids, $list_ids);

            //delete the lists from which you've removed yourself
            $condiFirst = array('notequal'=>array('list_id'=> $notEqual), 'equal' => array('user_id' => $id, 'unsub_date' => 0));
            $modelUL=&WYSIJA::get('user_list','model');
            $modelUL->update(array('unsub_date'=>time()),$condiFirst);
            $modelUL->reset();
            $this->notice(__('Newsletter profile has been updated.',WYSIJA));
            $this->subscriptions();

            //reset post otherwise wordpress will not recognise the post !!!
            $_POST=array();
        }
        return true;
    }
}