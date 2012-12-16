<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_uninstall extends WYSIJA_object{
    function WYSIJA_help_uninstall(){

    }
    function reinstall(){
        if($this->removeProcess()) $this->notice(__("Wysija has been reinstalled successfully using the same version. Settings and data have been deleted.",WYSIJA));
    }
    function uninstall(){
        if($this->removeProcess()) $this->wp_notice(__("Wysija has been uninstalled. Your site is now cleared of Wysija.",WYSIJA));
    }
    function removeProcess(){
        if(is_admin()){
            $filename = dirname(__FILE__).DS.'uninstall.sql';
            $handle = fopen($filename, 'r');
            $query = fread($handle, filesize($filename));
            fclose($handle);
            $modelObj=&WYSIJA::get('user','model');
            $queries=str_replace('DROP TABLE `','DROP TABLE `[wysija]',$query);
            $queries=explode('-- QUERY ---',$queries);
            $modelWysija=new WYSIJA_model();
            global $wpdb;
            foreach($queries as $query)
                $modelWysija->query($query);
            delete_option('wysija');
            WYSIJA::update_option('wysija_reinstall',1);
            global $wp_roles;
            foreach($wp_roles->roles as $rolek=>$roled){
                if($rolek=='administrator') continue;
                $role=get_role($rolek);

                $arr=array('wysija_newsletters','wysija_subscribers','wysija_subscriwidget','wysija_config');
                foreach($arr as $arrkey)    $role->remove_cap( $arrkey );
            }
            return true;
        }
        return false;
    }

}
