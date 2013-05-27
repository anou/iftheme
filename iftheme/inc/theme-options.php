<?php
/**
 * Institut Français Theme Options
 * inspired by Institut Français Theme Options file. Thanks !
 */

//declare a global var to know if we have a simple or multi-antennas website
global $multi;
$multi = multi_antennas();

//set a prefix for country's options
global $pays;
if($multi) $pays = 'country';

//set a global var section for country's options
global $section;
$section = FALSE;
/**
 * Properly enqueue styles and scripts for our theme options page.
 *
 * This function is attached to the admin_enqueue_scripts action hook.
 */
function iftheme_admin_enqueue_scripts( $hook_suffix ) {
	wp_enqueue_style( 'iftheme-theme-options', get_template_directory_uri() . '/inc/theme-options.css', false, '2012-07-03' );
	wp_enqueue_script('media-upload');
	wp_enqueue_script('thickbox');
	wp_enqueue_script( 'iftheme-theme-options', get_template_directory_uri() . '/inc/theme-options.js', array( 'jquery','media-upload','thickbox' ), '2011-06-10' );
	wp_enqueue_style('thickbox');
	//wp_enqueue_style( 'farbtastic' );
}
add_action( 'admin_print_styles-appearance_page_theme_options', 'iftheme_admin_enqueue_scripts' );
 

/**
 * Register the form setting for our iftheme_options array.
 *
 * This function is attached to the admin_init action hook.
 *
 * This call to register_setting() registers a validation callback, iftheme_theme_options_validate(),
 * which is used when the option is saved, to ensure that our option values are complete, properly
 * formatted, and safe.
 */
function iftheme_theme_options_init() {
	global $multi;
	global $current_user; get_currentuserinfo();
  	$antenna = get_antenna();  
	register_setting(
		'iftheme_options',       // Options group, see settings_fields() call in iftheme_theme_options_render_page()
		'iftheme_theme_options_'.$antenna, // Database option, see iftheme_get_theme_options()
		'iftheme_theme_options_validate' // The sanitization callback, see iftheme_theme_options_validate()
	);

	// Register our settings field group
	add_settings_section(
		'general', // Unique identifier for the settings section
		__('Antenna options','iftheme'), // Section title 
		'__return_false', // Section callback (we don't want anything)
		'theme_options' // Menu slug, used to uniquely identify the page; see iftheme_theme_options_add_page()
	);
	
	if($multi && $current_user->ID === 1) {
		//adding a section for the Country hompage if exist (multi-antenna site)
		add_settings_section(
			'homepage', // Unique identifier for the settings section
			__('Country homepage','iftheme'), // Section title
			'country_section_callback', // Section callback 
			'theme_options' // Menu slug, used to uniquely identify the page; see iftheme_theme_options_add_page()
		);
	}
	// Add the section to theme options settings so we can add our fields to it.
	//Only for admin (user 1) -- SPECIAL SETTINGS
	if($current_user->ID === 1) {
	  add_settings_section('special_setting_section', __('Special settings','iftheme'), 'special_setting_section_callback_function','theme_options');
	  add_settings_field('theme_options_setting_header', __("Display header's menu pages",'iftheme'),'theme_options_setting_header_callback_function','theme_options','special_setting_section');
	  add_settings_field('theme_options_wysija_embed', __("Use wysija theme form",'iftheme'),'theme_options_setting_wysija_embed_callback_function','theme_options','special_setting_section');
	  
	  //TODO: ADD choice of bg color : array(#ADA59A,#ECB813,#FF4B00,#BAC900,#595959,#D2204C,#55BCBE,#3E647E)
  }
	//if($current_user->ID === 1) {
  add_settings_section('social_setting_section', __('Social sites on the web','iftheme'), 'social_setting_section_callback_function','theme_options');
  //}
	
	// Register our individual settings fields
	add_settings_field(
		'bg_frame',  // Unique identifier for the field for this section
		__( 'Background frame', 'iftheme' ), // Setting field label
		'iftheme_settings_field_bg_frames', // Function that renders the settings field
		'theme_options', // Menu slug, used to uniquely identify the page; see iftheme_theme_options_add_page()
		'general' // Settings section. Same as the first argument in the add_settings_section() above
	);

	add_settings_field( 'theme_home_categ', __( 'Displayed home categories', 'iftheme' ), 'iftheme_settings_field_home_categories', 'theme_options', 'general' );//categories on homepage
	add_settings_field( 'theme_home_nb_events', __( 'Number of events for each category displayed on homepage:', 'iftheme' ), 'iftheme_settings_field_nb_events', 'theme_options', 'general' );//number of posts on homepage
	add_settings_field('background_img', __('Background image','iftheme'), 'iftheme_settings_field_background_img', 'theme_options', 'general'); // Background image


	// Add the field with the names and function to use for our new
	// settings, put it in our new section
	add_settings_field('theme_options_setting_facebook', __('Facebook Page','iftheme'),'theme_options_setting_facebook_callback_function','theme_options','social_setting_section');
	
	// Register our setting so that $_POST handling is done for us and
	// our callback function just has to echo the <input>
	//register_setting('general','theme_options_setting_facebook');
	
	add_settings_field('theme_options_setting_twitter', __('Twitter Account','iftheme'),'theme_options_setting_twitter_callback_function','theme_options','social_setting_section');
	add_settings_field('theme_options_setting_googleplus', __('Google Plus Page','iftheme'),'theme_options_setting_googleplus_callback_function','theme_options','social_setting_section');
	add_settings_field('theme_options_setting_iftv', __('Your IF TV Page','iftheme'),'theme_options_setting_iftv_callback_function','theme_options','social_setting_section');

	// Register our individual settings fields for country hompage if multi-antenna site
	if($multi && $current_user->ID === 1) {
		add_settings_field('bg_frame_country', __( "Country's background frame", 'iftheme' ), 'iftheme_settings_field_bg_frames_country', 'theme_options', 'homepage' );
		add_settings_field( 'theme_home_categ_country', __( "Displayed country's homepage categories", 'iftheme' ), 'iftheme_settings_field_home_categories_country', 'theme_options', 'homepage' );//categories on homepage
	add_settings_field( 'theme_home_nb_events_country', __( "Number of events for each category displayed on country's homepage:", 'iftheme' ), 'iftheme_settings_field_nb_events_country', 'theme_options', 'homepage' );//number of posts on homepage
		add_settings_field('background_img_country', __("Country's background image",'iftheme'), 'iftheme_settings_field_background_img_country', 'theme_options', 'homepage'); // Background image
	}
}
add_action( 'admin_init', 'iftheme_theme_options_init' );

//define $section var to display different info if homepage section exist
function country_section_callback(){
	global $section;
	$section = TRUE;
	return $section;
}

/**
 * Change the capability required to save the 'iftheme_options' options group.
 *
 * @see iftheme_theme_options_init() First parameter to register_setting() is the name of the options group.
 * @see iftheme_theme_options_add_page() The edit_theme_options capability is used for viewing the page.
 *
 * By default, the options groups for all registered settings require the manage_options capability.
 * This filter is required to change our theme options page to edit_theme_options instead.
 * By default, only administrators have either of these capabilities, but the desire here is
 * to allow for finer-grained control for roles and users.
 *
 * @param string $capability The capability used for the page, which is manage_options by default.
 * @return string The capability to actually use.
 */
function iftheme_option_page_capability( $capability ) {
	return 'edit_theme_options';
}
add_filter( 'option_page_capability_iftheme_options', 'iftheme_option_page_capability' );

/**
 * Add our theme options page to the admin menu, including some help documentation.
 *
 * This function is attached to the admin_menu action hook.
 */
function iftheme_theme_options_add_page() {
	$theme_page = add_theme_page(
		__( 'Theme Options' ),   // Name of page
		__( 'Theme Options' ),   // Label in menu
		'edit_theme_options',                    // Capability required
		'theme_options',                         // Menu slug, used to uniquely identify the page
		'iftheme_theme_options_render_page' // Function that renders the options page
	);

	if ( ! $theme_page )
		return;
}
add_action( 'admin_menu', 'iftheme_theme_options_add_page' );

/**
 * Returns an array of background frames for Institut Français.
 */
function iftheme_bg_frames() {
	//check for frame images
	//this way just put frame images, via FTP, in /inc/images/frames/ to add to the option page list
	$exclude_list = array(".", "..");
	$frames = scandir(TEMPLATEPATH . '/inc/images/frames');
	$frames = array_values(array_diff($frames, $exclude_list));
	natsort($frames);
	$frames = array_values($frames);
	//count the number of files
	$nbf = count($frames);
	
	if(!empty($frames)){
		foreach($frames as $k => $file){
			$bg_frames_options['f'.($k+1)]= array(
				'value' => 'f'.($k+1),
				'label' => sprintf( __('Frame %s', 'iftheme'), ($k+1) ),
				'thumbnail' => get_template_directory_uri() . '/inc/images/frames/' . $file,
			);
		}
	} else { 
		$bg_frames_options = array(
			'f1' => array(
				'value' => 'f1',
				'label' => __( 'Frame 1', 'iftheme' ),
				'thumbnail' => get_template_directory_uri() . '/inc/images/frames/f1.png',
			),
		);
	}
	//echo '<pre>';print_r($bg_frames_options);echo '</pre>';

	return apply_filters( 'iftheme_bg_frames', $bg_frames_options );
}

/**
 * Returns an array of categories to choose for homepage for Institut Français.
 */
function iftheme_home_categories($pays=NULL) { 
	global $current_user; get_currentuserinfo();
	global $sitepress;
	$default_lg = isset($sitepress) ? $sitepress->get_default_language() : 'fr';//assuming that 'fr' should be default
	$antenna_id = get_cat_if_user($current_user->ID);
	$antenna_id = function_exists('icl_object_id') ? icl_object_id($antenna_id, 'category', TRUE) : $antenna_id; //icl_object_id(ID, type, return_original_if_missing,language_code)
	$args = $pays ? array( 'hide_empty' => 0) : array('child_of' => $antenna_id, 'hide_empty' => 0);

	//$home_categ_options = new array;
	$categories = get_categories( $args );

	foreach($categories as $category) { 
	  $level = get_level($category->cat_ID);
	  //get only second level categories
	  //if($level == 1){
	  //get all except the level 0 ones
	    if($category->parent){
      	$home_categ_options[$category->term_id] = array(
      		'value' => function_exists('icl_object_id') ? icl_object_id($category->term_id, 'category', TRUE, $default_lg) : $category->term_id,
      		'label' => $category->name,
      		'antenne' => $pays ? get_cat_name(get_root_category($category->term_id)) : NULL,
      		'level' => $level,
      	);
      	
      	if($pays) $root_categ[get_root_category($category->term_id)] = get_root_category($category->term_id);
      }
    }
    
    if($pays) foreach($root_categ as $k => $id){ unset($home_categ_options[$k]); }
    
	return apply_filters( 'iftheme_home_categories', $home_categ_options );
}




/**
 * Returns the default options for Institut Français.
 */
function iftheme_get_default_theme_options() {
	global $pays;
	
	$default_theme_options = array(
		'bg_frame' => 'f1',
		'bg_frame_country' => 'f1',
		'background_img' => get_template_directory_uri() . '/images/bg-body-if.jpg',
		'background_img_country' => get_template_directory_uri() . '/images/bg-body-if.jpg',
		'theme_options_setting_facebook' => '',
		'theme_options_setting_twitter' => '',
		'theme_options_setting_googleplus' => '',
		'theme_options_setting_iftv' => 'http://institutfrancais.tv',
		'theme_home_nb_events' => '5',
		'theme_options_setting_hmenupage' => '1',
		'theme_options_setting_wysija_embed' => '1',
	);
	
	if ($pays) {
    $default_theme_options['theme_home_nb_events_country'] = '5';
	}

	return apply_filters( 'iftheme_default_theme_options', $default_theme_options );
}


/**
 * Returns the options array for Institut Français.
 */
function iftheme_get_theme_options() {
	$antenna = get_antenna();
	return get_option( 'iftheme_theme_options_'.$antenna, iftheme_get_default_theme_options() );
}


/**
 * Renders the Background frame setting fields.
 */
function iftheme_settings_field_bg_frames($pays = NULL) {
  $antenna = get_antenna();
	$options = iftheme_get_theme_options();

	foreach ( iftheme_bg_frames() as $frame ) {
	?>
	<div class="layout image-radio-option bg-frame">
	<label class="description">
		<input type="radio" name="iftheme_theme_options_<?php echo $antenna;?>[bg_frame<?php echo $pays ? '_'.$pays : '';?>]" value="<?php echo esc_attr( $frame['value'] ); ?>" <?php checked( $pays ? $options['bg_frame_country']:$options['bg_frame'], $frame['value'] ); ?> />
		<div style="background: transparent url('<?php echo esc_url( $frame['thumbnail'] ); ?>') repeat left top; width: 130px; height: 60px;"></div>
		<span><?php echo $frame['label']; ?></span>
	</label>
	</div>
	<?php
	}
}

function iftheme_settings_field_bg_frames_country() {
	global $pays;
	iftheme_settings_field_bg_frames($pays);
}

/**
 * Renders the homepage categories setting fields.
 */
function iftheme_settings_field_home_categories($pays=NULL) {
  $antenna = get_antenna();
	$options = iftheme_get_theme_options();
	
	$categz = $pays ? iftheme_home_categories($pays) : iftheme_home_categories();

  if (!empty($categz)) { 
   foreach ( $categz as $home_categ ) {
	  $keyCateg = $pays ? 'theme_home_categ_country' : 'theme_home_categ';
	  $checked = isset($options[$keyCateg][0][$home_categ['value']]) ? $options[$keyCateg][0][$home_categ['value']] : '';
		?>
		<div class="layout image-checkbox-option theme-home-categ">
		<label class="description">
			<input type="checkbox" name="iftheme_theme_options_<?php echo $antenna;?>[theme_home_categ<?php echo $pays ? '_'.$pays : '';?>][<?php echo $home_categ['value'];?>]" value="<?php echo esc_attr( $home_categ['value'] ); ?>" <?php checked( $checked, $home_categ['value'] ); ?> />
			<span><?php echo $home_categ['label']; ?></span> <?php if(isset($home_categ['antenne'])):?><span class="small">(<?php echo $home_categ['antenne']; ?>)</span><?php endif;?>
		</label>
		</div>
		<?php
    } 
  } else { echo '<span class="warning">'.__('You must create some child categories','iftheme').' > <a href="/wp-admin/edit-tags.php?taxonomy=category">'.__('Categories').'</a></span>';}
}
function iftheme_settings_field_home_categories_country() {
	global $pays;
	iftheme_settings_field_home_categories($pays);
}

/**
 * Renders the number of event displayed on homepage settings
 */

function iftheme_settings_field_nb_events($pays=NULL) {
  $antenna = get_antenna();
	$options = iftheme_get_theme_options(); 
	$nb_events = $pays ?  $options['theme_home_nb_events_' . $pays] : $options['theme_home_nb_events'];
	
	if(!$nb_events) { 
	 $defaults = iftheme_get_default_theme_options();
	 $nb_events = $pays ?  $defaults['theme_home_nb_events_' . $pays] : $defaults['theme_home_nb_events'];
	}
	
	?>
		<div class="layout image-checkbox-option theme-home-categ">
			<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_home_nb_events<?php echo $pays ? '_'.$pays : '';?>]" type="number" step="1" min="1" id="theme_home_nb_events<?php echo $pays ? '_'.$pays : '';?>" value="<?php echo $nb_events;?>" class="small-text" />
		</div>
<?php }

function iftheme_settings_field_nb_events_country() {
	global $pays;
	iftheme_settings_field_nb_events($pays);
}

/**
 * Renders the background_img image setting fields.
 */
function iftheme_settings_field_background_img($pays = NULL) {
	global $section;
	//var_dump($section);

	$antenna = get_antenna();
	$options = iftheme_get_theme_options();
	//echo '<pre>';print_r($options);echo '</pre>';
	?>
		<div class="layout image-background_img-option background-img">
			<label for="background_img" class="description">
				<input id="background_img<?php echo $pays ? '_'.$pays:'';?>" class="background_img" type="text" size="36" name="iftheme_theme_options_<?php echo $antenna;?>[background_img<?php echo $pays ? '_'.$pays : '';?>]" value="<?php echo $pays ? esc_attr( $options['background_img_country'] ) : esc_attr( $options['background_img'] ); ?>" /><?php //TODO : hide image when uploading a new one !!!?>
				<button id="upload_image_button<?php echo $pays ? '_'.$pays : '';?>" type="button" class="upload-button"><?php _e('Upload image', 'iftheme');?></button>
				<div><?php _e('Choose your background image', 'iftheme'); ?></div>
			</label>
			
			<?php if($options['background_img'] && !$section): ?><span class="actual-img"><?php _e('Actual Image', 'iftheme');?></span><div class="bg-img-preview"><img src="<?php echo esc_attr( $options['background_img'] ); ?>" alt="" width="150" /></div> <?php endif;?>
			
			<?php if($options['background_img_country'] && $section): ?><span class="actual-img"><?php _e('Actual Image', 'iftheme');?></span><div class="bg-img-preview"><img src="<?php echo esc_attr( $options['background_img_country'] ); ?>" alt="" width="150" /></div> <?php endif;?>
			
			<button type="button" id="reset-bg-img" class="reset-bg-img"><?php _e('No image','iftheme');?></button>
		</div>
	<?php
}
function iftheme_settings_field_background_img_country() {
	global $pays;
	iftheme_settings_field_background_img($pays);
}

//---------- SPECIAL settings -------------//
// —————-Settings section callback function special settings
function special_setting_section_callback_function() {
	echo '<p><em>'.__('In this section you can custom some functionalities.','iftheme').'</em></p>';
}
//header pages menu
function theme_options_setting_header_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options();
	$defaults = iftheme_get_default_theme_options();
	
  $checked = isset($options['theme_options_setting_hmenupage']) ? $options['theme_options_setting_hmenupage'] : $defaults['theme_options_setting_hmenupage'];
?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_hmenupage]" id="theme_options_setting_hmenupage" type="checkbox"  value="1" <?php checked( $checked, 1 ); ?> />&nbsp;<span><?php _e("Check this box to display the header's pages menu", 'iftheme');?></span>
<?php 
}
//wysija form embed in theme
function theme_options_setting_wysija_embed_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options();
	$defaults = iftheme_get_default_theme_options();
	
  $checked = isset($options['theme_options_setting_wysija_embed']) ? $options['theme_options_setting_wysija_embed'] : $defaults['theme_options_setting_wysija_embed'];
?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_wysija_embed]" id="theme_options_setting_wysija_embed" type="checkbox"  value="1" <?php checked( $checked, 1 ); ?> />&nbsp;<span><?php _e("Check this box to use and display the wysija newsletter subscription form embedded in the IF theme", 'iftheme');?></span>
<?php 
}

// —————-Settings section callback function social networks
function social_setting_section_callback_function() {
	echo '<p><em>'.__('This section is where you can save the social sites where readers can find you on the Internet.','iftheme').'</em></p>';
}
function theme_options_setting_facebook_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options(); ?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_facebook]" id="theme_options_setting_facebook" type="text" value="<?php echo $options['theme_options_setting_facebook'] ;?>" />
<?php }
function theme_options_setting_twitter_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options(); ?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_twitter]" id="theme_options_setting_twitter" type="text" value="<?php echo $options['theme_options_setting_twitter'] ;?>" />
<?php }
function theme_options_setting_googleplus_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options(); ?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_googleplus]" id="theme_options_setting_googleplus" type="text" value="<?php echo $options['theme_options_setting_googleplus'] ;?>" />
<?php }
function theme_options_setting_iftv_callback_function() {
	$antenna = get_antenna();
	$options = iftheme_get_theme_options();
	$defaults = iftheme_get_default_theme_options();
	$value = (isset($options['theme_options_setting_iftv']) && !empty($options['theme_options_setting_iftv'])) ? $options['theme_options_setting_iftv'] : $defaults['theme_options_setting_iftv'];
	?>
	<input name="iftheme_theme_options_<?php echo $antenna;?>[theme_options_setting_iftv]" id="theme_options_setting_iftv" type="text" value="<?php echo $value;?>" />
<?php }

/**
 * Returns the options page for Institut Français. *********************
 */
function iftheme_theme_options_render_page() { ?>
	<div class="wrap">
		<?php screen_icon('tools'); ?>
		<?php $theme_name = function_exists( 'wp_get_theme' ) ? wp_get_theme() : get_current_theme(); ?>
		<h2><?php printf( __( '%s Theme Options', 'iftheme' ), $theme_name ); ?></h2>
		<?php settings_errors(); ?>
	<?php $opts = iftheme_get_theme_options(); ?>

		<form method="post" action="options.php" enctype="multipart/form-data">
			<?php
				settings_fields( 'iftheme_options' );
				do_settings_sections( 'theme_options' );
				submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Sanitize and validate form input. Accepts an array, return a sanitized array.
 *
 * @see iftheme_theme_options_init()
 * @todo set up Reset Options action
 */
function iftheme_theme_options_validate( $input ) { 

	$output = $defaults = iftheme_get_default_theme_options();

	// Background frame must be in our array of background frame options
	if ( isset( $input['bg_frame'] ) && array_key_exists( $input['bg_frame'], iftheme_bg_frames() ) )
		$output['bg_frame'] = $input['bg_frame'];
	
	// Background frame must be in our array of background frame options
	if ( isset( $input['bg_frame_country'] ) && array_key_exists( $input['bg_frame_country'], iftheme_bg_frames() ) )
		$output['bg_frame_country'] = $input['bg_frame_country'];

	// Home categories 
	if ( isset( $input['theme_home_categ'] ) && is_array( $input['theme_home_categ'] ) )
		$output['theme_home_categ'][] = $input['theme_home_categ'];
	//nb of events homepage
	if ( isset( $input['theme_home_nb_events'] ) && strlen($input['theme_home_nb_events']))
		$output['theme_home_nb_events'] = $input['theme_home_nb_events'];

	// Country Home categories 
	if ( isset( $input['theme_home_categ_country'] ) && is_array( $input['theme_home_categ_country'] ) )
		$output['theme_home_categ_country'][] = $input['theme_home_categ_country'];
	//nb of events country's homepage
	if ( isset( $input['theme_home_nb_events_country'] ) && strlen($input['theme_home_nb_events_country']) )
		$output['theme_home_nb_events_country'] = $input['theme_home_nb_events_country'];

	// Background image 
	if ( isset( $input['background_img'] ) )
		$output['background_img'] = $input['background_img'];
	// Country Background image 
	if ( isset( $input['background_img_country'] ) )
		$output['background_img_country'] = $input['background_img_country'];
	//header page menu
	$output['theme_options_setting_hmenupage'] = isset($input['theme_options_setting_hmenupage']) ? $input['theme_options_setting_hmenupage'] : 0;
	//wysija embedded sub. form
	$output['theme_options_setting_wysija_embed'] = isset($input['theme_options_setting_wysija_embed']) ? $input['theme_options_setting_wysija_embed'] : 0;

	if ( isset( $input['theme_options_setting_facebook'] ) )
		$output['theme_options_setting_facebook'] = $input['theme_options_setting_facebook'];
	if ( isset( $input['theme_options_setting_twitter'] ) )
		$output['theme_options_setting_twitter'] = $input['theme_options_setting_twitter'];
	if ( isset( $input['theme_options_setting_googleplus'] ) )
		$output['theme_options_setting_googleplus'] = $input['theme_options_setting_googleplus'];
	if ( isset( $input['theme_options_setting_iftv'] ) && strlen($input['theme_options_setting_iftv']) )
		$output['theme_options_setting_iftv'] = $input['theme_options_setting_iftv'];
		
	return apply_filters( 'iftheme_theme_options_validate', $output, $input, $defaults );
}



//------------------------------ TODO --------------------------------- A VOIR ------------
/**
 * Implements Institut Français theme options into Theme Customizer
 *
 * @param $wp_customize Theme Customizer object
 * @return void
 */
/*
function iftheme_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	$options  = iftheme_get_theme_options();
	$defaults = iftheme_get_default_theme_options();

	$wp_customize->add_setting( 'iftheme_theme_options[color_scheme]', array(
		'default'    => $defaults['color_scheme'],
		'type'       => 'option',
		'capability' => 'edit_theme_options',
	) );

	$schemes = iftheme_color_schemes();
	$choices = array();
	foreach ( $schemes as $scheme ) {
		$choices[ $scheme['value'] ] = $scheme['label'];
	}

	$wp_customize->add_control( 'iftheme_color_scheme', array(
		'label'    => __( 'Color Scheme', 'iftheme' ),
		'section'  => 'colors',
		'settings' => 'iftheme_theme_options[color_scheme]',
		'type'     => 'radio',
		'choices'  => $choices,
		'priority' => 5,
	) );

	// Link Color (added to Color Scheme section in Theme Customizer)
	$wp_customize->add_setting( 'iftheme_theme_options[link_color]', array(
		'default'           => iftheme_get_default_link_color( $options['color_scheme'] ),
		'type'              => 'option',
		'sanitize_callback' => 'sanitize_hex_color',
		'capability'        => 'edit_theme_options',
	) );

	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'link_color', array(
		'label'    => __( 'Link Color', 'iftheme' ),
		'section'  => 'colors',
		'settings' => 'iftheme_theme_options[link_color]',
	) ) );

	// Default Layout
	$wp_customize->add_section( 'iftheme_layout', array(
		'title'    => __( 'Layout', 'iftheme' ),
		'priority' => 50,
	) );

	$wp_customize->add_setting( 'iftheme_theme_options[theme_layout]', array(
		'type'              => 'option',
		'default'           => $defaults['theme_layout'],
		'sanitize_callback' => 'sanitize_key',
	) );
	
	$layouts = iftheme_layouts();
	$choices = array();
	foreach ( $layouts as $layout ) {
		$choices[$layout['value']] = $layout['label'];
	}

	$wp_customize->add_control( 'iftheme_theme_options[theme_layout]', array(
		'section'    => 'iftheme_layout',
		'type'       => 'radio',
		'choices'    => $choices,
	) );
}
add_action( 'customize_register', 'iftheme_customize_register' );

*/
/**
 * Bind JS handlers to make Theme Customizer preview reload changes asynchronously.
 * Used with blogname and blogdescription.
 *
 * @since Institut Français 1.3
 */
/*
function iftheme_customize_preview_js() {
	wp_enqueue_script( 'iftheme-customizer', get_template_directory_uri() . '/inc/theme-customizer.js', array( 'customize-preview' ), '20120523', true );
}
add_action( 'customize_preview_init', 'iftheme_customize_preview_js' );
*/
