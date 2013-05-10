<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_control_back_config extends WYSIJA_control_back{
    var $view='config';
    var $model='config';

    function WYSIJA_control_back_config(){

    }

    function main() {
        parent::WYSIJA_control_back();
        wp_enqueue_style('thickbox');

        if(!isset($_REQUEST['action'])) $this->action='main';
        else $this->action=$_REQUEST['action'];

        $this->jsTrans['testemail'] = __('Sending a test email', WYSIJA);
        $this->jsTrans['bounceconnect'] = __('Bounce handling connection test', WYSIJA);
        $this->jsTrans['processbounceT'] = __('Bounce handling processing', WYSIJA);
        $this->jsTrans['doubleoptinon'] = __('Subscribers will now need to activate their subscription by email in order to receive your newsletters. This is recommended.', WYSIJA);
        $this->jsTrans['doubleoptinoff'] = __('Unconfirmed subscribers will receive your newslettters from now on without the need to activate their subscriptions.', WYSIJA);
        $this->jsTrans['processbounce'] = __('Process bounce handling now!', WYSIJA);
        $this->jsTrans['errorbounceforward'] = __('When setting up the bounce system, you need to have a different address for the bounce email and the forward to address', WYSIJA);

        // form list
        $this->jsTrans['suredelete'] = __('Are you sure you want to delete this form?', WYSIJA);

        switch($this->action) {
            case 'log':
            case 'save':
            case 'clearlog':
                wp_enqueue_script('wysija-config-settings', WYSIJA_URL.'js/admin-config-settings.js', array('jquery'), WYSIJA::get_version());
            case 'form_add':
            case 'form_edit':
            case 'form_duplicate':
            case 'form_delete':
            case 'form_widget_settings':
                return $this->{$this->action}();
                break;
            case 'reinstall':
                $this->reinstall();
                return;
                break;
            case 'dkimcheck':
                $this->dkimcheck();
                if(defined('WYSIJA_REDIRECT'))  $this->redirectProcess();
                return;
                break;
            case 'doreinstall':
                $this->doreinstall();
                if(defined('WYSIJA_REDIRECT')){
                     global $wysi_location;
                     $wysi_location='admin.php?page=wysija_campaigns';
                    $this->redirectProcess();
                }
                return;
                break;
            default:
                wp_enqueue_script('wysija-config-settings', WYSIJA_URL.'js/admin-config-settings.js', array('jquery'), WYSIJA::get_version());
        }

        if(WYSIJA_DBG > 1) {
            $this->viewObj->arrayMenus = array('log' => 'View log');
        }

        $this->data=array();
        $this->action='main';

        if(isset($_REQUEST['validate'])){
            $this->notice(str_replace(array('[link]','[/link]'),
            array('<a title="'.__('Get Premium now',WYSIJA).'" class="premium-tab" href="javascript:;">','</a>'),
            __('You\'re almost there. Click this [link]link[/link] to activate the licence you have just purchased.',WYSIJA)));

        }

    }

    function dkimcheck(){

        if(isset($_POST['xtz'])){

            $dataconf=json_decode(base64_decode($_POST['xtz']));
            if(isset($dataconf->dkim_pubk->key) && isset($dataconf->dkim_privk)){
                $modelConf=&WYSIJA::get('config','model');
                $dataconfsave=array('dkim_pubk'=>$dataconf->dkim_pubk->key, 'dkim_privk'=>$dataconf->dkim_privk,'dkim_1024'=>1);

                $modelConf->save($dataconfsave);
                WYSIJA::update_option('dkim_autosetup',false);
            }
        }

        $this->redirect('admin.php?page=wysija_config');
        return true;
    }

    function save(){
        $_REQUEST   = stripslashes_deep($_REQUEST);
        $_POST   = stripslashes_deep($_POST);
        $this->requireSecurity();
        $this->modelObj->save($_REQUEST['wysija']['config'],true);

        //wp_redirect('admin.php?page=wysija_config'.$_REQUEST['redirecttab']);

    }

    function reinstall(){
        $this->viewObj->title=__('Reinstall Wysija?',WYSIJA);
        return true;
    }

    function changeMode(){
        $helperFile=&WYSIJA::get('file','helper');
        $helperFile->chmodr(WYSIJA_UPLOADS_DIR, 0666, 0777);
        $this->redirect('admin.php?page=wysija_config');
        return true;
    }

    function doreinstall(){

        if(isset($_REQUEST['postedfrom']) && $_REQUEST['postedfrom'] === 'reinstall') {
            $uninstaller=&WYSIJA::get('uninstall','helper');
            $uninstaller->reinstall();
        }
        $this->redirect('admin.php?page=wysija_config');
        return true;
    }

    function render(){
        $this->checkTotalSubscribers();
        $this->viewObj->render($this->action,$this->data);
    }

    function log(){
        $this->viewObj->arrayMenus=array('clearlog'=>'Clear log');
        $this->viewObj->title='Wysija\'s log';
        return true;
    }

    function clearlog(){
        update_option('wysija_log', array());
        $this->redirect('admin.php?page=wysija_config&action=log');
        return true;
    }

    // WYSIJA Form Editor
    function form_add() {
        $helper_form_engine =& WYSIJA::get('form_engine', 'helper');
        // set default form data
        $helper_form_engine->set_data();

        // create form in database with default data
        $form = array(
            'name' => __('New Form', WYSIJA),
            'data' => $helper_form_engine->get_encoded('data')
        );

        // insert into form table
        $model_forms =& WYSIJA::get('forms', 'model');
        $form_id = $model_forms->insert($form);

        if($form_id !== null && (int)$form_id > 0) {
            // redirect to form editor, passing along the newly created form id
            //$this->redirect('admin.php?page=wysija_config&action=form_edit&id='.$form_id);
            WYSIJA::redirect('admin.php?page=wysija_config&action=form_edit&id='.$form_id);
        } else {
            WYSIJA::redirect('admin.php?page=wysija_config#tab-forms');
        }
        return true;
    }

    function form_duplicate() {

        if(isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $form_id = (int)$_GET['id'];

            $model_forms =& WYSIJA::get('forms', 'model');

            // get form data
            $form = $model_forms->getOne(array('name', 'data', 'styles'), array('form_id' => $form_id));

            if(empty($form)) {
                $this->error(__('This form does not exist', WYSIJA), true);
            } else {
                // reset model forms
                $model_forms->reset();

                // add "copy" to the name
                $form['name'] = $form['name'].' '.__('Copy', WYSIJA);

                // insert form (duplicated)
                $model_forms->insert($form);

                // display notice
                $this->notice(sprintf(__('The form named "%1$s" has been created.', WYSIJA), $form['name']));
            }
        }

        WYSIJA::redirect('admin.php?page=wysija_config#tab-forms');
    }

    function form_delete() {

        $this->requireSecurity();

        if(isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $form_id = (int)$_GET['id'];

            $model_forms =& WYSIJA::get('forms', 'model');

            // get form data
            $form = $model_forms->getOne(array('name'), array('form_id' => $form_id));

            if(empty($form)) {
                $this->error(__('This form has already been deleted.', WYSIJA), true);
            } else {
                // delete the form in the database
                $model_forms->reset();
                $model_forms->delete(array('form_id' => $form_id));

                // display notice
                $this->notice(sprintf(__('The form named "%1$s" has been deleted.', WYSIJA), $form['name']));
            }
        }

        WYSIJA::redirect('admin.php?page=wysija_config#tab-forms');
    }

    function form_edit() {
        // wysija form editor javascript files
        $this->js[]='wysija-form-editor';
        $this->js[]='wysija-admin-ajax-proto';
        $this->js[]='wysija-admin-ajax';
        $this->js[]='wysija-base-script-64';

        // make sure the editor content is not cached
        //header('Cache-Control: no-cache, max-age=0, must-revalidate, no-store'); // HTTP/1.1
        //header('Expires: Fri, 9 Mar 1984 00:00:00 GMT');

        // get form id
        $form_id = (isset($_REQUEST['id']) && (int)$_REQUEST['id'] > 0) ? (int)$_REQUEST['id'] : null;
        $form = array('name' => __('New form', WYSIJA));

        // if no form id was specified, then it's a new form
        if($form_id !== null) {
            // try to get form data based on form id
            $model_forms =& WYSIJA::get('forms', 'model');
            $form = $model_forms->getOne($form_id);

            // if the form does not exist
            if(empty($form)) {
                // redirect to forms list
                $this->error(__('This form does not exist.', WYSIJA), true);
                WYSIJA::redirect('admin.php?page=wysija_config#tab-forms');
            } else {
                // pass form id to the view
                $this->data['form_id'] = (int)$form['form_id'];
            }
        }
        // pass form to the view
        $this->data['form'] = $form;

        $helper_form_engine =& WYSIJA::get('form_engine', 'helper');
        $lists = $helper_form_engine->get_lists();

        // select default list
        $default_list = array();
        if(!empty($lists)) {
            $default_list[] = array(
                'list_id' => $lists[0]['list_id'],
                'is_checked' => 0
            );
        }

        $this->data['lists'] = $lists;

        // get available custom fields
        $model_user_field =& WYSIJA::get('user_field', 'model');
        $model_user_field->orderBy('field_id');
        $custom_fields = $model_user_field->getRows(false);

        // extra widgets that can be added more than once
        $extra_fields = array(
            array(
                'name' => __('List selection', WYSIJA),
                'column_name' => 'list',
                'column_type' => 'list',
                'params' => array(
                    'label' => __('Select a list:', WYSIJA),
                    'values' => $default_list
                )
            ),
            array(
                'name' => __('Random text or instructions', WYSIJA),
                'column_name' => 'text',
                'column_type' => 'text',
                'params' => array(
                    'text' => __('Random text or instructions', WYSIJA)
                )
            ),
            array(
                'name' => __('Divider', WYSIJA),
                'column_name' => 'divider',
                'column_type' => 'divider'
            )
        );

        // set data to be passed to the view
        $this->data['custom_fields'] = array_merge($custom_fields, $extra_fields);

        // translations
        $this->jsTrans = array_merge($this->jsTrans, $helper_form_engine->get_translations());

        // This should be the title of the page but I don't know how to make it happen...
        // __('Edit', WYSIJA).' '.$this->data['form']['name'];
    }

    /*
     * Handles the settings popup of wysija form widgets
     */
    function form_widget_settings() {
        $this->iframeTabs = array('form_widget_settings' => __('Widget Settings', WYSIJA));
        $this->js[] = 'wysija-admin-ajax';
        $this->js[] = 'wysija-base-script-64';
        $this->js[] = 'wysija-form-widget-settings';

        $_GET['tab'] = 'form_widget_settings';

        // extract parameters from url
        $params = array();
        foreach(explode('|', $_REQUEST['params']) as $pair) {
            // extract both key and value
            list($key, $value) = explode(':', $pair);

            // decode value
            $value = base64_decode($value);
            // unserialize if necessary (using is_serialized from WordPress)
            if(is_serialized($value) === true) {
                $value = unserialize($value);
            }
            $params[$key] = $value;
        }

        // common widget data
        $this->data['name'] = $_REQUEST['name'];
        $this->data['type'] = $_REQUEST['type'];
        $this->data['field'] = $_REQUEST['field'];

        // widget params
        $this->data['params'] = $params;

        // extra data that needs to be fetched for some widget
        $extra = array();

        switch($this->data['type']) {
            // in case of the list widget, we need to pass an array of all available lists
            case 'list':
                $model_list =& WYSIJA::get('list', 'model');

                // get lists users can subscribe to (aka "enabled list")
                $extra['lists'] = $model_list->get(array('name', 'list_id', 'is_public'), array('is_enabled' => 1));
                break;
        }

        $this->data['extra'] = $extra;

        return $this->popupContent();
        exit;
    }
    // End: WYSIJA Form Editor
}