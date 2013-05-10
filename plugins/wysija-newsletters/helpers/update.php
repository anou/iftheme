<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_update extends WYSIJA_object{
    function WYSIJA_help_update(){
        $this->modelWysija=new WYSIJA_model();

        $this->updates=array('1.1','2.0','2.1','2.1.6','2.1.7','2.1.8','2.2','2.2.1','2.3.3','2.3.4', '2.4', '2.4.1', '2.4.3','2.4.4');
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
           case '2.2':
               $mConfig=&WYSIJA::get('config','model');

               $mList=&WYSIJA::get('list','model');
               $mList->update(array('name'=>'WordPress Users'),array('list_id'=>$mConfig->getValue('importwp_list_id'), 'namekey'=>'users'));

               $querys[]='DELETE FROM `[wysija]user_list` WHERE `list_id` = '.$mConfig->getValue('importwp_list_id').' AND `user_id` in ( SELECT user_id FROM `[wysija]user` where wpuser_id=0 );';
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
               break;
           case '2.2.1':
                $helperU=&WYSIJA::get('user','helper');
                $helperU->cleanWordpressUsersList();
                return true;
               break;
           case '2.3.3':
                update_option('wysija_log', $optionlog);
                return true;
               break;

           case '2.3.4':
                $model_config=&WYSIJA::get('config','model');
                $dbl_optin=(int)$model_config->getValue('confirm_dbleoptin');

                $querys[]='UPDATE `[wysija]user_list` as A inner join `[wysija]user` as B on (A.user_id = B.user_id) set A.sub_date= '.time().' where A.sub_date=0 and A.unsub_date=0 and B.status>='.$dbl_optin.';';
                $errors=$this->runUpdateQueries($querys);
                if($errors){
                    $this->error(implode($errors,"\n"));
                    return false;
                }
                return true;
                break;
            case '2.4':
                $queries = array();
                $queries[] = 'CREATE TABLE IF NOT EXISTS `[wysija]form` ('.
                    '`form_id` INT unsigned AUTO_INCREMENT NOT NULL,'.
                    '`name` tinytext COLLATE utf8_bin,'.
                    '`data` longtext COLLATE utf8_bin,'.
                    '`styles` longtext COLLATE utf8_bin,'.
                    '`subscribed` int(10) unsigned NOT NULL DEFAULT "0",'.
                    'PRIMARY KEY (`form_id`)'.
                ') ENGINE=MyISAM ';
                $errors = $this->runUpdateQueries($queries);
                if($errors) {
                    $this->error(implode($errors,"\n"));
                    return false;
                } else {

                    if((bool)$this->modelWysija->query('SHOW TABLES LIKE "[wysija]form";') === false) {
                        return false;
                    } else {

                        $widgets_converted = $this->convert_widgets_to_forms();
                        if($widgets_converted === 0) {
                            $helper_install =& WYSIJA::get('install', 'helper');
                            $helper_install->create_default_subscription_form();
                        }
                    }
                }
                return true;
                break;
            case '2.4.1':
                $model_email=&WYSIJA::get('email','model');
                $model_email->setConditions(array('type'=>'2'));
                $emails = $model_email->getRows(array('email_id','params'));

                foreach($emails as $email){
                    $model_email->getParams($email);
                    if(isset($email['params']) && $email['params']['autonl']['event']=='new-articles'){
                        $model_queue=&WYSIJA::get('queue','model');
                        $model_queue->delete(array('email_id'=>$email['email_id']));
                    }
                }
                return true;
            break;
            case '2.4.3':


                $model_forms =& WYSIJA::get('forms', 'model');
                $forms = $model_forms->getRows();
                if(is_array($forms) && count($forms) > 0) {
                    foreach ($forms as $i => $form) {
                        $requires_update = false;

                        $data = unserialize(base64_decode($form['data']));

                        if(strlen($data['settings']['success_message']) % 4 === 0 && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data['settings']['success_message'])) {

                            $data['settings']['success_message'] = base64_decode($data['settings']['success_message']);
                            $requires_update = true;
                        }

                        foreach ($data['body'] as $j => $block) {

                            if($block['type'] === 'text') {

                                if(strlen($block['params']['text']) % 4 === 0 && preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $block['params']['text'])) {

                                    $data['body'][$j]['params']['text'] = base64_decode($block['params']['text']);
                                    $requires_update = true;
                                }
                            }
                        }

                        if($requires_update === true) {
                            $model_forms->reset();
                            $model_forms->update(array('data' => base64_encode(serialize($data))), array('form_id' => (int)$form['form_id']));
                        }
                    }
                }
                return true;
            break;
            case '2.4.4':

                WYSIJA::update_option('installation_step', '16');
                return true;
                break;
            default:
                return false;
        }
        return false;
    }
    function customerRequestMissingSubscriber(){
        $mConfig=&WYSIJA::get('config','model');
        $querys[]='UPDATE `[wysija]user_list` as A set A.sub_date= '.time().' where A.sub_date=0;';
        $errors=$this->runUpdateQueries($querys);
        if($errors){
            $this->error(implode($errors,"\n"));
            return false;
        }
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
        if(((is_multisite() && current_user_can('manage_network') ) || current_user_can('update_plugins') ) && !empty($r->package) ){
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


        if(WYSIJA::current_user_can('switch_themes') ){





            if(isset($_REQUEST['page']) && in_array($_REQUEST['page'], array('wysija_config','wysija_campaigns','wysija_subscribers'))){

                $whats_new_option='wysija_whats_new';
                $is_multisite=is_multisite();
                $is_network_admin=WYSIJA::current_user_can('manage_network');
                if($is_multisite){
                    if($is_network_admin){
                        $whats_new_option='ms_wysija_whats_new';
                    }else {
                        return;
                    }
                }

                if((!$config->getValue($whats_new_option) || version_compare($config->getValue($whats_new_option),WYSIJA::get_version()) < 0)){

                    if(isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('whats_new','welcome_new','activate-plugin')))  $noredirect=true;
                    if(!$noredirect) {
                        $timeInstalled=$config->getValue('installed_time')+3600;

                        if(time()>$timeInstalled){
                            WYSIJA::redirect('admin.php?page=wysija_campaigns&action=whats_new');
                        }else{
                            WYSIJA::redirect('admin.php?page=wysija_campaigns&action=welcome_new');
                        }
                    }
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


    function convert_widget_to_form($values = array()) {

        if(!is_array($values)) return false;

        if(isset($values['form']) && (int)$values['form'] > 0) return false;
        $settings = $body = array();


        if($values['autoregister'] === 'not_auto_register') {
            $settings['lists_selected_by'] = 'admin';
        } else {

            $settings['lists_selected_by'] = 'user';
        }

        $settings['lists'] = $values['lists'];

        $settings['on_success'] = 'message';
        $settings['success_message'] = $values['success'];

        if($values['labelswithin'] === 'labels_within') {
            $label_within = true;
        } else {
            $label_within = false;
        }

        $blocks = array();

        if(isset($values['instruction']) && strlen(trim($values['instruction'])) > 0) {
            $blocks[] = array(
                'params' => array(
                    'text' => base64_encode($values['instruction']),
                ),
                'type' => 'text',
                'field' => 'text',
                'name' => __('Random text or instructions', WYSIJA)
            );
        }

        $has_email_field = false;
        foreach($values['customfields'] as $field => $params) {
            switch($field) {
                case 'firstname':
                    $name = __('First name', WYSIJA);
                    break;
                case 'lastname':
                    $name = __('Last name', WYSIJA);
                    break;
                case 'email':
                    $has_email_field = true;
                    $name = __('Email', WYSIJA);
                    break;
            }
            $blocks[] = array(
                'name' => $name,
                'type' => 'input',
                'field' => $field,
                'params' => array(
                    'label' => $params['label'],
                    'required' => 1,
                    'label_within' => (int)$label_within
                )
            );
        }

        if($has_email_field === false) {
            $blocks[] = array(
                'name' => __('Email', WYSIJA),
                'type' => 'input',
                'field' => 'email',
                'params' => array(
                    'label' => __('Email', WYSIJA),
                    'required' => 1,
                    'label_within' => (int)$label_within
                )
            );
        }

        if($settings['lists_selected_by'] === 'user') {
            $list_values = array();
            foreach($settings['lists'] as $list_id) {
                $list_values[] = array(
                    'list_id' => $list_id,
                    'is_checked' => 1
                );
            }
            $blocks[] = array(
                'name' => __('List selection', WYSIJA),
                'type' => 'list',
                'field' => 'list',
                'params' => array(
                    'label' => __('Select a list:', WYSIJA),
                    'values' => $list_values
                )
            );
        }

        $submit_label = __('Subscribe!', WYSIJA);
        if(isset($values['submit']) && strlen(trim($values['submit'])) > 0) {
            $submit_label = $values['submit'];
        }
        $blocks[] = array(
            'name' => __('Submit', WYSIJA),
            'type' => 'submit',
            'field' => 'submit',
            'params' => array(
                'label' => $submit_label
            )
        );

        for($i = 0, $count = count($blocks); $i < $count; $i++) {
            $body['block-'.($i + 1)] = array_merge($blocks[$i], array('position' => ($i + 1)));
        }

        $form_name = __('New Form', WYSIJA);

        if(isset($values['title']) && strlen(trim($values['title'])) > 0) {
            $form_name = $values['title'];
        }

        $helper_form_engine =& WYSIJA::get('form_engine', 'helper');

        $model_forms =& WYSIJA::get('forms', 'model');
        $model_forms->reset();

        $form_id = $model_forms->insert(array('name' => $form_name));
        if((int)$form_id > 0) {
            $model_forms->reset();

            $helper_form_engine->set_data(array(
                'form_id' => (int)$form_id,
                'settings' => $settings,
                'body' => $body
            ));

            $model_forms->update(array('data' => $helper_form_engine->get_encoded('data')), array('form_id' => $form_id));
            return $form_id;
        } else {
            return false;
        }
    }
    function convert_widgets_to_forms() {
        $widgets_converted = 0;

        $widgets = get_option('widget_wysija');
        foreach($widgets as $key => &$values) {
            $form_id = $this->convert_widget_to_form($values);
            if($form_id!==false) {
                $values['default_form'] = $form_id;
                $widgets_converted++;
            }
        }
        update_option('widget_wysija',$widgets);
        return $widgets_converted;
    }
}