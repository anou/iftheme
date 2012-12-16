<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_tmce extends WYSIJA_control{

    function WYSIJA_control_back_tmce(){
        if(!WYSIJA::current_user_can('wysija_subscriwidget')) die("Action is forbidden.");
        parent::WYSIJA_control();
        $this->viewObj=&WYSIJA::get('tmce','view');
    }


    function registerAdd(){
        $this->viewObj->title=__("Insert Newsletter Registration Form",WYSIJA);

        $this->viewObj->registerAdd($this->getData());
        exit;
    }

    function registerEdit(){
        $this->viewObj->title=__("Edit Newsletter Registration Form",WYSIJA);

        $this->viewObj->registerAdd($this->getData(),true);
        exit;
    }


    function getData(){
        $datawidget=array();
        if(isset($_REQUEST['widget-data64'])){
            $datawidget=unserialize(base64_decode($_REQUEST['widget-data64']));
            $datawidget['preview']=true;
        }

        if(isset($_POST['widget-wysija'])){
            $datawidget=array('widget_id'=>$_POST['widget_id'],'preview'=>true);
            $customfields=array();

            foreach($_POST['widget-wysija'] as $arra){
                foreach($arra as $k => $v) {
                    switch($k){
                        case 'lists':
                            if(isset($datawidget[$k]))  $datawidget[$k][]=$v[0];
                            else    $datawidget[$k]=array($v[0]);
                            break;
                        case 'lists_name':
                            foreach($v as $kv=>$vv){
                                if(isset($datawidget[$k]))  $datawidget[$k][$kv]=$vv;
                                else    $datawidget[$k]=array($kv=>$vv);
                            }

                            break;
                        case 'customfields':

                            $found=false;
                            foreach($customfields as $keycol => $params){
                                if(isset($v[$keycol])){
                                    $customfields[$keycol]=  array_merge($customfields[$keycol],$v[$keycol]);
                                    $found=true;
                                }
                            }

                            if(!$found) $customfields=array_merge($customfields,$v);

                            break;

                        default:
                            $datawidget[$k]=stripslashes($v);
                    }

                }
            }


            $count=count($customfields);
            if($count>1){
                foreach($customfields as $keycol=>$paraval){
                    if($keycol!='email' && !isset($paraval['column_name'])) unset($customfields[$keycol]);
                }
            }

            $count=count($customfields);
            if($count>0){
                if(!isset($customfields['email'])){
                    $customfields['email']['column_name']='email';
                    $customfields['email']['label']=__('Email',WYSIJA);
                }
            }

            $count=count($customfields);
            if($count==1 && isset($customfields['email'])){

                $customfields=array();
            }

            if($customfields)   $datawidget['customfields']=$customfields;

        }

        //dbg($datawidget,0);
        if(!isset($datawidget['customfields']) && isset($datawidget['labelswithin']) && $datawidget['labelswithin']=='labels_within'){
            $datawidget['customfields']=array('email'=>array('label'=>__('Email',WYSIJA)));
        }

        return $datawidget;
    }

}