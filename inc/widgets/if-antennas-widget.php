<?php 
/*
 * Plugin Name: IF Antennas
 * Description: Displays all the local antennas
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
  
class If_Antennas extends WP_Widget {
	
	function If_Antennas() {
		$widget_ops = array(
			'classname' => 'ifantennas',
			'description' => __("Displays a list of all the local antennas",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifantennas-widget'
		);
		$this->WP_Widget('ifantennas-widget', __('Local antennas', 'iftheme'), $widget_ops, $control_ops);
	}
	
	function form ($instance) {
		// prints the form on the widgets page
		$defaults = array('title'=> __('Local antennas','iftheme'));
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php __('Title','iftheme')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20" />
		</p>
		<p>
			<?php __("This widget displays all the local antennas. No configuration options", 'iftheme'); ?>
		</p>
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	function widget ($args,$instance) {
	// used when the sidebar calls in the widget
		extract($args);
		
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? $name : $instance['title'], $instance, $this->id_base);
		
		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title; ?>
		<ul><?php get_if_top_categ(array('orderby' => 'name')); ?></ul>
  <?php echo $after_widget;
	}
}//end If_Antennas

function if_antennas_load_widgets() {
	register_widget('If_Antennas');
}

add_action('widgets_init', 'if_antennas_load_widgets');
?>