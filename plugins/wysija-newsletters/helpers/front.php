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

                }else $this->error('Action does not exists.');
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
           $mConfig=&WYSIJA::get('config','model');
           if($mConfig->getValue('commentform')){
                add_action('comment_form', array($this,'comment_form_extend'));
                add_action('comment_post',  array($this,'comment_posted'), 60,2);
           }
        }
    }
    function meta_page_title(){

        return $this->controller->title;
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
        if ( $comment->comment_karma == 0 && isset($_POST['wysija']['comment_subscribe']) && $_POST['wysija']['comment_subscribe']) {
            $mConfig=&WYSIJA::get('config','model');
            $userHelper=&WYSIJA::get('user','helper');
            $data=array('user'=>array('email'=>$comment->comment_author_email,'firstname'=>$comment->comment_author),'user_list'=>array('list_ids'=>$mConfig->getValue('commentform_lists')));
            $userHelper->addSubscriber($data);
        }
    }
    function scan_title($title){
        
        global $post;
        if(trim($title)==trim(single_post_title( '', false ))){
            $post->comment_status="close";
            $post->post_password="";
            return $this->controller->title;
        }else{
            return $title;
        }
    }
    function scan_content($content){
        $wysija_content="";
        if(isset($this->controller->subtitle))  $wysija_content=$this->controller->subtitle;
        return str_replace("[wysija_page]",$wysija_content,$content);
    }

    function scan_content_NLform($content){
        preg_match_all('/\<div class="wysija-register">(.*?)\<\/div>/i',$content,$matches);
        if(!empty($matches[1]) && count($matches[1])>0)   require_once(WYSIJA_WIDGETS.'wysija_nl.php');
        foreach($matches[1] as $key => $mymatch){
            if($mymatch){
                $widgetdata=unserialize(base64_decode($mymatch));
                $widgetNL=new WYSIJA_NL_Widget(1);
                $contentTABLE= $widgetNL->widget($widgetdata,$widgetdata);
                $content=str_replace($matches[0][$key],$contentTABLE,$content);
            }//endif
        }//endforeach
        return $content;
    }
}
