<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_control_front_subscribers extends WYSIJA_control_front{
    var $model="user";
    var $view="widget_nl";

    function WYSIJA_control_front_subscribers(){
        parent::WYSIJA_control_front();
        if(isset($_REQUEST['message_success'])){
            $this->messages['insert'][true]=$_REQUEST['message_success'];
        }else{
            $this->messages['insert'][true]=__("User has been inserted.",WYSIJA);
        }

        $this->messages['insert'][false]=__("User has not been inserted.",WYSIJA);
        $this->messages['update'][true]=__("User has been updated.",WYSIJA);
        $this->messages['update'][false]=__("User has not been updated.",WYSIJA);
    }

   function save(){
        $config=&WYSIJA::get('config','model');

        if(!$config->getValue("allow_no_js")){
            $this->notice(__("Subscription without JavaScript is disabled.",WYSIJA));
            return false;
        }


        if(isset($_REQUEST['wysija']['user_list']['list_id'])){
            $_REQUEST['wysija']['user_list']['list_ids']=$_REQUEST['wysija']['user_list']['list_id'];
            unset($_REQUEST['wysija']['user_list']['list_id']);
        }elseif(isset($_REQUEST['wysija']['user_list']['list_ids'])){
            $_REQUEST['wysija']['user_list']['list_ids']=explode(',',$_REQUEST['wysija']['user_list']['list_ids']);
        }

        $data=$_REQUEST['wysija'];
        unset($_REQUEST['wysija']);

        foreach($_REQUEST as $key => $val){
            if(!isset($data[$key]))  $data[$key]=$val;
        }

        $helperUser=&WYSIJA::get('user','helper');
        if(!$helperUser->checkData($data))return false;

        $helperUser->addSubscriber($data);

        return true;
    }


    function wysija_outter() {

        if(isset($_REQUEST['encodedForm'])){
            $encodedForm=json_decode(base64_decode(urldecode($_REQUEST['encodedForm'])));
        }
        else{
            if(isset($_REQUEST['fullWysijaForm'])){
                $encodedForm=json_decode(base64_decode(urldecode($_REQUEST['fullWysijaForm'])));
            }else{
                if(isset($_REQUEST['widgetnumber'])){

                    $widgets=get_option('widget_wysija');
                    if(isset($widgets[$_REQUEST['widgetnumber']])){
                        $encodedForm=$widgets[$_REQUEST['widgetnumber']];
                    }

                }else{
                    $encodedForm=$_REQUEST['formArray'];
                    $encodedForm=stripslashes_deep($encodedForm);
                }

            }

        }

        $widgetdata=array();
        if($encodedForm){
           foreach($encodedForm as $key =>$val) {
                $widgetdata[$key]=$val;
                if(is_object($val)){
                    $valu=array();
                    foreach($val as $keyin =>$valin){
                        $valu[$keyin]=$valin;
                        if(is_object($valin)){
                            $inin=array();
                            foreach($valin as $kin => $vin){
                                $inin[$kin]=$vin;
                            }
                            $valu[$keyin]=$inin;
                        }
                    }
                    $widgetdata[$key]=$valu;
                }
            }
        }else{
            if(current_user_can('switch_themes'))    echo '<b>'.str_replace(array('[link]','[/link]'),array('<a target="_blank" href="'.  admin_url('widgets.php').'">','</a>'),__('It seems your widget has been deleted from the WordPress\' [link]widgets area[/link].',WYSIJA)).'</b>';
            exit;
        }



        if(isset($_REQUEST['widgetnumber']))  $intrand=$_REQUEST['widgetnumber'];
        else $intrand=rand(5, 1500);
        $widgetdata['widget_id']='wysija-nl-iframe-'.$intrand;
        require_once(WYSIJA_WIDGETS.'wysija_nl.php');
        $widgetNL=new WYSIJA_NL_Widget(1);
        $widgetNL->iFrame=true;
        $subscriptionForm= $widgetNL->widget($widgetdata,$widgetdata);

        echo $subscriptionForm;
        exit;
    }

}