<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_bounce extends WYSIJA_help{
  var $report = false;
  var $config;
  var $mailer;
  var $mailbox;
  var $_message;
  var $listsubClass;
  var $subClass;
  var $db;
  var $deletedUsers = array();
  var $unsubscribedUsers = array();
  var $addtolistUsers = array();
  var $bounceMessages = array();
  var $listdetails=array();
  var $usepear = false;
  var $detectEmail = '/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@([a-z0-9\-]+\.)+[a-z0-9]{2,8}/i';
  var $messages = array();
  function WYSIJA_help_bounce(){
    $this->config = &WYSIJA::get('config','model');
    $this->mailer = &WYSIJA::get('mailer','helper');
    $this->rulesClass = &WYSIJA::get('rules','helper');
    $this->mailer->report = false;
    $this->subClass = &WYSIJA::get('user','model');//acymailing_get('class.subscriber');
    $this->listsubClass = &WYSIJA::get('user_list','model');//acymailing_get('class.listsub');
    $this->listsubClass->checkAccess = false;
    $this->listsubClass->sendNotif = false;
    $this->listsubClass->sendConf = false;
    $this->historyClass = &WYSIJA::get('user_history','model');
  }
  function init($config=false){
  if($config){

       foreach($config as $key => $val)    $this->config->values[$key]=$val;
    }
  if($this->config->getValue('bounce_connection_method') == 'pear'){
       $this->usepear = true;
      include_once(WYSIJA_INC.'pear'.DS.'pop3.php');
      return true;
    }
    if(extension_loaded("imap") OR function_exists('imap_open')) return true;
    $prefix = (PHP_SHLIB_SUFFIX == 'dll') ? 'php_' : '';
    $EXTENSION = $prefix . 'imap.' . PHP_SHLIB_SUFFIX;
    if(function_exists('dl')){

      $fatalMessage = 'The system tried to load dynamically the '.$EXTENSION.' extension';
      $fatalMessage .= '<br/>If you see this message, that means the system could not load this PHP extension';
      $fatalMessage .= '<br/>Please enable the PHP Extension '.$EXTENSION;
      ob_start();
      echo $fatalMessage;

      dl($EXTENSION);
      $warnings = str_replace($fatalMessage,'',ob_get_clean());
      if(extension_loaded("imap") OR function_exists('imap_open')) return true;
    }
    if($this->report){
      $this->error('The extension "'.$EXTENSION.'" could not be loaded, please change your PHP configuration to enable it or use the pop3 method without imap extension',true);
      if(!empty($warnings)) $this->error($warnings,true);
    }
    return false;
  }
    function connect(){
        if($this->usepear) return $this->_connectpear();
        return $this->_connectimap();
    }
  function _connectpear(){
    ob_start();
    $this->mailbox = new Net_POP3();
    $timeout = $this->config->getValue('bounce_timeout');
      if(!empty($timeout)) $this->mailbox->setTimeOut($timeout);
    $port = intval($this->config->getValue('bounce_port',''));
    if(empty($port)) $port = '110/pop3/notls';
    $serverName = $this->config->getValue('bounce_host');
    $secure = $this->config->getValue('bounce_connection_secure','');

    if(!empty($secure) AND !strpos($serverName,'://')) $serverName = $secure.'://'.$serverName;
    if(!$this->mailbox->connect($serverName,$port)){
      $warnings = ob_get_clean();
      if($this->report) {
          $this->error('Error connecting to the server '.$this->config->getValue('bounce_host').' : '.$port,true);
          return false;
      }
      if(!empty($warnings) AND $this->report) $this->error($warnings,true);
          return false;
      }
      $login = $this->mailbox->login(trim($this->config->getValue('bounce_login')),trim($this->config->getValue('bounce_password')),'USER' );
      if(empty($login) OR isset($login->code)){
      $warnings = ob_get_clean();
      if($this->report) {
          $this->error('Identication error '.$this->config->getValue('bounce_login').':'.$this->config->getValue('bounce_password'),true);
          return false;
      }
      if(!empty($warnings) AND $this->report) $this->error($warnings,true);
          return false;
      }
      ob_clean();
      return true;
  }
  function _connectimap(){
    ob_start();

    $buff = imap_alerts();
    $buff = imap_errors();
    $timeout = $this->config->getValue('bounce_timeout');
    if(!empty($timeout)) imap_timeout(IMAP_OPENTIMEOUT,$timeout);
    $port = $this->config->getValue('bounce_port','');
    $secure = $this->config->getValue('bounce_connection_secure','');
    $protocol = $this->config->getValue('bounce_connection_method','');
    $serverName = '{'.$this->config->getValue('bounce_host');
    if(empty($port)){
        if($secure == 'ssl' && $protocol == 'imap') $port = '993';
        elseif($protocol == 'imap') $port = '143';
        elseif($protocol == 'pop3') $port = '110';
    }

    if(!empty($port)) $serverName .= ':'.$port;

    if(!empty($secure)) $serverName .= '/'.$secure;
    if($this->config->getValue('bounce_selfsigned',false)) $serverName .= '/novalidate-cert';

    if(!empty($protocol)) $serverName .='/service='.$protocol;
    $serverName .= '}';
    $this->mailbox = imap_open($serverName,trim($this->config->getValue('bounce_login')),trim($this->config->getValue('bounce_password')));
    $warnings = ob_get_clean();
	if($this->report){
		if(!$this->mailbox){
			$this->error('Error connecting to '.$serverName,true);
		}
		if(!empty($warnings)){
			$this->error($warnings,true);
		}
	}

    return $this->mailbox ? true : false;
  }
  function getNBMessages(){
    if($this->usepear){
      $this->nbMessages = $this->mailbox->numMsg();
    }else{
    $this->nbMessages = imap_num_msg($this->mailbox);
    }
    return $this->nbMessages;
  }
    function getMessage($msgNB){
        if($this->usepear){
                    $message = null;
                    $message->headerString = $this->mailbox->getRawHeaders($msgNB);
                    if(empty($message->headerString)) return false;
        }else{
            $message = imap_headerinfo($this->mailbox,$msgNB);
        }
            return $message;
    }
    function deleteMessage($msgNB){
            if($this->usepear){
                    $this->mailbox->deleteMsg($msgNB);
            }else{
                    imap_delete($this->mailbox,$msgNB);
                    imap_expunge($this->mailbox);
            }
    }
  function close(){
    if($this->usepear){
      $this->mailbox->disconnect();
    }else{
      imap_close($this->mailbox);
    }
  }
  function decodeMessage(){
    if($this->usepear){
        return $this->_decodeMessagepear();
      }else{
        return  $this->_decodeMessageimap();
      }
  }
  function _decodeMessagepear(){
    $this->_message->headerinfo = $this->mailbox->getParsedHeaders($this->_message->messageNB);
    if(empty($this->_message->headerinfo['subject'])) return false;
    $this->_message->text = '';
    $this->_message->html = $this->mailbox->getBody($this->_message->messageNB);
    $this->_message->subject = $this->_decodeHeader($this->_message->headerinfo['subject']);
    $this->_message->header->sender_email = @$this->_message->headerinfo['return-path'];
    if(is_array($this->_message->header->sender_email)) $this->_message->header->sender_email=reset($this->_message->header->sender_email);
    if(preg_match($this->detectEmail,$this->_message->header->sender_email,$results)){
      $this->_message->header->sender_email = $results[0];
    }
    $this->_message->header->sender_name = strip_tags(@$this->_message->headerinfo['from']);
    $this->_message->header->reply_to_email = $this->_message->header->sender_email;
    $this->_message->header->reply_to_name = $this->_message->header->sender_name;
    $this->_message->header->from_email = $this->_message->header->sender_email;
    $this->_message->header->from_name = $this->_message->header->sender_name;
    return true;
  }
  function _decodeMessageimap(){
    $this->_message->structure = imap_fetchstructure($this->mailbox,$this->_message->messageNB);
    if(empty($this->_message->structure)) return false;
    $this->_message->headerinfo = imap_fetchheader($this->mailbox,$this->_message->messageNB);
      $this->_message->html = '';
    $this->_message->text = '';

      if($this->_message->structure->type == 1){
        $this->_message->contentType = 2;
        $allParts = $this->_explodeBody($this->_message->structure);
		$this->_message->text = '';
        foreach($allParts as $num => $onePart){
          $charset = $this->_getMailParam($onePart,'charset');
                if ($onePart->subtype=='HTML'){
                  $this->_message->html = $this->_decodeContent(imap_fetchbody($this->mailbox,$this->_message->messageNB,$num),$onePart);
                }else{
                  $this->_message->text .= $this->_decodeContent(imap_fetchbody($this->mailbox,$this->_message->messageNB,$num),$onePart)."\n\n- - -\n\n";
                }
        }
      }else{
        $charset = $this->_getMailParam($this->_message->structure,'charset');
        if($this->_message->structure->subtype == 'HTML'){
          $this->_message->contentType = 1;
          $this->_message->html = $this->_decodeContent(imap_body($this->mailbox,$this->_message->messageNB),$this->_message->structure);
        }else{
          $this->_message->contentType = 0;
          $this->_message->text = $this->_decodeContent(imap_body($this->mailbox,$this->_message->messageNB),$this->_message->structure);
        }
      }

      $this->_message->subject = $this->_decodeHeader($this->_message->subject);
      $this->_decodeAddressimap('sender');
      $this->_decodeAddressimap('from');
      $this->_decodeAddressimap('reply_to');
      $this->_decodeAddressimap('to');
    return true;
  }
  function handleMessages(){
      $modelList=&WYSIJA::get("list","model");
      $listdetails=$modelList->getRows(array("name","list_id"));
      foreach($listdetails as $listinfo){
          $this->listdetails[$listinfo["list_id"]]=$listinfo["name"];
      }
    $maxMessages = min($this->nbMessages,$this->config->getValue('bounce_max',100));
    if(empty($maxMessages)) $maxMessages = $this->nbMessages;
    if($this->report){

		if(!headers_sent() AND ob_get_level() > 0){
			ob_end_flush();
		}

		$disp = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" />';
		$disp .= '<title>'.addslashes(__('Bounce Handling',WYSIJA)).'</title>';
		$disp .= '<style>body{font-size:12px;font-family: Arial,Helvetica,sans-serif;} strong{color: black;}</style></head><body>';
		$disp .= "<div style='position:relative; top:3px;left:3px;'>";
		$disp .= __("Bounce Handling",WYSIJA);
      $disp .= ':  <span id="counter"/>0</span> / '. $maxMessages;
      $disp .= '</div>';
      $disp .= '<br/>';
      $disp .= '<script type="text/javascript" language="javascript">';
      $disp .= 'var mycounter = document.getElementById("counter");';
      $disp .= 'function setCounter(val){ mycounter.innerHTML=val;}';
      $disp .= '</script>';
      echo $disp;
		if(function_exists('ob_flush')) @ob_flush();
			@flush();
	}

     $rules = $this->rulesClass->getRules();
    $msgNB = $maxMessages;
    $listClass=&WYSIJA::get("list","model");
    $this->allLists = $listClass->getRows();
    while(($msgNB>0) && ($this->_message = $this->getMessage($msgNB))){
      if($this->report){
        echo '<script type="text/javascript" language="javascript">setCounter('. ($maxMessages-$msgNB+1) .')</script>';
    if(function_exists('ob_flush')) @ob_flush();
    @flush();
      }
      $this->_message->messageNB = $msgNB;
      $this->decodeMessage();
      $msgNB--;
      if(empty($this->_message->subject)) continue;
      $this->_message->analyseText = $this->_message->html.' '.$this->_message->text;
      $this->_display('<strong>'.__("Subject",WYSIJA).' : '.strip_tags($this->_message->subject).'</strong>',false,$maxMessages-$this->_message->messageNB+1);

      preg_match('#WY([0-9]+)SI([0-9]+)JA#i',$this->_message->analyseText,$resultsVars);
      if(!empty($resultsVars[1])) $this->_message->user_id = $resultsVars[1];
      if(!empty($resultsVars[2])) $this->_message->email_id = $resultsVars[2];

      
                if(empty($this->_message->user_id)){

                    preg_match_all($this->detectEmail,$this->_message->analyseText,$results);
                    $replyemail = $this->config->getValue('reply_email');
                    $fromemail=$this->config->getValue('from_email');
                    $bouncemail = $this->config->getValue('bounce_email');
                    $removeEmails = '#('.str_replace(array('%'),array('@'),$this->config->getValue('bounce_login'));
                    if(!empty($bouncemail)) $removeEmails .= '|'.$bouncemail;
                    if(!empty($fromemail)) $removeEmails .= '|'.$fromemail;
                    if(!empty($replyemail)) $removeEmails .= '|'.$replyemail;
                    $removeEmails .= ')#i';
                    if(!empty($results[0])){
                            $alreadyChecked = array();
                                    foreach($results[0] as $oneEmail){

                                            if(!preg_match($removeEmails,$oneEmail)){

                                                    $this->_message->subemail = strtolower($oneEmail);

                                                    if(!empty($alreadyChecked[$this->_message->subemail])) continue;
                                                    $this->subClass->getFormat=OBJECT;
                                                    $result=$this->subClass->getOne(array('user_id'),array('email'=>$this->_message->subemail));
                                                    $this->_message->user_id = $result->user_id;
                                                    $alreadyChecked[$this->_message->subemail] = true;
                                                    if(!empty($this->_message->user_id)) break;
                                            }
                                    }
                            }
                }
		if(empty($this->_message->email_id) && !empty($this->_message->user_id)){

		    $modelEUS=&WYSIJA::get("email_user_stat","model");
                     $emailres= $modelEUS->query("get_row",'SELECT `email_id` FROM [wysija]'.$modelEUS->table_name.' WHERE `user_id` = '.(int) $this->_message->user_id.' ORDER BY `sent_at` DESC LIMIT 1');
                    $this->_message->email_id=$emailres['email_id'];

		}
      foreach($rules as $oneRule){

        if($this->_handleRule($oneRule)) break;
      }
      if($msgNB%50 == 0) $this->_subActions();
    }
  $this->_subActions();
	if($this->report){

		echo "</body></html>";
	}
  }
  function _subActions(){
    if(!empty($this->deletedUsers)){
        $this->subClass->testdelete=true;
        $this->subClass->delete(array("user_id"=>$this->deletedUsers));
        $this->deletedUsers = array();
    }
    if(!empty($this->unsubscribedUsers)){

        $modeUH=&WYSIJA::get('user','helper');
        if(array($this->unsubscribedUsers)){
            foreach($this->unsubscribedUsers as $unsub_user_id){
                $modeUH->unsubscribe($unsub_user_id,true);
            }
        }else{
            $modeUH->unsubscribe($this->unsubscribedUsers,true);
        }

        $this->unsubscribedUsers = array();
    }
    if(!empty($this->addtolistUsers)){

        $modeUH=&WYSIJA::get("user","helper");
        foreach($this->addtolistUsers as $listid =>$user_ids){
          $modeUH->addToList($listid,$user_ids);
        }
        $this->addtolistUsers = array();
    }
    if(!empty($this->bounceMessages)){
        foreach($this->bounceMessages as $email_id => $bouncedata){
            if(!empty($bouncedata['user_id'])){

                $modelEUS=&WYSIJA::get("email_user_stat","model");
                $modelEUS->update(array("status"=>-1),array("user_id"=>$bouncedata['user_id'],"email_id"=>(int) $email_id));
                
            }
        }
        $this->bounceMessages = array();
    }
  }
  function _handleRule(&$oneRule){
      $regex = $oneRule['regex'];
    if(empty($regex)) return false;

  $analyseText = '';
  if(isset($oneRule['executed_on']['senderinfo'])) $analyseText .= ' '.$this->_message->header->sender_name.$this->_message->header->sender_email;
  if(isset($oneRule['executed_on']['subject'])) $analyseText .= ' '.$this->_message->subject;
  if(isset($oneRule['executed_on']['body'])){
    if(!empty($this->_message->html)) $analyseText .= ' '.$this->_message->html;
    if(!empty($this->_message->text)) $analyseText .= ' '.$this->_message->text;
  }

    if(!preg_match('#'.$regex.'#is',$analyseText)) return false;
    $message = $oneRule['name'];
  $message .= $this->_actionuser($oneRule);
  $message .= $this->_actionmessage($oneRule);
    $this->_display($message,true);
    return true;
  }
  function _actionuser(&$oneRule){
        $message = '';
        if(empty($this->_message->user_id)){
                $message .= 'user not identified';
                if(!empty($this->_message->subemail)) $message .= ' ( '.$this->_message->subemail.' ) ';
                return $message;
        }
        if(isset($oneRule['action_user']) && in_array($oneRule['action_user'],array("unsub"))){
                $status = $this->subClass->getSubscriptionStatus($this->_message->user_id);
                if(empty($this->_message->subemail)){
                    $currentUser = $this->subClass->getObject($this->_message->user_id);
                    if(!empty($currentUser->email)) $this->_message->subemail = $currentUser->email;
                }
            }
        if(empty($this->_message->subemail)) $this->_message->subemail = $this->_message->user_id;


          if(isset($oneRule['action_user_stats'])){

            if(!empty($this->_message->email_id)){
              if(empty($this->bounceMessages[$this->_message->email_id]['nbbounces'])){
                $this->bounceMessages[$this->_message->email_id] = array();
                $this->bounceMessages[$this->_message->email_id]['nbbounces'] = 1;
              }else{
                $this->bounceMessages[$this->_message->email_id]['nbbounces']++ ;
              }
              if(!empty($this->_message->user_id) AND ((isset($oneRule['action_user']) && $oneRule['action_user']!='delete') || !isset($oneRule['action_user']) )){

                $this->bounceMessages[$this->_message->email_id]['user_id'][] = intval($this->_message->user_id);
              }
            }
          }





        if(isset($oneRule['action_user'])){
            switch($oneRule['action_user']){
                case 'delete'://1 -Delete user
                    $message .= ', user '.$this->_message->subemail.' deleted';
                    $this->deletedUsers[] = intval($this->_message->user_id);
                    break;
                case 'unsub'://2-Unsubscribe user

                    $message .= ', user '.$this->_message->subemail.' unsubscribed';
                    $this->unsubscribedUsers[]=$this->_message->user_id;
                    break;
                default:

                    if(strpos($oneRule['action_user'],"unsub_")!==false){
                        $listid=(int)str_replace("unsub_","",$oneRule['action_user']);
                        $message .= ', user '.$this->_message->subemail.' unsubscribed';
                        $this->unsubscribedUsers[]=$this->_message->user_id;
                        $this->addtolistUsers[$listid][]=$this->_message->user_id;
                        $message .= ', user '.$this->_message->subemail.' added to list "' . $this->listdetails[$listid] . '"';
                    }
            }
        }


        if(!empty($oneRule['action_user_min']) && $oneRule['action_user_min']>1){

          $modelEUS=&WYSIJA::get("email_user_stat","model");
          $res=$modelEUS->query("get_row",'SELECT COUNT(email_id) as count FROM [wysija]'.$modelEUS->table_name.' WHERE status = -1 AND user_id = '.$this->_message->user_id);
          $nb = intval($res['count']) + 1;
          if($nb < $oneRule['action_user_min']){
            $message .= ', '.sprintf(__('We received %1$s messages from the user %2$s',WYSIJA),$nb,$this->_message->subemail).', '.sprintf(__('Actions will be executed after %1$s messages',WYSIJA),$oneRule['action_user_min']);
            return $message;
          }
        }



        

      return $message;
  }
    function _actionmessage(&$oneRule){
        $message = '';

        if(isset($oneRule['action_message']['save']) && !empty($this->_message->user_id)){

            $data = array();
            $data[] = 'SUBJECT::'.$this->_message->subject;
            if(!empty($this->_message->html)) $data[] = 'HTML_VERSION::'.htmlentities($this->_message->html);
            if(!empty($this->_message->text)) $data[] = 'TEXT_VERSION::'.nl2br(htmlentities($this->_message->text));
            $data[] = 'REPLYTO_ADDRESS::'.$this->_message->header->reply_to_name. ' ( '.$this->_message->header->reply_to_email.' )';
            $data[] = 'FROM_ADDRESS::'.$this->_message->header->from_name. ' ( '.$this->_message->header->from_email.' )';
            $data[] = print_r($this->_message->headerinfo,true);
            $this->historyClass->insert($this->_message->user_id,'bounce',$data,@$this->_message->email_id);
            $message .= ', message saved (user '.$this->_message->user_id.')';
        }
        if(isset($oneRule['forward'])){
            if(isset($oneRule['action_message_forwardto']) && !empty($oneRule['action_message_forwardto']) && trim($oneRule['action_message_forwardto']) !=trim($this->config->getValue('bounce_email'))){

                $this->mailer->clearAll();
                $this->mailer->Subject = 'BOUNCE FORWARD : '.$this->_message->subject;
                $this->mailer->AddAddress($oneRule['action_message_forwardto']);
                if(!empty($this->_message->html)){
                $this->mailer->IsHTML(true);
                $this->mailer->Body = $this->_message->html;
                if(!empty($this->_message->text)) $this->mailer->Body .= '<br/><br/>-------<br/>'.nl2br($this->_message->text);
                }else{
                    $this->mailer->IsHTML(false);
                    $this->mailer->Body = $this->_message->text;
                }


                $this->mailer->Body .= print_r($this->_message->headerinfo,true);
                $this->mailer->AddReplyTo($this->_message->header->reply_to_email,$this->_message->header->reply_to_name);
                $this->mailer->setFrom($this->_message->header->from_email,$this->_message->header->from_name);
                if($this->mailer->send()){
                    $message .= ', forwarded to '.$oneRule['action_message_forwardto'];
                }else{
                    $message .= ', error forwarding to '.$oneRule['action_message_forwardto'];
                }
            }else{

                unset($oneRule['action_message']['delete']);
            }
        }
        if(isset($oneRule['action_message']['delete'])){
            $message .= ', message deleted';
            $this->deleteMessage($this->_message->messageNB);
        }

        return $message;
    }
  function _decodeAddressimap($type){
    $address = $type.'address';
    $name = $type.'_name';
    $email = $type.'_email';
    if(empty($this->_message->$type)) return false;
    $var = $this->_message->$type;
    if(!empty($this->_message->$address)){
      $this->_message->header->$name = $this->_message->$address;
    }else{
      $this->_message->header->$name = $var[0]->personal;
    }
    $this->_message->header->$email = $var[0]->mailbox.'@'.@$var[0]->host;
    return true;
  }

  
  function _display($message,$status = '',$num = ''){
    $this->messages[] = $message;
    if(!$this->report) return;
    $color = $status ? 'black' : 'blue';
    if(!empty($num)) echo '<br/>'.$num.' : ';
    else echo '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo '<font style="font-family: Arial;" color="'.$color.'">'.$message.'</font>';
  if(function_exists('ob_flush')) @ob_flush();
  @flush();
  }
    function _decodeHeader($input){

        $input = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $input);
      $this->charset = false;

        while (preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $input, $matches)) {
            $encoded  = $matches[1];
            $charset  = $matches[2];
            $encoding = $matches[3];
            $text     = $matches[4];
            switch (strtolower($encoding)) {
                case 'b':
                    $text = base64_decode($text);
                    break;
                case 'q':
                    $text = str_replace('_', ' ', $text);
                    preg_match_all('/=([a-f0-9]{2})/i', $text, $matches);
                    foreach($matches[1] as $value)
                        $text = str_replace('='.$value, chr(hexdec($value)), $text);
                    break;
            }
            $this->charset = $charset;
            $input = str_replace($encoded, $text, $input);
        }
        return $input;
    }
     function _explodeBody($struct, $path="0",$inline=0){
      $allParts = array();
        if(empty($struct->parts)) return $allParts;
    $c=0; //counts real content
        foreach ($struct->parts as $part){
          if ($part->type==1){

              if ($part->subtype=="MIXED"){ //Mixed:
              $path = $this->_incPath($path,1); //refreshing current path
              $newpath = $path.".0"; //create a new path-id (ex.:2.0)
              $allParts = array_merge($this->_explodeBody($part,$newpath),$allParts); //fetch new parts
              }
              else{ //Alternativ / rfc / signed
              $newpath = $this->_incPath($path, 1);
              $path = $this->_incPath($path,1);
              $allParts = array_merge($this->_explodeBody($part,$newpath,1),$allParts);
              }
          }
          else {
              $c++;

              if ($c==1 && $inline){
              $path = $path.".0";
              }

              $path = $this->_incPath($path, 1);

              $allParts[$path] = $part;
          }
        }
        return $allParts;
    }

    function _incPath($path, $inc){
        $newpath="";
        $path_elements = explode(".",$path);
        $limit = count($path_elements);
        for($i=0;$i < $limit;$i++){
          if($i == $limit-1){ //last element
              $newpath .= $path_elements[$i]+$inc; // new Part-Number
          }
          else{
              $newpath .= $path_elements[$i]."."; //rebuild "1.2.2"-Chronology
          }
        }
        return $newpath;
    }
  function _decodeContent($content,$structure){
    $encoding = $structure->encoding;

        if($encoding == 2) $content = imap_binary($content);
        elseif($encoding == 3) $content = imap_base64($content);
        elseif($encoding == 4) $content = imap_qprint($content);


        $charset = $this->_getMailParam($structure,'charset');
        return $content;
  }
    function _getMailParam($params,$name){
      $searchIn = array();
    if ($params->ifparameters)
      $searchIn=array_merge($searchIn,$params->parameters);
    if ($params->ifdparameters)
      $searchIn=array_merge($searchIn,$params->dparameters);
    if(empty($searchIn)) return false;
    foreach($searchIn as $num => $values)
    {
            if (strtolower($values->attribute) == $name)
      {
                return $values->value;
      }
    }
    }
  function getErrors(){
    $return = array();
    if($this->usepear){

    }else{
      $alerts = imap_alerts();
      $errors = imap_errors();
      if(!empty($alerts)) $return = array_merge($return,$alerts);
      if(!empty($errors)) $return = array_merge($return,$errors);
    }
    return $return;
  }
}