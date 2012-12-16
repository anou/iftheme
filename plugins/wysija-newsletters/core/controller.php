<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_control extends WYSIJA_object{
    var $model="";
    var $view="";
    var $action="";
    var $list_columns=array();
    var $form_columns=array();
    var $filters=array();
    var $js=array();
    var $jsLoc=array();
    var $extension="wysija-newsletters";
    var $joins=array();
    var $title="";

    function WYSIJA_control(){
        /*setup some required objects for the request*/

        if(!defined('DOING_AJAX')){
            if($this->view) $this->viewObj=&WYSIJA::get($this->view,"view",false,$this->extension);

            if($this->model){

                $this->modelObj=&WYSIJA::get($this->model,"model",false,$this->extension);
                $this->viewObj->model=&WYSIJA::get($this->model,"model",false,$this->extension);
            }
        }


        /**
         * test for security, some actions require security some others don't
         */

        if(isset($_REQUEST['_wpnonce'])){
            $_REQUEST['wpnonceback']=$_REQUEST['_wpnonce'];

            if($_REQUEST['action']=="wysija_ajax"){
                $actionnonce="wysija_ajax";
            }else{
                if(isset($_REQUEST['page'])){
                    $actionnonce=$_REQUEST['page']."-action_".$_REQUEST['action'];
                    if(isset($_REQUEST['id'])) $actionnonce.="-id_".$_REQUEST['id'];
                }elseif(isset($_REQUEST['controller'])){
                    $actionnonce=$_REQUEST['controller']."-action_".$_REQUEST['action'];
                    if(isset($_REQUEST['id'])) $actionnonce.="-id_".$_REQUEST['id'];
                }
            }

            if(!$_REQUEST['action']) return true;

           /*if the wp_nonce has been set up then we test it against the one here if it fails we just die*/
           $nonce=$_REQUEST['_wpnonce'];

           if(!function_exists('wp_verify_nonce')) include(ABSPATH.'wp-includes'.DS.'pluggable.php');
           if(!wp_verify_nonce($nonce, $actionnonce) ) die('Security failure during request.');
        }
    }


    function requireSecurity(){

        if(!isset($_REQUEST['wpnonceback']) && !isset($_REQUEST['_wpnonce'])) {
           die("Your request is not safe.");
        }else{
            return true;
        }

    }


    function getPKVal(){

        if(isset($_POST['wysija'][$this->modelObj->table_name][$this->modelObj->pk]) && $_POST['wysija'][$this->modelObj->table_name][$this->modelObj->pk]){
            /* this is an update */
            $conditions=array($this->modelObj->pk =>$_POST['wysija'][$this->modelObj->table_name][$this->modelObj->pk]);
            unset($_POST['wysija'][$this->modelObj->table_name][$this->modelObj->pk]);

        }elseif(isset($_GET['id'])){
            $conditions=array($this->modelObj->pk =>$_GET['id']);
        }else{
            $conditions=array();
        }

        return $conditions;
    }

}