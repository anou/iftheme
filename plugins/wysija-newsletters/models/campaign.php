<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_campaign extends WYSIJA_model{
    
    var $pk="campaign_id";
    var $table_name="campaign";
    var $columns=array(
        'campaign_id'=>array("type"=>"integer"),
        'name'=>array("req"=>true),
        'description' => array(),
    );
    var $escapeFields=array('name','description');
    var $escapingOn=true;
    
    
    
    function WYSIJA_model_campaign(){
        $this->WYSIJA_model();
    }
    
    function getDetails($emailid=false){
        if(!$emailid) $emailid=$_REQUEST['id'];

        $condemail=array("email_id"=>$emailid);
        
        $data=array();
        $modelEmail=&WYSIJA::get("email","model");
        $data['email']=$modelEmail->getOne(false,$condemail);


        $data['campaign']=$this->getOne(false,array("campaign_id"=>$data['email']['campaign_id']));
        $modelCL=&WYSIJA::get("campaign_list","model");
        $data['campaign']['lists']['full']=$modelCL->get(array("list_id","filter"),array("campaign_id"=>$data['email']['campaign_id']));
        
        foreach($data['campaign']['lists']['full'] as $list){
            $data['campaign']['lists']['ids'][]=$list['list_id'];
        }
        
        return $data;
    }
}
