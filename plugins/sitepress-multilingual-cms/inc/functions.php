<?php 

/**
 * Add settings link to plugin page.
*/
function icl_plugin_action_links($links, $file) {
    $this_plugin = basename(ICL_PLUGIN_PATH) . '/sitepress.php';
    global $sitepress_settings;
    if($file == $this_plugin) {
        $links[] = '<a href="admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php">' . __('Configure', 'sitepress') . '</a>';
    }
    return $links;
}

if(defined('ICL_DEBUG_MODE') && ICL_DEBUG_MODE){           
    add_action('admin_notices', '_icl_deprecated_icl_debug_mode');
}

function _icl_deprecated_icl_debug_mode(){
    echo '<div class="updated"><p><strong>ICL_DEBUG_MODE</strong> no longer supported. Please use <strong>WP_DEBUG</strong> instead.</p></div>';
} 



function icl_js_escape($str){ 
    $str = esc_js($str);
    $str = htmlspecialchars_decode($str);
    return $str;
}  

function icl_nobreak($str){
    return preg_replace("# #", '&nbsp;', $str);
} 

function icl_strip_control_chars($string){
    // strip out control characters (all but LF, NL and TAB)
    $string = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $string);
    return $string;
}

function _icl_tax_has_objects_recursive($id, $term_id = -1, $rec = 0){
    // based on the case where two categories were one the parent of another
    // eliminating the chance of infinite loops by letting this function calling itself too many times
    // 100 is the default limit in most of teh php configuration
    //
    // this limit this function to work only with categories nested up to 60 levels
    // should enough for most cases
    if($rec > 60) return false;
    
    global $wpdb;
    
    if($term_id === -1){
        $term_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d", $id));
    }
    
    $children = $wpdb->get_results($wpdb->prepare("
        SELECT term_taxonomy_id, term_id, count FROM {$wpdb->term_taxonomy} WHERE parent = %d
    ", $term_id));
    
    $count = 0;
    foreach($children as $ch){
        $count += $ch->count;
    }
    
    if($count){
        return true;
    }else{
        foreach($children as $ch){
            if(_icl_tax_has_objects_recursive($ch->term_taxonomy_id, $ch->term_id,  $rec+1)){
                return true;
            }    
        }
        
    }                    
    return false;
}    

function icl_get_post_children_recursive($post, $type = 'page'){
    global $wpdb;
    
    $post = (array)$post;
    
    $children = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_parent IN (".join(',', $post).")", $type));
    
    if(!empty($children)){
        $children = array_merge($children, icl_get_post_children_recursive($children));
    }
    
    return $children;
    
}

function icl_get_tax_children_recursive($id, $taxonomy = 'category'){
    global $wpdb;
    
    $id = (array)$id;    
    
    $children = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} x WHERE x.taxonomy=%s AND parent IN (".join(',', $id).")", $taxonomy));
    
    if(!empty($children)){
        $children = array_merge($children, icl_get_tax_children_recursive($children));
    }
    
    return $children;
    
}

function _icl_trash_restore_prompt(){
    global $sitepress;
    if(isset($_GET['lang'])){
        $post = get_post(intval($_GET['post']));
        if(isset($post->post_status) && $post->post_status == 'trash'){
            $post_type_object = get_post_type_object( $post->post_type );
            $ret = '<p>';
            $ret .= sprintf(__('This translation is currently in the trash. You need to either <a href="%s">delete it permanently</a> or <a href="%s">restore</a> it in order to continue.'), 
                get_delete_post_link($post->ID, '', true) , 
                wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID)
                );
            $ret .= '</p>';
            wp_die($ret);
        }            
    }    
}

function icl_pop_info($message, $icon='info', $args = array()){
    switch($icon){
        case 'info':
            $icon = ICL_PLUGIN_URL . '/res/img/info.png';
            break;
        case 'question':
            $icon = ICL_PLUGIN_URL . '/res/img/question1.png';
            break;
    }
    
    $defaults = array(
        'icon_size' => 16,
        'but_style' => array()
    );
    extract($defaults);
    extract($args, EXTR_OVERWRITE);
    
    ?>
    <div class="icl_pop_info_wrap">
    <img class="icl_pop_info_but <?php echo join(' ', $but_style)?>" src="<?php echo $icon ?>" width="<?php echo $icon_size ?>" height="<?php echo $icon_size ?>" alt="info" />
    <div class="icl_cyan_box icl_pop_info">
    <img class="icl_pop_info_but_close" align="right" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ico-close.png" width="12" height="12" alt="x" />
    <?php echo $message; ?>
    </div>
    </div>
    <?php
}

function icl_is_post_edit(){
    static $is;
    if(is_null($is)){
        global $pagenow;
        $is = ($pagenow == 'post-new.php' || ($pagenow == 'post.php' && isset($_GET['action']) && $_GET['action']=='edit'));    
    }
    return $is;    
}