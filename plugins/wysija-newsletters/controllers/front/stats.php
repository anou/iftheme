<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_control_front_stats extends WYSIJA_control_front{
    var $model=''   ;
    var $view='';

    function WYSIJA_control_front_stats(){
        parent::WYSIJA_control_front();
    }


    function rm_url_param($param_rm=array(), $query='')
    {
        if(!$query) return $query;
        $queries=explode('?',$query);
        $params=array();
        parse_str($queries[1], $params);

        foreach($param_rm as $param_rmuniq)
            unset($params[$param_rmuniq]);
        $newquery = $queries[0];

        if($params){
            $newquery.='?';
            $i=0;
            foreach($params as $k => $v){
                if($i>0)    $newquery .= '&';
                $newquery.=$k.'='.$v;
                $i++;

            }
        }else return $newquery;

        return substr($newquery,1);
    }

    /**
     * count the click statistic and redirect to the right url
     * @return boolean
     */
    function analyse(){
        if(isset($_REQUEST['debug'])){
            if(version_compare(phpversion(), '5.4')>= 0){
                error_reporting(E_ALL ^ E_STRICT);

            }else{
                error_reporting(E_ALL);
            }
            ini_set('display_errors', '1');
        }
        if(isset($_REQUEST['email_id']) && isset($_REQUEST['user_id'])){
            $email_id=(int)$_REQUEST['email_id'];
            $user_id=(int)$_REQUEST['user_id'];

            //debug message
            if(isset($_REQUEST['debug']))   echo '<h2>isset email_id and user_id</h2>';

            $requesturlencoded=false;
            if(isset($_REQUEST['urlencoded'])){
                $requesturlencoded=$_REQUEST['urlencoded'];
            }elseif(isset($_REQUEST['urlpassed'])){
                $requesturlencoded=$_REQUEST['urlpassed'];
            }

            if($requesturlencoded){
                //clicked stats
                if(isset($_REQUEST['no64'])){
                    $recordedUrl=$decodedUrl=$requesturlencoded;
                }else{
                    $recordedUrl=$decodedUrl=base64_decode($requesturlencoded);
                }
                if(strpos($recordedUrl, 'utm_source')!==false){
                    $recordedUrl=$this->rm_url_param(array('utm_source','utm_campaign','utm_medium'),$recordedUrl);
                }

                //debug message
                if(isset($_REQUEST['debug']))   echo '<h2>isset urlencoded '.$decodedUrl.'</h2>';

                if($email_id && !isset($_REQUEST['demo'])){ //if not email_id that means it is an email preview
                    //look for url entry and insert if not exists
                    $modelUrl=&WYSIJA::get('url','model');

                    $urlObj=$modelUrl->getOne(false,array('url'=>$recordedUrl));

                    if(!$urlObj){
                        //we need to insert in url
                        $modelUrl->insert(array('url'=>$recordedUrl));
                        $urlObj=$modelUrl->getOne(false,array('url'=>$recordedUrl));
                    }
                    $modelUrl=null;

                    //look for email_user_url entry and insert if not exists
                    $modelEmailUserUrl=WYSIJA::get('email_user_url','model');
                    $dataEmailUserUrl=array('email_id'=>$email_id,'user_id'=>$user_id,'url_id'=>$urlObj['url_id']);
                    $emailUserUrlObj=$modelEmailUserUrl->getOne(false,$dataEmailUserUrl);
                    $uniqueclick=false;
                    if(!$emailUserUrlObj){
                        //we need to insert in email_user_url
                        $modelEmailUserUrl->reset();
                        $modelEmailUserUrl->insert($dataEmailUserUrl);
                        $uniqueclick=true;
                    }

                    //increment stats counter on email_user_url clicked
                    $modelEmailUserUrl=WYSIJA::get('email_user_url','model');
                    $modelEmailUserUrl->update(array('clicked_at'=>time(),'number_clicked'=>'[increment]'),$dataEmailUserUrl);
                    $modelEmailUserUrl=null;

                    //look for url_mail entry and insert if not exists
                    $modelUrlMail=&WYSIJA::get('url_mail','model');
                    $dataUrlEmail=array('email_id'=>$email_id,'url_id'=>$urlObj['url_id']);
                    $urlMailObj=$modelUrlMail->getOne(false,$dataUrlEmail);
                    if(!$urlMailObj){
                        //we need to insert in url_mail
                        $modelUrlMail->reset();
                        $modelUrlMail->insert($dataUrlEmail);
                    }

                    $dataUpdate=array('total_clicked'=>'[increment]');
                    if(!$uniqueclick)    $dataUpdate['unique_clicked']='[increment]';
                    //increment stats counter on url_mail clicked
                    $modelUrlMail->update($dataUpdate,$dataUrlEmail);
                    $modelUrlMail=null;

                    $statusEmailUserStat=2;
                    if(in_array($recordedUrl,array('[unsubscribe_link]','[subscriptions_link]','[view_in_browser_link]'))){
                        $this->subscriberClass = &WYSIJA::get('user','model');
                        $this->subscriberClass->getFormat=OBJECT;

                        //check if the security hash is passed to insure privacy
                        $receiver=$link=false;
                        if(isset($_REQUEST['hash'])){
                            if($_REQUEST['hash']==md5(AUTH_KEY.$recordedUrl.$user_id)){
                                $receiver = $this->subscriberClass->getOne(array('user_id'=>$user_id));
                            }else{
                                die('Security check failure.');
                            }
                        }else{
                            //link is not valid anymore
                            //propose to resend the newsletter with good links ?
                            $link=$this->subscriberClass->getResendLink($user_id,$email_id);
                        }


                        switch($recordedUrl){
                            case '[unsubscribe_link]':
                                //we need to make sure that this link belongs to that user
                                if($receiver){
                                    $link=$this->subscriberClass->getUnsubLink($receiver,true);
                                    $statusEmailUserStat=3;
                                }
                                break;
                            case '[subscriptions_link]':
                                if($receiver){
                                    $link=$this->subscriberClass->getEditsubLink($receiver,true);
                                }
                                break;
                            case '[view_in_browser_link]':
                                $modelEmail=&WYSIJA::get('email','model');
                                $dataEmail=$modelEmail->getOne(false,array('email_id'=>$email_id));
                                $emailH=&WYSIJA::get('email','helper');
                                $link=$emailH->getVIB($dataEmail);
                                break;
                        }

                        //if the subscriber still exists in the DB we will have a link
                        if($link){
                            $decodedUrl=$link;
                        }else{
                            //the subscriber doesn't appear in the DB we can redirect to the web version
                            $decodedUrl=$this->_get_browser_link($email_id);

                            return $this->redirect($decodedUrl);
                        }

                    }else{

                        if(strpos($decodedUrl, 'http://' )=== false && strpos($decodedUrl, 'https://' )=== false) $decodedUrl='http://'.$decodedUrl;
                        //check that there is no broken unsubscribe link such as http://[unsubscribe_link]
                        if(strpos($decodedUrl, '[unsubscribe_link]')!==false){
                            $this->subscriberClass = &WYSIJA::get('user','model');
                            $this->subscriberClass->getFormat=OBJECT;
                            $receiver = $this->subscriberClass->getOne($user_id);
                            $decodedUrl=$this->subscriberClass->getUnsubLink($receiver,true);
                        }

                        if(strpos($decodedUrl, '[view_in_browser_link]')!==false){
                            $link=$this->_get_browser_link($email_id);
                            $decodedUrl=$link;
                        }

                    }

                    //debug information
                    if(isset($_REQUEST['debug']))   echo '<h2>isset decoded url '.$decodedUrl.'</h2>';

                    $modelEmailUS=&WYSIJA::get('email_user_stat','model');
                    $exists=$modelEmailUS->getOne(false,array('equal'=>array('email_id'=>$email_id,'user_id'=>$user_id), 'less'=>array('status'=>$statusEmailUserStat)));
                    $dataupdate=array('status'=>$statusEmailUserStat);
                    if($exists && isset($exists['opened_at']) && !(int)$exists['opened_at']){
                        $dataupdate['opened_at']=time();
                    }

                    $modelEmailUS->reset();
                    $modelEmailUS->colCheck=false;
                    $modelEmailUS->update($dataupdate,array('equal'=>array('email_id'=>$email_id,'user_id'=>$user_id), 'less'=>array('status'=>$statusEmailUserStat)));


                }else{
                   if(in_array($recordedUrl,array('[unsubscribe_link]','[subscriptions_link]','[view_in_browser_link]'))){
                        $modelU=&WYSIJA::get('user','model');
                        $modelU->getFormat=OBJECT;
                        $objUser=$modelU->getOne(false,array('wpuser_id'=>get_current_user_id()));
                        switch($recordedUrl){
                            case '[unsubscribe_link]':
                                $link=$modelU->getConfirmLink($objUser,'unsubscribe',false,true).'&demo=1';

                                break;
                            case '[subscriptions_link]':
                                $link=$modelU->getConfirmLink($objUser,'subscriptions',false,true).'&demo=1';
                                //$link=$this->subscriberClass->getEditsubLink($receiver,true);
                                break;
                            case 'view_in_browser_link':
                            case '[view_in_browser_link]':
                                if(!$email_id) $email_id=$_REQUEST['id'];

                                $link=$this->_get_browser_link($email_id);
                                break;
                        }
                        $decodedUrl=$link;

                    }else{
                        if(strpos($decodedUrl, 'http://' )=== false && strpos($decodedUrl, 'https://' )=== false) $decodedUrl='http://'.$decodedUrl;
                    }
                    if(isset($_REQUEST['debug']))   {
                        echo '<h2>not email_id </h2>';
                    }
                }

                //sometimes this will be a life saver :)
                $decodedUrl = str_replace('&amp;','&',$decodedUrl);
                if(isset($_REQUEST['debug']))   {
                    echo '<h2>final decoded url '.$decodedUrl.'</h2>';
                    exit;
                }
                $this->redirect($decodedUrl);


            }else{
                //opened stat */
                //$modelEmail=&WYSIJA::get("email","model");
                //$modelEmail->update(array('number_opened'=>"[increment]"),array("email_id"=>$email_id));

                $modelEmailUS=&WYSIJA::get('email_user_stat','model');
                $modelEmailUS->reset();
                $modelEmailUS->update(
                        array('status'=>1,'opened_at'=>time()),
                        array('email_id'=>$email_id,'user_id'=>$user_id,'status'=>0));

		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		if(empty($picture)) $picture = WYSIJA_DIR_IMG.'statpicture.png';
		$handle = fopen($picture, 'r');

		if(!$handle) exit;
		header('Content-type: image/png');
		$contents = fread($handle, filesize($picture));
		fclose($handle);
		echo $contents;
                exit;
            }


        }

        return true;
    }

    function _get_browser_link($email_id){
        $paramsurl=array(
            'wysija-page'=>1,
            'controller'=>'email',
            'action'=>'view',
            'email_id'=>$email_id,
            'user_id'=>0
            );
        $config=&WYSIJA::get('config','model');
        return WYSIJA::get_permalink($config->getValue('confirm_email_link'),$paramsurl);
    }

}
