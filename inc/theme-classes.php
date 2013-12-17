<?php
//include the main class file
//for post customization
require_once("meta-box-class/my-meta-box-class.php");

global $current_user; get_currentuserinfo();

/*
* configure your meta box
*/
/**
 * SLIDERS
 */
$config = array(
    'id' => 'upload_img_slider',             // meta box id, unique per meta box
    'title' => __('Slider images','iftheme'),      // meta box title
    'pages' => array('if_slider'),    // post types, accept custom post types as well, default is array('post'); optional
    'context' => 'normal',               // where the meta box appear: normal (default), advanced, side; optional
    'priority' => 'high',                // order of meta box: high (default), low; optional
    'fields' => array(),                 // list of meta fields (can be added by field arrays) or using the class's functions
    'local_images' => true,             // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => true            //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
);
/*
* Initiate your meta box
*/
$slider = new AT_Meta_Box($config);	
/*
* Add fields to your meta box
*/
/*
* To Create a reapeater Block first create an array of fields
* use the same functions as above but add true as a last param
*/
if($current_user->ID == 1) { $slider->addCheckbox('is_country',array('name'=> __('Country slider','iftheme'), 'desc'=>__("Check this box if this slider is for Country homepage",'iftheme'))); }
 
$repeater_fields[] = $slider->addText('slide_title',array('name'=> __('Title / Description', 'iftheme')),true);
$repeater_fields[] = $slider->addText('url_img_slide',array('name'=> __('URL link for the image', 'iftheme')),true);
$repeater_fields[] = $slider->addImage('image_slide',array('name'=> __('Image', 'iftheme')),true);
 
/*
* Then just add the fields to the repeater block
*/
//repeater block
$slider->addRepeaterBlock('re_',array('inline' => true, 'name' => __('Slider Images', 'iftheme'),'fields' => $repeater_fields));
//hidden field
//to assign the slider to antenna
$slider->addHidden('slide_antenna', array('name'=> 'antenna', 'std'=>get_cat_if_user($current_user->ID)),false);

/*
* Don't Forget to Close up the meta box deceleration
*/
//Finish Meta Box Deceleration
$slider->Finish();

/**
 * PARTNERS
 */
$c_partner = array(
    'id' => 'upload_img_partner',             // meta box id, unique per meta box
    'title' => __('Partners logos','iftheme'),      // meta box title
    'pages' => array('if_partner'),    // post types, accept custom post types as well, default is array('post'); optional
    'context' => 'normal',               // where the meta box appear: normal (default), advanced, side; optional
    'priority' => 'high',                // order of meta box: high (default), low; optional
    'fields' => array(),                 // list of meta fields (can be added by field arrays) or using the class's functions
    'local_images' => true,             // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => true            //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
);

$partner = new AT_Meta_Box($c_partner);	

 
$repeater_fields_p[] = $partner->addText('partner_title',array('name'=> __('Name of the partner', 'iftheme')),true);
$repeater_fields_p[] = $partner->addText('link_to_partner',array('name'=> __('Link', 'iftheme')),true);
$repeater_fields_p[] = $partner->addImage('image_logo',array('name'=> __('Logo', 'iftheme')),true);
//repeater block
$partner->addRepeaterBlock('re_',array('inline' => true, 'name' => __('Partners logos', 'iftheme'),'fields' => $repeater_fields_p));
//hidden field
//to assign the slider to antenna
$partner->addHidden('partner_antenna', array('name'=> 'antenna', 'std'=>get_cat_if_user($current_user->ID)),false);
//Finish Meta Box Deceleration
$partner->Finish();




//DATA for INSCRIPTION FORM
$inscription = array(
    'id' => 'form_infos',             // meta box id, unique per meta box
    'title' => __('Booking informations','iftheme'),      // meta box title
    'pages' => array('post'),    // post types, accept custom post types as well, default is array('post'); optional
    'context' => 'advanced',               // where the meta box appear: normal (default), advanced, side; optional
    'priority' => 'low',                // order of meta box: high (default), low; optional
    'fields' => array(),                 // list of meta fields (can be added by field arrays) or using the class's functions
    'local_images' => true,             // Use local or hosted images (meta box images for add/remove)
    'use_with_theme' => true            //change path if used with theme set to true, false for a plugin or anything else for a custom path(default false).
);
$booking = new AT_Meta_Box($inscription);	
$booking->addCheckbox('if_book_enable',array('name'=> __('Open booking','iftheme'), 'desc'=>__("By checking this box, you enable the booking for this event.",'iftheme')));
$booking->addText('if_book_mail',array('name'=> __('Email','iftheme'), 'desc'=>__("Email to whom send the booking",'iftheme')));
$booking->addWysiwyg('if_book_desc',array('name'=> __('Details','iftheme'), 'desc'=>__("Some information you want to add at the top of the booking form",'iftheme'), 'style' => 'height:150px'));
$booking->Finish();
//end INSCRIPTION
