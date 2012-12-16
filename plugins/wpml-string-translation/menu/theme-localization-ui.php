        
        <h3><?php _e('Strings in the theme', 'wpml-string-translation'); ?></h3>
        
        <div class="updated fade">
            <p><i><?php _e('Re-scanning the plugins or the themes will reset the strings tracked in the code or the HTML source', 'wpml-string-translation') ?></i></p>
        </div>
        
        <div id="icl_strings_in_theme_wrap">
        
        <?php if($theme_localization_stats): ?>
        <p><?php _e('The following strings were found in your theme.', 'wpml-string-translation'); ?></p>
        <table id="icl_strings_in_theme" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col"><?php echo __('Domain', 'wpml-string-translation') ?></th>
                    <th scope="col"><?php echo __('Translation status', 'wpml-string-translation') ?></th>
                    <th scope="col" style="text-align:right"><?php echo __('Count', 'wpml-string-translation') ?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </thead>  
            <tbody>
                <?php foreach($sitepress_settings['st']['theme_localization_domains'] as $tl_domain): ?>
                <?php 
                    $_tmpcomp = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['complete'];
                    $_tmpinco = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['incomplete'];
                ?>
                <tr>
                    <td rowspan="3"><?php echo $tl_domain ? $tl_domain : '<i>' . __('no domain','wpml-string-translation') . '</i>'; ?></td>
                    <td><?php echo __('Fully translated', 'wpml-string-translation') ?></td>
                    <td align="right"><?php echo $_tmpcomp; ?></td>
                    <td rowspan="3" align="right" style="padding-top:10px;">
                        <a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? 'theme%20' . $tl_domain : 'WordPress' ?>" class="button-secondary"><?php echo __("View all the theme's texts",'wpml-string-translation')?></a>
                        <?php if($_tmpinco): ?>
                        <a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? 'theme%20' . $tl_domain : 'WordPress' ?>&amp;status=0" class="button-primary"><?php echo __("View strings that need translation",'wpml-string-translation')?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Not translated or needs update', 'wpml-string-translation') ?></td>
                    <td align="right"><?php echo $_tmpinco ?></td>
                </tr>
                <tr style="background-color:#f9f9f9;">
                    <td><strong><?php echo __('Total', 'wpml-string-translation') ?></strong></td>
                    <td align="right"><strong><?php echo $_tmpcomp + $_tmpinco; if(1 < count($sitepress_settings['st']['theme_localization_domains'])) { if(!isset($_tmpgt)) $_tmpgt = 0; $_tmpgt += $_tmpcomp + $_tmpinco; } ?></strong></td>
                </tr>            
                <?php endforeach  ?>
            </tbody>
            <?php if(1 < count($sitepress_settings['st']['theme_localization_domains'])): ?>
                <tfoot>
                    <tr>                
                        <th scope="col"><?php echo __('Total', 'wpml-string-translation') ?></th>
                        <th scope="col">&nbsp;</th>
                        <th scope="col" style="text-align:right"><?php echo $_tmpgt ?></th>
                        <th scope="col">&nbsp;</th>
                    </tr>
                </tfoot>                              
            <?php endif; ?>
        </table>
        <?php else: ?>
        <p><?php echo __("To translate your theme's texts, click on the button below. WPML will scan your theme for texts and let you enter translations.", 'wpml-string-translation') ?></p>
        <?php endif; ?>
        
        </div>
                
        <p>
        <label>
        <input type="checkbox" id="icl_load_mo_themes" value="1" checked="checked" />            
        <?php _e('Load translations if found in the .mo files. (it will not override existing translations)', 'wpml-string-translation')?></label> 
        </p>
                
        <p>
        <input id="icl_tl_rescan" type="button" class="button-primary" value="<?php echo __("Scan the theme for strings",'wpml-string-translation')?>" />
        <img class="icl_ajx_loader" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt="" />
        </p>        
        <div id="icl_tl_scan_stats"></div>  
        
        <br />
        
        <h3><?php _e('Strings in the plugins', 'wpml-string-translation'); ?></h3>
        <?php 
        $plugins = get_plugins();
        $active_plugins = get_option('active_plugins'); 
        $mu_plugins = wp_get_mu_plugins();
        foreach($mu_plugins as $p){
            $pfile = basename($p);
            $plugins[$pfile] = array('Name' => 'MU :: ' . $pfile);
            $mu_plugins_base[$pfile] = true;
        }
        $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
        ?>
        <form id="icl_tl_rescan_p" action="">
            <div id="icl_strings_in_plugins_wrap">
                <table id="icl_strings_in_plugins" class="widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" class="column-cb check-column"><input type="checkbox" /></th>
                            <th scope="col"><?php echo __('Plugin', 'wpml-string-translation') ?></th>
                            <th scope="col"><?php echo __('Active', 'wpml-string-translation') ?></th>
                            <th scope="col"><?php echo __('Translation status', 'wpml-string-translation') ?><div style="float:right"><?php echo __('Count', 'wpml-string-translation') ?></div></th>
                            <th scope="col">&nbsp;</th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>  
                    <tfoot>
                        <tr>
                            <th scope="col" class="column-cb check-column"><input type="checkbox" /></th>
                            <th scope="col"><?php echo __('Plugin', 'wpml-string-translation') ?></th>
                            <th scope="col"><?php echo __('Active', 'wpml-string-translation') ?></th>
                            <th scope="col"><?php echo __('Translation status', 'wpml-string-translation') ?><div style="float:right"><?php echo __('Count', 'wpml-string-translation') ?></div></th>
                            <th scope="col">&nbsp;</th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </tfoot>                              
                    <tbody>
                        <?php foreach($plugins as $file=>$plugin): ?>
                        <?php   
                            $plugin_id = (false !== strpos($file, '/')) ? dirname($file) : $file;
                            $plugin_id = 'plugin ' . $plugin_id;
                            if(isset($plugin_localization_stats[$plugin_id]['complete'])){
                                $_tmpcomp = $plugin_localization_stats[$plugin_id]['complete'];
                                $_tmpinco = $plugin_localization_stats[$plugin_id]['incomplete'];
                                $_tmptotal = $_tmpcomp + $_tmpinco;
                                $_tmplink = true;
                            }else{
                                $_tmpcomp = $_tmpinco = $_tmptotal =  __('n/a', 'wpml-string-translation');
                                $_tmplink = false;
                            }
                            $is_mu_plugin = false;
                            if(in_array($file, $active_plugins)){
                                $plugin_active_status = __('Yes', 'wpml-string-translation');    
                            }elseif(isset($wpmu_sitewide_plugins[$file])){
                                $plugin_active_status = __('Network', 'wpml-string-translation');    
                            }elseif(isset($mu_plugins_base[$file])){
                                $plugin_active_status = __('MU', 'wpml-string-translation');    
                                $is_mu_plugin = true;
                            }else{
                                $plugin_active_status = __('No', 'wpml-string-translation');    
                            } 
                            
                            
                        ?>
                        <tr>
                            <td><input type="checkbox" value="<?php echo $file ?>" name="<?php if($is_mu_plugin):?>mu-plugin[]<?php else:?>plugin[]<?php endif; ?>" /></td>
                            <td><?php echo $plugin['Name'] ?></td>
                            <td align="center"><?php echo $plugin_active_status ?></td>
                            <td>
                                <table width="100%" cellspacing="0">
                                    <tr>
                                        <td><?php echo __('Fully translated', 'wpml-string-translation') ?></td>                    
                                        <td align="right"><?php echo $_tmpcomp ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo __('Not translated or needs update', 'wpml-string-translation') ?></td>
                                        <td align="right"><?php echo $_tmpinco  ?></td>
                                    </tr>
                                    <tr>
                                        <td style="border:none"><strong><?php echo __('Total', 'wpml-string-translation') ?></strong></td>
                                        <td style="border:none" align="right"><strong><?php echo $_tmptotal; ?></strong></td>
                                    </tr>            
                                </table>
                            </td>
                            <td align="right" style="padding:10px;">
                                <?php if($_tmplink): ?>
                                    <p><a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $plugin_id ?>" class="button-secondary"><?php echo __("View all the plugin's texts",'wpml-string-translation')?></a></p>
                                    <?php if($_tmpinco): ?>
                                    <p><a href="admin.php?page=<?php echo WPML_ST_FOLDER ?>/menu/string-translation.php&amp;context=<?php echo $plugin_id ?>&amp;status=0" class="button-primary"><?php echo __("View strings that need translation",'wpml-string-translation')?></a></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a class="wpml_st_pop_download button-secondary" href="#<?php echo urlencode($file) ?>"><?php _e('create PO file', 'wpml-string-translation'); ?></a>                                
                            </td>                     
                        </tr>
                        <?php endforeach  ?>
                    </tbody>
                </table>        
            </div>    
            
            <p>
            <label>
            <input type="checkbox" name="icl_load_mo" value="1" checked="checked" />            
            <?php _e('Load translations if found in the .mo files. (it will not override existing translations)', 'wpml-string-translation')?></label> 
            </p>
            <p>
            <input type="submit" class="button-primary" value="<?php echo __("Scan the selected plugins for strings",'wpml-string-translation')?>" />
            <img class="icl_ajx_loader_p" src="<?php echo WPML_ST_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt="" />
            </p>
        
        
        </form>
        
        <div id="icl_tl_scan_stats_p"></div>  