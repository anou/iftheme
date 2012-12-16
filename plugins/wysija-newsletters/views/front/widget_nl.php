<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_view_front_widget_nl extends WYSIJA_view_front {

    function WYSIJA_view_front_widget_nl(){
        $this->model=&WYSIJA::get("user","model");
    }

    function wrap($content){
        $html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
<meta name="robots" content="NOINDEX,NOFOLLOW">
<meta charset="utf-8" />
<title>'.__('Wysija Subscription',WYSIJA).'</title>';
        global $wp_scripts,$wp_styles;

        ob_start();
        //if(isset($_REQUEST['external_site'])) wp_head();
        wp_print_scripts('jquery');
        wp_print_styles('validate-engine-css');
        //add custom css for external site iframe
        if(isset($_REQUEST['external_site']) && file_exists(WYSIJA_UPLOADS_DIR.'css'.DS.'iframe.css')){
            wp_register_style('wysija-iframe',WYSIJA_UPLOADS_URL."css/iframe.css",array(),WYSIJA::get_version());
            wp_print_styles('wysija-iframe');
        }
        wp_print_scripts('wysija-validator-lang');
        wp_print_scripts('wysija-validator');
        wp_print_scripts('wysija-front-subscribers');

        $html.=ob_get_contents();
        ob_end_clean();

        $html.='</head><body>';
        if(isset($_REQUEST['external_site'])){
            $classform='';
        }else{
            $classform=' iframe-hidden';
        }
        $html.='<div class="wysija-frame'.$classform.'" >'.$content.'</div>';
        $html.='</body></html>';
        return $html;
    }

    function display($title="",$params,$echo=true,$iframe=false){
        if(!$iframe)    $this->addScripts();
        $data=$labelemail="";
        $formidreal="form-".$params['id_form'];

        $data.= $title;
        $listfieldshidden=$listfields='';
        $disabledSubmit=$msgsuccesspreview='';
        if(isset($params['preview'])){
            $disabledSubmit='disabled="disabled"';
            $msgsuccesspreview='<div class="allmsgs"><div class="updated">'.$params["success"].'</div></div>';
        }

        //a subscription form needs to have lists associated to itself, otherwise it's no good
        if(!isset($params["lists"]) || !$params["lists"])   return;

        $data.='<div class="widget_wysija_cont">';
        if(isset($_POST['wysija']['user']['email']) && isset($_POST['formid']) && $formidreal==$_POST['formid']){
            $data.= str_replace ('class="wysija-msg', 'id="msg-'.$formidreal.'" class="wysija-msg', $this->messages());
        }else{
            $data.='<div id="msg-'.$formidreal.'" class="wysija-msg ajax">'.$msgsuccesspreview.'</div>';
        }

        // add form unless it's a preview
        if(!isset($params['preview']) or (isset($params['preview']) && $params['preview'] === false)) {
            $data .= '<form id="'.$formidreal.'" method="post" action="#wysija" class="widget_wysija form-valid-sub">';
        }

            if(isset($params['instruction']) && $params['instruction'])   {
                if(strpos($params['instruction'], '[total_subscribers')!==false){
                    $modelC=&WYSIJA::get('config','model');
                    $totalsubscribers=  str_replace(',', ' ', number_format($modelC->getValue('total_subscribers')));

                    $params['instruction']=str_replace('[total_subscribers]', $totalsubscribers, $params['instruction']);
                }
                $data.='<p class="wysija-instruct">'.$params['instruction'].'</p>';
            }


            if(isset($params['autoregister']) && $params['autoregister']=='auto_register'){
                $listfields='<div class="wysija_lists">';
                $i=0;
                foreach($params["lists"] as $listid){
                    $listfields.='<p class="wysija_list_check">
                        <label for="'.$formidreal.'_list_id_'.$listid.'"><input id="'.$formidreal.'_list_id_'.$listid.'" class="validate[minCheckbox[1]] checkbox checklists" type="checkbox" name="wysija[user_list][list_id][]" value="'.$listid.'" checked="checked" /> '.$params['lists_name'][$listid].' </label>
                            </p>';
                    $i++;
                }
                $listfields.='</div>';

            }else{

                if(isset($params["lists"])) $listexploded=esc_attr(implode(',',$params["lists"]));
                else $listexploded="";

                $listfieldshidden='<input type="hidden" name="wysija[user_list][list_ids]" value="'.$listexploded.'" />';
            }

            $submitbutton=$listfields.'<input type="submit" '.$disabledSubmit.' class="wysija-submit wysija-submit-field" name="submit" value="'.esc_attr($params['submit']).'"/>';
            $dataCf=$this->customFields($params,$formidreal,$submitbutton);



            if($dataCf){
                $data.=$dataCf;
            }else{
                $classValidate="wysija-email ".$this->getClassValidate($this->model->columns['email'],true);
                $data.='<p><input type="text" id="'.$formidreal.'-wysija-to" class="'.$classValidate.'" name="wysija[user][email]" />';
                if(!isset($params['preview'])) $data.=$this->honey($params,$formidreal);
                $data.=$submitbutton.'</p>';
            }




            if(!isset($params['preview'])){
                $data.='<input type="hidden" name="formid" value="'.esc_attr($formidreal).'" />
                    <input type="hidden" name="action" value="save" />
                '.$listfieldshidden.'
                <input type="hidden" name="message_success" value="'.esc_attr($params["success"]).'" />
                <input type="hidden" name="controller" value="subscribers" />';
                //$data.=$this->secure(array('action'=>'save','controller'=>'subscribers'),false,false);

                $data.='<input type="hidden" value="1" name="wysija-page" />';
                //$data.='<input type="hidden" value="'.wp_create_nonce("wysija_ajax").'" id="wysijax" />';
            }

        // add form unless it's a preview
        if(!isset($params['preview']) or (isset($params['preview']) && $params['preview'] === false)) {
            $data.='</form>';
        }

        //hook to let plugins modify our html the way they want
        $data = apply_filters('wysija_subscription_form', $data);
        $data.='</div>';
        if($echo) echo $data;
        else return $data;
    }

    function customFields($params,$formidreal,$submitbutton){
        $html="";
        $validationsCF=array(
            'email' => array("req"=>true,"type"=>"email","defaultLabel"=>__("Email",WYSIJA)),
            'firstname' => array("req"=>true,"defaultLabel"=>__("First name",WYSIJA)),
            'lastname' => array("req"=>true,"defaultLabel"=>__("Last name",WYSIJA)),
        );

        if(isset($params['customfields']) && $params['customfields']){
            foreach($params['customfields'] as $fieldKey=> $field){
                $classField='wysija-'.$fieldKey;
                $classValidate=$classField." ".$this->getClassValidate($validationsCF[$fieldKey],true);
                if(!isset($field['label']) || !$field['label']) $field['label']=$validationsCF[$fieldKey]['defaultLabel'];
                if($fieldKey=="email") $fieldid=$formidreal."-wysija-to";
                else $fieldid=$formidreal.'-'.$fieldKey;
                if(isset($params['getHtml'])){
                    $titleplaceholder='placeholder="'.$field['label'].'" title="'.$field['label'].'"';
                }else{
                    $titleplaceholder='title="'.$field['label'].'"';
                }
                if(count($params['customfields'])>1){
                    if(isset($params['labelswithin'])){
                         if($params['labelswithin']=='labels_within'){
                            $fieldstring='<input type="text" id="'.$fieldid.'" '.$titleplaceholder.' class="defaultlabels '.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                        }else{
                            $fieldstring='<label for="'.$fieldid.'">'.$field['label'].'</label><input type="text" id="'.$fieldid.'" class="'.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                        }
                    }else{
                        $fieldstring='<label for="'.$fieldid.'">'.$field['label'].'</label><input type="text" id="'.$fieldid.'" class="'.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                    }
                }else{
                    if(isset($params['labelswithin'])){
                         if($params['labelswithin']=='labels_within'){
                            $fieldstring='<input type="text" id="'.$fieldid.'" '.$titleplaceholder.' class="defaultlabels '.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                        }else{
                            $fieldstring='<input type="text" id="'.$fieldid.'" class="'.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                        }
                    }else{
                        $fieldstring='<input type="text" id="'.$fieldid.'" class="'.$classValidate.'" name="wysija[user]['.$fieldKey.']" />';
                    }
                }


                $html.='<p class="wysija-p-'.$fieldKey.'">'.$fieldstring.'</p>';
            }

            if(!isset($params['preview'])) $html.=$this->honey($params,$formidreal);

            if($html) $html.=$submitbutton;
        }

        return $html;
    }

    function honey($params,$formidreal){
        $arrayhoney=array(
            "firstname"=>array('label'=>__("First name",WYSIJA),"type"=>"req"),
            "lastname"=>array('label'=>__("Last name",WYSIJA),"type"=>"req"),
            "email"=>array('label'=>__("Email",WYSIJA),"type"=>"email")

            );
        $html="";
        foreach($arrayhoney as $fieldKey=> $field){
            $fieldid=$formidreal.'-abs-'.$fieldKey;

            if(isset($params['labelswithin'])){
                $fieldstring='<input type="text" id="'.$fieldid.'" value="" class="defaultlabels validated[abs]['.$field['type'].']" name="wysija[user][abs]['.$fieldKey.']" />';
            }else{
                $fieldstring='<label for="'.$fieldid.'">'.$field['label'].'</label><input type="text" id="'.$fieldid.'" class="validated[abs]['.$field['type'].']" name="wysija[user][abs]['.$fieldKey.']" />';
            }
            $html.='<span class="wysija-p-'.$fieldKey.' abs-req">'.$fieldstring.'</span>';
        }
        return $html;
    }

}
