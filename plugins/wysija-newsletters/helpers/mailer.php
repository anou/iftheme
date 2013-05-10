<?php

defined('WYSIJA') or die('Restricted access');
require_once(WYSIJA_DIR.'inc'.DS.'phpmailer'.DS.'class.phpmailer.php');
class WYSIJA_help_mailer extends acymailingPHPMailer {
	var $report = true;
	var $checkConfirmField = true;
	var $checkEnabled = true;
	var $checkAccept = true;
	var $parameters = array();

	var $errorNumber = 0;
	var $reportMessage = '';
	var $autoAddUser = false;
	var $errorNewTry = array(1,6);
	var $forceTemplate = 0;
        var $testemail=false;
        var $isMailjet=false;
        var $isElasticRest=false;
        var $isSendGridRest=false;
        var $DKIM_selector   = 'wys';
        var $listids=false;
        var $listnames=false;
        
	function WYSIJA_help_mailer($extension='',$config=false, $multisiteTest=false) {
            $this->subscriberClass = &WYSIJA::get('user','model');
            $this->subscriberClass->getFormat=OBJECT;
            $this->encodingHelper = &WYSIJA::get('encoding','helper');
            $this->config =&WYSIJA::get('config','model');

            $optionsMsOverride=array();
            if(!empty($config)){
                $optionsMsOverride=array('sending_method','sendmail_path','smtp_rest','smtp_host','smtp_port','smtp_secure','smtp_auth','smtp_login','smtp_password');

               foreach($config as $key => $val){
                   if($multisiteTest && in_array($key, $optionsMsOverride) && isset($config['ms_'.$key])){
                       $this->config->values[$key]=$config['ms_'.$key];
                   }else{
                       $this->config->values[$key]=$config[$key];
                   }
               }
            }
            $is_multisite=is_multisite();


            if($is_multisite && $this->config->getValue('ms_sending_config')=='one-for-all' && ($this->config->getValue('sending_method')=='network' ||$multisiteTest)){

                $optionsMsOverride=array('from_email','sendmail_path','smtp_rest','smtp_host','smtp_port','smtp_secure','smtp_auth','smtp_login','smtp_password');
                foreach($optionsMsOverride as $key){
                    if(isset($this->config->values['ms_'.$key]))    $this->config->values[$key]=$this->config->values['ms_'.$key];
                }
            }
            $this->setFrom($this->config->getValue('from_email'),$this->config->getValue('from_name'));
            $this->Sender 	= $this->cleanText($this->config->getValue('bounce_email'));
            if(empty($this->Sender)) $this->Sender = '';
            switch ( $this->config->getValue('sending_method') )
            {
                case 'gmail' :
                case 'smtp' :


                    $this->Host = $this->config->getValue('smtp_host');
                    if(in_array(trim($this->Host), array('smtp.elasticemail.com','smtp25.elasticemail.com'))){

                        include_once (WYSIJA_INC. 'phpmailer' . DS . 'class.elasticemail.php');
                        $this->Mailer = 'elasticemail';
                        $this->elasticEmail = new acymailingElasticemail();
                        $this->elasticEmail->Username = trim($this->config->getValue('smtp_login'));
                        $this->elasticEmail->Password = trim($this->config->getValue('smtp_password'));
                        $this->isElasticRest=true;
                    }elseif(in_array(trim($this->Host), array('smtp.sendgrid.net')) && $this->config->getValue('smtp_rest')){

                        include_once (WYSIJA_INC. 'phpmailer' . DS . 'class.sendgrid.php');
                        $this->Mailer = 'sendgrid';
                        $this->sendGrid = new acymailingSendgrid();
                        $this->sendGrid->Username = trim($this->config->getValue('smtp_login'));
                        $this->sendGrid->Password = trim($this->config->getValue('smtp_password'));
                        $this->isSendGridRest=true;
                    }else{
                        $this->IsSMTP();
                        if(strpos($this->Host, 'mailjet.com')!==false){
                            $this->isMailjet=true;
                        }
                        $port = $this->config->getValue('smtp_port');
                        if(empty($port) && $this->config->getValue('smtp_secure') == 'ssl') $port = 465;
                        if(!empty($port)) $this->Host.= ':'.$port;
                        $this->SMTPAuth = (bool) $this->config->getValue('smtp_auth');
                        $this->Username = trim($this->config->getValue('smtp_login'));
                        $this->Password = trim($this->config->getValue('smtp_password'));
                        $this->SMTPSecure = trim((string)$this->config->getValue('smtp_secure'));
                        if(empty($this->Sender)) $this->Sender = strpos($this->Username,'@') ? $this->Username : $this->config->getValue('from_email');
                        
                    }
                        break;
                case 'site':
                    if($this->config->getValue('sending_emails_site_method')=='phpmail'){
                        $this->IsMail();
                    }else{
                       $this->IsSendmail();
                        $this->SendMail = trim($this->config->getValue('sendmail_path'));
                        if(empty($this->SendMail)) $this->SendMail = '/usr/sbin/sendmail';
                    }
                    break;
                case 'qmail' :
                        $this->IsQmail();
                        break;
                default :
                        $this->IsMail();
                        break;
            }//endswitch
            $this->PluginDir =  dirname(__FILE__).DS;
            $this->CharSet = strtolower($this->config->getValue('advanced_charset'));
            if(empty($this->CharSet)) $this->CharSet = 'utf-8';
            $this->clearAll();
            $this->Encoding = '8bit';

            $this->WordWrap = 150;
            if($this->config->getValue('dkim_active') && $this->config->getValue('dkim_pubk') && !$this->isElasticRest && !$this->isSendGridRest){
               $this->DKIM_domain = $this->config->getValue('dkim_domain');
               $this->DKIM_private = trim($this->config->getValue('dkim_privk'));
           }
           $this->DKIM_selector   = 'wys';
	}//endfct

	function send(){
            if(!empty($this->sendHTML)){
                $this->AltBody = $this->textVersion($this->Body,true);
            }else{
                $this->Body = $this->textVersion($this->Body,false);
            }

            if(empty($this->ReplyTo)){
                    $replyToName = $this->config->getValue('reply_name');
                    $this->AddReplyTo($this->config->getValue('reply_email'),$replyToName);
            }

            if((bool)$this->config->getValue('embed_images',0) && !$this->isElasticRest && !$this->isSendGridRest){
                    $this->embedImages();
            }
            if(empty($this->Subject) OR empty($this->Body)){
                    $this->reportMessage = __('There is no subject or body in this email',WYSIJA);
                    $this->errorNumber = 8;
                    return false;
            }
            if(function_exists('mb_convert_encoding') && !empty($this->sendHTML)){
                    $this->Body = mb_convert_encoding($this->Body,'HTML-ENTITIES','UTF-8');
                    $this->Body = str_replace('&amp;','&',$this->Body);
            }
            if($this->CharSet != 'utf-8'){
                    $this->Body = $this->encodingHelper->change($this->Body,'UTF-8',$this->CharSet);
                    $this->Subject = $this->encodingHelper->change($this->Subject,'UTF-8',$this->CharSet);
                    if(!empty($this->AltBody)) $this->AltBody = $this->encodingHelper->change($this->AltBody,'UTF-8',$this->CharSet);
            }
            if($this->isElasticRest){
                $this->addCustomHeader('referral:cfb09bc8-558d-496b-83e6-b05e901a945c');
            }
            $this->Subject = str_replace(array('â€™','â€œ','â€�','â€“'),array("'",'"','"','-'),$this->Subject);
            $this->Body = str_replace(chr(194),chr(32),$this->Body);
            ob_start();
            $result = parent::Send();

            $warnings = ob_get_clean();
            if(!empty($warnings) && strpos($warnings,'bloque')){
                    $result = false;
            }
            $receivers =  array();
            foreach($this->to as $oneReceiver){
                    $receivers[] = $oneReceiver[0];
            }
            if(!$result){
                $this->reportMessage = sprintf(__('Error Sending Message <b><i>%s</i></b> to <b><i>%s</i></b>',WYSIJA),$this->Subject,implode('", "',$receivers));
                    if(!empty($this->ErrorInfo)) {

                        foreach($this->ErrorInfo as $error){
                            $this->reportMessage.=' | '.$error['error'];
                        }
                        $this->ErrorInfo=array();


                    }
                    if(!empty($warnings)) $this->reportMessage .= ' | '.$warnings;
                    $this->errorNumber = 1;
                    if($this->report){
                            $this->error($this->reportMessage);
                    }
            }else{
                    $this->reportMessage = sprintf(__('Successfully sent to <b><i>%s</i></b>',WYSIJA), implode('", "',$receivers));
                    if($this->report){
                        if(!empty($warnings)){
                            $this->reportMessage .= ' | '.$warnings;
                            $this->notice($this->reportMessage,false);
                        }
                    }
            }
            return $result;
	}
	function load($email_id){
            $mailClass = &WYSIJA::get('email','model');
            $mailClass->getFormat=OBJECT;
            $this->defaultMail[$email_id] = $mailClass->getOne($email_id);
            if(!is_array($this->defaultMail[$email_id]->params)) $this->defaultMail[$email_id]->params = unserialize(base64_decode($this->defaultMail[$email_id]->params));
            $this->defaultMail[$email_id]->attach=$this->defaultMail[$email_id]->attachments;
            unset($this->defaultMail[$email_id]->attachments);
            if(empty($this->defaultMail[$email_id]->email_id)) return false;
            if(empty($this->defaultMail[$email_id]->altbody)) $this->defaultMail[$email_id]->altbody = $this->textVersion($this->defaultMail[$email_id]->body);
            if(!empty($this->defaultMail[$email_id]->attach)){
                    $this->defaultMail[$email_id]->attachments = array();
                    $uploadFolder = str_replace(array('/','\\'),DS,html_entity_decode($this->config->getValue('uploadfolder')));
                    $uploadFolder = trim($uploadFolder,DS.' ').DS;
                    $uploadPath = str_replace(array('/','\\'),DS,$uploadFolder);
                    $uploadURL = $this->config->getValue('uploadurl');
                    foreach($this->defaultMail[$email_id]->attach as $oneAttach){
                            $attach = new StdClass();
                            $attach->name = $oneAttach->filename;
                            $attach->filename = $uploadPath.$oneAttach->filename;
                            $attach->url = $uploadURL.$oneAttach->filename;
                            $this->defaultMail[$email_id]->attachments[] = $attach;
                    }
            }
            $this->recordEmail($email_id);
            return $this->defaultMail[$email_id];
	}

        function recordEmail($email_id,$email_object=false){
            if($email_object && !isset($this->defaultMail[$email_id])){
                 $this->defaultMail[$email_id]=$email_object;
                 
            }

            if($this->isMailjet){
                $this->defaultMail[$email_id]->mailjetid=get_option('siteurl').'-'.$this->defaultMail[$email_id]->email_id;
            }

            $this->parseUserTags($this->defaultMail[$email_id]);
            $this->parseSubjectUserTags($this->defaultMail[$email_id]);
            $this->parseRelativeURL($this->defaultMail[$email_id]);
            add_action('wysija_replacetags', array($this,'replacetags'));
            do_action('wysija_replacetags', $email_id);
        }
        function parseUserTags(&$emailobj){
            if(!isset($emailobj->tags) || !$emailobj->tags){
                preg_match_all("#\[([^\]]*):([^\]|]*)([^\]]*)\]#Uis", $emailobj->body, $values_user);
                $tags=array();
                foreach($values_user[0] as  $tag ){
                    $tags[$tag]=explode(' | ',str_replace(array('[',']'),'',$tag));
                    foreach($tags[$tag] as &$arg){
                        $arg=explode(':',$arg);
                    }
                }
                $emailobj->tags=$tags;
            }
            if(!isset($emailobj->tags) || !$emailobj->tags)$emailobj->tags=array();
        }
        function parseSubjectUserTags(&$emailobj){
            if(!isset($emailobj->subject_tags) || !$emailobj->subject_tags){
                preg_match_all("#\[([^\]]*):([^\]|]*)([^\]]*)\]#Uis", $emailobj->subject, $values_user);
                $tags=array();
                foreach($values_user[0] as  $tag ){
                    $tags[$tag]=explode(' | ',str_replace(array('[',']'),'',$tag));
                    foreach($tags[$tag] as &$arg){
                        $arg=explode(':',$arg);
                    }
                }
                $emailobj->subject_tags = $tags;
            }
            if(!isset($emailobj->subject_tags) || !$emailobj->subject_tags)$emailobj->subject_tags=array();
        }

        function parseRelativeURL(&$emailobj){
            static $mainurl = '';
            $siteurl=get_option('siteurl');
            $lastchar=substr($siteurl, -1);
            if($lastchar!='/')$siteurl.='/';
            if(empty($mainurl)){
                $urls = parse_url($siteurl);
                if(!empty($urls['path'])){
                    $mainurl = substr($siteurl,0,strrpos($siteurl,$urls['path'])).'/';
                }else{
                    $mainurl = $siteurl;
                }
            }




            $replace = array();
            $replaceBy = array();

            if($mainurl !== $siteurl){


                $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\#|\[|[a-z]{3,7}:|/))(?:\.\./)#i';
                $replaceBy[] = '$1="'.substr($siteurl,0,strrpos(rtrim($siteurl,'/'),'/')+1);
            }
            $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\#|\[|[a-z]{3,7}:|/))(?:\.\./|\./)?#i';
            $replaceBy[] = '$1="'.$siteurl;
            $replace[] = '#(href|src|action|background)[ ]*=[ ]*\"(?!(\{|%7B|\#|\[|[a-z]{3,7}:))/#i';
            $replaceBy[] = '$1="'.$mainurl;

            $replace[] = '#(background-image[ ]*:[ ]*url\(\'?"?(?!([a-z]{3,7}:|/|\'|"))(?:\.\./|\./)?)#i';
            $replaceBy[] = '$1'.$siteurl;
            $emailobj->body=preg_replace($replace,$replaceBy,$emailobj->body);
            return true;
        }

	function clearAll(){
            $this->Subject = '';
            $this->Body = '';
            $this->AltBody = '';
            $this->ClearAllRecipients();
            $this->ClearAttachments();
            $this->ClearCustomHeaders();
            $this->ClearReplyTos();
            $this->errorNumber = 0;
            $this->setFrom($this->config->getValue('from_email'),$this->config->getValue('from_name'));
	}
	function sendOne($email_id,$receiverid,$confirmEmail=false){
            $this->clearAll();
            if(is_object($email_id)){
                $emailObj=$email_id;
                $email_id=$email_id->email_id;
                $this->recordEmail($email_id,$emailObj);
            }
            if(!isset($this->defaultMail[$email_id])){
                if(!$this->load($email_id)){
                    $this->reportMessage = 'Can not load the email : '.$email_id;
                    if($this->report){
                            $this->error($this->reportMessage);
                    }
                    $this->errorNumber = 2;
                    return false;
                }
            }

            
            $this->addCustomHeader( 'X-email_id: ' . $this->defaultMail[$email_id]->email_id );
            if(isset($this->defaultMail[$email_id]->mailjetid)){
                $this->addCustomHeader( 'X-Mailjet-Campaign: ' . $this->defaultMail[$email_id]->mailjetid);
            }
            if(!isset($this->forceVersion) AND empty($this->defaultMail[$email_id]->status)){
                $this->reportMessage = sprintf(__('The email ID %s is not published',WYSIJA),$email_id);
                $this->errorNumber = 3;
                if($this->report){
                        $this->error($this->reportMessage);
                }
                return false;
            }
            if(!is_object($receiverid)){
                $this->subscriberClass->getFormat = OBJECT;
                $receiver = $this->subscriberClass->getOne($receiverid);
                if(!$receiver){
                    $userHelper = &WYSIJA::get('user','helper');
                    if($userHelper->validEmail($receiverid)){
                        $this->subscriberClass->getFormat = OBJECT;
                        $receiver = $this->subscriberClass->getOne(false,array('email'=>$receiverid));
                    }

                }
                if((!$receiver || empty($receiver->user_id)) AND is_string($receiverid) AND $this->autoAddUser){
                    $userHelper = &WYSIJA::get('user','helper');
                    if($userHelper->validEmail($receiverid)){
                        $newUser = array();
                        $newUser['email'] = $receiverid;
                        $newUser['status'] = 1;
                        if(isset($this->wp_user))$newUser['wpuser_id'] = $this->wp_user->ID;
                        $this->subscriberClass->checkVisitor = false;
                        $this->subscriberClass->sendConf = false;
                        $user_id = $this->subscriberClass->insert($newUser);
                        $this->subscriberClass->getFormat = OBJECT;
                        $receiver = $this->subscriberClass->getOne($user_id);
                    }
                }
            }else{
                $receiver = $receiverid;
            }
            if(empty($receiver->email)){
                $this->reportMessage = sprintf(__('Subscriber not found : <b><i>%s</i></b>',WYSIJA),isset($receiver->user_id) ? $receiver->user_id : $receiverid);
                if($this->report){
                        $this->error($this->reportMessage);
                }
                $this->errorNumber = 4;
                return false;
            }
            $this->MessageID = '<'.preg_replace("|[^a-z0-9+_]|i",'',base64_encode(rand(0,9999999)).'WY'.$receiver->user_id.'SI'.$this->defaultMail[$email_id]->email_id.'JA'.base64_encode(time().rand(0,99999))).'@'.$this->ServerHostname().'>';

            if(!isset($this->forceVersion)){
                    if( $this->checkConfirmField AND empty($receiver->status) AND $this->config->getValue('confirm_dbleoptin')==1 AND $email_id != $this->config->getValue('confirm_email_id')){
                            $this->reportMessage = sprintf(__($this->config->getValue('confirm_dbleoptin').' The subscriber <b><i>%s</i></b> is not confirmed',WYSIJA),$receiver->email);
                            if($this->report){
                                    $this->error($this->reportMessage);
                            }
                            $this->errorNumber = 5;
                            return false;
                    }
            }
            $addedName = $this->cleanText($receiver->firstname.' '.$receiver->lastname);
            $this->AddAddress($this->cleanText($receiver->email),$addedName);
            if(!isset($this->forceVersion)){


                    $this->sendHTML = true;
                    $this->IsHTML($this->sendHTML);
            }else{
                    $this->sendHTML = (bool) $this->forceVersion;
                    $this->IsHTML($this->sendHTML);
            }
            $this->Subject = $this->defaultMail[$email_id]->subject;
            if($this->sendHTML){
                $this->Body =  $this->defaultMail[$email_id]->body;
                if($confirmEmail)    {
                    $this->Body =  nl2br($this->Body );

                }
                if($this->config->getValue('multiple_part',false)){
                        $this->AltBody = $this->defaultMail[$email_id]->altbody;
                }
            }else{
                    $this->Body =  $this->defaultMail[$email_id]->altbody;
            }

            if(!empty($this->defaultMail[$email_id]->replyto_email)){
                    $replyToName = $this->cleanText($this->defaultMail[$email_id]->replyto_name) ;
                    $this->AddReplyTo($this->cleanText($this->defaultMail[$email_id]->replyto_email),$replyToName);
            }
            if(!empty($this->defaultMail[$email_id]->attachments)){
                    if(true ){
                            foreach($this->defaultMail[$email_id]->attachments as $attachment){
                                    $this->AddAttachment($attachment->filename);
                            }
                    }else{
                            
                    }
            }
            if(!empty($this->parameters)){
                    $keysparams = array_keys($this->parameters);
                    $this->Subject = str_replace($keysparams,$this->parameters,$this->Subject);
                    $this->Body = str_replace($keysparams,$this->parameters,$this->Body);
                    if(!empty($this->AltBody)) $this->AltBody = str_replace($keysparams,$this->parameters,$this->AltBody);
            }

            $is_multisite=is_multisite();


            if($is_multisite && $this->config->getValue('ms_sending_config')=='one-for-all' && $this->config->getValue('sending_method')=='network'){
                $this->defaultMail[$email_id]->from_email=$this->config->getValue('ms_from_email');
            }
            $this->setFrom($this->defaultMail[$email_id]->from_email,$this->defaultMail[$email_id]->from_name);
            

            $mailforTrigger = new StdClass();
            $mailforTrigger->body = &$this->Body;

            $mailforTrigger->subject = &$this->Subject;
            $mailforTrigger->from = &$this->From;
            $mailforTrigger->fromName = &$this->FromName;
            $mailforTrigger->replyto = &$this->ReplyTo;
            $mailforTrigger->replyname = &$this->defaultMail[$email_id]->replyname;
            $mailforTrigger->replyemail = &$this->defaultMail[$email_id]->replyemail;
            $mailforTrigger->email_id = $this->defaultMail[$email_id]->email_id;
            $mailforTrigger->type = &$this->defaultMail[$email_id]->type;
            if(isset($this->defaultMail[$email_id]->params))    {
                if(!is_array($this->defaultMail[$email_id]->params))    $this->defaultMail[$email_id]->params=unserialize(base64_decode($this->defaultMail[$email_id]->params));
                $mailforTrigger->params = $this->defaultMail[$email_id]->params;
            }
            $mailforTrigger->tags = &$this->defaultMail[$email_id]->tags;
            $mailforTrigger->subject_tags = &$this->defaultMail[$email_id]->subject_tags;
            $mailforTrigger->sendHTML = true;
            

            add_action('wysija_replaceusertags', array($this,'replaceusertags'),10,2);
            add_action('wysija_replaceusertags', array($this,'tracker_replaceusertags'),11,2);
            add_action('wysija_replaceusertags', array($this,'openrate_replaceusertags'),12,2);
            #\[user:([^\]|]*)([^\]]*)\]#Uis
            do_action( 'wysija_replaceusertags', $mailforTrigger,$receiver);
            if(!empty($mailforTrigger->customHeaders)){
                    foreach($mailforTrigger->customHeaders as $oneHeader){
                            $this->addCustomHeader( $oneHeader );
                    }
            }

            return $this->send();
	}
	function embedImages(){
	    preg_match_all('/(src|background)="([^"]*)"/Ui', $this->Body, $images);
	   	$result = true;
	    if(!empty($images[2])) {
	   		$mimetypes = array('bmp'   =>  'image/bmp',
						      'gif'   =>  'image/gif',
						      'jpeg'  =>  'image/jpeg',
						      'jpg'   =>  'image/jpeg',
						      'jpe'   =>  'image/jpeg',
						      'png'   =>  'image/png',
						      'tiff'  =>  'image/tiff',
						      'tif'   =>  'image/tiff');
	   		$allimages = array();
	      foreach($images[2] as $i => $url) {
	      	if(isset($allimages[$url])) continue;
	      	$allimages[$url] = 1;
	      	$path = str_replace(array($this->config->getValue('uploadurl'),'/'),array($this->config->getValue('uploadfolder'),DS),urldecode($url));
	        $filename  = basename($url);
	        $md5 = md5($filename);
	        $cid       = 'cid:' . $md5;
	        $fileParts = explode(".", $filename);
	        $ext       = strtolower($fileParts[1]);
	        if(!isset($mimetypes[$ext])) continue;
	        $mimeType  = $mimetypes[$ext];
	        if($this->AddEmbeddedImage($path, $md5, $filename, 'base64', $mimeType)){
	       		$this->Body = preg_replace("/".$images[1][$i]."=\"".preg_quote($url, '/')."\"/Ui", $images[1][$i]."=\"".$cid."\"", $this->Body);
	        }else{
	        	$result = false;
	        }
	      }
	    }
	    return $result;
	}
	function textVersion($html,$fullConvert = true){

		if($fullConvert){
			$html = preg_replace('# +#',' ',$html);
			$html = str_replace(array("\n","\r","\t"),'',$html);
		}
		$removepictureslinks = "#< *a[^>]*> *< *img[^>]*> *< *\/ *a *>#isU";
		$removeScript = "#< *script(?:(?!< */ *script *>).)*< */ *script *>#isU";
		$removeStyle = "#< *style(?:(?!< */ *style *>).)*< */ *style *>#isU";
		$removeStrikeTags =  '#< *strike(?:(?!< */ *strike *>).)*< */ *strike *>#iU';
		$replaceByTwoReturnChar = '#< *(h1|h2)[^>]*>#Ui';
		$replaceByStars = '#< *li[^>]*>#Ui';
		$replaceByReturnChar1 = '#< */ *(li|td|tr|div|p)[^>]*> *< *(li|td|tr|div|p)[^>]*>#Ui';
		$replaceByReturnChar = '#< */? *(br|p|h1|h2|legend|h3|li|ul|h4|h5|h6|tr|td|div)[^>]*>#Ui';
		$replaceLinks = '/< *a[^>]*href *= *"([^#][^"]*)"[^>]*>(.*)< *\/ *a *>/Uis';
		$text = preg_replace(array($removepictureslinks,$removeScript,$removeStyle,$removeStrikeTags,$replaceByTwoReturnChar,$replaceByStars,$replaceByReturnChar1,$replaceByReturnChar,$replaceLinks),array('','','','',"\n\n","\n* ","\n","\n",'${2} ( ${1} )'),$html);
		$text = str_replace(array("Â ","&nbsp;"),' ',strip_tags($text));
		$text = trim(@html_entity_decode($text,ENT_QUOTES,'UTF-8'));
		if($fullConvert){
			$text = preg_replace('# +#',' ',$text);
			$text = preg_replace('#\n *\n\s+#',"\n\n",$text);
		}
		return $text;
	}
	function cleanText($text){
		return trim( preg_replace( '/(%0A|%0D|\n+|\r+)/i', '', (string) $text ) );
	}
	function setFrom($email,$name=''){
		if(!empty($email)){
			$this->From = $this->cleanText($email);
		}
		if(!empty($name)){
			$this->FromName = $this->cleanText($name);
		}
	}
	function addParamInfo(){
		if(!empty($_SERVER)){
			$serverinfo = array();
			foreach($_SERVER as $oneKey => $oneInfo){
				$serverinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo,true));
			}
			$this->addParam('serverinfo',implode('<br />',$serverinfo));
		}
		if(!empty($_REQUEST)){
			$postinfo = array();
			foreach($_REQUEST as $oneKey => $oneInfo){
				$postinfo[] = $oneKey.' => '.strip_tags(print_r($oneInfo,true));
			}
			$this->addParam('postinfo',implode('<br />',$postinfo));
		}
	}
	function addParam($name,$value){
		$tagName = '{'.$name.'}';
		$this->parameters[$tagName] = $value;
	}
        function sendSimple($sendto,$subject,$body,$params=array(),$format='text'){
            $modelConfig=&WYSIJA::get('config','model');
            $emailObj=new StdClass();

            $emailObj->email_id=0;
            if(isset($params['email_id'])) $emailObj->email_id=$params['email_id'];
            while(isset($this->defaultMail[$emailObj->email_id])){
                $emailObj->email_id=$emailObj->email_id-1;
            }
            $emailObj->subject=$subject;
            $emailObj->body=$body;
            $emailObj->status=1;
            $emailObj->attachments="";

            if(isset($params['from_name']))    $emailObj->from_name=$params['from_name'];
            else $emailObj->from_name=$modelConfig->getValue('from_name');
            if(isset($params['from_email']))    $emailObj->from_email=$params['from_email'];
            else $emailObj->from_email=$modelConfig->getValue('from_email');
            if(isset($params['replyto_name']))    $emailObj->replyto_name=$params['replyto_name'];
            else $emailObj->replyto_name=$modelConfig->getValue('replyto_name');
            if(isset($params['replyto_email']))    $emailObj->replyto_email=$params['replyto_email'];
            else $emailObj->replyto_email=$modelConfig->getValue('replyto_email');
            if(isset($params['params']))    $emailObj->params=$params['params'];


            $emailObj->mail_format=$format;
            $emailObj->simple=1;

            
            $this->checkConfirmField=false;
            if(!$this->testemail){
                
                add_action('wysija_replacetags', array($this,'replacetags'));
                do_action( 'wysija_replacetags', array(&$emailObj));
            }
            

            if(is_string($sendto)){
                $dummyreceiver=new stdClass();
                $dummyreceiver->user_id=0;
                $dummyreceiver->email=trim($sendto);
                $dummyreceiver->status=1;
                $dummyreceiver->lastname=$dummyreceiver->firstname ='';
            }else $dummyreceiver=$sendto;
            return $this->sendOne($emailObj,$dummyreceiver);
        }
        function replacetags($email_id){
            $find=array();
            $replace=array();
            $find[]='[unsubscribe_linklabel]';
            if(!isset($this->config->values['unsubscribe_linkname'])) $replace[]=__('Unsubscribe',WYSIJA);
            else $replace[]=$this->config->getValue('unsubscribe_linkname');
            $this->defaultMail[$email_id]->body=str_replace($find,$replace,$this->defaultMail[$email_id]->body);
        }
        function replaceusertags($email,$receiver){
            $arrayfind = array();
            $arrayreplace = array();

            $shortcodesH =& WYSIJA::get('shortcodes','helper');
            $email->subject = $shortcodesH->replace_subject($email, $receiver);
            $email->body = $shortcodesH->replace_body($email, $receiver);

            $arrayfind[]='[subscriptions_links]';
            if(!empty($receiver))   $subscriptions_links='<div>'.$this->subscriberClass->getUnsubLink($receiver).'</div>';
            else $subscriptions_links='';
            $arrayreplace[]=$subscriptions_links;
            if($email->email_id == $this->config->getValue('confirm_email_id')){
                $this->subscriberClass->reset();
                $activation_link=$this->subscriberClass->getConfirmLink($receiver,'subscribe',false,true);
                $listids='';
                if($this->listids){
                    $listids='&wysiconf='.base64_encode(serialize($this->listids));
                    $arrayfind[]='[lists_to_confirm]';
                    if(!$this->listnames) $arrayreplace[]='';
                    else $arrayreplace[]='<strong>'.implode(', ', $this->listnames).'</strong>';
                }
                $activation_link.='';
                $arrayfind[]='[activation_link]';
                $arrayreplace[]='<a href="'.$activation_link.$listids.'" target="_blank">';
                $arrayfind[]='[/activation_link]';
                $arrayreplace[]='</a>';
            }
            $email->body=str_replace($arrayfind,$arrayreplace,$email->body);
            $email->subject=str_replace($arrayfind,$arrayreplace,$email->subject);

        }
        function tracker_replaceusertags($email,$user){



            $urls = array();
            if(!preg_match_all('#href[ ]*=[ ]*"(?!mailto:|\#|ymsgr:|callto:|file:|ftp:|webcal:|skype:)([^"]+)"#Ui',$email->body,$results)) return;
            $modelConf=&WYSIJA::get('config','model');


            foreach($results[1] as $i => $url){
                $coreUrl=false;
                if(isset($urls[$results[0][$i]])|| strpos($url, 'wysija-key')) continue;
                $urlreuse=$url;
                if((strpos($urlreuse, '[view_in_browser_link]')===false || strpos($urlreuse, '[unsubscribe_link]')===false) && isset($email->params['googletrackingcode']) && trim($email->params['googletrackingcode']) ){
                    $hashPartUrl = '';
                    if (strpos($urlreuse, '#') !== false){
                        $hashPartUrl = substr($urlreuse,strpos($urlreuse, '#'));
                        $urlreuse = substr($urlreuse,0,strpos($urlreuse, '#'));
                    }
                    if (strpos($urlreuse, '?') !== false) $charStart='&';
                    else $charStart='?';
                    $urlreuse.=$charStart;
                    $argsp=array();
                    $argsp['utm_source'] = 'wysija';
                    $argsp['utm_medium'] = 'email';
                    $argsp['utm_campaign'] = trim($email->params['googletrackingcode']);
                    $paramsinline=array();
                    foreach($argsp as $k => $v){
                        $paramsinline[]=$k."=".$v;
                    }
                    $urlreuse.=implode('&',$paramsinline);
                    $urlreuse.=$hashPartUrl;
                }


                $Wysijaurls=array();
                $Wysijaurls['action=unsubscribe']='[unsubscribe_link]';
                $Wysijaurls['action=subscriptions']='[subscriptions_link]';
                $Wysijaurls['action=viewinbrowser']='[view_in_browser_link]';
                $urlsportions=array_keys($Wysijaurls);
                if(preg_match('#'.implode('|',$urlsportions).'|\{|%7B#i',$urlreuse)){
                    foreach($Wysijaurls as $k =>$v){
                        if(strpos($urlreuse, $k)!==false){
                            if($modelConf->getValue('urlstats_base64')){
                                $cururl=base64_encode($v);
                            }else{
                                $cururl=$v;
                            }
                            $urlencoded=urlencode($cururl);
                            break;
                        }
                    }
                }else{

                    $urlreuse=trim($urlreuse);
                    if($modelConf->getValue('urlstats_base64')){
                        $cururl=base64_encode($urlreuse);
                    }else{
                        $cururl=$urlreuse;
                    }
                    $urlencoded=urlencode($cururl);
                }
                $args = array();
                $args['email_id'] = $email->email_id;
                $args['user_id'] = $user->user_id;
                if(empty($user->user_id)) $args['demo']=1;
                $args['urlpassed'] = $urlencoded;
                $args['controller'] = 'stats';

                if(strpos($urlreuse, '[unsubscribe_link]')!==false){
                    $args['hash']=md5(AUTH_KEY.'[unsubscribe_link]'.$args['user_id']);
                }
                if(strpos($urlreuse, '[subscriptions_link]')!==false){
                    $args['hash']=md5(AUTH_KEY.'[subscriptions_link]'.$args['user_id']);
                }

                $forbiddenparams=$modelConf->getValue('params_forbidden');
                if(isset($forbiddenparams['controller']['stats'])) $args['controller'] = $forbiddenparams['controller']['stats'];
                $args['action'] = 'analyse';
                $args['wysija-page'] = 1;
                if(!$modelConf->getValue('urlstats_base64')){
                    $args['no64'] = 1;
                }
                $mytracker=WYSIJA::get_permalink($modelConf->getValue('confirm_email_link'),$args);
                $urls[$results[0][$i]] = str_replace($url,$mytracker,$results[0][$i]);
            }
            $email->body = str_replace(array_keys($urls),$urls,$email->body);
	}//endfct

        function openrate_replaceusertags($email,$user){

                $typemails=array(1,2,3);
		if(empty($email->type) OR !in_array($email->type,$typemails) OR strpos($email->body,'[nostatpicture]')){
			$email->body = str_replace(array('[statpicture]','[nostatpicture]'),'',$email->body);
			return;
		}
                $widths = range(1, 50);
                shuffle($widths);
                $heights = range(1, 3);
                shuffle($heights);
                
		$widthsize = $widths[0];
		$heightsize = $heights[0];
		$width = empty($widthsize) ? '' : ' width="'.$widthsize.'" ';
		$height = empty($heightsize) ? '' : ' height="'.$heightsize.'" ';
                $modelConf=&WYSIJA::get('config','model');
                $args = array();
                $args['email_id'] = $email->email_id;
                $args['user_id'] = $user->user_id;
                $args['controller'] = 'stats';
                $forbiddenparams=$modelConf->getValue('params_forbidden');
                if(isset($forbiddenparams['controller']['stats'])) $args['controller'] = $forbiddenparams['controller']['stats'];

                $args['action'] = 'analyse';
                $args['wysija-page'] = 1;
                $args['render'] = 1;
                $mytracker=WYSIJA::get_permalink($modelConf->getValue("confirm_email_link"),$args);
		$statPicture = '<img alt="" src="'.$mytracker.'"  border="0" '.$height.$width.'/>';
                if(strpos($email->body,'[statpicture]')) $email->body = str_replace('[statpicture]',$statPicture,$email->body);
		elseif(strpos($email->body,'</body>')) $email->body = str_replace('</body>',$statPicture.'</body>',$email->body);
		else $email->body .= $statPicture;
	 }
        function SetError($key,$var="") {
            if(count($this->language) < 1) {
              $this->SetLanguage('en'); // set the default language
            }
            $this->error_count++;
            if(!$this->ErrorInfo) $this->ErrorInfo=array();

            $varerror='';
            if(!is_array($var)) $varerror=$var;
            elseif(isset($var['error'])) $varerror=$var['error'];
            if(!isset($this->language[$key])){
                $errormsg=$key;
            }else   $errormsg=$this->language[$key];
            if($varerror) $errormsg.='('.$varerror.')';
            $this->ErrorInfo[] = array('error'=>$errormsg);

        }
}
class WYSIJA_sendfalse {
    function send(){
        return true;
    }
}
