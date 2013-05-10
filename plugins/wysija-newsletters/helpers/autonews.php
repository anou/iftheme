<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_autonews  extends WYSIJA_object {
    function WYSIJA_help_autonews() {
    }
    function events($key=false,$get=true,$value_set=array()){
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
            $events[$key]=$value_set;
        }
    }
    function register($key_event,$event=array()){
        $this->events($key_event,false,$event);
    }
    function get($fieldKey){
         return $this->events($fieldKey);
    }
    
    function _deprecated_nextSend($email=false){
        if(!$email) return;
        $model_email=&WYSIJA::get('email','model');
        if(is_array($email)){
            $email_data=$model_email->getOne(false,array('email_id'=>$email['email_id']));
        }else{
            $email_data=$model_email->getOne(false,array('email_id'=>$email));
        }
        return $model_email->give_birth($email_data);
    }
    
    function getNextSend($email) {
        $schedule_at = -1;

        if((int)$email['type'] === 2 && isset($email['params']['autonl']['event']) && $email['params']['autonl']['event'] === 'new-articles') {
            $helper_toolbox =& WYSIJA::get('toolbox','helper');

            $now = time();






            if(!isset($email['params']['autonl']['nextSend']) || $now > $helper_toolbox->localtime_to_servertime($email['params']['autonl']['nextSend'])) {
                switch($email['params']['autonl']['when-article']) {
                    case 'immediate':
                        break;
                    case 'daily':

                        $schedule_at = strtotime($email['params']['autonl']['time']);

                        if($helper_toolbox->localtime_to_servertime($schedule_at) < $now) {

                            $schedule_at = strtotime('tomorrow '.$email['params']['autonl']['time']);
                        }
                        break;
                    case 'weekly':

                        $schedule_at = strtotime(ucfirst($email['params']['autonl']['dayname']).' '.$email['params']['autonl']['time']);

                        if($helper_toolbox->localtime_to_servertime($schedule_at) < $now) {

                            $schedule_at = strtotime('next '.ucfirst($email['params']['autonl']['dayname']).' '.$email['params']['autonl']['time']);
                        }
                        break;
                    case 'monthly':
                        $time_current_day=date('d',$now);
                        $time_current_month=date('m',$now);
                        $time_current_year=date('y',$now);

                        if($time_current_day > $email['params']['autonl']['daynumber']) {
                            if((int)$time_current_month === 12) {

                               $time_current_month=1;
                               $time_current_year++;
                            }else{

                                $time_current_month++;
                            }
                        }
                        $schedule_at=strtotime($time_current_month.'/'.$email['params']['autonl']['daynumber'].'/'.$time_current_year.' '.$email['params']['autonl']['time']);
                        break;
                    case 'monthlyevery': // monthly every X Day of the week
                        $current_day = date('d', $now);
                        $current_month = date('m', $now);
                        $current_year = date('y', $now);


                        $schedule_at = strtotime(
                            sprintf('%02d/01/%02d %d %s %s',
                            $current_month,
                            $current_year,
                            $email['params']['autonl']['dayevery'],
                            ucfirst($email['params']['autonl']['dayname']),
                            $email['params']['autonl']['time']
                        ));
                        if($helper_toolbox->localtime_to_servertime($schedule_at) < $now) {

                            $first_day_of_next_month = $this->get_first_day_of_month($schedule_at, 1);

                            $schedule_at = strtotime(
                                sprintf('%02d/01/%02d %d %s %s',
                                    date('m', $first_day_of_next_month),
                                    date('y', $first_day_of_next_month),
                                    $email['params']['autonl']['dayevery'],
                                    ucfirst($email['params']['autonl']['dayname']),
                                    $email['params']['autonl']['time']
                                )
                            );
                        }
                        break;
                }
            }
        }
        return $schedule_at;
    }
    function get_first_day_of_month($time_stamp, $months_to_add = 0) {

        $date = getdate($time_stamp); // Covert to Array

        $date['mon'] = $date['mon'] + (int)$months_to_add;

        $date['mday'] = 1;

        return mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);
    }
    
    function getNextDay($first_day_of_month,$day_name,$which_number,$time_now){
        $name_first_day = strtolower(date('l', $first_day_of_month));
        if($name_first_day == strtolower($day_name)) $which_number--;
        for($i=0; $i < $which_number;$i++){
            $first_day_of_month = strtotime('next '.ucfirst($day_name), $first_day_of_month);
        }
        return $first_day_of_month;
    }

    
    function checkPostNotif(){

        $current_check = (int)get_option('wysija_check_pn');

        if(microtime(true) < ($current_check+60)){
            WYSIJA::log('already_running_checkPN', $current_check, 'post_notif');
            return false;
        }

        $current_check=microtime(true);
        WYSIJA::update_option('wysija_check_pn',$current_check);

        WYSIJA::log('check_post_notif_starts', $current_check , 'post_notif');
        $model_email=&WYSIJA::get('email','model');
        $model_email->reset();
        $all_emails=$model_email->get(false,array('type'=>'2','status'=>array('1','3','99')));
        if($all_emails){
            $helper_toolbox=&WYSIJA::get('toolbox','helper');
            foreach($all_emails as $email){

                if($email['params']['autonl']['event']=='new-articles' && $email['params']['autonl']['when-article']!='immediate'){



                    if(!isset($email['params']['autonl']['nextSend'])){
                        WYSIJA::log('check_post_notif_next_send_not_set', $current_check , 'post_notif');
                    }else {

                        $time_now_server=time();
                        if($time_now_server > $helper_toolbox->localtime_to_servertime($email['params']['autonl']['nextSend'])){
                            $how_late=$time_now_server-$helper_toolbox->localtime_to_servertime($email['params']['autonl']['nextSend']);


                            if(!$this->cancel_late_post_notification($email,$how_late)){
                                 WYSIJA::log('check_post_notif_before_give_birth', $current_check, 'post_notif');

                                $model_email->give_birth($email);
                            }
                        }
                    }
                }
            }
        }
    }
    
    function cancel_late_post_notification($email,$how_late){
        $cancel_it=false;
        switch($email['params']['autonl']['when-article']) {
            case 'daily':

                if($how_late>(2*3600)){
                    $cancel_it=true;
                }
                break;
            case 'weekly':

                if($how_late>(12*3600)){
                    $cancel_it=true;
                }
                break;
            case 'monthly':

                if($how_late>(24*3600)){
                    $cancel_it=true;
                }
                break;
            case 'monthlyevery':

                if($how_late>(24*3600)){
                    $cancel_it=true;
                }
                break;
        }

        if($cancel_it){
            $late_send=$email['params']['autonl']['nextSend'];
            WYSIJA::log('cancel_late_post_notification_late_send', $late_send, 'post_notif');
            $next_send=$this->getNextSend($email);
            $email['params']['autonl']['nextSend']=$next_send;
            $email['params']['autonl']['late_send']=$late_send;
            $model_email=&WYSIJA::get('email','model');
            $model_email->reset();
            $model_email->update(array('params'=>$email['params']), array('email_id' => $email['email_id']));
            return true;
        }
        return false;
    }
    
    function checkScheduled(){
        $model_email=&WYSIJA::get('email','model');
        $helper_toolbox=&WYSIJA::get('toolbox','helper');
        $model_email->reset();

        $all_emails=$model_email->get(false,array('type'=>'1','status'=>'4'));
        if($all_emails){
            foreach($all_emails as $email){

                if(isset($email['params']['schedule']['isscheduled'])){
                    $schedule_date=$email['params']['schedule']['day'].' '.$email['params']['schedule']['time'];
                    $unix_scheduled_time=strtotime($schedule_date);


                    if($helper_toolbox->localtime_to_servertime($unix_scheduled_time) < time()){
                        $model_email->reset();
                        $model_email->send_activate($email);
                    }
                }
            }
        }
    }
}
