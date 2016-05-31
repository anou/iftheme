<?php
//include the main class file
// for category customization
require_once("Tax-meta-class/migration/tax_to_term_meta.php");
require_once("Tax-meta-class/Tax-meta-class.php");

  global $current_user;
  $current_user = wp_get_current_user();

 
/*
* configure taxonomy custom fields
*/
$configt = array(
   'id' => 'categ_meta_box',                         // meta box id, unique per meta box
   'title' => 'Category options',                      // meta box title
   'pages' => array('category'),                    // taxonomy name, accept categories, post_tag and custom taxonomies
   'context' => 'normal',                           // where the meta box appear: normal (default), advanced, side; optional
   'fields' => array(),                             // list of meta fields (can be added by field arrays)
   'local_images' => true,                         // Use local or hosted images (meta box images for add/remove)
   'use_with_theme' => true                        //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
);
 
/*
* Initiate your taxonomy custom fields
*/
$custom_category = new Tax_Meta_Class($configt);

/*
* Add fields
*/
//Categories image
$custom_category->addImage('categ_img',array('name'=> __('Download an image for your category','iftheme')));
//Display children checkbox
$custom_category->addCheckbox('categ_children',array('name'=> __('Display sub-categories','iftheme'), 'desc'=>__("Check this box if you want to display a list of child categories",'iftheme')));
//Display children checkbox
$check = true;

if(isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'category' ){
	$check = isset($_GET['tag_ID']) ? get_term_meta($_GET['tag_ID'],'categ_posts') : true;
}
$custom_category->addCheckbox('categ_posts',array('name'=> __('Display Posts','iftheme'), 'desc'=>__("Check this box if you want to display a list of the category's posts",'iftheme'), 'std' => $check));
//hidden field
//to avoid conflict with wpml plugin
//  global $current_user;
  $current_user = wp_get_current_user();

//$custom_category->addHidden('cur_user',array('name'=> 'current user', 'std'=>$current_user->ID));
//$custom_category->addText('cur_user',array('name'=> 'current user', 'std'=>$current_user->ID));

$custom_category->Finish();


