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
    
    <br />
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
            
            <?php if(empty($icl_menus_sync->sync_data)): ?>
                <tr><td align="center" colspan="3"><?php _e('Nothing to sync.', 'sitepress')?></td></tr>
            <?php else: ?>

                <?php foreach($icl_menus_sync->menus as $menu_id => $menu): ?>
                <?php if(
                            isset($icl_menus_sync->sync_data['menu_translations'][$menu_id]) || 
                            isset($icl_menus_sync->sync_data['add'][$menu_id]) || 
                            isset($icl_menus_sync->sync_data['del'][$menu_id]) || 
                            isset($icl_menus_sync->sync_data['mov'][$menu_id])
                        ): ?>
                <tr class="icl_msync_menu_title"><td colspan="3"><?php echo $menu['name'] ?></td></tr>
                
                <?php // Display actions per menu ?>
                <?php // menu translations ?>
                <?php if(isset($icl_menus_sync->sync_data['menu_translations'])): foreach($icl_menus_sync->sync_data['menu_translations'][$menu_id] as $language => $name):?>
                <?php $lang_details = $sitepress->get_language_details($language);?>
                <tr>
                    <th scope="row" class="check-column"><input type="checkbox" name="sync[menu_translation][<?php echo $menu_id ?>][<?php echo $language ?>]" value="<?php echo esc_attr($name) ?>" /></th>
                    <td><?php echo $lang_details['display_name']; ?></td>
                    <td><?php printf(__('Add menu translation:  %s', 'sitepress'), '<strong>' . $name . '</strong>'); ?> </td> 
                </tr>
                <?php endforeach; endif; ?>
                <?php // items translations / add ?>
                <?php if(isset($icl_menus_sync->sync_data['add'][$menu_id])): foreach($icl_menus_sync->sync_data['add'][$menu_id] as $item_id => $languages):?>
                <?php foreach($languages as $language => $name):?>
                <?php $lang_details = $sitepress->get_language_details($language);?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="sync[add][<?php echo $menu_id ?>][<?php echo $item_id ?>][<?php echo $language ?>]" value="<?php echo esc_attr($name) ?>" />
                    </th>
                    <td><?php echo $lang_details['display_name']; ?></td>
                    <td><?php 
                        echo str_repeat(' - ', $icl_menus_sync->get_item_depth($menu_id, $item_id));
                        printf(__('Add %s', 'sitepress'), '<strong>' . $name . '</strong>'); 
                    ?> </td> 
                </tr>
                <?php endforeach; ?>
                <?php endforeach; endif; ?>

                <?php // items translations / mov ?>
                <?php if(isset($icl_menus_sync->sync_data['mov'][$menu_id])): foreach($icl_menus_sync->sync_data['mov'][$menu_id] as $item_id => $changes):?>
                <?php foreach($changes as $language => $details):?>
                <?php 
                    $lang_details = $sitepress->get_language_details($language);
                    $new_menu_order = key($details);
                    $name = current($details);
                ?>                
                <tr>
                    <th scope="row" class="check-column">
                        <input type="hidden" name="sync[mov][<?php echo $menu_id ?>][<?php echo $item_id ?>][<?php echo $language ?>][<?php echo $new_menu_order ?>]" value="<?php echo esc_attr($name) ?>" />
                    </th>
                    <td><?php echo $lang_details['display_name']; ?></td>
                    <td><?php 
                        echo str_repeat(' - ', $icl_menus_sync->get_item_depth($menu_id, $item_id));
                        printf(__('Change menu order for %s', 'sitepress'), '<strong>' . $name . '</strong>'); 
                    ?> </td> 
                </tr>
                <?php endforeach; ?>
                <?php endforeach; endif; ?>
                
                <?php // items translations / del ?>
                <?php if(isset($icl_menus_sync->sync_data['del'][$menu_id])): foreach($icl_menus_sync->sync_data['del'][$menu_id] as $language => $items):?>
                <?php foreach($items as $item_id => $name):?>
                <?php $lang_details = $sitepress->get_language_details($language);?>
                <tr>
                    <th scope="row" class="check-column">
                        <input type="checkbox" name="sync[del][<?php echo $menu_id ?>][<?php echo $language ?>][<?php echo $item_id ?>]" value="<?php echo esc_attr($name) ?>" />
                    </th>
                    <td><?php echo $lang_details['display_name'];?></td>
                    <td><?php 
                        printf(__('Remove %s', 'sitepress'), '<strong>' . $name . '</strong>'); 
                    ?> </td> 
                </tr>
                <?php endforeach; ?>
                <?php endforeach; endif; ?>
                
                
                <?php endif; ?>
                <?php endforeach; ?>
                
            <?php endif; ?>
            
            </tbody>
        </table>
                           
        <p class="submit">
            <input id="icl_msync_submit" class="button-primary" type="submit" value="<?php _e('Apply changes') ?>" <?php 
                if(empty($icl_menus_sync->sync_data) || (empty($icl_menus_sync->sync_data['mov']) && empty($icl_menus_sync->sync_data['mov'][$menu_id]))): ?>disabled="disabled"<?php endif; ?> />&nbsp;
            <input id="icl_msync_cancel" class="button-secondary" type="button" value="<?php _e('Cancel') ?>" />
        </p>  
                    
        </form>    
        
    <?php else:?>
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
                <?php if(empty($icl_menus_sync->menus)): ?>
                <tr>
                    <td align="center" colspan="<?php echo count($active_languages) ?>"><?php _e('No menus found', 'sitepress') ?></td>
                </tr>
                <?php else: ?>
                <?php foreach( $icl_menus_sync->menus as  $menu_id => $menu): ?>
                
                <tr class="icl_msync_menu_title">
                    <td><strong><?php echo $menu['name'] ?></strong></td>
                    <?php foreach($secondary_languages as $l): ?>
                    <td>
                    <?php if(isset($menu['translations'][$l['code']]['name'])): echo $menu['translations'][$l['code']]['name']; else: // menu is translated in $l[code] ?>
                        <input type="text" name="sync[menu_translations][<?php echo $menu_id ?>][<?php echo $l['code'] ?>]" class="icl_msync_add" value="<?php 
                            echo esc_attr($menu['name']) . ' - ' . $l['display_name'] ?>" />
                        <small><?php _e('Auto-generated title. Edit to change.', 'sitepress') ?></small>
                    <?php endif; ?>
                    </td>
                    <?php endforeach; //foreach($secondary_languages as $l): ?>
                </tr>            
                
                <?php $icl_menus_sync->render_items_tree_default($menu_id); ?>
                
                <?php endforeach; //foreach( $icl_menus_sync->menus as  $menu_id => $menu): ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p class="submit">
            <input id="icl_msync_sync" type="submit" class="button-primary" value="<?php _e('Sync', 'sitepress')?>"<?php if(empty($icl_menus_sync->menus)): ?> disabled="disabled"<?php endif; ?> />
        </p>
        
        </form>
        
        <?php if(!empty($icl_menus_sync->operations)) foreach($icl_menus_sync->operations as $op => $c):?>
        <?php if($op == 'add'): ?>
        <span class="icl_msync_item icl_msync_add"><?php _e('Item will be added', 'sitepress') ?></span>
        <?php elseif($op == 'del'): ?>
        <span class="icl_msync_item icl_msync_del"><?php _e('Item will be removed', 'sitepress') ?></span>
        <?php elseif($op == 'not'): ?>
        <span class="icl_msync_item icl_msync_not"><?php _e('Item cannot be added (parent not translated)', 'sitepress') ?></span>
        <?php elseif($op == 'mov'): ?>
        <span class="icl_msync_item icl_msync_mov"><?php _e('Item changed position', 'sitepress') ?></span>
        <?php endif; ?>
        <?php endforeach; ?>
        
        
    <?php endif; ?>
    
    <?php do_action('icl_menu_footer'); ?>
</div>
