<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_http extends WYSIJA_object{

    function WYSIJA_help_http(){
    }
    
    function request($url){
        if(ini_get('allow_url_fopen'))  return file_get_contents($url);
        elseif(function_exists('curl_init')) {
            $this->opts = array(
                CURLOPT_HEADER => FALSE,
                CURLOPT_RETURNTRANSFER => TRUE
            );
            $result=$this->curl_get($url);
            return $result['cr'];
        }elseif(function_exists('http_get')){
            return http_parse_message(http_get($url))->body;
        }else{
            $this->error(__('Your server doesn\'t support remote exchanges.',WYSIJA));
            $this->error(__('Contact your administrator to modify that, it should be configurable.',WYSIJA));
            $this->error('<strong>CURL library</strong> DISABLED');
            $this->error('<strong>allow_url_fopen</strong> DISABLED');
            $this->error('<strong>PECL pecl_http >= 0.1.0</strong> DISABLED');
            return false;
        }
    }
    function request_timeout($url,$timeout='3'){
        if(function_exists('curl_init')) {
            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 0 );
            curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
            $result = curl_exec( $ch );
            return   curl_close( $ch );
        }elseif(ini_get('allow_url_fopen')){
            ini_set('default_socket_timeout',(int)$timeout);
            return @file_get_contents($url);
        }elseif(function_exists('http_get')){
            return @http_get($url, array('timeout'=>(int)$timeout));
        }else{
            $this->error(__('Your server doesn\'t support remote exchanges.',WYSIJA));
            $this->error(__('Contact your administrator to modify that, it should be configurable.',WYSIJA));
            $this->error('<strong>CURL library</strong> DISABLED');
            $this->error('<strong>allow_url_fopen</strong> DISABLED');
            $this->error('<strong>PECL pecl_http >= 0.1.0</strong> DISABLED');
            return false;
        }
    }
    function curl_request($ch,$opt){
        # assign global options array
        $opts = $this->opts;
        # assign user's options
        foreach($opt as $k=>$v){$opts[$k] = $v;}
        curl_setopt_array($ch,$opts);
        curl_exec($ch);
        $r['code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $r['cr'] = curl_exec($ch);
        $r['ce'] = curl_errno($ch);
        curl_close($ch);
        return $r;
    }
    function curl_get($url='',$opt=array()){
        # create cURL resource
        $ch = curl_init($url);
        return $this->curl_request($ch,$opt);
    }
   
}
