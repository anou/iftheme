<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_wp_tools extends WYSIJA_object{
    function WYSIJA_help_wp_tools(){
    }
    function set_default_rolecaps(){
        

        $rolesadmin=array('administrator','super_admin');
        foreach($rolesadmin as $roladm){
            $role = get_role($roladm);
            if(!$role) continue;

            $arr=array('wysija_newsletters','wysija_subscribers','wysija_subscriwidget','wysija_config');
            foreach($arr as $arrkey){
                if(!$role->has_cap($arrkey)) $role->add_cap( $arrkey );
            }
        }
    }
    function wp_get_roles() {
        
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);
        $rolearray=array();
        $sum=6;
        foreach($editable_roles as $keyrol => $roledetails){
            switch($keyrol){
                case 'super_admin':
                    $index=1;
                    break;
                case 'administrator':
                    $index=2;
                    break;
                case 'editor':
                    $index=3;
                    break;
                case 'author':
                    $index=4;
                    break;
                case 'contributor':
                    $index=5;
                    break;
                case 'subscriber':
                    $index=6;
                    break;
                default:
                    $sum++;
                    $index=$sum;
            }
            $rolearray[$index]=array('key'=>$keyrol,'name'=>$roledetails['name']);
        }
        ksort($rolearray);
        return $rolearray;
    }
    function wp_get_editable_roles() {
        
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles);
        $possible_values=array();
        foreach ( $all_roles as $role => $details ) {
            $name = translate_user_role($details['name'] );
            switch($role){
                case 'administrator':
                    $keyrole='switch_themes';
                    break;
                case 'editor':
                    $keyrole='moderate_comments';
                    break;
                case 'author':
                    $keyrole='upload_files';
                    break;
                case 'contributor':
                    $keyrole='edit_posts';
                    break;
                case 'subscriber':
                    $keyrole='read';
                    break;
                default:
                    $keyrole=$role;
            }
            $possible_values[$keyrole]=$name;

        }
        return $possible_values;
    }

    function is_caching_active(){
        $checkPlugins=array(
            'wp-super-cache/wp-cache.php' ,
            'w3-total-cache/w3-total-cache.php',
            'quick-cache/quick-cache.php',
            'hyper-cache/plugin.php'
            );

        foreach($checkPlugins as $pluginFileName){
            if(WYSIJA::is_plugin_active($pluginFileName)){
                switch($pluginFileName){
                    case 'wp-super-cache/wp-cache.php':
                        global $cache_enabled, $super_cache_enabled;
                        if(!(WP_CACHE && $cache_enabled && $super_cache_enabled))   continue(2);
                        break;
                    case 'w3-total-cache/w3-total-cache.php':
                        $config = & w3_instance("W3_Config");
                        if(!(WP_CACHE && $config->get_boolean("pgcache.enabled")))   continue(2);
                        break;
                    case 'quick-cache/quick-cache.php':
                        if(!(WP_CACHE && $GLOBALS["WS_PLUGIN__"]["qcache"]["o"]["enabled"]))   continue(2);
                        break;
                    case 'hyper-cache/plugin.php':
                        if(!(WP_CACHE))   continue(2);
                        break;
                    default:
                        continue(2);
                }
                return true;
            }
        }
    }
    function get_permalink($pageid,$params=array(),$simple=false){
        $post = &get_post($pageid);
        $url=get_permalink($post);
        if(!$url){

            $values=array();
            $helperInstall=&WYSIJA::get('install','helper');
            $helperInstall->createPage($values);
            $modelConf=&WYSIJA::get('config','model');
            $modelConf->save($values);
            $post = &get_post($values['confirm_email_link']);
            $url=get_permalink($post);
            if(!$url) $this->error('Error with the wysijap subscription confirmation page.');
        }
        $paramsquery=parse_url($url);
        if($params!==false) $params[$post->post_type]=$post->post_name;

        if($simple){
            $url=site_url();
            if(array_pop(str_split($url))!='/') $url.='/';
        }
        if(isset($paramsquery['query'])){
            $myparams=explode('&',$paramsquery['query']);

            foreach($myparams as $paramvalu){
                $splitkeyval=explode('=',$paramvalu);
                $params[$splitkeyval[0]]=$splitkeyval[1];
            }
        }
        $url = sprintf('%s://%s%s', $paramsquery['scheme'], $paramsquery['host'], $paramsquery['path']);
        if($params){
            if(strpos($url, '?') !== false) $charStart='&';
            else $charStart='?';
            $url.=$charStart;
            $paramsinline=array();
            foreach($params as $k => $v){
                if(is_array($v))    $v = http_build_query(array($k => $v));
                $paramsinline[]=$k.'='.$v;
            }
            $url.=implode('&',$paramsinline);
        }

        $parsed_url = parse_url($url);
        if (empty($parsed_url['scheme'])) {
            $url = get_bloginfo('url') . $url;
        }
        return $url;
    }
    function get_post_types(){
        $args=array(
          'public'   => true,
          '_builtin' => false,
          'show_in_menu'=>true,
          'show_ui'=>true,
        );
        return get_post_types($args,'objects');
    }
}