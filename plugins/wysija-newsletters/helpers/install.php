<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_install extends WYSIJA_object{
    function WYSIJA_help_install(){
        if(file_exists(ABSPATH . 'wp-admin'.DS.'includes'.DS.'upgrade.php'))    require_once(ABSPATH . 'wp-admin'.DS.'includes'.DS.'upgrade.php');
    }
    function install(){
        $values=array();
        
        if(!$this->testSystem()) return false;
        
        if(!$this->createTables()) return false;
        
        $this->moveData('themes');
        $this->moveData('dividers');
        $this->moveData('bookmarks');
        
        $this->recordDefaultUserField();
        
        $this->defaultSettings($values);
        
        $this->defaultList($values);
        
        $this->defaultCampaign($values);
        
        $helpImport=&WYSIJA::get('import','helper');
        $values['importwp_list_id']=$helpImport->importWP();
        
        $this->createPage($values);
        
        $this->createWYSIJAdir($values);
        
        $modelConf=&WYSIJA::get('config','model');
        $mailModel=&WYSIJA::get('email','model');
        $mailModel->blockMe=true;
        $values['confirm_email_id']=$mailModel->insert(
                array("type"=>"0",
                    "from_email"=>$values["from_email"],
                    "from_name"=>$values["from_name"],
                    "replyto_email"=>$values["from_email"],
                    "replyto_name"=>$values["from_name"],
                    "subject"=>$modelConf->getValue("confirm_email_title"),
                    "body"=>$modelConf->getValue("confirm_email_body"),
                    "status"=>99));
        
        $values['installed']=true;
        $values['manage_subscriptions']=true;
        $values['installed_time']=time();
        $values['wysija_db_version']=WYSIJA::get_version();
        $wptoolboxs =& WYSIJA::get('toolbox', 'helper');
        $values['dkim_domain']=$wptoolboxs->_make_domain_name();
        if(get_option('wysija_reinstall',0)) $values['wysija_whats_new']=WYSIJA::get_version();
        $modelConf->save($values);
        
        $this->testNLplugins();
        
        $wptools =& WYSIJA::get('wp_tools', 'helper');
        $wptools->set_default_rolecaps();
        global $wysija_installing;
        $wysija_installing=false;
        WYSIJA::update_option('wysija_reinstall',0);
        return true;
    }

    
    function testSystem(){

        
        $modelObj=&WYSIJA::get('user','model');
        $query='CREATE TABLE IF NOT EXISTS `'.$modelObj->getPrefix().'user_list` (
  `list_id` INT unsigned NOT NULL,
  `user_id` INT unsigned NOT NULL,
  `sub_date` INT unsigned DEFAULT 0,
  `unsub_date` INT unsigned DEFAULT 0,
  PRIMARY KEY (`list_id`,`user_id`)
) ENGINE=MyISAM';
        global $wpdb;

        

        $wpdb->query($query);
        $query="SHOW TABLES like '".$modelObj->getPrefix()."user_list';";
        $res=$wpdb->get_var($query);
        $haserrors=false;
        if(!$res){
            $this->wp_error(sprintf(
                    __('The MySQL user you have setup on your Wordpress site (wp-config.php) doesn\'t have enough privileges to CREATE MySQL tables. Please change this user yourself or contact the administrator of your site in order to complete Wysija\'s installation. mysql errors:(%1$s)',WYSIJA),  mysql_error()));
            $haserrors=true;
        }
        

        
        $helperF=&WYSIJA::get('file','helper');
        if(!$helperF->makeDir()){
            $upload_dir = wp_upload_dir();
            $this->wp_error(sprintf(__('The folder "%1$s" is not writable, please change the access rights to this folder so that Wysija can setup itself properly.',WYSIJA),$upload_dir['basedir']).'<a target="_blank" href="http://codex.wordpress.org/Changing_File_Permissions">'.__('Read documentation',WYSIJA).'</a>');
            $haserrors=true;
        }

        if($haserrors) return false;
        return true;
    }
    function defaultList(&$values){
        $model=&WYSIJA::get('list','model');
        $listname=__('My first list',WYSIJA);
        $defaultListId=$model->insert(array(
            'name'=>$listname,
            'description'=>__('The list created automatically on install of the Wysija.',WYSIJA),
            'is_public'=>1,
            'is_enabled'=>1));
        $values['default_list_id']=$defaultListId;
    }
    function defaultCampaign($valuesconfig){
        $modelCampaign=&WYSIJA::get('campaign','model');
        $campaign_id=$modelCampaign->insert(
                array(
                    'name'=>__('5 Minute User Guide',WYSIJA),
                    'description'=>__('Default newsletter created automatically during installation.',WYSIJA),
                    ));
        $modelEmail=&WYSIJA::get('email','model');
        $modelEmail->fieldValid=false;
        $dataEmail=array(
            'campaign_id'=>$campaign_id,
            'subject'=>__('5 Minute User Guide',WYSIJA)
        );

        $wjEngine =& WYSIJA::get('wj_engine', 'helper');

        $hDividers =& WYSIJA::get('dividers', 'helper');
        $defaultDivider = $hDividers->getDefault();

        $hBookmarks =& WYSIJA::get('bookmarks', 'helper');
        $bookmarks = $hBookmarks->getAllByIconset('medium', '02');
        //--------------
        $dataEmail['wj_data'] = array(
            'version' => WYSIJA::get_version(),
            'header' => array(
                'text' => null,
                'image' => array(
                    'src' => WYSIJA_EDITOR_IMG.'default-newsletter/newsletter/header.png',
                    'width' => 600,
                    'height' => 72,
                    'alignment' => 'center',
                    'static' => false
                ),
                'alignment' => 'center',
                'static' => false,
                'type' => 'header'
            ),
            'body' => array(
                'block-1' => array(
                    'text' => array(
                        'value' => '<h2><strong>'.__('Step 1:', WYSIJA).'</strong> '.__('hey, click on this text!', WYSIJA).'</h2>'.'<p>'.__('To edit, simply click on this block of text.', WYSIJA).'</p>'
                    ),
                    'image' => null,
                    'alignment' => 'left',
                    'static' => false,
                    'position' => 1,
                    'type' => 'content'
                ),
                'block-2' => array_merge(array(
                        'position' => 2,
                        'type' => 'divider'
                    ), $defaultDivider
                ),
                'block-3' => array(
                    'text' => array(
                        'value' => '<h2><strong>'.__('Step 2:', WYSIJA).'</strong> '.__('play with this image', WYSIJA).'</h2>'
                    ),
                    'image' => null,
                    'alignment' => 'left',
                    'static' => false,
                    'position' => 3,
                    'type' => 'content'
                ),
                'block-4' => array(
                    'text' => array(
                        'value' => '<p>'.__('Position your mouse over the image to the left.', WYSIJA).'</p>'
                    ),
                    'image' => array(
                        'src' => WYSIJA_EDITOR_IMG.'default-newsletter/newsletter/pigeon.png',
                        'width' => 281,
                        'height' => 190,
                        'alignment' => 'left',
                        'static' => false
                    ),
                    'alignment' => 'left',
                    'static' => false,
                    'position' => 4,
                    'type' => 'content'
                ),
                'block-5' => array_merge(array(
                        'position' => 5,
                        'type' => 'divider'
                    ), $defaultDivider
                ),
                'block-6' => array(
                    'text' => array(
                        'value' => '<h2><strong>'.__('Step 3:', WYSIJA).'</strong> '.__('drop content here', WYSIJA).'</h2>'.
                                    '<p>'.sprintf(__('Drag and drop %1$stext, posts, dividers.%2$s Look on the right!', WYSIJA), '<strong>', '</strong>').'</p>'.
                                    '<p>'.sprintf(__('You can even %1$ssocial bookmarks%2$s like these:', WYSIJA), '<strong>', '</strong>').'</p>'
                    ),
                    'image' => null,
                    'alignment' => 'left',
                    'static' => false,
                    'position' => 6,
                    'type' => 'content'
                ),
                'block-7' => array(
                    'width' => 184,
                    'alignment' => 'center',
                    'items' => array(
                        array_merge(array(
                            'url' => 'http://www.facebook.com/wysija',
                            'alt' => 'Facebook',
                            'cellWidth' => 61,
                            'cellHeight' => 32
                        ), $bookmarks['facebook']),
                        array_merge(array(
                            'url' => 'http://www.twitter.com/wysija',
                            'alt' => 'Twitter',
                            'cellWidth' => 61,
                            'cellHeight' => 32
                        ), $bookmarks['twitter']),
                        array_merge(array(
                            'url' => 'https://plus.google.com/104749849451537343615',
                            'alt' => 'Google',
                            'cellWidth' => 61,
                            'cellHeight' => 32
                        ), $bookmarks['google'])
                    ),
                    'position' => 7,
                    'type' => 'gallery'
                ),
                'block-8' => array_merge(array(
                        'position' => 8,
                        'type' => 'divider'
                    ), $defaultDivider
                ),
                'block-9' => array(
                    'text' => array(
                        'value' => '<h2><strong>'.__('Step 4:', WYSIJA).'</strong> '.__('and the footer?', WYSIJA).'</h2>'.
                                    '<p>'.sprintf(__('Change the footer\'s content in Wysija\'s %1$sSettings%2$s page.', WYSIJA), '<strong>', '</strong>').'</p>'
                    ),
                    'image' => null,
                    'alignment' => 'left',
                    'static' => false,
                    'position' => 9,
                    'type' => 'content'
                )
            ),
            'footer' => array(
                'text' => NULL,
                'image' => array(
                    'src' => WYSIJA_EDITOR_IMG.'default-newsletter/newsletter/footer.png',
                    'width' => 600,
                    'height' => 46,
                    'alignment' => 'center',
                    'static' => false,
                ),
                'alignment' => 'center',
                'static' => false,
                'type' => 'footer'
            )
        );
        $dataEmail['wj_styles'] = array(
            'html' => array(
                'background' => 'e8e8e8'
            ),
            'header' => array(
                'background' => 'e8e8e8'
            ),
            'body' => array(
                'color' => '000000',
                'family' => 'Arial',
                'size' => 16,
                'background' => 'ffffff'
            ),
            'footer' => array(
                'background' => 'e8e8e8'
            ),
            'h1' => array(
                'color' => '000000',
                'family' => 'Trebuchet MS',
                'size' => 40
            ),
            'h2' => array(
                'color' => '424242',
                'family' => 'Trebuchet MS',
                'size' => 30
            ),
            'h3' => array(
                'color' => '424242',
                'family' => 'Trebuchet MS',
                'size' => 24
            ),
            'a' => array(
                'color' => '0000FF',
                'underline' => false
            ),
            'unsubscribe' => array(
                'color' => '000000'
            ),
            'viewbrowser' => array(
                'color' => '000000',
                'family' => 'Arial',
                'size' => 12
            )
        );
        //---- END DEFAULT EMAIL ---------
        foreach( $dataEmail['wj_data'] as $key =>&$eachval){
            if($key=="body") {
                foreach($eachval as &$realeachval){
                    if(isset($realeachval['text']['value']))    $realeachval['text']['value']=base64_encode($realeachval['text']['value']);
                }
            }
        }
        $dataEmail['params'] = array(
            'quickselection' => array(
                'wp-301' => array(
                    'identifier' => 'wp-301',
                    'width' => 281,
                    'height' => 190,
                    'url' => WYSIJA_EDITOR_IMG.'default-newsletter/newsletter/pigeon.png',
                    'thumb_url' => WYSIJA_EDITOR_IMG.'default-newsletter/newsletter/pigeon-150x150.png'
                )
            )
        );
        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        $wjEngine->setData($dataEmail['wj_data']);
        $result = false;
        $dataEmail['params'] = base64_encode(serialize($dataEmail['params']));
        $dataEmail['wj_styles'] = base64_encode(serialize($dataEmail['wj_styles']));
        $dataEmail['wj_data'] = base64_encode(serialize($dataEmail['wj_data']));

        $dataEmail['replyto_name']=$dataEmail['from_name']=$valuesconfig['from_name'];
        $dataEmail['replyto_email']=$dataEmail['from_email']=$valuesconfig['from_email'];
        $data['email']['email_id']=$modelEmail->insert($dataEmail);

        $modelEmail =& WYSIJA::get('email', 'model');
        $emailData = $modelEmail->getOne(array('wj_styles', 'subject', 'params', 'email_id'), array('email_id' => $data['email']['email_id']));
        $wjEngine->setStyles($emailData['wj_styles'], true);
        $values = array('wj_data' => $wjEngine->getEncoded('data'));
        $values['body'] = $wjEngine->renderEmail($emailData);
        $result = $modelEmail->update($values, array('email_id' => $data['email']['email_id']));
    }
    function createTables(){
        $haserrors=false;
        $filename = dirname(__FILE__).DS."install.sql";
        $handle = fopen($filename, "r");
        $query = fread($handle, filesize($filename));
        fclose($handle);
        $modelObj=&WYSIJA::get("user","model");
        $query=str_replace("CREATE TABLE IF NOT EXISTS `","CREATE TABLE IF NOT EXISTS `".$modelObj->getPrefix(),$query);
        $queries=explode("-- QUERY ---",$query);

        global $wpdb;
        foreach($queries as $qry){
            $wpdb->query($qry);
            $error = mysql_error( $wpdb->dbh );
            if($error){
                $this->notice(mysql_error());
                $haserrors=true;
            }
        }

        $arraytables=array("user_list","user","list","campaign","campaign_list","email","user_field","queue","user_history","email_user_stat","url","email_user_url","url_mail");
        $modelWysija=new WYSIJA_model();
        $missingtables=array();
        foreach($arraytables as $tablename){
            if(!$modelWysija->query("SHOW TABLES like '".$modelWysija->getPrefix().$tablename."';")) {
                $missingtables[]=$modelWysija->getPrefix().$tablename;
            }
        }
        if($missingtables) {
            $this->error(sprintf(__('These tables could not be created on installation: %1$s',WYSIJA),implode(', ',$missingtables)),1);
            $haserrors=true;
        }
        if($haserrors) return false;
        return true;
    }
    function createWYSIJAdir(&$values){
        $upload_dir = wp_upload_dir();
        $dirname=$upload_dir['basedir'].DS."wysija".DS;
        $url=$upload_dir['baseurl']."/wysija/";
        if(!file_exists($dirname)){
            if(!mkdir($dirname, 0755,true)){
                return false;
            }
        }
        $values['uploadfolder']=$dirname;
        $values['uploadurl']=$url;
    }
    function moveData($folder) {
        $fileHelper =& WYSIJA::get('file', 'helper');

        $targetDir = $fileHelper->makeDir($folder);
        if($targetDir === FALSE) {

            return FALSE;
        } else {

            $sourceDir = WYSIJA_DATA_DIR.$folder.DS;

            if(is_dir($sourceDir) === FALSE) return FALSE;

            $files = scandir($sourceDir);

            foreach($files as $filename) {
                if(!in_array($filename, array('.', '..', '.DS_Store', 'Thumbs.db'))) {
                    $this->rcopy($sourceDir.$filename, $targetDir.$filename);
                }
            }
        }
    }
    function rrmdir($dir) {
      if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file)
        if ($file != "." && $file != "..") $this->rrmdir("$dir".DS."$file");
        rmdir($dir);
      }
      else if (file_exists($dir)) {
          $dir=str_replace('/',DS,$dir);
          unlink($dir);
      }
    }
    function rcopy($src, $dst) {
      if (file_exists($dst)) $this->rrmdir($dst);
      if (is_dir($src)) {
        mkdir($dst);
        $files = scandir($src);
        foreach ($files as $file)
        if ($file != "." && $file != "..") $this->rcopy("$src/$file", "$dst/$file");
      }
      else if (file_exists($src)) {
          copy(str_replace('/',DS,$src), str_replace('/',DS,$dst));
      }
    }
    function recordDefaultUserField(){
        $modelUF=&WYSIJA::get("user_field","model");
        $arrayInsert=array(
            array("name"=>__("First name",WYSIJA),"column_name"=>"firstname","error_message"=>__("Please enter first name",WYSIJA)),
            array("name"=>__("Last name",WYSIJA),"column_name"=>"lastname","error_message"=>__("Please enter last name",WYSIJA)));
        foreach($arrayInsert as $insert){
            $modelUF->insert($insert);
            $modelUF->reset();
        }

    }
    function defaultSettings(&$values){
        

        $current_user=WYSIJA::wp_get_userdata();
        $values['replyto_name']=$values['from_name']=$current_user->user_login;
        $values['emails_notified']=$values['replyto_email']=$values['from_email']=$current_user->user_email;
    }

    function createPage(&$values){
        
        $my_post = array(
        'post_status' => 'publish',
        'post_type' => 'wysijap',
        'post_author' => 1,
        'post_content' => '[wysija_page]',
        'post_title' => __("Subscription confirmation",WYSIJA),
        'post_name' => 'subscriptions');

        $helpersWPPOSTS=&WYSIJA::get('wp_posts','model');
        $postss=$helpersWPPOSTS->get_posts(array('post_type'=>'wysijap'));
        $postid=false;
        if($postss){
            if(isset($postss[0]['post_content']) && strpos($postss[0]['post_content'], '[wysija_page]')!==false){
                $postid=$postss[0]['ID'];
            }
        }
        if(!$postid){
            remove_all_actions('pre_post_update');
            remove_all_actions('save_post');
            remove_all_actions('wp_insert_post');
            $values['confirm_email_link']=wp_insert_post( $my_post );
            flush_rewrite_rules();
        }else $values['confirm_email_link']=$postid;

    }

    function testNLplugins(){
        $importHelp=&WYSIJA::get("import","helper");
        $importHelp->testPlugins();
    }
}
