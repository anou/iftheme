<?php

class ICLMenusSync{
    public $menus_tree;
    public $menus_translated;
    public $is_preview = false;
    public $sync_data = null;
    
        
    function __construct(){
        
        add_action('init', array($this, 'init'), 20);
        
        $this->menus_translated = $this->get_menus_translated();
        $this->menus_tree = $this->get_menus_tree();
        
        if(isset($_GET['updated'])){
            add_action('admin_notices', array($this, 'admin_notices'));            
        }
        
    }
   
    function init(){
        
        if(isset($_POST['action']) && $_POST['action']=='icl_msync_preview'){
            $this->is_preview = true;    
            $this->sync_data = $_POST;
        }elseif(isset($_POST['action']) && $_POST['action']=='icl_msync_confirm'){
            $this->_do_the_sync($_POST);
            wp_redirect(admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/menus-sync.php&updated=true'));
        }        
        
        $this->deleted_items = $this->_get_deleted_menu_items();
        
    }
    
    function admin_notices(){
        echo '<div class="updated"><p>' . __('Menu(s) syncing complete.', 'sitepress') . '</p></div>';
    }
    
    function get_menus_tree(){
        global $wpdb, $sitepress;
        
        // Get menus in the default language
        $menus_array = array();        
        $menus = $wpdb->get_results($wpdb->prepare("
            SELECT tm.term_id, tm.name FROM {$wpdb->terms} tm 
                JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = tm.term_id
                JOIN {$wpdb->prefix}icl_translations tr ON tr.element_id = tx.term_taxonomy_id AND tr.element_type='tax_nav_menu'
            WHERE tr.language_code=%s
        ", $sitepress->get_default_language()));
        
        // Get elements AND their translations for each menu
        // menu items translations are not necessarily part of the translated menus - fetching all translations for menu items
        foreach($menus as $menu){
            $items_array = array();
            $items = wp_get_nav_menu_items($menu->term_id);

            $icl_element_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'", $menu->term_id));
            $trid           = $sitepress->get_element_trid($icl_element_id, 'tax_nav_menu');
            $menu_translations   = $sitepress->get_element_translations($trid, 'tax_nav_menu');
            unset($menu_translations[$sitepress->get_default_language()]);
            
            foreach($items as $item){
                if($sitepress->is_translated_post_type($item->object)){
                    $ob_type = 'post';                    
                    $tr_title_field = 'post_title';
                }elseif($sitepress->is_translated_taxonomy($item->object)){
                    $ob_type = 'tax';
                    $tr_title_field = 'name';
                }elseif($item->object == 'custom'){
                    continue;
                }
                
                
                $trid           = $sitepress->get_element_trid($item->object_id, $ob_type . '_' . $item->object);
                $translations   = $sitepress->get_element_translations($trid, $ob_type . '_' . $item->object);
                unset($translations[$sitepress->get_default_language()]);
                
                foreach($translations as $lang=>$val){
                    $menu_translated_id = icl_object_id($menu->term_id, 'nav_menu', 0, $lang);
                    
                    $synced = 0;
                    if(isset($this->menus_translated[$lang]) && isset($this->menus_translated[$lang][$menu_translated_id])){
                        
                        foreach($this->menus_translated[$lang][$menu_translated_id]['items'] as $mti){
                            if($mti['object_id'] == $val->element_id && $mti['object'] == $item->object ){
                                $synced = 1;
                                break;
                            }    
                        }
                        
                        $translations[$lang]->synced = $synced;
                        
                    }
                    
                }
                
                //$to_be_added = $menus_translated[$]
                
                $items_array[$item->ID] = array(
                    'title'             => $item->title,
                    'menu_item_parent'  => $item->menu_item_parent,
                    'object_id'         => $item->object_id,
                    'object'            => $item->object,
                    'translations'      => $translations,
                    'tr_title_field'    => $tr_title_field,
                    'item_id'           => $item->ID                    
                );
            }
            
            $menus_array[$menu->term_id] =  array(
                'items'             => $items_array,
                'name'              => $menu->name,
                'translations'      => $menu_translations
            );
        }
        
        return $menus_array;
    }
    
    function get_menus_translated(){
        global $wpdb, $sitepress;
        
        $menus_translated = array();
         
        // Get menus in all translated languages
        foreach($sitepress->get_active_languages() as $lang){ 
         
            if($lang['code'] == $sitepress->get_default_language()) continue;
            
            $menus_array = array();
                
            // Get menus in $lang['code']
            $menus = $wpdb->get_results($wpdb->prepare("
                SELECT tm.term_id, tm.name FROM {$wpdb->terms} tm 
                    JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = tm.term_id
                    JOIN {$wpdb->prefix}icl_translations tr ON tr.element_id = tx.term_taxonomy_id AND tr.element_type='tax_nav_menu'
                WHERE tr.language_code=%s
            ", $lang['code']));
            
            // For each menu, get its elements
            foreach($menus as $menu){
                $items_array = array();
                
                remove_filter('get_term', array($sitepress, 'get_term_adjust_id')); // AVOID filtering to current language                
                $items = wp_get_nav_menu_items($menu->term_id);
                add_filter('get_term', array($sitepress, 'get_term_adjust_id')); // RESTORE filtering to current language
                
                
                foreach($items as $item){
                    $items_array[$item->ID] = array(
                        'title'             => $item->title,
                        'menu_item_parent'  => $item->menu_item_parent,
                        'object_id'         => $item->object_id,
                        'object'            => $item->object,
                        'item_id'           => $item->ID
                    );
                }

                $menus_array[$menu->term_id] =  array(
                    'items'             => $items_array,
                    'name'              => $menu->name                
                );
                
            }    
        
            $menus_translated[$lang['code']] = $menus_array;
            
        }
        
        return $menus_translated;
    }
    
    function _get_deleted_menu_items(){
        global $sitepress;
        
        $res = array() ;
        
        $active_languages = $sitepress->get_active_languages();        
        foreach($active_languages as $lang){
            if($lang['code'] != $sitepress->get_default_language()){
                $secondary_languages[] = $lang;
            }
        }
        
        foreach($this->menus_tree as $menu_id => $menu){
            foreach($secondary_languages as $l){
                
                $translated_menu_id = icl_object_id($menu_id, 'nav_menu', false, $l['code']);
                
                if($translated_menu_id && isset($this->menus_translated[$l['code']][$translated_menu_id]['items'])){
                    foreach($this->menus_translated[$l['code']][$translated_menu_id]['items'] as $item_id => $item){
                        
                        if($item['object'] == 'custom'){
                            continue;
                        }
                        
                        $object_id = $item['object_id'];
                        $original_object_id = icl_object_id($object_id, $item['object'], false, $sitepress->get_default_language());
                        
                        $menus_tree_object_ids = array();
                        foreach($this->menus_tree[$menu_id]['items'] as $i){
                            $menus_tree_object_ids[] = $i['object_id'];
                        }

                        if(!in_array($original_object_id, $menus_tree_object_ids)){
                            $res[$menu_id][$l['code']][$item_id] = $item;
                        }    
                    }
                }
                
            }    
        }
        
        return $res;    
    }
    
    function _are_deleted_items($menu_id){
        $are = false;
        if(isset($this->deleted_items[$menu_id])){
            foreach($this->deleted_items[$menu_id] as $lang => $items){
                if(!empty($items)){
                    $are = true;
                    break;
                }
            }    
        }
        
        return $are;
    }
    
    function _do_the_sync($data){
        global $sitepress, $wpdb;
                             
        if(isset($data['menu_translation']) && is_array($data['menu_translation'])){
            
            foreach($data['menu_translation'] as $menuinfo){
                list($original_menu_id, $language, $menu_name) = explode('#', $menuinfo);
                $menu_name = urldecode($menu_name);
                
                $_POST['icl_translation_of'] = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$original_menu_id));
                $_POST['icl_nav_menu_language'] = $language;
                
                $ming = $minc = '';
                do{
                    $new_menu_id = wp_update_nav_menu_object(0, array('menu-name' => $menu_name . $ming . $minc));
                    $minc = $minc != '' ? $minc + 1 : 2;
                    $ming = '-';
                }while(is_wp_error($new_menu_id) && $minc < 10);
                
                $new_menus[$original_menu_id][$language] = $new_menu_id; 
            }
        }
        
        if(isset($data['del']) && is_array($data['del'])){
            foreach($data['del'] as $iteminfo){
                list($menu_id, $item_id) = explode('#', $iteminfo);
                
                wp_delete_post($item_id, true);
            }
        }
        
        if(isset($data['add']) && is_array($data['add'])){
            
            foreach($data['add'] as $iteminfo){
                list($menu_id, $object_id, $ob_type, $parent_id) = explode('#', $iteminfo);
                
                if(!is_numeric($menu_id)){
                    if(preg_match('#newfrom-([0-9]+)-(.+)#', $menu_id, $matches)){
                        $menu_id =  $new_menus[$matches[1]][$matches[2]];    
                    }
                }
                
                global $wp_post_types;
                if(taxonomy_exists($ob_type)){
                    $menu_obj = get_term($object_id, $ob_type);    
                    $menu_item_type = 'taxonomy';
                }elseif(in_array($ob_type, array_keys($wp_post_types))){
                    $menu_obj = get_post($object_id);    
                    $menu_item_type = 'post_type';
                }
                
                $menu_tax_id = $wpdb->get_var($wpdb->prepare("
                    SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'", $menu_id));
                
                $_ldetails = $sitepress->get_element_language_details($menu_tax_id, 'tax_nav_menu');
                $language = $_ldetails->language_code;
                
                $menudata = array(
                  'menu-item-db-id' => 0,
                  'menu-item-object-id' => $object_id,
                  'menu-item-object' => $ob_type,
                  'menu-item-parent-id' => 0, // we'll fix the hierarchy on a second pass
                  'menu-item-position' => 0,
                  'menu-item-type' => $menu_item_type,
                  'menu-item-title' => '',
                  'menu-item-url' => '',
                  'menu-item-description' => '',
                  'menu-item-attr-title' => '',
                  'menu-item-target' => '',
                  'menu-item-classes' => '',
                  'menu-item-xfn' => '',
                  'menu-item-status' => 'publish',
                 );
                
                remove_filter('get_term', array($sitepress, 'get_term_adjust_id')); // AVOID filtering to current language
                $nav_item_id = wp_update_nav_menu_item($menu_id, 0, $menudata); 

                // set language explicitly since the 'wp_update_nav_menu_item' is still TBD                
                $sitepress->set_element_language_details($nav_item_id, 'post_nav_menu_item', null, $language); 
                
                //
                if($nav_item_id && $menu_tax_id){
                    $rel = $wpdb->get_var($wpdb->prepare("SELECT object_id FROM {$wpdb->term_relationships} WHERE object_id=%d AND term_taxonomy_id=%d", $nav_item_id, $menu_tax_id));
                    if(!$rel){
                        $wpdb->insert($wpdb->term_relationships, array('object_id' => $nav_item_id, 'term_taxonomy_id' => $menu_tax_id));
                    }
                }
                
            }
            
            // check and set hierarchy
            foreach($data['add'] as $iteminfo){
                
                list($menu_id, $object_id, $ob_type, $original_item_parent) = explode('#', $iteminfo);
                
                if(!is_numeric($menu_id)){
                    $exp = explode('-', $menu_id);
                    $menu_id =  $new_menus[$exp[1]][$exp[2]];
                }
                
                global $wp_post_types;
                if(taxonomy_exists($ob_type)){
                    $menu_obj = get_term($object_id, $ob_type);    
                    $menu_item_type = 'taxonomy';
                }elseif(in_array($ob_type, array_keys($wp_post_types))){
                    $menu_obj = get_post($object_id);    
                    $menu_item_type = 'post_type';
                }
                
                if(!$original_item_parent){
                    //get original object
                    $original_object = icl_object_id($object_id, $ob_type, false, $sitepress->get_default_language());
                    // get item id of original object in original menu
                    $original_menu_id = icl_object_id($menu_id, 'nav_menu', false, $sitepress->get_default_language());
                    
                    $original_item_id = $wpdb->get_var($wpdb->prepare("
                        SELECT p.post_id FROM {$wpdb->postmeta}  p
                            JOIN {$wpdb->term_relationships} r ON r.object_id = p.post_id
                            JOIN {$wpdb->term_taxonomy} x ON r.term_taxonomy_id = x.term_taxonomy_id AND taxonomy = 'nav_menu'
                            JOIN {$wpdb->terms} m ON m.term_id = x.term_id
                        WHERE meta_key='_menu_item_object_id' AND meta_value=%d AND m.term_id=%d", $original_object, $original_menu_id));
                    
                    $original_item_parent = get_post_meta($original_item_id, '_menu_item_menu_item_parent', true);
                }
                
                if($original_item_parent){
                    $original_item_parent_object_id = get_post_meta($original_item_parent, '_menu_item_object_id', true);
                    $parent_ob_type = get_post_meta($original_item_parent, '_menu_item_object', true);
                    
                    $menu_tax_id = $wpdb->get_var($wpdb->prepare("
                        SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'", $menu_id));
                    $_ldetails = $sitepress->get_element_language_details($menu_id, 'tax_nav_menu');
                    $language = $_ldetails->language_code;
                    
                    $item_parent_object_id = icl_object_id($original_item_parent_object_id, $parent_ob_type, false, $language);
                    
                    $item_parent = $wpdb->get_var($wpdb->prepare("
                        SELECT p.post_id FROM {$wpdb->postmeta}  p
                            JOIN {$wpdb->term_relationships} r ON r.object_id = p.post_id
                            JOIN {$wpdb->term_taxonomy} x ON r.term_taxonomy_id = x.term_taxonomy_id AND taxonomy = 'nav_menu'
                            JOIN {$wpdb->terms} m ON m.term_id = x.term_id
                        WHERE meta_key='_menu_item_object_id' AND meta_value=%d AND m.term_id=%d
                    ", $item_parent_object_id, $menu_id));
                    
                    $item_id = $wpdb->get_var($wpdb->prepare("
                        SELECT p.post_id FROM {$wpdb->postmeta}  p
                            JOIN {$wpdb->term_relationships} r ON r.object_id = p.post_id
                            JOIN {$wpdb->term_taxonomy} x ON r.term_taxonomy_id = x.term_taxonomy_id AND taxonomy = 'nav_menu'
                            JOIN {$wpdb->terms} m ON m.term_id = x.term_id
                        WHERE meta_key='_menu_item_object_id' AND meta_value=%d AND m.term_id=%d
                    ", $object_id, $menu_id));
                    

                    update_post_meta($item_id, '_menu_item_menu_item_parent', $item_parent);
                }
                
            }
            
        }
    }
    
}
  
?>
