<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_import extends WYSIJA_object{
    function WYSIJA_help_import(){
    }

    function getPluginsInfo($table=false){
        $pluginsTest=array(
            'newsletter'=>array(
                "name"=>__("Newsletter Pro (by Satollo)",WYSIJA),
                "pk"=>"id",
                "matches"=>array("name"=>"firstname","email"=>"email","surname"=>"lastname","ip"=>"ip"),
                "where"=>array("status"=>"C")
            ),
            'easymail_subscribers'=>array(
                "name"=>"ALO EasyMail",
                "pk"=>"id",
                "matches"=>array("name"=>"firstname","email"=>"email"),
                "where"=>array("active"=>1),
                "whereunconfirmed"=>array("active"=>0)
            ),
            'meenewsusers'=>array(
                "name"=>"Meenews",
                "pk"=>"id",
                "matches"=>array("name"=>"firstname","email"=>"email"),
                "where"=>array("state"=>2),
                "whereunconfirmed"=>array("state"=>1),
                'list'=>array(
                    'list'=> array(
                        'table'=>'meenewscategories',
                        'matches'=>array(
                            'categoria'=>'name'
                            ),
                        'pk'=>'id',
                        ),
                    'user_list'=>array(
                        'table'=>'meenewsusers',
                        'matches'=>array(
                            'id_categoria'=>'list_id',
                            'subscriber_id'=>'id'
                            )
                        )
                    )
            ),
            'mailpress_users'=>array(
                "name"=>"MailPress",
                "pk"=>"id",
                "matches"=>array("name"=>"firstname","email"=>"email","laststatus_IP"=>"ip"),
                "where"=>array("status"=>"active"),
                "whereunconfirmed"=>array("status"=>"waiting")
            ),
            'subscribe2'=>array(
                "name"=>"Subscribe2",
                "pk"=>"id",
                "matches"=>array("email"=>"email","ip"=>"ip"),
                "where"=>array("active"=>1),
                "whereunconfirmed"=>array("active"=>0)
            ),
            'eemail_newsletter_sub'=>array(
                "name"=>"Email newsletter",
                "pk"=>"eemail_id_sub",
                "matches"=>array("eemail_email_sub"=>"email","eemail_name_sub"=>"firstname"),
                "where"=>array("eemail_status_sub"=>"YES")
            ),
            'gsom_subscribers'=>array(
                "name"=>"G-Lock Double Opt-in Manager",
                "pk"=>"intId",
                "matches"=>array("varEmail"=>"email","gsom_fname_field"=>"firstname","Last_Name1"=>"lastname","varIP"=>"ip"),
                "where"=>array("intStatus"=>1),
                "whereunconfirmed"=>array("intStatus"=>0)
            ),
            'wpr_subscribers'=>array(
                "name"=>"WP Autoresponder",
                "pk"=>"id",
                "matches"=>array("name"=>"firstname","email"=>"email"),
                "where"=>array("active"=>1,"confirmed"=>1),
                "whereunconfirmed"=>array("active"=>1,"confirmed"=>0),
                'list'=>array(
                    'list'=> array(
                        'table'=>'wpr_newsletters',
                        'matches'=>array(
                            'name'=>'name'
                            ),
                        'pk'=>'id',
                        ),
                    'user_list'=>array(
                        'table'=>'wpr_subscribers',
                        'matches'=>array(
                            'nid'=>'list_id',
                            'id'=>'user_id'
                            )
                        )
                    )
            ),
            'wpmlsubscribers'=>array(
                "name"=>__("Newsletters (by Tribulant)"),
                "pk"=>"id",
                "matches"=>array("email"=>"email","ip_address"=>"ip"),
                "where"=>array("wpmlsubscriberslists.active"=>"Y"),
                'list'=>array(
                    'list'=> array(
                        'table'=>'wpmlmailinglists',
                        'matches'=>array(
                            'title'=>'name'
                            ),
                        'pk'=>'id',
                        ),
                    'user_list'=>array(
                        'table'=>'wpmlsubscriberslists',
                        'matches'=>array(
                            'list_id'=>'list_id',
                            'subscriber_id'=>'user_id'
                            )
                        )
                    )
            ),
            'nl_email'=>array(
                "name"=>"Sendit",
                "pk"=>"id_email",
                "matches"=>array("email"=>"email","contactname"=>"firstname"),
                "where"=>array("accepted"=>"y"),
                "whereunconfirmed"=>array("accepted"=>"n"),
                'list'=>array(
                    'list'=> array(
                        'table'=>'nl_liste',
                        'matches'=>array(
                            'nomelista'=>'name'
                            ),
                        'pk'=>'id_lista',
                        ),
                    'user_list'=>array(
                        'table'=>'nl_email',
                        'matches'=>array(
                            'id_lista'=>'list_id',
                            'id_email'=>'user_id'
                            )
                        )
                    )
            )
        );
        if($table){
            if(!isset($pluginsTest[$table])) return false;
            return $pluginsTest[$table];
        }
        return $pluginsTest;
    }
    function reverseMatches($matches){
        $matchesrev=array();
        foreach($matches as $key => $val){
            $matchesrev[$val]=$key;
        }
        return $matchesrev;
    }
    
    function testPlugins(){
        $modelWysija=new WYSIJA_model();
        $possibleImport=array();
        foreach($this->getPluginsInfo() as $tableName =>$pluginInfos){
            
            $result=$modelWysija->query("SHOW TABLES like '".$modelWysija->wpprefix.$tableName."';");
            if($result){
                
                $where=$this->generateWhere($pluginInfos['where']);
                $result=$modelWysija->query("get_row", "SELECT COUNT(`".$pluginInfos['pk']."`) as total FROM `".$modelWysija->wpprefix.$tableName."` ".$where." ;", ARRAY_A);
                $pluginInfosSave=array();
                if((int)$result['total']>0){
                    
                    $pluginInfosSave['total']=(int)$result['total'];
                    
                    if(isset($pluginInfos['list'])){
                        $resultlist=$modelWysija->query("SHOW TABLES like '".$modelWysija->wpprefix.$pluginInfos['list']['list']['table']."';");
                        if($resultlist){
                            
                            
                            $where="";
                            $queryLists="SELECT COUNT(`".$pluginInfos['list']['list']['pk']."`) as total FROM `".$modelWysija->wpprefix.$pluginInfos['list']['list']['table']."` ".$where." ;";
                            $resultlist=$modelWysija->query("get_row", $queryLists, ARRAY_A);
                            if((int)$resultlist['total']>0){
                                
                                $pluginInfosSave['total_lists']=(int)$resultlist['total'];
                            }
                        }
                    }
                    if(!isset($pluginInfosSave['total_lists']))$pluginInfosSave['total_lists']=1;
                    $possibleImport[$tableName]=$pluginInfosSave;
                }
            }
        }
        
        if($possibleImport){
            $modelConfig=&WYSIJA::get("config","model");
            $modelConfig->save(array("pluginsImportableEgg"=>$possibleImport));
        }
    }

    function import($tablename,$plugInfo,$issyncwp=false,$ismainsite=true,$isSynch=false){
        
        global $wpdb;
        
        $model=&WYSIJA::get('list','model');
        
        if(!$isSynch){
            if($issyncwp)   $listname=__('WordPress Users',WYSIJA);
            else $listname=sprintf(__('%1$s\'s import list',WYSIJA),$plugInfo["name"]);
            $descriptionList=sprintf(__('The list created automatically on import of the plugin\'s subscribers : "%1$s',WYSIJA),$plugInfo["name"]);
            
            $defaultListId=$model->insert(array(
                'name'=>$listname,
                'description'=>$descriptionList,
                'is_enabled'=>0,
                'namekey'=>$tablename));
        }else $defaultListId=$isSynch['wysija_list_main_id'];

        $mktime=time();
        $mktimeConfirmed=$mktime+1;
        if(strpos($tablename, 'query-')!==false){
            $lowertbname=str_replace('-', '_', $tablename);

            $matches=apply_filters('wysija_fields_'.$lowertbname);
            $querySelect=apply_filters('wysija_select_'.$lowertbname);
            $querySelect=str_replace('[created_at]', $mktime, $querySelect);
            $fields="(`".implode("`,`",$matches)."`,`created_at` )";
            $query="INSERT IGNORE INTO `[wysija]user` $fields $querySelect";
        }else{
            
            
            $colsPlugin=array_keys($plugInfo['matches']);
            $extracols=$extravals="";
            if(isset($plugInfo['matchesvar'])){
                $extracols=",`".implode("`,`",array_keys($plugInfo["matchesvar"]))."`";
                $extravals=",".implode(",",$plugInfo["matchesvar"]);
            }
            
            if(isset($plugInfo['whereunconfirmed'])){
                $fields="(`".implode("`,`",$plugInfo["matches"])."`,`created_at` ".$extracols." )";
                $values="`".implode("`,`",$colsPlugin)."`,".$mktime.$extravals;
                
                $where=$this->generateWhere($plugInfo['whereunconfirmed']);
                $query="INSERT IGNORE INTO `[wysija]user` $fields SELECT $values FROM ".$model->wpprefix.$tablename.$where;
                $model->query($query);
            }

            $fields="(`".implode("`,`",$plugInfo["matches"])."`,`created_at` ".$extracols." )";
            $values="`".implode("`,`",$colsPlugin)."`,".$mktimeConfirmed.$extravals;
            
            if($tablename=='users') {
                
                $innerjoin='';
                if(!$ismainsite){
                    $innerjoin=' INNER JOIN '.$wpdb->base_prefix.'usermeta ON ( '.$wpdb->base_prefix.$tablename.'.ID = '.$wpdb->base_prefix.'usermeta.user_id )';
                    $innerjoin.=" WHERE ".$wpdb->base_prefix."usermeta.meta_key = '".$model->wpprefix."capabilities'";
                }
                $query="INSERT IGNORE INTO `[wysija]user` $fields SELECT $values FROM ".$wpdb->base_prefix.$tablename.$innerjoin;
            }else    {
                
                $where=$this->generateWhere($plugInfo['where']);
                $query="INSERT IGNORE INTO `[wysija]user` $fields SELECT $values FROM ".$model->wpprefix.$tablename.$where;
            }
        }

        $model->query($query);

        $modelU=&WYSIJA::get('user','model');
        $modelU->update(array('status'=>1),array('created_at'=>$mktimeConfirmed));

        
        $this->insertUserList($defaultListId,$mktime,$mktimeConfirmed);
        $query="SELECT COUNT(user_id) as total FROM ".$model->getPrefix()."user WHERE created_at IN ('".$mktime."','".$mktimeConfirmed."')";
        $result=$wpdb->get_row($query, ARRAY_A);
        
        if(isset($plugInfo['list'])){
            $listmatchesrev=$this->reverseMatches($plugInfo['list']['list']['matches']);
            
            $selectListsKeep="SELECT `".$listmatchesrev['name']."`,`".$plugInfo['list']['list']['pk']."` FROM ".$model->wpprefix.$plugInfo['list']['list']['table'];
            $resultslists=$model->query("get_res",$selectListsKeep);
            if($resultslists){
                $userlistmatchesrev=$this->reverseMatches($plugInfo['list']['user_list']['matches']);
                foreach($resultslists as $listresult){
                    
                    if(!$isSynch){
                       $listname=sprintf(__('"%2$s" imported from %1$s',WYSIJA),$plugInfo["name"],$listresult[$listmatchesrev['name']]);
                        $descriptionList=sprintf(__('The list existed in "%1$s" and has been imported automatically.',WYSIJA),$plugInfo["name"]);
                        $listidimported=$model->insert(array(
                        "name"=>$listname,
                        "description"=>$descriptionList,
                        "is_enabled"=>0,
                        "namekey"=>$tablename."-listimported-".$listresult[$plugInfo['list']['list']['pk']]));
                    }else {
                        $model->reset();
                        $datalist=$model->getOne(false,array("namekey"=>$tablename."-listimported-".$listresult[$plugInfo['list']['list']['pk']]));
                        $listidimported=$datalist['list_id'];
                    }
                    
                    $innerjoin=' INNER JOIN '.$model->wpprefix.$tablename.' ON ( [wysija]user.email = '.$model->wpprefix.$tablename.'.'.$plugInfo['matches']['email'].' )';
                    if($plugInfo['list']['user_list']['table']!=$tablename) $innerjoin.=' INNER JOIN '.$model->wpprefix.$plugInfo['list']['user_list']['table'].' ON ( '.$model->wpprefix.$plugInfo['list']['user_list']['table'].'.'.$userlistmatchesrev['user_id'].' = '.$model->wpprefix.$tablename.'.'.$plugInfo['pk'].' )';
                    $innerjoin.=" WHERE ".$model->wpprefix.$plugInfo['list']['user_list']['table'].".".$userlistmatchesrev['list_id']."='".$listresult[$plugInfo['list']['list']['pk']]."' ";
                    $selectuserCreated="SELECT `[wysija]user`.`user_id`, ".$listidimported.", ".time()." FROM [wysija]user ".$innerjoin;
                    $query="INSERT IGNORE INTO `[wysija]user_list` (`user_id`,`list_id`,`sub_date`) ".$selectuserCreated;
                    $model->query($query);
                }
            }
        }
        $helperU=&WYSIJA::get('user','helper');
        $helperU->refreshUsers();
        if(!$isSynch){

        }else{

        }
        return $defaultListId;
    }
    function insertUserList($defaultListId,$mktime,$mktimeConfirmed,$querySelect=false){
        $model=&WYSIJA::get('list','model');
        if(!$querySelect)   $querySelect='SELECT `user_id`, '.$defaultListId.', '.time()." FROM [wysija]user WHERE created_at IN ('".$mktime."','".$mktimeConfirmed."')";
        $query='INSERT IGNORE INTO `[wysija]user_list` (`user_id`,`list_id`,`sub_date`) '.$querySelect;
        return $model->query($query);
    }
    function generateWhere($plugInfoWhere){
        $where=" as B WHERE";
        $i=0;
        foreach($plugInfoWhere as $keyy => $vale){
            if($i>0)$where.=' AND ';
            if($keyy=="wpmlsubscriberslists.active"){
                $model=&WYSIJA::get("list","model");
                $innerjoin=' INNER JOIN '.$model->wpprefix.'wpmlsubscriberslists as B ON ( '.$model->wpprefix.'wpmlsubscribers.id = B.subscriber_id )';
                $where=$innerjoin." WHERE B.active='".$vale."' ";
            }else{
                $where.=' B.'.$keyy."='".$vale."' ";
            }
            $i++;
        }
        return $where;
    }
    function importWP(){
        $ismainsite=true;
        if (is_multisite()){
            
            global $wpdb;
            if($wpdb->prefix!=$wpdb->base_prefix){
                $ismainsite=false;
            }
        }
        $infosImport=array("name"=>"WordPress",
            "pk"=>"ID",
            "matches"=>array("ID"=>"wpuser_id","user_email"=>"email"),
            "matchesvar"=>array("status"=>1));
        $tablename='users';
        return $this->import($tablename,$infosImport,true,$ismainsite);
    }

}
