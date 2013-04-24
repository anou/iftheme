<?php

global $w_this_lang;


if($w_this_lang['code']=='all'){
    $main_language['native_name'] = __('All languages', 'sitepress');
}
if(empty($main_language)){
    $main_language['native_name'] = $w_this_lang['display_name'];
    $main_language['translated_name'] = $w_this_lang['display_name'];
    $main_language['language_code'] = $w_this_lang['code'];
    if( $this->settings['icl_lso_flags'] || $icl_language_switcher_preview){
        $flag = $this->get_flag($w_this_lang['code']);
        if($flag->from_template){
            $wp_upload_dir = wp_upload_dir();
            $main_language['country_flag_url'] = $wp_upload_dir['baseurl'] . '/flags/' . $flag->flag;
        }else{
            $main_language['country_flag_url'] = ICL_PLUGIN_URL . '/res/flags/'.$flag->flag;
        }
    }
}
?>
<div id="lang_sel"<?php if ($this->settings['icl_lang_sel_type'] == 'list') echo ' style="display:none;"';?> <?php if($this->is_rtl()): ?>class="icl_rtl"<?php endif; ?> >
    <ul>
        <li><a href="#" class="lang_sel_sel icl-<?php echo $w_this_lang['code'] ?>">
            <?php if( $this->settings['icl_lso_flags'] || $icl_language_switcher_preview):?>                
            <img <?php if( !$this->settings['icl_lso_flags'] ):?>style="display:none"<?php endif?> class="iclflag" src="<?php echo $main_language['country_flag_url'] ?>" alt="<?php echo $main_language['language_code'] ?>"  title="<?php echo $this->settings['icl_lso_native_lang'] ? esc_attr($main_language['native_name']) : esc_attr($main_language['translated_name']) ; ?>" />                                
            &nbsp;<?php endif;
                if($icl_language_switcher_preview){
                    $lang_native = $main_language['native_name'];
                    if($this->settings['icl_lso_native_lang']){
                        $lang_native_hidden = false;
                    }else{
                        $lang_native_hidden = true;
                    }
                    $lang_translated = $main_language['translated_name'];
                    if($this->settings['icl_lso_display_lang']){
                        $lang_translated_hidden = false;
                    }else{
                        $lang_translated_hidden = true;
                    }                            
                }else{
                    if($this->settings['icl_lso_native_lang']){
                        $lang_native = $main_language['native_name'];
                    }else{
                        $lang_native = false;
                    }
                    if($this->settings['icl_lso_display_lang']){
                        $lang_translated = $main_language['translated_name'];
                    }else{
                        $lang_translated = false;
                    }
                    
                    $lang_native_hidden = false;
                    $lang_translated_hidden = false;
                    
                }
                echo icl_disp_language($lang_native, $lang_translated, $lang_native_hidden, $lang_translated_hidden);                
            
			 if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
            <?php if(!empty($active_languages)): ?>
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>            
            <ul>
                <?php $active_languages_ordered = $this->order_languages($active_languages); ?>
                <?php foreach($active_languages_ordered as $lang): ?>
                <li class="icl-<?php echo $lang['language_code'] ?>">          
                    <a rel="alternate" hreflang="<?php echo $lang['language_code'] ?>" href="<?php echo apply_filters('WPML_filter_link', $lang['url'], $lang)?>">
                    <?php if( $this->settings['icl_lso_flags'] || $icl_language_switcher_preview):?>                
                    <img <?php if( !$this->settings['icl_lso_flags'] ):?>style="display:none"<?php endif?> class="iclflag" src="<?php echo $lang['country_flag_url'] ?>" alt="<?php echo $lang['language_code'] ?>" title="<?php echo $this->settings['icl_lso_native_lang'] ? esc_attr($lang['native_name']) : esc_attr($lang['translated_name']) ; ?>" />&nbsp;                    
                    <?php endif; ?>
                    <?php 
                        if($icl_language_switcher_preview){
                            $lang_native = $lang['native_name'];
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native_hidden = false;
                            }else{
                                $lang_native_hidden = true;
                            }
                            $lang_translated = $lang['translated_name'];
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated_hidden = false;
                            }else{
                                $lang_translated_hidden = true;
                            }                            
                        }else{
                            if($this->settings['icl_lso_native_lang']){
                                $lang_native = $lang['native_name'];
                            }else{
                                $lang_native = false;
                            }
                            if($this->settings['icl_lso_display_lang']){
                                $lang_translated = $lang['translated_name'];
                            }else{
                                $lang_translated = false;
                            }
                        }
                        echo icl_disp_language($lang_native, $lang_translated, $lang_native_hidden, $lang_translated_hidden);
                         ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>            
            <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?> 
            <?php endif; ?>
        </li>
    </ul>    
</div>
