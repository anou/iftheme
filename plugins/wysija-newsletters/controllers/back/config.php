<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_config extends WYSIJA_control_back{
    var $view="config";
    var $model="config";

    function WYSIJA_control_back_config(){

    }


    function main(){
        parent::WYSIJA_control_back();
        $this->js[]='jquery-ui-tabs';
        $this->js[]='wysija-admin-ajax';
        $this->js[]='thickbox';
        $this->js[]='wysija-validator';
        wp_enqueue_style( 'thickbox' );

        wp_enqueue_style('wysija-admin-edit-tb', WYSIJA_URL.'css/admin-editor-forms.css',array(),WYSIJA::get_version());
        wp_enqueue_script('wysija-admin-edit-forms', WYSIJA_URL.'js/admin-editor-forms.js', array('jquery'), WYSIJA::get_version());

        if(!isset($_REQUEST['action'])) $this->action='main';
        else $this->action=$_REQUEST['action'];
        $localaction=$this->action;
        switch($localaction){
            case 'log':
            case 'save':
            case 'clearlog':
                return $this->$localaction();
                break;
            case "reinstall":
                $this->reinstall();
                //if(defined('WYSIJA_REDIRECT'))  $this->redirectProcess();

                return;
                break;
            case 'dkimcheck':

                $this->dkimcheck();
                if(defined('WYSIJA_REDIRECT'))  $this->redirectProcess();
                return;
                break;
            case "doreinstall":
                $this->doreinstall();
                if(defined('WYSIJA_REDIRECT')){
                     global $wysi_location;
                     $wysi_location='admin.php?page=wysija_campaigns';
                    $this->redirectProcess();
                }
                return;
                break;
        }

        if(WYSIJA_DBG>1){
            $this->viewObj->arrayMenus=array('log'=>'View log');
        }


        $this->data=array();
        $this->action="main";
        $this->jsTrans["testemail"]=__("Sending a test email",WYSIJA);
        $this->jsTrans["bounceconnect"]=__("Bounce handling connection test",WYSIJA);
        $this->jsTrans["processbounceT"]=__("Bounce handling processing",WYSIJA);
        $this->jsTrans["doubleoptinon"]=__("Subscribers will now need to activate their subscription by email in order to receive your newsletters. This is recommended.",WYSIJA);
        $this->jsTrans["doubleoptinoff"]=__("Unconfirmed subscribers will receive your newslettters from now on without the need to activate their subscriptions.",WYSIJA);
        $this->jsTrans["processbounce"]=__("Process bounce handling now!",WYSIJA);
        $this->jsTrans["errorbounceforward"]=__("When setting up the bounce system, you need to have a different address for the bounce email and the forward to address",WYSIJA);

        if(isset($_REQUEST['validate'])){
            $this->notice(str_replace(array('[link]','[/link]'),
            array('<a title="'.__('Get Premium now',WYSIJA).'" class="premium-tab" href="javascript:;">','</a>'),
            __('You\'re almost there. Click this [link]link[/link] to activate the licence you have just purchased.',WYSIJA)));

        }

    }

    function dkimcheck(){

        if(isset($_POST['xtz'])){

            $dataconf=json_decode(base64_decode($_POST['xtz']));

            if(isset($dataconf->dkim_pubk->key) && isset($dataconf->dkim_privk)){

                $modelConf=&WYSIJA::get('config','model');
                $dataconfsave=array('dkim_pubk'=>$dataconf->dkim_pubk->key, 'dkim_privk'=>$dataconf->dkim_privk);

                $modelConf->save($dataconfsave);
                WYSIJA::update_option('dkim_autosetup',false);
            }
        }

        $this->redirect('admin.php?page=wysija_config');
        return true;
    }

    function save(){
        $_REQUEST   = stripslashes_deep($_REQUEST);
        $_POST   = stripslashes_deep($_POST);
        $this->requireSecurity();
        $this->modelObj->save($_REQUEST['wysija']['config'],true);
        wp_redirect('admin.php?page=wysija_config'.$_REQUEST['redirecttab']);

    }

    function reinstall(){

        $this->viewObj->title=__('Reinstall Wysija?',WYSIJA);
        return true;
    }

    function changeMode(){
        $helperFile=&WYSIJA::get('file','helper');
        $helperFile->chmodr(WYSIJA_UPLOADS_DIR, 0666, 0777);
        $this->redirect("admin.php?page=wysija_config");
        return true;
    }

    function doreinstall(){

        if(isset($_REQUEST['postedfrom']) && $_REQUEST['postedfrom']=='reinstall'){
            $uninstaller=&WYSIJA::get('uninstall','helper');
            $uninstaller->reinstall();

        }
        $this->redirect('admin.php?page=wysija_config');
        return true;
    }

    function render(){
        $this->checkTotalSubscribers();
        $this->viewObj->render($this->action,$this->data);
    }

    function log(){
        $this->viewObj->arrayMenus=array('clearlog'=>'Clear log');
        $this->viewObj->title='Wysija\'s log';
        return true;
    }

    function clearlog(){
        update_option('wysija_log', array());
        $this->redirect('admin.php?page=wysija_config&action=log');
        return true;
    }


}
