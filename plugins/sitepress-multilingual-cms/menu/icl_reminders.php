<?php
    global $sitepress;
    
    if (isset($sitepress->settings['icl_show_reminders'])) {
        $show = $sitepress->settings['icl_show_reminders'];
    } else {
        $show = true;
    }

?>
<div id="icl_reminder_message" class="updated message fade" style="clear:both;margin-top:5px;display:none; padding-bottom: 7px;margin-bottom:30px;">
    <table width="100%">
        <tr>
            <td><h4 style="margin-top:5px 0 5px 0; padding: 0;">ICanLocalize Reminders</h4></td>
            <td align="right">
            <a id="icl_reminder_close" class="icl_win_controls icl_close" href="#" title="<?php esc_attr_e('Hide reminders', 'sitepress')?>">x</a>
            <span id="icl_reminder_close_prompt" style="display: none;"><?php _e("Click OK to confirm.\nYou can enable them back from Translation Management / Multilingual Content setup.", 'sitepress')?></span>            
            <?php if(!$show): ?> 
            <a id="icl_reminder_show" class="icl_win_controls icl_maximize" href="#" title="<?php esc_attr_e('Expand reminders', 'sitepress')?>">+</a>
            <?php else: ?>
            <a id="icl_reminder_show" class="icl_win_controls icl_minimize" href="#" title="<?php esc_attr_e('Collapse reminders', 'sitepress')?>">-</a>     
            <?php endif; ?>            
            </td>
        </tr>
    </table>
    <div id="icl_reminder_list"<?php if(!$show) { echo ' style="display:none"';}?>>
    </div>
</div>

