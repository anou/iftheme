<?php

/**
 * widget class for user registration
 */
class WYSIJA_NL_Widget extends WP_Widget {
    var $classid='';
    var $iFrame=false;


    function WYSIJA_NL_Widget($coreOnly=false) {
        static $scriptregistered;
        if(WYSIJA_SIDE=='front'){

            if(!$scriptregistered){
                if(!isset($_REQUEST['controller']) || (isset($_REQUEST['controller']) && $_REQUEST['controller']=='confirm' && isset($_REQUEST['wysija-key']))){
                    $controller='subscribers';
                }else $controller=$_REQUEST['controller'];

                $mConfig=&WYSIJA::get('config','model');
                if(!$mConfig->getValue('relative_ajax') && !empty($_SERVER['HTTP_HOST'])){
                    $siteurl=get_site_url();
                    /*try to find the domain part in the site url*/
                    if(strpos($siteurl, $_SERVER['HTTP_HOST'])===false){
                        //if we don't find it then we need to create a new siteadminurl
                        //by replacing the part between http// and the first slash with the one from request uri

                        $siteurlarray=explode('/',
                                str_replace(array('http://'),'',$siteurl)
                                );

                        $ajaxurl=str_replace($siteurlarray[0], $_SERVER['HTTP_HOST'], $siteurl);

                    }else{
                        $ajaxurl=$siteurl;
                    }

                    //let's check if the current ajaxurl is https if so we need to make sure that also the url we're calling from is https
                    if(strpos($ajaxurl, 'https://')!==false){
                        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on'){
                            //ok
                        }else{
                            $ajaxurl=str_replace('https://','http://',$ajaxurl);
                        }
                    }

                    $lastchar=substr($ajaxurl, -1);

                    if($lastchar!='/')$ajaxurl.='/';
                    $ajaxurl.='wp-admin/admin-ajax.php';
                }else{
                    if($mConfig->getValue('relative_ajax')=='absolute'){
                        $ajaxurl=admin_url( 'admin-ajax.php', 'absolute' );
                    }else{
                        $ajaxurl=admin_url( 'admin-ajax.php', 'relative' );
                    }

                }


                $this->paramsajax=array(
                    'action' => 'wysija_ajax',
                    'controller' => $controller,
                    'ajaxurl'=>$ajaxurl,
                    'loadingTrans'  =>__('Loading...',WYSIJA)
                );

                if(is_user_logged_in()) $this->paramsajax['wysilog']=1;
                if($mConfig->getValue('no_js_val')) $this->paramsajax['noajax']=1;

                $scriptregistered=true;
            }

        }

        if($coreOnly) $this->coreOnly=true;
        $namekey='wysija';
        $title=__('Wysija Subscription',WYSIJA);
        $params=array( 'description' => __('Subscription form for your newsletters.',WYSIJA));
        $sizeWindow=array('width' => 400);

        $this->add_translated_default();

        if(defined('WP_ADMIN')){
            add_action('admin_menu', array($this,'add_translated_default'),96);
        }

        //add_action('init', array($this,'recordWysijaAjax'));
        $this->recordWysijaAjax();

        $this->classid=strtolower(str_replace(__CLASS__."_","",get_class($this)));
        //parent::__construct( $namekey, $title, $params,$sizeWindow );
        $this->WP_Widget( $namekey, $title, $params,$sizeWindow );

    }

    function recordWysijaAjax(){
        if(isset($this->paramsajax)){
            //$this->paramsajax['ajaxurl'] = apply_filters('wysijaAjaxURL', $this->paramsajax['ajaxurl']);
            wp_localize_script( 'wysija-front-subscribers', 'wysijaAJAX',$this->paramsajax );

        }
    }

    function add_translated_default(){
        $this->name=__('Wysija Subscription',WYSIJA);
        $this->widget_options['description']=__('Subscription form for your newsletters.',WYSIJA);

        $config=&WYSIJA::get('config','model');
        $this->successmsgconf=__('Check your inbox now to confirm your subscription.',WYSIJA);
        $this->successmsgsub=__("You've successfully subscribed.",WYSIJA);
        if($config->getValue('confirm_dbleoptin')){
            $successmsg=$this->successmsgconf;
        }else{
            $successmsg=$this->successmsgsub;
        }
        $this->fields=array(
            'title' =>array('label'=>__('Title:',WYSIJA),'default'=>__('Subscribe to our Newsletter',WYSIJA))
            ,'instruction' =>array('label'=>'','default'=>__('To subscribe to our dandy newsletter simply add your email below. A confirmation email will be sent to you!',WYSIJA))
            ,'lists' =>array('core'=>1,'label'=>__('Select a list:',WYSIJA),'default'=>array(1))
            ,'autoregister' =>array('core'=>1,'label'=>__('Let subscribers select their lists:',WYSIJA),'default'=>'not_auto_register')
            ,'customfields' =>array('core'=>1,'label'=>__('Ask for:',WYSIJA),'default'=>array('email'=>array('label'=>__('Email',WYSIJA))))
            ,'labelswithin'=>array('core'=>1,'default'=>true,'label'=>__('Display labels in inputs',WYSIJA),'hidden'=>1)
            ,'submit' =>array('core'=>1,'label'=>__('Button label:',WYSIJA),'default'=>__('Subscribe!',WYSIJA))
            ,'success'=>array('core'=>1,'label'=>__('Success message:',WYSIJA),'default'=>$successmsg)
            ,'iframe'=>array('core'=>1,'label'=>__('iframe version',WYSIJA))
            /*,"php"=>array("core"=>1,"label"=>__('Get php version',WYSIJA))*/
        );
    }


    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        /* check if custom fields are set in the new instance, it if is not then we remove it from the old instance */
        if(isset($instance['customfields']) && !isset($new_instance['customfields'])) unset($instance['customfields']);
        //if(isset($instance['labelswithin']) && !isset($new_instance['labelswithin'])) unset($instance['labelswithin']);

        /* for each new instance we update the current instance */
        foreach($new_instance as $key => $value) $instance[$key]=$value;


        /*get the custom fields*/
        $modelCustomF=&WYSIJA::get('user_field','model');
        $customs=$modelCustomF->get(false,array('type'=>'0'));

        /*set an array of custom fields easy to read*/
        $custombyid=array();
        foreach($customs as $customf)   $custombyid[$customf['column_name']]=$customf;

        if(!isset($instance['customfields']) && isset($instance['labelswithin']) && $instance['labelswithin']=='labels_within'){
            $instance['customfields']=array('email'=>array('label'=>__('Email',WYSIJA)));
        }


        /* if there were custom fields set in the previous instance*/
        if(isset($instance['customfields']) && $instance['customfields']){
            foreach($instance['customfields'] as $keycf => &$custom){
                /* make sure we remove the label data if the field is not selected anymore */
                if(!isset($custom['column_name']) && $keycf!='email') unset($instance['customfields'][$keycf]);
                else{
                    /*if a custom field is select but has no default label then we just set the default label for that field*/
                    if(!isset($custom['label']) || !$custom['label']) $custom['label']=$custombyid[$custom['column_name']]['name'];
                }
            }

        }

        return $instance;
    }

    function form( $instance ) {
        $formObj=&WYSIJA::get('forms','helper');

        $html='<div class="wysija-widget-form">';
        $modelList=&WYSIJA::get('list','model');
        $lists=$modelList->get(array('name','list_id'),array('is_enabled'=>1));
        if(!$lists){
            echo '<p>'.__('Before creating a subscription widget you\'ll need to create at least one list to add your subscribers to.',WYSIJA).' <a href="admin.php?page=wysija_subscribers&action=addlist">'.__('Create a list.',WYSIJA).'</a></p>';
            return;
        }

        foreach($this->fields as $field => $fieldParams){
            $extrascriptLabel='';
            $valuefield='';
            if((isset($fieldParams['hidden']) && $fieldParams['hidden']) || (isset($this->coreOnly) && !isset($fieldParams['core']))) continue;
            if(isset($instance[$field]))  {

                if($field=='success' && $instance[$field]==$this->successmsgsub.' '.$this->successmsgconf){
                    $config=&WYSIJA::get('config','model');
                    if(!$config->getValue('confirm_dbleoptin')){
                        $valuefield=$this->successmsgsub;
                    }else{
                        $valuefield=$instance[$field];
                    }
                }else   $valuefield=$instance[$field];
            }
            elseif(isset($fieldParams['default'])) $valuefield=$fieldParams['default'];

            $classDivLabel=$fieldHTML='';
            switch($field){
                case 'lists':
                    $classDivLabel='style="float:left"';
                    $fieldHTML= '<div class="lists-block">';

                    if(!$valuefield) {
                        $modelConfig=&WYSIJA::get('config','model');
                        $valuefield[]=$modelConfig->getValue('default_list_id');
                    }
                    foreach($lists as $list){
                        if(in_array($list['list_id'], $valuefield)) $checked=true;
                        else $checked=false;
                        $fieldHTML.= '<p class="labelcheck"><label for="'.
                                $this->get_field_id($field.$list['list_id']).'">'.$formObj->checkbox( array('id'=>$this->get_field_id($field.$list['list_id']),
                                    'name'=>$this->get_field_name($field).'[]'),
                                        $list['list_id'],$checked).$list['name'].'</label></p>';
                        $fieldHTML.='<input type="hidden" name="'.$this->get_field_name($field.'_name').'['.$list['list_id'].']'.'" value="'.$list['name'].'" />';
                    }

                    $fieldHTML .= '</div>';

                    break;
                case 'autoregister':
                    $classDivLabel=' style="clear:both; max-height: 116px; overflow: auto; float: left;margin: 0 10px 10px 0;" ';
                    $value='auto_register';
                    $checked=false;
                    if((isset($instance['autoregister']) && $instance['autoregister']=='auto_register')) $checked=true;

                    $id=str_replace('_','-',$field).'-'.$value;
                    $fieldHTML.='<p class="labelcheck"><label for="'.$id.'">';
                    $fieldHTML.=$formObj->radio(array('id'=>$id,'name'=>$this->get_field_name('autoregister')),$value,$checked);
                    $fieldHTML.=__('Yes',WYSIJA).'</label></p>';

                    $value='not_auto_register';
                    $checked=false;
                    if(!isset($instance['autoregister']) || $instance['autoregister']!='auto_register') $checked=true;
                    $id=str_replace('_','-',$field).'-'.$value;
                    $fieldHTML.='<p class="labelcheck"><label for="'.$id.'">';
                    $fieldHTML.=$formObj->radio(array('id'=>$id,'name'=>$this->get_field_name('autoregister')),$value,$checked);
                    $fieldHTML.=__('No',WYSIJA).'</label></p>';

                    break;
                case 'customfields':

                    if(!isset($instance['labelswithin']) && !isset($instance['customfields'])){
                        $instance['customfields']=$fieldParams['default'];
                        $instance['labelswithin']='labels_within';
                    }

                    $modelCustomF=&WYSIJA::get('user_field','model');
                    $modelCustomF->orderBy('field_id','ASC');
                    $customs=$modelCustomF->get(false,array('type'=>"0"));

                    $custombyid=array();
                    $classDivLabel='style="float:left"';
                    $fieldHTML= '<div class="lists-block">';

                    foreach($customs as $customf){
                        $custombyid[$customf['column_name']]=$customf;

                        if(is_array($valuefield) && isset($valuefield[$customf['column_name']])) $checked=true;
                        else $checked=false;

                        $fieldHTML.= '<p class="labelcheck"><label for="'.$this->get_field_id($field.$customf['field_id']).'">'.
                                $formObj->checkbox( array('id'=>$this->get_field_id($field.$customf['field_id']),
                                    'name'=>$this->get_field_name($field)."[".$customf['column_name']."][column_name]"),
                                        $customf['column_name'],$checked).$customf['name'].'</label></p>';
                    }
                    $fieldHTML .= '</div>';



                    $fieldHTML.= '<p style="clear:both;margin: 0;">'.$this->fields['labelswithin']['label'].'</p>';
                    $value='labels_within';
                    $checked=true;
                    if(!isset($instance['labelswithin']) || $instance['labelswithin']!='labels_within') $checked=true;

                    $id=str_replace('_','-',$field).'-'.$value;
                    $fieldHTML.='<p class="labelcheck"><label for="'.$id.'">';
                    $fieldHTML.=$formObj->radio(array('id'=>$id,'name'=>$this->get_field_name('labelswithin')),$value,$checked);
                    $fieldHTML.=__('Yes',WYSIJA).'</label></p>';

                    $value='labels_out';
                    $checked=false;
                    if((isset($instance['labelswithin']) && $instance['labelswithin']=='labels_out')) $checked=true;
                    $id=str_replace('_','-',$field).'-'.$value;
                    $fieldHTML.='<p class="labelcheck"><label for="'.$id.'">';
                    $fieldHTML.=$formObj->radio(array('id'=>$id,'name'=>$this->get_field_name('labelswithin')),$value,$checked);
                    $fieldHTML.=__('No',WYSIJA).'</label>';
                    $fieldHTML .= '</p>';

                    $fieldParamsLabels['email']=array('core'=>1,
                            'label'=>__('Label for email:',WYSIJA),
                            'default'=>__('Email',WYSIJA));
                     $custombyid['email']['name']='email';

                    /*custom fields management for labels*/

                    if(isset($instance['customfields']) && $instance['customfields']){
                         /* set label as default value */
                        foreach($instance['customfields'] as $cf_id => $customfield){
                            $defaultvalue='';
                            if(isset($valuefield[$cf_id]['label'])) $defaultvalue=$valuefield[$cf_id]['label'];
                            if(!$defaultvalue) $defaultvalue=$custombyid[$cf_id]['name'];
                            $fieldParamsLabels[$cf_id]=array('core'=>1,
                                'label'=>sprintf(__('Label for %1$s:',WYSIJA),$custombyid[$cf_id]['name']),
                                'default'=>$defaultvalue);
                        }
                    }

                    if(isset($instance['customfields']) ){
                        $fieldHTML.='<div style="clear:both;">';

                        foreach($fieldParamsLabels as $cfield_id => $customlabel){
                            $valuef='';
                            if(isset($valuefield[$cfield_id]['label'])) $valuef=$valuefield[$cfield_id]['label'];
                            if(!$valuef)    $valuef=$customlabel['default'];

                            if(count($fieldParamsLabels) == 1 && isset($instance['labelswithin']) && $instance['labelswithin']=='labels_within' || count($fieldParamsLabels) > 1){
                                $fieldHTML.= '<p><label for="'.$this->get_field_id($field.$cfield_id).'">'.$customlabel['label'];
                                $fieldHTML.= $formObj->input( array('id'=>$this->get_field_id($field.$cfield_id),'name'=>$this->get_field_name($field)."[".$cfield_id."][label]"),$valuef);
                                $fieldHTML.= '</label></p>';
                            }
                            else{
                                $fieldHTML.= $formObj->hidden( array('id'=>$this->get_field_id($field.$cfield_id),'name'=>$this->get_field_name($field)."[".$cfield_id."][label]"),$valuef);
                            }


                        }
                        $fieldHTML.='<div style="clear:both;"></div></div>';
                        //dbg($fieldHTML,0);
                    }



                    break;

                case 'instruction':
                case 'success':
                    $fieldHTML= $formObj->textarea( array('id'=>$this->get_field_id($field),'name'=>$this->get_field_name($field),'value'=>$valuefield,"cols"=>46,"rows"=>4,"style"=>'width:404px'),$valuefield);
                    break;
                case 'iframe':
                    $fieldHTML=$textareas=$labels='';
                    $fieldParams['nolabel']=1;
                    if((isset($instance['submit']))){
                        $fieldstype=array('iframe'=>__('iFrame version',WYSIJA),'php'=>__('PHP version',WYSIJA),'html'=>__('HTML version',WYSIJA));

                        $i=0;
                        $snippets = '';
                        foreach($fieldstype as $myfield=>$mytitle){

                            switch($myfield){
                                case 'iframe':
                                    $valuefield=$this->genIframe($instance,true);
                                    $scriptCloseOther='document.getElementById(\''.$this->get_field_id('php').'\').style.display=\'none\';';
                                    $scriptCloseOther.='document.getElementById(\''.$this->get_field_id('html').'\').style.display=\'none\';';
                                    break;
                                case 'php':
                                    $valuefield=$this->genPhp($instance,true);
                                    $scriptCloseOther='document.getElementById(\''.$this->get_field_id('html').'\').style.display=\'none\';';
                                    $scriptCloseOther.='document.getElementById(\''.$this->get_field_id('iframe').'\').style.display=\'none\';';
                                    break;
                                case 'html':
                                    $valuefield=$this->genHtml($instance,true);
                                    $scriptCloseOther='document.getElementById(\''.$this->get_field_id('php').'\').style.display=\'none\';';
                                    $scriptCloseOther.='document.getElementById(\''.$this->get_field_id('iframe').'\').style.display=\'none\';';
                                    break;
                            }

                            $scriptlabel=' style="color:#456465;text-decoration:underline;" onClick="'.$scriptCloseOther.'document.getElementById(\''.$this->get_field_id($myfield).'\').style.display = (document.getElementById(\''.$this->get_field_id($myfield).'\').style.display != \'none\' ? \'none\' : \'block\' );" ';
                            $labels.='<label for="'.$this->get_field_id($myfield).'" '.$scriptlabel.'>'.$mytitle.'</label>';
                            if($i<=1)$labels.=' | ';
                            $snippets .= '<div id="'.$this->get_field_id($myfield).'" style="margin:5px 0 5px 0;overflow:auto;display:none;width:404px;height:120px;background-color:#fff;border:1px solid #dfdfdf;color:#333;">'.nl2br(htmlentities($valuefield)).'</div>';
                            $i++;
                        }
                        $fieldHTML='<div>'.$labels.'</div>'.$snippets;
                    }

                    break;
                default:
                    $fieldHTML= $formObj->input( array('id'=>$this->get_field_id($field),'name'=>$this->get_field_name($field)),$valuefield ,' size="40" ');
                    break;
            }

            $html.='<div class="divblocks">';
            if(!isset($fieldParams['nolabel'])){

                $html.='<div '.$classDivLabel.'><label for="'.$this->get_field_id($field).'" '.$extrascriptLabel.'>'.$fieldParams['label'].'</label></div>';
            }
            $html.=$fieldHTML;
            $html.='<div style="clear:both;"></div></div>';

        }
        $html.='</div>';
        echo $html;

    }

    function genIframe($instance,$externalsite=false){
        $now=time();
        if(isset($instance['preview'])) unset($instance['preview']);
        $encodedForm=base64_encode(json_encode($instance));

        $paramsurl=array(
                'wysija-page'=>1,
                'controller'=>'subscribers',
                'action'=>'wysija_outter',
                );

        if(isset($this->number) && $this->number >0) {
            $paramsurl['widgetnumber']=$this->number;
            $idframe=$this->number;
        }
        else{
           $paramsurl['fullWysijaForm']=$encodedForm;
           $idframe=  rand(45000, 99999);
        }

        $modelConf=&WYSIJA::get('config','model');
        $onloadattr='';
        if($externalsite){
            $paramsurl['external_site']=1;

        }else{
            $onloadattr='onload="jQuery.WYSIJA_iframeloadhandler(this);"';
        }

        /*if(WYSIJA::is_plugin_active('wp-super-cache/wp-cache.php')){
            global $cache_page_secret;
            $paramsurl['donotcachepage']=$cache_page_secret;
        }*/

        //the final tru allow for shorter url
        $fullurl=WYSIJA::get_permalink($modelConf->getValue('confirm_email_link'),$paramsurl,true);


        //return '<iframe width="100%" scrolling="no" frameborder="0" src="'.$fullurl.'" name="wysija-'.$now.'" class="iframe-wysija" id="wysija-'.$idframe.'" vspace="0" tabindex="0" style="position: static; top: 0pt; margin: 0px; border-style: none; height: 330px; left: 0pt; visibility: visible;" marginwidth="0" marginheight="0" hspace="0" allowtransparency="true" title="'.__('Subscription Wysija',WYSIJA).'"></iframe>';
        return '<iframe '.$onloadattr.' width="100%" scrolling="no" frameborder="0" src="'.$fullurl.'" name="wysija-'.$now.'" class="iframe-wysija" id="wysija-'.$idframe.'" vspace="0" tabindex="0" style="position: static; top: 0pt; margin: 0px; border-style: none; height: 330px; left: 0pt; visibility: visible;" marginwidth="0" marginheight="0" hspace="0" allowtransparency="true" title="'.__('Subscription Wysija',WYSIJA).'"></iframe>';
        //$fieldHTML='<div class="widget-control-actions">';
    }

    function genPhp($instance,$externalsite=false){

        $instance2=$instance;
        if(isset($instance2['preview'])) unset($instance2['preview']);
        $instance2['widget_id']=$this->id.'-php';
        $phpcode='$widgetdata='.var_export($instance2,true).';'."\n";
        $phpcode.='$widgetNL=new WYSIJA_NL_Widget(1);'."\n";
        $phpcode.='$subscriptionForm= $widgetNL->widget($widgetdata,$widgetdata);'."\n";
        $phpcode.='echo $subscriptionForm;'."\n";

        return $phpcode;
        //$fieldHTML='<div class="widget-control-actions">';
    }

    function genHtml($instance,$externalsite=false){
        $this->coreOnly=true;
        $instance['getHtml']=true;
        $htmlreturn='';

        //generate scripts tags for validation and ajax submission
        ob_start();
        //if(isset($_REQUEST['external_site'])) wp_head();

        if(defined('WPLANG') && WPLANG!=''){
            $locale=explode('_',WPLANG);
            $wplang=$locale[0];
        }else{
            $wplang='en';
        }

        if(file_exists(WYSIJA_DIR.'js'.DS.'validate'.DS.'languages'.DS.'jquery.validationEngine-'.$wplang.'.js')){
            wp_register_script('wysija-validator-lang',WYSIJA_URL.'js/validate/languages/jquery.validationEngine-'.$wplang.'.js', array( 'jquery' ),WYSIJA::get_version(),true );
        }else{
            wp_register_script('wysija-validator-lang',WYSIJA_URL.'js/validate/languages/jquery.validationEngine-en.js', array( 'jquery' ),WYSIJA::get_version(),true );
        }
        wp_register_script('wysija-validator',WYSIJA_URL.'js/validate/jquery.validationEngine.js', array( 'jquery' ),WYSIJA::get_version(),true );
        wp_register_script('wysija-front-subscribers', WYSIJA_URL.'js/front-subscribers.js', array( 'jquery' ),WYSIJA::get_version(),true);
        $this->paramsajax=array(
                    'action' => 'wysija_ajax',
                    'controller' => 'subscribers',
                    'ajaxurl'=>  admin_url('admin-ajax.php','absolute'),
                    'loadingTrans'  =>__('Loading...',WYSIJA)
                );
        if(is_user_logged_in()) $this->paramsajax['wysilog']=1;
        wp_localize_script( 'wysija-front-subscribers', 'wysijaAJAX',$this->paramsajax );
        wp_print_scripts('jquery');
        wp_print_styles('validate-engine-css');
        wp_print_scripts('wysija-validator-lang');
        wp_print_scripts('wysija-validator');
        wp_print_scripts('wysija-front-subscribers');

        $htmlreturn.=ob_get_contents();
        ob_end_clean();


        $htmlreturn.=$this->widget(array('widget_id'=>  uniqid('html')), $instance);
        $this->coreOnly=false;
        return $htmlreturn;
        //$fieldHTML='<div class="widget-control-actions">';
    }

    function widget($args, $instance) {
        extract($args);

        $config=&WYSIJA::get('config','model');
        //if(!$config->getValue("sending_emails_ok")) return;
        foreach($this->fields as $field => $fieldParams){
            if(isset($this->coreOnly) && !isset($fieldParams['core'])) continue;
            if($field=='success' && $instance[$field]==$this->successmsgsub.' '.$this->successmsgconf){
                if(!$config->getValue('confirm_dbleoptin')){
                    $instance[$field]=$this->successmsgsub;
                }
            }
        }

        $instance['id_form']=str_replace('_','-',$args['widget_id']);

        if(!isset($this->coreOnly)) $title = apply_filters('widget_title',$instance['title'], $instance, $this->id_base);
        //dbg($before_title);
        /* some worpress weird thing for widgets management */
        if(!isset($before_widget)) $before_widget='';
        if(!isset($after_widget)) $after_widget='';
        if(!isset($before_title)) $before_title='';
        if(!isset($after_title)) $after_title='';

        $glob= $before_widget;
        if ( !isset($this->coreOnly) && $title ) $title=$before_title . $title . $after_title;
        else $title="";


        $view=&WYSIJA::get('widget_nl','view','front');
        /*if a cache plugin is active let's load the plugin in an iframe*/

        $glob.=$view->display($title,$instance,false,$this->iFrame);
        $glob.= $after_widget;

        if($this->iFrame){
            $glob=$view->wrap($glob);
        }

        if(isset($this->coreOnly) && $this->coreOnly) return $glob;
        else echo $glob;
    }
}