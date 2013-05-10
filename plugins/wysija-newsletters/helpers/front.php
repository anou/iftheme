<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_help_front extends WYSIJA_help{
    function WYSIJA_help_front(){
        parent::WYSIJA_help();

        
        
        

        
        if(isset($_REQUEST['wysija-page']) || isset($_REQUEST['wysija-launch'])){
            if(defined('DOING_AJAX')){
                add_action('wp_ajax_nopriv_wysija_ajax', array($this, 'ajax'));
            }else{
                $paramscontroller=$_REQUEST['controller'];

                if($paramscontroller=='stat') $paramscontroller='stats';
                $this->controller=&WYSIJA::get($paramscontroller,'controller');
                if(method_exists($this->controller, $_REQUEST['action'])){
                    add_action('init',array($this->controller,$_REQUEST['action']));

                }else $this->error('Action does not exist.');
                if(isset($_REQUEST['wysija-page'])){
                    
                    add_filter('wp_title', array($this,'meta_page_title'));
                    add_filter( 'the_title', array($this,'scan_title'));
                    add_filter( 'the_content', array($this,'scan_content'),98);
                    if(isset($_REQUEST['message_success'])){
                        add_filter( 'the_content', array($this,'scan_content_NLform'),99 );
                    }
                }
                if(isset($_REQUEST['wysija-page'])){
                    
                    add_filter('wp_title', array($this,'meta_page_title'));
                    add_filter( 'the_title', array($this,'scan_title'));
                    add_filter( 'the_content', array($this,'scan_content'),98);
                    if(isset($_REQUEST['message_success'])){
                        add_filter( 'the_content', array($this,'scan_content_NLform'),99 );
                    }
                }
            }
        }else{
            add_filter('the_content', array($this,'scan_content_NLform'),99 );
            add_shortcode('wysija_form', array($this,'scan_form_shortcode'));

           $mConfig=&WYSIJA::get('config','model');
           if($mConfig->getValue('commentform')){
                add_action('comment_form', array($this,'comment_form_extend'));
                add_action('comment_post',  array($this,'comment_posted'), 60,2);
           }

           if($mConfig->getValue('registerform')){
               if(is_multisite()){
                   add_action('signup_extra_fields', array($this,'register_form_extend'));
                   add_filter('wpmu_validate_user_signup',  array($this,'registerms_posted'), 60,3);
               }else{
                   add_action('register_form', array($this,'register_form_extend'));
                   add_action('register_post',  array($this,'register_posted'), 60,3);
               }
                if(WYSIJA::is_plugin_active('buddypress/bp-loader.php')){
                    add_action('bp_after_signup_profile_fields', array($this,'register_form_bp_extend'));
                    add_action('bp_signup_validate', array($this,'register_bp'),60,3);
                }
           }
        }
    }
    function meta_page_title(){

        return $this->controller->title;
    }

    function register_form_bp_extend(){
        if ( !is_user_logged_in()){
            $this->register_form_extend();
        }
    }
    function register_form_extend(){
        $checkbox= '<p class="wysija-after-register">';
        $checkbox.='<label for="wysija-box-after-register">';
        $checkbox.='<input type="checkbox" id="wysija-box-after-register" value="1" name="wysija[register_subscribe]">';
        $mConfig=&WYSIJA::get('config','model');
        $checkbox.=$mConfig->getValue('registerform_linkname').'</label></p>';
        echo '<div class="register-section" id="profile-details-section-wysija"><div class="editfield">'.$checkbox.'</div></div>';
    }

    function register_bp(){
        global $bp;
        if ( !isset($bp->signup->errors) && isset($_POST['wysija']['register_subscribe']) && $_POST['wysija']['register_subscribe'] ) {
            $mConfig=&WYSIJA::get('config','model');
            $userHelper=&WYSIJA::get('user','helper');
            $data=array('user'=>array('email'=>$bp->signup->email),'user_list'=>array('list_ids'=>$mConfig->getValue('registerform_lists')));
            $userHelper->addSubscriber($data);
        }
    }
    function registerms_posted($result){
        if ( empty($result['errors']->errors) && isset($_POST['wysija']['register_subscribe']) && $_POST['wysija']['register_subscribe']) {
            $mConfig=&WYSIJA::get('config','model');
            $userHelper=&WYSIJA::get('user','helper');
            $data=array('user'=>array('email'=>$result['user_email']),'user_list'=>array('list_ids'=>$mConfig->getValue('registerform_lists')));
            $userHelper->addSubscriber($data);
        }
        return $result;
    }
    function register_posted($login,$email,$errors){
        if ( empty($errors->errors) && isset($_POST['wysija']['register_subscribe']) && $_POST['wysija']['register_subscribe']) {
            $mConfig=&WYSIJA::get('config','model');
            $userHelper=&WYSIJA::get('user','helper');
            $data=array('user'=>array('email'=>$email),'user_list'=>array('list_ids'=>$mConfig->getValue('registerform_lists')));
            $userHelper->addSubscriber($data);
        }
    }

    function comment_form_extend(){
        echo '<p class="wysija-after-comment">';
        echo '<label for="wysija-box-after-comment">';
        echo '<input type="checkbox" id="wysija-box-after-comment" value="1" name="wysija[comment_subscribe]">';
        $mConfig=&WYSIJA::get('config','model');
        echo $mConfig->getValue('commentform_linkname').'</label></p>';
    }
    function comment_posted($cid,$comment){
        $cid = (int) $cid;
        if ( !is_object($comment) )
            $comment = get_comment($cid);

        if($comment->comment_approved=='spam') return;
        if(isset($_POST['wysija']['comment_subscribe']) && $_POST['wysija']['comment_subscribe']) {
            if($comment->comment_approved=='0')  add_comment_meta($cid, 'wysija_comment_subscribe', 1);
            else{
                $mConfig=&WYSIJA::get('config','model');
                $userHelper=&WYSIJA::get('user','helper');
                $data=array('user'=>array('email'=>$comment->comment_author_email,'firstname'=>$comment->comment_author),'user_list'=>array('list_ids'=>$mConfig->getValue('commentform_lists')));
                $userHelper->addSubscriber($data);
            }
        }
    }
    function scan_title($title){
        
        global $post;
        if(trim($title)==trim(single_post_title( '', false )) && !empty($this->controller->title)){
            $post->comment_status='close';
            $post->post_password='';
            return $this->controller->title;
        }else{
            return $title;
        }
    }
    function scan_content($content){
        $wysija_content='';
        if(isset($this->controller->subtitle) && !empty($this->controller->subtitle))  $wysija_content=$this->controller->subtitle;
        return str_replace('[wysija_page]',$wysija_content,$content);
    }
    
    function scan_form_shortcode($attributes) {
        if(isset($attributes['id']) && (int)$attributes['id']>0){
            $widget_data=array();
            $widget_data['form']=(int)$attributes['id'];
            $widget_data['form_type']='post';
            $widget_NL=new WYSIJA_NL_Widget(true);
            return $widget_NL->widget($widget_data);
        }
        return '';
    }
    function scan_content_NLform($content){
        preg_match_all('/\<div class="wysija-register">(.*?)\<\/div>/i',$content,$matches);
        if(!empty($matches[1]) && count($matches[1])>0)   require_once(WYSIJA_WIDGETS.'wysija_nl.php');
        foreach($matches[1] as $key => $mymatch){
            if($mymatch){
                $widgetdata=unserialize(base64_decode($mymatch));
                $widgetNL=new WYSIJA_NL_Widget(true);
                $contentTABLE= $widgetNL->widget($widgetdata,$widgetdata);
                $content=str_replace($matches[0][$key],$contentTABLE,$content);
            }//endif
        }//endforeach
        return $content;
    }
}
