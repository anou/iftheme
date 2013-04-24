<?php

class WPML_ST_MO_Downloader{
    const   LOCALES_XML_FILE = 'http://d2pf4b3z51hfy8.cloudfront.net/wp-locales.xml.gz';
    
    const   CONTEXT = 'WordPress';
    private $settings;
    private $xml;
    private $translation_files = array();
    
    
    function __construct(){
        global $wp_version;
        
        // requires Sitepress
        if(!defined('ICL_SITEPRESS_VERSION') || ICL_PLUGIN_INACTIVE) return;
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);
         
        $fh = fopen(WPML_ST_PATH . '/inc/lang-map.csv', 'r');
        while(list($locale, $code) = fgetcsv($fh)){
            $this->lang_map[$locale] = $code;            
        }   
        $this->lang_map_rev =& array_flip($this->lang_map);
         
         
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
                
                if(isset($this->translation_files[$language['code']])){
                    foreach($this->translation_files[$language['code']] as $project => $info){
                        
                        $this->settings['translations'][$language['code']][$project]['available'] = $info['signature'];
                        if(empty($this->settings['translations'][$language['code']][$project]['installed']) || 
                            isset($this->translation_files[$language['code']][$project]['available']) && 
                            $this->settings['translations'][$language['code']][$project]['installed'] != $this->translation_files[$language['code']][$project]['available']){
                                $updates['languages'][$language['code']][$project] = $this->settings['translations'][$language['code']][$project]['available'];    
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
            
            // filter only core( & admin)
            $updates_core = array();
            foreach($updates['languages'] as $k => $v){
                if(!empty($v['core'])){
                    $updates_core['languages'][$k]['core']  = $v['core'];                        
                }                
                if(!empty($v['admin'])){
                    $updates_core['languages'][$k]['admin']  = $v['admin'];                        
                }                                
            }
            $updates = $updates_core;            
                        
            if(!empty($updates)){
                $html .= '<table>';
            
            
                foreach($updates['languages'] as $language => $projects){
                    $l = $sitepress->get_language_details($language);
                    
                    if(!empty($projects['core']) || !empty($projects['admin'])){
                        
                        $vkeys = array();
                        foreach($projects as $key => $value){
                            $vkeys[] = $key . '|' . $value;
                        }
                        $version_key = join(';', $vkeys);
                        
                        
                        $html .= '<tr>';
                        $html .= '<td>' . sprintf(__("Updated %s translation is available", 'wpml-string-translation'), 
                            '<strong>' . $l['display_name'] . '</strong>') . '</td>';
                        $html .= '<td align="right">';
                        $html .= '<a href="' . admin_url('admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;download_mo=' . $language . '&amp;version=' . $version_key) . '" class="button-secondary">' .  __('Review changes and update', 'wpml-string-translation') . '</a>'; 
                        $html .= '</td>';
                        $html .= '<tr>';
                        $html .= '</tr>';
                    }
                    
                }

            
                $html .= '</table>';
            }else{
                $html .= __('No updates found.', 'wpml-string-translation');    
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
    
    function get_mo_file_urls($wplocale){
        global $wp_version;        
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version)   ;
        $wpversion = join('.', array_slice(explode('.', $wpversion), 0, 2)) . '.x';
        
        $exp = explode('-', $wplocale);
        $language = $exp[0];
        $locale = isset($exp[1]) ? $wplocale : $language;
        
        $mo_files = array();
        
        $projects = $this->xml->xpath($language . '/' . $locale);
        if(!empty($projects)){
            
            $project_names = array();
            foreach($projects[0] as $project_name => $data){
                // subprojects
                if(empty($data->versions)){
                    $subprojects = $this->xml->xpath($language . '/' . $locale . '/' . $project_name);
                    if(!empty($subprojects)){
                        foreach($subprojects[0] as $sub_project_name => $sdata){
                            $project_names[] = $project_name . '/' . $sub_project_name ;    
                        }    
                    }
                }else{
                    $project_names[] = $project_name;
                }
            }
            
            if(!empty($project_names)){
                foreach($project_names as $project_name){
                    // try to get the corresponding version
                    $locv_path = $this->xml->xpath("{$language}/{$locale}/{$project_name}/versions/version[@number=\"" . $wpversion . "\"]");
                    // try to get the dev recent version
                    if(empty($locv_path)){
                        $locv_path = $this->xml->xpath("{$language}/{$locale}/{$project_name}/versions/version[@number=\"dev\"]");
                    }
                    if(!empty($locv_path)){
                        $mo_files[$project_name]['url']         = (string)$locv_path[0]->url;
                        $mo_files[$project_name]['signature']   = (string)$locv_path[0]['signature'];
                        $mo_files[$project_name]['translated']  = (string)$locv_path[0]['translated'];
                        $mo_files[$project_name]['untranslated']= (string)$locv_path[0]['untranslated'];                    
                    }                    
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
            if(!isset($this->lang_map[$locale])) continue;
            $wplocale = $this->lang_map[$locale];
            
            $urls = $this->get_mo_file_urls($wplocale);            
            
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
            'types'      => array('core')
        );
        
        extract($defaults);
        extract($args, EXTR_OVERWRITE);

        if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
        $client = new WP_Http();
        
        foreach($types as $type){
            
            if(isset($this->translation_files[$language][$type]['url'])){
            
                $response = $client->request($this->translation_files[$language][$type]['url'], array('timeout'=>15));
                
                if(is_wp_error($response)){
                    $err = __('Error getting the translation file. Please go back and try again.', 'wordpress-language');
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
        }
        
        return $translations;
    }
    
    function save_translations($data, $language, $version = false){
        
       set_time_limit(0);
        
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
        
        
        $version_projects = explode(';', $version);
        foreach($version_projects as $project){
            $exp = explode('|', $project);
            $this->settings['translations'][$language][$exp[0]]['time'] = time();
            $this->settings['translations'][$language][$exp[0]]['installed'] = $exp[1];
        }        
        
        $this->save_settings();
        
    }
    
    
}
  
  
 
  
?>
