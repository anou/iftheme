<?php
/*
 * @file : this file is used to create and implement all functions useful for the role Antenna
 */
 
 
/*
 * Add new role and assign capabilities
 */
function antenna_role() {
	$author_role = get_role('author');
	add_role('antenna', __('Antenna','iftheme'), $author_role->capabilities);
	$owner_role = get_role('antenna');
	$owner_role->add_cap('edit_theme_options');
	$owner_role->add_cap('manage_categories');
	$owner_role->add_cap('unfiltered_html');
}
add_action('init','antenna_role',1);

/*
 * Add new field to user create/edit form
 *
 * This is only required for user with Antenna role and for Admin (user 1)
 */
function extendUser_antenna($user_id){
	  global $profileuser;
    global $sitepress;

    $default_lg = isset($sitepress) ? $sitepress->get_default_language() : get_site_lang();

    $userID = $user_id->ID;
    $userRole = $profileuser->roles[0];

    $antennaz = get_antenna_users();

    //display only top level categories even if no posts assign to it
    //FYI : add in array $args 'exclude'=>1 if you're not using the default category;
    $args = array('parent'=>0,'hide_empty'=>0);
    $categories = get_categories($args);

    //exclude categories that are allready in use
    foreach($antennaz as $k => $o){
    	$cat = get_user_meta($o->ID, 'categ_to_antenna', true);
	    $usedCateg[$cat]['cat'] = $cat; 
	    $usedCateg[$cat]['user'] = $o->ID; 
    }  
    
    foreach($categories as $c => $v){
      $tid = array_key_exists( 'wpml_object_id' , $GLOBALS['wp_filter'] ) ? apply_filters( 'wpml_object_id', $v->term_id, 'category', true, $default_lg) : $v->term_id;

	    if(isset($usedCateg[$tid]['cat']) && $userID != $usedCateg[$tid]['user']){
		    unset($categories[$c]);
	    }
    }
    
    if($userRole == "administrator" || $userRole == "antenna" && current_user_can('publish_posts')) : ?>
   
    <h3><?php echo __('Category assigned to this Antenna', 'iftheme');?></h3>
 
  <?php if( !empty($categories) ): ?>
    <table class="form-table if-form-table">
      <tr>
      <?php foreach($categories as $category) : 
        $categID = array_key_exists( 'wpml_object_id' , $GLOBALS['wp_filter'] ) ? apply_filters( 'wpml_object_id', $category->term_id, 'category', true, $default_lg) : $category->term_id;
      ?>
        <th scope="row"><label for="<?php echo $category->slug;?>"><?php echo $category->name; ?></label></th>
        <td><input type="radio" name="categ_to_antenna" value="<?php echo $categID ?>" <?php checked( get_user_meta($userID, "categ_to_antenna", true), $categID ); ?> /></td>
      <?php endforeach; ?>
      </tr>
    </table>
  <?php else: ?>
    <?php if($userRole == "administrator"): ?>
      <?php $categAdmin = get_category(get_user_meta(1, 'categ_to_antenna', true)); ?>
      <table class="form-table if-form-table">
        <tr>
          <th scope="row"><label for="<?php echo $categAdmin->slug;?>"><?php echo $categAdmin->name; ?></label></th>
          <td><input type="radio" name="categ_to_antenna" value="<?php echo $categAdmin->cat_ID ?>" <?php checked( get_user_meta(1, "categ_to_antenna", true), $categAdmin->cat_ID ); ?> /></td>
        </tr>
      </table>
    <?php else: ?>
      <?php _e('You must create another top level category to assign to this user', 'iftheme');?>
      <?php //defined('ICL_LANGUAGE_CODE') ? _e('Or there is no category in this language', 'iftheme') : '';?>
    <?php endif;?>
  <?php endif;?>
<?php 
    endif;
}

add_action('edit_user_profile', 'extendUser_antenna');
add_action('show_user_profile', 'extendUser_antenna');

//save/update our custom field
function extendUser_antenna_Save($user_id){
    $userID = $user_id;

    if(isset($_POST['categ_to_antenna'])) $categ_to_antenna = $_POST["categ_to_antenna"];
    else $categ_to_antenna = get_user_meta($userID, 'categ_to_antenna', true);


    update_user_meta($userID, "categ_to_antenna", $categ_to_antenna);
}
add_action('profile_update', 'extendUser_antenna_Save');
add_action('edit_user_profile_update', 'extendUser_antenna_Save' );

/*
 * Display category assigned to users antenna on the admin user listing page
 */
function if_modify_user_table( $column ) {
    $column['categ_to_antenna'] = __('Category');
    return $column;
}
add_filter( 'manage_users_columns', 'if_modify_user_table' );
 
//Add category assigned to user in admin users section
function if_modify_user_table_row( $val, $column_name, $user_id ) {
    $user = get_userdata( $user_id );
 
    if ( 'categ_to_antenna' == $column_name )
    	$categ = get_cat_name( $user->categ_to_antenna );
		return $categ;
}
add_filter( 'manage_users_custom_column', 'if_modify_user_table_row', 10, 3 );
