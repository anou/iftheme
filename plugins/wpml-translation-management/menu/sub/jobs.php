<?php //included from menu translation-management.php ?>
<?php
if(isset($_SESSION['translation_jobs_filter'])){
    $icl_translation_filter = $_SESSION['translation_jobs_filter'];
}
$icl_translation_filter['limit_no'] = 20;
$translation_jobs = $iclTranslationManagement->get_translation_jobs((array)$icl_translation_filter);
?>
<br />

<form method="post" name="translation-jobs-filter" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=jobs">
<input type="hidden" name="icl_tm_action" value="jobs_filter" />
<table class="form-table widefat fixed">
    <thead>
    <tr>
        <th scope="col"><strong><?php _e('Filter by','wpml-translation-management')?></strong></th>
    </tr>
    </thead> 
    <tbody>
        <tr valign="top">
            <td>
                <label>
                    <strong><?php _e('Translation jobs for:', 'wpml-translation-management')?>&nbsp;
                    <?php $iclTranslationManagement->translators_dropdown(array(
                        'name'          => 'filter[translator_id]',
                        'default_name'  => __('All', 'wpml-translation-management'),
                        'selected'      => isset($icl_translation_filter['translator_id'])?$icl_translation_filter['translator_id']:'',
                        'services'      => array('local', 'icanlocalize')
                        )
                     ); ?>            
                </label>&nbsp;
                <label>
                    <strong><?php _e('Status', 'wpml-translation-management')?></strong>&nbsp;
                    <select name="filter[status]">
                        <option value=""><?php _e('All translation jobs', 'wpml-translation-management')?></option>
                        <option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>" <?php 
                            if(!empty($icl_translation_filter['status']) 
                                && $icl_translation_filter['status']== ICL_TM_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_WAITING_FOR_TRANSLATOR); ?></option>
                        <option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php 
                            if(!empty($icl_translation_filter['status']) && $icl_translation_filter['status']==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_IN_PROGRESS); ?></option>
                        <option value="<?php echo ICL_TM_COMPLETE ?>" <?php 
                            if(!empty($icl_translation_filter['status']) &&  $icl_translation_filter['status']==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_COMPLETE); ?></option>                                                 <option value="<?php echo ICL_TM_DUPLICATE ?>" <?php 
                            if(!empty($icl_translation_filter['status']) &&  $icl_translation_filter['status']==ICL_TM_DUPLICATE):?>selected="selected"<?php endif ;?>><?php 
                                _e('Content duplication', 'wpml-translation-management') ?></option>                                                           
                    </select>
                </label>&nbsp;
                <label>
                    <strong><?php _e('From', 'wpml-translation-management');?></strong>
                        <select name="filter[from]">   
                            <option value=""><?php _e('Any language', 'wpml-translation-management')?></option>
                            <?php foreach($sitepress->get_active_languages() as $lang):?>
                            <option value="<?php echo $lang['code']?>" <?php 
                            if(!empty($icl_translation_filter['from']) && $icl_translation_filter['from']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                            <?php endforeach; ?>
                        </select>
                </label>&nbsp;        
                <label>
                    <strong><?php _e('To', 'wpml-translation-management');?></strong>
                        <select name="filter[to]">   
                            <option value=""><?php _e('Any language', 'wpml-translation-management')?></option>
                            <?php foreach($sitepress->get_active_languages() as $lang):?>
                            <option value="<?php echo $lang['code']?>" <?php 
                            if(!empty($icl_translation_filter['to']) && $icl_translation_filter['to']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                            <?php endforeach; ?>
                        </select>            
                </label>                
                &nbsp;
                <input class="button-secondary" type="submit" value="<?php _e('Apply', 'wpml-translation-management')?>" />
            </td>
        </tr>
    </tbody>     
</table>
</form>

<br />

<form method="post" id="icl-tm-jobs-form">
<input type="hidden" name="icl_tm_action" value="" />
<table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
    <thead>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col" width="60"><?php _e('Job ID', 'wpml-translation-management')?></th>
            <th scope="col"><?php _e('Title', 'wpml-translation-management')?></th>
            <th scope="col"><?php _e('Language', 'wpml-translation-management')?></th>            
            <th scope="col" class="manage-column" style="width:150px"><?php _e('Status', 'wpml-translation-management')?></th>
            <th scope="col" class="manage-column"><?php _e('Translator','wpml-translation-management') ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" /></th>
            <th scope="col" width="60"><?php _e('Job ID', 'wpml-translation-management')?></th>
            <th scope="col"><?php _e('Title', 'wpml-translation-management')?></th>
            <th scope="col"><?php _e('Language', 'wpml-translation-management')?></th>
            <th scope="col"><?php _e('Status', 'wpml-translation-management')?></th>
            <th scope="col" class="manage-column"><?php _e('Translator','wpml-translation-management') ?></th>
        </tr>
    </tfoot>    
    <tbody>
        <?php if(empty($translation_jobs)):?>
        <tr>
            <td colspan="6" align="center"><?php _e('No translation jobs found', 'wpml-translation-management')?></td>
        </tr>
        <?php else: foreach($translation_jobs as $job): ?>        
        <tr class="icl_tm_status_<?php echo $job->status ?>">
            <td scope="col">
                <?php if(($job->status == ICL_TM_WAITING_FOR_TRANSLATOR || $job->status == ICL_TM_IN_PROGRESS) && $job->translation_service=='local'): ?>
                <input type="checkbox" value="<?php echo $job->translation_id ?>" name="icl_translation_id[]" />
                <?php else: ?>
                &nbsp;
                <?php endif; ?>
            </td>            
            <td width="60"><?php echo $job->job_id; ?></td>
            <td><?php echo TranslationManagement::tm_post_link($job->original_doc_id); ?></td>
            <td><?php echo $job->lang_text ?></td>            
            <td><span id="icl_tj_job_status_<?php echo $job->job_id ?>"><?php echo $iclTranslationManagement->status2text($job->status) ?></span>
                <?php if($job->needs_update) _e(' - (needs update)', 'wpml-translation-management'); ?>
            </td>
            <td>
                <?php if(!empty($job->translator_id) && $job->status != ICL_TM_WAITING_FOR_TRANSLATOR): ?>                                        
                    <?php if($job->translation_service == 'icanlocalize'): ?>
                    <?php 
                    $contract_id = $lang_tr_id = false;
                    foreach($sitepress_settings['icl_lang_status'] as $lp){
                        if($lp['from'] == $job->source_language_code && $lp['to'] == $job->language_code){
                            $contract_id = $lp['contract_id'];
                            $lang_tr_id =  $lp['id']; 
                            break;
                        }
                    }
                    if($contract_id && $lang_tr_id){
                        echo $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/websites/' . $sitepress_settings['site_id']
                        . '/website_translation_offers/' . $lang_tr_id . '/website_translation_contracts/'
                        . $contract_id, array('title' => __('Chat with translator', 'wpml-translation-management'), 'unload_cb' => 'icl_thickbox_refresh')) . esc_html($job->translator_name)  . '</a> (ICanLocalize)';                    
                    }
                    ?>
                    <?php else: ?>
                    <a href="<?php echo $iclTranslationManagement->get_translator_edit_url($job->translator_id) ?>"><?php echo esc_html($job->translator_name) ?></a>
                    <?php endif;?>
                <?php else: ?>
                <span class="icl_tj_select_translator"><?php 
                    $iclTranslationManagement->translators_dropdown(
                        array(
                            'name'=>'icl_tj_translator_for_'.$job->job_id , 
                            'from'=>$job->source_language_code,
                            'to'=>$job->language_code, 
                            'selected'=>@intval($job->translator_id),
                            'services' => array('local', 'icanlocalize')
                        )
                    );
                    ?></span>
                <input type="hidden" id="icl_tj_ov_<?php echo $job->job_id ?>" value="<?php echo @intval($job->translator_id) ?>" />
                <span class="icl_tj_select_translator_controls" id="icl_tj_tc_<?php echo $job->job_id ?>">
                    <input type="button" class="button-secondary icl_tj_ok" value="<?php _e('Send', 'wpml-translation-management') ?>" />&nbsp;
                    <input type="button" class="button-secondary icl_tj_cancel" value="<?php _e('Cancel', 'wpml-translation-management') ?>" />
                </span>
                <?php endif; ?>                
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>    
</table>

<?php wp_nonce_field('assign_translator_nonce', '_icl_nonce_at') ?>
<br />
<input id="icl-tm-jobs-cancel-but" class="button-primary" type="submit" value="<?php _e('Cancel selected', 'wpml-translation-management') ?>" disabled="disabled" />
<span id="icl-tm-jobs-cancel-msg" style="display: none"><?php _e('Are you sure you want to cancel these jobs?', 'wpml-translation-management'); ?></span>
<span id="icl-tm-jobs-cancel-msg-2" style="display: none"><?php _e('WARNING: %s job(s) are currently being translated.', 'wpml-translation-management'); ?></span>

</form>
    
    <?php 
    // pagination  
    $page_links = paginate_links( array(
        'base' => add_query_arg('paged', '%#%' ),
        'format' => '',
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'total' => $wp_query->max_num_pages,
        'current' => $_GET['paged'],
        'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
    ));         
    ?> 
    <div class="tablenav">    
        <?php if ( $page_links ) { ?>
        <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'wpml-translation-management' ) . '</span>%s',
            number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
            number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
            number_format_i18n( $wp_query->found_posts ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
        <?php } ?>
    </div>    
    <?php // pagination - end ?>
