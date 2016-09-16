<?php 
/*
 * Plugin Name: IF Links
 * Description: Displays links relative to the Institut Français
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
 
class If_Mobile extends WP_Widget {
	
	function __construct() {
		$widget_ops = array(
			'classname' => 'ifmobile',
			'description' => __("Widget for the Institut Français Mobile app.",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifmobile-widget'
		);
// 		$this->WP_Widget('ifmobile-widget', __('Institut Français Mobile', 'iftheme'), $widget_ops, $control_ops);
    parent::__construct( 
        'ifmobile-widget' , 
        __('Institut Français Mobile', 'iftheme'), 
        $widget_ops, 
        $control_ops 
    );
	}
	
	function form ($instance) {
  	$links = IFMobile_links();
		// prints the form on the widgets page
		$defaults = array('title'=>__("IFMobile",'iftheme'), 'links' => $links);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','iftheme')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20" />
		</p>
		<?php $i = 0; 
		  foreach ($links as $url => $link_title) : ?> 
  		  <p>
        	<label for="<?php echo $this->get_field_id('links').'-'.$i.'-url'; ?>"><?php _e('URL for','iftheme')?> <?php echo $links[$i]['label'] ?></label>
        	<input type="text" name="<?php echo $this->get_field_name('links').'['.$i.']'.'[url]' ?>" id="<?php echo $this->get_field_id('links').'-'.$i.'-url'; ?>" value="<?php echo $instance['links'][$i]['url'] ?>" style="width:100%" /><br />
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

	function widget ($args, $instance) {
  // used when the sidebar calls in the widget
		
		extract($args);

		$title = apply_filters('widget_title', isset($instance['title']) ? $instance['title'] : __("IFMobile",'iftheme'));
  	$links = IFMobile_links();
  	$lang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : get_site_lang();

		//add class to my widget
/*
		$widgetclass = $args['class'];
		if( strpos($before_widget, 'class') === false ) {
      $before_widget = str_replace('>', 'class="'. $widgetclass . '"', $before_widget);
    }
    // there is 'class' attribute - append width value to it
    else {
      $before_widget = str_replace('class="', 'class="'. $widgetclass . ' ', $before_widget);
    }
*/


		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title;?>
		<div class="ifmobile-content">
  		<p><?php printf(__('Download IFmobile app to get notified about all the events of the <strong>Institut français %s </strong>', 'iftheme'), get_bloginfo('description'));?></p>
    		<?php $lz = $instance ? $instance['links'] : IFMobile_links();
    		  foreach ($lz as $k => $tab) :
    		    $classe = 'ifmobile'; 
    		    if($k==1) $classe = 'apple';
    		    if($k==2) $classe = 'google';
    		?>
    		  <?php echo $k == 1 ? '<div class="ifmobile-apps">' : '';?>
    		    <a class="<?php echo $classe;?>" href="<?php echo $tab['url'];?>" title="<?php echo $links[$k]['label'];?>" target="_blank">
      		    
      		    <?php if($k == 0): //IFMOBILE webpage ?><img src="<?php echo get_bloginfo('stylesheet_directory') . '/inc/images/ifmobile-widget.png'?>" alt="IFmobile" /><?php printf(__('To know more about %s', 'iftheme'), $links[$k]['label']);?><br /><?php endif; ?>
      		    
      		    <?php if($k == 1): //APPLE STORE. Attention Apple seams to have particular country code to there badges... ?><img src="<?php echo get_bloginfo('stylesheet_directory') . '/inc/images/apple-badges/Download_on_the_App_Store_Badge_' . strtoupper($lang) . '_135x40.png';?>" alt="<?php _e('Download on the App Store', 'iftheme');?>" /><?php endif; ?>
      		    
      		    <?php if($k == 2): //GOOGLE PLAY to have more badges cf: https://play.google.com/intl/en_us/badges/?>
      		      <img src="<?php echo get_bloginfo('stylesheet_directory') . '/inc/images/google/google-play-badge_' . $lang . '.png'?>" alt="<?php _e('Get it on Google Play','iftheme');?>" />
      		    <?php endif; ?>
            </a>
    		  <?php echo $k == 2 ? '</div>' : '';?>
    		<?php endforeach;?>
		</div>
		<?php echo $after_widget;
	}
}//end If_Mobile

function ifmobile_load_widgets() {
	register_widget('If_Mobile');
}

/*
 * help function for mobile links
 */
function IFMobile_links(){ //do not re-order please!
  	  $links =  array(
	    array(
	      'url' => 'http://www.institutfrancais.com/ifmobile-pour-smartphones-et-tablettes',
	      'label' => 'IFmobile',
	    ),
	    array(
	      'url' => 'https://itunes.apple.com/fr/artist/institut-francais/id563546984',
	      'label' => 'Apple store app.',
	    ),
	    array(
	      'url' => 'https://play.google.com/store/apps/details?id=com.mooveatis.ifmobile',
	      'label' => 'Google Play app.',
	    ),
	  );
	  return $links;
}

add_action('widgets_init', 'ifmobile_load_widgets');

?>
