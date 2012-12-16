<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_stats extends WYSIJA_object{
   function sendDailyReport(){

        $onedayago=time()-1;
        $onedayago=$onedayago-(3600*24);
        $modelEUS=&WYSIJA::get("email_user_stat","model");
        $query="SELECT COUNT(".$modelEUS->getPk().") as count, status FROM `[wysija]".$modelEUS->table_name."` 
            WHERE sent_at>".$onedayago."
                GROUP BY status";
        $statuscount=$modelEUS->query("get_res",$query);
        $modelUH=&WYSIJA::get("user_history","model");
        $query="SELECT B.user_id,B.email FROM `[wysija]".$modelUH->table_name."`  as A JOIN `[wysija]user` as B on A.user_id=B.user_id
            WHERE A.executed_at>".$onedayago." AND A.type='bounce'";
        $details=$modelUH->query("get_res",$query);
        $total=0;
        foreach($statuscount as &$count){
            switch($count['status']){
                case "-1":
                    $count['status']=__('bounced',WYSIJA);
                    break;
                case "0":
                    $count['status']=__('unopened',WYSIJA);
                    break;
                case "1":
                    $count['status']=__('opened',WYSIJA);
                    break;
                case "2":
                    $count['status']=__('clicked',WYSIJA);
                    break;
                case "3":
                    $count['status']=__('unsubscribed',WYSIJA);
                    break;
            }
            $total=$total+$count['count'];
        }
        if((int)$total<=0) return;
        $html="<h2>".__("Today's statistics",WYSIJA)."</h2>";
        $html.="<h3>".sprintf(__('Today you have sent %1$s emails',WYSIJA),$total);
        foreach($statuscount as $counting){
            $html.=sprintf(__(', %1$s of which were %2$s',WYSIJA),$counting['count'],$counting['status']);
        }
        $html.=".</h3>";
        if(count($details)>0){
            $html.="<h2>".sprintf(__('Here is the list of bounced emails.',WYSIJA),$total)."</h2>";
            foreach($details as $email){
                $html.="<h4>".$email['email']."</h4>";
            }
        }
        $html.="<p>".__("Cheers, your Wysija Newsletter Plugin",WYSIJA)."</p>";
        $modelC=&WYSIJA::get("config","model");
        $mailer=&WYSIJA::get("mailer","helper");
        $mailer->testemail=true;

        $res=$mailer->sendSimple($modelC->getValue('emails_notified'),__("Your daily newsletter stats",WYSIJA),$html);
   }
   
   function getDomainStats(){
        $data=array();
        $modelConfig=&WYSIJA::get("config","model");
        $url=admin_url('admin.php');
        $helperToolbox=&WYSIJA::get("toolbox","helper");
        $data['domain_name']=$helperToolbox->_make_domain_name($url);
        $data['url']=$url;
        $data[uniqid()]=uniqid('WYSIJA');
        $data['installed']=$modelConfig->getValue("installed_time");
        $data['contacts']=$modelConfig->getValue("emails_notified");
        $data['wysijaversion']=WYSIJA::get_version();
        $data['WPversion']=get_bloginfo('version');
        $data['sending_method']=$modelConfig->getValue("sending_method");
        if($data['sending_method']=="smtp") $data['smtp_host']=$modelConfig->getValue("smtp_host");
        $modelList=&WYSIJA::get("list","model");
        $data['number_list']=$modelList->count();
        $modelNL=&WYSIJA::get("email","model");
        $data['number_sent_nl']=$modelNL->count(array("status"=>2));
        $data['number_sub']=$modelConfig->getValue("total_subscribers");
        $data['optin_status']=$modelConfig->getValue("confirm_dbleoptin");
        $data['list_plugins']=unserialize(get_option("active_plugins"));
        $data=base64_encode(serialize($data));
        return $data;
    }
   function share(){
       $data=$this->getDomainStats();
        if(!$js) {
            WYSIJA::update_option("wysijey",$data);
        }
        $res['domain_name']=$data;
        $res['nocontact']=false;
        $httpHelp=&WYSIJA::get("http","helper");
        $jsonResult = $httpHelp->request('http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=customer&action=shareData&data='.$data);
        if($jsonResult){
        }
   }
}