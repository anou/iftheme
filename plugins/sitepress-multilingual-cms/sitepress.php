<?php
/*
Plugin Name: WPML Multilingual CMS
Plugin URI: http://wpml.org/
Description: WPML Multilingual CMS. <a href="http://wpml.org">Documentation</a>.
Author: ICanLocalize
Author URI: http://wpml.org
Version: 2.6.3
*/

if(defined('ICL_SITEPRESS_VERSION')) return;
define('ICL_SITEPRESS_VERSION', '2.6.3');
define('ICL_PLUGIN_PATH', dirname(__FILE__));
define('ICL_PLUGIN_FOLDER', basename(ICL_PLUGIN_PATH));
define('ICL_PLUGIN_URL', plugins_url() . '/' . ICL_PLUGIN_FOLDER );

if(defined('WP_ADMIN')){
    require ICL_PLUGIN_PATH . '/inc/php-version-check.php';
    if(defined('PHP_VERSION_INCOMPATIBLE')) return;
}

require ICL_PLUGIN_PATH . '/inc/not-compatible-plugins.php';
if(!empty($icl_ncp_plugins)){
    return;
}


if ( function_exists('is_multisite') && is_multisite() ) {    
    $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
    if(false === get_option('icl_sitepress_version', false) && isset($wpmu_sitewide_plugins[ICL_PLUGIN_FOLDER.'/'.basename(__FILE__)])){
        require_once ICL_PLUGIN_PATH . '/inc/sitepress-schema.php';        
        icl_sitepress_activate();
    }
    include_once ICL_PLUGIN_PATH . '/inc/functions-network.php';
    if(get_option('_wpml_inactive', false) && isset($wpmu_sitewide_plugins[ICL_PLUGIN_FOLDER.'/sitepress.php'])){
        define('ICL_PLUGIN_INACTIVE', true);
        return;
    }
}

require ICL_PLUGIN_PATH . '/inc/constants.php';

require_once ICL_PLUGIN_PATH . '/inc/sitepress-schema.php';
require ICL_PLUGIN_PATH . '/inc/template-functions.php';
require ICL_PLUGIN_PATH . '/sitepress.class.php';
require ICL_PLUGIN_PATH . '/inc/functions.php';
require ICL_PLUGIN_PATH . '/inc/hacks.php';
require ICL_PLUGIN_PATH . '/inc/upgrade.php';
require ICL_PLUGIN_PATH . '/inc/affiliate-info.php';
require ICL_PLUGIN_PATH . '/inc/language-switcher.php';
require ICL_PLUGIN_PATH . '/inc/import-xml.php';

// using a plugin version that the db can't be upgraded to
if(defined('WPML_UPGRADE_NOT_POSSIBLE') && WPML_UPGRADE_NOT_POSSIBLE) return;

if(is_admin() || defined('XMLRPC_REQUEST')){
    require ICL_PLUGIN_PATH . '/lib/icl_api.php';
    require ICL_PLUGIN_PATH . '/lib/xml2array.php';
    require ICL_PLUGIN_PATH . '/lib/Snoopy.class.php';
    require ICL_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php';
    require ICL_PLUGIN_PATH . '/inc/translation-management/pro-translation.class.php';        
    require ICL_PLUGIN_PATH . '/inc/pointers.php';        
}elseif(preg_match('#wp-comments-post\.php$#', $_SERVER['REQUEST_URI'])){
    require ICL_PLUGIN_PATH . '/inc/translation-management/translation-management.class.php';
    require ICL_PLUGIN_PATH . '/inc/translation-management/pro-translation.class.php';        
}

if( !isset($_REQUEST['action'])     || ($_REQUEST['action']!='activate' && $_REQUEST['action']!='activate-selected') 
    || ((!isset($_REQUEST['plugin']) || $_REQUEST['plugin'] != basename(ICL_PLUGIN_PATH).'/'.basename(__FILE__)) 
        && !@in_array(basename(ICL_PLUGIN_PATH).'/'.basename(__FILE__), $_REQUEST['checked']))){

    $sitepress = new SitePress();
    $sitepress_settings = $sitepress->get_settings();

    // Comments translation
    if($sitepress_settings['existing_content_language_verified']){
        require ICL_PLUGIN_PATH . '/inc/comments-translation/functions.php';
    }

    require ICL_PLUGIN_PATH . '/modules/cache-plugins-integration/cache-plugins-integration.php';
    
    require ICL_PLUGIN_PATH . '/inc/wp-login-filters.php';
    
    require_once ICL_PLUGIN_PATH . '/inc/plugins-integration.php';

}

if(!empty($sitepress_settings['automatic_redirect'])){
    require_once ICL_PLUGIN_PATH . '/inc/browser-redirect.php';    
}



// activation hook
register_activation_hook( WP_PLUGIN_DIR . '/' . ICL_PLUGIN_FOLDER . '/sitepress.php', 'icl_sitepress_activate' );
register_deactivation_hook( WP_PLUGIN_DIR . '/' . ICL_PLUGIN_FOLDER . '/sitepress.php', 'icl_sitepress_deactivate');

add_filter('plugin_action_links', 'icl_plugin_action_links', 10, 2);