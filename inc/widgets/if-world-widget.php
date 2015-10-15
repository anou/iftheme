<?php 
/*
 * Plugin Name: IF Links
 * Description: Displays links relative to the Institut Français
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
 
class If_World_Links extends WP_Widget {
	
	function __construct() {
		$widget_ops = array(
			'classname' => 'ifworldlinks',
			'description' => __("Displays a list of links related to the Institut Français",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifworldlinks-widget'
		);
// 		$this->WP_Widget('ifworldlinks-widget', __('Institut Français links', 'iftheme'), $widget_ops, $control_ops);
    parent::__construct( 
        'ifworldlinks' . '-widget' , 
         __('Institut Français links', 'iftheme'), 
        $widget_ops, 
        $control_ops 
    );
	}
	
	function form ($instance) {
  	$links = linksTab();
		// prints the form on the widgets page
		$defaults = array('title'=>__("Numeric IF",'iftheme'), 'links' => $links);
		$instance = wp_parse_args( (array) $instance, $defaults ); 
  ?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','iftheme')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" />
		</p>
		<?php $i = 0; 
		  foreach ($links as $url => $link_title) : ?> 
  		  <p>
    			<label for="<?php echo $this->get_field_id('links').'-'.$i.'-label'; ?>"><?php _e('Label','iftheme')?></label>
    			<input type="text" name="<?php echo $this->get_field_name('links').'['.$i.']'.'[label]' ?>" id="<?php echo $this->get_field_id('links').'-'.$i.'-label' ?> " value="<?php echo $instance['links'][$i]['label'] ?>"  style="width:100%" />
        	<label for="<?php echo $this->get_field_id('links').'-'.$i.'-url'; ?>"><?php _e('URL','iftheme')?></label>
        	<input type="text" name="<?php echo $this->get_field_name('links').'['.$i.']'.'[url]' ?>" id="<?php echo $this->get_field_id('links').'-'.$i.'-url'; ?>" value="<?php echo $instance['links'][$i]['url'] ?>"  style="width:100%" /><br />
  		  </p>
		<?php $i++; 
		  endforeach; ?>
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
 		$instance['links'] = $new_instance['links'];
		
		return $instance;
	}

	function widget ($args,$instance) {
	// used when the sidebar calls in the widget
		extract($args);

		$title = apply_filters('widget_title', isset($instance['title']) ? $instance['title'] : __("Numeric IF",'iftheme'));
		
		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title; ?>
		<ul class="xoxo blogroll">
  		<?php  $lz = isset($instance['links']) ? $instance['links'] : linksTab();
  		  foreach ($lz as $k => $tab) : ?>
  		<?php if(strlen($tab['url'])):?><li><a href="<?php echo $tab['url'];?>" title="<?php echo $tab['label'];?>" target="_blank"><?php echo $tab['label'];?></a></li><?php endif;?>
  		<?php endforeach;?>
		</ul>

		<?php echo $after_widget;
	}
}//end If_World_Links

function ifworld_links_load_widgets() {
	register_widget('If_World_Links');
}

function linksTab(){
  	  $links =  array(
	    array(
	      'url' => 'http://www.institutfrancais.com/ifmobile-pour-smartphones-et-tablettes',
	      'label' => 'IFmobile',
	    ),
	    array(
	      'url' => 'http://ifprog.institutfrancais.com',
	      'label' => 'IFprog',
	    ),
	    array(
	      'url' => 'http://ifmapp.institutfrancais.com',
	      'label' => 'IFmapp',
	    ),
	    array(
	      'url' => 'http://ifcinema.institutfrancais.com',
	      'label' => 'IFcinéma',
	    ),
	    array(
	      'url' => 'http://www.ifverso.com',
	      'label' => 'IFverso',
	    ),
	    array(
	      'url' => 'http://institutfrancais.tv',
	      'label' => 'Web TV',
	    ),
	    array(
	      'url' => 'http://www.institutfrancais.com',
	      'label' => 'Institut français',
	    ),
	    array(
	      'url' => '',
	      'label' => '',
	    ),
	    array(
	      'url' => '',
	      'label' => '',
	    ),
	    array(
	      'url' => '',
	      'label' => '',
	    ),
	  );
	  return $links;
}

add_action('widgets_init', 'ifworld_links_load_widgets');

?>
