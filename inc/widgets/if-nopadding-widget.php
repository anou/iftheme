<?php 
/*
 * Plugin Name: IF codeRSS
 * Description: Displays codeRSS from http://rss.institutfrancais.com.
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
 
class If_No_Padding extends WP_Widget {
	
	function If_No_Padding() {
		$widget_ops = array(
			'classname' => 'ifnopadding',
			'description' => __("Widget for IF RSS",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifnopadding-widget'
		);
		$this->WP_Widget('ifnopadding-widget', __('Institut Français RSS', 'iftheme'), $widget_ops, $control_ops);
	}
	
	function form ($instance) {
		// prints the form on the widgets page
		$defaults = array('title'=> __("IF RSS feed",'iftheme'), 'codeRSS' => '');
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','iftheme')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" />
		</p>
    <p>
      <label for="<?php echo $this->get_field_id('codeRSS'); ?>"><?php _e('Your text','iftheme')?></label><br />
      <textarea name="<?php echo $this->get_field_name('codeRSS'); ?>" id="<?php echo $this->get_field_id('codeRSS'); ?>" style="width:100%"><?php echo $instance['codeRSS']; ?></textarea>
    </p>
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
 		$instance['codeRSS'] = $new_instance['codeRSS'];
		
		return $instance;
	}

	function widget ($args, $instance) {
  // used when the sidebar calls in the widget
		
		extract($args);

		$title = apply_filters('widget_title', isset($instance['title']) ? $instance['title'] : __("IF RSS feed",'iftheme'));
  	//$codeRSS = 

		//print the widget for the widget area
		echo $before_widget;
		echo $title ? $before_title.$title.$after_title : '';
		echo $instance['codeRSS'];
		echo $after_widget;
	}
}//end If_No_Padding

function ifnopadding_load_widgets() {
	register_widget('If_No_Padding');
}

add_action('widgets_init', 'ifnopadding_load_widgets');

?>
