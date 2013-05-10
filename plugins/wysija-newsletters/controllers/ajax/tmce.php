<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_tmce extends WYSIJA_control{

    function WYSIJA_control_back_tmce(){
        if(!WYSIJA::current_user_can('wysija_subscriwidget')) die('Action is forbidden.');
        parent::WYSIJA_control();
        $this->viewObj=&WYSIJA::get('tmce','view');
    }


    function registerAdd(){
        $this->viewObj->title=__('Insert Subscription Form',WYSIJA);

        $this->viewObj->registerAdd($this->getData());
        exit;
    }

    function registerEdit(){
        $this->viewObj->title=__('Insert Subscription Form',WYSIJA);

        $this->viewObj->registerAdd($this->getData(),true);
        exit;
    }


    function getData(){
        $data_widget=array();

        // if this parameter is passed that means we have an old widget let's import it and select
        if(isset($_REQUEST['widget-data64'])){
            $data_widget=unserialize(base64_decode($_REQUEST['widget-data64']));
            $post=get_post($_REQUEST['post_id']);

            // we need a title to identify the form
            $data_widget['title'] = 'Form on '.$post->post_type.': '.$post->post_title;

            $model_forms =& WYSIJA::get('forms', 'model');
            $model_forms->reset();
            $form = $model_forms->getOne(false,array('name' => $data_widget['title']));

            // this form doesn't exist yet in the new format so let's try to import it
            if(empty($form)){
                $helper_update=&WYSIJA::get('update','helper');

                $form_id = $helper_update->convert_widget_to_form($data_widget);
                if($form_id!==false) {
                    $data_widget['default_form'] = $form_id;
                }
            }
        }

        if(isset($_POST['widget-wysija'])){
            $data_widget=array('widget_id'=>$_POST['widget_id'],'preview'=>true);
            $custom_fields=array();

            foreach($_POST['widget-wysija'] as $arra){
                foreach($arra as $k => $v) {
                    switch($k){
                        case 'lists':
                            if(isset($data_widget[$k]))  $data_widget[$k][]=$v[0];
                            else    $data_widget[$k]=array($v[0]);
                            break;
                        case 'lists_name':
                            foreach($v as $kv=>$vv){
                                if(isset($data_widget[$k]))  $data_widget[$k][$kv]=$vv;
                                else    $data_widget[$k]=array($kv=>$vv);
                            }

                            break;
                        case 'customfields':

                            $found=false;
                            foreach($custom_fields as $keycol => $params){
                                if(isset($v[$keycol])){
                                    $custom_fields[$keycol]=  array_merge($custom_fields[$keycol],$v[$keycol]);
                                    $found=true;
                                }
                            }

                            if(!$found) $custom_fields=array_merge($custom_fields,$v);
                            break;

                        default:
                            $data_widget[$k]=stripslashes($v);
                    }

                }
            }


            $count=count($custom_fields);
            if($count>1){
                foreach($custom_fields as $keycol=>$paraval){
                    if($keycol!='email' && !isset($paraval['column_name'])) unset($custom_fields[$keycol]);
                }
            }

            $count=count($custom_fields);
            if($count>0){
                if(!isset($custom_fields['email'])){
                    $custom_fields['email']['column_name']='email';
                    $custom_fields['email']['label']=__('Email',WYSIJA);
                }
            }

            $count=count($custom_fields);
            if($count==1 && isset($custom_fields['email'])){

                $custom_fields=array();
            }

            if($custom_fields)   $data_widget['customfields']=$custom_fields;

        }

        //dbg($datawidget,0);
        if(!isset($data_widget['customfields']) && isset($data_widget['labelswithin']) && $data_widget['labelswithin']=='labels_within'){
            $data_widget['customfields']=array('email'=>array('label'=>__('Email',WYSIJA)));
        }

        return $data_widget;
    }

}