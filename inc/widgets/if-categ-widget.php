<?php 
/*
 * Plugin Name: IF Category children
 * Description: Displays category's children
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
 
class If_Antenna_Categ extends WP_Widget {
	
	function __construct() {
		$widget_ops = array(
			'classname' => 'ifantennacateg',
			'description' => __("Displays a list of category's children, related to the current antenna",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifantennacateg-widget'
		);
// 		$this->WP_Widget('ifantennacateg-widget', __("Antenna's categories", 'iftheme'), $widget_ops, $control_ops);
    parent::__construct( 
        'ifantennacateg' . '-widget' , 
        __("Antenna's categories", 'iftheme'), 
        $widget_ops, 
        $control_ops 
    );
	}
	
	function form ($instance) {
		  global $current_user;
  $current_user = wp_get_current_user();

		//get antenna ID
		$aid = get_cat_if_user_lang($current_user->ID);

		//get antenna's children
		$children = get_categories('child_of='.$aid.'&hide_empty=0');

    // prints the form on the widgets page
		$catid = isset($instance['catid']) ? defined('ICL_LANGUAGE_CODE') ? icl_object_id($instance['catid'],'category',false,ICL_LANGUAGE_CODE) : $instance['catid'] : $children[0]->term_id;
		
		$defaults = array('catid' => $catid, 'title' => '');
		$instance = wp_parse_args( (array) $instance, $defaults ); 
		
		//if admin access to all categories
		if($current_user->ID == 1 ) {$aid = 0;}

		
		$args = array(
			'hide_empty' => 0,
			'hierarchical' => 1,
			'id' => $this->get_field_id('catid'),
			'name' => $this->get_field_name('catid'),
			'selected' => $catid,
			'child_of' => $aid,
			'show_option_none' => __('None','iftheme'),
		);
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20" /><div class="description"><?php _e("You can custom the widget title here. Default is the category name you choose in the Category parent field below.", 'iftheme');?></div>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('catid'); ?>"><?php _e('Category parent', 'iftheme')?></label>
			<?php wp_dropdown_categories($args); ?>
		</p>
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['catid'] = $new_instance['catid'];
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}
	
	function widget ($args,$instance) {
	// used when the sidebar calls in the widget
		extract($args);
		
		$catid = $instance['catid'];
    
    //get cat ID dependent on language
    $catid = defined('ICL_LANGUAGE_CODE') ? icl_object_id($catid,'category',false,ICL_LANGUAGE_CODE) : $catid;

		$name = get_cat_name( $catid );
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? $name : $instance['title'], $instance, $this->id_base);
		//$title = empty( $instance['title'] ) ? $name : $instance['title'];
    

		$cat_args = array(
			'title_li' => '',
			'use_desc_for_title' => 0,
			'hide_empty' => 0,
			'child_of' => $catid,
			'depth' => 1
		);
		
		//$before_widget = '<aside class="widget-area widget-header ifworldlinks">';

		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title; ?>
		<ul><?php  echo wp_list_categories($cat_args);?></ul>
		<?php echo $after_widget;
	}
}

function ifantenna_categ_load_widgets() {
	register_widget('If_Antenna_Categ');
}

add_action('widgets_init', 'ifantenna_categ_load_widgets');

/*
function ifantenna_categ_shortcode ($atts) {
  ob_start(); // Start capture 
  // Using the_widget() to make a plugin template tag 
  the_widget(
    If_Antenna_Categ,
    $instance = shortcode_atts( array(
          'title' => get_cat_name( get_query_var('cat') ),
          'catid' => get_query_var('cat'),
    ), $atts ),
    $args = array (
		'before_widget' => '<aside class=" widget-sidebar bxshadow ifworldlinks">',
		'after_widget' => '</aside>',
		'before_title' => '<h3>',
		'after_title' => '</h3>'
    )
  );
  $ifantenna_categ_output = ob_get_contents(); // Captured output 
  ob_end_clean(); // Stop capture 
   
  return $ifantenna_categ_output;
}
add_shortcode( 'ifantenna_categ', 'ifantenna_categ_shortcode' );
*/
?>
