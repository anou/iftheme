<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_cron extends WYSIJA_object{
    var $report=false;
    function WYSIJA_help_cron(){
    }
    function run() {
        @ini_set('max_execution_time',0);
        $modelC=&WYSIJA::get('config','model');
        $running=false;
        if(!$modelC->getValue('cron_manual')){
            exit;
        }

        $report=$process=false;
        if(isset($_REQUEST['process']) && $_REQUEST['process']){
            $process=$_REQUEST['process'];
        }elseif(!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SHELL']) && isset($_SERVER['argv'][2]) && $_SERVER['argv'][2]){
            $process=$_SERVER['argv'][2];
        }
        if(isset($_REQUEST['report']) && $_REQUEST['report']){
            $this->report=$_REQUEST['report'];
        }elseif(!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SHELL']) && isset($_SERVER['argv'][3]) && $_SERVER['argv'][3]){
            $this->report=$_SERVER['argv'][3];
        }
        if($process){
            
            
            if(isset($_REQUEST[WYSIJA_CRON]) || ( isset($_SERVER['argv'][1]) && $_SERVER['argv'][1]==WYSIJA_CRON )) echo '';
            else exit;
            $cron_schedules=get_option('wysija_schedules');
            $processes=array();
            if(strpos($process, ',')!==false){
                $processes=explode(',', $process);
            }else $processes[]=$process;
            foreach($processes as $scheduleprocess){
                if($scheduleprocess!='all'){
                    $this->check_process($cron_schedules,$scheduleprocess);
                }else{
                    $allProcesses=array('queue','bounce','daily','weekly','monthly');
                    foreach($allProcesses as $processNK){
                        $this->check_process($cron_schedules,$processNK);
                    }
                    if($this->report) echo 'processed : All'.'<br/>';
                    if(!isset($_REQUEST['silent'])) echo "It works. Wysija's cron is trigger happy.";
                    exit;
                }
            }
        }
        if(!isset($_REQUEST['silent'])) echo "It works. Wysija's cron is trigger happy.";
        exit;
    }
    function check_process($cron_schedules,$processNK){
        $toolboxH=&WYSIJA::get('toolbox','helper');
        $timepassed=$timeleft=0;
        if($cron_schedules[$processNK]['running']){
            $timepassed=time()-$cron_schedules[$processNK]['running'];
            $timepassed=$toolboxH->duration($timepassed,true,2);
        }else{
            $timeleft=$cron_schedules[$processNK]['next_schedule']-time();
            $timeleft=$toolboxH->duration($timeleft,true,2);
        }

        if($cron_schedules[$processNK]['next_schedule']<time() && !$cron_schedules[$processNK]['running']){
            if($this->report) echo 'exec process '.$processNK.'<br/>';
            $this->wysija_exec_process($processNK);
        }else{
           if($this->report){
               if($timepassed) $texttime=' running since : '.$timepassed;
               else  $texttime=' next run : '.$timeleft;
               echo 'skip process <strong>'.$processNK.'</strong>'.$texttime.'<br/>';
           }
        }
    }
    function wysija_exec_process($process='queue'){
        $scheduled_times=WYSIJA::get_cron_schedule($process);
        if(isset($scheduled_times['running']) && $scheduled_times['running'] && $scheduled_times['running']+900>time()){
            if($this->report)   echo 'already running : '.$process.'<br/>';
            return;
        }

        WYSIJA::set_cron_schedule($process,0,time());

        switch($process){
            case 'queue':
                WYSIJA::croned_queue($process);
                if(defined('WYSIJANLP')){
                    $hPremium =& WYSIJA::get('premium', 'helper', false, WYSIJANLP);
                    $hPremium->splitVersion_croned_queue_process();
                }
                break;
            case 'bounce':
                if(defined('WYSIJANLP')){
                    $hPremium =& WYSIJA::get('premium', 'helper', false, WYSIJANLP);
                    $hPremium->croned_bounce();
                }
                break;
            case 'daily':
                WYSIJA::croned_daily();
                break;
            case 'weekly':
                if(defined('WYSIJANLP')){
                    $hPremium =& WYSIJA::get('premium', 'helper', false, WYSIJANLP);
                    $hPremium->croned_weekly();
                }
                break;
            case 'monthly':
                WYSIJA::croned_monthly();
                break;
        }

        WYSIJA::set_cron_schedule($process);
        if($this->report) echo 'processed : '.$process.'<br/>';
    }
}
