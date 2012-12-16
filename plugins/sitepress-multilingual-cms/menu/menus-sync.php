<?php 
    $active_languages = $sitepress->get_active_languages();
    $default_language_details = $sitepress->get_language_details($sitepress->get_default_language());
    foreach($active_languages as $lang){
        if($lang['code'] != $default_language_details['code']){
            $secondary_languages[] = $lang;
        }
    }
?>
<div class="wrap">
    <div id="icon-wpml" class="icon32" ><br /></div>
    <h2><?php echo __( 'WP Menus Sync', 'sitepress' ) ?></h2>
    
    <?php if($icl_menus_sync->is_preview): ?>
        
        
        <form id="icl_msync_confirm_form" method="post">
        <input type="hidden" name="action" value="icl_msync_confirm" />

        <table id="icl_msync_confirm" class="widefat icl_msync">
            <thead>
                <tr>
                    <th scope="row" class="check-column"><input type="checkbox" /></th>
                    <th><?php _e('Language', 'sitepress') ?></th>
                    <th><?php _e('Action', 'sitepress') ?></th>
                </tr>
            </thead>  
            <tbody>
            
            <?php if(empty($icl_menus_sync->sync_data['menu_sync']) && empty($icl_menus_sync->sync_data['menu_item_sync'])): ?>
                <tr><td align="center" colspan="3"><?php _e('Nothing to sync.', 'sitepress')?></td></tr>
            <?php else: ?>
            
            
                <?php if(isset($icl_menus_sync->sync_data['menu_sync'])): foreach($icl_menus_sync->sync_data['menu_sync'] as $key=>$action):?>
                    <?php 
                        list($original_menu_id, $language) = explode('#', $key);
                        $lang_details = $sitepress->get_language_details($language);
                    ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="menu_translation[]" value="<?php echo $original_menu_id ?>#<?php echo $language ?>#<?php echo urlencode($icl_menus_sync->sync_data['menu_new_name'][$original_menu_id.'#'.$language])?>" /></th>
                        <td><?php echo $lang_details['display_name']; ?></td>
                        <td><?php printf(__('Add %s translation for the menu %s. Menu name: %s', 'sitepress'), 
                            $lang_details['display_name'], '<strong>' . $icl_menus_sync->menus_tree[$original_menu_id]['name']. '</strong>', 
                            '<strong>' . $icl_menus_sync->sync_data['menu_new_name'][$original_menu_id.'#'.$language] . '</strong>') ; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                
                <?php if(isset($icl_menus_sync->sync_data['menu_item_sync'])) 
                    foreach($icl_menus_sync->sync_data['menu_item_sync'] as $key=>$action): ?>
                    <?php                         
                        list($original_menu_id, $language, $menu_object_id, $menu_object_title, $ob_type) = explode('#', $key);
                        $menu_object_title = urldecode($menu_object_title);
                        
                        $lang_details = $sitepress->get_language_details($language);
                        $translated_menu_id = icl_object_id($original_menu_id, 'nav_menu', false, $language);
                        
                        if(!empty($translated_menu_id)){
                            remove_filter('get_term', array($sitepress, 'get_term_adjust_id')); // AVOID filtering to current language
                            $translated_menu = get_term_by('id', $translated_menu_id, 'nav_menu');    
                            add_filter('get_term', array($sitepress, 'get_term_adjust_id')); // RESTORE filtering to current language
                            $translated_menu = $translated_menu->name;
                        }else{
                            $translated_menu = $icl_menus_sync->sync_data['menu_new_name'][$original_menu_id.'#'.$language];    
                            $translated_menu_id = 'newfrom-' . $original_menu_id . '-' . $language;
                        } 
                        $original_menu_object_id = icl_object_id($menu_object_id, $ob_type, false, $sitepress->get_default_language());                        
                        $original_menu_item_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_menu_item_object_id' AND meta_value = %d", $original_menu_object_id));
                        $original_menu_item_parent_id = $wpdb->get_var($wpdb->prepare("
                            SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_menu_item_menu_item_parent' AND post_id = %d", $original_menu_item_id));                        
                        $original_menu_item_parent_object_id = $wpdb->get_var($wpdb->prepare("
                            SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_menu_item_object_id' AND post_id = %d", $original_menu_item_parent_id));                        
                            
                        $menu_item_parent_object_id = icl_object_id($original_menu_item_parent_object_id, $ob_type, false, $language);
                            
                        if($original_menu_item_parent_object_id && !$menu_item_parent_object_id){ // parent exits but not yet translated
                            $menu_item_parent_title = sprintf(__('translation of: %s', 'sitepress'), $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $original_menu_item_parent_object_id)));
                            $menu_item_parent_object_id = true; // parent exists. don't know the ID at this point
                        }else{
                            $menu_item_parent_title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $menu_item_parent_object_id));    
                        }
                        
                        if($menu_item_parent_object_id){
                            $under = '<strong>' . $menu_item_parent_title . '</strong>' . ' (<strong>' . $translated_menu . '</strong>)' ;    
                        }else{
                            $under = '<strong>' . $translated_menu . '</strong>';
                        }
                        
                    ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="<?php echo $action ?>[]" value="<?php echo $translated_menu_id ?>#<?php echo $menu_object_id ?>#<?php echo $ob_type ?>#<?php echo $original_menu_item_parent_id ?>" /></th>
                        <td><?php echo $lang_details['display_name']; ?></td>
                        <td><?php 
                            switch($action){
                                case 'add':
                                    printf(__('Add %s under %s', 'sitepress'), '<strong>'.$menu_object_title.'</strong>', $under );
                                    break;                            
                                case 'del':
                                    printf(__('Delete %s from %s', 'sitepress'), '<strong>'.$menu_object_title.'</strong>', $under);
                                    break;                                                            
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>  
                
            <?php endif; ?>              
            </tbody>
        </table>
        
        <p class="submit">
            <input id="icl_msync_submit" class="button-primary" type="submit" value="<?php _e('Apply changes') ?>" disabled="disabled" />&nbsp;
            <input id="icl_msync_cancel" class="button-secondary" type="button" value="<?php _e('Cancel') ?>" />
        </p>  
        
        </form>
        
    <?php elseif(!empty($icl_menus_sync->menus_tree)): ?> 
     
        <form method="post" action="">
        <input type="hidden" name="action" value="icl_msync_preview" />
        <table class="widefat icl_msync">
            <thead>
                <tr>
                    <th><?php echo $default_language_details['display_name']; ?></th>
                    <?php foreach( $secondary_languages as $lang ): ?>
                    <th><?php echo $lang['display_name']; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $icl_menus_sync->menus_tree as  $menu_id => $menu): ?>
                <tr class="icl_msync_menu_title">
                    <td><strong><?php echo $menu['name'] ?></strong></td>
                    <?php foreach($secondary_languages as $l): ?>
                        <td>
                        <?php if(isset($menu['translations'][$l['code']]->name)): // menu is translated in $l[code] ?>
                            <?php echo $menu['translations'][$l['code']]->name; ?>
                        <?php else: ?>
                            <input type="text" name="menu_new_name[<?php echo $menu_id ?>#<?php echo $l['code'] ?>]" class="icl_msync_add" value="<?php echo esc_attr($menu['name']) . ' - ' . $l['display_name'] ?>" />
                            <small><?php _e('Auto-generated title. Edit to change.', 'sitepress') ?></small>
                            <input type="hidden" name="menu_sync[<?php echo $menu_id ?>#<?php echo $l['code'] ?>]" value="add" />
                        <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php foreach( $menu['items'] as  $menu_item): $tr_title_field = $menu_item['tr_title_field']; ?>
                <tr>
                    <td><?php echo $menu_item['title'] ?></td>
                    <?php foreach($secondary_languages as $l): ?>
                        <td>
                        <?php 
                            if(!isset($menu_item['translations'][$l['code']]) || !isset($menu_item['translations'][$l['code']]->$tr_title_field)){
                                echo '<i class="inactive">' . __('Not translated', 'sitepress') . '</i>';
                            }else{
                                if(empty($menu_item['translations'][$l['code']]->synced)) echo '<span class="icl_msync_add">';
                                echo $menu_item['translations'][$l['code']]->$tr_title_field; 
                                if(empty($menu_item['translations'][$l['code']]->synced)) echo '</span>';
                                if(empty($menu_item['translations'][$l['code']]->synced)){
                                    if($sitepress->is_translated_post_type($menu_item['object'])){
                                        //$ob_type = 'post';
                                        $tr_title_field = 'post_title';
                                    }elseif($sitepress->is_translated_taxonomy($menu_item['object'])){
                                        //$ob_type = $wpdb->get_var($wpdb->prepare("SELECT taxonomy FROM {$wpdb->term_taxonomy} x JOIN {$wpdb->terms} t ON t.term_id = x.term_id WHERE t.term_id=%d AND "))
                                        //$ob_type = 'tax';
                                        $tr_title_field = 'name';
                                    } 
                                    echo '<input type="hidden" name="menu_item_sync['.$menu_id.'#'.$l['code'].'#'.$menu_item['translations'][$l['code']]->element_id.'#'. esc_attr(urlencode($menu_item['translations'][$l['code']]->$tr_title_field)) . '#' . $menu_item['object'] .']" value="add" />';    
                                }
                            }
                        ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php // deleted items ? ?>                           
                <?php if($icl_menus_sync->_are_deleted_items($menu_id)): ?>                
                <tr>
                    <td>&nbsp;</td>
                    <?php foreach($secondary_languages as $l): ?>
                    <td>                                                              
                    <?php if(isset($icl_menus_sync->deleted_items[$menu_id][$l['code']])) foreach($icl_menus_sync->deleted_items[$menu_id][$l['code']] as $item_id => $item): ?>
                        <span class="icl_msync_del"><?php echo $item['title']?></span><br />
                        <input type="hidden" name="menu_item_sync[<?php echo $menu_id ?>#<?php echo $l['code']?>#<?php echo $item['item_id']; // the menu item id ?>#<?php echo $item['title']?>#<?php echo $item['object'] ?>]" value="del" />
                    <?php endforeach; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endif; ?>
                
                
                <?php endforeach; ?>
            </tbody>
            
        </table>

        <p class="submit">
            <input id="icl_msync_sync" type="submit" class="button-primary" value="<?php _e('Sync', 'sitepress')?>" />
        </p>
    
    <?php else: //if(!empty($icl_menus_sync->menus_tree)) ?> 
        <center><?php _e('No menus found', 'sitepress'); ?></center>
    <?php endif; ?>
    
    </form>
    
    <?php do_action('icl_menu_footer'); ?>
</div>
