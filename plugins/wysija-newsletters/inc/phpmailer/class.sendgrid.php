<?php
/**
 * @copyright	Copyright (C) 2009-2011 ACYBA SARL - All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
class acymailingSendgrid {
	/**
	 * Ressources : Connection to the elasticemail server
	 */
	var $conn;

	/**
	 * String : Last error...
	 */
	var $error;
	var $Username = '';
	var $Password = '';

	/* Function which permit to send an email based on the object's values.
	 * First, we do the test if we have enough credit to send emails.
	 */
	function sendMail(& $object) {
            $url = 'http://sendgrid.com/';

            $to = array_merge(array($object->to[0][0]), $object->cc, $object->bcc);
            /*foreach($to as $oneRecipient){
                    $data .= '&to[]='.urlencode($object->AddrFormat($oneRecipient).";");
            }*/
            $params = array(
            'api_user' => $this->Username,
            'api_key' => $this->Password,
            'to' => array_filter($to),
            'replyto'=> $object->ReplyTo[0][0],
            'from' => $object->From,
            'fromname' =>  $object->FromName,
            );

            if(!empty($object->ReplyTo[0][1])) $params['replytoname']= $object->ReplyTo[0][1];

            if (!empty ($object->Subject)) $params['subject']= $object->Subject;

            if (!empty($object->Sender)) $params['sender']=$object->Sender;

            if (!empty ($object->sendHTML) || !empty($object->AltBody)){
                $params['html']= $object->Body;
                if (!empty ($object->AltBody)) $params['text']=$object->AltBody;
            }else{
                $params['text']=$object->Body;
            }


            if ($object->attachment) {
                $ArrayID = array ();
                foreach ($object->attachment as $oneAttachment) {
                    $params['files'][$oneAttachment[2]]=$oneAttachment[0];
                }
            }

            $header=array();
            $header['Content-Type']='application/x-www-form-urlencoded';
            $header['Connection']='Keep-Alive';

            $params['headers']=json_encode($header);
            $request = $url.'api/mail.send.json';

            // Generate curl request
            $session = curl_init($request);

            // Tell curl to use HTTP POST
            curl_setopt ($session, CURLOPT_POST, true);

            // Tell curl that this is the body of the POST
            curl_setopt ($session, CURLOPT_POSTFIELDS, http_build_query($params));

            // Tell curl not to return headers, but do return the response
            curl_setopt($session, CURLOPT_HEADER, false);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

            // obtain response
            $result = curl_exec($session);
            curl_close($session);

            // print everything out
//            print_r($result);
//            exit;

            //We take the last value of the server's response which correspond of the file's ID.
            $result=json_decode($result);

            //If the ID is correct and we have no Errors
            $this->error='';
            if(isset($result->message) && $result->message=='error'){
                foreach($result->errors as $msgError)
                    $this->error .= $msgError."\n\r";
                return false;
            } else {
                return true;
            }
	}

}