    <?php if(!defined('WPML_TM_VERSION')): ?><div style="width:50%;float:left;margin-right:12px;"><?php endif;?>
    <form id="icl_page_sync_options" name="icl_page_sync_options" action="">        
    <?php wp_nonce_field('icl_page_sync_options_nonce', '_icl_nonce'); ?>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Posts and pages synchronization', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none;">
                    <br />                    
                    <p>
                        <label><input type="checkbox" id="icl_sync_page_ordering" name="icl_sync_page_ordering" <?php if($sitepress_settings['sync_page_ordering']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize page order for translations', 'sitepress') ?></label>                        
                    </p>
                    <p>
                        <label><input type="checkbox" id="icl_sync_page_parent" name="icl_sync_page_parent" <?php if($sitepress_settings['sync_page_parent']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Set page parent for translation according to page parent of the original language', 'sitepress') ?></label>                        
                    </p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_page_template" <?php if($sitepress_settings['sync_page_template']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize page template', 'sitepress') ?></label>                        
                    </p>                    
                    <p>
                        <label><input type="checkbox" name="icl_sync_comment_status" <?php if($sitepress_settings['sync_comment_status']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize comment status', 'sitepress') ?></label>                        
                    </p>                    
                    <p>
                        <label><input type="checkbox" name="icl_sync_ping_status" <?php if($sitepress_settings['sync_ping_status']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize ping status', 'sitepress') ?></label>                        
                    </p>                                        
                    <p>
                        <label><input type="checkbox" name="icl_sync_sticky_flag" <?php if($sitepress_settings['sync_sticky_flag']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize sticky flag', 'sitepress') ?></label>                        
                    </p>                                                            
                    <p>
                        <label><input type="checkbox" name="icl_sync_private_flag" <?php if($sitepress_settings['sync_private_flag']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize private flag', 'sitepress') ?></label>                        
                    </p>                    
                    <p>
                        <label><input type="checkbox" name="icl_sync_post_format" <?php if($sitepress_settings['sync_post_format']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize posts format', 'sitepress') ?></label>                        
                    </p>                                        
                    <p style="border-top:solid 1px #ddd;font-size:2px">&nbsp;</p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_delete" <?php if($sitepress_settings['sync_delete']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('When deleting a post, delete translations as well', 'sitepress') ?></label>                        
                    </p>  
                    <p>
                        <label><input type="checkbox" name="icl_sync_delete_tax" <?php if($sitepress_settings['sync_delete_tax']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('When deleting a taxonomy (category, tag or custom), delete translations as well', 'sitepress') ?></label>                        
                    </p>                      
                    <p style="border-top:solid 1px #ddd;font-size:2px">&nbsp;</p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_post_taxonomies" <?php if($sitepress_settings['sync_post_taxonomies']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Copy taxonomy to translations', 'sitepress') ?></label>                        
                    </p>  
                    <p>
                        <label><input type="checkbox" name="icl_sync_post_date" <?php if($sitepress_settings['sync_post_date']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Copy publishing date to translations', 'sitepress') ?></label>                        
                    </p>  
                    <p style="border-top:solid 1px #ddd;font-size:2px">&nbsp;</p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_taxonomy_parents" <?php if($sitepress_settings['sync_taxonomy_parents']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Sync taxonomy (e.g. category) parents ', 'sitepress') ?></label>                        
                    </p>  
                    <?php if(defined('WPML_TM_VERSION')): ?>
                    <p style="border-top:solid 1px #ddd;font-size:2px">&nbsp;</p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_comments_on_duplicates" <?php if(!empty($sitepress_settings['sync_comments_on_duplicates'])): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize comments on duplicate content', 'sitepress') ?></label>                        
                    </p>  
                    <?php endif; ?>
                    
                    <p>
                        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                        <span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
                    </p>                    
                </td>
            </tr>
        </tbody>
    </table>
    </form>
    
    <?php if(!defined('WPML_TM_VERSION')): ?>
    <br clear="all" />                
    </div>
    <?php endif; ?>
    
