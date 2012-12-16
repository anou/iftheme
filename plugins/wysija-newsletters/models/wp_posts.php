<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_model_wp_posts extends WYSIJA_model{

    var $pk='ID';
    var $tableWP=true;
    var $table_name='posts';
    var $columns=array(
        'ID'=>array('req' => true, 'type' => 'integer'),
        'post_author' => array('type' => 'integer'),
        'post_date' => array(),
        'post_date_gmt' => array(),
        'post_content' => array(),
        'post_title' => array(),
        'post_excerpt' => array(),
        'post_status' => array(),
        'comment_status' => array(),
        'ping_status' => array(),
        'post_password' => array(),
        'post_name' => array(),
        'to_ping' => array(),
        'pinged' => array(),
        'post_modified' => array(),
        'post_modified_gmt' => array(),
        'post_content_filtered' => array(),
        'post_parent' => array('type' => 'integer'),
        'guid' => array(),
        'menu_order'=> array('type' => 'integer'),
        'post_type' => array(),
        'post_mime_type' => array(),
        'comment_count' =>array('type' => 'integer'),
    );

    function WYSIJA_model_wp_posts(){
        $this->WYSIJA_model();
        $this->table_prefix='';
    }

    function get_posts($args=array()){
        if(!$args) return false;
        $customQuery='';

        /**
         * SELECT A.ID, A.post_title, A.post_content, A.post_date FROM `wp_posts` A
         * LEFT JOIN `wp_term_relationships` B ON (A.ID = B.object_id)
         * LEFT JOIN `wp_term_taxonomy` C ON (C.term_taxonomy_id = B.term_taxonomy_id)
         * WHERE C.term_id IN (326) AND A.post_type IN ('post') AND A.post_status IN ('publish') ORDER BY post_date DESC LIMIT 0,10;
         *
         */

        $customQuery='SELECT DISTINCT A.ID, A.post_title, A.post_content, A.post_excerpt FROM `[wp]'.$this->table_name.'` A ';

        if(isset($args['category']) && $args['category']) {
            $customQuery.='JOIN `[wp]term_relationships` as B ON (A.ID = B.object_id) ';
            $customQuery.='JOIN `[wp]term_taxonomy` C ON (C.term_taxonomy_id = B.term_taxonomy_id) ';
        }

        $conditionsOut=$conditionsIn=array();

        foreach($args as $col => $val){
            if(!$val) continue;
            switch($col){
                case 'category':
                    //$conditionsIn['B.term_taxonomy_id']=array('sign'=>'IN','val' =>$val, 'cast' => 'int');
                    $conditionsIn['C.term_id']=array('sign'=>'IN','val' =>$val, 'cast' => 'int');
                    break;
                case 'include':
                    $conditionsIn['A.ID'] = array('sign'=>'IN','val' =>$val, 'cast' => 'int');
                    break;
                case 'exclude':
                    $conditionsIn['A.ID'] = array('sign'=>'NOT IN', 'val' => $val, 'cast' => 'int');
                    break;
                case 'post_type':
                    $conditionsIn['A.post_type']=array('sign'=>'IN','val' =>$val);
                    break;
                case 'post_status':
                    $conditionsIn['A.post_status']=array('sign'=>'IN','val' =>$val);
                    break;
                case 'post_date':
                    //convert the date
                    $toob=&WYSIJA::get('toolbox','helper');
                    $val= $toob->time_tzed($val);

                    if($val !== '') {
                        $conditionsIn['A.post_date']=array('sign'=>'>','val' =>$val);
                    }
                    break;
                default:
            }
        }

        $customQuery.='WHERE ';

        $customQuery.=$this->setWhereCond($conditionsIn);

        if(isset($args['orderby'])){
            $customQuery.=' ORDER BY '.$args['orderby'];
            if(isset($args['order'])) $customQuery.=' '.$args['order'];
        }

        if(isset($args['numberposts'])){
            $customQuery.=' LIMIT 0,'.$args['numberposts'];
        }
        WYSIJA::log('post notif qry',$customQuery,'post_notif');
        return $this->query('get_res',$customQuery);
    }

    function setWhereCond($conditionsIn){
        $customQuery='';
        $i = 0;

        foreach($conditionsIn as $col => $data) {

            if($i > 0) $customQuery .=' AND ';

            $customQuery .= $col.' ';

            $value = $data['val'];

            switch($data['sign']) {
                case 'IN':
                case 'NOT IN':
                    $values = '';
                    if(is_array($value)) {
                        if(array_key_exists('cast', $data) && $data['cast'] === 'int') {
                            $count = count($value);
                            for($j = 0; $j < $count; $j++) {
                                if($value[$j] === null) continue;
                                $value[$j] = intval($value[$j]);
                            }
                            $values = join(', ', $value);
                        } else {
                            $values = "'".join("', '", $value)."'";
                        }
                    } else {
                        if(strpos($value, ',') === FALSE) {
                            // single value
                            $values = "'".$value."'";
                        } else {
                            // multiple values
                            $values = "'".join("','", explode(',', $value))."'";
                        }
                    }

                    if($values !== '') {
                        $customQuery.= $data['sign'].' ('.$values.') ';
                    }
                    break;

                default:
                    $sign='=';
                    if(isset($data['sign'])) $sign = $data['sign'];

                    if(array_key_exists('cast', $data) && $data['cast'] === 'int') {
                        $customQuery.= $sign.(int)$value." ";
                    } else {
                        $customQuery.= $sign."'".$value."' ";
                    }
            }
            $i++;
        }
        return $customQuery;
    }

}