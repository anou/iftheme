<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_server extends WYSIJA_object {
    function WYSIJA_help_server() {
    }
    
    function unhealthy($return_result=false){
        $server_missing_capabilities=array();
        $missing_functions_result=$this->missing_php_functions();

        if($missing_functions_result) $server_missing_capabilities['functions']=$missing_functions_result;

        if(!$this->can_make_dir()) $server_missing_capabilities['mkdir']=true;

        if(!$this->can_unzip()) $server_missing_capabilities['unzip']=true;

        if(!$this->can_move()) $server_missing_capabilities['move']=true;

        if(!$this->can_sql_create_tables()) $server_missing_capabilities['sql_create']=true;

        if(!$this->can_sql_alter_tables()) $server_missing_capabilities['sql_alter']=true;
        if(!empty($server_missing_capabilities))  return $server_missing_capabilities;
        else return false;
    }
    
    function missing_php_functions(){

        $functions_per_environment=array(
            'required'=> array(
                'functions' => array('base64_decode', 'base64_encode'),
                ),
            'remote calls' => array(
                'functions'=>array('curl_init', 'http_get', 'file_get_contents'),
            ),
            'DKIM signature'=>array(
                'functions'=>array('openssl_sign')
                )
            );
        $missing_functions=array();
        foreach($functions_per_environment as $environment => &$data){

            foreach($data['functions'] as $function_name){
                if($this->is_function_available($function_name)){
                    $data['functions'][$function_name]=true;
                }else{
                    $missing_functions[$environment][$function_name]=true;
                }
            }
        }
        if(!empty($missing_functions))    return $missing_functions;
        return false;
    }

        
    function is_function_available($function_name) {

        if(!is_string($function_name) || $function_name=='') return false;

        $disabled = explode(', ', ini_get('disable_functions'));

        if(function_exists($function_name) && !in_array(strtolower($function_name), $disabled)) return true;
        return false;
    }
    
    function can_make_dir(){

        $hFile = &WYSIJA::get('file','helper');
        $upload_dir = wp_upload_dir();
        $temp_dir = $hFile->makeDir();
        if (!$temp_dir) {
            $this->error(sprintf(__('The folder "%1$s" is not writable, please change the access rights to this folder so that Wysija can setup itself properly.',WYSIJA),$upload_dir['basedir']).'<a target="_blank" href="http://codex.wordpress.org/Changing_File_Permissions">'.__('Read documentation',WYSIJA).'</a>');
            return false;
        } else {

            $index_file = 'index.html';
            fclose(fopen($temp_dir.$index_file, 'w'));
            return true;
        }
    }
    
    function can_unzip(){
        return true;
    }
    
    function can_move(){
        return true;
    }
    
    function can_sql_create_tables(){

        $model_user=&WYSIJA::get('user','model');
        $this->_create_temp_sql_table_if_not_exists();
        $query="SHOW TABLES like '".$model_user->getPrefix()."user_list_temp';";
        global $wpdb;
        $res=$wpdb->get_var($query);
        if(!$res){
            $this->error(sprintf(
                    __('The MySQL user you have setup on your Wordpress site (wp-config.php) doesn\'t have enough privileges to CREATE MySQL tables. Please change this user yourself or contact the administrator of your site in order to complete Wysija\'s installation. mysql errors:(%1$s)',WYSIJA),  mysql_error()));
            return false;
        }
        return true;
    }
    
    function can_sql_alter_tables(){

        $this->_create_temp_sql_table_if_not_exists();

        $model_user=&WYSIJA::get('user','model');
        $query='ALTER TABLE `'.$model_user->getPrefix().'user_list_temp` ADD `namekey` VARCHAR( 255 ) NULL;';
        global $wpdb;
        if(!mysql_query($query, $wpdb->dbh)){
            $error_message=__('The MySQL user you have setup on your Wordpress site (wp-config.php) doesn\'t have enough privileges to CREATE MySQL tables. Please change this user yourself or contact the administrator of your site in order to complete Wysija\'s installation. mysql errors:(%1$s)',WYSIJA);
            $this->error(sprintf(str_replace('CREATE', 'ALTER', $error_message), mysql_error($wpdb->dbh)));
            $this->_drop_temp_sql_table();
            return false;
        }
        $this->_drop_temp_sql_table();
        return true;
    }
    
    function _create_temp_sql_table_if_not_exists(){
        $model_user=&WYSIJA::get('user','model');
        $query='CREATE TABLE IF NOT EXISTS `'.$model_user->getPrefix().'user_list_temp` (
  `list_id` INT unsigned NOT NULL,
  `user_id` INT unsigned NOT NULL,
  `sub_date` INT unsigned DEFAULT 0,
  `unsub_date` INT unsigned DEFAULT 0,
  PRIMARY KEY (`list_id`,`user_id`)
) ENGINE=MyISAM';
        global $wpdb;
        $wpdb->query($query);
        return true;
    }

    
    function _drop_temp_sql_table(){
        $model_user=&WYSIJA::get('user','model');
        global $wpdb;
        $query='DROP TABLE `'.$model_user->getPrefix().'user_list_temp`;';
        if(!mysql_query($query, $wpdb->dbh)){
            return false;
        }
        return true;
    }
}