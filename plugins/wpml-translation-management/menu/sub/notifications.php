<?php $nsettings = $iclTranslationManagement->settings['notification']; ?>

<form method="post" name="translation-notifications" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=notifications">
<input type="hidden" name="icl_tm_action" value="save_notification_settings" />
<h4><?php _e('Notify translator about new job:', 'wpml-translation-management'); ?></h4>
<ul>    
    <li>
        <input name="notification[new-job]" type="radio" id="icl_tm_notify_translator" value="<?php echo ICL_TM_NOTIFICATION_IMMEDIATELY ?>"
        <?php if($nsettings['new-job']==ICL_TM_NOTIFICATION_IMMEDIATELY):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_translator"><?php _e('Notify immediately', 'wpml-translation-management'); ?></label>
    </li>
    <li>
        <input name="notification[new-job]" type="radio" id="icl_tm_notify_translator_dont" value="<?php echo ICL_TM_NOTIFICATION_NONE ?>"
        <?php if($nsettings['new-job']==ICL_TM_NOTIFICATION_NONE):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_translator_dont"><?php _e('No notification', 'wpml-translation-management'); ?></label>
    </li>    
</ul>

<?php do_action('WPML_translator_notification'); ?>

<h4><?php _e('Notify translator manager when job is completed:', 'wpml-translation-management'); ?></h4>
<ul>    
    <li>
        <input name="notification[completed]" type="radio" id="icl_tm_notify_complete1" value="<?php echo ICL_TM_NOTIFICATION_IMMEDIATELY ?>"
        <?php if($nsettings['completed']==ICL_TM_NOTIFICATION_IMMEDIATELY):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_complete1"><?php _e('Notify immediately', 'wpml-translation-management'); ?></label>
    </li>
    <!--
    <li>
        <input name="notification[completed]" type="radio" id="icl_tm_notify_complete2" value="<?php echo ICL_TM_NOTIFICATION_DAILY ?>"
        <?php if($nsettings['completed']==ICL_TM_NOTIFICATION_DAILY):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_complete2"><?php _e('Daily notifications summary', 'wpml-translation-management'); ?></label>
    </li>    
    -->
    <li>
        <input name="notification[completed]" type="radio" id="icl_tm_notify_complete0" value="<?php echo ICL_TM_NOTIFICATION_NONE ?>"
        <?php if($nsettings['completed']==ICL_TM_NOTIFICATION_NONE):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_complete0"><?php _e('No notification', 'wpml-translation-management'); ?></label>
    </li>    
</ul>

<h4><?php _e('Notify translator when removed from job:', 'wpml-translation-management'); ?></h4>
<ul>    
    <li>
        <input name="notification[resigned]" type="radio" id="icl_tm_notify_resigned1" value="<?php echo ICL_TM_NOTIFICATION_IMMEDIATELY ?>"
        <?php if($nsettings['resigned']==ICL_TM_NOTIFICATION_IMMEDIATELY):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_resigned1"><?php _e('Notify immediately', 'wpml-translation-management'); ?></label>
    </li>
    <?php /*
    <li>
        <input name="notification[resigned]" type="radio" id="icl_tm_notify_resigned2" value="<?php echo ICL_TM_NOTIFICATION_DAILY ?>"
        <?php if($nsettings['resigned']==ICL_TM_NOTIFICATION_DAILY):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_resigned2"><?php _e('Daily notifications summary', 'wpml-translation-management'); ?></label>
    </li>
    */ ?>
    <li>
        <input name="notification[resigned]" type="radio" id="icl_tm_notify_resigned0" value="<?php echo ICL_TM_NOTIFICATION_NONE ?>"
        <?php if($nsettings['resigned']==ICL_TM_NOTIFICATION_NONE):?>checked="checked"<?php endif; ?> />
        <label for="icl_tm_notify_resigned0"><?php _e('No notification', 'wpml-translation-management'); ?></label>
    </li>    
</ul>

<?php /*
<input type="hidden" name="notification[dashboard]" value="0" />
<input type="checkbox" name="notification[dashboard]" id="icl_tm_notify_dashboard" value="1" 
    <?php if($nsettings['dashboard']==ICL_TM_NOTIFICATION_NONE):?>checked="checked"<?php endif; ?> />
<label for="icl_tm_notify_dashboard"><?php _e('Show notifications on the translation dashboard', 'wpml-translation-management') ?></label>
*/ ?>

<?php /*
<h4><?php _e('Delete old messages after:', 'wpml-translation-management'); ?></h4>
<ul>    
    <li>
        <select name="notification[purge-old]">
            <option value="7" <?php if($nsettings['purge-old']==7):?>selected="selected"<?php endif; ?>><?php printf(__('%s days', 'wpml-translation-management'), 7) ?></option>
            <option value="15" <?php if($nsettings['purge-old']==15):?>selected="selected"<?php endif; ?>><?php printf(__('%s days', 'wpml-translation-management'), 15) ?></option>
            <option value="30" <?php if($nsettings['purge-old']==30):?>selected="selected"<?php endif; ?>><?php printf(__('%s days', 'wpml-translation-management'), 30) ?></option>
            <option value="60" <?php if($nsettings['purge-old']==60):?>selected="selected"<?php endif; ?>><?php printf(__('%s days', 'wpml-translation-management'), 60) ?></option>
            <option value="90" <?php if($nsettings['purge-old']==90):?>selected="selected"<?php endif; ?>><?php printf(__('%s days', 'wpml-translation-management'), 90) ?></option>
            <option value="0" <?php if($nsettings['purge-old']==0):?>selected="selected"<?php endif; ?>><?php _e("Don't delete", 'wpml-translation-management') ?></option>
        </select>        
    </li>
</ul>
*/ ?>

<p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save','wpml-translation-management')?>" />
</p>

</form>