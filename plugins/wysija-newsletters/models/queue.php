<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_queue extends WYSIJA_model{

    var $pk=array("email_id","user_id");
    var $table_name="queue";
    var $columns=array(
        'email_id'=>array("type"=>"integer"),
        'user_id'=>array("type"=>"integer"),
        'send_at' => array("req"=>true,"type"=>"integer"),
        'priority' => array("type"=>"integer"),
        'number_try' => array("type"=>"integer")
    );



    function WYSIJA_model_queue(){
        $this->WYSIJA_model();
    }

    function queueCampaign($campaignobj){
        if(!$campaignobj) {
            $this->error("Missing campaign id in queueCampaign()");
            return false;
        }

        /* get campaign information */
        $modelCamp=&WYSIJA::get("campaign","model");
        $data=$modelCamp->getDetails($campaignobj);
        $modelC=&WYSIJA::get("config","model");
        if($modelC->getValue("confirm_dbleoptin")) $statusmin=0;
        else $statusmin=-1;


        $query="INSERT IGNORE INTO [wysija]queue (`email_id` ,`user_id`,`send_at`) ";
        $query.="SELECT ".$data['email']['email_id'].", A.user_id,".time()."
            FROM [wysija]user_list as A
                JOIN [wysija]user as B on A.user_id=B.user_id
                    WHERE B.status>".$statusmin." AND A.list_id IN (".implode(",",$data['campaign']['lists']['ids']).") AND A.sub_date>".$statusmin." ";

        $this->query($query);

        return true;
    }


    function ACYdelete($filters){
            $query = 'DELETE a.* FROM [wysija]queue as a';
            if(!empty($filters)){
                    $query .= ' JOIN [wysija]user as b on a.user_id = b.user_id';
                    $query .= ' JOIN [wysija]email as c on a.email_id = c.email_id';
                    $query .= ' WHERE ('.implode(') AND (',$filters).')';
            }
            //dbg($filters);
            $this->query($query);
            $nbRecords = $this->getAffectedRows();
            if(empty($filters)){
                $this->query('TRUNCATE TABLE [wysija]queue');
            }
            return $nbRecords;
    }

    function nbQueue($mailid){
            $mailid = (int) $mailid;
            return $this->query('get_res','SELECT count(user_id) FROM [wysija]queue WHERE email_id = '.$mailid.' GROUP BY email_id');
    }

    function queue($mailid,$time,$onlyNew = false){
            $mailid = intval($mailid);
            if(empty($mailid)) return false;

            $classLists =&WYSIJA::get("campaign_list","model");
            $lists = $classLists->getReceivers($mailid,false);
            if(empty($lists)) return 0;
            $config = &WYSIJA::get("config","model");
            $querySelect = 'SELECT DISTINCT a.user_id,'.$mailid.','.$time.','.(int) $config->getValue('priority_newsletter',3);
            $querySelect .= ' FROM [wysija]user_list as a ';
            $querySelect .= ' JOIN [wysija]user as b ON a.user_id = b.user_id ';
            $querySelect .= 'WHERE a.list_id IN ('.implode(',',array_keys($lists)).') AND a.status = 1 ';

            if($config->getValue('confirm_dbleoptin')){ $querySelect .= 'AND b.status = 1 '; }
            $query = 'INSERT IGNORE INTO [wysija]queue (user_id,email_id,send_at,priority) '.$querySelect;

            if(!$this->query($query)){
                    //acymailing_display($this->database->getErrorMsg(),'error');
                $this->error($this->getErrorMsg());
            }
            $totalinserted = $this->getAffectedRows();
            if($onlyNew){
                    $query='DELETE b.* FROM `[wysija]email_user_stat` as a JOIN `[wysija]queue` as b on a.user_id = b.user_id WHERE a.email_id = '.$mailid;
                    $this->query($query);
                    $totalinserted = $totalinserted - $this->getAffectedRows();
            }
            //JPluginHelper::importPlugin('acymailing');
    /*$dispatcher = &JDispatcher::getInstance();
    $dispatcher->trigger('onAcySendNewsletter',array($mailid));*/
            return $totalinserted;
    }

    function getDelayed($mailid=0){
        if(!$mailid) return array();
        $query = 'SELECT c.*,a.* FROM [wysija]queue as a';
        $query .= ' JOIN [wysija]email as b on a.`email_id` = b.`email_id` ';
        $query .= ' JOIN [wysija]user as c on a.`user_id` = c.`user_id` ';
        $query .= ' WHERE  b.`status` IN (1,3,99)';
        if(!empty($mailid)) $query .= ' AND a.`email_id` = '.$mailid;
        $query .= ' ORDER BY a.`priority` ASC, a.`send_at` ASC, a.`user_id` ASC';

        $results=$this->query("get_res",$query);


        return $results;
    }

    function getReady($limit,$mailid = 0,$user_id=false){
        $query = 'SELECT c.*,a.* FROM [wysija]queue as a';
        $query .= ' JOIN [wysija]email as b on a.`email_id` = b.`email_id` ';
        $query .= ' JOIN [wysija]user as c on a.`user_id` = c.`user_id` ';
        $query .= ' WHERE a.`send_at` <= '.time().' AND b.`status` IN (1,3,99)';
        if(!empty($mailid)) $query .= ' AND a.`email_id` = '.$mailid;
        if($user_id) $query .= ' AND a.`user_id` = '.$user_id;
        $query .= ' ORDER BY a.`priority` ASC, a.`send_at` ASC, a.`user_id` ASC';
        if(!empty($limit)) $query .= ' LIMIT '.$limit;

        $results=$this->query("get_res",$query,OBJECT_K);
        //$results = $this->database->loadObjectList();
        if($results === null){
            $this->query('REPAIR TABLE [wysija]queue, [wysija]user, [wysija]email');
        }

        if(!empty($results)){
                $firstElementQueued = reset($results);
                //$this->database->setQuery();
                $this->query('UPDATE [wysija]queue SET send_at = send_at + 1 WHERE email_id = '.$firstElementQueued->email_id.' AND user_id = '.$firstElementQueued->user_id.' LIMIT 1');
        }
        return $results;
    }

    function queueStatus($mailid,$all = false){
            $query = 'SELECT a.email_id, count(a.user_id) as nbsub,min(a.send_at) as send_at, b.subject FROM [wysija]queue as a';
            $query .= ' JOIN [wysija]email as b on a.email_id = b.email_id';
            $query .= ' WHERE b.published > 0';
            if(!$all){
                    $query .= ' AND a.send_at < '.time();
                    if(!empty($mailid)) $query .= ' AND a.email_id = '.$mailid;
            }
            $query .= ' GROUP BY a.email_id';
            //$this->database->setQuery($query);
            $queueStatus=$this->query("get_res",$query,OBJECT_K);
            //$queueStatus = $this->database->loadObjectList('email_id');
            return $queueStatus;
    }

}
