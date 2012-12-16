<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_control_front_email extends WYSIJA_control_front{
    var $model='email';
    var $view='email';

    function WYSIJA_control_front_email(){
        parent::WYSIJA_control_front();
    }

    function view(){
        $data=array();
        header('Content-type:text/html; charset=utf-8');
        $emailM=&WYSIJA::get('email','model');
        $configM=&WYSIJA::get('config','model');
        $data=$emailM->getOne(false,array('email_id'=>(int)$_REQUEST['email_id']));

        $this->title=sprintf(__('Online version of newsletter: %1$s',WYSIJA),$data['subject']);

        $find=array();
        $replace=array();

        $find[]='[unsubscribe_linklabel]';
        $replace[]=$configM->getValue('unsubscribe_linkname');


        $this->subtitle=str_replace($find,$replace,$data['body']);
        //$this->subtitle=$data['body'];

        $emailH=&WYSIJA::get('email','helper');
        $this->subtitle=$emailH->stripPersonalLinks($this->subtitle);
        do_action( 'wysija_preview', array(&$this));
        echo $this->subtitle;

        exit;
    }

}