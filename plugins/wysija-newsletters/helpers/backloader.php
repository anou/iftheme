<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_help_backloader extends WYSIJA_help{
    var $jsVariables='';
    function WYSIJA_help_backloader(){
        parent::WYSIJA_help();
    }
    function initLoad(&$controller){
        wp_enqueue_style('wysija-admin-css', WYSIJA_URL.'css/admin.css',array(),WYSIJA::get_version());
        wp_enqueue_script('wysija-admin', WYSIJA_URL.'js/admin.js', array( 'jquery' ), true, WYSIJA::get_version());
        
        wp_enqueue_script('wysija-admin-if', WYSIJA_URL.'js/admin-wysija.js', array( 'jquery' ),WYSIJA::get_version());

        if(!$controller->jsTrans){
            $controller->jsTrans["selecmiss"]=__('Please make a selection first!',WYSIJA);
            $controller->jsTrans["suredelete"]=__('Deleting a list will not delete any subscribers.',WYSIJA);
        }
        $controller->js[]='wysija-admin-ajax';
        $controller->js[]='thickbox';
        wp_enqueue_style( 'thickbox' );
    }
    function loadScriptsStyles($pagename,$dirname,$urlname,&$controller,$extension="newsletter") {
        
            if(file_exists($dirname."js".DS."admin-".$pagename.".js"))
                wp_enqueue_script('wysija-'.$extension.'-admin-'.$pagename, $urlname."js/admin-".$pagename.".js",array(),WYSIJA::get_version());
            
            if(file_exists($dirname."js".DS."admin-".$pagename.".php"))
                wp_enqueue_script('wysija-'.$extension.'-admin-'.$pagename, $urlname."js/admin-".$pagename.".php",array(),WYSIJA::get_version());
            if(file_exists($dirname."css".DS."admin-".$pagename.".css"))
                wp_enqueue_style('wysija-'.$extension.'-admin-'.$pagename."css", $urlname."css/admin-".$pagename.".css",array(),WYSIJA::get_version());

            if(isset($_GET['action'])){
                
                if(file_exists($dirname."js".DS."admin-".$pagename."-".$_REQUEST['action'].".js"))
                    wp_enqueue_script('wysija-'.$extension.'-admin-'.$pagename."-".$_REQUEST['action'], $urlname."js/admin-".$pagename."-".$_REQUEST['action'].".js",array(),WYSIJA::get_version());
                
                if(file_exists($dirname."js".DS."admin-".$pagename."-".$_REQUEST['action'].".php"))
                    wp_enqueue_script('wysija-'.$extension.'-admin-'.$pagename."-".$_REQUEST['action'], $urlname."js/admin-".$pagename."-".$_REQUEST['action'].".php",array(),WYSIJA::get_version());
                if(file_exists($dirname."css".DS."admin-".$pagename."-".$_REQUEST['action'].".css"))
                    wp_enqueue_style('wysija-'.$extension.'-admin-'.$pagename."-".$_REQUEST['action'], $urlname."css/admin-".$pagename."-".$_REQUEST['action'].".css",array(),WYSIJA::get_version());
                
                if($_GET['action']=="edit" || $_GET['action']=="add")
                    $controller->js[]="wysija-validator";
            }else{
                if(file_exists($dirname."js".DS."admin-".$pagename."-default".".js"))
                    wp_enqueue_script('wysija-'.$extension.'-admin-'.$pagename."-default", $urlname."js/admin-".$pagename."-default.js",array(),WYSIJA::get_version());
                if($pagename!="config")  wp_enqueue_script('wysija-admin-list');
            }
        return true;
    }

    function jsParse(&$controller,$pagename,$urlbase=WYSIJA_URL){

        $plugin = substr(strrchr(substr($urlbase, 0, strlen($urlbase)-1), '/'), 1);
        
            if($controller->js){
                foreach($controller->js as $kjs=> $js){
                    switch($js){
                        case "jquery-ui-tabs":
                            wp_enqueue_script($js);



                            wp_enqueue_style('wysija-tabs-css', WYSIJA_URL."css/smoothness/jquery-ui-1.8.20.custom.css",array(),WYSIJA::get_version());
                            break;
                        case "wysija-validator":
                            wp_enqueue_script('wysija-validator-lang');
                            wp_enqueue_script($js);
                            wp_enqueue_script('wysija-form');
                            wp_enqueue_style('validate-engine-css');
                            break;
                        case "wysija-admin-ajax":
                            if($plugin!='wysija-newsletters')   $ajaxvarname=$plugin;
                            else $ajaxvarname='wysija';
                            $dataajaxxx=array(
                                'action' => 'wysija_ajax',
                                'controller' => $pagename,
                                'wysijaplugin' => $plugin,
                                'dataType'=>"json",
                                'ajaxurl'=>admin_url( 'admin-ajax.php', 'relative' ),
                                'loadingTrans'  =>__('Loading...',WYSIJA)
                            );
                            if(is_user_logged_in()){
                                $dataajaxxx['adminurl']=admin_url( 'admin.php' );
                                $dataajaxxx['wysilog']='1';
                            }
                            wp_localize_script( 'wysija-admin-ajax', $ajaxvarname.'AJAX',$dataajaxxx );
                            wp_enqueue_script("jquery-ui-dialog");
                            wp_enqueue_script($js);
                            wp_enqueue_style('wysija-tabs-css', WYSIJA_URL."css/smoothness/jquery-ui-1.8.20.custom.css",array(),WYSIJA::get_version());
                            break;
                        case "wysija-admin-ajax-proto":
                            
                            wp_enqueue_script($js);
                            break;
                        case 'wysija-edit-autonl':
                            wp_enqueue_script('wysija-edit-autonl', WYSIJA_URL."js/admin-campaigns-editAutonl.js",array('jquery'),WYSIJA::get_version());
                            break;
                        case "wysija-editor":
                            wp_enqueue_script("wysija-prototype", WYSIJA_URL."js/prototype/prototype.js",array(),WYSIJA::get_version());
                            wp_deregister_script('thickbox');
                            wp_register_script('thickbox',WYSIJA_URL."js/thickbox/thickbox.js",array(),WYSIJA::get_version());
                            wp_localize_script('thickbox', 'thickboxL10n', array(
                                'next' => __('Next &gt;'),
                                'prev' => __('&lt; Prev'),
                                'image' => __('Image'),
                                'of' => __('of'),
                                'close' => __('Close'),
                                'noiframes' => __('This feature requires inline frames. You have iframes disabled or your browser does not support them.'),
                                'l10n_print_after' => 'try{convertEntities(thickboxL10n);}catch(e){};'
                            ));
                            wp_enqueue_script("wysija-proto-scriptaculous", WYSIJA_URL."js/prototype/scriptaculous.js",array("wysija-prototype"),WYSIJA::get_version());
                            wp_enqueue_script("wysija-proto-dragdrop", WYSIJA_URL."js/prototype/dragdrop.js",array("wysija-proto-scriptaculous"),WYSIJA::get_version());
                            wp_enqueue_script("wysija-proto-controls", WYSIJA_URL."js/prototype/controls.js",array("wysija-proto-scriptaculous"),WYSIJA::get_version());
                            wp_enqueue_script("wysija-timer", WYSIJA_URL."js/timer.js",array(),WYSIJA::get_version());
                            wp_enqueue_script($js, WYSIJA_URL."js/".$js.".js",array(),WYSIJA::get_version());
                            wp_enqueue_script('wysija-konami', WYSIJA_URL."js/konami.js",array(),WYSIJA::get_version());
                            wp_enqueue_script('wysija-tinymce', WYSIJA_URL."js/tinymce/tiny_mce.js",array(),WYSIJA::get_version());
                            wp_enqueue_script('wysija-tinymce-init', WYSIJA_URL."js/tinymce_init.js",array(),WYSIJA::get_version());
                            wp_enqueue_style('wysija-editor-css', WYSIJA_URL."css/wysija-editor.css",array(),WYSIJA::get_version());
                            wp_enqueue_script('wysija-colorpicker', WYSIJA_URL."js/excolor/jquery.modcoder.excolor.js",array(),WYSIJA::get_version());
                            
                            wp_localize_script('wysija-editor', 'Wysija_i18n', $controller->jsTrans);
                            break;
                        case 'wysija-colorpicker':
                            wp_enqueue_script('wysija-colorpicker', WYSIJA_URL."js/excolor/jquery.modcoder.excolor.js",array(),WYSIJA::get_version());
                            break;
                        default:
                            if(is_string($kjs)) {

                                if(substr($urlbase, -1) !== '/') $urlbase .= '/';

                                if(substr($urlbase, -3) !== '.js') $js .= '.js';

                                wp_enqueue_script($kjs, $urlbase.'js/'.$js,array(),WYSIJA::get_version());
                            } else {
                                wp_enqueue_script($js);
                            }
                    }
                }
            }

    }
    function localize($pagename,$dirname,$urlname,&$controller,$extension="newsletter"){
        if($controller->jsLoc){
            foreach($controller->jsLoc as $key =>$value){
                foreach($value as $kf => $local){


                    $this->localizeme($key, $kf, $local);
                }
            }
        }
    }
    function localizeme( $handle, $object_name, $l10n ) {
		foreach ( (array) $l10n as $key => $value ) {
			if ( !is_scalar($value) )
				continue;
			$l10n[$key] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8');
		}
		$this->jsVariables.= "var $object_name = " . json_encode($l10n) . ';';

                add_action('admin_head',array($this,'printAdminLocalized'));

	}
    function printAdminLocalized(){
        echo "<script type='text/javascript'>\n"; // CDATA and type='text/javascript' is not needed for HTML 5
        echo "\n";
        echo $this->jsVariables."\n";
        echo "\n";
        echo "</script>\n";
    }
}

