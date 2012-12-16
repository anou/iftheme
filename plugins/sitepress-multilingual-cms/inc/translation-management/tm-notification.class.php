<?php

class TM_Notification{
    
    var $_process_mail_queue = FALSE;
    
    function __construct(){
        add_action('init', array($this, 'mail_queue'), 9999);
        add_action('wp_redirect', array($this, 'mail_queue'), 9999);
        add_action('icl_ajx_custom_call', array($this, 'mail_queue'), 9999);
    }
    
    function footer(){
    }
    
    function new_job_any($job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);
        $translators = $iclTranslationManagement->get_blog_translators(array('to'=>$job->language_code));
        $edit_url = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id);
        foreach($translators as $t){
            
            if($job->manager_id == $t->ID) continue;
            
            // get current user admin language
            $user_language = $sitepress->get_user_admin_language($t->ID);
            // override locale
            $sitepress->switch_locale($user_language);
            
            $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
                $job->source_language_code, $user_language));
            $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
                $job->language_code, $user_language));

            $post_link = $iclTranslationManagement->tm_post_permalink($job->original_doc_id);
            if ($post_link != "") {
                $post_link = sprintf(__("\nView the original document in %s here: %s\n", 'sitepress'), $lang_from, $post_link);
            }

            $mail['to'] = $t->display_name . ' <' . $t->user_email . '>';
            //$mail['to'] = $t->user_email;
            $mail['subject'] = sprintf(__('New translation job from %s', 'sitepress'), get_bloginfo('name'));
            $mail['body'] = sprintf(__("New job available from %s to %s.\n%s\nStart editing: %s", 'sitepress'),
                $lang_from, $lang_to, $post_link, $edit_url);
            $mail['type'] = 'translator';

            $this->send_mail($mail, $user_language);
            
            //restore locale
            $sitepress->switch_locale();
        }
    }
    
    function new_job_translator($job_id, $translator_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);
        
        if($job->manager_id == $job->translator_id) return;
        
        $edit_url = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id);
        
        $user = new WP_User($translator_id);
        
        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($user->ID);
        // override locale
        $sitepress->switch_locale($user_language);

        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));

        $post_link = $iclTranslationManagement->tm_post_permalink($job->original_doc_id);
        if ($post_link != "") {
            $post_link = sprintf(__("\nView the original document in %s here: %s\n", 'sitepress'), $lang_from, $post_link);
        }
        
        $mail['to'] = $user->display_name . ' <' . $user->user_email . '>';
        //$mail['to'] = $user->user_email;
        $mail['subject'] = sprintf(__('New translation job from %s', 'sitepress'), get_bloginfo('name'));
        //exit;

        $mail['body'] = sprintf(__("Hi %s,", 'sitepress'),
                                $user->display_name);

        $mail['body'] .= "\n\n";
        $mail['body'] .= sprintf(__("You have been assigned to new translation job from %s to %s.\n%s\nStart editing: %s", 'sitepress'),
            $lang_from, $lang_to, $post_link, $edit_url);

        $mail['type'] = 'translator';

        $mail = apply_filters('WPML_new_job_notification', $mail, $job_id);
                   
        $this->send_mail($mail, $user_language);
        
        //restore locale
        $sitepress->switch_locale();
    }
    
    function work_complete($job_id, $update = false){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $job->translator_id) return;
        $manager = new WP_User($job->manager_id);
        $translator = new WP_User($job->translator_id);

        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($manager->ID);
        // override locale
        $sitepress->switch_locale($user_language);

        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));
        
        $tj_url = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs');
        $doc_url = get_edit_post_link($job->original_doc_id, 'not_display');
        
        if($iclTranslationManagement->settings['notification']['completed'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
            $mail['to'] = $manager->display_name . ' <' . $manager->user_email . '>';
            if($update){
                $mail['subject'] = sprintf(__('Translator has updated translation job for %s', 'sitepress'), get_bloginfo('name'));
                $mail['body'] = sprintf(__("Translator (%s) has updated translation of job \"%s\" for %s to %s.\n%s\n\nView translation jobs: %s", 'sitepress'),
                    $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, $doc_url, $tj_url);            
            }else{
                $mail['subject'] = sprintf(__('Translator has completed translation job for %s', 'sitepress'), get_bloginfo('name'));
                $mail['body'] = sprintf(__("Translator (%s) has completed translation of job \"%s\" for %s to %s.\n%s\n\nView translation jobs: %s", 'sitepress'),
                    $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, $doc_url, $tj_url);            
            }
            $mail['type'] = 'admin';
            $this->send_mail($mail, $user_language);
        }
        
        // restore locale
        $sitepress->switch_locale();        
    }
    
    function translator_resigned($translator_id, $job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $translator_id) return;
        $translator = new WP_User($translator_id);
        $manager = new WP_User($job->manager_id);
        
        $tj_url = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=jobs');
        
        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($manager->ID);
        // override locale
        $sitepress->switch_locale($user_language);

        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));        
        
        if($iclTranslationManagement->settings['notification']['resigned'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
            $mail['to'] = $manager->display_name . ' <' . $manager->user_email . '>';
            $mail['subject'] = sprintf(__('Translator has resigned from job on %s', 'sitepress'), get_bloginfo('name'));
            $mail['body'] = sprintf(__('Translator %s has resigned from the translation job "%s" for %s to %s.%sView translation jobs: %s', 'sitepress'),
            $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, "\n", $tj_url);            
            $mail['type'] = 'admin';
            
            $this->send_mail($mail, $user_language);
        }
        //restore locale
        $sitepress->switch_locale();
    }
    
    function translator_removed($translator_id, $job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $translator_id) return;
        $translator = new WP_User($translator_id);
        $manager = new WP_User($job->manager_id);
        
        $user_language = $sitepress->get_user_admin_language($manager->ID);
        // override locale
        $sitepress->switch_locale($user_language);
        
        $mail['to'] = $translator->display_name . ' <' . $translator->user_email . '>';
        $mail['subject'] = sprintf(__('Removed from translation job on %s', 'sitepress'), get_bloginfo('name'));
        $mail['body'] = sprintf(__('You have been removed from the translation job "%s" for %s to %s.', 'sitepress'),
        $job->original_doc_title, $lang_from, $lang_to);
        $mail['type'] = 'translator';
            
        $this->send_mail($mail, $user_language);
        
        // restore locale
        $sitepress->switch_locale();
    }
    
    function send_mail($mail, $language = false){
        global $sitepress;
        static $cache = array();
        
        if($language !== false);
        // override locale
        $sitepress->switch_locale($language);

        if ($mail != 'empty_queue') {
            $cache[$mail['type']][$mail['to']][$mail['subject']]['body'][] = $mail['body'];
            if (isset($mail['attachment'])) {
                $cache[$mail['type']][$mail['to']][$mail['subject']]['attachment'][] = $mail['attachment'];
            }
            $this->_process_mail_queue = TRUE;
        } else if (!empty($cache)) {
            $tj_url = admin_url('admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php');
            foreach ($cache as $type => $mail_to_send) {
                foreach ($mail_to_send as $to => $subjects) {
                    $body_to_send = '';
                    foreach ($subjects as $subject => $content) {
                        $body = $content['body'];
                        $body_to_send .= $body_to_send . "\n\n" . implode("\n\n\n\n", $body) . "\n\n\n\n";
                        if($type == 'translator'){
                            $footer = sprintf(__('You can view your other translation jobs here: %s', 'sitepress'), $tj_url) . "\n\n--\n"
                            . sprintf(__("This message was automatically sent by Translation Management running on %s. To stop receiving these notifications contact the system administrator at %s.\n\nThis email is not monitored for replies.", 'sitepress'), get_bloginfo('name'), get_option('home'));
                        } else {
                            $footer = "\n--\n" . sprintf(__("This message was automatically sent by Translation Management running on %s. To stop receiving these notifications, go to Notification Settings, or contact the system administrator at %s.\n\nThis email is not monitored for replies.", 'sitepress'), get_bloginfo('name'), get_option('home'));
                        }
                        $body_to_send .= $footer;
                        
                        if (isset($content['attachment'])) {
                            $attachments = $content['attachment'];
                        } else {
                            $attachments = array();
                        }
                        
                        $body_to_send = apply_filters('WPML_new_job_notification_body', $body_to_send, $tj_url);
                        $attachments = apply_filters('WPML_new_job_notification_attachments', $attachments);
                        wp_mail($to, $subject, $body_to_send, '', $attachments);
                    }
                }
            }
            $cache = array();
            $this->_process_mail_queue = FALSE;
        }
        
        // restore locale
        $sitepress->switch_locale();
    }

    function mail_queue($location = NULL) {
        if ($this->_process_mail_queue) {
            $this->send_mail('empty_queue');
        }
        if (!is_null($location)) {
            return $location;
        }
    }
}
 
?>