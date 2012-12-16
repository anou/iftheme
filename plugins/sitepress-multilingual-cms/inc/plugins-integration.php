<?php

add_action('plugins_loaded', 'wpml_plugins_integration_setup', 50);


function wpml_plugins_integration_setup(){
    global $sitepress_settings;
    
    // WPSEO XML Sitemaps integration
    if(defined('WPSEO_VERSION') && version_compare(WPSEO_VERSION, '1.0.3', '>=')){
        if($sitepress_settings['language_negotiation_type'] == 2){
            require ICL_PLUGIN_PATH . '/inc/wpseo-sitemaps-filter.php';
        }
    }
    
    
} 
  
?>
