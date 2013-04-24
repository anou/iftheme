<?php

class ICLMenusSync{
    
    var $menus;
    var $is_preview = false;
        
    function __construct(){
        
        add_action('init', array($this, 'init'), 20);
        
        
        if(isset($_GET['updated'])){
            add_action('admin_notices', array($this, 'admin_notices'));            
        }
        
    }
   
    function init(){
        
        $this->get_menus_tree();
        
        if(isset($_POST['action']) && $_POST['action']=='icl_msync_preview'){
            $this->is_preview = true;
            $this->sync_data = isset($_POST['sync']) ? $_POST['sync'] : false ;
        }elseif(isset($_POST['action']) && $_POST['action']=='icl_msync_confirm'){
            $this->do_sync($_POST['sync']);
        }
        
        
    }
    
    function get_menus_tree(){
        global $sitepress, $wpdb;
        
        $menus = $wpdb->get_results($wpdb->prepare("
            SELECT tm.term_id, tm.name FROM {$wpdb->terms} tm 
                JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = tm.term_id
                JOIN {$wpdb->prefix}icl_translations tr ON tr.element_id = tx.term_taxonomy_id AND tr.element_type='tax_nav_menu'
            WHERE tr.language_code=%s
        ", $sitepress->get_default_language()));
        
        if($menus){
        
            foreach($menus as $menu){
                $this->menus[$menu->term_id] = array(
                    'name'          => $menu->name,
                    'items'         => $this->get_menu_items($menu->term_id),
                    'translations'  => $this->get_menu_translations($menu->term_id)
                );
            }
            
            $this->add_ghost_entries();
            
            $this->set_new_menu_order();
            
        }
        
    }
    
    function get_menu_items($menu_id, $translations = true){
        $items = wp_get_nav_menu_items($menu_id);   

        $menu_items = array();
        
        foreach($items as $item){
            $item->object_type = get_post_meta($item->ID, '_menu_item_type', true);
            $_itemadd = array(
                'ID'            => $item->ID,
                'menu_order'    => $item->menu_order,
                'parent'        => $item->menu_item_parent,
                'object'        => $item->object,
                'object_type'   => $item->object_type,
                'object_id'     => $item->object_id,
                'title'         => $item->title,
                'depth'         => $this->get_menu_item_depth($item->ID)
            );              
            if($translations){
                $_itemadd['translations'] = $this->get_menu_item_translations($item);
            }
            $menu_items[$item->ID] = $_itemadd;
        }
        
        return $menu_items;
         
    }
    
    function get_menu_item_depth($item_id){
        global $wpdb;
        
        $depth = 0;
        
        do{           
           $item = get_post($item_id); 
           $object_parent = get_post_meta($item_id, '_menu_item_menu_item_parent', true);
           
           if($object_parent){
               $item_id = $object_parent;
               $depth++;                    
           }
            
        }while($object_parent > 0);
        
        return $depth;        
    }
     
    function get_menu_translations($menu_id){
        global $sitepress, $wpdb;
        $languages = $sitepress->get_active_languages();
        
        $translations = false;
        
        foreach($languages as $language){
            if($language['code'] != $sitepress->get_default_language()){
               
                $menu_translated_id = icl_object_id($menu_id, 'nav_menu', 0, $language['code']);
                $menu_data = array();
                if($menu_translated_id){                    
                    $menu_data['items'] = $this->get_menu_items($menu_translated_id, false);
                    //$menu_object = get_term($menu_translated_id, 'nav_menu');
                    $menu_object = $wpdb->get_row($wpdb->prepare("
                        SELECT t.term_id, t.name, t.slug, t.term_group, x.term_taxonomy_id, x.taxonomy, x.description, x.parent, x.count
                        FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON t.term_id = t.term_id AND x.taxonomy='nav_menu'
                        WHERE t.term_id = %d
                    ", $menu_translated_id));
                    $menu_data['id']    = $menu_object->term_id;
                    $menu_data['name']  = $menu_object->name;                    
                }
                $translations[$language['code']] = $menu_data;
            }
        }        
        
        return $translations;
    }
    
    function get_menu_item_translations($item){
        global $sitepress, $wpdb;
        $languages = $sitepress->get_active_languages();
        
        foreach($languages as $language){
            if($language['code'] != $sitepress->get_default_language()){
                
                $translation = false;                
                
                // Does the object of this item have a translation?                
                $translated_object_id = icl_object_id($item->object_id, $item->object, false, $language['code']);
                
                // Is the object corresponding to the parent item translated?
                $parent_not_translated = 0;
                if($item->menu_item_parent > 0){
                    $item_parent_object_id  = get_post_meta($item->menu_item_parent, '_menu_item_object_id', true);
                    $item_parent_object     = get_post_meta($item->menu_item_parent, '_menu_item_object', true);                
                    $parent_translated = icl_object_id($item_parent_object_id, $item_parent_object, false, $language['code']);
                    if(empty($parent_translated)){
                        $parent_not_translated = 1;        
                    }
                }
                
                if($translated_object_id){

                    if($item->object_type == 'post_type'){
                        $translated_object = get_post($translated_object_id);
                        if($translated_object->post_status == 'trash'){
                            $translated_object_id = $translated_object = false;    
                        }else{
                            $translated_object_title = $translated_object->post_title;    
                        }                        
                    }elseif($item->object_type == 'taxonomy'){
                        $taxonomy = get_post_meta($item->ID, '_menu_item_object', true);
                        $translated_object = get_term($translated_object_id, $taxonomy);                            
                        $translated_object_title = $translated_object->name;
                    }else{
                        // CUSTOM
                        // continue;
                        $translated_object = false;
                        $translated_object_title = '';
                    }
                    
                    // Is there a corresponding menu item for the translated object (menu item equivalent)
                    $translated_item_id = $wpdb->get_var($wpdb->prepare("
                        SELECT  p1.post_id FROM {$wpdb->postmeta} p1, {$wpdb->postmeta} p2                        
                        WHERE   p1.post_id = p2.post_id AND p1.meta_key = '_menu_item_object_id' AND 
                                p2.meta_key='_menu_item_object' AND p2.meta_value = %s AND
                                p1.meta_value=%d ORDER BY p1.post_id DESC LIMIT 1", $item->object, $translated_object_id));
                    // 
                    
                    
                    if($translated_item_id){
                        
                        $translated_item = get_post($translated_item_id); // get details fo ritem
                        $translated_item_title = !empty($translated_item->post_title) ? $translated_item->post_title : $translated_object_title;
                        
                        $translation = array(
                            'ID'                => $translated_item_id,
                            'menu_order'        => $translated_item->menu_order,
                            'parent'            => intval(get_post_meta($translated_item_id, '_menu_item_menu_item_parent', true)),
                            'object'            => $item->object,
                            'object_type'       => $item->object_type,
                            'object_id'         => $translated_object_id,
                            'title'             => $translated_item_title,
                            'parent_not_translated' => $parent_not_translated,
                            'depth'             => $this->get_menu_item_depth($translated_item_id)
                            
                        );
                    }else{
                        $translation = array(
                            'ID'            => false,
                            'menu_order'    => 0,
                            'parent'        => 0,
                            'object'        => $item->object,
                            'object_type'   => $item->object_type,
                            'object_id'     => $translated_object_id,
                            'title'         => $translated_object_title,
                            'parent_not_translated' => $parent_not_translated
                        );
                    }
                    
                    
                }
                
                
                $translations[$language['code']] = $translation; 
                
            }
        }
        
        return $translations;
    }
    
    function render_items_tree_default($menu_id, $parent = 0, $depth = 0){
        global $sitepress;
                
        foreach($this->menus[$menu_id]['items'] as $idx => $item){
            
            
            // deleted items #2 (menu order beyond)
            static $d2_items = array();
            $deleted_items = array();
            foreach($this->menus[$menu_id]['translations'] as $language => $tmenu){
                
                if(!isset($d2_items[$language])) $d2_items[$language] = array();
                
                if(!empty($this->menus[$menu_id]['translations'][$language]['deleted_items'])){
                    foreach($this->menus[$menu_id]['translations'][$language]['deleted_items'] as $deleted_item){
                        if(!in_array($deleted_item['ID'], $d2_items[$language]) && $deleted_item['menu_order'] > count($this->menus[$menu_id]['items'])){     
                            $deleted_items[$language][] = $deleted_item;      
                            $d2_items[$language][] = $deleted_item['ID'];                      
                        }                        
                    }
                    
                }
            }            
            if($deleted_items){ 
                ?>
                <tr>
                    <td>&nbsp;</td>
                    <?php foreach($sitepress->get_active_languages() as $language): if($language['code']==$sitepress->get_default_language()) continue; ?>
                    <td>
                        <?php if(isset($deleted_items[$language['code']])): ?>
                        <?php foreach($deleted_items[$language['code']] as $deleted_item): ?>
                        <?php echo str_repeat(' - ', $depth) ?><span class="icl_msync_item icl_msync_del"><?php echo $deleted_item['title'] ?></span>
                        <input type="hidden" name="sync[del][<?php echo $menu_id ?>][<?php echo $language['code'] ?>][<?php echo $deleted_item['ID'] ?>]" value="<?php echo esc_attr($deleted_item['title']) ?>" />
                        <?php $this->operations['del'] = empty($this->operations['del']) ? 1 : $this->operations['del']++; ?>
                        <br />
                        <?php endforeach;?>
                        <?php else: ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>                        
                <?php
            }
            
            // show deleted item?
            static $mo_added = array();
            $deleted_items = array();
            foreach($this->menus[$menu_id]['translations'] as $language => $tmenu){
                
                if(!isset($mo_added[$language])) $mo_added[$language] = array();
                
                if(!empty($this->menus[$menu_id]['translations'][$language]['deleted_items'])){
                    foreach($this->menus[$menu_id]['translations'][$language]['deleted_items'] as $deleted_item){
                        
                        if(!in_array($item['menu_order'], $mo_added[$language]) && $deleted_item['menu_order'] == $item['menu_order']){
                            $deleted_items[$language] = $deleted_item;
                            $mo_added[$language][] = $item['menu_order'];                            
                        }
                        
                    }
                }
            }
            
            if($deleted_items){
                ?>
                <tr>
                    <td>&nbsp;</td>
                    <?php foreach($sitepress->get_active_languages() as $language): if($language['code']==$sitepress->get_default_language()) continue; ?>
                    <td>
                        <?php if(isset($deleted_items[$language['code']])): ?>
                        <?php echo str_repeat(' - ', $depth) ?><span class="icl_msync_item icl_msync_del"><?php echo $deleted_items[$language['code']]['title'] ?></span>
                        <input type="hidden" name="sync[del][<?php echo $menu_id ?>][<?php echo $language['code'] ?>][<?php echo $deleted_items[$language['code']]['ID'] ?>]" value="<?php echo esc_attr($deleted_items[$language['code']]['title']) ?>" />
                        <?php $this->operations['del'] = empty($this->operations['del']) ? 1 : $this->operations['del']++; ?>
                        <?php else: ?>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>                        
                <?php
            }
            
            if($item['parent'] == $parent){
                ?>
                <tr>
                    <td><?php 
                        echo str_repeat(' - ', $depth) . $item['title'];
                    ?></td>
                    <?php foreach($sitepress->get_active_languages() as $language): if($language['code']==$sitepress->get_default_language()) continue; ?>
                    <td>
                        <?php 
                                                        
                           
                            $item_translation = $item['translations'][$language['code']];
                            echo str_repeat(' - ', $depth);
                            
                            if(!empty($item_translation['ID'])){ 
                                // item translation exists
                                if($item_translation['menu_order'] != $item_translation['menu_order_new'] || $item_translation['depth'] != $item['depth']){   // MOVED
                                    echo '<span class="icl_msync_item icl_msync_mov">' . $item_translation['title'] . '</span>';
                                    echo '<input type="hidden" name="sync[mov]['.$menu_id.'][' . $item['ID'] . '][' . $language['code'] . ']['.$item_translation['menu_order_new'].']" value="' . esc_attr($item_translation['title']) . '" />';
                                    $this->operations['mov'] = empty($this->operations['mov']) ? 1 : $this->operations['mov']++;                                    
                                }else{ // NO CHANGE
                                    echo $item_translation['title'];
                                }
                                
                            }elseif(!empty($item_translation['object_id'])){ 
                                // item translation does not exist but translated object does 
                                if($item_translation['parent_not_translated']){
                                    echo '<span class="icl_msync_item icl_msync_not">' . $item_translation['title'] . '</span>';                                    
                                    $this->operations['not'] = empty($this->operations['not']) ? 1 : $this->operations['not']++;
                                }else{
                                    // item translation does not exist but translated object does 
                                    echo '<span class="icl_msync_item icl_msync_add">' . $item_translation['title'] . '</span>';
                                    echo '<input type="hidden" name="sync[add]['.$menu_id.'][' . $item['ID'] . '][' . $language['code'] . ']" value="' . esc_attr($item_translation['title']) . '" />';
                                    $this->operations['add'] = empty($this->operations['add']) ? 1 : $this->operations['add']++;
                                }
                            }else{ 
                                // item translation and object translation do not exist
                                echo '<i class="inactive">' . __('Not translated', 'sitepress') . '</i>';
                            }
                            
                        ?>
                        
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php
                
                if($this->_item_has_children($menu_id, $item['ID'])){
                    $this->render_items_tree_default($menu_id, $item['ID'], $depth + 1);    
                }
                
            }
                        
        }
        
        
    }
    
    function _item_has_children($menu_id, $item_id){
        $has = false;
        foreach($this->menus[$menu_id]['items'] as $item){
            if($item['parent'] == $item_id){
                $has = true;
            }
        }    
        return $has;
    }
       
    function get_item_depth($menu_id, $item_id){
        $depth = 0;
        
        do{
            foreach($this->menus[$menu_id]['items'] as $item){
                if($item['ID'] == $item_id){
                    $parent = $item['parent'];
                    if($parent > 0){
                        $depth++;
                        $item_id = $parent;
                    }else{
                        break;
                    }
                }
            }    
        } while($parent > 0);
        
        return $depth;
        
    }
    
    function add_ghost_entries(){
        
        if(is_array($this->menus)){
            foreach($this->menus as $menu_id => $menu){
                
                foreach($menu['translations'] as $language => $tmenu){                
                    if(!empty($tmenu)){
                        
                        $last_found_position = -1;
                        
                        foreach($tmenu['items'] as $titem){
                            
                            // has a place in the default menu?                        
                            $exists = false;
                            foreach($this->menus[$menu_id]['items'] as $item){
                                if($item['translations'][$language]['ID'] == $titem['ID']){
                                    $exists = true;
                                }
                            }
                            
                            if(!$exists){
                                $this->menus[$menu_id]['translations'][$language]['deleted_items'][] = array(                            
                                    'ID'            => $titem['ID'],
                                    'title'         => $titem['title'],
                                    'menu_order'    => $titem['menu_order']
                                );
                            }
                        }
                    }
                }
            }
        }
    }
   
    function set_new_menu_order(){
        
        $menu_index_by_lang = array();                
        
        if(is_array($this->menus)){
            foreach($this->menus as $menu_id => $menu){
                foreach($menu['items'] as $item_id => $item){
                    foreach($item['translations'] as $language => $item_translation){
                        if($item_translation['ID']){
                            $new_menu_order = empty($menu_index_by_lang[$language]) ? 1 : $menu_index_by_lang[$language]+1;
                            $menu_index_by_lang[$language] = $new_menu_order;
                            $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['menu_order_new'] = $new_menu_order;                            
                        }
                    }
                }
            }
        }
        
    }

    function do_sync($data){
        global $wpdb, $sitepress;
            
        // menu translations        
        if(!empty($data['menu_translation'])){
            foreach($data['menu_translation'] as $menu_id => $translations){
                foreach($translations as $language => $name){
                    
                    $_POST['icl_translation_of'] = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$menu_id));
                    $_POST['icl_nav_menu_language'] = $language;
                    
                    $ming = $minc = '';
                    do{
                        $new_menu_id = wp_update_nav_menu_object(0, array('menu-name' => $name . $ming . $minc));
                        $minc = $minc != '' ? $minc + 1 : 2;
                        $ming = '-';
                    }while(is_wp_error($new_menu_id) && $minc < 10);
                    
                    $this->menus[$menu_id]['translations'][$language] = array(
                        'id' => $new_menu_id
                    );
                    
                }
            }
            
        }        
        
        // deleting items
        if(!empty($data['del'])){
            foreach($data['del'] as $menu_id => $languages){
                foreach($languages as $language => $items){
                    foreach($items as $item_id => $name){
                        wp_delete_post($item_id, true);    
                    }
                }
            }
        }
        
        if(!empty($data['mov'])){
            
            foreach($data['mov'] as $menu_id => $items){
                foreach($items as $item_id =>$changes){
                    foreach($changes as $language => $details){
                        $translated_item_id = $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['ID'];
                        
                        $new_menu_order = key($details);
                        $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['menu_order'] = $new_menu_order;    
                        
                        $wpdb->update($wpdb->posts, 
                            array('menu_order' => $new_menu_order), 
                            array('ID' => $translated_item_id));
                        
                    }
                }
                
            }
            
            // fix hierarchy
            foreach($data['mov'] as $menu_id => $items){
                foreach($items as $item_id =>$changes){
                    
                    $object    = get_post_meta($item_id, '_menu_item_object', true);
                    $parent_item    = get_post_meta($item_id, '_menu_item_menu_item_parent', true);
                    $parent_object  = get_post_meta($parent_item, '_menu_item_object_id', true);
                    
                    foreach($changes as $language => $details){                        
                        
                        $translated_item_id = $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['ID'];
                        $translated_parent_object_id = icl_object_id($parent_object, $object, false, $language);                                                     
                        $translated_parent_item_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_menu_item_object_id' AND meta_value=%d", $translated_parent_object_id));
                        
                        update_post_meta($translated_item_id, '_menu_item_menu_item_parent', $translated_parent_item_id);
                        
                    }
                }                
            }
            
        }
        
        // adding items
        if(!empty($data['add'])){
            foreach($data['add'] as $menu_id => $items){
                
                foreach($items as $item_id => $translations){

                    foreach($translations as $language => $name){
                        $translated_object = $this->menus[$menu_id]['items'][$item_id]['translations'][$language];
                        
                        $menudata = array(
                          'menu-item-db-id' => 0,
                          'menu-item-object-id' => $translated_object['object_id'],
                          'menu-item-object' => $translated_object['object'],
                          'menu-item-parent-id' => 0, // we'll fix the hierarchy on a second pass
                          'menu-item-position' => 0,
                          'menu-item-type' => $translated_object['object_type'],
                          'menu-item-title' => '',
                          'menu-item-url' => '',
                          'menu-item-description' => '',
                          'menu-item-attr-title' => '',
                          'menu-item-target' => '',
                          'menu-item-classes' => '',
                          'menu-item-xfn' => '',
                          'menu-item-status' => 'publish',
                         );

                         $translated_menu_id = $this->menus[$menu_id]['translations'][$language]['id'];
                         
                         remove_filter('get_term', array($sitepress, 'get_term_adjust_id')); // AVOID filtering to current language
                         $translated_item_id = wp_update_nav_menu_item($translated_menu_id, 0, $menudata); 
                         
                         // set language explicitly since the 'wp_update_nav_menu_item' is still TBD                
                         $sitepress->set_element_language_details($translated_item_id, 'post_nav_menu_item', null, $language); 

                        //
                        $menu_tax_id = $wpdb->get_var($wpdb->prepare("
                            SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'", $translated_menu_id));
                        
                        if($translated_item_id && $menu_tax_id){
                            $rel = $wpdb->get_var($wpdb->prepare("SELECT object_id FROM {$wpdb->term_relationships} WHERE object_id=%d AND term_taxonomy_id=%d", $translated_item_id, $menu_tax_id));
                            if(!$rel){
                                $wpdb->insert($wpdb->term_relationships, array('object_id' => $translated_item_id, 'term_taxonomy_id' => $menu_tax_id));
                            }
                        }
                        
                        $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['ID'] = $translated_item_id;
                            
                    }
                    
                }
                
            }
            
            // set/fix hierarchy
            foreach($data['add'] as $menu_id => $items){
                foreach($items as $item_id => $translations){
                    foreach($translations as $language => $name){
                        
                        $item_parent = $this->menus[$menu_id]['items'][$item_id]['parent'];
                        if($item_parent){
                            $parent_object_id               = $this->menus[$menu_id]['items'][$item_parent]['object_id'];                            
                            $parent_object                  = $this->menus[$menu_id]['items'][$item_parent]['object'];
                            $translated_parent_object_id    = icl_object_id($parent_object_id, $parent_object, false, $language);

                            if($translated_parent_object_id){
                                $translated_parent_item_id = $wpdb->get_var($wpdb->prepare("
                                    SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_menu_item_object_id' AND meta_value=%d ORDER BY meta_id DESC LIMIT 1",
                                                             $translated_parent_object_id));
                                $translated_item_id = $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['ID'];
                                update_post_meta($translated_item_id, '_menu_item_menu_item_parent', $translated_parent_item_id);        
                            }
                        }                        
                    }
                }
            }
            
        }
        
        // set menu order
        foreach($this->menus as $menu_id => $menu){
            
            $menu_index_by_lang = array();                
            foreach($menu['items'] as $item_id => $item){
                $menu_order = $item['menu_order'];
                foreach($item['translations'] as $language => $item_translation){
                    if($item_translation['ID']){
                        $new_menu_order = empty($menu_index_by_lang[$language]) ? 1 : $menu_index_by_lang[$language]+1;
                        $menu_index_by_lang[$language] = $new_menu_order;
                        if($new_menu_order != $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['menu_order']){
                            $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['menu_order'] = $new_menu_order;    
                            $wpdb->update($wpdb->posts, 
                                array('menu_order' => $this->menus[$menu_id]['items'][$item_id]['translations'][$language]['menu_order']), 
                                array('ID' => $item_translation['ID']));
                        }
                    }
                }
            }
        }
        
        
        wp_redirect(admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/menus-sync.php&updated=1'));
        exit;
        
    }
    
    function admin_notices(){
        echo '<div class="updated"><p>' . __('Menu(s) syncing complete.', 'sitepress') . '</p></div>';
    }
    
    
    
    
   
}
  
?>
