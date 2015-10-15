<?php
/*
 * Institut Français functions
 *
 * Management system of categories, posts & pages
 * The concept is bsed on the facts that every pages are posts and menu items are categories. And are all related to an antenna (top level category, the ones with no parent)
 * WP pages are used only for general purpose and are common for all antenna.
 *
 * An antenna is a user (role antenna) associated with a top level category.
 *
 * First thing to do is to create a top level category nammed by the city you want (an Institut Français antenna)
 * Then assign this top level category to the user of your choice. If you only have one, means it's the admin, so assign-it to the admin.
 *
 * et voilà.
 *
 * for more information contact anou(at)smol(dot)org
 */

function if_init() {
	if (!is_admin()) {
/*
		wp_deregister_script('jquery');
		wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.min.js"), false);
		wp_enqueue_script('jquery');
*/
		wp_enqueue_script('masonry', get_bloginfo('stylesheet_directory') . '/js/jquery.masonry.min.js', array('jquery'));
		wp_enqueue_script('moment', get_bloginfo('stylesheet_directory') . '/js/moment.min.js', array('jquery'));
		wp_enqueue_script('langs', get_bloginfo('stylesheet_directory') . '/js/langs.min.js', array('moment'));

		//RTL languages
		if ( is_rtl() ) {
			wp_enqueue_script( 'slides-rtl', get_bloginfo('stylesheet_directory') . '/js/slides.jquery.rtl.js', array('jquery'));
		} else {
			wp_enqueue_script('slides', get_bloginfo('stylesheet_directory') . '/js/slides.jquery.js', array('jquery'));
		}
	}
}
add_action('init', 'if_init');

//activate auto-update
//add_filter( 'auto_update_theme', '__return_true' );

/////////////////ERROR MESSAGE IF NO CATEG FOR USER ////////////////
add_action( 'admin_notices', 'iftheme_categtouser_error_notice' );
function iftheme_categtouser_error_notice($raw = false){
  global $current_screen;
  global $current_user; get_currentuserinfo();
  $usercat = get_cat_if_user($current_user->ID);
  
  if ( $current_screen->base == 'appearance_page_theme_options' && !$usercat ) {
    if(!$raw) {
      echo '<div class="error"><p>';
      printf( __('Warning - You must assign a category to the current user <a href="%2$s/wp-admin/users.php"><b>%1$s</b>!</a>', 'iftheme'), $current_user->data->display_name, get_bloginfo('wpurl') );
      echo '</p></div>';
    }
    
    return 'user-categ-error';

  }
}

/**
 * Tell WordPress to run iftheme_setup() when the 'after_setup_theme' hook is run.
 */
add_action( 'after_setup_theme', 'iftheme_setup' );

if ( ! function_exists( 'iftheme_setup' ) ):
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which runs
 * before the init hook. The init hook is too late for some features, such as indicating
 * support post thumbnails.
 *
 * To override iftheme_setup() in a child theme, add your own iftheme_setup to your child theme's
 * functions.php file.
 *
 * @uses load_theme_textdomain() For translation/localization support.
 * @uses add_editor_style() To style the visual editor.
 * @uses add_theme_support() To add support for post thumbnails, automatic feed links, and Post Formats.
 * @uses register_nav_menus() To add support for navigation menus.
 * @uses add_custom_background() To add support for a custom background.
 * @uses add_custom_image_header() To add support for a custom header.
 * @uses register_default_headers() To register the default custom header images provided with the theme.
 * @uses set_post_thumbnail_size() To set a custom post thumbnail size.
 *
 */
function iftheme_setup() {

	/* Make Institut Français available for translation.
	 * Translations can be added to the /languages/ directory.
	 */
	load_theme_textdomain( 'iftheme', get_template_directory() . '/languages' );

	// Load up our theme options page and related code.
	require( get_template_directory() . '/inc/theme-options.php' );
	
	// Load up our post meta box .
	require( get_template_directory() . '/inc/events/if-events.php' );

	//include user-role antenna file
	require_once( get_template_directory() . "/inc/if-user.php");

	//include if widgets
	require_once( get_template_directory() . "/inc/widgets/if-world-widget.php");
	require_once( get_template_directory() . "/inc/widgets/if-categ-widget.php");
	require_once( get_template_directory() . "/inc/widgets/if-partners-widget.php");
	require_once( get_template_directory() . "/inc/widgets/if-antennas-widget.php");
	require_once( get_template_directory() . "/inc/widgets/if-mobile-widget.php");
	require_once( get_template_directory() . "/inc/widgets/if-nopadding-widget.php");

	//include antenna categories widget
	require_once( get_template_directory() . "/inc/editor-styles/editor-styles.php");
	
	//include the main classes file
	// for category and post customization
	require_once( get_template_directory() . "/inc/theme-classes.php");
	require_once( get_template_directory() . "/inc/theme-taxo-classes.php");

}
endif; // iftheme_setup

/*
 * MENU REGISTRATION
 */
// Register menus
/*
function iftheme_menu_register() {
  	register_nav_menus(
  		array(
  		  'header-menu' => __('Header Menu','iftheme'),
  		  'sidebar-menu' => __('Sidebar Menu','iftheme'),
  		  'footer-menu' => __('Footer Menu','iftheme'),
  		)
  	);
}
add_action( 'init', 'iftheme_menu_register' );
*/

/*
 * CATEGORIES
 */
 
//fix for categories 
define('IF_CATEGORY_FIX_FIELDS', 'my_category_fields_option');

// your fields (the form)
function if_fix_category_fields($tag) {
    global $current_user; get_currentuserinfo();
    $tag_extra_fields = get_option(IF_CATEGORY_FIX_FIELDS);
    
    ?>

<table class="form-table">
        <tr class="form-field">
            <td><input name="if_fix_field" id="if_fix_field" type="hidden" aria-required="false" value="<?php echo $current_user->ID; ?>" />
        </tr>
</table>

    <?php
}
add_filter('edit_category_form', 'if_fix_category_fields');


// when the form gets submitted, and the category gets updated (in your case the option will get updated with the values of your custom fields above
function update_my_category_fields($term_id) {
  if($_POST['taxonomy'] == 'category'):
    $tag_extra_fields = get_option(IF_CATEGORY_FIX_FIELDS);
    $tag_extra_fields[$term_id]['if_fix_field'] = strip_tags($_POST['if_fix_field']);
    update_option(IF_CATEGORY_FIX_FIELDS, $tag_extra_fields);
  endif;
}
add_filter('edited_terms', 'update_my_category_fields');

// when a category is removed
function remove_my_category_fields($term_id) {
  if($_POST['taxonomy'] == 'category'):
    $tag_extra_fields = get_option(IF_CATEGORY_FIX_FIELDS);
    unset($tag_extra_fields[$term_id]);
    update_option(IF_CATEGORY_FIX_FIELDS, $tag_extra_fields);
  endif;
}
add_filter('deleted_term_taxonomy', 'remove_my_category_fields');

//get site language in short code
function get_site_lang() {
  //to realize if the actual php version ist newer than '5.3'
  if (strnatcmp(phpversion(),'5.3.0') >= 0) {
      $lg = strstr(get_bloginfo('language'), '-', TRUE);
  }
  else {
    $haystack = get_bloginfo('language');
    $needle = '-';
    $result = substr($haystack, 0, strpos($haystack, $needle)); // $result = php
  }

  $lg = $lg ? $lg : 'fr'; //if nothing is returned best to return at least 'fr'. We're assuming that default language is french...

  return $lg;
}


//get level 1 (key=0) categories.
function get_if_top_categ($args=array()){
	
	$default_args = array(
		'hide_empty' => 0,
		'use_desc_for_title' => 0,
		'title_li' => '',
		'child_of' => 0,
		'depth' => 1,
    );
    
    if(!empty($args) && is_array($args)) {
	    $default_args = array_merge($default_args,$args);
    }
	
	wp_list_categories($default_args);
}
//get level 2 (key=1) categories.
function get_if_level2_categ($raw = false, $args = array()) {
	
	$default_args = array(
		'hide_empty' => 0,
		'use_desc_for_title' => 0,
		'title_li' => '',
		'child_of' => get_current_parent_categ(),
		'depth' => 2
    );
    
    if(!empty($args) && is_array($args)) {
	    $default_args = array_merge($default_args,$args);
    }
	if (!$raw) {
  	wp_list_categories($default_args);
	}
	else {
  	$default_args = array(
  		'hide_empty' => 0,
  		'parent' 	 => get_current_parent_categ(),
    );
  	return get_terms( 'category', $default_args);
	}
	
}

function str_lreplace($search, $replace, $subject) {
    return preg_replace('~(.*)' . preg_quote($search, '~') . '~', '$1' . $replace, $subject, 1);
}
//add css class to categ listing
/*
function add_markup_categories($output) { 
  $patern = '/cat-item/'; 
  $output = preg_replace($patern, ' first-cat-item cat-item', $output, 1);
  $output = substr_replace($output, " last-cat-item cat-item", strripos($output, "cat-item"), strlen("cat-item"));

  return $output;
}
add_filter('wp_list_categories', 'add_markup_categories');
*/

//current categ on single
function if_show_current_cat_on($output) {
  global $post;
  if( is_single() ) {
  $categories = wp_get_post_categories($post->ID);
  foreach( $categories as $catid ) {
    $cat = get_category($catid);
    $pcat = get_ifcat_parents($catid);

     // Find cat-item-ID in the string
     if(preg_match('#cat-item-' . $cat->cat_ID . '"#', $output)) { 
        $output = str_replace('cat-item-'.$cat->cat_ID, 'cat-item-'.$cat->cat_ID . ' current-cat', $output);
     }

    if(in_array($cat->parent, $pcat)){
      foreach ($pcat as $k => $pcid) {
        if(preg_match('#cat-item-' . $pcid . '"#', $output)) {
          $output = str_replace('cat-item-'.$pcid, 'cat-item-'.$pcid . ' current-cat-parent', $output);
        }
      }
    }
   }
/*
    if(preg_match('#cat-item-' . $cat->parent . '"#', $output)) {
        $output = str_replace('cat-item-'.$cat->parent, 'cat-item-'.$cat->parent . ' current-cat-parent', $output);
     }
*/

  }
  
  if (is_category()){
    $cid = get_query_var('cat');
    $tabcat = get_ifcat_parents($cid);
    
    foreach ($tabcat as $k => $pcid) {
      if(preg_match('#cat-item-' . $pcid . '"#', $output)) {
        $output = str_replace('cat-item-'.$pcid, 'cat-item-'.$pcid . ' current-cat-parent', $output);
      }
    }
  }
  return $output;
}

add_filter('wp_list_categories', 'if_show_current_cat_on');

function get_ifcat_parents($cid){
  $pcatz = get_category_parents($cid,false,'_/',true);
  $pcatz = explode('_/', $pcatz);
  foreach ($pcatz as $k => $slug) {
    $pcato = get_category_by_slug($slug);
    if($pcato) { 
      //$tabcat[] = $pcato; //get all cat object
      $tabcat[] = $pcato->term_id; //get cat ID
    }
  }
  
  return $tabcat;
}

//Get categ slug
function get_cat_slug($cat_id) { 
	$cat_id = (int) $cat_id;
  remove_all_filters('get_term');
	$category = get_category($cat_id);

	  if(is_object($category))
      return property_exists($category,'slug') ? $category->slug : '';
}

//Prepare vars for IF category vs antenna system
function get_cat_if_user($uid){
	$categ = 0;
	$user = get_user_meta( $uid );

	//must have assigned a category to user (cf. edit profil page)
	if(isset($user['categ_to_antenna'])) $categ = $user['categ_to_antenna'][0];
		
	//returns an ID
	return $categ;	
}

/*
 * antenna's ID depending on language
 */
function get_cat_if_user_lang($uid){
	$categ = 0;
	$user = get_user_meta( $uid );

	//must have assigned a category to user (cf. edit profil page)
	if(isset($user['categ_to_antenna'])) $categ = $user['categ_to_antenna'][0];
	
	if(defined('ICL_LANGUAGE_CODE')) $categ = icl_object_id($categ,'category',false,ICL_LANGUAGE_CODE);
	
	//returns an ID (dependant on language if any)
	return $categ;	
}
//Get info for IF Antenna
//only useful for theme option page (must be logged)
function get_antenna(){
	global $current_user; get_currentuserinfo();
	$antenna = get_cat_slug(get_cat_if_user($current_user->ID));
	
	return $antenna;
}

//Get antenna users
function get_antenna_users() {
      $ua = get_users('role=antenna');
      
      //administrator (user 1) is always an antenna
      $admin = get_users('include=1');
      $ua[] = $admin[0];
      
      return $ua;
}

//Verify if it's a Multi antennas site
function multi_antennas() {
	$output = FALSE;
	$nb = count(get_antenna_users());
	//if count(get_antenna_users()) == 1, than it means that we have only 1 antenna, the administrator one.
	if($nb>1) $output = TRUE;
	
	return $output;
}

//Get the antennas details & options settings
function get_antennas_details(){
	//get all antenna users
	$users = get_antenna_users();
	//count the number of user
	$nb = count($users);

	if($nb === 1) {//if only 1 user, we assume that it's the admin user so $user->ID = 1
		$categ_admin = get_cat_if_user(1);
		$antenna =  get_cat_slug($categ_admin);
		$options = get_option('iftheme_theme_options_' . $antenna, iftheme_get_default_theme_options() );//cf. theme-options.php for keys of the option array
		
		//adding useful infos to $options
		$options['aid'] = $categ_admin;
		$options['slug'] = $antenna;

	} else {//more than 1 user
		foreach($users as $k => $o){
			$categ = get_cat_if_user($o->ID);
			
			//$categ = function_exists('icl_object_id') ? icl_object_id($categ, 'category', TRUE) : $categ;
			$antenna =  get_cat_slug($categ) ? get_cat_slug($categ) : __('You must assign a category to this user : ','iftheme').$o->display_name;
			$options[$categ] = get_option('iftheme_theme_options_' . $antenna, iftheme_get_default_theme_options() );//cf. theme-options.php for keys of the option array

			//unset country options for non admin user
			//@todo: posibility to have multiple admin. Maybe check user's roles more then his ID
			if($o->ID != 1) { 
				unset($options[$categ]['bg_frame_country']);
				unset($options[$categ]['background_img_country']);
				unset($options[$categ]['theme_home_categ_country']);
			}
			//adding useful infos to $options
			$options[$categ]['aid'] = !$categ ? null : $categ;
			$options[$categ]['slug'] = $antenna;
		}
	}
  
  
  return $options;
}

function test(){
		$cats = get_the_category();
		return get_root_category(12);
		return get_the_category();
}
// Add specific CSS class by filter
function iftheme_body_class($classes) {
	$cid = get_current_parent_categ();
	$class = 'category-'. $cid .' black';
	
	if (is_home()) $class .= ' accueil';
	
	// add $class to the $classes array
	$classes[] = $class;
	// return the $classes array
	return $classes;
}
add_filter('body_class','iftheme_body_class');

//style for the wysiwyg
add_editor_style('style.css');

/**
 * get top level categories
 */
function get_root_category($category_id) {
  $rootID = false; //OR 1 ? > maybe admin categ ?
  //returns cat's name
	//$parent_cats = get_category_parents($category_id);
	//returns cat's slug
	$parent_cats = get_category_parents($category_id, false, '/', true);
//if( is_super_admin() ) d($parent_cats);
	if(!is_object($parent_cats)) {
	  $split_arr = explode('/', $parent_cats);
	  //$return = get_cat_id($split_arr[0]);
	  $catObj = get_category_by_slug($split_arr[0]);
	  $rootID = $catObj->term_id;
  }
  
  return $rootID;
}

//function to get depth category
function get_level($cid, $level = 0) {
	$max_depth_to_test = intval(9); //set this to highest level you might have
	$last_depth = 0; //top level
	$cat = get_category($cid);

    if ($cat->category_parent == 0) {
        return $level;
    } else {
		for ( $i = 1; $i <= $max_depth_to_test; $i += 1) {
		  if ($cat->category_parent) {
		    $cat = get_category($cat->category_parent);
		    $last_depth = $i;
		  }
		}
		//$last_depth +=1;
		$level = $last_depth;
    }
	return $level;
}

//get current top level category/antenna
function get_current_antenna(){
	global $sitepress;
	$default_lg = isset($sitepress) ? $sitepress->get_default_language() : get_site_lang();
	
	$categ_admin = get_cat_if_user(1) != 0 ? get_cat_if_user(1) : 1;
	$current_id = function_exists('icl_object_id') ? icl_object_id($categ_admin, 'category', true, $default_lg) : $categ_admin;//default category

	if(is_category()) {
		//get root category (antenna)
	  $current_id = defined('ICL_LANGUAGE_CODE') ? icl_object_id(get_root_category(get_query_var('cat')),'category',true,$default_lg) : get_root_category(get_query_var('cat'));

		//$current_id = function_exists('icl_object_id') ? icl_object_id($current_id, 'category', true, $default_lg) : $current_id;
	} elseif(is_single()){
		//get the category id of post
		$cats = get_the_category();
    //return default categ if none found
    if( empty($cats) ) return $categ_admin;
//     if( empty($cats) ) return 'front';
    //get root category (antenna)
		//if post has multiple categories, no problem we only need to get the root categ.
		$current_id = defined('ICL_LANGUAGE_CODE') ? icl_object_id(get_root_category($cats[0]->term_id),'category',true,$default_lg) : get_root_category($cats[0]->term_id);
	}

	return	$current_id;
}

//get current top level category
function get_current_parent_categ(){
	global $sitepress;
	$default_lg = isset($sitepress) ? $sitepress->get_default_language() : 'fr';//assuming that 'fr' should be default language
	
  
  $check_top_categ = get_terms( 'category', 'parent=0&hide_empty=0' );
  //default is category (or translation) from admin (user 1)
	$categ_admin = get_cat_if_user(1) != 0 ? get_cat_if_user(1) : 1;
	$current_id = function_exists('icl_object_id') ? icl_object_id($categ_admin, 'category', true) : $categ_admin;//default category
	
	if(is_category()) {
		//get root category (antenna)
	  $current_id = get_root_category(get_query_var('cat'));

		//$current_id = function_exists('icl_object_id') ? icl_object_id($current_id, 'category', true, $default_lg) : $current_id;
	} elseif(is_single()){
		//get the category id of post
		$cats = get_the_category();
    if( empty($cats) ) return $categ_admin;
//     if( empty($cats) ) return 'front';
		//get root category (antenna)
		//if post has multiple categories, no problem we only need to get the root categ.
		$current_id = get_root_category($cats[0]->term_id);
	}

	return	$current_id;
}

//get meta data from category
function get_categ_data($cid){
	$data['img'] = get_tax_meta($cid,'categ_img');
	$data['children'] = get_tax_meta($cid,'categ_children');
	$data['posts'] = get_tax_meta($cid,'categ_posts');
	
	return $data;
}

//get slug in default language -- OBSOLETE --- use get_cat_slug() !!!
function get_category_slug($id) {
    global $wpdb;
    $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = {$id}");
    if ($term_id) {
        return $wpdb->get_var("SELECT slug FROM {$wpdb->prefix}terms WHERE term_id = {$term_id}");
    } else {
        return null;
    }
}

/**
 * Tests if any of a post's assigned categories are descendants of target categories
 *
 * @param int|array $cats The target categories. Integer ID or array of integer IDs
 * @param int|object $_post The post. Omit to test the current post in the Loop or main query
 * @return bool True if at least 1 of the post's categories is a descendant of any of the target categories
 * @see get_term_by() You can get a category by name or slug, then pass ID to this function
 * @uses get_term_children() Passes $cats
 * @uses in_category() Passes $_post (can be empty)
 * @version 2.7
 * @link http://codex.wordpress.org/Function_Reference/in_category#Testing_if_a_post_is_in_a_descendant_category
 */
if ( ! function_exists( 'post_is_in_descendant_category' ) ) {
	function post_is_in_descendant_category( $cats, $_post = null ) {
		foreach ( (array) $cats as $cat ) {
			// get_term_children() accepts integer ID only
			$descendants = get_term_children( (int) $cat, 'category' );
			if ( $descendants && in_category( $descendants, $_post ) )
				return true;
		}
		return false;
	}
}

/*
 * ADMIN
 */
//Add css&js files to admin 
function load_custom_wp_admin_style(){
        wp_register_style( 'custom_wp_admin_css', get_bloginfo('stylesheet_directory') . '/inc/if-admin-style.css', false, '1.0.0' );
        wp_enqueue_style( 'custom_wp_admin_css' );
        wp_register_script( 'custom_wp_admin_js', get_bloginfo('stylesheet_directory') . '/inc/if-admin-script.js', false, '1.0.0' );
        wp_enqueue_script( 'custom_wp_admin_js' );
        
        $test_user_categ = iftheme_categtouser_error_notice(true);
        if($test_user_categ){ 
          $params = array('id' => 'submit');
          wp_localize_script( 'custom_wp_admin_js', 'ifAdmin', $params );
        }
}
add_action('admin_enqueue_scripts', 'load_custom_wp_admin_style');

//Add inline js to admin
function load_inline_js_to_admin(){
	echo '<script type="text/javascript"> var templateDir = "'.get_template_directory_uri().'"</script>';

}
add_action('admin_head', 'load_inline_js_to_admin');

//Add inline css to admin for displaying right widget sidebar zone to users
function load_inline_css_to_admin(){
	global $current_user; get_currentuserinfo();
	$out = '<style type="text/css">';
	$out .= '#widgets-right .widgets-holder-wrap {display:none}';
	$out .= $current_user->ID == 1 ? '#widgets-right .widgets-holder-wrap {display:block}':'#widgets-right .widgets-holder-wrap.sidebar-'.get_cat_if_user($current_user->ID).' {display:block}';
	
	$out .= '</style>';

	echo $out;
}
add_action('admin_head', 'load_inline_css_to_admin');

//Add theme  JS
function if_scripts() {
	wp_enqueue_script("jquery");
	wp_enqueue_script('chosen',	get_template_directory_uri() . '/js/chosen/chosen.jquery.js');
    wp_register_style( 'chosen_css', get_bloginfo('stylesheet_directory') . '/js/chosen/chosen.css', false, '1.0.0' );
    wp_enqueue_style( 'chosen_css' );
  
  $script = is_rtl() ? 'if-script-rtl' : 'if-script';
	wp_enqueue_script($script,	get_template_directory_uri() . '/js/'. $script .'.js',	array('jquery'));
  
  $varForJS = array(
    'select_txt' => '-- ' . __('Select' , 'iftheme') . ' --',
  );
  wp_localize_script( $script, 'ifvarJS', $varForJS );
	
	wp_enqueue_script('if-ajax',	get_template_directory_uri() . '/inc/calendar/ajax.js');
}
add_action('wp_enqueue_scripts', 'if_scripts');


//columns for posts
function if_manage_post_columns( $columns ) {
  //unset($columns['date']);
  return $columns;
}

//columns for categories
function if_manage_categ_columns($columns) {
	//hide categ description column
	unset($columns['description']);
	
	//add categ image column
	$columns['categ_image'] = __('Image','iftheme');
	
	return $columns;
}


function manage_category_custom_fields($val, $column_name, $term_id) {
	if ($column_name == 'categ_image') {
		$cat_data = get_tax_meta($term_id,'categ_img');
		//array key : id,src
		if(isset($cat_data['src'])) echo '<img src="'.$cat_data['src'].'" alt="'.get_cat_name($term_id).'" width="50" />';
	}
}
add_action('manage_category_custom_column','manage_category_custom_fields',10,3);

function if_restrict_categories($categories) {
	global $current_user; get_currentuserinfo();
	
	$a = get_cat_if_user_lang($current_user->ID);

	$onPostPage = (strpos($_SERVER['PHP_SELF'], 'edit-tags.php'));
	
	if (is_admin() && $onPostPage && !current_user_can('level_10')) {
	//if (is_admin() && $onPostPage) {
		$size = count($categories);

		for ($i = 0; $i < $size; $i++) {
		 if(is_object($categories[$i])){
			//if($categories[$i]->parent != $a && $categories[$i]->term_id != $a){
			if(!cat_is_ancestor_of($a, $categories[$i]->term_id) && $categories[$i]->term_id != $a){
				unset($categories[$i]);
			} 
		 }
		}
	}

	return $categories;
}
add_filter('get_terms', 'if_restrict_categories');

function if_column_init() {
  add_filter( 'manage_posts_columns' , 'if_manage_post_columns' );
  add_filter( 'manage_edit-category_columns' , 'if_manage_categ_columns' );
}
add_action( 'admin_init' , 'if_column_init' );



//remove some meta box from admin post edit page
function if_remove_meta_boxes() {
    //remove_meta_box( 'submitdiv', 'post', 'normal' ); // Publish meta box
    remove_meta_box( 'commentsdiv', 'post', 'normal' ); // Comments meta box
    remove_meta_box( 'revisionsdiv', 'post', 'normal' ); // Revisions meta box
    //remove_meta_box( 'authordiv', 'post', 'normal' ); // Author meta box
    //remove_meta_box( 'slugdiv', 'post', 'normal' ); // Slug meta box
    remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' ); // Post tags meta box
    //remove_meta_box( 'categorydiv', 'post', 'side' ); // Category meta box
    //remove_meta_box( 'postexcerpt', 'post', 'normal' ); // Excerpt meta box
    //remove_meta_box( 'formatdiv', 'post', 'normal' ); // Post format meta box
    //remove_meta_box( 'trackbacksdiv', 'post', 'normal' ); // Trackbacks meta box
    //remove_meta_box( 'postcustom', 'post', 'normal' ); // Custom fields meta box
    remove_meta_box( 'commentstatusdiv', 'post', 'normal' ); // Comment status meta box
    //remove_meta_box( 'postimagediv', 'post', 'side' ); // Featured image meta box  
    //remove_meta_box( 'pageparentdiv', 'page', 'side' ); // Page attributes meta box
}
add_action( 'admin_menu', 'if_remove_meta_boxes' );

/*
 * POSTS
 */
 
/**
* Alter main query to be aware of our meta field "if_events_startdate"
* order by startdate and special treatment for end date if start date allready passed
*
*/
function if_display_posts_listing ( $query ) {

	if( $query->is_main_query() && is_category() && !is_admin() ) {
	
	$value = mktime(23, 59, 59, date('m'), date('d')-1, date('Y')); //yesterday
	$value2 = time(); //last hour of the last past 24 hours
	$value3 = mktime(0,0,0,date("m"),date("d")+1,date("Y")); //tomorrow
	$compare = '>=';
	$compare2 = '<=';
	
  $meta_query[] =
		array(
		   'key' => 'if_events_enddate',
		   'value' => strtotime('yesterday'),
		   'compare' => $compare,
		   'type' => 'numeric'
		  );
		
		$query->set( 'meta_query', $meta_query );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', 'if_events_startdate' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'if_display_posts_listing' );

/**
* Alter main query for archive pages
* to be aware of our meta field "if_events_startdate"
*/
function if_display_posts_on_archive_pages( $query ) {
	//if( $query->is_main_query() && isset($query->query['year']) && !is_admin() ) {
	if( $query->is_main_query() && $query->is_archive && !is_admin() && !$query->is_category) {

		$year = $query->query['year'];
		$month = isset($query->query['monthnum']) ? $query->query['monthnum'] : null;
		$day = isset($query->query['day']) ? $query->query['day'] : null;
		
		if($query->is_year){
			$value = array(mktime(0, 0, 0, 01, 01, $year), mktime(23, 59, 59, 12, 31, $year));
			$compare = 'BETWEEN';
			
		} else if($query->is_month){
			$next_month = sprintf("%02d",$month+1);
			$value = array(mktime(0, 0, 0, $month, 01, $year), mktime(0, 0, 0, $next_month, 01, $year));
			$compare = 'BETWEEN';
			
		} else if($query->is_day){
			$value = mktime(0, 0, 0, $month, $day, $year);
			$compare = '<=';
			$compare2 = '>=';
		}
		
		$meta_query[] =
			array(
			   'key' => 'if_events_startdate',
			   'value' => $value,
			   'compare' => $compare,
			  );
		
		if($query->is_month){
			$meta_query[] =
				array(
				   'key' => 'if_events_enddate',
				   'value' => $value,
				   'compare' => $compare,
				  );
			$meta_query['relation'] = 'OR';
		}
		
		if($query->is_day){
			$meta_query[] =
				array(
				   'key' => 'if_events_enddate',
				   'value' => $value,
				   'compare' => $compare2
				  );
			$meta_query['relation'] = 'AND';
		}
		
		$query->set( 'year','' );
		$query->set( 'monthnum', '' );
		$query->set( 'day','' );
	if(!$query->is_month){
		$query->set( 'meta_key', 'if_events_startdate' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'ASC' );
	}
		$query->set( 'posts_per_page', '10' );	
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'if_display_posts_on_archive_pages' );

//get meta data of post for display on page
function get_meta_if_post($pid = ''){
	global $post;
	$pid = !$pid ? $post->ID : $pid;
  $data['post_id'] = $pid;//for ref.
	
	setlocale(LC_ALL, get_locale());

	$meta = get_post_meta($pid);
	
	$post_categs = wp_get_post_categories($pid);
	$top_categ = get_root_category($post_categs[0]);//getting top category
  $data['antenna_id'] = $top_categ;
	
	$start = isset($meta['if_events_startdate']) ? $meta['if_events_startdate'][0] : null;
	$end = isset($meta['if_events_enddate']) ? $meta['if_events_enddate'][0] : null;
		
	$end = $end <= $start ? false : $end;

	$end = !empty($end) ? utf8_encode(strftime('%d %b',$end)) : null;
	$time = isset($meta['if_events_time']) ? $meta['if_events_time'][0] : null;
	

	$data['start'] = !empty($start) ? utf8_encode(strftime('%d %b',$start)) : null;
	$data['end'] = !$end ? (strlen($time) ? ' / '.$time : '') : ' / '.$end;  
	$data['time'] = $time;
	
	//add featured img id if exist
	$img = isset($meta['_thumbnail_id']) ?$meta['_thumbnail_id'] : array();
	$data['img'] = !empty($img) ? $img[0] : null;
	
	//booking infos
	$book =  isset($meta['if_book_enable']) ? $meta['if_book_enable'][0] : null;
	if($book == 'on'){
		$data['booking'] = $book;
		$data['book_mail'] = $meta['if_book_mail'][0];
		$data['book_desc'] = $meta['if_book_desc'][0];
	} else {
		$data['booking'] = "close";
	}
	
	//infos (about)
	$data['disciplines'] = isset($meta['if_events_disciplines']) ? unserialize($meta['if_events_disciplines'][0]) : null;
	$data['lieu'] = isset($meta['if_events_lieu']) ? $meta['if_events_lieu'][0] : null;
	$data['adresse'] = isset($meta['if_events_adresse']) ? $meta['if_events_adresse'][0] : null;
	$data['adressebis'] = isset($meta['if_events_adresse_bis']) ? $meta['if_events_adresse_bis'][0] : null;
	$data['zip'] = isset($meta['if_events_zip']) ? $meta['if_events_zip'][0] : null;
	$data['city'] = isset($meta['if_events_city']) ? $meta['if_events_city'][0] : null;
	
	$data['pays'] = isset($meta['if_events_pays']) ? $meta['if_events_pays'][0] : null;//TODO check code ISO to print Country
	
	$data['longitude'] = isset($meta['if_events_long']) ? $meta['if_events_long'][0] : null;
	$data['latitude'] = isset($meta['if_events_lat']) ? $meta['if_events_lat'][0] : null;
	$data['schedule'] = isset($meta['if_events_hour']) ? $meta['if_events_hour'][0] : null;//field schedule. not used for now.
	$data['tel'] = isset($meta['if_events_tel']) ? $meta['if_events_tel'][0] : null;
	$data['event_mail'] = isset($meta['if_events_mmail']) ? $meta['if_events_mmail'][0] : null;
	$data['link1'] = isset($meta['if_events_link1']) ? $meta['if_events_link3'][0] : null;
	$data['link2'] = isset($meta['if_events_link2']) ? $meta['if_events_link3'][0] : null;
	$data['link3'] = isset($meta['if_events_link3']) ? $meta['if_events_link3'][0] : null;

	return $data;
}
//get meta data of slider
function get_meta_slider($pid=''){
	global $post;
	$pid = !$pid ? $post->ID : $pid;
		
	$data['antenna'] = get_post_meta($pid, 'slide_antenna', false);
	$data['frontpage'] = get_post_meta($pid, 'is_country', false);
	
	//imgz
	$tab_imgz = get_post_meta($post->ID,'re_');
	
	foreach($tab_imgz[0] as $k => $vals){
		$data['slides']['slide-'.$k] = $vals;
	}
	return $data;
}
//get meta data of partners
function get_meta_partners($pid=''){
	global $post;
	$pid = !$pid ? $post->ID : $pid;
		
	$data['antenna'] = get_post_meta($pid, 'partner_antenna', false);
	
	//imgz
	$tab_imgz = get_post_meta($pid,'re_');
	
	if(!$tab_imgz[0]) { 
		$data = '<div class="msg warning">'. sprintf( __('Your content <em>Partner</em> is empty. <a href="/wp-admin/post.php?post=%s&action=edit"> >Edit</a>','iftheme') , $pid ) .'</div>'; 
	} else {
		foreach($tab_imgz[0] as $k => $vals){
			$data['partners']['part-'.$k] = $vals;
		}
	}

	return $data;
}

//for use in php
function get_meta_raw_if_post($pid=''){
	global $post;
	$pid = !$pid ? $post->ID : $pid;
	
	//setlocale(LC_ALL,'LANGUAGE CODE'); =>TODO get the current language code
	
	$start = get_post_meta($pid, 'if_events_startdate', false);
	$data['start'] = !empty($start[0]) ? $start[0] : null;

	$end = get_post_meta($pid, 'if_events_enddate', false);
	$data['end'] = !empty($end[0]) ? $end[0] : null;
	
	$time = get_post_meta($pid, 'if_events_time', false);
	/* $data['time'] = !empty($time[0]) && $time[0] != '00:00' ? $time[0] : null; */
	$data['time'] = !empty($time[0]) ? $time[0] : null;
	
	//add featured img id if exist
	$img = get_post_meta($pid, '_thumbnail_id', false);
	$data['img'] = !empty($img[0]) ? $img[0] : null;
	
	$data['title'] = get_the_title($pid);

	return $data;
}

//BOOKING FORM FUNCTION
function get_booking_form() {
	$data = get_meta_if_post();

	$sendto = $data['book_mail'];
	$desc = $data['book_desc'];
	
	if(!$sendto) {
		$msg = '<div class="warning">'. __('You must fill in an email !','iftheme') .' <a href="'. get_edit_post_link() .'">'. __('Edit this post','iftheme') .'</a></div>';
		return $msg;
	} else {
	
		$msg = '';
		
		if ($_POST) {
			if(empty($_POST['lname'])) {$err_msg .=  __('The <b>Last name</b> field is required.','iftheme') . ' <br/>';}
			if(empty($_POST['fname'])) {$err_msg .=  __('The <b>First name</b> field is required.','iftheme') . ' <br/>';}
			if(empty($_POST['bookmail'])) {$err_msg .=  __('The <b>E-mail</b>  field is required.','iftheme') . ' <br/>';} 
			else if (!is_email($_POST['bookmail'])) {$err_msg .= __('The <b>E-mail name</b> field is invalid.','iftheme') . ' <br/>';}
	
			if (empty($_SESSION['captcha']) || strtolower(trim($_REQUEST['captcha'])) != $_SESSION['captcha']) {
				$err_msg .=  __('Invalid verification code.','iftheme') . ' <br/>';
			}
	
			if(empty($err_msg)) {
				$mail_body = __('Subcription request to: ') . get_the_title($post->ID) . "\n\n";
				$mail_body .= $_POST['fname'] . ' ' . $_POST['lname'] . ' (' . $_POST['bookmail'] . ')';
				if(wp_mail($sendto, __('Subcription from IF Website','iftheme'), $mail_body)) {
					$valid_msg = __('Your request has been sent.','iftheme') . ' <br/>';
				}
			} 
			
		}
	
		
		$form = '';
		
		$form .= $desc ? '<div class="bookdesc">'.$desc.'</div>' : '';
		
		$form .= '<form action="' . get_permalink($post->ID) . '#booking-form" method="POST" id="booking-form" name="booking-form">';
		$form .= !empty ($err_msg) ? '<div class="msg warning">' . $err_msg . '</div>' : '';
		$form .= !empty ($valid_msg) ? '<div class="msg info">' . $valid_msg . '</div>' : '';
		$form .= '<input type="hidden" id="formkey" name="sendto" value="" />';
		
		$form .= '<label for="lname"><span class="label">'. __('Last name','iftheme') .'</span>&nbsp;<input type="text" id="lname" name="lname" value="" class="book-text" /></label>';
		$form .= '<label for="fname"><span class="label">'. __('First name','iftheme') .'</span>&nbsp;<input type="text" id="fname" name="fname" value="" class="book-text" /></label>';
		$form .= '<label for="bookmail"><span class="label">'. __('E-mail','iftheme') .'</span>&nbsp;<input type="text" id="bookmail" name="bookmail" value="" class="book-text" /></label>';
		$form .= '<div id="captcha-container" class="clearfix"><img src="' . get_bloginfo('template_url') . '/inc/captcha/captcha.php" id="captcha-img" />&nbsp;<label for="captcha"><span class="label">'. __('Copy verification code','iftheme') .'</span>&nbsp;<input type="text" id="captcha" name="captcha" autocomplete="off" value="" class="book-text" /></label></div>';
		
		$form .= '<input type="submit" id="booksubmit" value="'. __('Submit') .'" class="book-submit" />';
		
		$form .= '</form>';
		
		return $form;

	}
}
/*
 * Add custom Post type
 */

function create_post_type() {
	// for homepages sliders
	register_post_type( 'if_slider',
		array(
			'labels' => array(
				'name' => __( 'Sliders','iftheme' ),
				'singular_name' => __( 'Slider','iftheme' ),
				'add_new_item' => __( 'Add new slider', 'iftheme' ),
				'edit_item' => __( 'Edit slider', 'iftheme' ),
				'new_item' => __( 'New slider', 'iftheme' ),
				'view_item' => __( 'View slider', 'iftheme' ),
				'search_items' => __( 'Search sliders', 'iftheme' ),
				'not_found' => __( 'No slider found', 'iftheme' ),
				'not_found_in_trash' => __( 'No sliders found in Trash', 'iftheme' ),
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'slider'),
			'menu_icon' => 'dashicons-format-gallery'
		)
	);
	
	//for Partners (Partenaires)
	register_post_type( 'if_partner',
		array(
			'labels' => array(
				'name' => __( 'Partners','iftheme' ),
				'singular_name' => __( 'Partner','iftheme' ),
				'add_new_item' => __( 'Add new partners', 'iftheme' ),
				'edit_item' => __( 'Edit partner', 'iftheme' ),
				'new_item' => __( 'New partner', 'iftheme' ),
				'view_item' => __( 'View partner', 'iftheme' ),
				'search_items' => __( 'Search partners', 'iftheme' ),
				'not_found' => __( 'No partners found', 'iftheme' ),
				'not_found_in_trash' => __( 'No partners found in Trash', 'iftheme' ),
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'partner'),
			'menu_icon' => 'dashicons-share-alt'
		)
	);
}
add_action( 'init', 'create_post_type' );


// Disables textarea form
function if_hide_stuff() {
 	global $post_type;
 	if($post_type == 'if_slider' || $post_type == 'if_partner'){
		remove_action( 'media_buttons', 'media_buttons' );
		remove_meta_box('postimagediv', $post_type, 'side');
		$if_hide_postdiv = '<style type="text/css"> #postdiv, #postdivrich { display: none; }</style>';
		print($if_hide_postdiv);
	}
}
add_action( 'admin_head', 'if_hide_stuff'  );


// hook search form
function if_search_form( $form ) {

    $form = '<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" ><div class="search-container"><input type="text" value="' . get_search_query() . '" name="s" id="s" class="type-text" /><input type="submit" id="searchsubmit" value="'. esc_attr__('OK','iftheme') .'" /></div></form>';

    return $form;
}
add_filter( 'get_search_form', 'if_search_form' );
 

//Remove some admin stuff
function remove_editor_menu() {
  remove_action('admin_menu', '_add_themes_utility_last', 101);
}
add_action('_admin_menu', 'remove_editor_menu', 1);

function if_remove_menu_pages() {
	global $current_user; get_currentuserinfo();
	//all users
	remove_menu_page('edit-comments.php');
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
	
	//all but admin
	if($current_user->ID != 1) {
		remove_submenu_page( 'edit.php','edit-tags.php?taxonomy=post_tag' );
		remove_submenu_page( 'themes.php', 'themes.php' );
		remove_menu_page('tools.php');
		remove_menu_page('users.php');
		remove_menu_page('profile.php');
	}
}
add_action( 'admin_menu', 'if_remove_menu_pages' );

/*
 * IMAGES
 */
 
if ( function_exists( 'add_theme_support' ) ) {
	add_theme_support( 'post-thumbnails' );
    //set_post_thumbnail_size( 150, 150 ); // default Post Thumbnail dimensions   
}

if ( function_exists( 'add_image_size' ) ) { 
	//home categories block
	add_image_size( 'home-block', 420, 9999 ); //310 pixels wide (and unlimited height)
	//listing post under category
	add_image_size( 'listing-post', 290, 9999 ); //200 pixels wide (and unlimited height)
	//category image
	add_image_size( 'categ-img', 630, 310, true); //(cropped)
	//post image
	add_image_size( 'post-img', 630, 9999, false); //(not cropped)
	//slider
	add_image_size( 'slider', 630, 290, true); //(cropped)
	//partners
	add_image_size( 'partner', 220, 110); //220 pixels wide 110 pixels height)
}

 
/*
 * WIDGETS
 */
// unregister all default WP Widgets
function unregister_default_wp_widgets() {
    unregister_widget('WP_Widget_Pages');
    unregister_widget('WP_Widget_Calendar');
    unregister_widget('WP_Widget_Archives');
    unregister_widget('WP_Widget_Links');
    unregister_widget('WP_Widget_Meta');
    unregister_widget('WP_Widget_Search');
    unregister_widget('WP_Widget_Text');
    unregister_widget('WP_Widget_Categories');
    unregister_widget('WP_Widget_Recent_Posts');
    unregister_widget('WP_Widget_Recent_Comments');
    unregister_widget('WP_Widget_RSS');
    unregister_widget('WP_Widget_Tag_Cloud');
}
add_action('widgets_init', 'unregister_default_wp_widgets', 1);

function register_default_wp_widgets(){
	//cf.unregister_default_wp_widgets() to add the ones you need here
	register_widget('WP_Widget_Links');
    //register_widget('WP_Widget_Categories');
    register_widget('WP_Widget_Text');
    register_widget('WP_Widget_Pages');
}
add_action('widgets_init', 'register_default_wp_widgets', 1);


//Admin  messages
function showMessage($message, $errormsg = false) {
	if ($errormsg) {
		echo '<div id="message" class="error">';
	}
	else {
		echo '<div id="message" class="updated fade">';
	}

	echo "<p><strong>$message</strong></p></div>";
} 
   
function categMsg(){
   showMessage(__("You must ask your administrator to select a user's category", 'iftheme'));
}

// enables wigitized sidebars
if ( function_exists('register_sidebar') ) {

	// Header Widget
	// Location: on right side of the header
/*
	register_sidebar(array('name'=>'Header',
		'before_widget' => '<aside class="widget widget-header">',
		'after_widget' => '</aside>',
		'before_title' => '<h3>',
		'after_title' => '</h3>',
	));
*/



	// Sidebar Widget for each antenna. Default to one only.
	global $current_user; get_currentuserinfo();
	$a_users = get_antenna_users();
	

	$a = count($a_users);
	$desc = $a>=2 ?  __("This sidebar is for all antennas only. Only the admin can configure it.",'iftheme'):'';
	//$sidebar_default = $a<2 ? 'Sidebar' : 'Sidebar front';
	register_sidebar(array(
	  'name'=> 'Sidebar',
		'id' => 'sidebar-default',
		'description' => $desc,
		'before_widget' => '<aside id="%1$s" class="widget-area widget-sidebar bxshadow %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3>',
		'after_title' => '</h3>',
	));
	
	foreach($a_users as $k => $o){
		if($a >= 2) {
			$user_categ = get_cat_if_user($o->ID);
			if($user_categ == 0) {
				add_action('admin_notices', 'categMsg');
			} else {
				register_sidebar(array(
				  'name'=>'Sidebar '. get_cat_slug($user_categ),
					'id' => 'sidebar-'.$user_categ,
					'description' => sprintf( __("This sidebar is for %s only" , 'iftheme') , get_cat_slug($user_categ) ),
					'before_widget' => '<aside id="%1$s" class="widget-area widget-sidebar bxshadow %2$s">',
					'after_widget' => '</aside>',
					'before_title' => '<h3>',
					'after_title' => '</h3>',
				));
			}
			
		}
	}


	// Footer Widget
	// Location: at the right of the footer, next to the logo
	register_sidebar(array(
	  'name'=>'Footer',
	  'id' => 'footer',
		'before_widget' => '<aside id="%1$s" class="widget-area widget-footer %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3>',
		'after_title' => '</h3>',
	));
	
		// Front Widget
	// Location: just under the slider zone on front pages
	register_sidebar(array(
	  'name'=>'Front-page',
	  'id' => 'front-page',
		'before_widget' => '<aside id="%1$s" class="widget-area widget-front %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3>',
		'after_title' => '</h3>',
	));

	// The Alert Widget
	// Location: displayed on the top of the home page, right after the header, right before the loop, within the content area
/*
	register_sidebar(array('name'=>'Alert',
		'before_widget' => '<li class="widget-area widget-alert">',
		'after_widget' => '</li>',
		'before_title' => '<h4>',
		'after_title' => '</h4>',
	));
*/
}

//custom language switcher
//Must have installed WPML plugin (cf. http://wpml.org)
if(function_exists('icl_get_languages')) {
	function languages_list_header(){ //cf. http://wpml.org/documentation/getting-started-guide/language-setup/custom-language-switcher/
	    $languages = icl_get_languages('skip_missing=0&orderby=code');

	    if(!empty($languages)){
	        
	        echo '<div id="header_language_list"><ul>';
	        
	        foreach($languages as $l){
	        	$class = $l['active'] ? 'class="active"':'';
	            echo '<li '.$class.'>';
	            if(!$l['active']) echo '<a href="'.$l['url'].'">';
	            //echo icl_disp_language($l['native_name'], $l['translated_name']);
	            echo icl_disp_language($l['language_code'],'');
	            if(!$l['active']) echo '</a>';
	            echo '</li>';
	        }
	        
	        echo '</ul></div>';
	    }
	}
}

	// adds the post thumbnail to the RSS feed
	function cwc_rss_post_thumbnail($content) {
	    global $post;
	    if(has_post_thumbnail($post->ID)) {
	        $content = '<p>' . get_the_post_thumbnail($post->ID) .
	        '</p>' . get_the_content();
	    }
	    return $content;
	}
	add_filter('the_excerpt_rss', 'cwc_rss_post_thumbnail');
	add_filter('the_content_feed', 'cwc_rss_post_thumbnail');


	// removes detailed login error information for security
	add_filter('login_errors',create_function('$a', "return null;"));
	
	// removes the WordPress version from your header for security
	function wb_remove_version() {
		return '<!--built on the Whiteboard Framework-->';
	}
	add_filter('the_generator', 'wb_remove_version');
	
	// Removes Trackbacks from the comment count
	add_filter('get_comments_number', 'comment_count', 0);
  function comment_count( $count ) {
    if ( ! is_admin() ) {
      global $id;
      $get_comments = get_comments('status=approve&post_id=' . $id);

      $comments_by_type = separate_comments($get_comments);

      return count($comments_by_type['comment']);
    } else {
      return $count;
    }
  }

	// invite rss subscribers to comment
	function rss_comment_footer($content) {
		if (is_feed()) {
			if (comments_open()) {
				$content .=  sprintf( __('Comments are open! <a href="%s">Add yours!</a>','iftheme'), get_permalink() );
			}
		}
		return $content;
	}

  if ( ! function_exists( 'iftheme_continue_reading_link' ) ) :
    /**
     * Returns a "Continue Reading" link for excerpts
     */
    function iftheme_continue_reading_link() {
    	return '<a href="'. esc_url( get_permalink() ) . '" class="read-more">'.__('Continue Reading','iftheme').' >'.'</a>';
    }
  endif; // iftheme_continue_reading_link

  /**
   * Replaces "[...]" (appended to automatically generated excerpts) with an ellipsis and iftheme_continue_reading_link().
   *
   * To override this in a child theme, remove the filter and add your own
   * function tied to the excerpt_more filter hook.
   */
  function iftheme_auto_excerpt_more( $more ) {
  	return '...' . iftheme_continue_reading_link();
  }
  add_filter( 'excerpt_more', 'iftheme_auto_excerpt_more' );
	
	// category id in body and post class
	function category_id_class($classes) {
		global $post;
		if(is_object($post)){
  		foreach(get_the_category($post->ID) as $category){
    		$classes [] = 'cat-' . $category->cat_ID . '-id';
  		}
    }
    return $classes;

	}
	add_filter('post_class', 'category_id_class');
	add_filter('body_class', 'category_id_class');

	// adds a class to the post if there is a thumbnail
	function has_thumb_class($classes) {
		global $post;
		if( has_post_thumbnail($post->ID) ) { $classes[] = 'has_thumb'; }
			return $classes;
	}
	add_filter('post_class', 'has_thumb_class');


//MISCELANEOUS
function if_login_logo() {
    echo '<style type="text/css">.login h1 a { background-image:url('.get_bloginfo('template_url').'/images/logo-if.png) !important; background-size:auto !important; width:auto} </style>';
}
add_action('login_head', 'if_login_logo');

function if_custom_logo() {
  echo '<style type="text/css"> #wp-admin-bar-wp-logo > .ab-item .ab-icon { background-image: url('.get_bloginfo('template_directory').'/images/admin-icon.png) !important; background-position: 0 0 !important; } </style>';
}
add_action('admin_head', 'if_custom_logo');

//change url on the logo login form
function if_login_logo_url(){
    return (get_bloginfo('wpurl')); 
}
add_filter('login_headerurl', 'if_login_logo_url');
	
// session handling
add_action('init', 'if_StartSession', 1);
add_action('wp_logout', 'if_EndSession');
add_action('wp_login', 'if_EndSession');

function if_StartSession() {
    if(!session_id()) {
        session_start();
    }
}

function if_EndSession() {
    session_destroy ();
}

/**
 * Create XML for mobile phones
 */
function createXML(){
  global $wpdb;
  $time = (time() - (60*60*24));//yesterday
	$query = "
		SELECT * 
		FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
		WHERE wposts.ID = wpostmeta.post_id 
		AND wposts.post_status = 'publish'
		AND wpostmeta.meta_key = 'if_events_enddate' 
		AND wpostmeta.meta_value >  $time
		AND wposts.post_type = 'post' 
		ORDER BY wpostmeta.meta_value DESC
		";
  $results = $wpdb->get_results($query); 

  $xml = '<?xml version="1.0" encoding="UTF-8"?>';
  $xml .= '<listevenements>';
  
  foreach ($results as $row){
    $meta_values = get_post_meta($row->ID);

    $xml .= '<evenement>';
    $xml .= '<id>' . $row->ID . '</id>'; //WP post id
    $xml .= '<titre>' . html_entity_decode($row->post_title) . '</titre>';
    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $row->ID ), 'post-img' );
    $xml .= '<imageurl>' . $image[0] . '</imageurl>';	//URL image à la une
    $xml .= '<videourl></videourl>';
    //$xml .= '<description>' .get_excerpt_by_id($row->ID) . '</description>'; 	// "the_excerpt" (! pas de HTML !)
    $xml .= '<description>' . strip_tags(strip_shortcodes(html_entity_decode($row->post_content,ENT_COMPAT, 'UTF-8'))) . '</description>'; 	// "the_content !!!" (! pas de HTML !)
    $xml .= '<datepub>' . mysql2date('d/m/Y',$row->post_date) . '</datepub>'; //format dd/mm/yyyy - date de publication  => post date WP
    $xml .= '<datedebut>' . mysql2date('d/m/Y', date_i18n('Y-m-d H:i:s',$meta_values['if_events_startdate'][0])) . '</datedebut>'; //date début event
		$xml .= '<datefin>' . mysql2date('d/m/Y', date_i18n('Y-m-d H:i:s',$meta_values['if_events_enddate'][0])) . '</datefin>'; //date fin event
		
		$disciplines = unserialize($meta_values['if_events_disciplines'][0]);

		$xml .= '<disciplines>';
		if($disciplines) {
  		foreach ($disciplines as $kd => $kval) { $xml .= $kval; if(($kd+1) < count($disciplines)) { $xml .= ','; } }
		} 
		$xml .= '</disciplines>';
		$xml .= '<lieu>' . $meta_values['if_events_lieu'][0] . '</lieu>';
		$xml .= '<adresse>' . $meta_values['if_events_adresse'][0] . '</adresse>';
		$xml .= '<adressecomplement>' . $meta_values['if_events_adresse_bis'][0] . '</adressecomplement>';
		$xml .= '<codepostal>' . $meta_values['if_events_zip'][0] . '</codepostal>';
		$xml .= '<ville>' . $meta_values['if_events_city'][0] . '</ville>';
		$xml .= '<pays>' . $meta_values['if_events_pays'][0] . '</pays>';
		$xml .= '<longitude>' . $meta_values['if_events_long'][0] . '</longitude>';
		$xml .= '<latitude>' . $meta_values['if_events_lat'][0] . '</latitude>';
		$xml .= '<horaires>' . $meta_values['if_events_time'][0] . '</horaires>';
		//$xml .= '<horaires>' . $meta_values['if_events_hour'][0] . '</horaires>';
		$xml .= '<tel>' . $meta_values['if_events_tel'][0] . '</tel>';
		$xml .= '<email>' . $meta_values['if_events_mmail'][0] . '</email>';
		$xml .= '<lien1>' . $meta_values['if_events_link1'][0] . '</lien1>';
		$xml .= '<lien2>' . $meta_values['if_events_link2'][0] . '</lien2>';
		$xml .= '<lien3>' . $meta_values['if_events_link3'][0] . '</lien3>';
		$xml .= '<url>' . $meta_values['if_events_url'][0] . '</url>';

    $xml .= '</evenement>'; 
  }
  
  $xml .= '</listevenements>';

  $path = 'wp-content/themes/iftheme/xml/events.xml';

  $xml = str_replace(array("&amp;", "&"), array("&", "&amp;"), $xml);
  $sxe = new SimpleXMLElement($xml);

  $sxe->asXML($path); 
}

/**
 * Cron task daily to generate the XML file
 */

if (!wp_next_scheduled('if_task_hook')) {
  wp_schedule_event( time(), 'daily', 'if_task_hook' );
}
add_action ( 'if_task_hook', 'createXML' );
//add_action ( 'init', 'createXML' );

/**
 * Useful function for createXML function.
 * Can be used some other places ;-)
 */
function get_excerpt_by_id($post_id){
  $the_post = get_post($post_id); //Gets post ID
  $the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
  $excerpt_length = 35; //Sets excerpt length by word count
  $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
  $words = explode(' ', $the_excerpt, $excerpt_length + 1);
  if(count($words) > $excerpt_length) :
  array_pop($words);
  array_push($words, '…');
  $the_excerpt = implode(' ', $words);
  endif;
  //$the_excerpt = '<p>' . $the_excerpt . '</p>';//no need of hmtl tags
  return $the_excerpt;
}

function createMenu(){
  return wp_create_nav_menu('if_menu_fix');
}
add_action ( 'init', 'createMenu' );

/**
 * Function to hide update notices
 *
 * Comment or Uncomment to show/hide notices
 */
/*
function wphidenag() {
  remove_action( 'admin_notices', 'update_nag', 3 );
}
add_action('admin_menu','wphidenag');
*/

/**
 * function curl
 *
 * Alternative à file_get_contents()
 */
function curl_get($url, array $get = null, array $options = array()) {    
    $defaults = array( 
        CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get), 
        CURLOPT_HEADER => 0, 
        CURLOPT_RETURNTRANSFER => TRUE, 
        CURLOPT_TIMEOUT => 4 
    ); 
    
    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
}

if ( ! function_exists( 'iftheme_content_nav' ) ) :
/**
 * Displays navigation to next/previous pages when applicable.
 *
 * FROM Twenty Twelve 1.0 ;-)
 */
function iftheme_content_nav( $html_id, $archives = TRUE ) {
	global $wp_query;
	$html_id = esc_attr( $html_id );
	
	if ( $wp_query->max_num_pages >= 1 ) : ?>
	<?php 
	  //Archives pages query
	  $archive_query = new WP_Query(array(
      'post_type'  => 'page',  //overrides default 'post'
      'posts_per_page' => 1, //get the latest one because Archives page should be unique.
      'meta_key'   => '_wp_page_template',
      'meta_value' => 'archives-page.php'
    ));
    // The Loop
    if ( $archive_query->have_posts() ) {
     	while ( $archive_query->have_posts() ) {
    		$archive_query->the_post();
        //$post_language_information = wpml_get_language_information(get_the_ID());
        $archivesID = get_the_ID();
        
        //get category we are in to pass it to archives page
        $cat = !is_integer(get_query_var('cat')) ? 'all' : get_query_var('cat');

        $link_to_archives = add_query_arg('ifcat', $cat, get_permalink( $archivesID ));
        $categ = $cat != 'all' ? get_category($cat) : null;
        $link_title = $categ ? $categ->name : '';
    	}
    } else {
    	// no posts found
      $archivesID = FALSE;
    }
    /* Restore original Post Data */
    wp_reset_postdata();

  	$prev = get_previous_posts_link();
  	$prev_link = !$prev && $archivesID && $archives ? '<a href="' . $link_to_archives . '">' . __( '<span class="meta-nav">&larr;</span> Archives', 'iftheme' ) . ' ' . $link_title . '</a>' : get_previous_posts_link( __( '<span class="meta-nav">&larr;</span> Previous', 'iftheme' ) );
	?>
		<nav id="<?php echo $html_id; ?>" class="navigation" role="navigation">
			<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentytwelve' ); ?></h3>
			<div class="nav-next alignleft"><?php echo $prev_link ?></div>
			<div class="nav-previous alignright"><?php next_posts_link( __( 'Next <span class="meta-nav">&rarr;</span>', 'iftheme' ) ); ?></div>
		</nav><!-- #<?php echo $html_id; ?> .navigation -->
	<?php endif;
}
endif;

add_filter( 'term_description', 'shortcode_unautop');
add_filter( 'term_description', 'do_shortcode' );
 
