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
            add_action('wysijaSubscribeTo', array($this, 'hook_subscriber_to_list'), 1);
        }

        do_action('wysijaSubscribeTo',$this->values);
    }

    function updateSubscription($subid,$lists){
		/*$result = true;
		$time = time();
		$listHelper = acymailing_get('helper.list');
		$listHelper->sendNotif = $this->sendNotif;
		$listHelper->sendConf = $this->sendConf;
		$listHelper->survey = $this->survey;
		foreach($lists as $status => $listids){
			if(empty($listids)) continue;
			JArrayHelper::toInteger($listids);
			//-1 is unsubscribe
			if($status == '-1') $column = 'unsubdate';
			else $column = 'subdate';
			$query = 'UPDATE '.acymailing_table('listsub').' SET `status` = '.intval($status).','.$column.'='.$time.' WHERE subid = '.intval($subid).' AND listid IN ('.implode(',',$listids).')';
			$this->database->setQuery($query);
			$result = $this->database->query() && $result;
			if($status == 1){
				$listHelper->subscribe($subid,$listids);
			}elseif($status == -1){
				$listHelper->unsubscribe($subid,$listids);
			}
		}
		return $result;*/
	}
	function removeSubscription($subid,$listids){
		/*JArrayHelper::toInteger($listids);
		$query = 'DELETE FROM '.acymailing_table('listsub').' WHERE subid = '.intval($subid).' AND listid IN ('.implode(',',$listids).')';
		$this->database->setQuery($query);
		$this->database->query();
		$listHelper = acymailing_get('helper.list');
		$listHelper->sendNotif = $this->sendNotif;
		$listHelper->unsubscribe($subid,$listids);
		return true;*/
	}
	function addSubscription($subid,$lists){
		/*$app =& JFactory::getApplication();
		$my = JFactory::getUser();
		$result = true;
		$time = time();
		$subid = intval($subid);
		$listHelper = acymailing_get('helper.list');
		foreach($lists as $status => $listids){
			$status = intval($status);
			JArrayHelper::toInteger($listids);
			$this->database->setQuery('SELECT `listid`,`access_sub` FROM '.acymailing_table('list').' WHERE `listid` IN ('.implode(',',$listids).') AND `type` = \'list\'');
			$allResults = $this->database->loadObjectList('listid');
			$listids = array_keys($allResults);
			//-1 is unsubscribe
			if($status == '-1') $column = 'unsubdate';
			else $column = 'subdate';
			$values = array();
			foreach($listids as $listid){
				if(empty($listid)) continue;
				if($status > 0 && acymailing_level(3)){
					if(!$app->isAdmin() && $this->checkAccess && $allResults[$listid]->access_sub != 'all'){
						if(!acymailing_isAllowed($allResults[$listid]->access_sub,$this->gid)) continue;
					}
				}
				$values[] = intval($listid).','.$subid.','.$status.','.$time;
			}
			if(empty($values)) continue;
			$query = 'INSERT INTO '.acymailing_table('listsub').' (listid,subid,`status`,'.$column.') VALUES ('.implode('),(',$values).')';
			$this->database->setQuery($query);
			$result = $this->database->query() && $result;
			if($status == 1){
				$listHelper->subscribe($subid,$listids);
			}
		}
		return $result;*/
	}

}
