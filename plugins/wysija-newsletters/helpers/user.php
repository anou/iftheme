<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_user extends WYSIJA_object{
    function getIP(){
        $ip = '';
        if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) AND strlen($_SERVER['HTTP_X_FORWARDED_FOR'])>6 ){
            $ip = strip_tags($_SERVER['HTTP_X_FORWARDED_FOR']);
        }elseif( !empty($_SERVER['HTTP_CLIENT_IP']) AND strlen($_SERVER['HTTP_CLIENT_IP'])>6 ){
             $ip = strip_tags($_SERVER['HTTP_CLIENT_IP']);
        }elseif(!empty($_SERVER['REMOTE_ADDR']) AND strlen($_SERVER['REMOTE_ADDR'])>6){
             $ip = strip_tags($_SERVER['REMOTE_ADDR']);
        }//endif
        if(!$ip) $ip="127.0.0.1";
        return strip_tags($ip);
    }
    function validEmail($email){
            if(empty($email) OR !is_string($email)) return false;
            $stringSpecialChars='\æ\ø\å\ä\ö\ü';
            if(!preg_match('/^([a-z0-9_\'&\.\-\+'.$stringSpecialChars.'])+\@(([a-z0-9\-'.$stringSpecialChars.'])+\.)+([a-z0-9]{2,10})+$/i',$email)) return false;
            return true;
    }
    function checkUserKey(){
        if(isset($_REQUEST['wysija-key'])){
            $modelUser=&WYSIJA::get('user','model');
            $result=$modelUser->getDetails(array('keyuser'=>$_REQUEST['wysija-key']));
            if($result) {
                return $result;
            }
            else{
                $this->error(__('Page is not accessible.',WYSIJA),true);
                return false;
            }
        }else{
            $this->error(__('Page is not accessible.',WYSIJA),true);
            return false;
        }
    }
    function getUserLists($user_id,$listids=array()){
        $userModel=&WYSIJA::get('user','model');
        $listidin='';
        if(!empty($listids)) $listidin="AND A.list_id IN(".implode (",", $listids).")";
        $query='SELECT A.* FROM [wysija]user_list as A LEFT JOIN [wysija]list as B on A.list_id=B.list_id WHERE A.user_id='.$user_id.' AND B.is_enabled=1 '.$listidin;
        return $userModel->getResults($query);
    }
    
    function subscribe($user_id,$status=true,$auto=false,$listids=array()){
        $time=time();
        $cols=false;
        
        $modelL=&WYSIJA::get('list','model');
        $listsdata=$modelL->get(array('list_id'),array('is_enabled'=>'1'));
        $listidsenabled=array();
        foreach($listsdata as $listdt){
            $listidsenabled[]=$listdt['list_id'];
        }
        
        $modelUserList=&WYSIJA::get('user_list','model');
        $listidsfromuser=$modelUserList->get(array('list_id'),array('user_id'=>$user_id,'list_id'=>$listidsenabled));
        $listidsenableduser=array();
        foreach($listidsfromuser as $listdt){
            $listidsenableduser[]=$listdt['list_id'];
        }
        if($status){
            $status=1;
        }else{

            $status=-1;
            $modelU=&WYSIJA::get('queue','model');
            $modelU->delete(array('user_id'=>$user_id));

            $modelUserList->update(array('unsub_date'=>time(), 'sub_date'=>0),array('user_id'=>$user_id));
        }
        $modelUser=&WYSIJA::get('user','model');
        $modelUser->update(array('status'=>$status),array('user_id'=>$user_id));

        if($status){

            if(!$auto){
                $modelUserList=&WYSIJA::get('user_list','model');
                $cols=array('sub_date'=>$time,'unsub_date'=>0);
                $modelUserList->update($cols,array('user_id'=>$user_id,'list_id'=>$listids));
            }

            $lists=$this->getUserLists($user_id,$listids);
            $this->sendAutoNl($user_id,$lists);
        }
        return $listidsenableduser;
    }
    function checkData(&$data){
         if(isset($data['user']['abs'])){
            foreach($data['user']['abs'] as $honeyKey => $honeyVal){

                if($honeyVal) return false;
            }
            unset($data['user']['abs']);
        }
        return true;
    }
    
    function addSubscriber($data,$backend=false){

        if(!$backend){
            $validEmail=apply_filters( 'wysija_beforeAddSubscriber', true ,$data['user']['email']);
            if(!$validEmail){
                $this->error(__('The email is not valid!',WYSIJA),true);
                return false;
            }
        }

        if(!$this->validEmail($data['user']['email'])){
            $this->error(__('The email is not valid!',WYSIJA),true);
            return false;
        }

        $model_user=&WYSIJA::get('user','model');
        $user_get=$model_user->getOne(false,array('email'=>trim($data['user']['email'])));
        $config=&WYSIJA::get('config','model');
        $dbloptin=$config->getValue('confirm_dbleoptin');

        $message_success='';
        if(isset($data['message_success'])) {
            $message_success = strip_tags($data['message_success'], '<p><em><span><b><strong><i><h1><h2><h3><a><ul><ol><li><br>');
        } else if(isset($data['success_message'])) {
            $message_success = strip_tags(nl2br(base64_decode($data['success_message'])), '<p><em><span><b><strong><i><h1><h2><h3><a><ul><ol><li><br>');
        } else if(isset($data['form_id'])) {

            $model_forms =& WYSIJA::get('forms', 'model');
            $form = $model_forms->getOne(array('data'), array('form_id' => (int)$data['form_id']));
            $form_data = unserialize(base64_decode($form['data']));

            if($form_data['settings']['on_success'] === 'message') {
                $message_success = nl2br($form_data['settings']['success_message']);
            }
        }
        if($user_get){

            if($backend){
                $this->error(str_replace(array('[link]','[/link]'),array('<a href="admin.php?page=wysija_subscribers&action=edit&id='.$user_get['user_id'].'" >',"</a>"),__('Subscriber already exists. [link]Click to edit[/link].',WYSIJA)),true);
                return false;
            }

            if((int)$user_get['status']<1){
                $model_user->reset();
                $model_user->update(array('status'=>0),array('user_id'=>$user_get['user_id']));
                $subscribetolist=0;
                if(!$dbloptin)  $subscribetolist=time();
                $this->addToLists($data['user_list']['list_ids'], $user_get['user_id'],$subscribetolist);
                if($dbloptin){
                    $emailsent=$this->sendConfirmationEmail((object)$user_get,true,$data['user_list']['list_ids']);
                }else{
                    $lists=$this->getUserLists($user_get['user_id'],$data['user_list']['list_ids']);
                    $this->sendAutoNl($user_get['user_id'],$lists);
                }

                if(!empty($message_success))    $this->notice($message_success);
                return true;
            }
            $mUserList=&WYSIJA::get('user_list','model');
            $userListsSub=$mUserList->get(array('list_id'),array('greater'=>array('sub_date'=>0),'equal'=>array('user_id'=>$user_get['user_id'])));
            $arrayListids=array();
            foreach($userListsSub as $userlistdetail){
                $arrayListids[]=$userlistdetail['list_id'];
            }

            $sendConfForIds=array();
            foreach($data['user_list']['list_ids'] as $listid){
                if(!in_array($listid, $arrayListids)){
                    $sendConfForIds[]=$listid;
                }
            }
            if(!empty($sendConfForIds)){
                $subscribetolist=$subscriber_status=0;
                if(isset($data['user']['status'])) $subscriber_status=$data['user']['status'];
                if(($dbloptin && $subscriber_status) || !$dbloptin) $subscribetolist=time();

                $this->addToLists($data['user_list']['list_ids'], $user_get['user_id'],$subscribetolist);
                if($dbloptin){

                    $emailsent=$this->sendConfirmationEmail((object)$user_get,true,$sendConfForIds);
                }
                if(!empty($message_success))    $this->notice($message_success);

                if(!$dbloptin &&(!empty($sendConfForIds))){
                    $lists=$this->getUserLists($user_get['user_id'],$data['user_list']['list_ids']);
                    $this->sendAutoNl($user_get['user_id'],$lists);
                }
            }else{

                $this->notice(__("Oops! You're already subscribed.",WYSIJA));
                return true;
            }
            return true;
        }

        $dataInsert=$data['user'];
        $dataInsert['ip']=$this->getIP();
        $model_user->reset();
        $user_id=$model_user->insert($dataInsert);

        if($user_id ){

            if(isset($data['form_id']) && (int)$data['form_id'] > 0) {

                $model_forms =& WYSIJA::get('forms', 'model');
                $form = $model_forms->getOne(array('form_id', 'subscribed'), array('form_id' => (int)$data['form_id']));
                if(isset($form['form_id']) && (int)$form['form_id'] === (int)$data['form_id']) {

                    $model_forms->update(array(
                        'subscribed' => $form['subscribed'] + 1
                    ), array(
                        'form_id' => (int)$form['form_id']
                    ));
                }
            }

            if(!empty($message_success))    $this->notice($message_success);
        }else{
            $this->notice(__('Subscriber has not been saved.',WYSIJA));
            if($backend) return false;
        }
        $subscribetolist=$subscriber_status=0;
        if(isset($data['user']['status'])) $subscriber_status=$data['user']['status'];
        if(($dbloptin && $subscriber_status) || !$dbloptin) $subscribetolist=time();

        $this->addToLists($data['user_list']['list_ids'], $user_id, $subscribetolist);

        $sendAutonl=false;
        if($subscriber_status>-1){
            if($dbloptin){
                if($subscriber_status==0){
                    $model_user->reset();
                    $model_user->getFormat=OBJECT;
                    $receiver=$model_user->getOne(false,array('email'=>trim($data['user']['email'])));
                    $this->sendConfirmationEmail($receiver,true,$data['user_list']['list_ids']);
                }else{

                    $sendAutonl=true;
                }
            }else{

                $sendAutonl=true;
                if($config->getValue('emails_notified') && $config->getValue('emails_notified_when_sub')){

                    if(!$backend) $this->_notify($data['user']['email'],true,$data['user_list']['list_ids']);
                }
            }
            if($sendAutonl){
                $lists=$this->getUserLists($user_id,$data['user_list']['list_ids']);
                $this->sendAutoNl($user_id,$lists,'subs-2-nl',$backend);
            }
        }
        return $user_id;
    }
    
    function sendAutoNl($user_id,$extraparams=false,$checkfortype='subs-2-nl',$addedByAdmin=false){

        $modelUser=&WYSIJA::get('user','model');
        $modelC=&WYSIJA::get('config','model');
        $dbloptin=(int)$modelC->getValue('confirm_dbleoptin');
        if(!$addedByAdmin && !$modelUser->exists(array('user_id'=>$user_id,'status'=>$dbloptin))) return false;
        $userListss=array();
        if($dbloptin && !$addedByAdmin){
            $modelUserList=&WYSIJA::get('user_list','model');
            $userListssres=$modelUserList->get(array('list_id'),array('equal'=>array('user_id'=>$user_id),'greater'=>array('sub_date'=>0)));
            foreach($userListssres as $res){
                $userListss[]=$res['list_id'];
            }
        }

        static $emails;
        if(empty($emails)){
            $modelEmail=&WYSIJA::get('email','model');
            $modelEmail->reset();
            $emails=$modelEmail->get(false,array('type'=>2,'status'=>array(1,3,99)));
            if(is_object($emails)){
                $emailarr=null;
                foreach($emails as $keyob => $valobj){
                    $emailarr[$keyob]=$valobj;
                }
                $emails=$emailarr;
            }
            if(is_array($emails) && isset($emails['body'])) $emails=array($emails);
        }

        foreach($emails as $key=> $email){
            if($email['params']['autonl']['event']!=$checkfortype) continue;
            switch($checkfortype){
                case 'subs-2-nl':
                    
                    foreach($extraparams as $details){
                        if(isset($email['params']['autonl']['subscribetolist']) && isset($details['list_id']) && $email['params']['autonl']['subscribetolist']==$details['list_id']){
                            $ok=true;
                            if(!$addedByAdmin && $dbloptin && !in_array($details['list_id'], $userListss)){

                                $ok=false;
                            }
                            if($ok) $this->insertAutoQueue($user_id,$email['email_id'], $email['params']['autonl']);
                        }
                    }
                    break;
                case 'new-user':
                    
                    $okInsert=false;
                    switch($email['params']['autonl']['roles']){
                        case 'any':
                            $okInsert=true;
                            break;
                        default:
                            foreach($extraparams->roles as $rolename){
                                if($rolename==$email['params']['autonl']['roles'])
                                    $okInsert=true;
                            }
                            break;
                    }
                    if($okInsert)
                        $this->insertAutoQueue($user_id,$email['email_id'], $email['params']['autonl']);
                    break;
            }
        }
    }
    
    function insertAutoQueue($user_id,$email_id,$email_params_autonl){
        $model_queue=&WYSIJA::get('queue','model');
        $queueData=array('priority'=>'-1','email_id'=>$email_id,'user_id'=>$user_id);
        $delay=$model_queue->calculate_delay($email_params_autonl);
        $queueData['send_at']=time()+$delay;
        if(!$model_queue->exists(array('email_id'=>$email_id,'user_id'=>$user_id))){

            if(isset($email_params_autonl['unique_send']) && $email_params_autonl['unique_send']){

                $modelEUS=&WYSIJA::get('email_user_stat','model');
                if(!$modelEUS->exists(array('email_id'=>$email_id,'user_id'=>$user_id)))
                        $model_queue->insert($queueData,true);
            }else{
                $model_queue->insert($queueData,true);
            }
        }

        if($delay==0){
            $queueH=&WYSIJA::get('queue','helper');
            $queueH->report=false;
            WYSIJA::log('insertAutoQueue queue process',array('email_id'=>$email_id,'user_id'=>$user_id),'queue_process');
            $queueH->process($email_id,$user_id);
        }
    }
    function unsubscribe($user_id,$auto=false){
        return $this->subscribe($user_id,false,$auto);
    }
    function sendConfirmationEmail($user_ids,$sendone=false,$listids=array()){
        if($sendone || is_object($user_ids)){
            
            $users=array($user_ids);
        }else{
            if(!is_array($user_ids)){
                $user_ids=(array)$user_ids;
            }
            
            $modelU=&WYSIJA::get('user','model');
            $modelU->getFormat=OBJECT_K;
            $users=$modelU->get(false,array('equal'=>array('user_id'=>$user_ids,'status'=>0)));
        }
        $config=&WYSIJA::get('config','model');
        $mailer=&WYSIJA::get('mailer','helper');

        if($listids){
            $mailer->listids=$listids;
            $mList=&WYSIJA::get('list','model');
            $listnamesarray=$mList->get(array('name'),array('list_id'=>$listids));
            $arrayNames=array();
            foreach($listnamesarray as $detailname){
                $arrayNames[]=$detailname['name'];
            }
            $mailer->listnames=$arrayNames;
        }

        $mEmail=&WYSIJA::get('email','model');
        $mEmail->getFormat=OBJECT;
        $emailConfirmationData=$mEmail->getOne(false,array('email_id'=>$config->getValue('confirm_email_id')));
        if(empty($emailConfirmationData)){

            $dataEmailCon=array('from_name'=>$config->getValue('from_name'),'from_email'=>$config->getValue('from_email'),
                    'replyto_name'=>$config->getValue('replyto_name'),'replyto_email'=>$config->getValue('replyto_email'),
                    'subject'=>$config->getValue('confirm_email_title'),'body'=>$config->getValue('confirm_email_body'),'type'=>'0','status'=>'99');
            $confemailid=$mEmail->insert($dataEmailCon);
            if($confemailid)    $config->save(array('confirm_email_id'=>$confemailid));
            $mEmail->reset();
            $mEmail->getFormat=OBJECT;
            $emailConfirmationData=$mEmail->getOne(false,array('email_id'=>$config->getValue('confirm_email_id')));
        }
        foreach($users as $userObj){
            $resultsend=$mailer->sendOne($emailConfirmationData,$userObj,true);
        }
        if(!$sendone)   $this->notice(sprintf(__('%1$d emails have been sent to unconfirmed subscribers.',WYSIJA),count($users)));
        else    return $resultsend;
        return true;
    }
    
    function get_unconfirmed_subscribers() {

        $model_user =& WYSIJA::get('user','model');
        $query = 'SELECT user_id
                  FROM ' . '[wysija]' . $model_user->table_name. '
                  WHERE  status = 0';
        $result = $model_user->query('get_res', $query);

        $unconfirmed_subscribers = array();
        foreach($result as $unconfirmed_user){
            $unconfirmed_subscribers[] = $unconfirmed_user['user_id'];
        }
        return $unconfirmed_subscribers;
    }

    function delete($user_ids,$sendone=false){
        if($sendone){
            
            $users=array($user_ids);
        }else{
            if(!is_array($user_ids)){
                $user_ids=(array)$user_ids;
            }
        }
        $modelEUR=&WYSIJA::get('user_history','model');
        $modelEUR->delete(array('user_id'=>$user_ids));
        $modelEUR=&WYSIJA::get('email_user_url','model');
        $modelEUR->delete(array('user_id'=>$user_ids));
        $modelEUS=&WYSIJA::get('email_user_stat','model');
        $modelEUS->delete(array('user_id'=>$user_ids));
        $modelUL=&WYSIJA::get('user_list','model');
        $modelUL->delete(array('user_id'=>$user_ids));
        $modelU=&WYSIJA::get('queue','model');
        $modelU->delete(array('user_id'=>$user_ids));
        $modelU=&WYSIJA::get('user','model');
        $emailsarr=$modelU->get(array('email'),array('user_id'=>$user_ids));
        $modelU->reset();
        $modelU->delete(array('user_id'=>$user_ids));
        $emails=array();
        foreach($emailsarr as $emobj)   $emails[]=$emobj['email'];
        if(count($user_ids)>1)  $this->notice(sprintf(__(' %1$s subscribers have been deleted.',WYSIJA),count($user_ids)));
        else    $this->notice(sprintf(__(' %1$s subscriber has been deleted.',WYSIJA),  implode(',', $emails)));
        return true;
    }

    function addToList($listid,$userids,$sub_date=0){
        $modelUser=&WYSIJA::get('user','model');
        $mConfig=&WYSIJA::get('config','model');
        $query='REPLACE INTO `[wysija]user_list` (`list_id`,`user_id`,`sub_date`)';
        $query.=' VALUES ';
        $sub_date=time();
        $total=count($userids);
        foreach($userids as $key=> $uid){
            $query.='('.(int)$listid.','.(int)$uid.','.$sub_date.")\n";
            if($total>($key+1)) $query.=',';
        }
        return $modelUser->query($query);
    }
    function addToLists($listids,$userid,$subscribedAT=0){
        $modelUser=&WYSIJA::get('user','model');
        $extraFieldsName=$extraFieldsVal='';
        if($subscribedAT){
            $extraFieldsName=',`sub_date`';
            $extraFieldsVal=','.$subscribedAT;
        }
        $query='INSERT IGNORE INTO `[wysija]user_list` (`list_id`,`user_id`'.$extraFieldsName.')';
        $query.=' VALUES ';
        $total=count($listids);
        foreach($listids as $key=> $listid){
            $query.='('.(int)$listid.','.(int)$userid.$extraFieldsVal.")\n";
            if($total>($key+1)) $query.=',';
        }
        return $modelUser->query($query);
    }

    function _notify($email,$subscribed=true,$listids=false){
        
        $modelUser=&WYSIJA::get('user_list','model');
        if($listids){
            $qry="Select B.name from `[wysija]list` as B where B.list_id IN ('".implode("','",$listids)."') and B.is_enabled>0";
        }else{
            $qry='Select B.name from `[wysija]user_list` as A join `[wysija]list` as B on A.list_id=B.list_id where A.user_id='.$this->uid.' and B.is_enabled>0';
        }
        $result=$modelUser->query('get_res',$qry);
        $listnames=array();
        foreach($result as $arra){
            $listnames[]=$arra['name'];
        }
        if($subscribed){
            $title=sprintf(__('New subscriber to %1$s',WYSIJA),implode(',',$listnames));
            $body=sprintf(__('Howdy,'."\n\n".'The subscriber %1$s has just subscribed to your list "%2$s".'."\n\n".'Cheers,'."\n\n".'The Wysija Plugin',WYSIJA),"<strong>".$email."</strong>","<strong>".implode(',',$listnames)."</strong>");
        }else{
            $title=sprintf(__('One less subscriber to %1$s',WYSIJA),implode(',',$listnames));
            $body=sprintf(__('Howdy,'."\n\n".'The subscriber : %1$s has just unsubscribed to your list "%2$s".'."\n\n".'Cheers,'."\n\n".'The Wysija Plugin',WYSIJA),"<strong>".$email."</strong>","<strong>".implode(',',$listnames)."</strong>");
        }
        $modelConf=&WYSIJA::get('config','model');
        $mailer=&WYSIJA::get('mailer','helper');
        $notifieds=$modelConf->getValue('emails_notified');

        $notifieds=explode(',',$notifieds);
        $mailer->testemail=true;
        $body=nl2br($body);
        foreach($notifieds as $receiver){
            $mailer->sendSimple(trim($receiver),$title,$body);
        }
    }
    function refreshUsers(){
        $modelU=&WYSIJA::get('user','model');
        $modelU->reset();
        $config=&WYSIJA::get('config','model');
        if($config->getValue('confirm_dbleoptin')){
            $modelU->setConditions(array('greater'=>array('status'=>0)));
        }else{
            $modelU->setConditions(array('greater'=>array('status'=>-1)));
        }
        $count=$modelU->count();
        $modelC=&WYSIJA::get('config','model');
        $modelC->save(array('total_subscribers'=>$count));
        return true;
    }
    
    function synchList($listid,$total=false){
        $model=&WYSIJA::get('list','model');
        $data=$model->getOne(false,array('list_id'=>(int)$listid,'is_enabled'=>'0'));
        if($data){
            if(strpos($data['namekey'], '-listimported-')!==false){
                
                $model->reset();

                $listdata=explode('-listimported-',$data['namekey']);
                $dataMainList=$model->getOne(false,array('namekey'=>$listdata[0],'is_enabled'=>'0'));
                $importHelper=&WYSIJA::get('import','helper');
                $dataPlugi=$importHelper->getPluginsInfo($listdata[0]);
                $listsids=array(
                    'wysija_list_main_id'=>$dataMainList['list_id'],
                    'wysija_list_id'=>$data['list_id'],
                    'plug_list_id'=>$listdata[1]
                );
                $importHelper->import($listdata[0],$dataPlugi,false,false,$listsids);
            }elseif($data['namekey']=='users'){
                

                $ismainsite=true;
                if (is_multisite()){
                    global $wpdb;
                    if($wpdb->prefix!=$wpdb->base_prefix){
                        $ismainsite=false;
                    }
                }
                $infosImport=array('name'=>'WordPress',
            'pk'=>'ID',
            'matches'=>array('ID'=>'wpuser_id','user_email'=>'email','display_name'=>'firstname'),
            'matchesvar'=>array('status'=>1));
                $importHelper=&WYSIJA::get('import','helper');
                $listsids=array(
                    'wysija_list_main_id'=>$data['list_id']
                );
                $importHelper->import($data['namekey'],$infosImport,false,$total,$listsids);
                $this->cleanWordpressUsersList();
            }elseif(strpos($data['namekey'], 'query-')!==false){
                

                $queryUid=apply_filters('wysija_synch_'.  str_replace('-', '_', $data['namekey']));
                $importHelper=&WYSIJA::get('import','helper');
                $queryUid=str_replace(array('[list_id]','[created_at]'), array($data['list_id'],time()), $queryUid);
                $importHelper->insertUserList(0,0,0,$queryUid);
            }else{
                
                $config=&WYSIJA::get('config','model');
                $importables=$config->getValue('pluginsImportableEgg');
                if(in_array($data['namekey'], array_keys($importables))){
                    $importHelper=&WYSIJA::get('import','helper');
                    $dataMainList=$model->getOne(false,array('namekey'=>$data['namekey'],'is_enabled'=>'0'));
                    $importHelper->import($data['namekey'],$importHelper->getPluginsInfo($data['namekey']),false,false,array('wysija_list_main_id'=>$dataMainList['list_id']));
                }
            }
            $this->notice(sprintf(__('List "%1$s" has been synchronised.',WYSIJA),$data['name']));
            return true;
        }else{
            $this->error(__('The list does not exists or cannot be synched.',WYSIJA),true);
            return false;
        }
    }
    function cleanWordpressUsersList(){

        $model=&WYSIJA::get('list','model');
        $query="UPDATE [wysija]user as A LEFT JOIN [wp]users as B on A.email=B.user_email SET A.wpuser_id = B.ID WHERE A.wpuser_id=0";
        $model->query($query);


        $model->reset();
        $model->query($query);
        $mConfig=&WYSIJA::get('config','model');
        $selectuserCreated="SELECT [wysija]user.user_id, ".$mConfig->getValue('importwp_list_id').", ".time()." FROM [wysija]user WHERE wpuser_id>0";
        $query="INSERT IGNORE INTO `[wysija]user_list` (`user_id`,`list_id`,`sub_date`) ".$selectuserCreated;
        $model->reset();
        $model->query($query);
        return true;
    }
}
