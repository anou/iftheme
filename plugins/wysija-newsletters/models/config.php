<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_config extends WYSIJA_object{
    var $name_option='wysija';
    var $cboxes=array(
        'emails_notified_when_sub',
        'emails_notified_when_unsub',
        'emails_notified_when_bounce',
        'emails_notified_when_dailysummary',
        'bounce_process_auto',
        'sharedata',
        'manage_subscriptions',
        'viewinbrowser',
        'dkim_active',
        'cron_manual',
        'commentform',
    );
    var $defaults=array(
        'limit_listing'=>10,
        'role_campaign'=>'switch_themes',
        'role_subscribers'=>'switch_themes',
        'emails_notified_when_unsub' =>true,
        'sending_method'=>'gmail',
        'sending_emails_number'=>'200',
        'sending_method'=>'site',
        'sending_emails_site_method'=>'phpmail',
        'smtp_port'=>'',
        'smtp_auth' =>true,
        'bounce_port'=>'',
        'confirm_dbleoptin' =>1,
        'bounce_selfsigned'=>0,
        'bounce_email_notexists'=>'unsub',
        'bounce_inbox_full'=>'not',
        'pluginsImportedEgg'=>false,
        'advanced_charset'=>'UTF-8',
        'sendmail_path'=>'/usr/sbin/sendmail',
        'sending_emails_each'=>'daily',
        'bounce_max'=>8,
        'debug_new'=>false,
        'manage_subscriptions'=>false,
        'editor_fullarticle'=>false,
        'allow_no_js'=>true,
        'urlstats_base64'=>true,
        'viewinbrowser'=>true,
        'commentform'=>false,

    );

    var $capabilities=array();
    var $values=array();

    function WYSIJA_model_config(){
        $this->add_translated_default();

        $encoded_option=get_option($this->name_option);
        global $wysija_installing;
        $installApp=false;
        if($encoded_option){
            $this->values=unserialize(base64_decode($encoded_option));
            if(!isset($this->values['installed'])) $installApp=true;

        }else $installApp=true;


        /*install the application because there is no option setup it's safer than the classic activation scheme*/
        if(defined('WP_ADMIN')){
            add_action('admin_menu', array($this,'add_translated_default'),96);

            if($installApp && $wysija_installing!==true){
                $wysija_installing=true;
                $installer=&WYSIJA::get('install','helper',false,'wysija-newsletters',false);
                add_action('admin_menu', array($installer,'install'),97);
            }else{
                $updater=&WYSIJA::get('update','helper',false,'wysija-newsletters',false);
                add_action('admin_menu', array($updater,'check'),103);

            }
        }


    }
    /*
     * to make sure the translation is not screwed by an empty space or so
     */
    function cleanTrans($string){
        return str_replace(array('[ link]','[link ]','[ link ]','[/ link]','[/link ]','[/ link ]'), array('[link]','[link]','[link]','[/link]','[/link]','[/link]'), trim($string));
    }
    function add_translated_default(){
        /* definition of extra translated defaults fields */
        $this->defaults['confirm_email_title']=sprintf(__('Confirm your subscription to %1$s',WYSIJA),get_option('blogname'));
        $this->defaults['confirm_email_body']=__("Hello!\n\nHurray! You've subscribed to our site.\nWe need you to activate your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\n The team!\n",WYSIJA);
        $this->defaults['subscribed_title']=__('You\'ve subscribed to: %1$s',WYSIJA);
        $this->defaults['subscribed_subtitle']=__("Yup, we've added you to our list. You'll hear from us shortly.",WYSIJA);
        $this->defaults['unsubscribed_title']=__("You've unsubscribed!",WYSIJA);
        $this->defaults['unsubscribed_subtitle']=__("Great, you'll never hear from us again!",WYSIJA);
        $this->defaults['unsubscribe_linkname']=__('Unsubscribe',WYSIJA);
        $this->defaults['manage_subscriptions_linkname']=__('Edit your subscription',WYSIJA);
        $this->defaults['viewinbrowser_linkname']=$this->cleanTrans(__('Display problems? [link]View this newsletter in your browser.[/link]',WYSIJA));
        $this->defaults['commentform_linkname']=__('Yes, add me to your mailing list.',WYSIJA);

        /**
         * List of all the conflictive extensions which invite themselves on our interfaces and break some of our js:
         * tribulant newsletter
         */
        $this->defaults['conflictivePlugins'] = array(
            'tribulant-wp-mailinglist' => array(
                'file' => 'wp-mailinglist/wp-mailinglist.php',
                'version' => '3.8.7',
                'clean' => array(
                    'admin_head' => array(
                        '10' => array(
                            'objects' => array('wpMail')
                        )
                    )
                )
            ),
            'wp-events' => array(
                'file' => 'wp-events/wp-events.php',
                'version' => '',
                'clean' => array(
                    'admin_head' => array(
                        '10' => array(
                            'function' => 'events_editor_admin_head'
                        )
                    )
                )
            ),
            'email-users' => array(
                'file' => 'email-users/email-users.php',
                'version' => '',
                'clean' => array(
                    'admin_head' => array(
                        '10' => array(
                            'function' => 'editor_admin_head'
                        )
                    )
                )
            ),
            'acf' => array(
                'file' => 'advanced-custom-fields/acf.php',
                'version' => '3.1.7',
                'clean' => array(
                    'init' => array(
                        '10' => array(
                            'objects' => array('Acf')
                        )
                    )
                )
            ),
            'wptofacebook' => array(
                'file' => 'wptofacebook/index.php',
                'version' => '1.2.3',
                'clean' => array(
                    'admin_head' => array(
                        '10' => array(
                            'function' => 'WpToFb::wptofb_editor_admin_head'
                        )
                    )
                )
            ),
            'mindvalley-pagemash' => array(
                'file' => 'mindvalley-pagemash/pagemash.php',
                'version' => '1.1',
                'clean' => array(
                    'admin_print_scripts' => array(
                        '10' => array(
                            'function' => 'pageMash_head'
                        )
                    )
                )
            ),
            'wp-polls' => array(
                'file' => 'wp-polls/wp-polls.php',
                'version' => '2.63',
                'clean' => array(
                    'wp_enqueue_scripts' => array(
                        '10' => array(
                            'function' => 'poll_scripts'
                        )
                    )
                )
            ),
            'wp_rokajaxsearch' => array(
                'file' => 'wp_rokajaxsearch/rokajaxsearch.php',
                'version' => '',
                'clean' => array(
                    'init' => array(
                        '-50' => array(
                            'function' => 'rokajaxsearch_mootools_init'
                        )
                    )
                )
            ),
            'wp_rokstories' => array(
                'file' => 'wp_rokstories/rokstories.php',
                'version' => '',
                'clean' => array(
                    'init' => array(
                        '-50' => array(
                            'function' => 'rokstories_mootools_init'
                        )
                    )
                )
            ),
            'simple-links' => array(
                'file' => 'simple-links/simple-links.php',
                'version' => '1.5',
                'clean' => array(
                    'admin_print_scripts' => array(
                        '10' => array(
                            'objects' => array('simple_links_admin')
                        )
                    )
                )
            )
        );

        $this->defaults['conflictiveThemes'] = array(
            'smallbiz' => array(
                'clean' => array(
                    'admin_head' => array(
                        '10' => array(
                            'function' => 'smallbiz_on_admin_head'
                        )
                    )
                )
            ),
            'balance' => array(
                'clean' => array(
                    'admin_enqueue_scripts' => array(
                        '10' => array(
                            'functions' => array('al_admin_scripts', 'al_adminpanel_scripts', 'al_pricing_tables_scripts')
                        )
                    ),
                    'admin_head' => array(
                        '10' => array(
                            'function' => 'al_admin_head'
                        )
                    )
                )
            )
        );


        $this->capabilities['newsletters']=array(
            'label'=>__('Who can create newsletters?',WYSIJA));
        $this->capabilities['subscribers']=array(
            'label'=>__('Who can manage subscribers?',WYSIJA));
        $this->capabilities['subscriwidget']=array(
            'label'=>__('Who can add forms to posts/pages?',WYSIJA));
        $this->capabilities['config']=array(
            'label'=>__('Who can change Wysija\'s settings?',WYSIJA));
    }

    /**
     * we have a specific save for option since we are saving it in wordpress options table
     * @param type $data
     * @param type $savedThroughInterface
     */
    function save($data=false,$savedThroughInterface=false) {

        if($data){
            /* when saving configuration from the settings page we need to make sure that if checkboxes have been unticked we remove the corresponding option */
            if($savedThroughInterface){
                $bouncing_freq_has_changed=$sending_freq_has_changed=false;
                $wptools=&WYSIJA::get('wp_tools','helper',false,'wysija-newsletters',false);
                $editable_roles=$wptools->wp_get_roles();
                foreach($this->capabilities as $keycap=>$capability){
                    foreach($editable_roles as $role){
                        $this->cboxes[]='rolescap---'.$role['key'].'---'.$keycap;
                    }
                }

                foreach($this->cboxes as $cbox){
                    if(!isset($data[$cbox])){
                        $this->values[$cbox]=false;
                    }else $this->values[$cbox]=1;

                    /*this checkbox is of a role type*/
                    if(strpos($cbox, 'rolescap---')!==false){

                        $rolecap=str_replace('rolescap---','',$cbox);
                        //this is a rolecap let's add or remove the cap to the role
                        $rolecapexp=explode('---', $rolecap);
                        $role=get_role($rolecapexp[0]);
                        $capab='wysija_'.$rolecapexp[1];
                        //added for invalid roles ...
                        if($role){
                            if($this->values[$cbox]){
                                $role->add_cap($capab);
                            }else{
                                //remove cap only for roles different of admins
                                if($role->has_cap($capab) && !in_array($rolecapexp[0], array('administrator','super_admin'))){
                                    $role->remove_cap($capab);
                                }
                            }
                        }
                        //no need to save those values which are already saved in wordpress
                        unset($this->values[$cbox]);
                    }
                }


                $userHelper = &WYSIJA::get('user','helper',false,'wysija-newsletters',false);
                if(isset($data['from_email']) && !$userHelper->validEmail($data['from_email'])){
                    if(!$data['from_email']) $data['from_email']=__('empty',WYSIJA);
                    $this->error(sprintf(__('The <strong>from email</strong> value you have entered (%1$s) is not a valid email address.',WYSIJA),$data['from_email']),true);
                    $data['from_email']=$this->values['from_email'];
                }

                if(isset($data['replyto_email']) && !$userHelper->validEmail($data['replyto_email'])){
                    if(!$data['replyto_email']) $data['replyto_email']=__('empty',WYSIJA);
                    $this->error(sprintf(__('The <strong>reply to</strong> email value you have entered (%1$s) is not a valid email address.',WYSIJA),$data['replyto_email']),true);
                    $data['replyto_email']=$this->values['replyto_email'];
                }

                /* in that case the admin changed the frequency of the wysija cron meaning that we need to clear it */
                if($data['sending_emails_each']!=$this->getValue('sending_emails_each')){
                    $sending_freq_has_changed=true;
                    $data['last_save']=time();
                }

                if(isset($data['bouncing_emails_each']) && $data['bouncing_emails_each']!=$this->getValue('bouncing_emails_each')){
                    $bouncing_freq_has_changed=true;
                    $data['last_save']=time();
                }

                /* if saved with gmail then we set up the smtp settings */
                if($data['sending_method']=='gmail') {
                    $data['smtp_host']='smtp.gmail.com';
                    $data['smtp_port']='465';
                    $data['smtp_secure']='ssl';
                    $data['smtp_auth']=true;
                }

                $data['smtp_host']=trim($data['smtp_host']);


                /* specific case to identify common action to different rules there some that doesnt show in the interface, yet we use them.*/
                foreach($data as $key => $value){
                    $fs='bounce_rule_';
                    if(strpos($key,$fs)!== false){
                        if(strpos($key,'_forwardto')===false){
                            $indexrule=str_replace($fs, '', $key);
                            $helpRules=&WYSIJA::get('rules','helper',false,'wysija-newsletters',false);
                            $rules=$helpRules->getRules();
                            foreach($rules as $keyy => $vals){
                                if(isset($vals['behave'])){
                                    $ruleMain=$helpRules->getRules($vals['behave']);
                                    $data[$fs.$vals['key']]=$value;
                                }
                            }
                        }
                    }
                }

                if(!isset($data['emails_notified_when_unsub'])){
                    $data['emails_notified_when_unsub']=false;
                }

            }
            foreach($data as $key => $value){
                /*verify that the confirm email body contains an activation link if it doesn't add i at the end of the email*/
                if($key=='confirm_email_body' && strpos($value, '[activation_link]')=== false){
                    /*the activation link was not found*/
                    $value.="\n".'[activation_link]Click here to confirm your subscription.[/activation_link]';
                }

                if($key=='dkim_pubk'){
                    $value = str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\n"),'',$value);
                }

                if($key=='manage_subscriptions_lists'){
                    $mList=&WYSIJA::get('list','model');
                    $mList->update(array('is_public'=>0),array('is_public'=>1));
                    $mList->reset();
                    $mList->update(array('is_public'=>1),array('list_id'=>$value));

                    unset($this->values[$key]);
                }

                if(is_string($value)) $value=$value;
                /* we save it only if it is different than the default no need to overload the db*/
                if(!isset($this->defaults[$key]) || (isset($this->defaults[$key]) && $value!=$this->defaults[$key])){
                    $this->values[$key]=  $value;
                }else{
                    unset($this->values[$key]);
                }
            }


            /* save the confirmation email in the email table */
            if(isset($data['confirm_email_title']) && isset($data['confirm_email_body'])){
                $mailModel=&WYSIJA::get('email','model',false,'wysija-newsletters',false);
                $mailModel->update(array('from_name'=>$data['from_name'],'from_email'=>$data['from_email'],
                    'replyto_name'=>$data['replyto_name'],'replyto_email'=>$data['replyto_email'],
                    'subject'=>$data['confirm_email_title'],'body'=>$data['confirm_email_body']),array('email_id'=>$this->values['confirm_email_id']));
            }
            unset($this->values['confirm_email_title']);
            unset($this->values['confirm_email_body']);
        }


        update_option($this->name_option,base64_encode(serialize($this->values)));
        if($savedThroughInterface){
            if($bouncing_freq_has_changed){
                wp_clear_scheduled_hook('wysija_cron_bounce');
                WYSIJA::set_cron_schedule('bounce');
            }
            if($sending_freq_has_changed){
                wp_clear_scheduled_hook('wysija_cron_queue');

                WYSIJA::set_cron_schedule('queue');
            }
            $this->notice(__('Your Wysija settings have been updated!',WYSIJA));
        }
    }


    /**
     *
     * @param type $key
     * @param type $type
     * @return type
     */
    function getValue($key,$default=false,$type='normal') {
        if(isset($this->values[$key])) {
            /*if($type=="trans")  return stripslashes($this->values[$key]);
            else return $this->values[$key]; */
            if($key=='pluginsImportableEgg'){
                $helperImport=&WYSIJA::get('import','helper',false,'wysija-newsletters',false);
                foreach($this->values[$key] as $tablename =>$plugInfosExtras){
                    $extraData=$helperImport->getPluginsInfo($tablename);
                    if($extraData)  $this->values[$key][$tablename]=array_merge($extraData,$this->values[$key][$tablename]);
                }
            }

            return $this->values[$key];
        }else{
            /* special case for the confirmation email */
            if(in_array($key, array('confirm_email_title','confirm_email_body'))){
                $mailModel=&WYSIJA::get('email','model',false,'wysija-newsletters',false);
                $mailObj=$mailModel->getOne($this->getValue('confirm_email_id'));
                if($mailObj){
                   $this->values['confirm_email_title']=$mailObj['subject'];
                   $this->values['confirm_email_body']=$mailObj['body'];
                   return $this->values[$key];
                }else{
                    if($default===false && isset($this->defaults[$key])) return $this->defaults[$key];
                    elseif(!($default===false)){
                        return $default;
                    }
                }

            }else{

                if($default===false && isset($this->defaults[$key])) return $this->defaults[$key];
                elseif(!($default===false)){
                    return $default;
                }
            }

        }
        return false;
    }

    /**
     *
     * @param type $editor
     */
    function emailFooterLinks($editor=false){
        $unsubscribe=array();
        $unsubscribetxt=$editsubscriptiontxt='';

        if(!isset($this->values['unsubscribe_linkname'])) $unsubscribetxt=__('Unsubscribe',WYSIJA);
        else $unsubscribetxt=$this->getValue('unsubscribe_linkname');

        if(!isset($this->values['manage_subscriptions_linkname'])) $editsubscriptiontxt=__('Edit your subscription',WYSIJA);
        else $editsubscriptiontxt=$this->getValue('manage_subscriptions_linkname');


        $unsubscribe[0] = array(
                'link' => '[unsubscribe_link]',
                'label' => $unsubscribetxt
            );

        if($this->getValue('manage_subscriptions')){
             $unsubscribe[1] =array(
                'link' => '[subscriptions_link]',
                'label' => $editsubscriptiontxt
            );
        }

        if($editor){
            $modelU=&WYSIJA::get('user','model',false,'wysija-newsletters',false);
            $modelU->getFormat=OBJECT;
            $objUser=$modelU->getOne(false,array('wpuser_id'=>WYSIJA::wp_get_userdata('ID')));

            $unsubscribe[0]['link'] = $modelU->getConfirmLink($objUser,'unsubscribe',false,true).'&demo=1';
            if($this->getValue('manage_subscriptions')) $unsubscribe[1]['link'] = $modelU->getConfirmLink($objUser,'subscriptions',false,true);
        }

        return $unsubscribe;
    }

    function viewInBrowserLink($editor=false){
        $data=array();

        if($this->getValue('viewinbrowser')){
           $linkname='';
           if(!isset($this->values['viewinbrowser_linkname'])) $linkname=$this->cleanTrans(__('Display problems? [link]View this newsletter in your browser.[/link]',WYSIJA));
           else $linkname=$this->getValue('viewinbrowser_linkname');

            if(strpos($linkname, '[link]')!==false){
                $linkpre=explode('[link]', $linkname);
                $data['pretext']=$linkpre[0];
                $linkpost=explode('[/link]', $linkpre[1]);
                $data['posttext']=$linkpost[1];
                $data['label']=$linkpost[0];
                $data['link']='[view_in_browser_link]';
           }
        }
        $email_id=0;
        if($editor){
            $paramsurl=array(
                'wysija-page'=>1,
                'controller'=>"email",
                'action'=>"view",
                'email_id'=>$email_id,
                'user_id'=>0
                );
            if($email_id==0) $paramsurl['email_id']=$_REQUEST['id'];
            $data['link']=WYSIJA::get_permalink($this->getValue('confirm_email_link'),$paramsurl);
        }

        return $data;
    }
}
