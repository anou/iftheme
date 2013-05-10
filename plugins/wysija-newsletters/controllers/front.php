<?php
defined('WYSIJA') or die('Restricted access');


class WYSIJA_control_front extends WYSIJA_control{

    function WYSIJA_control_front($extension="wysija-newsletters"){
        $this->extension=$extension;
        parent::WYSIJA_control();
        $_REQUEST   = stripslashes_deep($_REQUEST);
        $_POST   = stripslashes_deep($_POST);
        $this->action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'index';
    }

    function save(){
        $this->requireSecurity();
        /* see if it's an update or an insert */
        /*get the pk and its value as a conditions where pk = pkval*/
        $conditions=$this->getPKVal($this->modelObj);

        if($conditions){
            /* this an update */

            $result=$this->modelObj->update($_REQUEST['wysija'][$this->model],$conditions);

            if($result) $this->notice($this->messages['update'][true]);
            else{
                $this->error($this->messages['update'][false],true);
            }

        }else{
            /* this is an insert */
            unset($_REQUEST['wysija'][$this->modelObj->pk]);

            $result=$this->modelObj->insert($_REQUEST['wysija'][$this->model]);

            if($result) $this->notice($this->messages['insert'][true]);
            else{
                $this->error($this->messages['insert'][false],true);
            }

        }
        return $result;
    }

    function redirect($location){
        //header("Location: $location", true, $status);
        wp_redirect($location);
        exit;
    }

}
