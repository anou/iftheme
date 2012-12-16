<?php
defined('WYSIJA') or die('Restricted access');

class WYSIJA_view_back extends WYSIJA_view{

    var $column_actions=array();/*list of actions possible through links in the list*/
    var $column_action_list='';/*name of the column that will contain the list of action*/
    var $arrayMenus=array();
    var $bulks=array();
    var $action='';
    var $statuses=array();
    var $skip_header = false; // simply returns the wrapper if true
    var $listingHeader = '';
    var $hiddenFields = '';

    function WYSIJA_view_back(){
        /* the default actions to be linked in a listing */
        if(!$this->column_actions)  $this->column_actions=array('view'=>__('View',WYSIJA),'edit'=>__('Edit',WYSIJA),'delete'=>__('Delete',WYSIJA));

        $this->bulks["delete"]=array("name"=>__("Delete",WYSIJA));
    }

    /**
     * creation of a generic listing view
     * @param type $data
     */
    function main($data){

        echo '<form method="post" action="admin.php?page='.$_REQUEST['page'].'" id="posts-filter">';
        $this->filtersLink();
        $this->searchBox();

        $this->listing($data);
        echo '</form>';
    }

    function menuTop($actionmenu=false){

        $menu="";
        if(!empty($this->arrayMenus)){
           foreach($this->arrayMenus as $action =>$actiontrans){
                $menu.= '<a href="admin.php?page='.$_REQUEST['page'].'&action='.$action.'" class="button-secondary2">'.$actiontrans.'</a>';
            }
        }

        return $menu;
    }

    /**
     * to help reproduce the standard view of wordpress admin view here is the header part
     * @param type $methodView
     */
    function header($data){
        echo '<div id="wysija-app" class="wrap">';/*start div class wrap*/

        if($this->skip_header === true) return;

        echo '<div class="icon32" id="'.$this->icon.'"><br/></div>';

        $fulltitle=__($this->title,WYSIJA);
        $action=$subtitle="";

        if(isset($_REQUEST['action'])) $action=$_REQUEST['action'];
        if($action && $action!='main' && isset($this->subtitle)) $subtitle="[".ucfirst(__($action,WYSIJA))."]";


        if(isset($this->titlelink)){
            $mytitle='<a href="admin.php?page='.$_REQUEST['page'].'">'.$fulltitle.'</a> ';
        }else{
            $mytitle=$fulltitle.' ';
        }
        echo '<h2>'.$mytitle.$subtitle.$this->menuTop($this->action,$data).'</h2>';
        echo $this->messages();

    }


    /**
     * to help reproduce the standard view of wordpress admin view here is the footer part
     */
    function footer(){
        echo "</div>";/*end div class wrap*/
    }

    /**
     * to help reproduce the standard listing of a wordpress admin, here is the list of links appearing on top
     */
    function filtersLink(){
        if($this->statuses){
            ?>
            <div class="filter">
                <ul class="subsubsub">
                    <?php

                        $last_key = key(array_slice($this->statuses, -1, 1, TRUE));

                        foreach($this->statuses as $keyst=>$status){
                            $class='';
                            if(isset($_REQUEST['link_filter']) && $_REQUEST['link_filter']==$status['key']) $class='current';
                            echo '<li><a class="'.$class.'" href="'.$status['uri'].'">'.$status['title'].' <span class="count">('.$status['count'].')</span></a>';
                            if($last_key!=$keyst) echo ' | ';
                            echo '</li>';

                        }
                    ?>
                </ul>
            </div>
            <?php
        }
    }

    /**
     * to help reproduce the standard listing of a wordpress admin, here is the search box
     */
    function searchBox(){
        $search="";
        if(isset($_REQUEST['search'])) $search =stripslashes($_REQUEST['search']);
        ?>
            <p class="search-box">
                <label for="wysija-search-input" class="screen-reader-text"><?php echo $this->search['title'] ?></label>
                <input type="text" value="<?php echo esc_attr($search) ?>" class="searchbox" name="search" id="wysija-search-input">
                <input type="submit" class="searchsub button" value="<?php echo esc_attr($this->search['title']) ?>">
            </p>
        <?php
    }

    /**
     * to help reproduce the standard listing of a wordpress admin, here is the table header/footer of the listing
     * @param type $data
     * @return type
     */
    function buildHeaderListing($data){
        //building the headers labels
        if(!$data) {
            //$this->notice(__("There is no data at the moment.",WYSIJA));
            return false;
        }
        $this->listingHeader='<tr class="thead">';
        $columns=$data[0];
        $sorting=array();
        //dbg($columns);
        foreach($columns as $row =>$colss){
            $sorting[$row]=" sortable desc";
            if(isset($_REQUEST["orderby"]) && $_REQUEST["orderby"]==$row) $sorting[$row]=" sorted ".$_REQUEST["ordert"];
        }

        $hiddenOrder="";
        if(isset($_REQUEST["orderby"])){
            $hiddenOrder='<input type="hidden" name="orderby" id="wysija-orderby" value="'.esc_attr($_REQUEST["orderby"]).'"/>';
            $hiddenOrder.='<input type="hidden" name="ordert" id="wysija-ordert" value="'.esc_attr($_REQUEST["ordert"]).'"/>';
        }
        $nk=false;
        if(isset($columns[$this->model->pk])){
            $nk=str_replace("_",'-',$this->model->pk);
            unset($columns[$this->model->pk]);
            $this->cols_nks[$this->model->pk]=$nk;
        }

        if($this->bulks){
            if($nk){
                $this->listingHeader='<th class="manage-column column-'.$nk.' check-column" id="'.$nk.'" scope="col"><input type="checkbox"></th>';
            }
        }

        foreach($columns as $key => $value){
            $nk=str_replace("_",'-',$key);
            $this->listingHeader.='<th class="manage-column column-'.$nk.$sorting[$key].'" id="'.$nk.'" scope="col">';

            if(isset($this->model->columns[$key]['label'])) $label=$this->model->columns[$key]['label'];
            else  $label=ucfirst($key);
            $this->listingHeader.='<a class="orderlink" href="#"><span>'.$label.'</span><span class="sorting-indicator"></span></a>';

            $this->listingHeader.='</th>';

            $this->cols_nks[$key]=$value;
        }
        $this->hiddenFields=$hiddenOrder;
        $this->listingHeader.='</tr>';
        return $this->listingHeader;
    }


    /**
     * to help reproduce the standard listing of a wordpress admin, here is the bulk action dropdown applied to selected rows
     */
    function globalActions($data=false,$second=false){
         ?>
        <div class="tablenav">
            <?php if($this->bulks){ ?>
            <div class="alignleft actions">
                <select name="action2" class="global-action">
                    <option selected="selected" value=""><?php echo esc_attr(__('Bulk Actions', WYSIJA)); ?></option>
                    <?php
                        foreach($this->bulks as $key=> $bulk){
                            echo '<option value="bulk_'.$key.'">'.$bulk['name'].'</option>';
                        }
                    ?>
                </select>
                <input type="submit" class="bulksubmit button-secondary action" name="doaction" value="<?php echo  esc_attr(__('Apply', WYSIJA)); ?>">
                <?php if(!$second)$this->secure('bulk_delete'); ?>
            </div>
            <?php } ?>
            <?php $this->pagination('',$second); ?>
            <div class="clear"></div>
        </div>
        <?php

    }

    /**
     * helping function for listing management here is the pagination function
     */
    function pagination($paramsurl='',$second=false){
        $numperofpages=1;
        $numperofpages=ceil($this->model->countRows/$this->model->limit);

            ?>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php
                $limitend=$this->model->limit_end;
                if($this->model->limit_end>$this->model->countRows) $limitend=$this->model->countRows;
                if($numperofpages>1) echo sprintf(__('Displaying %1$d-%2$d of %3$d.',WYSIJA), $this->model->limit_start+1,$limitend,$this->model->countRows);
                else echo sprintf(__('%1$d Items',WYSIJA), $this->model->countRows);

                        ?></span>
                <?php
                $pagiNUM=1;
                if(isset($_REQUEST['pagi'])) $pagiNUM=$_REQUEST['pagi'];
                if($numperofpages>1){
                    $textBoxPagi="";
                    $sufix="";
                    if($second) $sufix="-2";

                    $textBoxPagi='<input id="wysija-pagination'.$sufix.'" type="text" name="pagi'.$sufix.'" size="4" value="'.$pagiNUM.'" />';
                    $textBoxPagi.='<input id="wysija-pagination-max'.$sufix.'" type="hidden" name="pagimax'.$sufix.'" value="'.$numperofpages.'" />';

                    $pagi='';
                    if($pagiNUM!=1){
                        $pagi.='<a class="prev page-numbers" href="admin.php?page='.$_REQUEST['page'].'&pagi=1'.$paramsurl.'" alt="1" title="'.sprintf(__('Page %1$s',WYSIJA),1).'">«</a>';
                        if($pagiNUM>2) $pagi.='<a class="prev page-numbers" href="admin.php?page='.$_REQUEST['page'].'&pagi='.($pagiNUM-1).$paramsurl.'" alt="'.($pagiNUM-1).'" title="'.sprintf(__('Page %1$s',WYSIJA),($pagiNUM-1)).'" ><</a>';
                    }

                    if($numperofpages>10){
                        if($pagiNUM>3){
                            if($pagiNUM>4)  $pagi.='<span class="dots">...</span>';
                            for($i=$pagiNUM-3;$i<=$pagiNUM-1;$i++){
                                if($i-1==$this->model->page){
                                    $pagi.='<span class="page-numbers current">'.$i.'</span>';
                                }else $pagi.='<a href="admin.php?page='.$_REQUEST['page'].'&pagi='.$i.$paramsurl.'" class="page-numbers" alt="'.$i.'" >'.$i.'</a>';
                            }
                        }else{
                            for($i=1;$i<=3;$i++){
                                if($i-1==$this->model->page){
                                    $pagi.='<span class="page-numbers current">'.$i.'</span>';
                                }else $pagi.='<a href="admin.php?page='.$_REQUEST['page'].'&pagi='.$i.$paramsurl.'" class="page-numbers" alt="'.$i.'" >'.$i.'</a>';
                            }
                            $pagi.='<span class="dots">...</span>';
                        }

                        $pagi.=$textBoxPagi;

                        if($pagiNUM>3 && $pagiNUM<($numperofpages-3)){
                            for($i=$pagiNUM+1;$i<=$pagiNUM+3;$i++){
                                if($i-1==$this->model->page){
                                    $pagi.='<span class="page-numbers current">'.$i.'</span>';
                                }else $pagi.='<a href="admin.php?page='.$_REQUEST['page'].'&pagi='.$i.$paramsurl.'" class="page-numbers" alt="'.$i.'" >'.$i.'</a>';
                            }
                            $pagi.='<span class="dots">...</span>';
                        }else{
                            if($pagiNUM<3){
                                $pagi.='<span class="dots">...</span>';
                                for($i=($numperofpages-2);$i<=$numperofpages;$i++){
                                    if($i-1==$this->model->page){
                                        $pagi.='<span class="page-numbers current">'.$i.'</span>';
                                    }else $pagi.='<a href="admin.php?page='.$_REQUEST['page'].'&pagi='.$i.$paramsurl.'" class="page-numbers" alt="'.$i.'" >'.$i.'</a>';
                                }
                            }


                        }

                    }else{
                        $pagi.=$textBoxPagi;
                        for($i=1;$i<=$numperofpages;$i++){
                            if($i-1==$this->model->page){
                                $pagi.='<span class="page-numbers current">'.$i.'</span>';
                            }else $pagi.='<a href="admin.php?page='.$_REQUEST['page'].'&pagi='.$i.$paramsurl.'" class="page-numbers" alt="'.$i.'" >'.$i.'</a>';
                        }
                    }


                    if($numperofpages >2 && $pagiNUM!=$numperofpages){

                        if(($numperofpages-$pagiNUM)>=2) $pagi.='<a class="next page-numbers" href="admin.php?page='.$_REQUEST['page'].'&pagi='.($pagiNUM+1).$paramsurl.'" alt="'.($pagiNUM+1).'" title="'.sprintf(__('Page %1$s',WYSIJA),($pagiNUM+1)).'">></a>';
                        $pagi.='<a class="next page-numbers" href="admin.php?page='.$_REQUEST['page'].'&pagi='.$numperofpages.$paramsurl.'" alt="'.$numperofpages.'" title="'.sprintf(__('Page %1$s',WYSIJA),$numperofpages).'" >»</a>';

                    }
                    echo $pagi;
                }
                ?>
            </div>
            <?php
    }



    /**
     * limit of records to show per page
     */
    function limitPerPage($urlbase=false){
        if($this->model->countRows<1) return true;

        $limitPerpageS=array(10,20,50,100,500);
        $pagi="";
        $limitPerpage=array();
        /* to correct a display bug */
        $lastLimitpp=false;
        foreach($limitPerpageS as $k=> $count){
            if(isset($this->limit_pp) && $this->limit_pp>=$count){
                $limitPerpage[]=$count;
                $lastLimitpp=true;
            }else{
                if($this->model->countRows>$count){
                    $limitPerpage[]=$count;
                    $lastLimitpp=false;
                }
            }


        }
        if(!$limitPerpage) return;

        $countT=count($limitPerpage);
        if(!$lastLimitpp && count($limitPerpage)<count($limitPerpageS))   $limitPerpage[]=$limitPerpageS[$countT];

        $lastval=end($limitPerpage);
        reset($limitPerpage);
        if(isset($this->limit_pp)) $pagi.='<input id="wysija-pagelimit" type="hidden" name="limit_pp" value="'.$this->limit_pp.'" />';
        foreach($limitPerpage as $k=> $count){
            $numperofpages=ceil($this->model->countRows/$count);
            $titleLink=' title="'.sprintf(__('Split subscribers into %1$s pages.',WYSIJA),$numperofpages).'" ';
            /*if($urlbase)    $linkk=$urlbase.'&limit_pp='.$count;
            else    $linkk='admin.php?page='.$_REQUEST['page'].'&limit_pp='.$count;*/
            $linkk='javascript:;';
            if(isset($_REQUEST['limit_pp'])){
                if($_REQUEST['limit_pp']==$count) $pagi.='<span '.$titleLink.'  class="page-limit current">'.$count.'</span>';
                else $pagi.='<a href="'.$linkk.'" '.$titleLink.' class="page-limit" >'.$count.'</a>';
            }else{

                if($this->model->limit==$count) $pagi.='<span class="page-limit current" '.$titleLink.' >'.$count.'</span>';
                else $pagi.='<a href="'.$linkk.'" '.$titleLink.' class="page-limit" >'.$count.'</a>';
            }
           if($count!=$lastval) $pagi.=" | ";
        }
        ?>
        <div class="tablenav-limits subsubsub">
            <span class="displaying-limits"><?php
            if(isset($this->viewObj->msgPerPage)){
                echo $this->viewObj->msgPerPage;
            }else{
                _e('Subscribers to show per page:',WYSIJA);
            }
             ?></span>
            <?php
                echo $pagi;
            ?>
        </div>
        <?php


    }


    /**
     * here is a helper for each column value on a listing view
     * @param type $key
     * @param type $val
     * @param type $type
     * @return type
     */
    function fieldListHTML($key,$val,$params=array()){
        /*get the params of that field if there is*/

        switch($params['type']){
            case "pk":
                return '<th class="check-column" scope="col"><input class="checkboxselec" type="checkbox" value="'.$val.'" id="'.$key.'_'.$val.'" name="wysija['.$this->model->table_name.']['.$key.'][]"></th>';
                break;
            case "boolean":

                $wrap='<td class="'.$key.' column-'.$key.'">';
                $wrap.=$params['values'][$val];
                $wrap.='</td>';

                break;
            case "date":

                $wrap='<td class="'.$key.' column-'.$key.'">';
                $wrap.=$this->fieldListHTML_created_at($val);
                $wrap.='</td>';

                break;
            case "time":

                if(!isset($params['format']))$params['format']='';

                $wrap='<td class="'.$key.' column-'.$key.'">';
                $wrap.=$this->fieldListHTML_created_at($val,$params['format']);
                $wrap.='</td>';

                break;
            default:
                $wrap='<td class="column-'.$key.'">';
                $specialMethod="fieldListHTML_".$key;
                if(method_exists($this, $specialMethod)) $wrap.=$this->$specialMethod($val);
                else $wrap.=$val;

                $wrap.=$this->getActionLinksList($key);

                $wrap.='</td>';

        }
         return $wrap;
    }

    function fieldListHTML_created_at($val,$format=''){
        if(!$val) return '---';
        if($format) return date_i18n($format,$val);
        else return date_i18n(get_option('date_format'),$val);
    }

    function fieldListHTML_created_at_time($val){
        return $this->fieldListHTML_created_at($val,get_option('date_format').' '.get_option('time_format'));
    }

    /**
     * this function adds a list of action links under the column valued in a listing
     * @param type $column
     * @return string
     */
    function getActionLinksList($column,$manual=false){
        $wrap='';
        if($this->column_action_list==$column ||$manual){
            $wrap='<div class="row-actions">';
            end($this->column_actions);
            $lastkey=key($this->column_actions);
            reset($this->column_actions);
            foreach($this->column_actions as $action => $title){
                switch($action){
                    case "delete":
                        $noncefield='&_wpnonce='.$this->secure(array('action'=>$action,'id'=>$this->valPk),true);
                        break;
                    default:
                        $noncefield="";
                }
                $separator='';

                if($action!=$lastkey)   $separator=' | ';

                if(!isset($this->model->model_name)) $this->model->model_name=$this->model->table_name;
                if(!isset($this->model->model_prefix)) $this->model->model_prefix=$this->model->table_prefix;
                $wrap.='<span class="'.$action.'">
                    <a href="admin.php?page='.$this->model->model_prefix.'_'.$this->model->model_name.'&id='.$this->valPk.'&action='.$action.$noncefield.'" class="submit'.$action.'">'.$title.'</a>'.$separator.'
                </span>';

            }

            $wrap.='</div>';
        }
        return $wrap;
    }


    /**
     * this function is here to help in generic forms
     * @param type $key
     * @param type $val
     * @param type $type
     * @return type
     */
    function fieldFormHTML($key,$wrapped){
        if(isset($this->model->columns[$key]['label'])) $label=$this->model->columns[$key]['label'];
        else  $label=ucfirst($key);
        $desc='';
        if(isset($this->model->columns[$key]['desc'])) $desc='<p class="description">'.$this->model->columns[$key]['desc'].'</p>';
        $wrap='<th scope="row">
                    <label for="'.$key.'">'.$label.$desc.' </label>
                </th><td>';

        $wrap.=$wrapped;
        $wrap.='</td>';
        return $wrap;
    }

    function buildMyForm($step,$data,$model,$required=false){
        $formFields="";

        foreach($step as $row =>$colparams){
            $class="";
            $value="";
            $paramscolumn=false;
            if(isset($colparams['rowclass'])) $class=' class="'.$colparams['rowclass'].'" ';
            $formFields.='<tr '.$class.'>';
            if($model=="config"){
                $value=$this->model->getValue($row);
            }else{
                if($row!="lists")   {
                    if(is_array($model)){
                        foreach($model as $mod){
                            if(isset($data[$mod][$row])){
                                $value=$data[$mod][$row];
                                break;
                            }
                        }

                    }else{
                        if(isset($colparams['isparams'])){
                            $params=$data[$model][$colparams['isparams']];
                            //if(!is_array($data[$model][$colparams['isparams']]))    $params=unserialize(base64_decode($data[$model][$colparams['isparams']]));
                            $value="";
                            if(isset($params[$row]))    $value=$params[$row];
                            $paramscolumn=$colparams['isparams'];
                        }
                        if(isset($data[$model][$row])) $value=$data[$model][$row];
                    }
                    if($value) $value;
                    elseif(isset($_REQUEST['wysija'][$this->model->table_name][$row])) $value=$_REQUEST['wysija'][$this->model->table_name][$row];
                    elseif(isset($colparams["default"])) $value=$colparams["default"];
                    else $value="";
                }elseif(isset($data[$row])) $value=$data[$row];
                elseif(isset($colparams["default"])) $value=$colparams["default"];
                else $value="";
            }

            if($required && !isset($colparams["class"]))   $colparams["class"]=$this->getClassValidate($this->model->columns[$row],true);

            if(isset($colparams['label'])) $label=$colparams['label'];
            else  $label=ucfirst($row);
            $desc='';
            if(isset($colparams['desc'])){
                if(isset($colparams['link'])) $colparams['desc']=str_replace(array("[link]","[/link]"),array($colparams['link'],"</a>"),$colparams['desc']);
                $desc='<p class="description">'.$colparams['desc'].'</p>';
            }
            //if(isset($colparams['desc'])) $desc='<p class="description">'.$colparams['desc'].'</p>';
            $colspan=' colspan="2" ';
            if(!isset($colparams['1col'])){
                $formFields.='<th scope="row">';
                if(!isset($colparams['labeloff'])) $formFields.='<label for="'.$row.'">';
                $formFields.=$label.$desc;
                if(!isset($colparams['labeloff']))  $formFields.=' </label>';
                $formFields.='</th>';
                $colspan='';
            }
            $formFields.='<td '.$colspan.'>';
            $formFields.=$this->fieldHTML($row,$value,$model,$colparams,$paramscolumn);
            $formFields.='</td>';

            $formFields.='</tr>';
        }
        return $formFields;
    }
    /**
     *
     * @param type $key
     * @param type $val
     * @param type $type
     * @return type
     */
    function fieldHTML($key,$val="",$model="",$params=array(),$paramscolumn=false){
        $classValidate=$wrap='';
        /*get the params of that field if there is*/
        $type=$params['type'];
        /* js validator class setup */
        if($params)  $classValidate=$this->getClassValidate($params);

        if($paramscolumn){
            $col=$paramscolumn."][".$key;
        }else $col=$key;

        switch($type){
            case "pk":
                return '<input type="hidden" value="'.$val.'" id="'.$key.'" name="wysija['.$model.']['.$col.']">';
                break;
            case "boolean":
                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->dropdown(array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$params['values'],$val,$classValidate);
                break;
            case "roles":
                $wptools=&WYSIJA::get('wp_tools','helper');
                $editable_roles=$wptools->wp_get_editable_roles();

                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->dropdown(array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$editable_roles,$val,$classValidate);
                break;
            case "password":
                if(!isset($params['size'])){
                    $classValidate.=' size="80"';
                }
                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->input(array('type'=>'password','id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$val,$classValidate);
                break;
            case "radio":

                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->radios(array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$params['values'],$val,$classValidate);
                break;
            case "dropdown_keyval":
                $newoption=array();
                foreach($params['values'] as $vall){
                    $newoption[$vall]=$vall;
                }
                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->dropdown(array('id'=>$key, 'name'=>'wysija['.$model.']['.$key.']'),$newoption,$val,$classValidate);
                break;
            case "dropdown":
                $formObj=&WYSIJA::get("forms","helper");
                $wrap.=$formObj->dropdown(array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$params['values'],$val,$classValidate);
                break;
            case "wysija_pages_list":
                $wrapd=get_pages( array('post_type'=>"wysijap",'echo'=>0,'name'=>'wysija['.$model.']['.$col.']','id'=>$key,'selected' => $val,'class'=>$classValidate) );

                break;
            case "pages_list":
                $wrap.=wp_dropdown_pages( array('echo'=>0,'name'=>'wysija['.$model.']['.$col.']','id'=>$key,'selected' => $val,'class'=>$classValidate) );
                break;
            default:
                if(!isset($params['size'])){
                    $classValidate.=' size="80"';
                }else{
                    $classValidate.=' size="'.$params['size'].'"';
                }
                if(isset($params['class'])){
                    $classValidate.=' class="'.$params['class'].'"';
                }

                $specialMethod="fieldFormHTML_".$type;

                if(method_exists($this, $specialMethod)) $wrap.=$this->$specialMethod($key,$val,$model,$params);
                else{
                    $formObj=&WYSIJA::get("forms","helper");
                    if(method_exists($formObj, $type)){
                        $dataInput=array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']');

                        if(isset($params['cols']))$dataInput['cols']=$params['cols'];
                        if(isset($params['rows']))$dataInput['rows']=$params['rows'];
                        $wrap.=$formObj->$type($dataInput,$val,$classValidate);
                    }else{
                        $wrap.=$formObj->input(array('id'=>$key, 'name'=>'wysija['.$model.']['.$col.']'),$val,$classValidate);
                    }

                }
        }
        return $wrap;
    }

    /**
     * this function is the default listing function
     * @param type $data
     */
    function listing($data,$simple=false){

        if(!$simple)    $this->globalActions();

            $html='<table cellspacing="0" class="widefat fixed">
                <thead>';
            $html.=$this->buildHeaderListing($data);
            $html.='</thead>';

             $html.='<tfoot>';
             $html.=$this->listingHeader;
             $html.='</tfoot>';

             $html.='<tbody class="list:'.$this->model->table_name.' '.$this->model->table_name.'-list" id="wysija-'.$this->model->table_name.'" >';

                        $listingRows="";
                        $alt=true;
                        foreach($data as $row =>$columns){
                            $classRow="";
                            if($alt) $classRow=' class="alternate" ';
                             $listingRows.='<tr'.$classRow.' id="'.$this->model->table_name.'-'.$this->model->table_name.'">';
                             $valpkcol=false;
                             if(isset($columns[$this->model->pk])) {
                                $this->valPk=$columns[$this->model->pk];
                                $valpkcol=$this->model->columns[$this->model->pk];
                                $this->model->columns[$this->model->pk]['type']='pk';
                                unset($columns[$this->model->pk]);
                            }
                            if($this->bulks){

                                if($valpkcol){
                                    $listingRows.=$this->fieldListHTML($this->model->pk,$this->valPk,$this->model->columns[$this->model->pk]);
                                }
                            }



                            foreach($columns as $key => $value){
                                if(isset($this->model->columns[$key])) $val=$this->model->columns[$key];
                                else $val="";
                                $listingRows.=$this->fieldListHTML($key,$value,$val);
                                $listingRows.='</th>';
                            }
                            $alt=!$alt;
                        }
                        $html.= $listingRows;



           $html.=' </tbody>
            </table>';
           if(!$simple) echo $html;
            if(!$simple) {
                $this->globalActions(false,true);
                $this->limitPerPage();
                echo $this->hiddenFields;
            }

            if($simple) return $html;
    }

    /**
     * here is a generic form view
     * @param type $data
     */
    function edit($data){

        $formid='wysija-'.$_REQUEST['action'];
        if($_REQUEST['action']=="edit"){
            $buttonName=__('Modify',WYSIJA);
        }else{
            $buttonName=__('Add',WYSIJA);
        }

        ?>

        <form name="<?php echo $formid ?>" method="post" id="<?php echo $formid ?>" action="" class="form-valid">

            <table class="form-table">
                <tbody>
                    <?php
                    foreach($data as $row =>$columns){
                        $formFields='<tr>';
                        if(isset($columns[$this->model->pk])){
                            $this->valPk=$columns[$this->model->pk];
                            $this->model->columns[$this->model->pk]['type']="pk";
                            $formFields.=$this->fieldHTML($this->model->pk,$this->valPk,$this->model->table_name,$this->model->columns[$this->model->pk ]);
                            unset($columns[$this->model->pk]);
                        }
                        foreach($columns as $key => $value){
                            $formFields.=$this->fieldFormHTML($key,$this->fieldHTML($key,$value,$this->model->table_name,$this->model->columns[$key]));
                            $formFields.='</tr>';
                        }
                        echo $formFields;
                    }
                    ?>
                </tbody>
            </table>
            <p class="submit">
                <?php $this->secure(array('action'=>"save", 'id'=> $this->valPk)); ?>
                <input type="hidden" value="save" name="action" />
                <input type="submit" value="<?php echo esc_attr($buttonName); ?>" class="button-primary wysija">
            </p>
        </form>
        <?php
    }

     /**
     * here is a generic form view
     * @param type $data
     */
    function view($data,$echo=true){

           $html=' <table class="form-table">
                <tbody>';
                    foreach($data as $row =>$columns){
                        $formFields='<tr>';

                        foreach($columns as $key => $value){
                            $formFields.=$this->fieldFormHTML($key,$value);
                            $formFields.='</tr>';
                        }
                        $html.= $formFields;
                    }

             $html.='   </tbody>
            </table>';

          if($echo) echo $html;
          else return $html;
    }

    function fieldFormHTML_fromname($key,$val,$model,$params){
        $formObj=&WYSIJA::get("forms","helper");
        $disableEmail=false;
        if($model!='config') $model='email';
        if($key=='from_name')   $keyemail='from_email';
        else    $keyemail='replyto_email';

        $dataInputEmail=array('class'=>'validate[required]', 'id'=>$keyemail,'name'=>"wysija[$model][$keyemail]", 'size'=>40);

        if(isset($this->data['email'][$key])){
            $valname=$this->data['email'][$key];
            $valemail=$this->data['email'][$keyemail];
        }else{
            $valname=$this->model->getValue($key);
            $valemail=$this->model->getValue($keyemail);
        }

        /*if from email and sending method is gmail then the email is blocked to the smtp_login*/
        if($key=='from_name'){
            $modelConfig=&WYSIJA::get('config','model');
            if($modelConfig->getValue('sending_method')=='gmail')   {
                $dataInputEmail['readonly']='readonly';
                $dataInputEmail['class'].=' disabled';
                $valemail=$modelConfig->getValue('smtp_login');
            }
        }




        $fieldHtml=$formObj->input( array('class'=>'validate[required]', 'id'=>$key,'name'=>"wysija[$model][$key]"),$valname);
        $fieldHtml.=$formObj->input($dataInputEmail ,$valemail);
        return $fieldHtml;
    }

    function _savebuttonsecure($data,$action="save",$button=false,$warning=false){
        if(!$button) $button=__("Save",WYSIJA);
        ?>
            <p class="submit">
                <?php

                $secure=array('action'=>$action);
                if(isset($data[$this->model->table_name][$this->model->pk]))    $secure["id"]=$data[$this->model->table_name][$this->model->pk];
                $this->secure($secure); ?>
                <input type="hidden" name="wysija[<?php echo $this->model->table_name ?>][<?php echo $this->model->pk ?>]" id="<?php echo $this->model->pk ?>" value="<?php if(isset($data[$this->model->table_name][$this->model->pk])) echo esc_attr($data[$this->model->table_name][$this->model->pk]) ?>" />
                <input type="hidden" value="<?php echo $action ?>" name="action" />
                <input type="submit" id="next-steptmpl" value="<?php echo esc_attr($button) ?>" name="submit-draft" class="button-primary wysija"/>
                <?php if($warning)  echo $warning; ?>
            </p>
        <?php
    }


}