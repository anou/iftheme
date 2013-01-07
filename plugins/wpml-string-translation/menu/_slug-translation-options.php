        <form name="icl_slug_translation" id="icl_slug_translation" action="">
        <?php wp_nonce_field('icl_slug_translation_nonce', '_icl_nonce'); ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th colspan="2"><?php _e('Custom posts slug translation options', 'wpml-string-translation') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: none;" nowrap="nowrap">
                        <p>
                        <label>
                            <input type="checkbox" name="icl_slug_translation_on" value="1" <?php checked(1,$sitepress_settings['posts_slug_translation']['on'],true) ?>  />&nbsp;
                            <?php echo __("Translate custom posts slugs (via WPML String Translation).", 'wpml-string-translation') ?>
                        </label>                        
                        </p>
                        
                        <div class="icl_cyan_box"><?php _e('Slug translation is a new and experimental feature, which may not work in all permalink settings.', 'wpml-string-translation') ?></div>
                        
                    </td>
                </tr>
                
                <tr>
                    <td style="border: none;">
                        <p>
                            <input type="submit" class="button-secondary" value="<?php _e('Save', 'wpml-string-translation')?>" />
                            <span class="icl_ajx_response" id="icl_ajx_response_sgtr"></span>                        
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        </form>
        <br />        
