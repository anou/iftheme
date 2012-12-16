<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_autonews  extends WYSIJA_object {
    function WYSIJA_help_autonews() {
    }
    function events($key=false,$get=true,$valueSet=array()){
        static $events=array();
        if($get){
            if(!$key){
                return $events;
            }else{
                if(isset($events[$key])) return $events[$key];
                return false; 
            }
        }else{
            if(isset($events[$key])) return false;
            $events[$key]=$valueSet;
        }
    }
    function register($keyevent,$event=array()){
        $this->events($keyevent,false,$event);
    }
    function get($fieldKey){
         return $this->events($fieldKey);
    }
    
    function nextSend($email=false){
        if(!$email) return;
        $modelEmail=&WYSIJA::get('email','model');
        if(is_array($email)){
            $emailArr=$modelEmail->getOne(false,array('email_id'=>$email['email_id']));
        }else{
            $emailArr=$modelEmail->getOne(false,array('email_id'=>$email));
        }
        return $modelEmail->giveBirth($emailArr);
    }
    
    function getNextSend($email){
        $timeNext=-1;

        if((int)$email['type']==2 && isset($email["params"]['autonl']['event']) && $email["params"]['autonl']['event']=='new-articles'){
            
            $toolboxH=&WYSIJA::get('toolbox','helper');
            if(!isset($email['params']['autonl']['nextSend']) || time()>$toolboxH->offset_time($email['params']['autonl']['nextSend'])){
                $timenow=time();
                switch($email["params"]['autonl']['when-article']){
                    case 'immediate':
                        break;
                    case 'daily':
                        $timeNext=date('m/d/y',$timenow);
                        $timeNext.=' '.$email["params"]['autonl']['time'];
                        $timeNext=strtotime($timeNext); 
                        $toolboxH=&WYSIJA::get('toolbox','helper');

                        if($timenow>$toolboxH->offset_time($timeNext)){
                            $timeNext+=3600*24;
                        }

                        break;
                    case 'weekly':
                        $timeNext=strtotime("next ".ucfirst($email["params"]['autonl']['dayname']),$timenow);
                        $timeNext=date('m/d/y',$timeNext);
                        $timeNext.=' '.$email["params"]['autonl']['time'];
                        $timeNext=strtotime($timeNext); 
                        $toolboxH=&WYSIJA::get('toolbox','helper');

                        if($timenow>$toolboxH->offset_time($timeNext)){
                            $timeNext+=3600*24*7;
                        }
                        break;
                    case 'monthly':
                        $timeCurrentDay=date('d',$timenow);
                        $timeCurrentMonth=date('m',$timenow);
                        $timeCurrentYear=date('y',$timenow);

                        if($timeCurrentDay>$email["params"]['autonl']['daynumber']){
                            if((int)$timeCurrentMonth==12){

                               $timeCurrentMonth=1;
                               $timeCurrentYear++;
                            }else{

                                $timeCurrentMonth++;
                            }
                        }
                        $timeNext=strtotime($timeCurrentMonth.'/'.$email["params"]['autonl']['daynumber'].'/'.$timeCurrentYear.' '.$email["params"]['autonl']['time']);
                        break;
                    case 'monthlyevery': //1st tuesday of the month
                        $timeCurrentDay=date('d',$timenow);
                        $timeCurrentMonth=date('m',$timenow);
                        $timeCurrentYear=date('y',$timenow);

                        $timeFirstDayofMonth=strtotime($timeCurrentMonth.'/1/'.$timeCurrentYear.' '.$email["params"]['autonl']['time']);
                        $timeNext=$this->getNextDay($timeFirstDayofMonth,$email["params"]['autonl']['dayname'],$email["params"]['autonl']['dayevery'],$timenow);
                        if($toolboxH->offset_time($timeNext)<$timenow){

                            $timeFirstDayofNextMonth=strtotime(($timeCurrentMonth+1).'/1/'.$timeCurrentYear.' '.$email["params"]['autonl']['time']);
                            $timeNext=$this->getNextDay($timeFirstDayofNextMonth,$email["params"]['autonl']['dayname'],$email["params"]['autonl']['dayevery'],$timenow);
                        }
                        break;             
                }
            }
        }
        return $timeNext;
    }//endfct
    
    function getNextDay($firstDayOfMonth,$dayname,$whichNumber,$timenow){
        $nameFirstday=  strtolower(date('l',$firstDayOfMonth));
        if($nameFirstday==strtolower($dayname)) $whichNumber--;
        for($i=0;$i<$whichNumber;$i++){
            $firstDayOfMonth=strtotime('next '.ucfirst($dayname),$firstDayOfMonth);
        }
        return $firstDayOfMonth;
    }
    
    
    function checkPostNotif(){
        $modelEmail=&WYSIJA::get('email','model');
        $modelEmail->reset();
        $allEmails=$modelEmail->get(false,array('type'=>'2','status'=>array('1','3','99')));
        if($allEmails){
            $toolboxH=&WYSIJA::get('toolbox','helper');
            
            foreach($allEmails as $email){
                
                if($email['params']['autonl']['event']=='new-articles' && $email['params']['autonl']['when-article']!='immediate'){
                    
                    if(time()>$toolboxH->offset_time($email['params']['autonl']['nextSend']))
                        $modelEmail->giveBirth($email);
                }
            }
        }
    }
    
    
    function checkScheduled(){
        $modelEmail=&WYSIJA::get('email','model');
        $modelEmail->reset();
        $allEmails=$modelEmail->get(false,array('type'=>'1','status'=>'4'));
        if($allEmails){
            $toolboxH=&WYSIJA::get('toolbox','helper');
            foreach($allEmails as $email){
                
                if(isset($email['params']['schedule']['isscheduled'])){
                    $scheduledate=$email['params']['schedule']['day'].' '.$email['params']['schedule']['time'];
                    $unixscheduledtime=strtotime($scheduledate);
                    
                    
                    if($toolboxH->offset_time($unixscheduledtime)<time()){
                        $modelEmail->reset();
                        $modelEmail->send($email,true);
                    }
                }
            }
        }
    }
}//endclass
