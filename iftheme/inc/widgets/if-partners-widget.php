<?php 
/*
 * Plugin Name: IF Partners
 * Description: Displays a list of partners form the Partner custom content type
 * Version: 1.0
 * Author: David Thomas
 * Author URI: http://www.smol.org
 * 
 */
 
class If_Antenna_Partners extends WP_Widget {
	
	function If_Antenna_Partners() {
		$widget_ops = array(
			'classname' => 'ifantennapartners',
			'description' => __("Displays a list of antenna's partners logo, related to the current antenna",'iftheme'),
		);
		$control_ops = array(
			'width' => 250,
			'height' => 250,
			'id_base' => 'ifantennapartners-widget'
		);
		$this->WP_Widget('ifantennapartners-widget', __("Antenna's partners", 'iftheme'), $widget_ops, $control_ops);
	}
	
	function form ($instance) {
		global $current_user; get_currentuserinfo();
		//get antenna ID
		$aid = get_cat_if_user($current_user->ID);
		//get antenna's partners
		$args = array(
			'post_type' => 'if_partner',
			'meta_key' => 'partner_antenna',
			'meta_value' => $aid
		);
		$partners = get_posts($args);
		
		// prints the form on the widgets page
		$defaults = array('partnersshown'=>0);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title')?></label>
			<input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20" /><div class="description"><?php _e("You can custom the widget title here. Default is the title of the selected partner.", 'iftheme');?></div>
		</p>
		<p>
		  <h4><?php _e("Select Partners:",'iftheme');?></h4>
		<?php if($partners):?>
			<?php foreach($partners as $o):?>
				<label for="<?php echo $this->get_field_id('partnersshown'); ?>">&nbsp;<input type="radio" id="<?php echo $this->get_field_id( 'partnersshown' ); ?>" name="<?php echo $this->get_field_name( 'partnersshown' ); ?>" value="<?php echo  $o->ID;?>" <?php if($instance['partnersshown'] == $o->ID):?>checked="checked"<?php endif;?> /><?php echo $o->post_title;?></label>
			<?php endforeach;?>
		<?php else:?>
			<div class="msg warning"><?php _e("You must create a Partner in order to use this widget: <a href=\"/wp-admin/edit.php?post_type=if_partner\">Partners</a>",'iftheme');?></div>
		<?php endif;?>
		</p>
		
	<?php 
	}	

	function update ($new_instance, $old_instance) {
	// used when the user saves their widget options
		$instance = $old_instance;
		$instance['partnersshown'] = $new_instance['partnersshown'];
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}
	
	function widget ($args,$instance) {
	// used when the sidebar calls in the widget
		extract($args);
		$postId = $instance['partnersshown'];

		//$postW = get_post( $postId );
		if($postId) {
			$wPartners = get_meta_partners( $postId );
			if(!is_array($wPartners)) {
			 echo $wPartners;
			 return;
			}
			//$a = $wPartners['antenna'];
			$partners = $wPartners['partners'];
			$name = get_the_title($postId);
		} else {
			$name = __('Error','iftheme');
		}
		
		
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? $name : $instance['title'], $instance, $this->id_base);

		//print the widget for the widget area
		echo $before_widget;
		echo $before_title.$title.$after_title; ?>
		<div class="partners">
            <div class="partners_container">
            <?php if(!$partners):?>
            	<div class="msg warning"><?php _e("You must choose a Partner in the widget configuration: <a href=\"/wp-admin/widgets.php\">widgets</a>",'iftheme');?></div>
            <?php else :?>
            <?php foreach($partners as $pPart => $pInfo): 
	            $img = wp_get_attachment_image_src( $pInfo['image_logo']['id'],'partner');
	            $marge = round((150 - $img[2]) /2); //150 = height of the widget block
	            $link = false;
	            if(strlen($pInfo['link_to_partner'])) {  $link = true; }
            ?>
            	<div>
            <?php if($link):?>
            	 <a href="<?php echo $pInfo['link_to_partner'];?>" target="_blank"><img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="<?php echo $pInfo['partner_title'];?>" style="margin-top:<?php echo $marge.'px';?>" /></a>
            <?php else :?>
            	 <img src="<?php echo $img[0]; ?>" width="<?php echo $img[1]; ?>" height="<?php echo $img[2]; ?>" alt="<?php echo $pInfo['partner_title'];?>" style="margin-top:<?php echo $marge.'px';?>" />
            <?php endif;?>
              </div>
            <?php endforeach;?>
            <?php endif;?>
            </div>
        </div>
		<?php echo $after_widget;
	}
}

function ifantenna_partners_load_widgets() {
	register_widget('If_Antenna_Partners');
}

add_action('widgets_init', 'ifantenna_partners_load_widgets');
?>
