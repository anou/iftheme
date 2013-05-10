<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_view_front_confirm extends WYSIJA_view_front {

    function WYSIJA_view_front_confirm(){
        $this->model=&WYSIJA::get('user','model');
    }

    /**
     * In that view we put all the content in a string because if we don't we won't be able to return it in the right place of the page
     * ob_start can't be used because of the other plugins possible conflicts
     * @param type $data
     * @return string
     */
    function subscriptions($data){
        $this->addScripts(false);
        $content=$this->messages();
        $formObj=&WYSIJA::get('forms','helper');

        $content.='<form id="wysija-subscriptions" method="post" action="#wysija-subscriptions" class="form-valid">';

        $content.='<table class="form-table">
                <tbody>';
        //user details */

        //do not show the email input if the subscriber is a wordPress user
        $configm=&WYSIJA::get('config','model');
        $synchwp=$configm->getValue('importwp_list_id');
        $iswpsynched=false;
        foreach($data['user']['lists'] as $listdt){
            if($listdt['list_id']==$synchwp) $iswpsynched=true;
        }
        if(!$iswpsynched){
            $content.='<tr>
                <th scope="row">
                    <label for="email">'.__('Email',WYSIJA).'</label>
                </th>
                <td>
                    <input type="text" size="40" class="validate[required,custom[email]]" id="email" value="'.esc_attr($data['user']['details']['email']).'" name="wysija[user][email]" />
                </td>
            </tr>';
        }


        $content.='<tr>
            <th scope="row">
                <label for="fname">'.__('First name',WYSIJA).'</label>
            </th>
            <td>
                <input type="text" size="40" id="fname" value="'.esc_attr($data['user']['details']['firstname']).'" name="wysija[user][firstname]" />
            </td>
        </tr>';

        $content.='<tr>
            <th scope="row">
                <label for="lname">'.__('Last name',WYSIJA).'</label>
            </th>
            <td>
                <input type="text" size="40" id="lname" value="'.esc_attr($data['user']['details']['lastname']).'" name="wysija[user][lastname]" />
            </td>
        </tr>';

        $content.='<tr>
            <th scope="row">
                <label for="status">'.__('Status',WYSIJA).'</label>
            </th>
            <td>
                '.$formObj->radios(
        array('id'=>'status', 'name'=>'wysija[user][status]'),
        array('-1'=>' '.__('Unsubscribed',WYSIJA).' ','1'=>' '.__('Subscribed',WYSIJA).' '),
        $data['user']['details']['status'],
        ' class="validate[required]" ').'
            </td>
        </tr>';


        //list subscriptions */
        if($data['list']){
            $content.='<tr></tr><tr>
                <th scope="row" colspan="2">';

            $content.='<h3>'.__('Your lists',WYSIJA).'</h3>';
            $field="lists-";

            $content.='</th>';


            $fieldHTML= '';
            $field='list';
            $valuefield=array();
            foreach($data['user']['lists'] as $list){
                $valuefield[$list['list_id']]=$list;
            }



            $fieldHTML= '';
            $field='list';
            $valuefield=array();
            if($data['user']){
                foreach($data['user']['lists'] as $list){
                    $valuefield[$list['list_id']]=$list;
                }
            }

            $formObj=&WYSIJA::get('forms','helper');
            foreach($data['list'] as $list){
                $checked=false;
                $extratext=$extraCheckbox=$hiddenField='';

                if(isset($valuefield[$list['list_id']])) {
                    //if the subscriber has this list and is not unsubed then we check the checkbox
                    if($valuefield[$list['list_id']]['unsub_date']<=0){
                        $checked=true;
                    }else{
                        //we keep a reference of the list to which we are unsubscribed
                        $hiddenField=$formObj->hidden(array('id'=>$field.$list['list_id'],'name'=>"wysija[user_list][unsub_list][]", 'class'=>'checkboxx'),$list['list_id']);
                        $hiddenField.=' / <span class="wysija-unsubscribed-on">'.sprintf(__('Unsubscribed on %1$s',WYSIJA),  date_i18n(get_option('date_format'), $valuefield[$list['list_id']]['unsub_date'])).'</span>';
                    }
                }
                $labelHTML= '<label for="'.$field.$list['list_id'].'">'.$list['name'].'</label>';
                $fieldHTML=$formObj->checkbox( array('id'=>$field.$list['list_id'],'name'=>"wysija[user_list][list_id][]", 'class'=>'checkboxx'),$list['list_id'],$checked,$extraCheckbox).$labelHTML;
                $fieldHTML.=$hiddenField;
                $content.= '<tr><td colspan="2">'. $fieldHTML.'</td></tr>';
            }

        }

        $content.='</tbody></table>';
        $content.='<p class="submit">
                        '.$this->secure(array('controller'=>"confirm",'action'=>"save", 'id'=> $data['user']['details']['user_id']),false,false).'
                        <input type="hidden" name="wysija[user][user_id]" id="user_id" value="'.esc_attr($data['user']['details']['user_id']).'" />
                       <input type="hidden" name="id" id="user_id2" value="'.esc_attr($data['user']['details']['user_id']).'" />
                        <input type="hidden" value="save" name="action" />
                        <input type="submit" value="'.esc_attr(__('Save',WYSIJA)).'" class="button-primary wysija">
                    </p>';
        $content.='</form>';
        return $content;
    }

    /**
     *
     */
    function resend(){
        $content.='<form id="wysija-subscriptions" method="post" action="#wysija-subscriptions" class="form-valid">';
        $content.='
                        '.$this->secure(array('controller'=>'confirm','action'=>'resendconfirm', 'id'=> (int)$_REQUEST['user_id']),false,false).'
                        <input type="hidden" name="id" id="id" value="'.(int)$_REQUEST['user_id'].'" />
                            <input type="hidden" name="email_id" id="id" value="'.(int)$_REQUEST['email_id'].'" />
                        <input type="hidden" value="resendconfirm" name="action" />
                        <h3>'.__('We cannot safely say that you clicked that linked.',WYSIJA).'</h3>
                            <p class="submit">
                        <input type="submit" value="'.esc_attr(__('Resend the email with safe links!',WYSIJA)).'" class="button-primary wysija">
                    </p>';
        $content.='</form>';
        return $content;
    }
}
