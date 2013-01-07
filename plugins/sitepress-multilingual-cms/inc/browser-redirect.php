<?php
// adapted from http://wordpress.org/extend/plugins/black-studio-wpml-javascript-redirect/
// thanks to Blank Studio - http://www.blackstudio.it/

class WPML_Browser_Redirect{

    static function init(){
        if(!is_admin() && !isset($_GET['redirect_to']) && !preg_match('#wp-login\.php$#', preg_replace("@\?(.*)$@", '', $_SERVER['REQUEST_URI']))) {
                add_action( 'wp_print_scripts', array('WPML_Browser_Redirect', 'scripts'));
        }  
    }
    
    static function scripts(){
        global $sitepress, $sitepress_settings;
         
        // Enque javascripts
        wp_enqueue_script('jquery.cookie', ICL_PLUGIN_URL . '/res/js/jquery.cookie.js', array('jquery'), ICL_SITEPRESS_VERSION);
        wp_enqueue_script('wpml-browser-redirect', ICL_PLUGIN_URL . '/res/js/browser-redirect.js', array('jquery', 'jquery.cookie'), ICL_SITEPRESS_VERSION);
            
        $args['skip_missing'] = intval($sitepress_settings['automatic_redirect'] == 1);
        
        // Build multi language urls array
        $languages      = $sitepress->get_ls_languages($args);
        $language_urls  = array();
        foreach($languages as $language) {
            $language_urls[$language['language_code']] = $language['url'];
        }
        // Cookie parameters
        $http_host = $_SERVER['HTTP_HOST'] == 'localhost' ? '' : $_SERVER['HTTP_HOST'];
        $cookie = array(
            'name' => '_icl_visitor_lang_js',
            'domain' => (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN? COOKIE_DOMAIN : $http_host),
            'path' => (defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/'), 
            'expiration' => $sitepress_settings['remember_language']
        );
        
        // Send params to javascript
        $params = array(
            'pageLanguage'  => defined('ICL_LANGUAGE_CODE')? ICL_LANGUAGE_CODE : get_bloginfo('language'),
            'languageUrls'  => $language_urls,
            'cookie'        => $cookie
        );
        wp_localize_script('wpml-browser-redirect', 'wpml_browser_redirect_params', $params);        
        
        
    }
    
    
}

add_action('init', array('WPML_Browser_Redirect', 'init'));

?>
