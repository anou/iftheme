<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_list extends WYSIJA_model{

    var $pk='list_id';
    var $table_name='list';
    var $columns=array(
        'list_id'=>array('auto'=>true),
        'name' => array('req'=>true,'type'=>"text"),
        'namekey' => array('req'=>true,"type"=>"text"),
        'description' => array("type"=>"text"),
        'unsub_mail_id' => array("req"=>true,"type"=>"integer"),
        'welcome_mail_id' => array("req"=>true,"type"=>"integer"),
        'is_enabled' => array("req"=>true,"type"=>"boolean"),
        'is_public' => array("req"=>true,"type"=>"boolean"),
        'ordering' => array("req"=>true,"type"=>"integer"),
        'created_at' => array("req"=>true,"type"=>"integer"),
    );
    var $escapeFields=array('name','description');
    var $escapingOn=true;



    function WYSIJA_model_list(){
        $this->columns['name']['label']=__('Name',WYSIJA);
        $this->columns['description']['label']=__('Description',WYSIJA);
        $this->columns['is_enabled']['label']=__('Enabled',WYSIJA);
        $this->columns['ordering']['label']=__('Ordering',WYSIJA);
        $this->WYSIJA_model();
    }

    function beforeInsert() {
        if(!isset($this->values['namekey']) || !$this->values['namekey']){
            if(isset($this->values['name']))    $this->values['namekey']=sanitize_title($this->values['name']);
        }
        return true;
    }

    function getLists($id=false){

        if($id){
            $query='SELECT A.name, A.list_id, A.description, A.is_enabled, A.is_public, A.namekey
                FROM '.$this->getPrefix().'list as A
                LEFT JOIN '.$this->getPrefix().'email as B on A.welcome_mail_id=B.email_id
                WHERE A.list_id='.(int)$id;
                $result=$this->getResults($query);
                $this->escapeQuotesFromRes($result);
                return $result[0];
        }else{
            $query='SELECT A.name, A.list_id, A.created_at, A.is_enabled, A.is_public, A.namekey, 0 as subscribers, 0 as campaigns_sent
            FROM '.$this->getPrefix().'list as A';

            $this->countRows=$this->count($query);

            if(isset($this->_limitison) && $this->_limitison)  $query.=$this->setLimit();
            $listres=$this->getResults($query);

            $listids=array();
            foreach($listres as $res) $listids[]=$res['list_id'];

            //add the count of subscribers and unsubscribers
            $qry='SELECT count(distinct A.user_id) as nbsub,A.list_id FROM `'.$this->getPrefix().'user_list` as A WHERE list_id IN ('.implode(',',$listids).')  GROUP BY list_id';
            $qry1='SELECT count(distinct A.user_id) as total,B.status,A.list_id FROM `'.$this->getPrefix().'user_list` as A LEFT JOIN `'.$this->getPrefix().'user` as B on A.user_id=B.user_id WHERE list_id IN ('.implode(',',$listids).') and A.sub_date>0 and A.unsub_date=0 GROUP BY A.list_id,B.status';

            $total=$this->getResults($qry);
            $subscribed=$this->getResults($qry1);


            foreach($total as $tot){
                foreach($listres as $key=>$res){
                    if($tot['list_id']==$res['list_id']) $listres[$key]['totals']=$tot['nbsub'];
                }
            }

            //get the count of the subscribed people per list
            foreach($subscribed as $subscriber){
                foreach($listres as $key=>$res){
                    if($subscriber['list_id']==$res['list_id']){
                        if((int)$subscriber['status'] <0){
                            if(!isset($listres[$key]['unsubscribers'])) $listres[$key]['unsubscribers']=0;
                            $listres[$key]['unsubscribers']=$listres[$key]['unsubscribers']+$subscriber['total'];
                        }elseif((int)$subscriber['status'] >0){
                            if(!isset($listres[$key]['subscribers'])) $listres[$key]['subscribers']=0;
                            $listres[$key]['subscribers']=$listres[$key]['subscribers']+$subscriber['total'];
                        }else{
                            if(!isset($listres[$key]['unconfirmed'])) $listres[$key]['unconfirmed']=0;
                            $listres[$key]['unconfirmed']=$listres[$key]['unconfirmed']+$subscriber['total'];
                        }
                    }
                }
            }

            $model_config=&WYSIJA::get('config','model');
            foreach($listres as $key=>$res){
                if(!isset($listres[$key]['unconfirmed'])) $listres[$key]['unconfirmed']=0;
                if(!isset($listres[$key]['unsubscribers'])) $listres[$key]['unsubscribers']=0;
                if(!isset($listres[$key]['subscribers'])) $listres[$key]['subscribers']=0;
                if(!isset($listres[$key]['totals'])) $listres[$key]['totals']=0;
                //if the double optin is not activated then we need to make the sum of the subscribed and unconfirmed
                //this is a rare case but it happens
                if(!$model_config->getValue('confirm_dbleoptin')){
                    $listres[$key]['subscribers']+=$listres[$key]['unconfirmed'];
                }
            }




            $this->escapeQuotesFromRes($listres);
            return $listres;
        }

    }


}
