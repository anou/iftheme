<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_toolbox extends WYSIJA_object{
    function WYSIJA_help_toolbox(){
    }

    
    function temp($content,$key='temp',$format='.tmp'){
        $helperF=&WYSIJA::get('file','helper');
        $tempDir=$helperF->makeDir();

        $filename=$key.'-'.time().$format;
        $handle=fopen($tempDir.$filename, 'w');
        fwrite($handle, $content);
        fclose($handle);
        return array('path'=>$tempDir.$filename,'name'=>$filename, 'url'=>$this->url($filename,'temp'));
    }
    
    function url($filename,$folder='temp'){
        $upload_dir = wp_upload_dir();
        if(file_exists($upload_dir['basedir'].DS.'wysija')){
            $url=$upload_dir['baseurl'].'/wysija/'.$folder.'/'.$filename;
        }else{
            $url=$upload_dir['baseurl'].'/'.$filename;
        }
        return $url;
    }
    
    function send($path){
        
        if(file_exists($path)){
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename="export_wysija.csv"');
            readfile($path);
            exit();
        }else $this->error(__('Yikes! We couldn\'t export. Make sure that your folder permissions for /wp-content/uploads/wysija/temp is set to 755.',WYSIJA),true);
    }
    
    function clear(){
        $foldersToclear=array('import','temp');
        $filenameRemoval=array('import-','export-');
        $deleted=array();
        $helperF=&WYSIJA::get('file','helper');
        foreach($foldersToclear as $folder){
            $path=$helperF->getUploadDir($folder);
            
            $files = scandir($path);
            foreach($files as $filename){
                if(!in_array($filename, array('.','..','.DS_Store','Thumbs.db'))){
                    if(preg_match('/('.implode($filenameRemoval,'|').')[0-9]*\.csv/',$filename,$match)){
                       $deleted[]=$path.$filename;
                    }
                }
            }
        }
        foreach($deleted as $filename){
            if(file_exists($filename)){
                unlink($filename);
            }
        }
    }
    function closetags($html) {
        #put all opened tags into an array
        preg_match_all('#<([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];   #put all closed tags into an array
        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        # all tags are closed
        if(count($closedtags) === $len_opened) {
            return $html;
        }
        $openedtags = array_reverse($openedtags);
        # close tags
        for($i=0; $i < $len_opened; $i++) {
            if(!in_array($openedtags[$i], $closedtags)){
                $html .= '</'.$openedtags[$i].'>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return $html;
    }
    
    function excerpt($text,$num_words=8,$more=' ...'){
        $words_array = preg_split('/[\r\t ]+/', $text, $num_words + 1, PREG_SPLIT_NO_EMPTY);
        if(count($words_array) > $num_words) {
                array_pop($words_array);
                $text = implode(' ', $words_array);
                $text = $text . $more;
        } else {
            $text = implode( ' ', $words_array );
        }
        return $this->closetags($text);
    }
    
    function _make_domain_name($url=false){
        if(!$url) $url=admin_url('admin.php');
        $domain_name=str_replace(array('https://','http://','www.'),'',strtolower($url));

        $domain_name=explode('/',$domain_name);
        return $domain_name[0];
    }
    
    function duration($s,$durationin=false,$level=1){
        $t=time();
        if($durationin){
            $e=$t+$s;
            $s=$t;

            $timestamp = $e - $s;
        }else{
            $timestamp = $t - $s;
        }

        $years=floor($timestamp/(60*60*24*365));$timestamp%=60*60*24*365;
        $weeks=floor($timestamp/(60*60*24*7));$timestamp%=60*60*24*7;
        $days=floor($timestamp/(60*60*24));$timestamp%=60*60*24;
        $hrs=floor($timestamp/(60*60));$timestamp%=60*60;
        $mins=floor($timestamp/60);
        if($timestamp>60)$secs=$timestamp%60;
        else $secs=$timestamp;


        $str='';
        $mylevel=0;
        if ($mylevel<$level && $years >= 1) { $str.= sprintf(_n( '%1$s year', '%1$s years', $years, WYSIJA ),$years).' ';$mylevel++; }
        if ($mylevel<$level && $weeks >= 1) { $str.= sprintf(_n( '%1$s week', '%1$s weeks', $weeks, WYSIJA ),$weeks).' ';$mylevel++; }
        if ($mylevel<$level && $days >= 1) { $str.=sprintf(_n( '%1$s day', '%1$s days', $days, WYSIJA ),$days).' ';$mylevel++; }
        if ($mylevel<$level && $hrs >= 1) { $str.=sprintf(_n( '%1$s hour', '%1$s hours', $hrs, WYSIJA ),$hrs).' ';$mylevel++; }
        if ($mylevel<$level && $mins >= 1) { $str.=sprintf(_n( '%1$s minute', '%1$s minutes', $mins, WYSIJA ),$mins).' ';$mylevel++; }
        if ($mylevel<$level && $secs >= 1) { $str.=sprintf(_n( '%1$s second', '%1$s seconds', $secs, WYSIJA ),$secs).' ';$mylevel++; }
        return $str;
    }
    
    function localtime($time,$justtime=false){
        if($justtime) $time=strtotime($time);
        return date(get_option('time_format'),$time);
    }
    
    function time_tzed($val=false){
        return gmdate( 'Y-m-d H:i:s', $this->servertime_to_localtime($val) );
    }
    
    function servertime_to_localtime($unixTime=false){

        $current_server_time = time();
        $gmt_time = $current_server_time - date('Z');

        $current_local_time = $gmt_time + ( get_option( 'gmt_offset' ) * 3600 );
        if(!$unixTime) return $current_local_time;
        else{

            $time_difference = $current_local_time - $current_server_time;

            return $unixTime + $time_difference;
        }
    }
    
    function localtime_to_servertime($server_time){

        $current_server_time = time();
        $gmt_time = $current_server_time - date('Z');

        $current_local_time = $gmt_time + ( get_option( 'gmt_offset' ) * 3600 );

        $time_difference = $current_local_time - $current_server_time;

        return $server_time - $time_difference;
    }
    
    function getday($day=false){
        $days=array('monday'=>__('Monday',WYSIJA),
                    'tuesday'=>__('Tuesday',WYSIJA),
                    'wednesday'=>__('Wednesday',WYSIJA),
                    'thursday'=>__('Thursday',WYSIJA),
                    'friday'=>__('Friday',WYSIJA),
                    'saturday'=>__('Saturday',WYSIJA),
                    'sunday'=>__('Sunday',WYSIJA));
        if(!$day || !isset($days[$day])) return $days;
        else return $days[$day];
    }
    
    function getweeksnumber($week=false){
        $weeks=array(
                    '1'=>__('1st',WYSIJA),
                    '2'=>__('2nd',WYSIJA),
                    '3'=>__('3rd',WYSIJA),
                    '4'=>__('Last',WYSIJA),
                    );
        if(!$week || !isset($weeks[$week])) return $weeks;
        else return $weeks[$week];
    }
    
    function getdaynumber($day=false){
        $daynumbers=array();

        for($i = 1;$i < 29;$i++) {
            switch($i){
                case 1:
                    $number=__('1st',WYSIJA);
                    break;
                case 2:
                    $number=__('2nd',WYSIJA);
                    break;
                case 3:
                    $number=__('3rd',WYSIJA);
                    break;
                default:
                    $number=sprintf(__('%1$sth',WYSIJA),$i);
            }
            $daynumbers[$i] = $number;
        }
        if(!$day || !isset($daynumbers[$day])) return $daynumbers;
        else return $daynumbers[$day];
    }
}
