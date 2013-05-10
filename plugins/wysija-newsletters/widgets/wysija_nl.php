<?php

/**
 * widget class for user registration
 */
class WYSIJA_NL_Widget extends WP_Widget {
    var $classid='';
    var $iFrame=false;


    function WYSIJA_NL_Widget($core_only=false) {
        static $script_registered;
        if(WYSIJA_SIDE=='front'){

            if(!$script_registered){
                if(!isset($_REQUEST['controller']) || (isset($_REQUEST['controller']) && $_REQUEST['controller']=='confirm' && isset($_REQUEST['wysija-key']))){
                    $controller='subscribers';
                }else $controller=$_REQUEST['controller'];

                $model_config=&WYSIJA::get('config','model');
                if(!$model_config->getValue('relative_ajax') && !empty($_SERVER['HTTP_HOST'])){
                    $site_url=get_site_url();
                    //try to find the domain part in the site url
                    if(strpos($site_url, $_SERVER['HTTP_HOST'])===false){
                        //if we don't find it then we need to create a new siteadminurl
                        //by replacing the part between http// and the first slash with the one from request uri

                        $site_url_array=explode('/',
                            str_replace(array('http://'),'',$site_url)
                        );

                        $ajaxurl=str_replace($site_url_array[0], $_SERVER['HTTP_HOST'], $site_url);

                    }else{
                        $ajaxurl=$site_url;
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
                    if($model_config->getValue('relative_ajax')=='absolute'){
                        $ajaxurl=admin_url( 'admin-ajax.php', 'absolute' );
                    }else{
                        $ajaxurl=admin_url( 'admin-ajax.php', 'relative' );
                    }

                }


                $this->params_ajax=array(
                    'action' => 'wysija_ajax',
                    'controller' => $controller,
                    'ajaxurl'=>$ajaxurl,
                );

                if($model_config->getValue('no_js_val')) $this->params_ajax['noajax']=1;

                $script_registered=true;
            }

        }

        if($core_only===true) $this->core_only=true;
        $namekey='wysija';
        $title=__('Wysija Subscription',WYSIJA);
        $params=array( 'description' => __('Subscription form for your newsletters.',WYSIJA));
        $sizeWindow=array('width' => 400);

        $this->add_translated_default();

        //wait until the translation files are loaded
        if(defined('WP_ADMIN')){
            add_action('admin_init', array($this,'add_translated_default'));
        }else{
            add_action('init', array($this,'add_translated_default'));
        }

        $this->classid=strtolower(str_replace(__CLASS__.'_','',get_class($this)));
        $this->WP_Widget( $namekey, $title, $params,$sizeWindow );

    }

    function add_translated_default(){
        $this->name=__('Wysija Subscription',WYSIJA);
        $this->widget_options['description']=__('Subscription form for your newsletters.',WYSIJA);


        //if the js array for the ajax request is set we declare it
        if(isset($this->params_ajax)){
            $this->params_ajax['loadingTrans']  =__('Loading...',WYSIJA);
            wp_localize_script( 'wysija-front-subscribers', 'wysijaAJAX',$this->params_ajax );
        }

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
        );
    }


    function update($new_instance, $old_instance) {
        $instance = $old_instance;

        // keep all of the fields passed from the new instance
        foreach($new_instance as $key => $value) $instance[$key]=$value;

        return $instance;
    }

    function form($instance) {
        $helper_forms=&WYSIJA::get('forms','helper');

        $html='<div class="wysija-widget-form">';

        $this->fields=array(
            'title' => array(
                'label' => __('Title:', WYSIJA),
                'default' => __('Subscribe to our Newsletter',WYSIJA)
            ),
            'select_form' => array(
                'core' => 1,
                'label' => __('Select a form:', WYSIJA)
            ),
            'edit_link' => array(
                'core' => 1,
                'label' => __('Edit or create new', WYSIJA),
                'nolabel' => 1
            )
        );

        foreach($this->fields as $field => $field_params){
            $extrascriptLabel='';
            $value_field='';
            if((isset($field_params['hidden']) && $field_params['hidden']) || (isset($this->core_only) && $this->core_only && !isset($field_params['core']))) continue;

            if(isset($instance[$field]))  {

                if($field === 'success' && $instance[$field]==$this->successmsgsub.' '.$this->successmsgconf){
                    $config=&WYSIJA::get('config','model');
                    if(!$config->getValue('confirm_dbleoptin')){
                        $value_field=$this->successmsgsub;
                    }else{
                        $value_field=$instance[$field];
                    }
                }else   $value_field=$instance[$field];
            } elseif(isset($field_params['default'])) {
                $value_field = $field_params['default'];
            }

            $class_div_label=$field_html='';
            switch($field){
                case 'select_form':
                    // offer the possibility to select a form
                    $model_forms =& WYSIJA::get('forms', 'model');
                    $model_forms->reset();
                    $forms = $model_forms->getRows(array('form_id', 'name'));

                    $field_html = '<select name="'.$this->get_field_name('form').'" id="'.$this->get_field_id('form').'">';

                    foreach($forms as $form) {
                        $checked='';
                        // preselect the saved form
                        if( ((isset($instance['form']) && (int)$instance['form'] === (int)$form['form_id'])) || (isset($instance['default_form'] ) && (int)$instance['default_form'] === (int)$form['form_id'])){
                            $checked=' selected="selected"';
                        }
                        $field_html .=    '<option value="'.$form['form_id'].'"'.$checked.'>'.$form['name'].'</option>';
                    }
                    $field_html .= '</select>';
                    break;
                case 'edit_link':
                    $field_html = '<a href="admin.php?page=wysija_config#tab-forms" target="_blank" title="'.$field_params['label'].'">'.$field_params['label'].'</a>';
                    break;
                default:
                    $field_html= $helper_forms->input( array('id'=>$this->get_field_id($field),'name'=>$this->get_field_name($field)),$value_field ,' size="40" ');
            }

            $html.='<div class="divblocks">';
            if(!isset($field_params['nolabel'])){
                $html.='<div '.$class_div_label.'><label for="'.$this->get_field_id($field).'" '.$extrascriptLabel.'>'.$field_params['label'].'</label></div>';
            }
            $html.=$field_html;
            $html.='<div style="clear:both;"></div></div>';

        }
        $html.='</div>';
        echo $html;

    }

    function widget($args, $instance = null) {
        // this lines feed local variables such as $before_widget ,$after_widget etc...
        extract($args);

        //in some case we may pass only one argument, in which case we will just assign the first to the second
        if($instance === null) $instance = $args;
        // we need that part on every form for the different instances of a form on the same page
        //widget id should include the type of widget iframe, php, etc...
        if(isset($args['widget_id'])) {
            // we come here only for classic wordpress widgetized area
            $instance['id_form']=str_replace('_','-',$args['widget_id']);
        } else {
            // we come here everywhere else
            if(isset($instance['form']) && isset($instance['form_type'])){
                //make the id of the form truly unique
                $instance['id_form']=str_replace('_','-','wysija-'.uniqid($instance['form_type']).'-'.$instance['form']);
            }
        }



        if(isset($instance['form']) && (int)$instance['form'] > 0) {

            // new form editor
            $title = '';
            if(!isset($this->core_only)) $title = apply_filters('widget_title',$instance['title'], $instance, $this->id_base);
            if(!isset($before_widget)) $before_widget='';
            if(!isset($after_widget)) $after_widget='';
            if(!isset($before_title)) $before_title='';
            if(!isset($after_title)) $after_title='';

            if(!isset($this->core_only)) {
                $title = $before_title.$title.$after_title;
            }

            $view =& WYSIJA::get('widget_nl','view','front');

            $output  = $before_widget;
            $output .= $view->display($title, $instance, false, $this->iFrame);
            $output .= $after_widget;

            if($this->iFrame) {
                $output = $view->wrap($output);
            }

            if(isset($this->core_only) && $this->core_only) {
                return $output;
            } else {
                echo $output;
            }
        } else {
            $model_config=&WYSIJA::get('config','model');
            //if(!$config->getValue("sending_emails_ok")) return;
            foreach($this->fields as $field => $field_params){
                if(isset($this->core_only) && $this->core_only && !isset($field_params['core'])) continue;
                if($field=='success' && $instance[$field]==$this->successmsgsub.' '.$this->successmsgconf){
                    if(!$model_config->getValue('confirm_dbleoptin')){
                        $instance[$field]=$this->successmsgsub;
                    }
                }
            }

            if(!isset($this->core_only)) $title = apply_filters('widget_title',$instance['title'], $instance, $this->id_base);

            //some worpress weird thing for widgets management
            if(!isset($before_widget)) $before_widget='';
            if(!isset($after_widget)) $after_widget='';
            if(!isset($before_title)) $before_title='';
            if(!isset($after_title)) $after_title='';

            $content_html= $before_widget;
            if ( !isset($this->core_only) && $title ) $title=$before_title . $title . $after_title;
            else $title='';


            $view =& WYSIJA::get('widget_nl','view','front');
            $content_html.=$view->display($title,$instance,false,$this->iFrame);

            $content_html.= $after_widget;

            if($this->iFrame){
                $content_html=$view->wrap($content_html);
            }

            if(isset($this->core_only) && $this->core_only) return $content_html;
            else echo $content_html;
        }
    }
}
