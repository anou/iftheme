<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_view_back_config extends WYSIJA_view_back{

    var $title="Settings";
    var $icon="icon-options-general";
    var $skip_header = true;

    function WYSIJA_view_back_support(){
        $this->title=__("Settings",WYSIJA);
        $this->WYSIJA_view_back();
    }
    function reinstall(){
        ?>
        <form name="wysija-settings" method="post" id="wysija-settings" action="" class="form-valid" autocomplete="off">
            <input type="hidden" value="doreinstall" name="action"/>
            <input type="hidden" value="reinstall" name="postedfrom"/>
            <h3><?php _e("If you confirm this, all your current Wysija data will be erased (newsletters, statistics, lists, subscribers, etc.).",WYSIJA); ?></h3>
            <p class="submit">
                <input type="submit" value="<?php _e("Confirm Reinstallation",WYSIJA)?>" class="button-secondary" id="submit" name="submit" />
                <?php $this->secure(array('action'=>"doreinstall")); ?>
            </p>
        </form>
        <?php

    }

    function fieldFormHTML_commentform($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $checked=false;
        if($this->model->getValue($key))   $checked=true;
        $fieldHTML='<p style="float:left;"><label for="'.$key.'">';
        $fieldHTML.=$formsHelp->checkbox(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']','class'=>'activateInput'),1,$checked);
        $fieldHTML.='</label>';
        $value=$this->model->getValue($key.'_linkname');

        $fieldHTML.='<div id="'.$key.'_linkname'.'" class="linknamecboxes">';
        $fieldHTML.=$formsHelp->input(array('name'=>'wysija['.$model.']['.$key.'_linkname]', 'size'=>'75'),$value).'</p>';
        $modelList=&WYSIJA::get('list','model');
        $lists=$modelList->get(array('name','list_id'),array('is_enabled'=>1));
        $valuefield=$this->model->getValue($key.'_lists');
        if(!$valuefield) $valuefield=array();
        foreach($lists as $list){
            if(in_array($list['list_id'], $valuefield)) $checked=true;
            else $checked=false;

            $fieldHTML.= '<p class="labelcheck"><label for="list-'.$list['list_id'].'">'.$formsHelp->checkbox( array('id'=>'list-'.$list['list_id'],
                        'name'=>'wysija[config]['.$key.'_lists][]', 'class'=>'validate[minCheckbox[1]]'),
                            $list['list_id'],$checked).$list['name'].'</label></p>';
        }
        $fieldHTML.='</div>';


        return $fieldHTML;
    }

    function fieldFormHTML_managesubscribe($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $checked=false;
        if($this->model->getValue($key))   $checked=true;
        $fieldHTML='<p style="float:left;"><label for="'.$key.'">';
        $fieldHTML.=$formsHelp->checkbox(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']','class'=>'activateInput'),1,$checked);
        $fieldHTML.='</label>';
        $value=$this->model->getValue($key.'_linkname');

        $fieldHTML.='<div id="'.$key.'_linkname'.'" class="linknamecboxes">';
        $fieldHTML.=$formsHelp->input(array('name'=>'wysija['.$model.']['.$key.'_linkname]', 'size'=>'75'),$value).'</p>';
        $fieldHTML.='<p style="margin-bottom:0px;">'.__('Subscribers can choose from these lists :',WYSIJA).'</p>';
        $modelList=&WYSIJA::get('list','model');
        $lists=$modelList->get(array('name','list_id','is_public'),array('is_enabled'=>1));


        foreach($lists as $list){
            if($list['is_public']) $checked=true;
            else $checked=false;

            $fieldHTML.= '<p class="labelcheck"><label for="'.$key.'list-'.$list['list_id'].'">'.$formsHelp->checkbox( array('id'=>$key.'list-'.$list['list_id'],
                        'name'=>'wysija[config]['.$key.'_lists][]'),
                            $list['list_id'],$checked).$list['name'].'</label></p>';
        }
        $fieldHTML.='</div>';


        return $fieldHTML;
    }

    function fieldFormHTML_viewinbrowser($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $checked=false;
        if($this->model->getValue($key))   $checked=true;
        $field='<p><label for="'.$key.'">';
        $field.=$formsHelp->checkbox(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']','class'=>'activateInput'),1,$checked);
        $field.='</label>';
        $value=$this->model->getValue($key.'_linkname');

        $field.=$formsHelp->input(array("id"=>$key.'_linkname','name'=>'wysija['.$model.']['.$key.'_linkname]', 'size'=>'75'),$value).'</p>';

        return $field;
    }

    function fieldFormHTML_cron($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $checked=false;
        if($this->model->getValue($key))   $checked=true;
        $field='<div><div class="cronleft"><label for="'.$key.'">';
        $field.=$formsHelp->checkbox(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']','class'=>'activateInput'),1,$checked);
        $field.='</label></div>';

        $urlcron=site_url( 'wp-cron.php').'?'.WYSIJA_CRON.'&action=wysija_cron&process=all';
        $field.='<div class="cronright" id="'.$key.'_linkname">';
        $field.='<p>'.'Almost done! Setup this cron job on your server or ask your host:'.'</p>';
        $field.='<p>Cron URL : <strong><a href="'.$urlcron.'" target="_blank">'.$urlcron.'</a></strong></p>';
        $field.='</div></div>';

        return $field;
    }

    function fieldFormHTML_debugnew($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $selected=$this->model->getValue($key);
        if(!$selected)   $selected=0;
        $field='<p><label for="'.$key.'">';
        $options=array(0=>'off',1=>'SQL queries',2=>'&nbsp+log',3=>'&nbsp&nbsp+safe PHP errors',4=>'&nbsp&nbsp&nbsp+safe PHP errors wp-admin',99=>'&nbsp&nbsp&nbsp&nbsp+PHP errors wp-admin(to use carefully)');
        $field.=$formsHelp->dropdown(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']'),$options,$selected);
        $field.='</label></p>';

        return $field;
    }

    function fieldFormHTML_dkim($key,$value,$model,$paramsex){

        $field='';
        $keypublickey=$key.'_pubk';

        if(!$this->model->getValue($keypublickey)){
            //refresh the public key private key generation
            $helpersLi=&WYSIJA::get('licence','helper');
            $helpersLi->dkim_config();
        }else{
            WYSIJA::update_option('dkim_autosetup',false);
            $formsHelp=&WYSIJA::get('forms','helper');


            $realkey=$key.'_active';
            $checked=false;
            if($this->model->getValue($realkey))   $checked=true;
            $field.='<p>';
            $field.=$formsHelp->checkbox(array('id'=>$realkey,'name'=>'wysija['.$model.']['.$realkey.']','style'=>'margin-left:0px;','class'=>'activateInput'),1,$checked);
            $field.='</p>';

            $field.='<div id="'.$realkey.'_linkname" >';
            //$titlelink=str_replace(array('[link]','[\link]'), array('<a href="">','</a>'),'');
            $titlelink= __('Configure your DNS by adding a key/value record in TXT as shown below.',WYSIJA).' <a href="http://support.wysija.com/knowledgebase/guide-to-dkim-in-wysija/?utm_source=wpadmin&utm_campaign=settings" target="_blank">'.__('Read more',WYSIJA).'</a>';
            $field.='<fieldset style=" border: 1px solid #ccc;margin: 0;padding: 10px;"><legend>'.$titlelink.'</legend>';

            $field.='<label id="drlab" for="domainrecord">'.__('Key',WYSIJA).' <input readonly="readonly" id="domainrecord" style="margin-right:10px;" type="text" value="wys._domainkey"/></label><label id="drpub" for="dkimpub">'.__('Value',WYSIJA).' <input readonly="readonly" id="dkimpub" type="text" size="70" value="v=DKIM1;k=rsa;g=*;s=email;h=sha1;t=s;p='.$this->model->getValue($keypublickey).'"/>';
            $field.='</fieldset>';
            $realkey=$key.'_domain';
            $field.='<p><label class="dkim" for="'.$realkey.'">'.__('Domain',WYSIJA).'</label>';

            $field.=$formsHelp->input(array('id'=>$realkey,'name'=>'wysija['.$model.']['.$realkey.']'),$this->model->getValue($realkey));
            $field.='</p>';

            $field.='</div>';
        }

        return $field;
    }

    function fieldFormHTML_debug($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');
        $checked=false;
        if($this->model->getValue($key))   $checked=true;
        $field='<p><label for="'.$key.'">';
        $field.=$formsHelp->checkbox(array('id'=>$key,'name'=>'wysija['.$model.']['.$key.']'),1,$checked);
        $field.='</label></p>';

        return $field;
    }

    function fieldFormHTML_capabilities($key,$value,$model,$paramsex){
        /*second part concerning the checkbox*/
        $formsHelp=&WYSIJA::get('forms','helper');

        $field='<table width="400" cellspacing="0" cellpadding="3" bordercolor="#FFFFFF" border="0" style="background-color:#FFFFFF" class="fixed">
    <thead>
        <tr>
<th class="rolestitle" style="width:200px">'.__('Roles and permissions',WYSIJA).'</th>';

        $wptools=&WYSIJA::get('wp_tools','helper');
        $editable_roles=$wptools->wp_get_roles();


        foreach($editable_roles as $role){
            $field.='<th class="rolestable" >'.$role['name'].'</th>';
        }

	$field.='</tr></thead><tbody>';

        $alternate=true;
        foreach($this->model->capabilities as $keycap=>$capability){
            $classAlternate='';
            if($alternate) $classAlternate=' class="alternate" ';
            $field.='<tr'.$classAlternate.'><td class="title"><p class="description">'.$capability['label'].'</p></td>';

                    foreach($editable_roles as $role){
                        $checked=false;
                        $keycheck='rolescap---'.$role['key'].'---'.$keycap;

                        //if($this->model->getValue($keycheck))   $checked=true;
                        $checkboxparams=array('id'=>$keycheck,'name'=>'wysija['.$model.']['.$keycheck.']');
                        if(in_array($role['key'], array('administrator','super_admin'))){
                            $checkboxparams['disabled']='disabled';
                        }

                        $roling = get_role( $role['key'] );

                        // add "organize_gallery" to this role object
                        if($roling->has_cap( 'wysija_'.$keycap )){
                            $checked=true;
                        }

                        $field.='<td class="rolestable" >'.$formsHelp->checkbox($checkboxparams,1,$checked).'</td>';
                    }

            $field.='</tr>';
            $alternate=!$alternate;
        }

        $field.='</tbody></table>';

        return $field;
    }



    function fieldFormHTML_email_notifications($key,$value,$model,$paramsex){
        /* first part concerning the field itself */
        $params=array();
        $params['type']='default';
        $field=$this->fieldHTML($key,$value,$model,$params);

        /*second part concerning the checkbox*/
        $threecheck=array(
            '_when_sub' =>__('When someone subscribes',WYSIJA)
            ,'_when_unsub'=>__('When someone unsubscribes',WYSIJA),
            '_when_dailysummary'=>__('Daily summary of emails sent',WYSIJA)
            //,"_when_bounce"=>__('When an email bounces',WYSIJA)
            );
        $formsHelp=&WYSIJA::get('forms','helper');
        foreach($threecheck as $keycheck => $checkobj){
            $checked=false;
            if($this->model->getValue($key.$keycheck))$checked=true;
            $field.='<p><label for="'.$key.$keycheck.'">';
            $field.=$formsHelp->checkbox(array("id"=>$key.$keycheck,'name'=>'wysija['.$model.']['.$key.$keycheck.']'),1,$checked);
            $field.=$checkobj.'</label></p>';
        }

        return $field;
    }


    function fieldFormHTML_selfsigned($key,$value,$model,$params){

        $formsHelp=&WYSIJA::get('forms','helper');

        $realvalue=$this->model->getValue($key);

        $value=0;
        $checked=false;
        if($value ==$realvalue) $checked=true;
        $id=str_replace('_','-',$key).'-'.$value;
        $field='<label for="'.$id.'">';
        $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija['.$model.']['.$key.']'),$value,$checked);
        $field.=__('No',WYSIJA).'</label>';

        $value=1;
        $checked=false;
        if($value ==$realvalue) $checked=true;
        $id=str_replace('_','-',$key).'-'.$value;
        $field.='<label for="'.$id.'">';
        $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija['.$model.']['.$key.']'),$value,$checked);
        $field.=__('Yes',WYSIJA).'</label>';

        return $field;
    }

    function tabs($current = 'basics') {
        $tabs = array(
            'basics' => __('Basics', WYSIJA),
            'subforms' => __('Subscription Form', WYSIJA),
            'emailactiv' => __('Signup Confirmation', WYSIJA),
            'sendingmethod' => __('Send With...', WYSIJA),
            'advanced' => __('Advanced', WYSIJA),
            'premium' => __('Premium Upgrade', WYSIJA),
        );

        if(!WYSIJA::is_wysija_admin()) unset($tabs['subforms']);

        $tabs=apply_filters('wysija_extend_settings', $tabs);

        echo '<div id="icon-options-general" class="icon32"><br /></div>';
        echo '<h2 id="wysija-tabs" class="nav-tab-wrapper">';
        foreach($tabs as $tab => $name) {
            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
            $extra = ($tab === 'premium') ? ' tab-premium' : '';
            echo "<a class='nav-tab$class$extra' href='#$tab'>$name</a>";
        }
        echo '</h2>';
    }




    function main(){
        echo $this->messages();
        ?>
        <div id="wysija-config">
            <?php $this->tabs(); ?>
            <form name="wysija-settings" method="post" id="wysija-settings" action="" class="form-valid" autocomplete="off">
                <div id="basics" class="wysija-panel">
                    <?php $this->basics(); ?>
                    <p class="submit">
                    <input type="submit" value="<?php echo esc_attr(__('Save settings',WYSIJA)); ?>" class="button-primary wysija" />
                    </p>
                </div>
                <div id="subforms" class="wysija-panel">
                    <?php if(WYSIJA::is_wysija_admin()) $this->subforms(); ?>
                </div>
                <div id="emailactiv" class="wysija-panel">
                    <?php $this->emailactiv(); ?>
                    <p class="submit">
                    <input type="submit" value="<?php echo esc_attr(__('Save settings',WYSIJA)); ?>" class="button-primary wysija" />
                    </p>
                </div>
                <div id="sendingmethod" class="wysija-panel">
                    <?php $this->sendingmethod(); ?>
                    <p class="submit">
                    <input type="submit" value="<?php echo esc_attr(__('Save settings',WYSIJA)); ?>" class="button-primary wysija" />
                    </p>
                </div>

                <div id="advanced" class="wysija-panel">
                    <?php $this->advanced(); ?>
                    <p class="submit">
                    <input type="submit" value="<?php echo esc_attr(__('Save settings',WYSIJA)); ?>" class="button-primary wysija" />
                    </p>
                </div>

                <?php
                add_filter('wysija_extend_settings_content',array($this,'extend_settings_premium'),8,2);

                echo apply_filters('wysija_extend_settings_content','',array('viewObj'=>&$this));
                ?>

                <p class="submitee">
                    <?php $this->secure(array('action'=>"save")); ?>
                    <input type="hidden" value="save" name="action" />
                    <input type="hidden" value="" name="redirecttab" id="redirecttab" />
                </p>

            </form>
        </div>
        <?php
    }


    function basics(){
        $step=array();

        $step['company_address']=array(
            'type'=>'textarea',
            'label'=>__("Your company's address",WYSIJA),
            'desc'=>__("The address will be added to your newsletter's footer. This helps avoid spam filters.",WYSIJA),
            'rows'=>"3",
            'cols'=>"40",);

        $step['emails_notified']=array(
            'type'=>'email_notifications',
            'label'=>__('Email notifications',WYSIJA),
            'desc'=>__('Enter the email addresses that should receive notifications (separate by comma).',WYSIJA));

        $step['from_name']=array(
            'type'=>'fromname',
            'class'=>'validate[required]',
            'label'=>__('Sender of notifications',WYSIJA),
            'desc'=>__("Choose a FROM name and email address for notifications emails.",WYSIJA));

        /* TODO add for rooster
        $step['sharedata']=array(
            'type'=>'debug',
            'label'=>__('Share your usage information ',WYSIJA),
            'desc'=>__('Help us improve Wysija by sharing information on how you use the plugin and get chance to win a Premium licence. [link]Find out more.[/link]',WYSIJA),
            'link'=>'<a href="http://support.wysija.com/knowledgebase/sharing-your-usage-data/" target="_blank" title="'.__("Find out more.",WYSIJA).'">');
        */

        $modelC=&WYSIJA::get('config','model');

        ?>
        <table class="form-table">
            <tbody>
                <?php
                echo $this->buildMyForm($step,$modelC->values,'config');
                ?>
            </tbody>
        </table>
        <?php
    }

    function subforms(){
        $mUserField=&WYSIJA::get('user_field','model');
        $mUserField->orderBy('field_id');
        $customFields=$mUserField->getRows(false);
        ?>
        <a id="wj-create-new-form" class="button-secondary"><?php echo __('New form',WYSIJA);?></a>
        <?php
        $wysija_forms=json_decode(get_option('wysija_forms'),true);

        if(empty($wysija_forms)){
            $wysija_forms=array();
            $defaultForm=array(
                'id'=>'default-form',
                'name'=>__('Default form',WYSIJA),
                'blocks'=>array(
                    0=>array('fields'=> array(
                                0=>array('type'=>'email', 'params'=>array('label'=>__('Email',WYSIJA)))
                            )
                        ),
                    1=>array('fields'=> array(
                                0=>array('type'=>'submit', 'params'=>array('label'=>__('Subscribe!',WYSIJA)))
                            )
                        ),
                    )
                );
            $wysija_forms['default-form']=$defaultForm;
            WYSIJA::update_option('wysija_forms',json_encode($wysija_forms));
        }

        ?>
        <script type="text/javascript">var wysijaForms=<?php echo json_encode($wysija_forms); ?>;</script>
            <select id="list-forms" name="wysija[profiles][forms]">
                <option value=""><?php echo __('Edit a form...',WYSIJA) ?></option>
                <?php
                foreach($wysija_forms as $wj_form){
                    echo '<option value="'.$wj_form['id'].'">'.$wj_form['name'].'</option>';
                }
                ?>
            </select>

        <hr/>
        <div id="wj-forms-editor" class="clearfix">
            <div id="wj-form-edit-drag">
                <div id="wj-form-name">Edit <span id="wj-edit-form-name">
                        <span id="wj-form-name-label"></span>
                        <input type="text" id="wj-form-name-value" name="wysija[profiles][form][name]" value=""/>
                        <input type="hidden" id="wj-form-id-value" name="wysija[profiles][form][id]" value=""/>
                    </span>
                </div>
                <div id="wj-currentform"></div>
                <div id="general-part">
                    <div class="list-selection"><p><?php _e('Add subscribers to these lists:',WYSIJA) ?></p><?php
                    $fieldHTML= '';

                    $modelList=&WYSIJA::get('list','model');
                    $lists=$modelList->get(array('name','list_id'),array('is_enabled'=>1));
                    foreach($lists as $list){
                        $checked=false;
                        //if(in_array($list['list_id'], $valuefield)) $checked=true;
                        $formObj=&WYSIJA::get('forms','helper');
                        $fieldHTML.= '<p class="labelcheck listcheck"><label for="listid-'.$list['list_id'].'">'.$formObj->checkbox( array('id'=>'listid-'.$list['list_id'],
                                    'name'=>'wysija[profiles][form][lists][]', 'class'=>''),
                                        $list['list_id'],$checked).$list['name'].'</label></p>';
                        $fieldHTML.='<input type="hidden" name="wysija[profiles][form][list_name]['.$list['list_id'].']'.'" value="'.$list['name'].'" />';
                    }
                    echo $fieldHTML;

                    ?></div>
                    <p class="submit">
                    <a href="javascript:;" id="forms-save" class="button-primary wysija" ><?php echo esc_attr(__('Save',WYSIJA)); ?></a>
                    <a href="javascript:;" id="form-delete"><?php echo esc_attr(__('Delete',WYSIJA)); ?></a>
                    </p>
                </div>
            </div>
            <div id="wysija_toolbar">
                <ul class="wysija_toolbar_tabs">
                    <li class="wjt-content">
                        <a class="selected" href="javascript:;" rel="#wj_content"><?php _e('Content',WYSIJA)?></a>
                    </li>
                </ul>

                <!-- CONTENT BAR -->
                <ul id="wj_content" class="wj-tab-inner" >
                    <?php

                    foreach($customFields as $cfield){
                        echo '<li class="wj_element"><a class="wysija_item" id="'.$cfield['column_name'].'" wysija_type="text">'.$cfield['name'].'</a></li>';
                    }
                    $extraTypes=array(
                        'list-selection'=>array('label'=>__('List selection',WYSIJA),'type'=>'lists'),
                        'text-instructions'=>array('label'=>__('Random text or instructions',WYSIJA),'type'=>'instructions'),
                        'divider'=>array('label'=>__('Divider',WYSIJA),'type'=>'divider'));
                    foreach($extraTypes as $key=>$data){
                        echo '<li class="wj_element"><a class="wysija_item" id="'.$key.'" wysija_type="'.$data['label'].'">'.$data['label'].'</a></li>';
                    }

                    add_filter('wysija_premium_fields_soon',array($this,'premiumSoonFields'),1);
                    echo apply_filters('wysija_premium_fields_soon', '');
                    ?>
                </ul>

                <div id="wysija_notices" style="display:none;"><span id="wysija_notice_msg"></span><img alt="loader" style="display:none;" id="ajax-loading" src="<?php echo WYSIJA_URL ?>img/wpspin_light.gif" /></div>
            </div>
        </div>
        <?php
    }
    function premiumSoonFields(){
        $html='';
        $html.='<li class="wj_element notice">'.str_replace(array('[link]','[/link]'), array('<a href="javascript:;" class="premium-tab">','</a>'), __('Soon available in [link]Premium[/link]:', WYSIJA)).'</li>';
        $extraTypes=array(
                        'new-text'=>array('label'=>__('Text or number',WYSIJA),'type'=>'text'),
                        'new-textarea'=>array('label'=>__('Paragraph text',WYSIJA),'type'=>'textarea'),
                        'new-date'=>array('label'=>__('Date or birthday',WYSIJA),'type'=>'date'),
                        'new-radio'=>array('label'=>__('Radio buttons',WYSIJA),'type'=>'radio'),
                        'new-checkbox'=>array('label'=>__('Checkboxes',WYSIJA),'type'=>'checkbox'),
                        'new-dropdown'=>array('label'=>__('Dropdown list',WYSIJA),'type'=>'dropdown'),
                        'new-image'=>array('label'=>__('Image',WYSIJA),'type'=>'image'),
                        'new-file'=>array('label'=>__('File',WYSIJA),'type'=>'file'),
                        'new-country'=>array('label'=>__('Country, State or Province',WYSIJA),'type'=>'country'));
        foreach($extraTypes as $key=>$data){
            $html.='<li class="wj_element"><a class="wysija_item disabled" id="'.$key.'" wysija_type="'.$data['label'].'">'.$data['label'].'</a></li>';
        }
        return $html;
    }

    function emailactiv(){
        $step=array();
        $step['confirm_dbleoptin']=array(
            'type'=>'radio',
            'values'=>array(true=>__('Yes',WYSIJA),false=>__('No',WYSIJA)),
            'label'=>__('Enable activation email',WYSIJA),
            'desc'=>__('Prevent fake signups by sending activation emails to your subscribers.',WYSIJA).' <a href="http://support.wysija.com/knowledgebase/why-you-should-enforce-email-activation/?utm_source=wpadmin&utm_campaign=activation email" target="_blank">'.__("Learn more.",WYSIJA)."</a>");

        $step['confirm_email_title']=array(
            'type'=>'input',
            'label'=>__('Email subject',WYSIJA),
            'rowclass'=>'confirmemail');

        $step['confirm_email_body']=array(
            'type'=>'textarea',
            'label'=>__('Email content',WYSIJA),
            'rowclass'=>'confirmemail');


        $modelU=&WYSIJA::get('user','model');
        $modelU->getFormat=OBJECT;

        $objUser=$modelU->getOne(false,array('wpuser_id'=>WYSIJA::wp_get_userdata('ID')));
        $step['subscribed_title']=array(
            'type'=>'input',
            'label'=>__('Confirmation page title',WYSIJA),
            'desc'=>__('When subscribers click on the activation link, they are redirected to this [link]confirmation page[/link]',WYSIJA),
            'link'=>'<a href="'.$modelU->getConfirmLink($objUser,"subscribe",false,true).'&demo=1" target="_blank" title="'.__("Preview page",WYSIJA).'">',
            'rowclass'=>'confirmemail');
        $step['subscribed_subtitle']=array(
            'type'=>'input',
            'label'=>__('Confirmation page content',WYSIJA),
            'rowclass'=>'confirmemail');

        ?>

        <table class="form-table">
            <tbody>
                <?php
                echo $this->buildMyForm($step,'','config');

                ?>
            </tbody>
        </table>
        <?php
    }

    function sendingmethod(){
        $key='sending_method';
        $realvalue=$this->model->getValue($key);
        $formsHelp=&WYSIJA::get('forms','helper');
        ?>
        <table class="form-table">
            <tbody>

                <tr class="methods">
                    <th scope="row">
                        <?php
                            $checked=false;
                            $value='site';
                            $id=str_replace("_",'-',$key).'-'.$value;
                            if($value ==$realvalue) $checked=true;
                            $field='<label for="'.$id.'" class="clearfix">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.='<h3>'.__('Your own website',WYSIJA).'</h3></label>';
                            $field.='<p>'.__('The simplest solution for small lists. Your web host sets a daily email limit.',WYSIJA).'</p>';
                            echo $field;
                        ?>
                    </th>
                    <th scope="row">
                        <?php
                            $checked=false;
                            $value='gmail';
                            $id=str_replace("_",'-',$key).'-'.$value;
                            if($value ==$realvalue) $checked=true;
                            $field='<label for="'.$id.'" class="clearfix">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.='<h3>Gmail</h3></label>';
                            $field.='<p>'.__("Easy to setup. Limited to 500 emails a day. We recommend that you open a dedicated Gmail account for this purpose.",WYSIJA).'</p>';
                            echo $field;
                        ?>
                    </th>
                    <th scope="row">
                        <?php
                            $checked = false;
                            $value = 'smtp';
                            if($value === $realvalue) $checked = true;

                            $id = str_replace('_', '-', $key).'-'.$value;
                            $field ='<label for="'.$id.'" class="clearfix">';
                            $field.= $formsHelp->radio(array('id' => $id, 'name' => 'wysija[config]['.$key.']'), $value, $checked);
                            $field.= '<h3>'.__('SMTP',WYSIJA).'</h3></label>';
                            $field.='<p>'.__('Send with a professional SMTP provider, a great choice for big and small lists. We\'ve negotiated promotional offers with a few providers for you.',WYSIJA).' <a href="http://support.wysija.com/knowledgebase/send-with-smtp-when-using-a-professional-sending-provider/?utm_source=wpadmin&utm_campaign=sending method" target="_blank">'.__('Read more',WYSIJA).'</a>.</p>';
                            echo $field;
                        ?>
                    </th>

                    <td>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-site">
                    <th scope="row">
                        <?php
                            $field=__('Delivery method',WYSIJA);
                            $field.='<p class="description">'.__('Send yourself some test emails to confirm which method works with your server.',WYSIJA).'</p>';
                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php
                            $key="sending_emails_site_method";
                            $checked=false;
                            $realvalue=$this->model->getValue($key);
                            $value="phpmail";
                            if($value ==$realvalue) $checked=true;

                            $id=str_replace("_",'-',$key).'-'.$value;
                            $field='<p class="title"><label for="'.$id.'">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.='PHP Mail</label><a class="button-secondary" id="send-test-mail-phpmail">'.__('Send a test mail',WYSIJA).'</a></p>';
                            $field.='<p class="description">'.__('This email engine works on 95&#37; of servers',WYSIJA).'</p>';


                            $value="sendmail";
                            $checked=false;
                            if($value ==$realvalue) $checked=true;

                            $id=str_replace("_",'-',$key).'-'.$value;
                            $field.='<p class="title"><label for="'.$id.'">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.='Sendmail</label>
                                <a class="button-secondary" id="send-test-mail-sendmail">'.__('Send a test mail',WYSIJA).'</a></p>';
                            $field.='<p class="description">'.__('This method works on 5&#37; of servers',WYSIJA).'</p>';

                            $id=str_replace("_",'-',$key).'-'.$value."-path";
                            $field.='<p class="title" id="p-'.$id.'"><label for="'.$id.'">';
                            $field.=__("Sendmail path",WYSIJA).'</label>'.$formsHelp->input(array("id"=>$id,'name'=>'wysija[config][sendmail_path]'),$this->model->getValue("sendmail_path")).'</p>';

                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp">
                    <th scope="row">
                        <?php
                            $key="smtp_host";
                            $id=str_replace("_",'-',$key);
                            $field='<label for="'.$id.'">'.__('SMTP Hostname',WYSIJA)."</label>";
                            $field.="<p class='description'>e.g.:smtp.mydomain.com</p>";
                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php
                            $value=$this->model->getValue($key);
                            $field=$formsHelp->input(array("id"=>$id,'name'=>'wysija[config]['.$key.']','size'=>'40'),$value,$checked);
                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp choice-sending-method-gmail">
                    <th scope="row">
                        <?php
                            $key="smtp_login";
                            $id=str_replace("_",'-',$key);
                            $field='<label for="'.$id.'">'.__('Login',WYSIJA)."</label>";

                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php
                            $value=$this->model->getValue($key);
                            $field=$formsHelp->input(array("id"=>$id,'name'=>'wysija[config]['.$key.']','size'=>'40'),$value,$checked);
                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp choice-sending-method-gmail">
                    <th scope="row">
                        <?php
                            $key="smtp_password";
                            $id=str_replace("_",'-',$key);
                            $field='<label for="'.$id.'">'.__('Password',WYSIJA)."</label>";
                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php
                            $value=$this->model->getValue($key);
                            $field=$formsHelp->input(array("type"=>"password","id"=>$id,'name'=>'wysija[config]['.$key.']','size'=>'40'),$value,$checked);
                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp">
                    <th scope="row">
                        <?php
                            $key="smtp_port";
                            $id=str_replace("_",'-',$key);
                            $field='<label for="'.$id.'">'.__('SMTP port',WYSIJA)."</label>";

                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php
                            $value=$this->model->getValue($key);
                            $field=$formsHelp->input(array("id"=>$id,'name'=>'wysija[config]['.$key.']','size'=>'40'),$value,$checked);
                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp">
                    <th scope="row">
                        <?php
                            $key="smtp_secure";
                            $id=str_replace("_",'-',$key);
                            $field='<label for="'.$id.'">'.__('Secure connection',WYSIJA)."</label>";
                            echo $field;
                        ?>
                    </th>
                    <td colspan="2">
                        <?php

                            $value=$this->model->getValue($key);

                            $field=$formsHelp->dropdown(array("name"=>'wysija[config]['.$key.']',"id"=>$id),array(false=>__("No"),"ssl"=>"SSL","tls"=>"TLS"),$value);
                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp">
                    <th scope="row">
                        <?php
                            $field=__('Authentication',WYSIJA);
                            echo $field.'<p class="description">'.__("Leave this option to Yes. Only a tiny portion of SMTP services ask Authentication to be turned off.",WYSIJA).'</p>';
                        ?>
                    </th>
                    <td colspan="2">
                        <?php

                            $key="smtp_auth";
                            $realvalue=$this->model->getValue($key);

                            $value=false;
                            $checked=false;
                            if($value ==$realvalue) $checked=true;
                            $id=str_replace("_",'-',$key).'-'.$value;
                            $field='<label for="'.$id.'">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.=__('No',WYSIJA).'</label>';

                            $value=true;
                            $checked=false;
                            if($value ==$realvalue) $checked=true;
                            $id=str_replace("_",'-',$key).'-'.$value;
                            $field.='<label for="'.$id.'">';
                            $field.=$formsHelp->radio(array("id"=>$id,'name'=>'wysija[config]['.$key.']'),$value,$checked);
                            $field.=__('Yes',WYSIJA).'</label>';



                            /*$key2=$key."_login";
                            $value=$this->model->getValue($key2);
                            $id=str_replace("_",'-',$key2).'-'.$value;
                            $field.="<p>".$formsHelp->input(array("default"=>__("Username",WYSIJA),"id"=>$id,'name'=>'wysija[config]['.$key2.']','size'=>'40'),$value,$checked)."</p>";

                            $key2=$key."_pass";
                            $value=$this->model->getValue($key2);
                            $id=str_replace("_",'-',$key2).'-'.$value;
                            $field.="<p>".$formsHelp->input(array("default"=>__("Password",WYSIJA),"id"=>$id,'name'=>'wysija[config]['.$key2.']','size'=>'40'),$value,$checked)."</p>";*/

                            echo $field;
                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp choice-sending-method-gmail">
                    <th scope="row">
                        <a class="button-secondary" id="send-test-mail-smtp"><?php _e("Send a test mail",WYSIJA)?></a>
                    </th>
                    <td colspan="2">
                        <?php

                        ?>
                    </td>
                </tr>

                <tr class="hidechoice choice-sending-method-smtp choice-sending-method-site choice-sending-method-gmail">
                    <th scope="row">
                        <?php
                            $field=__('Send...',WYSIJA);

                            echo $field.'<p class="description">'.str_replace(array('[link]','[/link]'),array('<a href="http://support.wysija.com/knowledgebase/wp-cron-batch-emails-sending-frequency/" target="_blank">','</a>'),__('Your web host has limits. We suggest 70 emails per hour to be safe. [link]Find out more[/link].',WYSIJA)).'</p>';
                        ?>
                    </th>
                    <td colspan="2">

                        <?php
                            $name='sending_emails_number';
                            $id=str_replace('_','-',$name);
                            $value=$this->model->getValue($name);
                            $params=array("id"=>$id,'name'=>'wysija[config]['.$name.']','size'=>'6');
                            //if($this->model->getValue("smtp_host")=="smtp.gmail.com") $params["readonly"]="readonly";
                            $field=$formsHelp->input($params,$value);
                            $field.= '&nbsp;'._x('emails', 'settings sending method send frequency', WYSIJA).'&nbsp;';


                            $name='sending_emails_each';
                            $id=str_replace('_','-',$name);
                            $value=$this->model->getValue($name);
                            $field.=$formsHelp->dropdown(array('name'=>'wysija[config]['.$name.']','id'=>$id),$formsHelp->eachValues,$value);
                            $field.='<span class="choice-under15"><b>'.__('This is fast!',WYSIJA).'</b> '.str_replace(array('[link]','[/link]'),array('<a href="http://support.wysija.com/knowledgebase/wp-cron-batch-emails-sending-frequency/?utm_source=wpadmin&utm_campaign=cron" target="_blank">','</a>'),__('We suggest you setup a cron job. [link]Read more[/link] on support.wysija.com',WYSIJA)).'</span>';
                            echo $field;


                        ?>
                    </td>
                </tr>

            </tbody>
        </table>
        <?php
    }

    function extend_settings_premium($resultHTML){

        $resultHTML='<div id="premium" class="wysija-panel">';
        $resultHTML.=$this->premium();
        $resultHTML.='</div>';
        return $resultHTML;
    }

    function log(){
        dbg(get_option('wysija_log'),0);
    }

    function advanced(){

        $step=array();

        $step['role_campaign']=array(
            'type'=>'capabilities',
            '1col'=>1);

        $step['replyto_name']=array(
            'type'=>'fromname',
            'class'=>'validate[required]',
            'label'=>__('Reply-to name & email',WYSIJA),
            'desc'=>__('You can change the default reply-to name and email for your newsletters. This option is also used for the activation emails and Admin notifications (in Basics).',WYSIJA));

        $step['bounce_email']=array(
            'type'=>'input',
            'label'=>__('Bounce Email',WYSIJA),
            'desc'=>__('To which address should all the bounced emails go? Get the [link]Premium version[/link] to automatically handle these.',WYSIJA),
            'link'=>'<a class="premium-tab" href="javascript:;" title="'.__("Purchase the premium version.",WYSIJA).'">');

        $step=apply_filters('wysija_settings_advanced', $step);

        $modelU=&WYSIJA::get('user','model');
        $objUser=$modelU->getCurrentSubscriber();


        $step['commentform']=array(
            'type'=>'commentform',
            'label'=>__('Subscribe in comments',WYSIJA),
            'desc'=>__('Visitors who submit a comment on a post can click on a checkbox to subscribe.',WYSIJA),
            );

        $step['viewinbrowser']=array(
            'type'=>'viewinbrowser',
            'label'=>__('Link to browser version',WYSIJA),
            'desc'=>__('Displays at the top of your newsletters. Don\'t forget to include the link tag, ie: [link]The link[/link]',WYSIJA),
            );

        $step['unsubscribe_linkname']=array(
            'type'=>'input',
            'label'=>__('Text of "Unsubscribe" link',WYSIJA),
            'desc'=>__('This changes the label for the unsubscribe link in the footer of your newsletters.',WYSIJA));

        $step['unsubscribed_title']=array(
            'type'=>'input',
            'label'=>__('Unsubscribe page title',WYSIJA),
            'desc'=>__('This is the [link]confirmation page[/link] a user is directed to after clicking on the unsubscribe link at the bottom of a newsletter.',WYSIJA),
            'link'=>'<a href="'.$modelU->getConfirmLink($objUser,"unsubscribe",false,true).'&demo=1" target="_blank" title="'.__('Preview page',WYSIJA).'">');


        $step['unsubscribed_subtitle']=array(
            'type'=>'input',
            'label'=>__('Unsubscribe page content',WYSIJA));


        $step['manage_subscriptions']=array(
        'type'=>'managesubscribe',
        'label'=>__('Subscribers can edit their profile',WYSIJA),
        'desc'=>__('Add a link in the footer of all your newsletters so subscribers can edit their profile and lists. [link]See your own subscriber profile page.[/link]',WYSIJA),
        'link'=>'<a href="'.$modelU->getConfirmLink($objUser,'subscriptions',false,true).'" target="_blank" title="'.__('Preview page',WYSIJA).'">',);


        $step['advanced_charset']=array(
            'type'=>'dropdown_keyval',
            'values'=>array('UTF-8','UTF-7',
                'BIG5',
                "ISO-8859-1","ISO-8859-2","ISO-8859-3","ISO-8859-4","ISO-8859-5","ISO-8859-6","ISO-8859-7","ISO-8859-8","ISO-8859-9","ISO-8859-10","ISO-8859-13","ISO-8859-14","ISO-8859-15",
                'Windows-1251','Windows-1252'),
            'label'=>__('Charset',WYSIJA),
            'desc'=>__('Squares or weird characters are displayed in your emails? Select the encoding for your language.',WYSIJA));

        $step=apply_filters('wysija_settings_advancednext', $step);

        $step['cron_manual']=array(
            'type'=>'cron',
            'label'=>'Enable Wysija Cron\'s',
            'desc'=>'Delayed or inconsistent sending? Replace wp-cron with ours.');

        $step['debug_new']=array(
            'type'=>'debugnew',
            'label'=>__('Debug mode',WYSIJA),
            'desc'=>__('Enable this to show Wysija\'s errors. Our support might ask you to enable this if you seek their help.',WYSIJA));


        ?>
        <table class="form-table">
            <tbody>
                <?php
                echo $this->buildMyForm($step,"","config");

                    ?>
                    <tr><th scope="row">
                        <div class="label"><?php _e('Reinstall from scratch',WYSIJA)?>
                        <p class="description"><?php _e('Want to start all over again? This will wipe out Wysija and reinstall anew.',WYSIJA)?></p>
                        </div>
                    </th><td><p><a class="button" href="admin.php?page=wysija_config&action=reinstall"><?php _e('Reinstall now...',WYSIJA); ?></a></p></td></tr>


            </tbody>
        </table>
        <?php
    }

    function premium(){
       $helperLicence=&WYSIJA::get('licence','helper');
       $urlpremium='http://www.wysija.com/?wysijap=checkout&wysijashop-page=1&controller=orders&action=checkout&wysijadomain='.$helperLicence->getDomainInfo().'&nc=1&utm_source=wpadmin&utm_campaign=purchasebutton';

       $arrayPremiumBullets=array(
           'more2000'=>array(
               'title'=>__('Send to more than 2000 subscribers.',WYSIJA),
               'desc'=>__('You have no more limits. Send to 100 000 if you want.',WYSIJA)
               ),
           'linksstats'=>array(
               'title'=>__('Find out which links are clicked.',WYSIJA),
               'desc'=>__('This is the most important engagement metric. You\'ll get hooked.',WYSIJA)
               ),
           'advlinkstats'=>array(
               'title'=>__('Track clicked links for each subscriber.',WYSIJA),
               'desc'=>__('Find out who is really addicted to your newsletters.',WYSIJA)
               ),
           'trackga'=>array(
               'title'=>__('Track with Google Analytics.',WYSIJA),
               'desc'=>__('Find out what your subscribers do once on your site.',WYSIJA)
               ),
           'cron'=>array(
               'title'=>__('We activate a cron job for you.',WYSIJA),
               'desc'=>__('We make sure you\'re sending every 15 minutes to avoid unregular delivery.',WYSIJA)
               ),
           'bounces'=>array(
               'title'=>__('Let us handle your bounces.',WYSIJA),
               'desc'=>__('It\'s bad to send to invalid addresses. Wysija removes them for you. Your reputation stays clean.',WYSIJA)
               ),
           'themes'=>array(
               'title'=>__('Download more beautiful themes.',WYSIJA),
               'desc'=>__('We work with top notch designers. The latest and prettiest are exclusive. [link]View them on our site.[/link]',WYSIJA),
               'link'=>'http://www.wysija.com/newsletter-templates-wordpress/?utm_source=wpadmin&utm_campaign=premiumtab'
               ),
           'support'=>array(
               'title'=>__('Fast and efficient support.',WYSIJA),
               'desc'=>__('It\'s like a valet service from the engineers themselves: Ben, Jo and Kim.',WYSIJA)
               ),
           'dkim'=>array(
               'title'=>__('Increase your deliverability with DKIM.',WYSIJA),
               'desc'=>__('Add this signature to your emails with Wysija. Spam filters can then authenticate your emails and your domain.',WYSIJA)
               ),
           'install'=>array(
               'title'=>__('Upgrade in a few clicks.',WYSIJA),
               'desc'=>__('You don\'t need to reinstall. We\'ll simply activate your site and you\'ll download a small plugin.',WYSIJA)
               ),
           'happy'=>array(
               'title'=>__('Join our happy users.',WYSIJA),
               'desc'=>__('Wysija is getting better every day thanks to users like you. <br />Read [link]what they are saying[/link].',WYSIJA),
               'link'=>'http://wordpress.org/support/view/plugin-reviews/wysija-newsletters'
               ),
           'trynow'=>array(
               'title'=>__('Try it now. Not happy? Get your money back.',WYSIJA),
               'desc'=>__('30-Day money back guarantee. Good reason to try us out.',WYSIJA)
               ),
       );

       $htmlContent='<div id="premium-content"><h2>'.__('12 Cool Reasons to Upgrade to Premium',WYSIJA).'</h2><div class="bulletium">';

        foreach($arrayPremiumBullets as $key => $bullet){
            $htmlContent.='<div id="'.$key.'" class="bullet-hold clearfix"><div class="feat-thumb"></div><div class="description"><h3>'.$bullet['title'].'</h3><p>';

            if(isset($bullet['link'])){
                $htmlContent.= str_replace(array('[link]','[/link]'),array('<a href="'.$bullet['link'].'" target="_blank">','</a>'),$bullet['desc']);
            }else   $htmlContent.= $bullet['desc'];

            $htmlContent.='</p></div></div>';
        }
        $htmlContent.='</div></div>';
        $htmlContent.='<p class="wysija-premium-wrapper">
            <a class="wysija-premium-btns wysija-premium" href="'.$urlpremium.'" target="_blank">'.__('Upgrade for $99 a year for 1 site.',WYSIJA).'<img src="'.WYSIJA_URL.'img/wpspin_light.gif" alt="loader"/></a></p>';
        $htmlContent.='<br><p>'.__('Already paid?', WYSIJA).' <a id="premium-activate" type="submit" class="wysija" href="javascript:;" />'. esc_attr(__('Activate your Premium licence.',WYSIJA)).'</a></p>';

        $htmlContent.='<p>'.str_replace(array('[link]','[/link]'),array('<a href="http://www.wysija.com/contact/?utm_source=wpadmin&utm_campaign=premiumtab" target="_blank">','</a>'),__('Got a sales question? [link]Get in touch[/link] with Kim, Jo, Adrien and Ben.',WYSIJA)).'</p>';
        $htmlContent.='<p>'.str_replace(array('[link]','[/link]'),array('<a href="http://support.wysija.com/terms-conditions/?utm_source=wpadmin&utm_campaign=premiumtab" target="_blank">','</a>'),__('Read our simple and easy [link]terms and conditions.[/link]',WYSIJA)).'</p>';

        return $htmlContent;
    }

}
