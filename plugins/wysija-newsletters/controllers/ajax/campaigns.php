<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_campaigns extends WYSIJA_control{

    function WYSIJA_control_back_campaigns(){
        if(!WYSIJA::current_user_can('wysija_newsletters'))  die("Action is forbidden.");
        parent::WYSIJA_control();
    }

    function switch_theme() {
        if(isset($_POST['wysijaData'])) {
            $rawData = $_POST['wysijaData'];
            // avoid using stripslashes as it's not reliable depending on the magic quotes settings
            $rawData = str_replace('\"', '"', $rawData);
            // decode JSON data
            $rawData = json_decode($rawData, true);

            $theme = (isset($rawData['theme'])) ? $rawData['theme'] : 'default';

            $wjEngine =& WYSIJA::get('wj_engine', 'helper');
            $res['templates'] = $wjEngine->renderTheme($theme);

            $email_id = (int)$_REQUEST['id'];

            $campaignsHelper =& WYSIJA::get('campaigns', 'helper');

            if(isset($res['templates']['divider_options'])) {
                // save divider
                $campaignsHelper->saveParameters($email_id, 'divider', $res['templates']['divider_options']);
            }

            // save theme used
            $campaignsHelper->saveParameters($email_id, 'theme', $theme);

            $res['templates']['theme'] = $theme;
            $res['styles'] = $wjEngine->renderThemeStyles($theme);
        } else {
            $res['msg'] = __("The theme you selected could not be loaded.",WYSIJA);
            $res['result'] = false;
        }
        return $res;
    }

    function save_editor() {
        // decode json data and convert to array
        $rawData = '';
        if(isset($_POST['wysijaData'])) {
            $rawData = $_POST['wysijaData'];
            // avoid using stripslashes as it's not reliable depending on the magic quotes settings
            $rawData = str_replace('\"', '"', $rawData);
            // decode JSON data
            $rawData = json_decode($rawData, true);
        }

        if(!$rawData){
            $this->error("Error saving",false);
            return array('result' => false);
        }

        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        $wjEngine->setData($rawData);
        $result = false;

        // get email id
        $email_id = (int)$_REQUEST['id'];

        $modelEmail =& WYSIJA::get('email', 'model');
        $emailData = $modelEmail->getOne(array('wj_styles', 'subject', 'params', 'email_id'), array('email_id' => $email_id));

        $wjEngine->setStyles($emailData['wj_styles'], true);

        $values = array('wj_data' => $wjEngine->getEncoded('data'));
        $values['body'] = $wjEngine->renderEmail($emailData);
        $values['email_id']=$email_id;

        // wtf is that? could it explain the Sent On being updated for no reason?
        $modelEmail->columns['modified_at']['autoup']=1;

        // update data in DB
        $result = $modelEmail->update($values, array('email_id' => $email_id));

        if(!$result) {
            // throw error
            $this->error(__("Your email could not be saved", WYSIJA));
        } else {
            // save successful
            $this->notice(__("Your email has been saved", WYSIJA));
        }

        return array('result' => $result);
    }

    function save_styles() {
        // decode json data and convert to array
        $rawData = '';
        if(isset($_POST['wysijaStyles'])) {
            $rawData = $_POST['wysijaStyles'];
            // avoid using stripslashes as it's not reliable depending on the magic quotes settings
            $rawData = str_replace('\"', '"', $rawData);
            // decode JSON data
            $rawData = json_decode($rawData, true);

        }

        // handle checkboxes
        if(array_key_exists('a-underline', $rawData) === false) {
            $rawData['a-underline'] = -1;
        }

        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        $wjEngine->setStyles($wjEngine->formatStyles($rawData));

        $result = false;

        $values = array(
            'wj_styles' => $wjEngine->getEncoded('styles')
        );

        // get email id
        $email_id = (int)$_REQUEST['id'];

        // update data in DB
        $modelEmail =& WYSIJA::get('email', 'model');
        $result = $modelEmail->update($values, array('email_id' => $email_id));

        if(!$result) {
            // throw error
            $this->error(__("Styles could not be saved", WYSIJA));
        } else {
            // save successful
            $this->notice(__("Styles have been saved", WYSIJA));
        }

        return array(
            'styles' => $wjEngine->renderStyles(),
            'result' => $result
        );
    }

    function deleteimg(){

        if(isset($_REQUEST['imgid']) && $_REQUEST['imgid']>0){
            /* delete the image with id imgid */
             $result=wp_delete_attachment($_REQUEST['imgid'],true);
             if($result){
                 $this->notice(__("Image has been deleted.",WYSIJA));
             }
        }

        $res=array();
        $res['result'] = $result;
        return $res;
    }

    function deleteTheme(){
        if(isset($_REQUEST['themekey']) && $_REQUEST['themekey']){
            /* delete the image with id imgid */
            $helperTheme=&WYSIJA::get("themes","helper");
            $result=$helperTheme->delete($_REQUEST['themekey']);
        }

        $res=array();
        $res['result'] = $result;
        return $res;
    }


    function save_IQS() {
        // decode json data and convert to array
        $wysijaIMG = '';
        if(isset($_POST['wysijaIMG'])) {
            $wysijaIMG = json_decode(stripslashes($_POST['wysijaIMG']), TRUE);
        }
        $values = array(
            'params' => array('quickselection'=>$wysijaIMG)
        );

        // get email id
        $email_id = (int)$_REQUEST['id'];
        $values['email_id']=$email_id;

        // update data in DB
        $modelEmail =& WYSIJA::get('email', 'model');
        $result = $modelEmail->update($values, array('email_id' => $email_id));

        if(!$result) {
            // throw error
            $this->error(__('Image selection has not been saved.', WYSIJA));
        } else {
            // save successful
            $this->notice(__('Image selection has been saved.', WYSIJA));
        }

        return array('result' => $result);
    }


    function view_NL() {
        // get campaign id
        $email_id = (int)$_REQUEST['id'];

        // update data in DB
        $modelEmail =& WYSIJA::get('email', 'model');
        $result = $modelEmail->getOne(false,array('email_id' => $email_id));

        echo $result['body'];
        exit;
    }

    function display_NL() {
        // get email id
        $email_id = (int)$_REQUEST['id'];

        // update data in DB
        $modelEmail =& WYSIJA::get('email', 'model');
        $email= $modelEmail->getOne(false,array('email_id' => $email_id));

        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        $wjEngine->setStyles($result['wj_styles'], true);
        $wjEngine->setData($result['wj_data'], true);
        $html = $wjEngine->renderEmail($email);
        print $html;
        exit;
    }

    /**
     * returns a list of articles to the popup in the visual editor
     * @global type $wpdb
     * @return boolean
     */
    function get_articles(){
        // fixes issue with pcre functions
        @ini_set('pcre.backtrack_limit', 1000000);

        $model=&WYSIJA::get('user','model');

        //Carefull WordPress global
        global $wpdb;
        $mConfig=&WYSIJA::get('config','model');
        $isFullArticle=$mConfig->getValue('editor_fullarticle');

        //test to set the default value
        if(!$isFullArticle && isset($_REQUEST['fullarticle'])){
            $mConfig->save(array('editor_fullarticle'=>true));
        }

        if($isFullArticle && !isset($_REQUEST['fullarticle'])){
            $mConfig->save(array('editor_fullarticle'=>false));
        }
        $cpt = array('post');
        if(isset($_REQUEST['cpt'])){
            $cpt = array();
            if($_REQUEST['cpt'] === 'all') {
                $hWPTools =& WYSIJA::get('wp_tools','helper');
                $post_types = $hWPTools->get_post_types();
                $cpt = array_keys($post_types);
                $cpt[] = 'post';
                $cpt[] = 'page';
            } else {
                $cpt = $_REQUEST['cpt'];
            }

            $query_cpt = '';
            if(is_array($cpt)) {
                $query_cpt = ' '.$wpdb->posts.'.post_type IN ("'.  implode('", "', $cpt).'")';
            } else {
                $query_cpt = ' '.$wpdb->posts.'.post_type="'.$cpt.'"';
            }
        }
        $hWPTools =& WYSIJA::get('wp_tools','helper');
        $post_statuses = $hWPTools->get_post_statuses();
        if(isset($_REQUEST['status'])){
            $statuses = array();
            if($_REQUEST['status'] === 'all') {

                $statuses = array_keys($post_statuses);
                $statuses[] = 'future';
            } else {
                $statuses = $_REQUEST['status'];
            }

            $query_statuses = '';
            if(is_array($statuses)) {
                $query_statuses = ' AND '.$wpdb->posts.'.post_status IN ("'.  implode('", "', $statuses).'")';
            } else {
                $query_statuses = ' AND '.$wpdb->posts.'.post_status="'.$statuses.'"';
            }
        }

        $limitquery='';
        $res=array();
        $res['append']=false;

        if(isset($_REQUEST['query_offset']) && (int)$_REQUEST['query_offset']){
            $res['append']=true;
            $limitquery = ' LIMIT '.(int)($_REQUEST['query_offset']).',10';
        } else {
            $limitquery = ' LIMIT 0,10';
        }

        if(isset($_REQUEST['search']) && strlen(trim($_REQUEST['search'])) > 0) {
            $querystr = "SELECT $wpdb->posts.ID , $wpdb->posts.post_type, $wpdb->posts.post_title, $wpdb->posts.post_content, $wpdb->posts.post_excerpt , $wpdb->posts.post_status
            FROM $wpdb->posts
            WHERE $wpdb->posts.post_title like '%".addcslashes(mysql_real_escape_string($_REQUEST['search'],$wpdb->dbh), '%_' )."%'";

            $querystr.= ' AND '.$query_cpt.$query_statuses;

            $querystr.=" ORDER BY $wpdb->posts.post_date DESC";
            $querystr.=$limitquery;
            // query to count total rows
            $queryCount = "SELECT COUNT(*) as total FROM $wpdb->posts WHERE $wpdb->posts.post_title like '%".addcslashes(mysql_real_escape_string($_REQUEST['search'],$wpdb->dbh), '%_' )."%' AND ".$query_cpt.$query_statuses;
        }else{
            $querystr = "SELECT $wpdb->posts.ID , $wpdb->posts.post_type, $wpdb->posts.post_title, $wpdb->posts.post_content, $wpdb->posts.post_excerpt , $wpdb->posts.post_status
            FROM $wpdb->posts WHERE";

            $querystr.= $query_cpt.$query_statuses;

            $querystr.= " ORDER BY $wpdb->posts.post_date DESC";
            $querystr.=$limitquery;

            // query to count total rows
            $queryCount = "SELECT COUNT(*) as total FROM $wpdb->posts WHERE ".$query_cpt.$query_statuses;
        }

        $res['posts']=$model->query('get_res',$querystr);
        $count = $model->query('get_row', $queryCount);
        $res['total'] = (int)$count['total'];

        $helper_engine=&WYSIJA::get('wj_engine','helper');
        $helper_articles =& WYSIJA::get('articles', 'helper');

        // set params for post format
        $params = array('post_content' => 'full');

        //if excerpt has been requested then we try to provide it */
        if(!isset($_REQUEST['fullarticle'])) {
            $params['post_content'] = 'excerpt';
        }

        if($res['posts']){
            $res['result'] = true;
            foreach($res['posts'] as $k =>$v){
                if($mConfig->getValue('interp_shortcode'))    $res['posts'][$k]['post_content']=apply_filters('the_content',$res['posts'][$k]['post_content']);

                $res['posts'][$k]['post_status']=$post_statuses[$res['posts'][$k]['post_status']];


                // get thumbnail
                $res['posts'][$k]['post_image'] = $helper_articles->getImage($v);

                // convert post data into block data
                $block = $helper_articles->convertPostToBlock($res['posts'][$k], $params);

                // make editor block from post data
                $res['posts'][$k]['html'] = base64_encode($helper_engine->renderEditorBlock($block));
            }
        }else {
            $res['msg'] = __('There are no posts corresponding to that search.',WYSIJA);
            $res['result'] = false;
        }

        return $res;
    }

    function send_preview($spamtest=false){
        $mailer=&WYSIJA::get('mailer','helper');
        $email_id = $_REQUEST['id'];
        $resultarray=array();

        // update data in DB
        $modelEmail =& WYSIJA::get('email', 'model');
        $modelEmail->getFormat=OBJECT;
        $emailObject = $modelEmail->getOne(false,array('email_id' => $email_id));
        $mailer->testemail=true;


        if(isset($_REQUEST['data'])){
           $dataTemp=$_REQUEST['data'];
            $_REQUEST['data']=array();
            foreach($dataTemp as $val) $_REQUEST['data'][$val['name']]=$val['value'];
            $dataTemp=null;
            foreach($_REQUEST['data'] as $k =>$v){
                $newkey=str_replace(array('wysija[email][',']'),'',$k);
                $configVal[$newkey]=$v;
            }
            if(isset($configVal['from_name'])){
                $params=array(
                    'from_name'=>$configVal['from_name'],
                    'from_email'=>$configVal['from_email'],
                    'replyto_name'=>$configVal['replyto_name'],
                    'replyto_email'=>$configVal['replyto_email']);
                if(isset($configVal['subject']))    $emailObject->subject=$configVal['subject'];
            }

        }else{
            $params=array(
                'from_name'=>$emailObject->from_name,
                'from_email'=>$emailObject->from_email,
                'replyto_name'=>$emailObject->replyto_name,
                'replyto_email'=>$emailObject->replyto_email
            );
        }

        if(strpos($_REQUEST['receiver'], ',')) {
            $receivers = explode(',',$_REQUEST['receiver']);
        } else if(strpos($_REQUEST['receiver'], ';')) {
            $receivers = explode(';',$_REQUEST['receiver']);
        } else {
            $receivers = array($_REQUEST['receiver']);
        }

        foreach($receivers as $key => $receiver){
            $receivers[$key] = trim($receiver);
            $dummyReceiver = new stdClass();
            $dummyReceiver->user_id = 0;
            $dummyReceiver->email = $receiver;
            $dummyReceiver->status = 1;
            $dummyReceiver->lastname = $dummyReceiver->firstname =$langextra='';
            if($spamtest){
                $dummyReceiver->firstname ='Mail Tester';
                if(defined('WPLANG') && WPLANG) $langextra='&lang='.WPLANG;
                $resultarray['urlredirect']='http://www.mail-tester.com/check.php?id='.urlencode($dummyReceiver->email).$langextra;
            }

            $receivers[$key] = $dummyReceiver;

        }

        $emailClone=array();
        foreach($emailObject as $kk=>$vv)  $emailClone[$kk]=$vv;


        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        // set data & styles
        if(isset($emailClone['wj_data'])) { $wjEngine->setData($emailClone['wj_data'], true); } else { $wjEngine->setData(); }
        if(isset($emailClone['wj_styles'])) { $wjEngine->setStyles($emailClone['wj_styles'], true); } else { $wjEngine->setStyles(); }

        // generate email html body
        $body = $wjEngine->renderEmail($emailClone);

        // get back email data as it will be updated during the rendering (articles ids + articles count)
        $emailChild = $wjEngine->getEmailData();

        if((int)$emailChild['type'] === 2 && isset($emailChild['params']['autonl']['articles'])){

            $itemCount = 0;
            $totalCount = 1;
            $firstSubject = '';

            if(isset($emailChild['params']['autonl']['articles']['count'])) $itemCount = (int)$emailChild['params']['autonl']['articles']['count'];
            if(isset($emailChild['params']['autonl']['articles']['first_subject'])) $firstSubject = $emailChild['params']['autonl']['articles']['first_subject'];
            if(isset($emailClone['params']['autonl']['total_child'])) $totalCount = (int)$emailClone['params']['autonl']['total_child'] + 1;

            if(empty($firstSubject)) {
                $this->error(__('There are no articles to be sent in this email.',WYSIJA),1);
                return array('result' => false);
            }
            $emailObject->subject = str_replace(
                    array('[total]','[number]','[post_title]'),
                    array($itemCount, $totalCount, $firstSubject),
                    $emailChild['subject']);
        }

        $successmsg=__('Your email preview has been sent to %1$s', WYSIJA);

        if(isset($emailObject->params)) {
            $params['params']=$emailObject->params;

            if(isset($configVal['params[googletrackingcode'])){
                $paramsemail=array();
                if(!is_array($emailObject->params)) $paramsemail=unserialize(base64_decode($emailObject->params));

                if(trim($configVal['params[googletrackingcode'])) {
                    $paramsemail['googletrackingcode']=$configVal['params[googletrackingcode'];
                }
                else {
                    unset($paramsemail['googletrackingcode']);
                }
                $params['params']=base64_encode(serialize($paramsemail));
            }
        }

        $params['email_id']=$emailObject->email_id;
        $receiversList = array();
        $res = false;
        foreach($receivers as $receiver){
            if($mailer->sendSimple($receiver,  stripslashes($emailObject->subject),$emailObject->body,$params)) {
                $res = true;
                $receiversList[] = $receiver->email;
            }
            WYSIJA::log('preview_sent', $mailer, 'manual');
        }

        if($res === true) {
            $this->notice(sprintf($successmsg, implode(', ', $receiversList)));
        }

        $resultarray['result'] = $res;
        return $resultarray;
    }

    /**
     * send spam test function step 2 of the newsletter edition process
     */
    function send_spamtest(){
        return apply_filters('wysija_send_spam_test','',$this);
    }

    function set_divider()
    {
        $src = isset($_POST['wysijaData']['src']) ? $_POST['wysijaData']['src'] : NULL;
        $width = isset($_POST['wysijaData']['width']) ? (int)$_POST['wysijaData']['width'] : NULL;
        $height = isset($_POST['wysijaData']['height']) ? (int)$_POST['wysijaData']['height'] : NULL;

        if($src === NULL OR $width === NULL OR $height === NULL) {
            // there is a least one missing parameter, fallback to default divider
            $dividersHelper =& WYSIJA::get('dividers', 'helper');
            $divider = $dividersHelper->getDefault();
        } else {
            // use provided params
            $divider = array(
                'src' => $src,
                'width' => $width,
                'height' => $height
            );
        }

        // update campaign parameters
        $email_id = (int)$_REQUEST['id'];
        $campaignsHelper =& WYSIJA::get('campaigns', 'helper');
        $campaignsHelper->saveParameters($email_id, 'divider', $divider);

        // set params
        $block = array_merge(array('no-block' => true, 'type' => 'divider'), $divider);

        $helper_engine=&WYSIJA::get("wj_engine","helper");
        return base64_encode($helper_engine->renderEditorBlock($block));
    }

    function get_social_bookmarks() {
        $size = isset($_POST['wysijaData']['size']) ? $_POST['wysijaData']['size'] : NULL;
        $theme = isset($_POST['wysijaData']['theme']) ? $_POST['wysijaData']['theme'] : NULL;

        $bookmarksHelper =& WYSIJA::get('bookmarks', 'helper');
        $bookmarks = $bookmarksHelper->getAll($size, $theme);

        return json_encode(array('icons' => $bookmarks));
    }

    function generate_social_bookmarks() {

        $size = 'medium';
        $iconset = '01';

        if(isset($_POST['wysijaData']) && !empty($_POST['wysijaData'])) {
            $data = $_POST['wysijaData'];
            $items = array();

            foreach($data as $key => $values) {
                if($values['name'] === 'bookmarks-size') {
                    // get size
                    $size = $values['value'];
                } else if($values['name'] === 'bookmarks-theme') {
                    // get theme name
                    $theme = $values['value'];
                } else if($values['name'] === 'bookmarks-iconset') {
                    // get iconset
                    $iconset = $values['value'];
                    if(strlen(trim($iconset)) === 0) {
                        $this->error('No iconset specified', false);
                        return false;
                    }
                } else {
                    $keys = explode('-', $values['name']);
                    $network = $keys[1];
                    $property = $keys[2];
                    if(array_key_exists($network, $items)) {
                        $items[$network][$property] = $values['value'];
                    } else {
                        $items[$network] = array($property => $values['value']);
                    }
                }
            }
        }

        $urls = array();
        // check data and remove network with an empty url
        foreach($items as $network => $item) {
            if(strlen(trim($item['url'])) === 0) {
                // empty url
                unset($items[$network]);
            } else {
                // url specified
                $urls[$network] = $item['url'];
            }
        }

        // check if there's at least one url left
        if(empty($urls)) {
            $this->error('No url specified', false);
            return false;
        }

        // save url in config
        $config=&WYSIJA::get('config',"model");
        $config->save(array('social_bookmarks' => $urls));

        // get iconset icons
        $bookmarksHelper =& WYSIJA::get('bookmarks', 'helper');

        // if the iconset is 00, then it's the theme's bookmarks
        if($iconset === '00') {
            $icons = $bookmarksHelper->getAllByTheme($theme);
        } else {
            // otherwise it's a basic iconset
            $icons = $bookmarksHelper->getAllByIconset($size, $iconset);
        }


        // format data
        $block = array(
            'position' => 1,
            'type' => 'gallery',
            'items' => array(),
            'alignment' => 'center'
        );

        $width = 0;
        foreach($items as $key => $item) {
            $block['items'][] = array_merge($item, $icons[$key], array('alt' => ucfirst($key)));
            $width += (int)$icons[$key]['width'];
        }
        // add margin between icons
        $width += (count($block['items']) - 1) * 10;
        // set optimal width
        $block['width'] = max(0, min($width, 564));

        $helper_engine=&WYSIJA::get("wj_engine","helper");
        return base64_encode($helper_engine->renderEditorBlock($block));
    }

    function install_theme() {
        if( isset($_REQUEST['theme_id'])){

            //check if theme is premium if you have the premium licence
            if(isset($_REQUEST['premium']) && $_REQUEST['premium']){
                $getpremiumtheme=apply_filters('wysija_install_theme_premium', false);

                if(!$getpremiumtheme){
                    $wjEngine =& WYSIJA::get('wj_engine', 'helper');
                    $themes = $wjEngine->renderThemes();
                    return array("result"=>false, 'themes' => $themes);
                }
            }


            $httpHelp=&WYSIJA::get('http','helper');
            $url=admin_url('admin.php');

            $helperToolbox=&WYSIJA::get('toolbox','helper');
            $domain_name=$helperToolbox->_make_domain_name($url);

            $request='http://api.wysija.com/download/zip/'.$_REQUEST['theme_id'].'?domain='.$domain_name;

            $ZipfileResult = $httpHelp->request($request);

            if(!$ZipfileResult){
                $result=false;
                $this->error(__('We were unable to contact the API, the site may be down. Please try again later.',WYSIJA),true);
            }else{
                $themesHelp=&WYSIJA::get('themes','helper');
                $result = $themesHelp->installTheme($ZipfileResult);

                // refresh themes list
                $wjEngine =& WYSIJA::get('wj_engine', 'helper');
                $themes = $wjEngine->renderThemes();
            }
        }else{
            $result=false;
            $this->notice('missing info');
        }

        return array('result'=>$result, 'themes' => $themes);
    }

    function refresh_themes() {
        // refresh themes list
        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        return array("result"=>true, 'themes' => $wjEngine->renderThemes());
    }

    function generate_auto_post() {
        // get params and generate html
        $wjEngine =& WYSIJA::get('wj_engine', 'helper');
        $helper_articles =& WYSIJA::get('articles', 'helper');

        // get parameters
        $block_params = array();
        if(isset($_POST['wysijaData'])) {
            foreach($_POST['wysijaData'] as $pairs) {
                // special cases
                switch($pairs['name']) {
                    case 'readmore':
                    case 'nopost_message':
                        $block_params[] = array('key' => $pairs['name'], 'value' => base64_encode(stripslashes($pairs['value'])));
                        break;
                    default:
                        $block_params[] = array('key' => $pairs['name'], 'value' => $pairs['value']);
                }
            }
        }

        if(empty($block_params)) {
            // an error occurred, do something!
            return false;
        } else {
            $data = array(
                'type' => 'auto-post',
                'params' => $block_params
            );
            return base64_encode($wjEngine->renderEditorBlock($data));
        }
    }

    function load_auto_post() {
        $params = array();

        if(isset($_POST['wysijaData'])) {

            $pairs = explode('&', $_POST['wysijaData']);

            foreach($pairs as $pair) {
                list($key, $value) = explode('=', $pair);


                switch($key) {
                    case 'autopost_count':
                        $params[$key] = (int)$value;
                        break;
                    case 'readmore':
                    case 'nopost_message':
                        $params[$key] = base64_decode($value);
                        break;
                    case 'exclude':
                        $params[$key] = explode(',', $value);
                        break;
                    default:
                        $params[$key] = $value;
                }
            }
        }

        if(empty($params)) {
            // an error occurred, do something!
            return false;
        } else {
            // get email params
            $email_id = (int)$_REQUEST['id'];
            $modelEmail =& WYSIJA::get('email', 'model');
            $email = $modelEmail->getOne(array('params','sent_at','campaign_id'), array('email_id' => $email_id));

            $articlesHelper =& WYSIJA::get('articles', 'helper');
            $wjEngine =& WYSIJA::get('wj_engine', 'helper');

            // see if posts have already been sent
            if(!empty($email['params']['autonl']['articles']['ids'])) {

                if(!isset($params['exclude'])) { $params['exclude'] = array(); }

                $params['exclude'] = array_unique(array_merge($email['params']['autonl']['articles']['ids'], $params['exclude']));
            }

            //we set the post_date to filter articles only older than that one
            if(isset($email['params']['autonl']['firstSend'])){
                $params['post_date'] = $email['params']['autonl']['firstSend'];
            }

            // if immediate let it know to the get post
            if(isset($email['params']['autonl']['articles']['immediatepostid'])){
                $params['include'] = $email['params']['autonl']['articles']['immediatepostid'];
                $params['post_limit'] = 1;
            }else{
                //we set the post_date to filter articles only older than the last time we sent articles
                if(isset($email['params']['autonl']['lastSend'])){
                    $params['post_date'] = $email['params']['autonl']['lastSend'];
                }else{
                    //get the latest child newsletter sent_at value
                    $mEmail=&WYSIJA::get('email','model');
                    $mEmail->reset();
                    $mEmail->orderBy('email_id','DESC');
                    $lastEmailSent=$mEmail->getOne(false,array('campaign_id'=>$email['campaign_id'],'type'=>'1'));

                    if(isset($data['sent_at'])) $params['post_date'] = $lastEmailSent['sent_at'];
                }
            }



            $posts = $articlesHelper->getPosts($params);

            // used to keep track of post ids present in the auto post
            $post_ids = array();

            // cleanup post and get image
            foreach($posts as $key => $post) {
                if($params['image_alignment'] !== 'none') {
                    // attempt to get post image
                    $posts[$key]['post_image'] = $articlesHelper->getImage($post);
                }

                $posts[$key] = $articlesHelper->convertPostToBlock($posts[$key], $params);

                // store article id
                $post_ids[] = $post['ID'];
            }
            // store article ids
            $params['post_ids'] = join(',', $post_ids);

            // get divider if necessary
            if($params['show_divider'] === 'yes') {
                if(isset($email['params']['divider'])) {
                    $params['divider'] = $email['params']['divider'];
                } else {
                    $dividersHelper =& WYSIJA::get('dividers', 'helper');
                    $params['divider'] = $dividersHelper->getDefault();
                }
            }

            return base64_encode($wjEngine->renderEditorAutoPost($posts, $params));
        }
    }
}
