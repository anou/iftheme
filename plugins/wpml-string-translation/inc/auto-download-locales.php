<?php

class WPML_ST_MO_Downloader{
    const   LOCALES_XML_FILE = 'http://icanlocalze-static.icanlocalize.com/wp-locales.xml.gz';
    
    const   CONTEXT = 'WordPress';
    private $settings;
    private $xml;
    private $translation_files = array();
    
    
    function __construct(){
        global $wp_version;
        
        // requires Sitepress
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE) return;
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);
         
        $this->settings = get_option('icl_adl_settings');
        
        if(empty($this->settings['wp_version']) || version_compare($wp_version, $this->settings['wp_version'], '>')){
            try{
                $this->updates_check(array('trigger' => 'wp-update'));    
            }catch(Exception $e){
                // do nothing - this is automated request for updates
            }
            
        }
        
        add_action('wp_ajax_icl_adm_updates_check', array($this, 'show_updates'));
        add_action('wp_ajax_icl_adm_save_preferences', array($this, 'save_preferences'));
        
            
    }
    
    function updates_check($args = array()){
        global $wp_version, $sitepress;
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);
        
        $defaults = array(
            'trigger' => 'manual'
        );
        extract($defaults);
        extract($args, EXTR_OVERWRITE);
        
        $active_languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();

        $this->load_xml();        
        $this->get_translation_files();
        
        $updates = array();
        
        foreach($active_languages as $language){
            if($language != $default_language){
                if(isset($this->translation_files[$language['code']]['core'])){
                    $int = preg_match('@tags/([^/]+)/@', $this->translation_files[$language['code']]['core'], $matches);   
                    if($int){
                        $version = $matches[1];                        
                        if(empty($this->settings['translations'][$language['code']]['installed']) 
                                || version_compare($this->settings['translations'][$language['code']]['installed'], $version, '<')){
                            $updates['languages'][$language['code']] = $version;    
                        }
                        $this->settings['translations'][$language['code']]['available'] = $version;                            
                    }else{
                        $int = preg_match('@/trunk/@', $this->translation_files[$language['code']]['core']);   
                        if($int){
                            $this->settings['translations'][$language['code']]['available'] = 'trunk';                            
                        }                        
                    } 
                }
            }
            
        }
        
        $this->settings['wp_version'] = $wpversion;
        
        $this->settings['last_time_xml_check'] = time();
        $this->settings['last_time_xml_check_trigger'] = $trigger;
        $this->save_settings();
        
        return $updates;
        
    }
    
    function show_updates(){
        global $sitepress;
        
        $html = '';
        
        try{
            $updates = $this->updates_check();
            
            if(!empty($updates)){
                $html .= '<table>';
            
            
                foreach($updates['languages'] as $language => $version){
                    $l = $sitepress->get_language_details($language);

                    $html .= '<tr>';
                    $html .= '<td>' . sprintf(__("Updated %s translation is available for WordPress %s.", 'wpml-string-translation'), 
                        '<strong>' . $l['display_name'] . '</strong>' , '<strong>' . $version . '</strong>') . '</td>';
                    $html .= '<td align="right">';
                    $html .= '<a href="' . admin_url('admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;download_mo=' . $language . '&amp;version=' . $version) . '" class="button-secondary">' .  __('Review changes and update', 'wpml-string-translation') . '</a>'; 
                    $html .= '</td>';
                    $html .= '<tr>';
                    $html .= '</tr>';
                }

            
                $html .= '</table>';
            }else{
                $html .= __('No newer versions found.', 'wpml-string-translation');    
            }
            
        }catch(Exception $error){
            $html .= '<span style="color:#f00" >' . $error->getMessage() . '</span>';    
        }
        
        echo json_encode(array('html' => $html));
        exit;
        
    }
    
    function save_preferences(){
        global $sitepress;
        
        $iclsettings['st']['auto_download_mo'] = @intval($_POST['auto_download_mo']);
        $iclsettings['hide_upgrade_notice'] = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
        $sitepress->save_settings($iclsettings);
        
        echo json_encode(array('enabled' => $iclsettings['st']['auto_download_mo']));
        
        exit;
    }
    
    function save_settings(){
        update_option('icl_adl_settings', $this->settings);
    }
    
    function get_option($name){
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }
    
    function load_xml(){
        if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
        $client = new WP_Http();
        $response = $client->request(self::LOCALES_XML_FILE, array('timeout'=>15, 'decompress'=>false));
        
        if(is_wp_error($response)){
            throw new Exception(__('Failed downloading the language information file. Please go back and try a little later.', 'wpml-string-translation'));     
        }else{
            if($response['response']['code'] == 200){
                $this->xml = new SimpleXMLElement(icl_gzdecode($response['body']));
                //$this->xml = new SimpleXMLElement($response['body']);
            }
        }
        
    }
    
    function get_mo_file_urls($locale){
        global $wp_version;        
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version)   ;
        
        if(false !== strpos($locale, '_')){
            $exp = explode('_', $locale);    
            $lpath = $exp[0] . '/' . $exp[1]; 
        }else{
            $lpath = $locale;
        }

        $mo_files = array();
        
        
        $language_path = $this->xml->xpath($lpath . '/versions/version[@number="' . $wpversion . '"]');
        if(empty($language_path)){
            $language_path = $this->xml->xpath($lpath . '/versions/version[@number="trunk"]');
        }
        if(!empty($language_path)){
            $mo_files = (array)$language_path[0];                
            unset($mo_files['@attributes']);
        }elseif(empty($language_path)){
            $other_versions = $this->xml->xpath($lpath . '/versions/version');
            if(is_array($other_versions) && !empty($other_versions)){
                $most_recent = 0;
                foreach($other_versions as $v){
                    $tmpv = (string)$v->attributes()->number;
                    if(version_compare($tmpv , $most_recent, '>')){
                        $most_recent = $tmpv;   
                    }
                }
                if($most_recent > 0){
                    $most_recent_version = $this->xml->xpath($lpath . '/versions/version[@number="' . $most_recent . '"]');
                    $mo_files['core'] = (string)$most_recent_version[0]->core[0];
                }
                
            }
        }

        return $mo_files;
        
    }
    
    function get_translation_files(){
        global $sitepress;
        
        $active_languages = $sitepress->get_active_languages();
        $default_language = $sitepress->get_default_language();
        
        foreach($active_languages as $language){            
            $locale = $sitepress->get_locale($language['code']);
            $urls = $this->get_mo_file_urls($locale);            
            if(!empty($urls)){
                $this->translation_files[$language['code']] = $urls;    
            }
            
        }
                
        return $this->translation_files;
        
    }
    
    function get_translations($language, $args = array()){
        global $wpdb;
        $translations = array();
        
        // defaults
        $defaults = array(
            'type'      => 'core'
        );
        
        extract($defaults);
        extract($args, EXTR_OVERWRITE);
        
        
        if(isset($this->translation_files[$language])){
        
            if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
            $client = new WP_Http();
            $response = $client->request($this->translation_files[$language][$type], array('timeout'=>15));
            
            if(is_wp_error($response)){
                $err = __('Error getting the translation file. Please go back and try again.', 'wpml-string-translation');
                if(isset($response->errors['http_request_failed'][0])){
                    $err .= '<br />' . $response->errors['http_request_failed'][0];
                }
                echo '<div class="error"><p>' . $err . '</p></div>';
                return false;
                
            }        
            
            $mo = new MO();
            $pomo_reader = new POMO_StringReader($response['body']);
            $mo->import_from_reader( $pomo_reader );
            
            
            foreach($mo->entries as $key=>$v){
                
                $tpairs = array();
                $v->singular = str_replace("\n",'\n', $v->singular);
                $tpairs[] = array(
                    'string'        => $v->singular, 
                    'translation'   => $v->translations[0],
                    'name'          => !empty($v->context) ? $v->context . ': ' . $v->singular : md5($v->singular)
                );
                
                if($v->is_plural){
                    $v->plural = str_replace("\n",'\n', $v->plural);
                    $tpairs[] = array(
                        'string'        => $v->plural, 
                        'translation'   => !empty($v->translations[1]) ? $v->translations[1] : $v->translations[0],
                        'name'          => !empty($v->context) ? $v->context . ': ' . $v->plural : md5($v->singular)
                    );
                }
                
                foreach($tpairs as $pair){
                    $existing_translation = $wpdb->get_var($wpdb->prepare("
                        SELECT st.value 
                        FROM {$wpdb->prefix}icl_string_translations st
                        JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                        WHERE s.context = %s AND s.name = %s AND st.language = %s 
                    ", self::CONTEXT, $pair['name'], $language));
                    
                    if(empty($existing_translation)){
                        $translations['new'][] = array(
                                                'string'        => $pair['string'],
                                                'translation'   => '',
                                                'new'           => $pair['translation'],
                                                'name'          => $pair['name']
                        );
                    }else{
                        
                        if(strcmp($existing_translation, $pair['translation']) !== 0){
                            $translations['updated'][] = array(
                                                'string'        => $pair['string'],
                                                'translation'   => $existing_translation,
                                                'new'           => $pair['translation'],
                                                'name'          => $pair['name']
                            );
                        }
                        
                    }
                } 
            }
        }
        
        return $translations;
    }
    
    function save_translations($data, $language, $version = false){
        
        if(false === $version){
            global $wp_version;        
            $version = preg_replace('#-(.+)$#', '', $wp_version)   ;            
        }    
        
        foreach($data as $key => $string){
            $string_id = icl_register_string(self::CONTEXT, $string['name'], $string['string']);
            if($string_id){
                icl_add_string_translation($string_id, $language, $string['translation'], ICL_STRING_TRANSLATION_COMPLETE);
            }
        }    
        
        
        $this->settings['translations'][$language]['time'] = time();
        $this->settings['translations'][$language]['installed'] = $version;
        $this->save_settings();
        
    }
    
    
}
  
  
 
  
?>
