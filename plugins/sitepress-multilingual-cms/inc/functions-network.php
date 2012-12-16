<?php
 
if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'resetwpml'){    
    include_once ICL_PLUGIN_PATH . '/inc/functions-troubleshooting.php';    
}


add_action('network_admin_menu', 'icl_network_administration_menu');


/*
add_action('network_admin_edit_resetwpml', 'icl_reset_wpml');
add_action('network_admin_edit_deactivatewpml', 'icl_network_deactivate_wpml');
add_action('network_admin_edit_activatewpml', 'icl_network_activate_wpml');
*/

add_action('wpmuadminedit', 'icl_wpmuadminedit');
function icl_wpmuadminedit(){
    switch($_REQUEST['action']){
        case 'resetwpml':  icl_reset_wpml(); break;
        case 'deactivatewpml':  icl_network_deactivate_wpml(); break;
        case 'activatewpml':  icl_network_activate_wpml(); break;
    }
}


function icl_network_administration_menu(){
    global $sitepress;
    add_action('admin_print_styles', array($sitepress,'css_setup'));
    add_menu_page(__('WPML','sitepress'), __('WPML','sitepress'), 'manage_sitess', 
        basename(ICL_PLUGIN_PATH).'/menu/network.php', null, ICL_PLUGIN_URL . '/res/img/icon16.png');
    add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/network.php', 
        __('Network settings','sitepress'), __('Network settings','sitepress'),
        'manage_sitess', basename(ICL_PLUGIN_PATH).'/menu/network.php');
}

function icl_network_deactivate_wpml($blog_id = false){
    
    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'deactivatewpml'){
        check_admin_referer( 'deactivatewpml' );    
    }
    
    if(empty($blog_id)){
        $blog_id = isset($_POST['id']) ? $_POST['id'] : $wpdb->blogid;
    }
      
    if($blog_id){
        switch_to_blog($blog_id);
        update_option('_wpml_inactive', true);
        restore_current_blog();
    }    
    
    if(isset($_REQUEST['submit'])){            
        wp_redirect(network_admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/network.php&updated=true&action=deactivatewpml'));
        exit();
    }
    
    
}

function icl_network_activate_wpml($blog_id = false){
    
    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'activatewpml'){
        if (empty($_REQUEST['_wpnonce']) || $_REQUEST['_wpnonce'] != wp_create_nonce( 'activatewpml' ) ){
            return;
        }    
    }
    
    if(empty($blog_id)){
        $blog_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $wpdb->blogid;
    }
      
    if($blog_id){
        switch_to_blog($blog_id);
        delete_option('_wpml_inactive');
        restore_current_blog();
    } 
    
    wp_redirect(network_admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/network.php&updated=true&action=activatewpml'));
    exit();
       
    
}