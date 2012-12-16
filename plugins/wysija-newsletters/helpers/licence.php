<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_help_licence extends WYSIJA_help{
    function WYSIJA_help_licence(){
        parent::WYSIJA_help();
    }
    function getDomainInfo(){
        $data=array();
        $url=admin_url('admin.php');
        $helperToolbox=&WYSIJA::get("toolbox","helper");
        $data['domain_name']=$helperToolbox->_make_domain_name($url);
        $data['url']=$url;
        $data[uniqid()]=uniqid('WYSIJA');
        $data=base64_encode(serialize($data));
        return $data;
    }
    function check($js=false){
        $data=$this->getDomainInfo();
        if(!$js) {
            WYSIJA::update_option('wysijey',$data);
        }
        $res['domain_name']=$data;
        $res['nocontact']=false;
        $httpHelp=&WYSIJA::get("http","helper");
        $jsonResult = $httpHelp->request('http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=checkDomain&data='.$data);
        if($jsonResult){
            


            if($jsonResult){
                $decoded=json_decode($jsonResult);
                if(isset($decoded->msgs))   $this->error($decoded->msgs);
                if($decoded->result){
                    $res['result']=true;

                    $dataconf=array('premium_key'=>base64_encode(get_option('home').time()),'premium_val'=>time());
                    $this->notice(__("Premium version is valid for your site.",WYSIJA));
                    WYSIJA::update_option("wysicheck",false);
                }else{
                    $dataconf=array('premium_key'=>"",'premium_val'=>"");
                    $this->error(str_replace(array("[link]","[/link]"),array('<a href="http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=orders&action=checkout&wysijadomain='.$data.'" target="_blank">','</a>'),
                        __("Premium licence does not exist for your site. Purchase from our website [link]here[/link].",WYSIJA)),1);
                }
                $modelConf=&WYSIJA::get("config","model");
                $modelConf->save($dataconf);
            }else{
                $res['nocontact']=true;
                 WYSIJA::update_option("wysicheck",true);

            }
        }else{
            $res['nocontact']=true;
            WYSIJA::update_option("wysicheck",true);
        }

        return $res;
    }

    function dkim_config(){


        $helperToolbox=&WYSIJA::get("toolbox","helper");
        $dkim_domain=$helperToolbox->_make_domain_name(admin_url('admin.php'));
        $res1=$errorssl=false;
        if(function_exists('openssl_pkey_new')){
            while ($err = openssl_error_string());
            $res1=openssl_pkey_new(array('private_key_bits' => 512));
            $errorssl=openssl_error_string();
        }
        if(function_exists('openssl_pkey_new') && $res1 && !$errorssl  && function_exists('openssl_pkey_get_details')){
            $rsaKey = array('private' => '', 'public' => '', 'error' => '');
            $res = openssl_pkey_new(array('private_key_bits' => 512));
            if($res && !openssl_error_string()){

                $privkey = '';
                openssl_pkey_export($res, $privkey);

                $pubkey = openssl_pkey_get_details($res);

                $dataconf=array('dkim_domain'=>$dkim_domain,'dkim_privk'=>$privkey,'dkim_pubk'=>$pubkey['key']);
                $modelConf=&WYSIJA::get('config','model');
                $modelConf->save($dataconf);
            }
        }else{//fetch them through a request to wysija.com
            $data=$this->getDomainInfo();
            $httpHelp=&WYSIJA::get("http","helper");
            $jsonResult = $httpHelp->request('http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=checkDkim&data='.$data);
            
            if($jsonResult){
                $decoded=json_decode($jsonResult);
                $dataconf=array('dkim_domain'=>$dkim_domain,'dkim_privk'=>$decoded->dkim_privk,'dkim_pubk'=>$decoded->dkim_pubk->key);
                $modelConf=&WYSIJA::get('config','model');
                $modelConf->save($dataconf);
                WYSIJA::update_option('dkim_autosetup',false);
            }else{
                 WYSIJA::update_option('dkim_autosetup',true);
            }
        }
    }
}
