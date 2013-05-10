<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_help_licence extends WYSIJA_help{
    function WYSIJA_help_licence(){
        parent::WYSIJA_help();
    }
    function getDomainInfo(){
        $domainData = array();
        $url = admin_url('admin.php');
        $hToolbox =& WYSIJA::get('toolbox','helper');
        $domainData['domain_name'] = $hToolbox->_make_domain_name($url);
        if(is_multisite()) {
            $domainData['multisite_domain'] = $hToolbox->_make_domain_name(network_site_url());
        }
        $domainData['url'] = $url;
        $domainData['cron_url'] = site_url('wp-cron.php');
        return base64_encode(serialize($domainData));
    }
    function check($js = false){
        $domainData = $this->getDomainInfo();
        if($js === false) {
            WYSIJA::update_option('wysijey', $domainData);
        }
        $res['domain_name'] = $domainData;
        $res['nocontact'] = false;
        $mConfig=&WYSIJA::get('config','model');
        if($mConfig->getValue('nocurl')){
            $jsonResult=false;
        }else{
            $hHTTP =& WYSIJA::get('http','helper');
            $jsonResult = $hHTTP->request('http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=checkDomain&data='.$domainData);
        }

        if($jsonResult) {
            $decoded = json_decode($jsonResult, true);
            if(isset($decoded['result']) === false) {

                $res['nocontact'] = true;

                WYSIJA::update_option('wysicheck', true);
            } else {

                $res['result'] = $decoded['result'];
                if($decoded['result'] === true) {

                    $configData = array(
                        'premium_key' => base64_encode(get_option('home').time()),
                        'premium_val' => time()
                    );

                    $this->notice(__('Premium version is valid for your site.', WYSIJA));

                    WYSIJA::update_option('wysicheck', false);
                } else {

                    if(isset($decoded['error'])) {
                        $res['code'] = $decoded['code'];
                        switch($res['code']){
                            case 1: //Domain \'%1$s\' does not exist.
                                $errormsg=__('\'%1$s\' does not exist!',WYSIJA);
                                break;
                            case 2: //'Licence (id: %d) does not exist for domain "%s"
                                $errormsg=__('There\'s no license for "%1$s". If you\'re Premium, add this domain in your [link]account manager[/link].',WYSIJA);
                                break;
                            case 3: //Licence has expired
                                $errormsg=__('Your Premium licence has expired.',WYSIJA);
                                break;
                            case 4: //You need to manually add this domain to your [link]account manager[/link]
                                $errormsg=__('You can add this domain to your [link]account manager[/link].',WYSIJA);
                                break;
                            case 5: //Your licence does not allow more domains, please upgrade your licence in your [link]account manager[/link]
                                $errormsg=__('Your licence doesn\'t allow more domains. Upgrade from your [link]account manager[/link].',WYSIJA);
                                break;
                            default:
                                $errormsg=$decoded['error'];
                        }
                        $this->error(str_replace(
                                array('[link]','[/link]','%1$s'),
                                array('<a href="http://www.wysija.com/account/licences/" target="_blank">','</a>',$decoded['domain']),
                                $errormsg), true);
                    }

                    $configData = array('premium_key' => '', 'premium_val' => '');


                     WYSIJA::update_option('wysicheck', false);
                }

                $mConfig =& WYSIJA::get('config','model');
                $mConfig->save($configData);
            }
        }else{
            $res['nocontact']=true;
            WYSIJA::update_option('wysicheck',true);
        }
        return $res;
    }

    function dkim_config(){


        $hToolbox =& WYSIJA::get('toolbox','helper');
        $dkim_domain = $hToolbox->_make_domain_name(admin_url('admin.php'));
        $res1=$errorssl=false;
        if(function_exists('openssl_pkey_new')){
            while ($err = openssl_error_string());
            $res1=openssl_pkey_new(array('private_key_bits' => 1024));
            $errorssl=openssl_error_string();
        }
        if(function_exists('openssl_pkey_new') && $res1 && !$errorssl  && function_exists('openssl_pkey_get_details')){
            $rsaKey = array('private' => '', 'public' => '', 'error' => '');
            $res = openssl_pkey_new(array('private_key_bits' => 1024));
            if($res && !openssl_error_string()){

                $privkey = '';
                openssl_pkey_export($res, $privkey);

                $pubkey = openssl_pkey_get_details($res);

                $configData=array('dkim_domain'=>$dkim_domain,'dkim_privk'=>$privkey,'dkim_pubk'=>$pubkey['key'],'dkim_1024'=>1);
                $mConfig =& WYSIJA::get('config','model');
                $mConfig->save($configData);
            }
        }else{//fetch them through a request to wysija.com
            $domainData=$this->getDomainInfo();
            $hHTTP =& WYSIJA::get('http','helper');
            $jsonResult = $hHTTP->request('http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=checkDkimNew&data='.$domainData);

            if($jsonResult){
                $decoded=json_decode($jsonResult);
                $configData=array('dkim_domain'=>$dkim_domain,'dkim_privk'=>$decoded->dkim_privk,'dkim_pubk'=>$decoded->dkim_pubk->key,'dkim_1024'=>1);
                $mConfig =& WYSIJA::get('config','model');
                $mConfig->save($configData);
                WYSIJA::update_option('dkim_autosetup',false);
            }else{
                 WYSIJA::update_option('dkim_autosetup',true);
            }
        }
    }
}
