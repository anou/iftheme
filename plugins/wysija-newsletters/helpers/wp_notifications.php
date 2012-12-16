<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_wp_notifications extends WYSIJA_object{
    function WYSIJA_help_wp_notifications(){
        add_filter( 'wp_mail', array( $this, 'wp_notifications_ourway' ),10,1 );
        add_filter( 'phpmailer_init', array( $this, 'wp_notifications_cancelled' ) );
    }

    function wp_notifications_ourway($var){
        $modelC=&WYSIJA::get('config','model');
        if(!$modelC->getValue('wp_notifications')) return $var;
        $modelEmail =& WYSIJA::get('email', 'model');
        $email = $modelEmail->getOne(false, array('status'=>99,'type' => 3)); // WARNING: This id matches the confirmation email on Wysija.com
        $hMailer=&WYSIJA::get('mailer','helper');
        $var['message'] = nl2br(strip_tags($var['message'], '<em><span><b><strong><i><h1><h2><h3><a>'));
        $hMailer= new WYSIJA_help_mailer();
        $hMailer->Subject = str_replace('[notifications]',$var['subject'] , $email['subject']);
        $hMailer->Body = str_replace('[content]',$var['message'] , $email['body']);
        $hMailer->IsHTML(true);
        $hMailer->sendHTML = true;
        $hMailer->AddAddress($var['to']);
        $result=$hMailer->send();

    }
    function wp_notifications_cancelled(&$phpmailer){
        $modelC=&WYSIJA::get('config','model');
        if(!$modelC->getValue('wp_notifications')) return $phpmailer;
        $mailobj=new WYSIJA_sendfalse();
        $phpmailer=$mailobj;
        return $phpmailer;
    }
}