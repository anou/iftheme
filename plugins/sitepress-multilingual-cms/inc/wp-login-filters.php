<?php
  
if($_SERVER['PHP_SELF'] == '/wp-login.php'){
    add_filter('login_redirect', 'icl_login_redirect_filter', 1, 3);
    add_action('site_url', 'icl_login_redirect_filter', 1, 3);


    function icl_login_redirect_filter($redirect_to, $redirect_to_set, $user){
        
        global $sitepress_settings, $sitepress;    
        
        if($sitepress_settings['language_negotiation_type'] == 2 && $sitepress->get_current_language() != $sitepress->get_default_language()){
            $this_domain = $sitepress_settings['language_domains'][$sitepress->get_current_language()] . '/';    
            $default_domain = $sitepress->language_url($sitepress->get_default_language());
            $redirect_to = str_replace($default_domain, $this_domain, $redirect_to);
        }
        
        return $redirect_to;
    }

}  
?>
