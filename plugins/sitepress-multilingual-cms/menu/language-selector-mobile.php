    <?php
        
    $languages = $this->get_ls_languages();
    
    foreach($languages as $code => $language){
        if($code == $this->get_current_language()){
            $current_language = $language;
            unset($languages[$code]);
            break;
        }     
    }
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if(preg_match('#MSIE ([0-9]+)\.[0-9]#',$user_agent,$matches)){
        $ie_ver = $matches[1];
    }
    ?>
    
    <div id="lang_sel_click" onclick="wpml_language_selector_click.toggle();" class="lang_sel_click<?php if($this->is_rtl()): ?> icl_rtl<?php endif; ?>" >
        <ul>
            <li>
                <a href="javascript:;" class="lang_sel_sel icl-<?php echo $current_language['language_code'] ?>">
                    <?php if( $this->settings['icl_lso_flags'] ):?>                
                    <img class="iclflag" src="<?php echo $current_language['country_flag_url'] ?>" alt="<?php echo $current_language['language_code'] ?>"  title="<?php 
                        echo $this->settings['icl_lso_native_lang'] ? esc_attr($current_language['native_name']) : esc_attr($current_language['translated_name']) ; ?>" />
                    <?php endif; ?>
                    <?php echo $current_language['native_name']; ?>
                
                <?php if(!isset($ie_ver) || $ie_ver > 6): ?></a><?php endif; ?>
                <?php if(isset($ie_ver) && $ie_ver <= 6): ?><table><tr><td><?php endif ?>
                
                <ul>
                    <?php foreach($languages as $code => $language): ?>
                    <li class="icl-<?php echo $language['language_code'] ?>">
                        <a rel="alternate" href="<?php echo apply_filters('WPML_filter_link', $language['url'], $language)?>">
                            <?php if( $this->settings['icl_lso_flags'] ):?>                
                            <img class="iclflag" src="<?php echo $language['country_flag_url'] ?>" alt="<?php echo $language['language_code'] ?>" title="<?php 
                                echo $this->settings['icl_lso_native_lang'] ? esc_attr($language['native_name']) : esc_attr($language['translated_name']) ; ?>" />&nbsp;                    
                            <?php endif; ?>                        
                            
                            <?php 
                            if($this->settings['icl_lso_display_lang'] && $this->settings['icl_lso_native_lang']){
                                $language_name = '<span class="icl_lang_sel_native">' . $language['native_name'] .'</span> 
                                         <span class="icl_lang_sel_translated">(' . $language['translated_name'] . ')</span>';
                            }elseif($this->settings['icl_lso_display_lang']){
                                $language_name = '<span class="icl_lang_sel_translated">' . $language['translated_name'] . '</span>';
                            }else{
                                $language_name = '<span class="icl_lang_sel_native">' . $language['native_name'] .'</span>';
                            }
                            
                            ?>
                            
                            <?php echo $language_name ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if(isset($ie_ver) && $ie_ver <= 6): ?></td></tr></table></a><?php endif ?>   
                
            </li>
            
        </ul>    
    </div>