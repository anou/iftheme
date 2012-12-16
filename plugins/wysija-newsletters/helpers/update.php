<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_update extends WYSIJA_object{
    function WYSIJA_help_update(){
        $this->modelWysija=new WYSIJA_model();
        
        $this->updates=array('1.1','2.0','2.1','2.1.6','2.1.7','2.1.8');
    }

    function runUpdate($version){


        switch($version){
            case '1.1':
                
                $modelconfig=&WYSIJA::get('config','model');
                if(!$this->modelWysija->query("SHOW COLUMNS FROM `[wysija]list` LIKE 'namekey';")){
                    $querys[]='ALTER TABLE `[wysija]list` ADD `namekey` VARCHAR( 255 ) NULL;';
                }
                $querys[]="UPDATE `[wysija]list` SET `namekey` = 'users' WHERE `list_id` =".$modelconfig->getValue('importwp_list_id').";";
                $errors=$this->runUpdateQueries($querys);
                $importHelp=&WYSIJA::get('import','helper');
                $importHelp->testPlugins();

                $installHelper =& WYSIJA::get('install', 'helper');
                $installHelper->moveData('dividers');
                $installHelper->moveData('bookmarks');
                $installHelper->moveData('themes');
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            case '2.0':
                
                $modelconfig=&WYSIJA::get('config','model');
                if(!$this->modelWysija->query("SHOW COLUMNS FROM `[wysija]email` LIKE 'modified_at';")){
                    $querys[]="ALTER TABLE `[wysija]email` ADD `modified_at` INT UNSIGNED NOT NULL DEFAULT '0';";
                }
                if(!$modelconfig->getValue('update_error_20')){
                    $querys[]="UPDATE `[wysija]email` SET `modified_at` = `sent_at`  WHERE `sent_at`>=0;";
                    $querys[]="UPDATE `[wysija]email` SET `modified_at` = `created_at` WHERE `modified_at`='0';";
                    $querys[]="UPDATE `[wysija]email` SET `status` = '99' WHERE `status` ='1';";//change sending status from 1 to 99
                }

                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $modelconfig->save(array('update_error_20'=>true));
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            case '2.1':
                $modelconfig=&WYSIJA::get('config','model');
                if(!$modelconfig->getValue('update_error_21')){
                    $modelEmails=&WYSIJA::get('email','model');
                    $modelEmails->reset();
                    $emailsLoaded=$modelEmails->get(array('subject','email_id'),array('status'=>2,'type'=>1));
                    
                    $wptools =& WYSIJA::get('wp_tools', 'helper');
                    $wptools->set_default_rolecaps();
                     

                    $minimumroles=array('role_campaign'=>'wysija_newsletters','role_subscribers'=>'wysija_subscribers');
                    foreach($minimumroles as $rolename=>$capability){
                        $rolesetting=$modelconfig->getValue($rolename);
                        switch($rolesetting){
                            case 'switch_themes':
                                $keyrole=1;
                                break;
                            case 'moderate_comments':
                                $keyrole=3;
                                break;
                            case 'upload_files':
                                $keyrole=4;
                                break;
                            case 'edit_posts':
                                $keyrole=5;
                                break;
                            case 'read':
                                $keyrole=6;
                                break;
                            default:
                                $keyrole=false;
                        }
                        if(!$keyrole){

                            $role = get_role($rolesetting);

                            if($role){
                                $role->add_cap( $capability );
                            }
                        }else{

                            $editable_roles=$wptools->wp_get_roles();
                            $startcount=1;
                            if(!isset($editable_roles[$startcount])) $startcount++;
                            for($i = $startcount; $i <= $keyrole; $i++) {
                                $rolename=$editable_roles[$i];

                                $role = get_role($rolename['key']);
                                $role->add_cap( $capability );
                            }
                        }
                    }
                    $wptoolboxs =& WYSIJA::get('toolbox', 'helper');
                    $modelconfig->save(array('dkim_domain'=>$wptoolboxs->_make_domain_name()));
                }
                if(!$this->modelWysija->query("SHOW COLUMNS FROM `[wysija]list` LIKE 'is_public';")){
                    $querys[]="ALTER TABLE `[wysija]list` ADD `is_public` TINYINT UNSIGNED NOT NULL DEFAULT 0;";
                    $errors=$this->runUpdateQueries($querys);
                    if($errors){
                        $modelconfig->save(array('update_error_21'=>true));
                        $this->error(implode($errors,"\n"));
                        return false;
                    }
                }
                return true;
            break;
            case '2.1.6':
                $querys[]="UPDATE `[wysija]user_list` as A inner join `[wysija]user` as B on (A.user_id= B.user_id) set A.sub_date= B.created_at where A.sub_date=0 and A.unsub_date=0 and B.status>-1;";
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            case '2.1.7':
                $querys[]='UPDATE `[wysija]user_list` as A inner join `[wysija]user` as B on (A.user_id= B.user_id) set A.sub_date= '.time().' where A.sub_date=0 and B.status>-1;';
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
           case '2.1.8':
               $mConfig=&WYSIJA::get('config','model');
               $querys[]='UPDATE `[wysija]user_list` as A set A.sub_date= '.time().' where A.list_id='.$mConfig->getValue('importwp_list_id').';';
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            default:
                return false;
        }
        return false;
    }
    
    function checkForNewVersion($file='wysija-newsletters/index.php'){
        $current = get_site_transient( 'update_plugins' );
	if ( !isset( $current->response[ $file ] ) )
		return false;
	$r = $current->response[ $file ];
        $default_headers = array(
		'Name' => 'Plugin Name',
		'PluginURI' => 'Plugin URI',
		'Version' => 'Version',
		'Description' => 'Description',
		'Author' => 'Author',
		'AuthorURI' => 'Author URI',
		'TextDomain' => 'Text Domain',
		'DomainPath' => 'Domain Path',
		'Network' => 'Network',
	);
        $plugin_data = get_file_data( WP_PLUGIN_DIR . DS.$file, $default_headers, 'plugin' );
	$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
	$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
	$details_url = self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $r->slug . '&section=changelog&TB_iframe=true&width=600&height=800');
        if((is_network_admin() || !is_multisite()) && current_user_can('update_plugins') && !empty($r->package) ){
            $this->notice(
                sprintf(
                    __('Hey! %1$s has an update. <a href="%2$s" class="thickbox" title="%3$s">See version %4$s details</a> or simply <a href="%5$s">update automatically</a>.')
                    , '<strong>'.$plugin_name.'</strong>',
                    esc_url($details_url),
                    esc_attr($plugin_name),
                    $r->new_version,
                    wp_nonce_url( self_admin_url('update.php?action=upgrade-plugin&plugin=') . $file, 'upgrade-plugin_' . $file) ),true,true);
        }
    }
    function check(){
        
        $config=&WYSIJA::get('config','model');
        if(!$config->getValue('wysija_db_version') || version_compare($config->getValue('wysija_db_version'),WYSIJA::get_version()) < 0){
            $this->update(WYSIJA::get_version());
        }
        
        $noredirect=false;
        $timeInstalled=$config->getValue('installed_time')+3600;

        if(current_user_can('switch_themes') ){
            if((!$config->getValue('wysija_whats_new') || version_compare($config->getValue('wysija_whats_new'),WYSIJA::get_version()) < 0) && isset($_REQUEST['page']) && in_array($_REQUEST['page'], array('wysija_config','wysija_campaigns','wysija_subscribers'))){
                if(isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('whats_new','welcome_new','activate-plugin')))  $noredirect=true;
                if(!$noredirect) {
                    if(time()>$timeInstalled){
                        WYSIJA::redirect('admin.php?page=wysija_campaigns&action=whats_new');
                    }else{
                        WYSIJA::redirect('admin.php?page=wysija_campaigns&action=welcome_new');
                    }

                    $mConfig=&WYSIJA::get('config','model');
                    $mConfig->save(array('wysija_whats_new'=>WYSIJA::get_version()));
                }
            }
        }
    }
    function update($version){
        $config=&WYSIJA::get('config','model');
        $config->getValue('wysija_db_version');
        foreach($this->updates as $version){
            if(version_compare($config->getValue('wysija_db_version'),$version) < 0){
                if(!$this->runUpdate($version)){
                    $this->error(sprintf(__('Update procedure to Wysija version "%1$s" failed!',WYSIJA),$version),true);
                    return false;
                }else{
                    $config->save(array('wysija_db_version'=>$version));
                }
            }
        }
    }
    
    function runUpdateQueries($queries){
        $failed=array();

        global $wpdb;
        foreach($queries as $query){
            $query=str_replace('[wysija]',$this->modelWysija->getPrefix(),$query);
            $result=mysql_query($query, $wpdb->dbh);
            if(!$result)    $failed[]=mysql_error($wpdb->dbh)." ($query)";
        }
        if($failed) return $failed;
        else return false;
    }
}
