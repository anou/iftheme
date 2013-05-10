<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_view_back_subscribers extends WYSIJA_view_back{


    var $icon="icon-users";
    var $column_action_list="email";

    function WYSIJA_view_back_subscribers(){
        $this->title=__("Lists and Subscribers",WYSIJA);
        $this->WYSIJA_view_back();

        $this->search=array("title"=>__("Search subscribers",WYSIJA));
        $this->column_actions=array('editlist'=>__('Edit',WYSIJA),'duplicatelist'=>__('Duplicate',WYSIJA),'deletelist'=>__('Delete',WYSIJA));
    }

    function main($data){
        echo '<form method="post" action="" id="posts-filter">';
        $this->filtersLink($data);
        $this->filterDDP($data);
        $this->listing($data);
        $this->limitPerPage();
        echo '</form>';
    }

    function menuTop($case=false){
        if(!$case) $case=$this->menuTop;
        $arrayTrans=array("backtolist"=>__("Back to lists",WYSIJA),"back"=>__("Back",WYSIJA),"add"=>__('Add Subscriber',WYSIJA),"addlist"=>__('Add List',WYSIJA),"lists"=>__('Edit Lists',WYSIJA),"import"=>__('Import',WYSIJA),"export"=>__('Export',WYSIJA));
        switch($case){
            case "add":
            case "edit":
            case "export":
            case "import":
            case "addlist":
            case "editlist":
            default:
               $arrayMenus=array("add","addlist","lists","import","export");
        }
        $html="";
        foreach($arrayMenus as $action){
            $html.= '<a href="admin.php?page=wysija_subscribers&action='.$action.'" class="button-secondary2">'.$arrayTrans[$action].'</a>';
        }
      return $html;
    }


    function filterDDP($data){

        ?>
        <ul class="subsubsub">
            <?php
            $total=count($data['counts']);
            $i=1;
            foreach($data['counts'] as $countType =>$count){
                if(!$count) {$i++;continue;}
                switch($countType){
                    case "all":
                        $tradText=__('All',WYSIJA);
                        break;
                    case "unconfirmed":
                        $tradText=__('Unconfirmed',WYSIJA);
                        break;
                    case "unsubscribed":
                        $tradText=__('Unsubscribed',WYSIJA);
                        break;
                    case "subscribed":
                        $tradText=__('Subscribed',WYSIJA);
                        break;
                    case "unlisted":
                        $tradText=__('Unlisted',WYSIJA);
                        break;
                }
                $classcurrent='';
                if((isset($_REQUEST['link_filter']) && $_REQUEST['link_filter']==$countType) || ($countType=='all' && !isset($_REQUEST['link_filter']))) $classcurrent='class="current"';
                echo '<li><a '.$classcurrent.' href="admin.php?page=wysija_subscribers&link_filter='.$countType.'">'.$tradText.' <span class="count">('.$count.')</span></a>';

                if($total!=$i) echo ' | ';
                echo '</li>';
                $i++;
            }

            ?>
        </ul>

        <?php $this->searchBox();

        ?>

        <div class="tablenav">
            <div class="alignleft actions">
                <select name="action2" class="global-action">
                    <option selected="selected" value=""><?php _e('Bulk Actions', WYSIJA); ?></option>
                    <?php
                    foreach($data['lists'] as $listK => $list){
                        if(!(isset($_REQUEST['filter-list']) && $_REQUEST['filter-list']== $listK) && $list['is_enabled']){
                            ?><option value="actionvar_copytolist-listid_<?php echo $listK ?>"><?php
                            echo sprintf(__('Add to list %1$s', WYSIJA),$list['name']);

                            if(isset($list['users']))  echo ' ('.$list['users'].')';
                            ?></option><?php
                        }
                    }
                    ?>
                    <option value="exportlist"><?php _e('Export', WYSIJA); ?></option>
                    <?php
                    /*$config=&WYSIJA::get("config","model");
                    if($config->getValue("confirm_dbleoptin")){
                        ?>
                        <option value="sendconfirmation"><?php _e('Resend the activation email', WYSIJA); ?></option>
                        <?php
                    }*/
                    ?>
                    <option value="deleteusers"><?php _e('Delete subscribers', WYSIJA); ?></option>
                </select>
                <input type="submit" class="bulksubmit button-secondary action" name="doaction" value="<?php echo esc_attr(__('Apply', WYSIJA)); ?>">
                <?php $this->secure('delete'); ?>
            </div>

            <div class="alignleft actions">
                <select name="filter-list" class="global-filter">
                    <option selected="selected" value=""><?php _e('View by lists', WYSIJA); ?></option>
                    <?php

                    foreach($data['lists'] as $listK => $list){
                        $selected="";
                        if(isset($_REQUEST['filter-list']) && $_REQUEST['filter-list']== $listK) $selected=' selected="selected" ';
                        if(isset($list['users']))  echo '<option '.$selected.' value="'.$list['list_id'].'">'.$list['name'].' ('.$list['users'].')'.'</option>';
                        else echo '<option '.$selected.' value="'.$list['list_id'].'">'.$list['name'].'</option>';
                    }
                    ?>
                    <?php
                        $orphaned_selected = '';
                        if(isset($_REQUEST['filter-list']) && $_REQUEST['filter-list'] === 'orphaned') {
                            $orphaned_selected =' selected="selected" ';
                        }
                    ?>
                    <option <?php echo $orphaned_selected; ?> value="orphaned"><?php _e('Subscribers in no list', WYSIJA); ?></option>
                </select>
                <input type="submit" class="filtersubmit button-secondary action" name="doaction" value="<?php echo esc_attr(__('Filter', WYSIJA)); ?>">
            </div>

            <?php $this->pagination(); ?>

            <div class="clear"></div>
        </div>
        <?php
    }



    /*
     * main view
     */
    function listing($data){

        ?>
        <div class="list">
            <table cellspacing="0" class="widefat fixed">
                    <thead>
                        <?php
                            $statussorting=$fnamesorting=$lnamesorting=$usrsorting=$datesorting=' sortable desc';
                            $hiddenOrder='';
                            if(isset($_REQUEST['orderby'])){
                                switch($_REQUEST['orderby']){
                                    case 'email':
                                        $usrsorting=' sorted '.$_REQUEST['ordert'];
                                        break;
                                    case 'created_at':
                                        $datesorting=' sorted '.$_REQUEST['ordert'];
                                        break;
                                    case 'status':
                                        $statussorting=' sorted '.$_REQUEST['ordert'];
                                        break;
                                    /*case "firstname":
                                        $fnamesorting=" sorted ".$_REQUEST["ordert"];
                                        break;
                                    case "lastname":
                                        $lnamesorting=" sorted ".$_REQUEST["ordert"];
                                        break;*/
                                }
                                $hiddenOrder='<input type="hidden" name="orderby" id="wysija-orderby" value="'.esc_attr($_REQUEST["orderby"]).'"/>';
                                $hiddenOrder.='<input type="hidden" name="ordert" id="wysija-ordert" value="'.esc_attr($_REQUEST["ordert"]).'"/>';
                            }
                            $header='<tr class="thead">
                            <th scope="col" id="user-id" class="manage-column column-user-id check-column"><input type="checkbox" /></th>
                            <th class="manage-column column-username'.$usrsorting.'" id="email" scope="col" style="width:140px;"><a href="#" class="orderlink" ><span>'.__('Email',WYSIJA).'</span><span class="sorting-indicator"></span></a></th>';
                            /*$header.='<th class="manage-column column-fname'.$fnamesorting.'" id="firstname" scope="col" style="width:80px;">'.__('First name',WYSIJA).'</th>
                            <th class="manage-column column-lname'.$lnamesorting.'" id="lastname" scope="col" style="width:80px;">'.__('Last name',WYSIJA).'</th>';*/

                            $header.='<th class="manage-column column-list-names" id="list-list" scope="col">'.__('Lists',WYSIJA).'</th>';
                            $header.='<th class="manage-column column-status'.$statussorting.'" id="status" scope="col" style="width:80px;"><a href="#" class="orderlink" ><span>'.__('Status',WYSIJA).'</span><span class="sorting-indicator"></span></a></th>';
                            /*$header.='<th class="manage-column column-emails" id="emails-list" scope="col">'.__('Emails',WYSIJA).'</th>
                            <th class="manage-column column-opened" id="opened-list" scope="col">'.__('Opened',WYSIJA).'</th>
                            <th class="manage-column column-clic" id="clic-list" scope="col">'.__('Clicked',WYSIJA).'</th>';*/
                            $header.='<th class="manage-column column-date'.$datesorting.'" id="created_at" scope="col"><a href="#" class="orderlink" ><span>'.__('Subscribed on',WYSIJA).'</span><span class="sorting-indicator"></span></a></th>
                        </tr>';
                            echo $header;
                        ?>
                    </thead>
                    <tfoot>
                        <?php
                        echo $header;
                        ?>
                    </tfoot>

                    <tbody class="list:<?php echo $this->model->table_name.' '.$this->model->table_name.'-list" id="wysija-'.$this->model->table_name.'"' ?>>

                            <?php
                            $listingRows='';
                            $alt=true;

                            $statuses=array('-1'=>__('Unsubscribed',WYSIJA),'0'=>__('Unconfirmed',WYSIJA),'1'=>__('Subscribed',WYSIJA));

                            $config=&WYSIJA::get('config','model');
                            if(!$config->getValue('confirm_dbleoptin'))  $statuses['0']=$statuses['1'];

                            //dbg($data,false);
                            foreach($data['subscribers'] as $row){
                                $classRow='';
                                if($alt) $classRow=' class="alternate" ';

                                ?>
                                <tr <?php echo $classRow ?> >

                                    <th scope="col" class="check-column" >
                                        <input type="checkbox" name="wysija[user][user_id][]" id="user_id_<?php echo $row["user_id"] ?>" value="<?php echo esc_attr($row["user_id"]) ?>" class="checkboxselec" />
                                    </th>
                                    <td class="username column-username">
                                        <?php
                                        echo get_avatar( $row["email"], 32 );
                                        echo "<strong>".$row["email"]."</strong>";
                                        echo "<p style='margin:0;'>".$row["firstname"]." ".$row["lastname"]."</p>";

                                        ?>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $row["user_id"] ?>&action=edit" class="submitedit"><?php _e('View stats or edit',WYSIJA)?></a>
                                            </span>
                                        </div>
                                    </td>
                                    <?php /*<td><?php echo $row["firstname"] ?></td>
                                    <td><?php  echo $row["lastname"] ?></td> */ ?>
                                    <td><?php

                                    if(isset($row['lists'])){
                                        echo $row['lists'];
                                        if(isset($row['unsub_lists'])) echo ' / ';
                                    }

                                    if(isset($row['unsub_lists'])) echo '<span class="wysija-unsubscribed-on" title="'.__('Lists to which the subscriber was subscribed.',WYSIJA).'">'.$row['unsub_lists'].'</span>';


                                    ?></td>
                                    <td><?php  echo $statuses[$row['status']]; ?></td>
                                    <?php /*<td><?php echo $row["emails"] ?></td>
                                    <td><?php echo $row["opened"] ?></td>
                                    <td><?php echo $row["clicked"] ?></td> */?>
                                    <td><?php echo $this->fieldListHTML_created_at($row['created_at']) ?></td>

                                </tr><?php
                                $alt=!$alt;
                            }

                        ?>

                    </tbody>
                </table>
            </div>

            <?php
            echo $hiddenOrder;
    }

    function export($data){
        /* make a list of fields to export */
        ?>
        <form name="submitexport" method="post" id="submitexport" action="" class="form-valid">
            <table class="form-table">
                <tbody>
                    <?php
                    if(!isset($data['subscribers'])){
                       /* set the filters necessary to export
                        * 1-export a list
                        * 2-export all
                        * 3-export confirmed
                        * 4-export
                        */

                        $formObj=&WYSIJA::get("forms","helper");

                        $config=&WYSIJA::get("config","model");


                        ?>
                        <tr>
                            <th><label for="filterlist"><?php _e("Pick a list",WYSIJA); ?></label></th>
                            <td>
                                <select name="wysija[export][filter][list]" id="filterlist">
                                    <option value=""><?php _e('All', WYSIJA); ?></option>
                                    <?php
                                    foreach($data['lists'] as $listK => $list){
                                        echo '<option value="'.esc_attr($list['list_id']).'">'.$list['name'].' ('.$list['totals'].')'.'</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <?php
                        if($config->getValue("confirm_dbleoptin")){
                            ?>
                            <tr>
                                <th><label for="confirmedcheck"><?php _e("Export confirmed subscribers only",WYSIJA); ?>
                                        <p class="description"><?php _e('Only export subscribers who have activated their subscription by email.',WYSIJA);?></p></label></th>
                                <td>
                                    <input type="checkbox" name="wysija[export][filter][confirmed]" checked="checked" value="1" id="confirmedcheck" />
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="listfields"><?php _e('List of fields to export',WYSIJA); ?></label>
                        </th>
                        <td>
                            <?php
                                $model=&WYSIJA::get("user_field","model");
                                $fields=$model->getFields();
                                //Subscribed/unconfirmed
                                $fields['status']=__('Subscribed/unconfirmed',WYSIJA);
                                $formHelper=&WYSIJA::get("forms","helper");
                                echo $formHelper->checkboxes(array('class'=>'validate[minCheckbox[1]] checkbox',"name"=>"wysija[export][fields][]","id"=>"wysijafields"),$fields,'email');
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="wysija[export][user_ids]" id="user_ids" value="<?php if(isset($data['subscribers']))   echo base64_encode(serialize($data['subscribers'])) ?>" />
                <input type="hidden" value="export_get" name="action" />
                <input type="submit" value="<?php echo esc_attr(__('Export',WYSIJA)) ?>" class="button-primary wysija">
            </p>
        </form>
    <?php
    }
    function add($data=false){

        if(!$data['user'] || isset($this->add)) {

            $this->buttonsave=__('Add Subscriber',WYSIJA);
        }

        $formid='wysija-'.$_REQUEST['action'];
        ?>
        <form name="<?php echo $formid ?>" method="post" id="<?php echo $formid ?>" action="" class="form-valid">

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email',WYSIJA); ?></label>
                        </th>
                        <td>
                            <input type="text" size="40" class="validate[required,custom[email]]" id="email" value="<?php if($data['user']) echo esc_attr($data['user']['details']['email']) ?>" name="wysija[user][email]" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="fname"><?php _e('First name',WYSIJA); ?></label>
                        </th>
                        <td>
                            <input type="text" size="40" id="fname" value="<?php if($data['user']) echo esc_attr($data['user']['details']['firstname']) ?>" name="wysija[user][firstname]" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="lname"><?php _e('Last name',WYSIJA); ?></label>
                        </th>
                        <td>
                            <input type="text" size="40" id="lname" value="<?php if($data['user']) echo esc_attr($data['user']['details']['lastname']) ?>" name="wysija[user][lastname]" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="user-status" ><?php _e('Status',WYSIJA); ?></label>
                        </th>
                        <td>
                            <?php
                            $formObj=&WYSIJA::get('forms','helper');
                            $userstatus=1;
                            $config=&WYSIJA::get('config','model');
                            if($config->getValue("confirm_dbleoptin")){
                                $statusddp=array('1'=>__('Subscribed',WYSIJA),'0'=>__('Unconfirmed',WYSIJA),'-1'=>__('Unsubscribed',WYSIJA));
                                if($data['user']) $userstatus=$data['user']['details']['status'];
                            }else{
                                $statusddp=array('1'=>__('Subscribed',WYSIJA),'-1'=>__('Unsubscribed',WYSIJA));
                                if($data['user']) {
                                    if((int)$data['user']['details']['status']==0){
                                        $userstatus=1;
                                    }else{
                                        $userstatus=$data['user']['details']['status'];
                                    }

                                }
                            }


                            echo "<p>".$formObj->radios(
                                    array('id'=>'user-status', 'name'=>'wysija[user][status]'),
                                    $statusddp,
                                    $userstatus,
                                    ' class="validate[required]" ')."</p>";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="lists" class="title"><?php _e('Lists',WYSIJA); ?></label>

                        </th>
                        <td>
                            <?php
                            $fieldHTML= '';
                            $field='list';
                            $valuefield=array();
                            if($data['user'] && isset($data['user']['lists'])){
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
                                        $hiddenField=$formObj->hidden(array('id'=>$field.$list['list_id'],'name'=>'wysija[user_list][unsub_list][]', 'class'=>'checkboxx'),$list['list_id']);
                                        $hiddenField.=' / <span class="wysija-unsubscribed-on"> '.  sprintf(__('Unsubscribed on %1$s',WYSIJA),  date('D, j M Y H:i:s', $valuefield[$list['list_id']]['unsub_date'])).'</span>';
                                    }
                                }


                                $fieldHTML.= '<p><label for="'.$field.$list['list_id'].'">';
                                $datacheck=array('id'=>$field.$list['list_id'],'name'=>"wysija[user_list][list_id][]", 'class'=>'');
                                if(!$list['is_enabled']) $datacheck['disabled']='disabled';
                                $fieldHTML.=$formObj->checkbox( $datacheck,$list['list_id'],$checked,$extraCheckbox).$list['name'];
                                $fieldHTML.=$hiddenField;
                                $fieldHTML.='</label></p>';

                            }


                            echo $fieldHTML;
                            ?>
                        </td>
                    </tr>

                </tbody>
            </table>
            <p class="submit">
                <?php $this->secure(array('action'=>"save", 'id'=> $data['user']['details']['user_id'])); ?>
                <input type="hidden" name="wysija[user][user_id]" id="user_id" value="<?php echo esc_attr($data['user']['details']['user_id']) ?>" />
                <input type="hidden" value="save" name="action" />
                <input type="submit" value="<?php echo esc_attr($this->buttonsave) ?>" class="button-primary wysija">
            </p>
        </form>
        <?php
    }

    function subscribers_stats($htmlContent,$data){
        $htmlContent='';

        if(count($data['charts']['stats'])>0 ){
            $htmlContent.= '<p>';
            $htmlContent.= str_replace(
                    array("[link]","[/link]"),
                    array('<a title="'.__('Get Premium now',WYSIJA).'" class="premium-tab" href="javascript:;">','</a>'),
                    __("Note: Find out what this subscribers opens and clicks with our [link]Premium version.[/link]",WYSIJA));
            $htmlContent.= '</p>';
        }
        return $htmlContent;
    }

    function edit($data){
        $formid='wysija-'.$_REQUEST['action'];
        add_filter('wysija_subscribers_stats',array($this,'subscribers_stats'),1,2);
        echo apply_filters('wysija_subscribers_stats', '',$data);

        //loop to show the core lists to which the user is subscribed to
        foreach($data['list'] as $keyl => $list){
            if(!$list['is_enabled']){
                //make sure this lists is in the user lists
                foreach($data['user']['lists'] as $ulist){
                    if($list['list_id']==$ulist['list_id']){
                        continue(2);
                    }
                }
                unset($data['list'][$keyl]);
            }
        }

        $this->buttonsave=__('Save',WYSIJA);
        $this->add($data);
    }


    function globalActionsLists($data=false){
         ?>
        <div class="tablenav">

            <?php $this->pagination("&action=lists"); ?>
            <div class="clear"></div>
        </div>
        <?php

    }

    /*
     * main view when editing lists it has one listing and one form
     */
    function lists($data){
        echo '<form method="post" action="" id="posts-filter">';
        $this->globalActionsLists($data);
        ?>
        <div class="list">
            <table cellspacing="0" class="widefat fixed" >
                    <thead>
                        <tr class="thead">
                            <th class="manage-column column-name" id="name-list" scope="col" style="width:140px;"><?php _e('Name',WYSIJA) ?></th>
                            <th class="manage-column column-subscribed" id="subscribed-list" scope="col"><?php _e('Subscribed',WYSIJA) ?></th>
                            <?php
                            $config=&WYSIJA::get("config","model");
                            if($config->getValue("confirm_dbleoptin")){
                                ?><th class="manage-column column-unsubscribed" id="unconfirmed-list" scope="col"><?php _e('Unconfirmed',WYSIJA) ?></th><?php
                            }
                            ?>

                            <th class="manage-column column-unsubscribed" id="unsubscribed-list" scope="col"><?php _e('Unsubscribed',WYSIJA) ?></th>
                            <?php /*<th class="manage-column column-campaigns" id="campaigns-list" scope="col"><?php _e('Newsletters sent',WYSIJA) ?></th> */?>
                            <th class="manage-column column-date" id="date-list" scope="col"><?php _e('Date created',WYSIJA) ?></th>
                        </tr>
                    </thead>

                    <tbody class="list:<?php echo $this->model->table_name.' '.$this->model->table_name.'-list" id="wysija-'.$this->model->table_name.'"' ?>>
                        <?php
                            $listingRows="";
                            $alt=true;
                            foreach($data['list'] as $row =>$columns){
                                $classRow="";
                                if($alt) $classRow=' class="alternate" ';

                                ?>
                                <tr <?php echo $classRow ?> >
                                    <td class="manage-column column-name"  scope="col">
                                    <strong><a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=editlist" class="submitedit"><?php
                                    echo $columns['name'];
                                    ?></a></strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=editlist" class="submitedit"><?php _e('Edit',WYSIJA)?></a> |
                                            </span>
                                            <span class="duplicate">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=duplicatelist" class="submitduplicate"><?php _e('Duplicate',WYSIJA)?></a>
                                            </span>
                                            <?php if($columns['namekey']!="users"): ?>
                                             |
                                             <span class="delete">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=deletelist&_wpnonce=<?php echo $this->secure(array("action"=>"deletelist","id"=>$columns['list_id']),true); ?>" class="submitdelete"><?php _e('Delete',WYSIJA)?></a>
                                            </span>
                                            <?php endif;
                                            if(!$columns['is_enabled']): ?>
                                             |
                                            <span class="synch">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=synchlist&_wpnonce=<?php echo $this->secure(array("action"=>"synchlist","id"=>$columns['list_id']),true); ?>" class="submitsynch"><?php _e('Update',WYSIJA)?></a>
                                            </span>
                                            <?php endif;
                                            global $current_user;

                                            if($columns['namekey']=='users' && !$columns['is_enabled'] && is_multisite() && is_super_admin( $current_user->ID )): ?>
                                             |
                                            <span class="synchtotal">
                                                <a href="admin.php?page=wysija_subscribers&id=<?php echo $columns['list_id'] ?>&action=synchlisttotal&_wpnonce=<?php echo $this->secure(array("action"=>"synchlisttotal","id"=>$columns['list_id']),true); ?>" class="submitsynch"><?php _e('Get all MS users',WYSIJA)?></a>
                                            </span>
                                            <?php endif; ?>
                                            <?php if(!$columns['is_enabled'] && $columns['namekey']!='users'): ?>
                                            |
                                            <?php endif; ?>
                                            <span class="view_subscribers">
                                                <a href="admin.php?page=wysija_subscribers&filter-list=<?php echo $columns['list_id'] ?>"><?php _e('View Subscribers',WYSIJA)?></a>
                                            </span>
                                        </div>

                                    </td>
                                    <td class="manage-column column-subscribed"  scope="col"><?php echo $columns['subscribers'] ?></td>
                                    <?php
                                    if($config->getValue("confirm_dbleoptin")){
                                        ?><td class="manage-column column-unconfirmed"  scope="col"><?php echo $columns['unconfirmed'] ?></td><?php
                                    }
                                    ?>

                                    <td class="manage-column column-unsubscribed"  scope="col"><?php echo $columns['unsubscribers'] ?></td>
                                    <?php /* <td class="manage-column column-campaigns"  scope="col"><?php echo $columns['campaigns_sent'] ?></td> */ ?>
                                    <td class="manage-column column-date"  scope="col"><?php echo $this->fieldListHTML_created_at($columns['created_at']) ?></td>
                                </tr>
                                <?php
                                $alt=!$alt;
                            }

                        ?>

                    </tbody>
                </table>
            </div>
            <?php
            echo '</form>';
    }
    function addList($data){
        $this->editList($data);

    }

    function editList($data){
        ?>
        <div class="form">

                <form class="form-valid" action="admin.php?page=wysija_subscribers&action=lists<?php if($data['form']['list_id']) echo "&id=".$data['form']['list_id'] ?>" id="wysija-edit" method="post" name="wysija-edit">

                    <input type="hidden" name="wysija[list][list_id]" id="list_id" value="<?php echo esc_attr($data['form']['list_id']) ?>">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="list-name"><?php _e('Name',WYSIJA); ?> </label>
                                </th>
                                <td>
                                    <input type="text" size="40" class="validate[required]" id="list-name" value="<?php echo esc_attr($data['form']['name']) ?>" name="wysija[list][name]" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="list-desc"><?php _e('Description',WYSIJA); ?> </label>
                                    <p class="description"><?php _e('For your own use and never shown to your subscribers.',WYSIJA); ?></p>
                                </th>
                                <td>
                                    <textarea type="text" cols="40" rows="3" id="list-desc" name="wysija[list][description]" /><?php echo $data['form']['description'] ?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                        if($_REQUEST['action']=="editlist"){
                            $buttonName=__('Update List',WYSIJA);
                        }else{
                            $buttonName=__('Add List',WYSIJA);
                        }

                    ?>
                    <p class="submit">
                        <?php $this->secure(array('action'=>"savelist", 'id'=> $data['form']['list_id'])); ?>
                        <input type="hidden" value="savelist" name="action" />
                        <input type="submit" value="<?php echo esc_attr($buttonName) ?>" class="button-primary wysija">
                    </p>
                </form>
            </div>
            <?php
    }


    function import($data){
        $helperNumbers=&WYSIJA::get('numbers','helper');
        $data = $helperNumbers->get_max_file_upload();
        $bytes=$data['maxmegas'];

        ?>
            <div class="form">
                <form class="form-valid" action="admin.php?page=wysija_subscribers&action=lists" id="wysija-edit" enctype="multipart/form-data" method="post" name="wysija-edit">
                    <table class="form-table">
                        <tbody>

                            <tr>
                                <th scope="row">
                                    <label for="redirect"><?php _e('How do you want to import?',WYSIJA); ?> </label>
                                </th>
                                <td>
                                    <p>
                                        <label for="copy-paste">
                                            <input type="radio" class="validate[required]" id="copy-paste" value="copy" name="wysija[import][type]" ><?php _e('Copy paste in a text box',WYSIJA); ?>
                                        </label>
                                        <label for="upload-file">
                                            <input type="radio" class="validate[required]" id="upload-file" value="upload" name="wysija[import][type]"><?php _e('Upload a file',WYSIJA); ?>
                                        </label>
                                        <?php
                                            $config=&WYSIJA::get('config',"model");
                                            $importPossible=$config->getValue("pluginsImportableEgg");
                                            $importedalready=$config->getValue("pluginsImportedEgg");
                                            if(is_array($importPossible)){
                                                foreach($importPossible as $tableName =>$pluginInfos){
                                                    if(is_array($importedalready) && in_array($tableName,$importedalready)) continue;

                                                    ?>
                                                    <label for="import-from-plugin-<?php echo $tableName; ?>">
                                                        <a class="button-secondary2" id="import-from-plugin-<?php echo $tableName; ?>" href="admin.php?page=wysija_subscribers&action=importplugins">
                                                        <?php echo sprintf(__('Import from %1$s',WYSIJA),'<strong>"'.$pluginInfos['name'].'"</strong>'); ?>
                                                        </a>
                                                    </label>
                                                    <?php

                                                }
                                            }
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <tr class="csvmode copy">
                                <th scope="row" >
                                    <label for="csvtext"><?php _e('Then paste your list here',WYSIJA); ?> </label>
                                    <p class="description"><?php echo str_replace(array("[link]","[/link]"),array('<a target="_blank" href="http://support.wysija.com/knowledgebase/importing-subscribers-with-a-csv-file/?utm_source=wpadmin&utm_campaign=import">','</a>'),__('This needs to be in CSV style or a simple paste from Gmail, Hotmail or Yahoo. See [link]examples in our support site[/link].',WYSIJA)) ?></p>
                                </th>
                                <td>
                                    <textarea type="text" style="width:500px;" cols="130" rows="10" class="validate[required]" id="csvtext" name="wysija[user_list][csv]" /></textarea>
                                    <p class="fieldsmatch"></p>
                                </td>
                            </tr>

                            <tr class="csvmode upload">
                                <th scope="row" >
                                    <label for="csvfile"><?php _e('Upload a file',WYSIJA); ?> </label>
                                    <p class="description"><?php echo str_replace(array("[link]","[/link]"),array('<a target="_blank" href="http://support.wysija.com/knowledgebase/importing-subscribers-with-a-csv-file/?utm_source=wpadmin&utm_campaign=import file">','</a>'),__('This needs to be in CSV style. See [link]examples in our support site[/link].',WYSIJA)) ?></p>
                                </th>
                                <td>
                                    <input type="file" name="importfile" size="50" />( <?php

                                    echo sprintf(__('total max upload file size : %1$s',WYSIJA),$bytes)?> )
                                    <p class="fieldsmatch"></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row" colspan="2">
                                    <label for="redirect"><?php _e('Did these subscribers ask to be in your list?',WYSIJA); ?> </label>
                                    <p class="description">
                                        <?php _e('If the answer is "no", consider yourself a spammer.',WYSIJA); ?><br />
                                        <?php echo str_replace(array("[link]","[/link]"),array('<a target="_blank" href="http://support.wysija.com/knowledgebase/dont-import-subscribers-who-didnt-sign-up/#utm_source=wpadmin&utm_campaign=importwarning">','</a>'),__('[link]Read more on support.wysija.com[/link].',WYSIJA)) ?>
                                    </p>
                                </th>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <input type="hidden" value="importmatch" name="action" />

                        <input type="submit" value="<?php echo esc_attr(__('Next step',WYSIJA)) ?>" class="button-primary wysija">

                    </p>
                </form>
            </div>

            <?php
    }


    function importmatch($data){
        ?>
        <form class="form-valid" action="admin.php?page=wysija_subscribers&action=lists" id="wysija-edit" method="post"  name="wysija-edit">
            <div class="list" style="overflow:auto">
                <table cellspacing="0" class="widefat fixed" >
                    <thead>
                        <tr class="thead">
                            <th><?php _e('Match the data',WYSIJA);?></th>
                            <?php
                            $columns=array("nomatch"=>__("Ignore column...",WYSIJA),'email'=>__('Email',WYSIJA),'firstname'=>__('First name',WYSIJA),'lastname'=>__('Last name',WYSIJA),'ip'=>__('IP address',WYSIJA));

                            $formObj=&WYSIJA::get("forms","helper");
                            $i=0;

                            $emailcolumnmatched=false;

                            foreach($data['csv'][0] as $key=> $cols) {
                                /* try to automatically match columns */
                                $selected="";
                                $columnsimple=str_replace(array(" ","-","_"),"",strtolower($cols));
                                $importfields=get_option("wysija_import_fields");
                                if(isset($importfields[$columnsimple])) $selected=$importfields[$columnsimple];

                                /* if it is a set column keyemail */
                                if(!$emailcolumnmatched && isset($data['keyemail'][$key])) {
                                    $selected="email";
                                    $emailcolumnmatched=true;
                                }

                                $dropdown=$formObj->dropdown(array('id'=>"columnMatch".$i,'name'=>"wysija[match][$i]"), $columns,$selected);
                                echo '<th>'.$dropdown.'</th>';
                                $i++;
                            }
                            ?>
                        </tr>
                    </thead>

                    <tbody class="list:<?php echo $this->model->table_name.' '.$this->model->table_name.'-list" id="wysija-'.$this->model->table_name.'"' ?>>

                        <?php
                            $listingRows="";
                            $alt=true;
                            $i=0;
                            foreach($data['csv'] as $columns){
                                $classRow="";
                                if($alt) $classRow=' class="alternate" ';

                                echo "<tr $classRow>";
                                if(isset($data['firstrowisdata'])){
                                    $j=$i+1;
                                }else $j=$i;

                                if($i==0){
                                     $valuefrow='';

                                    if(isset($data['firstrowisdata'])){
                                        $valuefrow='1<input value="1" type="hidden" id="firstrowdata" name="firstrowisdata"  />';
                                    }
                                    echo '<td>'.$valuefrow.'</td>';
                                    //echo '<td><label for="firstrowdata" class="title" title="'.__("This line is not a header description, it is data and needs to be inserted!",WYSIJA).'"><input '.$checked.' type="checkbox" id="firstrowdata" name="firstrowisdata"  />'.__("Insert line!",WYSIJA).'</label></td>';
                                }
                                else echo "<td>".$j."</td>";

                                foreach($columns as $val){
                                   if($i==0 &&  !isset($data['firstrowisdata']))  echo '<td><strong>'.$val.'</strong></td>';
                                   else echo '<td>'.$val.'</td>';
                                }
                                echo "</tr>";

                                $alt=!$alt;
                                $i++;
                            }

                            if($data['totalrows']>3){
                        ?>

                           <tr class="alternate" >
                           <?php

                                echo '<td>...</td>';
                                foreach($data['csv'][0] as $col){
                                    echo '<td>...</td>';
                                }

                           ?>
                            </tr>
                           <tr><td><?php echo $data['totalrows'] ?></td>
                               <?php

                               foreach($data['lastrow'] as $val){
                                   echo '<td>'.$val.'</td>';
                                }
                               ?>
                           </tr>
                           <?php
                           }

                           ?>
                    </tbody>
                </table>
            </div>
            <?php
            if($data['errormatch']){


            }else{
                ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="name"><?php _e('Pick one or many lists',WYSIJA); ?> </label>
                                <p class="description"><?php _e('Pick the lists you want to import those subscribers to.',WYSIJA); ?> </p>
                            </th>
                            <td>
                                <?php

                                    //create an array of existing lists to import within
                                    $model_list=&WYSIJA::get('list','model');
                                    $lists=$model_list->get(array('name','list_id'),array('is_enabled'=>1));
                                    //first value is to create new list
                                    $lists[]=array('name'=>__('New list',WYSIJA),'list_id'=>0);

                                    //create an array of active(status 99) follow_up emails aossicated to a list_id
                                    $helper_email=&WYSIJA::get('email','helper');
                                    $follow_ups_per_list=$helper_email->get_active_follow_ups(array('subject','params'));

                                    $follow_up_name_per_list=array();
                                    foreach($follow_ups_per_list as $list_id => $follow_ups){
                                        if(!isset($follow_up_name_per_list[$list_id])) $follow_up_name_per_list[$list_id]=array();
                                        foreach($follow_ups as $follow_up){
                                            $follow_up_name_per_list[$list_id][]=$follow_up['subject'];
                                        }

                                    }

                                    $formObj=&WYSIJA::get('forms','helper');
                                    //field name for processing
                                    $field='list';
                                    $fieldHTML= '<div>';
                                    foreach($lists as $list){
                                        if($list['list_id']==0){
                                            $fieldHTML.= '<p><label for="'.$field.$list['list_id'].'">';
                                            $fieldHTML.=$formObj->checkbox( array('class'=>'validate[minCheckbox[1]] checkbox','id'=>$field.$list['list_id'],'name'=>"wysija[user_list][$field][]"),$list['list_id']). '<span>' . $list['name'] . '</span>';
                                            $fieldHTML.='</label> ';
                                            $fieldHTML.='<span id="blocknewlist">'.$formObj->input( array('class'=>'validate[required]','id'=>"namenewlist",'size'=>30,'name'=>'wysija[list][newlistname]', 'value'=>'Type name of your new list')).'</span></p>';
                                        }else{
                                            $fieldHTML.= '<p><label for="'.$field.$list['list_id'].'">'.$formObj->checkbox( array('class'=>'validate[minCheckbox[1]] checkbox','id'=>$field.$list['list_id'],'name'=>"wysija[user_list][$field][]"),$list['list_id']).$list['name'];

                                            if(isset($follow_up_name_per_list[$list['list_id']])){
                                                $fieldHTML.=' <span style="margin-left:10px;"><strong>'.__('Note:',WYSIJA).' </strong>'.sprintf(__('subscribers will receive "%1$s" after import.',WYSIJA),  implode(', ', $follow_up_name_per_list[$list['list_id']])).'</span>';
                                            }
                                            $fieldHTML.='</label></p>';
                                        }
                                    }

                                    $fieldHTML .= '</div>';
                                    echo $fieldHTML;
                                ?>
                            </td>
                        </tr>
                    </tbody>
                 </table>
                <p class="submit">
                    <?php $this->secure(array('action'=>'import_save')); ?>
                    <input type="hidden" value="<?php echo esc_attr($data['dataImport']) ?>" name="wysija[dataImport]" />

                    <input type="hidden" value="import_save" name="action" />
                    <input type="submit" value="<?php echo esc_attr(__('Import',WYSIJA)) ?>" class="button-primary wysija">
                </p>
                <?php
            }
            ?>


        </form>
        <?php
    }

    function import_save($data){
        return false;
    }

    function importplugins($data){
        echo '<form class="form-valid" action="admin.php?page=wysija_subscribers&action=lists" id="wysija-edit" method="post"  name="wysija-edit">';
        echo '<ul>';
        $config=&WYSIJA::get('config','model');
        $importedalready=$config->getValue('pluginsImportedEgg');
        foreach($data['plugins'] as $tablename => $pluginInfos){
            if(is_array($importedalready) && in_array($tablename,$importedalready)) continue;
            echo '<li><label for="import-'.$tablename.'1">';
            echo sprintf(__('Import the %1$s subscribers from the plugin: %2$s ',WYSIJA),"<strong>".$pluginInfos['total']."</strong>","<strong>".$pluginInfos['name']."</strong>").'</label>';
            echo '<label for="import-'.$tablename.'1"><input checked="checked" type="radio" id="import-'.$tablename.'1" name="wysija[import]['.$tablename.']" value="1" />'.__('Yes',WYSIJA).'</label>';
            echo '<label for="import-'.$tablename.'0"><input type="radio" id="import-'.$tablename.'0" name="wysija[import]['.$tablename.']" value="0" />'.__('No',WYSIJA).'</label>';
            echo '</li>';
        }
        echo '</ul>';


        ?>
        <p class="submit">
            <?php $this->secure(array('action'=>"importpluginsave")); ?>
            <input type="hidden" value="importpluginsave" name="action" />
            <input type="submit" value="<?php echo esc_attr(__('Import',WYSIJA)) ?>" class="button-primary wysija">
        </p>
        <?php
        echo '</form>';
    }



}
