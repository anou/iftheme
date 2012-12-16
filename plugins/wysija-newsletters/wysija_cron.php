<?php
@ini_set('max_execution_time',0);
//get the param from where you want
$report=$process=false;
if(isset($_REQUEST['process']) && $_REQUEST['process']){
    $process=$_REQUEST['process'];
}elseif(!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SHELL']) && isset($_SERVER['argv'][2]) && $_SERVER['argv'][2]){
    $process=$_SERVER['argv'][2];
}

if(isset($_REQUEST['report']) && $_REQUEST['report']){
    $report=$_REQUEST['report'];
}elseif(!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SHELL']) && isset($_SERVER['argv'][3]) && $_SERVER['argv'][3]){
    $report=$_SERVER['argv'][3];
}

if($process){

    /*include the needed parts of wp plus wysija*/
    $plugin_path = dirname(__FILE__);
    $wp_root = dirname(dirname(dirname($plugin_path)));

    if ( !defined('WP_MEMORY_LIMIT') ) define('WP_MEMORY_LIMIT', '64M');

    require_once($wp_root.DIRECTORY_SEPARATOR.'wp-config.php');
    require_once($wp_root.DIRECTORY_SEPARATOR.'wp-includes'.DIRECTORY_SEPARATOR.'wp-db.php');
    require_once($plugin_path.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'base.php');

    if(isset($_REQUEST[WYSIJA_CRON]) || ( isset($_SERVER['argv'][1]) && $_SERVER['argv'][1]==WYSIJA_CRON )) echo '';
    else exit;
    $cron_schedules=get_option('wysija_schedules');

    $processes=array();
    if(strpos($process, ',')!==false){
        $processes=explode(',', $process);
    }else $processes[]=$process;

    foreach($processes as $scheduleprocess){
        if($scheduleprocess!='all'){
            if($cron_schedules[$scheduleprocess]['next_schedule']<time() && !$cron_schedules[$scheduleprocess]['running']){
                if($report) echo 'exec process '.$scheduleprocess.'<br/>';
                wysija_exec_process($scheduleprocess);
            }else{
               if($report) echo 'skip process '.$scheduleprocess.'<br/>';
            }
        }else{
            wysija_exec_process('queue');
            wysija_exec_process('bounce');
            wysija_exec_process('daily');
            wysija_exec_process('weekly');
            wysija_exec_process('monthly');
            if($report) echo 'processed : All'.'<br/>';
            exit;
        }
    }
}

function wysija_exec_process($process='queue'){
    $scheduled_times=WYSIJA::get_cron_schedule($process);
    if(isset($scheduled_times['running']) && $scheduled_times['running'] && $scheduled_times['running']+900>time()){
        echo 'already running : '.$process.'<br/>';
        return;
    }
    //set schedule as running
    WYSIJA::set_cron_schedule($process,0,time());
    //execute schedule
    switch($process){
        case 'queue':
            WYSIJA::croned_queue($process);
            $hPremium =& WYSIJA::get('premium', 'helper', false, WYSIJANLP);
            if(is_object($hPremium)) $hPremium->splitVersion_croned_queue_process();
            break;
        case 'bounce':
            $hPremium =& WYSIJA::get('premium', 'helper', false, WYSIJANLP);
            if(is_object($hPremium)) $hPremium->croned_bounce();
            break;
        case 'daily':
            WYSIJA::croned_daily();
            break;
        case 'weekly':
            if(is_object($hPremium)) $hPremium->croned_weekly();
            break;
        case 'monthly':
            WYSIJA::croned_monthly();
            break;
    }
    //set next_schedule
    WYSIJA::set_cron_schedule($process);
    if($report) echo 'processed : '.$process.'<br/>';
}





