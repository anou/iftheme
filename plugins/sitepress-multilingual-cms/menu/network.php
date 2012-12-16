<?php
    
$wp_list_table = _get_list_table('WP_MS_Sites_List_Table');
$pagenum = $wp_list_table->get_pagenum();
$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;
$wp_list_table->prepare_items();

$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
if ( $pagenum > $total_pages && $total_pages > 0 ) {
    wp_redirect( add_query_arg( 'paged', $total_pages ) );
    exit;
}


$msg = '';
if ( isset( $_REQUEST['updated'] ) && $_REQUEST['updated'] == 'true' && ! empty( $_REQUEST['action'] ) ) {
    switch ( $_REQUEST['action'] ) {
        case 'resetwpml':
            $msg = __('WPML has been reset for the selected site.', 'sitepress');
            break;
        case 'deactivatewpml':
            $msg = __('WPML has been deactivated for the selected site.', 'sitepress');
            break;
        case 'activatewpml':
            $msg = __('WPML has been activated for the selected site.', 'sitepress');
            break;                        
        default: $msg = false;
    }
    
    if ( $msg )
        $msg = '<div class="updated" id="message"><p>' . $msg . '</p></div>';
}



?>

<div class="wrap">
    <div id="icon-wpml" class="icon32" ><br /></div>
    <h2><?php echo __('WPML Network Setup', 'sitepress') ?>
    <?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
        printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $s ) );
    } ?>
    </h2>
    
    <form action="" method="get" id="ms-search">
    <p class="search-box">
        <label class="screen-reader-text" for="icl_ss"><?php echo $text; ?>:</label>
        <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']) ?>" />
        <input type="text" id="icl_ss" name="s" value="<?php _admin_search_query(); ?>" />
        <?php submit_button( __('Search', 'sitepress'), 'button', false, false, array('id' => 'search-submit') ); ?>
    
    </p>
    </form>
    
    <?php echo $msg; ?>
    
    <br />
    
    <?php if(empty($wp_list_table->items)): ?>
        <?php $wp_list_table->no_items(); ?>
    <?php else:?>
    
    <div class="tablenav top">
    <?php $wp_list_table->pagination( 'bottom' ); ?>
    </div>
    
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Site', 'sitepress'); ?></th>
                <th><?php _e('Status', 'sitepress'); ?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><?php _e('Site', 'sitepress'); ?></th>
                <th><?php _e('Status', 'sitepress'); ?></th>
                <th>&nbsp;</th>
            </tr>
        </tfoot>        
        <tbody>
        <?php 
            $status_list = array(
                'archived' => array( 'site-archived', __( 'Archived' ) ),
                'spam'     => array( 'site-spammed', _x( 'Spam', 'site' ) ),
                'deleted'  => array( 'site-deleted', __( 'Deleted' ) ),
                'mature'   => array( 'site-mature', __( 'Mature' ) )
            );        
            $class = '';                    
        ?>
        <?php foreach($wp_list_table->items as $blog):?>
        <?php 
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
            
            $blog_states = array();
            foreach ( $status_list as $status => $col ) {
                if ( get_blog_status( $blog['blog_id'], $status ) == 1 ) {
                    $class = $col[0];
                    $blog_states[] = $col[1];
                }
            }
            $blog_state = '';
            if ( ! empty( $blog_states ) ) {
                $state_count = count( $blog_states );
                $i = 0;
                $blog_state .= ' - ';
                foreach ( $blog_states as $state ) {
                    ++$i;
                    ( $i == $state_count ) ? $sep = '' : $sep = ', ';
                    $blog_state .= "<span class='post-state'>$state$sep</span>";
                }
            }
            
        ?>
            <tr class="<?php  echo $class ?>">
                <td class='column-blogname blogname'>
                    <?php $blogname = ( is_subdomain_install() ) ? str_replace( '.'.$current_site->domain, '', $blog['domain'] ) : $blog['path']; ?>
                    <a href="<?php echo esc_url( network_admin_url( 'site-info.php?id=' . $blog['blog_id'] ) ); ?>" class="edit"><?php echo $blogname . $blog_state; ?></a>
                    <?php 
                        // Preordered.
                        $actions = array(
                            'edit' => '', 'backend' => '',
                            'activate' => '', 'deactivate' => '',
                            'archive' => '', 'unarchive' => '',
                            'spam' => '', 'unspam' => '',
                            'delete' => '',
                            'visit' => '',
                        );

                        $actions['edit']    = '<span class="edit"><a href="' . esc_url( network_admin_url( 'site-info.php?id=' . $blog['blog_id'] ) ) . '">' . __( 'Edit', 'sitepress' ) . '</a></span>';
                        $actions['backend']    = "<span class='backend'><a href='" . esc_url( get_admin_url( $blog['blog_id'] ) ) . "' class='edit'>" . __( 'Dashboard', 'sitepress' ) . '</a></span>';
                        if ( $current_site->blog_id != $blog['blog_id'] ) {
                            if ( get_blog_status( $blog['blog_id'], 'deleted' ) == '1' )
                                $actions['activate']    = '<span class="activate"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=activateblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to activate the site %s', 'sitepress' ), $blogname ) ) ), 'confirm' ) ) . '">' . __( 'Activate', 'sitepress' ) . '</a></span>';
                            else
                                $actions['deactivate']    = '<span class="activate"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=deactivateblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to deactivate the site %s', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Deactivate', 'sitepress' ) . '</a></span>';

                            if ( get_blog_status( $blog['blog_id'], 'archived' ) == '1' )
                                $actions['unarchive']    = '<span class="archive"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=unarchiveblog&amp;id=' .  $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to unarchive the site %s.', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Unarchive', 'sitepress' ) . '</a></span>';
                            else
                                $actions['archive']    = '<span class="archive"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=archiveblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to archive the site %s.', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Archive', 'sitepress' ) . '</a></span>';

                            if ( get_blog_status( $blog['blog_id'], 'spam' ) == '1' )
                                $actions['unspam']    = '<span class="spam"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=unspamblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to unspam the site %s.', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Not Spam', 'sitepress' ) . '</a></span>';
                            else
                                $actions['spam']    = '<span class="spam"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=spamblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to mark the site %s as spam.', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Spam', 'sitepress' ) . '</a></span>';

                            if ( current_user_can( 'delete_site', $blog['blog_id'] ) )
                                $actions['delete']    = '<span class="delete"><a href="' . esc_url( wp_nonce_url( network_admin_url( 'edit.php?action=confirm&amp;action2=deleteblog&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to delete the site %s.', 'sitepress' ), $blogname ) ) ), 'confirm') ) . '">' . __( 'Delete', 'sitepress' ) . '</a></span>';
                        }

                        $actions['visit']    = "<span class='view'><a href='" . esc_url( get_home_url( $blog['blog_id'] ) ) . "' rel='permalink'>" . __( 'Visit', 'sitepress' ) . '</a></span>';

                        $actions = apply_filters( 'manage_sites_action_links', array_filter( $actions ), $blog['blog_id'], $blogname );
                        echo $wp_list_table->row_actions( $actions );                    
                    ?>
                </td>
                <td>
                    <?php switch_to_blog($blog['blog_id']); ?>
                    <?php if(get_option('_wpml_inactive', false, false)):?>
                    <?php _e('Inactive', 'sitepress'); ?>
                    <div class="row-actions">
                        <a href="<?php echo esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=activatewpml&amp;id=' . $blog['blog_id']  ), 'activatewpml'))  ?>"><?php _e('Activate', 'sitepress')?></a>
                    </div>
                    <?php else:?>
                    <?php _e('Active', 'sitepress'); ?>
                    <div class="row-actions">
                        <?php if($blog['blog_id'] != $current_blog->blog_id): ?>
                        <a href="<?php echo esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=confirm&amp;action2=deactivatewpml&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( sprintf( __( 'You are about to deactivate WPML on the site %s.', 'sitepress' ), $blogname ) ) ), 'confirm') ) ?>"><?php _e('Deactivate', 'sitepress')?></a>
                        <?php endif; ?>
                    </div>                    
                    <?php endif; ?>
                    <?php restore_current_blog();  ?>
                </td>
                <td><a href="<?php echo esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=confirm&amp;action2=resetwpml&amp;id=' . $blog['blog_id'] . '&amp;msg=' . urlencode( 
                    sprintf( __( 'You are about to reset WPML for this site: %s.', 'sitepress' ), $blogname ) . 
                    " " . __("All translations you have sent to ICanLocalize will be lost if you reset WPML's data. They cannot be recovered later.", 'sitepress') 
                    ) ), 'confirm') )  ?>"><?php _e('Reset', 'sitepress')?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="tablenav bottom">
    <?php $wp_list_table->pagination( 'bottom' ); ?>
    </div>
    
    <?php endif?>
    
</div>
