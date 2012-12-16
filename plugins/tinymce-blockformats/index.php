<?php
/*
  Plugin Name: TinyMCE Blockformats
  Plugin URI: http://www.jamiedust.net/
  Description: This plugin allows you to select which elements are used in the formats dropdown on the TinyMCE editor.
  Version: 1.1
  Author: Jamie Woolgar
  Author URI: http://www.jamiedust.net/
  License: GPL2
  Copyright 2011 Jamie Woolgar (email : dust@jamiedust.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//create options page
add_action ( 'admin_menu', 'tinymceblockformats_menu' );
function tinymceblockformats_menu (){
  add_options_page ( 'TinyMCE Blockformats', 'TinyMCE Blockformats', 'manage_options', 'tinymceblockformats_options', 'tinymceblockformats_settings' );
  add_action ( 'admin_init', 'register_tinymceblockformats_settings' );
}
//register settings
function register_tinymceblockformats_settings (){
  register_setting ( 'tinymceblockformats_settings_group', 'thetinymceblockformats' );
}
//setting page
function tinymceblockformats_settings (){
?>
<div class="wrap">
  <h2>TinyMCE Blockformats</h2>
  <form method="post" action="options.php">
    <?php
	  settings_fields ( 'tinymceblockformats_settings_group' );
	  do_settings_sections ( 'tinymceblockformats_settings_group' );
	  $plugin_dir = basename(dirname(__FILE__));
	  load_plugin_textdomain( 'tinymceblockformats', false, $plugin_dir );
	?>
	<p><?php _e( 'List html elements below, seperated by a comma.', 'tinymceblockformats' ) ?></p>
	<p><?php _e( 'Accepted elements are: p,h1,h2,h3,h4,h5,h6,div,address,pre,code,samp', 'tinymceblockformats' ) ?></p>
	<p><input type="text" name="thetinymceblockformats" style="width:270px" value="<?php echo get_option( 'thetinymceblockformats' ); ?>" /></p>
    <p><input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'tinymceblockformats' ) ?>" /></p>
</form>
</div>
<?php }
//uninstall hook
if ( function_exists('register_uninstall_hook') )
    register_uninstall_hook(__FILE__, 'tinymceblockformats_uninstall_hook');
function tinymceblockformats_uninstall_hook() {
  delete_option('thetinymceblockformats');
}
//TinyMCE format replace
function thetinymceblockformats($init){
  $init['theme_advanced_blockformats'] = ''.get_option('thetinymceblockformats').'';
  return $init;
}
add_filter( 'tiny_mce_before_init', 'thetinymceblockformats' );
?>