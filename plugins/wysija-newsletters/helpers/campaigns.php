<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_campaigns extends WYSIJA_object{
        
    function WYSIJA_help_campaigns(){
    }
    function saveParameters($email_id, $key, $value)
    {

        $modelEmail =& WYSIJA::get('email', 'model');
        $email = $modelEmail->getOne('params', array('email_id' => $email_id));
        $params = $email['params'];
        if(!is_array($params)) {
            $params = array();
        }

        if(array_key_exists($key, $params)) {
            $params[$key] = $value;
        } else {
            $params = array_merge($params, array($key => $value));
        }

        return $modelEmail->update(array('params' => $params), array('email_id' => $email_id));
    }
    function getParameters($email_id, $key = null) {

        $modelEmail =& WYSIJA::get('email', 'model');
        $email = $modelEmail->getOne('params', array('email_id' => $email_id));
        $params = $email['params'];
        if($key === null) {
            return $params;
        } else {
            if(!is_array($params) or array_key_exists($key, $params) === false) {
                return false;
            } else {
                return $params[$key];
            }
        }
    }
}