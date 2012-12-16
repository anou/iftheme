<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_queue extends WYSIJA_object{
	var $email_id = 0;
	var $report = true;
	var $send_limit = 0;
	var $finish = false;
	var $error = false;
	var $nbprocess = 0;
	var $start = 0;
	var $stoptime = 0;
	var $successSend =0;
	var $errorSend=0;
	var $consecutiveError=0;
	var $messages = array();
	var $pause = 0;
	var $config;
 	var $listsubClass;
  	var $subClass;
	function WYSIJA_help_queue(){
            $this->config = &WYSIJA::get("config","model");
            $this->subClass = &WYSIJA::get("user","model");//acymailing_get('class.sub');
            $this->listsubClass = &WYSIJA::get("user_list","model");//acymailing_get('class.listsub');
            $this->listsubClass->checkAccess = false;
            $this->listsubClass->sendNotif = false;
            $this->listsubClass->sendConf = false;
            $this->send_limit = (int) $this->config->getValue("sending_emails_number");
            if(isset($_REQUEST['totalsend'])){
                $this->send_limit = (int) $_REQUEST['totalsend']-$_REQUEST['alreadysent'];
            }
            @ini_set('max_execution_time',0);
            @ini_set('default_socket_timeout',10);
            @ignore_user_abort(true);
            $timelimit = ini_get('max_execution_time');
            if(!empty($timelimit)){
                    $this->stoptime = time()+$timelimit-4;
            }
	}
	function process($emailid=false,$user_id=false){
            if($emailid)    $this->email_id=$emailid;
                $queueClass = &WYSIJA::get("queue","model");
		$queueElements = $queueClass->getReady($this->send_limit,$this->email_id,$user_id);
                $this->total=count($queueElements);
                $this->start=0;
		if(empty($queueElements)){
			
                        $queueElements = $queueClass->getDelayed($this->email_id);

                        if(empty($queueElements)){
                            $this->clear();
                        }else{
                            if($this->report){
                                $disp = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" />';
                                $disp .= '<style>body{font-size:12px;font-family: Arial,Helvetica,sans-serif;}</style></head><body>';
                                $disp.= "<div style='background-color : white;border : 1px solid grey; padding : 3px;font-size:14px'>";
                                $disp.= "<span id='divpauseinfo' style='padding:10px;margin:5px;font-size:16px;font-weight:bold;display:none;background-color:black;color:white;'> </span>";
                                $disp.= sprintf(__('There are %1$s delayed email(s)',WYSIJA),count($queueElements));
                                $disp.= '</div>';
                                foreach($queueElements as $element){
                                    $disp.= "<div id='divinfo'>".sprintf(__('Email will be sent to %1$s at %2$s',WYSIJA),'<b>'.$element['email'].'</b>','<em>'.date_i18n(get_option('date_format').' H:i',$element['send_at']).'</em>')." </div>";
                                }
                                echo $disp;
                            }
                        }
                        $this->finish = true;
			return true;
		}
		if($this->report){
			if(!headers_sent() AND ob_get_level() > 0){
				ob_end_flush();
			}
			$disp = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" />';
			$disp .= '<title>'.addslashes(__("Send Process",WYSIJA)).'</title>';
			$disp .= '<style>body{font-size:12px;font-family: Arial,Helvetica,sans-serif;}</style></head><body>';
			$disp.= "<div style='background-color : white;border : 1px solid grey; padding : 3px;font-size:14px'>";
			$disp.= "<span id='divpauseinfo' style='padding:10px;margin:5px;font-size:16px;font-weight:bold;display:none;background-color:black;color:white;'> </span>";
			$disp.= __("Send Process",WYSIJA).': <span id="counter"/>'.$this->start.'</span> / '. $this->total;
			$disp.= '</div>';
			$disp.= "<div id='divinfo' style='display:none; position:fixed; bottom:3px;left:3px;background-color : white; border : 1px solid grey; padding : 3px;'> </div>";
			$disp .= '<br /><br />';
                        $url = 'admin.php?page=wysija_campaigns&action=send_test_editor&emailid='.$this->email_id.'&totalsend='.$this->total.'&alreadysent=';

			$disp.= '<script type="text/javascript" language="javascript">';
			$disp.= 'var mycounter = document.getElementById("counter");';
			$disp.= 'var divinfo = document.getElementById("divinfo");
					var divpauseinfo = document.getElementById("divpauseinfo");
					function setInfo(message){ divinfo.style.display = \'block\';divinfo.innerHTML=message; }
					function setPauseInfo(nbpause){ divpauseinfo.style.display = \'\';divpauseinfo.innerHTML=nbpause;}
					function setCounter(val){ mycounter.innerHTML=val;}
					var scriptpause = '.intval($this->pause).';
					function handlePause(){
						setPauseInfo(scriptpause);
						if(scriptpause > 0){
							scriptpause = scriptpause - 1;
							setTimeout(\'handlePause()\',1000);
						}else{
							document.location.href=\''.$url.'\'+mycounter.innerHTML;
						}
					}
					</script>';
			echo $disp;
			if(function_exists('ob_flush')) @ob_flush();
			@flush();
		}//endifreport
                $mailHelper=&WYSIJA::get("mailer","helper");

		$mailHelper->report = false;

                $mailHelper->SMTPKeepAlive = true;
		$queueDelete = array();
		$queueUpdate = array();
		$statsAdd = array();
		$actionSubscriber = array();
		$maxTry = (int) $this->config->getValue('queue_try',3);
		$currentMail = $this->start;
		$this->nbprocess = 0;
		if(count($queueElements) < $this->send_limit){
			$this->finish = true;
		}
                WYSIJA::log('helpers -> Queue ->process',$queueElements,'queue_process');
		foreach($queueElements as $oneQueue){
			$currentMail++; $this->nbprocess++;
			if($this->report){
				echo '<script type="text/javascript" language="javascript">setCounter('. $currentMail .')</script>';
				if(function_exists('ob_flush')) @ob_flush();
				@flush();
			}
                        WYSIJA::log('helpers -> Queue ->process ->sendOne',array('email_id'=>$oneQueue->email_id,'oneQueue'=>$oneQueue),'queue_process');
			$result = $mailHelper->sendOne($oneQueue->email_id,$oneQueue);
			$queueDeleteOk = true;
			$otherMessage = '';
			if($result){
				$this->successSend ++;
				$this->consecutiveError = 0;
				$queueDelete[$oneQueue->email_id][] = $oneQueue->user_id;
				$statsAdd[$oneQueue->email_id][1][(int)$mailHelper->sendHTML][] = $oneQueue->user_id;
				$queueDeleteOk = $this->_deleteQueue($queueDelete);
                                WYSIJA::log('helpers -> Queue ->process ->sendOne resultOK(queue delete)',$queueDelete,'queue_process');
				$queueDelete = array();
				if($this->nbprocess%10 == 0){
					$this->_statsAdd($statsAdd);
					$this->_queueUpdate($queueUpdate);
					$statsAdd = array();
					$queueUpdate = array();
				}
			}else{
                                $this->errorSend ++;
				$newtry = false;
				if(in_array($mailHelper->errorNumber,$mailHelper->errorNewTry)){
					if(empty($maxTry) OR $oneQueue->number_try < $maxTry-1){
						$newtry = true;
						$otherMessage = sprintf(__("Next try in %s minutes.",WYSIJA),round($this->config->getValue('queue_delay')/60));
					}
					if($mailHelper->errorNumber == 1) $this->consecutiveError ++;
                                        if($this->consecutiveError == 2) sleep(1);
				}
				if(!$newtry){
					$queueDelete[$oneQueue->email_id][] = $oneQueue->user_id;
					$statsAdd[$oneQueue->email_id][0][(int)@$mailHelper->sendHTML][] = $oneQueue->user_id;
					if($mailHelper->errorNumber == 1 AND $this->config->getValue('bounce_action_maxtry')){
						$queueDeleteOk = $this->_deleteQueue($queueDelete);
						$queueDelete = array();
						$otherMessage .= $this->_subscriberAction($oneQueue->user_id);
					}
				}else{
					$queueUpdate[$oneQueue->email_id][] = $oneQueue->user_id;
				}
                                WYSIJA::log('helpers -> Queue ->process ->sendOne resultFAILED(queue update)',$queueUpdate,'queue_process');
			}

                        $messageOnScreen = $mailHelper->reportMessage;
			if(!empty($otherMessage)) $messageOnScreen .= ' => '.$otherMessage;
			$this->_display($messageOnScreen,$result,$currentMail);
			if(!$queueDeleteOk){
				$this->finish = true;
				break;
			}
			if(!empty($this->stoptime) AND $this->stoptime < time()){
				$this->_display(__("Process refreshed to avoid a time limit.",WYSIJA));
				if($this->nbprocess < count($queueElements)) $this->finish = false;
				break;
			}
			if($this->consecutiveError > 2 AND $this->successSend>3){
				$this->_display(__("Process refreshed to avoid a possible loss of connection.",WYSIJA));
				break;
			}
			if($this->consecutiveError > 5 OR connection_aborted()){
				$this->finish = true;
				break;
			}
		}
		$this->_deleteQueue($queueDelete);
		$this->_statsAdd($statsAdd);
		$this->_queueUpdate($queueUpdate);

                $mailHelper->SmtpClose();
		if(!empty($this->total) AND $currentMail >= $this->total){
			$this->finish = true;
		}
		if($this->consecutiveError>5){
			$this->_handleError();
			return false;
		}
		if($this->report && !$this->finish){
			echo '<script type="text/javascript" language="javascript">handlePause();</script>';

		}
		if($this->report){
			echo "</body></html>";
                        exit;
		}
		return true;
	}
	function _deleteQueue($queueDelete){
		if(empty($queueDelete)) return true;
		$status = true;
                $modelQ=&WYSIJA::get("queue","model");
		foreach($queueDelete as $email_id => $subscribers){
			$nbsub = count($subscribers);


                        $realquery='DELETE FROM `[wysija]queue` WHERE email_id = '.intval($email_id).' AND user_id IN ('.implode(',',$subscribers).') LIMIT '.$nbsub;
                        WYSIJA::log('helpers -> Queue ->process ->deleteQueue',$realquery,'queue_process');
                        $res=$modelQ->query($realquery);
			if(!$res){
				$status = false;
                                WYSIJA::log('helpers -> Queue ->process ->deleteQueue failed',true,'queue_process');

			}else{
                                WYSIJA::log('deleting queue ok',array('email_id'=>$email_id,'subscribers'=>$subscribers),'queue_process');
                                $nbdeleted = $modelQ->getAffectedRows();
				if($nbdeleted != $nbsub){
					$status = false;
					$this->_display(__("Newsletters are already being sent. Your latest newsletter will be sent afterwards.",WYSIJA));
				}
			}
		}

		return $status;
	}
	function _statsAdd($statsAdd){
		$time = time();
		if(empty($statsAdd)) return true;
                $modelEUS=&WYSIJA::get("email_user_stat","model");
		foreach($statsAdd as $email_id => $infos){
			$email_id = intval($email_id);
			foreach($infos as $status => $infosSub){
				foreach($infosSub as $html => $subscribers){
					if(!$status) $status=-2;
                                        else $status=0;
                                        $query = 'INSERT IGNORE INTO `[wysija]email_user_stat` (email_id,user_id,status,sent_at) VALUES ('.$email_id.','.implode(','.$status.','.$time.'),('.$email_id.',',$subscribers).','.$status.','.$time.')';
					$modelEUS->query($query);
				}
			}
		}
	}
	function _queueUpdate($queueUpdate){
		if(empty($queueUpdate)) return true;
		$delay = $this->config->getValue('queue_delay',3600);
                $modelQ=&WYSIJA::get("queue","model");
		foreach($queueUpdate as $email_id => $subscribers){
			$query = 'UPDATE `[wysija]queue` SET send_at = send_at + '.$delay.', number_try = number_try +1 WHERE email_id = '.$email_id.' AND user_id IN ('.implode(',',$subscribers).')';
			$modelQ->query($query);
		}
	}
	function _handleError(){
		$this->finish = true;
		$message = __("The Send Process stopped because there are too many errors.",WYSIJA);
		$message .= '<br/>';
		$message .= __("We kept all non delivered emails in the queue, so you will be able to resume the send process later.",WYSIJA);
		$message .= '<br/>';
		if($this->report){
			if(empty($this->successSend) AND empty($this->start)){
				$message .= __("Please verify your mail configuration and make sure you can send a test of this email.",WYSIJA);
				$message .= '<br/>';
				$message .= __("If you recently, successfully, sent a lot of emails, those errors may also be due to your server limitations.",WYSIJA);
			}else{
				$message .= __("Your server apparently refuses to send more emails.",WYSIJA);
				$message .= '<br/>';
				
			}
		}
		$this->_display($message);
	}
	function _display($message,$status = '',$num = ''){
		$this->messages[] = strip_tags($message);
		if(!$this->report) return;
		if(!empty($num)){
			$color = $status ? 'green' : 'red';
			echo '<br/>'.$num.' : <font color="'.$color.'">'.$message.'</font>';
		}else{
			echo '<script type="text/javascript" language="javascript">setInfo(\''. addslashes($message) .'\')</script>';
		}
		if(function_exists('ob_flush')) @ob_flush();
		@flush();
	}
	function _subscriberAction($subid){
            return '';
            
                if($this->config->getValue('bounce_action_maxtry') == 'delete'){
			$this->subClass->delete($subid);
			return ' user '.$subid.' deleted';
		}
                $listId = 0;
                if(in_array($this->config->getValue('bounce_action_maxtry'),array('sub','remove','unsub'))){
                        $status = $this->subClass->getSubscriptionStatus($subid);
                }
                $message = '';
                $modelU=&WYSIJA::get("user","model");
		switch($this->config->getValue('bounce_action_maxtry')){
			case 'sub' :
				$listId = $this->config->getValue('bounce_action_lists_maxtry');
				if(!empty($listId)){
					$message .= ' user '.$subid.' subscribed to '.$listId;
		            if(empty($status[$listId])){
						$this->listsubClass->addSubscription($subid,array('1' => array($listId)));
		            }elseif($status[$listId]->status != 1){
					 	$this->listsubClass->updateSubscription($subid,array('1' => array($listId)));
		            }
				}
			case 'remove' :
				$unsubLists = array_diff(array_keys($status),array($listId));
				if(!empty($unsubLists)){
					$message .= ' | user '.$subid.' removed from lists '.implode(',',$unsubLists);
					$this->listsubClass->removeSubscription($subid,$unsubLists);
				}else{
					$message .= ' | user '.$subid.' not subscribed';
				}
				break;
			case 'unsub' :
				$unsubLists = array_diff(array_keys($status),array($listId));
				if(!empty($unsubLists)){
					$message .= ' | user '.$subid.' unsubscribed from lists '.implode(',',$unsubLists);
					$this->listsubClass->updateSubscription($subid,array('-1' => $unsubLists));
				}else{
					$message .= ' | user '.$subid.' not subscribed';
				}
				break;
			case 'delete' :
				$message .= ' | user '.$subid.' deleted';

                                $modelU->delete($subid);
                                $modelU->reset();
				break;
			case 'block' :
				$message .= ' | user '.$subid.' blocked';
				$modelU->query('UPDATE `[wysija]user` SET `enabled` = 0 WHERE `user_id` = '.intval($subid));
				$modelU->query('DELETE FROM `[wysija]queue` WHERE `user_id` = '.intval($subid));
				break;
	      }
		return $message;
	}

        function clear(){
            $configM=&WYSIJA::get('config','model');
            $modelQ=&WYSIJA::get('queue','model');

            $realquery='DELETE a.* FROM `[wysija]queue` as a LEFT JOIN `[wysija]user` as b on a.user_id = b.user_id WHERE b.status< '.$configM->getValue('confirm_dbleoptin');
            $modelQ->query($realquery);

            $realquery='DELETE a.* FROM `[wysija]queue` as a LEFT JOIN `[wysija]email` as b on a.email_id = b.email_id WHERE b.email_id IS NULL';
            $modelQ->query($realquery);

            $realquery='DELETE a.* FROM `[wysija]queue` as a LEFT JOIN `[wysija]user` as b on a.user_id = b.user_id WHERE b.user_id IS NULL';
            $modelQ->query($realquery);

            $conditions=array();
            $conditions["less"]=array('send_at'=>time()-(3600*48));
            if($modelQ->exists($conditions)){

                $configM->save(array('queue_sends_slow'=>1));
            }
            return true;
        }
}
