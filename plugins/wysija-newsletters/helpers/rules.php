<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_rules extends WYSIJA_help{
	var $tables = array('rules');
	var $pkey = 'ruleid';
	var $errors = array();
        var $defaultrules=array();       
        function WYSIJA_help_rules(){
            $forwardEmail="";
            $forwardEmail=count(str_split($forwardEmail)).':"'.$forwardEmail.'"';
            $this->defaultrules[]=array("order_display"=>2,"key"=>"action_required","name"=>__('Action Required',WYSIJA),
                                        "title"=>__('When you need to confirm you\'re a human being, forward to:',WYSIJA),
                                        "regex"=>'action *required|verif',
                                        "forward"=>1,
                                         "executed_on"=>array(
                                            "subject"=>1
                                            ), 
                                        "action_message"=>array(
                                            "delete"=>1
                                            ), 
                                        "action_user_min"=>0);
            
            $this->defaultrules[]=array("order_display"=>0,"key"=>"mailbox_full","name"=>__('Mailbox Full',WYSIJA),
                                        "title"=>__('When mailbox is full',WYSIJA),
                                        "regex"=>'((mailbox|mailfolder|storage|quota|space) *(is)? *(over)? *(exceeded|size|storage|allocation|full|quota|maxi))|((over|exceeded|full) *(mail|storage|quota))',
                                         "executed_on"=>array(
                                            "subject"=>1,
                                             "body"=>1
                                            ),
                                        "action_message"=>array(
                                            "delete"=>1,
                                            "save"=>1
                                            ),
                                        "action_user_min"=>3,
                                         "action_user_stats"=>1);
            $this->defaultrules[]=array("order_display"=>3,"key"=>"blocked_ip","name"=>__('Blocked IP',WYSIJA),
                                        "forward"=>1,
                                        "title"=>__('When you are flagged as a spammer forward the bounced message to',WYSIJA),
                                        "regex"=>'is *(currently)? *blocked *by|block *list|spam *detected|(unacceptable|banned|offensive|filtered|blocked) *(content|message|e-?mail)|administratively *denied',
                                         "executed_on"=>array(
                                            "body"=>1
                                            ), 
                                        "action_message"=>array(
                                            "delete"=>1
                                            ), 
                                        "action_user_min"=>0);
            $this->defaultrules[]=array("order_display"=>5,"behave"=>"mailbox_na","key"=>"message_delayed","name"=>__('Message delayed',WYSIJA),
                                        "title"=>__('When message is delayed',WYSIJA),
                                        "regex"=>'has.*been.*delayed|delayed *mail|temporary *failure',
                                         "executed_on"=>array(
                                            "subject"=>1,
                                             "body"=>1
                                            ), 
                                        "action_message"=>array(
                                            "delete"=>1,
                                            "save"=>1
                                            ), 
                                        "action_user_min"=>3,
                                         "action_user_stats"=>1);
            $this->defaultrules[]=array("order_display"=>1,"key"=>"mailbox_na","name"=>__('Mailbox not available',WYSIJA),
                                        "title"=>__('When mailbox is not available',WYSIJA),
                                        "regex"=>'(Invalid|no such|unknown|bad|des?activated|undelivered) *(mail|destination|recipient|user|address)|RecipNotFound|(user|mailbox|address|recipients?|host) *(disabled|failed|unknown|unavailable|not *found)',
                                         "executed_on"=>array(
                                            "subject"=>1,
                                             "body"=>1
                                            ),
                                        "action_message"=>array(
                                            "delete"=>1,
                                            "save"=>1
                                            ), 
                                        "action_user_min"=>0,
                                         "action_user_stats"=>1
                                            );
            $this->defaultrules[]=array("order_display"=>6,"behave"=>"mailbox_na","key"=>"failed_permanent","name"=>__('Failed Permanently',WYSIJA),
                                        "title"=>__('When failed permanently',WYSIJA),
                                        "regex"=>'failed *permanently|permanent *(fatal)? *(failure|error)|Unrouteable *address|not *accepting *(any)? *mail',
                                         "executed_on"=>array(
                                            "subject"=>1,
                                             "body"=>1
                                            ),
                                        "action_message"=>array(
                                            "delete"=>1,
                                            "save"=>1
                                            ), 
                                        "action_user_min"=>0,
                                         "action_user_stats"=>1
                                            );
            
            $this->defaultrules[]=array("order_display"=>4,"key"=>"nohandle","name"=>'Final Rule',
                                        "title"=>__('When the bounce is weird and we\'re not sure what to do, forward to:',WYSIJA),
                                        "forward"=>1,
                                        "regex"=>'.',
                                         "executed_on"=>array(
                                             "senderinfo"=>1,
                                             "subject"=>1
                                            ),
                                        "action_message"=>array(
                                            "delete"=>1
                                            ), 
                                        "action_user_min"=>0,
                                        "action_user_stats"=>1);
            $modelC=&WYSIJA::get("config","model");
            foreach($this->defaultrules as $ki =>$vi){

               if(isset($modelC->values['bounce_rule_'.$vi['key']])){
                   if($modelC->values['bounce_rule_'.$vi['key']]!=""){
                       $this->defaultrules[$ki]['action_user']=$modelC->values['bounce_rule_'.$vi['key']];
                   }
               }

               if(isset($modelC->values['bounce_rule_'.$vi['key'].'_forwardto'])){
                   if($modelC->values['bounce_rule_'.$vi['key'].'_forwardto']!=""){
                       $this->defaultrules[$ki]['action_message_forwardto']=$modelC->values['bounce_rule_'.$vi['key'].'_forwardto'];
                   }
               }
            }
        }
	function getRules($single=false,$display=false){
		$rules = $this->defaultrules;
                if($single){
                    foreach($rules as $id => $rule){
			if($rule['key']==$single) return $this->_prepareRule($rule,$id);
                    }
                }else{
                    if($display){
                        $newrules=array();
                        foreach($rules as $id => $rule){
                            if(isset($rule['order_display']))   $newrules[$rule['order_display']] = $this->_prepareRule($rule,$id);
                            else $newrules[rand(99,130)] = $this->_prepareRule($rule,$id);
                        }
                        $rules=$newrules;
                        ksort($rules);
                    }else{
                      foreach($rules as $id => $rule){
                            $rules[$id] = $this->_prepareRule($rule,$id);
                        }  
                    }
                    return $rules;
                }
	}
	function _prepareRule($rule,$id){
		$vals = array('executed_on','action_message','action_user','action_user_min','action_user_stats','action_user_block');
		foreach($vals as $oneVal){
                    if(!empty($rule[$oneVal])) {
                        $rule[$oneVal] = $rule[$oneVal];
                    }
		}
                $rule['id']=$id;
		return $rule;
	}
}
