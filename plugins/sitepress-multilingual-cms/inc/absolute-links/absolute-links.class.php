<?php

class AbsoluteLinks{
    public $custom_post_query_vars = array();
    public $taxonomies_query_vars = array();
    
    function __construct(){
        //add_action('init', array($this,'init_query_vars'));
        
        // Call directly because the 'init' action has already been done
        // when the object is created.
        $this->init_query_vars(); 
    }
    
    function init_query_vars(){
        global $wp_post_types, $wp_taxonomies;
        
        //custom posts query vars
        foreach($wp_post_types as $k=>$v){
            if(in_array($k, array('post','page'))){
                continue;
            }
            if($v->query_var){
                $this->custom_post_query_vars[$k] = $v->query_var;    
            }            
        }        
        //taxonomies query vars
        foreach($wp_taxonomies as $k=>$v){
            if(in_array($k, array('category'))){
                continue;
            }
            if($k == 'post_tag' && !$v->query_var){
                $v->query_var = $tag_base = get_option('tag_base') ? $tag_base : 'tag';
            }
            if($v->query_var){
                $this->taxonomies_query_vars[$k] = $v->query_var;    
            }            
        }
        
    }    
    
    function _process_generic_text($text, &$alp_broken_links){
        global $wpdb, $wp_rewrite, $sitepress, $sitepress_settings;
        
        if(!isset($wp_rewrite)){
            require_once ABSPATH . WPINC . '/rewrite.php'; 
            $wp_rewrite = new WP_Rewrite();
        }
        
        $rewrite = $wp_rewrite->wp_rewrite_rules();
        
        $home_url = $sitepress->language_url(empty($_POST['icl_post_language'])?false:$_POST['icl_post_language']);
         
        
        if($sitepress_settings['language_negotiation_type']==3){
            $home_url = preg_replace("#\?lang=([a-z-]+)#i", '', $home_url);    
        }       
        $home_url = str_replace("?", "\?",$home_url);           
        
        $int1  = preg_match_all('@<a([^>]*)href="(('.rtrim($home_url,'/').')?/([^"^>]+))"([^>]*)>@i',$text,$alp_matches1);        
        $int2 = preg_match_all('@<a([^>]*)href=\'(('.rtrim($home_url,'/').')?/([^\'^>]+))\'([^>]*)>@i',$text,$alp_matches2);        
        for($i = 0; $i < 6; $i++){
            $alp_matches[$i] = array_merge((array)$alp_matches1[$i], (array)$alp_matches2[$i]); 
        }               
        
        $sitepress_settings = $sitepress->get_settings();
        
        if($int1 || $int2){   
            $url_parts = parse_url(rtrim(get_option('home'),'/').'/');                                                    
            foreach($alp_matches[4] as $k=>$m){
                if(0===strpos($m,'wp-content')) continue;
                                
                if($sitepress_settings['language_negotiation_type']==1){
                        $m_orig = $m;
                        $exp = explode('/', $m, 2);                
                        $lang = $exp[0];
                        if($wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='{$lang}'")){
                            $m = $exp[1];    
                        }else{
                            $m = $m_orig;
                            unset($m_orig);
                            $lang = false;
                        }                        
                }

                $pathinfo = '';
                $req_uri = '/' . $m;                                                                
                $req_uri_array = explode('?', $req_uri);
                $req_uri = $req_uri_array[0];
                $req_uri_params = '';
                if (isset($req_uri_array[1])) {
                    $req_uri_params = $req_uri_array[1];
                }
                // separate anchor
                $req_uri_array = explode('#', $req_uri);                
                $req_uri = $req_uri_array[0];
                $anchor = isset($req_uri_array[1]) ? $req_uri_array[1] : false;                
                $self = '/index.php';
                $home_path = parse_url(get_option('home'));
                if ( isset($home_path['path']) )
                    $home_path = $home_path['path'];
                else
                    $home_path = '';
                $home_path = trim($home_path, '/');
                
                $req_uri = str_replace($pathinfo, '', rawurldecode($req_uri));
                $req_uri = trim($req_uri, '/');
                $req_uri = preg_replace("|^$home_path|", '', $req_uri);
                $req_uri = trim($req_uri, '/');
                $pathinfo = trim($pathinfo, '/');
                $pathinfo = preg_replace("|^$home_path|", '', $pathinfo);
                $pathinfo = trim($pathinfo, '/');
                $self = trim($self, '/');
                $self = preg_replace("|^$home_path|", '', $self);
                $self = trim($self, '/');
                
                if ( ! empty($pathinfo) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $pathinfo) ) {
                    $request = $pathinfo;
                } else {
                    // If the request uri is the index, blank it out so that we don't try to match it against a rule.
                    if ( $req_uri == $wp_rewrite->index )
                        $req_uri = '';
                    $request = $req_uri;
                }
                       
                $this_request = $request;
                
                $request_match = $request;
                
                $perma_query_vars = array();
                
                foreach ( (array) $rewrite as $match => $query) {

                    // If the requesting file is the anchor of the match, prepend it
                    // to the path info.
                    if ((! empty($req_uri)) && (strpos($match, $req_uri) === 0) && ($req_uri != $request)) {
                        $request_match = $req_uri . '/' . $request;
                    }
                    
                    if (preg_match("!^$match!", $request_match, $matches) ||
                        preg_match("!^$match!", urldecode($request_match), $matches)) {
                        // Got a match.
                        $matched_rule = $match;

                        // Trim the query of everything up to the '?'.
                        $query = preg_replace("!^.+\?!", '', $query);
                        
                        // Substitute the substring matches into the query.
                        $query = addslashes(WP_MatchesMapRegex::apply($query, $matches));

                        $matched_query = $query;

                        // Parse the query.
                        parse_str($query, $perma_query_vars);
                        
                        break;
                    }
                }  
                       
                $post_name = $category_name = $tax_name = false;
                
                if(isset($perma_query_vars['pagename'])){
                    $icl_post_lang = isset($_POST['icl_post_language']) ? $_POST['icl_post_language'] : $sitepress->get_current_language();
                    $sitepress->switch_lang($icl_post_lang);
                    $page_by_path = get_page_by_path($perma_query_vars['pagename']);
                    $sitepress->switch_lang();
                    
                    if(!empty($page_by_path->post_type)){
                        $post_name = $perma_query_vars['pagename']; 
                        $post_type = 'page';
                    }else{
                        $post_name = $perma_query_vars['pagename']; 
                        $post_type = 'post';
                    }
                    
                }elseif(isset($perma_query_vars['name'])){
                    $post_name = $perma_query_vars['name']; 
                    $post_type = 'post';
                }elseif(isset($perma_query_vars['category_name'])){
                    $category_name = $perma_query_vars['category_name']; 
                }elseif(isset($perma_query_vars['p'])){ // case or /archives/%post_id
                    $p = $perma_query_vars['p'];
                    list($post_type, $post_name) = $wpdb->get_row($wpdb->prepare(
                        "SELECT post_type, post_name FROM {$wpdb->posts} WHERE id=%d", $perma_query_vars['p']), ARRAY_N);
                }else{
                    foreach($this->custom_post_query_vars as $k=>$v){
                        if(isset($perma_query_vars[$v])){
                            $post_name = $perma_query_vars[$v];
                            $post_type = $k;
                            $post_qv   = $v;
                            break;
                        }
                    }
                    foreach($this->taxonomies_query_vars as $k=>$v){
                        if(isset($perma_query_vars[$v])){
                            $tax_name = $perma_query_vars[$v];
                            $tax_type = $v;
                            break;
                        }
                    }                    
                }  
                
                if($post_name){     
                    
                    $icl_post_lang = isset($_POST['icl_post_language']) ? $_POST['icl_post_language'] : $sitepress->get_current_language();
                    $sitepress->switch_lang($icl_post_lang);
                    $p = get_page_by_path($post_name, OBJECT, $post_type);
                    $sitepress->switch_lang();
                                
                    if(empty($p)){ // fail safe
                        if($post_id = url_to_postid($home_path . '/' . $post_name)){
                            $p = get_post($post_id);
                        }
                    }
                    
                    //$name = $wpdb->escape($post_name);
                    //$post_type = isset($perma_query_vars['pagename']) ? 'page' : 'post';
                    //$p = $wpdb->get_row("SELECT ID, post_type FROM {$wpdb->posts} WHERE post_name='{$name}' AND post_type ='{$post_type}'");
                    
                    if($p){
                        if($post_type=='page'){
                            $qvid = 'page_id';
                        }else{
                            $qvid = 'p';
                        }
                        if($sitepress_settings['language_negotiation_type']==1 && $lang){
                            $langprefix = '/' . $lang;
                        }else{
                            $langprefix = '';
                        }
                        $perm_url = '('.rtrim($home_url,'/') . ')?' . $langprefix .'/'. str_replace('?', '\?', $m);
                        $regk = '@href=["\']('.$perm_url.')["\']@i'; 
                        if ($anchor){
                            $anchor = "#".$anchor;
                        } else {
                            $anchor = "";
                        }
                        // check if this is an offsite url
                        if($p->post_type=='page' && $offsite_url = get_post_meta($p->ID, '_cms_nav_offsite_url', true)){
                            $regv = 'href="'.$offsite_url.$anchor.'"';
                        }else{
                            $regv = 'href="' . '/' . ltrim($url_parts['path'],'/') . '?' . $qvid . '=' . $p->ID;
                            if ($req_uri_params != '') {
                                $regv .= '&' . $req_uri_params;
                            }
                            $regv .= $anchor.'"';
                        }
                        $def_url[$regk] = $regv;
                    }else{ 
                        $alp_broken_links[$alp_matches[2][$k]] = array();                            
                        $name = $wpdb->escape($post_name);
                        $p = $wpdb->get_results("SELECT ID, post_type FROM {$wpdb->posts} WHERE post_name LIKE '{$name}%' AND post_type IN('post','page')");
                        if($p){
                            foreach($p as $post_suggestion){
                                if($post_suggestion->post_type=='page'){
                                    $qvid = 'page_id';
                                }else{
                                    $qvid = 'p';
                                }
                                $alp_broken_links[$alp_matches[2][$k]]['suggestions'][] = array(
                                        'absolute'=> '/' . ltrim($url_parts['path'],'/') . '?' . $qvid . '=' . $post_suggestion->ID,
                                        'perma'=> '/'. ltrim(str_replace(get_option('home'),'',get_permalink($post_suggestion->ID)),'/'),
                                        );
                            }
                        }                        
                    }
                }elseif($category_name){
                    if(false !== strpos($category_name, '/')){
                        $splits = explode('/', $category_name);
                        $category_name = array_pop($splits);
                        $category_parent = array_pop($splits);
                        $category_parent_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE slug=%s", $category_parent));
                        $c = $wpdb->get_row($wpdb->prepare("SELECT t.term_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON x.term_id=t.term_id AND x.taxonomy='category' AND x.parent=%d AND t.slug=%s", $category_parent_id, $category_name));                        
                    }else{
                        $c = $wpdb->get_row($wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE slug=%s", $category_name));                                        
                    }
                    if($c){
                        /* not used ?? */
                        if($sitepress_settings['language_negotiation_type']==1 && $lang){ 
                            $langprefix = '/' . $lang;                                  
                        }else{
                            $langprefix = '';
                        }
                        /* not used ?? */
                        $perm_url = '('.rtrim($home_url,'/') . ')?' . $langprefix .'/'.$m;
                        $regk = '@href=[\'"]('.$perm_url.')[\'"]@i';
                        $url_parts = parse_url(rtrim(get_option('home'),'/').'/');
                        $regv = 'href="' . '/' . ltrim($url_parts['path'],'/') . '?cat_ID=' . $c->term_id.'"';
                        $def_url[$regk] = $regv;
                    }else{
                        $alp_broken_links[$alp_matches[2][$k]] = array();                             
                        $c = $wpdb->get_results("SELECT term_id FROM {$wpdb->terms} WHERE slug LIKE '{$name}%'");                        
                        if($c){
                            foreach($c as $cat_suggestion){
                                $alp_broken_links[$alp_matches[2][$k]]['suggestions'][] = array(
                                        'absolute'=>'?cat_ID=' . $cat_suggestion->term_id,
                                        'perma'=> '/'. ltrim(str_replace(get_option('home'),'',get_category_link($cat_suggestion->term_id)),'/')
                                        );
                            }
                        }                        
                    }                        
                }elseif($tax_name){
                    
                    if($sitepress_settings['language_negotiation_type']==1 && $lang){
                        $langprefix = '/' . $lang;
                    }else{
                        $langprefix = '';
                    }
                    
                    $perm_url = '('.rtrim($home_url,'/') . ')?' . $langprefix .'/'.$m;
                    $regk = '@href=["\']('.$perm_url.')["\']@i'; 
                    if ($anchor){
                        $anchor = "#".$anchor;
                    } else {
                        $anchor = "";
                    }
                    
                    $regv = 'href="' . '/' . ltrim($url_parts['path'],'/') . '?' . $tax_type . '=' . $tax_name.$anchor.'"';
                    $def_url[$regk] = $regv;
                    
                }
            }
            
            if(!empty($def_url)){
                $text = preg_replace(array_keys($def_url),array_values($def_url),$text);
                
            }
            
            $tx_qvs = !empty($this->taxonomies_query_vars) && is_array($this->taxonomies_query_vars) ? '|' . join('|',$this->taxonomies_query_vars) : '';                            
            $post_qvs = !empty($this->custom_posts_query_vars) && is_array($this->custom_posts_query_vars) ? '|' . join('|',$this->custom_posts_query_vars) : '';    
            $int = preg_match_all('@href=[\'"]('.rtrim(get_option('home'),'/').'/?\?(p|page_id'.$tx_qvs.$post_qvs.')=([0-9a-z-]+)(#.+)?)[\'"]@i',$text,$matches2);          
            if($int){
                $url_parts = parse_url(rtrim(get_option('home'),'/').'/');
                $text = preg_replace('@href=[\'"]('. rtrim(get_option('home'),'/') .'/?\?(p|page_id'.$tx_qvs.$post_qvs.')=([0-9a-z-]+)(#.+)?)[\'"]@i', 'href="'.'/' . ltrim($url_parts['path'],'/').'?$2=$3$4"', $text);
            }
            
            
        } 
        
        return $text;
    }
    
    
    function process_string($st_id, $translation=true){
        global $wpdb;
        if($st_id){
            if($translation){
                $string_value = $wpdb->get_var("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE id=" . $st_id);
            }else{
                $string_value = $wpdb->get_var("SELECT value FROM {$wpdb->prefix}icl_strings WHERE id=" . $st_id);
            }
            $alp_broken_links = array();
            $string_value_up = $this->_process_generic_text($string_value, $alp_broken_links);            
            if($string_value_up != $string_value){                
                if($translation){
                    $wpdb->update($wpdb->prefix . 'icl_string_translations', array('value'=>$string_value_up), array('id'=>$st_id));
                }else{
                    $wpdb->update($wpdb->prefix . 'icl_strings', array('value'=>$string_value_up), array('id'=>$st_id));
                }
            }
        }
    }        
    
    function process_post($post_id){
        global $wpdb, $wp_rewrite;
        global $sitepress;
        
        
        delete_post_meta($post_id,'_alp_broken_links');

        $post = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID={$post_id}");
        $alp_broken_links = array();
        $post_content = $this->_process_generic_text($post->post_content, $alp_broken_links);
        
        if($post_content != $post->post_content){
            $wpdb->update($wpdb->posts, array('post_content'=>$post_content), array('ID'=>$post_id));
        }
            
        update_post_meta($post_id,'_alp_processed',time());        
        if(!empty($alp_broken_links)){
            update_post_meta($post_id,'_alp_broken_links',$alp_broken_links);                    
        }
    }    
    
}  
?>
