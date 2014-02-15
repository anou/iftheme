<?php
/**
 * Detect plugin. For use on Front End only.
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// check for plugin using plugin name
if ( is_plugin_active( 'underconstruction/underConstruction.php' ) ) :
  get_template_part( 'under-construction-IF' ); 

else :
	if ( current_user_can( 'install_plugins' ) ) {
		$link = network_admin_url( 'plugins.php' );
		wp_die( sprintf( __( 'You must activate the Under construction plugin to use this template: <a href="%s">Go to plugins admin page</a>.' ), $link ) );
	}

	wp_die( __( 'You do not have sufficient permissions to use this template.', 'iftheme' ) );

endif; ?>
